<?php

use App\Http\Requests\UpdateInventoryAdjustmentRequest;
use App\Models\{InventoryAdjustment, Product, Warehouse};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public InventoryAdjustment $inventoryAdjustment;
    public $warehouse_id = '';
    public $product_id = '';
    public $adjustment_type = '';
    public $quantity = 0;
    public $unit_cost = '';
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

    public function mount(InventoryAdjustment $inventoryAdjustment): void
    {
        // Only allow editing drafts and rejected adjustments
        if (! $inventoryAdjustment->canBeEdited()) {
            session()->flash('error', 'Solo se pueden editar ajustes en estado borrador o rechazado.');
            $this->redirect(route('adjustments.show', $inventoryAdjustment->slug), navigate: true);

            return;
        }

        $this->inventoryAdjustment = $inventoryAdjustment;
        // Cast IDs to strings for Livewire select binding
        $this->warehouse_id = (string) $inventoryAdjustment->warehouse_id;
        $this->product_id = (string) $inventoryAdjustment->product_id;
        $this->adjustment_type = $inventoryAdjustment->adjustment_type;
        // Display quantity as absolute value since form handles sign
        $this->quantity = abs($inventoryAdjustment->quantity);
        $this->unit_cost = $inventoryAdjustment->unit_cost;
        $this->reason = $inventoryAdjustment->reason;
        $this->justification = $inventoryAdjustment->justification;
        $this->corrective_actions = $inventoryAdjustment->corrective_actions;
        $this->reference_document = $inventoryAdjustment->reference_document;
        $this->reference_number = $inventoryAdjustment->reference_number;
        $this->storage_location_id = $inventoryAdjustment->storage_location_id ? (string) $inventoryAdjustment->storage_location_id : '';
        $this->batch_number = $inventoryAdjustment->batch_number;
        $this->expiry_date = $inventoryAdjustment->expiry_date?->format('Y-m-d');
        $this->notes = $inventoryAdjustment->notes;
        $this->cost_center = $inventoryAdjustment->cost_center;
        $this->project_code = $inventoryAdjustment->project_code;
        $this->department = $inventoryAdjustment->department;
        $this->status = $inventoryAdjustment->status;
    }

    public function updatedProductId($value): void
    {
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
        }
    }

    public function save(): void
    {
        $validated = $this->validate((new UpdateInventoryAdjustmentRequest())->rules());

        $this->inventoryAdjustment->update([
            'warehouse_id' => $validated['warehouse_id'],
            'product_id' => $validated['product_id'],
            'adjustment_type' => $validated['adjustment_type'],
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unit_cost'] ?? 0,
            'reason' => $validated['reason'],
            'justification' => $validated['justification'] ?? null,
            'corrective_actions' => $validated['corrective_actions'] ?? null,
            'reference_document' => $validated['reference_document'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'storage_location_id' => $validated['storage_location_id'] ?? null,
            'batch_number' => $validated['batch_number'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'cost_center' => $validated['cost_center'] ?? null,
            'project_code' => $validated['project_code'] ?? null,
            'department' => $validated['department'] ?? null,
            'status' => $validated['status'] ?? 'borrador',
        ]);

        session()->flash('success', 'Ajuste de inventario actualizado exitosamente.');
        $this->redirect(route('adjustments.show', $this->inventoryAdjustment->slug), navigate: true);
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function with(): array
    {
        // Use the adjustment's company_id to filter warehouses and products
        $companyId = $this->inventoryAdjustment->company_id;

        return [
            'warehouses' => Warehouse::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Editar Ajuste de Inventario</flux:heading>
            <flux:text class="mt-1">{{ $inventoryAdjustment->adjustment_number }}</flux:text>
        </div>

        <flux:button variant="ghost" icon="arrow-left" href="{{ route('adjustments.show', $inventoryAdjustment->slug) }}" wire:navigate>
            Volver al detalle
        </flux:button>
    </div>

    @if($inventoryAdjustment->status === 'rechazado' && $inventoryAdjustment->rejection_reason)
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:heading size="sm">Motivo del Rechazo</flux:heading>
            <flux:text>{{ $inventoryAdjustment->rejection_reason }}</flux:text>
        </flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Basic Information -->
        <flux:card>
            <flux:heading size="lg">Información Básica</flux:heading>
            <flux:separator class="my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Bodega *</flux:label>
                    <flux:select wire:model.live="warehouse_id" required>
                        <option value="">Seleccionar bodega...</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('warehouse_id') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Producto *</flux:label>
                    <flux:select wire:model.live="product_id" required>
                        <option value="">Seleccionar producto...</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                        @endforeach
                    </flux:select>
                    @error('product_id') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de Ajuste *</flux:label>
                    <flux:select wire:model="adjustment_type" required>
                        <option value="positive">Ajuste Positivo (Sobrante)</option>
                        <option value="negative">Ajuste Negativo (Faltante)</option>
                        <option value="damage">Producto Dañado</option>
                        <option value="expiry">Producto Vencido</option>
                        <option value="loss">Pérdida/Robo</option>
                        <option value="correction">Corrección de Conteo</option>
                        <option value="return">Devolución</option>
                        <option value="other">Otro</option>
                    </flux:select>
                    <flux:text>Para negativos, ingrese cantidad positiva (se convertirá automáticamente)</flux:text>
                    @error('adjustment_type') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Cantidad *</flux:label>
                    <flux:input type="number" wire:model="quantity" step="0.0001" required placeholder="Ej: 5.5" />
                    <flux:text>Ingrese siempre como positivo</flux:text>
                    @error('quantity') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Costo Unitario</flux:label>
                    <flux:input type="number" wire:model="unit_cost" step="0.0001" placeholder="0.00" />
                    <flux:text>Se autocompleta al seleccionar producto</flux:text>
                    @error('unit_cost') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Reason & Justification -->
        <flux:card>
            <flux:heading size="lg">Motivo y Justificación</flux:heading>
            <flux:separator class="my-4" />

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Motivo *</flux:label>
                    <flux:input wire:model="reason" placeholder="Ej: Producto dañado durante almacenamiento" required />
                    <flux:text>Breve descripción del ajuste</flux:text>
                    @error('reason') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Justificación Detallada</flux:label>
                    <flux:textarea wire:model="justification" placeholder="Explicación completa de la situación..." rows="3" />
                    <flux:text>Explicación completa del por qué del ajuste</flux:text>
                    @error('justification') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Acciones Correctivas</flux:label>
                    <flux:textarea wire:model="corrective_actions" placeholder="Medidas que se tomarán para evitar este problema..." rows="3" />
                    <flux:text>Qué se hará para prevenir este tipo de ajuste en el futuro</flux:text>
                    @error('corrective_actions') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Reference & Additional Details -->
        <flux:card>
            <flux:heading size="lg">Referencia y Detalles Adicionales</flux:heading>
            <flux:separator class="my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Tipo de Documento de Referencia</flux:label>
                    <flux:input wire:model="reference_document" placeholder="Ej: Acta, Informe, etc." />
                    @error('reference_document') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Número de Documento</flux:label>
                    <flux:input wire:model="reference_number" placeholder="Ej: ACT-2025-001" />
                    @error('reference_number') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Número de Lote</flux:label>
                    <flux:input wire:model="batch_number" placeholder="Ej: LOTE-2025-001" />
                    @error('batch_number') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de Vencimiento</flux:label>
                    <flux:input type="date" wire:model="expiry_date" />
                    @error('expiry_date') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Centro de Costo</flux:label>
                    <flux:input wire:model="cost_center" placeholder="Ej: CC-001" />
                    @error('cost_center') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Código de Proyecto</flux:label>
                    <flux:input wire:model="project_code" placeholder="Ej: PROY-2025-001" />
                    @error('project_code') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Departamento</flux:label>
                    <flux:input wire:model="department" placeholder="Ej: Almacén General" />
                    @error('department') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Notes -->
        <flux:card>
            <flux:heading size="lg">Notas Adicionales</flux:heading>
            <flux:separator class="my-4" />

            <flux:field>
                <flux:label>Notas</flux:label>
                <flux:textarea wire:model="notes" placeholder="Información adicional relevante..." rows="4" />
                @error('notes') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
            </flux:field>
        </flux:card>

        <!-- Actions -->
        <div class="flex justify-end gap-4">
            <flux:button variant="ghost" href="{{ route('adjustments.show', $inventoryAdjustment->slug) }}" wire:navigate>
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary" wire:model="status" value="borrador">
                Guardar como Borrador
            </flux:button>
            <flux:button type="button" variant="primary" wire:click="$set('status', 'pendiente')" wire:then="save">
                Guardar y Enviar para Aprobación
            </flux:button>
        </div>
    </form>
</div>
