<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('components.layouts.app')] class extends Component
{
    public Permission $permission;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $display_name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    public function mount(Permission $permission): void
    {
        $this->permission = $permission;

        // Fill form with current data
        $this->name = $permission->name;
        $this->display_name = $permission->display_name ?? '';
        $this->description = $permission->description ?? '';
    }

    #[Computed]
    public function rolesWithPermission()
    {
        return Role::whereHas('permissions', function($query) {
            $query->where('name', $this->permission->name);
        })->withCount('users')->get();
    }

    public function save(): void
    {
        $rules = $this->rules();
        $rules['name'] = 'required|string|max:255|unique:permissions,name,' . $this->permission->id;

        $validated = $this->validate($rules);

        $this->permission->update([
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
        ]);

        $this->dispatch('permission-updated', [
            'message' => 'Permiso actualizado exitosamente',
            'permission' => $this->permission->display_name ?: $this->permission->name
        ]);

        $this->redirect(route('admin.permissions.index'), navigate: true);
    }

    public function delete(): void
    {
        if ($this->permission->roles()->count() > 0) {
            $this->dispatch('permission-delete-failed', [
                'message' => 'No se puede eliminar un permiso que está asignado a roles'
            ]);
            return;
        }

        $permissionName = $this->permission->display_name ?: $this->permission->name;
        $this->permission->delete();

        $this->dispatch('permission-deleted', [
            'message' => "Permiso '{$permissionName}' eliminado exitosamente"
        ]);

        $this->redirect(route('admin.permissions.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => 'Editar Permiso - ' . ($this->permission->display_name ?: $this->permission->name),
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('admin.permissions.index')" wire:navigate>
                Volver
            </flux:button>
            <div class="flex-1">
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Editar Permiso
                </flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Actualiza la información de {{ $permission->display_name ?: $permission->name }}
                </flux:text>
            </div>

            <!-- Permission Type Badge -->
            <flux:badge color="green" size="lg">
                {{ explode('.', $permission->name)[0] ?? 'general' }}
            </flux:badge>
        </div>

        <!-- Permission Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="shield-check" class="h-8 w-8 text-blue-500" />
                    <div>
                        <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                            {{ $permission->roles->count() }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Roles asignados</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="users" class="h-8 w-8 text-green-500" />
                    <div>
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                            {{ $this->rolesWithPermission->sum('users_count') }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Usuarios afectados</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="calendar" class="h-8 w-8 text-purple-500" />
                    <div>
                        <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">
                            {{ $permission->created_at->format('M Y') }}
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
                <flux:heading size="lg">Información del Permiso</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Datos principales del permiso en el sistema
                </flux:text>
            </flux:heading>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Permission Name -->
                <flux:field>
                    <flux:label>Nombre del Permiso *</flux:label>
                    <flux:input wire:model="name" placeholder="ej: users.create" description="Nombre técnico del permiso (formato: módulo.acción)" />
                    <flux:error name="name" />
                </flux:field>

                <!-- Display Name -->
                <flux:field>
                    <flux:label>Nombre para Mostrar</flux:label>
                    <flux:input wire:model="display_name" placeholder="ej: Crear Usuarios" description="Nombre amigable que se mostrará en la interfaz" />
                    <flux:error name="display_name" />
                </flux:field>

                <!-- Description -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>Descripción</flux:label>
                        <flux:textarea wire:model="description" rows="3" placeholder="Describe qué permite hacer este permiso..." description="Descripción detallada del alcance del permiso" />
                        <flux:error name="description" />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <!-- Roles with this Permission -->
        @if($this->rolesWithPermission->count() > 0)
            <flux:card>
                <flux:heading>
                    <flux:heading size="lg">Roles que tienen este Permiso</flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ $this->rolesWithPermission->count() }} {{ Str::plural('rol', $this->rolesWithPermission->count()) }} {{ $this->rolesWithPermission->count() === 1 ? 'tiene' : 'tienen' }} asignado este permiso
                    </flux:text>
                </flux:heading>

                <div class="space-y-4">
                    @foreach($this->rolesWithPermission as $role)
                        <div class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <div class="flex items-center gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                    <flux:icon name="shield-check" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <flux:text class="font-medium">
                                        {{ $role->display_name ?: $role->name }}
                                    </flux:text>
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ $role->name }} • {{ $role->users_count }} {{ Str::plural('usuario', $role->users_count) }}
                                    </flux:text>
                                </div>
                            </div>

                            <flux:badge variant="outline" color="blue">
                                {{ $role->permissions->count() }} permisos
                            </flux:badge>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                    <div class="flex items-start gap-3">
                        <flux:icon name="exclamation-triangle" class="h-5 w-5 text-amber-600 dark:text-amber-400 mt-0.5" />
                        <div>
                            <flux:text class="text-sm font-medium text-amber-800 dark:text-amber-200">
                                Advertencia
                            </flux:text>
                            <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                                Modificar este permiso afectará a {{ $this->rolesWithPermission->sum('users_count') }} {{ Str::plural('usuario', $this->rolesWithPermission->sum('users_count')) }} en el sistema.
                            </flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>
        @endif

        <!-- Permission Analysis -->
        <flux:card>
            <flux:heading>
                <flux:heading size="lg">Análisis del Permiso</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Información técnica y de uso
                </flux:text>
            </flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">
                        Información Técnica:
                    </flux:text>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Nombre técnico:</span>
                            <span class="font-mono">{{ $permission->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Guard:</span>
                            <span class="font-mono">{{ $permission->guard_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Módulo:</span>
                            <flux:badge size="sm" variant="outline">
                                {{ explode('.', $permission->name)[0] ?? 'general' }}
                            </flux:badge>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Acción:</span>
                            <flux:badge size="sm" variant="outline" color="green">
                                {{ explode('.', $permission->name)[1] ?? 'unknown' }}
                            </flux:badge>
                        </div>
                    </div>
                </div>

                <div>
                    <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">
                        Estadísticas de Uso:
                    </flux:text>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Roles asignados:</span>
                            <span class="font-semibold">{{ $permission->roles->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Usuarios afectados:</span>
                            <span class="font-semibold">{{ $this->rolesWithPermission->sum('users_count') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Fecha de creación:</span>
                            <span>{{ $permission->created_at->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Última actualización:</span>
                            <span>{{ $permission->updated_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Form Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-4">
                <flux:button variant="ghost" :href="route('admin.permissions.index')" wire:navigate>
                    Cancelar
                </flux:button>
                @if($permission->roles->count() === 0)
                    <flux:button
                        variant="danger"
                        icon="trash"
                        wire:click="delete"
                        wire:confirm="¿Estás seguro de eliminar este permiso? Esta acción no se puede deshacer."
                    >
                        Eliminar Permiso
                    </flux:button>
                @else
                    <flux:text class="text-sm text-zinc-500">
                        No se puede eliminar: está asignado a roles
                    </flux:text>
                @endif
            </div>
            <flux:button type="submit" variant="primary" icon="check">
                Actualizar Permiso
            </flux:button>
        </div>
    </form>
</div>
