<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';
    public string $moduleFilter = '';
    public array $selectedPermissions = [];
    public bool $selectAll = false;

    // Modal states
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showRoleAssignModal = false;
    public ?Permission $selectedPermission = null;

    // Form data
    public string $name = '';
    public string $displayName = '';
    public string $description = '';
    public string $module = '';
    public array $selectedRoles = [];

    public function mount(): void
    {
        // Add authorization check if needed
    }

    #[Computed]
    public function permissions()
    {
        return Permission::query()
            ->withCount('roles')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('display_name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->moduleFilter, function ($query) {
                $query->where('name', 'like', '%-' . $this->moduleFilter . '%')
                    ->orWhere('name', 'like', $this->moduleFilter . '.%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);
    }

    #[Computed]
    public function groupedPermissions()
    {
        $allPermissions = Permission::orderBy('name')->get();
        return $allPermissions->groupBy(function ($permission) {
            // Handle different naming conventions
            if (str_contains($permission->name, '.')) {
                // Standard module.action format
                return explode('.', $permission->name)[0];
            } else if (str_contains($permission->name, '-')) {
                // action-module format (like view-branches, create-users)
                $parts = explode('-', $permission->name, 2);
                if (count($parts) >= 2) {
                    // For complex permissions, extract the main module
                    $module = $parts[1];
                    // Handle special cases like "branches-by-company" -> "branches"
                    if (str_contains($module, '-by-')) {
                        return explode('-by-', $module)[0];
                    }
                    // Handle cases like "branch-status" -> "branches"
                    if ($module === 'branch-status') return 'branches';
                    if ($module === 'warehouse-status') return 'warehouses';
                    if ($module === 'warehouse-capacity') return 'warehouses';
                    if ($module === 'company-users') return 'companies';
                    if ($module === 'user-permissions') return 'users';
                    if ($module === 'inventory-reports') return 'inventory';

                    return $module;
                }
            }
            return 'general';
        });
    }

    #[Computed]
    public function modules()
    {
        return Permission::all()
            ->map(function ($permission) {
                // Handle different naming conventions
                if (str_contains($permission->name, '.')) {
                    // Standard module.action format
                    return explode('.', $permission->name)[0];
                } else if (str_contains($permission->name, '-')) {
                    // action-module format (like view-branches, create-users)
                    $parts = explode('-', $permission->name, 2);
                    if (count($parts) >= 2) {
                        // For complex permissions, extract the main module
                        $module = $parts[1];
                        // Handle special cases like "branches-by-company" -> "branches"
                        if (str_contains($module, '-by-')) {
                            return explode('-by-', $module)[0];
                        }
                        // Handle cases like "branch-status" -> "branches"
                        if ($module === 'branch-status') return 'branches';
                        if ($module === 'warehouse-status') return 'warehouses';
                        if ($module === 'warehouse-capacity') return 'warehouses';
                        if ($module === 'company-users') return 'companies';
                        if ($module === 'user-permissions') return 'users';
                        if ($module === 'inventory-reports') return 'inventory';

                        return $module;
                    }
                }
                return 'general';
            })
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy($field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openCreateModal(): void
    {
        $this->reset(['name', 'displayName', 'description', 'module']);
        $this->showCreateModal = true;
    }

    public function openEditModal(Permission $permission): void
    {
        $this->selectedPermission = $permission;
        $this->name = $permission->name;
        $this->displayName = $permission->display_name ?? '';
        $this->description = $permission->description ?? '';
        $this->module = explode('.', $permission->name)[0];
        $this->showEditModal = true;
    }

    public function openRoleAssignModal(Permission $permission): void
    {
        $this->selectedPermission = $permission;
        $this->selectedRoles = $permission->roles->pluck('name')->toArray();
        $this->showRoleAssignModal = true;
    }

    public function createPermission(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'displayName' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'module' => 'required|string|max:50',
        ], [
            'name.required' => 'El nombre del permiso es obligatorio',
            'name.unique' => 'Ya existe un permiso con este nombre',
            'module.required' => 'El módulo es obligatorio',
        ]);

        // Ensure name follows module.action convention
        if (!str_contains($this->name, '.')) {
            $this->name = $this->module . '.' . $this->name;
        }

        Permission::create([
            'name' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
            'guard_name' => 'web',
        ]);

        $this->showCreateModal = false;
        $this->reset(['name', 'displayName', 'description', 'module']);
        $this->dispatch('permission-created', ['message' => 'Permiso creado exitosamente']);
    }

    public function updatePermission(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $this->selectedPermission->id,
            'displayName' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'El nombre del permiso es obligatorio',
            'name.unique' => 'Ya existe un permiso con este nombre',
        ]);

        $this->selectedPermission->update([
            'name' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
        ]);

        $this->showEditModal = false;
        $this->reset(['name', 'displayName', 'description', 'module']);
        $this->selectedPermission = null;
        $this->dispatch('permission-updated', ['message' => 'Permiso actualizado exitosamente']);
    }

    public function updateRoleAssignments(): void
    {
        // Remove permission from all roles first
        foreach ($this->selectedPermission->roles as $role) {
            $role->revokePermissionTo($this->selectedPermission);
        }

        // Assign to selected roles
        foreach ($this->selectedRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($this->selectedPermission);
            }
        }

        $this->showRoleAssignModal = false;
        $this->selectedPermission = null;
        $this->selectedRoles = [];
        $this->dispatch('role-assignments-updated', ['message' => 'Asignaciones de roles actualizadas']);
    }

    public function deletePermission(Permission $permission): void
    {
        if ($permission->roles()->count() > 0) {
            $this->dispatch('permission-delete-failed', ['message' => 'No se puede eliminar un permiso que está asignado a roles']);
            return;
        }

        $permission->delete();
        $this->dispatch('permission-deleted', ['message' => 'Permiso eliminado exitosamente']);
    }

    public function bulkAssignToRole($roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return;
        }

        // Convert string IDs to integers for the query
        $permissionIds = array_map(fn($id) => (int) $id, $this->selectedPermissions);
        $permissions = Permission::whereIn('id', $permissionIds)->get();
        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission);
        }

        $this->selectedPermissions = [];
        $this->selectAll = false;
        $this->dispatch('bulk-assigned', ['message' => "Permisos asignados al rol {$role->display_name}"]);
    }

    public function togglePermissionSelection($permissionId): void
    {
        if (in_array($permissionId, $this->selectedPermissions)) {
            $this->selectedPermissions = array_filter($this->selectedPermissions, fn($id) => $id !== $permissionId);
        } else {
            $this->selectedPermissions[] = $permissionId;
        }

        $this->selectAll = count($this->selectedPermissions) === $this->permissions->count();
    }

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedPermissions = $this->permissions->pluck('id')->toArray();
        } else {
            $this->selectedPermissions = [];
        }
    }

    public function toggleModulePermissions($modulePermissionIds): void
    {
        // Convert to strings for comparison (wire:model stores as strings)
        $modulePermissionIds = array_map(fn($id) => (string) $id, $modulePermissionIds);

        // Check if all permissions in this module are already selected
        $allSelected = true;
        foreach ($modulePermissionIds as $permissionId) {
            if (!in_array($permissionId, $this->selectedPermissions)) {
                $allSelected = false;
                break;
            }
        }

        if ($allSelected) {
            // Remove all module permissions from selection
            $this->selectedPermissions = array_values(array_filter(
                $this->selectedPermissions,
                fn($id) => !in_array((string) $id, $modulePermissionIds)
            ));
        } else {
            // Add all module permissions to selection
            foreach ($modulePermissionIds as $permissionId) {
                if (!in_array($permissionId, $this->selectedPermissions)) {
                    $this->selectedPermissions[] = $permissionId;
                }
            }
        }

        // Update selectAll state
        $this->selectAll = count($this->selectedPermissions) === Permission::count();
    }

    public function with(): array
    {
        return [
            'title' => 'Gestión de Permisos',
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Gestión de Permisos
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Administra permisos del sistema y sus asignaciones
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="outline" icon="arrow-path" wire:click="$refresh">
                    Actualizar
                </flux:button>
                <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                    Nuevo Permiso
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Bar -->
    @if(count($selectedPermissions) > 0)
        <flux:card class="mb-6 border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <flux:text class="font-medium">
                        {{ count($selectedPermissions) }} permisos seleccionados
                    </flux:text>
                    <flux:button size="sm" variant="ghost" wire:click="$set('selectedPermissions', []); $set('selectAll', false)">
                        Deseleccionar todo
                    </flux:button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:text class="text-sm">Asignar a rol:</flux:text>
                    @foreach($this->roles->take(4) as $role)
                        <flux:button
                            size="sm"
                            variant="outline"
                            wire:click="bulkAssignToRole('{{ $role->name }}')"
                        >
                            {{ $role->display_name ?? $role->name }}
                        </flux:button>
                    @endforeach
                </div>
            </div>
        </flux:card>
    @endif

    <!-- Filters and Search -->
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="lg:col-span-2">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar permisos por nombre o descripción..."
                    icon="magnifying-glass"
                />
            </div>

            <!-- Module Filter -->
            <div>
                <flux:select wire:model.live="moduleFilter" placeholder="Todos los módulos">
                    <flux:select.option value="">Todos los módulos</flux:select.option>
                    @foreach($this->modules as $module)
                        <flux:select.option value="{{ $module }}">{{ ucfirst(str_replace('-', ' ', $module)) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Sort Options -->
            <div>
                <flux:select wire:model.live="sortBy">
                    <flux:select.option value="name">Nombre</flux:select.option>
                    <flux:select.option value="display_name">Nombre para mostrar</flux:select.option>
                    <flux:select.option value="created_at">Fecha de creación</flux:select.option>
                    <flux:select.option value="roles_count">Número de roles</flux:select.option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    <!-- Permissions by Module -->
    @if($this->groupedPermissions->count() > 0)
        <div class="space-y-6 mb-8">
            @foreach($this->groupedPermissions as $module => $modulePermissions)
                @if(!$moduleFilter || $moduleFilter === $module)
                    <flux:card>
                        <div class="flex items-center justify-between mb-4">
                            <flux:heading size="lg" class="flex items-center gap-2">
                                <flux:icon name="squares-2x2" class="h-5 w-5" />
                                {{ ucfirst($module) }}
                                <flux:badge size="sm" color="blue">{{ $modulePermissions->count() }}</flux:badge>
                            </flux:heading>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="outline"
                                    wire:click="toggleModulePermissions({{ json_encode($modulePermissions->pluck('id')->toArray()) }})">
                                    Seleccionar/Deseleccionar todo
                                </flux:button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                <thead class="bg-zinc-50 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left">
                                            @php
                                                $moduleIds = $modulePermissions->pluck('id')->map(fn($id) => (string) $id)->toArray();
                                                $selectedAsStrings = array_map(fn($id) => (string) $id, $selectedPermissions);
                                                $allModuleSelected = count($moduleIds) > 0 && count(array_intersect($moduleIds, $selectedAsStrings)) === count($moduleIds);
                                            @endphp
                                            <flux:checkbox
                                                wire:click="toggleModulePermissions({{ json_encode($moduleIds) }})"
                                                :checked="$allModuleSelected"
                                                class="text-blue-600"
                                            />
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                            Permiso
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                            Descripción
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                            Roles Asignados
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach($modulePermissions as $permission)
                                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                            <td class="px-4 py-3">
                                                <flux:checkbox
                                                    value="{{ $permission->id }}"
                                                    wire:model.live="selectedPermissions"
                                                    class="text-blue-600"
                                                />
                                            </td>
                                            <td class="px-4 py-3">
                                                <div>
                                                    <flux:heading size="sm" class="font-medium">
                                                        {{ $permission->display_name ?: $permission->name }}
                                                    </flux:heading>
                                                    <flux:text class="text-sm text-zinc-500 font-mono">
                                                        {{ $permission->name }}
                                                    </flux:text>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <flux:text class="text-sm">
                                                    {{ $permission->description ?: 'Sin descripción' }}
                                                </flux:text>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap gap-1">
                                                    @forelse($permission->roles as $role)
                                                        <flux:badge size="sm" color="purple">
                                                            {{ $role->display_name ?? $role->name }}
                                                        </flux:badge>
                                                    @empty
                                                        <flux:text class="text-sm text-zinc-400">Sin asignar</flux:text>
                                                    @endforelse
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <flux:dropdown align="end">
                                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                                    <flux:menu>
                                                        <flux:menu.item icon="pencil" wire:click="openEditModal({{ $permission->id }})">
                                                            Editar
                                                        </flux:menu.item>
                                                        <flux:menu.item icon="user-group" wire:click="openRoleAssignModal({{ $permission->id }})">
                                                            Gestionar roles
                                                        </flux:menu.item>
                                                        <flux:menu.separator />
                                                        <flux:menu.item
                                                            icon="trash"
                                                            variant="danger"
                                                            wire:click="deletePermission({{ $permission->id }})"
                                                            wire:confirm="¿Estás seguro de que quieres eliminar este permiso?"
                                                        >
                                                            Eliminar
                                                        </flux:menu.item>
                                                    </flux:menu>
                                                </flux:dropdown>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </flux:card>
                @endif
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <flux:card class="text-center py-12">
            <flux:icon name="key" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">
                {{ $search ? 'No se encontraron permisos' : 'No hay permisos registrados' }}
            </flux:heading>
            <flux:text class="text-zinc-500 mb-6">
                @if($search)
                    No se encontraron resultados para "{{ $search }}"
                @else
                    Comienza creando el primer permiso del sistema
                @endif
            </flux:text>
            @if(!$search)
                <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                    Crear primer permiso
                </flux:button>
            @else
                <flux:button variant="outline" wire:click="$set('search', '')">
                    Limpiar búsqueda
                </flux:button>
            @endif
        </flux:card>
    @endif

    <!-- Create Permission Modal -->
    <flux:modal :open="$showCreateModal" wire:model.boolean="showCreateModal">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">Crear Nuevo Permiso</flux:heading>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Módulo</flux:label>
                        <flux:input wire:model="module" placeholder="ej: users, warehouse, inventory" />
                        @error('module') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Acción</flux:label>
                        <flux:input wire:model="name" placeholder="ej: create, edit, delete, view" />
                        @error('name') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Nombre para mostrar</flux:label>
                    <flux:input wire:model="displayName" placeholder="ej: Crear Usuarios" />
                </flux:field>

                <flux:field>
                    <flux:label>Descripción</flux:label>
                    <flux:textarea wire:model="description" rows="3" placeholder="Describe qué permite hacer este permiso..." />
                </flux:field>

                <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        <strong>Vista previa del nombre:</strong> {{ $module }}.{{ $name }}
                    </flux:text>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" wire:click="createPermission">
                    Crear Permiso
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showCreateModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Edit Permission Modal -->
    <flux:modal :open="$showEditModal" wire:model.boolean="showEditModal">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">Editar Permiso</flux:heading>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Nombre del permiso</flux:label>
                    <flux:input wire:model="name" />
                    @error('name') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Nombre para mostrar</flux:label>
                    <flux:input wire:model="displayName" />
                </flux:field>

                <flux:field>
                    <flux:label>Descripción</flux:label>
                    <flux:textarea wire:model="description" rows="3" />
                </flux:field>
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" wire:click="updatePermission">
                    Actualizar Permiso
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showEditModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Role Assignment Modal -->
    <flux:modal :open="$showRoleAssignModal" wire:model.boolean="showRoleAssignModal">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">
                Gestionar Roles - {{ $selectedPermission?->display_name ?: $selectedPermission?->name }}
            </flux:heading>

            <div class="space-y-3">
                @foreach($this->roles as $role)
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <flux:checkbox
                            value="{{ $role->name }}"
                            wire:model="selectedRoles"
                        />
                        <div>
                            <flux:text class="font-medium">{{ $role->display_name ?? $role->name }}</flux:text>
                            @if($role->description)
                                <flux:text class="text-sm text-zinc-500">{{ $role->description }}</flux:text>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" wire:click="updateRoleAssignments">
                    Actualizar Asignaciones
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showRoleAssignModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
