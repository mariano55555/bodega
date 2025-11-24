<?php

use Livewire\Volt\Component;
use App\Models\{InventoryMovement, Product, Warehouse, MovementReason};
use Livewire\Attributes\{Computed, Layout};

new #[Layout('components.layouts.app')] class extends Component
{
    public ?int $productId = null;
    public ?int $warehouseId = null;
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $movementType = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    #[Computed]
    public function products()
    {
        return Product::active()->orderBy('name')->get(['id', 'name', 'sku']);
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::active()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function kardexData()
    {
        if (!$this->productId || !$this->warehouseId) {
            return collect([]);
        }

        $query = InventoryMovement::query()
            ->where('product_id', $this->productId)
            ->where('warehouse_id', $this->warehouseId)
            ->with(['product', 'warehouse', 'user', 'movementable']);

        if ($this->dateFrom) {
            $query->whereDate('movement_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('movement_date', '<=', $this->dateTo);
        }

        if ($this->movementType) {
            $query->where('movement_type', $this->movementType);
        }

        return $query->orderBy('movement_date', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->get();
    }

    #[Computed]
    public function kardexSummary()
    {
        if (!$this->productId || !$this->warehouseId) {
            return null;
        }

        $movements = $this->kardexData;

        if ($movements->isEmpty()) {
            return null;
        }

        $totalEntries = $movements->whereIn('movement_type', ['entry', 'transfer_in'])->sum('quantity');
        $totalExits = $movements->whereIn('movement_type', ['exit', 'transfer_out'])->sum('quantity');

        $initialBalance = $movements->first()->balance_before ?? 0;
        $finalBalance = $movements->last()->balance_after ?? 0;

        return [
            'initial_balance' => $initialBalance,
            'final_balance' => $finalBalance,
            'total_entries' => $totalEntries,
            'total_exits' => $totalExits,
            'net_movement' => $totalEntries - $totalExits,
            'movement_count' => $movements->count(),
        ];
    }

    public function clearFilters(): void
    {
        $this->productId = null;
        $this->warehouseId = null;
        $this->movementType = '';
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function with(): array
    {
        return [
            'title' => __('Consulta de Kardex'),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
            Consulta de Kardex Histórico
        </flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            Consulta el historial completo de movimientos de inventario por producto y almacén
        </flux:text>
    </div>

    <flux:card class="mb-6">
        <div class="space-y-6">
            <flux:heading size="lg">Filtros de Búsqueda</flux:heading>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Producto *</flux:label>
                    <flux:select wire:model.live="productId" placeholder="Seleccionar producto">
                        @foreach($this->products as $product)
                        <flux:select.option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Almacén *</flux:label>
                    <flux:select wire:model.live="warehouseId" placeholder="Seleccionar almacén">
                        @foreach($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de Movimiento</flux:label>
                    <flux:select wire:model.live="movementType" placeholder="Todos los tipos">
                        <flux:select.option value="entry">Entrada</flux:select.option>
                        <flux:select.option value="exit">Salida</flux:select.option>
                        <flux:select.option value="transfer_in">Transferencia Entrada</flux:select.option>
                        <flux:select.option value="transfer_out">Transferencia Salida</flux:select.option>
                        <flux:select.option value="adjustment">Ajuste</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Fecha Desde</flux:label>
                    <flux:input type="date" wire:model.live="dateFrom" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha Hasta</flux:label>
                    <flux:input type="date" wire:model.live="dateTo" />
                </flux:field>
            </div>

            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                    Limpiar Filtros
                </flux:button>
            </div>
        </div>
    </flux:card>

    @if(!$productId || !$warehouseId)
    <flux:card>
        <div class="text-center py-12">
            <flux:icon name="document-text" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="text-zinc-700 dark:text-zinc-300 mb-2">
                Selecciona un Producto y Almacén
            </flux:heading>
            <flux:text class="text-zinc-500">
                Para visualizar el kardex histórico, selecciona un producto y un almacén en los filtros superiores
            </flux:text>
        </div>
    </flux:card>
    @else
        @if($this->kardexSummary)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <flux:card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-blue-600 dark:text-blue-400">Saldo Inicial</flux:text>
                        <flux:heading size="2xl" class="text-blue-900 dark:text-blue-100">
                            {{ number_format($this->kardexSummary['initial_balance'], 2) }}
                        </flux:heading>
                    </div>
                    <flux:icon name="arrow-up-circle" class="h-8 w-8 text-blue-500" />
                </div>
            </flux:card>

            <flux:card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-green-200 dark:border-green-800">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">Total Entradas</flux:text>
                        <flux:heading size="2xl" class="text-green-900 dark:text-green-100">
                            {{ number_format($this->kardexSummary['total_entries'], 2) }}
                        </flux:heading>
                    </div>
                    <flux:icon name="arrow-down-tray" class="h-8 w-8 text-green-500" />
                </div>
            </flux:card>

            <flux:card class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-red-200 dark:border-red-800">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-red-600 dark:text-red-400">Total Salidas</flux:text>
                        <flux:heading size="2xl" class="text-red-900 dark:text-red-100">
                            {{ number_format($this->kardexSummary['total_exits'], 2) }}
                        </flux:heading>
                    </div>
                    <flux:icon name="arrow-up-tray" class="h-8 w-8 text-red-500" />
                </div>
            </flux:card>

            <flux:card class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border-purple-200 dark:border-purple-800">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-purple-600 dark:text-purple-400">Saldo Final</flux:text>
                        <flux:heading size="2xl" class="text-purple-900 dark:text-purple-100">
                            {{ number_format($this->kardexSummary['final_balance'], 2) }}
                        </flux:heading>
                    </div>
                    <flux:icon name="calculator" class="h-8 w-8 text-purple-500" />
                </div>
            </flux:card>
        </div>
        @endif

        <flux:card>
            @if($this->kardexData && $this->kardexData->count() > 0)
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">Movimientos de Kardex</flux:heading>
                <div class="flex gap-2">
                    <flux:button variant="outline" size="sm" icon="arrow-down-tray">Exportar Excel</flux:button>
                    <flux:button variant="outline" size="sm" icon="document-arrow-down">Exportar PDF</flux:button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Fecha</flux:table.column>
                        <flux:table.column>Tipo</flux:table.column>
                        <flux:table.column>Referencia</flux:table.column>
                        <flux:table.column class="text-right">Entrada</flux:table.column>
                        <flux:table.column class="text-right">Salida</flux:table.column>
                        <flux:table.column class="text-right">Saldo</flux:table.column>
                        <flux:table.column>Usuario</flux:table.column>
                        <flux:table.column>Notas</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($this->kardexData as $movement)
                        <flux:table.row wire:key="movement-{{ $movement->id }}">
                            <flux:table.cell>
                                <flux:text class="font-medium">{{ $movement->movement_date->format('d/m/Y') }}</flux:text>
                                <flux:text class="text-sm text-zinc-500 block">{{ $movement->movement_date->format('H:i') }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                $typeColors = ['entry' => 'green', 'exit' => 'red', 'transfer_in' => 'blue', 'transfer_out' => 'orange', 'adjustment' => 'purple'];
                                $typeLabels = ['entry' => 'Entrada', 'exit' => 'Salida', 'transfer_in' => 'Transfer. Entrada', 'transfer_out' => 'Transfer. Salida', 'adjustment' => 'Ajuste'];
                                @endphp
                                <flux:badge color="{{ $typeColors[$movement->movement_type] ?? 'zinc' }}" size="sm">
                                    {{ $typeLabels[$movement->movement_type] ?? $movement->movement_type }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm">{{ $movement->reference ?? '-' }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                @if(in_array($movement->movement_type, ['entry', 'transfer_in']) || ($movement->movement_type === 'adjustment' && $movement->quantity > 0))
                                <flux:text class="font-medium text-green-600">{{ number_format($movement->quantity, 2) }}</flux:text>
                                @else
                                <flux:text class="text-zinc-400">-</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                @if(in_array($movement->movement_type, ['exit', 'transfer_out']) || ($movement->movement_type === 'adjustment' && $movement->quantity < 0))
                                <flux:text class="font-medium text-red-600">{{ number_format(abs($movement->quantity), 2) }}</flux:text>
                                @else
                                <flux:text class="text-zinc-400">-</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:text class="font-medium text-blue-600">{{ number_format($movement->balance_after ?? 0, 2) }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm">{{ $movement->user->name ?? 'N/A' }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm text-zinc-600">{{ $movement->notes ? Str::limit($movement->notes, 30) : '-' }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
            @else
            <div class="text-center py-12">
                <flux:icon name="document-text" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                <flux:text class="text-zinc-500">No se encontraron movimientos para los filtros seleccionados</flux:text>
            </div>
            @endif
        </flux:card>
    @endif
</div>
