<?php

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $activeTab = 'entry';

    public string $search = '';

    #[Url(as: 'q', history: true)]
    public string $movementSearch = '';

    #[Url(as: 'producto', history: true)]
    public ?int $filterProductId = null;

    #[Url(as: 'bodega', history: true)]
    public ?int $filterWarehouseId = null;

    #[Url(as: 'tipo', history: true)]
    public ?string $filterMovementType = null;

    #[Url(history: true)]
    public int $perPage = 20;

    public ?int $selectedProductId = null;

    public ?int $selectedWarehouseId = null;

    public string $quantity = '';

    public string $notes = '';

    public string $referenceNumber = '';

    public string $lotNumber = '';

    public ?string $expirationDate = null;

    public bool $showEntryForm = false;

    public bool $showExitForm = false;

    public bool $showMovementModal = false;

    public ?int $viewingMovementId = null;

    public function mount(): void
    {
        // Component initialization
    }

    public function updatedMovementSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterProductId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterWarehouseId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMovementType(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        return Product::when($this->search, function ($query) {
            $query->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('sku', 'like', '%'.$this->search.'%');
        })->orderBy('name')->get();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get();
    }

    #[Computed]
    public function movements()
    {
        return InventoryMovement::query()
            ->with(['product', 'warehouse', 'transfer', 'dispatch', 'purchase', 'donation', 'adjustment'])
            ->when($this->movementSearch, function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('name', 'like', '%'.$this->movementSearch.'%')
                        ->orWhere('sku', 'like', '%'.$this->movementSearch.'%');
                });
            })
            ->when($this->filterProductId, fn ($q) => $q->where('product_id', $this->filterProductId))
            ->when($this->filterWarehouseId, fn ($q) => $q->where('warehouse_id', $this->filterWarehouseId))
            ->when($this->filterMovementType, function ($query) {
                // Special handling for donation filter - filter by donation_id presence
                if ($this->filterMovementType === 'donation') {
                    $query->whereNotNull('donation_id');
                } elseif ($this->filterMovementType === 'purchase') {
                    // For purchase, only show actual purchases (not donations)
                    $query->where('movement_type', 'purchase')->whereNull('donation_id');
                } else {
                    $query->where('movement_type', $this->filterMovementType);
                }
            })
            ->latest('movement_date')
            ->latest('id')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function viewingMovement()
    {
        if (! $this->viewingMovementId) {
            return null;
        }

        return InventoryMovement::with([
            'product.unitOfMeasure',
            'product.category',
            'warehouse',
            'fromWarehouse',
            'toWarehouse',
            'transfer',
            'dispatch',
            'purchase',
            'donation',
            'adjustment',
            'creator',
            'confirmedBy',
            'approvedBy',
            'completedBy',
            'movementReason',
        ])->find($this->viewingMovementId);
    }

    public function clearFilters(): void
    {
        $this->movementSearch = '';
        $this->filterProductId = null;
        $this->filterWarehouseId = null;
        $this->filterMovementType = null;
        $this->resetPage();
    }

    public function viewMovement(int $movementId): void
    {
        $this->viewingMovementId = $movementId;
        $this->showMovementModal = true;
    }

    public function closeMovementModal(): void
    {
        $this->showMovementModal = false;
        $this->viewingMovementId = null;
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetForm();
    }

    public function showForm(string $type): void
    {
        if ($type === 'entry') {
            $this->showEntryForm = true;
            $this->showExitForm = false;
        } else {
            $this->showExitForm = true;
            $this->showEntryForm = false;
        }
        $this->resetForm();
    }

    public function hideForm(): void
    {
        $this->showEntryForm = false;
        $this->showExitForm = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->selectedProductId = null;
        $this->selectedWarehouseId = null;
        $this->quantity = '';
        $this->notes = '';
        $this->referenceNumber = '';
        $this->lotNumber = '';
        $this->expirationDate = null;
    }

    public function recordEntry(): void
    {
        $this->validate([
            'selectedProductId' => 'required|exists:products,id',
            'selectedWarehouseId' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.01',
            'referenceNumber' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'lotNumber' => 'nullable|string|max:255',
            'expirationDate' => 'nullable|date|after:today',
        ], [], [
            'selectedProductId' => __('Product'),
            'selectedWarehouseId' => __('Warehouse'),
            'quantity' => __('Quantity'),
            'referenceNumber' => __('Reference Number'),
            'notes' => __('Notes'),
            'lotNumber' => __('Lot Number'),
            'expirationDate' => __('Expiration Date'),
        ]);

        // Find existing inventory or create new one
        $inventory = Inventory::firstOrNew([
            'product_id' => $this->selectedProductId,
            'warehouse_id' => $this->selectedWarehouseId,
            'lot_number' => $this->lotNumber,
        ]);

        $product = Product::find($this->selectedProductId);

        // Update inventory
        $inventory->quantity = ($inventory->quantity ?? 0) + (float) $this->quantity;
        $inventory->unit_cost = $product->cost;
        $inventory->location = $inventory->location ?? 'A-01-01'; // Default location
        $inventory->expiration_date = $this->expirationDate;
        $inventory->is_active = true;

        $inventory->save();

        // Flash success message
        session()->flash('success', __('Product entry recorded successfully'));

        $this->hideForm();
    }

    public function recordExit(): void
    {
        $this->validate([
            'selectedProductId' => 'required|exists:products,id',
            'selectedWarehouseId' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.01',
            'referenceNumber' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ], [], [
            'selectedProductId' => __('Product'),
            'selectedWarehouseId' => __('Warehouse'),
            'quantity' => __('Quantity'),
            'referenceNumber' => __('Reference Number'),
            'notes' => __('Notes'),
        ]);

        // Find existing inventory
        $inventory = Inventory::where([
            'product_id' => $this->selectedProductId,
            'warehouse_id' => $this->selectedWarehouseId,
        ])->where('available_quantity', '>=', $this->quantity)->first();

        if (! $inventory) {
            $this->addError('quantity', __('Insufficient stock available'));

            return;
        }

        // Update inventory
        $inventory->quantity = $inventory->quantity - (float) $this->quantity;
        $inventory->save();

        // Flash success message
        session()->flash('success', __('Product exit recorded successfully'));

        $this->hideForm();
    }

    public function with(): array
    {
        return [
            'title' => __('Movement History'),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Operaciones de Bodega</flux:heading>
            <flux:text class="mt-1">Registre entradas y salidas de productos para operaciones de bodega</flux:text>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-4">
        <flux:button variant="primary" icon="plus" wire:click="showForm('entry')"
            class="bg-green-600 hover:bg-green-700 text-white">
            Registrar Entrada
        </flux:button>

        <flux:button variant="danger" icon="minus" wire:click="showForm('exit')"
            class="bg-red-600 hover:bg-red-700 text-white">
            Registrar Salida
        </flux:button>

        <flux:button variant="outline" icon="arrow-path" :href="route('transfers.index')" wire:navigate>
            Crear Traslado
        </flux:button>

        <flux:button variant="outline" icon="chart-bar" :href="route('reports.kardex')" wire:navigate>
            Generar Reporte
        </flux:button>
    </div>

    <!-- Success Message -->
    @if (session('success'))
    <flux:callout color="green" class="mb-6">
        {{ session('success') }}
    </flux:callout>
    @endif

    <!-- Movement History Section -->
    <flux:card>
        <div class="flex items-center justify-between mb-6">
            <div>
                <flux:heading size="lg">Historial de Movimientos</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Historial completo de todas las transacciones de inventario
                </flux:text>
            </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <flux:field>
                <flux:label>Buscar Producto</flux:label>
                <flux:input wire:model.live.debounce.300ms="movementSearch" placeholder="Buscar por nombre o SKU" />
            </flux:field>

            <flux:field>
                <flux:label>Producto</flux:label>
                <flux:select wire:model.live="filterProductId" placeholder="Todos los productos">
                    <flux:select.option value="">Todos los productos</flux:select.option>
                    @foreach($this->products as $product)
                    <flux:select.option value="{{ $product->id }}">
                        {{ $product->name }}
                    </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Bodega</flux:label>
                <flux:select wire:model.live="filterWarehouseId" placeholder="Todas las bodegas">
                    <flux:select.option value="">Todas las bodegas</flux:select.option>
                    @foreach($this->warehouses as $warehouse)
                    <flux:select.option value="{{ $warehouse->id }}">
                        {{ $warehouse->name }}
                    </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Tipo de Movimiento</flux:label>
                <flux:select wire:model.live="filterMovementType" placeholder="Todos los tipos">
                    <flux:select.option value="">Todos los tipos</flux:select.option>
                    <flux:select.option value="purchase">Compra</flux:select.option>
                    <flux:select.option value="sale">Venta</flux:select.option>
                    <flux:select.option value="transfer_in">Traslado Entrada</flux:select.option>
                    <flux:select.option value="transfer_out">Traslado Salida</flux:select.option>
                    <flux:select.option value="adjustment">Ajuste</flux:select.option>
                    <flux:select.option value="donation">Donación</flux:select.option>
                    <flux:select.option value="expiry">Vencimiento</flux:select.option>
                    <flux:select.option value="return">Devolución</flux:select.option>
                </flux:select>
            </flux:field>
        </div>

        @if($this->movementSearch || $this->filterProductId || $this->filterWarehouseId || $this->filterMovementType)
        <div class="mb-4">
            <flux:button size="sm" variant="outline" wire:click="clearFilters">
                Limpiar Filtros
            </flux:button>
        </div>
        @endif

        <!-- Stats and Per Page -->
        <div class="flex items-center justify-between mb-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Mostrando {{ $this->movements->firstItem() ?? 0 }} - {{ $this->movements->lastItem() ?? 0 }} de {{ $this->movements->total() }} movimientos
            </div>
            <div class="flex items-center gap-2">
                <flux:text class="text-sm">Por página:</flux:text>
                <flux:select wire:model.live="perPage" class="w-20">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </flux:select>
            </div>
        </div>

        <!-- Movements Table -->
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Fecha</flux:table.column>
                    <flux:table.column>Tipo</flux:table.column>
                    <flux:table.column>Producto</flux:table.column>
                    <flux:table.column>Bodega</flux:table.column>
                    <flux:table.column class="text-right">Cantidad</flux:table.column>
                    <flux:table.column class="text-right">Saldo</flux:table.column>
                    <flux:table.column>Referencia / Razón</flux:table.column>
                    <flux:table.column class="text-center">Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->movements as $movement)
                    <flux:table.row>
                        <flux:table.cell>
                            {{ $movement->movement_date ? $movement->movement_date->format('d/m/Y H:i') : '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                            $typeColors = [
                                'purchase' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'donation' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'transfer_in' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
                                'transfer_out' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                'adjustment' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                'sale' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                'expiry' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                                'return' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300',
                                'in' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'out' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                            ];
                            $typeLabels = [
                                'purchase' => 'Compra',
                                'donation' => 'Donación',
                                'transfer_in' => 'Traslado Ent.',
                                'transfer_out' => 'Traslado Sal.',
                                'adjustment' => 'Ajuste',
                                'sale' => 'Venta',
                                'expiry' => 'Vencimiento',
                                'return' => 'Devolución',
                                'in' => 'Entrada',
                                'out' => 'Salida',
                            ];
                            // Determine display type based on related documents
                            $displayType = $movement->movement_type;
                            if ($movement->donation_id) {
                                $displayType = 'donation';
                            } elseif ($movement->purchase_id) {
                                $displayType = 'purchase';
                            }
                            $colorClass = $typeColors[$displayType] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $colorClass }}">
                                {{ $typeLabels[$displayType] ?? ucfirst(str_replace('_', ' ', $displayType)) }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-medium">{{ $movement->product?->name ?? '-' }}</div>
                            <div class="text-xs text-zinc-500">{{ $movement->product?->sku ?? '-' }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $movement->warehouse?->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <span class="{{ $movement->quantity >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="text-right font-medium">
                            {{ number_format($movement->balance_quantity ?? 0, 2) }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($movement->adjustment)
                                <!-- Adjustment Information -->
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                            {{ $movement->adjustment->adjustment_type_spanish }}
                                        </span>
                                    </div>
                                    @if($movement->adjustment->reason)
                                        <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ Str::limit($movement->adjustment->reason, 50) }}
                                        </div>
                                    @endif
                                    @if($movement->adjustment->justification)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ Str::limit($movement->adjustment->justification, 60) }}
                                        </div>
                                    @endif
                                    <div class="text-xs text-zinc-400">
                                        Ajuste: {{ $movement->adjustment->adjustment_number }}
                                    </div>
                                </div>
                            @else
                                <!-- Regular Movement Information -->
                                @if($movement->document_number)
                                    <div class="text-sm">{{ $movement->document_number }}</div>
                                @endif
                                @if($movement->notes)
                                    <div class="text-xs text-zinc-500">{{ Str::limit($movement->notes, 40) }}</div>
                                @endif
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-center">
                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewMovement({{ $movement->id }})">
                                Ver
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-8">
                            <flux:icon name="document-text" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                            <flux:text class="text-zinc-500">No se encontraron movimientos</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $this->movements->links() }}
        </div>
    </flux:card>

    <!-- Entry Form Modal -->

    <flux:modal name="entry-form" class="max-w-2xl" wire:model.self="showEntryForm">
        <flux:heading>
            <flux:heading size="lg">Registrar Entrada</flux:heading>
        </flux:heading>

        <div class="space-y-6">
            <div class="space-y-6">
                <!-- Product Selection -->
                <flux:field>
                    <flux:label>Producto <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model="selectedProductId" placeholder="Seleccione un producto">
                        @foreach($this->products as $product)
                        <flux:select.option value="{{ $product->id }}">
                            {{ $product->name }} ({{ $product->sku }})
                        </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="selectedProductId" />
                </flux:field>

                <!-- Warehouse Selection -->
                <flux:field>
                    <flux:label>Bodega <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model="selectedWarehouseId" placeholder="Seleccione una bodega">
                        @foreach($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">
                            {{ $warehouse->name }} - {{ $warehouse->location }}
                        </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="selectedWarehouseId" />
                </flux:field>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Quantity -->
                    <flux:field>
                        <flux:label>Cantidad <span class="text-red-500">*</span></flux:label>
                        <flux:input type="number" step="0.01" min="0.01" wire:model="quantity" placeholder="0.00" />
                        <flux:error name="quantity" />
                    </flux:field>

                    <!-- Reference Number -->
                    <flux:field>
                        <flux:label>Número de Referencia</flux:label>
                        <flux:input wire:model="referenceNumber" placeholder="Referencia opcional" />
                        <flux:error name="referenceNumber" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Lot Number -->
                    <flux:field>
                        <flux:label>Número de Lote</flux:label>
                        <flux:input wire:model="lotNumber" placeholder="Número de lote opcional" />
                        <flux:error name="lotNumber" />
                    </flux:field>

                    <!-- Expiration Date -->
                    <flux:field>
                        <flux:label>Fecha de Vencimiento</flux:label>
                        <flux:input type="date" wire:model="expirationDate" />
                        <flux:error name="expirationDate" />
                    </flux:field>
                </div>

                <!-- Notes -->
                <flux:field>
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" placeholder="Notas o comentarios adicionales" rows="3" />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </div>

        <div class="flex gap-3 pt-4">
            <flux:button variant="outline" wire:click="hideForm">
                Cancelar
            </flux:button>
            <flux:button variant="primary" wire:click="recordEntry" wire:loading.attr="disabled"
                class="bg-green-600 hover:bg-green-700">
                <span wire:loading.remove>Registrar Entrada</span>
                <span wire:loading>Guardando...</span>
            </flux:button>
        </div>
    </flux:modal>


    <!-- Exit Form Modal -->

    <flux:modal name="exit-form" class="max-w-2xl" wire:model.self="showExitForm">
        <flux:heading>
            <flux:heading size="lg">Registrar Salida</flux:heading>
        </flux:heading>

        <div class="space-y-6">
            <div class="space-y-6">
                <!-- Product Selection -->
                <flux:field>
                    <flux:label>Producto <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model="selectedProductId" placeholder="Seleccione un producto">
                        @foreach($this->products as $product)
                        <flux:select.option value="{{ $product->id }}">
                            {{ $product->name }} ({{ $product->sku }})
                        </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="selectedProductId" />
                </flux:field>

                <!-- Warehouse Selection -->
                <flux:field>
                    <flux:label>Bodega <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model="selectedWarehouseId" placeholder="Seleccione una bodega">
                        @foreach($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">
                            {{ $warehouse->name }} - {{ $warehouse->location }}
                        </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="selectedWarehouseId" />
                </flux:field>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Quantity -->
                    <flux:field>
                        <flux:label>Cantidad <span class="text-red-500">*</span></flux:label>
                        <flux:input type="number" step="0.01" min="0.01" wire:model="quantity" placeholder="0.00" />
                        <flux:error name="quantity" />
                    </flux:field>

                    <!-- Reference Number -->
                    <flux:field>
                        <flux:label>Número de Referencia</flux:label>
                        <flux:input wire:model="referenceNumber" placeholder="Referencia opcional" />
                        <flux:error name="referenceNumber" />
                    </flux:field>
                </div>

                <!-- Notes -->
                <flux:field>
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" placeholder="Notas o comentarios adicionales" rows="3" />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </div>

        <div class="flex gap-3 pt-4">
            <flux:button variant="outline" wire:click="hideForm">
                Cancelar
            </flux:button>
            <flux:button variant="danger" wire:click="recordExit" wire:loading.attr="disabled"
                class="bg-red-600 hover:bg-red-700">
                <span wire:loading.remove>Registrar Salida</span>
                <span wire:loading>Guardando...</span>
            </flux:button>
        </div>
    </flux:modal>

    <!-- Movement Details Modal -->
    <flux:modal name="movement-details" class="max-w-3xl" wire:model.self="showMovementModal">
        @if($this->viewingMovement)
        @php $movement = $this->viewingMovement; @endphp
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <flux:heading size="lg">Detalle del Movimiento</flux:heading>
                <flux:text class="text-zinc-500">{{ $movement->document_number ?? 'Sin número de documento' }}</flux:text>
            </div>

            <!-- Movement Type and Date -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Tipo de Movimiento</flux:text>
                    @php
                    $typeColors = [
                        'purchase' => 'green',
                        'donation' => 'blue',
                        'transfer_in' => 'indigo',
                        'transfer_out' => 'purple',
                        'adjustment' => 'yellow',
                        'sale' => 'red',
                        'expiry' => 'orange',
                        'return' => 'teal',
                        'in' => 'green',
                        'out' => 'red',
                    ];
                    $typeLabelsModal = [
                        'purchase' => 'Compra',
                        'donation' => 'Donación',
                        'transfer_in' => 'Traslado Entrada',
                        'transfer_out' => 'Traslado Salida',
                        'adjustment' => 'Ajuste',
                        'sale' => 'Venta',
                        'expiry' => 'Vencimiento',
                        'return' => 'Devolución',
                        'in' => 'Entrada',
                        'out' => 'Salida',
                    ];
                    // Determine display type based on related documents
                    $displayTypeModal = $movement->movement_type;
                    if ($movement->donation_id) {
                        $displayTypeModal = 'donation';
                    } elseif ($movement->purchase_id) {
                        $displayTypeModal = 'purchase';
                    }
                    @endphp
                    <flux:badge color="{{ $typeColors[$displayTypeModal] ?? 'zinc' }}" size="sm" class="mt-1">
                        {{ $typeLabelsModal[$displayTypeModal] ?? ucfirst(str_replace('_', ' ', $displayTypeModal)) }}
                    </flux:badge>
                </div>
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Fecha del Movimiento</flux:text>
                    <flux:text class="font-semibold">{{ $movement->movement_date?->format('d/m/Y') ?? '-' }}</flux:text>
                </div>
            </div>

            <!-- Product Information -->
            <div class="border dark:border-zinc-700 rounded-lg p-4">
                <flux:heading size="sm" class="mb-3">Información del Producto</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Producto</flux:text>
                        <flux:text class="font-medium">{{ $movement->product?->name ?? '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">SKU</flux:text>
                        <flux:text class="font-medium">{{ $movement->product?->sku ?? '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Categoría</flux:text>
                        <flux:text class="font-medium">{{ $movement->product?->category?->name ?? '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Unidad de Medida</flux:text>
                        <flux:text class="font-medium">{{ $movement->product?->unitOfMeasure?->name ?? '-' }}</flux:text>
                    </div>
                </div>
            </div>

            <!-- Quantity Information -->
            <div class="border dark:border-zinc-700 rounded-lg p-4">
                <flux:heading size="sm" class="mb-3">Cantidades</flux:heading>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Cantidad</flux:text>
                        <flux:text class="text-xl font-bold {{ $movement->quantity >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                        </flux:text>
                    </div>
                    <div class="text-center p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Saldo Anterior</flux:text>
                        <flux:text class="text-xl font-semibold">{{ number_format($movement->previous_quantity ?? 0, 2) }}</flux:text>
                    </div>
                    <div class="text-center p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Nuevo Saldo</flux:text>
                        <flux:text class="text-xl font-semibold">{{ number_format($movement->new_quantity ?? $movement->balance_quantity ?? 0, 2) }}</flux:text>
                    </div>
                    <div class="text-center p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Costo Unitario</flux:text>
                        <flux:text class="text-xl font-semibold">{{ $movement->unit_cost ? '$'.number_format($movement->unit_cost, 2) : '-' }}</flux:text>
                    </div>
                </div>
            </div>

            <!-- Warehouse Information -->
            <div class="border dark:border-zinc-700 rounded-lg p-4">
                <flux:heading size="sm" class="mb-3">Bodega</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($movement->fromWarehouse && $movement->toWarehouse)
                        <!-- Transfer -->
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Bodega Origen</flux:text>
                            <flux:text class="font-medium">{{ $movement->fromWarehouse->name }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Bodega Destino</flux:text>
                            <flux:text class="font-medium">{{ $movement->toWarehouse->name }}</flux:text>
                        </div>
                    @else
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Bodega</flux:text>
                            <flux:text class="font-medium">{{ $movement->warehouse?->name ?? '-' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Ubicación</flux:text>
                            <flux:text class="font-medium">{{ $movement->location ?? '-' }}</flux:text>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Lot Information (if applicable) -->
            @if($movement->lot_number || $movement->batch_number || $movement->expiration_date)
            <div class="border dark:border-zinc-700 rounded-lg p-4">
                <flux:heading size="sm" class="mb-3">Información de Lote</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if($movement->lot_number)
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Número de Lote</flux:text>
                        <flux:text class="font-medium">{{ $movement->lot_number }}</flux:text>
                    </div>
                    @endif
                    @if($movement->batch_number)
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Número de Batch</flux:text>
                        <flux:text class="font-medium">{{ $movement->batch_number }}</flux:text>
                    </div>
                    @endif
                    @if($movement->expiration_date)
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Fecha de Vencimiento</flux:text>
                        <flux:text class="font-medium">{{ $movement->expiration_date->format('d/m/Y') }}</flux:text>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Related Documents -->
            @if($movement->transfer || $movement->purchase || $movement->donation || $movement->dispatch || $movement->adjustment)
            <div class="border dark:border-zinc-700 rounded-lg p-4">
                <flux:heading size="sm" class="mb-3">Documento Relacionado</flux:heading>
                @if($movement->transfer)
                <div class="flex items-center gap-2">
                    <flux:badge color="indigo" size="sm">Traslado</flux:badge>
                    <flux:text class="font-medium">{{ $movement->transfer->transfer_number ?? 'N/A' }}</flux:text>
                </div>
                @endif
                @if($movement->purchase)
                <div class="flex items-center gap-2">
                    <flux:badge color="green" size="sm">Compra</flux:badge>
                    <flux:text class="font-medium">{{ $movement->purchase->purchase_number ?? 'N/A' }}</flux:text>
                </div>
                @endif
                @if($movement->donation)
                <div class="flex items-center gap-2">
                    <flux:badge color="blue" size="sm">Donación</flux:badge>
                    <flux:text class="font-medium">{{ $movement->donation->donation_number ?? 'N/A' }}</flux:text>
                </div>
                @endif
                @if($movement->dispatch)
                <div class="flex items-center gap-2">
                    <flux:badge color="purple" size="sm">Despacho</flux:badge>
                    <flux:text class="font-medium">{{ $movement->dispatch->dispatch_number ?? 'N/A' }}</flux:text>
                </div>
                @endif
                @if($movement->adjustment)
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <flux:badge color="yellow" size="sm">Ajuste</flux:badge>
                        <flux:text class="font-medium">{{ $movement->adjustment->adjustment_number }}</flux:text>
                    </div>
                    @if($movement->adjustment->reason)
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Razón</flux:text>
                        <flux:text>{{ $movement->adjustment->reason }}</flux:text>
                    </div>
                    @endif
                    @if($movement->adjustment->justification)
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Justificación</flux:text>
                        <flux:text>{{ $movement->adjustment->justification }}</flux:text>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @endif

            <!-- Notes -->
            @if($movement->notes)
            <div class="border dark:border-zinc-700 rounded-lg p-4">
                <flux:heading size="sm" class="mb-3">Notas</flux:heading>
                <flux:text>{{ $movement->notes }}</flux:text>
            </div>
            @endif

            <!-- Audit Information -->
            <div class="border dark:border-zinc-700 rounded-lg p-4 bg-zinc-50 dark:bg-zinc-800/50">
                <flux:heading size="sm" class="mb-3">Información de Auditoría</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Creado por</flux:text>
                        <flux:text class="font-medium">{{ $movement->creator?->name ?? '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Fecha de Creación</flux:text>
                        <flux:text class="font-medium">{{ $movement->created_at?->format('d/m/Y H:i') ?? '-' }}</flux:text>
                    </div>
                    @if($movement->confirmedBy)
                    <div>
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Confirmado por</flux:text>
                        <flux:text class="font-medium">{{ $movement->confirmedBy->name }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Fecha de Confirmación</flux:text>
                        <flux:text class="font-medium">{{ $movement->confirmed_at?->format('d/m/Y H:i') ?? '-' }}</flux:text>
                    </div>
                    @endif
                    @if($movement->approvedBy)
                    <div>
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Aprobado por</flux:text>
                        <flux:text class="font-medium">{{ $movement->approvedBy->name }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Fecha de Aprobación</flux:text>
                        <flux:text class="font-medium">{{ $movement->approved_at?->format('d/m/Y H:i') ?? '-' }}</flux:text>
                    </div>
                    @endif
                    @if($movement->completedBy)
                    <div>
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Completado por</flux:text>
                        <flux:text class="font-medium">{{ $movement->completedBy->name }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Fecha de Completado</flux:text>
                        <flux:text class="font-medium">{{ $movement->completed_at?->format('d/m/Y H:i') ?? '-' }}</flux:text>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <flux:button variant="outline" wire:click="closeMovementModal">
                Cerrar
            </flux:button>
        </div>
        @else
        <div class="py-8 text-center">
            <flux:icon name="exclamation-circle" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
            <flux:text class="text-zinc-500">No se pudo cargar la información del movimiento</flux:text>
        </div>
        @endif
    </flux:modal>

    <!-- Mobile-Friendly Quick Actions -->
    <div class="fixed bottom-6 right-6 lg:hidden">
        <div class="flex flex-col gap-3">
            <flux:button size="sm" variant="primary" icon="plus" wire:click="showForm('entry')"
                class="bg-green-600 hover:bg-green-700 text-white rounded-full p-3 shadow-lg">
                <span class="sr-only">{{ __('Record Entry') }}</span>
            </flux:button>

            <flux:button size="sm" variant="danger" icon="minus" wire:click="showForm('exit')"
                class="bg-red-600 hover:bg-red-700 text-white rounded-full p-3 shadow-lg">
                <span class="sr-only">{{ __('Record Exit') }}</span>
            </flux:button>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:initialized', () => {
    // Modal functionality is handled by Flux UI components
    // Auto-hide removed to prevent unwanted closures
});
</script>
