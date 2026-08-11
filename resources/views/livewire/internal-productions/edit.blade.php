<?php

use App\Models\Area;
use App\Models\Employee;
use App\Models\InternalProduction;
use App\Models\InternalProductionDetail;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public InternalProduction $internalProduction;

    public $company_id = '';

    public $warehouse_id = '';

    public $area_id = '';

    public $employee_id = '';

    public $physical_document_number = '';

    public bool $documentNumberExists = false;

    public $document_date = '';

    public $notes = '';

    public $status = 'borrador';

    public array $details = [];

    public function mount(InternalProduction $internalProduction): void
    {
        $this->internalProduction = $internalProduction->load(['details']);

        if (! $internalProduction->canBeEdited()) {
            session()->flash('error', 'Esta producción no puede ser editada.');
            $this->redirect(route('internal-productions.show', $internalProduction), navigate: true);

            return;
        }

        $this->company_id = $internalProduction->company_id;
        $this->warehouse_id = $internalProduction->warehouse_id;
        $this->area_id = $internalProduction->area_id;
        $this->employee_id = $internalProduction->employee_id ?? '';
        $this->physical_document_number = $internalProduction->physical_document_number;
        $this->document_date = $internalProduction->document_date?->format('Y-m-d') ?? '';
        $this->notes = $internalProduction->notes ?? '';
        $this->status = $internalProduction->status;

        $this->details = $internalProduction->details->map(fn ($detail) => [
            'id' => $detail->id,
            'product_id' => $detail->product_id,
            'description' => $detail->description ?? '',
            'quantity' => (float) $detail->quantity,
            'unit_of_measure_id' => $detail->unit_of_measure_id,
            'unit_price' => (float) $detail->unit_price,
            'notes' => $detail->notes ?? '',
        ])->toArray();

        if (empty($this->details)) {
            $this->details = [$this->getEmptyDetail()];
        }
    }

    private function getEmptyDetail(): array
    {
        return [
            'id' => null,
            'product_id' => '',
            'description' => '',
            'quantity' => 1,
            'unit_of_measure_id' => '',
            'unit_price' => 0,
            'notes' => '',
        ];
    }

    public function updatedAreaId(): void
    {
        // Area change no longer affects employees
    }

    public function updatedWarehouseId(): void
    {
        // Warehouse change - could add logic here if needed
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function addDetail(): void
    {
        $this->details[] = $this->getEmptyDetail();
    }

    public function removeDetail(int $index): void
    {
        unset($this->details[$index]);
        $this->details = array_values($this->details);
    }

    public function addMoreRows(): void
    {
        $newRows = array_fill(0, 5, $this->getEmptyDetail());
        $this->details = array_merge($this->details, $newRows);
        \Flux::toast('5 filas agregadas', variant: 'success');
    }

    public function updatedPhysicalDocumentNumber(): void
    {
        $this->physical_document_number = preg_replace('/\D+/', '', (string) $this->physical_document_number);

        $this->documentNumberExists = false;

        if (empty($this->physical_document_number)) {
            return;
        }

        $this->documentNumberExists = DB::table('internal_productions')
            ->where('physical_document_number', $this->physical_document_number)
            ->where('id', '!=', $this->internalProduction->id)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function save(): void
    {
        $filledDetails = array_filter($this->details, fn ($detail) => ! empty($detail['product_id']));

        if (empty($filledDetails)) {
            \Flux::toast('Debe agregar al menos un producto a la producción.', variant: 'danger');

            return;
        }

        $this->details = array_values($filledDetails);

        $rules = [
            'warehouse_id' => 'required|exists:warehouses,id',
            'area_id' => 'required|exists:areas,id',
            'physical_document_number' => 'required|string|max:100|regex:/^[0-9]+$/|unique:internal_productions,physical_document_number,'.$this->internalProduction->id,
            'document_date' => ['required', 'date', new \App\Rules\DocumentDateNotTooOld],
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:0.00001',
            'details.*.unit_of_measure_id' => 'required|exists:units_of_measure,id',
            'details.*.unit_price' => 'required|numeric|min:0',
        ];

        $customAttributes = [
            'warehouse_id' => 'bodega destino',
            'area_id' => 'unidad de origen',
            'physical_document_number' => 'número de documento físico',
            'document_date' => 'fecha del documento',
            'details.*.product_id' => 'producto',
            'details.*.quantity' => 'cantidad',
            'details.*.unit_of_measure_id' => 'unidad de medida',
            'details.*.unit_price' => 'precio unitario',
        ];

        $this->validate($rules, [
            'physical_document_number.regex' => 'El número de documento físico solo puede contener números.',
        ], $customAttributes);

        \DB::transaction(function () {
            $this->internalProduction->update([
                'warehouse_id' => $this->warehouse_id,
                'area_id' => $this->area_id,
                'employee_id' => $this->employee_id ?: null,
                'physical_document_number' => $this->physical_document_number,
                'document_date' => $this->document_date ?: null,
                'notes' => $this->notes,
            ]);

            $existingDetailIds = collect($this->details)->pluck('id')->filter()->toArray();
            $this->internalProduction->details()->whereNotIn('id', $existingDetailIds)->delete();

            foreach ($this->details as $detail) {
                if (! empty($detail['id'])) {
                    $existingDetail = InternalProductionDetail::find($detail['id']);
                    $existingDetail->fill([
                        'product_id' => $detail['product_id'],
                        'description' => $detail['description'] ?? null,
                        'quantity' => $detail['quantity'],
                        'unit_of_measure_id' => $detail['unit_of_measure_id'],
                        'unit_price' => $detail['unit_price'] ?? 0,
                        'notes' => $detail['notes'] ?? null,
                    ]);
                    $existingDetail->save();
                } else {
                    InternalProductionDetail::create([
                        'internal_production_id' => $this->internalProduction->id,
                        'product_id' => $detail['product_id'],
                        'description' => $detail['description'] ?? null,
                        'quantity' => $detail['quantity'],
                        'unit_of_measure_id' => $detail['unit_of_measure_id'],
                        'unit_price' => $detail['unit_price'] ?? 0,
                        'notes' => $detail['notes'] ?? null,
                    ]);
                }
            }

            $this->internalProduction->refresh();
            $this->internalProduction->calculateTotals();

            session()->flash('success', 'Producción actualizada exitosamente.');
            $this->redirect(route('internal-productions.show', $this->internalProduction), navigate: true);
        });
    }

    #[Computed]
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

    #[Computed]
    public function areas()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return Area::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function employees()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return Employee::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->select('id', 'name', 'position')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return Product::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->with('unitOfMeasure')
            ->select('id', 'name', 'sku', 'unit_of_measure_id', 'cost')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
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

    #[Computed]
    public function productsData(): array
    {
        return $this->products->keyBy('id')->map(fn ($p) => [
            'cost' => (float) ($p->cost ?? 0),
            'unit' => $p->unitOfMeasure?->abbreviation ?? '',
            'unit_id' => $p->unit_of_measure_id,
        ])->toArray();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Editar Producción Interna</flux:heading>
            <flux:text class="mt-1">{{ $internalProduction->production_number }}</flux:text>
        </div>

        <flux:button variant="ghost" href="{{ route('internal-productions.show', $internalProduction) }}" wire:navigate>
            Cancelar
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-8">
        <flux:card>
            <flux:heading size="lg" class="mb-6">Información de la Producción</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label badge="Requerido">Número de Documento Físico</flux:label>
                    <flux:input wire:model.blur="physical_document_number" placeholder="Ingrese el número de documento" inputmode="numeric" maxlength="100" />
                    <flux:error name="physical_document_number" />
                    @if ($documentNumberExists)
                        <flux:text size="sm" class="text-red-600">Este número de documento ya existe.</flux:text>
                    @endif
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Fecha del Documento</flux:label>
                    <flux:input type="date" wire:model="document_date" />
                    <flux:error name="document_date" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Unidad de Origen</flux:label>
                    <flux:select wire:model.live="area_id">
                        <option value="">Seleccione unidad de origen</option>
                        @foreach ($this->areas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:description>El área o departamento que produce los productos</flux:description>
                    <flux:error name="area_id" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Bodega Destino</flux:label>
                    <flux:select wire:model.live="warehouse_id">
                        <option value="">Seleccione bodega destino</option>
                        @foreach ($this->warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:description>La bodega donde se recibirán los productos</flux:description>
                    <flux:error name="warehouse_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Persona que Entrega</flux:label>
                    <flux:select wire:model="employee_id" variant="listbox" searchable placeholder="Buscar persona...">
                        @foreach ($this->employees as $employee)
                            <flux:select.option value="{{ $employee->id }}">{{ $employee->name }}{{ $employee->position ? ' - ' . $employee->position : '' }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="employee_id" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" rows="3" placeholder="Observaciones adicionales..." />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg" badge="Requerido">Productos de la Producción</flux:heading>
                <div class="flex gap-2">
                    <flux:button type="button" variant="outline" size="sm" icon="plus" wire:click="addDetail">
                        +1 fila
                    </flux:button>
                    <flux:button type="button" variant="primary" size="sm" icon="plus" wire:click="addMoreRows">
                        +5 filas
                    </flux:button>
                </div>
            </div>

            <div class="overflow-x-auto"
                 x-data
                 x-init="
                    $store.productionProducts = @js($this->productsData);
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
                        <flux:table.column class="min-w-[250px]">Producto</flux:table.column>
                        <flux:table.column class="min-w-[150px]">Descripción</flux:table.column>
                        <flux:table.column class="w-32">Unidad</flux:table.column>
                        <flux:table.column class="w-28 text-center">Cantidad</flux:table.column>
                        <flux:table.column class="w-32 text-right">Precio Unit.</flux:table.column>
                        <flux:table.column class="w-32 text-right">Total</flux:table.column>
                        <flux:table.column class="w-16 text-center">Acción</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($details as $index => $detail)
                        <flux:table.row wire:key="detail-{{ $index }}"
                            x-data="{
                                productId: '{{ $detail['product_id'] }}',
                                quantity: {{ (float) ($detail['quantity'] ?? 1) }},
                                unitPrice: {{ (float) ($detail['unit_price'] ?? 0) }},
                                get total() { return this.quantity * this.unitPrice; },
                                get productInfo() {
                                    return this.productId ? $store.productionProducts[this.productId] : null;
                                },
                                selectProduct(id) {
                                    this.productId = id;
                                    $wire.set('details.{{ $index }}.product_id', id, false);
                                    if (this.productInfo) {
                                        this.unitPrice = this.productInfo.cost || 0;
                                        $wire.set('details.{{ $index }}.unit_price', this.unitPrice, false);
                                        $wire.set('details.{{ $index }}.unit_of_measure_id', this.productInfo.unit_id, false);
                                    }
                                    this.emitTotal();
                                },
                                emitTotal() {
                                    $dispatch('row-total-updated', { index: {{ $index }}, total: this.total });
                                }
                            }"
                            x-init="emitTotal()"
                            x-bind:class="productId ? '' : 'opacity-60'"
                        >
                            <flux:table.cell class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $index + 1 }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:select
                                    variant="listbox"
                                    searchable
                                    x-model="productId"
                                    x-on:change="selectProduct($event.target.value)"
                                    placeholder="Seleccionar producto..."
                                >
                                    @foreach($this->products as $product)
                                        <flux:select.option value="{{ $product->id }}">
                                            {{ $product->name }}{{ $product->sku ? ' - ' . $product->sku : '' }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="details.{{ $index }}.product_id" />
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:input
                                    wire:model="details.{{ $index }}.description"
                                    placeholder="Descripción..."
                                    size="sm"
                                />
                            </flux:table.cell>

                            <flux:table.cell>
                                <template x-if="productInfo">
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400" x-text="productInfo.unit"></span>
                                </template>
                                <flux:error name="details.{{ $index }}.unit_of_measure_id" />
                            </flux:table.cell>

                            <flux:table.cell>
                                <input
                                    type="number"
                                    step="0.00001"
                                    min="0.00001"
                                    x-model.number="quantity"
                                    @input="emitTotal()"
                                    @change="$wire.set('details.{{ $index }}.quantity', quantity, false)"
                                    class="block w-full text-center rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                />
                                <flux:error name="details.{{ $index }}.quantity" />
                            </flux:table.cell>

                            <flux:table.cell>
                                <input
                                    type="number"
                                    step="0.00001"
                                    min="0"
                                    x-model.number="unitPrice"
                                    @input="emitTotal()"
                                    @change="$wire.set('details.{{ $index }}.unit_price', unitPrice, false)"
                                    placeholder="0.00000"
                                    class="block w-full text-right rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                />
                                <flux:error name="details.{{ $index }}.unit_price" />
                            </flux:table.cell>

                            <flux:table.cell class="text-right font-semibold">
                                $<span x-text="total.toFixed(5)"></span>
                            </flux:table.cell>

                            <flux:table.cell class="text-center">
                                @if(count($details) > 1)
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        wire:click="removeDetail({{ $index }})"
                                    />
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <flux:button type="button" variant="outline" size="sm" icon="plus" wire:click="addDetail">
                    +1 fila
                </flux:button>
                <flux:button type="button" variant="primary" size="sm" icon="plus" wire:click="addMoreRows">
                    +5 filas
                </flux:button>
            </div>

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
            <flux:button variant="ghost" href="{{ route('internal-productions.show', $internalProduction) }}" wire:navigate type="button">
                Cancelar
            </flux:button>

            <flux:button type="submit" variant="primary" icon="check" :disabled="$documentNumberExists">
                Guardar Cambios
            </flux:button>
        </div>
    </form>
</div>
