<?php

use App\Models\Company;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public array $company_settings = [];

    public array $company_employee_code_settings = [];

    public bool $isSuperAdmin = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = auth()->user();
        $this->isSuperAdmin = $user->isSuperAdmin();

        if ($this->isSuperAdmin) {
            // Cargar configuración de todas las empresas
            $companies = Company::whereNotNull('active_at')->get();
            foreach ($companies as $company) {
                $this->company_settings[$company->id] = $company->settings['auto_generate_sku'] ?? false;
                $this->company_employee_code_settings[$company->id] = $company->settings['auto_generate_employee_code'] ?? false;
            }
        } else {
            // Si es company admin, solo puede ver y modificar su empresa
            if (! $user->company_id) {
                abort(403, 'Debes estar asociado a una empresa para acceder a esta página.');
            }

            if (! $user->hasRole('company-admin')) {
                abort(403, 'No tienes autorización para modificar estas configuraciones.');
            }

            $company = Company::find($user->company_id);

            if (! $company) {
                abort(404, 'Empresa no encontrada.');
            }

            $this->company_settings[$user->company_id] = $company->settings['auto_generate_sku'] ?? false;
            $this->company_employee_code_settings[$user->company_id] = $company->settings['auto_generate_employee_code'] ?? false;
        }
    }

    #[Computed]
    public function companies()
    {
        if ($this->isSuperAdmin) {
            return Company::query()
                ->whereNotNull('active_at')
                ->orderBy('name')
                ->get();
        }

        // Si no es super admin, solo devolver su empresa
        $user = auth()->user();

        return Company::query()
            ->where('id', $user->company_id)
            ->get();
    }

    /**
     * Save general settings for a specific company.
     */
    public function updateCompanySetting(int $companyId, bool $value): void
    {
        $user = auth()->user();

        // Si no es super admin, validar que solo modifique su empresa
        if (! $this->isSuperAdmin) {
            if (! $user->hasRole('company-admin')) {
                abort(403, 'No tienes autorización para modificar estas configuraciones.');
            }

            if ($companyId !== $user->company_id) {
                abort(403, 'Solo puedes modificar la configuración de tu empresa.');
            }
        }

        $company = Company::find($companyId);

        if (! $company) {
            \Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: 'Empresa no encontrada.',
            );

            return;
        }

        $settings = $company->settings ?? [];
        $settings['auto_generate_sku'] = $value;
        $company->update(['settings' => $settings]);

        // Actualizar el estado local
        $this->company_settings[$companyId] = $value;

        \Flux::toast(
            variant: 'success',
            heading: '¡Éxito!',
            text: 'Configuración actualizada para '.$company->name,
        );
    }

    /**
     * Update employee code generation setting for a specific company.
     */
    public function updateEmployeeCodeSetting(int $companyId, bool $value): void
    {
        $user = auth()->user();

        // Si no es super admin, validar que solo modifique su empresa
        if (! $this->isSuperAdmin) {
            if (! $user->hasRole('company-admin')) {
                abort(403, 'No tienes autorización para modificar estas configuraciones.');
            }

            if ($companyId !== $user->company_id) {
                abort(403, 'Solo puedes modificar la configuración de tu empresa.');
            }
        }

        $company = Company::find($companyId);

        if (! $company) {
            \Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: 'Empresa no encontrada.',
            );

            return;
        }

        $settings = $company->settings ?? [];
        $settings['auto_generate_employee_code'] = $value;
        $company->update(['settings' => $settings]);

        // Actualizar el estado local
        $this->company_employee_code_settings[$companyId] = $value;

        \Flux::toast(
            variant: 'success',
            heading: '¡Éxito!',
            text: 'Configuración de código de empleado actualizada para '.$company->name,
        );
    }

    public function with(): array
    {
        return [
            'title' => __('Configuraciones Generales'),
        ];
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Configuraciones Generales')"
        :subheading="$isSuperAdmin ? __('Administra las configuraciones de las empresas') : __('Administra las configuraciones de tu empresa')">

        <div class="my-6 w-full space-y-6">
            <flux:card>
                <flux:heading size="lg" class="mb-6">Generación Automática de SKU</flux:heading>

                @if($isSuperAdmin)
                <div class="space-y-4">
                    <flux:text class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Activa o desactiva la generación automática de códigos SKU para cada empresa.
                        Los cambios se aplican inmediatamente.
                    </flux:text>

                    <!-- Tabla de empresas -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Empresa
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->companies as $company)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $company->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($company_settings[$company->id] ?? false)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Activado
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                Desactivado
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <flux:switch
                                            wire:model.live="company_settings.{{ $company->id }}"
                                            wire:change="updateCompanySetting({{ $company->id }}, $event.target.checked)" />
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Info box -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 mt-4">
                        <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Formato:</strong> PRO-XXXXXX (6 caracteres aleatorios en mayúsculas)
                            <br>
                            <strong>Ejemplo:</strong> PRO-A7K9M2
                            <br>
                            <strong>Nota:</strong> Cuando está activado, el campo SKU se deshabilitará automáticamente en el formulario de creación de productos.
                        </flux:text>
                    </div>
                </div>
                @else
                <!-- Vista para Company Admin -->
                <div class="space-y-4">
                    @foreach($this->companies as $company)
                    <flux:field>
                        <flux:switch
                            wire:model.live="company_settings.{{ $company->id }}"
                            wire:change="updateCompanySetting({{ $company->id }}, $event.target.checked)"
                            description="Cuando está activo, el sistema generará automáticamente códigos SKU únicos para nuevos productos (Ej: PRO-ABC123)">
                            Generar SKU automáticamente
                        </flux:switch>
                    </flux:field>

                    @if($company_settings[$company->id] ?? false)
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Formato:</strong> PRO-XXXXXX (6 caracteres aleatorios en mayúsculas)
                            <br>
                            <strong>Ejemplo:</strong> PRO-A7K9M2
                        </flux:text>
                    </div>
                    @endif
                    @endforeach
                </div>
                @endif
            </flux:card>

            <!-- Employee Code Generation Card -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Generación Automática de Código de Empleado</flux:heading>

                @if($isSuperAdmin)
                <div class="space-y-4">
                    <flux:text class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Activa o desactiva la generación automática de códigos de empleado para cada empresa.
                        Los cambios se aplican inmediatamente.
                    </flux:text>

                    <!-- Tabla de empresas -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Empresa
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->companies as $company)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $company->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($company_employee_code_settings[$company->id] ?? false)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Activado
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                Desactivado
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <flux:switch
                                            wire:model.live="company_employee_code_settings.{{ $company->id }}"
                                            wire:change="updateEmployeeCodeSetting({{ $company->id }}, $event.target.checked)" />
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Info box -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 mt-4">
                        <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Formato:</strong> EMP-XXX (prefijo personalizable + número secuencial)
                            <br>
                            <strong>Ejemplo:</strong> EMP-001, EMP-002, EMP-003
                            <br>
                            <strong>Nota:</strong> Cuando está activado, el campo Código de Empleado se deshabilitará automáticamente en el formulario de creación de empleados.
                        </flux:text>
                    </div>
                </div>
                @else
                <!-- Vista para Company Admin -->
                <div class="space-y-4">
                    @foreach($this->companies as $company)
                    <flux:field>
                        <flux:switch
                            wire:model.live="company_employee_code_settings.{{ $company->id }}"
                            wire:change="updateEmployeeCodeSetting({{ $company->id }}, $event.target.checked)"
                            description="Cuando está activo, el sistema generará automáticamente códigos secuenciales para nuevos empleados (Ej: EMP-001, EMP-002)">
                            Generar códigos de empleado automáticamente
                        </flux:switch>
                    </flux:field>

                    @if($company_employee_code_settings[$company->id] ?? false)
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Formato:</strong> EMP-XXX (prefijo personalizable + número secuencial)
                            <br>
                            <strong>Ejemplo:</strong> EMP-001, EMP-002, EMP-003
                            <br>
                            <strong>Configuración:</strong> Puedes personalizar el prefijo y cantidad de dígitos en la configuración de tu empresa.
                        </flux:text>
                    </div>
                    @endif
                    @endforeach
                </div>
                @endif
            </flux:card>
        </div>
    </x-settings.layout>
</section>
