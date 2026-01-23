<?php

use App\Models\Employee;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Employee $employee;

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

    public function mount(Employee $employee): void
    {
        $this->employee = $employee;
        $this->company_id = $employee->company_id;
        $this->area_id = $employee->area_id;
        $this->employee_code = $employee->employee_code;
        $this->name = $employee->name;
        $this->position = $employee->position;
        $this->phone = $employee->phone;
        $this->mobile = $employee->mobile;
        $this->email = $employee->email;
        $this->notes = $employee->notes;
        $this->is_active = $employee->is_active;
    }

    public function updatedCompanyId(): void
    {
        $this->area_id = '';
    }

    #[Computed]
    public function companies()
    {
        return \App\Models\Company::where('is_active', true)
            ->orderBy('name')
            ->get();
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

    public function save(): void
    {
        try {
            $validated = $this->validate([
                'company_id' => 'required|exists:companies,id',
                'area_id' => 'required|exists:areas,id',
                'name' => 'required|string|max:255',
                'employee_code' => [
                    'nullable',
                    'string',
                    'max:50',
                    function ($attribute, $value, $fail) {
                        if ($value && Employee::where('company_id', $this->company_id)
                            ->where('employee_code', $value)
                            ->where('id', '!=', $this->employee->id)
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
            ]);

            $this->employee->update([
                'company_id' => $validated['company_id'],
                'area_id' => $validated['area_id'],
                'employee_code' => $validated['employee_code'] ?? null,
                'name' => $validated['name'],
                'position' => $validated['position'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'email' => $validated['email'],
                'notes' => $validated['notes'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            Flux::toast('Empleado actualizado exitosamente.', variant: 'success');
            $this->redirect(route('employees.index'), navigate: true);
        } catch (\Exception $e) {
            Flux::toast('Error al actualizar el empleado: '.$e->getMessage(), variant: 'danger');
        }
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Editar Empleado: {{ $employee->name }}</flux:heading>
            <flux:text class="mt-1">Actualizar información del empleado</flux:text>
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
                        <flux:description>Empresa a la que pertenece el empleado</flux:description>
                        @error('company_id') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                    </flux:field>
                @endif

                <flux:field class="md:col-span-2">
                    <flux:label badge="Requerido">Área</flux:label>
                    <flux:select variant="listbox" wire:model="area_id" placeholder="Seleccione área" required :disabled="!$company_id">
                        @foreach ($this->areas as $area)
                            <flux:select.option value="{{ $area->id }}">{{ $area->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:description>Área o departamento al que pertenece el empleado</flux:description>
                    @error('area_id') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Código de Empleado</flux:label>
                    <flux:input wire:model="employee_code" placeholder="Ej: EMP-001" />
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
                Actualizar Empleado
            </flux:button>
        </div>
    </form>
</div>
