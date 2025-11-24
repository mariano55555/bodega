<?php

use Livewire\Volt\Component;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductCategory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $company = '';
    public string $warehouse = '';
    public string $category = '';
    public string $stockLevel = '';
    public bool $showLowStock = false;
    public bool $showExpiring = false;

    public function mount(): void
    {
        // Auto-set company for non-super admins
        if (!auth()->user()->isSuperAdmin()) {
            $this->company = (string) auth()->user()->company_id;
        }
    }

    public function updatedCompany(): void
    {
        $this->warehouse = '';
        $this->category = '';
        $this->resetPage();
    }

    #[Computed]
    public function inventoryItems()
    {
        $query = Inventory::query()
            ->with([
                'product' => function ($query) {
                    $query->with(['category', 'unitOfMeasure']);
                },
                'warehouse',
                'storageLocation',
                'lastCounter'
            ])
            ->active()
            ->whereHas('product', function ($q) {
                $q->where('track_inventory', true);
            });

        // Company filter - filter through warehouse relationship
        if ($this->company) {
            $query->whereHas('warehouse', function ($q) {
                $q->where('company_id', $this->company);
            });
        }

        // Search filter
        if ($this->search) {
            $query->whereHas('product', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%")
                  ->orWhere('barcode', 'like', "%{$this->search}%");
            })->orWhere('lot_number', 'like', "%{$this->search}%")
              ->orWhere('location', 'like', "%{$this->search}%");
        }

        // Warehouse filter
        if ($this->warehouse) {
            $query->where('warehouse_id', $this->warehouse);
        }

        // Category filter
        if ($this->category) {
            $query->whereHas('product.category', function ($q) {
                $q->where('id', $this->category);
            });
        }

        // Stock level filters
        if ($this->showLowStock) {
            $query->whereHas('product', function ($q) {
                $q->whereRaw('inventory.available_quantity <= products.minimum_stock');
            });
        }

        if ($this->showExpiring) {
            $query->expiringSoon(30);
        }

        // Stock level filter
        if ($this->stockLevel === 'available') {
            $query->available();
        } elseif ($this->stockLevel === 'reserved') {
            $query->where('reserved_quantity', '>', 0);
        } elseif ($this->stockLevel === 'zero') {
            $query->where('available_quantity', '<=', 0);
        }

        return $query->orderBy('updated_at', 'desc')
                    ->paginate(15);
    }

    #[Computed]
    public function companies()
    {
        return \App\Models\Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function warehouses()
    {
        $query = Warehouse::active();

        if ($this->company) {
            $query->where('company_id', $this->company);
        }

        return $query->get(['id', 'name']);
    }

    #[Computed]
    public function categories()
    {
        $query = ProductCategory::active();

        if ($this->company) {
            $query->where('company_id', $this->company);
        }

        return $query->get(['id', 'name']);
    }

    #[Computed]
    public function summaryStats()
    {
        $query = Inventory::active();

        // Company filter - filter through warehouse relationship
        if ($this->company) {
            $query->whereHas('warehouse', function ($q) {
                $q->where('company_id', $this->company);
            });
        }

        $totalItems = (clone $query)->count();
        $lowStockItems = (clone $query)
            ->whereHas('product', function ($q) {
                $q->whereRaw('inventory.available_quantity <= products.minimum_stock');
            })->count();
        $expiringItems = (clone $query)->expiringSoon(30)->count();
        $totalValue = (clone $query)->sum('total_value');

        return [
            'total_items' => $totalItems,
            'low_stock_items' => $lowStockItems,
            'expiring_items' => $expiringItems,
            'total_value' => $totalValue
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedWarehouse(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedStockLevel(): void
    {
        $this->resetPage();
    }

    public function updatedShowLowStock(): void
    {
        $this->resetPage();
    }

    public function updatedShowExpiring(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        if (auth()->user()->isSuperAdmin()) {
            $this->company = '';
        }
        $this->warehouse = '';
        $this->category = '';
        $this->stockLevel = '';
        $this->showLowStock = false;
        $this->showExpiring = false;
        $this->resetPage();
    }

    public function viewMovementHistory($inventoryId): void
    {
        // Get the inventory item to extract product and warehouse info
        $inventory = Inventory::find($inventoryId);

        if ($inventory) {
            // Redirect to movements page with filters
            $this->redirect(route('inventory.movements.index', [
                'product_id' => $inventory->product_id,
                'warehouse_id' => $inventory->warehouse_id
            ]), navigate: true);
        }
    }

    public function exportExcel(): void
    {
        $this->redirect(route('inventory.products.export', [
            'company_id' => $this->company ?: null,
            'search' => $this->search,
            'warehouse_id' => $this->warehouse,
            'category_id' => $this->category,
            'stock_level' => $this->stockLevel,
            'show_low_stock' => $this->showLowStock,
            'show_expiring' => $this->showExpiring,
        ]));
    }

    public function with(): array
    {
        return [
            'title' => __('inventory.inventory_management'),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    {{ __('inventory.inventory') }}
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('inventory.manage_inventory_description') }}
                </flux:text>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <flux:button variant="outline" icon="document-arrow-down" wire:click="exportExcel">
                    {{ __('ui.export') }}
                </flux:button>
                <flux:button variant="primary" icon="plus" href="{{ route('inventory.products.create') }}" wire:navigate>
                    {{ __('inventory.new_product') }}
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <flux:card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-blue-600 dark:text-blue-400">
                        {{ __('inventory.total_items') }}
                    </flux:text>
                    <flux:heading size="2xl" class="text-blue-900 dark:text-blue-100">
                        {{ number_format($this->summaryStats['total_items']) }}
                    </flux:heading>
                </div>
                <flux:icon name="cube" class="h-8 w-8 text-blue-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-red-200 dark:border-red-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-red-600 dark:text-red-400">
                        {{ __('inventory.low_stock') }}
                    </flux:text>
                    <flux:heading size="2xl" class="text-red-900 dark:text-red-100">
                        {{ number_format($this->summaryStats['low_stock_items']) }}
                    </flux:heading>
                </div>
                <flux:icon name="exclamation-triangle" class="h-8 w-8 text-red-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 border-yellow-200 dark:border-yellow-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
                        {{ __('inventory.expiring_soon') }}
                    </flux:text>
                    <flux:heading size="2xl" class="text-yellow-900 dark:text-yellow-100">
                        {{ number_format($this->summaryStats['expiring_items']) }}
                    </flux:heading>
                </div>
                <flux:icon name="clock" class="h-8 w-8 text-yellow-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-green-200 dark:border-green-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">
                        {{ __('inventory.total_value') }}
                    </flux:text>
                    <flux:heading size="2xl" class="text-green-900 dark:text-green-100">
                        ${{ number_format($this->summaryStats['total_value'], 2) }}
                    </flux:heading>
                </div>
                <flux:icon name="currency-dollar" class="h-8 w-8 text-green-500" />
            </div>
        </flux:card>
    </div>

    <!-- Filters and Search -->
    <flux:card class="mb-6">
        <div class="space-y-4">
            @if (auth()->user()->isSuperAdmin())
                <!-- Company Filter for Super Admin -->
                <div>
                    <flux:field>
                        <flux:label>Empresa</flux:label>
                        <flux:select wire:model.live="company">
                            <option value="">Todas las empresas</option>
                            @foreach($this->companies as $comp)
                            <flux:select.option value="{{ $comp->id }}">{{ $comp->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            @endif

            <!-- First row - Search and main filters -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="sm:col-span-2 lg:col-span-1">
                    <flux:input wire:model.live.debounce.300ms="search"
                               :placeholder="__('inventory.search_products_placeholder')"
                               icon="magnifying-glass" />
                </div>

                <!-- Warehouse Filter -->
                <div>
                    <flux:select wire:model.live="warehouse" :placeholder="__('warehouse.all_warehouses')">
                        @foreach($this->warehouses as $w)
                        <flux:select.option value="{{ $w->id }}">{{ $w->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Category Filter -->
                <div>
                    <flux:select wire:model.live="category" :placeholder="__('inventory.all_categories')">
                        @foreach($this->categories as $cat)
                        <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Stock Level Filter -->
                <div>
                    <flux:select wire:model.live="stockLevel" :placeholder="__('inventory.stock_level')">
                        <flux:select.option value="available">{{ __('inventory.with_stock') }}</flux:select.option>
                        <flux:select.option value="reserved">{{ __('inventory.with_reservations') }}</flux:select.option>
                        <flux:select.option value="zero">{{ __('inventory.no_stock') }}</flux:select.option>
                    </flux:select>
                </div>
            </div>

            <!-- Second row - Toggle filters -->
            <div class="flex flex-wrap gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:switch wire:model.live="showLowStock" label="{{ __('inventory.show_low_stock_only') }}" />

                <flux:switch wire:model.live="showExpiring" label="{{ __('inventory.show_expiring_only') }}" />
            </div>

            <!-- Clear filters -->
            @if($search || $company || $warehouse || $category || $stockLevel || $showLowStock || $showExpiring)
            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                    {{ __('ui.clear_filters') }}
                </flux:button>
            </div>
            @endif
        </div>
    </flux:card>

    <!-- Inventory Table -->
    <flux:card>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('inventory.product') }}</flux:table.column>
                    <flux:table.column>{{ __('inventory.location') }}</flux:table.column>
                    <flux:table.column>{{ __('inventory.stock') }}</flux:table.column>
                    <flux:table.column>{{ __('inventory.lot_expiration') }}</flux:table.column>
                    <flux:table.column>{{ __('inventory.cost') }}</flux:table.column>
                    <flux:table.column>{{ __('inventory.last_count') }}</flux:table.column>
                    <flux:table.column>{{ __('ui.actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->inventoryItems as $item)
                    <flux:table.row wire:key="inventory-{{ $item->id }}">
                        <flux:table.cell>
                            <div class="min-w-0">
                                <flux:text class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ $item->product->name }}
                                </flux:text>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ $item->product->sku }}
                                    </flux:text>
                                    @if($item->product->category)
                                    <flux:badge color="blue" size="xs">
                                        {{ $item->product->category->name }}
                                    </flux:badge>
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm">
                                <flux:text class="font-medium">{{ $item->warehouse->name }}</flux:text>
                                @if($item->storageLocation)
                                <flux:text class="text-zinc-500 block">
                                    {{ $item->storageLocation->name }}
                                </flux:text>
                                @endif
                                @if($item->location)
                                <flux:text class="text-zinc-500 block text-xs">
                                    {{ $item->location }}
                                </flux:text>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-center">
                                <div class="space-y-1">
                                    <flux:text class="font-medium {{ $item->available_quantity <= ($item->product->minimum_stock ?? 0) ? 'text-red-600' : 'text-zinc-900 dark:text-zinc-100' }}">
                                        {{ number_format($item->available_quantity, 2) }}
                                    </flux:text>
                                    @if($item->product->unitOfMeasure)
                                    <flux:text class="text-xs text-zinc-500">
                                        {{ $item->product->unitOfMeasure->abbreviation ?? $item->product->unitOfMeasure->name }}
                                    </flux:text>
                                    @endif
                                </div>
                                @if($item->reserved_quantity > 0)
                                <flux:text class="text-xs text-amber-600">
                                    {{ number_format($item->reserved_quantity, 2) }} {{ __('inventory.reserved') }}
                                </flux:text>
                                @endif
                                @if($item->available_quantity <= ($item->product->minimum_stock ?? 0))
                                <div class="mt-1">
                                    <flux:badge color="red" size="xs">{{ __('inventory.low_stock') }}</flux:badge>
                                </div>
                                @endif
                                @if($item->expiration_date && $item->isExpiringSoon())
                                <div class="mt-1">
                                    <flux:badge color="yellow" size="xs">{{ __('inventory.expiring_soon') }}</flux:badge>
                                </div>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm">
                                @if($item->lot_number)
                                <flux:text class="font-medium">{{ $item->lot_number }}</flux:text>
                                @endif
                                @if($item->expiration_date)
                                <flux:text class="text-zinc-500 block {{ $item->isExpired() ? 'text-red-600' : ($item->isExpiringSoon() ? 'text-yellow-600' : '') }}">
                                    {{ __('inventory.expires') }}: {{ $item->expiration_date->format('d/m/Y') }}
                                </flux:text>
                                @endif
                                @if(!$item->lot_number && !$item->expiration_date)
                                <flux:text class="text-zinc-400 text-xs">{{ __('inventory.no_lot') }}</flux:text>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-right">
                                <flux:text class="font-medium">
                                    ${{ number_format($item->unit_cost, 2) }}
                                </flux:text>
                                <flux:text class="text-sm text-zinc-500 block">
                                    {{ __('ui.total') }}: ${{ number_format($item->total_value, 2) }}
                                </flux:text>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm">
                                @if($item->last_counted_at)
                                <flux:text class="text-zinc-700 dark:text-zinc-300">
                                    {{ $item->last_counted_at->format('d/m/Y') }}
                                </flux:text>
                                @if($item->lastCounter)
                                <flux:text class="text-zinc-500 block text-xs">
                                    {{ __('ui.by') }} {{ $item->lastCounter->name }}
                                </flux:text>
                                @endif
                                @else
                                <flux:text class="text-zinc-400 text-xs">{{ __('inventory.no_count') }}</flux:text>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button variant="ghost" size="sm" icon="eye" :title="__('ui.view_details')" href="{{ route('inventory.products.show', $item->product) }}" wire:navigate>
                                    <span class="sr-only">{{ __('ui.view') }}</span>
                                </flux:button>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" href="{{ route('inventory.products.edit', $item->product) }}" wire:navigate>
                                            {{ __('ui.edit') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="clock" wire:click="viewMovementHistory({{ $item->id }})">
                                            {{ __('inventory.movement_history') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="pencil-square" href="{{ route('adjustments.create', ['inventory_id' => $item->id]) }}" wire:navigate>
                                            {{ __('inventory.adjust_stock') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="arrows-right-left" href="{{ route('transfers.create', ['product_id' => $item->product_id, 'warehouse_id' => $item->warehouse_id]) }}" wire:navigate>
                                            {{ __('inventory.transfer') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="calculator" href="{{ route('adjustments.create', ['inventory_id' => $item->id, 'type' => 'count']) }}" wire:navigate>
                                            {{ __('inventory.perform_count') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-12">
                            <flux:icon name="cube" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                            <flux:text class="text-zinc-500">{{ __('inventory.no_inventory_items_found') }}</flux:text>
                            <flux:text class="text-sm text-zinc-400 mt-2">
                                {{ __('inventory.try_changing_filters_or_add_products') }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <!-- Pagination -->
        @if($this->inventoryItems->hasPages())
        <div class="mt-6 px-6 pb-6">
            {{ $this->inventoryItems->links() }}
        </div>
        @endif
    </flux:card>
</div>
