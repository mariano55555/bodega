<?php

use App\Models\Customer;
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

    public $customer_id = '';

    public $dispatch_type = 'interno';

    public $recipient_name = '';

    public $recipient_email = '';

    public $recipient_phone = '';

    public $delivery_address = '';

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
        $this->customer_id = $dispatch->customer_id;
        $this->dispatch_type = $dispatch->dispatch_type;
        $this->recipient_name = $dispatch->recipient_name;
        $this->recipient_email = $dispatch->recipient_email;
        $this->recipient_phone = $dispatch->recipient_phone;
        $this->delivery_address = $dispatch->delivery_address;
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

    public function save(): void
    {
        $this->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'dispatch_type' => 'required|in:venta,interno,externo,donacion',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:0.0001',
            'details.*.unit_of_measure_id' => 'required|exists:units_of_measure,id',
        ]);

        \DB::transaction(function () {
            $this->dispatch->update([
                'warehouse_id' => $this->warehouse_id,
                'customer_id' => $this->customer_id ?: null,
                'dispatch_type' => $this->dispatch_type,
                'recipient_name' => $this->recipient_name,
                'recipient_email' => $this->recipient_email,
                'recipient_phone' => $this->recipient_phone,
                'delivery_address' => $this->delivery_address,
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

    public function with(): array
    {
        // Get the company_id from the dispatch being edited
        $companyId = $this->dispatch->company_id;

        return [
            'warehouses' => Warehouse::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'customers' => Customer::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
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
                    <flux:label>Cliente</flux:label>
                    <flux:select wire:model="customer_id">
                        <option value="">Sin cliente</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </flux:select>
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

                <flux:field class="md:col-span-2">
                    <flux:label>Dirección de Entrega</flux:label>
                    <flux:textarea wire:model="delivery_address" rows="2" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" rows="3" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Productos</flux:heading>
                <flux:button type="button" size="sm" icon="plus" wire:click="addDetail">
                    Agregar Producto
                </flux:button>
            </div>

            <div class="mt-4 space-y-4">
                @foreach ($details as $index => $detail)
                    <div class="p-4 border rounded-lg dark:border-gray-700">
                        <div class="flex items-start justify-between mb-4">
                            <flux:heading size="sm">Producto #{{ $index + 1 }}</flux:heading>
                            @if (count($details) > 1)
                                <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeDetail({{ $index }})">
                                    Eliminar
                                </flux:button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <flux:field class="md:col-span-2">
                                <flux:label badge="Requerido">Producto</flux:label>
                                <flux:select wire:model="details.{{ $index }}.product_id" required>
                                    <option value="">Seleccione producto</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="details.{{ $index }}.product_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Requerido">Cantidad</flux:label>
                                <flux:input type="number" step="0.01" wire:model="details.{{ $index }}.quantity" required />
                                <flux:error name="details.{{ $index }}.quantity" />
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Requerido">Unidad</flux:label>
                                <flux:select wire:model="details.{{ $index }}.unit_of_measure_id" required>
                                    <option value="">Unidad</option>
                                    @foreach ($units as $unit)
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

                @error('details') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
            </div>
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
