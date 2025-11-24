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
    public ?int $year = null;

    #[Url]
    public ?int $month = null;

    public function mount(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }

        // Default to current year/month
        if (! $this->year) {
            $this->year = now()->year;
        }
        if (! $this->month) {
            $this->month = now()->month;
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
    public function years()
    {
        return collect(range(now()->year, now()->year - 5, -1));
    }

    #[Computed]
    public function months()
    {
        return collect([
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ]);
    }

    #[Computed]
    public function movementsSummary()
    {
        if (! $this->effectiveCompanyId) {
            return null;
        }

        $companyId = $this->effectiveCompanyId;
        $startDate = \Carbon\Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = InventoryMovement::where('company_id', $companyId)
            ->whereBetween('movement_date', [$startDate, $endDate]);

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        $movements = $query->get();

        // Count entries as movements with quantity_in > 0, exits as movements with quantity_out > 0
        // Transfer movements include both transfer_in and transfer_out types
        $transferTypes = ['transfer', 'transfer_in', 'transfer_out'];

        return [
            'total_movements' => $movements->count(),
            'total_entries' => $movements->filter(fn ($m) => $m->quantity_in > 0)->count(),
            'total_exits' => $movements->filter(fn ($m) => $m->quantity_out > 0)->count(),
            'total_transfers' => $movements->filter(fn ($m) => in_array($m->movement_type, $transferTypes))->count(),
            'total_adjustments' => $movements->where('movement_type', 'adjustment')->count(),
            'quantity_in' => $movements->sum('quantity_in'),
            'quantity_out' => $movements->sum('quantity_out'),
            'total_value_in' => $movements->filter(fn ($m) => $m->quantity_in > 0)->sum('total_cost'),
            'total_value_out' => $movements->filter(fn ($m) => $m->quantity_out > 0)->sum('total_cost'),
        ];
    }

    #[Computed]
    public function movementsByDay()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        $companyId = $this->effectiveCompanyId;
        $startDate = \Carbon\Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = InventoryMovement::where('company_id', $companyId)
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->selectRaw('DATE(movement_date) as date, COUNT(*) as count, SUM(quantity_in) as qty_in, SUM(quantity_out) as qty_out')
            ->groupBy('date')
            ->orderBy('date');

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        return $query->get();
    }

    public function exportExcel(): void
    {
        $this->redirect(route('reports.movements.monthly.export', [
            'warehouse_id' => $this->warehouse_id,
            'year' => $this->year,
            'month' => $this->month,
            'empresa' => $this->effectiveCompanyId,
        ]));
    }

    public function resetFilters(): void
    {
        $this->warehouse_id = null;
        $this->year = now()->year;
        $this->month = now()->month;
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Movimientos Mensuales</flux:heading>

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
                <flux:label>Ano</flux:label>
                <flux:select wire:model.live="year" :disabled="!$this->effectiveCompanyId">
                    @foreach ($this->years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Mes</flux:label>
                <flux:select wire:model.live="month" :disabled="!$this->effectiveCompanyId">
                    @foreach ($this->months as $m => $name)
                        <option value="{{ $m }}">{{ $name }}</option>
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
                    Seleccione una empresa para ver el resumen de movimientos mensuales
                </flux:text>
            </div>
        </flux:card>
    @elseif ($this->movementsSummary)
        {{-- Summary Cards --}}
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-5">
            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Total Movimientos</flux:text>
                        <flux:heading size="2xl">{{ number_format($this->movementsSummary['total_movements']) }}</flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <flux:icon.arrows-right-left class="size-8 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Entradas</flux:text>
                        <flux:heading size="2xl" class="text-green-600 dark:text-green-400">
                            {{ number_format($this->movementsSummary['total_entries']) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                        <flux:icon.arrow-down-tray class="size-8 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Salidas</flux:text>
                        <flux:heading size="2xl" class="text-red-600 dark:text-red-400">
                            {{ number_format($this->movementsSummary['total_exits']) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                        <flux:icon.arrow-up-tray class="size-8 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Traslados</flux:text>
                        <flux:heading size="2xl" class="text-purple-600 dark:text-purple-400">
                            {{ number_format($this->movementsSummary['total_transfers']) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                        <flux:icon.arrow-path class="size-8 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Ajustes</flux:text>
                        <flux:heading size="2xl" class="text-amber-600 dark:text-amber-400">
                            {{ number_format($this->movementsSummary['total_adjustments']) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <flux:icon.adjustments-horizontal class="size-8 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Quantity Summary --}}
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Resumen de Cantidades</flux:heading>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <flux:text class="font-medium">Total Cantidad Entrada</flux:text>
                        <flux:text class="text-lg font-bold text-green-600 dark:text-green-400">
                            +{{ number_format($this->movementsSummary['quantity_in'], 2) }}
                        </flux:text>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <flux:text class="font-medium">Total Cantidad Salida</flux:text>
                        <flux:text class="text-lg font-bold text-red-600 dark:text-red-400">
                            -{{ number_format($this->movementsSummary['quantity_out'], 2) }}
                        </flux:text>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-zinc-100 dark:bg-zinc-800 rounded-lg border-t-2 border-zinc-300 dark:border-zinc-600">
                        <flux:text class="font-medium">Balance Neto</flux:text>
                        @php
                            $netBalance = $this->movementsSummary['quantity_in'] - $this->movementsSummary['quantity_out'];
                        @endphp
                        <flux:text class="text-lg font-bold {{ $netBalance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $netBalance >= 0 ? '+' : '' }}{{ number_format($netBalance, 2) }}
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-4">Resumen de Valores</flux:heading>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <flux:text class="font-medium">Valor Total Entradas</flux:text>
                        <flux:text class="text-lg font-bold text-green-600 dark:text-green-400">
                            ${{ number_format($this->movementsSummary['total_value_in'], 2) }}
                        </flux:text>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <flux:text class="font-medium">Valor Total Salidas</flux:text>
                        <flux:text class="text-lg font-bold text-red-600 dark:text-red-400">
                            ${{ number_format($this->movementsSummary['total_value_out'], 2) }}
                        </flux:text>
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Daily Breakdown --}}
        @if ($this->movementsByDay->isNotEmpty())
            <flux:card>
                <flux:heading size="lg" class="mb-4">Movimientos por Dia - {{ $this->months[$this->month] }} {{ $this->year }}</flux:heading>

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Fecha</flux:table.column>
                            <flux:table.column class="text-right">Movimientos</flux:table.column>
                            <flux:table.column class="text-right">Cantidad Entrada</flux:table.column>
                            <flux:table.column class="text-right">Cantidad Salida</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->movementsByDay as $day)
                                <flux:table.row>
                                    <flux:table.cell>
                                        {{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right tabular-nums">
                                        {{ number_format($day->count) }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right tabular-nums text-green-600 dark:text-green-400">
                                        +{{ number_format($day->qty_in ?? 0, 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right tabular-nums text-red-600 dark:text-red-400">
                                        -{{ number_format($day->qty_out ?? 0, 2) }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </flux:card>
        @else
            <flux:card>
                <div class="py-12 text-center">
                    <flux:icon.calendar class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mt-4">Sin Movimientos</flux:heading>
                    <flux:text class="mt-2">
                        No se encontraron movimientos para {{ $this->months[$this->month] }} {{ $this->year }}
                    </flux:text>
                </div>
            </flux:card>
        @endif
    @endif
</div>
