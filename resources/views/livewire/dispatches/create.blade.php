<?php

use App\Models\{Warehouse, Customer, Product, UnitOfMeasure, Dispatch, DispatchDetail, Inventory};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public $company_id = '';
    public $warehouse_id = '';
    public $customer_id = '';
    public $dispatch_type = 'interno';
    public $recipient_name = '';
    public $recipient_email = '';
    public $recipient_phone = '';
    public $delivery_address = '';
    public $notes = '';
    public $status = 'borrador';

    public array $details = [];
    public array $stockInfo = [];

    public function mount(): void
    {
        // Set default company to user's company if not super admin
        if (!auth()->user()->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }

        $this->addDetail();
    }

    public function updatedCompanyId(): void
    {
        // Reset selections when company changes
        $this->warehouse_id = '';
        $this->customer_id = '';
        $this->stockInfo = [];
    }

    public function updatedWarehouseId(): void
    {
        // Refresh stock info when warehouse changes
        $this->refreshAllStockInfo();
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

    public function updatedDetails($value, $key): void
    {
        // Check if product_id was updated
        if (str_ends_with($key, 'product_id') && $value) {
            // Extract the index from the key (e.g., "0.product_id" -> 0)
            $index = (int) explode('.', $key)[0];

            // Find the product and set its unit of measure
            $product = Product::find($value);
            if ($product && $product->unit_of_measure_id) {
                $this->details[$index]['unit_of_measure_id'] = $product->unit_of_measure_id;
            }

            // Get stock info for this product
            $this->updateStockInfo($index, $value);
        }
    }

    public function updateStockInfo(int $index, $productId): void
    {
        if (!$this->warehouse_id || !$productId) {
            $this->stockInfo[$index] = null;
            return;
        }

        $inventory = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $this->warehouse_id)
            ->where('is_active', true)
            ->first();

        $product = Product::with('unitOfMeasure')->find($productId);
        $unitCode = $product?->unitOfMeasure?->abbreviation ?? $product?->unitOfMeasure?->code ?? '';

        if ($inventory) {
            $this->stockInfo[$index] = [
                'quantity' => $inventory->quantity,
                'reserved' => $inventory->reserved_quantity,
                'available' => $inventory->available_quantity,
                'unit' => $unitCode,
            ];
        } else {
            $this->stockInfo[$index] = [
                'quantity' => 0,
                'reserved' => 0,
                'available' => 0,
                'unit' => $unitCode,
            ];
        }
    }

    public function refreshAllStockInfo(): void
    {
        $this->stockInfo = [];
        foreach ($this->details as $index => $detail) {
            if (!empty($detail['product_id'])) {
                $this->updateStockInfo($index, $detail['product_id']);
            }
        }
    }

    public function getAvailableStock(int $productId): float
    {
        if (!$this->warehouse_id) {
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
        $rules = [
            'warehouse_id' => 'required|exists:warehouses,id',
            'dispatch_type' => 'required|in:venta,interno,externo,donacion',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:0.0001',
            'details.*.unit_of_measure_id' => 'required|exists:units_of_measure,id',
        ];

        // Add company_id validation for super admins
        if ($this->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $customAttributes = [
            'warehouse_id' => 'bodega',
            'dispatch_type' => 'tipo de despacho',
            'company_id' => 'empresa',
            'details.*.product_id' => 'producto',
            'details.*.quantity' => 'cantidad',
            'details.*.unit_of_measure_id' => 'unidad de medida',
        ];

        $this->validate($rules, [], $customAttributes);

        // Validate stock availability for each product
        $stockErrors = [];
        foreach ($this->details as $index => $detail) {
            if (!empty($detail['product_id']) && !empty($detail['quantity'])) {
                $availableStock = $this->getAvailableStock((int) $detail['product_id']);
                if ($detail['quantity'] > $availableStock) {
                    $product = Product::find($detail['product_id']);
                    $productName = $product ? $product->name : 'Producto';
                    $stockErrors["details.{$index}.quantity"] = "La cantidad solicitada ({$detail['quantity']}) excede el stock disponible ({$availableStock}) para {$productName}.";
                }
            }
        }

        if (!empty($stockErrors)) {
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
                'customer_id' => $this->customer_id ?: null,
                'dispatch_type' => $this->dispatch_type,
                'recipient_name' => $this->recipient_name,
                'recipient_email' => $this->recipient_email,
                'recipient_phone' => $this->recipient_phone,
                'delivery_address' => $this->delivery_address,
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
        if (!$this->company_id) {
            return collect([]);
        }

        return Warehouse::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[\Livewire\Attributes\Computed]
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

    #[\Livewire\Attributes\Computed]
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

    #[\Livewire\Attributes\Computed]
    public function units()
    {
        if (!$this->company_id) {
            return collect([]);
        }

        return UnitOfMeasure::forCompany($this->company_id)->active()->get();
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
                    <flux:label>Destinatario</flux:label>
                    <flux:select wire:model="customer_id" :disabled="$this->isSuperAdmin() && !$company_id">
                        <option value="">Seleccione destinatario</option>
                        @foreach ($this->customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->type ? ' - ' . $customer->type : '' }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="customer_id" />
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

                <flux:field class="md:col-span-2">
                    <flux:label>Dirección de Entrega</flux:label>
                    <flux:textarea wire:model="delivery_address" rows="2" />
                    <flux:error name="delivery_address" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" rows="3" />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between mb-6">
                <flux:heading size="lg" badge="Requerido">Productos</flux:heading>
                <flux:button type="button" variant="primary" size="sm" icon="plus" wire:click="addDetail">
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
                                    <option value="">Seleccione producto</option>
                                    @foreach ($this->products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </flux:select>
                                @if (isset($stockInfo[$index]) && $warehouse_id)
                                    <div class="mt-1 text-sm">
                                        @if ($stockInfo[$index]['available'] > 0)
                                            <span class="text-green-600 dark:text-green-400">
                                                Stock disponible: {{ number_format($stockInfo[$index]['available'], 2) }} {{ $stockInfo[$index]['unit'] }}
                                            </span>
                                            @if ($stockInfo[$index]['reserved'] > 0)
                                                <span class="text-amber-600 dark:text-amber-400 ml-2">
                                                    (Reservado: {{ number_format($stockInfo[$index]['reserved'], 2) }} {{ $stockInfo[$index]['unit'] }})
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-red-600 dark:text-red-400">
                                                Sin stock disponible
                                            </span>
                                        @endif
                                    </div>
                                @elseif (!$warehouse_id && !empty($detail['product_id']))
                                    <flux:text size="sm" class="mt-1 text-amber-600 dark:text-amber-400">
                                        Seleccione una bodega para ver el stock
                                    </flux:text>
                                @endif
                                <flux:error name="details.{{ $index }}.product_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Requerido">Cantidad</flux:label>
                                <flux:input type="number" step="0.01" wire:model="details.{{ $index }}.quantity" :max="isset($stockInfo[$index]) ? $stockInfo[$index]['available'] : null" />
                                <flux:error name="details.{{ $index }}.quantity" />
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Requerido">Unidad</flux:label>
                                <flux:select wire:model="details.{{ $index }}.unit_of_measure_id" :disabled="$this->isSuperAdmin() && !$company_id">
                                    <option value="">Unidad</option>
                                    @foreach ($this->units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="details.{{ $index }}.unit_of_measure_id" />
                            </flux:field>

                            <flux:field class="md:col-span-2">
                                <flux:label>Precio Unitario</flux:label>
                                <flux:input type="number" step="0.01" wire:model="details.{{ $index }}.unit_price" placeholder="0.00" />
                                <flux:error name="details.{{ $index }}.unit_price" />
                            </flux:field>

                            <flux:field class="md:col-span-2">
                                <flux:label>Notas del Producto</flux:label>
                                <flux:textarea wire:model="details.{{ $index }}.notes" rows="2" />
                                <flux:error name="details.{{ $index }}.notes" />
                            </flux:field>
                        </div>
                    </div>
                @endforeach

                <flux:error name="details" />
            </div>
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
