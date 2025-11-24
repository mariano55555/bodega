<?php

use App\Models\Product;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public function mount(): void
    {
        // Component initialization
    }

    #[Computed]
    public function totalProducts(): int
    {
        return Product::count();
    }

    #[Computed]
    public function totalWarehouses(): int
    {
        return Warehouse::count();
    }

    #[Computed]
    public function lowStockAlerts(): int
    {
        // Placeholder for now, will be implemented when InventoryAlert model is ready
        return 3;
    }

    #[Computed]
    public function recentMovements()
    {
        // Placeholder data until InventoryMovement model is implemented
        return collect([
            (object) [
                'id' => 1,
                'product' => (object) ['name' => 'Producto Demo 1', 'sku' => 'PRD001'],
                'warehouse' => (object) ['name' => 'Almacén Central'],
                'movement_type' => 'entry',
                'quantity' => 50,
                'created_at' => now()->subHours(2),
            ],
            (object) [
                'id' => 2,
                'product' => (object) ['name' => 'Producto Demo 2', 'sku' => 'PRD002'],
                'warehouse' => (object) ['name' => 'Almacén Norte'],
                'movement_type' => 'exit',
                'quantity' => 25,
                'created_at' => now()->subHours(5),
            ],
        ]);
    }

    #[Computed]
    public function stockByWarehouse()
    {
        return Warehouse::withSum('inventory', 'available_quantity')
            ->get()
            ->map(function ($warehouse) {
                return (object) [
                    'name' => $warehouse->name,
                    'location' => $warehouse->location,
                    'total_items' => $warehouse->inventory_sum_available_quantity ?? 0,
                ];
            });
    }

    #[Computed]
    public function criticalStockProducts()
    {
        // Sample critical stock products
        return collect([
            (object) [
                'name' => 'Producto Crítico 1',
                'sku' => 'CRIT001',
                'category' => (object) ['name' => 'Categoría A'],
            ],
            (object) [
                'name' => 'Producto Crítico 2',
                'sku' => 'CRIT002',
                'category' => (object) ['name' => 'Categoría B'],
            ],
        ]);
    }

    public function with(): array
    {
        return [
            'title' => 'Resumen de Inventario',
        ];
    }
}; ?>

<div class="px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Inventario en Tiempo Real
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Monitorea los niveles de inventario y operaciones de almacén en tiempo real
                </flux:text>
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-wrap gap-3">
                <flux:button variant="primary" icon="plus" :href="route('inventory.movements.index')" wire:navigate>
                    Registrar Entrada
                </flux:button>
                <flux:button variant="outline" icon="minus" :href="route('inventory.movements.index')" wire:navigate>
                    Registrar Salida
                </flux:button>
                <flux:button variant="outline" icon="arrow-path" :href="route('transfers.index')" wire:navigate>
                    Crear Traslado
                </flux:button>
                <flux:button variant="outline" icon="chart-bar" :href="route('reports.inventory.index')" wire:navigate>
                    Generar Reporte
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Products -->
        <flux:card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-blue-600 dark:text-blue-400">
                        Productos
                    </flux:text>
                    <flux:heading size="2xl" class="text-blue-900 dark:text-blue-100">
                        {{ number_format($this->totalProducts) }}
                    </flux:heading>
                </div>
                <flux:icon name="cube" class="h-8 w-8 text-blue-500" />
            </div>
        </flux:card>

        <!-- Total Warehouses -->
        <flux:card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-green-200 dark:border-green-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">
                        Bodegas
                    </flux:text>
                    <flux:heading size="2xl" class="text-green-900 dark:text-green-100">
                        {{ number_format($this->totalWarehouses) }}
                    </flux:heading>
                </div>
                <flux:icon name="building-office" class="h-8 w-8 text-green-500" />
            </div>
        </flux:card>

        <!-- Low Stock Alerts -->
        <flux:card class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-red-200 dark:border-red-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-red-600 dark:text-red-400">
                        Alertas de Stock Bajo
                    </flux:text>
                    <flux:heading size="2xl" class="text-red-900 dark:text-red-100">
                        {{ number_format($this->lowStockAlerts) }}
                    </flux:heading>
                </div>
                <flux:icon name="exclamation-triangle" class="h-8 w-8 text-red-500" />
            </div>
        </flux:card>

        <!-- Recent Activity -->
        <flux:card class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border-purple-200 dark:border-purple-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-purple-600 dark:text-purple-400">
                        Actividad Reciente
                    </flux:text>
                    <flux:heading size="2xl" class="text-purple-900 dark:text-purple-100">
                        {{ number_format($this->recentMovements->count()) }}
                    </flux:heading>
                </div>
                <flux:icon name="clock" class="h-8 w-8 text-purple-500" />
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Stock by Warehouse -->
        <flux:card>
            <flux:heading>
                <flux:heading size="lg">Niveles de Stock</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Niveles de stock actuales por bodega
                </flux:text>
            </flux:heading>

            <div class="space-y-4">
                @forelse($this->stockByWarehouse as $warehouse)
                    <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <flux:icon name="building-office" class="h-5 w-5 text-zinc-500" />
                            <div>
                                <flux:text class="font-medium">{{ $warehouse->name }}</flux:text>
                                <flux:text class="text-sm text-zinc-500">{{ $warehouse->location }}</flux:text>
                            </div>
                        </div>
                        <div class="text-right">
                            <flux:text class="font-semibold text-lg">{{ number_format($warehouse->total_items) }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">artículos</flux:text>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <flux:icon name="building-office" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                        <flux:text class="text-zinc-500">No se encontraron bodegas</flux:text>
                    </div>
                @endforelse
            </div>
        </flux:card>

        <!-- Critical Stock Products -->
        <flux:card>
            <flux:heading>
                <flux:heading size="lg">Productos con Stock Crítico</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Productos que requieren atención inmediata
                </flux:text>
            </flux:heading>

            <div class="space-y-4">
                @forelse($this->criticalStockProducts as $product)
                    <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <div class="flex items-center gap-3">
                            <flux:icon name="exclamation-triangle" class="h-5 w-5 text-red-500" />
                            <div>
                                <flux:text class="font-medium">{{ $product->name }}</flux:text>
                                <flux:text class="text-sm text-zinc-500">{{ $product->sku }}</flux:text>
                            </div>
                        </div>
                        <flux:badge color="red" size="sm">
                            Stock Bajo
                        </flux:badge>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <flux:icon name="check-circle" class="h-12 w-12 text-green-400 mx-auto mb-3" />
                        <flux:text class="text-zinc-500">Todos los productos tienen niveles de stock adecuados</flux:text>
                    </div>
                @endforelse
            </div>

            @if($this->criticalStockProducts->count() > 0)
                <div class="mt-4">
                    <flux:button variant="outline" size="sm" :href="route('inventory.alerts.index')" wire:navigate>
                        Ver Todas las Alertas
                    </flux:button>
                </div>
            @endif
        </flux:card>
    </div>

    <!-- Recent Movements -->
    <flux:card class="mt-8">
        <flux:heading>
            <flux:heading size="lg">Movimientos Recientes</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                Últimos movimientos y transacciones de inventario
            </flux:text>
        </flux:heading>

        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Producto</flux:table.column>
                    <flux:table.column>Bodega</flux:table.column>
                    <flux:table.column>Tipo de Movimiento</flux:table.column>
                    <flux:table.column>Cantidad</flux:table.column>
                    <flux:table.column>Fecha</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->recentMovements as $movement)
                        <flux:table.row>
                            <flux:table.cell>
                                <div>
                                    <flux:text class="font-medium">{{ $movement->product->name }}</flux:text>
                                    <flux:text class="text-sm text-zinc-500">{{ $movement->product->sku }}</flux:text>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $movement->warehouse->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge
                                    :color="match($movement->movement_type) {
                                        'entry' => 'green',
                                        'exit' => 'red',
                                        'transfer' => 'blue',
                                        'adjustment' => 'yellow',
                                        default => 'zinc'
                                    }"
                                    size="sm"
                                >
                                    {{ match($movement->movement_type) {
                                        'entry' => 'Entrada',
                                        'exit' => 'Salida',
                                        'transfer' => 'Traslado',
                                        'adjustment' => 'Ajuste',
                                        default => ucfirst($movement->movement_type)
                                    } }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="font-medium {{ $movement->movement_type === 'entry' ? 'text-green-600' : ($movement->movement_type === 'exit' ? 'text-red-600' : 'text-zinc-600') }}">
                                    {{ $movement->movement_type === 'entry' ? '+' : ($movement->movement_type === 'exit' ? '-' : '') }}{{ number_format($movement->quantity) }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm">{{ $movement->created_at->format('Y-m-d H:i') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-8">
                                <flux:icon name="bars-3-bottom-left" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                                <flux:text class="text-zinc-500">No se encontraron movimientos recientes</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if($this->recentMovements->count() > 0)
            <div class="mt-4">
                <flux:button variant="outline" size="sm" :href="route('inventory.movements.index')" wire:navigate>
                    Ver Todos los Movimientos
                </flux:button>
            </div>
        @endif
    </flux:card>
</div>
