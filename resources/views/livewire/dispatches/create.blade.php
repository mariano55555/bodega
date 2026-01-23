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
    public $notes = '';

    public $status = 'borrador';

    public array $details = [];

    public array $stockInfo = [];

    public function mount(): void
    {
        // Set default company to user's company if not super admin
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }

        // Initialize with 1 empty row (user can add more as needed)
        $this->addDetail();
    }

    public function updatedCompanyId(): void
    {
        // Reset selections when company changes
        $this->warehouse_id = '';
        $this->area_id = '';
        $this->employee_id = '';
        $this->stockInfo = [];
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
    }

    public function updatedEmployeeId(): void
    {
        if ($this->employee_id) {
            $employee = $this->employees->firstWhere('id', (int) $this->employee_id);
            if ($employee) {
                $this->recipient_name = $employee->name ?? '';
                $this->recipient_phone = $employee->phone ?? $employee->mobile ?? '';
                $this->recipient_email = $employee->email ?? '';
            }
        } else {
            // Clear recipient fields if no employee selected
            $this->recipient_name = '';
            $this->recipient_phone = '';
            $this->recipient_email = '';
        }
    }

    public function updatedWarehouseId(): void
    {
        // Clear stock info when warehouse changes - will be lazy loaded when products are selected
        $this->stockInfo = [];
        // Clear cached products so they reload for the new warehouse
        unset($this->products);
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
        unset($this->stockInfo[$index]);
    }

    public function addMoreRows(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->addDetail();
        }

        \Flux::toast('5 filas agregadas', variant: 'success');
    }

    public function updatedDetails($value, $key): void
    {
        // Check if product_id was updated
        if (str_ends_with($key, 'product_id') && $value) {
            // Extract the index from the key (e.g., "0.product_id" -> 0)
            $index = (int) explode('.', $key)[0];

            // Single optimized query to get unit_of_measure_id AND stock info
            $this->updateProductAndStockInfo($index, $value);
        }
    }

    public function updateProductAndStockInfo(int $index, $productId): void
    {
        if (! $this->warehouse_id || ! $productId) {
            $this->stockInfo[$index] = null;

            return;
        }

        // Get product from cached list - already has stock info (no DB query needed)
        $product = $this->products->firstWhere('id', (int) $productId);

        if ($product) {
            $this->details[$index]['unit_of_measure_id'] = $product->unit_of_measure_id;

            // Always set unit price from product cost when product changes
            $this->details[$index]['unit_price'] = $product->cost ?? 0;

            $this->stockInfo[$index] = [
                'quantity' => $product->stock_quantity ?? 0,
                'reserved' => $product->stock_reserved ?? 0,
                'available' => $product->stock_available ?? 0,
                'unit' => $product->unit_abbreviation ?? '',
            ];
        } else {
            $this->stockInfo[$index] = null;
        }
    }

    public function refreshAllStockInfo(): void
    {
        $this->stockInfo = [];
        foreach ($this->details as $index => $detail) {
            if (! empty($detail['product_id'])) {
                $this->updateProductAndStockInfo($index, $detail['product_id']);
            }
        }
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

                <!-- Unidad Solicitante (Área) -->
                <flux:field>
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
                    <flux:label badge="Requerido">Persona Solicitante</flux:label>
                    <div wire:loading wire:target="area_id" class="flex items-center gap-2 py-2">
                        <flux:icon name="arrow-path" class="w-4 h-4 animate-spin text-blue-500" />
                        <flux:text size="sm" class="text-blue-600 dark:text-blue-400">Cargando personas...</flux:text>
                    </div>
                    <div wire:loading.remove wire:target="area_id">
                        <flux:select wire:model.live="employee_id" :disabled="!$area_id">
                            <option value="">Seleccione persona</option>
                            @foreach ($this->employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}{{ $employee->position ? ' (' . $employee->position . ')' : '' }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:description>
                        @if(!$area_id)
                            Primero seleccione una unidad solicitante
                        @else
                            Persona del área seleccionada
                        @endif
                    </flux:description>
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

        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg" badge="Requerido">Productos del Despacho</flux:heading>
                <div class="flex gap-2">
                    <flux:button type="button" variant="outline" size="sm" icon="plus" wire:click="addDetail">
                        +1 fila
                    </flux:button>
                    <flux:button type="button" variant="primary" size="sm" icon="plus" wire:click="addMoreRows">
                        +5 filas
                    </flux:button>
                </div>
            </div>

            <!-- Loading indicator when warehouse changes -->
            <div wire:loading wire:target="warehouse_id" class="flex items-center justify-center py-8">
                <flux:icon name="arrow-path" class="w-6 h-6 animate-spin text-blue-500" />
                <flux:text class="ml-2 text-blue-600 dark:text-blue-400">Cargando productos...</flux:text>
            </div>

            <div wire:loading.remove wire:target="warehouse_id" class="overflow-x-auto">
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
                        <tbody x-data="{ expanded: false }" wire:key="detail-group-{{ $index }}">
                        <flux:table.row class="{{ $detail['product_id'] ? '' : 'opacity-60' }}">
                            <flux:table.cell class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $index + 1 }}
                            </flux:table.cell>

                            <!-- Product Select with Unit and Stock Badge -->
                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <flux:select
                                        variant="listbox"
                                        searchable
                                        wire:model.live="details.{{ $index }}.product_id"
                                        :disabled="!$company_id || !$warehouse_id"
                                        placeholder="{{ !$company_id ? 'Seleccione empresa primero' : (!$warehouse_id ? 'Seleccione bodega primero' : 'Seleccionar producto...') }}"
                                        class="w-full"
                                    >
                                        @foreach($this->products as $product)
                                            <flux:select.option value="{{ $product->id }}">
                                                {{ $product->name }}{{ $product->sku ? ' - ' . $product->sku : '' }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <!-- Unit and Stock Badge -->
                                    @if($detail['product_id'] && isset($stockInfo[$index]) && $warehouse_id)
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @php
                                                $stock = $stockInfo[$index];
                                                $available = $stock['available'] ?? 0;
                                                $unit = $stock['unit'] ?? '';
                                                $badgeColor = $available > 10 ? 'green' : ($available > 0 ? 'amber' : 'red');
                                            @endphp
                                            @if($unit)
                                                <flux:badge size="sm" color="zinc">
                                                    Unidad: {{ $unit }}
                                                </flux:badge>
                                            @endif
                                            <flux:badge size="sm" color="{{ $badgeColor }}">
                                                Stock: {{ number_format($available, 2) }} {{ $unit }}
                                            </flux:badge>
                                            @if(($stock['reserved'] ?? 0) > 0)
                                                <flux:badge size="sm" color="amber">
                                                    {{ number_format($stock['reserved'], 2) }} reservado
                                                </flux:badge>
                                            @endif
                                        </div>
                                    @elseif(!$warehouse_id && !empty($detail['product_id']))
                                        <flux:text size="sm" class="text-amber-600 dark:text-amber-400">
                                            Seleccione bodega primero
                                        </flux:text>
                                    @endif
                                    <flux:error name="details.{{ $index }}.product_id" />
                                </div>
                            </flux:table.cell>

                            <!-- Quantity -->
                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <flux:input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        wire:model.blur="details.{{ $index }}.quantity"
                                        class="w-full text-center"
                                    />
                                    <flux:error name="details.{{ $index }}.quantity" />
                                </div>
                                <!-- Hidden unit_of_measure_id (auto-set from product) -->
                                <input type="hidden" wire:model="details.{{ $index }}.unit_of_measure_id" />
                            </flux:table.cell>

                            <!-- Unit Price -->
                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <flux:input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model.blur="details.{{ $index }}.unit_price"
                                        placeholder="0.00"
                                        class="w-full text-right"
                                    />
                                    <flux:error name="details.{{ $index }}.unit_price" />
                                </div>
                            </flux:table.cell>

                            <!-- Total (Calculated) -->
                            <flux:table.cell class="text-right font-semibold">
                                ${{ number_format(($detail['quantity'] ?? 0) * ($detail['unit_price'] ?? 0), 2) }}
                            </flux:table.cell>

                            <!-- Actions -->
                            <flux:table.cell class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    @if($detail['product_id'])
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

                                        <!-- Remove Button -->
                                        <flux:button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            wire:click="removeDetail({{ $index }})"
                                        />
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>

                        <!-- Expandable Notes Row (Alpine.js - client-side only) -->
                        <flux:table.row x-show="expanded" x-collapse class="bg-zinc-50 dark:bg-zinc-800">
                            <flux:table.cell colspan="6" class="py-3">
                                <div class="px-4">
                                    <flux:label>Notas (opcional)</flux:label>
                                    <flux:textarea
                                        wire:model="details.{{ $index }}.notes"
                                        placeholder="Información adicional sobre este producto..."
                                        rows="2"
                                    />
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
                <flux:button type="button" variant="outline" size="sm" icon="plus" wire:click="addDetail">
                    +1 fila
                </flux:button>
                <flux:button type="button" variant="primary" size="sm" icon="plus" wire:click="addMoreRows">
                    +5 filas
                </flux:button>
            </div>

            <!-- Grand Total -->
            <div class="mt-4 flex justify-end">
                <div class="bg-zinc-100 dark:bg-zinc-800 px-6 py-3 rounded-lg">
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">Total General</flux:text>
                    <flux:heading size="lg">
                        ${{ number_format(collect($details)->sum(fn($d) => ($d['quantity'] ?? 0) * ($d['unit_price'] ?? 0)), 2) }}
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
