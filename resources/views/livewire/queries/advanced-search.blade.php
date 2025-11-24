<?php

use Livewire\Volt\Component;
use App\Models\{InventoryMovement, Product, Warehouse, Supplier, User, Customer, Purchase, Dispatch, Donation};
use Livewire\Attributes\{Computed, Layout};
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    // Search filters
    public string $searchTerm = '';
    public string $searchType = 'all'; // all, product, movement, document
    public string $productCode = '';
    public string $documentNumber = '';
    public string $warehouse = '';
    public string $supplier = '';
    public string $customer = '';
    public string $user = '';
    public string $movementType = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $minAmount = 0;
    public int $maxAmount = 0;

    public function mount(): void
    {
        $this->dateFrom = now()->subMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    #[Computed]
    public function searchResults()
    {
        if ($this->searchType === 'product') {
            return $this->searchProducts();
        }

        if ($this->searchType === 'movement') {
            return $this->searchMovements();
        }

        if ($this->searchType === 'document') {
            return $this->searchDocuments();
        }

        // Search all
        return $this->searchAll();
    }

    protected function searchProducts()
    {
        $query = Product::query()
            ->with(['category', 'unitOfMeasure', 'supplier'])
            ->active();

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->searchTerm}%")
                  ->orWhere('sku', 'like', "%{$this->searchTerm}%")
                  ->orWhere('barcode', 'like', "%{$this->searchTerm}%")
                  ->orWhere('description', 'like', "%{$this->searchTerm}%");
            });
        }

        if ($this->productCode) {
            $query->where(function ($q) {
                $q->where('sku', 'like', "%{$this->productCode}%")
                  ->orWhere('barcode', 'like', "%{$this->productCode}%");
            });
        }

        if ($this->supplier) {
            $query->where('supplier_id', $this->supplier);
        }

        return $query->orderBy('name')->paginate(20);
    }

    protected function searchMovements()
    {
        $query = InventoryMovement::query()
            ->with([
                'product',
                'warehouse',
                'user',
                'movementable'
            ]);

        if ($this->searchTerm) {
            $query->whereHas('product', function ($q) {
                $q->where('name', 'like', "%{$this->searchTerm}%")
                  ->orWhere('sku', 'like', "%{$this->searchTerm}%");
            })->orWhere('reference', 'like', "%{$this->searchTerm}%")
              ->orWhere('notes', 'like', "%{$this->searchTerm}%");
        }

        if ($this->productCode) {
            $query->whereHas('product', function ($q) {
                $q->where('sku', 'like', "%{$this->productCode}%")
                  ->orWhere('barcode', 'like', "%{$this->productCode}%");
            });
        }

        if ($this->warehouse) {
            $query->where('warehouse_id', $this->warehouse);
        }

        if ($this->user) {
            $query->where('user_id', $this->user);
        }

        if ($this->movementType) {
            $query->where('movement_type', $this->movementType);
        }

        if ($this->dateFrom) {
            $query->whereDate('movement_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('movement_date', '<=', $this->dateTo);
        }

        if ($this->minAmount > 0) {
            $query->where('quantity', '>=', $this->minAmount);
        }

        if ($this->maxAmount > 0) {
            $query->where('quantity', '<=', $this->maxAmount);
        }

        return $query->orderBy('movement_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
    }

    protected function searchDocuments()
    {
        $results = collect();

        if ($this->documentNumber || $this->searchTerm) {
            $searchTerm = $this->documentNumber ?: $this->searchTerm;

            // Search purchases
            $purchases = Purchase::query()
                ->with(['supplier', 'warehouse'])
                ->when($searchTerm, function ($q) use ($searchTerm) {
                    $q->where('document_number', 'like', "%{$searchTerm}%")
                      ->orWhere('notes', 'like', "%{$searchTerm}%");
                })
                ->when($this->supplier, fn($q) => $q->where('supplier_id', $this->supplier))
                ->when($this->warehouse, fn($q) => $q->where('warehouse_id', $this->warehouse))
                ->when($this->dateFrom, fn($q) => $q->whereDate('purchase_date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn($q) => $q->whereDate('purchase_date', '<=', $this->dateTo))
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'type' => 'purchase',
                    'id' => $item->id,
                    'number' => $item->document_number,
                    'date' => $item->purchase_date,
                    'entity' => $item->supplier->name ?? 'N/A',
                    'warehouse' => $item->warehouse->name ?? 'N/A',
                    'total' => $item->total_amount,
                    'status' => $item->status,
                    'url' => route('purchases.show', $item)
                ]);

            // Search dispatches
            $dispatches = Dispatch::query()
                ->with(['customer', 'warehouse'])
                ->when($searchTerm, function ($q) use ($searchTerm) {
                    $q->where('document_number', 'like', "%{$searchTerm}%")
                      ->orWhere('notes', 'like', "%{$searchTerm}%");
                })
                ->when($this->customer, fn($q) => $q->where('customer_id', $this->customer))
                ->when($this->warehouse, fn($q) => $q->where('warehouse_id', $this->warehouse))
                ->when($this->dateFrom, fn($q) => $q->whereDate('dispatch_date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn($q) => $q->whereDate('dispatch_date', '<=', $this->dateTo))
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'type' => 'dispatch',
                    'id' => $item->id,
                    'number' => $item->document_number,
                    'date' => $item->dispatch_date,
                    'entity' => $item->customer->name ?? 'N/A',
                    'warehouse' => $item->warehouse->name ?? 'N/A',
                    'total' => $item->total_amount,
                    'status' => $item->status,
                    'url' => route('dispatches.show', $item)
                ]);

            // Search donations
            $donations = Donation::query()
                ->with(['warehouse'])
                ->when($searchTerm, function ($q) use ($searchTerm) {
                    $q->where('document_number', 'like', "%{$searchTerm}%")
                      ->orWhere('recipient_name', 'like', "%{$searchTerm}%")
                      ->orWhere('notes', 'like', "%{$searchTerm}%");
                })
                ->when($this->warehouse, fn($q) => $q->where('warehouse_id', $this->warehouse))
                ->when($this->dateFrom, fn($q) => $q->whereDate('donation_date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn($q) => $q->whereDate('donation_date', '<=', $this->dateTo))
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'type' => 'donation',
                    'id' => $item->id,
                    'number' => $item->document_number,
                    'date' => $item->donation_date,
                    'entity' => $item->recipient_name,
                    'warehouse' => $item->warehouse->name ?? 'N/A',
                    'total' => 0,
                    'status' => $item->status,
                    'url' => route('donations.show', $item)
                ]);

            $results = $purchases->concat($dispatches)->concat($donations);
        }

        return $results->sortByDesc('date')->values();
    }

    protected function searchAll()
    {
        return collect([
            'products' => $this->searchProducts()->take(5),
            'movements' => $this->searchMovements()->take(5),
            'documents' => $this->searchDocuments()->take(5)
        ]);
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::active()->get(['id', 'name']);
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::active()->get(['id', 'name']);
    }

    #[Computed]
    public function customers()
    {
        return Customer::active()->get(['id', 'name']);
    }

    #[Computed]
    public function users()
    {
        return User::whereNull('deleted_at')->get(['id', 'name']);
    }

    public function clearFilters(): void
    {
        $this->searchTerm = '';
        $this->productCode = '';
        $this->documentNumber = '';
        $this->warehouse = '';
        $this->supplier = '';
        $this->customer = '';
        $this->user = '';
        $this->movementType = '';
        $this->minAmount = 0;
        $this->maxAmount = 0;
        $this->dateFrom = now()->subMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function updatingSearchTerm(): void
    {
        $this->resetPage();
    }

    public function updatingSearchType(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'title' => __('Búsqueda Avanzada'),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
            Búsqueda Avanzada
        </flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            Busca productos, movimientos y documentos con múltiples criterios
        </flux:text>
    </div>

    <!-- Search Type Selector -->
    <flux:card class="mb-6">
        <div class="flex flex-wrap gap-2">
            <flux:button
                variant="{{ $searchType === 'all' ? 'primary' : 'outline' }}"
                wire:click="$set('searchType', 'all')"
                size="sm">
                Buscar Todo
            </flux:button>
            <flux:button
                variant="{{ $searchType === 'product' ? 'primary' : 'outline' }}"
                wire:click="$set('searchType', 'product')"
                size="sm">
                Productos
            </flux:button>
            <flux:button
                variant="{{ $searchType === 'movement' ? 'primary' : 'outline' }}"
                wire:click="$set('searchType', 'movement')"
                size="sm">
                Movimientos
            </flux:button>
            <flux:button
                variant="{{ $searchType === 'document' ? 'primary' : 'outline' }}"
                wire:click="$set('searchType', 'document')"
                size="sm">
                Documentos
            </flux:button>
        </div>
    </flux:card>

    <!-- Advanced Filters -->
    <flux:card class="mb-6">
        <div class="space-y-6">
            <!-- Main search -->
            <div>
                <flux:input
                    wire:model.live.debounce.500ms="searchTerm"
                    placeholder="Buscar por nombre, código, descripción, número de documento..."
                    icon="magnifying-glass"
                    class="w-full" />
            </div>

            <!-- Filter Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Product Code -->
                <flux:field>
                    <flux:label>Código de Producto</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="productCode"
                        placeholder="SKU o código de barras" />
                </flux:field>

                <!-- Document Number -->
                <flux:field>
                    <flux:label>Número de Documento</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="documentNumber"
                        placeholder="Factura, despacho, etc." />
                </flux:field>

                <!-- Warehouse -->
                <flux:field>
                    <flux:label>Almacén</flux:label>
                    <flux:select wire:model.live="warehouse" placeholder="Todos los almacenes">
                        @foreach($this->warehouses as $w)
                        <flux:select.option value="{{ $w->id }}">{{ $w->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Supplier -->
                <flux:field>
                    <flux:label>Proveedor</flux:label>
                    <flux:select wire:model.live="supplier" placeholder="Todos los proveedores">
                        @foreach($this->suppliers as $s)
                        <flux:select.option value="{{ $s->id }}">{{ $s->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Customer -->
                <flux:field>
                    <flux:label>Cliente</flux:label>
                    <flux:select wire:model.live="customer" placeholder="Todos los clientes">
                        @foreach($this->customers as $c)
                        <flux:select.option value="{{ $c->id }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- User -->
                <flux:field>
                    <flux:label>Usuario</flux:label>
                    <flux:select wire:model.live="user" placeholder="Todos los usuarios">
                        @foreach($this->users as $u)
                        <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Movement Type -->
                @if($searchType === 'movement' || $searchType === 'all')
                <flux:field>
                    <flux:label>Tipo de Movimiento</flux:label>
                    <flux:select wire:model.live="movementType" placeholder="Todos los tipos">
                        <flux:select.option value="entry">Entrada</flux:select.option>
                        <flux:select.option value="exit">Salida</flux:select.option>
                        <flux:select.option value="transfer_out">Transferencia Salida</flux:select.option>
                        <flux:select.option value="transfer_in">Transferencia Entrada</flux:select.option>
                        <flux:select.option value="adjustment">Ajuste</flux:select.option>
                    </flux:select>
                </flux:field>
                @endif

                <!-- Date From -->
                <flux:field>
                    <flux:label>Fecha Desde</flux:label>
                    <flux:input
                        type="date"
                        wire:model.live="dateFrom" />
                </flux:field>

                <!-- Date To -->
                <flux:field>
                    <flux:label>Fecha Hasta</flux:label>
                    <flux:input
                        type="date"
                        wire:model.live="dateTo" />
                </flux:field>
            </div>

            <!-- Amount Range (for movements) -->
            @if($searchType === 'movement' || $searchType === 'all')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:field>
                    <flux:label>Cantidad Mínima</flux:label>
                    <flux:input
                        type="number"
                        wire:model.live="minAmount"
                        placeholder="0" />
                </flux:field>

                <flux:field>
                    <flux:label>Cantidad Máxima</flux:label>
                    <flux:input
                        type="number"
                        wire:model.live="maxAmount"
                        placeholder="Sin límite" />
                </flux:field>
            </div>
            @endif

            <!-- Clear Filters -->
            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                    Limpiar Filtros
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Results Section -->
    @if($searchType === 'product')
        <!-- Product Results -->
        <flux:card>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Producto</flux:table.column>
                        <flux:table.column>SKU / Código</flux:table.column>
                        <flux:table.column>Categoría</flux:table.column>
                        <flux:table.column>Proveedor</flux:table.column>
                        <flux:table.column>Precio</flux:table.column>
                        <flux:table.column>Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($this->searchResults as $product)
                        <flux:table.row wire:key="product-{{ $product->id }}">
                            <flux:table.cell>
                                <flux:text class="font-medium">{{ $product->name }}</flux:text>
                                @if($product->description)
                                <flux:text class="text-sm text-zinc-500 mt-1">{{ Str::limit($product->description, 50) }}</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text>{{ $product->sku }}</flux:text>
                                @if($product->barcode)
                                <flux:text class="text-sm text-zinc-500 block">{{ $product->barcode }}</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($product->category)
                                <flux:badge color="blue" size="sm">{{ $product->category->name }}</flux:badge>
                                @else
                                <flux:text class="text-zinc-400 text-sm">Sin categoría</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($product->supplier)
                                <flux:text>{{ $product->supplier->name }}</flux:text>
                                @else
                                <flux:text class="text-zinc-400 text-sm">Sin proveedor</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="font-medium">${{ number_format($product->cost_price, 2) }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button variant="ghost" size="sm" href="{{ route('inventory.products.show', $product) }}" wire:navigate>
                                    Ver Detalles
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                        @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-12">
                                <flux:icon name="magnifying-glass" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                                <flux:text class="text-zinc-500">No se encontraron productos</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            @if($this->searchResults->hasPages())
            <div class="mt-6 px-6 pb-6">
                {{ $this->searchResults->links() }}
            </div>
            @endif
        </flux:card>
    @elseif($searchType === 'movement')
        <!-- Movement Results -->
        <flux:card>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Fecha</flux:table.column>
                        <flux:table.column>Producto</flux:table.column>
                        <flux:table.column>Tipo</flux:table.column>
                        <flux:table.column>Cantidad</flux:table.column>
                        <flux:table.column>Almacén</flux:table.column>
                        <flux:table.column>Usuario</flux:table.column>
                        <flux:table.column>Referencia</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($this->searchResults as $movement)
                        <flux:table.row wire:key="movement-{{ $movement->id }}">
                            <flux:table.cell>
                                <flux:text class="font-medium">{{ $movement->movement_date->format('d/m/Y') }}</flux:text>
                                <flux:text class="text-sm text-zinc-500 block">{{ $movement->movement_date->format('H:i') }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="font-medium">{{ $movement->product->name }}</flux:text>
                                <flux:text class="text-sm text-zinc-500 block">{{ $movement->product->sku }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                $typeColors = [
                                    'entry' => 'green',
                                    'exit' => 'red',
                                    'transfer_in' => 'blue',
                                    'transfer_out' => 'orange',
                                    'adjustment' => 'purple'
                                ];
                                $typeLabels = [
                                    'entry' => 'Entrada',
                                    'exit' => 'Salida',
                                    'transfer_in' => 'Transferencia Entrada',
                                    'transfer_out' => 'Transferencia Salida',
                                    'adjustment' => 'Ajuste'
                                ];
                                @endphp
                                <flux:badge color="{{ $typeColors[$movement->movement_type] ?? 'zinc' }}" size="sm">
                                    {{ $typeLabels[$movement->movement_type] ?? $movement->movement_type }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="font-medium">{{ number_format($movement->quantity, 2) }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text>{{ $movement->warehouse->name }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text>{{ $movement->user->name }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($movement->reference)
                                <flux:text class="text-sm">{{ $movement->reference }}</flux:text>
                                @else
                                <flux:text class="text-zinc-400 text-sm">Sin referencia</flux:text>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                        @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-12">
                                <flux:icon name="magnifying-glass" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                                <flux:text class="text-zinc-500">No se encontraron movimientos</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            @if($this->searchResults->hasPages())
            <div class="mt-6 px-6 pb-6">
                {{ $this->searchResults->links() }}
            </div>
            @endif
        </flux:card>
    @elseif($searchType === 'document')
        <!-- Document Results -->
        <flux:card>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Tipo</flux:table.column>
                        <flux:table.column>Número</flux:table.column>
                        <flux:table.column>Fecha</flux:table.column>
                        <flux:table.column>Entidad</flux:table.column>
                        <flux:table.column>Almacén</flux:table.column>
                        <flux:table.column>Total</flux:table.column>
                        <flux:table.column>Estado</flux:table.column>
                        <flux:table.column>Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($this->searchResults as $document)
                        <flux:table.row wire:key="document-{{ $document['type'] }}-{{ $document['id'] }}">
                            <flux:table.cell>
                                @php
                                $docTypeColors = [
                                    'purchase' => 'green',
                                    'dispatch' => 'red',
                                    'donation' => 'blue'
                                ];
                                $docTypeLabels = [
                                    'purchase' => 'Compra',
                                    'dispatch' => 'Despacho',
                                    'donation' => 'Donación'
                                ];
                                @endphp
                                <flux:badge color="{{ $docTypeColors[$document['type']] ?? 'zinc' }}" size="sm">
                                    {{ $docTypeLabels[$document['type']] ?? $document['type'] }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="font-medium">{{ $document['number'] }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text>{{ $document['date']->format('d/m/Y') }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text>{{ $document['entity'] }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text>{{ $document['warehouse'] }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($document['total'] > 0)
                                <flux:text class="font-medium">${{ number_format($document['total'], 2) }}</flux:text>
                                @else
                                <flux:text class="text-zinc-400 text-sm">N/A</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                $statusColors = [
                                    'draft' => 'zinc',
                                    'pending' => 'yellow',
                                    'completed' => 'green',
                                    'cancelled' => 'red'
                                ];
                                @endphp
                                <flux:badge color="{{ $statusColors[$document['status']] ?? 'zinc' }}" size="sm">
                                    {{ ucfirst($document['status']) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button variant="ghost" size="sm" href="{{ $document['url'] }}" wire:navigate>
                                    Ver Detalles
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                        @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-12">
                                <flux:icon name="magnifying-glass" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                                <flux:text class="text-zinc-500">No se encontraron documentos</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>
    @else
        <!-- All Results (Combined View) -->
        <div class="space-y-6">
            <!-- Products Section -->
            @if($this->searchResults['products']->count() > 0)
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Productos</flux:heading>
                    <flux:button variant="ghost" size="sm" wire:click="$set('searchType', 'product')">
                        Ver Todos
                    </flux:button>
                </div>
                <div class="space-y-2">
                    @foreach($this->searchResults['products'] as $product)
                    <div class="flex items-center justify-between p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-lg">
                        <div>
                            <flux:text class="font-medium">{{ $product->name }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">{{ $product->sku }}</flux:text>
                        </div>
                        <flux:button variant="ghost" size="sm" href="{{ route('inventory.products.show', $product) }}" wire:navigate>
                            Ver
                        </flux:button>
                    </div>
                    @endforeach
                </div>
            </flux:card>
            @endif

            <!-- Movements Section -->
            @if($this->searchResults['movements']->count() > 0)
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Movimientos</flux:heading>
                    <flux:button variant="ghost" size="sm" wire:click="$set('searchType', 'movement')">
                        Ver Todos
                    </flux:button>
                </div>
                <div class="space-y-2">
                    @foreach($this->searchResults['movements'] as $movement)
                    <div class="flex items-center justify-between p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-lg">
                        <div class="flex-1">
                            <flux:text class="font-medium">{{ $movement->product->name }}</flux:text>
                            <div class="flex items-center gap-2 mt-1">
                                <flux:text class="text-sm text-zinc-500">{{ $movement->movement_date->format('d/m/Y') }}</flux:text>
                                <flux:badge color="blue" size="xs">{{ $movement->movement_type }}</flux:badge>
                                <flux:text class="text-sm text-zinc-500">{{ number_format($movement->quantity, 2) }}</flux:text>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </flux:card>
            @endif

            <!-- Documents Section -->
            @if($this->searchResults['documents']->count() > 0)
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Documentos</flux:heading>
                    <flux:button variant="ghost" size="sm" wire:click="$set('searchType', 'document')">
                        Ver Todos
                    </flux:button>
                </div>
                <div class="space-y-2">
                    @foreach($this->searchResults['documents'] as $document)
                    <div class="flex items-center justify-between p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-lg">
                        <div>
                            <flux:text class="font-medium">{{ $document['number'] }}</flux:text>
                            <div class="flex items-center gap-2 mt-1">
                                <flux:badge color="blue" size="xs">{{ $document['type'] }}</flux:badge>
                                <flux:text class="text-sm text-zinc-500">{{ $document['entity'] }}</flux:text>
                            </div>
                        </div>
                        <flux:button variant="ghost" size="sm" href="{{ $document['url'] }}" wire:navigate>
                            Ver
                        </flux:button>
                    </div>
                    @endforeach
                </div>
            </flux:card>
            @endif

            @if($this->searchResults['products']->count() === 0 && $this->searchResults['movements']->count() === 0 && $this->searchResults['documents']->count() === 0)
            <flux:card>
                <div class="text-center py-12">
                    <flux:icon name="magnifying-glass" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                    <flux:text class="text-zinc-500">No se encontraron resultados</flux:text>
                    <flux:text class="text-sm text-zinc-400 mt-2">
                        Intenta cambiar los filtros o términos de búsqueda
                    </flux:text>
                </div>
            </flux:card>
            @endif
        </div>
    @endif
</div>
