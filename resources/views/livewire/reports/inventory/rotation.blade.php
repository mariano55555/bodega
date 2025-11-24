<?php

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Product;
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

        // Default to last 3 months
        if (! $this->date_from) {
            $this->date_from = now()->subMonths(3)->startOfMonth()->format('Y-m-d');
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

        return \App\Models\Warehouse::where('company_id', $this->effectiveCompanyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function rotationData()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        $companyId = $this->effectiveCompanyId;
        $dateFrom = $this->date_from;
        $dateTo = $this->date_to;

        return Product::where('company_id', $companyId)
            ->with(['inventory' => function ($q) {
                if ($this->warehouse_id) {
                    $q->where('warehouse_id', $this->warehouse_id);
                }
            }])
            ->get()
            ->map(function ($product) use ($dateFrom, $dateTo, $companyId) {
                $avgInventory = $product->inventory->avg('quantity') ?? 0;

                $totalOut = InventoryMovement::where('company_id', $companyId)
                    ->where('product_id', $product->id)
                    ->when($this->warehouse_id, function ($q) {
                        $q->where('warehouse_id', $this->warehouse_id);
                    })
                    ->whereBetween('movement_date', [$dateFrom, $dateTo])
                    ->where('quantity_out', '>', 0)
                    ->sum('quantity_out');

                $rotationRate = $avgInventory > 0 ? ($totalOut / $avgInventory) : 0;

                return [
                    'product' => $product,
                    'avg_inventory' => $avgInventory,
                    'total_out' => $totalOut,
                    'rotation_rate' => $rotationRate,
                ];
            })
            ->filter(fn ($item) => $item['avg_inventory'] > 0 || $item['total_out'] > 0)
            ->sortByDesc('rotation_rate')
            ->values();
    }

    public function exportExcel(): void
    {
        $this->redirect(route('reports.inventory.rotation.export', [
            'warehouse_id' => $this->warehouse_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'empresa' => $this->effectiveCompanyId,
        ]));
    }

    public function resetFilters(): void
    {
        $this->warehouse_id = null;
        $this->date_from = now()->subMonths(3)->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->endOfMonth()->format('Y-m-d');
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Rotacion de Inventarios</flux:heading>

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

            <flux:button wire:click="exportExcel" variant="primary" :disabled="!$this->effectiveCompanyId" icon="table-cells">
                Exportar Excel
            </flux:button>
        </div>
    </flux:card>

    {{-- Summary Cards --}}
    @if ($this->effectiveCompanyId && $this->rotationData->isNotEmpty())
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Productos Analizados</flux:text>
                        <flux:heading size="2xl">{{ number_format($this->rotationData->count()) }}</flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <flux:icon.cube class="size-8 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Alta Rotacion</flux:text>
                        <flux:heading size="2xl" class="text-green-600 dark:text-green-400">
                            {{ number_format($this->rotationData->filter(fn($i) => $i['rotation_rate'] >= 1)->count()) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                        <flux:icon.arrow-trending-up class="size-8 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Baja Rotacion</flux:text>
                        <flux:heading size="2xl" class="text-red-600 dark:text-red-400">
                            {{ number_format($this->rotationData->filter(fn($i) => $i['rotation_rate'] < 0.5)->count()) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                        <flux:icon.arrow-trending-down class="size-8 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- Results Table --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Analisis de Rotacion</flux:heading>

        @if (! $this->effectiveCompanyId)
            <div class="py-12 text-center">
                <flux:icon.building-office class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Seleccione una Empresa</flux:heading>
                <flux:text class="mt-2">
                    Seleccione una empresa para ver el analisis de rotacion de inventarios
                </flux:text>
            </div>
        @elseif ($this->rotationData->isEmpty())
            <div class="py-12 text-center">
                <flux:icon.arrow-path class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Sin Datos</flux:heading>
                <flux:text class="mt-2">
                    No se encontraron datos de rotacion para el periodo seleccionado
                </flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>SKU</flux:table.column>
                        <flux:table.column>Producto</flux:table.column>
                        <flux:table.column class="text-right">Inv. Promedio</flux:table.column>
                        <flux:table.column class="text-right">Total Salidas</flux:table.column>
                        <flux:table.column class="text-right">Tasa Rotacion</flux:table.column>
                        <flux:table.column>Clasificacion</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->rotationData->take(50) as $item)
                            <flux:table.row>
                                <flux:table.cell>
                                    <span class="font-mono text-sm">{{ $item['product']->sku }}</span>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $item['product']->name }}
                                </flux:table.cell>

                                <flux:table.cell class="text-right tabular-nums">
                                    {{ number_format($item['avg_inventory'], 2) }}
                                </flux:table.cell>

                                <flux:table.cell class="text-right tabular-nums">
                                    {{ number_format($item['total_out'], 2) }}
                                </flux:table.cell>

                                <flux:table.cell class="text-right tabular-nums font-medium">
                                    {{ number_format($item['rotation_rate'], 2) }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($item['rotation_rate'] >= 1)
                                        <flux:badge color="green" size="sm">Alta</flux:badge>
                                    @elseif ($item['rotation_rate'] >= 0.5)
                                        <flux:badge color="yellow" size="sm">Media</flux:badge>
                                    @else
                                        <flux:badge color="red" size="sm">Baja</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            @if ($this->rotationData->count() > 50)
                <flux:text class="mt-4 text-sm text-zinc-500">
                    Mostrando 50 de {{ number_format($this->rotationData->count()) }} productos. Exporte a Excel para ver todos.
                </flux:text>
            @endif
        @endif
    </flux:card>
</div>
