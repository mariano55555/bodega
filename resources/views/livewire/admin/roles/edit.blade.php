<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('components.layouts.app')] class extends Component
{
    public Role $role;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $display_name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    public array $selectedPermissions = [];

    public function mount(Role $role): void
    {
        $this->role = $role->load('permissions', 'users');

        // Fill form with current data
        $this->name = $role->name;
        $this->display_name = $role->display_name ?? '';
        $this->description = $role->description ?? '';
        $this->selectedPermissions = $this->role->permissions->pluck('name')->toArray();
    }

    #[Computed]
    public function permissions()
    {
        return Permission::orderBy('name')->get()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0] ?? 'general';
        });
    }

    public function save(): void
    {
        $rules = $this->rules();
        $rules['name'] = 'required|string|max:255|unique:roles,name,' . $this->role->id;

        $validated = $this->validate($rules);

        $this->role->update([
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
        ]);

        $this->role->syncPermissions($this->selectedPermissions);

        $this->dispatch('role-updated', [
            'message' => 'Rol actualizado exitosamente',
            'role' => $this->role->display_name ?: $this->role->name
        ]);

        $this->redirect(route('admin.roles.index'), navigate: true);
    }

    public function delete(): void
    {
        // Ensure users relationship is loaded
        if (!$this->role->relationLoaded('users')) {
            $this->role->load('users');
        }

        if ($this->role->users->count() > 0) {
            $this->dispatch('role-delete-failed', [
                'message' => 'No se puede eliminar un rol que tiene usuarios asignados'
            ]);
            return;
        }

        $roleName = $this->role->display_name ?: $this->role->name;
        $this->role->delete();

        $this->dispatch('role-deleted', [
            'message' => "Rol '{$roleName}' eliminado exitosamente"
        ]);

        $this->redirect(route('admin.roles.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => 'Editar Rol - ' . ($this->role->display_name ?: $this->role->name),
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
            <div class="flex-1">
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Editar Rol
                </flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Actualiza la información de {{ $role->display_name ?: $role->name }}
                </flux:text>
            </div>

            <!-- Role Stats Badge -->
            <flux:badge color="blue" size="lg">
                {{ $role->users->count() }} {{ Str::plural('usuario', $role->users->count()) }}
            </flux:badge>
        </div>

        <!-- Role Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="users" class="h-8 w-8 text-blue-500" />
                    <div>
                        <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                            {{ $role->users->count() }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Usuarios asignados</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="key" class="h-8 w-8 text-green-500" />
                    <div>
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                            {{ $role->permissions->count() }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Permisos asignados</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="calendar" class="h-8 w-8 text-purple-500" />
                    <div>
                        <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">
                            {{ $role->created_at->format('M Y') }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Creado</flux:text>
                    </div>
                </div>
            </flux:card>
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
                                    Alternar Todos
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
                    <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto">
                        @foreach($selectedPermissions as $permission)
                            <flux:badge variant="outline" color="blue" size="sm">
                                {{ $permission }}
                            </flux:badge>
                        @endforeach
                    </div>
                </div>
            @endif
        </flux:card>

        <!-- Users with this Role -->
        @if($role->users->count() > 0)
            <flux:card>
                <flux:heading>
                    <flux:heading size="lg">Usuarios con este Rol</flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ $role->users->count() }} {{ Str::plural('usuario', $role->users->count()) }} {{ $role->users->count() === 1 ? 'tiene' : 'tienen' }} asignado este rol
                    </flux:text>
                </flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($role->users as $user)
                        <div class="flex items-center gap-3 p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-medium">
                                {{ $user->initials() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <flux:text class="font-medium truncate">{{ $user->name }}</flux:text>
                                <flux:text class="text-sm text-zinc-500 truncate">{{ $user->email }}</flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        @endif

        <!-- Form Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-4">
                <flux:button variant="ghost" :href="route('admin.roles.index')" wire:navigate>
                    Cancelar
                </flux:button>
                @if($role->users->count() === 0)
                    <flux:button
                        variant="danger"
                        icon="trash"
                        wire:click="delete"
                        wire:confirm="¿Estás seguro de eliminar este rol? Esta acción no se puede deshacer."
                    >
                        Eliminar Rol
                    </flux:button>
                @else
                    <flux:text class="text-sm text-zinc-500">
                        No se puede eliminar: hay usuarios asignados
                    </flux:text>
                @endif
            </div>
            <flux:button type="submit" variant="primary" icon="check">
                Actualizar Rol
            </flux:button>
        </div>
    </form>
</div>
