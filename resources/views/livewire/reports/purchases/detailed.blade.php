<?php

use App\Models\Company;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Url(as: 'empresa')]
    public $company_id = '';

    #[Url(as: 'inicio')]
    public $start_date = '';

    #[Url(as: 'fin')]
    public $end_date = '';

    #[Url(as: 'linea')]
    public $category_id = '';

    #[Url(as: 'proveedor')]
    public $supplier_id = '';

    public function mount(): void
    {
        // Default to current month
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');

        // Set company_id for non-super-admin users
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }
    }

    public function updatedCompanyId(): void
    {
        $this->supplier_id = '';
        $this->category_id = '';
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
    public function suppliers()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        return Supplier::where('company_id', $this->effectiveCompanyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function categories()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        return ProductCategory::where('company_id', $this->effectiveCompanyId)
            ->whereNotNull('parent_id')
            ->where('is_active', true)
            ->with('parent')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function reportData()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        $query = PurchaseDetail::query()
            ->select([
                'purchase_details.*',
                'purchases.document_date',
                'purchases.document_number',
                'purchases.supplier_id',
                'products.name as product_name',
                'products.category_id',
                'units_of_measure.abbreviation as unit_abbreviation',
                'units_of_measure.name as unit_name',
                'suppliers.name as supplier_name',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'product_categories.parent_id',
                'parent_categories.name as parent_category_name',
                'parent_categories.legacy_code as parent_category_code',
            ])
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->join('products', 'purchase_details.product_id', '=', 'products.id')
            ->leftJoin('units_of_measure', 'products.unit_of_measure_id', '=', 'units_of_measure.id')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('purchases.company_id', $this->effectiveCompanyId)
            ->whereIn('purchases.status', ['aprobado', 'recibido']);

        if ($this->start_date) {
            $query->where('purchases.document_date', '>=', $this->start_date);
        }

        if ($this->end_date) {
            $query->where('purchases.document_date', '<=', $this->end_date);
        }

        if ($this->category_id) {
            $query->where('products.category_id', $this->category_id);
        }

        if ($this->supplier_id) {
            $query->where('purchases.supplier_id', $this->supplier_id);
        }

        return $query
            ->orderBy('category_name')
            ->orderBy('supplier_name')
            ->orderBy('purchases.document_date')
            ->get();
    }

    #[Computed]
    public function groupedData()
    {
        return $this->reportData->groupBy('category_name')->map(function ($items, $categoryName) {
            $firstItem = $items->first();
            return (object) [
                'category_name' => $categoryName ?: 'Sin Línea Presupuestaria',
                'category_code' => $firstItem->category_code ?? '',
                'parent_name' => $firstItem->parent_category_name ?? 'Sin Categoría',
                'parent_code' => $firstItem->parent_category_code ?? '',
                'items' => $items,
                'subtotal' => $items->sum('total'),
                'by_supplier' => $items->groupBy('supplier_name')->map(fn($s) => $s->sum('total')),
            ];
        });
    }

    #[Computed]
    public function totals(): array
    {
        $data = $this->reportData;

        return [
            'total_items' => $data->count(),
            'total_quantity' => $data->sum('quantity'),
            'total_amount' => $data->sum('total'),
            'total_categories' => $data->pluck('category_id')->unique()->count(),
            'total_suppliers' => $data->pluck('supplier_id')->unique()->count(),
        ];
    }

    public function exportPdf()
    {
        $params = [
            'empresa' => $this->effectiveCompanyId,
            'inicio' => $this->start_date,
            'fin' => $this->end_date,
        ];

        if ($this->category_id) {
            $params['linea'] = $this->category_id;
        }

        if ($this->supplier_id) {
            $params['proveedor'] = $this->supplier_id;
        }

        return $this->redirect(route('reports.purchases.detailed.pdf', $params));
    }

    public function exportExcel()
    {
        $params = [
            'empresa' => $this->effectiveCompanyId,
            'inicio' => $this->start_date,
            'fin' => $this->end_date,
        ];

        if ($this->category_id) {
            $params['linea'] = $this->category_id;
        }

        if ($this->supplier_id) {
            $params['proveedor'] = $this->supplier_id;
        }

        return $this->redirect(route('reports.purchases.detailed.excel', $params));
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Reporte Mensual de Compras Detalladas</flux:heading>
            <flux:text class="mt-1">Detalle de compras por línea presupuestaria y proveedor</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('reports.purchases.hub') }}" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Filters --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Filtros</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            @if ($this->isSuperAdmin)
                <flux:field class="lg:col-span-6">
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
                <flux:label>Fecha Inicio</flux:label>
                <flux:input type="date" wire:model.live="start_date" />
            </flux:field>

            <flux:field>
                <flux:label>Fecha Fin</flux:label>
                <flux:input type="date" wire:model.live="end_date" />
            </flux:field>

            <flux:field class="lg:col-span-2">
                <flux:label>Línea Presupuestaria</flux:label>
                <flux:select wire:model.live="category_id">
                    <flux:select.option value="">-- Todas las líneas --</flux:select.option>
                    @foreach ($this->categories as $category)
                        <flux:select.option value="{{ $category->id }}">
                            {{ $category->legacy_code }} - {{ $category->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Proveedor</flux:label>
                <flux:select wire:model.live="supplier_id">
                    <flux:select.option value="">-- Todos --</flux:select.option>
                    @foreach ($this->suppliers as $supplier)
                        <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="flex items-end gap-2">
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
                    Seleccione una empresa para ver el reporte de compras detalladas
                </flux:text>
            </div>
        </flux:card>
    @else
        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <flux:card class="text-center">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Total Líneas</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_categories']) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Total Proveedores</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_suppliers']) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Total Artículos</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_items']) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Cantidad Total</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_quantity'], 2) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center bg-blue-50 dark:bg-blue-900/20">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Total de Compras</flux:text>
                <flux:heading size="xl" class="text-blue-600 dark:text-blue-400">
                    ${{ number_format($this->totals['total_amount'], 2) }}
                </flux:heading>
            </flux:card>
        </div>

        {{-- Grouped Data Table --}}
        @forelse ($this->groupedData as $categoryName => $group)
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <flux:heading size="lg">{{ $group->category_name }}</flux:heading>
                        <flux:text class="text-sm">
                            Código: {{ $group->category_code }} |
                            Categoría: {{ $group->parent_name }} ({{ $group->parent_code }})
                        </flux:text>
                    </div>
                    <flux:badge variant="info" size="lg">
                        Subtotal: ${{ number_format($group->subtotal, 2) }}
                    </flux:badge>
                </div>

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Fecha</flux:table.column>
                            <flux:table.column>Proveedor</flux:table.column>
                            <flux:table.column>No. Factura</flux:table.column>
                            <flux:table.column>Descripción</flux:table.column>
                            <flux:table.column>Unidad</flux:table.column>
                            <flux:table.column class="text-right">Cantidad</flux:table.column>
                            <flux:table.column class="text-right">P. Unitario</flux:table.column>
                            <flux:table.column class="text-right">Valor Neto</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($group->items as $item)
                                <flux:table.row :key="$item->id">
                                    <flux:table.cell>
                                        {{ \Carbon\Carbon::parse($item->document_date)->format('d/m/Y') }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <div class="font-medium">{{ $item->supplier_name }}</div>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $item->document_number ?? '-' }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $item->product_name }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $item->unit_abbreviation ?? $item->unit_name ?? '-' }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right">
                                        {{ number_format($item->quantity, 2) }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right">
                                        ${{ number_format($item->unit_cost, 2) }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right font-medium">
                                        ${{ number_format($item->total, 2) }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Subtotals by supplier within this category --}}
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-wrap gap-4 text-sm">
                        @foreach ($group->by_supplier as $supplierName => $supplierTotal)
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">{{ $supplierName }}:</span>
                                <span class="font-semibold">${{ number_format($supplierTotal, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card>
                <div class="py-12 text-center">
                    <flux:icon.document-magnifying-glass class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mt-4">Sin Resultados</flux:heading>
                    <flux:text class="mt-2">
                        No se encontraron compras en el período seleccionado
                    </flux:text>
                </div>
            </flux:card>
        @endforelse

        {{-- Grand Total --}}
        @if ($this->groupedData->count() > 0)
            <flux:card class="bg-gray-50 dark:bg-gray-800/50">
                <div class="flex items-center justify-end gap-8">
                    <div class="text-right">
                        <flux:text class="text-sm text-gray-500">Total de compras</flux:text>
                        <flux:heading size="xl">${{ number_format($this->totals['total_amount'], 2) }}</flux:heading>
                    </div>
                </div>
            </flux:card>
        @endif
    @endif
</div>
