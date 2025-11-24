<?php

use App\Models\Dispatch;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public $start_date = '';

    public $end_date = '';

    public $warehouse_id = '';

    public $category_id = '';

    public function mount(): void
    {
        // Default to current month
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
    }

    public function with(): array
    {
        // Get internal use dispatches
        $query = Dispatch::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('is_internal_use', true)
            ->whereIn('status', ['aprobado', 'despachado', 'entregado'])
            ->with(['warehouse:id,name', 'details.product.category']);

        // Apply filters
        if ($this->start_date) {
            $query->where('document_date', '>=', $this->start_date);
        }

        if ($this->end_date) {
            $query->where('document_date', '<=', $this->end_date);
        }

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        $dispatches = $query->latest('document_date')->get();

        // Group by product with category filter
        $consumptionData = collect();
        foreach ($dispatches as $dispatch) {
            foreach ($dispatch->details as $detail) {
                // Apply category filter if set
                if ($this->category_id && $detail->product->category_id != $this->category_id) {
                    continue;
                }

                $key = $detail->product_id;
                if (! $consumptionData->has($key)) {
                    $consumptionData[$key] = [
                        'product' => $detail->product,
                        'category' => $detail->product->category,
                        'quantity' => 0,
                        'total_value' => 0,
                        'dispatch_count' => 0,
                        'reasons' => collect(),
                    ];
                }

                $consumptionData[$key]['quantity'] += $detail->quantity;
                $consumptionData[$key]['total_value'] += $detail->total;
                $consumptionData[$key]['dispatch_count']++;

                if ($dispatch->internal_use_reason) {
                    $consumptionData[$key]['reasons']->push($dispatch->internal_use_reason);
                }
            }
        }

        $consumptionData = $consumptionData->sortByDesc('total_value');

        // Calculate monthly trend (last 6 months)
        $monthlyTrend = Dispatch::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('is_internal_use', true)
            ->whereIn('status', ['aprobado', 'despachado', 'entregado'])
            ->where('document_date', '>=', now()->subMonths(6)->startOfMonth())
            ->select([
                DB::raw('DATE_FORMAT(document_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as dispatch_count'),
                DB::raw('SUM(total) as total_value'),
            ])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Calculate totals
        $totals = [
            'total_dispatches' => $dispatches->count(),
            'total_products' => $consumptionData->count(),
            'total_value' => $consumptionData->sum('total_value'),
            'total_quantity' => $consumptionData->sum('quantity'),
        ];

        return [
            'consumptionData' => $consumptionData,
            'monthlyTrend' => $monthlyTrend,
            'totals' => $totals,
            'warehouses' => Warehouse::where('company_id', auth()->user()->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'categories' => ProductCategory::where('company_id', auth()->user()->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ];
    }

    public function exportPdf(): void
    {
        session()->flash('info', 'Exportación a PDF - próximamente disponible');
    }

    public function exportExcel(): void
    {
        session()->flash('info', 'Exportación a Excel - próximamente disponible');
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Reporte de Autoconsumo</flux:heading>
            <flux:text class="mt-1">Análisis de productos consumidos internamente por la empresa</flux:text>
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

    @if (session('info'))
        <flux:callout variant="info" icon="information-circle">
            {{ session('info') }}
        </flux:callout>
    @endif

    {{-- Filters --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Filtros</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <flux:field>
                <flux:label>Fecha Inicio</flux:label>
                <flux:input type="date" wire:model.live="start_date" />
            </flux:field>

            <flux:field>
                <flux:label>Fecha Fin</flux:label>
                <flux:input type="date" wire:model.live="end_date" />
            </flux:field>

            <flux:field>
                <flux:label>Bodega</flux:label>
                <flux:select wire:model.live="warehouse_id" placeholder="Todas las bodegas">
                    <option value="">Todas</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Categoría</flux:label>
                <flux:select wire:model.live="category_id" placeholder="Todas las categorías">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="flex items-end gap-2 col-span-2">
                <flux:button wire:click="exportPdf" variant="ghost" icon="document-arrow-down" size="sm">
                    PDF
                </flux:button>
                <flux:button wire:click="exportExcel" variant="ghost" icon="document-arrow-down" size="sm">
                    Excel
                </flux:button>
            </div>
        </div>
    </flux:card>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <flux:icon name="document-text" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Total Despachos</flux:text>
                    <flux:heading size="lg">{{ number_format($totals['total_dispatches']) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <flux:icon name="cube" class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Productos Diferentes</flux:text>
                    <flux:heading size="lg">{{ number_format($totals['total_products']) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <flux:icon name="chart-bar" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Cantidad Total</flux:text>
                    <flux:heading size="lg">{{ number_format($totals['total_quantity'], 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                    <flux:icon name="currency-dollar" class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Valor Total</flux:text>
                    <flux:heading size="lg">${{ number_format($totals['total_value'], 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Monthly Trend Chart --}}
    @if ($monthlyTrend->count() > 0)
        <flux:card>
            <flux:heading size="lg" class="mb-4">Tendencia de Autoconsumo (Últimos 6 Meses)</flux:heading>

            <div class="space-y-3">
                @foreach ($monthlyTrend as $month)
                    @php
                        $monthLabel = \Carbon\Carbon::parse($month->month.'-01')->format('M Y');
                        $percentage = $monthlyTrend->max('total_value') > 0
                            ? ($month->total_value / $monthlyTrend->max('total_value')) * 100
                            : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <flux:text class="text-sm font-medium">
                                {{ $monthLabel }} ({{ $month->dispatch_count }} despachos)
                            </flux:text>
                            <flux:text class="text-sm font-semibold">
                                ${{ number_format($month->total_value, 2) }}
                            </flux:text>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div
                                class="bg-red-600 dark:bg-red-500 h-2 rounded-full"
                                style="width: {{ $percentage }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    {{-- Detailed Table --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Detalle de Autoconsumo por Producto</flux:heading>

        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>#</flux:table.column>
                    <flux:table.column>Producto</flux:table.column>
                    <flux:table.column>Categoría</flux:table.column>
                    <flux:table.column>Cantidad Consumida</flux:table.column>
                    <flux:table.column>Valor Total</flux:table.column>
                    <flux:table.column>Despachos</flux:table.column>
                    <flux:table.column>Motivos</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($consumptionData as $index => $data)
                        <flux:table.row :key="$data['product']->id">
                            <flux:table.cell>{{ $index + 1 }}</flux:table.cell>

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
                                {{ number_format($data['quantity'], 4) }} {{ $data['product']->unit_of_measure }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="font-semibold">${{ number_format($data['total_value'], 2) }}</div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge variant="info">{{ $data['dispatch_count'] }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($data['reasons']->isNotEmpty())
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        @foreach ($data['reasons']->unique()->take(3) as $reason)
                                            <div>• {{ $reason }}</div>
                                        @endforeach
                                        @if ($data['reasons']->unique()->count() > 3)
                                            <div class="text-xs text-gray-500">+{{ $data['reasons']->unique()->count() - 3 }} más</div>
                                        @endif
                                    </div>
                                @else
                                    <flux:text class="text-sm text-gray-400">Sin motivo especificado</flux:text>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center text-gray-500">
                                No se encontró autoconsumo en el período seleccionado
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>
