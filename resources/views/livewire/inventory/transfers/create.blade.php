<?php

use App\Models\{InventoryTransfer, InventoryTransferDetail, Warehouse, Product, Inventory, Company};
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public $company_id = '';
    public $from_warehouse_id = '';
    public $to_warehouse_id = '';
    public $reason = '';
    public $notes = '';
    public $shipping_cost = 0;
    public $products = [];
    public $availableStock = [];
    public $productUnits = [];

    public function mount(): void
    {
        // Auto-set company_id for non-super admins
        if (!auth()->user()->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }

        $this->addProduct();
    }

    public function updatedCompanyId(): void
    {
        // Reset warehouses and products when company changes
        $this->from_warehouse_id = '';
        $this->to_warehouse_id = '';
        $this->products = [];
        $this->availableStock = [];
        $this->productUnits = [];
        $this->addProduct();
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
        unset($this->productUnits[$index]);
        $this->products = array_values($this->products);
        $this->productUnits = array_values($this->productUnits);
    }

    public function updatedProducts($value, $key): void
    {
        // Check if product_id was updated
        if (str_contains($key, 'product_id')) {
            $index = explode('.', $key)[0];
            $this->checkAvailableStock($index);
            $this->loadProductUnit($index);
        }
    }

    public function updatedFromWarehouseId(): void
    {
        // Recheck stock for all products when warehouse changes
        foreach ($this->products as $index => $product) {
            if (!empty($product['product_id'])) {
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

    private function loadProductUnit($index): void
    {
        if (empty($this->products[$index]['product_id'])) {
            unset($this->productUnits[$index]);
            return;
        }

        $product = Product::with('unitOfMeasure')->find($this->products[$index]['product_id']);
        if ($product && $product->unitOfMeasure) {
            $this->productUnits[$index] = [
                'abbreviation' => $product->unitOfMeasure->abbreviation,
                'name' => $product->unitOfMeasure->name,
            ];
        } else {
            $this->productUnits[$index] = null;
        }
    }

    private function getValidationRules(): array
    {
        $companyId = $this->company_id;
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        $rules = [
            'from_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId)
                        ->where('is_active', true);
                }),
                'different:to_warehouse_id',
            ],
            'to_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId)
                        ->where('is_active', true);
                }),
                'different:from_warehouse_id',
            ],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId)
                        ->where('is_active', true);
                }),
            ],
            'products.*.quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'products.*.notes' => ['nullable', 'string', 'max:500'],
        ];

        if ($isSuperAdmin) {
            $rules['company_id'] = ['required', 'integer', Rule::exists('companies', 'id')->where('is_active', true)];
        }

        return $rules;
    }

    private function getValidationMessages(): array
    {
        return [
            'company_id.required' => 'La empresa es obligatoria.',
            'company_id.exists' => 'La empresa seleccionada no existe o no está activa.',
            'from_warehouse_id.required' => 'La bodega de origen es obligatoria.',
            'from_warehouse_id.exists' => 'La bodega de origen seleccionada no existe o no está activa.',
            'from_warehouse_id.different' => 'La bodega de origen debe ser diferente a la bodega de destino.',
            'to_warehouse_id.required' => 'La bodega de destino es obligatoria.',
            'to_warehouse_id.exists' => 'La bodega de destino seleccionada no existe o no está activa.',
            'to_warehouse_id.different' => 'La bodega de destino debe ser diferente a la bodega de origen.',
            'reason.string' => 'El motivo debe ser texto.',
            'reason.max' => 'El motivo no puede exceder 500 caracteres.',
            'notes.string' => 'Las notas deben ser texto.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'shipping_cost.numeric' => 'El costo de envío debe ser un número.',
            'shipping_cost.min' => 'El costo de envío no puede ser negativo.',
            'shipping_cost.max' => 'El costo de envío no puede exceder 999,999.99.',
            'products.required' => 'Debe agregar al menos un producto al traslado.',
            'products.array' => 'Los productos deben ser un arreglo.',
            'products.min' => 'Debe agregar al menos un producto al traslado.',
            'products.*.product_id.required' => 'El producto es obligatorio.',
            'products.*.product_id.exists' => 'El producto seleccionado no existe o no está activo.',
            'products.*.quantity.required' => 'La cantidad es obligatoria.',
            'products.*.quantity.numeric' => 'La cantidad debe ser un número.',
            'products.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
            'products.*.quantity.max' => 'La cantidad no puede exceder 999,999.9999.',
            'products.*.notes.string' => 'Las notas del producto deben ser texto.',
            'products.*.notes.max' => 'Las notas del producto no pueden exceder 500 caracteres.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate($this->getValidationRules(), $this->getValidationMessages());

        \DB::beginTransaction();
        try {
            // Create the transfer
            $transfer = InventoryTransfer::create([
                'company_id' => $this->company_id,
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'status' => 'pending',
            ]);

            // Create transfer details
            foreach ($validated['products'] as $product) {
                InventoryTransferDetail::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                    'notes' => $product['notes'] ?? null,
                ]);
            }

            \DB::commit();

            session()->flash('success', 'Traslado creado exitosamente.');
            $this->redirect(route('transfers.show', $transfer), navigate: true);
        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Error al crear el traslado. Por favor intente nuevamente.');
            \Log::error('Error creating transfer: '.$e->getMessage());
        }
    }

    public function cancel(): void
    {
        $this->redirect(route('transfers.index'), navigate: true);
    }

    public function with(): array
    {
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        return [
            'isSuperAdmin' => $isSuperAdmin,
            'companies' => $isSuperAdmin ? Company::where('is_active', true)->orderBy('name')->get() : collect(),
            'warehouses' => $this->company_id
                ? Warehouse::where('company_id', $this->company_id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
                : collect(),
            'allProducts' => $this->company_id
                ? Product::where('company_id', $this->company_id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
                : collect(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nuevo Traslado</flux:heading>
            <flux:text class="mt-1">Crear un nuevo traslado entre bodegas</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <flux:card>
            <flux:heading size="lg" class="mb-6">Información del Traslado</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if ($isSuperAdmin)
                    <flux:field class="md:col-span-2">
                        <flux:label badge="Requerido">Empresa</flux:label>
                        <flux:select wire:model.live="company_id">
                            <option value="">Seleccione una empresa</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="company_id" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label badge="Requerido">Bodega de Origen</flux:label>
                    <flux:select wire:model.live="from_warehouse_id" :disabled="!$company_id">
                        <option value="">Seleccione bodega de origen</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="from_warehouse_id" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Bodega de Destino</flux:label>
                    <flux:select wire:model="to_warehouse_id" :disabled="!$company_id">
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
            <div class="flex items-center justify-between mb-6">
                <div>
                    <flux:heading size="lg">Productos</flux:heading>
                    <flux:badge color="red" size="sm" class="mt-1">Requerido</flux:badge>
                </div>
                <flux:button type="button" wire:click="addProduct" variant="primary" size="sm" icon="plus">
                    Agregar Producto
                </flux:button>
            </div>

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
                                <flux:label badge="Requerido">Producto</flux:label>
                                <flux:select wire:model.live="products.{{ $index }}.product_id">
                                    <option value="">Seleccione un producto</option>
                                    @foreach ($allProducts as $prod)
                                        <option value="{{ $prod->id }}">{{ $prod->name }} - {{ $prod->sku }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="products.{{ $index }}.product_id" />
                            </flux:field>

                            <flux:field>
                                <div class="flex items-center gap-2">
                                    <flux:label badge="Requerido">Cantidad</flux:label>
                                    @if (isset($productUnits[$index]) && $productUnits[$index])
                                        <flux:tooltip content="{{ $productUnits[$index]['name'] }}">
                                            <flux:badge size="sm" color="zinc">{{ $productUnits[$index]['abbreviation'] }}</flux:badge>
                                        </flux:tooltip>
                                    @endif
                                </div>
                                <flux:input type="number" step="0.0001" wire:model="products.{{ $index }}.quantity" />
                                <flux:error name="products.{{ $index }}.quantity" />

                                @if (isset($availableStock[$index]))
                                    <div class="mt-2 text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Stock disponible:</span>
                                        <span class="font-medium {{ $availableStock[$index] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ number_format($availableStock[$index], 4) }}
                                            @if (isset($productUnits[$index]) && $productUnits[$index])
                                                {{ $productUnits[$index]['abbreviation'] }}
                                            @else
                                                unidades
                                            @endif
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
                Guardar Traslado
            </flux:button>
        </div>
    </form>
</div>
