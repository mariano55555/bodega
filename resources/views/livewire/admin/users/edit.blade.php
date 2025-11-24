<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Role;

new #[Layout('components.layouts.app')] class extends Component
{
    public User $user;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|email|max:255')]
    public string $email = '';

    #[Validate('nullable|exists:companies,id')]
    public string $company_id = '';

    #[Validate('nullable|exists:branches,id')]
    public string $branch_id = '';

    #[Validate('nullable|string|min:8')]
    public string $password = '';

    #[Validate('nullable|string|same:password')]
    public string $password_confirmation = '';

    public array $selectedRoles = [];
    public bool $is_active = true;

    public function mount(User $user): void
    {
        $this->authorize('update', $user);

        $this->user = $user;

        // Fill form with current data
        $this->name = $user->name;
        $this->email = $user->email;
        $this->company_id = $user->company_id ? (string) $user->company_id : '';
        $this->branch_id = $user->branch_id ? (string) $user->branch_id : '';
        $this->is_active = $user->active_at !== null;
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
    }

    #[Computed]
    public function companies()
    {
        return Company::active()->orderBy('name')->get();
    }

    #[Computed]
    public function branches()
    {
        if (!$this->company_id) {
            return collect([]);
        }

        return Branch::where('company_id', $this->company_id)
            ->active()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function availableRoles()
    {
        return Role::orderBy('name')->get();
    }

    public function updatedCompanyId(): void
    {
        $this->branch_id = '';
    }

    public function save(): void
    {
        $this->authorize('update', $this->user);

        $rules = $this->rules();

        // Adjust unique validation for email to exclude current user
        $rules['email'] = 'required|string|email|max:255|unique:users,email,' . $this->user->id;

        $validated = $this->validate($rules);

        // Remove empty password fields
        if (empty($this->password)) {
            unset($validated['password']);
        } else {
            $validated['password'] = bcrypt($this->password);
        }

        // Handle active status
        if ($this->is_active && !$this->user->active_at) {
            $validated['active_at'] = now();
        } elseif (!$this->is_active) {
            $validated['active_at'] = null;
        }

        $this->user->update($validated);

        // Update roles
        $this->user->syncRoles($this->selectedRoles);

        $this->dispatch('user-updated', [
            'message' => 'Usuario actualizado exitosamente',
            'user' => $this->user->name
        ]);

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->user);

        $userName = $this->user->name;
        $this->user->delete();

        $this->dispatch('user-deleted', [
            'message' => "Usuario '{$userName}' eliminado exitosamente"
        ]);

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => 'Editar Usuario - ' . $this->user->name,
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('admin.users.index')" wire:navigate>
                Volver
            </flux:button>
            <div class="flex-1">
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Editar Usuario
                </flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Actualiza la información de {{ $user->name }}
                </flux:text>
            </div>

            <!-- User Status Badge -->
            <flux:badge :color="$user->active_at ? 'green' : 'red'" size="lg">
                {{ $user->active_at ? 'Activo' : 'Inactivo' }}
            </flux:badge>
        </div>

        <!-- User Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="building-office-2" class="h-8 w-8 text-blue-500" />
                    <div>
                        <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                            {{ $user->company?->name ?? 'Sin empresa' }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Empresa</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="shield-check" class="h-8 w-8 text-green-500" />
                    <div>
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                            {{ $user->roles->count() }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Roles Asignados</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="calendar" class="h-8 w-8 text-purple-500" />
                    <div>
                        <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">
                            {{ $user->created_at->format('M Y') }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Registrado</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <!-- Basic Information -->
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">Información Básica</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400 mt-1">
                    Datos principales del usuario
                </flux:text>
            </div>

            <flux:separator />

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Name -->
                <flux:field>
                    <flux:label>Nombre Completo *</flux:label>
                    <flux:input wire:model="name" placeholder="Ej: Juan Pérez García" />
                    <flux:error name="name" />
                </flux:field>

                <!-- Email -->
                <flux:field>
                    <flux:label>Correo Electrónico *</flux:label>
                    <flux:input wire:model="email" type="email" placeholder="usuario@empresa.com" />
                    <flux:error name="email" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Organization Assignment -->
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">Asignación Organizacional</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400 mt-1">
                    Empresa y sucursal del usuario
                </flux:text>
            </div>

            <flux:separator />

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Company -->
                <flux:field>
                    <flux:label>Empresa</flux:label>
                    <flux:select wire:model.live="company_id" placeholder="Selecciona una empresa">
                        <flux:select.option value="">Sin empresa asignada</flux:select.option>
                        @foreach($this->companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:description>Selecciona la empresa a la que pertenece el usuario</flux:description>
                    <flux:error name="company_id" />
                </flux:field>

                <!-- Branch -->
                <flux:field>
                    <flux:label>Sucursal</flux:label>
                    <flux:select wire:model="branch_id" placeholder="Selecciona una sucursal" :disabled="!$company_id">
                        <flux:select.option value="">Sin sucursal asignada</flux:select.option>
                        @foreach($this->branches as $branch)
                            <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:description>{{ !$company_id ? 'Primero selecciona una empresa' : 'Sucursal específica del usuario' }}</flux:description>
                    <flux:error name="branch_id" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Password Update -->
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">Actualizar Contraseña</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400 mt-1">
                    Deja en blanco si no deseas cambiar la contraseña
                </flux:text>
            </div>

            <flux:separator />

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Password -->
                <flux:field>
                    <flux:label>Nueva Contraseña</flux:label>
                    <flux:input wire:model="password" type="password" placeholder="Mínimo 8 caracteres" />
                    <flux:description>Deja en blanco para mantener la contraseña actual</flux:description>
                    <flux:error name="password" />
                </flux:field>

                <!-- Password Confirmation -->
                <flux:field>
                    <flux:label>Confirmar Nueva Contraseña</flux:label>
                    <flux:input wire:model="password_confirmation" type="password" placeholder="Repite la contraseña" />
                    <flux:error name="password_confirmation" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Roles and Permissions -->
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">Roles y Permisos</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400 mt-1">
                    Asigna roles para definir los permisos del usuario
                </flux:text>
            </div>

            <flux:separator />

            <div>
                <flux:label class="mb-3">Roles Asignados</flux:label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($this->availableRoles as $role)
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition-colors">
                            <flux:checkbox
                                wire:model="selectedRoles"
                                value="{{ $role->name }}"
                            />
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ ucwords(str_replace('-', ' ', $role->name)) }}</span>
                                @if($role->description)
                                    <p class="text-xs text-zinc-500">{{ $role->description }}</p>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
                <flux:description class="mt-3">Selecciona uno o más roles para el usuario</flux:description>
                <flux:error name="selectedRoles" />
            </div>
        </flux:card>

        <!-- Status -->
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">Estado del Usuario</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400 mt-1">
                    Configuración del estado de acceso
                </flux:text>
            </div>

            <flux:separator />

            <div class="flex items-start gap-4 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                <flux:switch wire:model="is_active" />
                <div>
                    <flux:label class="cursor-pointer">Usuario activo</flux:label>
                    <flux:description>Determina si el usuario puede acceder al sistema</flux:description>
                </div>
            </div>
            <flux:error name="is_active" />
        </flux:card>

        <!-- Form Actions -->
        <flux:card class="!bg-zinc-50 dark:!bg-zinc-800/50">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate icon="arrow-left">
                        Cancelar
                    </flux:button>
                    @can('delete', $user)
                        <flux:button
                            variant="danger"
                            icon="trash"
                            wire:click="delete"
                            wire:confirm="¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer."
                        >
                            Eliminar
                        </flux:button>
                    @endcan
                </div>
                <flux:button type="submit" variant="primary" icon="check">
                    Guardar Cambios
                </flux:button>
            </div>
        </flux:card>
    </form>
</div>
