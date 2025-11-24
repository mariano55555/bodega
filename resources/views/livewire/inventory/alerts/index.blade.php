<?php

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $activeTab = 'low_stock';

    public string $search = '';

    public ?int $selectedWarehouseId = null;

    public function mount(): void
    {
        // Component initialization
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    #[Computed]
    public function warehouses()
    {
        $user = auth()->user();

        return Warehouse::query()
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function lowStockProducts()
    {
        $user = auth()->user();

        return Product::with(['inventory.warehouse'])
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->whereHas('inventory', function ($query) {
                $query->whereRaw('available_quantity < (SELECT minimum_stock FROM products WHERE products.id = inventory.product_id)')
                    ->when($this->selectedWarehouseId, function ($q) {
                        $q->where('warehouse_id', $this->selectedWarehouseId);
                    });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->get();
    }

    #[Computed]
    public function expiringSoonProducts()
    {
        $user = auth()->user();

        return Inventory::with(['product', 'warehouse'])
            ->when($user->company_id, fn ($q) => $q->whereHas('warehouse', fn ($w) => $w->where('company_id', $user->company_id)))
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [now(), now()->addDays(30)])
            ->when($this->selectedWarehouseId, function ($query) {
                $query->where('warehouse_id', $this->selectedWarehouseId);
            })
            ->when($this->search, function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('expiration_date')
            ->get();
    }

    #[Computed]
    public function overstockProducts()
    {
        $user = auth()->user();

        return Product::with(['inventory.warehouse'])
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->whereHas('inventory', function ($query) {
                $query->whereRaw('available_quantity > (SELECT maximum_stock FROM products WHERE products.id = inventory.product_id AND maximum_stock IS NOT NULL)')
                    ->when($this->selectedWarehouseId, function ($q) {
                        $q->where('warehouse_id', $this->selectedWarehouseId);
                    });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->get();
    }

    #[Computed]
    public function zeroStockProducts()
    {
        $user = auth()->user();

        return Product::with(['inventory.warehouse'])
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->whereDoesntHave('inventory', function ($query) {
                $query->where('available_quantity', '>', 0);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->get();
    }

    public function getStockStatus(Product $product, ?Inventory $inventory = null): array
    {
        if (! $inventory || $inventory->available_quantity <= 0) {
            return ['status' => 'out_of_stock', 'color' => 'red', 'label' => 'Sin Stock'];
        }

        if ($inventory->available_quantity < $product->minimum_stock) {
            return ['status' => 'low_stock', 'color' => 'yellow', 'label' => 'Stock Bajo'];
        }

        if ($product->maximum_stock && $inventory->available_quantity > $product->maximum_stock) {
            return ['status' => 'overstock', 'color' => 'orange', 'label' => 'Exceso de Stock'];
        }

        return ['status' => 'normal', 'color' => 'green', 'label' => 'Normal'];
    }

    public function with(): array
    {
        return [
            'title' => 'Alertas de Stock',
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
            Alertas de Stock
        </flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            Monitoree los niveles de inventario y reciba alertas en tiempo real para situaciones críticas de stock
        </flux:text>
    </div>

    <!-- Filter Controls -->
    <flux:card class="mb-8">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <flux:input
                    wire:model.live="search"
                    placeholder="Buscar productos por nombre o SKU"
                    icon="magnifying-glass"
                />
            </div>

            <!-- Warehouse Filter -->
            <div class="lg:w-64">
                <flux:select wire:model.live="selectedWarehouseId" placeholder="Todas las bodegas">
                    <flux:select.option value="">Todas las bodegas</flux:select.option>
                    @foreach($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">
                            {{ $warehouse->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:card>

    <!-- Alert Tabs -->
    <flux:tabs wire:model.live="activeTab" class="mb-8">
        <flux:tab name="low_stock">
            <flux:icon name="exclamation-triangle" class="h-4 w-4" />
            Stock Bajo
            @if($this->lowStockProducts->count() > 0)
                <flux:badge color="red" size="sm">{{ $this->lowStockProducts->count() }}</flux:badge>
            @endif
        </flux:tab>

        <flux:tab name="expiring_soon">
            <flux:icon name="clock" class="h-4 w-4" />
            Por Vencer
            @if($this->expiringSoonProducts->count() > 0)
                <flux:badge color="yellow" size="sm">{{ $this->expiringSoonProducts->count() }}</flux:badge>
            @endif
        </flux:tab>

        <flux:tab name="overstock">
            <flux:icon name="arrow-trending-up" class="h-4 w-4" />
            Exceso de Stock
            @if($this->overstockProducts->count() > 0)
                <flux:badge color="orange" size="sm">{{ $this->overstockProducts->count() }}</flux:badge>
            @endif
        </flux:tab>

        <flux:tab name="zero_stock">
            <flux:icon name="x-circle" class="h-4 w-4" />
            Sin Stock
            @if($this->zeroStockProducts->count() > 0)
                <flux:badge color="zinc" size="sm">{{ $this->zeroStockProducts->count() }}</flux:badge>
            @endif
        </flux:tab>
    </flux:tabs>

    <!-- Alert Content -->
    <flux:card>
        @if($activeTab === 'low_stock')
            <!-- Low Stock Alerts -->
            <flux:heading>
                <flux:heading size="lg" class="text-red-600 dark:text-red-400">
                    Alerta de Stock Bajo
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Productos por debajo del nivel mínimo de stock
                </flux:text>
            </flux:heading>

            <div class="space-y-4">
                @forelse($this->lowStockProducts as $product)
                    @foreach($product->inventory as $inventory)
                        @php $status = $this->getStockStatus($product, $inventory); @endphp
                        <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                            <div class="flex items-center gap-4">
                                <flux:icon name="exclamation-triangle" class="h-6 w-6 text-red-500" />
                                <div>
                                    <flux:heading size="sm">{{ $product->name }}</flux:heading>
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $product->sku }} • {{ $inventory->warehouse->name }}
                                    </flux:text>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:badge color="{{ $status['color'] }}" size="sm">
                                        {{ $status['label'] }}
                                    </flux:badge>
                                </div>
                                <flux:text class="text-sm">
                                    Actual: {{ number_format($inventory->available_quantity, 2) }}
                                </flux:text>
                                <flux:text class="text-sm text-zinc-500">
                                    Mínimo: {{ number_format($product->minimum_stock, 2) }}
                                </flux:text>
                            </div>
                        </div>
                    @endforeach
                @empty
                    <div class="text-center py-12">
                        <flux:icon name="check-circle" class="h-16 w-16 text-green-400 mx-auto mb-4" />
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400 mb-2">
                            ¡Todo en orden!
                        </flux:heading>
                        <flux:text class="text-zinc-500">
                            No hay productos por debajo del nivel mínimo de stock
                        </flux:text>
                    </div>
                @endforelse
            </div>

        @elseif($activeTab === 'expiring_soon')
            <!-- Expiring Soon Alerts -->
            <flux:heading>
                <flux:heading size="lg" class="text-yellow-600 dark:text-yellow-400">
                    Productos por Vencer
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Productos que vencen en los próximos 30 días
                </flux:text>
            </flux:heading>

            <div class="space-y-4">
                @forelse($this->expiringSoonProducts as $inventory)
                    @php
                        $daysUntilExpiry = now()->diffInDays($inventory->expiration_date);
                        $urgencyColor = $daysUntilExpiry <= 7 ? 'red' : ($daysUntilExpiry <= 14 ? 'yellow' : 'blue');
                    @endphp
                    <div class="flex items-center justify-between p-4 bg-{{ $urgencyColor }}-50 dark:bg-{{ $urgencyColor }}-900/20 rounded-lg border border-{{ $urgencyColor }}-200 dark:border-{{ $urgencyColor }}-800">
                        <div class="flex items-center gap-4">
                            <flux:icon name="clock" class="h-6 w-6 text-{{ $urgencyColor }}-500" />
                            <div>
                                <flux:heading size="sm">{{ $inventory->product->name }}</flux:heading>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $inventory->product->sku }} • {{ $inventory->warehouse->name }}
                                </flux:text>
                                @if($inventory->lot_number)
                                    <flux:text class="text-xs text-zinc-500">
                                        Lote: {{ $inventory->lot_number }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <flux:badge color="{{ $urgencyColor }}" size="sm">
                                {{ $daysUntilExpiry }} días
                            </flux:badge>
                            <flux:text class="text-sm block mt-1">
                                {{ $inventory->expiration_date->format('d/m/Y') }}
                            </flux:text>
                            <flux:text class="text-sm text-zinc-500">
                                {{ number_format($inventory->available_quantity, 2) }} unidades
                            </flux:text>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <flux:icon name="check-circle" class="h-16 w-16 text-green-400 mx-auto mb-4" />
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400 mb-2">
                            Sin Productos por Vencer
                        </flux:heading>
                        <flux:text class="text-zinc-500">
                            No hay productos que venzan en los próximos 30 días
                        </flux:text>
                    </div>
                @endforelse
            </div>

        @elseif($activeTab === 'overstock')
            <!-- Overstock Alerts -->
            <flux:heading>
                <flux:heading size="lg" class="text-orange-600 dark:text-orange-400">
                    Alerta de Exceso de Stock
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Productos por encima del nivel máximo de stock
                </flux:text>
            </flux:heading>

            <div class="space-y-4">
                @forelse($this->overstockProducts as $product)
                    @foreach($product->inventory as $inventory)
                        <div class="flex items-center justify-between p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                            <div class="flex items-center gap-4">
                                <flux:icon name="arrow-trending-up" class="h-6 w-6 text-orange-500" />
                                <div>
                                    <flux:heading size="sm">{{ $product->name }}</flux:heading>
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $product->sku }} • {{ $inventory->warehouse->name }}
                                    </flux:text>
                                </div>
                            </div>
                            <div class="text-right">
                                <flux:badge color="orange" size="sm">
                                    Exceso de Stock
                                </flux:badge>
                                <flux:text class="text-sm block mt-1">
                                    Actual: {{ number_format($inventory->available_quantity, 2) }}
                                </flux:text>
                                <flux:text class="text-sm text-zinc-500">
                                    Máximo: {{ number_format($product->maximum_stock, 2) }}
                                </flux:text>
                            </div>
                        </div>
                    @endforeach
                @empty
                    <div class="text-center py-12">
                        <flux:icon name="check-circle" class="h-16 w-16 text-green-400 mx-auto mb-4" />
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400 mb-2">
                            Niveles de Stock Normales
                        </flux:heading>
                        <flux:text class="text-zinc-500">
                            No hay productos con exceso de stock actualmente
                        </flux:text>
                    </div>
                @endforelse
            </div>

        @else
            <!-- Zero Stock Alerts -->
            <flux:heading>
                <flux:heading size="lg" class="text-zinc-600 dark:text-zinc-400">
                    Sin Stock
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Productos sin stock disponible
                </flux:text>
            </flux:heading>

            <div class="space-y-4">
                @forelse($this->zeroStockProducts as $product)
                    <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-4">
                            <flux:icon name="x-circle" class="h-6 w-6 text-zinc-500" />
                            <div>
                                <flux:heading size="sm">{{ $product->name }}</flux:heading>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $product->sku }}
                                </flux:text>
                            </div>
                        </div>
                        <div class="text-right">
                            <flux:badge color="zinc" size="sm">
                                Sin Stock
                            </flux:badge>
                            <flux:text class="text-sm block mt-1 text-zinc-500">
                                No hay stock disponible
                            </flux:text>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <flux:icon name="check-circle" class="h-16 w-16 text-green-400 mx-auto mb-4" />
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400 mb-2">
                            Todos los Productos con Stock
                        </flux:heading>
                        <flux:text class="text-zinc-500">
                            Todos los productos tienen stock disponible
                        </flux:text>
                    </div>
                @endforelse
            </div>
        @endif
    </flux:card>

    <!-- Quick Actions -->
    <div class="mt-8 flex flex-wrap gap-4">
        <flux:button variant="primary" icon="plus" :href="route('inventory.movements.index')" wire:navigate>
            Registrar Entrada
        </flux:button>
        <flux:button variant="outline" icon="arrow-path" :href="route('transfers.index')" wire:navigate>
            Crear Traslado
        </flux:button>
        <flux:button variant="outline" icon="chart-bar">
            Exportar Reporte
        </flux:button>
    </div>
</div>

<!-- Auto-refresh functionality -->
<script>
document.addEventListener('livewire:initialized', () => {
    // Auto-refresh alerts every 30 seconds
    setInterval(() => {
        @this.$refresh();
    }, 30000);
});
</script>
