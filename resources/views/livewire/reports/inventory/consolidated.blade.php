<?php

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component
{
    #[Url(as: 'empresa')]
    public $company_id = '';

    #[Url(as: 'bodega')]
    public $warehouse_id = '';

    #[Url(as: 'inicio')]
    public $start_date = '';

    #[Url(as: 'fin')]
    public $end_date = '';

    public function mount(): void
    {
        $this->start_date = request()->query('inicio', now()->startOfMonth()->format('Y-m-d'));
        $this->end_date = request()->query('fin', now()->endOfMonth()->format('Y-m-d'));

        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }
    }

    public function updatedCompanyId(): void
    {
        $this->warehouse_id = '';
    }

    #[Computed]
    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    #[Computed]
    public function effectiveCompanyId()
    {
        return $this->isSuperAdmin ? $this->company_id : auth()->user()->company_id;
    }

    #[Computed]
    public function companies()
    {
        return $this->isSuperAdmin
            ? Company::where('is_active', true)->orderBy('name')->get()
            : collect();
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
    public function reportData()
    {
        if (! $this->effectiveCompanyId || ! $this->warehouse_id) {
            return collect();
        }

        $companyId = $this->effectiveCompanyId;
        $warehouseId = $this->warehouse_id;

        // Get products with movements
        $query = DB::table('inventory_movements as im')
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.cost as product_cost',
                'units_of_measure.abbreviation as unit_abbreviation',
                'units_of_measure.name as unit_name',
                'product_categories.id as category_id',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'parent_categories.id as parent_id',
                'parent_categories.name as parent_name',
                'parent_categories.legacy_code as parent_code',
            ])
            ->join('products', 'im.product_id', '=', 'products.id')
            ->leftJoin('units_of_measure', 'products.unit_of_measure_id', '=', 'units_of_measure.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('im.company_id', $companyId)
            ->where('im.warehouse_id', $warehouseId)
            ->whereNotNull('im.balance_quantity')
            ->where(function ($q) {
                $q->whereBetween('im.movement_date', [$this->start_date, $this->end_date])
                    ->orWhere('im.movement_date', '<', $this->start_date);
            })
            ->groupBy([
                'products.id',
                'products.name',
                'products.sku',
                'products.cost',
                'units_of_measure.abbreviation',
                'units_of_measure.name',
                'product_categories.id',
                'product_categories.name',
                'product_categories.legacy_code',
                'parent_categories.id',
                'parent_categories.name',
                'parent_categories.legacy_code',
            ])
            ->get();

        return $query->map(function ($product) use ($companyId, $warehouseId) {
            // Get initial stock (balance just before start_date)
            $initialMovement = InventoryMovement::where('company_id', $companyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $warehouseId)
                ->where('movement_date', '<', $this->start_date)
                ->whereNotNull('balance_quantity')
                ->orderByDesc('movement_date')
                ->orderByDesc('id')
                ->first();

            $initialStock = $initialMovement?->balance_quantity ?? 0;

            // Get entries during period
            $entries = InventoryMovement::where('company_id', $companyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $warehouseId)
                ->whereBetween('movement_date', [$this->start_date, $this->end_date])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_in');

            // Get exits during period
            $exits = InventoryMovement::where('company_id', $companyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $warehouseId)
                ->whereBetween('movement_date', [$this->start_date, $this->end_date])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_out');

            $currentStock = (float) $initialStock + (float) $entries - (float) $exits;

            // Get warehouse-specific unit cost from the most recent movement
            $lastCostMovement = InventoryMovement::where('company_id', $companyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $warehouseId)
                ->where('movement_date', '<=', $this->end_date)
                ->whereNotNull('balance_quantity')
                ->where('unit_cost', '>', 0)
                ->orderByDesc('movement_date')
                ->orderByDesc('id')
                ->first();

            $unitCost = (float) ($lastCostMovement?->unit_cost ?? $product->product_cost ?? 0);
            $totalCost = $currentStock * $unitCost;

            return (object) [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'unit' => $product->unit_abbreviation ?? $product->unit_name ?? '-',
                'category_id' => $product->category_id,
                'category_name' => $product->category_name,
                'category_code' => $product->category_code,
                'parent_id' => $product->parent_id,
                'parent_name' => $product->parent_name,
                'parent_code' => $product->parent_code,
                'initial_stock' => (float) $initialStock,
                'entries' => (float) $entries,
                'exits' => (float) $exits,
                'current_stock' => $currentStock,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
            ];
        });
    }

    #[Computed]
    public function groupedByCategory()
    {
        return $this->reportData->groupBy('parent_name')->map(function ($items, $parentName) {
            $firstItem = $items->first();

            $subcategories = $items->groupBy('category_name')->map(function ($subItems, $categoryName) {
                $first = $subItems->first();

                return (object) [
                    'category_name' => $categoryName ?: 'Sin Subcategoría',
                    'category_code' => $first->category_code ?? '',
                    'items' => $subItems->sortBy('sku')->values(),
                    'subtotals' => (object) [
                        'initial_stock' => $subItems->sum('initial_stock'),
                        'entries' => $subItems->sum('entries'),
                        'exits' => $subItems->sum('exits'),
                        'current_stock' => $subItems->sum('current_stock'),
                        'total_cost' => $subItems->sum('total_cost'),
                    ],
                ];
            })->sortBy('category_code')->values();

            return (object) [
                'parent_name' => $parentName ?: 'Sin Categoría',
                'parent_code' => $firstItem->parent_code ?? '',
                'subcategories' => $subcategories,
                'parent_subtotals' => (object) [
                    'initial_stock' => $items->sum('initial_stock'),
                    'entries' => $items->sum('entries'),
                    'exits' => $items->sum('exits'),
                    'current_stock' => $items->sum('current_stock'),
                    'total_cost' => $items->sum('total_cost'),
                ],
            ];
        })->sortBy(fn ($group) => $group->parent_code)->values();
    }

    #[Computed]
    public function totals(): array
    {
        $data = $this->reportData;

        return [
            'total_products' => $data->count(),
            'total_categories' => $data->pluck('parent_id')->unique()->count(),
            'initial_stock' => $data->sum('initial_stock'),
            'entries' => $data->sum('entries'),
            'exits' => $data->sum('exits'),
            'current_stock' => $data->sum('current_stock'),
            'total_cost' => $data->sum('total_cost'),
        ];
    }

    public function exportPdf()
    {
        $params = [
            'empresa' => $this->effectiveCompanyId,
            'bodega' => $this->warehouse_id,
            'inicio' => $this->start_date,
            'fin' => $this->end_date,
        ];

        return $this->redirect(route('reports.inventory.consolidated.pdf', $params));
    }

    public function exportExcel(): void
    {
        $this->redirect(route('reports.inventory.consolidated.export', [
            'empresa' => $this->effectiveCompanyId,
            'bodega' => $this->warehouse_id,
            'inicio' => $this->start_date,
            'fin' => $this->end_date,
        ]));
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Reporte Inventario Consolidado</flux:heading>
            <flux:text class="mt-1">Existencias, movimientos y valorización por línea presupuestaria</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('reports.inventory.index') }}" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Filtros</flux:heading>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            @if ($this->isSuperAdmin)
                <flux:field class="md:col-span-5">
                    <flux:label>Empresa</flux:label>
                    <flux:select wire:model.live="company_id">
                        <flux:select.option value="">-- Seleccione una empresa --</flux:select.option>
                        @foreach ($this->companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Bodega</flux:label>
                <flux:select wire:model.live="warehouse_id">
                    <flux:select.option value="">-- Seleccione --</flux:select.option>
                    @foreach ($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Fecha Inicio</flux:label>
                <flux:input type="date" wire:model.live="start_date" />
            </flux:field>

            <flux:field>
                <flux:label>Fecha Fin</flux:label>
                <flux:input type="date" wire:model.live="end_date" />
            </flux:field>

            <div class="flex items-end gap-2 md:col-span-2">
                <flux:button wire:click="exportPdf" variant="filled" icon="document-arrow-down" size="sm" class="bg-red-600 hover:bg-red-700">
                    PDF
                </flux:button>
                <flux:button wire:click="exportExcel" variant="filled" icon="document-arrow-down" size="sm" class="bg-green-600 hover:bg-green-700">
                    Excel
                </flux:button>
            </div>
        </div>
    </flux:card>

    @if (! $this->effectiveCompanyId)
        <flux:card>
            <div class="py-12 text-center">
                <flux:icon.building-office class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Seleccione una Empresa</flux:heading>
                <flux:text class="mt-2">
                    Seleccione una empresa para ver el reporte de inventario consolidado
                </flux:text>
            </div>
        </flux:card>
    @elseif (! $this->warehouse_id)
        <flux:card>
            <div class="py-12 text-center">
                <flux:icon.building-storefront class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Seleccione una Bodega</flux:heading>
                <flux:text class="mt-2">
                    Seleccione una bodega para ver el reporte de inventario consolidado
                </flux:text>
            </div>
        </flux:card>
    @else
        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <flux:card class="text-center">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Productos</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_products']) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center bg-blue-50 dark:bg-blue-900/20">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Existencia Inicial</flux:text>
                <flux:heading size="xl" class="text-blue-600 dark:text-blue-400">
                    {{ number_format($this->totals['initial_stock'], 2) }}
                </flux:heading>
            </flux:card>

            <flux:card class="text-center bg-green-50 dark:bg-green-900/20">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Entradas</flux:text>
                <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                    {{ number_format($this->totals['entries'], 2) }}
                </flux:heading>
            </flux:card>

            <flux:card class="text-center bg-red-50 dark:bg-red-900/20">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Salidas</flux:text>
                <flux:heading size="xl" class="text-red-600 dark:text-red-400">
                    {{ number_format($this->totals['exits'], 2) }}
                </flux:heading>
            </flux:card>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-2">
            <flux:card class="text-center bg-amber-50 dark:bg-amber-900/20">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Existencia Actual</flux:text>
                <flux:heading size="xl" class="text-amber-600 dark:text-amber-400">
                    {{ number_format($this->totals['current_stock'], 2) }}
                </flux:heading>
            </flux:card>

            <flux:card class="text-center bg-indigo-50 dark:bg-indigo-900/20">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Costo Total</flux:text>
                <flux:heading size="xl" class="text-indigo-600 dark:text-indigo-400">
                    ${{ number_format($this->totals['total_cost'], 2) }}
                </flux:heading>
            </flux:card>
        </div>

        {{-- Grouped Data by Categoría Padre > Línea Presupuestaria --}}
        @forelse ($this->groupedByCategory as $group)
            <flux:card>
                {{-- Parent Category Header --}}
                <div class="mb-4 flex items-center gap-3 rounded-lg bg-zinc-100 p-3 dark:bg-zinc-800">
                    <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900">
                        <flux:icon name="folder" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ $group->parent_name }}</flux:heading>
                        <flux:text class="text-sm text-gray-500">Código {{ $group->parent_code }}</flux:text>
                    </div>
                </div>

                {{-- Subcategories / Líneas Presupuestarias --}}
                @foreach ($group->subcategories as $subcategory)
                    <div class="mb-6 last:mb-0" wire:key="sub-{{ $subcategory->category_code }}">
                        <div class="mb-2 flex items-center gap-2 border-l-4 border-blue-500 pl-3">
                            <div>
                                <flux:heading size="md">Línea Presupuestaria: {{ $subcategory->category_name }}</flux:heading>
                                <flux:text class="text-sm text-gray-500">Específico {{ $subcategory->category_code }}</flux:text>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Descripción del Producto</flux:table.column>
                                    <flux:table.column>Unidad de Medida</flux:table.column>
                                    <flux:table.column align="end">Existencia Inicial</flux:table.column>
                                    <flux:table.column align="end">Entradas</flux:table.column>
                                    <flux:table.column align="end">Salidas</flux:table.column>
                                    <flux:table.column align="end">Existencia Actual</flux:table.column>
                                    <flux:table.column align="end">Precio Unitario</flux:table.column>
                                    <flux:table.column align="end">Costo Total</flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach ($subcategory->items as $item)
                                        <flux:table.row :key="$item->product_id">
                                            <flux:table.cell>
                                                <div class="font-medium">{{ $item->product_name }}</div>
                                                <div class="text-xs text-zinc-500">{{ $item->sku }}</div>
                                            </flux:table.cell>

                                            <flux:table.cell>
                                                <flux:badge>{{ $item->unit }}</flux:badge>
                                            </flux:table.cell>

                                            <flux:table.cell align="end" class="tabular-nums">
                                                {{ number_format($item->initial_stock, 2) }}
                                            </flux:table.cell>

                                            <flux:table.cell align="end" class="tabular-nums text-green-600 dark:text-green-400">
                                                {{ number_format($item->entries, 2) }}
                                            </flux:table.cell>

                                            <flux:table.cell align="end" class="tabular-nums text-red-600 dark:text-red-400">
                                                {{ number_format($item->exits, 2) }}
                                            </flux:table.cell>

                                            <flux:table.cell align="end" class="font-medium tabular-nums">
                                                {{ number_format($item->current_stock, 2) }}
                                            </flux:table.cell>

                                            <flux:table.cell align="end" class="tabular-nums">
                                                ${{ number_format($item->unit_cost, 2) }}
                                            </flux:table.cell>

                                            <flux:table.cell align="end" class="font-semibold tabular-nums">
                                                ${{ number_format($item->total_cost, 2) }}
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach

                                    {{-- Subtotal row inside table --}}
                                    <flux:table.row class="bg-gray-50 dark:bg-zinc-800/50 border-t-2 border-gray-300 dark:border-gray-600">
                                        <flux:table.cell variant="strong">
                                            Total Línea {{ $subcategory->category_code }}
                                        </flux:table.cell>
                                        <flux:table.cell></flux:table.cell>
                                        <flux:table.cell align="end" variant="strong" class="tabular-nums">
                                            {{ number_format($subcategory->subtotals->initial_stock, 2) }}
                                        </flux:table.cell>
                                        <flux:table.cell align="end" variant="strong" class="tabular-nums text-green-600 dark:text-green-400">
                                            {{ number_format($subcategory->subtotals->entries, 2) }}
                                        </flux:table.cell>
                                        <flux:table.cell align="end" variant="strong" class="tabular-nums text-red-600 dark:text-red-400">
                                            {{ number_format($subcategory->subtotals->exits, 2) }}
                                        </flux:table.cell>
                                        <flux:table.cell align="end" variant="strong" class="tabular-nums text-amber-600 dark:text-amber-400">
                                            {{ number_format($subcategory->subtotals->current_stock, 2) }}
                                        </flux:table.cell>
                                        <flux:table.cell></flux:table.cell>
                                        <flux:table.cell align="end" variant="strong" class="tabular-nums text-indigo-600 dark:text-indigo-400">
                                            ${{ number_format($subcategory->subtotals->total_cost, 2) }}
                                        </flux:table.cell>
                                    </flux:table.row>
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    </div>
                @endforeach

                {{-- Parent category total --}}
                <div class="mt-4 border-t-2 border-gray-300 pt-4 dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <flux:heading size="md">Total {{ $group->parent_name }}</flux:heading>
                        <div class="grid grid-cols-5 gap-6 text-right">
                            <div>
                                <flux:text class="text-sm text-gray-500">Inicial</flux:text>
                                <flux:heading size="md">{{ number_format($group->parent_subtotals->initial_stock, 2) }}</flux:heading>
                            </div>
                            <div>
                                <flux:text class="text-sm text-gray-500">Entradas</flux:text>
                                <flux:heading size="md" class="text-green-600 dark:text-green-400">{{ number_format($group->parent_subtotals->entries, 2) }}</flux:heading>
                            </div>
                            <div>
                                <flux:text class="text-sm text-gray-500">Salidas</flux:text>
                                <flux:heading size="md" class="text-red-600 dark:text-red-400">{{ number_format($group->parent_subtotals->exits, 2) }}</flux:heading>
                            </div>
                            <div>
                                <flux:text class="text-sm text-gray-500">Actual</flux:text>
                                <flux:heading size="md" class="text-amber-600 dark:text-amber-400">{{ number_format($group->parent_subtotals->current_stock, 2) }}</flux:heading>
                            </div>
                            <div>
                                <flux:text class="text-sm text-gray-500">Costo Total</flux:text>
                                <flux:heading size="md" class="text-indigo-600 dark:text-indigo-400">${{ number_format($group->parent_subtotals->total_cost, 2) }}</flux:heading>
                            </div>
                        </div>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card>
                <div class="py-12 text-center">
                    <flux:icon.document-magnifying-glass class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mt-4">Sin Resultados</flux:heading>
                    <flux:text class="mt-2">
                        No se encontraron movimientos en el período seleccionado
                    </flux:text>
                </div>
            </flux:card>
        @endforelse

        {{-- Grand Total --}}
        @if ($this->groupedByCategory->count() > 0)
            <flux:card class="border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 dark:border-amber-800 dark:from-amber-900/30 dark:to-orange-900/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-amber-100 p-3 dark:bg-amber-900">
                            <flux:icon name="calculator" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        <flux:heading size="lg">Totales del Período</flux:heading>
                    </div>
                    <div class="grid grid-cols-5 gap-8 text-right">
                        <div>
                            <flux:text class="text-sm">Inicial</flux:text>
                            <flux:heading size="lg">{{ number_format($this->totals['initial_stock'], 2) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm">Entradas</flux:text>
                            <flux:heading size="lg" class="text-green-600 dark:text-green-400">{{ number_format($this->totals['entries'], 2) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm">Salidas</flux:text>
                            <flux:heading size="lg" class="text-red-600 dark:text-red-400">{{ number_format($this->totals['exits'], 2) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm">Actual</flux:text>
                            <flux:heading size="lg" class="text-amber-600 dark:text-amber-400">{{ number_format($this->totals['current_stock'], 2) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm">Costo Total</flux:text>
                            <flux:heading size="lg" class="text-indigo-600 dark:text-indigo-400">${{ number_format($this->totals['total_cost'], 2) }}</flux:heading>
                        </div>
                    </div>
                </div>
            </flux:card>
        @endif
    @endif
</div>
