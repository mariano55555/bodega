<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Validate('required|string|max:255|unique:roles,name')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $display_name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    public array $selectedPermissions = [];

    #[Computed]
    public function permissions()
    {
        return Permission::orderBy('name')->get()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0] ?? 'general';
        });
    }

    public function save(): void
    {
        $this->validate();

        $role = Role::create([
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'guard_name' => 'web',
        ]);

        if (!empty($this->selectedPermissions)) {
            $role->syncPermissions($this->selectedPermissions);
        }

        $this->dispatch('role-created', [
            'message' => 'Rol creado exitosamente',
            'role' => $role->display_name ?: $role->name
        ]);

        $this->redirect(route('admin.roles.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => 'Crear Nuevo Rol',
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('admin.roles.index')" wire:navigate>
                Volver
            </flux:button>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Crear Nuevo Rol
                </flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Define un nuevo rol y asigna sus permisos correspondientes
                </flux:text>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <!-- Basic Information -->
        <flux:card>
            <flux:heading>
                <flux:heading size="lg">Información Básica del Rol</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Datos principales del rol en el sistema
                </flux:text>
            </flux:heading>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Technical Name -->
                <flux:field>
                    <flux:label>Nombre Técnico *</flux:label>
                    <flux:input wire:model="name" placeholder="ej: warehouse-manager" description="Nombre interno del rol (sin espacios, usar guiones)" />
                    <flux:error name="name" />
                </flux:field>

                <!-- Display Name -->
                <flux:field>
                    <flux:label>Nombre para Mostrar</flux:label>
                    <flux:input wire:model="display_name" placeholder="ej: Gerente de Almacén" description="Nombre amigable que se mostrará en la interfaz" />
                    <flux:error name="display_name" />
                </flux:field>

                <!-- Description -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>Descripción</flux:label>
                        <flux:textarea wire:model="description" rows="3" placeholder="Describe las responsabilidades y alcance de este rol..." description="Descripción detallada de las funciones del rol" />
                        <flux:error name="description" />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <!-- Permissions Assignment -->
        <flux:card>
            <flux:heading>
                <flux:heading size="lg">Asignación de Permisos</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Selecciona los permisos que tendrá este rol
                </flux:text>
            </flux:heading>

            <div class="space-y-6">
                @if($this->permissions->count() > 0)
                    @foreach($this->permissions as $module => $modulePermissions)
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <flux:heading size="md" class="capitalize">
                                    {{ str_replace('-', ' ', $module) }}
                                </flux:heading>
                                <flux:button
                                    size="sm"
                                    variant="outline"
                                    wire:click="$toggle('selectedPermissions', {{ json_encode($modulePermissions->pluck('name')->toArray()) }})"
                                >
                                    Seleccionar Todos
                                </flux:button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($modulePermissions as $permission)
                                    <flux:checkbox
                                        wire:model="selectedPermissions"
                                        value="{{ $permission->name }}"
                                        :label="str_replace(['-', '_'], ' ', explode('.', $permission->name)[1] ?? $permission->name)"
                                    />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <flux:icon name="key" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                        <flux:text class="text-zinc-500">
                            No hay permisos disponibles en el sistema
                        </flux:text>
                    </div>
                @endif
            </div>

            @if(count($selectedPermissions) > 0)
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <flux:text class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                        Permisos seleccionados: {{ count($selectedPermissions) }}
                    </flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach($selectedPermissions as $permission)
                            <flux:badge variant="outline" color="blue" size="sm">
                                {{ $permission }}
                            </flux:badge>
                        @endforeach
                    </div>
                </div>
            @endif
        </flux:card>

        <!-- Form Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button variant="ghost" :href="route('admin.roles.index')" wire:navigate>
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                Crear Rol
            </flux:button>
        </div>
    </form>
</div>
