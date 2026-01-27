<?php

use App\Http\Requests\StorePurchaseRequest;
use App\Models\AcquisitionType;
use App\Models\Company;
use App\Models\FundSource;
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

    public $purchase_type = 'credito';

    public $payment_method = null; // Comentado en UI por petición del cliente

    public $acquisition_type_id = '';

    public $project_name = '';

    public $agreement_number = '';

    public $fund_source_id = '';

    public $shipping_cost = 0;

    // Modal para crear origen de fondos
    public $newFundSourceName = '';

    public $newFundSourceDescription = '';

    // Modal para crear tipo de adquisición
    public $newAcquisitionTypeName = '';

    public $newAcquisitionTypeDescription = '';

    public $notes = '';

    public $admin_notes = '';

    public $details = [];

    public function mount(): void
    {
        $this->document_date = now()->format('Y-m-d');

        // If not super admin, set company_id to user's company
        if (! $this->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }

        // Initialize with 10 empty rows
        $this->details = array_fill(0, 10, [
            'product_id' => '',
            'quantity' => 1,
            'unit_cost' => 0,
            'discount_percentage' => 0,
            'tax_percentage' => 13,
            'lot_number' => '',
            'expiration_date' => '',
            'notes' => '',
        ]);
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

    public function addMoreRows(): void
    {
        // Add 5 rows at once (single array operation, single re-render)
        $newRows = array_fill(0, 5, [
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
            'due_date' => ! empty($validated['due_date']) ? $validated['due_date'] : null,
            'purchase_type' => $validated['purchase_type'],
            'payment_method' => $validated['payment_method'] ?? null,
            'acquisition_type_id' => $validated['acquisition_type_id'],
            'project_name' => $validated['project_name'] ?? null,
            'agreement_number' => $validated['agreement_number'] ?? null,
            'is_retroactive' => $isRetroactive,
            'fund_source_id' => $validated['fund_source_id'] ?? null,
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
                'expiration_date' => ! empty($detail['expiration_date']) ? $detail['expiration_date'] : null,
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

    #[Computed]
    public function fundSources()
    {
        return FundSource::active()->orderBy('name')->get();
    }

    #[Computed]
    public function acquisitionTypes()
    {
        return AcquisitionType::active()->orderBy('name')->get();
    }

    public function openFundSourceModal(): void
    {
        $this->resetFundSourceForm();
        $this->modal('fund-source-modal')->show();
    }

    public function closeFundSourceModal(): void
    {
        $this->modal('fund-source-modal')->close();
        $this->resetFundSourceForm();
    }

    public function resetFundSourceForm(): void
    {
        $this->newFundSourceName = '';
        $this->newFundSourceDescription = '';
        $this->resetValidation(['newFundSourceName']);
    }

    public function saveFundSource(): void
    {
        $this->validate([
            'newFundSourceName' => ['required', 'string', 'max:255', 'unique:fund_sources,name'],
            'newFundSourceDescription' => ['nullable', 'string', 'max:1000'],
        ], [
            'newFundSourceName.required' => 'El nombre es obligatorio.',
            'newFundSourceName.unique' => 'Este origen de fondos ya existe.',
        ]);

        $fundSource = FundSource::create([
            'name' => $this->newFundSourceName,
            'description' => $this->newFundSourceDescription,
            'is_active' => true,
            'active_at' => now(),
            'created_by' => auth()->id(),
        ]);

        $this->fund_source_id = $fundSource->id;
        $this->closeFundSourceModal();

        \Flux::toast(
            variant: 'success',
            heading: '¡Éxito!',
            text: 'Origen de fondos creado exitosamente.',
        );
    }

    public function openAcquisitionTypeModal(): void
    {
        $this->resetAcquisitionTypeForm();
        $this->modal('acquisition-type-modal')->show();
    }

    public function closeAcquisitionTypeModal(): void
    {
        $this->modal('acquisition-type-modal')->close();
        $this->resetAcquisitionTypeForm();
    }

    public function resetAcquisitionTypeForm(): void
    {
        $this->newAcquisitionTypeName = '';
        $this->newAcquisitionTypeDescription = '';
        $this->resetValidation(['newAcquisitionTypeName']);
    }

    public function saveAcquisitionType(): void
    {
        $this->validate([
            'newAcquisitionTypeName' => ['required', 'string', 'max:255', 'unique:acquisition_types,name'],
            'newAcquisitionTypeDescription' => ['nullable', 'string', 'max:1000'],
        ], [
            'newAcquisitionTypeName.required' => 'El nombre es obligatorio.',
            'newAcquisitionTypeName.unique' => 'Este tipo de adquisición ya existe.',
        ]);

        $acquisitionType = AcquisitionType::create([
            'name' => $this->newAcquisitionTypeName,
            'description' => $this->newAcquisitionTypeDescription,
            'is_active' => true,
            'active_at' => now(),
            'created_by' => auth()->id(),
        ]);

        $this->acquisition_type_id = $acquisitionType->id;
        $this->closeAcquisitionTypeModal();

        \Flux::toast(
            variant: 'success',
            heading: '¡Éxito!',
            text: 'Tipo de adquisición creado exitosamente.',
        );
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

    /**
     * Get products as a keyed array for Alpine.js
     * This allows instant access to product data without server roundtrip
     */
    #[Computed]
    public function productsData(): array
    {
        return $this->products->keyBy('id')->map(fn ($p) => [
            'name' => $p->name,
            'sku' => $p->sku,
            'cost' => (float) ($p->cost ?? 0),
            'unit' => $p->unitOfMeasure?->abbreviation ?? '',
            'unit_name' => $p->unitOfMeasure?->name ?? '',
        ])->toArray();
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
                        <flux:select wire:model.live="company_id" variant="listbox" searchable placeholder="Buscar empresa...">
                            @foreach ($this->companies as $company)
                                <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="company_id" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label badge="Requerido">Bodega Destino</flux:label>
                    <flux:select wire:model="warehouse_id" variant="listbox" searchable placeholder="Buscar bodega..." :disabled="$this->isSuperAdmin() && !$company_id">
                        @foreach ($this->warehouses as $warehouse)
                            <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="warehouse_id" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Proveedor</flux:label>
                    <flux:select wire:model="supplier_id" variant="listbox" searchable placeholder="Buscar proveedor..." :disabled="$this->isSuperAdmin() && !$company_id">
                        @foreach ($this->suppliers as $supplier)
                            <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }}</flux:select.option>
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
                        <option value="contado">Contado</option>
                        <option value="credito">Crédito</option>
                    </flux:select>
                    <flux:error name="purchase_type" />
                </flux:field>

                {{-- Método de Pago - Comentado por petición del cliente --}}
                {{-- <flux:field>
                    <flux:label>Método de Pago</flux:label>
                    <flux:select wire:model="payment_method">
                        <option value="">Seleccione un método</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="cheque">Cheque</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                    </flux:select>
                    <flux:error name="payment_method" />
                </flux:field> --}}

                <flux:field>
                    <div class="flex items-center justify-between">
                        <flux:label>Origen de Fondos</flux:label>
                        <button type="button" wire:click="openFundSourceModal" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1">
                            <flux:icon name="plus" class="h-3 w-3" />
                            Nuevo origen
                        </button>
                    </div>
                    <flux:select wire:model="fund_source_id" variant="listbox" searchable placeholder="Buscar origen de fondos...">
                        @foreach ($this->fundSources as $fundSource)
                            <flux:select.option value="{{ $fundSource->id }}">{{ $fundSource->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="fund_source_id" />
                </flux:field>

                {{-- Costo de Envío - Comentado por petición del cliente --}}
                {{-- <flux:field>
                    <flux:label>Costo de Envío ($)</flux:label>
                    <flux:input type="number" step="0.01" wire:model="shipping_cost" />
                    <flux:error name="shipping_cost" />
                </flux:field> --}}
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-6">Tipo de Adquisición</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:field>
                    <div class="flex items-center justify-between">
                        <flux:label badge="Requerido">Tipo de Adquisición</flux:label>
                        <button type="button" wire:click="openAcquisitionTypeModal" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1">
                            <flux:icon name="plus" class="h-3 w-3" />
                            Nuevo tipo
                        </button>
                    </div>
                    <flux:select wire:model.live="acquisition_type_id" variant="listbox" searchable placeholder="Buscar tipo de adquisición...">
                        @foreach ($this->acquisitionTypes as $acquisitionType)
                            <flux:select.option value="{{ $acquisitionType->id }}">{{ $acquisitionType->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="acquisition_type_id" />
                </flux:field>

                @php
                    $selectedAcquisitionType = $this->acquisitionTypes->firstWhere('id', $acquisition_type_id);
                    $acquisitionSlug = $selectedAcquisitionType?->slug ?? '';
                @endphp

                @if($acquisitionSlug === 'proyecto')
                    <flux:field>
                        <flux:label>Nombre del Proyecto</flux:label>
                        <flux:input wire:model="project_name" placeholder="Ej: Proyecto Infraestructura 2025" />
                        <flux:error name="project_name" />
                    </flux:field>
                @endif

                @if($acquisitionSlug === 'convenio')
                    <flux:field>
                        <flux:label>Número de Convenio</flux:label>
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

        <flux:card wire:key="products-card-{{ $company_id }}-{{ count($details) }}">
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

            <!-- Loading indicator when company changes -->
            <div wire:loading wire:target="company_id" class="flex items-center justify-center py-8">
                <flux:icon name="arrow-path" class="w-6 h-6 animate-spin text-blue-500" />
                <flux:text class="ml-2 text-blue-600 dark:text-blue-400">Cargando productos...</flux:text>
            </div>

            <!-- Loading indicator when adding rows -->
            <div wire:loading wire:target="addDetail, addMoreRows" class="flex items-center justify-center py-4">
                <flux:icon name="arrow-path" class="w-5 h-5 animate-spin text-blue-500" />
                <flux:text class="ml-2 text-blue-600 dark:text-blue-400">Agregando filas...</flux:text>
            </div>

            <div wire:loading.remove wire:target="company_id, addDetail, addMoreRows" class="overflow-x-auto"
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
                                        :disabled="$this->isSuperAdmin() && !$company_id"
                                        placeholder="{{ $this->isSuperAdmin() && !$company_id ? 'Seleccione empresa primero' : 'Buscar producto...' }}"
                                    >
                                        @foreach($this->products as $product)
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

    <!-- Modal para crear origen de fondos -->
    <flux:modal name="fund-source-modal" class="min-w-[30rem]">
        <form wire:submit="saveFundSource" class="space-y-6">
            <div>
                <flux:heading size="lg">Nuevo Origen de Fondos</flux:heading>
                <flux:subheading>Completa la información para crear un nuevo origen de fondos</flux:subheading>
            </div>

            <div class="space-y-6">
                <!-- Nombre -->
                <flux:field>
                    <flux:label badge="Requerido">Nombre</flux:label>
                    <flux:input wire:model="newFundSourceName" placeholder="Ej: Fondos Propios, Fondos GOES" />
                    <flux:error name="newFundSourceName" />
                </flux:field>

                <!-- Descripción -->
                <flux:field>
                    <flux:label>Descripción (Opcional)</flux:label>
                    <flux:textarea wire:model="newFundSourceDescription" rows="3" placeholder="Descripción adicional..." />
                    <flux:error name="newFundSourceDescription" />
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal para crear tipo de adquisición -->
    <flux:modal name="acquisition-type-modal" class="min-w-[30rem]">
        <form wire:submit="saveAcquisitionType" class="space-y-6">
            <div>
                <flux:heading size="lg">Nuevo Tipo de Adquisición</flux:heading>
                <flux:subheading>Completa la información para crear un nuevo tipo de adquisición</flux:subheading>
            </div>

            <div class="space-y-6">
                <!-- Nombre -->
                <flux:field>
                    <flux:label badge="Requerido">Nombre</flux:label>
                    <flux:input wire:model="newAcquisitionTypeName" placeholder="Ej: Compra Normal, Convenio, Proyecto" />
                    <flux:error name="newAcquisitionTypeName" />
                </flux:field>

                <!-- Descripción -->
                <flux:field>
                    <flux:label>Descripción (Opcional)</flux:label>
                    <flux:textarea wire:model="newAcquisitionTypeDescription" rows="3" placeholder="Descripción adicional..." />
                    <flux:error name="newAcquisitionTypeDescription" />
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
