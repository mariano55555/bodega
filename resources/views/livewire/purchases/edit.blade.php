<?php

use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Purchase $purchase;

    public $warehouse_id = '';

    public $supplier_id = '';

    public $document_type = 'factura';

    public $document_number = '';

    public $document_date = '';

    public $due_date = '';

    public $purchase_type = 'efectivo';

    public $payment_method = '';

    public $fund_source = '';

    public $shipping_cost = 0;

    public $notes = '';

    public $admin_notes = '';

    public $acquisition_type = 'normal';

    public $project_name = '';

    public $agreement_number = '';

    public $details = [];

    public function mount(Purchase $purchase): void
    {
        // Only allow editing drafts
        if ($purchase->status !== 'borrador') {
            session()->flash('error', 'Solo se pueden editar compras en estado borrador.');
            $this->redirect(route('purchases.show', $purchase), navigate: true);

            return;
        }

        $this->purchase = $purchase;

        // Fill form with existing data
        $this->warehouse_id = $purchase->warehouse_id;
        $this->supplier_id = $purchase->supplier_id;
        $this->document_type = $purchase->document_type;
        $this->document_number = $purchase->document_number ?? '';
        $this->document_date = $purchase->document_date?->format('Y-m-d') ?? '';
        $this->due_date = $purchase->due_date?->format('Y-m-d') ?? '';
        $this->purchase_type = $purchase->purchase_type;
        $this->payment_method = strtolower($purchase->payment_method ?? '');
        $this->fund_source = $purchase->fund_source ?? '';
        $this->shipping_cost = $purchase->shipping_cost ?? 0;
        $this->notes = $purchase->notes ?? '';
        $this->admin_notes = $purchase->admin_notes ?? '';
        $this->acquisition_type = $purchase->acquisition_type;
        $this->project_name = $purchase->project_name ?? '';
        $this->agreement_number = $purchase->agreement_number ?? '';

        // Load existing details
        foreach ($purchase->details as $detail) {
            $this->details[] = [
                'id' => $detail->id,
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'unit_cost' => $detail->unit_cost,
                'discount_percentage' => $detail->discount_percentage,
                'tax_percentage' => $detail->tax_percentage,
                'lot_number' => $detail->lot_number,
                'expiration_date' => $detail->expiration_date ? $detail->expiration_date->format('Y-m-d') : '',
                'notes' => $detail->notes,
            ];
        }
    }

    public function addDetail(): void
    {
        $this->details[] = [
            'id' => null,
            'product_id' => '',
            'quantity' => 1,
            'unit_cost' => 0,
            'discount_percentage' => 0,
            'tax_percentage' => 13,
            'lot_number' => '',
            'expiration_date' => '',
            'notes' => '',
        ];
    }

    public function removeDetail($index): void
    {
        unset($this->details[$index]);
        $this->details = array_values($this->details);
    }

    public function addMoreRows(): void
    {
        // Add 5 rows at once (single array operation, single re-render)
        $newRows = array_fill(0, 5, [
            'id' => null,
            'product_id' => '',
            'quantity' => 1,
            'unit_cost' => 0,
            'discount_percentage' => 0,
            'tax_percentage' => 13,
            'lot_number' => '',
            'expiration_date' => '',
            'notes' => '',
        ]);

        $this->details = array_merge($this->details, $newRows);

        \Flux::toast('5 filas agregadas', variant: 'success');
    }

    public function save(): void
    {
        // Filter out empty rows (rows without product_id)
        $filledDetails = array_filter($this->details, fn ($detail) => ! empty($detail['product_id']));

        if (empty($filledDetails)) {
            \Flux::toast('Debe agregar al menos un producto a la compra.', variant: 'danger');

            return;
        }

        // Re-index the array
        $this->details = array_values($filledDetails);

        $request = new UpdatePurchaseRequest;
        $validated = $this->validate($request->rules(), $request->messages());

        // Check if purchase date is retroactive (before current month)
        $isRetroactive = \Carbon\Carbon::parse($validated['document_date'])->isBefore(now()->startOfMonth());

        \DB::beginTransaction();
        try {
            // Update purchase header
            $this->purchase->update([
                'warehouse_id' => $validated['warehouse_id'],
                'supplier_id' => $validated['supplier_id'],
                'document_type' => $validated['document_type'],
                'document_number' => $validated['document_number'] ?? null,
                'document_date' => $validated['document_date'],
                'due_date' => $validated['due_date'] ?? null,
                'purchase_type' => $validated['purchase_type'],
                'payment_method' => $validated['payment_method'] ?? null,
                'acquisition_type' => $validated['acquisition_type'],
                'project_name' => $validated['project_name'] ?? null,
                'agreement_number' => $validated['agreement_number'] ?? null,
                'is_retroactive' => $isRetroactive,
                'fund_source' => $validated['fund_source'] ?? null,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            // Delete all existing details
            $this->purchase->details()->delete();

            // Create new details
            foreach ($validated['details'] as $detail) {
                PurchaseDetail::create([
                    'purchase_id' => $this->purchase->id,
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'unit_cost' => $detail['unit_cost'],
                    'discount_percentage' => $detail['discount_percentage'] ?? 0,
                    'tax_percentage' => $detail['tax_percentage'] ?? 0,
                    'lot_number' => $detail['lot_number'] ?? null,
                    'expiration_date' => $detail['expiration_date'] ?? null,
                    'notes' => $detail['notes'] ?? null,
                ]);
            }

            // Recalculate totals
            $this->purchase->calculateTotals();

            \DB::commit();

             Flux::toast(
                variant: 'success',
                heading: '¡Exito!',
                text: 'Compra actualizada exitosamente.',
            );

            //session()->flash('success', 'Compra actualizada exitosamente.');

            $this->redirect(route('purchases.show', $this->purchase), navigate: true);

        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Error al actualizar la compra: '.$e->getMessage());
        }
    }

    public function cancel(): void
    {
        $this->redirect(route('purchases.show', $this->purchase), navigate: true);
    }

    public function getProductUnit($productId): array
    {
        if (! $productId) {
            return ['abbreviation' => '', 'name' => ''];
        }

        $companyId = $this->purchase->company_id;

        $product = Product::with('unitOfMeasure')
            ->where('company_id', $companyId)
            ->where('id', $productId)
            ->first();

        return [
            'abbreviation' => $product?->unitOfMeasure?->abbreviation ?? '',
            'name' => $product?->unitOfMeasure?->name ?? '',
        ];
    }

    /**
     * Get products as a keyed array for Alpine.js
     * This allows instant access to product data without server roundtrip
     */
    #[Computed]
    public function productsData(): array
    {
        $companyId = $this->purchase->company_id;

        return Product::with('unitOfMeasure')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->keyBy('id')
            ->map(fn ($p) => [
                'name' => $p->name,
                'sku' => $p->sku,
                'cost' => (float) ($p->cost ?? 0),
                'unit' => $p->unitOfMeasure?->abbreviation ?? '',
                'unit_name' => $p->unitOfMeasure?->name ?? '',
            ])->toArray();
    }

    public function with(): array
    {
        // Use the purchase's company_id for filtering (important for super admins)
        $companyId = $this->purchase->company_id;

        return [
            'suppliers' => Supplier::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'warehouses' => Warehouse::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'products' => Product::with('unitOfMeasure')
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ];
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Editar Compra #{{ $purchase->purchase_number }}</flux:heading>

    @if (session('error'))
        <flux:callout variant="danger" class="mb-6">{{ session('error') }}</flux:callout>
    @endif

    <form wire:submit="save" class="space-y-8">
        {{-- Document Information --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Información del Documento</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label badge="Requerido">Bodega</flux:label>
                    <flux:select wire:model="warehouse_id">
                        <option value="">Seleccione una bodega</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('warehouse_id') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Proveedor</flux:label>
                    <flux:select wire:model="supplier_id">
                        <option value="">Seleccione un proveedor</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('supplier_id') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Tipo de Documento</flux:label>
                    <flux:select wire:model="document_type">
                        <option value="factura">Factura</option>
                        <option value="ccf">CCF</option>
                        <option value="ticket">Ticket</option>
                        <option value="otro">Otro</option>
                    </flux:select>
                    @error('document_type') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Número de Documento</flux:label>
                    <flux:input wire:model="document_number" placeholder="Ej: FAC-001234" />
                    @error('document_number') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Fecha del Documento</flux:label>
                    <flux:input type="date" wire:model="document_date" />
                    @error('document_date') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de Vencimiento</flux:label>
                    <flux:input type="date" wire:model="due_date" />
                    @error('due_date') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
            </div>
        </flux:card>

        {{-- Payment Information --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Información de Pago</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <flux:field>
                    <flux:label badge="Requerido">Tipo de Compra</flux:label>
                    <flux:select wire:model="purchase_type">
                        <option value="efectivo">Efectivo</option>
                        <option value="credito">Crédito</option>
                    </flux:select>
                    @error('purchase_type') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Método de Pago</flux:label>
                    <flux:select wire:model="payment_method">
                        <option value="">Seleccione un método</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="cheque">Cheque</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                    </flux:select>
                    @error('payment_method') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Origen de Fondos</flux:label>
                    <flux:input wire:model="fund_source" placeholder="Ej: Presupuesto 2025" />
                    @error('fund_source') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>

                {{-- Costo de Envío - Comentado por petición del cliente --}}
                {{-- <flux:field>
                    <flux:label>Costo de Envío</flux:label>
                    <flux:input type="number" step="0.01" wire:model="shipping_cost" />
                    @error('shipping_cost') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field> --}}
            </div>
        </flux:card>

        {{-- Acquisition Type --}}
        <flux:card>
            <flux:heading size="lg" class="mb-6">Tipo de Adquisición</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:field>
                    <flux:label badge="Requerido">Tipo de Adquisición</flux:label>
                    <flux:select wire:model.live="acquisition_type">
                        <option value="normal">Compra Normal</option>
                        <option value="convenio">Convenio</option>
                        <option value="proyecto">Proyecto</option>
                        <option value="otro">Otro</option>
                    </flux:select>
                    @error('acquisition_type') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>

                @if($acquisition_type === 'proyecto')
                    <flux:field>
                        <flux:label badge="Requerido">Nombre del Proyecto</flux:label>
                        <flux:input wire:model="project_name" placeholder="Ej: Proyecto Infraestructura 2025" />
                        @error('project_name') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                @endif

                @if($acquisition_type === 'convenio')
                    <flux:field>
                        <flux:label badge="Requerido">Número de Convenio</flux:label>
                        <flux:input wire:model="agreement_number" placeholder="Ej: CONV-2025-001" />
                        @error('agreement_number') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                @endif
            </div>

            @if(\Carbon\Carbon::parse($document_date ?? now())->isBefore(now()->startOfMonth()))
                <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">
                    <strong>Nota:</strong> La fecha del documento es anterior al mes actual. Esta compra será marcada como retroactiva.
                </flux:callout>
            @endif
        </flux:card>

        {{-- Products --}}
        <flux:card wire:key="products-card-{{ count($details) }}">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg" badge="Requerido">Productos</flux:heading>
                <div class="flex gap-2">
                    <flux:button type="button" variant="outline" size="sm" icon="plus" wire:click="addDetail" wire:loading.attr="disabled" wire:target="addDetail, addMoreRows">
                        <span wire:loading.remove wire:target="addDetail">+1 fila</span>
                        <span wire:loading wire:target="addDetail">...</span>
                    </flux:button>
                    <flux:button type="button" variant="primary" size="sm" icon="plus" wire:click="addMoreRows" wire:loading.attr="disabled" wire:target="addDetail, addMoreRows">
                        <span wire:loading.remove wire:target="addMoreRows">+5 filas</span>
                        <span wire:loading wire:target="addMoreRows">...</span>
                    </flux:button>
                </div>
            </div>

            <!-- Loading indicator when adding rows -->
            <div wire:loading wire:target="addDetail, addMoreRows" class="flex items-center justify-center py-4">
                <flux:icon name="arrow-path" class="w-5 h-5 animate-spin text-blue-500" />
                <flux:text class="ml-2 text-blue-600 dark:text-blue-400">Agregando filas...</flux:text>
            </div>

            <div wire:loading.remove wire:target="addDetail, addMoreRows" class="overflow-x-auto"
                 x-data
                 x-init="
                    $store.purchaseProducts = @js($this->productsData);
                    $store.purchaseRowTotals = {};
                    $store.purchaseGrandTotal = 0;
                 "
                 x-on:purchase-row-total-updated.window="
                    $store.purchaseRowTotals[$event.detail.index] = $event.detail.total;
                    $store.purchaseGrandTotal = Object.values($store.purchaseRowTotals).reduce((sum, val) => sum + (parseFloat(val) || 0), 0);
                 "
            >
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-12">#</flux:table.column>
                        <flux:table.column class="min-w-[280px]">Producto</flux:table.column>
                        <flux:table.column class="w-28 text-center">Cantidad</flux:table.column>
                        <flux:table.column class="w-32 text-right">Costo Unit.</flux:table.column>
                        <flux:table.column class="w-32 text-right">Total</flux:table.column>
                        <flux:table.column class="w-24 text-center">Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($details as $index => $detail)
                        <tbody x-data="purchaseRow({
                            index: {{ $index }},
                            productId: '{{ $detail['product_id'] }}',
                            quantity: {{ (float) ($detail['quantity'] ?? 1) }},
                            unitCost: {{ (float) ($detail['unit_cost'] ?? 0) }},
                            discountPercentage: {{ (float) ($detail['discount_percentage'] ?? 0) }},
                            taxPercentage: {{ (float) ($detail['tax_percentage'] ?? 13) }},
                            lotNumber: `{{ addslashes($detail['lot_number'] ?? '') }}`,
                            expirationDate: '{{ $detail['expiration_date'] ?? '' }}',
                            notes: `{{ addslashes($detail['notes'] ?? '') }}`
                        })" wire:key="detail-group-{{ $index }}">
                        <flux:table.row x-bind:class="productId ? '' : 'opacity-60'">
                            <flux:table.cell class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $index + 1 }}
                            </flux:table.cell>

                            <!-- Product Select with Unit Badge -->
                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <flux:select
                                        variant="listbox"
                                        searchable
                                        x-model="productId"
                                        x-on:change="selectProduct($event.target.value)"
                                        placeholder="Buscar producto..."
                                    >
                                        @foreach($products as $product)
                                            <flux:select.option value="{{ $product->id }}">
                                                {{ $product->name }}{{ $product->sku ? ' - ' . $product->sku : '' }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <!-- Unit Badge (Alpine.js - instant) -->
                                    <template x-if="productInfo && productInfo.unit">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-md bg-zinc-100 dark:bg-zinc-700 px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                                Unidad: <span x-text="productInfo.unit" class="ml-1"></span>
                                            </span>
                                        </div>
                                    </template>
                                    <flux:error name="details.{{ $index }}.product_id" />
                                </div>
                            </flux:table.cell>

                            <!-- Quantity -->
                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <input
                                        type="number"
                                        step="0.0001"
                                        min="0.0001"
                                        x-model.number="quantity"
                                        @input="emitTotal()"
                                        @change="updateQuantity()"
                                        class="block w-full text-center rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                                    />
                                    <flux:error name="details.{{ $index }}.quantity" />
                                </div>
                            </flux:table.cell>

                            <!-- Unit Cost -->
                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        x-model.number="unitCost"
                                        @input="emitTotal()"
                                        @change="updateUnitCost()"
                                        placeholder="0.00"
                                        class="block w-full text-right rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                                    />
                                    <flux:error name="details.{{ $index }}.unit_cost" />
                                </div>
                            </flux:table.cell>

                            <!-- Total (Calculated with Alpine - instant) -->
                            <flux:table.cell class="text-right font-semibold">
                                $<span x-text="total.toFixed(2)"></span>
                            </flux:table.cell>

                            <!-- Actions -->
                            <flux:table.cell class="text-center">
                                <div class="flex items-center justify-center gap-1" x-show="productId">
                                    <!-- Expand/Collapse for extra fields (Alpine.js - client-side only) -->
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        x-on:click="expanded = !expanded"
                                        x-tooltip="'Más opciones'"
                                    >
                                        <flux:icon x-show="!expanded" name="chevron-down" variant="mini" />
                                        <flux:icon x-show="expanded" name="chevron-up" variant="mini" />
                                    </flux:button>

                                    <!-- Clear Button (Alpine.js - instant) -->
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

                        <!-- Expandable Row for additional fields (Alpine.js - client-side only) -->
                        <flux:table.row x-show="expanded" x-collapse class="bg-zinc-50 dark:bg-zinc-800">
                            <flux:table.cell colspan="6" class="py-3">
                                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 px-4">
                                    <!-- Discount -->
                                    <div>
                                        <flux:label class="text-xs">Descuento (%)</flux:label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            x-model.number="discountPercentage"
                                            @input="emitTotal()"
                                            @change="updateDiscount()"
                                            placeholder="0"
                                            class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                                        />
                                    </div>

                                    <!-- Tax -->
                                    <div>
                                        <flux:label class="text-xs">IVA (%)</flux:label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            x-model.number="taxPercentage"
                                            @input="emitTotal()"
                                            @change="updateTax()"
                                            placeholder="13"
                                            class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                                        />
                                    </div>

                                    <!-- Lot Number -->
                                    <div>
                                        <flux:label class="text-xs">Número de Lote</flux:label>
                                        <input
                                            type="text"
                                            x-model="lotNumber"
                                            @blur="syncToLivewire()"
                                            placeholder="Ej: LOT-001"
                                            class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                                        />
                                    </div>

                                    <!-- Expiration Date -->
                                    <div>
                                        <flux:label class="text-xs">Fecha Vencimiento</flux:label>
                                        <input
                                            type="date"
                                            x-model="expirationDate"
                                            @change="syncToLivewire()"
                                            class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                                        />
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <flux:label class="text-xs">Notas</flux:label>
                                        <input
                                            type="text"
                                            x-model="notes"
                                            @blur="syncToLivewire()"
                                            placeholder="Notas opcionales..."
                                            class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                                        />
                                    </div>
                                </div>

                                <!-- Summary when expanded -->
                                <div class="flex justify-end gap-4 mt-3 px-4 text-xs text-zinc-500 dark:text-zinc-400">
                                    <span>Subtotal: $<span x-text="subtotal.toFixed(2)"></span></span>
                                    <span x-show="discountAmount > 0">Descuento: -$<span x-text="discountAmount.toFixed(2)"></span></span>
                                    <span x-show="taxAmount > 0">IVA: +$<span x-text="taxAmount.toFixed(2)"></span></span>
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
                <flux:button type="button" variant="outline" size="sm" icon="plus" wire:click="addDetail" wire:loading.attr="disabled" wire:target="addDetail, addMoreRows">
                    <span wire:loading.remove wire:target="addDetail">+1 fila</span>
                    <span wire:loading wire:target="addDetail">Agregando...</span>
                </flux:button>
                <flux:button type="button" variant="primary" size="sm" icon="plus" wire:click="addMoreRows" wire:loading.attr="disabled" wire:target="addDetail, addMoreRows">
                    <span wire:loading.remove wire:target="addMoreRows">+5 filas</span>
                    <span wire:loading wire:target="addMoreRows">Agregando...</span>
                </flux:button>
            </div>

            <!-- Grand Total (calculated with Alpine.js for real-time updates) -->
            <div class="mt-4 flex justify-end" x-data>
                <div class="bg-zinc-100 dark:bg-zinc-800 px-6 py-3 rounded-lg">
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">Total General</flux:text>
                    <flux:heading size="lg">
                        $<span x-text="($store.purchaseGrandTotal || 0).toFixed(2)">0.00</span>
                    </flux:heading>
                </div>
            </div>

            <flux:error name="details" />
        </flux:card>

        {{-- Notes --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Notas</flux:heading>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Notas Generales</flux:label>
                    <flux:textarea wire:model="notes" rows="3" placeholder="Notas visibles para todos los usuarios..." />
                    @error('notes') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Notas Administrativas</flux:label>
                    <flux:textarea wire:model="admin_notes" rows="3" placeholder="Notas internas solo para administradores..." />
                    @error('admin_notes') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
            </div>
        </flux:card>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                Actualizar Compra
            </flux:button>
        </div>
    </form>
</div>
