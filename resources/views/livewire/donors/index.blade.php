<?php

use App\Models\Donor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';
    public ?int $donorToDelete = null;
    public bool $showFilters = false;
    public int $perPage = 15;

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
        $this->showFilters = !$this->showFilters;
    }

    public function confirmDelete(int $donorId): void
    {
        $this->donorToDelete = $donorId;
    }

    public function cancelDelete(): void
    {
        $this->donorToDelete = null;
    }

    public function with(): array
    {
        $query = Donor::query()
            ->when(!auth()->user()->isSuperAdmin(), function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('legal_name', 'like', "%{$this->search}%")
                        ->orWhere('tax_id', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('contact_person', 'like', "%{$this->search}%");
                });
            })
            ->when($this->typeFilter, fn ($q) => $q->where('donor_type', $this->typeFilter))
            ->when($this->statusFilter !== '', function ($q) {
                if ($this->statusFilter === 'active') {
                    $q->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->latest();

        return [
            'donors' => $query->paginate($this->perPage),
        ];
    }

    public function delete(): void
    {
        if (!$this->donorToDelete) {
            return;
        }

        $donor = Donor::find($this->donorToDelete);

        if (!$donor) {
            \Flux\Flux::toast('Donante no encontrado.', variant: 'danger');
            $this->donorToDelete = null;
            return;
        }

        if ($donor->donations()->exists()) {
            \Flux\Flux::toast('No se puede eliminar el donante porque tiene donaciones asociadas.', variant: 'danger');
            $this->donorToDelete = null;
            return;
        }

        $donor->delete();
        \Flux\Flux::toast('Donante eliminado exitosamente.', variant: 'success');
        $this->donorToDelete = null;
    }

    public function toggleStatus(int $donorId): void
    {
        $donor = Donor::find($donorId);

        if (!$donor) {
            \Flux\Flux::toast('Donante no encontrado.', variant: 'danger');
            return;
        }

        $donor->is_active = ! $donor->is_active;
        $donor->active_at = $donor->is_active ? now() : null;
        $donor->save();

        \Flux\Flux::toast('Estado del donante actualizado.', variant: 'success');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Donantes</flux:heading>
            <flux:text class="mt-1">Gestión de donantes de la compañía</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" href="{{ route('donors.create') }}" wire:navigate>
            Nuevo Donante
        </flux:button>
    </div>

    <!-- Search and Filter Toggle -->
    <div class="flex gap-3">
        <div class="w-full md:w-96">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre, NIT, email, contacto..."
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
                <flux:label>Tipo</flux:label>
                <flux:select wire:model.live="typeFilter" placeholder="Todos los tipos">
                    <option value="">Todos</option>
                    <option value="individual">Persona Individual</option>
                    <option value="organization">Organización</option>
                    <option value="government">Gobierno</option>
                    <option value="ngo">ONG</option>
                    <option value="international">Org. Internacional</option>
                </flux:select>
            </flux:field>

            <flux:field class="w-full sm:w-48">
                <flux:label>Estado</flux:label>
                <flux:select wire:model.live="statusFilter" placeholder="Todos los estados">
                    <option value="">Todos</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </flux:select>
            </flux:field>
        </div>
    @endif

    <!-- Stats and Per Page -->
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Mostrando {{ $donors->firstItem() ?? 0 }} - {{ $donors->lastItem() ?? 0 }} de {{ $donors->total() }} donantes
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
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>NIT/DUI</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Contacto</flux:table.column>
                <flux:table.column>Teléfono</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($donors as $donor)
                    <flux:table.row :key="$donor->id">
                        <flux:table.cell>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $donor->name }}
                                </div>
                                @if ($donor->legal_name && $donor->legal_name !== $donor->name)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $donor->legal_name }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $donor->tax_id ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="$donor->getDonorTypeBadgeColor()">
                                {{ $donor->getDonorTypeLabel() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm">
                                <div class="text-gray-900 dark:text-gray-100">
                                    {{ $donor->contact_person ?? 'Sin contacto' }}
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $donor->phone ?? $donor->contact_phone ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $donor->email ?? $donor->contact_email ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="$donor->is_active ? 'green' : 'red'">
                                {{ $donor->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil"
                                    href="{{ route('donors.edit', $donor) }}"
                                    wire:navigate
                                    title="Editar"
                                />

                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    :icon="$donor->is_active ? 'x-circle' : 'check-circle'"
                                    wire:click="toggleStatus({{ $donor->id }})"
                                    :title="$donor->is_active ? 'Desactivar' : 'Activar'"
                                />

                                <flux:modal.trigger name="delete-donor-{{ $donor->id }}">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        title="Eliminar"
                                        class="text-red-600 hover:text-red-700 dark:text-red-500 dark:hover:text-red-400"
                                    />
                                </flux:modal.trigger>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <flux:icon name="users" class="mx-auto h-12 w-12 mb-3 opacity-20" />
                            <div>No se encontraron donantes.</div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    @if ($donors->hasPages())
        <div class="mt-6">
            {{ $donors->links() }}
        </div>
    @endif

    <!-- Delete Confirmation Modals -->
    @foreach ($donors as $donor)
        <flux:modal name="delete-donor-{{ $donor->id }}" class="min-w-[22rem]">
            <form wire:submit="delete" class="space-y-6">
                <div>
                    <flux:heading size="lg">Confirmar eliminación</flux:heading>
                    <flux:text class="mt-2">
                        ¿Está seguro de que desea eliminar el donante <strong>{{ $donor->name }}</strong>?<br>
                        Esta acción no se puede deshacer.
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button
                        type="button"
                        variant="danger"
                        wire:click="confirmDelete({{ $donor->id }})"
                        wire:then="delete"
                    >
                        Eliminar donante
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endforeach
</div>
