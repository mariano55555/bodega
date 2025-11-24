<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use App\Services\DashboardService;

new class extends Component {
    public int $selectedPeriod = 30; // days
    public string $activeTab = 'overview';

    #[Computed]
    public function dashboardService(): DashboardService
    {
        return new DashboardService(auth()->user());
    }

    #[Computed]
    public function metrics(): array
    {
        return $this->dashboardService->getMetrics($this->selectedPeriod);
    }

    #[Computed]
    public function movementChartData(): array
    {
        return $this->dashboardService->getMovementChartData($this->selectedPeriod);
    }

    #[Computed]
    public function inventoryValueChartData(): array
    {
        return $this->dashboardService->getInventoryValueChartData($this->selectedPeriod);
    }

    #[Computed]
    public function recentActivities(): array
    {
        return $this->dashboardService->getRecentActivities(8);
    }

    public function refreshData(): void
    {
        unset($this->metrics, $this->movementChartData, $this->inventoryValueChartData, $this->recentActivities);
        $this->js('$wire.$refresh()');
    }

    public function updatedSelectedPeriod(): void
    {
        unset($this->metrics, $this->movementChartData, $this->inventoryValueChartData);
    }
}; ?>

<div class="space-y-6">
    {{-- Dashboard Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent dark:from-blue-400 dark:to-purple-400">
                Panel de Control
            </flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                Bienvenido, {{ auth()->user()->name }} • {{ auth()->user()->roles->first()?->name ?? 'Usuario' }}
            </flux:text>
        </div>

        <div class="flex items-center gap-3">
            {{-- Period Selector --}}
            <flux:select wire:model.live="selectedPeriod" variant="listbox" size="sm" class="w-32">
                <flux:select.option value="7">7 días</flux:select.option>
                <flux:select.option value="30">30 días</flux:select.option>
                <flux:select.option value="90">90 días</flux:select.option>
            </flux:select>

            {{-- Refresh Button --}}
            <flux:button
                wire:click="refreshData"
                variant="outline"
                size="sm"
                icon="arrow-path"
                wire:loading.attr="disabled"
                wire:target="refreshData"
            >
                <span wire:loading.remove wire:target="refreshData">Actualizar</span>
                <span wire:loading wire:target="refreshData">Actualizando...</span>
            </flux:button>
        </div>
    </div>

    {{-- Key Metrics Cards - Gradient Style --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        {{-- Total Products --}}
        <flux:card class="overflow-hidden border-l-4 border-blue-500 bg-gradient-to-br from-blue-50 to-white dark:from-blue-950/20 dark:to-zinc-900">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <flux:text size="sm" class="font-medium text-blue-600 dark:text-blue-400">
                            Productos
                        </flux:text>
                        <flux:heading size="xl" class="mt-2 text-zinc-900 dark:text-zinc-100">
                            {{ number_format($this->metrics['overview']['total_products']) }}
                        </flux:heading>
                        <flux:text size="xs" class="mt-1 text-zinc-600 dark:text-zinc-400">
                            {{ number_format($this->metrics['overview']['unique_skus']) }} SKUs únicos
                        </flux:text>
                    </div>
                    <div class="rounded-lg bg-blue-500/10 p-3 dark:bg-blue-500/20">
                        <flux:icon name="cube" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Total Warehouses --}}
        <flux:card class="overflow-hidden border-l-4 border-green-500 bg-gradient-to-br from-green-50 to-white dark:from-green-950/20 dark:to-zinc-900">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <flux:text size="sm" class="font-medium text-green-600 dark:text-green-400">
                            Bodegas
                        </flux:text>
                        <flux:heading size="xl" class="mt-2 text-zinc-900 dark:text-zinc-100">
                            {{ number_format($this->metrics['overview']['total_warehouses']) }}
                        </flux:heading>
                        <flux:text size="xs" class="mt-1 text-zinc-600 dark:text-zinc-400">
                            Activas
                        </flux:text>
                    </div>
                    <div class="rounded-lg bg-green-500/10 p-3 dark:bg-green-500/20">
                        <flux:icon name="building-office" class="h-6 w-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Total Value --}}
        <flux:card class="overflow-hidden border-l-4 border-emerald-500 bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-950/20 dark:to-zinc-900">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <flux:text size="sm" class="font-medium text-emerald-600 dark:text-emerald-400">
                            Valor Total
                        </flux:text>
                        <flux:heading size="xl" class="mt-2 text-zinc-900 dark:text-zinc-100">
                            ${{ number_format($this->metrics['overview']['total_value'], 0) }}
                        </flux:heading>
                        <flux:text size="xs" class="mt-1 text-zinc-600 dark:text-zinc-400">
                            {{ number_format($this->metrics['overview']['total_quantity'], 0) }} unidades
                        </flux:text>
                    </div>
                    <div class="rounded-lg bg-emerald-500/10 p-3 dark:bg-emerald-500/20">
                        <flux:icon name="currency-dollar" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Movements --}}
        <flux:card class="overflow-hidden border-l-4 border-purple-500 bg-gradient-to-br from-purple-50 to-white dark:from-purple-950/20 dark:to-zinc-900">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <flux:text size="sm" class="font-medium text-purple-600 dark:text-purple-400">
                            Movimientos
                        </flux:text>
                        <flux:heading size="xl" class="mt-2 text-zinc-900 dark:text-zinc-100">
                            {{ number_format($this->metrics['movements']['total']) }}
                        </flux:heading>
                        <div class="mt-1 flex items-center gap-1">
                            @if($this->metrics['movements']['trend'] > 0)
                                <flux:icon name="arrow-trending-up" class="h-3 w-3 text-green-600 dark:text-green-400" />
                                <flux:text size="xs" class="text-green-600 dark:text-green-400">
                                    +{{ $this->metrics['movements']['trend'] }}%
                                </flux:text>
                            @elseif($this->metrics['movements']['trend'] < 0)
                                <flux:icon name="arrow-trending-down" class="h-3 w-3 text-red-600 dark:text-red-400" />
                                <flux:text size="xs" class="text-red-600 dark:text-red-400">
                                    {{ $this->metrics['movements']['trend'] }}%
                                </flux:text>
                            @else
                                <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400">
                                    Sin cambios
                                </flux:text>
                            @endif
                        </div>
                    </div>
                    <div class="rounded-lg bg-purple-500/10 p-3 dark:bg-purple-500/20">
                        <flux:icon name="arrows-right-left" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Alerts --}}
        <flux:card class="overflow-hidden border-l-4 border-orange-500 bg-gradient-to-br from-orange-50 to-white dark:from-orange-950/20 dark:to-zinc-900">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <flux:text size="sm" class="font-medium text-orange-600 dark:text-orange-400">
                            Alertas Activas
                        </flux:text>
                        <flux:heading size="xl" class="mt-2 text-zinc-900 dark:text-zinc-100">
                            {{ number_format($this->metrics['alerts']['total']) }}
                        </flux:heading>
                        <flux:text size="xs" class="mt-1 text-zinc-600 dark:text-zinc-400">
                            {{ $this->metrics['alerts']['critical'] }} críticas
                        </flux:text>
                    </div>
                    <div class="rounded-lg bg-orange-500/10 p-3 dark:bg-orange-500/20">
                        <flux:icon name="exclamation-triangle" class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Low Stock --}}
        <flux:card class="overflow-hidden border-l-4 border-red-500 bg-gradient-to-br from-red-50 to-white dark:from-red-950/20 dark:to-zinc-900">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <flux:text size="sm" class="font-medium text-red-600 dark:text-red-400">
                            Stock Bajo
                        </flux:text>
                        <flux:heading size="xl" class="mt-2 text-zinc-900 dark:text-zinc-100">
                            {{ number_format($this->metrics['low_stock_count']) }}
                        </flux:text>
                        <flux:text size="xs" class="mt-1 text-zinc-600 dark:text-zinc-400">
                            Productos
                        </flux:text>
                    </div>
                    <div class="rounded-lg bg-red-500/10 p-3 dark:bg-red-500/20">
                        <flux:icon name="inbox" class="h-6 w-6 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Charts Section --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Movements Over Time Chart --}}
        <flux:card>
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                    Tendencia de Movimientos
                </flux:heading>
                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                    Entradas vs Salidas ({{ $selectedPeriod }} días)
                </flux:text>
            </div>

            <div class="p-4">
                <flux:chart :value="$this->movementChartData" class="aspect-[3/1]">
                    <flux:chart.svg>
                        <flux:chart.line field="entries" class="text-green-500 dark:text-green-400" />
                        <flux:chart.line field="exits" class="text-red-500 dark:text-red-400" />
                        <flux:chart.axis axis="x" field="date">
                            <flux:chart.axis.tick />
                            <flux:chart.axis.line />
                        </flux:chart.axis>
                        <flux:chart.axis axis="y">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>
                        <flux:chart.cursor />
                    </flux:chart.svg>

                    <flux:chart.tooltip>
                        <flux:chart.tooltip.heading field="date" :format="['year' => 'numeric', 'month' => 'short', 'day' => 'numeric']" />
                        <flux:chart.tooltip.value field="entries" label="Entradas" />
                        <flux:chart.tooltip.value field="exits" label="Salidas" />
                        <flux:chart.tooltip.value field="total" label="Total" />
                    </flux:chart.tooltip>
                </flux:chart>

                <div class="mt-4 flex justify-center gap-6">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-green-500"></div>
                        <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">Entradas ({{ number_format($this->metrics['movements']['entries']) }})</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-red-500"></div>
                        <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">Salidas ({{ number_format($this->metrics['movements']['exits']) }})</flux:text>
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Inventory Value Trend --}}
        <flux:card>
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                    Valor del Inventario
                </flux:heading>
                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                    Tendencia de valor total ({{ $selectedPeriod }} días)
                </flux:text>
            </div>

            <div class="p-4">
                <flux:chart :value="$this->inventoryValueChartData" class="aspect-[3/1]">
                    <flux:chart.svg>
                        <flux:chart.line field="value" class="text-blue-500 dark:text-blue-400" />
                        <flux:chart.area field="value" class="text-blue-200/50 dark:text-blue-400/30" />
                        <flux:chart.axis axis="x" field="date">
                            <flux:chart.axis.tick />
                            <flux:chart.axis.line />
                        </flux:chart.axis>
                        <flux:chart.axis axis="y" tick-prefix="$" :format="[
                            'notation' => 'compact',
                            'compactDisplay' => 'short',
                            'maximumFractionDigits' => 1,
                        ]">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>
                        <flux:chart.cursor />
                    </flux:chart.svg>

                    <flux:chart.tooltip>
                        <flux:chart.tooltip.heading field="date" :format="['year' => 'numeric', 'month' => 'short', 'day' => 'numeric']" />
                        <flux:chart.tooltip.value field="value" label="Valor" :format="['style' => 'currency', 'currency' => 'USD']" />
                    </flux:chart.tooltip>
                </flux:chart>
            </div>
        </flux:card>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Top Products --}}
        <flux:card>
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                    Productos Más Activos
                </flux:heading>
                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                    Por frecuencia de movimientos
                </flux:text>
            </div>

            <div class="p-4">
                @if(count($this->metrics['top_products']) > 0)
                    <div class="space-y-3">
                        @foreach($this->metrics['top_products'] as $index => $product)
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-purple-500 text-sm font-bold text-white">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1">
                                    <flux:text size="sm" class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ Str::limit($product['product_name'], 30) }}
                                    </flux:text>
                                    <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400">
                                        SKU: {{ $product['product_sku'] }}
                                    </flux:text>
                                </div>
                                <flux:badge variant="solid" size="sm" class="bg-blue-500 text-white">
                                    {{ $product['movement_count'] }}
                                </flux:badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <div class="rounded-full bg-zinc-100 p-3 dark:bg-zinc-800">
                            <flux:icon name="cube" class="h-6 w-6 text-zinc-400" />
                        </div>
                        <flux:heading size="sm" class="mt-3 text-zinc-900 dark:text-zinc-100">
                            Sin datos
                        </flux:heading>
                        <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                            No hay movimientos en este período
                        </flux:text>
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- Warehouse Utilization --}}
        <flux:card>
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                    Utilización de Bodegas
                </flux:heading>
                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                    Capacidad vs Uso actual
                </flux:text>
            </div>

            <div class="p-4">
                @if(count($this->metrics['warehouse_utilization']) > 0)
                    <div class="space-y-4">
                        @foreach($this->metrics['warehouse_utilization'] as $warehouse)
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <flux:text size="sm" class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ Str::limit($warehouse['name'], 25) }}
                                    </flux:text>
                                    <flux:text size="sm" class="font-medium text-zinc-600 dark:text-zinc-400">
                                        {{ $warehouse['utilization_percent'] }}%
                                    </flux:text>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <div
                                        class="h-full rounded-full transition-all {{ $warehouse['utilization_percent'] >= 90 ? 'bg-red-500' : ($warehouse['utilization_percent'] >= 75 ? 'bg-orange-500' : 'bg-green-500') }}"
                                        style="width: {{ min($warehouse['utilization_percent'], 100) }}%"
                                    ></div>
                                </div>
                                <flux:text size="xs" class="mt-1 text-zinc-600 dark:text-zinc-400">
                                    {{ number_format($warehouse['current_usage'], 0) }} / {{ number_format($warehouse['capacity'], 0) }} unidades
                                </flux:text>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <div class="rounded-full bg-zinc-100 p-3 dark:bg-zinc-800">
                            <flux:icon name="building-office" class="h-6 w-6 text-zinc-400" />
                        </div>
                        <flux:heading size="sm" class="mt-3 text-zinc-900 dark:text-zinc-100">
                            Sin bodegas
                        </flux:heading>
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- Recent Activities --}}
        <flux:card>
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                            Actividad Reciente
                        </flux:heading>
                        <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                            Últimos movimientos
                        </flux:text>
                    </div>
                    <flux:button
                        variant="ghost"
                        size="sm"
                        :href="route('inventory.movements.index')"
                        wire:navigate
                    >
                        Ver todos
                    </flux:button>
                </div>
            </div>

            <div class="p-4">
                @if(count($this->recentActivities) > 0)
                    <div class="space-y-3">
                        @foreach($this->recentActivities as $activity)
                            <div class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 transition-all hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600">
                                <div class="mt-0.5 rounded-full bg-zinc-100 p-2 dark:bg-zinc-800">
                                    @if(str_contains($activity['type'], 'entry') || str_contains($activity['type'], 'purchase'))
                                        <flux:icon name="arrow-down" class="h-4 w-4 text-green-600 dark:text-green-400" />
                                    @elseif(str_contains($activity['type'], 'exit') || str_contains($activity['type'], 'dispatch'))
                                        <flux:icon name="arrow-up" class="h-4 w-4 text-red-600 dark:text-red-400" />
                                    @else
                                        <flux:icon name="arrow-path" class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <flux:text size="sm" class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ Str::limit($activity['product'], 30) }}
                                    </flux:text>
                                    <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400">
                                        {{ $activity['type_label'] }} • {{ $activity['warehouse'] }}
                                    </flux:text>
                                    <flux:text size="xs" class="mt-1 text-zinc-500 dark:text-zinc-500">
                                        {{ $activity['created_at']->diffForHumans() }}
                                    </flux:text>
                                </div>
                                <flux:text size="sm" class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($activity['quantity'], 2) }}
                                </flux:text>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <div class="rounded-full bg-zinc-100 p-3 dark:bg-zinc-800">
                            <flux:icon name="arrows-right-left" class="h-6 w-6 text-zinc-400" />
                        </div>
                        <flux:heading size="sm" class="mt-3 text-zinc-900 dark:text-zinc-100">
                            Sin actividad
                        </flux:heading>
                        <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                            No hay movimientos recientes
                        </flux:text>
                    </div>
                @endif
            </div>
        </flux:card>
    </div>

    {{-- Quick Actions --}}
    <flux:card>
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                Acciones Rápidas
            </flux:heading>
        </div>

        <div class="p-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <flux:button
                    variant="outline"
                    icon="plus-circle"
                    :href="route('inventory.products.index')"
                    wire:navigate
                    class="justify-start"
                >
                    Nuevo Producto
                </flux:button>

                <flux:button
                    variant="outline"
                    icon="arrows-right-left"
                    :href="route('transfers.index')"
                    wire:navigate
                    class="justify-start"
                >
                    Transferir Stock
                </flux:button>

                <flux:button
                    variant="outline"
                    icon="shopping-cart"
                    :href="route('purchases.index')"
                    wire:navigate
                    class="justify-start"
                >
                    Nueva Compra
                </flux:button>

                <flux:button
                    variant="outline"
                    icon="chart-bar"
                    :href="route('reports.inventory.index')"
                    wire:navigate
                    class="justify-start"
                >
                    Ver Reportes
                </flux:button>
            </div>
        </div>
    </flux:card>
</div>
