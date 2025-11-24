<?php

use App\Models\StorageLocation;
use App\Models\Warehouse;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public StorageLocation $location;

    public $code = '';

    public $name = '';

    public $description = '';

    public $warehouse_id = '';

    public $parent_location_id = '';

    public $type = '';

    public $capacity = '';

    public $capacity_unit = 'units';

    public $max_weight = '';

    public $weight_unit = 'kg';

    public $coordinates = '';

    public $sort_order = 0;

    public $is_pickable = false;

    public $is_receivable = false;

    public function mount(StorageLocation $location): void
    {
        $this->location = $location;
        $this->code = $location->code;
        $this->name = $location->name;
        $this->description = $location->description;
        $this->warehouse_id = $location->warehouse_id;
        $this->parent_location_id = $location->parent_location_id;
        $this->type = $location->type;
        $this->capacity = $location->capacity;
        $this->capacity_unit = $location->capacity_unit ?? 'units';
        $this->max_weight = $location->max_weight;
        $this->weight_unit = $location->weight_unit ?? 'kg';
        $this->coordinates = $location->coordinates;
        $this->sort_order = $location->sort_order ?? 0;
        $this->is_pickable = $location->is_pickable;
        $this->is_receivable = $location->is_receivable;
    }

    public function save(): void
    {
        $this->validate([
            'code' => 'required|string|max:100|unique:storage_locations,code,'.$this->location->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'warehouse_id' => 'required|exists:warehouses,id',
            'parent_location_id' => 'nullable|exists:storage_locations,id',
            'type' => 'required|in:shelf,pallet,bin,zone,floor',
            'capacity' => 'nullable|numeric|min:0',
            'capacity_unit' => 'nullable|in:units,m3,m2,pallets',
            'max_weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|in:kg,ton,lb',
            'coordinates' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_pickable' => 'boolean',
            'is_receivable' => 'boolean',
        ]);

        // Prevent self-referencing parent
        if ($this->parent_location_id == $this->location->id) {
            session()->flash('error', 'Una ubicación no puede ser su propia ubicación padre.');

            return;
        }

        $this->location->update([
            'code' => $this->code,
            'name' => $this->name,
            'slug' => \Str::slug($this->code),
            'description' => $this->description,
            'warehouse_id' => $this->warehouse_id,
            'parent_location_id' => $this->parent_location_id ?: null,
            'type' => $this->type,
            'capacity' => $this->capacity ?: null,
            'capacity_unit' => $this->capacity_unit,
            'max_weight' => $this->max_weight ?: null,
            'weight_unit' => $this->weight_unit,
            'coordinates' => $this->coordinates,
            'sort_order' => $this->sort_order ?? 0,
            'is_pickable' => $this->is_pickable,
            'is_receivable' => $this->is_receivable,
            'updated_by' => auth()->id(),
        ]);

        session()->flash('success', 'Ubicación actualizada exitosamente.');
        $this->redirect(route('storage-locations.show', $this->location->slug), navigate: true);
    }

    public function with(): array
    {
        $warehouses = Warehouse::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->get();

        $parentLocations = StorageLocation::where('company_id', auth()->user()->company_id)
            ->when($this->warehouse_id, fn ($q) => $q->where('warehouse_id', $this->warehouse_id))
            ->where('id', '!=', $this->location->id) // Exclude self
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'warehouses' => $warehouses,
            'parentLocations' => $parentLocations,
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Editar Ubicación</flux:heading>
            <flux:text class="mt-1">{{ $location->code }} - {{ $location->name }}</flux:text>
        </div>

        <flux:button variant="ghost" href="{{ route('storage-locations.show', $location->slug) }}" wire:navigate>
            Cancelar
        </flux:button>
    </div>

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle">
            {{ session('error') }}
        </flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Información Básica -->
        <flux:card>
            <flux:heading size="lg">Información Básica</flux:heading>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
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
                        <option value="">Seleccione tipo</option>
                        <option value="zone">Zona</option>
                        <option value="floor">Piso</option>
                        <option value="shelf">Estante</option>
                        <option value="pallet">Pallet</option>
                        <option value="bin">Contenedor</option>
                    </flux:select>
                    @error('type') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Ubicación Padre</flux:label>
                    <flux:select wire:model="parent_location_id">
                        <option value="">Sin ubicación padre (nivel raíz)</option>
                        @foreach ($parentLocations as $parentLocation)
                            <option value="{{ $parentLocation->id }}">{{ $parentLocation->code }} - {{ $parentLocation->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('parent_location_id') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                    <flux:text size="sm" class="text-gray-500 mt-1">
                        Selecciona una ubicación padre para crear una jerarquía (ej: Pasillo A > Estante 1 > Nivel 1)
                    </flux:text>
                </flux:field>

                <flux:field>
                    <flux:label>Coordenadas</flux:label>
                    <flux:input wire:model="coordinates" placeholder="Ej: A-1-1 o 10.5, 20.3" />
                    @error('coordinates') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                    <flux:text size="sm" class="text-gray-500 mt-1">
                        Coordenadas o referencia de ubicación física
                    </flux:text>
                </flux:field>

                <flux:field>
                    <flux:label>Orden de Clasificación</flux:label>
                    <flux:input type="number" wire:model="sort_order" placeholder="0" />
                    @error('sort_order') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                    <flux:text size="sm" class="text-gray-500 mt-1">
                        Orden para mostrar las ubicaciones (menor = primero)
                    </flux:text>
                </flux:field>
            </div>
        </flux:card>

        <!-- Capacidad y Límites -->
        <flux:card>
            <flux:heading size="lg">Capacidad y Límites</flux:heading>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Capacidad</flux:label>
                    <flux:input type="number" step="0.01" wire:model="capacity" placeholder="0.00" />
                    @error('capacity') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Unidad de Capacidad</flux:label>
                    <flux:select wire:model="capacity_unit">
                        <option value="units">Unidades</option>
                        <option value="m3">m³ (metros cúbicos)</option>
                        <option value="m2">m² (metros cuadrados)</option>
                        <option value="pallets">Pallets</option>
                    </flux:select>
                    @error('capacity_unit') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Peso Máximo</flux:label>
                    <flux:input type="number" step="0.01" wire:model="max_weight" placeholder="0.00" />
                    @error('max_weight') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Unidad de Peso</flux:label>
                    <flux:select wire:model="weight_unit">
                        <option value="kg">kg (kilogramos)</option>
                        <option value="ton">ton (toneladas)</option>
                        <option value="lb">lb (libras)</option>
                    </flux:select>
                    @error('weight_unit') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Configuración -->
        <flux:card>
            <flux:heading size="lg">Configuración</flux:heading>

            <div class="mt-4 space-y-3">
                <flux:checkbox wire:model="is_pickable">
                    <flux:label>Ubicación de Picking</flux:label>
                    <flux:text size="sm" class="text-gray-500">
                        Permitir preparar pedidos desde esta ubicación
                    </flux:text>
                </flux:checkbox>

                <flux:checkbox wire:model="is_receivable">
                    <flux:label>Ubicación de Recepción</flux:label>
                    <flux:text size="sm" class="text-gray-500">
                        Permitir recibir productos en esta ubicación
                    </flux:text>
                </flux:checkbox>
            </div>
        </flux:card>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('storage-locations.show', $location->slug) }}" wire:navigate>
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary">
                Guardar Cambios
            </flux:button>
        </div>
    </form>
</div>
