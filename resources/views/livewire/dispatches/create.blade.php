<?php

use App\Models\Employee;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public $company_id = '';

    public $warehouse_id = '';

    public $area_id = '';

    public $employee_id = '';

    public $dispatch_type = 'interno';

    public $recipient_name = '';

    public $recipient_email = '';

    public $recipient_phone = '';

    // public $delivery_address = ''; // Comentado por petición del cliente: quitar dirección de entrega
    public $physical_document_number = '';

    public $document_date = '';

    public $notes = '';

    public $status = 'borrador';

    public array $details = [];

    // Purchase loading properties
    public string $purchaseSearch = '';

    public ?int $loadedPurchaseId = null;

    public ?string $loadedPurchaseNumber = null;

    public array $purchaseLoadErrors = [];

    public function mount(): void
    {
        // Set default company to user's company if not super admin
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }

        $this->document_date = now()->format('Y-m-d');

        // Initialize with 10 empty rows
        $this->details = array_fill(0, 10, [
            'product_id' => '',
            'quantity' => 1,
            'unit_of_measure_id' => '',
            'unit_price' => 0,
            'notes' => '',
        ]);
    }

    public function updatedCompanyId(): void
    {
        // Reset selections when company changes
        $this->warehouse_id = '';
        $this->area_id = '';
        $this->employee_id = '';
        // Clear cached data since company changed
        unset($this->products);
        unset($this->employees);
        // Clear loaded purchase
        $this->clearLoadedPurchase();
    }

    public function updatedAreaId(): void
    {
        // Area change no longer affects employees since we show all employees
    }

    public function updatedWarehouseId(): void
    {
        // Clear cached products so they reload for the new warehouse
        unset($this->products);
        // Dispatch event to update Alpine's productsData
        $this->dispatch('products-updated', productsData: $this->productsData);
        // Clear loaded purchase since warehouse changed
        $this->purchaseSearch = '';
        $this->loadedPurchaseId = null;
        $this->loadedPurchaseNumber = null;
        $this->purchaseLoadErrors = [];
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
            'unit_of_measure_id' => '',
            'unit_price' => 0,
            'notes' => '',
        ];
    }

    public function removeDetail(int $index): void
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
            'unit_of_measure_id' => '',
            'unit_price' => 0,
            'notes' => '',
        ]);

        $this->details = array_merge($this->details, $newRows);

        \Flux::toast('5 filas agregadas', variant: 'success');
    }

    public function searchAndLoadPurchase(): void
    {
        $this->purchaseLoadErrors = [];

        if (empty($this->purchaseSearch)) {
            $this->purchaseLoadErrors[] = 'Ingrese un número de compra o factura.';

            return;
        }

        if (empty($this->company_id)) {
            $this->purchaseLoadErrors[] = 'Seleccione una empresa primero.';

            return;
        }

        if (empty($this->warehouse_id)) {
            $this->purchaseLoadErrors[] = 'Seleccione una bodega primero.';

            return;
        }

        // Find purchase by purchase_number or document_number
        $purchase = Purchase::where('company_id', $this->company_id)
            ->where(function ($query) {
                $query->where('purchase_number', $this->purchaseSearch)
                    ->orWhere('document_number', $this->purchaseSearch);
            })
            ->with(['details.product', 'warehouse'])
            ->first();

        if (! $purchase) {
            $this->purchaseLoadErrors[] = 'No se encontró la compra con el código: '.$this->purchaseSearch;

            return;
        }

        // Validate status
        if ($purchase->status !== 'recibido') {
            $statusLabels = [
                'borrador' => 'Borrador',
                'pendiente' => 'Pendiente',
                'aprobado' => 'Aprobado',
                'cancelado' => 'Cancelado',
            ];
            $statusLabel = $statusLabels[$purchase->status] ?? $purchase->status;
            $this->purchaseLoadErrors[] = 'La compra debe estar en estado "Recibido" para poder despachar. Estado actual: '.$statusLabel;

            return;
        }

        // Validate warehouse matches
        if ((int) $purchase->warehouse_id !== (int) $this->warehouse_id) {
            $this->purchaseLoadErrors[] = 'La compra fue recibida en "'.$purchase->warehouse->name.'" pero la bodega seleccionada es diferente.';

            return;
        }

        // Load products from purchase
        $this->loadProductsFromPurchase($purchase);
    }

    private function loadProductsFromPurchase(Purchase $purchase): void
    {
        // Clear existing details
        $this->details = [];

        // Get available stock for validation
        $availableStock = Inventory::where('warehouse_id', $this->warehouse_id)
            ->whereIn('product_id', $purchase->details->pluck('product_id'))
            ->get()
            ->keyBy('product_id');

        $stockWarnings = [];

        // Load each purchase detail as a dispatch detail
        foreach ($purchase->details as $detail) {
            $currentStock = $availableStock->get($detail->product_id)?->available_quantity ?? 0;

            // Add detail row
            $this->details[] = [
                'product_id' => (string) $detail->product_id,
                'quantity' => min($detail->quantity, $currentStock), // Don't exceed available stock
                'unit_of_measure_id' => (string) ($detail->product->unit_of_measure_id ?? ''),
                'unit_price' => $detail->unit_cost ?? 0,
                'notes' => $detail->notes ?? '',
            ];

            // Check for stock issues
            if ($currentStock < $detail->quantity) {
                $productName = $detail->product?->name ?? 'Producto #'.$detail->product_id;
                $stockWarnings[] = "{$productName}: solicitado ".number_format($detail->quantity, 5).", disponible ".number_format($currentStock, 5);
            }
        }

        // Set loaded purchase info
        $this->loadedPurchaseId = $purchase->id;
        $this->loadedPurchaseNumber = $purchase->purchase_number;

        // Auto-fill notes
        $this->notes = "Despacho de compra {$purchase->purchase_number}";

        // Show success/warnings
        if (empty($stockWarnings)) {
            \Flux::toast(
                heading: 'Compra Cargada',
                text: 'Se cargaron '.count($this->details)." productos de la compra {$purchase->purchase_number}",
                variant: 'success',
            );
        } else {
            $this->purchaseLoadErrors = $stockWarnings;
            \Flux::toast(
                heading: 'Compra Cargada con Advertencias',
                text: 'Algunos productos no tienen stock suficiente. Se ajustaron las cantidades.',
                variant: 'warning',
            );
        }
    }

    public function clearLoadedPurchase(): void
    {
        $this->purchaseSearch = '';
        $this->loadedPurchaseId = null;
        $this->loadedPurchaseNumber = null;
        $this->purchaseLoadErrors = [];
        $this->notes = '';

        // Reset to default empty rows
        $this->details = array_fill(0, 10, [
            'product_id' => '',
            'quantity' => 1,
            'unit_of_measure_id' => '',
            'unit_price' => 0,
            'notes' => '',
        ]);
    }

    public function getAvailableStock(int $productId): float
    {
        if (! $this->warehouse_id) {
            return 0;
        }

        $inventory = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $this->warehouse_id)
            ->where('is_active', true)
            ->first();

        return $inventory ? (float) $inventory->available_quantity : 0;
    }

    public function save(): void
    {
        // Filter out empty rows (rows without product_id)
        $filledDetails = array_filter($this->details, fn($detail) => !empty($detail['product_id']));

        if (empty($filledDetails)) {
            \Flux::toast('Debe agregar al menos un producto al despacho.', variant: 'danger');
            return;
        }

        // Re-index the array
        $this->details = array_values($filledDetails);

        $rules = [
            'warehouse_id' => 'required|exists:warehouses,id',
            'dispatch_type' => 'required|in:venta,interno,externo,donacion',
            'physical_document_number' => 'required|string|max:100|unique:dispatches,physical_document_number',
            'document_date' => 'required|date',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:0.0001',
            'details.*.unit_of_measure_id' => 'required|exists:units_of_measure,id',
            'details.*.unit_price' => 'required|numeric|min:0.01',
        ];

        // Add company_id validation for super admins
        if ($this->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $customAttributes = [
            'warehouse_id' => 'bodega',
            'dispatch_type' => 'tipo de despacho',
            'physical_document_number' => 'número de documento físico',
            'document_date' => 'fecha del documento',
            'company_id' => 'empresa',
            'details.*.product_id' => 'producto',
            'details.*.quantity' => 'cantidad',
            'details.*.unit_of_measure_id' => 'unidad de medida',
            'details.*.unit_price' => 'precio unitario',
        ];

        $this->validate($rules, [], $customAttributes);

        // Validate stock availability for each product
        $stockErrors = [];
        foreach ($this->details as $index => $detail) {
            if (! empty($detail['product_id']) && ! empty($detail['quantity'])) {
                $availableStock = $this->getAvailableStock((int) $detail['product_id']);
                if ($detail['quantity'] > $availableStock) {
                    $product = Product::find($detail['product_id']);
                    $productName = $product ? $product->name : 'Producto';
                    $stockErrors["details.{$index}.quantity"] = "La cantidad solicitada ({$detail['quantity']}) excede el stock disponible ({$availableStock}) para {$productName}.";
                }
            }
        }

        if (! empty($stockErrors)) {
            foreach ($stockErrors as $field => $message) {
                $this->addError($field, $message);
            }

            return;
        }

        // Use selected company_id or user's company_id for non-super-admins
        $companyId = $this->isSuperAdmin() ? $this->company_id : auth()->user()->company_id;

        \DB::transaction(function () use ($companyId) {
            $dispatch = Dispatch::create([
                'company_id' => $companyId,
                'warehouse_id' => $this->warehouse_id,
                'area_id' => $this->area_id ?: null,
                'employee_id' => $this->employee_id ?: null,
                'dispatch_type' => $this->dispatch_type,
                'physical_document_number' => $this->physical_document_number,
                'document_date' => $this->document_date,
                'recipient_name' => $this->recipient_name,
                'recipient_email' => $this->recipient_email,
                'recipient_phone' => $this->recipient_phone,
                // 'delivery_address' => $this->delivery_address, // Comentado por petición del cliente: quitar dirección de entrega
                'notes' => $this->notes,
                'status' => $this->status,
                'shipping_cost' => 0,
            ]);

            foreach ($this->details as $detail) {
                DispatchDetail::create([
                    'dispatch_id' => $dispatch->id,
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'unit_of_measure_id' => $detail['unit_of_measure_id'],
                    'unit_price' => $detail['unit_price'] ?? 0,
                    'notes' => $detail['notes'] ?? null,
                ]);
            }

            $dispatch->calculateTotals();

            session()->flash('success', 'Despacho creado exitosamente.');
            $this->redirect(route('dispatches.show', $dispatch), navigate: true);
        });
    }

    #[\Livewire\Attributes\Computed]
    public function companies()
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Company::active()->orderBy('name')->get(['id', 'name']);
        }

        return collect([]);
    }

    #[\Livewire\Attributes\Computed]
    public function warehouses()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return Warehouse::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    #[\Livewire\Attributes\Computed(persist: true)]
    public function employees()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return Employee::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->select('id', 'name', 'position', 'phone', 'mobile', 'email')
            ->orderBy('name')
            ->get();
    }

    public function updatedEmployeeId(): void
    {
        if (! $this->employee_id) {
            return;
        }

        $employee = $this->employees->firstWhere('id', $this->employee_id);
        if ($employee) {
            $this->recipient_name = $employee->name ?? '';
            $this->recipient_phone = $employee->phone ?: ($employee->mobile ?? '');
            $this->recipient_email = $employee->email ?? '';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function areas()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return \App\Models\Area::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    #[\Livewire\Attributes\Computed(persist: true)]
    public function products()
    {
        if (! $this->company_id || ! $this->warehouse_id) {
            return collect([]);
        }

        // Load products with stock info and cost in a single query
        // This prevents additional queries when selecting a product
        return Product::where('products.company_id', $this->company_id)
            ->where('products.is_active', true)
            ->join('inventory', function ($join) {
                $join->on('inventory.product_id', '=', 'products.id')
                    ->where('inventory.warehouse_id', '=', $this->warehouse_id)
                    ->where('inventory.is_active', '=', true)
                    ->where('inventory.available_quantity', '>', 0);
            })
            ->leftJoin('units_of_measure', 'units_of_measure.id', '=', 'products.unit_of_measure_id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.unit_of_measure_id',
                'products.cost',
                'inventory.quantity as stock_quantity',
                'inventory.reserved_quantity as stock_reserved',
                'inventory.available_quantity as stock_available',
                'units_of_measure.abbreviation as unit_abbreviation'
            )
            ->orderBy('products.name')
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function units()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return UnitOfMeasure::forCompany($this->company_id)
            ->active()
            ->select('id', 'name', 'abbreviation')
            ->get();
    }

    /**
     * Get products as a keyed array for Alpine.js
     * This allows instant access to product data without server roundtrip
     */
    #[\Livewire\Attributes\Computed]
    public function productsData(): array
    {
        return $this->products->keyBy('id')->map(fn($p) => [
            'cost' => (float) ($p->cost ?? 0),
            'unit' => $p->unit_abbreviation ?? '',
            'unit_id' => $p->unit_of_measure_id,
            'stock' => (float) ($p->stock_available ?? 0),
            'reserved' => (float) ($p->stock_reserved ?? 0),
        ])->toArray();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nuevo Despacho</flux:heading>
            <flux:text class="mt-1">Crear un nuevo despacho</flux:text>
        </div>

        <flux:button variant="ghost" href="{{ route('dispatches.index') }}" wire:navigate>
            Cancelar
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-8">
        <flux:card>
            <flux:heading size="lg" class="mb-6">Información del Despacho</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($this->isSuperAdmin())
                    <flux:field class="md:col-span-2">
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
                    <flux:label badge="Requerido">Bodega</flux:label>
                    <div wire:loading.delay wire:target="company_id" class="mb-2">
                        <flux:text size="sm" class="text-blue-600 dark:text-blue-400">
                            Cargando bodegas...
                        </flux:text>
                    </div>
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
                    <flux:input value="Interno" disabled />
                    <flux:description>Los despachos siempre son internos</flux:description>
                    <flux:error name="dispatch_type" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Número de documento físico</flux:label>
                    <flux:input wire:model="physical_document_number" placeholder="Ingrese el número de documento físico" />
                    <flux:error name="physical_document_number" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Fecha del Documento</flux:label>
                    <flux:input type="date" wire:model="document_date" />
                    <flux:error name="document_date" />
                </flux:field>

                <!-- Unidad Solicitante (Área) -->
                <flux:field wire:key="area-field">
                    <flux:label badge="Requerido">Unidad Solicitante</flux:label>
                    <flux:select wire:model.live="area_id" :disabled="$this->isSuperAdmin() && !$company_id">
                        <option value="">Seleccione área</option>
                        @foreach ($this->areas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:description>Seleccione el área solicitante</flux:description>
                    <flux:error name="area_id" />
                </flux:field>

                <!-- Persona Solicitante -->
                <flux:field>
                    <flux:label>Persona Solicitante</flux:label>
                    <flux:select wire:model.live="employee_id">
                        <option value="">Seleccione persona...</option>
                        @foreach ($this->employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}{{ $employee->position ? ' (' . $employee->position . ')' : '' }}</option>
                        @endforeach
                    </flux:select>
                    <flux:description>Al seleccionar, se autocompletarán los datos del receptor</flux:description>
                    <flux:error name="employee_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Nombre del Receptor</flux:label>
                    <flux:input wire:model="recipient_name" />
                    <flux:error name="recipient_name" />
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono del Receptor</flux:label>
                    <flux:input wire:model="recipient_phone" />
                    <flux:error name="recipient_phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Email del Receptor</flux:label>
                    <flux:input type="email" wire:model="recipient_email" />
                    <flux:error name="recipient_email" />
                </flux:field>

                {{-- Comentado por petición del cliente: quitar dirección de entrega
                <flux:field class="md:col-span-2">
                    <flux:label>Dirección de Entrega</flux:label>
                    <flux:textarea wire:model="delivery_address" rows="2" />
                    <flux:error name="delivery_address" />
                </flux:field>
                --}}

                <flux:field class="md:col-span-2">
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" rows="3" />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        {{-- Load from Purchase Section --}}
        <flux:card>
            <flux:accordion>
                <flux:accordion.item>
                    <flux:accordion.heading>
                        <div class="flex items-center gap-2">
                            <flux:icon name="document-arrow-down" class="w-5 h-5" />
                            <span>Cargar desde Compra</span>
                            @if($loadedPurchaseNumber)
                                <flux:badge color="green" size="sm">{{ $loadedPurchaseNumber }}</flux:badge>
                            @endif
                        </div>
                    </flux:accordion.heading>
                    <flux:accordion.content>
                        <div class="space-y-4 pt-4">
                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                Ingrese el número de compra o número de factura para cargar todos los productos automáticamente.
                            </flux:text>

                            <div class="flex gap-3" x-data="{ searchValue: '' }">
                                <flux:field class="flex-1">
                                    <flux:input
                                        wire:model="purchaseSearch"
                                        x-model="searchValue"
                                        placeholder="Ej: PUR-20260127-ABC123 o FAC-001234"
                                        :disabled="!$company_id || !$warehouse_id"
                                        wire:keydown.enter="searchAndLoadPurchase"
                                    />
                                </flux:field>
                                <flux:button
                                    type="button"
                                    variant="primary"
                                    icon="magnifying-glass"
                                    wire:click="searchAndLoadPurchase"
                                    wire:loading.attr="disabled"
                                    x-bind:disabled="!searchValue.trim() || {{ (!$company_id || !$warehouse_id) ? 'true' : 'false' }}"
                                >
                                    <span wire:loading.remove wire:target="searchAndLoadPurchase">Buscar</span>
                                    <span wire:loading wire:target="searchAndLoadPurchase">Buscando...</span>
                                </flux:button>
                            </div>

                            @if(!$company_id || !$warehouse_id)
                                <flux:callout color="yellow" icon="information-circle">
                                    Seleccione empresa y bodega primero para buscar compras.
                                </flux:callout>
                            @endif

                            @if(!empty($purchaseLoadErrors))
                                <flux:callout color="amber" icon="exclamation-triangle">
                                    <div class="space-y-1">
                                        @foreach($purchaseLoadErrors as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                </flux:callout>
                            @endif

                            @if($loadedPurchaseNumber)
                                <div class="flex items-center justify-between bg-green-50 dark:bg-green-900/20 p-3 rounded-lg border border-green-200 dark:border-green-800">
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="check-circle" class="w-5 h-5 text-green-600" />
                                        <span class="text-green-700 dark:text-green-300">
                                            Compra cargada: <strong>{{ $loadedPurchaseNumber }}</strong>
                                        </span>
                                    </div>
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        icon="x-mark"
                                        wire:click="clearLoadedPurchase"
                                    >
                                        Limpiar
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    </flux:accordion.content>
                </flux:accordion.item>
            </flux:accordion>
        </flux:card>

        <flux:card wire:key="products-card-{{ $warehouse_id }}-{{ count($details) }}">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg" badge="Requerido">Productos del Despacho</flux:heading>
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

            <!-- Loading indicator when warehouse changes -->
            <div wire:loading wire:target="warehouse_id" class="flex items-center justify-center py-8">
                <flux:icon name="arrow-path" class="w-6 h-6 animate-spin text-blue-500" />
                <flux:text class="ml-2 text-blue-600 dark:text-blue-400">Cargando productos...</flux:text>
            </div>

            <!-- Loading indicator when adding rows -->
            <div wire:loading wire:target="addDetail, addMoreRows" class="flex items-center justify-center py-4">
                <flux:icon name="arrow-path" class="w-5 h-5 animate-spin text-blue-500" />
                <flux:text class="ml-2 text-blue-600 dark:text-blue-400">Agregando filas...</flux:text>
            </div>

            <div wire:loading.remove wire:target="warehouse_id, addDetail, addMoreRows" class="overflow-x-auto"
                 x-data
                 x-init="
                    $store.dispatchProducts = @js($this->productsData);
                    $store.rowTotals = {};
                    $store.grandTotal = 0;
                 "
                 x-on:products-updated.window="$store.dispatchProducts = $event.detail.productsData"
                 x-on:row-total-updated.window="
                    $store.rowTotals[$event.detail.index] = $event.detail.total;
                    $store.grandTotal = Object.values($store.rowTotals).reduce((sum, val) => sum + (parseFloat(val) || 0), 0);
                 "
            >
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-12">#</flux:table.column>
                        <flux:table.column class="min-w-[300px]">Producto</flux:table.column>
                        <flux:table.column class="w-28 text-center">Cantidad</flux:table.column>
                        <flux:table.column class="w-32 text-right">Precio Unit.</flux:table.column>
                        <flux:table.column class="w-32 text-right">Total</flux:table.column>
                        <flux:table.column class="w-24 text-center">Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($details as $index => $detail)
                        <tbody x-data="dispatchRow({
                            index: {{ $index }},
                            productId: '{{ $detail['product_id'] }}',
                            quantity: {{ (float) ($detail['quantity'] ?? 1) }},
                            unitPrice: {{ (float) ($detail['unit_price'] ?? 0) }},
                            unitId: '{{ $detail['unit_of_measure_id'] }}',
                            notes: `{{ addslashes($detail['notes'] ?? '') }}`
                        })" wire:key="detail-group-{{ $index }}">
                        <flux:table.row x-bind:class="productId ? '' : 'opacity-60'">
                            <flux:table.cell class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $index + 1 }}
                            </flux:table.cell>

                            <!-- Product Select with Unit and Stock Badge -->
                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <flux:select
                                        variant="listbox"
                                        searchable
                                        x-model="productId"
                                        x-on:change="selectProduct($event.target.value)"
                                        :disabled="!$company_id || !$warehouse_id"
                                        placeholder="{{ !$company_id ? 'Seleccione empresa primero' : (!$warehouse_id ? 'Seleccione bodega primero' : 'Seleccionar producto...') }}"
                                    >
                                        @foreach($this->products as $product)
                                            <flux:select.option value="{{ $product->id }}">
                                                {{ $product->name }}{{ $product->sku ? ' - ' . $product->sku : '' }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <!-- Unit and Stock Badge (Alpine.js - instant) -->
                                    <template x-if="productInfo">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <template x-if="productInfo.unit">
                                                <span class="inline-flex items-center rounded-md bg-zinc-100 dark:bg-zinc-700 px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                                    Unidad: <span x-text="productInfo.unit" class="ml-1"></span>
                                                </span>
                                            </template>
                                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                                                  :class="productInfo.stock > 10 ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300' : (productInfo.stock > 0 ? 'bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300' : 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300')">
                                                Stock: <span x-text="productInfo.stock.toFixed(5)" class="ml-1"></span>
                                                <span x-text="productInfo.unit" class="ml-1"></span>
                                            </span>
                                            <template x-if="productInfo.reserved > 0">
                                                <span class="inline-flex items-center rounded-md bg-amber-100 dark:bg-amber-900 px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                                                    <span x-text="productInfo.reserved.toFixed(5)"></span> reservado
                                                </span>
                                            </template>
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
                                        step="0.00001"
                                        min="0.00001"
                                        x-model.number="quantity"
                                        @input="emitTotal()"
                                        @change="updateQuantity()"
                                        class="block w-full text-center rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                                    />
                                    <flux:error name="details.{{ $index }}.quantity" />
                                </div>
                            </flux:table.cell>

                            <!-- Unit Price -->
                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <input
                                        type="number"
                                        step="0.00001"
                                        min="0"
                                        x-model.number="unitPrice"
                                        @input="emitTotal()"
                                        @change="updateUnitPrice()"
                                        placeholder="0.00000"
                                        class="block w-full text-right rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                                    />
                                    <flux:error name="details.{{ $index }}.unit_price" />
                                </div>
                            </flux:table.cell>

                            <!-- Total (Calculated with Alpine - instant) -->
                            <flux:table.cell class="text-right font-semibold">
                                $<span x-text="total.toFixed(5)"></span>
                            </flux:table.cell>

                            <!-- Actions -->
                            <flux:table.cell class="text-center">
                                <div class="flex items-center justify-center gap-1" x-show="productId">
                                    <!-- Expand/Collapse for Notes (Alpine.js - client-side only) -->
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        x-on:click="expanded = !expanded"
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

                        <!-- Expandable Notes Row (Alpine.js - client-side only) -->
                        <flux:table.row x-show="expanded" x-collapse class="bg-zinc-50 dark:bg-zinc-800">
                            <flux:table.cell colspan="6" class="py-3">
                                <div class="px-4">
                                    <flux:label>Notas (opcional)</flux:label>
                                    <textarea
                                        x-model="notes"
                                        @blur="syncToLivewire()"
                                        placeholder="Información adicional sobre este producto..."
                                        rows="2"
                                        class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
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
                        $<span x-text="($store.grandTotal || 0).toFixed(5)">0.00000</span>
                    </flux:heading>
                </div>
            </div>

            <flux:error name="details" />
        </flux:card>

        <div class="flex items-center justify-between">
            <flux:button variant="ghost" href="{{ route('dispatches.index') }}" wire:navigate type="button">
                Cancelar
            </flux:button>

            <flux:button type="submit" variant="primary" icon="check">
                Guardar Despacho
            </flux:button>
        </div>
    </form>
</div>
