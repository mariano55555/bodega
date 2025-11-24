<?php

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public ?int $customerToDelete = null;

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
        $this->showFilters = ! $this->showFilters;
    }

    public function confirmDelete(int $customerId): void
    {
        $this->customerToDelete = $customerId;
    }

    public function cancelDelete(): void
    {
        $this->customerToDelete = null;
    }

    public function with(): array
    {
        $query = Customer::query()
            ->when(! auth()->user()->isSuperAdmin(), function ($q) {
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
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter !== '', function ($q) {
                if ($this->statusFilter === 'active') {
                    $q->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->latest();

        return [
            'customers' => $query->paginate($this->perPage),
        ];
    }

    public function delete(): void
    {
        if (! $this->customerToDelete) {
            return;
        }

        $customer = Customer::find($this->customerToDelete);

        if (! $customer) {
            \Flux\Flux::toast('Cliente no encontrado.', variant: 'danger');
            $this->customerToDelete = null;

            return;
        }

        if ($customer->dispatches()->exists()) {
            \Flux\Flux::toast('No se puede eliminar el cliente porque tiene despachos asociados.', variant: 'danger');
            $this->customerToDelete = null;

            return;
        }

        $customer->delete();
        \Flux\Flux::toast('Cliente eliminado exitosamente.', variant: 'success');
        $this->customerToDelete = null;
    }

    public function toggleStatus(int $customerId): void
    {
        $customer = Customer::find($customerId);

        if (! $customer) {
            \Flux\Flux::toast('Cliente no encontrado.', variant: 'danger');

            return;
        }

        $customer->is_active = ! $customer->is_active;
        $customer->active_at = $customer->is_active ? now() : null;
        $customer->save();

        \Flux\Flux::toast('Estado del cliente actualizado.', variant: 'success');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Clientes</flux:heading>
            <flux:text class="mt-1">Gestión de clientes de la compañía</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" href="{{ route('customers.create') }}" wire:navigate>
            Nuevo Cliente
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
                    <option value="individual">Individual</option>
                    <option value="business">Empresa</option>
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
            Mostrando {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} de {{ $customers->total() }} clientes
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
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>NIT/DUI</flux:table.column>
                <flux:table.column>Contacto</flux:table.column>
                <flux:table.column>Teléfono</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($customers as $customer)
                    <flux:table.row :key="$customer->id">
                        <flux:table.cell>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $customer->name }}
                                </div>
                                @if ($customer->legal_name && $customer->legal_name !== $customer->name)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $customer->legal_name }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="$customer->getTypeBadgeColor()">
                                {{ $customer->getTypeLabel() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $customer->tax_id ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm">
                                <div class="text-gray-900 dark:text-gray-100">
                                    {{ $customer->contact_person ?? 'Sin contacto' }}
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $customer->phone ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $customer->email ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="$customer->is_active ? 'green' : 'red'">
                                {{ $customer->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil"
                                    href="{{ route('customers.edit', $customer) }}"
                                    wire:navigate
                                    title="Editar"
                                />

                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    :icon="$customer->is_active ? 'x-circle' : 'check-circle'"
                                    wire:click="toggleStatus({{ $customer->id }})"
                                    :title="$customer->is_active ? 'Desactivar' : 'Activar'"
                                />

                                <flux:modal.trigger name="delete-customer-{{ $customer->id }}">
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
                            <div>No se encontraron clientes.</div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    @if ($customers->hasPages())
        <div class="mt-6">
            {{ $customers->links() }}
        </div>
    @endif

    <!-- Delete Confirmation Modals -->
    @foreach ($customers as $customer)
        <flux:modal name="delete-customer-{{ $customer->id }}" class="min-w-[22rem]">
            <form wire:submit="delete" class="space-y-6">
                <div>
                    <flux:heading size="lg">Confirmar eliminación</flux:heading>
                    <flux:text class="mt-2">
                        ¿Está seguro de que desea eliminar el cliente <strong>{{ $customer->name }}</strong>?<br>
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
                        wire:click="confirmDelete({{ $customer->id }})"
                        wire:then="delete"
                    >
                        Eliminar cliente
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endforeach
</div>
