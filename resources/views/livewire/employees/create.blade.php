<?php

use App\Models\Company;
use App\Models\Employee;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public $company_id = '';

    public $area_id = '';

    public $employee_code = '';

    public $name = '';

    public $position = '';

    public $phone = '';

    public $mobile = '';

    public $email = '';

    public $notes = '';

    public $is_active = true;

    public $autoGenerateCode = false;

    public $codePreview = '';

    public function mount(): void
    {
        if (! $this->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;

            // Check if auto-generate employee code is enabled for non-super admin
            $this->checkAutoGenerateCode();
        }
    }

    public function checkAutoGenerateCode(): void
    {
        if ($this->company_id) {
            $company = \App\Models\Company::find($this->company_id);
            $this->autoGenerateCode = $company && ($company->settings['auto_generate_employee_code'] ?? false);

            if ($this->autoGenerateCode) {
                $this->codePreview = Employee::previewEmployeeCode((int) $this->company_id);
            } else {
                $this->codePreview = '';
            }
        } else {
            $this->autoGenerateCode = false;
            $this->codePreview = '';
        }
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->hasRole('super-admin');
    }

    #[Computed]
    public function companies()
    {
        if ($this->isSuperAdmin()) {
            return Company::active()->orderBy('name')->get();
        }

        return collect([]);
    }

    #[Computed]
    public function areas()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return \App\Models\Area::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function updatedCompanyId(): void
    {
        $this->area_id = '';

        // Update auto-generate code status
        $this->checkAutoGenerateCode();

        // Clear employee code if auto-generation is enabled
        if ($this->autoGenerateCode) {
            $this->employee_code = '';
        }
    }

    public function save(): void
    {
        try {
            $companyId = $this->isSuperAdmin() ? $this->company_id : auth()->user()->company_id;

            $rules = [
                'area_id' => 'required|exists:areas,id',
                'name' => 'required|string|max:255',
                'employee_code' => [
                    'nullable',
                    'string',
                    'max:50',
                    function ($attribute, $value, $fail) use ($companyId) {
                        if ($value && Employee::where('company_id', $companyId)
                            ->where('employee_code', $value)
                            ->exists()) {
                            $fail('El código de empleado ya existe en esta empresa.');
                        }
                    },
                ],
                'position' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:50',
                'mobile' => 'nullable|string|max:50',
                'email' => 'required|email|max:255',
                'notes' => 'nullable|string',
                'is_active' => 'boolean',
            ];

            if ($this->isSuperAdmin()) {
                $rules['company_id'] = 'required|exists:companies,id';
            }

            $validated = $this->validate($rules);

            // Auto-generate employee code if enabled and no code provided
            $employeeCode = $validated['employee_code'];
            if ($this->autoGenerateCode && empty($employeeCode)) {
                $employeeCode = Employee::generateEmployeeCode($companyId);
            }

            $employee = Employee::create([
                'company_id' => $companyId,
                'area_id' => $validated['area_id'],
                'employee_code' => $employeeCode,
                'name' => $validated['name'],
                'slug' => \Illuminate\Support\Str::slug($validated['name']),
                'position' => $validated['position'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'email' => $validated['email'],
                'notes' => $validated['notes'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            Flux::toast('Empleado creado exitosamente.', variant: 'success');
            $this->redirect(route('employees.index'), navigate: true);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                Flux::toast('El código de empleado ya está en uso. Por favor use un código diferente.', variant: 'danger');
            } else {
                Flux::toast('Error al crear el empleado: '.$e->getMessage(), variant: 'danger');
            }
        } catch (\Exception $e) {
            Flux::toast('Error al crear el empleado: '.$e->getMessage(), variant: 'danger');
        }
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nuevo Empleado</flux:heading>
            <flux:text class="mt-1">Registrar un nuevo empleado en el sistema</flux:text>
        </div>

        <flux:button variant="ghost" icon="arrow-left" href="{{ route('employees.index') }}" wire:navigate>
            Volver al listado
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:card>
            <flux:heading size="lg">Información del Empleado</flux:heading>
            <flux:separator class="my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if(auth()->user()->hasRole('super-admin'))
                    <flux:field class="md:col-span-2">
                        <flux:label badge="Requerido">Empresa</flux:label>
                        <flux:select variant="listbox" wire:model.live="company_id" placeholder="Seleccione empresa" required>
                            @foreach ($this->companies as $company)
                                <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:description>Empresa para la cual se creará este empleado</flux:description>
                        @error('company_id') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                    </flux:field>
                @endif

                <flux:field class="md:col-span-2">
                    <flux:label badge="Requerido">Área</flux:label>
                    <flux:select variant="listbox" wire:model="area_id" :disabled="!$company_id" placeholder="Seleccione área" required>
                        @foreach ($this->areas as $area)
                            <flux:select.option value="{{ $area->id }}">{{ $area->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:description>
                        @if(!$company_id && auth()->user()->hasRole('super-admin'))
                            Primero selecciona una empresa
                        @else
                            Área o departamento al que pertenece el empleado
                        @endif
                    </flux:description>
                    @error('area_id') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Código de Empleado</flux:label>
                    <flux:input
                        wire:model="employee_code"
                        placeholder="Ej: EMP-001"
                        :disabled="$autoGenerateCode"
                    />
                    @if($autoGenerateCode && $codePreview)
                        <flux:description>
                            Se generará automáticamente: <strong class="text-zinc-900 dark:text-zinc-100">{{ $codePreview }}</strong>
                        </flux:description>
                    @endif
                    @error('employee_code') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Nombre Completo</flux:label>
                    <flux:input wire:model="name" placeholder="Ej: Juan Pérez" required />
                    @error('name') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Posición/Cargo</flux:label>
                    <flux:input wire:model="position" placeholder="Ej: Gerente de Ventas" />
                    @error('position') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono</flux:label>
                    <flux:input wire:model="phone" placeholder="2222-2222" />
                    @error('phone') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Móvil</flux:label>
                    <flux:input wire:model="mobile" placeholder="7777-7777" />
                    @error('mobile') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label badge="Requerido">Email</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="empleado@ejemplo.com" required />
                    @error('email') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" placeholder="Información adicional sobre el empleado..." rows="4" />
                    @error('notes') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:checkbox
                        wire:model="is_active"
                        label="Empleado activo"
                    />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex justify-end gap-4">
            <flux:button variant="ghost" href="{{ route('employees.index') }}" wire:navigate>
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary">
                Guardar Empleado
            </flux:button>
        </div>
    </form>
</div>
