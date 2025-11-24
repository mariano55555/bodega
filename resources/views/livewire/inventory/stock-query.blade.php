<?php

use App\Models\Company;
use App\Models\Inventory;
use App\Models\Warehouse;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(as: 'empresa')]
    public string $company_id = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'bodega')]
    public string $warehouse_id = '';

    #[Url(as: 'categoria')]
    public string $category_id = '';

    #[Url(as: 'estado')]
    public string $status = 'all';

    #[Url(as: 'porpagina')]
    public int $perPage = 15;

    public function mount(): void
    {
        // Set company_id for non-super-admin users (only if not set from URL)
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCompanyId(): void
    {
        $this->warehouse_id = '';
        $this->resetPage();
    }

    public function updatedWarehouseId(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
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
        $this->warehouse_id = '';
        $this->status = 'all';
        $this->perPage = 15;

        // Only reset company_id for super admins
        if (auth()->user()->isSuperAdmin()) {
            $this->company_id = '';
        }

        $this->resetPage();
    }

    public function with(): array
    {
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        // Get the effective company_id based on user type
        $effectiveCompanyId = $isSuperAdmin
            ? $this->company_id
            : auth()->user()->company_id;

        $companies = $isSuperAdmin
            ? Company::where('is_active', true)->orderBy('name')->get()
            : collect();

        $warehouses = $effectiveCompanyId
            ? Warehouse::where('company_id', $effectiveCompanyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
            : collect();

        $query = Inventory::query()
            ->with(['product', 'warehouse', 'product.category'])
            ->when($effectiveCompanyId, function ($q) use ($effectiveCompanyId) {
                $q->whereHas('warehouse', function ($warehouseQuery) use ($effectiveCompanyId) {
                    $warehouseQuery->where('company_id', $effectiveCompanyId);
                });
            })
            ->when($this->search, function ($q) {
                $q->whereHas('product', function ($productQuery) {
                    $productQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%')
                        ->orWhere('barcode', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->warehouse_id, fn ($q) => $q->where('warehouse_id', $this->warehouse_id))
            ->when($this->status === 'low', function ($q) {
                $q->whereHas('product', function ($productQuery) {
                    $productQuery->whereColumn('inventory.quantity', '<=', 'products.minimum_stock');
                });
            })
            ->when($this->status === 'out', fn ($q) => $q->where('quantity', '<=', 0));

        return [
            'inventories' => $query->paginate($this->perPage),
            'isSuperAdmin' => $isSuperAdmin,
            'companies' => $companies,
            'warehouses' => $warehouses,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Consulta de Existencias') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Consulta el stock disponible en todas las bodegas') }}</flux:text>
        </div>
    </div>

    <!-- Filters -->
    <flux:card>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @if ($isSuperAdmin)
                <flux:field class="md:col-span-4">
                    <flux:label>{{ __('Empresa') }}</flux:label>
                    <flux:select wire:model.live="company_id">
                        <option value="">{{ __('Seleccione empresa') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <flux:field>
                <flux:label>{{ __('Buscar') }}</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Producto, SKU, o código de barras...') }}" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Bodega') }}</flux:label>
                <flux:select wire:model.live="warehouse_id">
                    <option value="">{{ __('Todas las bodegas') }}</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Estado') }}</flux:label>
                <flux:select wire:model.live="status">
                    <option value="all">{{ __('Todos') }}</option>
                    <option value="low">{{ __('Stock Bajo') }}</option>
                    <option value="out">{{ __('Sin Stock') }}</option>
                </flux:select>
            </flux:field>

            <div class="flex items-end">
                <flux:button variant="outline" wire:click="clearFilters" class="w-full">
                    {{ __('Limpiar Filtros') }}
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Stats and Per Page -->
    <div class="flex items-center justify-between">
        <div class="text-sm text-zinc-600 dark:text-zinc-400">
            Mostrando {{ $inventories->firstItem() ?? 0 }} - {{ $inventories->lastItem() ?? 0 }} de {{ $inventories->total() }} registros
        </div>
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

    <!-- Results Table -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Producto') }}</flux:table.column>
                <flux:table.column>{{ __('SKU') }}</flux:table.column>
                <flux:table.column>{{ __('Bodega') }}</flux:table.column>
                <flux:table.column>{{ __('Cantidad') }}</flux:table.column>
                <flux:table.column>{{ __('Unidad') }}</flux:table.column>
                <flux:table.column>{{ __('Ubicación') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($inventories as $inventory)
                    <flux:table.row :key="$inventory->id">
                        <flux:table.cell>
                            <div class="font-medium">{{ $inventory->product->name }}</div>
                            <div class="text-sm text-zinc-500">{{ $inventory->product->category?->name }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $inventory->product->sku }}</flux:table.cell>
                        <flux:table.cell>{{ $inventory->warehouse->name }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="font-medium {{ $inventory->quantity <= 0 ? 'text-red-600' : ($inventory->quantity <= $inventory->product->minimum_stock ? 'text-yellow-600' : 'text-green-600') }}">
                                {{ number_format($inventory->quantity, 2) }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>{{ $inventory->product->unitOfMeasure->abbreviation }}</flux:table.cell>
                        <flux:table.cell>{{ $inventory->location ?? 'N/A' }}</flux:table.cell>
                        <flux:table.cell>
                            @if($inventory->quantity <= 0)
                                <flux:badge color="red" size="sm">{{ __('Sin Stock') }}</flux:badge>
                            @elseif($inventory->quantity <= $inventory->product->minimum_stock)
                                <flux:badge color="yellow" size="sm">{{ __('Stock Bajo') }}</flux:badge>
                            @else
                                <flux:badge color="green" size="sm">{{ __('Disponible') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-8 text-zinc-500">
                            {{ __('No se encontraron resultados') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($inventories->hasPages())
            <div class="mt-4">
                {{ $inventories->links() }}
            </div>
        @endif
    </flux:card>
</div>
