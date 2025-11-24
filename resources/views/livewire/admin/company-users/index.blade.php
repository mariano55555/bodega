<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Warehouse;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';
    public string $statusFilter = 'all';
    public string $roleFilter = '';
    public string $companyFilter = '';
    public string $branchFilter = '';
    public array $selectedUsers = [];
    public bool $selectAll = false;

    // Modal states
    public bool $showInviteModal = false;
    public bool $showBulkAssignModal = false;
    public bool $showWarehouseAccessModal = false;
    public ?User $selectedUser = null;

    // Invite form
    public string $inviteName = '';
    public string $inviteEmail = '';
    public ?int $inviteCompanyId = null;
    public ?int $inviteBranchId = null;
    public array $inviteRoles = [];

    // Bulk assign form
    public string $bulkAction = '';
    public array $bulkRoles = [];
    public ?int $bulkCompanyId = null;
    public ?int $bulkBranchId = null;

    // Warehouse access
    public array $selectedWarehouses = [];

    public function mount(): void
    {
        // Add authorization check if needed
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with(['company', 'branch', 'roles'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->companyFilter, function ($query) {
                $query->where('company_id', $this->companyFilter);
            })
            ->when($this->branchFilter, function ($query) {
                $query->where('branch_id', $this->branchFilter);
            })
            ->when($this->roleFilter, function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', $this->roleFilter);
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'active') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    #[Computed]
    public function companies()
    {
        return Company::active()->withCount('users')->orderBy('name')->get();
    }

    #[Computed]
    public function branches()
    {
        if (!$this->companyFilter) {
            return collect([]);
        }
        return Branch::where('company_id', $this->companyFilter)->active()->orderBy('name')->get();
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function warehouses()
    {
        if (!$this->selectedUser) {
            return collect([]);
        }

        // Get warehouses for the user's company/branch
        $query = Warehouse::query();

        if ($this->selectedUser->company_id) {
            $query->where('company_id', $this->selectedUser->company_id);
        }

        if ($this->selectedUser->branch_id) {
            $query->where('branch_id', $this->selectedUser->branch_id);
        }

        return $query->orderBy('name')->get();
    }

    #[Computed]
    public function inviteBranches()
    {
        if (!$this->inviteCompanyId) {
            return collect([]);
        }
        return Branch::where('company_id', $this->inviteCompanyId)->active()->orderBy('name')->get();
    }

    #[Computed]
    public function bulkBranches()
    {
        if (!$this->bulkCompanyId) {
            return collect([]);
        }
        return Branch::where('company_id', $this->bulkCompanyId)->active()->orderBy('name')->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCompanyFilter(): void
    {
        $this->branchFilter = '';
        $this->resetPage();
    }

    public function updatedInviteCompanyId(): void
    {
        $this->inviteBranchId = null;
    }

    public function updatedBulkCompanyId(): void
    {
        $this->bulkBranchId = null;
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

    public function openInviteModal(): void
    {
        $this->reset(['inviteName', 'inviteEmail', 'inviteCompanyId', 'inviteBranchId', 'inviteRoles']);
        $this->showInviteModal = true;
    }

    public function openBulkAssignModal(): void
    {
        if (empty($this->selectedUsers)) {
            $this->dispatch('no-users-selected', ['message' => 'Debes seleccionar al menos un usuario']);
            return;
        }

        $this->reset(['bulkAction', 'bulkRoles', 'bulkCompanyId', 'bulkBranchId']);
        $this->showBulkAssignModal = true;
    }

    public function openWarehouseAccessModal(User $user): void
    {
        $this->selectedUser = $user;
        // In a real app, you'd load user's current warehouse access
        $this->selectedWarehouses = [];
        $this->showWarehouseAccessModal = true;
    }

    public function inviteUser(): void
    {
        $this->validate([
            'inviteName' => 'required|string|max:255',
            'inviteEmail' => 'required|email|max:255|unique:users,email',
            'inviteCompanyId' => 'nullable|exists:companies,id',
            'inviteBranchId' => 'nullable|exists:branches,id',
            'inviteRoles' => 'array',
        ], [
            'inviteName.required' => 'El nombre es obligatorio',
            'inviteEmail.required' => 'El email es obligatorio',
            'inviteEmail.email' => 'El email debe ser válido',
            'inviteEmail.unique' => 'Este email ya está en uso',
        ]);

        $tempPassword = Str::random(12);

        $user = User::create([
            'name' => $this->inviteName,
            'email' => $this->inviteEmail,
            'password' => Hash::make($tempPassword),
            'company_id' => $this->inviteCompanyId,
            'branch_id' => $this->inviteBranchId,
            'email_verified_at' => null, // User must verify email
        ]);

        if (!empty($this->inviteRoles)) {
            $user->syncRoles($this->inviteRoles);
        }

        // In a real app, you'd send an email invitation with the temp password
        $this->showInviteModal = false;
        $this->reset(['inviteName', 'inviteEmail', 'inviteCompanyId', 'inviteBranchId', 'inviteRoles']);
        $this->dispatch('user-invited', ['message' => "Usuario invitado exitosamente. Contraseña temporal: {$tempPassword}"]);
    }

    public function processBulkAction(): void
    {
        if (empty($this->selectedUsers)) {
            return;
        }

        $users = User::whereIn('id', $this->selectedUsers);

        switch ($this->bulkAction) {
            case 'assign_roles':
                if (!empty($this->bulkRoles)) {
                    foreach ($users->get() as $user) {
                        $user->syncRoles(array_merge($user->roles->pluck('name')->toArray(), $this->bulkRoles));
                    }
                    $message = 'Roles asignados a los usuarios seleccionados';
                }
                break;

            case 'replace_roles':
                if (!empty($this->bulkRoles)) {
                    foreach ($users->get() as $user) {
                        $user->syncRoles($this->bulkRoles);
                    }
                    $message = 'Roles reemplazados en los usuarios seleccionados';
                }
                break;

            case 'change_company':
                if ($this->bulkCompanyId) {
                    $users->update([
                        'company_id' => $this->bulkCompanyId,
                        'branch_id' => $this->bulkBranchId,
                    ]);
                    $message = 'Empresa/sucursal actualizada para los usuarios seleccionados';
                }
                break;

            case 'activate':
                $users->update(['email_verified_at' => now()]);
                $message = 'Usuarios activados exitosamente';
                break;

            case 'deactivate':
                $users->update(['email_verified_at' => null]);
                $message = 'Usuarios desactivados exitosamente';
                break;

            default:
                $message = 'Acción no válida';
                break;
        }

        $this->showBulkAssignModal = false;
        $this->selectedUsers = [];
        $this->selectAll = false;
        $this->dispatch('bulk-action-completed', ['message' => $message ?? 'Acción completada']);
    }

    public function updateWarehouseAccess(): void
    {
        // In a real app, you'd update the user's warehouse access in a pivot table
        // For now, we'll just simulate the action

        $this->showWarehouseAccessModal = false;
        $this->selectedUser = null;
        $this->selectedWarehouses = [];
        $this->dispatch('warehouse-access-updated', ['message' => 'Acceso a almacenes actualizado']);
    }

    public function toggleUserSelection($userId): void
    {
        if (in_array($userId, $this->selectedUsers)) {
            $this->selectedUsers = array_filter($this->selectedUsers, fn($id) => $id !== $userId);
        } else {
            $this->selectedUsers[] = $userId;
        }

        $this->selectAll = count($this->selectedUsers) === $this->users->count();
    }

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedUsers = $this->users->pluck('id')->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
        $this->dispatch('user-deleted', ['message' => 'Usuario eliminado exitosamente']);
    }

    public function toggleUserStatus(User $user): void
    {
        $user->update([
            'email_verified_at' => $user->email_verified_at ? null : now()
        ]);

        $message = $user->email_verified_at ? 'Usuario activado' : 'Usuario desactivado';
        $this->dispatch('user-status-toggled', ['message' => $message]);
    }

    public function with(): array
    {
        return [
            'title' => 'Administración de Usuarios por Empresa',
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Administración de Usuarios por Empresa
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Gestiona usuarios, asignaciones de empresas y accesos a almacenes
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="outline" icon="arrow-path" wire:click="$refresh">
                    Actualizar
                </flux:button>
                <flux:button variant="primary" icon="envelope" wire:click="openInviteModal">
                    Invitar Usuario
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Company Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach($this->companies->take(4) as $company)
            <flux:card class="hover:shadow-lg transition-shadow cursor-pointer"
                wire:click="$set('companyFilter', {{ $company->id }})">
                <div class="text-center">
                    <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                        {{ $company->users_count }}
                    </flux:heading>
                    <flux:text class="text-sm font-medium">{{ $company->name }}</flux:text>
                    <flux:text class="text-xs text-zinc-500">usuarios</flux:text>
                </div>
            </flux:card>
        @endforeach
    </div>

    <!-- Bulk Actions Bar -->
    @if(count($selectedUsers) > 0)
        <flux:card class="mb-6 border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <flux:text class="font-medium">
                        {{ count($selectedUsers) }} usuarios seleccionados
                    </flux:text>
                    <flux:button size="sm" variant="ghost" wire:click="$set('selectedUsers', []); $set('selectAll', false)">
                        Deseleccionar todo
                    </flux:button>
                </div>
                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="outline" icon="cog" wire:click="openBulkAssignModal">
                        Acciones masivas
                    </flux:button>
                </div>
            </div>
        </flux:card>
    @endif

    <!-- Filters and Search -->
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <!-- Search -->
            <div class="lg:col-span-2">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar usuarios por nombre o email..."
                    icon="magnifying-glass"
                />
            </div>

            <!-- Company Filter -->
            <div>
                <flux:select wire:model.live="companyFilter" placeholder="Todas las empresas">
                    <flux:select.option value="">Todas las empresas</flux:select.option>
                    @foreach($this->companies as $company)
                        <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Branch Filter -->
            <div>
                <flux:select wire:model.live="branchFilter" placeholder="Todas las sucursales" :disabled="!$companyFilter">
                    <flux:select.option value="">Todas las sucursales</flux:select.option>
                    @foreach($this->branches as $branch)
                        <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Role Filter -->
            <div>
                <flux:select wire:model.live="roleFilter" placeholder="Todos los roles">
                    <flux:select.option value="">Todos los roles</flux:select.option>
                    @foreach($this->roles as $role)
                        <flux:select.option value="{{ $role->name }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Status Filter -->
            <div>
                <flux:select wire:model.live="statusFilter">
                    <flux:select.option value="all">Todos los estados</flux:select.option>
                    <flux:select.option value="active">Activos</flux:select.option>
                    <flux:select.option value="inactive">Inactivos</flux:select.option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    <!-- Users Table -->
    @if($this->users->count() > 0)
        <flux:card class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <flux:checkbox
                                    wire:model.live="selectAll"
                                    class="text-blue-600"
                                />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('name')">
                                <div class="flex items-center gap-2">
                                    Usuario
                                    @if($sortBy === 'name')
                                        <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-4 w-4" />
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Empresa / Sucursal
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Roles
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Último acceso
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->users as $user)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4">
                                    <flux:checkbox
                                        value="{{ $user->id }}"
                                        wire:click="toggleUserSelection({{ $user->id }})"
                                        :checked="in_array($user->id, $selectedUsers)"
                                        class="text-blue-600"
                                    />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <flux:avatar size="sm" :name="$user->name" />
                                        <div>
                                            <flux:heading size="sm" class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $user->name }}
                                            </flux:heading>
                                            <flux:text class="text-sm text-zinc-500">
                                                {{ $user->email }}
                                            </flux:text>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        @if($user->company)
                                            <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $user->company->name }}
                                            </div>
                                        @else
                                            <div class="text-zinc-400 italic">Sin empresa</div>
                                        @endif
                                        @if($user->branch)
                                            <div class="text-zinc-500">
                                                {{ $user->branch->name }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($user->roles as $role)
                                            <flux:badge size="sm" color="blue">
                                                {{ $role->name }}
                                            </flux:badge>
                                        @empty
                                            <flux:text class="text-sm text-zinc-400">Sin roles</flux:text>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <flux:badge
                                        :color="$user->email_verified_at ? 'green' : 'red'"
                                        size="sm"
                                    >
                                        {{ $user->email_verified_at ? 'Activo' : 'Inactivo' }}
                                    </flux:badge>
                                </td>
                                <td class="px-6 py-4">
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ $user->updated_at->diffForHumans() }}
                                    </flux:text>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <flux:dropdown align="end">
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                        <flux:menu>
                                            <flux:menu.item icon="user" :href="route('admin.users.profile', $user)" wire:navigate>
                                                Ver perfil
                                            </flux:menu.item>
                                            <flux:menu.item icon="pencil" :href="route('admin.users.edit', $user)" wire:navigate>
                                                Editar
                                            </flux:menu.item>
                                            <flux:menu.item icon="building-office" wire:click="openWarehouseAccessModal({{ $user->id }})">
                                                Acceso a almacenes
                                            </flux:menu.item>
                                            <flux:menu.item
                                                icon="{{ $user->email_verified_at ? 'eye-slash' : 'eye' }}"
                                                wire:click="toggleUserStatus({{ $user->id }})"
                                            >
                                                {{ $user->email_verified_at ? 'Desactivar' : 'Activar' }}
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                icon="trash"
                                                variant="danger"
                                                wire:click="deleteUser({{ $user->id }})"
                                                wire:confirm="¿Estás seguro de que quieres eliminar este usuario?"
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

        <!-- Pagination -->
        <div class="mt-6 flex justify-center">
            {{ $this->users->links() }}
        </div>
    @else
        <!-- Empty State -->
        <flux:card class="text-center py-12">
            <flux:icon name="users" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">
                {{ $search || $companyFilter || $roleFilter ? 'No se encontraron usuarios' : 'No hay usuarios registrados' }}
            </flux:heading>
            <flux:text class="text-zinc-500 mb-6">
                @if($search || $companyFilter || $roleFilter)
                    Ajusta los filtros para ver más resultados
                @else
                    Comienza invitando el primer usuario al sistema
                @endif
            </flux:text>
            @if(!$search && !$companyFilter && !$roleFilter)
                <flux:button variant="primary" icon="envelope" wire:click="openInviteModal">
                    Invitar primer usuario
                </flux:button>
            @else
                <flux:button variant="outline" wire:click="$set('search', ''); $set('companyFilter', ''); $set('roleFilter', '')">
                    Limpiar filtros
                </flux:button>
            @endif
        </flux:card>
    @endif

    <!-- Invite User Modal -->
    <flux:modal :open="$showInviteModal" wire:model.boolean="showInviteModal">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">Invitar Usuario</flux:heading>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input wire:model="inviteName" />
                    @error('inviteName') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="inviteEmail" />
                    @error('inviteEmail') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Empresa</flux:label>
                        <flux:select wire:model.live="inviteCompanyId" placeholder="Seleccionar empresa">
                            <flux:select.option value="">Sin empresa</flux:select.option>
                            @foreach($this->companies as $company)
                                <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Sucursal</flux:label>
                        <flux:select wire:model="inviteBranchId" placeholder="Seleccionar sucursal" :disabled="!$inviteCompanyId">
                            <flux:select.option value="">Sin sucursal</flux:select.option>
                            @foreach($this->inviteBranches as $branch)
                                <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Roles</flux:label>
                    <div class="space-y-2 max-h-40 overflow-y-auto border border-zinc-300 dark:border-zinc-700 rounded-lg p-3">
                        @foreach($this->roles as $role)
                            <label class="flex items-center gap-2 text-sm">
                                <flux:checkbox
                                    value="{{ $role->name }}"
                                    wire:model="inviteRoles"
                                />
                                <span>{{ $role->display_name ?? $role->name }}</span>
                                @if($role->description)
                                    <span class="text-zinc-500 text-xs">- {{ $role->description }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </flux:field>
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" wire:click="inviteUser">
                    Enviar invitación
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showInviteModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Bulk Actions Modal -->
    <flux:modal :open="$showBulkAssignModal" wire:model.boolean="showBulkAssignModal">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">Acciones Masivas</flux:heading>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Acción</flux:label>
                    <flux:select wire:model.live="bulkAction" placeholder="Seleccionar acción">
                        <flux:select.option value="assign_roles">Asignar roles adicionales</flux:select.option>
                        <flux:select.option value="replace_roles">Reemplazar todos los roles</flux:select.option>
                        <flux:select.option value="change_company">Cambiar empresa/sucursal</flux:select.option>
                        <flux:select.option value="activate">Activar usuarios</flux:select.option>
                        <flux:select.option value="deactivate">Desactivar usuarios</flux:select.option>
                    </flux:select>
                </flux:field>

                @if(in_array($bulkAction, ['assign_roles', 'replace_roles']))
                    <flux:field>
                        <flux:label>Roles</flux:label>
                        <div class="space-y-2 max-h-40 overflow-y-auto border border-zinc-300 dark:border-zinc-700 rounded-lg p-3">
                            @foreach($this->roles as $role)
                                <label class="flex items-center gap-2 text-sm">
                                    <flux:checkbox
                                        value="{{ $role->name }}"
                                        wire:model="bulkRoles"
                                    />
                                    <span>{{ $role->display_name ?? $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </flux:field>
                @endif

                @if($bulkAction === 'change_company')
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Nueva empresa</flux:label>
                            <flux:select wire:model.live="bulkCompanyId" placeholder="Seleccionar empresa">
                                @foreach($this->companies as $company)
                                    <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <flux:field>
                            <flux:label>Nueva sucursal</flux:label>
                            <flux:select wire:model="bulkBranchId" placeholder="Seleccionar sucursal" :disabled="!$bulkCompanyId">
                                <flux:select.option value="">Sin sucursal</flux:select.option>
                                @foreach($this->bulkBranches as $branch)
                                    <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>
                @endif
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" wire:click="processBulkAction">
                    Ejecutar acción
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showBulkAssignModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Warehouse Access Modal -->
    <flux:modal :open="$showWarehouseAccessModal" wire:model.boolean="showWarehouseAccessModal">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">
                Acceso a Almacenes - {{ $selectedUser?->name }}
            </flux:heading>

            <div class="space-y-3">
                @forelse($this->warehouses as $warehouse)
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <flux:checkbox
                            value="{{ $warehouse->id }}"
                            wire:model="selectedWarehouses"
                        />
                        <div>
                            <flux:text class="font-medium">{{ $warehouse->name }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">
                                {{ $warehouse->branch?->name }} - {{ $warehouse->company?->name }}
                            </flux:text>
                        </div>
                    </label>
                @empty
                    <flux:text class="text-zinc-500">
                        No hay almacenes disponibles para este usuario
                    </flux:text>
                @endforelse
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" wire:click="updateWarehouseAccess">
                    Actualizar acceso
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showWarehouseAccessModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>