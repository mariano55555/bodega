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
            <flux:heading size="xl">Reportes de Compras</flux:heading>
            <flux:text class="mt-1">Centro de reportes para el control y seguimiento de compras</flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('reports.administrative') }}" wire:navigate>
            Volver
        </flux:button>
    </div>

    <!-- Report Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Compras Detalladas -->
        <flux:card class="hover:shadow-lg transition-shadow cursor-pointer group">
            <a href="{{ route('reports.purchases.detailed') }}" wire:navigate class="block">
                <div class="flex items-start gap-4">
                    <div class="p-4 bg-blue-100 dark:bg-blue-900 rounded-xl group-hover:scale-110 transition-transform">
                        <flux:icon name="document-text" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="flex-1">
                        <flux:heading size="lg">Compras Detalladas</flux:heading>
                        <flux:text class="mt-2 text-sm">
                            Reporte mensual detallado de todas las compras realizadas, agrupadas por línea presupuestaria y proveedor.
                        </flux:text>
                        <div class="mt-4 flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                            <flux:icon name="arrow-right" class="w-4 h-4" />
                            <span>Ver reporte</span>
                        </div>
                    </div>
                </div>
            </a>
        </flux:card>

        <!-- Resumen por Línea Presupuestaria -->
        <flux:card class="hover:shadow-lg transition-shadow cursor-pointer group">
            <a href="{{ route('reports.purchases.summary-by-line') }}" wire:navigate class="block">
                <div class="flex items-start gap-4">
                    <div class="p-4 bg-green-100 dark:bg-green-900 rounded-xl group-hover:scale-110 transition-transform">
                        <flux:icon name="chart-bar" class="w-8 h-8 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="flex-1">
                        <flux:heading size="lg">Resumen por Línea</flux:heading>
                        <flux:text class="mt-2 text-sm">
                            Resumen mensual de compras agrupadas por categoría y línea presupuestaria con totales consolidados.
                        </flux:text>
                        <div class="mt-4 flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                            <flux:icon name="arrow-right" class="w-4 h-4" />
                            <span>Ver reporte</span>
                        </div>
                    </div>
                </div>
            </a>
        </flux:card>

        <!-- Compras por Proveedor (existente) -->
        <flux:card class="hover:shadow-lg transition-shadow cursor-pointer group">
            <a href="{{ route('reports.purchases-by-supplier') }}" wire:navigate class="block">
                <div class="flex items-start gap-4">
                    <div class="p-4 bg-purple-100 dark:bg-purple-900 rounded-xl group-hover:scale-110 transition-transform">
                        <flux:icon name="building-storefront" class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="flex-1">
                        <flux:heading size="lg">Compras por Proveedor</flux:heading>
                        <flux:text class="mt-2 text-sm">
                            Análisis consolidado de compras realizadas a cada proveedor con métricas y tendencias.
                        </flux:text>
                        <div class="mt-4 flex items-center gap-2 text-sm text-purple-600 dark:text-purple-400">
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
                <flux:heading size="md">Acerca de los Reportes de Compras</flux:heading>
                <flux:text class="mt-2">
                    Estos reportes están diseados para el control presupuestario y seguimiento de adquisiciones.
                    Todos los reportes pueden exportarse en formato PDF y Excel para su uso en documentación oficial.
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
                        <span>Filtros por período</span>
                    </div>
                </div>
            </div>
        </div>
    </flux:card>
</div>
