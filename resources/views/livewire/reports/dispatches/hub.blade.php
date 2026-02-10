<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    //
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Reportes de Salidas</flux:heading>
            <flux:text class="mt-1">Centro de reportes para el control y seguimiento de salidas de bodega</flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('reports.administrative') }}" wire:navigate>
            Volver
        </flux:button>
    </div>

    <!-- Report Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Salidas Mensuales -->
        <flux:card class="hover:shadow-lg transition-shadow cursor-pointer group">
            <a href="{{ route('reports.dispatches.monthly') }}" wire:navigate class="block">
                <div class="flex items-start gap-4">
                    <div class="p-4 bg-orange-100 dark:bg-orange-900 rounded-xl group-hover:scale-110 transition-transform">
                        <flux:icon name="truck" class="w-8 h-8 text-orange-600 dark:text-orange-400" />
                    </div>
                    <div class="flex-1">
                        <flux:heading size="lg">Salidas Mensuales</flux:heading>
                        <flux:text class="mt-2 text-sm">
                            Reporte detallado de todas las salidas de bodega agrupadas por almacén, con cantidades y valores.
                        </flux:text>
                        <div class="mt-4 flex items-center gap-2 text-sm text-orange-600 dark:text-orange-400">
                            <flux:icon name="arrow-right" class="w-4 h-4" />
                            <span>Ver reporte</span>
                        </div>
                    </div>
                </div>
            </a>
        </flux:card>

        <!-- Resumen Salidas por Línea (Valor) -->
        <flux:card class="hover:shadow-lg transition-shadow cursor-pointer group">
            <a href="{{ route('reports.dispatches.summary-by-line') }}" wire:navigate class="block">
                <div class="flex items-start gap-4">
                    <div class="p-4 bg-amber-100 dark:bg-amber-900 rounded-xl group-hover:scale-110 transition-transform">
                        <flux:icon name="chart-bar" class="w-8 h-8 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div class="flex-1">
                        <flux:heading size="lg">Resumen por Línea (Valor)</flux:heading>
                        <flux:text class="mt-2 text-sm">
                            Resumen mensual de salidas agrupadas por categoría y línea presupuestaria con valores monetarios consolidados.
                        </flux:text>
                        <div class="mt-4 flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400">
                            <flux:icon name="arrow-right" class="w-4 h-4" />
                            <span>Ver reporte</span>
                        </div>
                    </div>
                </div>
            </a>
        </flux:card>

        <!-- Resumen Salidas por Línea (Cantidad) -->
        <flux:card class="hover:shadow-lg transition-shadow cursor-pointer group">
            <a href="{{ route('reports.dispatches.summary-by-line-quantity') }}" wire:navigate class="block">
                <div class="flex items-start gap-4">
                    <div class="p-4 bg-teal-100 dark:bg-teal-900 rounded-xl group-hover:scale-110 transition-transform">
                        <flux:icon name="calculator" class="w-8 h-8 text-teal-600 dark:text-teal-400" />
                    </div>
                    <div class="flex-1">
                        <flux:heading size="lg">Resumen por Línea (Cantidad)</flux:heading>
                        <flux:text class="mt-2 text-sm">
                            Resumen mensual de salidas agrupadas por categoría y línea presupuestaria con cantidades consolidadas.
                        </flux:text>
                        <div class="mt-4 flex items-center gap-2 text-sm text-teal-600 dark:text-teal-400">
                            <flux:icon name="arrow-right" class="w-4 h-4" />
                            <span>Ver reporte</span>
                        </div>
                    </div>
                </div>
            </a>
        </flux:card>

        <!-- Existencias y Movimientos de Inventario -->
        <flux:card class="hover:shadow-lg transition-shadow cursor-pointer group">
            <a href="{{ route('reports.dispatches.stock-movements') }}" wire:navigate class="block">
                <div class="flex items-start gap-4">
                    <div class="p-4 bg-indigo-100 dark:bg-indigo-900 rounded-xl group-hover:scale-110 transition-transform">
                        <flux:icon name="arrows-right-left" class="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div class="flex-1">
                        <flux:heading size="lg">Existencias y Movimientos</flux:heading>
                        <flux:text class="mt-2 text-sm">
                            Resumen de existencias iniciales, entradas, salidas y existencias finales por producto y categoría.
                        </flux:text>
                        <div class="mt-4 flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400">
                            <flux:icon name="arrow-right" class="w-4 h-4" />
                            <span>Ver reporte</span>
                        </div>
                    </div>
                </div>
            </a>
        </flux:card>
    </div>

    <!-- Info Section -->
    <flux:card class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-white dark:bg-slate-700 rounded-lg shadow-sm">
                <flux:icon name="information-circle" class="w-6 h-6 text-slate-600 dark:text-slate-400" />
            </div>
            <div>
                <flux:heading size="md">Acerca de los Reportes de Salidas</flux:heading>
                <flux:text class="mt-2">
                    Estos reportes permiten el seguimiento y control de las salidas de productos desde las bodegas.
                    Incluyen despachos internos, externos y cualquier movimiento de salida registrado en el sistema.
                </flux:text>
                <div class="mt-4 flex flex-wrap gap-4">
                    <div class="flex items-center gap-2 text-sm">
                        <flux:icon name="document-arrow-down" class="w-4 h-4 text-red-500" />
                        <span>Exportación PDF</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <flux:icon name="table-cells" class="w-4 h-4 text-green-500" />
                        <span>Exportación Excel</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <flux:icon name="funnel" class="w-4 h-4 text-blue-500" />
                        <span>Filtros por período y bodega</span>
                    </div>
                </div>
            </div>
        </div>
    </flux:card>
</div>
