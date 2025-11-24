<?php

use App\Models\InventoryAdjustment;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public InventoryAdjustment $inventoryAdjustment;

    public function mount(InventoryAdjustment $inventoryAdjustment): void
    {
        $this->inventoryAdjustment = $inventoryAdjustment->load([
            'warehouse',
            'product',
            'storageLocation',
            'submitter',
            'approver',
            'rejector',
            'processor',
            'creator',
            'updater',
            'inventoryMovement',
        ]);
    }

    public function submit(): void
    {
        if ($this->inventoryAdjustment->submit(auth()->id())) {
            session()->flash('success', 'Ajuste enviado para aprobación exitosamente.');
            $this->inventoryAdjustment->refresh();
        } else {
            session()->flash('error', 'No se pudo enviar el ajuste.');
        }
        $this->modal('confirm-submit')->close();
    }

    public function approve(): void
    {
        if ($this->inventoryAdjustment->approve(auth()->id())) {
            session()->flash('success', 'Ajuste aprobado exitosamente.');
            $this->inventoryAdjustment->refresh();
        } else {
            session()->flash('error', 'No se pudo aprobar el ajuste.');
        }
        $this->modal('confirm-approve')->close();
    }

    public function process(): void
    {
        if ($this->inventoryAdjustment->process(auth()->id())) {
            session()->flash('success', 'Ajuste procesado exitosamente. Inventario actualizado.');
            $this->inventoryAdjustment->refresh();
        } else {
            session()->flash('error', 'Error al procesar el ajuste. Revise los logs.');
        }
        $this->modal('confirm-process')->close();
    }

    public string $rejectionReason = '';
    public bool $showRejectModal = false;

    public function openRejectModal(): void
    {
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->rejectionReason = '';
    }

    public function reject(): void
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:10|max:500',
        ], [
            'rejectionReason.required' => 'El motivo del rechazo es requerido.',
            'rejectionReason.min' => 'El motivo debe tener al menos 10 caracteres.',
            'rejectionReason.max' => 'El motivo no puede exceder 500 caracteres.',
        ]);

        if ($this->inventoryAdjustment->reject(auth()->id(), $this->rejectionReason)) {
            session()->flash('success', 'Ajuste rechazado exitosamente.');
            $this->inventoryAdjustment->refresh();
            $this->closeRejectModal();
        } else {
            session()->flash('error', 'No se pudo rechazar el ajuste.');
        }
    }

    public function cancel(): void
    {
        if ($this->inventoryAdjustment->cancel()) {
            session()->flash('success', 'Ajuste cancelado.');
            $this->inventoryAdjustment->refresh();
        } else {
            session()->flash('error', 'No se pudo cancelar el ajuste.');
        }
        $this->modal('confirm-cancel')->close();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Ajuste de Inventario: {{ $inventoryAdjustment->adjustment_number }}</flux:heading>
            <flux:text class="mt-1">{{ $inventoryAdjustment->reason }}</flux:text>
        </div>

        <div class="flex gap-2">
            @if($inventoryAdjustment->canBeEdited())
                <flux:button variant="ghost" icon="pencil" href="{{ route('adjustments.edit', $inventoryAdjustment->slug) }}" wire:navigate>
                    Editar
                </flux:button>
            @endif
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('adjustments.index') }}" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle">
            {{ session('error') }}
        </flux:callout>
    @endif

    <!-- Status Badge -->
    <div>
        <flux:badge
            :color="match($inventoryAdjustment->status) {
                'borrador' => 'zinc',
                'pendiente' => 'yellow',
                'aprobado' => 'blue',
                'procesado' => 'green',
                'rechazado' => 'red',
                'cancelado' => 'gray',
                default => 'zinc'
            }"
            size="lg"
        >
            {{ $inventoryAdjustment->status_spanish }}
        </flux:badge>
    </div>

    <!-- Workflow Actions -->
    @if($inventoryAdjustment->canBeSubmitted() || $inventoryAdjustment->canBeApproved() || $inventoryAdjustment->canBeProcessed() || $inventoryAdjustment->canBeRejected() || $inventoryAdjustment->canBeCancelled())
        <flux:card>
            <flux:heading size="lg">Acciones de Workflow</flux:heading>
            <flux:separator class="my-4" />

            <div class="flex gap-4">
                @if($inventoryAdjustment->canBeSubmitted())
                    <flux:modal.trigger name="confirm-submit">
                        <flux:button variant="primary" icon="paper-airplane">
                            Enviar para Aprobación
                        </flux:button>
                    </flux:modal.trigger>
                @endif

                @if($inventoryAdjustment->canBeApproved())
                    <flux:modal.trigger name="confirm-approve">
                        <flux:button variant="primary" icon="check">
                            Aprobar
                        </flux:button>
                    </flux:modal.trigger>
                @endif

                @if($inventoryAdjustment->canBeRejected())
                    <flux:button
                        variant="danger"
                        icon="x-mark"
                        wire:click="openRejectModal"
                    >
                        Rechazar
                    </flux:button>
                @endif

                @if($inventoryAdjustment->canBeProcessed())
                    <flux:modal.trigger name="confirm-process">
                        <flux:button variant="primary" icon="cog">
                            Procesar Ajuste
                        </flux:button>
                    </flux:modal.trigger>
                @endif

                @if($inventoryAdjustment->canBeCancelled())
                    <flux:modal.trigger name="confirm-cancel">
                        <flux:button variant="ghost">
                            Cancelar Ajuste
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        </flux:card>
    @endif

    <!-- Basic Information -->
    <flux:card>
        <flux:heading size="lg">Información del Ajuste</flux:heading>
        <flux:separator class="my-4" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Número de Ajuste</flux:text>
                <flux:text>{{ $inventoryAdjustment->adjustment_number }}</flux:text>
            </div>

            <div>
                <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Tipo de Ajuste</flux:text>
                <flux:badge :color="$inventoryAdjustment->isPositiveAdjustment() ? 'green' : 'red'">
                    {{ $inventoryAdjustment->adjustment_type_spanish }}
                </flux:badge>
            </div>

            <div>
                <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Bodega</flux:text>
                <flux:text>{{ $inventoryAdjustment->warehouse->name }}</flux:text>
            </div>

            <div>
                <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Producto</flux:text>
                <flux:text>{{ $inventoryAdjustment->product->name }} ({{ $inventoryAdjustment->product->sku }})</flux:text>
            </div>

            <div>
                <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Cantidad</flux:text>
                <flux:text class="{{ $inventoryAdjustment->isPositiveAdjustment() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-bold text-lg">
                    {{ $inventoryAdjustment->isPositiveAdjustment() ? '+' : '' }}{{ number_format($inventoryAdjustment->quantity, 4) }}
                </flux:text>
            </div>

            <div>
                <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Costo Unitario</flux:text>
                <flux:text>${{ number_format($inventoryAdjustment->unit_cost, 4) }}</flux:text>
            </div>

            <div>
                <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Valor Total</flux:text>
                <flux:text class="font-bold">${{ number_format($inventoryAdjustment->total_value, 2) }}</flux:text>
            </div>

            @if($inventoryAdjustment->storage_location_id)
                <div>
                    <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Ubicación</flux:text>
                    <flux:text>{{ $inventoryAdjustment->storageLocation->name ?? 'N/A' }}</flux:text>
                </div>
            @endif
        </div>
    </flux:card>

    <!-- Reason & Justification -->
    <flux:card>
        <flux:heading size="lg">Motivo y Justificación</flux:heading>
        <flux:separator class="my-4" />

        <div class="space-y-4">
            <div>
                <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Motivo</flux:text>
                <flux:text>{{ $inventoryAdjustment->reason }}</flux:text>
            </div>

            @if($inventoryAdjustment->justification)
                <div>
                    <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Justificación Detallada</flux:text>
                    <flux:text>{{ $inventoryAdjustment->justification }}</flux:text>
                </div>
            @endif

            @if($inventoryAdjustment->corrective_actions)
                <div>
                    <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Acciones Correctivas</flux:text>
                    <flux:text>{{ $inventoryAdjustment->corrective_actions }}</flux:text>
                </div>
            @endif
        </div>
    </flux:card>

    <!-- Workflow Tracking -->
    <flux:card>
        <flux:heading size="lg">Seguimiento del Workflow</flux:heading>
        <flux:separator class="my-4" />

        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Creado por</flux:text>
                    <flux:text>{{ $inventoryAdjustment->creator->name ?? 'Sistema' }}</flux:text>
                    <flux:text class="text-sm text-gray-500">{{ $inventoryAdjustment->created_at->format('d/m/Y H:i') }}</flux:text>
                </div>

                @if($inventoryAdjustment->submitted_at)
                    <div>
                        <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Enviado por</flux:text>
                        <flux:text>{{ $inventoryAdjustment->submitter->name ?? 'N/A' }}</flux:text>
                        <flux:text class="text-sm text-gray-500">{{ $inventoryAdjustment->submitted_at->format('d/m/Y H:i') }}</flux:text>
                    </div>
                @endif

                @if($inventoryAdjustment->approved_at)
                    <div>
                        <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Aprobado por</flux:text>
                        <flux:text>{{ $inventoryAdjustment->approver->name ?? 'N/A' }}</flux:text>
                        <flux:text class="text-sm text-gray-500">{{ $inventoryAdjustment->approved_at->format('d/m/Y H:i') }}</flux:text>
                    </div>
                @endif

                @if($inventoryAdjustment->rejected_at)
                    <div>
                        <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Rechazado por</flux:text>
                        <flux:text>{{ $inventoryAdjustment->rejector->name ?? 'N/A' }}</flux:text>
                        <flux:text class="text-sm text-gray-500">{{ $inventoryAdjustment->rejected_at->format('d/m/Y H:i') }}</flux:text>
                        @if($inventoryAdjustment->rejection_reason)
                            <flux:text class="text-sm text-red-600">Motivo: {{ $inventoryAdjustment->rejection_reason }}</flux:text>
                        @endif
                    </div>
                @endif

                @if($inventoryAdjustment->processed_at)
                    <div>
                        <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Procesado por</flux:text>
                        <flux:text>{{ $inventoryAdjustment->processor->name ?? 'N/A' }}</flux:text>
                        <flux:text class="text-sm text-gray-500">{{ $inventoryAdjustment->processed_at->format('d/m/Y H:i') }}</flux:text>
                    </div>
                @endif
            </div>

            @if($inventoryAdjustment->approval_notes)
                <div>
                    <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Notas de Aprobación</flux:text>
                    <flux:text>{{ $inventoryAdjustment->approval_notes }}</flux:text>
                </div>
            @endif
        </div>
    </flux:card>

    <!-- Reference Information -->
    @if($inventoryAdjustment->reference_document || $inventoryAdjustment->reference_number || $inventoryAdjustment->batch_number)
        <flux:card>
            <flux:heading size="lg">Información de Referencia</flux:heading>
            <flux:separator class="my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($inventoryAdjustment->reference_document)
                    <div>
                        <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Tipo de Documento</flux:text>
                        <flux:text>{{ $inventoryAdjustment->reference_document }}</flux:text>
                    </div>
                @endif

                @if($inventoryAdjustment->reference_number)
                    <div>
                        <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Número de Documento</flux:text>
                        <flux:text>{{ $inventoryAdjustment->reference_number }}</flux:text>
                    </div>
                @endif

                @if($inventoryAdjustment->batch_number)
                    <div>
                        <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Número de Lote</flux:text>
                        <flux:text>{{ $inventoryAdjustment->batch_number }}</flux:text>
                    </div>
                @endif

                @if($inventoryAdjustment->expiry_date)
                    <div>
                        <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Fecha de Vencimiento</flux:text>
                        <flux:text>{{ $inventoryAdjustment->expiry_date->format('d/m/Y') }}</flux:text>
                    </div>
                @endif

                @if($inventoryAdjustment->cost_center)
                    <div>
                        <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Centro de Costo</flux:text>
                        <flux:text>{{ $inventoryAdjustment->cost_center }}</flux:text>
                    </div>
                @endif

                @if($inventoryAdjustment->project_code)
                    <div>
                        <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Código de Proyecto</flux:text>
                        <flux:text>{{ $inventoryAdjustment->project_code }}</flux:text>
                    </div>
                @endif

                @if($inventoryAdjustment->department)
                    <div>
                        <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Departamento</flux:text>
                        <flux:text>{{ $inventoryAdjustment->department }}</flux:text>
                    </div>
                @endif
            </div>
        </flux:card>
    @endif

    <!-- Notes -->
    @if($inventoryAdjustment->notes || $inventoryAdjustment->admin_notes)
        <flux:card>
            <flux:heading size="lg">Notas</flux:heading>
            <flux:separator class="my-4" />

            @if($inventoryAdjustment->notes)
                <div class="mb-4">
                    <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Notas</flux:text>
                    <flux:text>{{ $inventoryAdjustment->notes }}</flux:text>
                </div>
            @endif

            @if($inventoryAdjustment->admin_notes)
                <div>
                    <flux:text class="font-semibold text-gray-700 dark:text-gray-300">Notas Administrativas</flux:text>
                    <flux:text>{{ $inventoryAdjustment->admin_notes }}</flux:text>
                </div>
            @endif
        </flux:card>
    @endif

    <!-- Inventory Movement Link -->
    @if($inventoryAdjustment->inventory_movement_id)
        <flux:card>
            <flux:heading size="lg">Movimiento de Inventario Generado</flux:heading>
            <flux:separator class="my-4" />

            <div>
                <flux:text>Este ajuste generó el movimiento de inventario ID: {{ $inventoryAdjustment->inventory_movement_id }}</flux:text>
                <flux:text class="text-sm text-gray-500">El inventario fue actualizado el {{ $inventoryAdjustment->processed_at->format('d/m/Y H:i') }}</flux:text>
            </div>
        </flux:card>
    @endif

    <!-- Rejection Modal -->
    <flux:modal name="reject-adjustment" wire:model="showRejectModal" class="md:w-96">
        <form wire:submit="reject" class="space-y-6">
            <div>
                <flux:heading size="lg">Rechazar Ajuste</flux:heading>
                <flux:text class="mt-2">Por favor, indique el motivo del rechazo de este ajuste de inventario.</flux:text>
            </div>

            <flux:field>
                <flux:label>Motivo del Rechazo *</flux:label>
                <flux:textarea
                    wire:model="rejectionReason"
                    placeholder="Explique por qué se rechaza este ajuste..."
                    rows="4"
                    required
                />
                <flux:text>Mínimo 10 caracteres, máximo 500</flux:text>
                @error('rejectionReason')
                    <flux:text variant="danger">{{ $message }}</flux:text>
                @enderror
            </flux:field>

            <div class="flex justify-end gap-4">
                <flux:button variant="ghost" type="button" wire:click="closeRejectModal">
                    Cancelar
                </flux:button>
                <flux:button variant="danger" type="submit">
                    Confirmar Rechazo
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Submit Confirmation Modal -->
    @if($inventoryAdjustment->canBeSubmitted())
        <flux:modal name="confirm-submit" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">¿Enviar para aprobación?</flux:heading>
                    <flux:text class="mt-2">
                        <p>El ajuste será enviado para su revisión y aprobación.</p>
                        <p>No podrá editarlo mientras esté pendiente de aprobación.</p>
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" wire:click="submit">Enviar</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Approve Confirmation Modal -->
    @if($inventoryAdjustment->canBeApproved())
        <flux:modal name="confirm-approve" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">¿Aprobar ajuste?</flux:heading>
                    <flux:text class="mt-2">
                        <p>Una vez aprobado, el ajuste quedará listo para ser procesado.</p>
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" wire:click="approve">Aprobar</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Process Confirmation Modal -->
    @if($inventoryAdjustment->canBeProcessed())
        <flux:modal name="confirm-process" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">¿Procesar ajuste?</flux:heading>
                    <flux:text class="mt-2">
                        <p>Esta acción actualizará el inventario.</p>
                        <p class="font-semibold text-red-600">Esta acción NO se puede revertir.</p>
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="process">Procesar</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Cancel Confirmation Modal -->
    @if($inventoryAdjustment->canBeCancelled())
        <flux:modal name="confirm-cancel" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">¿Cancelar ajuste?</flux:heading>
                    <flux:text class="mt-2">
                        <p>El ajuste será cancelado.</p>
                        <p>Esta acción no se puede deshacer.</p>
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Volver</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="cancel">Cancelar Ajuste</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
