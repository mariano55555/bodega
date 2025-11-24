<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public function with(): array
    {
        return [
            'title' => 'Reportes de Inventario',
        ];
    }
}; ?>

<div class="px-4 sm:px-6 lg:px-8 py-8">
    <flux:heading size="xl" class="mb-6">Reportes de Inventario</flux:heading>

    <flux:text class="mb-8 text-zinc-600 dark:text-zinc-400">
        Acceda a los diferentes reportes de inventario disponibles en el sistema.
    </flux:text>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        {{-- Consolidated Inventory Report --}}
        <flux:card>
            <div class="flex h-full flex-col">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.cube class="size-6 text-blue-600 dark:text-blue-400" />
                </div>

                <flux:heading size="lg" class="mb-2">Inventario Consolidado</flux:heading>

                <flux:text class="mb-4 flex-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Visualice el inventario consolidado por bodega individual, fraccionaria o global con filtros avanzados.
                </flux:text>

                <flux:button :href="route('reports.inventory.consolidated')" variant="primary" class="w-full">
                    Ver Reporte
                    <flux:icon.arrow-right class="ml-2 size-4" />
                </flux:button>
            </div>
        </flux:card>

        {{-- Inventory Value Report --}}
        <flux:card>
            <div class="flex h-full flex-col">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                    <flux:icon.currency-dollar class="size-6 text-green-600 dark:text-green-400" />
                </div>

                <flux:heading size="lg" class="mb-2">Valor de Inventarios</flux:heading>

                <flux:text class="mb-4 flex-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Reporte del valor total de inventarios con desglose por bodega y producto.
                </flux:text>

                <flux:button :href="route('reports.inventory.value')" variant="primary" class="w-full">
                    Ver Reporte
                    <flux:icon.arrow-right class="ml-2 size-4" />
                </flux:button>
            </div>
        </flux:card>

        {{-- Inventory Rotation Report --}}
        <flux:card>
            <div class="flex h-full flex-col">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <flux:icon.arrow-path class="size-6 text-purple-600 dark:text-purple-400" />
                </div>

                <flux:heading size="lg" class="mb-2">Rotación de Inventarios</flux:heading>

                <flux:text class="mb-4 flex-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Análisis de rotación de inventarios con clasificación por producto y tasa de rotación.
                </flux:text>

                <flux:button :href="route('reports.inventory.rotation')" variant="primary" class="w-full">
                    Ver Reporte
                    <flux:icon.arrow-right class="ml-2 size-4" />
                </flux:button>
            </div>
        </flux:card>

        {{-- Kardex Report --}}
        <flux:card>
            <div class="flex h-full flex-col">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30">
                    <flux:icon.document-text class="size-6 text-orange-600 dark:text-orange-400" />
                </div>

                <flux:heading size="lg" class="mb-2">Kardex de Inventario</flux:heading>

                <flux:text class="mb-4 flex-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Historial detallado de movimientos por producto y almacén con exportación a PDF y Excel.
                </flux:text>

                <flux:button :href="route('reports.kardex')" variant="primary" class="w-full">
                    Ver Reporte
                    <flux:icon.arrow-right class="ml-2 size-4" />
                </flux:button>
            </div>
        </flux:card>

        {{-- Monthly Movements Report --}}
        <flux:card>
            <div class="flex h-full flex-col">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                    <flux:icon.calendar class="size-6 text-indigo-600 dark:text-indigo-400" />
                </div>

                <flux:heading size="lg" class="mb-2">Movimientos Mensuales</flux:heading>

                <flux:text class="mb-4 flex-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Resumen de movimientos mensuales por período con estadísticas de entradas y salidas.
                </flux:text>

                <flux:button :href="route('reports.movements.monthly')" variant="primary" class="w-full">
                    Ver Reporte
                    <flux:icon.arrow-right class="ml-2 size-4" />
                </flux:button>
            </div>
        </flux:card>

        {{-- Transfers Report --}}
        <flux:card>
            <div class="flex h-full flex-col">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-900/30">
                    <flux:icon.arrow-right-circle class="size-6 text-teal-600 dark:text-teal-400" />
                </div>

                <flux:heading size="lg" class="mb-2">Traslados entre Bodegas</flux:heading>

                <flux:text class="mb-4 flex-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Reporte de traslados entre bodegas con filtros por origen, destino y estado.
                </flux:text>

                <flux:button :href="route('reports.movements.transfers')" variant="primary" class="w-full">
                    Ver Reporte
                    <flux:icon.arrow-right class="ml-2 size-4" />
                </flux:button>
            </div>
        </flux:card>
    </div>

    {{-- Additional Reports Section --}}
    <flux:heading size="lg" class="mb-4 mt-12">Otros Reportes</flux:heading>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Income Movements Report --}}
        <flux:card>
            <div class="flex items-center gap-4">
                <div class="flex size-12 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon.arrow-down-tray class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>

                <div class="flex-1">
                    <flux:heading size="md" class="mb-1">Ingresos por Período</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Reporte de ingresos con totales por bodega
                    </flux:text>
                </div>

                <flux:button :href="route('reports.movements.income')" variant="ghost">
                    Ver
                    <flux:icon.arrow-right class="ml-1 size-4" />
                </flux:button>
            </div>
        </flux:card>

        {{-- Consumption by Line Report --}}
        <flux:card>
            <div class="flex items-center gap-4">
                <div class="flex size-12 flex-shrink-0 items-center justify-center rounded-lg bg-rose-100 dark:bg-rose-900/30">
                    <flux:icon.chart-bar class="size-6 text-rose-600 dark:text-rose-400" />
                </div>

                <div class="flex-1">
                    <flux:heading size="md" class="mb-1">Consumo por Línea</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Consumo mensual por línea de productos
                    </flux:text>
                </div>

                <flux:button :href="route('reports.movements.consumption-by-line')" variant="ghost">
                    Ver
                    <flux:icon.arrow-right class="ml-1 size-4" />
                </flux:button>
            </div>
        </flux:card>
    </div>
</div>
