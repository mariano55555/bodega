<?php

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
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
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');

        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }
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

        // Get products with movements in the period
        $query = DB::table('inventory_movements as im')
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'unit_of_measures.abbreviation as unit_abbreviation',
                'unit_of_measures.name as unit_name',
                'product_categories.id as category_id',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'parent_categories.id as parent_id',
                'parent_categories.name as parent_name',
                'parent_categories.legacy_code as parent_code',
            ])
            ->join('products', 'im.product_id', '=', 'products.id')
            ->leftJoin('unit_of_measures', 'products.unit_of_measure_id', '=', 'unit_of_measures.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('im.company_id', $this->effectiveCompanyId)
            ->where('im.warehouse_id', $this->warehouse_id)
            ->whereNotNull('im.balance_quantity')
            ->where(function ($q) {
                $q->whereBetween('im.movement_date', [$this->start_date, $this->end_date])
                    ->orWhere('im.movement_date', '<', $this->start_date);
            })
            ->groupBy([
                'products.id',
                'products.name',
                'products.sku',
                'unit_of_measures.abbreviation',
                'unit_of_measures.name',
                'product_categories.id',
                'product_categories.name',
                'product_categories.legacy_code',
                'parent_categories.id',
                'parent_categories.name',
                'parent_categories.legacy_code',
            ])
            ->get();

        // For each product, calculate initial stock, entries, exits
        $results = $query->map(function ($product) {
            // Get initial stock (balance just before start_date)
            $initialMovement = InventoryMovement::where('company_id', $this->effectiveCompanyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $this->warehouse_id)
                ->where('movement_date', '<', $this->start_date)
                ->whereNotNull('balance_quantity')
                ->orderByDesc('movement_date')
                ->orderByDesc('id')
                ->first();

            $initialStock = $initialMovement?->balance_quantity ?? 0;

            // Get entries during period
            $entries = InventoryMovement::where('company_id', $this->effectiveCompanyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $this->warehouse_id)
                ->whereBetween('movement_date', [$this->start_date, $this->end_date])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_in');

            // Get exits during period
            $exits = InventoryMovement::where('company_id', $this->effectiveCompanyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $this->warehouse_id)
                ->whereBetween('movement_date', [$this->start_date, $this->end_date])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_out');

            // Final stock
            $finalStock = (float) $initialStock + (float) $entries - (float) $exits;

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
                'final_stock' => $finalStock,
            ];
        });

        return $results;
    }

    #[Computed]
    public function groupedByCategory()
    {
        return $this->reportData->groupBy('parent_name')->map(function ($items, $parentName) {
            $firstItem = $items->first();

            return (object) [
                'parent_name' => $parentName ?: 'Sin Categoría',
                'parent_code' => $firstItem->parent_code ?? '',
                'items' => $items,
                'subtotals' => (object) [
                    'initial_stock' => $items->sum('initial_stock'),
                    'entries' => $items->sum('entries'),
                    'exits' => $items->sum('exits'),
                    'final_stock' => $items->sum('final_stock'),
                ],
            ];
        });
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
            'final_stock' => $data->sum('final_stock'),
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

        return $this->redirect(route('reports.dispatches.stock-movements.pdf', $params));
    }

    public function exportExcel()
    {
        $params = [
            'empresa' => $this->effectiveCompanyId,
            'bodega' => $this->warehouse_id,
            'inicio' => $this->start_date,
            'fin' => $this->end_date,
        ];

        return $this->redirect(route('reports.dispatches.stock-movements.excel', $params));
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Existencias y Movimientos de Inventario</flux:heading>
            <flux:text class="mt-1">Resumen de existencias iniciales, entradas, salidas y existencias finales por producto</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('reports.dispatches.hub') }}" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Filtros</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
                    <flux:select.option value="">-- Todas --</flux:select.option>
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

            <div class="md:col-span-2 flex items-end gap-2">
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
                    Seleccione una empresa para ver el reporte de existencias y movimientos
                </flux:text>
            </div>
        </flux:card>
    @elseif (! $this->warehouse_id)
        <flux:card>
            <div class="py-12 text-center">
                <flux:icon.building-storefront class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Seleccione una Bodega</flux:heading>
                <flux:text class="mt-2">
                    Seleccione una bodega para ver el reporte de existencias y movimientos
                </flux:text>
            </div>
        </flux:card>
    @else
        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <flux:card class="text-center">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Productos</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_products']) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Categorías</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_categories']) }}</flux:heading>
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

            <flux:card class="text-center bg-amber-50 dark:bg-amber-900/20">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Existencia Final</flux:text>
                <flux:heading size="xl" class="text-amber-600 dark:text-amber-400">
                    {{ number_format($this->totals['final_stock'], 2) }}
                </flux:heading>
            </flux:card>
        </div>

        {{-- Grouped Data by Category --}}
        @forelse ($this->groupedByCategory as $parentName => $group)
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-amber-100 dark:bg-amber-900 rounded-lg">
                            <flux:icon name="folder" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <flux:heading size="lg">Categoría: {{ $group->parent_name }} - {{ $group->parent_code }}</flux:heading>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Descripción</flux:table.column>
                            <flux:table.column>Unidad</flux:table.column>
                            <flux:table.column class="text-right">Existencia Inicial</flux:table.column>
                            <flux:table.column class="text-right">Entradas</flux:table.column>
                            <flux:table.column class="text-right">Salidas</flux:table.column>
                            <flux:table.column class="text-right">Existencia Final</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($group->items as $item)
                                <flux:table.row :key="$item->product_id">
                                    <flux:table.cell>
                                        <div class="font-medium">{{ $item->product_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $item->sku }}</div>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:badge>{{ $item->unit }}</flux:badge>
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right">
                                        {{ number_format($item->initial_stock, 2) }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right text-green-600 dark:text-green-400">
                                        {{ number_format($item->entries, 2) }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right text-red-600 dark:text-red-400">
                                        {{ number_format($item->exits, 2) }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right font-medium">
                                        {{ number_format($item->final_stock, 2) }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Subtotal for this category --}}
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-4 gap-4 text-right">
                        <div>
                            <flux:text class="text-sm text-gray-500">Subtotal Inicial</flux:text>
                            <flux:heading size="md">{{ number_format($group->subtotals->initial_stock, 2) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm text-gray-500">Subtotal Entradas</flux:text>
                            <flux:heading size="md" class="text-green-600 dark:text-green-400">{{ number_format($group->subtotals->entries, 2) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm text-gray-500">Subtotal Salidas</flux:text>
                            <flux:heading size="md" class="text-red-600 dark:text-red-400">{{ number_format($group->subtotals->exits, 2) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm text-gray-500">Subtotal Final</flux:text>
                            <flux:heading size="md" class="text-amber-600 dark:text-amber-400">{{ number_format($group->subtotals->final_stock, 2) }}</flux:heading>
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
            <flux:card class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/30 border-amber-200 dark:border-amber-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-amber-100 dark:bg-amber-900 rounded-xl">
                            <flux:icon name="calculator" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        <flux:heading size="lg">Totales del Período</flux:heading>
                    </div>
                    <div class="grid grid-cols-4 gap-8 text-right">
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
                            <flux:text class="text-sm">Final</flux:text>
                            <flux:heading size="lg" class="text-amber-600 dark:text-amber-400">{{ number_format($this->totals['final_stock'], 2) }}</flux:heading>
                        </div>
                    </div>
                </div>
            </flux:card>
        @endif
    @endif
</div>
