<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Departamento;
use App\Models\Ciudad;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app')] class extends Component
{
    public User $user;

    // Profile form
    public string $name = '';
    public string $email = '';
    public ?int $companyId = null;
    public ?int $branchId = null;
    public array $selectedRoles = [];

    // Security form
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    // Profile details
    public string $fullName = '';
    public string $primaryPhone = '';
    public string $secondaryPhone = '';
    public string $emergencyContact = '';
    public string $emergencyPhone = '';
    public string $address = '';
    public ?int $departamentoId = null;
    public ?int $ciudadId = null;
    public string $postalCode = '';
    public string $notes = '';

    // Modal states
    public bool $showPasswordModal = false;
    public bool $showRolesModal = false;
    public bool $showProfileModal = false;

    // Activity history
    public array $activityHistory = [];

    public function mount(User $user): void
    {
        $this->user = $user->load(['company', 'branch', 'roles', 'profile']);
        $this->loadUserData();
    }

    private function loadUserData(): void
    {
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->companyId = $this->user->company_id;
        $this->branchId = $this->user->branch_id;
        $this->selectedRoles = $this->user->roles->pluck('name')->toArray();

        // Load profile data
        if ($this->user->profile) {
            $profile = $this->user->profile;
            $this->fullName = $profile->full_name ?? '';
            $this->primaryPhone = $profile->phone ?? '';
            $this->secondaryPhone = $profile->mobile ?? '';
            $this->emergencyContact = $profile->emergency_contact_name ?? '';
            $this->emergencyPhone = $profile->emergency_contact_phone ?? '';
            $this->address = $profile->address ?? '';
            $this->departamentoId = $profile->departamento_id;
            $this->ciudadId = $profile->ciudad_id;
            $this->postalCode = $profile->postal_code ?? '';
            $this->notes = $profile->notes ?? '';
        }

        $this->loadActivityHistory();
    }

    private function loadActivityHistory(): void
    {
        // Simulate activity history - in real app, you'd pull from audit logs
        $this->activityHistory = [
            [
                'action' => 'Inicio de sesión',
                'timestamp' => now()->subHours(2)->format('d/m/Y H:i:s'),
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Chrome 120.0.0'
            ],
            [
                'action' => 'Perfil actualizado',
                'timestamp' => now()->subDays(1)->format('d/m/Y H:i:s'),
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Chrome 120.0.0'
            ],
            [
                'action' => 'Contraseña cambiada',
                'timestamp' => now()->subDays(3)->format('d/m/Y H:i:s'),
                'ip_address' => '192.168.1.50',
                'user_agent' => 'Firefox 119.0'
            ],
        ];
    }

    #[Computed]
    public function companies()
    {
        return Company::active()->orderBy('name')->get();
    }

    #[Computed]
    public function branches()
    {
        if (!$this->companyId) {
            return collect([]);
        }
        return Branch::where('company_id', $this->companyId)->active()->orderBy('name')->get();
    }

    #[Computed]
    public function availableRoles()
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function departamentos()
    {
        return Departamento::active()->orderBy('name')->get();
    }

    #[Computed]
    public function ciudades()
    {
        if (!$this->departamentoId) {
            return collect([]);
        }
        return Ciudad::where('departamento_id', $this->departamentoId)->active()->orderBy('name')->get();
    }

    public function updatedCompanyId(): void
    {
        $this->branchId = null;
    }

    public function updatedDepartamentoId(): void
    {
        $this->ciudadId = null;
    }

    public function updateBasicInfo(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->user->id,
            'companyId' => 'nullable|exists:companies,id',
            'branchId' => 'nullable|exists:branches,id',
        ], [
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está en uso',
            'companyId.exists' => 'La empresa seleccionada no es válida',
            'branchId.exists' => 'La sucursal seleccionada no es válida',
        ]);

        $this->user->update([
            'name' => $this->name,
            'email' => $this->email,
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
        ]);

        $this->dispatch('basic-info-updated', ['message' => 'Información básica actualizada exitosamente']);
    }

    public function updatePassword(): void
    {
        $this->validate([
            'newPassword' => 'required|min:8|confirmed',
            'newPasswordConfirmation' => 'required',
        ], [
            'newPassword.required' => 'La nueva contraseña es obligatoria',
            'newPassword.min' => 'La contraseña debe tener al menos 8 caracteres',
            'newPassword.confirmed' => 'Las contraseñas no coinciden',
        ]);

        $this->user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->showPasswordModal = false;
        $this->reset(['newPassword', 'newPasswordConfirmation']);
        $this->dispatch('password-updated', ['message' => 'Contraseña actualizada exitosamente']);
    }

    public function updateRoles(): void
    {
        $this->user->syncRoles($this->selectedRoles);
        $this->showRolesModal = false;
        $this->dispatch('roles-updated', ['message' => 'Roles actualizados exitosamente']);
    }

    public function updateProfile(): void
    {
        $this->validate([
            'fullName' => 'required|string|max:255',
            'primaryPhone' => 'nullable|string|max:20',
            'secondaryPhone' => 'nullable|string|max:20',
            'emergencyContact' => 'nullable|string|max:255',
            'emergencyPhone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'departamentoId' => 'nullable|exists:departamentos,id',
            'ciudadId' => 'nullable|exists:ciudades,id',
            'postalCode' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ], [
            'fullName.required' => 'El nombre completo es obligatorio',
        ]);

        // Parse full name into first and last name
        $nameParts = explode(' ', trim($this->fullName), 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        $this->user->profile()->updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $this->primaryPhone,
                'mobile' => $this->secondaryPhone,
                'emergency_contact_name' => $this->emergencyContact,
                'emergency_contact_phone' => $this->emergencyPhone,
                'address' => $this->address,
                'departamento_id' => $this->departamentoId,
                'ciudad_id' => $this->ciudadId,
                'postal_code' => $this->postalCode,
                'notes' => $this->notes,
            ]
        );

        $this->showProfileModal = false;
        $this->dispatch('profile-updated', ['message' => 'Perfil actualizado exitosamente']);
    }

    public function toggleUserStatus(): void
    {
        $this->user->update([
            'active_at' => $this->user->active_at ? null : now()
        ]);

        $message = $this->user->active_at ? 'Usuario activado' : 'Usuario desactivado';
        $this->dispatch('user-status-toggled', ['message' => $message]);
    }

    public function deleteUser(): void
    {
        $userName = $this->user->name;
        $this->user->delete();

        $this->dispatch('user-deleted', ['message' => "Usuario {$userName} eliminado exitosamente"]);
        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => 'Perfil de Usuario - ' . $this->user->name,
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-4">
                <flux:avatar size="lg" :name="$user->name" />
                <div>
                    <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                        {{ $user->display_name }}
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ $user->email }}
                    </flux:text>
                    <div class="flex items-center gap-2 mt-1">
                        <flux:badge :color="$user->active_at ? 'green' : 'red'" size="sm">
                            {{ $user->active_at ? 'Activo' : 'Inactivo' }}
                        </flux:badge>
                        @foreach($user->roles as $role)
                            <flux:badge color="blue" size="sm">{{ $role->name }}</flux:badge>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="outline" :href="route('admin.users.index')" wire:navigate>
                    ← Volver a usuarios
                </flux:button>
                <flux:button variant="outline" :href="route('admin.users.edit', $user)" wire:navigate>
                    Editar usuario
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Información Básica</flux:heading>
                    <flux:button size="sm" variant="outline" wire:click="updateBasicInfo">
                        Guardar cambios
                    </flux:button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Nombre</flux:label>
                        <flux:input wire:model="name" />
                        @error('name') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input type="email" wire:model="email" />
                        @error('email') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Empresa</flux:label>
                        <flux:select wire:model.live="companyId" placeholder="Seleccionar empresa">
                            <flux:select.option value="">Sin empresa</flux:select.option>
                            @foreach($this->companies as $company)
                                <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Sucursal</flux:label>
                        <flux:select wire:model="branchId" placeholder="Seleccionar sucursal" :disabled="!$companyId">
                            <flux:select.option value="">Sin sucursal</flux:select.option>
                            @foreach($this->branches as $branch)
                                <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            </flux:card>

            <!-- Profile Details -->
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Detalles del Perfil</flux:heading>
                    <flux:button size="sm" variant="outline" wire:click="$set('showProfileModal', true)">
                        Editar perfil
                    </flux:button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nombre completo</flux:text>
                        <flux:text class="text-sm">{{ $fullName ?: 'No especificado' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Teléfono principal</flux:text>
                        <flux:text class="text-sm">{{ $primaryPhone ?: 'No especificado' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Teléfono secundario</flux:text>
                        <flux:text class="text-sm">{{ $secondaryPhone ?: 'No especificado' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Contacto de emergencia</flux:text>
                        <flux:text class="text-sm">{{ $emergencyContact ?: 'No especificado' }}</flux:text>
                    </div>
                    <div class="md:col-span-2">
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Dirección</flux:text>
                        <flux:text class="text-sm">
                            @if($address || $departamentoId || $ciudadId)
                                {{ $address }}
                                @if($user->profile?->ciudad), {{ $user->profile->ciudad->name }}@endif
                                @if($user->profile?->departamento), {{ $user->profile->departamento->name }}@endif
                                @if($postalCode) {{ $postalCode }}@endif
                            @else
                                No especificada
                            @endif
                        </flux:text>
                    </div>
                    @if($notes)
                        <div class="md:col-span-2">
                            <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Notas</flux:text>
                            <flux:text class="text-sm">{{ $notes }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Activity History -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Historial de Actividad</flux:heading>

                <div class="space-y-3">
                    @foreach($activityHistory as $activity)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                            <div>
                                <flux:text class="font-medium">{{ $activity['action'] }}</flux:text>
                                <flux:text class="text-sm text-zinc-500">
                                    {{ $activity['timestamp'] }} - {{ $activity['ip_address'] }}
                                </flux:text>
                            </div>
                            <flux:text class="text-xs text-zinc-400">
                                {{ $activity['user_agent'] }}
                            </flux:text>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Acciones Rápidas</flux:heading>

                <div class="space-y-3">
                    <flux:button
                        variant="outline"
                        class="w-full justify-start"
                        icon="key"
                        wire:click="$set('showPasswordModal', true)"
                    >
                        Cambiar contraseña
                    </flux:button>

                    <flux:button
                        variant="outline"
                        class="w-full justify-start"
                        icon="shield-check"
                        wire:click="$set('showRolesModal', true)"
                    >
                        Gestionar roles
                    </flux:button>

                    <flux:button
                        variant="outline"
                        class="w-full justify-start"
                        :icon="$user->active_at ? 'eye-slash' : 'eye'"
                        wire:click="toggleUserStatus"
                    >
                        {{ $user->active_at ? 'Desactivar usuario' : 'Activar usuario' }}
                    </flux:button>

                    <flux:button
                        variant="danger"
                        class="w-full justify-start"
                        icon="trash"
                        wire:click="deleteUser"
                        wire:confirm="¿Estás seguro de que quieres eliminar este usuario?"
                    >
                        Eliminar usuario
                    </flux:button>
                </div>
            </flux:card>

            <!-- User Statistics -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Estadísticas</flux:heading>

                <div class="space-y-4">
                    <div class="text-center">
                        <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                            {{ $user->roles->count() }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Roles asignados</flux:text>
                    </div>

                    <div class="text-center">
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                            {{ $user->created_at->diffInDays() }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Días en el sistema</flux:text>
                    </div>

                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:text class="text-sm text-zinc-500">
                            Creado: {{ $user->created_at->format('d/m/Y H:i') }}
                        </flux:text>
                        @if($user->updated_at != $user->created_at)
                            <flux:text class="text-sm text-zinc-500">
                                Actualizado: {{ $user->updated_at->diffForHumans() }}
                            </flux:text>
                        @endif
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    <!-- Password Change Modal -->
    <flux:modal :open="$showPasswordModal" wire:model.boolean="showPasswordModal">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">Cambiar Contraseña</flux:heading>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Nueva contraseña</flux:label>
                    <flux:input type="password" wire:model="newPassword" />
                    @error('newPassword') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Confirmar nueva contraseña</flux:label>
                    <flux:input type="password" wire:model="newPasswordConfirmation" />
                </flux:field>
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" wire:click="updatePassword">
                    Cambiar contraseña
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showPasswordModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Roles Management Modal -->
    <flux:modal :open="$showRolesModal" wire:model.boolean="showRolesModal">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">Gestionar Roles</flux:heading>

            <div class="space-y-3">
                @foreach($this->availableRoles as $role)
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
                <flux:button variant="primary" wire:click="updateRoles">
                    Actualizar roles
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showRolesModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Profile Edit Modal -->
    <flux:modal :open="$showProfileModal" wire:model.boolean="showProfileModal" class="md:w-[32rem] lg:w-[40rem]">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">Editar Perfil</flux:heading>

            <div class="space-y-4 max-h-[28rem] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Nombre completo</flux:label>
                        <flux:input wire:model="fullName" />
                        @error('fullName') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Teléfono principal</flux:label>
                        <flux:input wire:model="primaryPhone" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Teléfono secundario</flux:label>
                        <flux:input wire:model="secondaryPhone" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Contacto de emergencia</flux:label>
                        <flux:input wire:model="emergencyContact" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Teléfono de emergencia</flux:label>
                        <flux:input wire:model="emergencyPhone" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Dirección</flux:label>
                    <flux:input wire:model="address" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Departamento</flux:label>
                        <flux:select wire:model.live="departamentoId" placeholder="Seleccionar departamento">
                            <flux:select.option value="">Sin departamento</flux:select.option>
                            @foreach($this->departamentos as $departamento)
                                <flux:select.option value="{{ $departamento->id }}">{{ $departamento->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('departamentoId') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Municipio</flux:label>
                        <flux:select wire:model="ciudadId" placeholder="Seleccionar municipio" :disabled="!$departamentoId">
                            <flux:select.option value="">Sin municipio</flux:select.option>
                            @foreach($this->ciudades as $ciudad)
                                <flux:select.option value="{{ $ciudad->id }}">{{ $ciudad->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('ciudadId') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Código postal</flux:label>
                    <flux:input wire:model="postalCode" />
                </flux:field>

                <flux:field>
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" rows="3" />
                </flux:field>
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" wire:click="updateProfile">
                    Actualizar perfil
                </flux:button>
                <flux:button variant="outline" wire:click="$set('showProfileModal', false)">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>