<?php

use App\Http\Requests\StorePurchaseRequest;
use App\Models\Company;
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
    public $company_id = '';

    public $warehouse_id = '';

    public $supplier_id = '';

    public $document_type = 'factura';

    public $document_number = '';

    public $document_date = '';

    public $due_date = '';

    public $purchase_type = 'efectivo';

    public $payment_method = '';

    public $acquisition_type = 'normal';

    public $project_name = '';

    public $agreement_number = '';

    public $fund_source = '';

    public $shipping_cost = 0;

    public $notes = '';

    public $admin_notes = '';

    public $details = [];

    public function mount(): void
    {
        $this->document_date = now()->format('Y-m-d');
        $this->addDetail();

        // If not super admin, set company_id to user's company
        if (! $this->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }
    }

    public function updatedCompanyId(): void
    {
        // Reset selections when company changes
        $this->warehouse_id = '';
        $this->supplier_id = '';
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function addDetail(): void
    {
        $this->details[] = [
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
        $request = new StorePurchaseRequest;
        $rules = $request->rules();
        $messages = $request->messages();

        // Add company_id validation for super admins
        if ($this->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
            $messages['company_id.required'] = 'La empresa es requerida.';
            $messages['company_id.exists'] = 'La empresa seleccionada no existe.';
        }

        $validated = $this->validate($rules, $messages);

        // Use selected company_id or user's company_id for non-super-admins
        $companyId = $this->isSuperAdmin() ? $this->company_id : auth()->user()->company_id;

        // Check if purchase date is retroactive (before current month)
        $isRetroactive = \Carbon\Carbon::parse($validated['document_date'])->isBefore(now()->startOfMonth());

        $purchase = Purchase::create([
            'company_id' => $companyId,
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
            'status' => 'borrador',
        ]);

        foreach ($validated['details'] as $detail) {
            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
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

        $purchase->calculateTotals();

        session()->flash('success', 'Compra creada exitosamente.');
        $this->redirect(route('purchases.show', $purchase), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('purchases.index'), navigate: true);
    }

    #[Computed]
    public function companies()
    {
        if ($this->isSuperAdmin()) {
            return Company::active()->orderBy('name')->get();
        }

        return collect([]);
    }

    #[Computed]
    public function suppliers()
    {
        if ($this->isSuperAdmin()) {
            if (! $this->company_id) {
                return collect([]);
            }

            return Supplier::where('company_id', $this->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        // Non-super-admin users see only their company's suppliers
        return Supplier::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function warehouses()
    {
        if ($this->isSuperAdmin()) {
            if (! $this->company_id) {
                return collect([]);
            }

            return Warehouse::where('company_id', $this->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        // Non-super-admin users see only their company's warehouses
        return Warehouse::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        if ($this->isSuperAdmin()) {
            if (! $this->company_id) {
                return collect([]);
            }

            return Product::with('unitOfMeasure')
                ->where('company_id', $this->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        // Non-super-admin users see only their company's products
        return Product::with('unitOfMeasure')
            ->where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getProductUnit($productId): array
    {
        if (! $productId) {
            return ['abbreviation' => '', 'name' => ''];
        }

        $product = $this->products->firstWhere('id', $productId);

        return [
            'abbreviation' => $product?->unitOfMeasure?->abbreviation ?? '',
            'name' => $product?->unitOfMeasure?->name ?? '',
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nueva Compra</flux:heading>
            <flux:text class="mt-1">Registrar una nueva compra de productos</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <flux:card>
            <flux:heading size="lg" class="mb-6">Información del Documento</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @if($this->isSuperAdmin())
                    <flux:field class="md:col-span-3">
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

                <flux:field>
                    <flux:label badge="Requerido">Bodega Destino</flux:label>
                    <flux:select wire:model="warehouse_id" :disabled="$this->isSuperAdmin() && !$company_id">
                        <option value="">Seleccione una bodega</option>
                        @foreach ($this->warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="warehouse_id" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Proveedor</flux:label>
                    <flux:select wire:model="supplier_id" :disabled="$this->isSuperAdmin() && !$company_id">
                        <option value="">Seleccione un proveedor</option>
                        @foreach ($this->suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="supplier_id" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Tipo de Documento</flux:label>
                    <flux:select wire:model="document_type">
                        <option value="factura">Factura</option>
                        <option value="ccf">CCF</option>
                        <option value="ticket">Ticket</option>
                        <option value="otro">Otro</option>
                    </flux:select>
                    <flux:error name="document_type" />
                </flux:field>

                <flux:field>
                    <flux:label>Número de Documento</flux:label>
                    <flux:input wire:model="document_number" placeholder="Ej: F-001-00001234" />
                    <flux:error name="document_number" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Fecha del Documento</flux:label>
                    <flux:input type="date" wire:model="document_date" />
                    <flux:error name="document_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de Vencimiento</flux:label>
                    <flux:input type="date" wire:model="due_date" />
                    <flux:error name="due_date" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-6">Información de Pago</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:field>
                    <flux:label badge="Requerido">Tipo de Compra</flux:label>
                    <flux:select wire:model="purchase_type">
                        <option value="efectivo">Efectivo</option>
                        <option value="credito">Crédito</option>
                    </flux:select>
                    <flux:error name="purchase_type" />
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
                    <flux:error name="payment_method" />
                </flux:field>

                <flux:field>
                    <flux:label>Origen de Fondos</flux:label>
                    <flux:input wire:model="fund_source" placeholder="Ej: Fondos propios, Donación" />
                    <flux:error name="fund_source" />
                </flux:field>

                <flux:field>
                    <flux:label>Costo de Envío ($)</flux:label>
                    <flux:input type="number" step="0.01" wire:model="shipping_cost" />
                    <flux:error name="shipping_cost" />
                </flux:field>
            </div>
        </flux:card>

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
                    <flux:error name="acquisition_type" />
                </flux:field>

                @if($acquisition_type === 'proyecto')
                    <flux:field>
                        <flux:label badge="Requerido">Nombre del Proyecto</flux:label>
                        <flux:input wire:model="project_name" placeholder="Ej: Proyecto Infraestructura 2025" />
                        <flux:error name="project_name" />
                    </flux:field>
                @endif

                @if($acquisition_type === 'convenio')
                    <flux:field>
                        <flux:label badge="Requerido">Número de Convenio</flux:label>
                        <flux:input wire:model="agreement_number" placeholder="Ej: CONV-2025-001" />
                        <flux:error name="agreement_number" />
                    </flux:field>
                @endif
            </div>

            @if(\Carbon\Carbon::parse($document_date ?? now())->isBefore(now()->startOfMonth()))
                <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">
                    <strong>Nota:</strong> La fecha del documento es anterior al mes actual. Esta compra será marcada como retroactiva.
                </flux:callout>
            @endif
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between mb-6">
                <flux:heading size="lg" badge="Requerido">Productos</flux:heading>
                <flux:button type="button" wire:click="addDetail" variant="primary" size="sm" icon="plus">
                    Agregar Producto
                </flux:button>
            </div>

            <div class="space-y-4">
                @foreach ($details as $index => $detail)
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg" wire:key="detail-{{ $index }}">
                        <div class="flex items-start justify-between mb-4">
                            <flux:heading size="sm">Producto #{{ $index + 1 }}</flux:heading>
                            @if (count($details) > 1)
                                <flux:button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    wire:click="removeDetail({{ $index }})"
                                >
                                    Eliminar
                                </flux:button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <flux:field class="md:col-span-2">
                                <flux:label badge="Requerido">Producto</flux:label>
                                <flux:select wire:model.live="details.{{ $index }}.product_id" :disabled="$this->isSuperAdmin() && !$company_id">
                                    <option value="">Seleccione un producto</option>
                                    @foreach ($this->products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} - {{ $product->sku }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="details.{{ $index }}.product_id" />
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
                                <flux:error name="details.{{ $index }}.quantity" />
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Requerido">Costo Unitario ($)</flux:label>
                                <flux:input type="number" step="0.01" wire:model="details.{{ $index }}.unit_cost" />
                                <flux:error name="details.{{ $index }}.unit_cost" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Descuento (%)</flux:label>
                                <flux:input type="number" step="0.01" wire:model="details.{{ $index }}.discount_percentage" />
                                <flux:error name="details.{{ $index }}.discount_percentage" />
                            </flux:field>

                            <flux:field>
                                <flux:label>IVA (%)</flux:label>
                                <flux:input type="number" step="0.01" wire:model="details.{{ $index }}.tax_percentage" />
                                <flux:error name="details.{{ $index }}.tax_percentage" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Número de Lote</flux:label>
                                <flux:input wire:model="details.{{ $index }}.lot_number" />
                                <flux:error name="details.{{ $index }}.lot_number" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Fecha de Vencimiento</flux:label>
                                <flux:input type="date" wire:model="details.{{ $index }}.expiration_date" />
                                <flux:error name="details.{{ $index }}.expiration_date" />
                            </flux:field>
                        </div>

                        <flux:field class="mt-4">
                            <flux:label>Notas del Producto</flux:label>
                            <flux:textarea wire:model="details.{{ $index }}.notes" rows="2" />
                            <flux:error name="details.{{ $index }}.notes" />
                        </flux:field>
                    </div>
                @endforeach
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-6">Notas</flux:heading>

            <div class="space-y-6">
                <flux:field>
                    <flux:label>Notas Generales</flux:label>
                    <flux:textarea wire:model="notes" rows="3" placeholder="Observaciones sobre la compra..." />
                    <flux:error name="notes" />
                </flux:field>

                <flux:field>
                    <flux:label>Notas Administrativas</flux:label>
                    <flux:textarea wire:model="admin_notes" rows="3" placeholder="Notas internas de administración..." />
                    <flux:error name="admin_notes" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex items-center justify-between">
            <flux:button variant="ghost" wire:click="cancel" type="button">
                Cancelar
            </flux:button>

            <flux:button type="submit" variant="primary" icon="check">
                Guardar Compra
            </flux:button>
        </div>
    </form>
</div>
