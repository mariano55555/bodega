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
    public ?int $warehouse_id = null;

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
            ->orderBy('name')
            ->get();
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
            ->where('warehouse_id', $this->warehouse_id)
            ->whereNotNull('balance_quantity')
            ->with(['product', 'warehouse', 'movementReason']);

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

        return $query->orderBy('movement_date')
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
        if (! $this->warehouse_id) {
            return null;
        }

        return Warehouse::find($this->warehouse_id);
    }

    public function exportPdf(): void
    {
        if (! $this->product_id || ! $this->warehouse_id) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Por favor seleccione un producto y un almacén',
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
        if (! $this->product_id || ! $this->warehouse_id) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Por favor seleccione un producto y un almacén',
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
                <flux:select wire:model.live="movement_type" placeholder="Todos los tipos">
                    <option value="">Todos</option>
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                    <option value="ajuste">Ajuste</option>
                    <option value="transferencia">Transferencia</option>
                </flux:select>
            </flux:field>

            {{-- Movement Reason --}}
            <flux:field>
                <flux:label>Motivo</flux:label>
                <flux:select wire:model.live="movement_reason_id" placeholder="Todos los motivos">
                    <option value="">Todos</option>
                    @foreach ($this->movementReasons as $reason)
                        <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>

        <div class="mt-4 flex gap-3">
            <flux:button wire:click="resetFilters" variant="ghost">
                Limpiar Filtros
            </flux:button>

            @if ($product_id && $warehouse_id)
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
    @if ($this->selectedProduct && $this->selectedWarehouse)
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
                        {{ $this->selectedWarehouse->name }}
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
                    @elseif (! $product_id || ! $warehouse_id)
                        Seleccione un producto y un almacén para ver el kardex
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
                        <flux:table.column>Documento</flux:table.column>
                        <flux:table.column>Motivo</flux:table.column>
                        <flux:table.column class="text-right">Entrada</flux:table.column>
                        <flux:table.column class="text-right">Salida</flux:table.column>
                        <flux:table.column class="text-right">Saldo</flux:table.column>
                        <flux:table.column class="text-right">Costo Unit.</flux:table.column>
                        <flux:table.column class="text-right">Valor Total</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->movements as $movement)
                            <flux:table.row>
                                <flux:table.cell>
                                    {{ $movement->movement_date?->format('d/m/Y') ?? $movement->created_at->format('d/m/Y') }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-col">
                                        @if ($movement->document_number)
                                            <span class="font-medium">{{ $movement->document_number }}</span>
                                        @endif
                                        @if ($movement->reference_number)
                                            <span class="text-xs text-zinc-500">{{ $movement->reference_number }}</span>
                                        @endif
                                        @if (! $movement->document_number && ! $movement->reference_number)
                                            <span class="text-xs text-zinc-400">Sin documento</span>
                                        @endif
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $movement->movementReason?->name ?? $movement->movement_type_spanish }}
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
                                        <span class="text-red-600 dark:text-red-400">
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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center justify-between">
                        <flux:text class="font-medium">Total de Movimientos:</flux:text>
                        <flux:text class="font-semibold">{{ $this->movements->count() }}</flux:text>
                    </div>

                    @if ($this->movements->isNotEmpty())
                        @php
                            $lastMovement = $this->movements->last();
                            $finalValue = $lastMovement->balance_quantity * ($lastMovement->unit_cost ?? 0);
                            $totalIn = $this->movements->sum('quantity_in');
                            $totalOut = $this->movements->sum('quantity_out');
                        @endphp

                        <div class="flex items-center justify-between">
                            <flux:text class="font-medium">Saldo Final (Cantidad):</flux:text>
                            <flux:text class="text-lg font-bold {{ $lastMovement->balance_quantity < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                {{ number_format($lastMovement->balance_quantity, 2) }}
                            </flux:text>
                        </div>

                        <div class="flex items-center justify-between">
                            <flux:text class="font-medium">Valor en Inventario:</flux:text>
                            <flux:text class="text-lg font-bold text-green-600 dark:text-green-400">
                                ${{ number_format($finalValue, 2) }}
                            </flux:text>
                        </div>

                        <div class="flex items-center justify-between">
                            <flux:text class="font-medium">Total Entradas:</flux:text>
                            <flux:text class="font-semibold text-green-600 dark:text-green-400">
                                {{ number_format($totalIn, 2) }}
                            </flux:text>
                        </div>

                        <div class="flex items-center justify-between">
                            <flux:text class="font-medium">Total Salidas:</flux:text>
                            <flux:text class="font-semibold text-red-600 dark:text-red-400">
                                {{ number_format($totalOut, 2) }}
                            </flux:text>
                        </div>

                        @if ($lastMovement->unit_cost)
                            <div class="flex items-center justify-between">
                                <flux:text class="font-medium">Costo Unitario Actual:</flux:text>
                                <flux:text class="font-semibold">
                                    ${{ number_format($lastMovement->unit_cost, 2) }}
                                </flux:text>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    </flux:card>
</div>
