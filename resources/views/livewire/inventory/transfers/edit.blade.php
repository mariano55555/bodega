<?php

use App\Http\Requests\UpdateInventoryTransferRequest;
use App\Models\{InventoryTransfer, InventoryTransferDetail, Warehouse, Product, Inventory};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public InventoryTransfer $transfer;
    public $from_warehouse_id = '';
    public $to_warehouse_id = '';
    public $reason = '';
    public $notes = '';
    public $shipping_cost = 0;
    public $products = [];
    public $availableStock = [];

    public function mount(InventoryTransfer $transfer): void
    {
        // Only allow editing pending transfers
        if ($transfer->status !== 'pending') {
            session()->flash('error', 'Solo se pueden editar traslados en estado pendiente.');
            $this->redirect(route('transfers.show', $transfer), navigate: true);

            return;
        }

        $this->transfer = $transfer;

        // Populate form fields
        $this->from_warehouse_id = $transfer->from_warehouse_id;
        $this->to_warehouse_id = $transfer->to_warehouse_id;
        $this->reason = $transfer->reason;
        $this->notes = $transfer->notes;
        $this->shipping_cost = $transfer->shipping_cost ?? 0;

        // Populate products
        foreach ($transfer->details as $detail) {
            $this->products[] = [
                'id' => $detail->id,
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'notes' => $detail->notes,
            ];

            // Check stock for existing products
            $this->checkAvailableStock(count($this->products) - 1);
        }
    }

    public function addProduct(): void
    {
        $this->products[] = [
            'product_id' => '',
            'quantity' => 1,
            'notes' => '',
        ];
    }

    public function removeProduct($index): void
    {
        unset($this->products[$index]);
        $this->products = array_values($this->products);
        unset($this->availableStock[$index]);
        $this->availableStock = array_values($this->availableStock);
    }

    public function updatedProducts($value, $key): void
    {
        // Check if product_id was updated
        if (str_contains($key, 'product_id')) {
            $index = explode('.', $key)[0];
            $this->checkAvailableStock($index);
        }
    }

    public function updatedFromWarehouseId(): void
    {
        // Recheck stock for all products when warehouse changes
        foreach ($this->products as $index => $product) {
            if (! empty($product['product_id'])) {
                $this->checkAvailableStock($index);
            }
        }
    }

    private function checkAvailableStock($index): void
    {
        if (empty($this->from_warehouse_id) || empty($this->products[$index]['product_id'])) {
            unset($this->availableStock[$index]);

            return;
        }

        // Get current available quantity from inventory table
        $inventory = Inventory::where('warehouse_id', $this->from_warehouse_id)
            ->where('product_id', $this->products[$index]['product_id'])
            ->first();

        $this->availableStock[$index] = $inventory ? $inventory->available_quantity : 0;
    }

    public function save(): void
    {
        $validated = $this->validate((new UpdateInventoryTransferRequest())->rules());

        \DB::beginTransaction();
        try {
            // Update transfer
            $this->transfer->update([
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
            ]);

            // Delete existing details and create new ones
            $this->transfer->details()->delete();

            foreach ($validated['products'] as $product) {
                InventoryTransferDetail::create([
                    'transfer_id' => $this->transfer->id,
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                    'notes' => $product['notes'] ?? null,
                ]);
            }

            \DB::commit();

            session()->flash('success', 'Traslado actualizado exitosamente.');
            $this->redirect(route('transfers.show', $this->transfer), navigate: true);
        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Error al actualizar el traslado. Por favor intente nuevamente.');
            \Log::error('Error updating transfer: '.$e->getMessage());
        }
    }

    public function cancel(): void
    {
        $this->redirect(route('transfers.show', $this->transfer), navigate: true);
    }

    public function with(): array
    {
        return [
            'warehouses' => Warehouse::where('company_id', auth()->user()->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'allProducts' => Product::where('company_id', auth()->user()->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Editar Traslado {{ $transfer->transfer_number }}</flux:heading>
            <flux:text class="mt-1">Modificar traslado en estado pendiente</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <flux:card>
            <flux:heading size="lg">Información del Traslado</flux:heading>
            <flux:separator />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Bodega de Origen *</flux:label>
                    <flux:select wire:model.live="from_warehouse_id">
                        <option value="">Seleccione bodega de origen</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="from_warehouse_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Bodega de Destino *</flux:label>
                    <flux:select wire:model="to_warehouse_id">
                        <option value="">Seleccione bodega de destino</option>
                        @foreach ($warehouses as $warehouse)
                            @if ($warehouse->id != $from_warehouse_id)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endif
                        @endforeach
                    </flux:select>
                    <flux:error name="to_warehouse_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Motivo del Traslado</flux:label>
                    <flux:input wire:model="reason" placeholder="Ej: Reabastecimiento, Redistribución" />
                    <flux:error name="reason" />
                </flux:field>

                <flux:field>
                    <flux:label>Costo de Envío ($)</flux:label>
                    <flux:input type="number" step="0.01" wire:model="shipping_cost" />
                    <flux:error name="shipping_cost" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">Productos *</flux:heading>
                <flux:button type="button" wire:click="addProduct" variant="primary" size="sm" icon="plus">
                    Agregar Producto
                </flux:button>
            </div>
            <flux:separator />

            @if (empty($from_warehouse_id))
                <flux:callout color="yellow" class="mb-6">
                    Por favor seleccione la bodega de origen para ver el inventario disponible.
                </flux:callout>
            @endif

            <div class="space-y-4">
                @foreach ($products as $index => $product)
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg" wire:key="product-{{ $index }}">
                        <div class="flex items-start justify-between mb-4">
                            <flux:heading size="sm">Producto #{{ $index + 1 }}</flux:heading>
                            @if (count($products) > 1)
                                <flux:button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    wire:click="removeProduct({{ $index }})"
                                >
                                    Eliminar
                                </flux:button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:field class="md:col-span-2">
                                <flux:label>Producto *</flux:label>
                                <flux:select wire:model.live="products.{{ $index }}.product_id">
                                    <option value="">Seleccione un producto</option>
                                    @foreach ($allProducts as $prod)
                                        <option value="{{ $prod->id }}">{{ $prod->name }} - {{ $prod->sku }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="products.{{ $index }}.product_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Cantidad *</flux:label>
                                <flux:input type="number" step="0.0001" wire:model="products.{{ $index }}.quantity" />
                                <flux:error name="products.{{ $index }}.quantity" />

                                @if (isset($availableStock[$index]))
                                    <div class="mt-2 text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Stock disponible:</span>
                                        <span class="font-medium {{ $availableStock[$index] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ number_format($availableStock[$index], 4) }} unidades
                                        </span>
                                    </div>
                                @endif
                            </flux:field>

                            <flux:field>
                                <flux:label>Notas del Producto</flux:label>
                                <flux:input wire:model="products.{{ $index }}.notes" placeholder="Observaciones específicas" />
                                <flux:error name="products.{{ $index }}.notes" />
                            </flux:field>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Notas Generales</flux:heading>
            <flux:separator />

            <flux:field>
                <flux:label>Notas del Traslado</flux:label>
                <flux:textarea wire:model="notes" rows="4" placeholder="Observaciones adicionales sobre el traslado..." />
                <flux:error name="notes" />
            </flux:field>
        </flux:card>

        <div class="flex items-center justify-between">
            <flux:button variant="ghost" wire:click="cancel" type="button">
                Cancelar
            </flux:button>

            <flux:button type="submit" variant="primary" icon="check">
                Actualizar Traslado
            </flux:button>
        </div>
    </form>
</div>
