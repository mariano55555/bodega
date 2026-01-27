<?php

use App\Models\Employee;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Inventory;
use App\Models\Product;
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

    public $notes = '';

    public $status = 'borrador';

    public array $details = [];

    public function mount(): void
    {
        // Set default company to user's company if not super admin
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }

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
    }

    public function updatedAreaId(): void
    {
        // Reset employee when area changes
        $this->employee_id = '';
        // Clear recipient fields when area changes
        $this->recipient_name = '';
        $this->recipient_phone = '';
        $this->recipient_email = '';
        // Clear cached employees so they reload for the new area
        unset($this->employees);

        // Skip full component re-render - only update the employee select
        $this->skipRender();

        // Dispatch browser event to update employees dropdown via Alpine
        $this->dispatch('employees-updated', employees: $this->employees->toArray());
    }

    public function updatedEmployeeId(): void
    {
        // This is now handled via Alpine.js in the view for better performance
        // Keeping this method for when employee_id is set directly via Livewire
    }

    public function updatedWarehouseId(): void
    {
        // Clear cached products so they reload for the new warehouse
        unset($this->products);
        // Dispatch event to update Alpine's productsData
        $this->dispatch('products-updated', productsData: $this->productsData);
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
                'area_id' => $this->area_id,
                'employee_id' => $this->employee_id ?: null,
                'dispatch_type' => $this->dispatch_type,
                'physical_document_number' => $this->physical_document_number,
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
        // Requiere tanto company_id como area_id
        if (! $this->company_id || ! $this->area_id) {
            return collect([]);
        }

        return Employee::where('company_id', $this->company_id)
            ->where('area_id', $this->area_id)
            ->where('is_active', true)
            ->select('id', 'name', 'position', 'phone', 'mobile', 'email')
            ->orderBy('name')
            ->get();
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

                <!-- Persona Solicitante y Receptor (Alpine-managed for performance) -->
                <div
                    class="contents"
                    x-data="{
                        employees: @js($this->employees),
                        loading: false,
                        selectedEmployee: '{{ $employee_id }}',
                        recipientName: '{{ addslashes($recipient_name) }}',
                        recipientPhone: '{{ addslashes($recipient_phone) }}',
                        recipientEmail: '{{ addslashes($recipient_email) }}'
                    }"
                    x-on:employees-updated.window="employees = $event.detail.employees; loading = false; selectedEmployee = '';"
                    x-init="
                        $watch('selectedEmployee', value => {
                            if (value) {
                                const emp = employees.find(e => e.id == value);
                                if (emp) {
                                    recipientName = emp.name || '';
                                    recipientPhone = emp.phone || emp.mobile || '';
                                    recipientEmail = emp.email || '';
                                    $wire.set('employee_id', value, false);
                                    $wire.set('recipient_name', recipientName, false);
                                    $wire.set('recipient_phone', recipientPhone, false);
                                    $wire.set('recipient_email', recipientEmail, false);
                                }
                            } else {
                                recipientName = '';
                                recipientPhone = '';
                                recipientEmail = '';
                                $wire.set('employee_id', '', false);
                                $wire.set('recipient_name', '', false);
                                $wire.set('recipient_phone', '', false);
                                $wire.set('recipient_email', '', false);
                            }
                        });
                    "
                >
                    <flux:field wire:key="employee-field">
                        <flux:label badge="Requerido">Persona Solicitante</flux:label>
                        <div x-show="loading" class="flex items-center gap-2 py-2">
                            <flux:icon name="arrow-path" class="w-4 h-4 animate-spin text-blue-500" />
                            <flux:text size="sm" class="text-blue-600 dark:text-blue-400">Cargando personas...</flux:text>
                        </div>
                        <div x-show="!loading">
                            <select
                                x-model="selectedEmployee"
                                :disabled="employees.length === 0"
                                class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:focus:border-zinc-500 dark:focus:ring-zinc-700 disabled:opacity-50"
                            >
                                <option value="">Seleccione persona</option>
                                <template x-for="emp in employees" :key="emp.id">
                                    <option :value="emp.id" x-text="emp.name + (emp.position ? ' (' + emp.position + ')' : '')"></option>
                                </template>
                            </select>
                        </div>
                        <flux:description>
                            <span x-show="employees.length === 0">Primero seleccione una unidad solicitante</span>
                            <span x-show="employees.length > 0">Persona del área seleccionada</span>
                        </flux:description>
                        <flux:error name="employee_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nombre del Receptor</flux:label>
                        <input
                            type="text"
                            x-model="recipientName"
                            @change="$wire.set('recipient_name', recipientName, false)"
                            class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                        />
                        <flux:error name="recipient_name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Teléfono del Receptor</flux:label>
                        <input
                            type="text"
                            x-model="recipientPhone"
                            @change="$wire.set('recipient_phone', recipientPhone, false)"
                            class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                        />
                        <flux:error name="recipient_phone" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Email del Receptor</flux:label>
                        <input
                            type="email"
                            x-model="recipientEmail"
                            @change="$wire.set('recipient_email', recipientEmail, false)"
                            class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                        />
                        <flux:error name="recipient_email" />
                    </flux:field>
                </div>

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
