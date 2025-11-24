<?php

use App\Models\{Dispatch, DispatchDetail, Warehouse, Customer, Product, UnitOfMeasure, Inventory, MovementReason, InventoryMovement};
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';

    // Quick Dispatch Form
    public bool $showQuickDispatch = false;
    public string $company_id = '';
    public string $warehouse_id = '';
    public string $customer_id = '';
    public string $dispatch_type = 'interno';
    public string $recipient_name = '';
    public string $product_id = '';
    public string $quantity = '';
    public string $unit_of_measure_id = '';
    public string $notes = '';
    public ?string $availableStock = null;
    public ?string $stockUnit = null;

    public function mount(): void
    {
        if (!$this->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function updatedCompanyId(): void
    {
        $this->warehouse_id = '';
        $this->customer_id = '';
        $this->product_id = '';
        $this->unit_of_measure_id = '';
        $this->availableStock = null;
        $this->stockUnit = null;
    }

    public function updatedWarehouseId(): void
    {
        $this->checkAvailableStock();
    }

    public function updatedProductId(): void
    {
        $this->checkAvailableStock();

        // Auto-fill unit of measure from product
        if ($this->product_id) {
            $product = Product::find($this->product_id);
            if ($product && $product->unit_of_measure_id) {
                $this->unit_of_measure_id = (string) $product->unit_of_measure_id;
            }
        }
    }

    public function updatedShowQuickDispatch($value): void
    {
        if (!$value) {
            $this->resetQuickDispatchForm();
        }
    }

    public function resetQuickDispatchForm(): void
    {
        $this->warehouse_id = '';
        $this->customer_id = '';
        $this->dispatch_type = 'interno';
        $this->recipient_name = '';
        $this->product_id = '';
        $this->quantity = '';
        $this->unit_of_measure_id = '';
        $this->notes = '';
        $this->availableStock = null;
        $this->stockUnit = null;

        if ($this->isSuperAdmin()) {
            $this->company_id = '';
        }
    }

    private function checkAvailableStock(): void
    {
        if ($this->product_id && $this->warehouse_id) {
            $inventory = Inventory::where('product_id', (int) $this->product_id)
                ->where('warehouse_id', (int) $this->warehouse_id)
                ->where('is_active', true)
                ->first();

            $product = Product::with('unitOfMeasure')->find($this->product_id);
            $this->stockUnit = $product?->unitOfMeasure?->abbreviation ?? $product?->unitOfMeasure?->code ?? '';

            $this->availableStock = $inventory ? number_format($inventory->available_quantity, 2) : '0.00';
        } else {
            $this->availableStock = null;
            $this->stockUnit = null;
        }
    }

    #[Computed]
    public function companies()
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Company::active()->orderBy('name')->get(['id', 'name']);
        }
        return collect([]);
    }

    #[Computed]
    public function warehouses()
    {
        if (!$this->company_id) {
            return collect([]);
        }
        return Warehouse::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function customers()
    {
        if (!$this->company_id) {
            return collect([]);
        }
        return Customer::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        if (!$this->company_id) {
            return collect([]);
        }
        return Product::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function units()
    {
        if (!$this->company_id) {
            return collect([]);
        }
        return UnitOfMeasure::forCompany($this->company_id)->active()->get();
    }

    public function showForm(): void
    {
        $this->showQuickDispatch = true;
        $this->resetQuickDispatchForm();

        // Re-set company for non-super-admins
        if (!$this->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }
    }

    public function createQuickDispatch(): void
    {
        $this->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'dispatch_type' => 'required|in:venta,interno,externo,donacion',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_of_measure_id' => 'required|exists:units_of_measure,id',
        ], [], [
            'warehouse_id' => 'bodega',
            'dispatch_type' => 'tipo de despacho',
            'product_id' => 'producto',
            'quantity' => 'cantidad',
            'unit_of_measure_id' => 'unidad de medida',
        ]);

        // Check stock availability
        $inventory = Inventory::where('product_id', (int) $this->product_id)
            ->where('warehouse_id', (int) $this->warehouse_id)
            ->where('is_active', true)
            ->first();

        $availableQty = $inventory ? (float) $inventory->available_quantity : 0;

        if ((float) $this->quantity > $availableQty) {
            $this->addError('quantity', "La cantidad solicitada ({$this->quantity}) excede el stock disponible ({$availableQty}).");
            return;
        }

        $companyId = $this->isSuperAdmin() ? $this->company_id : auth()->user()->company_id;

        \DB::beginTransaction();
        try {
            // Create dispatch directly in 'despachado' status
            $dispatch = Dispatch::create([
                'company_id' => $companyId,
                'warehouse_id' => $this->warehouse_id,
                'customer_id' => $this->customer_id ?: null,
                'dispatch_type' => $this->dispatch_type,
                'recipient_name' => $this->recipient_name,
                'notes' => $this->notes,
                'status' => 'despachado',
                'dispatched_at' => now(),
                'dispatched_by' => auth()->id(),
            ]);

            // Create dispatch detail
            $detail = DispatchDetail::create([
                'dispatch_id' => $dispatch->id,
                'product_id' => $this->product_id,
                'quantity' => $this->quantity,
                'unit_of_measure_id' => $this->unit_of_measure_id,
                'unit_price' => 0,
                'quantity_dispatched' => $this->quantity,
                'is_reserved' => true,
                'reserved_by' => auth()->id(),
                'reserved_at' => now(),
            ]);

            // Calculate totals
            $dispatch->calculateTotals();

            // Create inventory movement
            $movementType = match ($this->dispatch_type) {
                'venta' => 'sale',
                'interno' => 'transfer_out',
                'externo' => 'transfer_out',
                'donacion' => 'sale',
                default => 'sale',
            };

            $movementReasonCode = match ($this->dispatch_type) {
                'venta' => 'DISPATCH_SALE',
                'interno' => 'DISPATCH_INTERNAL',
                'externo' => 'DISPATCH_EXTERNAL',
                'donacion' => 'DISPATCH_DONATION',
                default => 'DISPATCH_INTERNAL',
            };

            $movementReason = MovementReason::where('code', $movementReasonCode)->first();
            if (!$movementReason) {
                $movementReason = MovementReason::where('movement_type', 'out')->first();
            }

            // Get current stock balance
            $currentStock = InventoryMovement::where('warehouse_id', $this->warehouse_id)
                ->where('product_id', $this->product_id)
                ->whereNotNull('balance_quantity')
                ->orderBy('movement_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $previousBalance = $currentStock ? $currentStock->balance_quantity : 0;
            $newBalance = $previousBalance - (float) $this->quantity;

            // Create inventory movement
            InventoryMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => $this->warehouse_id,
                'product_id' => $this->product_id,
                'movement_reason_id' => $movementReason?->id,
                'dispatch_id' => $dispatch->id,
                'movement_type' => $movementType,
                'movement_date' => now(),
                'quantity' => $this->quantity,
                'quantity_in' => 0,
                'quantity_out' => $this->quantity,
                'balance_quantity' => $newBalance,
                'previous_quantity' => $previousBalance,
                'new_quantity' => $newBalance,
                'unit_cost' => 0,
                'total_cost' => 0,
                'notes' => "Despacho Rápido {$dispatch->dispatch_number} - {$this->dispatch_type}",
                'is_active' => true,
                'active_at' => now(),
                'created_by' => auth()->id(),
            ]);

            // Update inventory
            if ($inventory) {
                $inventory->quantity -= (float) $this->quantity;
                $inventory->available_quantity -= (float) $this->quantity;
                $inventory->save();
            }

            \DB::commit();

            \Flux::toast(
                variant: 'success',
                heading: 'Despacho Rápido Creado',
                text: "Despacho {$dispatch->dispatch_number} procesado exitosamente.",
            );

            $this->showQuickDispatch = false;
            $this->resetQuickDispatchForm();

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error creating quick dispatch: ' . $e->getMessage());
            $this->addError('general', 'Error al procesar el despacho. Por favor intente nuevamente.');
        }
    }

    public function with(): array
    {
        $query = Dispatch::query()
            ->with(['customer', 'warehouse', 'warehouse.company'])
            ->when(!$this->isSuperAdmin(), fn ($q) => $q->where('company_id', auth()->user()->company_id))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('dispatch_number', 'like', "%{$this->search}%")
                        ->orWhere('document_number', 'like', "%{$this->search}%")
                        ->orWhere('recipient_name', 'like', "%{$this->search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('dispatch_type', $this->typeFilter))
            ->latest();

        return [
            'dispatches' => $query->paginate(15),
        ];
    }

    public function delete(Dispatch $dispatch): void
    {
        if (in_array($dispatch->status, ['despachado', 'entregado'])) {
            session()->flash('error', 'No se puede eliminar un despacho que ya fue despachado o entregado.');

            return;
        }

        $dispatch->delete();
        session()->flash('success', 'Despacho eliminado exitosamente.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Despachos</flux:heading>
            <flux:text class="mt-1">Gestión de despachos y entregas</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="outline" icon="bolt" wire:click="showForm">
                Despacho Rápido
            </flux:button>
            <flux:button variant="primary" icon="plus" href="{{ route('dispatches.create') }}" wire:navigate>
                Nuevo Despacho
            </flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle">
            {{ session('error') }}
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por número, documento, cliente, receptor..."
                icon="magnifying-glass"
            />
        </div>

        <flux:select wire:model.live="statusFilter" placeholder="Todos los estados">
            <option value="">Todos</option>
            <option value="borrador">Borrador</option>
            <option value="pendiente">Pendiente</option>
            <option value="aprobado">Aprobado</option>
            <option value="despachado">Despachado</option>
            <option value="entregado">Entregado</option>
            <option value="cancelado">Cancelado</option>
        </flux:select>

        <flux:select wire:model.live="typeFilter" placeholder="Todos los tipos">
            <option value="">Todos</option>
            <option value="venta">Venta</option>
            <option value="interno">Interno</option>
            <option value="externo">Externo</option>
            <option value="donacion">Donación</option>
        </flux:select>
    </div>

    <div class="overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Número</flux:table.column>
                <flux:table.column>Fecha</flux:table.column>
                @if ($this->isSuperAdmin())
                    <flux:table.column>Empresa</flux:table.column>
                @endif
                <flux:table.column>Cliente/Receptor</flux:table.column>
                <flux:table.column>Bodega</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Total</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($dispatches as $dispatch)
                    <flux:table.row :key="$dispatch->id">
                        <flux:table.cell>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $dispatch->dispatch_number }}
                                </div>
                                @if ($dispatch->document_number)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ ucfirst($dispatch->document_type ?? 'Doc') }}: {{ $dispatch->document_number }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $dispatch->created_at->format('d/m/Y') }}
                        </flux:table.cell>

                        @if ($this->isSuperAdmin())
                            <flux:table.cell>
                                {{ $dispatch->warehouse->company->name ?? 'N/A' }}
                            </flux:table.cell>
                        @endif

                        <flux:table.cell>
                            <div>
                                @if ($dispatch->customer)
                                    <div class="font-medium">{{ $dispatch->customer->name }}</div>
                                @endif
                                @if ($dispatch->recipient_name)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $dispatch->recipient_name }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $dispatch->warehouse->name }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="match($dispatch->dispatch_type) {
                                    'venta' => 'emerald',
                                    'interno' => 'sky',
                                    'externo' => 'amber',
                                    'donacion' => 'pink',
                                    default => 'zinc'
                                }"
                                :icon="match($dispatch->dispatch_type) {
                                    'venta' => 'currency-dollar',
                                    'interno' => 'arrow-path',
                                    'externo' => 'arrow-up-right',
                                    'donacion' => 'gift',
                                    default => 'cube'
                                }"
                            >
                                {{ $dispatch->getDispatchTypeSpanishAttribute() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            ${{ number_format($dispatch->total, 2) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="match($dispatch->status) {
                                    'borrador' => 'zinc',
                                    'pendiente' => 'amber',
                                    'aprobado' => 'sky',
                                    'despachado' => 'indigo',
                                    'entregado' => 'emerald',
                                    'cancelado' => 'red',
                                    default => 'zinc'
                                }"
                                :icon="match($dispatch->status) {
                                    'borrador' => 'pencil-square',
                                    'pendiente' => 'clock',
                                    'aprobado' => 'check-circle',
                                    'despachado' => 'truck',
                                    'entregado' => 'check-badge',
                                    'cancelado' => 'x-circle',
                                    default => 'question-mark-circle'
                                }"
                            >
                                {{ $dispatch->getStatusSpanishAttribute() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="eye" href="{{ route('dispatches.show', $dispatch) }}" wire:navigate />

                                @if ($dispatch->canBeEdited())
                                    <flux:button size="sm" variant="ghost" icon="pencil" href="{{ route('dispatches.edit', $dispatch) }}" wire:navigate />
                                @endif

                                @if ($dispatch->status === 'cancelado')
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="delete({{ $dispatch->id }})"
                                        wire:confirm="¿Está seguro de eliminar este despacho?"
                                    />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell :colspan="$this->isSuperAdmin() ? 10 : 9" class="text-center py-12">
                            <div class="text-gray-500 dark:text-gray-400">
                                No se encontraron despachos
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="mt-4">
        {{ $dispatches->links() }}
    </div>

    {{-- Quick Dispatch Modal --}}
    <flux:modal name="quick-dispatch" class="max-w-2xl" variant="flyout" wire:model="showQuickDispatch">
        <flux:heading>
            <flux:heading size="lg">Despacho Rápido</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                Crear y procesar un despacho inmediatamente (sin flujo de aprobación)
            </flux:text>
        </flux:heading>

        <div class="space-y-6 mt-6">
            @error('general')
                <flux:callout variant="danger" icon="x-circle">
                    {{ $message }}
                </flux:callout>
            @enderror

            @if($this->isSuperAdmin())
                <flux:field>
                    <flux:label badge="Requerido">Empresa</flux:label>
                    <flux:select wire:model.live="company_id">
                        <option value="">Seleccione una empresa</option>
                        @foreach ($this->companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="company_id" />
                </flux:field>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label badge="Requerido">Bodega</flux:label>
                    <flux:select wire:model.live="warehouse_id" :disabled="$this->isSuperAdmin() && !$company_id">
                        <option value="">Seleccione bodega</option>
                        @foreach ($this->warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="warehouse_id" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Tipo de Despacho</flux:label>
                    <flux:select wire:model="dispatch_type">
                        <option value="venta">Venta</option>
                        <option value="interno">Interno</option>
                        <option value="externo">Externo</option>
                        <option value="donacion">Donación</option>
                    </flux:select>
                    <flux:error name="dispatch_type" />
                </flux:field>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Cliente</flux:label>
                    <flux:select wire:model="customer_id" :disabled="$this->isSuperAdmin() && !$company_id">
                        <option value="">Sin cliente</option>
                        @foreach ($this->customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Nombre del Receptor</flux:label>
                    <flux:input wire:model="recipient_name" placeholder="Nombre de quien recibe" />
                </flux:field>
            </div>

            <flux:separator />

            <flux:field>
                <flux:label badge="Requerido">Producto</flux:label>
                <flux:select wire:model.live="product_id" :disabled="$this->isSuperAdmin() && !$company_id">
                    <option value="">Seleccione producto</option>
                    @foreach ($this->products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="product_id" />
            </flux:field>

            @if($availableStock !== null)
                <div class="p-3 rounded-lg border {{ $availableStock === '0.00' ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' }}">
                    <div class="flex items-center gap-2">
                        <flux:icon name="information-circle" class="h-5 w-5 {{ $availableStock === '0.00' ? 'text-red-500' : 'text-blue-500' }}" />
                        <flux:text class="{{ $availableStock === '0.00' ? 'text-red-700 dark:text-red-300' : 'text-blue-700 dark:text-blue-300' }} font-medium">
                            Stock disponible: {{ $availableStock }} {{ $stockUnit }}
                        </flux:text>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label badge="Requerido">Cantidad</flux:label>
                    <flux:input type="number" step="0.01" min="0.01" wire:model="quantity" placeholder="0.00" />
                    <flux:error name="quantity" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Unidad de Medida</flux:label>
                    <flux:select wire:model="unit_of_measure_id" :disabled="$this->isSuperAdmin() && !$company_id">
                        <option value="">Seleccione unidad</option>
                        @foreach ($this->units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="unit_of_measure_id" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Notas</flux:label>
                <flux:textarea wire:model="notes" rows="2" placeholder="Notas adicionales del despacho" />
            </flux:field>

            @if($product_id && $warehouse_id && $quantity)
                <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg border">
                    <flux:heading size="sm" class="mb-3">Resumen del Despacho</flux:heading>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Producto:</span>
                            <span>{{ $this->products->firstWhere('id', (int) $product_id)?->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Bodega:</span>
                            <span>{{ $this->warehouses->firstWhere('id', (int) $warehouse_id)?->name }}</span>
                        </div>
                        <div class="flex justify-between font-medium">
                            <span class="text-zinc-600 dark:text-zinc-400">Cantidad:</span>
                            <span>{{ number_format((float) $quantity, 2) }} {{ $stockUnit }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <flux:separator class="my-6" />

        <div class="flex justify-end gap-3">
            <flux:button variant="ghost" x-on:click="$flux.modal('quick-dispatch').close()">
                Cancelar
            </flux:button>
            <flux:button
                variant="primary"
                wire:click="createQuickDispatch"
                wire:loading.attr="disabled"
                :disabled="$availableStock === '0.00' || $availableStock === null"
            >
                <span wire:loading.remove>Crear y Procesar Despacho</span>
                <span wire:loading>Procesando...</span>
            </flux:button>
        </div>

        @if($availableStock === '0.00')
            <div class="mt-4">
                <flux:callout variant="warning" icon="exclamation-triangle">
                    No hay stock disponible para este producto en la bodega seleccionada.
                </flux:callout>
            </div>
        @endif
    </flux:modal>

    {{-- Mobile Quick Action Button --}}
    <div class="fixed bottom-6 right-6 lg:hidden">
        <flux:button
            variant="primary"
            icon="bolt"
            wire:click="showForm"
            class="rounded-full p-4 shadow-lg"
        >
            <span class="sr-only">Despacho Rápido</span>
        </flux:button>
    </div>
</div>
