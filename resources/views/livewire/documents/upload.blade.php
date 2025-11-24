<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

new class extends Component {
    use WithFileUploads;

    public ?TemporaryUploadedFile $file = null;
    public string $documentable_type = '';
    public string $documentable_id = '';
    public string $document_type = '';
    public string $title = '';
    public string $description = '';
    public string $document_number = '';
    public string $document_date = '';
    public string $document_amount = '';
    public string $issuer = '';
    public string $recipient = '';
    public bool $is_public = false;
    public bool $requires_approval = false;
    public bool $uploading = false;

    public function mount(): void
    {
        $this->documentable_type = request()->query('type', '');
        $this->documentable_id = request()->query('id', '');
    }

    public function updatedFile(): void
    {
        $this->validate([
            'file' => 'required|file|max:51200', // 50MB
        ], [
            'file.required' => 'Debe seleccionar un archivo',
            'file.max' => 'El archivo no debe exceder 50MB',
        ]);
    }

    public function upload(): void
    {
        $this->validate([
            'file' => 'required|file|max:51200',
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
            'document_type' => 'required|string|in:invoice,receipt,ccf,delivery_note,photo,contract,certificate,report,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'document_number' => 'nullable|string|max:100',
            'document_date' => 'nullable|date',
            'document_amount' => 'nullable|numeric|min:0',
            'issuer' => 'nullable|string|max:255',
            'recipient' => 'nullable|string|max:255',
        ], [
            'file.required' => 'Debe seleccionar un archivo',
            'file.max' => 'El archivo no debe exceder 50MB',
            'documentable_type.required' => 'Debe seleccionar a qué adjuntar el documento',
            'documentable_id.required' => 'Debe seleccionar el elemento específico',
            'document_type.required' => 'Debe seleccionar el tipo de documento',
            'title.required' => 'El título es requerido',
            'title.max' => 'El título no debe exceder 255 caracteres',
        ]);

        $this->uploading = true;

        try {
            $user = auth()->user();
            $extension = $this->file->getClientOriginalExtension();
            $filename = Str::slug(pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . time() . '.' . $extension;

            // Store file
            $path = $this->file->storeAs(
                'documents/' . $this->documentable_type . '/' . $this->documentable_id,
                $filename,
                'local'
            );

            // Create document record
            Document::create([
                'company_id' => $user->company_id,
                'uploaded_by' => $user->id,
                'created_by' => $user->id,
                'documentable_type' => $this->documentable_type,
                'documentable_id' => $this->documentable_id,
                'document_type' => $this->document_type,
                'title' => $this->title,
                'description' => $this->description ?: null,
                'document_number' => $this->document_number ?: null,
                'file_name' => $this->file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $extension,
                'mime_type' => $this->file->getMimeType(),
                'file_size' => $this->file->getSize(),
                'disk' => 'local',
                'document_date' => $this->document_date ?: null,
                'document_amount' => $this->document_amount ?: null,
                'issuer' => $this->issuer ?: null,
                'recipient' => $this->recipient ?: null,
                'status' => 'active',
                'is_public' => $this->is_public,
                'requires_approval' => $this->requires_approval,
            ]);

            $this->dispatch('document-uploaded', message: 'Documento subido exitosamente.');
            $this->redirectRoute('documents.index');
        } catch (\Exception $e) {
            $this->addError('file', 'Error al subir el documento: ' . $e->getMessage());
        } finally {
            $this->uploading = false;
        }
    }

    public function cancel(): void
    {
        $this->redirectRoute('documents.index');
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                Subir Documento
            </flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                Adjunta documentos a operaciones de bodega
            </flux:text>
        </div>

        <flux:button
            wire:click="cancel"
            variant="outline"
            icon="x-mark"
        >
            Cancelar
        </flux:button>
    </div>

    {{-- Upload Form --}}
    <flux:card>
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                Información del Documento
            </flux:heading>
        </div>

        <div class="space-y-6 p-6">
            {{-- File Upload --}}
            <flux:field>
                <flux:label>Archivo *</flux:label>
                <flux:input
                    type="file"
                    wire:model="file"
                />
                @error('file')
                    <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror
                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                    Tamaño máximo: 50MB
                </flux:text>
                @if($file)
                    <div class="mt-2 flex items-center gap-2 rounded border border-zinc-200 p-3 dark:border-zinc-700">
                        <flux:icon name="document" class="h-5 w-5 text-zinc-500 dark:text-zinc-400" />
                        <flux:text size="sm" class="text-zinc-900 dark:text-zinc-100">
                            {{ $file->getClientOriginalName() }}
                        </flux:text>
                        <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400">
                            ({{ number_format($file->getSize() / 1024 / 1024, 2) }} MB)
                        </flux:text>
                    </div>
                @endif
            </flux:field>

            <div class="grid gap-6 md:grid-cols-2">
                {{-- Document Type --}}
                <flux:field>
                    <flux:label>Tipo de Documento *</flux:label>
                    <flux:select wire:model="document_type" variant="listbox" placeholder="Seleccionar tipo">
                        <flux:select.option value="invoice">Factura</flux:select.option>
                        <flux:select.option value="receipt">Recibo</flux:select.option>
                        <flux:select.option value="ccf">CCF</flux:select.option>
                        <flux:select.option value="delivery_note">Nota de Entrega</flux:select.option>
                        <flux:select.option value="photo">Fotografía</flux:select.option>
                        <flux:select.option value="contract">Contrato</flux:select.option>
                        <flux:select.option value="certificate">Certificado</flux:select.option>
                        <flux:select.option value="report">Reporte</flux:select.option>
                        <flux:select.option value="other">Otro</flux:select.option>
                    </flux:select>
                    @error('document_type')
                        <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </flux:field>

                {{-- Title --}}
                <flux:field>
                    <flux:label>Título *</flux:label>
                    <flux:input
                        wire:model="title"
                        placeholder="Ej: Factura de compra #001"
                    />
                    @error('title')
                        <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </flux:field>
            </div>

            {{-- Description --}}
            <flux:field>
                <flux:label>Descripción</flux:label>
                <flux:textarea
                    wire:model="description"
                    placeholder="Descripción opcional del documento"
                    rows="3"
                />
                @error('description')
                    <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror
            </flux:field>

            <div class="grid gap-6 md:grid-cols-2">
                {{-- Documentable Type --}}
                <flux:field>
                    <flux:label>Adjuntar a *</flux:label>
                    <flux:select wire:model="documentable_type" variant="listbox" placeholder="Seleccionar tipo">
                        <flux:select.option value="App\Models\Purchase">Compra</flux:select.option>
                        <flux:select.option value="App\Models\Dispatch">Despacho</flux:select.option>
                        <flux:select.option value="App\Models\Transfer">Traslado</flux:select.option>
                        <flux:select.option value="App\Models\InventoryAdjustment">Ajuste de Inventario</flux:select.option>
                        <flux:select.option value="App\Models\Product">Producto</flux:select.option>
                        <flux:select.option value="App\Models\Warehouse">Bodega</flux:select.option>
                    </flux:select>
                    @error('documentable_type')
                        <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </flux:field>

                {{-- Documentable ID --}}
                <flux:field>
                    <flux:label>ID del Elemento *</flux:label>
                    <flux:input
                        type="number"
                        wire:model="documentable_id"
                        placeholder="ID del elemento"
                    />
                    @error('documentable_id')
                        <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </flux:field>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                {{-- Document Number --}}
                <flux:field>
                    <flux:label>Número de Documento</flux:label>
                    <flux:input
                        wire:model="document_number"
                        placeholder="Ej: FAC-001"
                    />
                    @error('document_number')
                        <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </flux:field>

                {{-- Document Date --}}
                <flux:field>
                    <flux:label>Fecha del Documento</flux:label>
                    <flux:input
                        type="date"
                        wire:model="document_date"
                    />
                    @error('document_date')
                        <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </flux:field>

                {{-- Document Amount --}}
                <flux:field>
                    <flux:label>Monto</flux:label>
                    <flux:input
                        type="number"
                        step="0.01"
                        wire:model="document_amount"
                        placeholder="0.00"
                    />
                    @error('document_amount')
                        <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </flux:field>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                {{-- Issuer --}}
                <flux:field>
                    <flux:label>Emisor</flux:label>
                    <flux:input
                        wire:model="issuer"
                        placeholder="Quien emite el documento"
                    />
                    @error('issuer')
                        <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </flux:field>

                {{-- Recipient --}}
                <flux:field>
                    <flux:label>Destinatario</flux:label>
                    <flux:input
                        wire:model="recipient"
                        placeholder="Quien recibe el documento"
                    />
                    @error('recipient')
                        <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </flux:field>
            </div>

            {{-- Options --}}
            <div class="flex flex-col gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="text-zinc-900 dark:text-zinc-100">
                    Opciones
                </flux:heading>

                <flux:checkbox wire:model="is_public">
                    Documento Público (visible para todos los usuarios)
                </flux:checkbox>

                <flux:checkbox wire:model="requires_approval">
                    Requiere Aprobación (necesita ser aprobado por un manager)
                </flux:checkbox>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3">
                <flux:button
                    wire:click="cancel"
                    variant="outline"
                >
                    Cancelar
                </flux:button>

                <flux:button
                    wire:click="upload"
                    variant="primary"
                    :disabled="!$file || $uploading"
                    wire:loading.attr="disabled"
                    wire:target="upload"
                    icon="arrow-up-tray"
                >
                    <span wire:loading.remove wire:target="upload">Subir Documento</span>
                    <span wire:loading wire:target="upload">Subiendo...</span>
                </flux:button>
            </div>
        </div>
    </flux:card>

    {{-- Help Card --}}
    <flux:card>
        <div class="p-6">
            <flux:callout variant="info">
                <div class="space-y-2">
                    <flux:heading size="sm">Información</flux:heading>
                    <ul class="list-inside list-disc space-y-1 text-sm">
                        <li>Los documentos se adjuntan a operaciones específicas (compras, despachos, traslados, etc.)</li>
                        <li>El tamaño máximo de archivo es 50MB</li>
                        <li>Los documentos públicos son visibles para todos los usuarios de la compañía</li>
                        <li>Los documentos que requieren aprobación deben ser aprobados por un manager o administrador</li>
                        <li>Se mantiene un historial de versiones para documentos actualizados</li>
                    </ul>
                </div>
            </flux:callout>
        </div>
    </flux:card>
</div>
