<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';
    public array $selectedRoles = [];
    public bool $selectAll = false;

    // Modal states
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showPermissionModal = false;
    public bool $showDeleteModal = false;
    public ?Role $selectedRole = null;
    public ?int $roleToDeleteId = null;

    // Form data
    public string $name = '';
    public string $displayName = '';
    public string $description = '';
    public array $selectedPermissions = [];

    public function mount(): void
    {
        // Add authorization check if needed
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount(['permissions', 'users'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('display_name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(12);
    }

    #[Computed]
    public function permissions()
    {
        return Permission::orderBy('name')->get()->groupBy(function ($permission) {
            // Handle different naming conventions
            if (str_contains($permission->name, '.')) {
                // Standard module.action format
                return explode('.', $permission->name)[0];
            } elseif (str_contains($permission->name, '-')) {
                // action-module format (like view-branches, create-users)
                $parts = explode('-', $permission->name, 2);
                if (count($parts) >= 2) {
                    $module = $parts[1];
                    // Handle special cases
                    if (str_contains($module, '-by-')) {
                        return explode('-by-', $module)[0];
                    }
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

    /**
     * Get readable module name in Spanish.
     */
    public function getModuleDisplayName(string $module): string
    {
        $moduleNames = [
            'branches' => 'Sucursales',
            'companies' => 'Empresas',
            'users' => 'Usuarios',
            'roles' => 'Roles',
            'permissions' => 'Permisos',
            'warehouses' => 'Almacenes',
            'inventory' => 'Inventario',
            'products' => 'Productos',
            'categories' => 'Categorías',
            'suppliers' => 'Proveedores',
            'customers' => 'Clientes',
            'orders' => 'Pedidos',
            'reports' => 'Reportes',
            'settings' => 'Configuración',
            'general' => 'General',
        ];

        return $moduleNames[$module] ?? ucfirst(str_replace('-', ' ', $module));
    }

    /**
     * Get readable permission name.
     */
    public function getPermissionDisplayName($permission): string
    {
        if ($permission->display_name) {
            return $permission->display_name;
        }

        // Parse permission name to create readable format
        $name = $permission->name;

        // Action translations
        $actions = [
            'view' => 'Ver',
            'create' => 'Crear',
            'edit' => 'Editar',
            'update' => 'Actualizar',
            'delete' => 'Eliminar',
            'manage' => 'Gestionar',
            'assign' => 'Asignar',
            'toggle' => 'Cambiar estado',
            'export' => 'Exportar',
            'import' => 'Importar',
        ];

        // Try to parse action-module format (view-branches)
        if (str_contains($name, '-')) {
            $parts = explode('-', $name, 2);
            $action = $parts[0];
            $actionTranslated = $actions[$action] ?? ucfirst($action);

            return $actionTranslated;
        }

        // Try to parse module.action format (branches.view)
        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            $action = end($parts);
            $actionTranslated = $actions[$action] ?? ucfirst($action);

            return $actionTranslated;
        }

        return ucfirst(str_replace(['-', '_', '.'], ' ', $name));
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
        $this->reset(['name', 'displayName', 'description', 'selectedPermissions']);
        $this->showCreateModal = true;
    }

    public function openEditModal(int $roleId): void
    {
        $this->selectedRole = Role::find($roleId);
        if (!$this->selectedRole) {
            return;
        }
        $this->name = $this->selectedRole->name;
        $this->displayName = $this->selectedRole->display_name ?? '';
        $this->description = $this->selectedRole->description ?? '';
        $this->selectedPermissions = $this->selectedRole->permissions()->pluck('name')->toArray();
        $this->showEditModal = true;
    }

    public function openPermissionModal(int $roleId): void
    {
        $this->selectedRole = Role::find($roleId);
        if (!$this->selectedRole) {
            return;
        }
        $this->selectedPermissions = $this->selectedRole->permissions()->pluck('name')->toArray();
        $this->showPermissionModal = true;
    }

    public function createRole(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'displayName' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'selectedPermissions' => 'array',
        ], [
            'name.required' => 'El nombre del rol es obligatorio',
            'name.unique' => 'Ya existe un rol con este nombre',
        ]);

        $role = Role::create([
            'name' => $this->name,
            'slug' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
            'guard_name' => 'web',
        ]);

        if (!empty($this->selectedPermissions)) {
            $role->syncPermissions($this->selectedPermissions);
        }

        $this->showCreateModal = false;
        $this->reset(['name', 'displayName', 'description', 'selectedPermissions']);
        $this->dispatch('role-created', ['message' => 'Rol creado exitosamente']);
    }

    public function updateRole(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $this->selectedRole->id,
            'displayName' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'selectedPermissions' => 'array',
        ], [
            'name.required' => 'El nombre del rol es obligatorio',
            'name.unique' => 'Ya existe un rol con este nombre',
        ]);

        $this->selectedRole->update([
            'name' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
        ]);

        $this->selectedRole->syncPermissions($this->selectedPermissions);

        $this->showEditModal = false;
        $this->reset(['name', 'displayName', 'description', 'selectedPermissions']);
        $this->selectedRole = null;
        $this->dispatch('role-updated', ['message' => 'Rol actualizado exitosamente']);
    }

    public function updatePermissions(): void
    {
        $this->selectedRole->syncPermissions($this->selectedPermissions);
        $this->showPermissionModal = false;
        $this->selectedRole = null;
        $this->dispatch('permissions-updated', ['message' => 'Permisos actualizados exitosamente']);
    }

    public function confirmDeleteRole(int $roleId): void
    {
        $this->roleToDeleteId = $roleId;
        $this->showDeleteModal = true;
    }

    public function deleteRole(): void
    {
        $role = Role::find($this->roleToDeleteId);

        if (!$role) {
            $this->showDeleteModal = false;
            return;
        }

        if ($role->users()->count() > 0) {
            $this->dispatch('role-delete-failed', ['message' => 'No se puede eliminar un rol que tiene usuarios asignados']);
            $this->showDeleteModal = false;
            return;
        }

        $role->delete();
        $this->showDeleteModal = false;
        $this->roleToDeleteId = null;
        $this->dispatch('role-deleted', ['message' => 'Rol eliminado exitosamente']);
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->roleToDeleteId = null;
    }

    public function duplicateRole(int $roleId): void
    {
        $role = Role::find($roleId);
        if (!$role) {
            return;
        }

        $newName = $role->name . '_copia';
        $newRole = Role::create([
            'name' => $newName,
            'slug' => $newName,
            'display_name' => ($role->display_name ?? $role->name) . ' (Copia)',
            'description' => $role->description,
            'guard_name' => 'web',
        ]);

        $newRole->syncPermissions($role->permissions()->pluck('name')->toArray());

        $this->dispatch('role-duplicated', ['message' => 'Rol duplicado exitosamente']);
    }

    public function with(): array
    {
        return [
            'title' => 'Gestión de Roles',
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Gestión de Roles
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Administra roles y permisos del sistema
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="outline" icon="arrow-path" wire:click="$refresh">
                    Actualizar
                </flux:button>
                <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                    Nuevo Rol
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <flux:card class="mb-6">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar roles por nombre o descripción..."
                    icon="magnifying-glass"
                />
            </div>

            <!-- Sort Options -->
            <div class="lg:w-48">
                <flux:select wire:model.live="sortBy">
                    <flux:select.option value="name">Nombre</flux:select.option>
                    <flux:select.option value="display_name">Nombre para mostrar</flux:select.option>
                    <flux:select.option value="created_at">Fecha de creación</flux:select.option>
                    <flux:select.option value="users_count">Número de usuarios</flux:select.option>
                    <flux:select.option value="permissions_count">Número de permisos</flux:select.option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    <!-- Roles Grid -->
    @if($this->roles->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($this->roles as $role)
                <flux:card class="hover:shadow-lg transition-shadow duration-200">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1 min-w-0">
                            <flux:heading size="lg" class="truncate">
                                {{ $role->display_name ?: $role->name }}
                            </flux:heading>
                            <flux:text class="text-sm text-zinc-500 font-mono">
                                {{ $role->name }}
                            </flux:text>
                        </div>

                        <!-- Action Dropdown -->
                        <flux:dropdown align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="openEditModal({{ $role->id }})">
                                    Editar
                                </flux:menu.item>
                                <flux:menu.item icon="key" wire:click="openPermissionModal({{ $role->id }})">
                                    Gestionar permisos
                                </flux:menu.item>
                                <flux:menu.item icon="document-duplicate" wire:click="duplicateRole({{ $role->id }})">
                                    Duplicar
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="confirmDeleteRole({{ $role->id }})"
                                >
                                    Eliminar
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>

                    @if($role->description)
                        <flux:text class="text-sm line-clamp-2 mb-4">
                            {{ $role->description }}
                        </flux:text>
                    @endif

                    <!-- Role Stats -->
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="text-center">
                            <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                                {{ $role->users_count }}
                            </flux:heading>
                            <flux:text class="text-xs text-zinc-500">
                                Usuarios
                            </flux:text>
                        </div>
                        <div class="text-center">
                            <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                                {{ $role->permissions_count }}
                            </flux:heading>
                            <flux:text class="text-xs text-zinc-500">
                                Permisos
                            </flux:text>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500">
                        Creado: {{ $role->created_at->format('d/m/Y') }}
                    </div>
                </flux:card>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            {{ $this->roles->links() }}
        </div>
    @else
        <!-- Empty State -->
        <flux:card class="text-center py-12">
            <flux:icon name="shield-check" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">
                {{ $search ? 'No se encontraron roles' : 'No hay roles registrados' }}
            </flux:heading>
            <flux:text class="text-zinc-500 mb-6">
                @if($search)
                    No se encontraron resultados para "{{ $search }}"
                @else
                    Comienza creando el primer rol del sistema
                @endif
            </flux:text>
            @if(!$search)
                <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                    Crear primer rol
                </flux:button>
            @else
                <flux:button variant="outline" wire:click="$set('search', '')">
                    Limpiar búsqueda
                </flux:button>
            @endif
        </flux:card>
    @endif

    <!-- Create Role Modal -->
    <flux:modal :open="$showCreateModal" wire:model.boolean="showCreateModal" class="md:w-[32rem] lg:w-[40rem]">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">Crear Nuevo Rol</flux:heading>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Nombre del rol (técnico)</flux:label>
                    <flux:input wire:model="name" placeholder="ej: warehouse-manager" />
                    @error('name') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Nombre para mostrar</flux:label>
                    <flux:input wire:model="displayName" placeholder="ej: Gerente de Almacén" />
                </flux:field>

                <flux:field>
                    <flux:label>Descripción</flux:label>
                    <flux:textarea wire:model="description" rows="3" placeholder="Describe las responsabilidades de este rol..." />
                </flux:field>

                <flux:field>
                    <flux:label>Permisos</flux:label>
                    <div class="max-h-60 overflow-y-auto border border-zinc-300 dark:border-zinc-700 rounded-lg p-4 space-y-4">
                        @foreach($this->permissions as $module => $modulePermissions)
                            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:icon name="squares-2x2" class="h-4 w-4 text-zinc-500" />
                                    <flux:text class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">
                                        {{ $this->getModuleDisplayName($module) }}
                                    </flux:text>
                                    <flux:badge size="sm" color="zinc">{{ $modulePermissions->count() }}</flux:badge>
                                </div>
                                <div class="grid grid-cols-2 gap-2 pl-6">
                                    @foreach($modulePermissions as $permission)
                                        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded px-2 py-1">
                                            <flux:checkbox
                                                value="{{ $permission->name }}"
                                                wire:model="selectedPermissions"
                                            />
                                            <span class="text-zinc-700 dark:text-zinc-300">{{ $this->getPermissionDisplayName($permission) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </flux:field>
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" wire:click="createRole">
                    Crear Rol
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showCreateModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Edit Role Modal -->
    <flux:modal :open="$showEditModal" wire:model.boolean="showEditModal" class="md:w-[40rem] lg:w-[56rem]">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">Editar Rol</flux:heading>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Nombre del rol (técnico)</flux:label>
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

                <flux:field>
                    <flux:label>Permisos</flux:label>
                    <div class="max-h-60 overflow-y-auto border border-zinc-300 dark:border-zinc-700 rounded-lg p-4 space-y-4">
                        @foreach($this->permissions as $module => $modulePermissions)
                            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:icon name="squares-2x2" class="h-4 w-4 text-zinc-500" />
                                    <flux:text class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">
                                        {{ $this->getModuleDisplayName($module) }}
                                    </flux:text>
                                    <flux:badge size="sm" color="zinc">{{ $modulePermissions->count() }}</flux:badge>
                                </div>
                                <div class="grid grid-cols-2 gap-2 pl-6">
                                    @foreach($modulePermissions as $permission)
                                        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded px-2 py-1">
                                            <flux:checkbox
                                                value="{{ $permission->name }}"
                                                wire:model="selectedPermissions"
                                            />
                                            <span class="text-zinc-700 dark:text-zinc-300">{{ $this->getPermissionDisplayName($permission) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </flux:field>
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" wire:click="updateRole">
                    Actualizar Rol
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showEditModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Permission Management Modal -->
    <flux:modal :open="$showPermissionModal" wire:model.boolean="showPermissionModal" class="md:w-[48rem] lg:w-[64rem]">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">
                Gestionar Permisos - {{ $selectedRole?->display_name ?: $selectedRole?->name }}
            </flux:heading>

            <div class="max-h-80 overflow-y-auto border border-zinc-300 dark:border-zinc-700 rounded-lg p-4 space-y-4">
                @foreach($this->permissions as $module => $modulePermissions)
                    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <flux:icon name="squares-2x2" class="h-4 w-4 text-zinc-500" />
                                <flux:text class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">
                                    {{ $this->getModuleDisplayName($module) }}
                                </flux:text>
                                <flux:badge size="sm" color="zinc">{{ $modulePermissions->count() }}</flux:badge>
                            </div>
                            <flux:button
                                size="xs"
                                variant="outline"
                                wire:click="$toggle('selectedPermissions', {{ json_encode($modulePermissions->pluck('name')->toArray()) }})"
                            >
                                Seleccionar todo
                            </flux:button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 pl-6">
                            @foreach($modulePermissions as $permission)
                                <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded px-2 py-1">
                                    <flux:checkbox
                                        value="{{ $permission->name }}"
                                        wire:model="selectedPermissions"
                                    />
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $this->getPermissionDisplayName($permission) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" wire:click="updatePermissions">
                    Actualizar Permisos
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showPermissionModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal :open="$showDeleteModal" wire:model.boolean="showDeleteModal">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <flux:icon name="exclamation-triangle" class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">Eliminar Rol</flux:heading>
                    <flux:text class="text-zinc-500">Esta accion no se puede deshacer</flux:text>
                </div>
            </div>

            <flux:text class="mb-6">
                ¿Estás seguro de que deseas eliminar este rol? Todos los permisos asociados a este rol serán removidos.
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="outline" wire:click="cancelDelete">
                    Cancelar
                </flux:button>
                <flux:button variant="danger" wire:click="deleteRole">
                    Eliminar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>