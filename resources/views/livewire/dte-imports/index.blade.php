<?php

use App\Models\Company;
use App\Models\DteImport;
use App\Services\DteImportService;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new class extends Component {
    use WithFileUploads, WithPagination;

    public ?TemporaryUploadedFile $file = null;
    public bool $uploading = false;
    public bool $showUploadModal = false;
    public array $parsedData = [];
    public array $summary = [];
    public ?string $parseError = null;
    public string $search = '';
    public string $statusFilter = '';
    public ?int $selectedCompanyId = null;

    public function mount(): void
    {
        $user = auth()->user();
        // Set default company for non-super admins
        if ($user->company_id) {
            $this->selectedCompanyId = $user->company_id;
        }
    }

    #[Computed]
    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    #[Computed]
    public function companies()
    {
        return Company::query()
            ->whereNotNull('active_at')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function getCompanyId(): ?int
    {
        $user = auth()->user();

        // Super admin uses selected company
        if ($user->isSuperAdmin()) {
            return $this->selectedCompanyId;
        }

        // Regular users use their assigned company
        return $user->company_id;
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return $this->getCompanyId() !== null;
    }

    #[Computed]
    public function imports()
    {
        $companyId = $this->getCompanyId();

        if (!$companyId) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        return DteImport::query()
            ->forCompany($companyId)
            ->with(['supplier', 'purchase', 'creator'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('emisor_nombre', 'like', '%' . $this->search . '%')
                      ->orWhere('codigo_generacion', 'like', '%' . $this->search . '%')
                      ->orWhere('numero_control', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    #[Computed]
    public function statusCounts()
    {
        $companyId = $this->getCompanyId();

        if (!$companyId) {
            return [];
        }

        return DteImport::query()
            ->forCompany($companyId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function openUploadModal(): void
    {
        $this->showUploadModal = true;
        $this->reset(['file', 'parsedData', 'summary', 'parseError']);
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->reset(['file', 'parsedData', 'summary', 'parseError']);
    }

    public function updatedFile(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:json,txt|max:5120',
        ], [
            'file.required' => 'Debe seleccionar un archivo JSON',
            'file.mimes' => 'El archivo debe ser JSON',
            'file.max' => 'El archivo no debe exceder 5MB',
        ]);

        $this->parseFile();
    }

    protected function parseFile(): void
    {
        if (!$this->file) {
            return;
        }

        $service = app(DteImportService::class);
        $result = $service->parseJson($this->file);

        if (!$result['success']) {
            $this->parseError = $result['error'];
            $this->parsedData = [];
            $this->summary = [];
            return;
        }

        $this->parseError = null;
        $this->parsedData = $result['data'];
        $this->summary = $service->getDteSummary($result['data']);

        // Check if already exists
        $companyId = $this->getCompanyId();
        if ($companyId && $service->dteExists($this->summary['codigo_generacion'], $companyId)) {
            $this->parseError = 'Este DTE ya fue importado anteriormente (código: ' . $this->summary['codigo_generacion'] . ')';
        }
    }

    public function confirmUpload(): void
    {
        if (empty($this->parsedData) || $this->parseError) {
            return;
        }

        $companyId = $this->getCompanyId();
        if (!$companyId) {
            $this->addError('file', 'Debe seleccionar una compañía.');
            return;
        }

        $this->uploading = true;

        try {
            $service = app(DteImportService::class);

            // Create DTE import record
            $dteImport = $service->createImport($this->parsedData, $companyId);

            // Try to find or create supplier
            $supplierResult = $service->findOrCreateSupplier(
                $this->parsedData['emisor'],
                $companyId
            );

            // Update DTE with supplier
            $dteImport->update(['supplier_id' => $supplierResult['supplier']->id]);

            $this->closeUploadModal();

            // Show success message
            $message = 'DTE importado exitosamente.';
            if ($supplierResult['created']) {
                $message .= ' Se creó el proveedor "' . $supplierResult['supplier']->name . '".';
            }

            session()->flash('success', $message);

            // Redirect to review page
            $this->redirect(route('dte-imports.review', $dteImport), navigate: true);

        } catch (\Exception $e) {
            $this->addError('file', 'Error al importar: ' . $e->getMessage());
        } finally {
            $this->uploading = false;
        }
    }

    public function deleteDte(int $id): void
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            session()->flash('error', 'Debe seleccionar una compañía.');
            return;
        }

        $dte = DteImport::forCompany($companyId)->findOrFail($id);

        if ($dte->isProcessed()) {
            session()->flash('error', 'No se puede eliminar un DTE ya procesado.');
            return;
        }

        $dte->delete();
        session()->flash('success', 'DTE eliminado exitosamente.');
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                Importación de DTE
            </flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                Importa facturas electrónicas (DTE) desde archivos JSON
            </flux:text>
        </div>
        <flux:button wire:click="openUploadModal" variant="primary" icon="arrow-up-tray" :disabled="!$this->hasCompany">
            Importar DTE
        </flux:button>
    </div>

    {{-- Super Admin Company Selector --}}
    @if($this->isSuperAdmin)
        <flux:card class="p-4">
            <div class="flex items-center gap-4">
                <flux:icon name="building-office" class="h-5 w-5 text-zinc-500" />
                <div class="flex-1">
                    <flux:select
                        wire:model.live="selectedCompanyId"
                        placeholder="Seleccione una empresa..."
                    >
                        <flux:select.option value="">Seleccione una empresa...</flux:select.option>
                        @foreach($this->companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
            @if(!$this->hasCompany)
                <flux:text size="sm" class="mt-2 text-amber-600 dark:text-amber-400">
                    <flux:icon name="exclamation-triangle" class="inline h-4 w-4" />
                    Debe seleccionar una empresa para ver y gestionar los DTEs importados.
                </flux:text>
            @endif
        </flux:card>
    @elseif(!$this->hasCompany)
        <flux:callout variant="warning">
            <flux:heading size="sm">Sin empresa asignada</flux:heading>
            <flux:text size="sm">No tiene una empresa asignada. Contacte al administrador para poder importar DTEs.</flux:text>
        </flux:callout>
    @endif

    {{-- Status Summary Cards --}}
    <div class="grid gap-4 sm:grid-cols-5">
        <button
            wire:click="setStatusFilter('pending')"
            class="rounded-lg border-l-4 p-4 text-left transition-all {{ $statusFilter === 'pending' ? 'border-amber-600 bg-amber-100 dark:bg-amber-950/40' : 'border-amber-500 bg-amber-50 dark:bg-amber-950/20' }}"
        >
            <flux:text size="sm" class="font-medium text-amber-600 dark:text-amber-400">
                Pendientes
            </flux:text>
            <flux:heading size="xl" class="mt-1 text-amber-900 dark:text-amber-100">
                {{ number_format($this->statusCounts['pending'] ?? 0) }}
            </flux:heading>
        </button>

        <button
            wire:click="setStatusFilter('reviewing')"
            class="rounded-lg border-l-4 p-4 text-left transition-all {{ $statusFilter === 'reviewing' ? 'border-blue-600 bg-blue-100 dark:bg-blue-950/40' : 'border-blue-500 bg-blue-50 dark:bg-blue-950/20' }}"
        >
            <flux:text size="sm" class="font-medium text-blue-600 dark:text-blue-400">
                En Revisión
            </flux:text>
            <flux:heading size="xl" class="mt-1 text-blue-900 dark:text-blue-100">
                {{ number_format($this->statusCounts['reviewing'] ?? 0) }}
            </flux:heading>
        </button>

        <button
            wire:click="setStatusFilter('ready')"
            class="rounded-lg border-l-4 p-4 text-left transition-all {{ $statusFilter === 'ready' ? 'border-green-600 bg-green-100 dark:bg-green-950/40' : 'border-green-500 bg-green-50 dark:bg-green-950/20' }}"
        >
            <flux:text size="sm" class="font-medium text-green-600 dark:text-green-400">
                Listos
            </flux:text>
            <flux:heading size="xl" class="mt-1 text-green-900 dark:text-green-100">
                {{ number_format($this->statusCounts['ready'] ?? 0) }}
            </flux:heading>
        </button>

        <button
            wire:click="setStatusFilter('processed')"
            class="rounded-lg border-l-4 p-4 text-left transition-all {{ $statusFilter === 'processed' ? 'border-emerald-600 bg-emerald-100 dark:bg-emerald-950/40' : 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/20' }}"
        >
            <flux:text size="sm" class="font-medium text-emerald-600 dark:text-emerald-400">
                Procesados
            </flux:text>
            <flux:heading size="xl" class="mt-1 text-emerald-900 dark:text-emerald-100">
                {{ number_format($this->statusCounts['processed'] ?? 0) }}
            </flux:heading>
        </button>

        <button
            wire:click="setStatusFilter('failed')"
            class="rounded-lg border-l-4 p-4 text-left transition-all {{ $statusFilter === 'failed' ? 'border-red-600 bg-red-100 dark:bg-red-950/40' : 'border-red-500 bg-red-50 dark:bg-red-950/20' }}"
        >
            <flux:text size="sm" class="font-medium text-red-600 dark:text-red-400">
                Con Error
            </flux:text>
            <flux:heading size="xl" class="mt-1 text-red-900 dark:text-red-100">
                {{ number_format($this->statusCounts['failed'] ?? 0) }}
            </flux:heading>
        </button>
    </div>

    {{-- Filters & Search --}}
    <flux:card>
        <div class="flex items-center gap-4 p-4">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por proveedor, código o número de control..."
                    icon="magnifying-glass"
                />
            </div>
            @if($statusFilter)
                <flux:button wire:click="$set('statusFilter', '')" variant="ghost" size="sm">
                    Limpiar filtro
                </flux:button>
            @endif
        </div>
    </flux:card>

    {{-- DTE List --}}
    <flux:card>
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                Documentos Tributarios Electrónicos
            </flux:heading>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Proveedor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Código</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @forelse($this->imports as $dte)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $dte->fecha_emision->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ Str::limit($dte->emisor_nombre, 35) }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    NIT: {{ $dte->emisor_nit }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $dte->tipo_dte === '01' ? 'Factura' : 'DTE-' . $dte->tipo_dte }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-mono text-xs text-zinc-600 dark:text-zinc-400" title="{{ $dte->codigo_generacion }}">
                                    {{ Str::limit($dte->codigo_generacion, 20) }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-right font-medium text-zinc-900 dark:text-zinc-100">
                                ${{ number_format($dte->total_pagar, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <flux:badge color="{{ $dte->status_color }}" size="sm">
                                    {{ $dte->status_label }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($dte->canBeEdited())
                                        <flux:button
                                            :href="route('dte-imports.review', $dte)"
                                            wire:navigate
                                            variant="outline"
                                            size="sm"
                                            icon="pencil-square"
                                        >
                                            Revisar
                                        </flux:button>
                                    @elseif($dte->isProcessed() && $dte->purchase)
                                        <flux:button
                                            :href="route('purchases.show', $dte->purchase)"
                                            wire:navigate
                                            variant="ghost"
                                            size="sm"
                                            icon="eye"
                                        >
                                            Ver Compra
                                        </flux:button>
                                    @endif

                                    @if(!$dte->isProcessed())
                                        <flux:button
                                            wire:click="deleteDte({{ $dte->id }})"
                                            wire:confirm="¿Está seguro de eliminar este DTE?"
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            class="text-red-600 hover:text-red-700"
                                        />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <flux:icon name="document-text" class="mx-auto h-12 w-12 text-zinc-400" />
                                <flux:heading size="sm" class="mt-4 text-zinc-900 dark:text-zinc-100">
                                    No hay DTEs importados
                                </flux:heading>
                                <flux:text size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                                    Importa tu primer documento tributario electrónico
                                </flux:text>
                                <flux:button wire:click="openUploadModal" variant="primary" class="mt-4" icon="arrow-up-tray">
                                    Importar DTE
                                </flux:button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->imports->hasPages())
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                {{ $this->imports->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Upload Modal --}}
    <flux:modal wire:model="showUploadModal" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Importar DTE</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Sube un archivo JSON de factura electrónica (DTE) para procesarlo
                </flux:text>
            </div>

            {{-- File Upload --}}
            <flux:field>
                <flux:label>Archivo JSON del DTE</flux:label>
                <flux:input
                    type="file"
                    wire:model="file"
                    accept=".json"
                />
                @error('file')
                    <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror
            </flux:field>

            {{-- Parse Error --}}
            @if($parseError)
                <flux:callout variant="danger">
                    <flux:heading size="sm">Error al procesar</flux:heading>
                    <flux:text size="sm">{{ $parseError }}</flux:text>
                </flux:callout>
            @endif

            {{-- Preview Summary --}}
            @if(!empty($summary) && !$parseError)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:heading size="sm">Vista Previa del DTE</flux:heading>
                    </div>
                    <div class="space-y-4 p-4">
                        {{-- Document Info --}}
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Tipo</flux:text>
                                <flux:text class="text-zinc-900 dark:text-zinc-100">{{ $summary['tipo_dte'] }}</flux:text>
                            </div>
                            <div>
                                <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Fecha</flux:text>
                                <flux:text class="text-zinc-900 dark:text-zinc-100">{{ $summary['fecha_emision'] }}</flux:text>
                            </div>
                        </div>

                        {{-- Emisor Info --}}
                        <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                            <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Proveedor (Emisor)</flux:text>
                            <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $summary['emisor']['nombre_comercial'] }}
                            </flux:text>
                            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                NIT: {{ $summary['emisor']['nit'] }}
                            </flux:text>
                        </div>

                        {{-- Totals --}}
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-950/20">
                                <flux:text size="sm" class="font-medium text-blue-600 dark:text-blue-400">Items</flux:text>
                                <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">
                                    {{ $summary['num_items'] }}
                                </flux:heading>
                            </div>
                            <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-950/20">
                                <flux:text size="sm" class="font-medium text-amber-600 dark:text-amber-400">IVA</flux:text>
                                <flux:heading size="lg" class="text-amber-900 dark:text-amber-100">
                                    ${{ number_format($summary['totales']['total_iva'], 2) }}
                                </flux:heading>
                            </div>
                            <div class="rounded-lg bg-green-50 p-3 dark:bg-green-950/20">
                                <flux:text size="sm" class="font-medium text-green-600 dark:text-green-400">Total</flux:text>
                                <flux:heading size="lg" class="text-green-900 dark:text-green-100">
                                    ${{ number_format($summary['totales']['total_pagar'], 2) }}
                                </flux:heading>
                            </div>
                        </div>

                        {{-- Code --}}
                        <div>
                            <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Código de Generación</flux:text>
                            <flux:text size="sm" class="font-mono text-zinc-600 dark:text-zinc-400">
                                {{ $summary['codigo_generacion'] }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3">
                <flux:button wire:click="closeUploadModal" variant="ghost">
                    Cancelar
                </flux:button>
                <flux:button
                    wire:click="confirmUpload"
                    variant="primary"
                    :disabled="empty($parsedData) || $parseError || $uploading"
                    wire:loading.attr="disabled"
                    wire:target="confirmUpload"
                    icon="arrow-up-tray"
                >
                    <span wire:loading.remove wire:target="confirmUpload">Importar y Revisar</span>
                    <span wire:loading wire:target="confirmUpload">Importando...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
