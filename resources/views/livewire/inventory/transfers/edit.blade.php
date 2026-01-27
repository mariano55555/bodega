<?php

use App\Http\Requests\UpdateInventoryTransferRequest;
use App\Models\{InventoryTransfer, InventoryTransferDetail, Warehouse, Product, Inventory};
use Livewire\Attributes\Computed;
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

        // Populate existing products from transfer details
        foreach ($transfer->details as $detail) {
            $this->products[] = [
                'id' => $detail->id,
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'notes' => $detail->notes ?? '',
            ];
        }

        // Add empty rows to make total of 10 (or more if existing > 10)
        $existingCount = count($this->products);
        $targetCount = max(10, $existingCount);
        for ($i = $existingCount; $i < $targetCount; $i++) {
            $this->addProduct();
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

    public function addMoreRows(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->addProduct();
        }
        \Flux::toast('5 filas agregadas', variant: 'success');
    }

    public function removeProduct($index): void
    {
        unset($this->products[$index]);
        $this->products = array_values($this->products);
    }

    #[Computed]
    public function productsData(): array
    {
        $companyId = $this->transfer->company_id;

        if (! $this->from_warehouse_id) {
            return [];
        }

        // Get product IDs already in the transfer (to always show them)
        $existingProductIds = collect($this->products)->pluck('product_id')->filter()->toArray();

        // Only get products that have stock OR are already in the transfer
        return Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($query) use ($existingProductIds) {
                $query->whereHas('inventory', function ($q) {
                    $q->where('warehouse_id', $this->from_warehouse_id)
                        ->where('available_quantity', '>', 0);
                })->orWhereIn('id', $existingProductIds);
            })
            ->with(['unitOfMeasure', 'inventory' => function ($query) {
                $query->where('warehouse_id', $this->from_warehouse_id);
            }])
            ->get()
            ->keyBy('id')
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'unit_abbreviation' => $p->unitOfMeasure?->abbreviation ?? 'UND',
                'unit_name' => $p->unitOfMeasure?->name ?? 'Unidad',
                'available_stock' => $p->inventory->first()?->available_quantity ?? 0,
            ])
            ->toArray();
    }

    #[Computed]
    public function availableStockData(): array
    {
        if (! $this->from_warehouse_id) {
            return [];
        }

        return Inventory::where('warehouse_id', $this->from_warehouse_id)
            ->where('available_quantity', '>', 0)
            ->get()
            ->keyBy('product_id')
            ->map(fn($inv) => $inv->available_quantity)
            ->toArray();
    }

    /**
     * Get products with stock for the dropdown
     */
    #[Computed]
    public function productsWithStock()
    {
        $companyId = $this->transfer->company_id;

        if (! $this->from_warehouse_id) {
            return collect();
        }

        // Get product IDs already in the transfer (to always show them)
        $existingProductIds = collect($this->products)->pluck('product_id')->filter()->toArray();

        return Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($query) use ($existingProductIds) {
                $query->whereHas('inventory', function ($q) {
                    $q->where('warehouse_id', $this->from_warehouse_id)
                        ->where('available_quantity', '>', 0);
                })->orWhereIn('id', $existingProductIds);
            })
            ->with(['unitOfMeasure', 'inventory' => function ($query) {
                $query->where('warehouse_id', $this->from_warehouse_id);
            }])
            ->orderBy('name')
            ->get();
    }

    public function updatedFromWarehouseId(): void
    {
        // Stock data will be automatically refreshed via computed property
    }

    public function save(): void
    {
        // Filter out empty rows before validation
        $this->products = array_values(array_filter($this->products, function ($product) {
            return ! empty($product['product_id']);
        }));

        if (empty($this->products)) {
            $this->addError('products', 'Debe agregar al menos un producto al traslado.');
            return;
        }

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
            'warehouses' => Warehouse::where('company_id', $this->transfer->company_id)
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
            <flux:heading size="lg" class="mb-6">Información del Traslado</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label badge="Requerido">Bodega de Origen</flux:label>
                    <flux:select variant="listbox" searchable wire:model.live="from_warehouse_id" placeholder="Seleccione bodega de origen">
                        @foreach ($warehouses as $warehouse)
                            <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="from_warehouse_id" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Bodega de Destino</flux:label>
                    <flux:select variant="listbox" searchable wire:model="to_warehouse_id" placeholder="Seleccione bodega de destino">
                        @foreach ($warehouses as $warehouse)
                            @if ($warehouse->id != $from_warehouse_id)
                                <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
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
                    <flux:input type="number" step="0.00001" wire:model="shipping_cost" />
                    <flux:error name="shipping_cost" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card wire:key="products-card-{{ $from_warehouse_id }}-{{ count($products) }}">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg" badge="Requerido">Productos</flux:heading>
                <div class="flex gap-2">
                    <flux:button type="button" wire:click="addProduct" variant="outline" size="sm" icon="plus" wire:loading.attr="disabled" wire:target="addProduct, addMoreRows">
                        <span wire:loading.remove wire:target="addProduct">+1 fila</span>
                        <span wire:loading wire:target="addProduct">...</span>
                    </flux:button>
                    <flux:button type="button" wire:click="addMoreRows" variant="primary" size="sm" icon="plus" wire:loading.attr="disabled" wire:target="addProduct, addMoreRows">
                        <span wire:loading.remove wire:target="addMoreRows">+5 filas</span>
                        <span wire:loading wire:target="addMoreRows">...</span>
                    </flux:button>
                </div>
            </div>

            @if (empty($from_warehouse_id))
                <flux:callout color="yellow" class="mb-6">
                    Por favor seleccione la bodega de origen para ver los productos con stock disponible.
                </flux:callout>
            @elseif ($from_warehouse_id && $this->productsWithStock->isEmpty())
                <flux:callout color="red" class="mb-6" icon="exclamation-triangle">
                    No hay productos con stock disponible en la bodega seleccionada.
                </flux:callout>
            @endif

            <!-- Loading indicator when warehouse changes -->
            <div wire:loading wire:target="from_warehouse_id" class="flex items-center justify-center py-8">
                <flux:icon name="arrow-path" class="w-6 h-6 animate-spin text-blue-500" />
                <flux:text class="ml-2 text-blue-600">Cargando inventario...</flux:text>
            </div>

            <!-- Loading indicator when adding rows -->
            <div wire:loading wire:target="addProduct, addMoreRows" class="flex items-center justify-center py-4">
                <flux:icon name="arrow-path" class="w-5 h-5 animate-spin text-blue-500" />
                <flux:text class="ml-2 text-blue-600 dark:text-blue-400">Agregando filas...</flux:text>
            </div>

            <div wire:loading.remove wire:target="from_warehouse_id, addProduct, addMoreRows" class="overflow-x-auto"
                 x-data
                 x-init="
                    Alpine.store('transferProducts', @js($this->productsData));
                    Alpine.store('transferAvailableStock', @js($this->availableStockData));
                 ">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-12">#</flux:table.column>
                        <flux:table.column class="min-w-[300px]">Producto</flux:table.column>
                        <flux:table.column class="w-28 text-center">Cantidad</flux:table.column>
                        <flux:table.column class="w-32 text-center">Stock Disp.</flux:table.column>
                        <flux:table.column class="w-24 text-center">Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($products as $index => $product)
                        <tbody x-data="transferRow({
                            index: {{ $index }},
                            productId: '{{ $product['product_id'] ?? '' }}',
                            quantity: {{ $product['quantity'] ?? 1 }},
                            notes: `{{ addslashes($product['notes'] ?? '') }}`
                        })" wire:key="product-group-{{ $index }}">
                        <flux:table.row x-bind:class="productId ? '' : 'opacity-60'">
                            <flux:table.cell class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $index + 1 }}
                            </flux:table.cell>

                            <!-- Product Selection -->
                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <flux:select
                                        variant="listbox"
                                        searchable
                                        x-model="productId"
                                        x-on:change="selectProduct($event.target.value)"
                                        :disabled="!$from_warehouse_id"
                                        placeholder="{{ !$from_warehouse_id ? 'Seleccione bodega primero' : ($this->productsWithStock->isEmpty() ? 'Sin productos con stock' : 'Seleccionar producto...') }}"
                                    >
                                        @foreach ($this->productsWithStock as $prod)
                                            <flux:select.option value="{{ $prod->id }}">
                                                {{ $prod->name }}{{ $prod->sku ? ' - ' . $prod->sku : '' }} ({{ number_format($prod->inventory->first()?->available_quantity ?? 0, 5) }} disp.)
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <!-- Unit Badge (Alpine.js - instant) -->
                                    <template x-if="productInfo">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <template x-if="productInfo.unit_abbreviation">
                                                <span class="inline-flex items-center rounded-md bg-zinc-100 dark:bg-zinc-700 px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                                    Unidad: <span x-text="productInfo.unit_abbreviation" class="ml-1"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </template>
                                    <flux:error name="products.{{ $index }}.product_id" />
                                </div>
                            </flux:table.cell>

                            <!-- Quantity -->
                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <input
                                        type="number"
                                        x-model="quantity"
                                        @change="updateQuantity()"
                                        step="0.00001"
                                        min="0"
                                        class="block w-full text-center rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                                    />
                                    <flux:error name="products.{{ $index }}.quantity" />
                                </div>
                            </flux:table.cell>

                            <!-- Available Stock -->
                            <flux:table.cell class="text-center">
                                <template x-if="productId && {{ $from_warehouse_id ? 'true' : 'false' }}">
                                    <span :class="availableStock > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                          class="text-sm font-medium">
                                        <span x-text="availableStock.toFixed(5)"></span>
                                        <span x-text="productInfo?.unit_abbreviation" class="text-xs"></span>
                                    </span>
                                </template>
                                <template x-if="!productId || !{{ $from_warehouse_id ? 'true' : 'false' }}">
                                    <span class="text-gray-400 text-sm">-</span>
                                </template>
                            </flux:table.cell>

                            <!-- Actions -->
                            <flux:table.cell class="text-center">
                                <div class="flex items-center justify-center gap-1" x-show="productId">
                                    <!-- Expand/Collapse for Notes -->
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        x-on:click="expanded = !expanded"
                                    >
                                        <flux:icon x-show="!expanded" name="chevron-down" variant="mini" />
                                        <flux:icon x-show="expanded" name="chevron-up" variant="mini" />
                                    </flux:button>

                                    <!-- Clear Button -->
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        x-on:click="clearRow()"
                                    />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>

                        <!-- Expandable Notes Row -->
                        <flux:table.row x-show="expanded" x-collapse class="bg-zinc-50 dark:bg-zinc-800">
                            <flux:table.cell colspan="5" class="py-3">
                                <div class="px-4">
                                    <flux:label>Notas (opcional)</flux:label>
                                    <textarea
                                        x-model="notes"
                                        @blur="syncToLivewire()"
                                        placeholder="Observaciones del producto..."
                                        rows="2"
                                        class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                                    ></textarea>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                        </tbody>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <!-- Bottom buttons to add more rows -->
            <div class="mt-4 flex justify-end gap-2">
                <flux:button type="button" variant="outline" size="sm" icon="plus" wire:click="addProduct" wire:loading.attr="disabled" wire:target="addProduct, addMoreRows">
                    <span wire:loading.remove wire:target="addProduct">+1 fila</span>
                    <span wire:loading wire:target="addProduct">Agregando...</span>
                </flux:button>
                <flux:button type="button" variant="primary" size="sm" icon="plus" wire:click="addMoreRows" wire:loading.attr="disabled" wire:target="addProduct, addMoreRows">
                    <span wire:loading.remove wire:target="addMoreRows">+5 filas</span>
                    <span wire:loading wire:target="addMoreRows">Agregando...</span>
                </flux:button>
            </div>

            <flux:error name="products" class="mt-2" />
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-6">Notas Generales</flux:heading>

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
