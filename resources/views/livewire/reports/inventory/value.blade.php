<?php

use App\Models\Company;
use App\Models\Inventory;
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

    public function mount(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
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
    public function inventories()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        $companyId = $this->effectiveCompanyId;

        $query = Inventory::query()
            ->whereHas('warehouse', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where('quantity', '>', 0)
            ->with(['product', 'warehouse']);

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        return $query->get();
    }

    #[Computed]
    public function summary()
    {
        $inventories = $this->inventories;

        $totalValue = $inventories->sum(function ($inventory) {
            return $inventory->quantity * ($inventory->product->cost ?? 0);
        });

        $byWarehouse = $inventories->groupBy('warehouse_id')->map(function ($items) {
            return [
                'warehouse' => $items->first()->warehouse,
                'total_value' => $items->sum(function ($inventory) {
                    return $inventory->quantity * ($inventory->product->cost ?? 0);
                }),
                'total_quantity' => $items->sum('quantity'),
                'products_count' => $items->unique('product_id')->count(),
            ];
        });

        return [
            'total_value' => $totalValue,
            'by_warehouse' => $byWarehouse,
            'total_products' => $inventories->unique('product_id')->count(),
        ];
    }

    public function exportExcel(): void
    {
        $this->redirect(route('reports.inventory.value.export', [
            'warehouse_id' => $this->warehouse_id,
            'empresa' => $this->effectiveCompanyId,
        ]));
    }

    public function resetFilters(): void
    {
        $this->warehouse_id = null;
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Valor de Inventarios</flux:heading>

        <flux:button :href="route('reports.inventory.index')" variant="ghost">
            <flux:icon.arrow-left class="size-4" />
            Volver a Reportes
        </flux:button>
    </div>

    {{-- Filters --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
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
        </div>

        <div class="mt-4 flex gap-3">
            <flux:button wire:click="resetFilters" variant="ghost">
                Limpiar Filtros
            </flux:button>

            <flux:button wire:click="exportExcel" variant="primary" icon="table-cells">
                Exportar Excel
            </flux:button>
        </div>
    </flux:card>

    {{-- Summary Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Valor Total de Inventarios</flux:text>
                    <flux:heading size="2xl" class="text-green-600 dark:text-green-400">
                        ${{ number_format($this->summary['total_value'], 2) }}
                    </flux:heading>
                </div>
                <div class="flex size-16 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                    <flux:icon.currency-dollar class="size-8 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Total de Productos</flux:text>
                    <flux:heading size="2xl">{{ number_format($this->summary['total_products']) }}</flux:heading>
                </div>
                <div class="flex size-16 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.cube class="size-8 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Summary by Warehouse --}}
    @if ($this->summary['by_warehouse']->isNotEmpty())
        <flux:card class="mb-6">
            <flux:heading size="lg" class="mb-4">Valor por Bodega</flux:heading>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->summary['by_warehouse'] as $warehouseData)
                    <flux:card variant="outline">
                        <flux:heading size="md" class="mb-3">{{ $warehouseData['warehouse']->name }}</flux:heading>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <flux:text class="text-sm">Productos:</flux:text>
                                <flux:text class="text-sm font-medium">{{ $warehouseData['products_count'] }}</flux:text>
                            </div>
                            <div class="flex justify-between">
                                <flux:text class="text-sm">Cantidad:</flux:text>
                                <flux:text class="text-sm font-medium">{{ number_format($warehouseData['total_quantity'], 2) }}</flux:text>
                            </div>
                            <div class="flex justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                                <flux:text class="font-medium">Valor Total:</flux:text>
                                <flux:text class="font-bold text-green-600 dark:text-green-400">
                                    ${{ number_format($warehouseData['total_value'], 2) }}
                                </flux:text>
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        </flux:card>
    @endif

    {{-- Detailed Table --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Detalle por Producto</flux:heading>

        @if ($this->inventories->isEmpty())
            <div class="py-12 text-center">
                <flux:icon.cube class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">No hay inventario</flux:heading>
                <flux:text class="mt-2">
                    No se encontró inventario con los filtros seleccionados
                </flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Bodega</flux:table.column>
                        <flux:table.column>SKU</flux:table.column>
                        <flux:table.column>Producto</flux:table.column>
                        <flux:table.column class="text-right">Cantidad</flux:table.column>
                        <flux:table.column class="text-right">Costo Unitario</flux:table.column>
                        <flux:table.column class="text-right">Valor Total</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->inventories as $inventory)
                            <flux:table.row>
                                <flux:table.cell>
                                    {{ $inventory->warehouse->name }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <span class="font-mono text-sm">{{ $inventory->product->sku }}</span>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $inventory->product->name }}
                                </flux:table.cell>

                                <flux:table.cell class="text-right font-medium tabular-nums">
                                    {{ number_format($inventory->quantity, 2) }}
                                    <span class="ml-1 text-xs text-zinc-500">
                                        {{ $inventory->product->unitOfMeasure?->abbreviation ?? 'UND' }}
                                    </span>
                                </flux:table.cell>

                                <flux:table.cell class="text-right tabular-nums">
                                    ${{ number_format($inventory->product->cost ?? 0, 2) }}
                                </flux:table.cell>

                                <flux:table.cell class="text-right font-semibold tabular-nums">
                                    @php
                                        $totalValue = $inventory->quantity * ($inventory->product->cost ?? 0);
                                    @endphp
                                    <span class="text-green-600 dark:text-green-400">
                                        ${{ number_format($totalValue, 2) }}
                                    </span>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </flux:card>
</div>
