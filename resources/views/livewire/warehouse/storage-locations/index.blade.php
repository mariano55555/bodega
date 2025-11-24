<?php

use App\Models\StorageLocation;
use App\Models\Warehouse;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $warehouseFilter = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public bool $showFilters = false;

    public int $perPage = 15;

    public ?int $selectedLocationId = null;

    public ?string $selectedLocationName = null;

    public ?bool $selectedLocationIsActive = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function with(): array
    {
        $query = StorageLocation::query()
            ->with(['warehouse', 'parentLocation', 'capacityUnit'])
            ->when(! auth()->user()->isSuperAdmin(), function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->warehouseFilter, fn ($q) => $q->where('warehouse_id', $this->warehouseFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter, function ($q) {
                if ($this->statusFilter === 'active') {
                    $q->where('is_active', true);
                } else {
                    $q->where('is_active', false);
                }
            })
            ->orderBy('warehouse_id')
            ->orderBy('sort_order')
            ->orderBy('name');

        $warehouses = Warehouse::when(! auth()->user()->isSuperAdmin(), function ($q) {
            $q->where('company_id', auth()->user()->company_id);
        })->get();

        return [
            'locations' => $query->paginate($this->perPage),
            'warehouses' => $warehouses,
        ];
    }

    public function confirmToggleStatus(int $locationId, string $name, bool $isActive): void
    {
        $this->selectedLocationId = $locationId;
        $this->selectedLocationName = $name;
        $this->selectedLocationIsActive = $isActive;
        $this->modal('toggle-status-modal')->show();
    }

    public function toggleStatus(): void
    {
        $location = StorageLocation::findOrFail($this->selectedLocationId);
        $location->update([
            'is_active' => ! $location->is_active,
            'active_at' => $location->is_active ? null : now(),
        ]);

        $this->modal('toggle-status-modal')->close();
        \Flux\Flux::toast('Estado actualizado exitosamente.', variant: 'success');
    }

    public function confirmDelete(int $locationId, string $name): void
    {
        $this->selectedLocationId = $locationId;
        $this->selectedLocationName = $name;
        $this->modal('delete-location-modal')->show();
    }

    public function delete(): void
    {
        $location = StorageLocation::findOrFail($this->selectedLocationId);
        $location->delete();

        $this->modal('delete-location-modal')->close();
        \Flux\Flux::toast('Ubicación eliminada exitosamente.', variant: 'success');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Ubicaciones de Almacenamiento</flux:heading>
            <flux:text class="mt-1">Gestión de ubicaciones físicas en bodegas</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" href="{{ route('storage-locations.create') }}" wire:navigate>
            Nueva Ubicación
        </flux:button>
    </div>

    <!-- Search and Filter Toggle -->
    <div class="flex gap-3">
        <div class="w-full md:w-96">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por código, nombre, descripción..."
                icon="magnifying-glass"
            />
        </div>
        <flux:button
            variant="ghost"
            icon="funnel"
            wire:click="toggleFilters"
            :title="$showFilters ? 'Ocultar filtros' : 'Mostrar filtros'"
        />
    </div>

    <!-- Collapsible Filters -->
    @if ($showFilters)
        <div class="flex flex-wrap gap-4" x-data x-transition>
            <flux:field class="w-full sm:w-48">
                <flux:label>Bodega</flux:label>
                <flux:select wire:model.live="warehouseFilter" placeholder="Todas las bodegas">
                    <flux:select.option value="">Todas</flux:select.option>
                    @foreach ($warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field class="w-full sm:w-48">
                <flux:label>Tipo</flux:label>
                <flux:select wire:model.live="typeFilter" placeholder="Todos los tipos">
                    <flux:select.option value="">Todos</flux:select.option>
                    <flux:select.option value="zone">Zona</flux:select.option>
                    <flux:select.option value="aisle">Pasillo</flux:select.option>
                    <flux:select.option value="shelf">Estante</flux:select.option>
                    <flux:select.option value="bin">Contenedor</flux:select.option>
                    <flux:select.option value="dock">Muelle</flux:select.option>
                    <flux:select.option value="staging">Preparación</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field class="w-full sm:w-48">
                <flux:label>Estado</flux:label>
                <flux:select wire:model.live="statusFilter" placeholder="Todos los estados">
                    <flux:select.option value="">Todos</flux:select.option>
                    <flux:select.option value="active">Activos</flux:select.option>
                    <flux:select.option value="inactive">Inactivos</flux:select.option>
                </flux:select>
            </flux:field>
        </div>
    @endif

    <!-- Stats and Per Page -->
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Mostrando {{ $locations->firstItem() ?? 0 }} - {{ $locations->lastItem() ?? 0 }} de {{ $locations->total() }} ubicaciones
        </div>
        <div class="flex items-center gap-2">
            <flux:text class="text-sm">Por página:</flux:text>
            <flux:select wire:model.live="perPage" class="w-20">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </flux:select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Código</flux:table.column>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Bodega</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Capacidad</flux:table.column>
                <flux:table.column>Ubicación Padre</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($locations as $location)
                    <flux:table.row :key="$location->id">
                        <flux:table.cell>
                            <span class="font-mono text-sm font-semibold">{{ $location->code }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div>
                                <div class="font-medium">{{ $location->name }}</div>
                                @if ($location->description)
                                    <div class="text-sm text-gray-500">{{ Str::limit($location->description, 40) }}</div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $location->warehouse->name ?? 'N/A' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $typeLabels = [
                                    'zone' => 'Zona',
                                    'aisle' => 'Pasillo',
                                    'shelf' => 'Estante',
                                    'bin' => 'Contenedor',
                                    'dock' => 'Muelle',
                                    'staging' => 'Preparación',
                                ];
                            @endphp
                            <flux:badge size="sm" color="zinc">
                                {{ $typeLabels[$location->type] ?? $location->type }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($location->capacity)
                                {{ number_format($location->capacity) }} {{ $location->capacityUnit?->abbreviation ?? '' }}
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($location->parentLocation)
                                <span class="text-sm">{{ $location->parentLocation->name }}</span>
                            @else
                                <span class="text-gray-400">Raíz</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:badge :color="$location->is_active ? 'green' : 'red'" size="sm">
                                    {{ $location->is_active ? 'Activo' : 'Inactivo' }}
                                </flux:badge>
                                @if ($location->is_pickable)
                                    <flux:badge color="blue" size="sm">Picking</flux:badge>
                                @endif
                                @if ($location->is_receivable)
                                    <flux:badge color="purple" size="sm">Recepción</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="eye"
                                    href="{{ route('storage-locations.show', $location->slug) }}"
                                    wire:navigate
                                >
                                    Ver
                                </flux:button>

                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="pencil"
                                    href="{{ route('storage-locations.edit', $location->slug) }}"
                                    wire:navigate
                                >
                                    Editar
                                </flux:button>

                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    :icon="$location->is_active ? 'x-circle' : 'check-circle'"
                                    wire:click="confirmToggleStatus({{ $location->id }}, '{{ addslashes($location->name) }}', {{ $location->is_active ? 'true' : 'false' }})"
                                >
                                    {{ $location->is_active ? 'Desactivar' : 'Activar' }}
                                </flux:button>

                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $location->id }}, '{{ addslashes($location->name) }}')"
                                >
                                    Eliminar
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">
                            <div class="text-center py-12">
                                <flux:icon.map-pin class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-500 mb-4" />
                                <flux:heading size="lg" class="mb-2">No hay ubicaciones de almacenamiento</flux:heading>
                                <flux:text class="text-zinc-500 dark:text-zinc-400 mb-6">
                                    @if($search || $warehouseFilter || $typeFilter || $statusFilter)
                                        No se encontraron ubicaciones con los filtros aplicados.
                                    @else
                                        Aún no se han creado ubicaciones. Crea la primera para organizar tu inventario.
                                    @endif
                                </flux:text>
                                @if(!$search && !$warehouseFilter && !$typeFilter && !$statusFilter)
                                    <flux:button variant="primary" icon="plus" href="{{ route('storage-locations.create') }}" wire:navigate>
                                        Crear Primera Ubicación
                                    </flux:button>
                                @else
                                    <flux:button variant="outline" wire:click="$set('search', ''); $set('warehouseFilter', ''); $set('typeFilter', ''); $set('statusFilter', '');">
                                        Limpiar Filtros
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    @if ($locations->hasPages())
        <div class="mt-6">
            {{ $locations->links() }}
        </div>
    @endif

    <!-- Toggle Status Confirmation Modal -->
    <flux:modal name="toggle-status-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $selectedLocationIsActive ? '¿Desactivar ubicación?' : '¿Activar ubicación?' }}
                </flux:heading>
                <flux:text class="mt-2">
                    @if($selectedLocationIsActive)
                        <p>Estás a punto de desactivar la ubicación <strong>{{ $selectedLocationName }}</strong>.</p>
                        <p>La ubicación no estará disponible para operaciones de inventario.</p>
                    @else
                        <p>Estás a punto de activar la ubicación <strong>{{ $selectedLocationName }}</strong>.</p>
                        <p>La ubicación estará disponible para operaciones de inventario.</p>
                    @endif
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button wire:click="toggleStatus" :variant="$selectedLocationIsActive ? 'danger' : 'primary'">
                    {{ $selectedLocationIsActive ? 'Desactivar' : 'Activar' }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-location-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Eliminar ubicación?</flux:heading>
                <flux:text class="mt-2">
                    <p>Estás a punto de eliminar la ubicación <strong>{{ $selectedLocationName }}</strong>.</p>
                    <p>Esta acción no se puede deshacer.</p>
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button wire:click="delete" variant="danger">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
