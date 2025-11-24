<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Warehouse $warehouse;

    #[Validate('required|exists:branches,id')]
    public string $branch_id = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    #[Validate('required|string|max:50')]
    public string $code = '';

    #[Validate('nullable|string|max:255')]
    public string $address = '';

    #[Validate('nullable|string|max:100')]
    public string $city = '';

    #[Validate('nullable|string|max:100')]
    public string $state = '';

    #[Validate('nullable|string|max:100')]
    public string $country = 'El Salvador';

    #[Validate('nullable|string|max:20')]
    public string $postal_code = '';

    #[Validate('nullable|numeric|min:0')]
    public string $latitude = '';

    #[Validate('nullable|numeric|min:0')]
    public string $longitude = '';

    #[Validate('nullable|numeric|min:0')]
    public string $total_capacity = '';

    #[Validate('nullable|string|max:50')]
    public string $capacity_unit = 'm³';

    #[Validate('nullable|exists:users,id')]
    public string $manager_id = '';

    #[Validate('boolean')]
    public bool $is_active = true;

    // Operating Hours
    public string $opening_time = '08:00';

    public string $closing_time = '18:00';

    public array $operating_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public bool $is_24_hours = false;

    // Settings
    public bool $has_loading_dock = false;

    public bool $has_refrigeration = false;

    public bool $has_security_system = false;

    public bool $has_fire_system = false;

    public string $temperature_controlled = 'no';

    public string $access_type = 'ground';

    // Hierarchical selection
    public string $company_id = '';

    public function mount(Warehouse $warehouse): void
    {
        $this->authorize('update', $warehouse);

        $this->warehouse = $warehouse;

        // Fill form with current data
        $this->branch_id = (string) $warehouse->branch_id;
        $this->company_id = (string) $warehouse->branch->company_id;
        $this->name = $warehouse->name;
        $this->description = $warehouse->description ?? '';
        $this->code = $warehouse->code;
        $this->address = $warehouse->address ?? '';
        $this->city = $warehouse->city ?? '';
        $this->state = $warehouse->state ?? '';
        $this->country = $warehouse->country ?: 'El Salvador';
        $this->postal_code = $warehouse->postal_code ?? '';
        $this->latitude = $warehouse->latitude ? (string) $warehouse->latitude : '';
        $this->longitude = $warehouse->longitude ? (string) $warehouse->longitude : '';
        $this->total_capacity = $warehouse->total_capacity ? (string) $warehouse->total_capacity : '';
        $this->capacity_unit = $warehouse->capacity_unit ?? 'm³';
        $this->manager_id = $warehouse->manager_id ? (string) $warehouse->manager_id : '';
        $this->is_active = $warehouse->is_active;

        // Fill operating hours
        $operatingHours = $warehouse->operating_hours ?? [];
        $this->opening_time = $operatingHours['opening_time'] ?? '08:00';
        $this->closing_time = $operatingHours['closing_time'] ?? '18:00';
        $this->operating_days = $operatingHours['operating_days'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $this->is_24_hours = $operatingHours['is_24_hours'] ?? false;

        // Fill settings
        $settings = $warehouse->settings ?? [];
        $facilities = $settings['facilities'] ?? [];
        $this->has_loading_dock = $facilities['has_loading_dock'] ?? false;
        $this->has_refrigeration = $facilities['has_refrigeration'] ?? false;
        $this->has_security_system = $facilities['has_security_system'] ?? false;
        $this->has_fire_system = $facilities['has_fire_system'] ?? false;
        $this->temperature_controlled = $settings['temperature_controlled'] ?? 'no';
        $this->access_type = $settings['access_type'] ?? 'ground';
    }

    #[Computed]
    public function companies()
    {
        return Company::active()->orderBy('name')->get();
    }

    #[Computed]
    public function branches()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return Branch::where('company_id', $this->company_id)
            ->active()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function managers()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return User::where('company_id', $this->company_id)
            ->active()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function capacityUnits()
    {
        return [
            'm³' => 'Metros cúbicos (m³)',
            'm²' => 'Metros cuadrados (m²)',
            'pallets' => 'Pallets',
            'units' => 'Unidades',
            'kg' => 'Kilogramos (kg)',
            'tons' => 'Toneladas',
        ];
    }

    #[Computed]
    public function temperatureOptions()
    {
        return [
            'no' => 'No controlada',
            'controlled' => 'Temperatura controlada',
            'refrigerated' => 'Refrigerado (0-10°C)',
            'frozen' => 'Congelado (-18°C o menos)',
        ];
    }

    #[Computed]
    public function accessTypes()
    {
        return [
            'ground' => 'Acceso a nivel de suelo',
            'dock' => 'Muelle de carga',
            'ramp' => 'Rampa de acceso',
            'crane' => 'Acceso con grúa',
        ];
    }

    #[Computed]
    public function daysOfWeek()
    {
        return [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miércoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
        ];
    }

    public function updatedCompanyId(): void
    {
        $this->branch_id = '';
        $this->manager_id = '';
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
            'Ahuachapán' => [
                'Ahuachapán', 'Apaneca', 'Atiquizaya', 'Concepción de Ataco', 'El Refugio',
                'Guaymango', 'Jujutla', 'San Francisco Menéndez', 'San Lorenzo', 'San Pedro Puxtla',
                'Tacuba', 'Turín',
            ],
            'Cabañas' => [
                'Cinquera', 'Dolores', 'Guacotecti', 'Ilobasco', 'Jutiapa', 'San Isidro',
                'Sensuntepeque', 'Tejutepeque', 'Victoria',
            ],
            'Chalatenango' => [
                'Agua Caliente', 'Arcatao', 'Azacualpa', 'Chalatenango', 'Citalá', 'Comalapa',
                'Concepción Quezaltepeque', 'Dulce Nombre de María', 'El Carrizal', 'El Paraíso',
                'La Laguna', 'La Palma', 'La Reina', 'Las Vueltas', 'Nombre de Jesús', 'Nueva Concepción',
                'Nueva Trinidad', 'Ojos de Agua', 'Potonico', 'San Antonio de la Cruz',
                'San Antonio Los Ranchos', 'San Fernando', 'San Francisco Lempa', 'San Francisco Morazán',
                'San Ignacio', 'San Isidro Labrador', 'San José Cancasque', 'San José Las Flores',
                'San Luis del Carmen', 'San Miguel de Mercedes', 'San Rafael', 'Santa Rita', 'Tejutla',
            ],
            'Cuscatlán' => [
                'Candelaria', 'Cojutepeque', 'El Carmen', 'El Rosario', 'Monte San Juan',
                'Oratorio de Concepción', 'San Bartolomé Perulapía', 'San Cristóbal', 'San José Guayabal',
                'San Pedro Perulapán', 'San Rafael Cedros', 'San Ramón', 'Santa Cruz Analquito',
                'Santa Cruz Michapa', 'Suchitoto', 'Tenancingo',
            ],
            'La Libertad' => [
                'Antiguo Cuscatlán', 'Ciudad Arce', 'Colón', 'Comasagua', 'Chiltiupán', 'Huizúcar',
                'Jayaque', 'Jicalapa', 'La Libertad', 'Santa Tecla', 'Nuevo Cuscatlán', 'San Juan Opico',
                'Quezaltepeque', 'Sacacoyo', 'San José Villanueva', 'San Matías', 'San Pablo Tacachico',
                'Talnique', 'Tamanique', 'Teotepeque', 'Tepecoyo', 'Zaragoza',
            ],
            'La Paz' => [
                'Cuyultitán', 'El Rosario', 'Jerusalén', 'Mercedes La Ceiba', 'Olocuilta', 'Paraíso de Osorio',
                'San Antonio Masahuat', 'San Emigdio', 'San Francisco Chinameca', 'San Juan Nonualco',
                'San Juan Talpa', 'San Juan Tepezontes', 'San Luis La Herradura', 'San Luis Talpa',
                'San Miguel Tepezontes', 'San Pedro Masahuat', 'San Pedro Nonualco', 'San Rafael Obrajuelo',
                'Santa María Ostuma', 'Santiago Nonualco', 'Tapalhuaca', 'Zacatecoluca',
            ],
            'La Unión' => [
                'Anamorós', 'Bolívar', 'Concepción de Oriente', 'Conchagua', 'El Carmen', 'El Sauce',
                'Intipucá', 'La Unión', 'Lislique', 'Meanguera del Golfo', 'Nueva Esparta', 'Pasaquina',
                'Polorós', 'San Alejo', 'San José', 'Santa Rosa de Lima', 'Yayantique', 'Yucuaiquín',
            ],
            'Morazán' => [
                'Arambala', 'Cacaopera', 'Chilanga', 'Corinto', 'Delicias de Concepción', 'El Divisadero',
                'El Rosario', 'Gualococti', 'Guatajiagua', 'Joateca', 'Jocoaitique', 'Jocoro', 'Lolotiquillo',
                'Meanguera', 'Osicala', 'Perquín', 'San Carlos', 'San Fernando', 'San Francisco Gotera',
                'San Isidro', 'San Simón', 'Sensembra', 'Sociedad', 'Torola', 'Yamabal', 'Yoloaiquín',
            ],
            'San Miguel' => [
                'Carolina', 'Chapeltique', 'Chinameca', 'Chirilagua', 'Ciudad Barrios', 'Comacarán',
                'El Tránsito', 'Lolotique', 'Moncagua', 'Nueva Guadalupe', 'Nuevo Edén de San Juan',
                'Quelepa', 'San Antonio', 'San Gerardo', 'San Jorge', 'San Luis de la Reina', 'San Miguel',
                'San Rafael Oriente', 'Sesori', 'Uluazapa',
            ],
            'San Salvador' => [
                'Aguilares', 'Apopa', 'Ayutuxtepeque', 'Cuscatancingo', 'Ciudad Delgado', 'El Paisnal',
                'Guazapa', 'Ilopango', 'Mejicanos', 'Nejapa', 'Panchimalco', 'Rosario de Mora',
                'San Marcos', 'San Martín', 'San Salvador', 'Santiago Texacuangos', 'Santo Tomás',
                'Soyapango', 'Tonacatepeque',
            ],
            'San Vicente' => [
                'Apastepeque', 'Guadalupe', 'San Cayetano Istepeque', 'San Esteban Catarina', 'San Ildefonso',
                'San Lorenzo', 'San Sebastián', 'San Vicente', 'Santa Clara', 'Santo Domingo',
                'Tecoluca', 'Tepetitán', 'Verapaz',
            ],
            'Santa Ana' => [
                'Candelaria de la Frontera', 'Chalchuapa', 'Coatepeque', 'El Congo', 'El Porvenir',
                'Masahuat', 'Metapán', 'San Antonio Pajonal', 'San Sebastián Salitrillo', 'Santa Ana',
                'Santa Rosa Guachipilín', 'Santiago de la Frontera', 'Texistepeque',
            ],
            'Sonsonate' => [
                'Acajutla', 'Armenia', 'Caluco', 'Cuisnahuat', 'Izalco', 'Juayúa', 'Nahuizalco',
                'Nahulingo', 'Salcoatitán', 'San Antonio del Monte', 'San Julián', 'Santa Catarina Masahuat',
                'Santa Isabel Ishuatán', 'Santo Domingo de Guzmán', 'Sonsonate', 'Sonzacate',
            ],
            'Usulután' => [
                'Alegría', 'Berlín', 'California', 'Concepción Batres', 'El Triunfo', 'Ereguayquín',
                'Estanzuelas', 'Jiquilisco', 'Jucuapa', 'Jucuarán', 'Mercedes Umaña', 'Nueva Granada',
                'Ozatlán', 'Puerto El Triunfo', 'San Agustín', 'San Buenaventura', 'San Dionisio',
                'San Francisco Javier', 'Santa Elena', 'Santa María', 'Santiago de María',
                'Tecapán', 'Usulután',
            ],
        ];

        if (! $this->state || ! isset($municipalitiesByDepartment[$this->state])) {
            return [];
        }

        return array_combine(
            $municipalitiesByDepartment[$this->state],
            $municipalitiesByDepartment[$this->state]
        );
    }

    public function save(): void
    {
        $this->authorize('update', $this->warehouse);

        $rules = $this->rules();

        // Adjust unique validation for code to exclude current warehouse
        $rules['code'] = 'required|string|max:50|unique:warehouses,code,'.$this->warehouse->id;

        $validated = $this->validate($rules);

        // Prepare operating hours
        $operatingHours = [
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'operating_days' => $this->operating_days,
            'is_24_hours' => $this->is_24_hours,
        ];

        // Prepare settings
        $settings = [
            'facilities' => [
                'has_loading_dock' => $this->has_loading_dock,
                'has_refrigeration' => $this->has_refrigeration,
                'has_security_system' => $this->has_security_system,
                'has_fire_system' => $this->has_fire_system,
            ],
            'temperature_controlled' => $this->temperature_controlled,
            'access_type' => $this->access_type,
        ];

        $validated['operating_hours'] = $operatingHours;
        $validated['settings'] = $settings;
        $validated['updated_by'] = auth()->id();

        // Convert empty strings to null for numeric fields
        if ($validated['latitude'] === '') {
            $validated['latitude'] = null;
        }
        if ($validated['longitude'] === '') {
            $validated['longitude'] = null;
        }
        if ($validated['total_capacity'] === '') {
            $validated['total_capacity'] = null;
        }

        $this->warehouse->update($validated);

        $this->dispatch('warehouse-updated', [
            'message' => 'Almacén actualizado exitosamente',
            'warehouse' => $this->warehouse->name,
        ]);

        $this->redirect(route('warehouse.warehouses.index'), navigate: true);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->warehouse);

        $warehouseName = $this->warehouse->name;
        $this->warehouse->delete();

        $this->dispatch('warehouse-deleted', [
            'message' => "Almacén '{$warehouseName}' eliminado exitosamente",
        ]);

        $this->redirect(route('warehouse.warehouses.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => 'Editar Almacén - '.$this->warehouse->name,
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('warehouse.warehouses.index')" wire:navigate>
                Volver
            </flux:button>
            <div class="flex-1">
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Editar Almacén
                </flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Actualiza la información de {{ $warehouse->name }}
                </flux:text>
            </div>

            <!-- Warehouse Status Badge -->
            <flux:badge :color="$warehouse->is_active ? 'green' : 'red'" size="lg">
                {{ $warehouse->is_active ? 'Activo' : 'Inactivo' }}
            </flux:badge>
        </div>

        <!-- Warehouse Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="building-office" class="h-8 w-8 text-blue-500" />
                    <div>
                        <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                            {{ $warehouse->branch->company->name }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Empresa</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="building-storefront" class="h-8 w-8 text-green-500" />
                    <div>
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                            {{ $warehouse->branch->name }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Sucursal</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="scale" class="h-8 w-8 text-purple-500" />
                    <div>
                        <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">
                            {{ $warehouse->total_capacity ? number_format($warehouse->total_capacity) : 'N/A' }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Capacidad</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="calendar" class="h-8 w-8 text-orange-500" />
                    <div>
                        <flux:heading size="lg" class="text-orange-600 dark:text-orange-400">
                            {{ $warehouse->created_at->format('M Y') }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Creado</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <!-- Hierarchical Selection -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Ubicación Organizacional</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Empresa y sucursal donde se ubica el almacén
                </flux:text>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Company Selection -->
                <flux:field>
                    <flux:label>Empresa *</flux:label>
                    <flux:select wire:model.live="company_id" placeholder="Selecciona una empresa">
                        @foreach($this->companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="company_id" />
                </flux:field>

                <!-- Branch Selection -->
                <flux:field>
                    <flux:label>Sucursal *</flux:label>
                    <flux:select wire:model.live="branch_id" placeholder="Selecciona una sucursal" :description="!$company_id ? 'Primero selecciona una empresa' : 'Sucursal a la que pertenece el almacén'">
                        @foreach($this->branches as $branch)
                            <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="branch_id" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Basic Information -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Información Básica</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Datos principales del almacén
                </flux:text>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Warehouse Name -->
                <flux:field>
                    <flux:label>Nombre del Almacén *</flux:label>
                    <flux:input wire:model="name" placeholder="Ej: Almacén Principal" />
                    <flux:error name="name" />
                </flux:field>

                <!-- Warehouse Code -->
                <flux:field>
                    <flux:label>Código *</flux:label>
                    <flux:input wire:model="code" placeholder="Ej: ALM001" description="Código único para identificar el almacén" />
                    <flux:error name="code" />
                </flux:field>

                <!-- Manager -->
                <flux:field>
                    <flux:label>Gerente</flux:label>
                    <flux:select wire:model="manager_id" placeholder="Selecciona un gerente" :description="!$company_id ? 'Primero selecciona una empresa' : 'Gerente responsable de este almacén'">
                        <flux:select.option value="">Sin gerente asignado</flux:select.option>
                        @foreach($this->managers as $manager)
                            <flux:select.option value="{{ $manager->id }}">{{ $manager->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="manager_id" />
                </flux:field>

                <!-- Access Type -->
                <flux:field>
                    <flux:label>Tipo de Acceso</flux:label>
                    <flux:select wire:model="access_type" placeholder="Tipo de acceso al almacén">
                        @foreach($this->accessTypes as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="access_type" />
                </flux:field>

                <!-- Description -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>Descripción</flux:label>
                        <flux:textarea wire:model="description" rows="3" placeholder="Describe las características principales de este almacén..." />
                        <flux:error name="description" />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <!-- Capacity Management -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Gestión de Capacidad</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Configuración de la capacidad de almacenamiento
                </flux:text>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Total Capacity -->
                <flux:field>
                    <flux:label>Capacidad Total</flux:label>
                    <flux:input type="number" step="0.01" wire:model="total_capacity" placeholder="1000" description="Capacidad máxima de almacenamiento" />
                    <flux:error name="total_capacity" />
                </flux:field>

                <!-- Capacity Unit -->
                <flux:field>
                    <flux:label>Unidad de Capacidad</flux:label>
                    <flux:select wire:model="capacity_unit" placeholder="Selecciona una unidad">
                        @foreach($this->capacityUnits as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="capacity_unit" />
                </flux:field>

                <!-- Temperature Control -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>Control de Temperatura</flux:label>
                        <flux:select wire:model="temperature_controlled" placeholder="Tipo de control de temperatura">
                            @foreach($this->temperatureOptions as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="temperature_controlled" />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <!-- Location Information -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Información de Ubicación</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Dirección física y coordenadas del almacén
                </flux:text>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Address -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>Dirección</flux:label>
                        <flux:input wire:model="address" placeholder="Ej: Calle Principal #123, Colonia Centro" />
                        <flux:error name="address" />
                    </flux:field>
                </div>

                <!-- Country (Fixed) -->
                <flux:field>
                    <flux:label>País</flux:label>
                    <flux:input value="El Salvador" disabled />
                    <input type="hidden" wire:model="country" />
                </flux:field>

                <!-- Department -->
                <flux:field>
                    <flux:label>Departamento *</flux:label>
                    <flux:select wire:model.live="state" placeholder="Selecciona un departamento">
                        @foreach($this->departments as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="state" />
                </flux:field>

                <!-- Municipality -->
                <flux:field>
                    <flux:label>Municipio *</flux:label>
                    <flux:select wire:model="city" placeholder="{{ $state ? 'Selecciona un municipio' : 'Primero selecciona un departamento' }}" :disabled="!$state">
                        @foreach($this->municipalities as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="city" />
                </flux:field>

                <!-- Postal Code -->
                <flux:field>
                    <flux:label>Código Postal</flux:label>
                    <flux:input wire:model="postal_code" placeholder="Ej: 01101" />
                    <flux:error name="postal_code" />
                </flux:field>

                <!-- GPS Coordinates -->
                <flux:field>
                    <flux:label>Latitud</flux:label>
                    <flux:input type="number" step="any" wire:model="latitude" placeholder="13.6929" description="Coordenada GPS (opcional)" />
                    <flux:error name="latitude" />
                </flux:field>

                <flux:field>
                    <flux:label>Longitud</flux:label>
                    <flux:input type="number" step="any" wire:model="longitude" placeholder="-89.2182" description="Coordenada GPS (opcional)" />
                    <flux:error name="longitude" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Operating Hours -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Horarios de Operación</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Configura los horarios de funcionamiento del almacén
                </flux:text>
            </div>

            <div class="space-y-6">
                <!-- 24 Hours Option -->
                <flux:checkbox wire:model.live="is_24_hours" label="Opera las 24 horas" description="Marca esta opción si el almacén funciona todo el día" />

                @if(!$is_24_hours)
                    <!-- Operating Hours -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <flux:field>
                            <flux:label>Hora de Apertura</flux:label>
                            <flux:input type="time" wire:model="opening_time" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Hora de Cierre</flux:label>
                            <flux:input type="time" wire:model="closing_time" />
                        </flux:field>
                    </div>
                @endif

                <!-- Operating Days -->
                <flux:field>
                    <flux:label>Días de Operación</flux:label>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-2">
                        @foreach($this->daysOfWeek as $day => $label)
                            <flux:checkbox wire:model="operating_days" value="{{ $day }}" :label="$label" />
                        @endforeach
                    </div>
                    <flux:text class="text-sm text-zinc-500 mt-2">Selecciona los días en que opera el almacén</flux:text>
                </flux:field>
            </div>
        </flux:card>

        <!-- Facilities & Features -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Instalaciones y Características</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Servicios y características especiales del almacén
                </flux:text>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <flux:checkbox wire:model="has_loading_dock" label="Muelle de carga" description="Cuenta con muelle para carga y descarga" />

                <flux:checkbox wire:model="has_refrigeration" label="Sistema de refrigeración" description="Tiene equipos de refrigeración" />

                <flux:checkbox wire:model="has_security_system" label="Sistema de seguridad" description="Cuenta con cámaras y vigilancia" />

                <flux:checkbox wire:model="has_fire_system" label="Sistema contra incendios" description="Tiene sistema de detección y extinción" />
            </div>
        </flux:card>

        <!-- Status -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Estado</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Configuración del estado del almacén
                </flux:text>
            </div>

            <flux:checkbox wire:model="is_active" label="Almacén activo" description="Determina si el almacén está disponible para operaciones" />
            <flux:error name="is_active" />
        </flux:card>

        <!-- Form Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-4">
                <flux:button variant="ghost" :href="route('warehouse.warehouses.index')" wire:navigate>
                    Cancelar
                </flux:button>
                @can('delete', $warehouse)
                    <flux:button
                        variant="danger"
                        icon="trash"
                        wire:click="delete"
                        wire:confirm="¿Estás seguro de eliminar este almacén? Esta acción no se puede deshacer."
                    >
                        Eliminar Almacén
                    </flux:button>
                @endcan
            </div>
            <flux:button type="submit" variant="primary" icon="check">
                Actualizar Almacén
            </flux:button>
        </div>
    </form>
</div>
