<?php

use App\Models\Employee;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $employeeToDelete = null;

    public bool $showFilters = false;

    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function confirmDelete(int $employeeId): void
    {
        $this->employeeToDelete = $employeeId;
    }

    public function cancelDelete(): void
    {
        $this->employeeToDelete = null;
    }

    public function with(): array
    {
        $query = Employee::query()
            ->with('area')
            ->when(! auth()->user()->isSuperAdmin(), function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('employee_code', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== '', function ($q) {
                if ($this->statusFilter === 'active') {
                    $q->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->latest();

        return [
            'employees' => $query->paginate($this->perPage),
        ];
    }

    public function delete(): void
    {
        if (! $this->employeeToDelete) {
            return;
        }

        $employee = Employee::find($this->employeeToDelete);

        if (! $employee) {
            \Flux\Flux::toast('Empleado no encontrado.', variant: 'danger');
            $this->employeeToDelete = null;

            return;
        }

        $employee->delete();
        \Flux\Flux::toast('Empleado eliminado exitosamente.', variant: 'success');
        $this->employeeToDelete = null;
    }

    public function toggleStatus(int $employeeId): void
    {
        $employee = Employee::find($employeeId);

        if (! $employee) {
            \Flux\Flux::toast('Empleado no encontrado.', variant: 'danger');

            return;
        }

        $employee->is_active = ! $employee->is_active;
        $employee->active_at = $employee->is_active ? now() : null;
        $employee->save();

        \Flux\Flux::toast('Estado del empleado actualizado.', variant: 'success');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Personal/Empleados</flux:heading>
            <flux:text class="mt-1">Gestión de empleados de la compañía</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" href="{{ route('employees.create') }}" wire:navigate>
            Nuevo Empleado
        </flux:button>
    </div>

    <!-- Search and Filter Toggle -->
    <div class="flex gap-3">
        <div class="w-full md:w-96">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre, código, email, teléfono..."
                icon="magnifying-glass"
            />
        </div>
        <flux:button
            variant="ghost"
            icon="funnel"
            wire:click="toggleFilters"
            :title="$showFilters ? 'Ocultar filtros' : 'Mostrar filtros'"
        />
    </div>

    <!-- Collapsible Filters -->
    @if ($showFilters)
        <div class="flex flex-wrap gap-4" x-data x-transition>
            <flux:field class="w-full sm:w-48">
                <flux:label>Estado</flux:label>
                <flux:select wire:model.live="statusFilter" placeholder="Todos los estados">
                    <option value="">Todos</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </flux:select>
            </flux:field>
        </div>
    @endif

    <!-- Stats and Per Page -->
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Mostrando {{ $employees->firstItem() ?? 0 }} - {{ $employees->lastItem() ?? 0 }} de {{ $employees->total() }} empleados
        </div>
        <div class="flex items-center gap-2">
            <flux:text class="text-sm">Por página:</flux:text>
            <flux:select wire:model.live="perPage" class="w-20">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </flux:select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Código</flux:table.column>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Área</flux:table.column>
                <flux:table.column>Posición</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Teléfono</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($employees as $employee)
                    <flux:table.row :key="$employee->id">
                        <flux:table.cell>
                            {{ $employee->employee_code ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $employee->name }}
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $employee->area?->name ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $employee->position ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $employee->email ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $employee->phone ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="$employee->is_active ? 'green' : 'red'">
                                {{ $employee->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil"
                                    href="{{ route('employees.edit', $employee) }}"
                                    wire:navigate
                                    title="Editar"
                                />

                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    :icon="$employee->is_active ? 'x-circle' : 'check-circle'"
                                    wire:click="toggleStatus({{ $employee->id }})"
                                    :title="$employee->is_active ? 'Desactivar' : 'Activar'"
                                />

                                <flux:modal.trigger name="delete-employee-{{ $employee->id }}">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        title="Eliminar"
                                        class="text-red-600 hover:text-red-700 dark:text-red-500 dark:hover:text-red-400"
                                    />
                                </flux:modal.trigger>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <flux:icon name="users" class="mx-auto h-12 w-12 mb-3 opacity-20" />
                            <div>No se encontraron empleados.</div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    @if ($employees->hasPages())
        <div class="mt-6">
            {{ $employees->links() }}
        </div>
    @endif

    <!-- Delete Confirmation Modals -->
    @foreach ($employees as $employee)
        <flux:modal name="delete-employee-{{ $employee->id }}" class="min-w-[22rem]">
            <form wire:submit="delete" class="space-y-6">
                <div>
                    <flux:heading size="lg">Confirmar eliminación</flux:heading>
                    <flux:text class="mt-2">
                        ¿Está seguro de que desea eliminar el empleado <strong>{{ $employee->name }}</strong>?<br>
                        Esta acción no se puede deshacer.
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button
                        type="button"
                        variant="danger"
                        wire:click="confirmDelete({{ $employee->id }})"
                        wire:then="delete"
                    >
                        Eliminar empleado
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endforeach
</div>
