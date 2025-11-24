<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $documentType = '';
    public string $status = '';
    public bool $requiresApproval = false;
    public bool $showFilters = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDocumentType(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedRequiresApproval(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function documents()
    {
        $user = auth()->user();
        $query = Document::query()
            ->with(['company', 'uploader', 'approver'])
            ->when(!$user->isSuperAdmin(), fn(Builder $q) => $q->forCompany($user->company_id))
            ->when($this->search, function (Builder $q) {
                $q->where(function (Builder $query) {
                    $query->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('document_number', 'like', "%{$this->search}%")
                        ->orWhere('file_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->documentType, fn(Builder $q) => $q->byType($this->documentType))
            ->when($this->status, fn(Builder $q) => $q->where('status', $this->status))
            ->when($this->requiresApproval, fn(Builder $q) => $q->pendingApproval())
            ->latest('created_at');

        return $query->paginate(20);
    }

    public function deleteDocument(int $documentId): void
    {
        $document = Document::findOrFail($documentId);
        $user = auth()->user();

        if (!$document->canBeDeleted($user)) {
            $this->dispatch('document-error', message: 'No tienes permiso para eliminar este documento.');
            return;
        }

        $document->update(['deleted_by' => $user->id]);
        $document->delete();

        $this->dispatch('document-deleted', message: 'Documento eliminado exitosamente.');
        $this->resetPage();
    }

    public function approveDocument(int $documentId): void
    {
        $document = Document::findOrFail($documentId);
        $user = auth()->user();

        if (!$user->hasRole(['super-admin', 'manager'])) {
            $this->dispatch('document-error', message: 'No tienes permiso para aprobar documentos.');
            return;
        }

        if (!$document->requires_approval) {
            $this->dispatch('document-error', message: 'Este documento no requiere aprobación.');
            return;
        }

        if ($document->isApproved()) {
            $this->dispatch('document-error', message: 'Este documento ya ha sido aprobado.');
            return;
        }

        $document->approve($user);

        $this->dispatch('document-approved', message: 'Documento aprobado exitosamente.');
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->documentType = '';
        $this->status = '';
        $this->requiresApproval = false;
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                Gestión Documental
            </flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                Administra documentos adjuntos a operaciones de bodega
            </flux:text>
        </div>

        <flux:button
            :href="route('documents.upload')"
            wire:navigate
            variant="primary"
            icon="arrow-up-tray"
        >
            Subir Documento
        </flux:button>
    </div>

    {{-- Search and Filters --}}
    <flux:card>
        <div class="space-y-4 p-4">
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por título, descripción, número o archivo..."
                        icon="magnifying-glass"
                    />
                </div>

                <flux:button
                    wire:click="$toggle('showFilters')"
                    variant="outline"
                    icon="adjustments-horizontal"
                >
                    {{ $showFilters ? 'Ocultar' : 'Filtros' }}
                </flux:button>

                @if($search || $documentType || $status || $requiresApproval)
                    <flux:button
                        wire:click="clearFilters"
                        variant="ghost"
                        icon="x-mark"
                    >
                        Limpiar
                    </flux:button>
                @endif
            </div>

            @if($showFilters)
                <div class="grid gap-4 border-t border-zinc-200 pt-4 dark:border-zinc-700 md:grid-cols-3">
                    <flux:field>
                        <flux:label>Tipo de Documento</flux:label>
                        <flux:select wire:model.live="documentType" variant="listbox" placeholder="Todos">
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
                    </flux:field>

                    <flux:field>
                        <flux:label>Estado</flux:label>
                        <flux:select wire:model.live="status" variant="listbox" placeholder="Todos">
                            <flux:select.option value="active">Activo</flux:select.option>
                            <flux:select.option value="archived">Archivado</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Aprobación</flux:label>
                        <flux:checkbox wire:model.live="requiresApproval">
                            Solo Pendientes de Aprobación
                        </flux:checkbox>
                    </flux:field>
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Documents Table --}}
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-12"></flux:table.column>
                <flux:table.column>Documento</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Adjunto a</flux:table.column>
                <flux:table.column>Tamaño</flux:table.column>
                <flux:table.column>Subido por</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column class="w-32">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($this->documents as $document)
                    <flux:table.row :key="'document-' . $document->id" wire:key="document-{{ $document->id }}">
                        {{-- Icon --}}
                        <flux:table.cell class="text-zinc-500 dark:text-zinc-400">
                            <flux:icon :name="$document->icon_class" class="h-6 w-6" />
                        </flux:table.cell>

                        {{-- Document Info --}}
                        <flux:table.cell>
                            <div class="flex flex-col">
                                <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $document->title }}
                                </flux:text>
                                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                    {{ $document->file_name }}
                                </flux:text>
                                @if($document->document_number)
                                    <flux:text size="xs" class="text-zinc-500 dark:text-zinc-500">
                                        #{{ $document->document_number }}
                                    </flux:text>
                                @endif
                            </div>
                        </flux:table.cell>

                        {{-- Type --}}
                        <flux:table.cell>
                            <flux:badge variant="outline" size="sm">
                                {{ $document->document_type_spanish }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Documentable --}}
                        <flux:table.cell>
                            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                {{ class_basename($document->documentable_type) }} #{{ $document->documentable_id }}
                            </flux:text>
                        </flux:table.cell>

                        {{-- File Size --}}
                        <flux:table.cell>
                            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                {{ $document->file_size_for_humans }}
                            </flux:text>
                        </flux:table.cell>

                        {{-- Uploader --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:avatar size="xs" :src="$document->uploader->avatar_url ?? null" />
                                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                    {{ $document->uploader->name }}
                                </flux:text>
                            </div>
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell>
                            <div class="flex flex-col gap-1">
                                <flux:badge
                                    :variant="$document->status === 'active' ? 'success' : 'outline'"
                                    size="sm"
                                >
                                    {{ ucfirst($document->status) }}
                                </flux:badge>

                                @if($document->requires_approval)
                                    @if($document->isApproved())
                                        <flux:badge variant="success" size="xs">
                                            Aprobado
                                        </flux:badge>
                                    @else
                                        <flux:badge variant="warning" size="xs">
                                            Pendiente
                                        </flux:badge>
                                    @endif
                                @endif

                                @if($document->version > 1)
                                    <flux:badge variant="outline" size="xs">
                                        v{{ $document->version }}
                                    </flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    href="{{ route('documents.view', $document) }}"
                                    target="_blank"
                                    variant="ghost"
                                    size="sm"
                                    icon="eye"
                                    title="Ver"
                                />

                                <flux:button
                                    href="{{ route('documents.download', $document) }}"
                                    variant="ghost"
                                    size="sm"
                                    icon="arrow-down-tray"
                                    title="Descargar"
                                />

                                @if($document->requires_approval && !$document->isApproved() && auth()->user()->hasRole(['super-admin', 'manager']))
                                    <flux:button
                                        wire:click="approveDocument({{ $document->id }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="check"
                                        title="Aprobar"
                                    />
                                @endif

                                @if($document->canBeDeleted(auth()->user()))
                                    <flux:button
                                        wire:click="deleteDocument({{ $document->id }})"
                                        wire:confirm="¿Estás seguro de eliminar este documento?"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        title="Eliminar"
                                    />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center">
                            <div class="py-12">
                                <flux:icon name="document" class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-600" />
                                <flux:heading size="base" class="mt-4 text-zinc-900 dark:text-zinc-100">
                                    No se encontraron documentos
                                </flux:heading>
                                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                                    {{ $search || $documentType || $status || $requiresApproval
                                        ? 'Intenta ajustar los filtros de búsqueda.'
                                        : 'Comienza subiendo tu primer documento.' }}
                                </flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($this->documents->hasPages())
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                {{ $this->documents->links() }}
            </div>
        @endif
    </flux:card>
</div>
