<?php

use App\Models\Company;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Warehouse;
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

    #[Url(as: 'bodega')]
    public $warehouse_id = '';

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');

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
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        $query = DispatchDetail::query()
            ->select([
                'dispatch_details.*',
                'dispatches.document_date',
                'dispatches.physical_document_number',
                'dispatches.warehouse_id',
                'dispatches.area_id',
                'products.name as product_name',
                'products.sku',
                'products.category_id',
                'units_of_measure.abbreviation as unit_abbreviation',
                'units_of_measure.name as unit_name',
                'warehouses.name as warehouse_name',
                'areas.name as area_name',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'parent_categories.name as parent_category_name',
                'parent_categories.legacy_code as parent_category_code',
            ])
            ->join('dispatches', 'dispatch_details.dispatch_id', '=', 'dispatches.id')
            ->join('products', 'dispatch_details.product_id', '=', 'products.id')
            ->leftJoin('units_of_measure', 'dispatch_details.unit_of_measure_id', '=', 'units_of_measure.id')
            ->join('warehouses', 'dispatches.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('areas', 'dispatches.area_id', '=', 'areas.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('dispatches.company_id', $this->effectiveCompanyId)
            ->whereIn('dispatches.status', ['despachado', 'entregado']);

        if ($this->start_date) {
            $query->where('dispatches.document_date', '>=', $this->start_date);
        }

        if ($this->end_date) {
            $query->where('dispatches.document_date', '<=', $this->end_date);
        }

        if ($this->warehouse_id) {
            $query->where('dispatches.warehouse_id', $this->warehouse_id);
        }

        return $query
            ->orderBy('warehouses.name')
            ->orderBy('dispatches.document_date')
            ->get();
    }

    #[Computed]
    public function groupedByWarehouse()
    {
        return $this->reportData->groupBy('warehouse_name')->map(function ($items, $warehouseName) {
            return (object) [
                'warehouse_name' => $warehouseName ?: 'Sin Bodega',
                'items' => $items,
                'total_quantity' => $items->sum('quantity'),
                'total_value' => $items->sum('total'),
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
            'total_value' => $data->sum('total'),
            'total_warehouses' => $data->pluck('warehouse_id')->unique()->count(),
        ];
    }

    public function exportPdf()
    {
        $params = [
            'empresa' => $this->effectiveCompanyId,
            'inicio' => $this->start_date,
            'fin' => $this->end_date,
        ];

        if ($this->warehouse_id) {
            $params['bodega'] = $this->warehouse_id;
        }

        return $this->redirect(route('reports.dispatches.monthly.pdf', $params));
    }

    public function exportExcel()
    {
        $params = [
            'empresa' => $this->effectiveCompanyId,
            'inicio' => $this->start_date,
            'fin' => $this->end_date,
        ];

        if ($this->warehouse_id) {
            $params['bodega'] = $this->warehouse_id;
        }

        return $this->redirect(route('reports.dispatches.monthly.excel', $params));
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Reporte Mensual de Salidas de Bodega</flux:heading>
            <flux:text class="mt-1">Detalle de despachos agrupados por bodega</flux:text>
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
                <flux:label>Fecha Inicio</flux:label>
                <flux:input type="date" wire:model.live="start_date" />
            </flux:field>

            <flux:field>
                <flux:label>Fecha Fin</flux:label>
                <flux:input type="date" wire:model.live="end_date" />
            </flux:field>

            <flux:field>
                <flux:label>Bodega</flux:label>
                <flux:select wire:model.live="warehouse_id">
                    <flux:select.option value="">-- Todas las bodegas --</flux:select.option>
                    @foreach ($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
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
                    Seleccione una empresa para ver el reporte de salidas
                </flux:text>
            </div>
        </flux:card>
    @else
        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <flux:card class="text-center">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Total Bodegas</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_warehouses']) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Total Artículos</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_items']) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Cantidad Total</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_quantity'], 2) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center bg-orange-50 dark:bg-orange-900/20">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Valor Total</flux:text>
                <flux:heading size="xl" class="text-orange-600 dark:text-orange-400">
                    ${{ number_format($this->totals['total_value'], 2) }}
                </flux:heading>
            </flux:card>
        </div>

        {{-- Grouped Data by Warehouse --}}
        @forelse ($this->groupedByWarehouse as $warehouseName => $group)
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                            <flux:icon name="building-office" class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                        </div>
                        <flux:heading size="lg">{{ $group->warehouse_name }}</flux:heading>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Fecha</flux:table.column>
                            <flux:table.column>N° Documento</flux:table.column>
                            <flux:table.column>Área Solicitante</flux:table.column>
                            <flux:table.column>Línea Presup.</flux:table.column>
                            <flux:table.column>Específico</flux:table.column>
                            <flux:table.column>Descripción</flux:table.column>
                            <flux:table.column>Unidad</flux:table.column>
                            <flux:table.column class="text-right">Cantidad</flux:table.column>
                            <flux:table.column class="text-right">Valor</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($group->items as $item)
                                <flux:table.row :key="$item->id">
                                    <flux:table.cell>
                                        {{ \Carbon\Carbon::parse($item->document_date)->format('d/m/Y') }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:badge>{{ $item->physical_document_number ?? '-' }}</flux:badge>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $item->area_name ?? '-' }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $item->parent_category_name ?? $item->category_name ?? '-' }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $item->category_code ?? '-' }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <div class="font-medium">{{ $item->product_name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $item->sku }}</div>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $item->unit_abbreviation ?? $item->unit_name ?? '-' }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right">
                                        {{ number_format($item->quantity, 2) }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right font-medium">
                                        ${{ number_format($item->total, 2) }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Subtotals for this warehouse --}}
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-end gap-8">
                        <div class="text-right">
                            <flux:text class="text-sm text-gray-500">Cantidad Mensual</flux:text>
                            <flux:heading size="md">{{ number_format($group->total_quantity, 2) }}</flux:heading>
                        </div>
                        <div class="text-right">
                            <flux:text class="text-sm text-gray-500">Valor Mensual</flux:text>
                            <flux:heading size="md">${{ number_format($group->total_value, 2) }}</flux:heading>
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
                        No se encontraron salidas en el período seleccionado
                    </flux:text>
                </div>
            </flux:card>
        @endforelse

        {{-- Grand Total --}}
        @if ($this->groupedByWarehouse->count() > 0)
            <flux:card class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/30 dark:to-amber-900/30 border-orange-200 dark:border-orange-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-xl">
                            <flux:icon name="calculator" class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <flux:heading size="lg">Total Mensual</flux:heading>
                    </div>
                    <div class="flex items-center gap-8">
                        <div class="text-right">
                            <flux:text class="text-sm text-gray-500">CANTIDAD</flux:text>
                            <flux:heading size="lg">{{ number_format($this->totals['total_quantity'], 2) }}</flux:heading>
                        </div>
                        <div class="text-right">
                            <flux:text class="text-sm text-gray-500">VALOR</flux:text>
                            <flux:heading size="xl" class="text-orange-600 dark:text-orange-400">
                                ${{ number_format($this->totals['total_value'], 2) }}
                            </flux:heading>
                        </div>
                    </div>
                </div>
            </flux:card>
        @endif
    @endif
</div>
