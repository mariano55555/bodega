<?php

use App\Models\InventoryClosure;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $warehouseFilter = '';

    public string $yearFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $user = auth()->user();

        $query = InventoryClosure::query()
            ->with(['warehouse'])
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('closure_number', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->warehouseFilter, fn ($q) => $q->where('warehouse_id', $this->warehouseFilter))
            ->when($this->yearFilter, fn ($q) => $q->where('year', $this->yearFilter))
            ->latest('year')
            ->latest('month');

        $warehouses = \App\Models\Warehouse::query()
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->get();

        return [
            'closures' => $query->paginate(15),
            'warehouses' => $warehouses,
        ];
    }

    public function delete(InventoryClosure $closure): void
    {
        if ($closure->status !== 'en_proceso') {
            session()->flash('error', 'Solo se pueden eliminar cierres en proceso.');

            return;
        }

        $closure->delete();
        session()->flash('success', 'Cierre eliminado exitosamente.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Cierres de Inventario</flux:heading>
            <flux:text class="mt-1">Gestión de cierres mensuales</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" href="{{ route('closures.create') }}" wire:navigate>
            Nuevo Cierre
        </flux:button>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle">
            {{ session('error') }}
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por número..."
                icon="magnifying-glass"
            />
        </div>

        <flux:select wire:model.live="warehouseFilter" placeholder="Todas las bodegas">
            <option value="">Todas</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="statusFilter" placeholder="Todos los estados">
            <option value="">Todos</option>
            <option value="en_proceso">En Proceso</option>
            <option value="cerrado">Cerrado</option>
            <option value="reabierto">Reabierto</option>
            <option value="cancelado">Cancelado</option>
        </flux:select>

        <flux:select wire:model.live="yearFilter" placeholder="Todos los años">
            <option value="">Todos</option>
            @foreach (range(now()->year, now()->year - 5) as $year)
                <option value="{{ $year }}">{{ $year }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Número</flux:table.column>
                <flux:table.column>Bodega</flux:table.column>
                <flux:table.column>Período</flux:table.column>
                <flux:table.column>Productos</flux:table.column>
                <flux:table.column>Valor Total</flux:table.column>
                <flux:table.column>Discrepancias</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($closures as $closure)
                    <flux:table.row :key="$closure->id">
                        <flux:table.cell>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $closure->closure_number }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Cierre: {{ $closure->closure_date->format('d/m/Y') }}
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $closure->warehouse->name }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <div>
                                <div class="font-medium">{{ $closure->monthName }} {{ $closure->year }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $closure->period_start_date->format('d/m') }} - {{ $closure->period_end_date->format('d/m') }}
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ number_format($closure->total_products) }} productos
                        </flux:table.cell>

                        <flux:table.cell>
                            ${{ number_format($closure->total_value, 2) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($closure->has_discrepancies)
                                <flux:badge color="red" size="sm">
                                    {{ $closure->discrepancy_count }} discrepancias
                                </flux:badge>
                            @else
                                <span class="text-gray-400">Sin discrepancias</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $statusColors = [
                                    'en_proceso' => 'yellow',
                                    'cerrado' => 'green',
                                    'reabierto' => 'blue',
                                    'cancelado' => 'red',
                                ];
                                $statusLabels = [
                                    'en_proceso' => 'En Proceso',
                                    'cerrado' => 'Cerrado',
                                    'reabierto' => 'Reabierto',
                                    'cancelado' => 'Cancelado',
                                ];
                            @endphp
                            <flux:badge :color="$statusColors[$closure->status] ?? 'zinc'" size="sm">
                                {{ $statusLabels[$closure->status] ?? $closure->status }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="eye"
                                    href="{{ route('closures.show', $closure->slug) }}"
                                    wire:navigate
                                >
                                    Ver
                                </flux:button>

                                @if ($closure->status === 'en_proceso')
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        wire:click="delete({{ $closure->id }})"
                                        wire:confirm="¿Está seguro de eliminar este cierre?"
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
                                <flux:icon.archive-box class="mx-auto h-12 w-12 text-gray-400 mb-3" />
                                <p class="text-sm">No se encontraron cierres de inventario</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="mt-4">
        {{ $closures->links() }}
    </div>
</div>
