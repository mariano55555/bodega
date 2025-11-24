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
            <flux:heading size="xl">{{ __('Reportes Personalizados') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Cree y configure reportes personalizados según sus necesidades') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus">
            {{ __('Crear Nuevo Reporte') }}
        </flux:button>
    </div>

    <!-- Info Card -->
    <flux:callout color="blue">
        {{ __('Los reportes personalizados le permiten definir sus propios criterios de filtrado, campos y formatos de exportación.') }}
    </flux:callout>

    <!-- Coming Soon -->
    <flux:card>
        <div class="text-center py-16">
            <div class="inline-flex p-4 bg-zinc-100 dark:bg-zinc-800 rounded-full mb-4">
                <flux:icon name="cog" class="w-12 h-12 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="mb-2">{{ __('Próximamente') }}</flux:heading>
            <flux:text class="text-zinc-500">
                {{ __('Esta funcionalidad estará disponible próximamente. Podrá crear reportes personalizados con filtros avanzados y configuraciones personalizadas.') }}
            </flux:text>
        </div>
    </flux:card>
</div>
