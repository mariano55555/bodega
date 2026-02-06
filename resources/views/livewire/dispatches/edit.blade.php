<?php

use App\Models\Employee;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Dispatch $dispatch;

    public $warehouse_id = '';

    public $area_id;

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

    public function mount(Dispatch $dispatch): void
    {
        // Only allow editing drafts and pending
        if (! $dispatch->canBeEdited()) {
            session()->flash('error', 'Solo se pueden editar despachos en estado borrador o pendiente.');
            $this->redirect(route('dispatches.show', $dispatch), navigate: true);

            return;
        }

        $this->dispatch = $dispatch;
        $this->warehouse_id = $dispatch->warehouse_id;
        $this->area_id = $dispatch->area_id;
        $this->employee_id = $dispatch->employee_id;
        $this->dispatch_type = $dispatch->dispatch_type;
        $this->recipient_name = $dispatch->recipient_name;
        $this->recipient_email = $dispatch->recipient_email;
        $this->recipient_phone = $dispatch->recipient_phone;
        // $this->delivery_address = $dispatch->delivery_address; // Comentado por petición del cliente: quitar dirección de entrega
        $this->physical_document_number = $dispatch->physical_document_number;
        $this->document_date = $dispatch->document_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->notes = $dispatch->notes;
        $this->status = $dispatch->status;

        // Load existing details - cast IDs to strings for Livewire select binding
        foreach ($dispatch->details as $detail) {
            $this->details[] = [
                'id' => $detail->id,
                'product_id' => (string) $detail->product_id,
                'quantity' => $detail->quantity,
                'unit_of_measure_id' => (string) $detail->unit_of_measure_id,
                'unit_price' => $detail->unit_price,
                'notes' => $detail->notes,
            ];
        }

        if (empty($this->details)) {
            $this->addDetail();
        }
    }

    public function updatedAreaId(): void
    {
        // Area change no longer affects employees since we show all employees
    }

    public function addDetail(): void
    {
        $this->details[] = [
            'id' => null,
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
            'id' => null,
            'product_id' => '',
            'quantity' => 1,
            'unit_of_measure_id' => '',
            'unit_price' => 0,
            'notes' => '',
        ]);

        $this->details = array_merge($this->details, $newRows);

        \Flux::toast('5 filas agregadas', variant: 'success');
    }

    #[\Livewire\Attributes\Computed]
    public function employees()
    {
        if (! $this->dispatch->company_id) {
            return collect([]);
        }

        return Employee::where('company_id', $this->dispatch->company_id)
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
        if (! $this->dispatch->company_id) {
            return collect([]);
        }

        return \App\Models\Area::where('company_id', $this->dispatch->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        // Filter out empty rows (rows without product_id)
        $filledDetails = array_filter($this->details, fn ($detail) => ! empty($detail['product_id']));

        if (empty($filledDetails)) {
            \Flux::toast('Debe agregar al menos un producto al despacho.', variant: 'danger');

            return;
        }

        // Re-index the array
        $this->details = array_values($filledDetails);

        $this->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'dispatch_type' => 'required|in:venta,interno,externo,donacion',
            'physical_document_number' => 'required|string|max:100|unique:dispatches,physical_document_number,'.$this->dispatch->id,
            'document_date' => 'required|date',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:0.0001',
            'details.*.unit_of_measure_id' => 'required|exists:units_of_measure,id',
        ]);

        \DB::transaction(function () {
            $this->dispatch->update([
                'warehouse_id' => $this->warehouse_id,
                'area_id' => $this->area_id,
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
            ]);

            // Get existing detail IDs
            $existingIds = collect($this->details)->pluck('id')->filter()->toArray();

            // Delete removed details
            $this->dispatch->details()->whereNotIn('id', $existingIds)->delete();

            // Update or create details
            foreach ($this->details as $detail) {
                if (isset($detail['id']) && $detail['id']) {
                    // Update existing
                    DispatchDetail::where('id', $detail['id'])->update([
                        'product_id' => $detail['product_id'],
                        'quantity' => $detail['quantity'],
                        'unit_of_measure_id' => $detail['unit_of_measure_id'],
                        'unit_price' => $detail['unit_price'] ?? 0,
                        'notes' => $detail['notes'] ?? null,
                    ]);
                } else {
                    // Create new
                    DispatchDetail::create([
                        'dispatch_id' => $this->dispatch->id,
                        'product_id' => $detail['product_id'],
                        'quantity' => $detail['quantity'],
                        'unit_of_measure_id' => $detail['unit_of_measure_id'],
                        'unit_price' => $detail['unit_price'] ?? 0,
                        'notes' => $detail['notes'] ?? null,
                    ]);
                }
            }

            $this->dispatch->calculateTotals();

            session()->flash('success', 'Despacho actualizado exitosamente.');
            $this->redirect(route('dispatches.show', $this->dispatch), navigate: true);
        });
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    /**
     * Get products as a keyed array for Alpine.js
     * This allows instant access to product data without server roundtrip
     */
    #[\Livewire\Attributes\Computed]
    public function productsData(): array
    {
        $companyId = $this->dispatch->company_id;

        return Product::with('unitOfMeasure')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->keyBy('id')
            ->map(fn ($p) => [
                'cost' => (float) ($p->cost ?? 0),
                'unit' => $p->unitOfMeasure?->abbreviation ?? '',
                'unit_id' => $p->unit_of_measure_id,
            ])->toArray();
    }

    public function with(): array
    {
        // Get the company_id from the dispatch being edited
        $companyId = $this->dispatch->company_id;

        return [
            'warehouses' => Warehouse::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'employees' => Employee::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::with('unitOfMeasure')->where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'units' => UnitOfMeasure::forCompany($companyId)->active()->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Editar Despacho</flux:heading>
            <flux:text class="mt-1">{{ $dispatch->dispatch_number }}</flux:text>
        </div>

        <flux:button variant="ghost" href="{{ route('dispatches.show', $dispatch) }}" wire:navigate>
            Volver al detalle
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:card>
            <flux:heading size="lg">Información del Despacho</flux:heading>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Bodega *</flux:label>
                    <flux:select wire:model="warehouse_id" required>
                        <option value="">Seleccione bodega</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('warehouse_id') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de Despacho *</flux:label>
                    <flux:select wire:model="dispatch_type" required>
                        <option value="venta">Venta</option>
                        <option value="interno">Interno</option>
                        <option value="externo">Externo</option>
                        <option value="donacion">Donación</option>
                    </flux:select>
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
                <flux:field>
                    <flux:label badge="Requerido">Unidad Solicitante</flux:label>
                    <flux:select wire:model.live="area_id">
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
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono del Receptor</flux:label>
                    <flux:input wire:model="recipient_phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Email del Receptor</flux:label>
                    <flux:input type="email" wire:model="recipient_email" />
                </flux:field>

                {{-- Comentado por petición del cliente: quitar dirección de entrega
                <flux:field class="md:col-span-2">
                    <flux:label>Dirección de Entrega</flux:label>
                    <flux:textarea wire:model="delivery_address" rows="2" />
                </flux:field>
                --}}

                <flux:field class="md:col-span-2">
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" rows="3" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card wire:key="products-card-{{ count($details) }}">
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

            <!-- Loading indicator when adding rows -->
            <div wire:loading wire:target="addDetail, addMoreRows" class="flex items-center justify-center py-4">
                <flux:icon name="arrow-path" class="w-5 h-5 animate-spin text-blue-500" />
                <flux:text class="ml-2 text-blue-600 dark:text-blue-400">Agregando filas...</flux:text>
            </div>

            <div wire:loading.remove wire:target="addDetail, addMoreRows" class="overflow-x-auto"
                 x-data
                 x-init="
                    $store.dispatchProducts = @js($this->productsData);
                    $store.rowTotals = {};
                    $store.grandTotal = 0;
                 "
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
                                    <template x-if="productInfo">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <template x-if="productInfo.unit">
                                                <span class="inline-flex items-center rounded-md bg-zinc-100 dark:bg-zinc-700 px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                                    Unidad: <span x-text="productInfo.unit" class="ml-1"></span>
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

        <div class="flex justify-end gap-2">
            <flux:button type="button" variant="ghost" href="{{ route('dispatches.show', $dispatch) }}" wire:navigate>
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary">
                Actualizar Despacho
            </flux:button>
        </div>
    </form>
</div>
