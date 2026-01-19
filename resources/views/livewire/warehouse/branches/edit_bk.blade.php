<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Branch $branch;

    #[Validate('required|exists:companies,id')]
    public $company_id = '';

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('nullable|string|max:500')]
    public $description = '';

    #[Validate('required|string|max:50')]
    public string $code = '';

    #[Validate('nullable|string|max:255')]
    public $address = '';

    #[Validate('nullable|string|max:100')]
    public $city = '';

    #[Validate('nullable|string|max:100')]
    public $state = '';

    #[Validate('nullable|string|max:100')]
    public $country = '';

    #[Validate('nullable|string|max:20')]
    public $postal_code = '';

    #[Validate('nullable|exists:users,id')]
    public $manager_id = '';

    #[Validate('boolean')]
    public $is_active = true;

    // Settings
    public $type = '';

    public $opening_time = '08:00';

    public $closing_time = '18:00';

    public array $operating_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public bool $has_parking = false;

    public bool $has_security = false;

    public bool $is_24_hours = false;

    public function mount(Branch $branch): void
    {
        $this->authorize('update', $branch);

        $this->branch = $branch;

        // Load relationships with counts
        $this->branch->loadCount('warehouses');

        // Fill basic properties from model
        $this->fill($branch->only([
            'name', 'description', 'address', 'city',
            'state', 'country', 'postal_code', 'is_active',
        ]));

        // Set IDs as strings
        $this->company_id = $this->branch->company_id;
        $this->manager_id = $branch->manager_id ? (string) $branch->manager_id : '';

        // Extract and assign settings
        $settings = $branch->settings ?? [];
        $this->type = $settings['type'] ?? '';

        $operatingHours = $settings['operating_hours'] ?? [];
        $this->opening_time = $operatingHours['opening_time'] ?? '08:00';
        $this->closing_time = $operatingHours['closing_time'] ?? '18:00';
        $this->operating_days = $operatingHours['operating_days'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $this->is_24_hours = $operatingHours['is_24_hours'] ?? false;

        $facilities = $settings['facilities'] ?? [];
        $this->has_parking = $facilities['has_parking'] ?? false;
        $this->has_security = $facilities['has_security'] ?? false;

        $this->code = $branch->code;

    }

    #[Computed]
    public function companies()
    {
        return Company::active()->orderBy('name')->get();
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
    public function branchTypes()
    {
        return [
            'warehouse' => 'Almacén',
            'retail' => 'Tienda',
            'office' => 'Oficina',
            'distribution' => 'Centro de Distribución',
            'manufacturing' => 'Manufactura',
            'service' => 'Centro de Servicio',
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
        $this->manager_id = '';
    }

    public function save(): void
    {
        $this->authorize('update', $this->branch);

        // Validate all fields with #[Validate] attributes, then add custom code validation
        $this->validate();

        // Additional validation for code with unique rule excluding current branch
        $this->validate([
            'code' => 'required|string|max:50|unique:branches,code,'.$this->branch->id,
        ]);

        try {
            // Prepare settings
            $settings = [
                'type' => $this->type,
                'operating_hours' => [
                    'opening_time' => $this->opening_time,
                    'closing_time' => $this->closing_time,
                    'operating_days' => $this->operating_days,
                    'is_24_hours' => $this->is_24_hours,
                ],
                'facilities' => [
                    'has_parking' => $this->has_parking,
                    'has_security' => $this->has_security,
                ],
            ];

            // Prepare validated data array
            $validated = [
                'company_id' => $this->company_id,
                'name' => $this->name,
                'description' => $this->description,
                'code' => $this->code,
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code,
                'manager_id' => $this->manager_id ?: null,
                'is_active' => $this->is_active,
                'settings' => $settings,
                'updated_by' => auth()->id(),
            ];

            $this->branch->update($validated);

            Flux::toast(
                text: "Sucursal '{$this->branch->name}' actualizada exitosamente",
                variant: 'success',
                duration: 3000
            );

            $this->redirect(route('warehouse.branches.index'), navigate: true);
        } catch (\Exception $e) {
            Flux::toast(
                text: 'Error al actualizar la sucursal. Por favor intente nuevamente.',
                variant: 'danger',
                duration: 5000
            );

            \Log::error('Error updating branch: '.$e->getMessage());
        }
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->branch);

        try {
            $branchName = $this->branch->name;
            $this->branch->delete();

            Flux::toast(
                text: "Sucursal '{$branchName}' eliminada exitosamente",
                variant: 'success',
                duration: 3000
            );

            $this->redirect(route('warehouse.branches.index'), navigate: true);
        } catch (\Exception $e) {
            Flux::toast(
                text: 'Error al eliminar la sucursal. Por favor intente nuevamente.',
                variant: 'danger',
                duration: 5000
            );

            \Log::error('Error deleting branch: '.$e->getMessage());
        }
    }

    public function with(): array
    {
        return [
            'title' => 'Editar Sucursal - '.$this->branch->name,
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('warehouse.branches.index')" wire:navigate>
                Volver
            </flux:button>
            <div class="flex-1">
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Editar Sucursal
                </flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Actualiza la información de {{ $branch->name }}
                </flux:text>
            </div>

            <!-- Branch Status Badge -->
            <flux:badge :color="$branch->is_active ? 'green' : 'red'" size="lg">
                {{ $branch->is_active ? 'Activa' : 'Inactiva' }}
            </flux:badge>
        </div>

        <!-- Branch Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="building-storefront" class="h-8 w-8 text-blue-500" />
                    <div>
                        <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                            {{ $this->branch->warehouses_count ?? 0 }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Almacenes</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="user" class="h-8 w-8 text-green-500" />
                    <div>
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                            {{ $branch->manager?->name ?? 'Sin asignar' }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Gerente</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="calendar" class="h-8 w-8 text-purple-500" />
                    <div>
                        <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">
                            {{ $branch->created_at->format('M Y') }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Creada</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <!-- Basic Information -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Información Básica</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Datos principales de la sucursal
                </flux:text>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Company Selection -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>
                            Empresa
                            <flux:badge size="sm" color="red" inset="top right">Requerido</flux:badge>
                        </flux:label>
                        <flux:select wire:model.live="company_id" placeholder="Selecciona una empresa">
                            @foreach($this->companies as $company)
                                <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="company_id" />
                    </flux:field>
                </div>

                <!-- Branch Name -->
                <flux:field>
                    <flux:label>
                        Nombre de la Sucursal
                        <flux:badge size="sm" color="red" inset="top right">Requerido</flux:badge>
                    </flux:label>
                    <flux:input wire:model="name" placeholder="Ej: Sucursal Centro" />
                    <flux:error name="name" />
                </flux:field>

                <!-- Branch Code -->
                <flux:field>
                    <flux:label>
                        Código {{ $this->code }}
                        <flux:badge size="sm" color="red" inset="top right">Requerido</flux:badge>
                    </flux:label>
                    {{-- <input type="text" wire:model='code' > --}}
                    <flux:input wire:model="code" placeholder="Ej: SUC001" />
                    <flux:error name="code" />
                    <flux:description>Código único para identificar la sucursal</flux:description>
                </flux:field>

                <!-- Branch Type -->
                <flux:field>
                    <flux:label>Tipo de Sucursal</flux:label>
                    <flux:select wire:model="type" placeholder="Selecciona el tipo">
                        @foreach($this->branchTypes as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="type" />
                </flux:field>

                <!-- Manager -->
                <flux:field>
                    <flux:label>Gerente</flux:label>
                    <flux:select wire:model="manager_id" placeholder="Selecciona un gerente">
                        <flux:select.option value="">Sin gerente asignado</flux:select.option>
                        @foreach($this->managers as $manager)
                            <flux:select.option value="{{ $manager->id }}">{{ $manager->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="manager_id" />
                    @if(!$company_id)
                        <flux:description>Primero selecciona una empresa</flux:description>
                    @else
                        <flux:description>Gerente responsable de esta sucursal</flux:description>
                    @endif
                </flux:field>

                <!-- Description -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>Descripción</flux:label>
                        <flux:textarea wire:model="description" rows="3" placeholder="Describe las características principales de esta sucursal..." />
                        <flux:error name="description" />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <!-- Address Information -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Información de Ubicación</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Dirección y datos de ubicación
                </flux:text>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Address -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>Dirección</flux:label>
                        <flux:input wire:model="address" placeholder="Ej: Calle 123 #45-67" />
                        <flux:error name="address" />
                    </flux:field>
                </div>

                <!-- City -->
                <flux:field>
                    <flux:label>Ciudad</flux:label>
                    <flux:input wire:model="city" placeholder="Ej: Bogotá" />
                    <flux:error name="city" />
                </flux:field>

                <!-- State -->
                <flux:field>
                    <flux:label>Departamento/Estado</flux:label>
                    <flux:input wire:model="state" placeholder="Ej: Cundinamarca" />
                    <flux:error name="state" />
                </flux:field>

                <!-- Postal Code -->
                <flux:field>
                    <flux:label>Código Postal</flux:label>
                    <flux:input wire:model="postal_code" placeholder="Ej: 110111" />
                    <flux:error name="postal_code" />
                </flux:field>

                <!-- Country -->
                <flux:field>
                    <flux:label>País</flux:label>
                    <flux:select wire:model="country" placeholder="Selecciona un país">
                        <flux:select.option value="Colombia">Colombia</flux:select.option>
                        <flux:select.option value="Venezuela">Venezuela</flux:select.option>
                        <flux:select.option value="Ecuador">Ecuador</flux:select.option>
                        <flux:select.option value="Perú">Perú</flux:select.option>
                        <flux:select.option value="Chile">Chile</flux:select.option>
                        <flux:select.option value="Argentina">Argentina</flux:select.option>
                        <flux:select.option value="México">México</flux:select.option>
                        <flux:select.option value="España">España</flux:select.option>
                        <flux:select.option value="Estados Unidos">Estados Unidos</flux:select.option>
                    </flux:select>
                    <flux:error name="country" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Operating Hours -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Horarios de Operación</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Configura los horarios de funcionamiento
                </flux:text>
            </div>

            <div class="space-y-6">
                <!-- 24 Hours Option -->
                <flux:checkbox wire:model.live="is_24_hours" label="Opera las 24 horas" description="Marca esta opción si la sucursal funciona todo el día" />

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
                    <flux:text class="text-sm text-zinc-500 mt-2">Selecciona los días en que opera la sucursal</flux:text>
                </flux:field>
            </div>
        </flux:card>

        <!-- Facilities -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Instalaciones</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Características y servicios disponibles
                </flux:text>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <flux:checkbox wire:model="has_parking" label="Cuenta con estacionamiento" description="Indica si la sucursal tiene parqueadero" />

                <flux:checkbox wire:model="has_security" label="Tiene servicio de seguridad" description="Indica si hay vigilancia o seguridad" />
            </div>
        </flux:card>

        <!-- Status -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Estado</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Configuración del estado de la sucursal
                </flux:text>
            </div>

            <flux:checkbox wire:model="is_active" label="Sucursal activa" description="Determina si la sucursal está disponible para operaciones" />
            <flux:error name="is_active" />
        </flux:card>

        <!-- Form Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-4">
                <flux:button variant="ghost" :href="route('warehouse.branches.index')" wire:navigate>
                    Cancelar
                </flux:button>
                @can('delete', $branch)
                    <flux:button
                        variant="danger"
                        icon="trash"
                        wire:click="$wire.modal('delete-branch').show()"
                    >
                        Eliminar Sucursal
                    </flux:button>
                @endcan
            </div>
            <flux:button type="submit" variant="primary" icon="check">
                Actualizar Sucursal
            </flux:button>
        </div>
    </form>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-branch" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Eliminar Sucursal</flux:heading>
                <flux:text class="mt-2">
                    <p>¿Estás seguro de eliminar la sucursal <strong>{{ $branch->name }}</strong>?</p>
                    <p class="mt-2 text-red-600 dark:text-red-400">Esta acción no se puede deshacer.</p>
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
