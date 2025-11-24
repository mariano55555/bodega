<?php

use App\Models\Donation;
use App\Models\Donor;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public $start_date = '';

    public $end_date = '';

    public $donor_id = '';

    public $category_id = '';

    public $warehouse_id = '';

    public function mount(): void
    {
        // Default to current year
        $this->start_date = now()->startOfYear()->format('Y-m-d');
        $this->end_date = now()->endOfYear()->format('Y-m-d');
    }

    public function with(): array
    {
        // Get donations with details
        $query = Donation::query()
            ->where('company_id', auth()->user()->company_id)
            ->whereIn('status', ['aprobado', 'recibido'])
            ->with(['donor:id,name,document_number', 'warehouse:id,name', 'details.product.category']);

        // Apply filters
        if ($this->start_date) {
            $query->where('document_date', '>=', $this->start_date);
        }

        if ($this->end_date) {
            $query->where('document_date', '<=', $this->end_date);
        }

        if ($this->donor_id) {
            $query->where('donor_id', $this->donor_id);
        }

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        $donations = $query->latest('document_date')->get();

        // Group by donor
        $donorData = collect();
        foreach ($donations as $donation) {
            $donorKey = $donation->donor_id ?? 'anonymous';
            $donorName = $donation->donor ? $donation->donor->name : $donation->donor_name;

            if (! $donorData->has($donorKey)) {
                $donorData[$donorKey] = [
                    'donor' => $donation->donor,
                    'donor_name' => $donorName,
                    'donation_count' => 0,
                    'total_value' => 0,
                    'products' => collect(),
                    'categories' => collect(),
                ];
            }

            $donorData[$donorKey]['donation_count']++;
            $donorData[$donorKey]['total_value'] += $donation->estimated_value ?? 0;

            // Group products
            foreach ($donation->details as $detail) {
                // Apply category filter if set
                if ($this->category_id && $detail->product->category_id != $this->category_id) {
                    continue;
                }

                $productKey = $detail->product_id;
                if (! $donorData[$donorKey]['products']->has($productKey)) {
                    $donorData[$donorKey]['products'][$productKey] = [
                        'product' => $detail->product,
                        'quantity' => 0,
                        'value' => 0,
                    ];
                }

                $donorData[$donorKey]['products'][$productKey]['quantity'] += $detail->quantity;
                $donorData[$donorKey]['products'][$productKey]['value'] += $detail->total ?? 0;

                // Track categories
                if ($detail->product->category) {
                    $donorData[$donorKey]['categories']->push($detail->product->category->name);
                }
            }
        }

        $donorData = $donorData->sortByDesc('total_value');

        // Group by category
        $categoryData = collect();
        foreach ($donations as $donation) {
            foreach ($donation->details as $detail) {
                // Apply category filter if set
                if ($this->category_id && $detail->product->category_id != $this->category_id) {
                    continue;
                }

                $categoryKey = $detail->product->category_id ?? 'uncategorized';
                $categoryName = $detail->product->category->name ?? 'Sin categoría';

                if (! $categoryData->has($categoryKey)) {
                    $categoryData[$categoryKey] = [
                        'name' => $categoryName,
                        'total_value' => 0,
                        'total_quantity' => 0,
                    ];
                }

                $categoryData[$categoryKey]['total_value'] += $detail->total ?? 0;
                $categoryData[$categoryKey]['total_quantity'] += $detail->quantity;
            }
        }

        // Monthly trend (last 12 months)
        $monthlyTrend = Donation::query()
            ->where('company_id', auth()->user()->company_id)
            ->whereIn('status', ['aprobado', 'recibido'])
            ->where('document_date', '>=', now()->subMonths(12)->startOfMonth())
            ->select([
                DB::raw('DATE_FORMAT(document_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as donation_count'),
                DB::raw('SUM(estimated_value) as total_value'),
            ])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Calculate totals
        $totals = [
            'total_donations' => $donations->count(),
            'total_donors' => $donorData->count(),
            'total_value' => $donorData->sum('total_value'),
            'average_donation' => $donations->count() > 0 ? $donorData->sum('total_value') / $donations->count() : 0,
        ];

        return [
            'donorData' => $donorData,
            'categoryData' => $categoryData->sortByDesc('total_value'),
            'monthlyTrend' => $monthlyTrend,
            'totals' => $totals,
            'donors' => Donor::where('company_id', auth()->user()->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
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
            <flux:heading size="xl">Reporte Consolidado de Donaciones</flux:heading>
            <flux:text class="mt-1">Análisis detallado de todas las donaciones recibidas</flux:text>
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
                <flux:label>Donante</flux:label>
                <flux:select wire:model.live="donor_id" placeholder="Todos los donantes">
                    <option value="">Todos</option>
                    @foreach ($donors as $donor)
                        <option value="{{ $donor->id }}">{{ $donor->name }}</option>
                    @endforeach
                </flux:select>
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

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <flux:icon name="gift" class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Total Donaciones</flux:text>
                    <flux:heading size="lg">{{ number_format($totals['total_donations']) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <flux:icon name="users" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Total Donantes</flux:text>
                    <flux:heading size="lg">{{ number_format($totals['total_donors']) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <flux:icon name="currency-dollar" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Valor Total Estimado</flux:text>
                    <flux:heading size="lg">${{ number_format($totals['total_value'], 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <flux:icon name="chart-bar" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Promedio por Donación</flux:text>
                    <flux:heading size="lg">${{ number_format($totals['average_donation'], 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Monthly Trend --}}
    @if ($monthlyTrend->count() > 0)
        <flux:card>
            <flux:heading size="lg" class="mb-4">Tendencia Mensual (Últimos 12 Meses)</flux:heading>

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
                                {{ $monthLabel }} ({{ $month->donation_count }} donaciones)
                            </flux:text>
                            <flux:text class="text-sm font-semibold">
                                ${{ number_format($month->total_value, 2) }}
                            </flux:text>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div
                                class="bg-green-600 dark:bg-green-500 h-2 rounded-full"
                                style="width: {{ $percentage }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    {{-- Donations by Category --}}
    @if ($categoryData->count() > 0)
        <flux:card>
            <flux:heading size="lg" class="mb-4">Donaciones por Categoría</flux:heading>

            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>#</flux:table.column>
                        <flux:table.column>Categoría</flux:table.column>
                        <flux:table.column>Cantidad Total</flux:table.column>
                        <flux:table.column>Valor Estimado</flux:table.column>
                        <flux:table.column>% del Total</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($categoryData as $index => $data)
                            <flux:table.row :key="$index">
                                <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge variant="info">{{ $data['name'] }}</flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ number_format($data['total_quantity'], 2) }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="font-semibold">${{ number_format($data['total_value'], 2) }}</div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @php
                                        $percentage = $totals['total_value'] > 0
                                            ? ($data['total_value'] / $totals['total_value']) * 100
                                            : 0;
                                    @endphp
                                    {{ number_format($percentage, 1) }}%
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>
    @endif

    {{-- Donations by Donor --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Donaciones por Donante</flux:heading>

        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>#</flux:table.column>
                    <flux:table.column>Donante</flux:table.column>
                    <flux:table.column>Donaciones</flux:table.column>
                    <flux:table.column>Categorías</flux:table.column>
                    <flux:table.column>Productos</flux:table.column>
                    <flux:table.column>Valor Estimado</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($donorData as $index => $data)
                        <flux:table.row :key="$index">
                            <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>

                            <flux:table.cell>
                                <div class="font-medium">{{ $data['donor_name'] }}</div>
                                @if ($data['donor'])
                                    <div class="text-sm text-gray-500">{{ $data['donor']->document_number ?? '' }}</div>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge variant="info">{{ $data['donation_count'] }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $data['categories']->unique()->count() }} categorías
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $data['products']->count() }} productos
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="font-semibold">${{ number_format($data['total_value'], 2) }}</div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-gray-500">
                                No se encontraron donaciones en el período seleccionado
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>
