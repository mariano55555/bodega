<?php

use App\Models\Company;
use App\Models\StorageLocation;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public $company_id = '';

    public $code = '';

    public $name = '';

    public $description = '';

    public $warehouse_id = '';

    public $parent_location_id = '';

    public $type = 'bin';

    public $capacity = '';

    public $capacity_unit_id = '';

    public $weight_limit = '';

    public $section = '';

    public $aisle = '';

    public $shelf = '';

    public $bin = '';

    public $barcode = '';

    public $is_pickable = true;

    public $is_receivable = true;

    public function mount(): void
    {
        // Set company_id for non-super-admin users
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }
    }

    public function save(): void
    {
        $rules = [
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'warehouse_id' => 'required|exists:warehouses,id',
            'parent_location_id' => 'nullable|exists:storage_locations,id',
            'type' => 'required|in:aisle,shelf,bin,zone,dock,staging',
            'capacity' => 'nullable|numeric|min:0',
            'capacity_unit_id' => 'nullable|exists:units_of_measure,id',
            'weight_limit' => 'nullable|numeric|min:0',
            'section' => 'nullable|string|max:255',
            'aisle' => 'nullable|string|max:255',
            'shelf' => 'nullable|string|max:255',
            'bin' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'is_pickable' => 'boolean',
            'is_receivable' => 'boolean',
        ];

        // Super admin must select a company
        if (auth()->user()->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $this->validate($rules);

        $companyId = auth()->user()->isSuperAdmin()
            ? $this->company_id
            : auth()->user()->company_id;

        // Get warehouse to get branch_id
        $warehouse = Warehouse::find($this->warehouse_id);

        // Calculate level based on parent
        $level = 0;
        if ($this->parent_location_id) {
            $parent = StorageLocation::find($this->parent_location_id);
            $level = $parent ? $parent->level + 1 : 0;
        }

        $location = StorageLocation::create([
            'company_id' => $companyId,
            'branch_id' => $warehouse?->branch_id,
            'warehouse_id' => $this->warehouse_id,
            'parent_location_id' => $this->parent_location_id ?: null,
            'level' => $level,
            'code' => $this->code,
            'name' => $this->name,
            'slug' => \Str::slug($this->code),
            'description' => $this->description ?: null,
            'type' => $this->type,
            'section' => $this->section ?: null,
            'aisle' => $this->aisle ?: null,
            'shelf' => $this->shelf ?: null,
            'bin' => $this->bin ?: null,
            'capacity' => $this->capacity ?: null,
            'capacity_unit_id' => $this->capacity_unit_id ?: null,
            'weight_limit' => $this->weight_limit ?: null,
            'barcode' => $this->barcode ?: null,
            'is_pickable' => $this->is_pickable,
            'is_receivable' => $this->is_receivable,
            'is_active' => true,
            'active_at' => now(),
            'created_by' => auth()->id(),
        ]);

        session()->flash('success', 'Ubicación creada exitosamente.');
        $this->redirect(route('storage-locations.show', $location->slug), navigate: true);
    }

    public function with(): array
    {
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        // Get the effective company_id based on user type
        $effectiveCompanyId = $isSuperAdmin
            ? $this->company_id
            : auth()->user()->company_id;

        $companies = $isSuperAdmin
            ? Company::where('is_active', true)->orderBy('name')->get()
            : collect();

        $warehouses = $effectiveCompanyId
            ? Warehouse::where('company_id', $effectiveCompanyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
            : collect();

        $parentLocations = $effectiveCompanyId
            ? StorageLocation::where('company_id', $effectiveCompanyId)
                ->when($this->warehouse_id, fn ($q) => $q->where('warehouse_id', $this->warehouse_id))
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
            : collect();

        // Get capacity units (volume, area, quantity types)
        $capacityUnits = UnitOfMeasure::whereIn('type', ['volume', 'area', 'quantity'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'isSuperAdmin' => $isSuperAdmin,
            'companies' => $companies,
            'warehouses' => $warehouses,
            'parentLocations' => $parentLocations,
            'capacityUnits' => $capacityUnits,
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nueva Ubicación de Almacenamiento</flux:heading>
            <flux:text class="mt-1">Crear una nueva ubicación en la bodega</flux:text>
        </div>

        <flux:button variant="ghost" href="{{ route('storage-locations.index') }}" wire:navigate>
            Cancelar
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Información Básica -->
        <flux:card>
            <div class="mb-6">
                <div>
                    <flux:heading size="lg">Información Básica</flux:heading>
                    <flux:badge color="red" size="sm" class="mt-1">Requerido</flux:badge>
                </div>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Datos principales de la ubicación de almacenamiento
                </flux:text>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if ($isSuperAdmin)
                    <flux:field class="md:col-span-2">
                        <flux:label>Empresa *</flux:label>
                        <flux:select wire:model.live="company_id" required>
                            <option value="">Seleccione empresa</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('company_id') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Código *</flux:label>
                    <flux:input wire:model="code" placeholder="Ej: A-01-01" required />
                    @error('code') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                    <flux:text size="sm" class="text-gray-500 mt-1">
                        Código único para identificar la ubicación
                    </flux:text>
                </flux:field>

                <flux:field>
                    <flux:label>Nombre *</flux:label>
                    <flux:input wire:model="name" placeholder="Ej: Pasillo A, Estante 1, Nivel 1" required />
                    @error('name') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Descripción</flux:label>
                    <flux:textarea wire:model="description" rows="2" placeholder="Descripción opcional..." />
                    @error('description') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Bodega *</flux:label>
                    <flux:select wire:model.live="warehouse_id" required>
                        <option value="">Seleccione bodega</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('warehouse_id') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de Ubicación *</flux:label>
                    <flux:select wire:model="type" required>
                        <flux:select.option value="zone">Zona</flux:select.option>
                        <flux:select.option value="aisle">Pasillo</flux:select.option>
                        <flux:select.option value="shelf">Estante</flux:select.option>
                        <flux:select.option value="bin">Contenedor</flux:select.option>
                        <flux:select.option value="dock">Muelle</flux:select.option>
                        <flux:select.option value="staging">Área de Preparación</flux:select.option>
                    </flux:select>
                    @error('type') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Ubicación Padre</flux:label>
                    <flux:select wire:model="parent_location_id">
                        <flux:select.option value="">Sin ubicación padre (nivel raíz)</flux:select.option>
                        @foreach ($parentLocations as $location)
                            <flux:select.option value="{{ $location->id }}">{{ $location->code }} - {{ $location->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('parent_location_id') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                    <flux:text size="sm" class="text-gray-500 mt-1">
                        Selecciona una ubicación padre para crear una jerarquía (ej: Zona A > Pasillo 1 > Estante 1)
                    </flux:text>
                </flux:field>

                <flux:field>
                    <flux:label>Código de Barras</flux:label>
                    <flux:input wire:model="barcode" placeholder="Ej: LOC-001-A1" />
                    @error('barcode') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Capacidad y Límites -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Capacidad y Límites</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Define la capacidad de almacenamiento y restricciones de peso
                </flux:text>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Capacidad</flux:label>
                    <flux:input type="number" step="0.01" wire:model="capacity" placeholder="0.00" />
                    @error('capacity') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Unidad de Capacidad</flux:label>
                    <flux:select wire:model="capacity_unit_id">
                        <flux:select.option value="">Seleccione unidad</flux:select.option>
                        @foreach ($capacityUnits as $unit)
                            <flux:select.option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('capacity_unit_id') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Peso Máximo (kg)</flux:label>
                    <flux:input type="number" step="0.01" wire:model="weight_limit" placeholder="0.00" />
                    @error('weight_limit') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                    <flux:text size="sm" class="text-gray-500 mt-1">Límite de peso en kilogramos</flux:text>
                </flux:field>
            </div>
        </flux:card>

        <!-- Ubicación Física -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Ubicación Física</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Detalles de la ubicación física dentro del almacén (opcional)
                </flux:text>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:field>
                    <flux:label>Sección</flux:label>
                    <flux:input wire:model="section" placeholder="Ej: A" />
                    @error('section') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Pasillo</flux:label>
                    <flux:input wire:model="aisle" placeholder="Ej: 01" />
                    @error('aisle') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Estante</flux:label>
                    <flux:input wire:model="shelf" placeholder="Ej: 03" />
                    @error('shelf') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Contenedor</flux:label>
                    <flux:input wire:model="bin" placeholder="Ej: B" />
                    @error('bin') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Configuración -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">Configuración</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Opciones funcionales de la ubicación
                </flux:text>
            </div>

            <div class="space-y-3">
                <flux:checkbox wire:model="is_pickable" label="Ubicación de Picking" description="Permitir preparar pedidos desde esta ubicación" />
                <flux:checkbox wire:model="is_receivable" label="Ubicación de Recepción" description="Permitir recibir productos en esta ubicación" />
            </div>
        </flux:card>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('storage-locations.index') }}" wire:navigate>
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary">
                Crear Ubicación
            </flux:button>
        </div>
    </form>
</div>
