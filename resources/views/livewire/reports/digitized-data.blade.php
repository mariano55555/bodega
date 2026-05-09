<?php

use App\Models\Company;
use App\Models\Dispatch;
use App\Models\InternalProduction;
use App\Models\InventoryTransfer;
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

    #[Url(as: 'tipo')]
    public string $type = 'dispatches';

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
    public function reportTypes(): array
    {
        return [
            'dispatches' => 'Despachos',
            'internal-productions' => 'Producción Interna',
            'transfers' => 'Traslados',
        ];
    }

    #[Computed]
    public function typeLabel(): string
    {
        return $this->reportTypes[$this->type] ?? 'Despachos';
    }

    #[Computed]
    public function vouchers()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        return match ($this->type) {
            'internal-productions' => $this->loadInternalProductions(),
            'transfers' => $this->loadTransfers(),
            default => $this->loadDispatches(),
        };
    }

    protected function loadDispatches()
    {
        $query = Dispatch::query()
            ->with([
                'area:id,name',
                'warehouse:id,name',
                'details.product:id,name,sku',
                'details.unitOfMeasure:id,name,abbreviation',
            ])
            ->where('company_id', $this->effectiveCompanyId)
            ->whereNotIn('status', ['anulado', 'cancelado'])
            ->whereBetween('document_date', [$this->start_date, $this->end_date]);

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        return $query
            ->orderBy('warehouse_id')
            ->orderBy('document_date')
            ->orderBy('id')
            ->get()
            ->map(function (Dispatch $dispatch) {
                return (object) [
                    'id' => $dispatch->id,
                    'document_number' => $dispatch->physical_document_number ?: $dispatch->dispatch_number,
                    'document_date' => $dispatch->document_date,
                    'requesting_unit' => $dispatch->area?->name ?? '-',
                    'warehouse_name' => $dispatch->warehouse?->name ?? '-',
                    'items' => $dispatch->details->map(fn ($detail) => (object) [
                        'sku' => $detail->product?->sku ?? '-',
                        'description' => $detail->product?->name ?? '-',
                        'unit' => $detail->unitOfMeasure?->abbreviation ?? $detail->unitOfMeasure?->name ?? '-',
                        'quantity' => (float) $detail->quantity,
                        'unit_price' => (float) $detail->unit_price,
                        'total' => (float) $detail->total,
                    ]),
                    'total_quantity' => (float) $dispatch->details->sum('quantity'),
                    'total_amount' => (float) $dispatch->details->sum('total'),
                ];
            });
    }

    protected function loadInternalProductions()
    {
        $query = InternalProduction::query()
            ->with([
                'area:id,name',
                'warehouse:id,name',
                'details.product:id,name,sku',
                'details.unitOfMeasure:id,name,abbreviation',
            ])
            ->where('company_id', $this->effectiveCompanyId)
            ->whereNotIn('status', ['cancelado'])
            ->whereBetween('document_date', [$this->start_date, $this->end_date]);

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        return $query
            ->orderBy('warehouse_id')
            ->orderBy('document_date')
            ->orderBy('id')
            ->get()
            ->map(function (InternalProduction $production) {
                return (object) [
                    'id' => $production->id,
                    'document_number' => $production->physical_document_number ?: $production->production_number,
                    'document_date' => $production->document_date,
                    'requesting_unit' => $production->area?->name ?? '-',
                    'warehouse_name' => $production->warehouse?->name ?? '-',
                    'items' => $production->details->map(fn ($detail) => (object) [
                        'sku' => $detail->product?->sku ?? '-',
                        'description' => $detail->product?->name ?? $detail->description ?? '-',
                        'unit' => $detail->unitOfMeasure?->abbreviation ?? $detail->unitOfMeasure?->name ?? '-',
                        'quantity' => (float) $detail->quantity,
                        'unit_price' => (float) $detail->unit_price,
                        'total' => (float) $detail->total,
                    ]),
                    'total_quantity' => (float) $production->details->sum('quantity'),
                    'total_amount' => (float) $production->details->sum('total'),
                ];
            });
    }

    protected function loadTransfers()
    {
        $companyId = $this->effectiveCompanyId;

        $query = InventoryTransfer::query()
            ->with([
                'fromWarehouse:id,name,company_id',
                'toWarehouse:id,name',
                'details.product:id,name,sku',
            ])
            ->whereHas('fromWarehouse', fn ($q) => $q->where('company_id', $companyId))
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('document_date', [$this->start_date, $this->end_date]);

        if ($this->warehouse_id) {
            $query->where('from_warehouse_id', $this->warehouse_id);
        }

        return $query
            ->orderBy('from_warehouse_id')
            ->orderBy('document_date')
            ->orderBy('id')
            ->get()
            ->map(function (InventoryTransfer $transfer) {
                return (object) [
                    'id' => $transfer->id,
                    'document_number' => $transfer->physical_document_number ?: $transfer->transfer_number,
                    'document_date' => $transfer->document_date,
                    'requesting_unit' => $transfer->toWarehouse?->name ?? '-',
                    'warehouse_name' => $transfer->fromWarehouse?->name ?? '-',
                    'items' => $transfer->details->map(fn ($detail) => (object) [
                        'sku' => $detail->product?->sku ?? '-',
                        'description' => $detail->product?->name ?? '-',
                        'unit' => '-',
                        'quantity' => (float) $detail->quantity,
                        'unit_price' => (float) $detail->unit_cost,
                        'total' => (float) $detail->quantity * (float) $detail->unit_cost,
                    ]),
                    'total_quantity' => (float) $transfer->details->sum('quantity'),
                    'total_amount' => (float) $transfer->details->sum(fn ($d) => $d->quantity * $d->unit_cost),
                ];
            });
    }

    #[Computed]
    public function totals(): array
    {
        $vouchers = $this->vouchers;

        return [
            'total_vouchers' => $vouchers->count(),
            'total_items' => $vouchers->sum(fn ($v) => $v->items->count()),
            'total_quantity' => $vouchers->sum('total_quantity'),
            'total_amount' => $vouchers->sum('total_amount'),
        ];
    }

    public function exportPdf()
    {
        $params = [
            'empresa' => $this->effectiveCompanyId,
            'inicio' => $this->start_date,
            'fin' => $this->end_date,
            'tipo' => $this->type,
        ];

        if ($this->warehouse_id) {
            $params['bodega'] = $this->warehouse_id;
        }

        return $this->redirect(route('reports.digitized-data.pdf', $params));
    }

    public function exportExcel()
    {
        $params = [
            'empresa' => $this->effectiveCompanyId,
            'inicio' => $this->start_date,
            'fin' => $this->end_date,
            'tipo' => $this->type,
        ];

        if ($this->warehouse_id) {
            $params['bodega'] = $this->warehouse_id;
        }

        return $this->redirect(route('reports.digitized-data.excel', $params));
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Reporte de Datos Digitados</flux:heading>
            <flux:text class="mt-1">Listado de despachos, producción interna y traslados ingresados al sistema</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('reports.administrative') }}" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    @if (session('error'))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>No se pudo generar el reporte</flux:callout.heading>
            <flux:callout.text>{{ session('error') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Filters --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Filtros</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            @if ($this->isSuperAdmin)
                <flux:field class="md:col-span-6">
                    <flux:label>Empresa</flux:label>
                    <flux:select wire:model.live="company_id">
                        <flux:select.option value="">-- Seleccione una empresa --</flux:select.option>
                        @foreach ($this->companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <flux:field class="md:col-span-2">
                <flux:label>Tipo</flux:label>
                <flux:select wire:model.live="type">
                    @foreach ($this->reportTypes as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
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

            <flux:field>
                <flux:label>{{ $type === 'transfers' ? 'Bodega Origen' : 'Bodega' }}</flux:label>
                <flux:select wire:model.live="warehouse_id">
                    <flux:select.option value="">-- Todas --</flux:select.option>
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
                <flux:text class="mt-2">Seleccione una empresa para ver el reporte</flux:text>
            </div>
        </flux:card>
    @else
        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <flux:card class="text-center">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Comprobantes</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_vouchers']) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Total Líneas</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_items']) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Cantidad Total</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_quantity'], 2) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center bg-orange-50 dark:bg-orange-900/20">
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Valor Total</flux:text>
                <flux:heading size="xl" class="text-orange-600 dark:text-orange-400">
                    ${{ number_format($this->totals['total_amount'], 2) }}
                </flux:heading>
            </flux:card>
        </div>

        {{-- Vouchers --}}
        @forelse ($this->vouchers as $voucher)
            <flux:card wire:key="voucher-{{ $type }}-{{ $voucher->id }}">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <flux:text class="text-xs text-gray-500">N° Documento</flux:text>
                        <flux:heading size="md">{{ $voucher->document_number }}</flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-xs text-gray-500">Fecha</flux:text>
                        <flux:heading size="md">{{ \Carbon\Carbon::parse($voucher->document_date)->format('d/m/Y') }}</flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-xs text-gray-500">
                            {{ $type === 'transfers' ? 'Bodega Destino' : 'Unidad Solicitante' }}
                        </flux:text>
                        <flux:heading size="md">{{ $voucher->requesting_unit }}</flux:heading>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Código</flux:table.column>
                            <flux:table.column>Descripción</flux:table.column>
                            <flux:table.column>Unidad</flux:table.column>
                            <flux:table.column class="text-right">Cantidad</flux:table.column>
                            <flux:table.column class="text-right">Valor Unitario</flux:table.column>
                            <flux:table.column class="text-right">Total</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($voucher->items as $idx => $item)
                                <flux:table.row wire:key="item-{{ $voucher->id }}-{{ $idx }}">
                                    <flux:table.cell>{{ $item->sku }}</flux:table.cell>
                                    <flux:table.cell>{{ $item->description }}</flux:table.cell>
                                    <flux:table.cell>{{ $item->unit }}</flux:table.cell>
                                    <flux:table.cell class="text-right">{{ number_format($item->quantity, 2) }}</flux:table.cell>
                                    <flux:table.cell class="text-right">${{ number_format($item->unit_price, 2) }}</flux:table.cell>
                                    <flux:table.cell class="text-right font-medium">${{ number_format($item->total, 2) }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-8">
                    <div class="text-right">
                        <flux:text class="text-xs text-gray-500">Cantidad Total</flux:text>
                        <flux:heading size="md">{{ number_format($voucher->total_quantity, 2) }}</flux:heading>
                    </div>
                    <div class="text-right">
                        <flux:text class="text-xs text-gray-500">Total Comprobante</flux:text>
                        <flux:heading size="md" class="text-orange-600 dark:text-orange-400">
                            ${{ number_format($voucher->total_amount, 2) }}
                        </flux:heading>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card>
                <div class="py-12 text-center">
                    <flux:icon.document-magnifying-glass class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mt-4">Sin Resultados</flux:heading>
                    <flux:text class="mt-2">No se encontraron registros en el período seleccionado</flux:text>
                </div>
            </flux:card>
        @endforelse
    @endif
</div>
