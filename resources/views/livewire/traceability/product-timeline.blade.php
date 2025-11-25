<?php

use Livewire\Volt\Component;
use App\Models\{Product, InventoryMovement, Warehouse};
use Livewire\Attributes\{Computed, Layout, Url};
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public ?int $productId = null;

    #[Url]
    public ?int $warehouseId = null;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $movementType = '';

    public bool $showTimeline = true;

    public function mount(): void
    {
        // Only set defaults if not already set from URL
        if (empty($this->dateFrom)) {
            $this->dateFrom = now()->subMonths(3)->format('Y-m-d');
        }
        if (empty($this->dateTo)) {
            $this->dateTo = now()->format('Y-m-d');
        }
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
    public function traceabilityData()
    {
        if (!$this->productId) {
            return collect([]);
        }

        $query = InventoryMovement::query()
            ->with(['product', 'warehouse', 'creator', 'purchase', 'dispatch', 'donation', 'transfer'])
            ->where('product_id', $this->productId);

        if ($this->warehouseId) {
            $query->where('warehouse_id', $this->warehouseId);
        }

        if ($this->dateFrom) {
            $query->whereDate('movement_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('movement_date', '<=', $this->dateTo);
        }

        if ($this->movementType) {
            $query->where('movement_type', $this->movementType);
        }

        return $query->orderBy('movement_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
    }

    #[Computed]
    public function traceabilitySummary()
    {
        if (!$this->productId) {
            return null;
        }

        $movements = InventoryMovement::where('product_id', $this->productId)
            ->when($this->warehouseId, fn($q) => $q->where('warehouse_id', $this->warehouseId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('movement_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('movement_date', '<=', $this->dateTo))
            ->get();

        $warehouses = $movements->pluck('warehouse_id')->unique()->count();
        $firstMovement = $movements->sortBy('movement_date')->first();
        $lastMovement = $movements->sortByDesc('movement_date')->first();

        return [
            'total_movements' => $movements->count(),
            'warehouses_count' => $warehouses,
            'first_movement' => $firstMovement,
            'last_movement' => $lastMovement,
            'total_entries' => $movements->whereIn('movement_type', ['entry', 'transfer_in'])->sum('quantity'),
            'total_exits' => $movements->whereIn('movement_type', ['exit', 'transfer_out'])->sum('quantity'),
        ];
    }

    #[Computed]
    public function locationHistory()
    {
        if (!$this->productId) {
            return collect([]);
        }

        return InventoryMovement::where('product_id', $this->productId)
            ->with('warehouse')
            ->when($this->dateFrom, fn($q) => $q->whereDate('movement_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('movement_date', '<=', $this->dateTo))
            ->select('warehouse_id', \DB::raw('MIN(movement_date) as first_seen'), \DB::raw('MAX(movement_date) as last_seen'), \DB::raw('COUNT(*) as movement_count'))
            ->groupBy('warehouse_id')
            ->orderBy('first_seen')
            ->get();
    }

    public function clearFilters(): void
    {
        $this->productId = null;
        $this->warehouseId = null;
        $this->movementType = '';
        $this->dateFrom = now()->subMonths(3)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function with(): array
    {
        return ['title' => __('Trazabilidad de Producto')];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">Trazabilidad de Producto</flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            Visualiza el recorrido completo de un producto desde su ingreso hasta su consumo final
        </flux:text>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <div class="space-y-6">
            <flux:heading size="lg">Filtros de Búsqueda</flux:heading>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Producto *</flux:label>
                    <flux:select wire:model.live="productId">
                        <flux:select.option value="">-- Seleccione un producto --</flux:select.option>
                        @foreach($this->products as $product)
                        <flux:select.option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Almacén</flux:label>
                    <flux:select wire:model.live="warehouseId">
                        <flux:select.option value="">-- Todos los almacenes --</flux:select.option>
                        @foreach($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de Movimiento</flux:label>
                    <flux:select wire:model.live="movementType">
                        <flux:select.option value="">-- Todos los tipos --</flux:select.option>
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

    @if(!$productId)
    <flux:card>
        <div class="text-center py-12">
            <flux:icon name="magnifying-glass" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="text-zinc-700 dark:text-zinc-300 mb-2">
                Selecciona un Producto
            </flux:heading>
            <flux:text class="text-zinc-500">
                Selecciona un producto para visualizar su trazabilidad completa
            </flux:text>
        </div>
    </flux:card>
    @else
        <!-- Summary Cards -->
        @if($this->traceabilitySummary)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <flux:card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-blue-600">Total Movimientos</flux:text>
                        <flux:heading size="2xl" class="text-blue-900">{{ number_format($this->traceabilitySummary['total_movements']) }}</flux:heading>
                    </div>
                    <flux:icon name="clipboard-document-list" class="h-8 w-8 text-blue-500" />
                </div>
            </flux:card>

            <flux:card class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-purple-600">Almacenes</flux:text>
                        <flux:heading size="2xl" class="text-purple-900">{{ $this->traceabilitySummary['warehouses_count'] }}</flux:heading>
                    </div>
                    <flux:icon name="building-storefront" class="h-8 w-8 text-purple-500" />
                </div>
            </flux:card>

            <flux:card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-green-600">Total Entradas</flux:text>
                        <flux:heading size="2xl" class="text-green-900">{{ number_format($this->traceabilitySummary['total_entries'], 2) }}</flux:heading>
                    </div>
                    <flux:icon name="arrow-down-tray" class="h-8 w-8 text-green-500" />
                </div>
            </flux:card>

            <flux:card class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-red-600">Total Salidas</flux:text>
                        <flux:heading size="2xl" class="text-red-900">{{ number_format($this->traceabilitySummary['total_exits'], 2) }}</flux:heading>
                    </div>
                    <flux:icon name="arrow-up-tray" class="h-8 w-8 text-red-500" />
                </div>
            </flux:card>
        </div>
        @endif

        <!-- Location History -->
        @if($this->locationHistory && $this->locationHistory->count() > 0)
        <flux:card class="mb-6">
            <flux:heading size="lg" class="mb-4">Historial de Ubicaciones</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->locationHistory as $location)
                <div class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <flux:text class="font-medium text-lg">{{ $location->warehouse->name }}</flux:text>
                            <div class="mt-2 space-y-1">
                                <flux:text class="text-sm text-zinc-600">
                                    Primera visita: {{ \Carbon\Carbon::parse($location->first_seen)->format('d/m/Y H:i') }}
                                </flux:text>
                                <flux:text class="text-sm text-zinc-600">
                                    Última visita: {{ \Carbon\Carbon::parse($location->last_seen)->format('d/m/Y H:i') }}
                                </flux:text>
                                <flux:badge color="blue" size="sm">{{ $location->movement_count }} movimientos</flux:badge>
                            </div>
                        </div>
                        <flux:icon name="map-pin" class="h-6 w-6 text-blue-500" />
                    </div>
                </div>
                @endforeach
            </div>
        </flux:card>
        @endif

        <!-- Timeline -->
        <flux:card>
            <div class="flex items-center justify-between mb-6">
                <flux:heading size="lg">Línea de Tiempo</flux:heading>
                <flux:button variant="outline" size="sm" icon="arrow-down-tray">
                    Exportar Timeline
                </flux:button>
            </div>

            @if($this->traceabilityData && $this->traceabilityData->count() > 0)
            <div class="relative">
                <!-- Timeline Line -->
                <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-zinc-200 dark:bg-zinc-700"></div>

                <!-- Timeline Items -->
                <div class="space-y-6">
                    @foreach($this->traceabilityData as $movement)
                    <div class="relative pl-20">
                        <!-- Timeline Dot -->
                        <div class="absolute left-6 -translate-x-1/2 w-4 h-4 rounded-full
                            {{ $movement->movement_type === 'entry' || $movement->movement_type === 'transfer_in' ? 'bg-green-500' :
                               ($movement->movement_type === 'exit' || $movement->movement_type === 'transfer_out' ? 'bg-red-500' : 'bg-purple-500') }}
                            border-4 border-white dark:border-zinc-900"></div>

                        <!-- Content Card -->
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 shadow-sm">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        @php
                                        $typeColors = ['entry' => 'green', 'exit' => 'red', 'transfer_in' => 'blue', 'transfer_out' => 'orange', 'adjustment' => 'purple'];
                                        $typeLabels = ['entry' => 'Entrada', 'exit' => 'Salida', 'transfer_in' => 'Transferencia Entrada', 'transfer_out' => 'Transferencia Salida', 'adjustment' => 'Ajuste'];
                                        @endphp
                                        <flux:badge color="{{ $typeColors[$movement->movement_type] ?? 'zinc' }}">
                                            {{ $typeLabels[$movement->movement_type] ?? $movement->movement_type }}
                                        </flux:badge>
                                        <flux:text class="text-sm font-medium">{{ $movement->warehouse->name }}</flux:text>
                                    </div>
                                    <flux:text class="text-xs text-zinc-500">
                                        {{ \Carbon\Carbon::parse($movement->movement_date)->format('d/m/Y H:i') }} • {{ $movement->creator->name ?? 'N/A' }}
                                    </flux:text>
                                </div>
                                <div class="text-right">
                                    <flux:text class="font-bold text-lg {{ in_array($movement->movement_type, ['entry', 'transfer_in']) ? 'text-green-600' : 'text-red-600' }}">
                                        {{ in_array($movement->movement_type, ['entry', 'transfer_in']) ? '+' : '-' }}{{ number_format($movement->quantity, 2) }}
                                    </flux:text>
                                    @if($movement->balance_after !== null)
                                    <flux:text class="text-sm text-zinc-500">
                                        Saldo: {{ number_format($movement->balance_after, 2) }}
                                    </flux:text>
                                    @endif
                                </div>
                            </div>

                            @if($movement->reference || $movement->notes)
                            <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                @if($movement->reference)
                                <flux:text class="text-sm"><strong>Referencia:</strong> {{ $movement->reference }}</flux:text>
                                @endif
                                @if($movement->notes)
                                <flux:text class="text-sm text-zinc-600 mt-1">{{ $movement->notes }}</flux:text>
                                @endif
                            </div>
                            @endif

                            <!-- Related Document Info -->
                            @if($movement->movementable)
                            <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                <flux:text class="text-sm text-zinc-600">
                                    <strong>Documento:</strong>
                                    @if($movement->movementable_type === 'App\\Models\\Purchase')
                                        Compra #{{ $movement->movementable->document_number }}
                                    @elseif($movement->movementable_type === 'App\\Models\\Dispatch')
                                        Despacho #{{ $movement->movementable->document_number }}
                                    @elseif($movement->movementable_type === 'App\\Models\\Donation')
                                        Donación #{{ $movement->movementable->document_number }}
                                    @elseif($movement->movementable_type === 'App\\Models\\InventoryTransfer')
                                        Traslado #{{ $movement->movementable->document_number }}
                                    @else
                                        {{ class_basename($movement->movementable_type) }}
                                    @endif
                                </flux:text>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            @if($this->traceabilityData->hasPages())
            <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->traceabilityData->links() }}
            </div>
            @endif
            @else
            <div class="text-center py-12">
                <flux:icon name="clock" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                <flux:text class="text-zinc-500">No se encontraron movimientos para este producto</flux:text>
            </div>
            @endif
        </flux:card>
    @endif
</div>
