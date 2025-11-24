<?php

use App\Models\Donation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    #[Url(as: 'tipo', history: true)]
    public string $donorTypeFilter = '';

    #[Url(as: 'bodega', history: true)]
    public string $warehouseFilter = '';

    #[Url(history: true)]
    public int $perPage = 15;

    public ?int $donationToDelete = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDonorTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->donorTypeFilter = '';
        $this->warehouseFilter = '';
        $this->resetPage();
    }

    #[Computed]
    public function summaryStats(): array
    {
        $user = auth()->user();

        $baseQuery = Donation::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('company_id', $user->company_id));

        return [
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', 'borrador')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pendiente')->count(),
            'received' => (clone $baseQuery)->where('status', 'recibido')->count(),
            'total_value' => (clone $baseQuery)->where('status', 'recibido')->sum('estimated_value'),
        ];
    }

    #[Computed]
    public function warehouses()
    {
        $user = auth()->user();

        return \App\Models\Warehouse::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('company_id', $user->company_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function with(): array
    {
        $user = auth()->user();

        $query = Donation::query()
            ->with(['warehouse'])
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('company_id', $user->company_id))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('donation_number', 'like', "%{$this->search}%")
                        ->orWhere('donor_name', 'like', "%{$this->search}%")
                        ->orWhere('document_number', 'like', "%{$this->search}%")
                        ->orWhere('purpose', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->donorTypeFilter, fn ($q) => $q->where('donor_type', $this->donorTypeFilter))
            ->when($this->warehouseFilter, fn ($q) => $q->where('warehouse_id', $this->warehouseFilter))
            ->latest();

        return [
            'donations' => $query->paginate($this->perPage),
        ];
    }

    public function confirmDelete(int $donationId): void
    {
        $this->donationToDelete = $donationId;
        $this->modal('delete-modal')->show();
    }

    public function delete(): void
    {
        if (! $this->donationToDelete) {
            return;
        }

        $donation = Donation::find($this->donationToDelete);

        if (! $donation) {
            \Flux::toast(variant: 'danger', text: 'Donación no encontrada.');
            $this->donationToDelete = null;
            return;
        }

        if (in_array($donation->status, ['aprobado', 'recibido'])) {
            \Flux::toast(variant: 'danger', text: 'No se puede eliminar una donación que ya fue aprobada o recibida.');
            $this->donationToDelete = null;
            return;
        }

        $donation->delete();
        $this->donationToDelete = null;
        \Flux::toast(variant: 'success', text: 'Donación eliminada exitosamente.');
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">Donaciones</flux:heading>
            <flux:text class="mt-1">Gestión de donaciones recibidas</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" href="{{ route('donations.create') }}" wire:navigate>
            Nueva Donación
        </flux:button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4">
        <flux:card class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border-purple-200 dark:border-purple-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-purple-600 dark:text-purple-400">Total</flux:text>
                    <flux:heading size="xl" class="text-purple-900 dark:text-purple-100">
                        {{ number_format($this->summaryStats['total']) }}
                    </flux:heading>
                </div>
                <flux:icon name="gift" class="h-8 w-8 text-purple-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900/20 dark:to-zinc-800/20 border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Borrador</flux:text>
                    <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                        {{ number_format($this->summaryStats['draft']) }}
                    </flux:heading>
                </div>
                <flux:icon name="pencil" class="h-8 w-8 text-zinc-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 border-amber-200 dark:border-amber-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-amber-600 dark:text-amber-400">Pendientes</flux:text>
                    <flux:heading size="xl" class="text-amber-900 dark:text-amber-100">
                        {{ number_format($this->summaryStats['pending']) }}
                    </flux:heading>
                </div>
                <flux:icon name="clock" class="h-8 w-8 text-amber-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 border-emerald-200 dark:border-emerald-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Recibidas</flux:text>
                    <flux:heading size="xl" class="text-emerald-900 dark:text-emerald-100">
                        {{ number_format($this->summaryStats['received']) }}
                    </flux:heading>
                </div>
                <flux:icon name="check-circle" class="h-8 w-8 text-emerald-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-green-200 dark:border-green-800 col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">Valor Total</flux:text>
                    <flux:heading size="xl" class="text-green-900 dark:text-green-100">
                        ${{ number_format($this->summaryStats['total_value'], 2) }}
                    </flux:heading>
                </div>
                <flux:icon name="currency-dollar" class="h-8 w-8 text-green-500" />
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="lg:col-span-2">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por número, donante, documento, propósito..."
                        icon="magnifying-glass"
                    />
                </div>

                <flux:select wire:model.live="statusFilter" placeholder="Todos los estados">
                    <flux:select.option value="">Todos los estados</flux:select.option>
                    <flux:select.option value="borrador">Borrador</flux:select.option>
                    <flux:select.option value="pendiente">Pendiente</flux:select.option>
                    <flux:select.option value="aprobado">Aprobado</flux:select.option>
                    <flux:select.option value="recibido">Recibido</flux:select.option>
                    <flux:select.option value="cancelado">Cancelado</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="donorTypeFilter" placeholder="Todos los tipos">
                    <flux:select.option value="">Tipo de donante</flux:select.option>
                    <flux:select.option value="individual">Individual</flux:select.option>
                    <flux:select.option value="organization">Organización</flux:select.option>
                    <flux:select.option value="government">Gobierno</flux:select.option>
                    <flux:select.option value="ngo">ONG</flux:select.option>
                    <flux:select.option value="international">Organismo Internacional</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="warehouseFilter" placeholder="Todas las bodegas">
                    <flux:select.option value="">Todas las bodegas</flux:select.option>
                    @foreach($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if($search || $statusFilter || $donorTypeFilter || $warehouseFilter)
                <div class="flex items-center justify-between pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-sm text-zinc-500">
                        Filtros activos
                    </flux:text>
                    <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                        Limpiar filtros
                    </flux:button>
                </div>
            @endif
        </div>
    </flux:card>

    <!-- Stats and Per Page -->
    <div class="flex items-center justify-between">
        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
            Mostrando {{ $donations->firstItem() ?? 0 }} - {{ $donations->lastItem() ?? 0 }} de {{ $donations->total() }} donaciones
        </flux:text>
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

    <!-- Table -->
    <flux:card class="overflow-hidden">
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Número</flux:table.column>
                    <flux:table.column>Fecha</flux:table.column>
                    <flux:table.column>Donante</flux:table.column>
                    <flux:table.column>Bodega</flux:table.column>
                    <flux:table.column>Documento</flux:table.column>
                    <flux:table.column class="text-right">Valor Estimado</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>

            <flux:table.rows>
                @forelse ($donations as $donation)
                    <flux:table.row :key="$donation->id">
                        <flux:table.cell>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $donation->donation_number }}
                                </div>
                                @if ($donation->project_name)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Proyecto: {{ $donation->project_name }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $donation->document_date ? $donation->document_date->format('d/m/Y') : '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <div>
                                <div class="font-medium">{{ $donation->donor_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 capitalize">
                                    {{ $donation->donor_type === 'individual' ? 'Individual' : ($donation->donor_type === 'organization' ? 'Organización' : 'Gobierno') }}
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $donation->warehouse->name }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($donation->document_number)
                                <div class="text-sm">
                                    <span class="font-medium capitalize">{{ $donation->document_type }}</span>:
                                    {{ $donation->document_number }}
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="text-right">
                            @if ($donation->estimated_value)
                                <span class="font-semibold">${{ number_format($donation->estimated_value, 2) }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $statusColors = [
                                    'borrador' => 'zinc',
                                    'pendiente' => 'yellow',
                                    'aprobado' => 'blue',
                                    'recibido' => 'green',
                                    'cancelado' => 'red',
                                ];
                                $statusLabels = [
                                    'borrador' => 'Borrador',
                                    'pendiente' => 'Pendiente',
                                    'aprobado' => 'Aprobado',
                                    'recibido' => 'Recibido',
                                    'cancelado' => 'Cancelado',
                                ];
                            @endphp
                            <flux:badge :color="$statusColors[$donation->status] ?? 'zinc'" size="sm">
                                {{ $statusLabels[$donation->status] ?? $donation->status }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="eye"
                                    href="{{ route('donations.show', $donation->slug) }}"
                                    wire:navigate
                                >
                                    Ver
                                </flux:button>

                                @if (in_array($donation->status, ['borrador', 'pendiente']))
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="pencil"
                                        href="{{ route('donations.edit', $donation->slug) }}"
                                        wire:navigate
                                    >
                                        Editar
                                    </flux:button>

                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        wire:click="confirmDelete({{ $donation->id }})"
                                    >
                                        Eliminar
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">
                            <div class="text-center py-8 text-gray-500">
                                <flux:icon.gift class="mx-auto h-12 w-12 text-gray-400 mb-3" />
                                <p class="text-sm">No se encontraron donaciones</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
            </flux:table>
        </div>

        @if ($donations->hasPages())
            <div class="mt-6 px-6 pb-6">
                {{ $donations->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Eliminar Donación</flux:heading>
                <flux:text class="mt-2">
                    <p>¿Está seguro de que desea eliminar esta donación?</p>
                    <p class="mt-1 text-red-600 dark:text-red-400 font-medium">Esta acción no se puede deshacer.</p>
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
