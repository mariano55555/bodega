<?php

use App\Models\Company;
use App\Models\InventoryTransfer;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Url(as: 'empresa')]
    public $company_id = '';

    #[Url]
    public ?int $warehouse_from_id = null;

    #[Url]
    public ?int $warehouse_to_id = null;

    #[Url]
    public ?string $date_from = null;

    #[Url]
    public ?string $date_to = null;

    #[Url]
    public ?string $status = null;

    public function mount(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }

        // Default to current month
        if (! $this->date_from) {
            $this->date_from = now()->startOfMonth()->format('Y-m-d');
        }
        if (! $this->date_to) {
            $this->date_to = now()->endOfMonth()->format('Y-m-d');
        }
    }

    public function updatedCompanyId(): void
    {
        $this->warehouse_from_id = null;
        $this->warehouse_to_id = null;
    }

    #[Computed]
    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    #[Computed]
    public function companies()
    {
        return $this->isSuperAdmin
            ? Company::where('is_active', true)->orderBy('name')->get()
            : collect();
    }

    #[Computed]
    public function effectiveCompanyId()
    {
        return $this->isSuperAdmin ? $this->company_id : auth()->user()->company_id;
    }

    #[Computed]
    public function warehouses()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        return Warehouse::where('company_id', $this->effectiveCompanyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function statuses(): array
    {
        return [
            'draft' => 'Borrador',
            'pending' => 'Pendiente',
            'approved' => 'Aprobado',
            'in_transit' => 'En Tránsito',
            'received' => 'Recibido',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
        ];
    }

    #[Computed]
    public function transfers()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        $companyId = $this->effectiveCompanyId;

        $query = InventoryTransfer::query()
            ->whereHas('fromWarehouse', fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('requested_at', [$this->date_from, $this->date_to.' 23:59:59'])
            ->with(['fromWarehouse', 'toWarehouse', 'requestedBy', 'details.product']);

        if ($this->warehouse_from_id) {
            $query->where('from_warehouse_id', $this->warehouse_from_id);
        }

        if ($this->warehouse_to_id) {
            $query->where('to_warehouse_id', $this->warehouse_to_id);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $query->orderBy('requested_at', 'desc')->get();
    }

    #[Computed]
    public function summary()
    {
        $transfers = $this->transfers;

        return [
            'total_transfers' => $transfers->count(),
            'by_status' => $transfers->groupBy('status')->map(fn ($items) => $items->count()),
            'total_items' => $transfers->sum(fn ($transfer) => $transfer->details->sum('quantity')),
            'total_shipping_cost' => $transfers->sum('shipping_cost'),
        ];
    }

    public function exportExcel(): void
    {
        $this->redirect(route('reports.movements.transfers.export', [
            'warehouse_from_id' => $this->warehouse_from_id,
            'warehouse_to_id' => $this->warehouse_to_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'status' => $this->status,
            'empresa' => $this->effectiveCompanyId,
        ]));
    }

    public function resetFilters(): void
    {
        $this->warehouse_from_id = null;
        $this->warehouse_to_id = null;
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->endOfMonth()->format('Y-m-d');
        $this->status = null;
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Traslados entre Bodegas</flux:heading>

        <flux:button :href="route('reports.inventory.index')" variant="ghost">
            <flux:icon.arrow-left class="size-4" />
            Volver a Reportes
        </flux:button>
    </div>

    {{-- Filters --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @if ($this->isSuperAdmin)
                <flux:field>
                    <flux:label>Empresa</flux:label>
                    <flux:select wire:model.live="company_id" placeholder="Seleccione una empresa">
                        <option value="">Seleccione una empresa</option>
                        @foreach ($this->companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Bodega Origen</flux:label>
                <flux:select wire:model.live="warehouse_from_id" placeholder="Todas las bodegas" :disabled="!$this->effectiveCompanyId">
                    <option value="">Todas las bodegas</option>
                    @foreach ($this->warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Bodega Destino</flux:label>
                <flux:select wire:model.live="warehouse_to_id" placeholder="Todas las bodegas" :disabled="!$this->effectiveCompanyId">
                    <option value="">Todas las bodegas</option>
                    @foreach ($this->warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Fecha Desde</flux:label>
                <flux:input type="date" wire:model.live="date_from" :disabled="!$this->effectiveCompanyId" />
            </flux:field>

            <flux:field>
                <flux:label>Fecha Hasta</flux:label>
                <flux:input type="date" wire:model.live="date_to" :disabled="!$this->effectiveCompanyId" />
            </flux:field>

            <flux:field>
                <flux:label>Estado</flux:label>
                <flux:select wire:model.live="status" placeholder="Todos los estados" :disabled="!$this->effectiveCompanyId">
                    <option value="">Todos los estados</option>
                    @foreach ($this->statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>

        <div class="mt-4 flex gap-3">
            <flux:button wire:click="resetFilters" variant="ghost">
                Limpiar Filtros
            </flux:button>

            <flux:button wire:click="exportExcel" variant="primary" :disabled="!$this->effectiveCompanyId" icon="table-cells">
                Exportar Excel
            </flux:button>
        </div>
    </flux:card>

    @if (! $this->effectiveCompanyId)
        <flux:card>
            <div class="py-12 text-center">
                <flux:icon.building-office class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Seleccione una Empresa</flux:heading>
                <flux:text class="mt-2">
                    Seleccione una empresa para ver los traslados entre bodegas
                </flux:text>
            </div>
        </flux:card>
    @else
        {{-- Summary Cards --}}
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Total Traslados</flux:text>
                        <flux:heading size="2xl">{{ number_format($this->summary['total_transfers']) }}</flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <flux:icon.arrow-path class="size-8 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Total Items</flux:text>
                        <flux:heading size="2xl" class="text-green-600 dark:text-green-400">
                            {{ number_format($this->summary['total_items']) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                        <flux:icon.cube class="size-8 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Completados</flux:text>
                        <flux:heading size="2xl" class="text-emerald-600 dark:text-emerald-400">
                            {{ number_format($this->summary['by_status']['completed'] ?? 0) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                        <flux:icon.check-circle class="size-8 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Costo Envío</flux:text>
                        <flux:heading size="2xl" class="text-amber-600 dark:text-amber-400">
                            ${{ number_format($this->summary['total_shipping_cost'], 2) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <flux:icon.truck class="size-8 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Status Breakdown --}}
        @if ($this->summary['by_status']->isNotEmpty())
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">Resumen por Estado</flux:heading>
                <div class="flex flex-wrap gap-4">
                    @foreach ($this->statuses as $statusKey => $statusLabel)
                        @if (isset($this->summary['by_status'][$statusKey]))
                            <div class="flex items-center gap-2 rounded-lg bg-zinc-100 px-4 py-2 dark:bg-zinc-800">
                                <flux:text class="font-medium">{{ $statusLabel }}:</flux:text>
                                <flux:badge>{{ $this->summary['by_status'][$statusKey] }}</flux:badge>
                            </div>
                        @endif
                    @endforeach
                </div>
            </flux:card>
        @endif

        {{-- Transfers Table --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Detalle de Traslados</flux:heading>

            @if ($this->transfers->isEmpty())
                <div class="py-12 text-center">
                    <flux:icon.arrow-path class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mt-4">Sin Traslados</flux:heading>
                    <flux:text class="mt-2">
                        No se encontraron traslados para el período seleccionado
                    </flux:text>
                </div>
            @else
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>No. Traslado</flux:table.column>
                            <flux:table.column>Fecha</flux:table.column>
                            <flux:table.column>Origen</flux:table.column>
                            <flux:table.column>Destino</flux:table.column>
                            <flux:table.column>Estado</flux:table.column>
                            <flux:table.column class="text-right">Items</flux:table.column>
                            <flux:table.column>Solicitado Por</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->transfers as $transfer)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <a href="{{ route('transfers.show', $transfer) }}" class="font-mono text-sm text-blue-600 hover:underline dark:text-blue-400">
                                            {{ $transfer->transfer_number }}
                                        </a>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $transfer->requested_at?->format('d/m/Y') }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $transfer->fromWarehouse?->name ?? '-' }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $transfer->toWarehouse?->name ?? '-' }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        @php
                                            $statusColors = [
                                                'draft' => 'zinc',
                                                'pending' => 'yellow',
                                                'approved' => 'blue',
                                                'in_transit' => 'purple',
                                                'received' => 'cyan',
                                                'completed' => 'green',
                                                'cancelled' => 'red',
                                            ];
                                        @endphp
                                        <flux:badge color="{{ $statusColors[$transfer->status] ?? 'zinc' }}" size="sm">
                                            {{ $this->statuses[$transfer->status] ?? $transfer->status }}
                                        </flux:badge>
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right tabular-nums">
                                        {{ number_format($transfer->details->sum('quantity')) }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $transfer->requestedBy?->name ?? '-' }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </flux:card>
    @endif
</div>
