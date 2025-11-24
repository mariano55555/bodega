<?php

use App\Models\Company;
use App\Models\InventoryMovement;
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
    public ?int $warehouse_id = null;

    #[Url]
    public ?string $date_from = null;

    #[Url]
    public ?string $date_to = null;

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
        $this->warehouse_id = null;
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
    public function movements()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        $companyId = $this->effectiveCompanyId;

        $query = InventoryMovement::query()
            ->where('company_id', $companyId)
            ->whereBetween('movement_date', [$this->date_from, $this->date_to])
            ->where('quantity_in', '>', 0)
            ->with(['product', 'warehouse', 'movementReason']);

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        return $query->orderBy('movement_date', 'desc')->get();
    }

    #[Computed]
    public function summary()
    {
        $movements = $this->movements;

        return [
            'total_movements' => $movements->count(),
            'total_quantity' => $movements->sum('quantity_in'),
            'total_value' => $movements->sum('total_cost'),
            'by_warehouse' => $movements->groupBy('warehouse_id')->map(function ($items) {
                return [
                    'warehouse' => $items->first()->warehouse,
                    'quantity' => $items->sum('quantity_in'),
                    'value' => $items->sum('total_cost'),
                    'count' => $items->count(),
                ];
            }),
        ];
    }

    public function resetFilters(): void
    {
        $this->warehouse_id = null;
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->endOfMonth()->format('Y-m-d');
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Ingresos por Periodo</flux:heading>

        <flux:button :href="route('reports.inventory.index')" variant="ghost">
            <flux:icon.arrow-left class="size-4" />
            Volver a Reportes
        </flux:button>
    </div>

    {{-- Filters --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
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
                <flux:label>Bodega</flux:label>
                <flux:select wire:model.live="warehouse_id" placeholder="Todas las bodegas" :disabled="!$this->effectiveCompanyId">
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
        </div>

        <div class="mt-4 flex gap-3">
            <flux:button wire:click="resetFilters" variant="ghost">
                Limpiar Filtros
            </flux:button>
        </div>
    </flux:card>

    @if (! $this->effectiveCompanyId)
        <flux:card>
            <div class="py-12 text-center">
                <flux:icon.building-office class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Seleccione una Empresa</flux:heading>
                <flux:text class="mt-2">
                    Seleccione una empresa para ver los ingresos por periodo
                </flux:text>
            </div>
        </flux:card>
    @else
        {{-- Summary Cards --}}
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Total Movimientos</flux:text>
                        <flux:heading size="2xl">{{ number_format($this->summary['total_movements']) }}</flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <flux:icon.arrow-down-tray class="size-8 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Cantidad Total</flux:text>
                        <flux:heading size="2xl" class="text-green-600 dark:text-green-400">
                            +{{ number_format($this->summary['total_quantity'], 2) }}
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
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Valor Total</flux:text>
                        <flux:heading size="2xl" class="text-amber-600 dark:text-amber-400">
                            ${{ number_format($this->summary['total_value'], 2) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <flux:icon.currency-dollar class="size-8 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- By Warehouse Summary --}}
        @if ($this->summary['by_warehouse']->isNotEmpty())
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">Resumen por Bodega</flux:heading>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->summary['by_warehouse'] as $warehouseData)
                        <flux:card variant="outline">
                            <flux:heading size="md" class="mb-3">{{ $warehouseData['warehouse']->name }}</flux:heading>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <flux:text class="text-sm">Movimientos:</flux:text>
                                    <flux:text class="text-sm font-medium">{{ number_format($warehouseData['count']) }}</flux:text>
                                </div>
                                <div class="flex justify-between">
                                    <flux:text class="text-sm">Cantidad:</flux:text>
                                    <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">
                                        +{{ number_format($warehouseData['quantity'], 2) }}
                                    </flux:text>
                                </div>
                                <div class="flex justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                                    <flux:text class="font-medium">Valor:</flux:text>
                                    <flux:text class="font-bold text-amber-600 dark:text-amber-400">
                                        ${{ number_format($warehouseData['value'], 2) }}
                                    </flux:text>
                                </div>
                            </div>
                        </flux:card>
                    @endforeach
                </div>
            </flux:card>
        @endif

        {{-- Movements Table --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Detalle de Ingresos</flux:heading>

            @if ($this->movements->isEmpty())
                <div class="py-12 text-center">
                    <flux:icon.arrow-down-tray class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mt-4">Sin Ingresos</flux:heading>
                    <flux:text class="mt-2">
                        No se encontraron ingresos para el periodo seleccionado
                    </flux:text>
                </div>
            @else
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Fecha</flux:table.column>
                            <flux:table.column>Bodega</flux:table.column>
                            <flux:table.column>SKU</flux:table.column>
                            <flux:table.column>Producto</flux:table.column>
                            <flux:table.column>Motivo</flux:table.column>
                            <flux:table.column class="text-right">Cantidad</flux:table.column>
                            <flux:table.column class="text-right">Valor</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->movements->take(100) as $movement)
                                <flux:table.row>
                                    <flux:table.cell>
                                        {{ $movement->movement_date?->format('d/m/Y') }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $movement->warehouse?->name ?? '-' }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="font-mono text-sm">{{ $movement->product?->sku ?? '-' }}</span>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $movement->product?->name ?? '-' }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $movement->movementReason?->name ?? $movement->movement_type }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right tabular-nums text-green-600 dark:text-green-400">
                                        +{{ number_format($movement->quantity_in, 2) }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right tabular-nums">
                                        ${{ number_format($movement->total_cost ?? 0, 2) }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                @if ($this->movements->count() > 100)
                    <flux:text class="mt-4 text-sm text-zinc-500">
                        Mostrando 100 de {{ number_format($this->movements->count()) }} movimientos.
                    </flux:text>
                @endif
            @endif
        </flux:card>
    @endif
</div>
