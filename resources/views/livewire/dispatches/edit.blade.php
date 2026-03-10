<?php

use App\Models\DispatchFuelDetail;
use App\Models\Employee;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
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

    public bool $documentNumberExists = false;

    public $document_date = '';

    public $notes = '';

    public $status = 'borrador';

    // Fuel dispatch fields
    public $vehicle_class = '';

    public $vehicle_brand = '';

    public $vehicle_model = '';

    public $vehicle_plate = '';

    public $odometer_reading = '';

    public $horometer_reading = '';

    public $place_to_visit = '';

    public $mission_description = '';

    public $kilometers_to_travel = '';

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

        // Load fuel detail if exists
        if ($dispatch->dispatch_type === 'combustible' && $dispatch->fuelDetail) {
            $fuel = $dispatch->fuelDetail;
            $this->vehicle_class = $fuel->vehicle_class ?? '';
            $this->vehicle_brand = $fuel->vehicle_brand ?? '';
            $this->vehicle_model = $fuel->vehicle_model ?? '';
            $this->vehicle_plate = $fuel->vehicle_plate ?? '';
            $this->odometer_reading = $fuel->odometer_reading ?? '';
            $this->horometer_reading = $fuel->horometer_reading ?? '';
            $this->place_to_visit = $fuel->place_to_visit ?? '';
            $this->mission_description = $fuel->mission_description ?? '';
            $this->kilometers_to_travel = $fuel->kilometers_to_travel ?? '';
        }

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

    public function updatedPhysicalDocumentNumber(): void
    {
        $this->documentNumberExists = false;

        if (empty($this->physical_document_number)) {
            return;
        }

        $this->documentNumberExists = DB::table('dispatches')
            ->where('physical_document_number', $this->physical_document_number)
            ->where('id', '!=', $this->dispatch->id)
            ->whereNull('deleted_at')
            ->exists();
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

        $rules = [
            'warehouse_id' => 'required|exists:warehouses,id',
            'dispatch_type' => 'required|in:venta,interno,externo,donacion,combustible',
            'physical_document_number' => 'required|string|max:100|unique:dispatches,physical_document_number,'.$this->dispatch->id,
            'document_date' => 'required|date',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:0.0001',
            'details.*.unit_of_measure_id' => 'required|exists:units_of_measure,id',
        ];

        // Add fuel-specific validation rules
        if ($this->dispatch_type === 'combustible') {
            $rules['vehicle_class'] = 'required|string|max:255';
            $rules['vehicle_brand'] = 'required|string|max:255';
            $rules['vehicle_model'] = 'nullable|string|max:255';
            $rules['vehicle_plate'] = 'required|string|max:100';
            $rules['odometer_reading'] = 'nullable|numeric|min:0';
            $rules['horometer_reading'] = 'nullable|numeric|min:0';
            $rules['place_to_visit'] = 'required|string|max:500';
            $rules['mission_description'] = 'required|string|max:1000';
            $rules['kilometers_to_travel'] = 'nullable|numeric|min:0';
        }

        $this->validate($rules);

        \DB::beginTransaction();
        try {
            $isDelivered = in_array($this->dispatch->status, ['despachado', 'entregado']);

            // Validate stock availability inside transaction with lock (aggregate check: same product in multiple rows)
            $productQuantities = [];
            foreach ($this->details as $index => $detail) {
                $pid = $detail['product_id'];
                if (! isset($productQuantities[$pid])) {
                    $productQuantities[$pid] = ['total' => 0, 'indices' => []];
                }
                $productQuantities[$pid]['total'] += (float) $detail['quantity'];
                $productQuantities[$pid]['indices'][] = $index;
            }

            $inventories = Inventory::where('warehouse_id', $this->warehouse_id)
                ->whereIn('product_id', array_keys($productQuantities))
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $hasStockError = false;
            foreach ($productQuantities as $pid => $data) {
                $available = $inventories->get($pid)?->available_quantity ?? 0;
                if ($data['total'] > $available) {
                    $hasStockError = true;
                    $productName = Product::find($pid)?->name ?? "Producto #{$pid}";
                    foreach ($data['indices'] as $idx) {
                        $this->addError(
                            "details.{$idx}.quantity",
                            "Stock insuficiente para '{$productName}'. Disponible: ".number_format($available, 5).", solicitado total: ".number_format($data['total'], 5)
                        );
                    }
                }
            }

            if ($hasStockError) {
                \DB::rollBack();
                $this->addError('stock', 'Uno o más productos exceden el stock disponible en la bodega.');

                return;
            }

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

            // No eliminar detalles existentes si el despacho ya fue despachado/entregado (tienen movimientos de inventario)
            if (! $isDelivered) {
                $this->dispatch->details()->whereNotIn('id', $existingIds)->delete();
            }

            // Update or create details
            $newDetails = [];
            foreach ($this->details as $detail) {
                if (isset($detail['id']) && $detail['id']) {
                    // Update existing (use Eloquent save to trigger saving event that recalculates subtotal/total)
                    $existingDetail = DispatchDetail::find($detail['id']);
                    if ($existingDetail) {
                        $existingDetail->fill([
                            'product_id' => $detail['product_id'],
                            'quantity' => $detail['quantity'],
                            'unit_of_measure_id' => $detail['unit_of_measure_id'],
                            'unit_price' => $detail['unit_price'] ?? 0,
                            'notes' => $detail['notes'] ?? null,
                        ]);
                        $existingDetail->save();
                    }
                } else {
                    // Create new
                    $newDetail = DispatchDetail::create([
                        'dispatch_id' => $this->dispatch->id,
                        'product_id' => $detail['product_id'],
                        'quantity' => $detail['quantity'],
                        'unit_of_measure_id' => $detail['unit_of_measure_id'],
                        'unit_price' => $detail['unit_price'] ?? 0,
                        'notes' => $detail['notes'] ?? null,
                    ]);
                    $newDetails[] = $newDetail;
                }
            }

            // TEMPORAL: Si el despacho ya fue despachado/entregado, procesar inventario para los nuevos items
            if ($isDelivered && ! empty($newDetails)) {
                $this->processInventoryForNewDetails($newDetails);
            }

            // Handle fuel detail
            if ($this->dispatch_type === 'combustible') {
                DispatchFuelDetail::updateOrCreate(
                    ['dispatch_id' => $this->dispatch->id],
                    [
                        'vehicle_class' => $this->vehicle_class,
                        'vehicle_brand' => $this->vehicle_brand,
                        'vehicle_model' => $this->vehicle_model ?: null,
                        'vehicle_plate' => $this->vehicle_plate,
                        'odometer_reading' => $this->odometer_reading ?: null,
                        'horometer_reading' => $this->horometer_reading ?: null,
                        'place_to_visit' => $this->place_to_visit,
                        'mission_description' => $this->mission_description,
                        'kilometers_to_travel' => $this->kilometers_to_travel ?: null,
                    ]
                );
            } elseif ($this->dispatch->fuelDetail) {
                // Remove fuel detail if type changed from combustible
                $this->dispatch->fuelDetail->delete();
            }

            $this->dispatch->load('details');
            $this->dispatch->calculateTotals();

            \DB::commit();

            session()->flash('success', 'Despacho actualizado exitosamente.');
            $this->redirect(route('dispatches.show', $this->dispatch), navigate: true);
        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Error al actualizar el despacho. Por favor intente nuevamente.');
            \Log::error('Error updating dispatch: '.$e->getMessage());
        }
    }

    /**
     * TEMPORAL: Procesa movimientos de inventario para nuevos detalles agregados a despachos ya entregados/despachados.
     *
     * @param  array<DispatchDetail>  $newDetails
     */
    private function processInventoryForNewDetails(array $newDetails): void
    {
        $dispatch = $this->dispatch;
        $userId = auth()->id();

        $movementReason = MovementReason::where('code', 'DISPATCH')->first()
            ?? MovementReason::where('movement_type', 'out')->first();

        if (! $movementReason) {
            throw new \Exception('Movement reason for dispatch not found');
        }

        $movementType = match ($dispatch->dispatch_type) {
            'venta' => 'sale',
            'interno' => 'transfer_out',
            'externo' => 'transfer_out',
            'donacion' => 'sale',
            'combustible' => 'transfer_out',
            default => 'sale',
        };

        foreach ($newDetails as $detail) {
            // Marcar como despachado y entregado
            $detail->quantity_dispatched = $detail->quantity;
            $detail->is_reserved = true;
            $detail->reserved_by = $userId;
            $detail->reserved_at = now();

            if ($dispatch->status === 'entregado') {
                $detail->quantity_delivered = $detail->quantity;
            }

            $detail->save();

            // Obtener balance actual del producto en la bodega
            $currentStock = InventoryMovement::where('warehouse_id', $dispatch->warehouse_id)
                ->where('product_id', $detail->product_id)
                ->whereNotNull('balance_quantity')
                ->orderBy('movement_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $previousBalance = $currentStock ? $currentStock->balance_quantity : 0;
            $newBalance = $previousBalance - $detail->quantity;

            // Crear movimiento de inventario
            InventoryMovement::create([
                'company_id' => $dispatch->company_id,
                'warehouse_id' => $dispatch->warehouse_id,
                'product_id' => $detail->product_id,
                'movement_reason_id' => $movementReason->id,
                'dispatch_id' => $dispatch->id,
                'movement_type' => $movementType,
                'movement_date' => $dispatch->dispatched_at ?? now(),
                'quantity' => $detail->quantity,
                'quantity_in' => 0,
                'quantity_out' => $detail->quantity,
                'balance_quantity' => $newBalance,
                'previous_quantity' => $previousBalance,
                'new_quantity' => $newBalance,
                'unit_cost' => $detail->unit_price,
                'total_cost' => $detail->quantity * $detail->unit_price,
                'document_type' => $dispatch->document_type,
                'document_number' => $dispatch->document_number,
                'notes' => "Despacho {$dispatch->dispatch_number} - Producto agregado post-entrega",
                'is_active' => true,
                'active_at' => now(),
                'created_by' => $userId,
            ]);

            // Actualizar inventario
            $inventory = Inventory::where('product_id', $detail->product_id)
                ->where('warehouse_id', $dispatch->warehouse_id)
                ->first();

            if ($inventory) {
                $inventory->quantity -= $detail->quantity;
                $inventory->available_quantity -= $detail->quantity;
                $inventory->save();
            }
        }
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    /**
     * Get available stock per product for the selected warehouse (for Alpine.js frontend validation)
     */
    #[\Livewire\Attributes\Computed]
    public function availableStockData(): array
    {
        if (! $this->warehouse_id) {
            return [];
        }

        return Inventory::where('warehouse_id', $this->warehouse_id)
            ->where('available_quantity', '>', 0)
            ->get()
            ->keyBy('product_id')
            ->map(fn ($inv) => $inv->available_quantity)
            ->toArray();
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
                    <flux:select wire:model.live="dispatch_type" required>
                        <option value="interno">Interno</option>
                        <option value="combustible">Combustibles y Lubricantes</option>
                        <option value="venta">Venta</option>
                        <option value="externo">Externo</option>
                        <option value="donacion">Donación</option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Número de documento físico</flux:label>
                    <flux:input wire:model.blur="physical_document_number" placeholder="Ingrese el número de documento físico" />
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

        {{-- Fuel Dispatch Fields --}}
        @if($dispatch_type === 'combustible')
            <flux:card>
                <flux:heading size="lg" class="mb-6">Descripción del Equipo o Vehículo</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <flux:field>
                        <flux:label badge="Requerido">Clase</flux:label>
                        <flux:input wire:model="vehicle_class" placeholder="Ej: Camioneta, Tractor, etc." />
                        <flux:error name="vehicle_class" />
                    </flux:field>
                    <flux:field>
                        <flux:label badge="Requerido">Marca</flux:label>
                        <flux:input wire:model="vehicle_brand" placeholder="Ej: Toyota, John Deere" />
                        <flux:error name="vehicle_brand" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Modelo</flux:label>
                        <flux:input wire:model="vehicle_model" placeholder="Ej: Hilux 2020" />
                        <flux:error name="vehicle_model" />
                    </flux:field>
                    <flux:field>
                        <flux:label badge="Requerido">Placa</flux:label>
                        <flux:input wire:model="vehicle_plate" placeholder="Ej: P-123-456" />
                        <flux:error name="vehicle_plate" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Lectura del Odómetro (km)</flux:label>
                        <flux:input type="number" step="0.01" wire:model="odometer_reading" placeholder="0.00" />
                        <flux:error name="odometer_reading" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Lectura del Horómetro (Hr)</flux:label>
                        <flux:input type="number" step="0.01" wire:model="horometer_reading" placeholder="0.00" />
                        <flux:error name="horometer_reading" />
                    </flux:field>
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-6">Descripción de la Justificación</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:field>
                        <flux:label badge="Requerido">Lugar a Visitar</flux:label>
                        <flux:input wire:model="place_to_visit" placeholder="Ingrese el lugar a visitar" />
                        <flux:error name="place_to_visit" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Kilómetros a Recorrer</flux:label>
                        <flux:input type="number" step="0.01" wire:model="kilometers_to_travel" placeholder="0.00" />
                        <flux:error name="kilometers_to_travel" />
                    </flux:field>
                    <flux:field class="md:col-span-2">
                        <flux:label badge="Requerido">Misión a Realizar</flux:label>
                        <flux:textarea wire:model="mission_description" rows="3" placeholder="Describa la misión a realizar" />
                        <flux:error name="mission_description" />
                    </flux:field>
                </div>
                <flux:text class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                    El responsable de la misión es la persona solicitante seleccionada arriba.
                </flux:text>
            </flux:card>
        @endif

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
                    $store.dispatchAvailableStock = @js($this->availableStockData);
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
                                        :max="availableStock > 0 ? availableStock : undefined"
                                        x-model.number="quantity"
                                        @input="emitTotal()"
                                        @change="updateQuantity()"
                                        :class="exceedsStock ? 'border-red-500 dark:border-red-500 ring-2 ring-red-200 dark:ring-red-900/50' : 'border-zinc-200 dark:border-zinc-600 focus:border-zinc-400 dark:focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:focus:ring-zinc-700'"
                                        class="block w-full text-center rounded-lg bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:outline-none dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500"
                                    />
                                    <template x-if="exceedsStock">
                                        <span class="text-xs text-red-600 dark:text-red-400">
                                            Máx: <span x-text="availableStock.toFixed(5)"></span>
                                        </span>
                                    </template>
                                    <template x-if="productId && availableStock > 0 && !exceedsStock">
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                            Disp: <span x-text="availableStock.toFixed(5)"></span>
                                        </span>
                                    </template>
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

            @error('stock')
                <flux:callout variant="danger" icon="exclamation-triangle" class="mt-4">
                    <flux:callout.heading>Stock insuficiente</flux:callout.heading>
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @enderror
        </flux:card>

        <div class="flex justify-end gap-2">
            <flux:button type="button" variant="ghost" href="{{ route('dispatches.show', $dispatch) }}" wire:navigate>
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary" :disabled="$documentNumberExists">
                Actualizar Despacho
            </flux:button>
        </div>
    </form>
</div>
