<?php

use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\Warehouse;
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

    public function save(): void
    {
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

                <flux:field>
                    <flux:label>Costo de Envío</flux:label>
                    <flux:input type="number" step="0.01" wire:model="shipping_cost" />
                    @error('shipping_cost') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
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
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg" badge="Requerido">Productos</flux:heading>
                <flux:button type="button" icon="plus" wire:click="addDetail" size="sm">
                    Agregar Producto
                </flux:button>
            </div>

            <div class="space-y-4">
                @foreach ($details as $index => $detail)
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-3">
                        <div class="flex items-center justify-between mb-2">
                            <flux:text size="sm" class="font-medium">Producto #{{ $index + 1 }}</flux:text>
                            @if (count($details) > 1)
                                <flux:button type="button" variant="danger" size="sm" icon="trash" wire:click="removeDetail({{ $index }})">
                                    Eliminar
                                </flux:button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            <flux:field>
                                <flux:label badge="Requerido">Producto</flux:label>
                                <flux:select wire:model.live="details.{{ $index }}.product_id">
                                    <option value="">Seleccione un producto</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </flux:select>
                                @error("details.{$index}.product_id") <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Requerido">Cantidad</flux:label>
                                <div class="flex items-center gap-2">
                                    <flux:input type="number" step="0.0001" wire:model="details.{{ $index }}.quantity" class="flex-1" />
                                    @if($detail['product_id'])
                                        @php $unit = $this->getProductUnit($detail['product_id']); @endphp
                                        @if($unit['abbreviation'])
                                            <flux:tooltip content="{{ $unit['name'] }}">
                                                <flux:badge color="zinc" class="whitespace-nowrap cursor-help">
                                                    {{ $unit['abbreviation'] }}
                                                </flux:badge>
                                            </flux:tooltip>
                                        @endif
                                    @endif
                                </div>
                                @error("details.{$index}.quantity") <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Requerido">Costo Unitario ($)</flux:label>
                                <flux:input type="number" step="0.01" wire:model="details.{{ $index }}.unit_cost" />
                                @error("details.{$index}.unit_cost") <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Descuento (%)</flux:label>
                                <flux:input type="number" step="0.01" wire:model="details.{{ $index }}.discount_percentage" />
                                @error("details.{$index}.discount_percentage") <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>IVA (%)</flux:label>
                                <flux:input type="number" step="0.01" wire:model="details.{{ $index }}.tax_percentage" />
                                @error("details.{$index}.tax_percentage") <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Número de Lote</flux:label>
                                <flux:input wire:model="details.{{ $index }}.lot_number" />
                                @error("details.{$index}.lot_number") <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Fecha de Vencimiento</flux:label>
                                <flux:input type="date" wire:model="details.{{ $index }}.expiration_date" />
                                @error("details.{$index}.expiration_date") <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:field>

                            <flux:field class="lg:col-span-4">
                                <flux:label>Notas del Producto</flux:label>
                                <flux:textarea wire:model="details.{{ $index }}.notes" rows="2" />
                                @error("details.{$index}.notes") <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:field>
                        </div>
                    </div>
                @endforeach
            </div>

            @error('details') <flux:error>{{ $message }}</flux:error> @enderror
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
