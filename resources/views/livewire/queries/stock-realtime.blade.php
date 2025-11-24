<?php

use Livewire\Volt\Component;
use App\Models\{Inventory, Product, Warehouse, ProductCategory};
use Livewire\Attributes\{Computed, Layout};
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $warehouse = '';
    public string $category = '';

    #[Computed]
    public function stockData()
    {
        $query = Inventory::query()
            ->with(['product.category', 'product.unitOfMeasure', 'warehouse', 'storageLocation'])
            ->active()
            ->whereHas('product', fn($q) => $q->where('track_inventory', true));

        if ($this->search) {
            $query->whereHas('product', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%")
                  ->orWhere('barcode', 'like', "%{$this->search}%");
            });
        }

        if ($this->warehouse) {
            $query->where('warehouse_id', $this->warehouse);
        }

        if ($this->category) {
            $query->whereHas('product.category', fn($q) => $q->where('id', $this->category));
        }

        return $query->orderBy('updated_at', 'desc')->paginate(20);
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::active()->get(['id', 'name']);
    }

    #[Computed]
    public function categories()
    {
        return ProductCategory::active()->get(['id', 'name']);
    }

    #[Computed]
    public function stockSummary()
    {
        return [
            'total_items' => Inventory::active()->count(),
            'total_quantity' => Inventory::active()->sum('available_quantity'),
            'total_value' => Inventory::active()->sum('total_value'),
            'warehouses_count' => Warehouse::active()->count(),
        ];
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->warehouse = '';
        $this->category = '';
        $this->resetPage();
    }

    public function with(): array
    {
        return ['title' => __('Consulta de Stock en Tiempo Real')];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">Stock en Tiempo Real</flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">Visualiza el stock actual de productos en todos los almacenes</flux:text>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <flux:card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-blue-600">Total Elementos</flux:text>
                    <flux:heading size="2xl" class="text-blue-900">{{ number_format($this->stockSummary['total_items']) }}</flux:heading>
                </div>
                <flux:icon name="cube" class="h-8 w-8 text-blue-500" />
            </div>
        </flux:card>
        <flux:card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-green-600">Cantidad Total</flux:text>
                    <flux:heading size="2xl" class="text-green-900">{{ number_format($this->stockSummary['total_quantity'], 0) }}</flux:heading>
                </div>
                <flux:icon name="cube-transparent" class="h-8 w-8 text-green-500" />
            </div>
        </flux:card>
        <flux:card class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-purple-600">Valor Total</flux:text>
                    <flux:heading size="2xl" class="text-purple-900">${{ number_format($this->stockSummary['total_value'], 0) }}</flux:heading>
                </div>
                <flux:icon name="currency-dollar" class="h-8 w-8 text-purple-500" />
            </div>
        </flux:card>
        <flux:card class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-amber-600">Almacenes</flux:text>
                    <flux:heading size="2xl" class="text-amber-900">{{ $this->stockSummary['warehouses_count'] }}</flux:heading>
                </div>
                <flux:icon name="building-storefront" class="h-8 w-8 text-amber-500" />
            </div>
        </flux:card>
    </div>

    <flux:card class="mb-6">
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar productos..." icon="magnifying-glass" />
                <flux:select wire:model.live="warehouse" placeholder="Todos los almacenes">
                    @foreach($this->warehouses as $w)
                    <flux:select.option value="{{ $w->id }}">{{ $w->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="category" placeholder="Todas las categorías">
                    @foreach($this->categories as $cat)
                    <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            @if($search || $warehouse || $category)
            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">Limpiar Filtros</flux:button>
            </div>
            @endif
        </div>
    </flux:card>

    <flux:card>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Producto</flux:table.column>
                    <flux:table.column>Almacén</flux:table.column>
                    <flux:table.column class="text-right">Disponible</flux:table.column>
                    <flux:table.column class="text-right">Reservado</flux:table.column>
                    <flux:table.column class="text-right">Total</flux:table.column>
                    <flux:table.column class="text-right">Valor</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->stockData as $item)
                    <flux:table.row wire:key="stock-{{ $item->id }}">
                        <flux:table.cell>
                            <flux:text class="font-medium">{{ $item->product->name }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">{{ $item->product->sku }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text>{{ $item->warehouse->name }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:text class="font-medium {{ $item->available_quantity <= ($item->product->minimum_stock ?? 0) ? 'text-red-600' : '' }}">
                                {{ number_format($item->available_quantity, 2) }}
                            </flux:text>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:text class="{{ $item->reserved_quantity > 0 ? 'font-medium text-amber-600' : 'text-zinc-400' }}">
                                {{ number_format($item->reserved_quantity, 2) }}
                            </flux:text>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:text class="font-medium text-blue-600">{{ number_format($item->total_quantity, 2) }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:text class="font-medium">${{ number_format($item->total_value, 2) }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($item->available_quantity <= 0)
                            <flux:badge color="red" size="sm">Sin Stock</flux:badge>
                            @elseif($item->available_quantity <= ($item->product->minimum_stock ?? 0))
                            <flux:badge color="yellow" size="sm">Stock Bajo</flux:badge>
                            @else
                            <flux:badge color="green" size="sm">Disponible</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-12">
                            <flux:icon name="cube" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                            <flux:text class="text-zinc-500">No se encontró stock</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if($this->stockData->hasPages())
        <div class="mt-6 px-6 pb-6">{{ $this->stockData->links() }}</div>
        @endif
    </flux:card>
</div>
