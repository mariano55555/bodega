<?php

use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public $warehouse_id = '';

    public $selected_month = '';

    public $selected_year = '';

    public $physical_counts = [];

    public $show_differences_only = false;

    public function mount(): void
    {
        // Default to current month/year
        $this->selected_month = now()->format('m');
        $this->selected_year = now()->format('Y');
    }

    public function with(): array
    {
        if (! $this->warehouse_id || ! $this->selected_month || ! $this->selected_year) {
            return [
                'inventoryData' => collect(),
                'totals' => [
                    'total_products' => 0,
                    'total_system_value' => 0,
                    'total_physical_value' => 0,
                    'total_difference_value' => 0,
                    'positive_differences' => 0,
                    'negative_differences' => 0,
                ],
                'warehouses' => $this->getWarehouses(),
                'months' => $this->getMonths(),
                'years' => $this->getYears(),
            ];
        }

        // Get the last day of the selected month
        $selectedDate = \Carbon\Carbon::create($this->selected_year, $this->selected_month, 1)->endOfMonth();

        // Get latest inventory balance for each product in the warehouse up to the selected date
        $latestMovements = InventoryMovement::query()
            ->select([
                'product_id',
                DB::raw('MAX(id) as latest_movement_id'),
            ])
            ->where('company_id', auth()->user()->company_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->where('movement_date', '<=', $selectedDate)
            ->groupBy('product_id');

        $inventoryData = InventoryMovement::query()
            ->joinSub($latestMovements, 'latest', function ($join) {
                $join->on('inventory_movements.id', '=', 'latest.latest_movement_id');
            })
            ->with(['product.category', 'product.unitOfMeasure'])
            ->get()
            ->map(function ($movement) {
                $productId = $movement->product_id;
                $systemStock = $movement->balance_quantity;
                $unitCost = $movement->product->unit_cost ?? 0;
                $physicalCount = $this->physical_counts[$productId] ?? null;

                $difference = null;
                $differenceValue = null;

                if ($physicalCount !== null && $physicalCount !== '') {
                    $difference = (float) $physicalCount - $systemStock;
                    $differenceValue = $difference * $unitCost;
                }

                return [
                    'product_id' => $productId,
                    'product' => $movement->product,
                    'category' => $movement->product->category,
                    'system_stock' => $systemStock,
                    'physical_count' => $physicalCount,
                    'unit_cost' => $unitCost,
                    'system_value' => $systemStock * $unitCost,
                    'physical_value' => $physicalCount !== null && $physicalCount !== '' ? (float) $physicalCount * $unitCost : null,
                    'difference' => $difference,
                    'difference_value' => $differenceValue,
                ];
            });

        // Filter to show only differences if selected
        if ($this->show_differences_only) {
            $inventoryData = $inventoryData->filter(function ($item) {
                return $item['difference'] !== null && $item['difference'] != 0;
            });
        }

        // Calculate totals
        $totals = [
            'total_products' => $inventoryData->count(),
            'total_system_value' => $inventoryData->sum('system_value'),
            'total_physical_value' => $inventoryData->whereNotNull('physical_value')->sum('physical_value'),
            'total_difference_value' => $inventoryData->whereNotNull('difference_value')->sum('difference_value'),
            'positive_differences' => $inventoryData->where('difference', '>', 0)->count(),
            'negative_differences' => $inventoryData->where('difference', '<', 0)->count(),
        ];

        return [
            'inventoryData' => $inventoryData->sortBy('product.name'),
            'totals' => $totals,
            'warehouses' => $this->getWarehouses(),
            'months' => $this->getMonths(),
            'years' => $this->getYears(),
        ];
    }

    public function generateAdjustments(): void
    {
        if (! $this->warehouse_id) {
            session()->flash('error', 'Debe seleccionar una bodega');

            return;
        }

        $adjustmentsCount = 0;

        foreach ($this->physical_counts as $productId => $physicalCount) {
            if ($physicalCount === null || $physicalCount === '') {
                continue;
            }

            // Get current system stock
            $selectedDate = \Carbon\Carbon::create($this->selected_year, $this->selected_month, 1)->endOfMonth();

            $latestMovement = InventoryMovement::query()
                ->where('company_id', auth()->user()->company_id)
                ->where('warehouse_id', $this->warehouse_id)
                ->where('product_id', $productId)
                ->where('movement_date', '<=', $selectedDate)
                ->orderByDesc('movement_date')
                ->orderByDesc('id')
                ->first();

            if (! $latestMovement) {
                continue;
            }

            $systemStock = $latestMovement->balance_quantity;
            $difference = (float) $physicalCount - $systemStock;

            // Only create adjustment if there's a difference
            if ($difference == 0) {
                continue;
            }

            // Check if adjustment already exists for this product/warehouse/date
            $existingAdjustment = InventoryAdjustment::query()
                ->where('company_id', auth()->user()->company_id)
                ->where('warehouse_id', $this->warehouse_id)
                ->where('product_id', $productId)
                ->whereDate('adjustment_date', $selectedDate)
                ->where('status', 'pendiente')
                ->first();

            if ($existingAdjustment) {
                // Update existing adjustment
                $existingAdjustment->update([
                    'quantity' => abs($difference),
                    'adjustment_type' => $difference > 0 ? 'incremento' : 'disminucion',
                    'reason' => 'Diferencia detectada en pre-cierre del mes '.$this->selected_month.'/'.$this->selected_year,
                    'previous_quantity' => $systemStock,
                    'new_quantity' => (float) $physicalCount,
                ]);
                $adjustmentsCount++;
            } else {
                // Create new adjustment
                InventoryAdjustment::create([
                    'company_id' => auth()->user()->company_id,
                    'warehouse_id' => $this->warehouse_id,
                    'product_id' => $productId,
                    'adjustment_date' => $selectedDate,
                    'adjustment_type' => $difference > 0 ? 'incremento' : 'disminucion',
                    'quantity' => abs($difference),
                    'reason' => 'Diferencia detectada en pre-cierre del mes '.$this->selected_month.'/'.$this->selected_year,
                    'previous_quantity' => $systemStock,
                    'new_quantity' => (float) $physicalCount,
                    'status' => 'pendiente',
                    'created_by' => auth()->id(),
                ]);
                $adjustmentsCount++;
            }
        }

        session()->flash('success', "Se generaron {$adjustmentsCount} ajustes de inventario. Revise la sección de Ajustes de Inventario para aprobarlos.");
    }

    public function exportPdf(): void
    {
        session()->flash('info', 'Exportación a PDF - próximamente disponible');
    }

    public function exportExcel(): void
    {
        session()->flash('info', 'Exportación a Excel - próximamente disponible');
    }

    private function getWarehouses()
    {
        return Warehouse::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function getMonths(): array
    {
        return [
            '01' => 'Enero',
            '02' => 'Febrero',
            '03' => 'Marzo',
            '04' => 'Abril',
            '05' => 'Mayo',
            '06' => 'Junio',
            '07' => 'Julio',
            '08' => 'Agosto',
            '09' => 'Septiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre',
        ];
    }

    private function getYears(): array
    {
        $currentYear = now()->year;

        return range($currentYear - 2, $currentYear + 1);
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Reporte de Diferencias Pre-Cierre</flux:heading>
            <flux:text class="mt-1">Comparación entre inventario del sistema y conteo físico</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('reports.administrative') }}" wire:navigate>
                Volver
            </flux:button>
        </div>
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

    @if (session('info'))
        <flux:callout variant="info" icon="information-circle">
            {{ session('info') }}
        </flux:callout>
    @endif

    {{-- Filters --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Parámetros de Consulta</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <flux:field>
                <flux:label badge="Requerido">Bodega</flux:label>
                <flux:select wire:model.live="warehouse_id" placeholder="Seleccione una bodega">
                    <option value="">Seleccione...</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label badge="Requerido">Mes</flux:label>
                <flux:select wire:model.live="selected_month">
                    @foreach ($months as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label badge="Requerido">Año</flux:label>
                <flux:select wire:model.live="selected_year">
                    @foreach ($years as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Mostrar Solo Diferencias</flux:label>
                <flux:switch wire:model.live="show_differences_only" />
            </flux:field>

            <div class="flex items-end gap-2">
                <flux:button wire:click="exportPdf" variant="ghost" icon="document-arrow-down" size="sm">
                    PDF
                </flux:button>
                <flux:button wire:click="exportExcel" variant="ghost" icon="document-arrow-down" size="sm">
                    Excel
                </flux:button>
            </div>
        </div>
    </flux:card>

    @if ($warehouse_id && $selected_month && $selected_year)
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <flux:card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <flux:icon name="cube" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Total Productos</flux:text>
                        <flux:heading size="lg">{{ number_format($totals['total_products']) }}</flux:heading>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <flux:icon name="arrow-trending-up" class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Diferencias Positivas</flux:text>
                        <flux:heading size="lg">{{ number_format($totals['positive_differences']) }}</flux:heading>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                        <flux:icon name="arrow-trending-down" class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Diferencias Negativas</flux:text>
                        <flux:heading size="lg">{{ number_format($totals['negative_differences']) }}</flux:heading>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center gap-4">
                    <div
                        class="p-3 {{ $totals['total_difference_value'] >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900' }} rounded-lg"
                    >
                        <flux:icon
                            name="currency-dollar"
                            class="w-6 h-6 {{ $totals['total_difference_value'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                        />
                    </div>
                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Diferencia Valor</flux:text>
                        <flux:heading
                            size="lg"
                            class="{{ $totals['total_difference_value'] >= 0 ? 'text-green-600' : 'text-red-600' }}"
                        >
                            ${{ number_format($totals['total_difference_value'], 2) }}
                        </flux:heading>
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Adjustment Actions --}}
        @if ($inventoryData->whereNotNull('physical_count')->where('difference', '!=', 0)->count() > 0)
            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">Generar Ajustes</flux:heading>
                        <flux:text class="mt-1">
                            Se detectaron {{ $inventoryData->whereNotNull('physical_count')->where('difference', '!=', 0)->count() }}
                            productos con diferencias. Puede generar ajustes automáticos para corregir el inventario.
                        </flux:text>
                    </div>
                    <flux:button wire:click="generateAdjustments" variant="primary" icon="document-plus">
                        Generar Ajustes de Inventario
                    </flux:button>
                </div>
            </flux:card>
        @endif

        {{-- Detailed Table --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">
                Comparación de Inventario - {{ $months[$selected_month] }} {{ $selected_year }}
            </flux:heading>

            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Producto</flux:table.column>
                        <flux:table.column>Categoría</flux:table.column>
                        <flux:table.column>Stock Sistema</flux:table.column>
                        <flux:table.column>Valor Sistema</flux:table.column>
                        <flux:table.column>Conteo Físico</flux:table.column>
                        <flux:table.column>Valor Físico</flux:table.column>
                        <flux:table.column>Diferencia</flux:table.column>
                        <flux:table.column>Diferencia Valor</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($inventoryData as $data)
                            <flux:table.row :key="$data['product_id']">
                                <flux:table.cell>
                                    <div class="font-medium">{{ $data['product']->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $data['product']->sku }}</div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge variant="gray" size="sm">
                                        {{ $data['category']->name ?? 'Sin categoría' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ number_format($data['system_stock'], 2) }} {{ $data['product']->unit_of_measure }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    ${{ number_format($data['system_value'], 2) }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:input
                                        type="number"
                                        step="0.01"
                                        wire:model.live.debounce.500ms="physical_counts.{{ $data['product_id'] }}"
                                        placeholder="Ingrese conteo"
                                        size="sm"
                                    />
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($data['physical_value'] !== null)
                                        ${{ number_format($data['physical_value'], 2) }}
                                    @else
                                        <flux:text class="text-sm text-gray-400">-</flux:text>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($data['difference'] !== null)
                                        <div
                                            class="font-semibold {{ $data['difference'] > 0 ? 'text-green-600' : ($data['difference'] < 0 ? 'text-red-600' : 'text-gray-600') }}"
                                        >
                                            {{ $data['difference'] > 0 ? '+' : '' }}{{ number_format($data['difference'], 2) }}
                                            {{ $data['product']->unit_of_measure }}
                                        </div>
                                    @else
                                        <flux:text class="text-sm text-gray-400">-</flux:text>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($data['difference_value'] !== null)
                                        <div
                                            class="font-semibold {{ $data['difference_value'] > 0 ? 'text-green-600' : ($data['difference_value'] < 0 ? 'text-red-600' : 'text-gray-600') }}"
                                        >
                                            {{ $data['difference_value'] > 0 ? '+' : '' }}${{ number_format($data['difference_value'], 2) }}
                                        </div>
                                    @else
                                        <flux:text class="text-sm text-gray-400">-</flux:text>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="8" class="text-center text-gray-500">
                                    No se encontró inventario para los parámetros seleccionados
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>

        {{-- Instructions --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Instrucciones</flux:heading>
            <div class="space-y-2">
                <flux:text>1. Seleccione la bodega, mes y año para el cual desea realizar el pre-cierre.</flux:text>
                <flux:text>2. El sistema mostrará el stock según los registros del sistema a la fecha de cierre del mes seleccionado.</flux:text>
                <flux:text>3. Ingrese el conteo físico real para cada producto en la columna "Conteo Físico".</flux:text>
                <flux:text>
                    4. El sistema calculará automáticamente las diferencias y su valor monetario.
                </flux:text>
                <flux:text>
                    5. Use el botón "Generar Ajustes de Inventario" para crear ajustes automáticos que corrijan las
                    diferencias encontradas.
                </flux:text>
                <flux:text>
                    6. Los ajustes se crearán con estado "Pendiente" y deberán ser aprobados en la sección de Ajustes de
                    Inventario.
                </flux:text>
            </div>
        </flux:card>
    @else
        <flux:card>
            <div class="text-center py-12">
                <flux:icon name="clipboard-document-list" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                <flux:heading size="lg" class="mb-2">Seleccione los Parámetros</flux:heading>
                <flux:text>
                    Seleccione una bodega, mes y año para visualizar el inventario del sistema y realizar el conteo físico.
                </flux:text>
            </div>
        </flux:card>
    @endif
</div>
