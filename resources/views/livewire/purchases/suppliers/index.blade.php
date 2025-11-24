<?php

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $supplierToDelete = null;
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

    public function confirmDelete(int $supplierId): void
    {
        $this->supplierToDelete = $supplierId;
    }

    public function cancelDelete(): void
    {
        $this->supplierToDelete = null;
    }

    public function with(): array
    {
        $query = Supplier::query()
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
            ->when($this->statusFilter !== '', function ($q) {
                if ($this->statusFilter === 'active') {
                    $q->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->latest();

        return [
            'suppliers' => $query->paginate($this->perPage),
        ];
    }

    public function delete(): void
    {
        if (!$this->supplierToDelete) {
            return;
        }

        $supplier = Supplier::find($this->supplierToDelete);

        if (!$supplier) {
            \Flux\Flux::toast('Proveedor no encontrado.', variant: 'danger');
            $this->supplierToDelete = null;
            return;
        }

        if ($supplier->inventoryMovements()->exists()) {
            \Flux\Flux::toast('No se puede eliminar el proveedor porque tiene movimientos de inventario asociados.', variant: 'danger');
            $this->supplierToDelete = null;
            return;
        }

        $supplier->delete();
        \Flux\Flux::toast('Proveedor eliminado exitosamente.', variant: 'success');
        $this->supplierToDelete = null;
    }

    public function toggleStatus(int $supplierId): void
    {
        $supplier = Supplier::find($supplierId);

        if (!$supplier) {
            \Flux\Flux::toast('Proveedor no encontrado.', variant: 'danger');
            return;
        }

        $supplier->is_active = ! $supplier->is_active;
        $supplier->active_at = $supplier->is_active ? now() : null;
        $supplier->save();

        \Flux\Flux::toast('Estado del proveedor actualizado.', variant: 'success');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Proveedores</flux:heading>
            <flux:text class="mt-1">Gestión de proveedores de la compañía</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" href="{{ route('purchases.suppliers.create') }}" wire:navigate>
            Nuevo Proveedor
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4" x-data x-transition>
            <flux:field class="md:w-64">
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
            Mostrando {{ $suppliers->firstItem() ?? 0 }} - {{ $suppliers->lastItem() ?? 0 }} de {{ $suppliers->total() }} proveedores
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
                <flux:table.column>Contacto</flux:table.column>
                <flux:table.column>Teléfono</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($suppliers as $supplier)
                    <flux:table.row :key="$supplier->id">
                        <flux:table.cell>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $supplier->name }}
                                </div>
                                @if ($supplier->legal_name && $supplier->legal_name !== $supplier->name)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $supplier->legal_name }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $supplier->tax_id ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm">
                                <div class="text-gray-900 dark:text-gray-100">
                                    {{ $supplier->contact_person ?? 'Sin contacto' }}
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $supplier->phone ?? $supplier->contact_phone ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $supplier->email ?? $supplier->contact_email ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="$supplier->is_active ? 'green' : 'red'">
                                {{ $supplier->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil"
                                    href="{{ route('purchases.suppliers.edit', $supplier) }}"
                                    wire:navigate
                                    title="Editar"
                                />

                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    :icon="$supplier->is_active ? 'x-circle' : 'check-circle'"
                                    wire:click="toggleStatus({{ $supplier->id }})"
                                    :title="$supplier->is_active ? 'Desactivar' : 'Activar'"
                                />

                                <flux:modal.trigger name="delete-supplier-{{ $supplier->id }}">
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
                        <flux:table.cell colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <flux:icon name="users" class="mx-auto h-12 w-12 mb-3 opacity-20" />
                            <div>No se encontraron proveedores.</div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    @if ($suppliers->hasPages())
        <div class="mt-6">
            {{ $suppliers->links() }}
        </div>
    @endif

    <!-- Delete Confirmation Modals -->
    @foreach ($suppliers as $supplier)
        <flux:modal name="delete-supplier-{{ $supplier->id }}" class="min-w-[22rem]">
            <form wire:submit="delete" class="space-y-6">
                <div>
                    <flux:heading size="lg">Confirmar eliminación</flux:heading>
                    <flux:text class="mt-2">
                        ¿Está seguro de que desea eliminar el proveedor <strong>{{ $supplier->name }}</strong>?<br>
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
                        wire:click="confirmDelete({{ $supplier->id }})"
                        wire:then="delete"
                    >
                        Eliminar proveedor
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endforeach
</div>
