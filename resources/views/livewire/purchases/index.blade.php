<?php

use App\Models\Purchase;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    #[Url(as: 'tipo', history: true)]
    public string $typeFilter = '';

    #[Url(as: 'adquisicion', history: true)]
    public string $acquisitionTypeFilter = '';

    #[Url(as: 'bodega', history: true)]
    public string $warehouseFilter = '';

    #[Url(history: true)]
    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAcquisitionTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->typeFilter = '';
        $this->acquisitionTypeFilter = '';
        $this->warehouseFilter = '';
        $this->resetPage();
    }

    #[Computed]
    public function summaryStats(): array
    {
        $user = auth()->user();

        $baseQuery = Purchase::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('company_id', $user->company_id));

        return [
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', 'borrador')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pendiente')->count(),
            'received' => (clone $baseQuery)->where('status', 'recibido')->count(),
            'total_value' => (clone $baseQuery)->where('status', 'recibido')->sum('total'),
        ];
    }

    #[Computed]
    public function warehouses()
    {
        $user = auth()->user();

        return \App\Models\Warehouse::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('company_id', $user->company_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function with(): array
    {
        $user = auth()->user();

        $query = Purchase::query()
            ->with(['supplier', 'warehouse'])
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('company_id', $user->company_id))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('purchase_number', 'like', "%{$this->search}%")
                        ->orWhere('document_number', 'like', "%{$this->search}%")
                        ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('purchase_type', $this->typeFilter))
            ->when($this->acquisitionTypeFilter, fn ($q) => $q->where('acquisition_type', $this->acquisitionTypeFilter))
            ->when($this->warehouseFilter, fn ($q) => $q->where('warehouse_id', $this->warehouseFilter))
            ->latest('document_date');

        return [
            'purchases' => $query->paginate($this->perPage),
        ];
    }

    public function delete(Purchase $purchase): void
    {
        $this->authorize('delete', $purchase);

        $purchase->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Compra Eliminada',
            text: 'La compra ha sido eliminada exitosamente.',
        );
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">Compras</flux:heading>
            <flux:text class="mt-1">Gestión de compras y órdenes de compra</flux:text>
        </div>

        @can('create', App\Models\Purchase::class)
            <flux:button variant="primary" icon="plus" href="{{ route('purchases.create') }}" wire:navigate>
                Nueva Compra
            </flux:button>
        @endcan
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4">
        <flux:card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-blue-600 dark:text-blue-400">Total</flux:text>
                    <flux:heading size="xl" class="text-blue-900 dark:text-blue-100">
                        {{ number_format($this->summaryStats['total']) }}
                    </flux:heading>
                </div>
                <flux:icon name="shopping-cart" class="h-8 w-8 text-blue-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900/20 dark:to-zinc-800/20 border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Borrador</flux:text>
                    <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                        {{ number_format($this->summaryStats['draft']) }}
                    </flux:heading>
                </div>
                <flux:icon name="pencil" class="h-8 w-8 text-zinc-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 border-amber-200 dark:border-amber-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-amber-600 dark:text-amber-400">Pendientes</flux:text>
                    <flux:heading size="xl" class="text-amber-900 dark:text-amber-100">
                        {{ number_format($this->summaryStats['pending']) }}
                    </flux:heading>
                </div>
                <flux:icon name="clock" class="h-8 w-8 text-amber-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 border-emerald-200 dark:border-emerald-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Recibidas</flux:text>
                    <flux:heading size="xl" class="text-emerald-900 dark:text-emerald-100">
                        {{ number_format($this->summaryStats['received']) }}
                    </flux:heading>
                </div>
                <flux:icon name="check-circle" class="h-8 w-8 text-emerald-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-green-200 dark:border-green-800 col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">Valor Total</flux:text>
                    <flux:heading size="xl" class="text-green-900 dark:text-green-100">
                        ${{ number_format($this->summaryStats['total_value'], 2) }}
                    </flux:heading>
                </div>
                <flux:icon name="currency-dollar" class="h-8 w-8 text-green-500" />
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="lg:col-span-2">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por número, documento, proveedor..."
                        icon="magnifying-glass"
                    />
                </div>

                <flux:select wire:model.live="statusFilter" placeholder="Todos los estados">
                    <flux:select.option value="">Todos los estados</flux:select.option>
                    <flux:select.option value="borrador">Borrador</flux:select.option>
                    <flux:select.option value="pendiente">Pendiente</flux:select.option>
                    <flux:select.option value="aprobado">Aprobado</flux:select.option>
                    <flux:select.option value="recibido">Recibido</flux:select.option>
                    <flux:select.option value="cancelado">Cancelado</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="typeFilter" placeholder="Tipo de pago">
                    <flux:select.option value="">Tipo de pago</flux:select.option>
                    <flux:select.option value="efectivo">Efectivo</flux:select.option>
                    <flux:select.option value="credito">Crédito</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="acquisitionTypeFilter" placeholder="Tipo de adquisición">
                    <flux:select.option value="">Tipo adquisición</flux:select.option>
                    <flux:select.option value="normal">Compra Normal</flux:select.option>
                    <flux:select.option value="convenio">Convenio</flux:select.option>
                    <flux:select.option value="proyecto">Proyecto</flux:select.option>
                    <flux:select.option value="otro">Otro</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="warehouseFilter" placeholder="Todas las bodegas">
                    <flux:select.option value="">Todas las bodegas</flux:select.option>
                    @foreach($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if($search || $statusFilter || $typeFilter || $acquisitionTypeFilter || $warehouseFilter)
                <div class="flex items-center justify-between pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-sm text-zinc-500">
                        Filtros activos
                    </flux:text>
                    <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                        Limpiar filtros
                    </flux:button>
                </div>
            @endif
        </div>
    </flux:card>

    <!-- Stats and Per Page -->
    <div class="flex items-center justify-between">
        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
            Mostrando {{ $purchases->firstItem() ?? 0 }} - {{ $purchases->lastItem() ?? 0 }} de {{ $purchases->total() }} compras
        </flux:text>
        <div class="flex items-center gap-2">
            <flux:text class="text-sm">Por página:</flux:text>
            <flux:select wire:model.live="perPage" class="w-20">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </flux:select>
        </div>
    </div>

    <!-- Table -->
    <flux:card class="overflow-hidden">
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Número</flux:table.column>
                    <flux:table.column>Fecha</flux:table.column>
                    <flux:table.column>Proveedor</flux:table.column>
                    <flux:table.column>Bodega</flux:table.column>
                    <flux:table.column>Tipo Pago</flux:table.column>
                    <flux:table.column>Adquisición</flux:table.column>
                    <flux:table.column class="text-right">Total</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>

            <flux:table.rows>
                @forelse ($purchases as $purchase)
                    <flux:table.row :key="$purchase->id">
                        <flux:table.cell>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $purchase->purchase_number }}
                                </div>
                                @if ($purchase->document_number)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ ucfirst($purchase->document_type) }}: {{ $purchase->document_number }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $purchase->document_date->format('d/m/Y') }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $purchase->supplier?->name ?? 'Proveedor eliminado' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $purchase->warehouse?->name ?? 'Bodega eliminada' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="$purchase->purchase_type === 'efectivo' ? 'emerald' : 'amber'"
                                :icon="$purchase->purchase_type === 'efectivo' ? 'banknotes' : 'credit-card'"
                            >
                                {{ ucfirst($purchase->purchase_type) }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:badge
                                    size="sm"
                                    :color="match($purchase->acquisition_type) {
                                        'normal' => 'zinc',
                                        'convenio' => 'sky',
                                        'proyecto' => 'violet',
                                        'otro' => 'amber',
                                        default => 'zinc'
                                    }"
                                >
                                    {{ $purchase->getAcquisitionTypeLabel() }}
                                </flux:badge>
                                @if ($purchase->is_retroactive)
                                    <flux:tooltip content="Compra Retroactiva">
                                        <flux:icon.exclamation-triangle class="w-4 h-4 text-amber-500" />
                                    </flux:tooltip>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="font-semibold">${{ number_format($purchase->total, 2) }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="match($purchase->status) {
                                    'borrador' => 'zinc',
                                    'pendiente' => 'amber',
                                    'aprobado' => 'sky',
                                    'recibido' => 'emerald',
                                    'cancelado' => 'red',
                                    default => 'zinc'
                                }"
                                :icon="match($purchase->status) {
                                    'borrador' => 'pencil',
                                    'pendiente' => 'clock',
                                    'aprobado' => 'check',
                                    'recibido' => 'check-circle',
                                    'cancelado' => 'x-circle',
                                    default => null
                                }"
                            >
                                {{ ucfirst($purchase->status) }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @can('view', $purchase)
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="eye"
                                        href="{{ route('purchases.show', $purchase) }}"
                                        wire:navigate
                                    >
                                        Ver
                                    </flux:button>
                                @endcan

                                @if ($purchase->status === 'borrador')
                                    @can('update', $purchase)
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="pencil"
                                            href="{{ route('purchases.edit', $purchase) }}"
                                            wire:navigate
                                        >
                                            Editar
                                        </flux:button>
                                    @endcan

                                    @can('delete', $purchase)
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            wire:click="delete({{ $purchase->id }})"
                                            wire:confirm="¿Está seguro de eliminar esta compra? Esta acción no se puede deshacer."
                                        >
                                            Eliminar
                                        </flux:button>
                                    @endcan
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <flux:icon name="shopping-cart" class="mx-auto h-12 w-12 mb-3 opacity-20" />
                            <div>No se encontraron compras.</div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
            </flux:table>
        </div>

        @if ($purchases->hasPages())
            <div class="mt-6 px-6 pb-6">
                {{ $purchases->links() }}
            </div>
        @endif
    </flux:card>
</div>
