<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

new #[Layout('components.layouts.app')] class extends Component
{
    // Basic user information
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?int $companyId = null;
    public ?int $branchId = null;

    // Profile information
    public string $fullName = '';
    public string $primaryPhone = '';
    public string $secondaryPhone = '';
    public string $emergencyContact = '';
    public string $emergencyPhone = '';
    public string $address = '';
    public string $city = '';
    public string $state = '';
    public string $postalCode = '';
    public string $country = 'El Salvador';
    public string $notes = '';

    // Role and permission assignments
    public array $selectedRoles = [];
    public array $selectedWarehouses = [];

    // Form state
    public bool $sendWelcomeEmail = true;
    public bool $generateRandomPassword = false;
    public bool $requirePasswordChange = true;
    public bool $isActive = true;

    public function mount(): void
    {
        // Set default password
        $this->password = Str::random(12);
        $this->password_confirmation = $this->password;
        $this->generateRandomPassword = true;

        // Add authorization check if needed
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
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function warehouses()
    {
        if (!$this->companyId) {
            return collect([]);
        }

        $query = \App\Models\Warehouse::where('company_id', $this->companyId);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        return $query->orderBy('name')->get();
    }

    public function updatedCompanyId(): void
    {
        $this->branchId = null;
        $this->selectedWarehouses = [];
    }

    public function updatedBranchId(): void
    {
        $this->selectedWarehouses = [];
    }

    public function updatedState(): void
    {
        $this->city = '';
    }

    #[Computed]
    public function departments()
    {
        return [
            'Ahuachapán' => 'Ahuachapán',
            'Cabañas' => 'Cabañas',
            'Chalatenango' => 'Chalatenango',
            'Cuscatlán' => 'Cuscatlán',
            'La Libertad' => 'La Libertad',
            'La Paz' => 'La Paz',
            'La Unión' => 'La Unión',
            'Morazán' => 'Morazán',
            'San Miguel' => 'San Miguel',
            'San Salvador' => 'San Salvador',
            'San Vicente' => 'San Vicente',
            'Santa Ana' => 'Santa Ana',
            'Sonsonate' => 'Sonsonate',
            'Usulután' => 'Usulután',
        ];
    }

    #[Computed]
    public function municipalities()
    {
        $municipalitiesByDepartment = [
            'Ahuachapán' => ['Ahuachapán', 'Apaneca', 'Atiquizaya', 'Concepción de Ataco', 'El Refugio', 'Guaymango', 'Jujutla', 'San Francisco Menéndez', 'San Lorenzo', 'San Pedro Puxtla', 'Tacuba', 'Turín'],
            'Cabañas' => ['Cinquera', 'Dolores', 'Guacotecti', 'Ilobasco', 'Jutiapa', 'San Isidro', 'Sensuntepeque', 'Tejutepeque', 'Victoria'],
            'Chalatenango' => ['Agua Caliente', 'Arcatao', 'Azacualpa', 'Chalatenango', 'Citalá', 'Comalapa', 'Concepción Quezaltepeque', 'Dulce Nombre de María', 'El Carrizal', 'El Paraíso', 'La Laguna', 'La Palma', 'La Reina', 'Las Vueltas', 'Nombre de Jesús', 'Nueva Concepción', 'Nueva Trinidad', 'Ojos de Agua', 'Potonico', 'San Antonio de la Cruz', 'San Antonio Los Ranchos', 'San Fernando', 'San Francisco Lempa', 'San Francisco Morazán', 'San Ignacio', 'San Isidro Labrador', 'San José Cancasque', 'San José Las Flores', 'San Luis del Carmen', 'San Miguel de Mercedes', 'San Rafael', 'Santa Rita', 'Tejutla'],
            'Cuscatlán' => ['Candelaria', 'Cojutepeque', 'El Carmen', 'El Rosario', 'Monte San Juan', 'Oratorio de Concepción', 'San Bartolomé Perulapía', 'San Cristóbal', 'San José Guayabal', 'San Pedro Perulapán', 'San Rafael Cedros', 'San Ramón', 'Santa Cruz Analquito', 'Santa Cruz Michapa', 'Suchitoto', 'Tenancingo'],
            'La Libertad' => ['Antiguo Cuscatlán', 'Ciudad Arce', 'Colón', 'Comasagua', 'Chiltiupán', 'Huizúcar', 'Jayaque', 'Jicalapa', 'La Libertad', 'Santa Tecla', 'Nuevo Cuscatlán', 'San Juan Opico', 'Quezaltepeque', 'Sacacoyo', 'San José Villanueva', 'San Matías', 'San Pablo Tacachico', 'Talnique', 'Tamanique', 'Teotepeque', 'Tepecoyo', 'Zaragoza'],
            'La Paz' => ['Cuyultitán', 'El Rosario', 'Jerusalén', 'Mercedes La Ceiba', 'Olocuilta', 'Paraíso de Osorio', 'San Antonio Masahuat', 'San Emigdio', 'San Francisco Chinameca', 'San Juan Nonualco', 'San Juan Talpa', 'San Juan Tepezontes', 'San Luis La Herradura', 'San Luis Talpa', 'San Miguel Tepezontes', 'San Pedro Masahuat', 'San Pedro Nonualco', 'San Rafael Obrajuelo', 'Santa María Ostuma', 'Santiago Nonualco', 'Tapalhuaca', 'Zacatecoluca'],
            'La Unión' => ['Anamorós', 'Bolívar', 'Concepción de Oriente', 'Conchagua', 'El Carmen', 'El Sauce', 'Intipucá', 'La Unión', 'Lislique', 'Meanguera del Golfo', 'Nueva Esparta', 'Pasaquina', 'Polorós', 'San Alejo', 'San José', 'Santa Rosa de Lima', 'Yayantique', 'Yucuaiquín'],
            'Morazán' => ['Arambala', 'Cacaopera', 'Chilanga', 'Corinto', 'Delicias de Concepción', 'El Divisadero', 'El Rosario', 'Gualococti', 'Guatajiagua', 'Joateca', 'Jocoaitique', 'Jocoro', 'Lolotiquillo', 'Meanguera', 'Osicala', 'Perquín', 'San Carlos', 'San Fernando', 'San Francisco Gotera', 'San Isidro', 'San Simón', 'Sensembra', 'Sociedad', 'Torola', 'Yamabal', 'Yoloaiquín'],
            'San Miguel' => ['Carolina', 'Chapeltique', 'Chinameca', 'Chirilagua', 'Ciudad Barrios', 'Comacarán', 'El Tránsito', 'Lolotique', 'Moncagua', 'Nueva Guadalupe', 'Nuevo Edén de San Juan', 'Quelepa', 'San Antonio', 'San Gerardo', 'San Jorge', 'San Luis de la Reina', 'San Miguel', 'San Rafael Oriente', 'Sesori', 'Uluazapa'],
            'San Salvador' => ['Aguilares', 'Apopa', 'Ayutuxtepeque', 'Cuscatancingo', 'Ciudad Delgado', 'El Paisnal', 'Guazapa', 'Ilopango', 'Mejicanos', 'Nejapa', 'Panchimalco', 'Rosario de Mora', 'San Marcos', 'San Martín', 'San Salvador', 'Santiago Texacuangos', 'Santo Tomás', 'Soyapango', 'Tonacatepeque'],
            'San Vicente' => ['Apastepeque', 'Guadalupe', 'San Cayetano Istepeque', 'San Esteban Catarina', 'San Ildefonso', 'San Lorenzo', 'San Sebastián', 'San Vicente', 'Santa Clara', 'Santo Domingo', 'Tecoluca', 'Tepetitán', 'Verapaz'],
            'Santa Ana' => ['Candelaria de la Frontera', 'Chalchuapa', 'Coatepeque', 'El Congo', 'El Porvenir', 'Masahuat', 'Metapán', 'San Antonio Pajonal', 'San Sebastián Salitrillo', 'Santa Ana', 'Santa Rosa Guachipilín', 'Santiago de la Frontera', 'Texistepeque'],
            'Sonsonate' => ['Acajutla', 'Armenia', 'Caluco', 'Cuisnahuat', 'Izalco', 'Juayúa', 'Nahuizalco', 'Nahulingo', 'Salcoatitán', 'San Antonio del Monte', 'San Julián', 'Santa Catarina Masahuat', 'Santa Isabel Ishuatán', 'Santo Domingo de Guzmán', 'Sonsonate', 'Sonzacate'],
            'Usulután' => ['Alegría', 'Berlín', 'California', 'Concepción Batres', 'El Triunfo', 'Ereguayquín', 'Estanzuelas', 'Jiquilisco', 'Jucuapa', 'Jucuarán', 'Mercedes Umaña', 'Nueva Granada', 'Ozatlán', 'Puerto El Triunfo', 'San Agustín', 'San Buenaventura', 'San Dionisio', 'San Francisco Javier', 'Santa Elena', 'Santa María', 'Santiago de María', 'Tecapán', 'Usulután'],
        ];

        if (!$this->state || !isset($municipalitiesByDepartment[$this->state])) {
            return [];
        }

        return array_combine(
            $municipalitiesByDepartment[$this->state],
            $municipalitiesByDepartment[$this->state]
        );
    }

    public function updatedGenerateRandomPassword(): void
    {
        if ($this->generateRandomPassword) {
            $this->password = Str::random(12);
            $this->password_confirmation = $this->password;
        } else {
            $this->password = '';
            $this->password_confirmation = '';
        }
    }

    public function generateNewPassword(): void
    {
        $newPassword = Str::random(12);
        $this->password = $newPassword;
        $this->password_confirmation = $newPassword;

        $this->dispatch('password-generated', ['password' => $newPassword]);
    }

    public function createUser(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'companyId' => 'nullable|exists:companies,id',
            'branchId' => 'nullable|exists:branches,id',
            'fullName' => 'required|string|max:255',
            'primaryPhone' => 'nullable|string|max:20',
            'secondaryPhone' => 'nullable|string|max:20',
            'emergencyContact' => 'nullable|string|max:255',
            'emergencyPhone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postalCode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'selectedRoles' => 'array',
            'selectedWarehouses' => 'array',
        ];

        $messages = [
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está en uso',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'fullName.required' => 'El nombre completo es obligatorio',
            'companyId.exists' => 'La empresa seleccionada no es válida',
            'branchId.exists' => 'La sucursal seleccionada no es válida',
        ];

        $this->validate($rules, $messages);

        // Create the user
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'email_verified_at' => $this->isActive ? now() : null,
            'active_at' => $this->isActive ? now() : null,
        ]);

        // Parse full name into first and last name
        $nameParts = explode(' ', trim($this->fullName), 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        // Create user profile with correct field mappings
        $user->profile()->create([
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $this->primaryPhone,
            'mobile' => $this->secondaryPhone,
            'emergency_contact_name' => $this->emergencyContact,
            'emergency_contact_phone' => $this->emergencyPhone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'is_active' => $this->isActive,
        ]);

        // Assign roles
        if (!empty($this->selectedRoles)) {
            $user->syncRoles($this->selectedRoles);
        }

        // Assign warehouse access
        foreach ($this->selectedWarehouses as $warehouseId) {
            $user->grantWarehouseAccess($warehouseId, 'full');
        }

        // In a real app, you'd send welcome email if requested
        if ($this->sendWelcomeEmail) {
            // Mail::to($user->email)->send(new WelcomeUser($user, $this->password));
        }

        $this->dispatch('user-created', [
            'message' => 'Usuario creado exitosamente',
            'userId' => $user->id,
            'password' => $this->generateRandomPassword ? $this->password : null
        ]);

        $this->redirect(route('admin.users.profile', $user), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => 'Crear Nuevo Usuario',
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Crear Nuevo Usuario
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Complete la información para crear una nueva cuenta de usuario
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="outline" :href="route('admin.users.index')" wire:navigate>
                    ← Cancelar
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Información Básica</flux:heading>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Nombre de usuario</flux:label>
                            <flux:input wire:model="name" placeholder="ej: Juan Pérez" />
                            @error('name') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                        </flux:field>

                        <flux:field>
                            <flux:label>Email</flux:label>
                            <flux:input type="email" wire:model="email" placeholder="usuario@ejemplo.com" />
                            @error('email') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Nombre completo</flux:label>
                        <flux:input wire:model="fullName" placeholder="Nombre completo del usuario" />
                        @error('fullName') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                    </flux:field>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Empresa</flux:label>
                            <flux:select wire:model.live="companyId" placeholder="Seleccionar empresa">
                                <flux:select.option value="">Sin empresa</flux:select.option>
                                @foreach($this->companies as $company)
                                    <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('companyId') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                        </flux:field>

                        <flux:field>
                            <flux:label>Sucursal</flux:label>
                            <flux:select wire:model.live="branchId" placeholder="Seleccionar sucursal" :disabled="!$companyId">
                                <flux:select.option value="">Sin sucursal</flux:select.option>
                                @foreach($this->branches as $branch)
                                    <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('branchId') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                        </flux:field>
                    </div>
                </div>
            </flux:card>

            <!-- Contact Information -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Información de Contacto</flux:heading>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Teléfono principal</flux:label>
                            <flux:input wire:model="primaryPhone" placeholder="+1234567890" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Teléfono secundario</flux:label>
                            <flux:input wire:model="secondaryPhone" placeholder="+1234567890" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Contacto de emergencia</flux:label>
                            <flux:input wire:model="emergencyContact" placeholder="Nombre del contacto" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Teléfono de emergencia</flux:label>
                            <flux:input wire:model="emergencyPhone" placeholder="+1234567890" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Dirección</flux:label>
                        <flux:input wire:model="address" placeholder="Dirección completa" />
                    </flux:field>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Country (Fixed) -->
                        <flux:field>
                            <flux:label>País</flux:label>
                            <flux:input value="El Salvador" disabled />
                        </flux:field>

                        <!-- Department -->
                        <flux:field>
                            <flux:label>Departamento</flux:label>
                            <flux:select wire:model.live="state" placeholder="Selecciona un departamento">
                                @foreach($this->departments as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <!-- Municipality -->
                        <flux:field>
                            <flux:label>Municipio</flux:label>
                            <flux:select wire:model="city" placeholder="{{ $state ? 'Selecciona un municipio' : 'Primero selecciona un departamento' }}" :disabled="!$state">
                                @foreach($this->municipalities as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <!-- Postal Code -->
                        <flux:field>
                            <flux:label>Código postal</flux:label>
                            <flux:input wire:model="postalCode" placeholder="Ej: 01101" />
                        </flux:field>
                    </div>
                </div>
            </flux:card>

            <!-- Password Configuration -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Configuración de Contraseña</flux:heading>

                <div class="space-y-4">
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2">
                            <flux:checkbox wire:model.live="generateRandomPassword" />
                            <flux:text class="text-sm">Generar contraseña automáticamente</flux:text>
                        </label>
                        @if($generateRandomPassword)
                            <flux:button size="sm" variant="outline" icon="arrow-path" wire:click="generateNewPassword">
                                Nueva contraseña
                            </flux:button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Contraseña</flux:label>
                            <flux:input
                                type="password"
                                wire:model="password"
                                :disabled="$generateRandomPassword"
                                placeholder="Mínimo 8 caracteres"
                            />
                            @error('password') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                        </flux:field>

                        <flux:field>
                            <flux:label>Confirmar contraseña</flux:label>
                            <flux:input
                                type="password"
                                wire:model="password_confirmation"
                                :disabled="$generateRandomPassword"
                                placeholder="Confirmar contraseña"
                            />
                        </flux:field>
                    </div>

                    @if($generateRandomPassword && $password)
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <flux:text class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                Contraseña generada: <code class="px-2 py-1 bg-white dark:bg-zinc-800 rounded">{{ $password }}</code>
                            </flux:text>
                            <flux:text class="text-xs text-yellow-700 dark:text-yellow-300 block mt-1">
                                Asegúrese de proporcionar esta contraseña al usuario de forma segura
                            </flux:text>
                        </div>
                    @endif

                    <div class="space-y-2">
                        <label class="flex items-center gap-2">
                            <flux:checkbox wire:model="requirePasswordChange" />
                            <flux:text class="text-sm">Requerir cambio de contraseña en el primer inicio de sesión</flux:text>
                        </label>

                        <label class="flex items-center gap-2">
                            <flux:checkbox wire:model="sendWelcomeEmail" />
                            <flux:text class="text-sm">Enviar email de bienvenida con credenciales</flux:text>
                        </label>
                    </div>
                </div>
            </flux:card>

            <!-- Additional Notes -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Notas Adicionales</flux:heading>

                <flux:field>
                    <flux:label>Notas internas</flux:label>
                    <flux:textarea
                        wire:model="notes"
                        rows="4"
                        placeholder="Información adicional sobre el usuario..."
                    />
                </flux:field>
            </flux:card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Account Status -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Estado de la Cuenta</flux:heading>

                <label class="flex items-center gap-2">
                    <flux:checkbox wire:model="isActive" />
                    <flux:text class="text-sm">Cuenta activa</flux:text>
                </label>

                <flux:text class="text-xs text-zinc-500 mt-2">
                    Si está marcada, el usuario podrá iniciar sesión inmediatamente
                </flux:text>
            </flux:card>

            <!-- Role Assignment -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Asignación de Roles</flux:heading>

                <div class="space-y-2 max-h-60 overflow-y-auto">
                    @foreach($this->roles as $role)
                        <label class="flex items-start gap-2 text-sm p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded">
                            <flux:checkbox
                                value="{{ $role->name }}"
                                wire:model="selectedRoles"
                                class="mt-0.5"
                            />
                            <div>
                                <flux:text class="font-medium">{{ $role->display_name ?? $role->name }}</flux:text>
                                @if($role->description)
                                    <flux:text class="text-xs text-zinc-500 block">{{ $role->description }}</flux:text>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </flux:card>

            <!-- Warehouse Access -->
            @if($this->warehouses->count() > 0)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Acceso a Almacenes</flux:heading>

                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        @foreach($this->warehouses as $warehouse)
                            <label class="flex items-start gap-2 text-sm p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded">
                                <flux:checkbox
                                    value="{{ $warehouse->id }}"
                                    wire:model="selectedWarehouses"
                                    class="mt-0.5"
                                />
                                <div>
                                    <flux:text class="font-medium">{{ $warehouse->name }}</flux:text>
                                    <flux:text class="text-xs text-zinc-500 block">
                                        {{ $warehouse->branch?->name ?? 'Sin sucursal' }}
                                    </flux:text>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </flux:card>
            @endif

            <!-- Creation Actions -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Acciones</flux:heading>

                <div class="space-y-3">
                    <flux:button
                        variant="primary"
                        class="w-full"
                        wire:click="createUser"
                        icon="user-plus"
                    >
                        Crear Usuario
                    </flux:button>

                    <flux:button
                        variant="outline"
                        class="w-full"
                        :href="route('admin.users.index')"
                        wire:navigate
                    >
                        Cancelar
                    </flux:button>
                </div>

                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-xs text-zinc-500">
                        Al crear el usuario, se generará automáticamente el perfil y se asignarán los roles seleccionados.
                    </flux:text>
                </div>
            </flux:card>
        </div>
    </div>
</div>