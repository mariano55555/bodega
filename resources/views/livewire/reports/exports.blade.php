<?php

use App\Models\Company;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Url(as: 'empresa')]
    public $company_id = '';

    public function mount(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }
    }

    #[Computed]
    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    #[Computed]
    public function companies()
    {
        return $this->isSuperAdmin
            ? Company::where('is_active', true)->orderBy('name')->get()
            : collect();
    }

    #[Computed]
    public function effectiveCompanyId()
    {
        return $this->isSuperAdmin ? $this->company_id : auth()->user()->company_id;
    }

    #[Computed]
    public function exportParams(): array
    {
        return $this->effectiveCompanyId ? ['empresa' => $this->effectiveCompanyId] : [];
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Exportación de Datos') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Exporte información del sistema en diversos formatos') }}</flux:text>
        </div>
    </div>

    {{-- Company Selector for Super Admin --}}
    @if ($this->isSuperAdmin)
        <flux:card>
            <div class="max-w-md">
                <flux:field>
                    <flux:label>Empresa</flux:label>
                    <flux:select wire:model.live="company_id" placeholder="Seleccione una empresa">
                        <option value="">Seleccione una empresa</option>
                        @foreach ($this->companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:description>Seleccione la empresa para exportar sus datos</flux:description>
                </flux:field>
            </div>
        </flux:card>
    @endif

    @if (! $this->effectiveCompanyId)
        <flux:card>
            <div class="py-12 text-center">
                <flux:icon.building-office class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Seleccione una Empresa</flux:heading>
                <flux:text class="mt-2">
                    Seleccione una empresa para ver las opciones de exportación disponibles
                </flux:text>
            </div>
        </flux:card>
    @else
        <!-- Export Options -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Inventory Exports -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Exportaciones de Inventario') }}</flux:heading>
                <div class="space-y-3">
                    <flux:button variant="outline" :href="route('reports.inventory.consolidated.export', $this->exportParams)" class="w-full justify-start" icon="arrow-down-tray">
                        {{ __('Inventario Consolidado (Excel)') }}
                    </flux:button>
                    <flux:button variant="outline" :href="route('reports.inventory.consolidated.pdf', $this->exportParams)" class="w-full justify-start" icon="document">
                        {{ __('Inventario Consolidado (PDF)') }}
                    </flux:button>
                    <flux:button variant="outline" :href="route('reports.inventory.value.export', $this->exportParams)" class="w-full justify-start" icon="arrow-down-tray">
                        {{ __('Valorización de Inventario (Excel)') }}
                    </flux:button>
                    <flux:button variant="outline" :href="route('reports.inventory.rotation.export', $this->exportParams)" class="w-full justify-start" icon="arrow-down-tray">
                        {{ __('Rotación de Inventario (Excel)') }}
                    </flux:button>
                </div>
            </flux:card>

            <!-- Movement Exports -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Exportaciones de Movimientos') }}</flux:heading>
                <div class="space-y-3">
                    <flux:button variant="outline" :href="route('reports.movements.monthly.export', $this->exportParams)" class="w-full justify-start" icon="arrow-down-tray">
                        {{ __('Movimientos Mensuales (Excel)') }}
                    </flux:button>
                    <flux:button variant="outline" :href="route('reports.movements.transfers.export', $this->exportParams)" class="w-full justify-start" icon="arrow-down-tray">
                        {{ __('Traslados (Excel)') }}
                    </flux:button>
                    <flux:button variant="outline" :href="route('reports.movements.consumption-by-line.export', $this->exportParams)" class="w-full justify-start" icon="arrow-down-tray">
                        {{ __('Consumo por Línea (Excel)') }}
                    </flux:button>
                </div>
            </flux:card>

            <!-- Kardex Exports -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Exportaciones de Kardex') }}</flux:heading>
                <div class="space-y-3">
                    <flux:button variant="outline" :href="route('reports.kardex', $this->exportParams)" class="w-full justify-start" icon="document-text">
                        {{ __('Ir al Reporte de Kardex') }}
                    </flux:button>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Para exportar el Kardex debe seleccionar un producto y bodega específicos desde el reporte.') }}
                    </flux:text>
                </div>
            </flux:card>

            <!-- Master Data Exports -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Exportaciones de Datos Maestros') }}</flux:heading>
                <div class="space-y-3">
                    <flux:button variant="outline" href="#" class="w-full justify-start" icon="arrow-down-tray" disabled>
                        {{ __('Productos (Excel)') }}
                    </flux:button>
                    <flux:button variant="outline" href="#" class="w-full justify-start" icon="arrow-down-tray" disabled>
                        {{ __('Proveedores (Excel)') }}
                    </flux:button>
                    <flux:button variant="outline" href="#" class="w-full justify-start" icon="arrow-down-tray" disabled>
                        {{ __('Clientes (Excel)') }}
                    </flux:button>
                </div>
            </flux:card>
        </div>

        <!-- Info -->
        <flux:callout color="blue">
            {{ __('Las exportaciones marcadas como deshabilitadas estarán disponibles próximamente.') }}
        </flux:callout>
    @endif
</div>
