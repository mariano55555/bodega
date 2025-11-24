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
            <flux:heading size="xl">{{ __('Reportes Administrativos') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Informes ejecutivos y administrativos del sistema') }}</flux:text>
        </div>
    </div>

    <!-- Report Categories -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Financial Reports -->
        <flux:card>
            <div class="flex items-start gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <flux:icon name="currency-dollar" class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg">{{ __('Reportes Financieros') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Valorización de inventario y costos') }}</flux:text>
                    <div class="mt-4 space-y-2">
                        <flux:button variant="ghost" size="sm" :href="route('reports.inventory.value')" wire:navigate class="w-full justify-start">
                            {{ __('Valorización de Inventario') }}
                        </flux:button>
                        <flux:button variant="ghost" size="sm" :href="route('reports.purchases-by-supplier')" wire:navigate class="w-full justify-start">
                            {{ __('Compras por Proveedor') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Operational Reports -->
        <flux:card>
            <div class="flex items-start gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <flux:icon name="chart-bar" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg">{{ __('Reportes Operacionales') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Eficiencia y productividad') }}</flux:text>
                    <div class="mt-4 space-y-2">
                        <flux:button variant="ghost" size="sm" :href="route('reports.inventory.rotation')" wire:navigate class="w-full justify-start">
                            {{ __('Rotación de Inventario') }}
                        </flux:button>
                        <flux:button variant="ghost" size="sm" :href="route('reports.self-consumption')" wire:navigate class="w-full justify-start">
                            {{ __('Autoconsumo') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Management Reports -->
        <flux:card>
            <div class="flex items-start gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <flux:icon name="document-chart-bar" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg">{{ __('Reportes Gerenciales') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Indicadores clave de desempeño') }}</flux:text>
                    <div class="mt-4 space-y-2">
                        <flux:button variant="ghost" size="sm" :href="route('reports.inventory.index')" wire:navigate class="w-full justify-start">
                            {{ __('Dashboard de Reportes') }}
                        </flux:button>
                        <flux:button variant="ghost" size="sm" :href="route('reports.donations-consolidated')" wire:navigate class="w-full justify-start">
                            {{ __('Donaciones Consolidadas') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Compliance Reports -->
        <flux:card>
            <div class="flex items-start gap-4">
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                    <flux:icon name="shield-check" class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg">{{ __('Reportes de Cumplimiento') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Auditoría y control') }}</flux:text>
                    <div class="mt-4 space-y-2">
                        <flux:button variant="ghost" size="sm" :href="route('admin.activity-logs.index')" wire:navigate class="w-full justify-start">
                            {{ __('Bitácora de Actividades') }}
                        </flux:button>
                        <flux:button variant="ghost" size="sm" :href="route('reports.pre-closure-differences')" wire:navigate class="w-full justify-start">
                            {{ __('Diferencias Pre-Cierre') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Comparison Reports -->
        <flux:card>
            <div class="flex items-start gap-4">
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <flux:icon name="arrows-right-left" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg">{{ __('Reportes Comparativos') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Análisis período a período') }}</flux:text>
                    <div class="mt-4 space-y-2">
                        <flux:button variant="ghost" size="sm" :href="route('reports.movements.monthly')" wire:navigate class="w-full justify-start">
                            {{ __('Movimientos Mensuales') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Exception Reports -->
        <flux:card>
            <div class="flex items-start gap-4">
                <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-lg">
                    <flux:icon name="exclamation-triangle" class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg">{{ __('Reportes de Excepciones') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Alertas y anomalías') }}</flux:text>
                    <div class="mt-4 space-y-2">
                        <flux:button variant="ghost" size="sm" :href="route('inventory.alerts.index')" wire:navigate class="w-full justify-start">
                            {{ __('Alertas de Stock') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>
</div>
