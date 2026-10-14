<?php

use App\Models\Company;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public array $company_settings = [];

    public array $company_employee_code_settings = [];

    public array $company_document_date_years_back = [];

    public array $company_manual_unit_cost = [];

    public array $company_manual_unit_cost_until = [];

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
                $this->company_document_date_years_back[$company->id] = $company->settings['document_date_min_years_back'] ?? '';
                $this->company_manual_unit_cost[$company->id] = (bool) ($company->settings['manual_unit_cost_enabled'] ?? false);
                $this->company_manual_unit_cost_until[$company->id] = $company->settings['manual_unit_cost_until'] ?? '';
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
            $this->company_document_date_years_back[$user->company_id] = $company->settings['document_date_min_years_back'] ?? '';
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

    /**
     * Update the minimum allowed document date age (years back) for a company.
     * An empty value removes the restriction.
     */
    public function updateDocumentDateYearsBack(int $companyId, mixed $value): void
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

        $normalized = ($value === '' || $value === null) ? null : max(0, (int) $value);

        $settings = $company->settings ?? [];

        if ($normalized === null) {
            unset($settings['document_date_min_years_back']);
        } else {
            $settings['document_date_min_years_back'] = $normalized;
        }

        $company->update(['settings' => $settings]);

        $this->company_document_date_years_back[$companyId] = $normalized ?? '';

        \Flux::toast(
            variant: 'success',
            heading: '¡Éxito!',
            text: $normalized === null
                ? 'Restricción de fecha de documento desactivada para '.$company->name
                : 'Restricción de fecha de documento actualizada para '.$company->name,
        );
    }

    /**
     * Enable or disable the manual unit cost in transfers and dispatches for a company (super admin only).
     * Enabling without an end date defaults to the last day of the current month.
     */
    public function updateManualUnitCost(int $companyId, bool $value): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Solo el super administrador puede cambiar esta configuración.');

        $company = Company::find($companyId);

        if (! $company) {
            \Flux::toast(variant: 'danger', heading: 'Error', text: 'Empresa no encontrada.');

            return;
        }

        $settings = $company->settings ?? [];
        $settings['manual_unit_cost_enabled'] = $value;

        if ($value && empty($settings['manual_unit_cost_until'])) {
            $settings['manual_unit_cost_until'] = now()->endOfMonth()->toDateString();
        }

        $company->update(['settings' => $settings]);

        $this->company_manual_unit_cost[$companyId] = $value;
        $this->company_manual_unit_cost_until[$companyId] = $settings['manual_unit_cost_until'] ?? '';

        \Flux::toast(
            variant: 'success',
            heading: '¡Éxito!',
            text: $value
                ? 'Costo unitario manual habilitado para '.$company->name
                : 'Costo unitario manual deshabilitado para '.$company->name,
        );
    }

    /**
     * Set the last day (inclusive) the manual unit cost stays enabled. An empty value means no end date.
     */
    public function updateManualUnitCostUntil(int $companyId, mixed $value): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Solo el super administrador puede cambiar esta configuración.');

        $company = Company::find($companyId);

        if (! $company) {
            \Flux::toast(variant: 'danger', heading: 'Error', text: 'Empresa no encontrada.');

            return;
        }

        $date = null;

        if (! empty($value)) {
            try {
                $date = \Carbon\CarbonImmutable::parse($value)->toDateString();
            } catch (\Exception) {
                $this->addError("company_manual_unit_cost_until.{$companyId}", 'La fecha no es válida.');

                return;
            }
        }

        $settings = $company->settings ?? [];

        if ($date === null) {
            unset($settings['manual_unit_cost_until']);
        } else {
            $settings['manual_unit_cost_until'] = $date;
        }

        $company->update(['settings' => $settings]);

        $this->company_manual_unit_cost_until[$companyId] = $date ?? '';

        \Flux::toast(
            variant: 'success',
            heading: '¡Éxito!',
            text: $date === null
                ? 'El costo unitario manual de '.$company->name.' ya no tiene fecha de vencimiento'
                : 'Costo unitario manual de '.$company->name.' habilitado hasta el '.\Carbon\CarbonImmutable::parse($date)->format('d/m/Y'),
        );
    }

    /**
     * Get the current cache table size in MB.
     */
    #[Computed]
    public function cacheSizeMb(): string
    {
        $bytes = DB::table('cache')->selectRaw('COALESCE(SUM(LENGTH(`value`)), 0) as total')->value('total');

        return number_format($bytes / 1024 / 1024, 2);
    }

    /**
     * Clear the application cache.
     */
    public function clearCache(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        Artisan::call('cache:clear');

        // Optimizar la tabla para recuperar espacio en disco
        DB::statement('OPTIMIZE TABLE cache');

        unset($this->cacheSizeMb);

        \Flux::toast(
            variant: 'success',
            heading: '¡Éxito!',
            text: 'La caché ha sido limpiada exitosamente. Se regenerará automáticamente cuando sea necesario.',
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
            <!-- Document Date Restriction Card -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Antigüedad Máxima de Fecha de Documento</flux:heading>

                @if($isSuperAdmin)
                <div class="space-y-4">
                    <flux:text class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Define cuántos años hacia atrás se permite en la fecha de documento físico (despachos, traslados,
                        compras, donaciones y producciones internas). <strong>0</strong> = solo el año actual ({{ now()->year }}),
                        <strong>1</strong> = desde {{ now()->year - 1 }}, y así sucesivamente. Déjalo vacío para no aplicar restricción.
                    </flux:text>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Empresa
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Años hacia atrás
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->companies as $company)
                                <tr wire:key="doc-date-{{ $company->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $company->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex justify-end">
                                            <flux:input
                                                type="number"
                                                min="0"
                                                class="max-w-32"
                                                placeholder="Sin límite"
                                                wire:model="company_document_date_years_back.{{ $company->id }}"
                                                wire:change="updateDocumentDateYearsBack({{ $company->id }}, $event.target.value)" />
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @else
                <div class="space-y-4">
                    @foreach($this->companies as $company)
                    <flux:field wire:key="doc-date-{{ $company->id }}">
                        <flux:label>Años hacia atrás permitidos</flux:label>
                        <flux:input
                            type="number"
                            min="0"
                            class="max-w-40"
                            placeholder="Sin límite"
                            wire:model="company_document_date_years_back.{{ $company->id }}"
                            wire:change="updateDocumentDateYearsBack({{ $company->id }}, $event.target.value)" />
                        <flux:description>
                            0 = solo el año actual ({{ now()->year }}), 1 = desde {{ now()->year - 1 }}. Vacío = sin restricción.
                        </flux:description>
                    </flux:field>
                    @endforeach
                </div>
                @endif
            </flux:card>

            @if($isSuperAdmin)
            <!-- Manual Unit Cost Card -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Costo Unitario Manual en Traslados y Despachos</flux:heading>

                <div class="space-y-4">
                    <flux:text class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Por defecto el costo unitario de traslados y despachos es el costo promedio ponderado de la bodega y no se puede editar.
                        Al habilitarlo, los usuarios de la empresa podrán escribir el costo a mano hasta la fecha indicada (inclusive).
                        Si se habilita sin fecha, vence el último día del mes actual. Deje la fecha vacía para que no venza.
                    </flux:text>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Empresa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Habilitado hasta</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->companies as $company)
                                @php
                                    $manualEnabled = $company_manual_unit_cost[$company->id] ?? false;
                                    $manualUntil = $company_manual_unit_cost_until[$company->id] ?? '';
                                    $manualExpired = $manualEnabled && $manualUntil !== '' && now()->startOfDay()->greaterThan(\Carbon\Carbon::parse($manualUntil));
                                @endphp
                                <tr wire:key="manual-cost-{{ $company->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $company->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($manualExpired)
                                            <flux:badge color="amber" size="sm">Vencido</flux:badge>
                                        @elseif($manualEnabled)
                                            <flux:badge color="green" size="sm">Habilitado</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Deshabilitado</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <flux:input
                                            type="date"
                                            class="max-w-44"
                                            wire:model="company_manual_unit_cost_until.{{ $company->id }}"
                                            wire:change="updateManualUnitCostUntil({{ $company->id }}, $event.target.value)" />
                                        <flux:error name="company_manual_unit_cost_until.{{ $company->id }}" />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <flux:switch
                                            wire:model.live="company_manual_unit_cost.{{ $company->id }}"
                                            wire:change="updateManualUnitCost({{ $company->id }}, $event.target.checked)" />
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800 mt-4">
                        <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                            <strong>Importante:</strong> una salida con costo manual no cambia el costo promedio de la bodega origen, por lo que el valor del Kardex puede diferir del valor del inventario.
                            En traslados, la bodega destino promedia con el costo escrito. Los movimientos con costo distinto al promedio quedan marcados como costo manual.
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Maintenance Card -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Mantenimiento del Sistema</flux:heading>

                <div class="space-y-4">
                    <flux:text class="text-sm text-gray-600 dark:text-gray-400">
                        Limpia los datos temporales almacenados en caché (consultas de productos, empleados, etc.).
                        El sistema regenerará la caché automáticamente cuando sea necesario.
                    </flux:text>

                    <div class="flex items-center gap-4">
                        <flux:button
                            variant="danger"
                            wire:click="clearCache"
                            wire:confirm="¿Estás seguro de que deseas limpiar toda la caché? Los formularios pueden tardar un poco más en cargar la primera vez después de esto."
                        >
                            <span wire:loading.remove wire:target="clearCache">Limpiar Caché</span>
                            <span wire:loading wire:target="clearCache">Limpiando...</span>
                        </flux:button>

                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">
                            Tamaño actual: <strong>{{ $this->cacheSizeMb }} MB</strong>
                        </flux:text>
                    </div>

                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800 mt-4">
                        <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                            <strong>Nota:</strong> La caché se limpia automáticamente cada hora. Use este botón solo si necesita liberar espacio inmediatamente.
                            <br>
                            <strong>Respaldos:</strong> Al exportar la base de datos, excluya las tablas <code>cache</code>, <code>cache_locks</code>, <code>sessions</code>, <code>jobs</code> y <code>job_batches</code> para reducir el tamaño del archivo.
                        </flux:text>
                    </div>
                </div>
            </flux:card>
            @endif
        </div>
    </x-settings.layout>
</section>
