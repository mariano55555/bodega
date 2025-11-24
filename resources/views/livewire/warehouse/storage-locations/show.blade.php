<?php

use App\Models\StorageLocation;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public StorageLocation $location;

    public function mount(StorageLocation $location): void
    {
        $this->location = $location->load(['warehouse', 'parentLocation', 'childLocations', 'creator', 'updater', 'capacityUnit']);
    }

    public function confirmToggleStatus(): void
    {
        $this->modal('toggle-status-modal')->show();
    }

    public function toggleStatus(): void
    {
        $this->location->update([
            'is_active' => ! $this->location->is_active,
            'active_at' => $this->location->is_active ? null : now(),
            'updated_by' => auth()->id(),
        ]);

        $this->modal('toggle-status-modal')->close();
        \Flux\Flux::toast('Estado actualizado exitosamente.', variant: 'success');
        $this->location->refresh();
    }

    public function confirmDelete(): void
    {
        $this->modal('delete-location-modal')->show();
    }

    public function delete(): void
    {
        $this->location->update([
            'deleted_by' => auth()->id(),
        ]);

        $this->location->delete();

        $this->modal('delete-location-modal')->close();
        \Flux\Flux::toast('Ubicación eliminada exitosamente.', variant: 'success');
        $this->redirect(route('storage-locations.index'), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('storage-locations.index') }}" wire:navigate>
                Volver
            </flux:button>
            <div>
                <flux:heading size="xl">{{ $location->code }}</flux:heading>
                <flux:text class="mt-1">{{ $location->name }}</flux:text>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if ($location->is_active)
                <flux:badge color="green" size="lg">Activo</flux:badge>
            @else
                <flux:badge color="red" size="lg">Inactivo</flux:badge>
            @endif

            <flux:button variant="primary" icon="pencil" href="{{ route('storage-locations.edit', $location->slug) }}" wire:navigate>
                Editar
            </flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Detalles Generales -->
            <flux:card>
                <flux:heading size="lg">Detalles Generales</flux:heading>

                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text size="sm" class="text-gray-500">Código</flux:text>
                            <flux:text class="mt-1 font-medium">{{ $location->code }}</flux:text>
                        </div>

                        <div>
                            <flux:text size="sm" class="text-gray-500">Nombre</flux:text>
                            <flux:text class="mt-1 font-medium">{{ $location->name }}</flux:text>
                        </div>

                        <div>
                            <flux:text size="sm" class="text-gray-500">Bodega</flux:text>
                            <flux:text class="mt-1 font-medium">{{ $location->warehouse->name }}</flux:text>
                        </div>

                        <div>
                            <flux:text size="sm" class="text-gray-500">Tipo</flux:text>
                            <div class="mt-1">
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
                                <flux:badge color="zinc" size="sm">
                                    {{ $typeLabels[$location->type] ?? $location->type }}
                                </flux:badge>
                            </div>
                        </div>

                        @if ($location->parentLocation)
                            <div class="col-span-2">
                                <flux:text size="sm" class="text-gray-500">Ubicación Padre</flux:text>
                                <flux:text class="mt-1">
                                    <a href="{{ route('storage-locations.show', $location->parentLocation->slug) }}" wire:navigate class="text-blue-600 hover:underline">
                                        {{ $location->parentLocation->code }} - {{ $location->parentLocation->name }}
                                    </a>
                                </flux:text>
                            </div>
                        @endif

                        @if ($location->description)
                            <div class="col-span-2">
                                <flux:text size="sm" class="text-gray-500">Descripción</flux:text>
                                <flux:text class="mt-1">{{ $location->description }}</flux:text>
                            </div>
                        @endif

                        @if ($location->barcode)
                            <div class="col-span-2">
                                <flux:text size="sm" class="text-gray-500">Código de Barras</flux:text>
                                <flux:text class="mt-1 font-mono">{{ $location->barcode }}</flux:text>
                            </div>
                        @endif

                        @if ($location->section || $location->aisle || $location->shelf || $location->bin)
                            <div class="col-span-2">
                                <flux:text size="sm" class="text-gray-500">Ubicación Física</flux:text>
                                <flux:text class="mt-1 font-mono">
                                    @if ($location->section)Sección: {{ $location->section }}@endif
                                    @if ($location->aisle) | Pasillo: {{ $location->aisle }}@endif
                                    @if ($location->shelf) | Estante: {{ $location->shelf }}@endif
                                    @if ($location->bin) | Contenedor: {{ $location->bin }}@endif
                                </flux:text>
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>

            <!-- Capacidad y Límites -->
            <flux:card>
                <flux:heading size="lg">Capacidad y Límites</flux:heading>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <flux:text size="sm" class="text-gray-500">Capacidad</flux:text>
                        @if ($location->capacity)
                            <flux:text class="mt-1 font-medium">
                                {{ number_format($location->capacity, 2) }}
                                {{ $location->capacityUnit?->abbreviation ?? '' }}
                            </flux:text>
                        @else
                            <flux:text class="mt-1 text-gray-400">No especificado</flux:text>
                        @endif
                    </div>

                    <div>
                        <flux:text size="sm" class="text-gray-500">Peso Máximo</flux:text>
                        @if ($location->weight_limit)
                            <flux:text class="mt-1 font-medium">
                                {{ number_format($location->weight_limit, 2) }} kg
                            </flux:text>
                        @else
                            <flux:text class="mt-1 text-gray-400">No especificado</flux:text>
                        @endif
                    </div>

                    @if ($location->length || $location->width || $location->height)
                        <div>
                            <flux:text size="sm" class="text-gray-500">Dimensiones (L x A x H)</flux:text>
                            <flux:text class="mt-1 font-medium">
                                {{ $location->length ?? '-' }} x {{ $location->width ?? '-' }} x {{ $location->height ?? '-' }} m
                            </flux:text>
                        </div>
                    @endif

                    <div>
                        <flux:text size="sm" class="text-gray-500">Nivel / Orden</flux:text>
                        <flux:text class="mt-1 font-medium">Nivel {{ $location->level ?? 0 }} / Orden {{ $location->sort_order ?? 0 }}</flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Configuración -->
            <flux:card>
                <flux:heading size="lg">Configuración</flux:heading>

                <div class="mt-4 flex gap-4">
                    @if ($location->is_pickable)
                        <flux:badge color="blue" size="sm" icon="check">Ubicación de Picking</flux:badge>
                    @else
                        <flux:badge color="zinc" size="sm">No es ubicación de Picking</flux:badge>
                    @endif

                    @if ($location->is_receivable)
                        <flux:badge color="green" size="sm" icon="check">Ubicación de Recepción</flux:badge>
                    @else
                        <flux:badge color="zinc" size="sm">No es ubicación de Recepción</flux:badge>
                    @endif
                </div>
            </flux:card>

            <!-- Ubicaciones Hijas -->
            @if ($location->childLocations->isNotEmpty())
                <flux:card>
                    <flux:heading size="lg">Ubicaciones Hijas ({{ $location->childLocations->count() }})</flux:heading>

                    <div class="mt-4 space-y-2">
                        @foreach ($location->childLocations as $child)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div>
                                    <flux:text class="font-medium">{{ $child->code }}</flux:text>
                                    <flux:text size="sm" class="text-gray-500">{{ $child->name }}</flux:text>
                                </div>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="eye"
                                    href="{{ route('storage-locations.show', $child->slug) }}"
                                    wire:navigate
                                >
                                    Ver
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Acciones -->
            <flux:card>
                <flux:heading size="lg">Acciones</flux:heading>

                <div class="mt-4 space-y-2">
                    <flux:button
                        variant="outline"
                        icon="pencil"
                        href="{{ route('storage-locations.edit', $location->slug) }}"
                        wire:navigate
                        class="w-full"
                    >
                        Editar Ubicación
                    </flux:button>

                    <flux:button
                        variant="outline"
                        :icon="$location->is_active ? 'x-circle' : 'check-circle'"
                        wire:click="confirmToggleStatus"
                        class="w-full"
                    >
                        {{ $location->is_active ? 'Desactivar' : 'Activar' }}
                    </flux:button>

                    <flux:button
                        variant="danger"
                        icon="trash"
                        wire:click="confirmDelete"
                        class="w-full"
                    >
                        Eliminar
                    </flux:button>
                </div>
            </flux:card>

            <!-- Información del Sistema -->
            <flux:card>
                <flux:heading size="lg">Información del Sistema</flux:heading>

                <div class="mt-4 space-y-3">
                    <div>
                        <flux:text size="sm" class="text-gray-500">Creado</flux:text>
                        <flux:text size="sm" class="mt-1">
                            {{ $location->created_at->format('d/m/Y H:i') }}
                        </flux:text>
                        @if ($location->creator)
                            <flux:text size="sm" class="text-gray-500">
                                por {{ $location->creator->name }}
                            </flux:text>
                        @endif
                    </div>

                    @if ($location->updated_at->ne($location->created_at))
                        <div>
                            <flux:text size="sm" class="text-gray-500">Última Actualización</flux:text>
                            <flux:text size="sm" class="mt-1">
                                {{ $location->updated_at->format('d/m/Y H:i') }}
                            </flux:text>
                            @if ($location->updater)
                                <flux:text size="sm" class="text-gray-500">
                                    por {{ $location->updater->name }}
                                </flux:text>
                            @endif
                        </div>
                    @endif

                    @if ($location->active_at)
                        <div>
                            <flux:text size="sm" class="text-gray-500">Fecha de Activación</flux:text>
                            <flux:text size="sm" class="mt-1">
                                {{ $location->active_at->format('d/m/Y H:i') }}
                            </flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>
    </div>

    <!-- Toggle Status Confirmation Modal -->
    <flux:modal name="toggle-status-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $location->is_active ? '¿Desactivar ubicación?' : '¿Activar ubicación?' }}
                </flux:heading>
                <flux:text class="mt-2">
                    @if($location->is_active)
                        <p>Estás a punto de desactivar la ubicación <strong>{{ $location->name }}</strong>.</p>
                        <p>La ubicación no estará disponible para operaciones de inventario.</p>
                    @else
                        <p>Estás a punto de activar la ubicación <strong>{{ $location->name }}</strong>.</p>
                        <p>La ubicación estará disponible para operaciones de inventario.</p>
                    @endif
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button wire:click="toggleStatus" :variant="$location->is_active ? 'danger' : 'primary'">
                    {{ $location->is_active ? 'Desactivar' : 'Activar' }}
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
                    <p>Estás a punto de eliminar la ubicación <strong>{{ $location->name }}</strong>.</p>
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
