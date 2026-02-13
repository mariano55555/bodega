<?php

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component
{
    #[Url]
    public ?int $company_id = null;

    #[Url]
    public ?int $product_id = null;

    #[Url]
    public string|int|null $warehouse_id = null;

    #[Url]
    public ?string $date_from = null;

    #[Url]
    public ?string $date_to = null;

    #[Url]
    public ?string $movement_type = null;

    #[Url]
    public ?int $movement_reason_id = null;

    public function mount(): void
    {
        // Auto-set company_id for non-super admins
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }

        // Set default date range to current month if not provided
        if (! $this->date_from) {
            $this->date_from = now()->startOfMonth()->format('Y-m-d');
        }
        if (! $this->date_to) {
            $this->date_to = now()->endOfMonth()->format('Y-m-d');
        }
    }

    public function updatedCompanyId(): void
    {
        // Reset products and warehouses when company changes
        $this->product_id = null;
        $this->warehouse_id = null;
    }

    #[Computed]
    public function companies()
    {
        return \App\Models\Company::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return Product::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function warehouses()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return Warehouse::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function movementReasons()
    {
        return \App\Models\MovementReason::where('is_active', true)
            ->orderBy('legacy_code')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function isAllWarehouses(): bool
    {
        return $this->warehouse_id === 'all';
    }

    #[Computed]
    public function movements()
    {
        if (! $this->company_id || ! $this->product_id || ! $this->warehouse_id) {
            return collect([]);
        }

        $query = InventoryMovement::query()
            ->where('company_id', $this->company_id)
            ->where('product_id', $this->product_id)
            ->whereNotNull('balance_quantity')
            ->with(['product', 'warehouse', 'movementReason', 'dispatch', 'transfer', 'purchase', 'donation']);

        if (! $this->isAllWarehouses) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        if ($this->date_from) {
            $query->whereDate('movement_date', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $query->whereDate('movement_date', '<=', $this->date_to);
        }

        if ($this->movement_type) {
            $query->where('movement_type', $this->movement_type);
        }

        if ($this->movement_reason_id) {
            $query->where('movement_reason_id', $this->movement_reason_id);
        }

        return $query->orderBy('warehouse_id')
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function selectedProduct()
    {
        if (! $this->product_id) {
            return null;
        }

        return Product::find($this->product_id);
    }

    #[Computed]
    public function selectedWarehouse()
    {
        if (! $this->warehouse_id || $this->isAllWarehouses) {
            return null;
        }

        return Warehouse::find($this->warehouse_id);
    }

    public function exportPdf(): void
    {
        if (! $this->product_id || ! $this->warehouse_id || $this->isAllWarehouses) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Para exportar PDF seleccione un producto y un almacén específico',
            ]);

            return;
        }

        $this->redirect(route('reports.kardex.pdf', [
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ]));
    }

    public function exportExcel(): void
    {
        if (! $this->product_id || ! $this->warehouse_id || $this->isAllWarehouses) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Para exportar Excel seleccione un producto y un almacén específico',
            ]);

            return;
        }

        $this->redirect(route('reports.kardex.excel', [
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ]));
    }

    public function resetFilters(): void
    {
        if (auth()->user()->isSuperAdmin()) {
            $this->company_id = null;
        }
        $this->product_id = null;
        $this->warehouse_id = null;
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->endOfMonth()->format('Y-m-d');
        $this->movement_type = null;
        $this->movement_reason_id = null;
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Kardex de Inventario</flux:heading>

    {{-- Filters --}}
    <flux:card class="mb-6">
        @if (auth()->user()->isSuperAdmin())
            <div class="mb-6">
                <flux:field>
                    <flux:label badge="Requerido">Empresa</flux:label>
                    <flux:select wire:model.live="company_id">
                        <option value="">Seleccione una empresa</option>
                        @foreach ($this->companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            {{-- Product Selection --}}
            <flux:field>
                <flux:label badge="Requerido">Producto</flux:label>
                <flux:select wire:model.live="product_id" placeholder="Seleccione un producto" :disabled="!$company_id">
                    @foreach ($this->products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            {{-- Warehouse Selection --}}
            <flux:field>
                <flux:label badge="Requerido">Almacén</flux:label>
                <flux:select wire:model.live="warehouse_id" placeholder="Seleccione un almacén" :disabled="!$company_id">
                    <option value="all">-- Todas las bodegas --</option>
                    @foreach ($this->warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            {{-- Date From --}}
            <flux:field>
                <flux:label>Fecha Desde</flux:label>
                <flux:input type="date" wire:model.live="date_from" />
            </flux:field>

            {{-- Date To --}}
            <flux:field>
                <flux:label>Fecha Hasta</flux:label>
                <flux:input type="date" wire:model.live="date_to" />
            </flux:field>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
            {{-- Movement Type --}}
            <flux:field>
                <flux:label>Tipo de Movimiento</flux:label>
                <flux:select variant="listbox" searchable wire:model.live="movement_type" placeholder="Todos los tipos">
                    <flux:select.option value="">Todos</flux:select.option>
                    <flux:select.option value="purchase">📦 Compra</flux:select.option>
                    <flux:select.option value="receipt">📥 Recepción</flux:select.option>
                    <flux:select.option value="return_customer">↩️ Devolución Cliente</flux:select.option>
                    <flux:select.option value="transfer_in">⬅️ Transferencia Entrada</flux:select.option>
                    <flux:select.option value="sale">💰 Venta</flux:select.option>
                    <flux:select.option value="shipment">📤 Envío</flux:select.option>
                    <flux:select.option value="return_supplier">↪️ Devolución Proveedor</flux:select.option>
                    <flux:select.option value="transfer_out">➡️ Transferencia Salida</flux:select.option>
                    <flux:select.option value="adjustment">⚖️ Ajuste</flux:select.option>
                    <flux:select.option value="expiry">⏰ Vencimiento</flux:select.option>
                </flux:select>
                <flux:description>Filtre por tipo de movimiento específico</flux:description>
            </flux:field>

            {{-- Movement Reason --}}
            <flux:field>
                <flux:label>Código de Transacción</flux:label>
                <flux:select variant="listbox" searchable wire:model.live="movement_reason_id" placeholder="Todos los códigos">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($this->movementReasons as $reason)
                        <flux:select.option value="{{ $reason->id }}">
                            @if ($reason->legacy_code)
                                {{ $reason->legacy_code }} - {{ $reason->legacy_name }}
                            @else
                                {{ $reason->name }}
                            @endif
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:description>Filtre por código de transacción ENA (E0, S1, etc.)</flux:description>
            </flux:field>
        </div>

        <div class="mt-4 flex gap-3">
            <flux:button wire:click="resetFilters" variant="ghost">
                Limpiar Filtros
            </flux:button>

            @if ($product_id && $warehouse_id && $warehouse_id !== 'all')
                <flux:button wire:click="exportPdf" variant="primary" icon="document-arrow-down">
                    Exportar PDF
                </flux:button>

                <flux:button wire:click="exportExcel" variant="primary" icon="table-cells">
                    Exportar Excel
                </flux:button>
            @endif
        </div>
    </flux:card>

    {{-- Report Header --}}
    @if ($this->selectedProduct && ($this->selectedWarehouse || $this->isAllWarehouses))
        <flux:card class="mb-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <flux:heading size="sm" class="mb-1">Producto</flux:heading>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $this->selectedProduct->name }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-500">
                        SKU: {{ $this->selectedProduct->sku }}
                    </p>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-1">Almacén</flux:heading>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        @if ($this->isAllWarehouses)
                            Todas las bodegas
                        @else
                            {{ $this->selectedWarehouse->name }}
                        @endif
                    </p>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-1">Período</flux:heading>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ \Carbon\Carbon::parse($date_from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($date_to)->format('d/m/Y') }}
                    </p>
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Movements Table --}}
    <flux:card>
        @if ($this->movements->isEmpty())
            <div class="py-12 text-center">
                <flux:icon.document-magnifying-glass class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">No hay movimientos</flux:heading>
                <flux:text class="mt-2">
                    @if (! $company_id)
                        Seleccione una empresa para comenzar
                    @elseif (! $product_id)
                        Seleccione un producto para ver el kardex
                    @elseif (! $warehouse_id)
                        Seleccione un almacén para ver el kardex
                    @else
                        No se encontraron movimientos para el período seleccionado
                    @endif
                </flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-32">Fecha</flux:table.column>
                        @if ($this->isAllWarehouses)
                            <flux:table.column>Bodega</flux:table.column>
                        @endif
                        <flux:table.column>Documento</flux:table.column>
                        <flux:table.column>Transacción</flux:table.column>
                        <flux:table.column align="right">Saldo Inicial</flux:table.column>
                        <flux:table.column align="right">Entrada</flux:table.column>
                        <flux:table.column align="right">Salida</flux:table.column>
                        <flux:table.column align="right">Saldo Final</flux:table.column>
                        <flux:table.column align="right">Costo Unit.</flux:table.column>
                        <flux:table.column align="right">Valor Total</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->movements as $movement)
                            <flux:table.row>
                                <flux:table.cell>
                                    {{ $movement->movement_date?->format('d/m/Y') ?? $movement->created_at->format('d/m/Y') }}
                                </flux:table.cell>

                                @if ($this->isAllWarehouses)
                                    <flux:table.cell>
                                        <flux:badge size="sm">{{ $movement->warehouse?->name ?? '-' }}</flux:badge>
                                    </flux:table.cell>
                                @endif

                                <flux:table.cell>
                                    <div class="flex flex-col gap-1">
                                        {{-- Documento Original del Sistema --}}
                                        @if ($movement->dispatch)
                                            <a href="{{ route('dispatches.show', $movement->dispatch->slug) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium" wire:navigate>
                                                {{ $movement->dispatch->dispatch_number }}
                                            </a>
                                            <span class="text-xs text-zinc-500">Despacho</span>
                                            @if ($movement->dispatch->physical_document_number)
                                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $movement->dispatch->physical_document_number }}</span>
                                            @endif
                                        @elseif ($movement->transfer)
                                            <a href="{{ route('transfers.show', $movement->transfer) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium" wire:navigate>
                                                {{ $movement->transfer->transfer_number ?? 'TRANS-'.$movement->transfer->id }}
                                            </a>
                                            <span class="text-xs text-zinc-500">Transferencia</span>
                                            @if ($movement->transfer->physical_document_number)
                                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $movement->transfer->physical_document_number }}</span>
                                            @endif
                                        @elseif ($movement->purchase)
                                            <a href="{{ route('purchases.show', $movement->purchase->slug) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium" wire:navigate>
                                                {{ $movement->purchase->purchase_number ?? 'PO-'.$movement->purchase->id }}
                                            </a>
                                            <span class="text-xs text-zinc-500">Compra</span>
                                            @if ($movement->purchase->document_number)
                                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $movement->purchase->document_number }}</span>
                                            @endif
                                        @elseif ($movement->donation)
                                            <a href="{{ route('donations.show', $movement->donation->slug) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium" wire:navigate>
                                                {{ $movement->donation->donation_number ?? 'DON-'.$movement->donation->id }}
                                            </a>
                                            <span class="text-xs text-zinc-500">Donación</span>
                                            @if ($movement->donation->document_number)
                                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $movement->donation->document_number }}</span>
                                            @endif
                                        @endif

                                        {{-- Documento Externo (Factura, Guía, etc.) --}}
                                        @if ($movement->document_number)
                                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $movement->document_number }}</span>
                                        @endif
                                        @if ($movement->reference_number)
                                            <span class="text-xs text-zinc-500">Ref: {{ $movement->reference_number }}</span>
                                        @endif

                                        {{-- Sin documento --}}
                                        @if (! $movement->dispatch && ! $movement->transfer && ! $movement->purchase && ! $movement->donation && ! $movement->document_number && ! $movement->reference_number)
                                            <span class="text-xs text-zinc-400">Sin documento</span>
                                        @endif
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($movement->movementReason)
                                        <div class="flex flex-col">
                                            <span class="font-medium">
                                                {{ $movement->movementReason->legacy_code ?? $movement->movementReason->code }}
                                            </span>
                                            <span class="text-xs text-zinc-600 dark:text-zinc-400">
                                                {{ $movement->movementReason->legacy_name ?? $movement->movementReason->name }}
                                            </span>
                                        </div>
                                    @else
                                        {{ $movement->movement_type_spanish }}
                                    @endif
                                </flux:table.cell>

                                {{-- Saldo Inicial (balance before this movement) --}}
                                <flux:table.cell class="text-right tabular-nums">
                                    @php
                                        $initialBalance = $movement->balance_quantity - $movement->quantity_in + $movement->quantity_out;
                                    @endphp
                                    <span class="{{ $initialBalance < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                        {{ number_format($initialBalance, 2) }}
                                    </span>
                                </flux:table.cell>

                                <flux:table.cell class="text-right font-medium tabular-nums">
                                    @if ($movement->quantity_in > 0)
                                        <span class="text-green-600 dark:text-green-400">
                                            {{ number_format($movement->quantity_in, 2) }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell class="text-right font-medium tabular-nums">
                                    @if ($movement->quantity_out > 0)
                                        <span class="text-blue-600 dark:text-blue-400">
                                            {{ number_format($movement->quantity_out, 2) }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell class="text-right font-semibold tabular-nums">
                                    <span class="{{ $movement->balance_quantity < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                        {{ number_format($movement->balance_quantity, 2) }}
                                    </span>
                                </flux:table.cell>

                                <flux:table.cell class="text-right tabular-nums">
                                    @if ($movement->unit_cost)
                                        <span class="text-zinc-700 dark:text-zinc-300">
                                            ${{ number_format($movement->unit_cost, 2) }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell class="text-right font-medium tabular-nums">
                                    @php
                                        $totalValue = $movement->balance_quantity * ($movement->unit_cost ?? 0);
                                    @endphp
                                    <span class="text-zinc-900 dark:text-zinc-100">
                                        ${{ number_format($totalValue, 2) }}
                                    </span>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            {{-- Summary --}}
            <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                @if ($this->movements->isNotEmpty())
                    @php
                        $totalIn = $this->movements->sum('quantity_in');
                        $totalOut = $this->movements->sum('quantity_out');
                    @endphp

                    @if ($this->isAllWarehouses)
                        {{-- Summary per warehouse when showing all --}}
                        @php
                            $byWarehouse = $this->movements->groupBy('warehouse_id');
                        @endphp

                        <flux:heading size="sm" class="mb-3">Resumen por Bodega</flux:heading>
                        <div class="space-y-3">
                            @foreach ($byWarehouse as $whId => $whMovements)
                                @php
                                    $lastWh = $whMovements->last();
                                    $whName = $lastWh->warehouse?->name ?? 'N/A';
                                    $whFinalValue = $lastWh->balance_quantity * ($lastWh->unit_cost ?? 0);
                                @endphp
                                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <div class="mb-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $whName }}</div>
                                    <div class="grid grid-cols-2 gap-2 text-sm md:grid-cols-5">
                                        <div>
                                            <span class="text-zinc-500">Entradas:</span>
                                            <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($whMovements->sum('quantity_in'), 2) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-zinc-500">Salidas:</span>
                                            <span class="font-semibold text-blue-600 dark:text-blue-400">{{ number_format($whMovements->sum('quantity_out'), 2) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-zinc-500">Existencia:</span>
                                            <span class="font-semibold {{ $lastWh->balance_quantity < 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ number_format($lastWh->balance_quantity, 2) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-zinc-500">Costo Unit.:</span>
                                            <span class="font-semibold">${{ number_format($lastWh->unit_cost ?? 0, 2) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-zinc-500">Valor:</span>
                                            <span class="font-semibold text-amber-600 dark:text-amber-400">${{ number_format($whFinalValue, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">Total Entradas (todas):</flux:text>
                                <flux:text class="font-semibold text-green-600 dark:text-green-400">
                                    {{ number_format($totalIn, 2) }}
                                </flux:text>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">Total Salidas (todas):</flux:text>
                                <flux:text class="font-semibold text-blue-600 dark:text-blue-400">
                                    {{ number_format($totalOut, 2) }}
                                </flux:text>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">Total Movimientos:</flux:text>
                                <flux:text class="font-semibold">{{ $this->movements->count() }}</flux:text>
                            </div>
                        </div>
                    @else
                        @php
                            $firstMovement = $this->movements->first();
                            $lastMovement = $this->movements->last();
                            $initialBalance = $firstMovement->balance_quantity - $firstMovement->quantity_in + $firstMovement->quantity_out;
                            $finalValue = $lastMovement->balance_quantity * ($lastMovement->unit_cost ?? 0);
                        @endphp

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            {{-- Row 1 --}}
                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">Total Entradas:</flux:text>
                                <flux:text class="font-semibold text-green-600 dark:text-green-400">
                                    {{ number_format($totalIn, 2) }}
                                </flux:text>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">Total Salidas:</flux:text>
                                <flux:text class="font-semibold text-blue-600 dark:text-blue-400">
                                    {{ number_format($totalOut, 2) }}
                                </flux:text>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">Valor en Inventario:</flux:text>
                                <flux:text class="font-semibold text-amber-600 dark:text-amber-400">
                                    ${{ number_format($finalValue, 2) }}
                                </flux:text>
                            </div>

                            {{-- Row 2 --}}
                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">Total Movimientos:</flux:text>
                                <flux:text class="font-semibold">{{ $this->movements->count() }}</flux:text>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">Existencia Actual:</flux:text>
                                <flux:text class="font-semibold {{ $lastMovement->balance_quantity < 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                                    {{ number_format($lastMovement->balance_quantity, 2) }}
                                </flux:text>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">Costo Unitario Actual:</flux:text>
                                <flux:text class="font-semibold">
                                    ${{ number_format($lastMovement->unit_cost ?? 0, 2) }}
                                </flux:text>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="flex items-center gap-2">
                        <flux:text class="font-medium">Total de Movimientos:</flux:text>
                        <flux:text class="font-semibold">0</flux:text>
                    </div>
                @endif
            </div>
        @endif
    </flux:card>
</div>
