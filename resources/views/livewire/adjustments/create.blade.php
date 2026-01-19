<?php

use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Models\Company;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Warehouse;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public $company_id = '';

    public $warehouse_id = '';

    public $product_id = '';

    public $adjustment_type = 'correction';

    public $quantity = 0;

    public $unit_cost = '';

    public $movement_reason_code = '';

    public $reason = '';

    public $justification = '';

    public $corrective_actions = '';

    public $reference_document = '';

    public $reference_number = '';

    public $storage_location_id = '';

    public $batch_number = '';

    public $expiry_date = '';

    public $notes = '';

    public $cost_center = '';

    public $project_code = '';

    public $department = '';

    public $status = 'borrador';

    public $availableStock = null;

    public $stockUnit = '';

    public function mount(): void
    {
        // For non-super admins, set company_id automatically
        if (! $this->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }

        // Check if coming from inventory page with pre-selected inventory
        $inventoryId = request()->query('inventory_id');
        $type = request()->query('type');

        if ($inventoryId) {
            $inventory = \App\Models\Inventory::with(['product.unitOfMeasure', 'warehouse.company'])->find($inventoryId);

            if ($inventory) {
                $this->company_id = $inventory->warehouse->company_id;
                $this->warehouse_id = $inventory->warehouse_id;
                $this->product_id = $inventory->product_id;
                $this->availableStock = $inventory->quantity;
                $this->stockUnit = $inventory->product->unitOfMeasure->symbol ?? '';
                $this->unit_cost = $inventory->unit_cost ?? $inventory->product->cost ?? 0;

                // Set adjustment type based on parameter
                if ($type === 'count') {
                    $this->adjustment_type = 'physical_count';
                    $this->reason = 'Conteo físico de inventario';
                }
            }
        }
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function updatedCompanyId(): void
    {
        // Reset warehouse and product when company changes
        $this->warehouse_id = '';
        $this->product_id = '';
        $this->availableStock = null;
        $this->stockUnit = '';
    }

    public function updatedWarehouseId(): void
    {
        // Reset stock info and re-check if product is selected
        $this->availableStock = null;
        $this->stockUnit = '';
        $this->checkAvailableStock();
    }

    public function updatedProductId($value): void
    {
        $this->availableStock = null;
        $this->stockUnit = '';

        if ($value && $this->warehouse_id) {
            // Get current stock and unit cost from latest inventory movement
            $movement = \App\Models\InventoryMovement::where('warehouse_id', $this->warehouse_id)
                ->where('product_id', $value)
                ->whereNotNull('balance_quantity')
                ->orderBy('movement_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if ($movement) {
                $this->unit_cost = $movement->unit_cost ?? 0;
            } else {
                $product = Product::find($value);
                $this->unit_cost = $product->cost ?? 0;
            }

            $this->checkAvailableStock();
        }
    }

    public function checkAvailableStock(): void
    {
        if (! $this->warehouse_id || ! $this->product_id) {
            $this->availableStock = null;
            $this->stockUnit = '';

            return;
        }

        // Get stock from inventory table
        $inventory = \App\Models\Inventory::where('warehouse_id', $this->warehouse_id)
            ->where('product_id', $this->product_id)
            ->first();

        if ($inventory) {
            $this->availableStock = $inventory->quantity;
        } else {
            // Fallback to latest movement balance
            $movement = \App\Models\InventoryMovement::where('warehouse_id', $this->warehouse_id)
                ->where('product_id', $this->product_id)
                ->whereNotNull('balance_quantity')
                ->orderBy('movement_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $this->availableStock = $movement ? $movement->balance_quantity : 0;
        }

        // Get the product's unit of measure
        $product = Product::with('unitOfMeasure')->find($this->product_id);
        $this->stockUnit = $product?->unitOfMeasure?->abbreviation ?? $product?->unitOfMeasure?->name ?? '';
    }

    public function save(): void
    {
        $request = new StoreInventoryAdjustmentRequest;
        $rules = $request->rules();
        $messages = $request->messages();

        // Remove rules for properties that don't exist in this component
        unset($rules['attachments'], $rules['attachments.*'], $rules['admin_notes'], $rules['is_active']);

        // Fix quantity validation to avoid duplicate messages
        // Change from ['required', 'numeric', 'not_in:0'] to a single clear rule
        $rules['quantity'] = ['required', 'numeric', 'gt:0'];
        $messages['quantity.gt'] = 'La cantidad debe ser mayor a cero.';

        // Add company_id validation for super admins
        if ($this->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
            $messages['company_id.required'] = 'La empresa es requerida.';
            $messages['company_id.exists'] = 'La empresa seleccionada no existe.';
        }

        $validated = $this->validate($rules, $messages);

        // Use selected company_id for super admin, otherwise use auth user's company
        $companyId = $this->isSuperAdmin() ? $this->company_id : auth()->user()->company_id;

        // Helper to convert empty strings to null
        $nullIfEmpty = fn ($value) => $value === '' ? null : $value;

        // Prepare admin notes with movement reason code
        $adminNotes = '';
        if ($this->movement_reason_code) {
            $movementReason = \App\Models\MovementReason::where('code', $this->movement_reason_code)->first();
            if ($movementReason) {
                $adminNotes = "Código ENA: {$movementReason->legacy_code} - {$movementReason->legacy_name} (Code: {$this->movement_reason_code})";
            }
        }

        $adjustment = InventoryAdjustment::create([
            'company_id' => $companyId,
            'warehouse_id' => $validated['warehouse_id'],
            'product_id' => $validated['product_id'],
            'adjustment_type' => $validated['adjustment_type'],
            'quantity' => $validated['quantity'],
            'unit_cost' => $nullIfEmpty($validated['unit_cost'] ?? null) ?? 0,
            'reason' => $validated['reason'],
            'justification' => $nullIfEmpty($validated['justification'] ?? null),
            'corrective_actions' => $nullIfEmpty($validated['corrective_actions'] ?? null),
            'reference_document' => $nullIfEmpty($validated['reference_document'] ?? null),
            'reference_number' => $nullIfEmpty($validated['reference_number'] ?? null),
            'storage_location_id' => $nullIfEmpty($validated['storage_location_id'] ?? null),
            'batch_number' => $nullIfEmpty($validated['batch_number'] ?? null),
            'expiry_date' => $nullIfEmpty($validated['expiry_date'] ?? null),
            'notes' => $nullIfEmpty($validated['notes'] ?? null),
            'admin_notes' => $adminNotes,
            'cost_center' => $nullIfEmpty($validated['cost_center'] ?? null),
            'project_code' => $nullIfEmpty($validated['project_code'] ?? null),
            'department' => $nullIfEmpty($validated['department'] ?? null),
            'status' => $validated['status'] ?? 'borrador',
        ]);

        session()->flash('success', 'Ajuste de inventario creado exitosamente.');
        $this->redirect(route('adjustments.show', $adjustment->slug), navigate: true);
    }

    public function saveAndSubmit(): void
    {
        $this->status = 'pendiente';
        $this->save();
    }

    #[\Livewire\Attributes\Computed]
    public function companies()
    {
        if ($this->isSuperAdmin()) {
            return Company::active()->orderBy('name')->get(['id', 'name']);
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
            ->orderBy('name')
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function products()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return Product::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function movementReasons()
    {
        // Get adjustment-related movement reasons
        return \App\Models\MovementReason::whereIn('category', ['adjustment', 'disposal'])
            ->where('is_active', true)
            ->orderBy('movement_type')
            ->orderBy('legacy_code')
            ->get();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nuevo Ajuste de Inventario</flux:heading>
            <flux:text class="mt-1">Registrar un ajuste de inventario por daños, pérdidas, correcciones, etc.</flux:text>
        </div>

        <flux:button variant="ghost" icon="arrow-left" href="{{ route('adjustments.index') }}" wire:navigate>
            Volver al listado
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-8">
        <!-- Basic Information -->
        <flux:card>
            <flux:heading size="lg" class="mb-6">Información Básica</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($this->isSuperAdmin())
                    <flux:field class="md:col-span-2">
                        <flux:label badge="Requerido">Empresa</flux:label>
                        <flux:select variant="listbox" searchable wire:model.live="company_id" placeholder="Seleccione una empresa">
                            <flux:select.option value="">Seleccione una empresa</flux:select.option>
                            @foreach ($this->companies as $company)
                                <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="company_id" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label badge="Requerido">Bodega</flux:label>
                    <flux:select variant="listbox" searchable wire:model.live="warehouse_id" :disabled="$this->isSuperAdmin() && !$company_id" placeholder="Seleccione bodega">
                        <flux:select.option value="">Seleccione bodega</flux:select.option>
                        @foreach ($this->warehouses as $warehouse)
                            <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @if($this->isSuperAdmin() && !$company_id)
                        <flux:description>Primero selecciona una empresa</flux:description>
                    @endif
                    <flux:error name="warehouse_id" />
                   <flux:description>&nbsp;</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Producto</flux:label>
                    <flux:select variant="listbox" searchable wire:model.live="product_id" :disabled="$this->isSuperAdmin() && !$company_id" placeholder="Seleccione producto">
                        <flux:select.option value="">Seleccione producto</flux:select.option>
                        @foreach ($this->products as $product)
                            <flux:select.option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    @if($this->isSuperAdmin() && !$company_id)
                        <flux:description>Primero selecciona una empresa</flux:description>
                    @endif
                    <flux:error name="product_id" />

                    @if($availableStock !== null)
                        <div class="mt-2 flex items-center gap-2">
                            <flux:badge size="sm" :color="$availableStock > 0 ? 'lime' : 'zinc'" icon="cube">
                                Stock disponible: {{ number_format($availableStock, 2) }} {{ $stockUnit }}
                            </flux:badge>
                        </div>
                    @endif
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Tipo de Ajuste</flux:label>
                    <flux:select variant="listbox" wire:model="adjustment_type" placeholder="Seleccione tipo de ajuste" searchable>
                        <flux:select.option value="physical_count">Conteo Físico</flux:select.option>
                        <flux:select.option value="positive">Ajuste Positivo (Sobrante)</flux:select.option>
                        <flux:select.option value="negative">Ajuste Negativo (Faltante)</flux:select.option>
                        <flux:select.option value="damage">Producto Dañado</flux:select.option>
                        <flux:select.option value="expiry">Producto Vencido</flux:select.option>
                        <flux:select.option value="loss">Pérdida/Robo</flux:select.option>
                        <flux:select.option value="correction">Corrección de Conteo</flux:select.option>
                        <flux:select.option value="return">Devolución</flux:select.option>
                        <flux:select.option value="other">Otro</flux:select.option>
                    </flux:select>
                    <flux:description>Para negativos, ingrese cantidad positiva (se convertirá automáticamente)</flux:description>
                    <flux:error name="adjustment_type" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label badge="Requerido">Código de Transacción ENA</flux:label>
                    <flux:select variant="listbox" searchable wire:model="movement_reason_code" placeholder="Seleccione el código de transacción">
                        {{-- <flux:select.option value="">Seleccione el código de transacción</flux:select.option> --}}
                        @foreach ($this->movementReasons as $reason)
                            <flux:select.option value="{{ $reason->code }}">
                                {{ $reason->legacy_code }} - {{ $reason->legacy_name }}
                                ({{ $reason->movement_type === 'in' ? 'ENTRADA' : 'SALIDA' }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:description>
                        Seleccione el código del sistema ENA que corresponde a este ajuste.
                        <strong>ENTRADAS:</strong> EJ (Ajuste), EM (Medición)
                        <strong>SALIDAS:</strong> SJ (Ajuste), SN (Medición), S4 (Descarte), S5 (Obsolescencia)
                    </flux:description>
                    <flux:error name="movement_reason_code" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Cantidad</flux:label>
                    <flux:input type="number" wire:model="quantity" step="0.0001" placeholder="Ej: 5.5" />
                    <flux:error name="quantity" />
                    <flux:description>Ingrese siempre como positivo</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Costo Unitario</flux:label>
                    <flux:input type="number" wire:model="unit_cost" step="0.0001" placeholder="0.00" />
                    <flux:error name="unit_cost" />
                    <flux:description>Se autocompleta al seleccionar producto</flux:description>
                </flux:field>
            </div>
        </flux:card>

        <!-- Reason & Justification -->
        <flux:card>
            <flux:heading size="lg" class="mb-6">Motivo y Justificación</flux:heading>

            <div class="grid grid-cols-1 gap-6">
                <flux:field>
                    <flux:label badge="Requerido">Motivo</flux:label>
                    <flux:input wire:model="reason" placeholder="Ej: Producto dañado durante almacenamiento" />
                    <flux:error name="reason" />
                    <flux:description>Breve descripción del ajuste</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Justificación Detallada</flux:label>
                    <flux:textarea wire:model="justification" placeholder="Explicación completa de la situación..." rows="3" />
                    <flux:error name="justification" />
                    <flux:description>Explicación completa del por qué del ajuste</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Acciones Correctivas</flux:label>
                    <flux:textarea wire:model="corrective_actions" placeholder="Medidas que se tomarán para evitar este problema..." rows="3" />
                    <flux:error name="corrective_actions" />
                    <flux:description>Qué se hará para prevenir este tipo de ajuste en el futuro</flux:description>
                </flux:field>
            </div>
        </flux:card>

        <!-- Reference & Additional Details -->
        <flux:card>
            <flux:heading size="lg" class="mb-6">Referencia y Detalles Adicionales</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Tipo de Documento de Referencia</flux:label>
                    <flux:input wire:model="reference_document" placeholder="Ej: Acta, Informe, etc." />
                    <flux:error name="reference_document" />
                </flux:field>

                <flux:field>
                    <flux:label>Número de Documento</flux:label>
                    <flux:input wire:model="reference_number" placeholder="Ej: ACT-2025-001" />
                    <flux:error name="reference_number" />
                </flux:field>

                <flux:field>
                    <flux:label>Número de Lote</flux:label>
                    <flux:input wire:model="batch_number" placeholder="Ej: LOTE-2025-001" />
                    <flux:error name="batch_number" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de Vencimiento</flux:label>
                    <flux:input type="date" wire:model="expiry_date" />
                    <flux:error name="expiry_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Centro de Costo</flux:label>
                    <flux:input wire:model="cost_center" placeholder="Ej: CC-001" />
                    <flux:error name="cost_center" />
                </flux:field>

                <flux:field>
                    <flux:label>Código de Proyecto</flux:label>
                    <flux:input wire:model="project_code" placeholder="Ej: PROY-2025-001" />
                    <flux:error name="project_code" />
                </flux:field>

                <flux:field>
                    <flux:label>Departamento</flux:label>
                    <flux:input wire:model="department" placeholder="Ej: Almacén General" />
                    <flux:error name="department" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Notes -->
        <flux:card>
            <flux:heading size="lg" class="mb-6">Notas Adicionales</flux:heading>

            <flux:field>
                <flux:label>Notas</flux:label>
                <flux:textarea wire:model="notes" placeholder="Información adicional relevante..." rows="4" />
                <flux:error name="notes" />
            </flux:field>
        </flux:card>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <flux:button variant="ghost" href="{{ route('adjustments.index') }}" wire:navigate type="button">
                Cancelar
            </flux:button>
            <div class="flex gap-3">
                <flux:button type="submit" variant="primary" icon="check">
                    Guardar como Borrador
                </flux:button>
                <flux:button type="button" variant="primary" wire:click="saveAndSubmit" icon="paper-airplane">
                    Guardar y Enviar para Aprobación
                </flux:button>
            </div>
        </div>
    </form>
</div>
