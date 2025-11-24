<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Company;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Spatie\Permission\Models\Role;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';
    public string $statusFilter = 'all';
    public string $companyFilter = '';
    public string $roleFilter = '';
    public array $selectedUsers = [];
    public bool $selectAll = false;

    // Delete modal state
    public bool $showDeleteModal = false;
    public ?int $userToDeleteId = null;
    public string $userToDeleteName = '';

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
            ->when($this->roleFilter, function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', $this->roleFilter);
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'active') {
                    $query->whereNotNull('active_at');
                } else {
                    $query->whereNull('active_at');
                }
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    #[Computed]
    public function companies()
    {
        return Company::active()->orderBy('name')->get();
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

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedUsers = $this->users->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function updatedSelectedUsers(): void
    {
        $this->selectAll = count($this->selectedUsers) === $this->users->count();
    }

    public function bulkDeactivate(): void
    {
        User::whereIn('id', $this->selectedUsers)->update(['active_at' => null]);
        $this->selectedUsers = [];
        $this->selectAll = false;
        $this->dispatch('users-updated', ['message' => 'Usuarios desactivados exitosamente']);
    }

    public function bulkActivate(): void
    {
        User::whereIn('id', $this->selectedUsers)->update(['active_at' => now()]);
        $this->selectedUsers = [];
        $this->selectAll = false;
        $this->dispatch('users-updated', ['message' => 'Usuarios activados exitosamente']);
    }

    public function confirmDeleteUser(User $user): void
    {
        $this->userToDeleteId = $user->id;
        $this->userToDeleteName = $user->name;
        $this->showDeleteModal = true;
    }

    public function deleteUser(): void
    {
        if ($this->userToDeleteId) {
            $user = User::find($this->userToDeleteId);
            if ($user) {
                $user->deleted_by = auth()->id();
                $user->save();
                $user->delete();
            }
        }

        $this->showDeleteModal = false;
        $this->userToDeleteId = null;
        $this->userToDeleteName = '';
        $this->dispatch('user-deleted', ['message' => 'Usuario eliminado exitosamente']);
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->userToDeleteId = null;
        $this->userToDeleteName = '';
    }

    public function toggleUserStatus(User $user): void
    {
        $user->update([
            'active_at' => $user->active_at ? null : now()
        ]);

        $message = $user->active_at ? 'Usuario activado' : 'Usuario desactivado';
        $this->dispatch('user-status-toggled', ['message' => $message]);
    }

    public function with(): array
    {
        return [
            'title' => 'Gestión de Usuarios',
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Gestión de Usuarios
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Administra usuarios, roles y permisos del sistema
                </flux:text>
            </div>
            <flux:button variant="primary" icon="plus" :href="route('admin.users.create')" wire:navigate>
                Nuevo Usuario
            </flux:button>
        </div>
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
                    <flux:button size="sm" variant="outline" icon="check-circle" wire:click="bulkActivate">
                        Activar
                    </flux:button>
                    <flux:button size="sm" variant="outline" icon="x-circle" wire:click="bulkDeactivate">
                        Desactivar
                    </flux:button>
                </div>
            </div>
        </flux:card>
    @endif

    <!-- Filters and Search -->
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('active_at')">
                                <div class="flex items-center gap-2">
                                    Estado
                                    @if($sortBy === 'active_at')
                                        <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-4 w-4" />
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('created_at')">
                                <div class="flex items-center gap-2">
                                    Creado
                                    @if($sortBy === 'created_at')
                                        <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-4 w-4" />
                                    @endif
                                </div>
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
                                        wire:model.live="selectedUsers"
                                        value="{{ $user->id }}"
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
                                        :color="$user->active_at ? 'green' : 'red'"
                                        size="sm"
                                    >
                                        {{ $user->active_at ? 'Activo' : 'Inactivo' }}
                                    </flux:badge>
                                </td>
                                <td class="px-6 py-4">
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ $user->created_at->format('d/m/Y') }}
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
                                            <flux:menu.item
                                                icon="{{ $user->active_at ? 'eye-slash' : 'eye' }}"
                                                wire:click="toggleUserStatus({{ $user->id }})"
                                            >
                                                {{ $user->active_at ? 'Desactivar' : 'Activar' }}
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                icon="trash"
                                                variant="danger"
                                                wire:click="confirmDeleteUser({{ $user->id }})"
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
                {{ $search ? 'No se encontraron usuarios' : 'No hay usuarios registrados' }}
            </flux:heading>
            <flux:text class="text-zinc-500 mb-6">
                @if($search)
                    No se encontraron resultados para "{{ $search }}"
                @else
                    Comienza creando el primer usuario del sistema
                @endif
            </flux:text>
            @if(!$search)
                <flux:button variant="primary" icon="plus" :href="route('admin.users.create')" wire:navigate>
                    Crear primer usuario
                </flux:button>
            @else
                <flux:button variant="outline" wire:click="$set('search', '')">
                    Limpiar búsqueda
                </flux:button>
            @endif
        </flux:card>
    @endif

    <!-- Delete Confirmation Modal -->
    <flux:modal :open="$showDeleteModal" wire:model.boolean="showDeleteModal">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30">
                <flux:icon name="exclamation-triangle" class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>

            <flux:heading size="lg" class="text-center mb-2">Eliminar Usuario</flux:heading>

            <flux:text class="text-center text-zinc-600 dark:text-zinc-400 mb-6">
                ¿Estás seguro de que quieres eliminar al usuario <strong class="text-zinc-900 dark:text-zinc-100">{{ $userToDeleteName }}</strong>?
                Esta acción se puede deshacer posteriormente.
            </flux:text>

            <div class="flex justify-center gap-3">
                <flux:button variant="outline" wire:click="cancelDelete">
                    Cancelar
                </flux:button>
                <flux:button variant="danger" wire:click="deleteUser">
                    Eliminar usuario
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>