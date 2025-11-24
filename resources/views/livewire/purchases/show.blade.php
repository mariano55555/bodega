<?php

use App\Models\Purchase;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Purchase $purchase;

    public function mount(Purchase $purchase): void
    {
        $this->authorize('view', $purchase);
        $this->purchase = $purchase->load(['supplier', 'warehouse', 'details.product', 'approver', 'receiver', 'creator']);
    }

    public function approve(): void
    {
        $this->authorize('approve', $this->purchase);

        Flux::modals()->close();

        if ($this->purchase->approve(auth()->id())) {
            Flux::toast(
                variant: 'success',
                heading: 'Compra Aprobada',
                text: 'La compra ha sido aprobada exitosamente.',
            );
            $this->purchase->refresh();
        } else {
            Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: 'No se puede aprobar la compra en su estado actual.',
            );
        }
    }

    public function receive(): void
    {
        $this->authorize('receive', $this->purchase);

        Flux::modals()->close();

        if ($this->purchase->receive(auth()->id())) {
            Flux::toast(
                variant: 'success',
                heading: 'Compra Recibida',
                text: 'La compra ha sido recibida y el inventario actualizado.',
            );
            $this->purchase->refresh();
        } else {
            Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: 'No se puede recibir la compra en su estado actual.',
            );
        }
    }

    public function cancel(): void
    {
        $this->authorize('cancel', $this->purchase);

        Flux::modals()->close();

        if ($this->purchase->cancel()) {
            Flux::toast(
                variant: 'warning',
                heading: 'Compra Cancelada',
                text: 'La compra ha sido cancelada.',
            );
            $this->purchase->refresh();
        } else {
            Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: 'No se puede cancelar la compra en su estado actual.',
            );
        }
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->purchase);

        Flux::modals()->close();

        $this->purchase->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Compra Eliminada',
            text: 'La compra ha sido eliminada exitosamente.',
        );

        $this->redirect(route('purchases.index'), navigate: true);
    }

    public function submit(): void
    {
        $this->authorize('submit', $this->purchase);

        Flux::modals()->close();

        if ($this->purchase->submit()) {
            Flux::toast(
                variant: 'success',
                heading: 'Compra Enviada',
                text: 'La compra ha sido enviada para aprobación.',
            );
            $this->purchase->refresh();
        } else {
            Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: 'No se puede enviar la compra en su estado actual.',
            );
        }
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Compra {{ $purchase->purchase_number }}</flux:heading>
            <flux:text class="mt-1">{{ $purchase->supplier->name }}</flux:text>
        </div>

        <div class="flex items-center gap-2">
            @can('update', $purchase)
                <flux:button variant="primary" icon="pencil" href="{{ route('purchases.edit', $purchase) }}" wire:navigate>
                    Editar
                </flux:button>
            @endcan

            @can('submit', $purchase)
                <flux:modal.trigger name="submit-modal">
                    <flux:button variant="primary" icon="paper-airplane">
                        Enviar
                    </flux:button>
                </flux:modal.trigger>
            @endcan

            @can('approve', $purchase)
                <flux:modal.trigger name="approve-modal">
                    <flux:button variant="primary" icon="check">
                        Aprobar
                    </flux:button>
                </flux:modal.trigger>
            @endcan

            @can('receive', $purchase)
                <flux:modal.trigger name="receive-modal">
                    <flux:button variant="filled" icon="check-circle">
                        Recibir
                    </flux:button>
                </flux:modal.trigger>
            @endcan

            @can('cancel', $purchase)
                <flux:modal.trigger name="cancel-modal">
                    <flux:button variant="danger" icon="x-circle">
                        Cancelar
                    </flux:button>
                </flux:modal.trigger>
            @endcan

            @can('delete', $purchase)
                <flux:modal.trigger name="delete-modal">
                    <flux:button variant="danger" icon="trash">
                        Eliminar
                    </flux:button>
                </flux:modal.trigger>
            @endcan

            <flux:button variant="ghost" icon="arrow-left" href="{{ route('purchases.index') }}" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Información del Documento</flux:heading>
                <flux:separator class="my-4" />

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Número de Compra</flux:text>
                        <flux:text class="font-semibold">{{ $purchase->purchase_number }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Estado</flux:text>
                        <div class="mt-1">
                            <flux:badge
                                size="sm"
                                :color="match($purchase->status) {
                                    'borrador' => 'zinc',
                                    'pendiente' => 'amber',
                                    'aprobado' => 'sky',
                                    'recibido' => 'emerald',
                                    'cancelado' => 'red',
                                    default => 'zinc'
                                }"
                                :icon="match($purchase->status) {
                                    'borrador' => 'pencil',
                                    'pendiente' => 'clock',
                                    'aprobado' => 'check',
                                    'recibido' => 'check-circle',
                                    'cancelado' => 'x-circle',
                                    default => null
                                }"
                            >
                                {{ ucfirst($purchase->status) }}
                            </flux:badge>
                        </div>
                    </div>

                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Tipo de Documento</flux:text>
                        <flux:text class="font-semibold">{{ ucfirst($purchase->document_type) }}</flux:text>
                    </div>

                    @if ($purchase->document_number)
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Número de Documento</flux:text>
                            <flux:text class="font-semibold">{{ $purchase->document_number }}</flux:text>
                        </div>
                    @endif

                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Fecha del Documento</flux:text>
                        <flux:text class="font-semibold">{{ $purchase->document_date->format('d/m/Y') }}</flux:text>
                    </div>

                    @if ($purchase->due_date)
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Fecha de Vencimiento</flux:text>
                            <flux:text class="font-semibold">{{ $purchase->due_date->format('d/m/Y') }}</flux:text>
                        </div>
                    @endif

                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Proveedor</flux:text>
                        <flux:text class="font-semibold">{{ $purchase->supplier->name }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Bodega Destino</flux:text>
                        <flux:text class="font-semibold">{{ $purchase->warehouse->name }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Tipo de Compra</flux:text>
                        <div class="mt-1">
                            <flux:badge
                                size="sm"
                                :color="$purchase->purchase_type === 'efectivo' ? 'emerald' : 'amber'"
                                :icon="$purchase->purchase_type === 'efectivo' ? 'banknotes' : 'credit-card'"
                            >
                                {{ ucfirst($purchase->purchase_type) }}
                            </flux:badge>
                        </div>
                    </div>

                    @if ($purchase->payment_method)
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Método de Pago</flux:text>
                            <flux:text class="font-semibold">{{ $purchase->payment_method }}</flux:text>
                        </div>
                    @endif

                    @if ($purchase->fund_source)
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Origen de Fondos</flux:text>
                            <flux:text class="font-semibold">{{ $purchase->fund_source }}</flux:text>
                        </div>
                    @endif

                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Tipo de Adquisición</flux:text>
                        <div class="mt-1">
                            <flux:badge
                                size="sm"
                                :color="match($purchase->acquisition_type) {
                                    'normal' => 'zinc',
                                    'convenio' => 'sky',
                                    'proyecto' => 'violet',
                                    'otro' => 'amber',
                                    default => 'zinc'
                                }"
                                :icon="match($purchase->acquisition_type) {
                                    'normal' => 'shopping-cart',
                                    'convenio' => 'document-text',
                                    'proyecto' => 'briefcase',
                                    'otro' => 'question-mark-circle',
                                    default => null
                                }"
                            >
                                {{ $purchase->getAcquisitionTypeLabel() }}
                            </flux:badge>
                        </div>
                    </div>

                    @if ($purchase->acquisition_type === 'proyecto' && $purchase->project_name)
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Nombre del Proyecto</flux:text>
                            <flux:text class="font-semibold">{{ $purchase->project_name }}</flux:text>
                        </div>
                    @endif

                    @if ($purchase->acquisition_type === 'convenio' && $purchase->agreement_number)
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Número de Convenio</flux:text>
                            <flux:text class="font-semibold">{{ $purchase->agreement_number }}</flux:text>
                        </div>
                    @endif

                    @if ($purchase->is_retroactive)
                        <div class="col-span-2">
                            <flux:callout variant="warning" icon="exclamation-triangle">
                                <strong>Compra Retroactiva:</strong> Esta compra fue registrada con fecha anterior al mes actual.
                            </flux:callout>
                        </div>
                    @endif
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-4">Productos</flux:heading>
                <flux:separator class="my-4" />

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Producto</flux:table.column>
                            <flux:table.column>Cantidad</flux:table.column>
                            <flux:table.column>Costo Unit.</flux:table.column>
                            <flux:table.column>Desc.</flux:table.column>
                            <flux:table.column>IVA</flux:table.column>
                            <flux:table.column>Total</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($purchase->details as $detail)
                                <flux:table.row :key="$detail->id">
                                    <flux:table.cell>
                                        <div>
                                            <div class="font-medium">{{ $detail->product->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $detail->product->sku }}</div>
                                            @if ($detail->lot_number)
                                                <div class="text-xs text-gray-400">Lote: {{ $detail->lot_number }}</div>
                                            @endif
                                        </div>
                                    </flux:table.cell>

                                    <flux:table.cell>{{ number_format($detail->quantity, 4) }}</flux:table.cell>

                                    <flux:table.cell>${{ number_format($detail->unit_cost, 2) }}</flux:table.cell>

                                    <flux:table.cell>
                                        @if ($detail->discount_percentage > 0)
                                            {{ number_format($detail->discount_percentage, 2) }}%
                                            <div class="text-xs text-gray-500">-${{ number_format($detail->discount_amount, 2) }}</div>
                                        @else
                                            -
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        @if ($detail->tax_percentage > 0)
                                            {{ number_format($detail->tax_percentage, 2) }}%
                                            <div class="text-xs text-gray-500">+${{ number_format($detail->tax_amount, 2) }}</div>
                                        @else
                                            -
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell class="font-semibold">${{ number_format($detail->total, 2) }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </flux:card>

            @if ($purchase->notes || $purchase->admin_notes)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Notas</flux:heading>
                    <flux:separator class="my-4" />

                    @if ($purchase->notes)
                        <div class="mb-4">
                            <flux:text class="text-sm font-semibold text-gray-700 dark:text-gray-300">Notas Generales</flux:text>
                            <flux:text class="mt-1">{{ $purchase->notes }}</flux:text>
                        </div>
                    @endif

                    @if ($purchase->admin_notes)
                        <div>
                            <flux:text class="text-sm font-semibold text-gray-700 dark:text-gray-300">Notas Administrativas</flux:text>
                            <flux:text class="mt-1">{{ $purchase->admin_notes }}</flux:text>
                        </div>
                    @endif
                </flux:card>
            @endif
        </div>

        <div class="space-y-6">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Resumen</flux:heading>
                <flux:separator class="my-4" />

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <flux:text class="text-gray-600 dark:text-gray-400">Subtotal</flux:text>
                        <flux:text class="font-semibold">${{ number_format($purchase->subtotal, 2) }}</flux:text>
                    </div>

                    @if ($purchase->discount_amount > 0)
                        <div class="flex justify-between text-red-600 dark:text-red-400">
                            <flux:text>Descuento</flux:text>
                            <flux:text class="font-semibold">-${{ number_format($purchase->discount_amount, 2) }}</flux:text>
                        </div>
                    @endif

                    @if ($purchase->tax_amount > 0)
                        <div class="flex justify-between">
                            <flux:text class="text-gray-600 dark:text-gray-400">IVA</flux:text>
                            <flux:text class="font-semibold">${{ number_format($purchase->tax_amount, 2) }}</flux:text>
                        </div>
                    @endif

                    @if ($purchase->shipping_cost > 0)
                        <div class="flex justify-between">
                            <flux:text class="text-gray-600 dark:text-gray-400">Envío</flux:text>
                            <flux:text class="font-semibold">${{ number_format($purchase->shipping_cost, 2) }}</flux:text>
                        </div>
                    @endif

                    <flux:separator class="my-3" />

                    <div class="flex justify-between text-lg">
                        <flux:text class="font-bold">Total</flux:text>
                        <flux:text class="font-bold text-primary-600 dark:text-primary-400">${{ number_format($purchase->total, 2) }}</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-4">Workflow</flux:heading>
                <flux:separator class="my-4" />

                <div class="space-y-4">
                    <div>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Creado por</flux:text>
                        <flux:text class="font-semibold">{{ $purchase->creator->name ?? 'Sistema' }}</flux:text>
                        <flux:text class="text-xs text-gray-400">{{ $purchase->created_at->format('d/m/Y H:i') }}</flux:text>
                    </div>

                    @if ($purchase->approved_at)
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Aprobado por</flux:text>
                            <flux:text class="font-semibold">{{ $purchase->approver->name ?? 'N/A' }}</flux:text>
                            <flux:text class="text-xs text-gray-400">{{ $purchase->approved_at->format('d/m/Y H:i') }}</flux:text>
                        </div>
                    @endif

                    @if ($purchase->received_at)
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Recibido por</flux:text>
                            <flux:text class="font-semibold">{{ $purchase->receiver->name ?? 'N/A' }}</flux:text>
                            <flux:text class="text-xs text-gray-400">{{ $purchase->received_at->format('d/m/Y H:i') }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Submit Confirmation Modal --}}
    @can('submit', $purchase)
        <flux:modal name="submit-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Enviar Compra para Aprobación</flux:heading>
                    <flux:text class="mt-2">
                        <p>Está a punto de enviar esta compra para su aprobación.</p>
                        <p class="mt-1">Una vez enviada, no podrá editarla hasta que sea rechazada.</p>
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
    @endcan

    {{-- Approve Confirmation Modal --}}
    @can('approve', $purchase)
        <flux:modal name="approve-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Aprobar Compra</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Confirma que desea aprobar esta compra?</p>
                        <p class="mt-1">Una vez aprobada, la compra estará lista para ser recibida.</p>
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
    @endcan

    {{-- Receive Confirmation Modal --}}
    @can('receive', $purchase)
        <flux:modal name="receive-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Recibir Compra</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Confirma que ha recibido todos los productos de esta compra?</p>
                        <p class="mt-1">Esta acción actualizará el inventario con los productos recibidos.</p>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" wire:click="receive">Confirmar Recepción</flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan

    {{-- Cancel Confirmation Modal --}}
    @can('cancel', $purchase)
        <flux:modal name="cancel-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Cancelar Compra</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Está seguro de que desea cancelar esta compra?</p>
                        <p class="mt-1">Esta acción marcará la compra como cancelada.</p>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Volver</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="cancel">Cancelar Compra</flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan

    {{-- Delete Confirmation Modal --}}
    @can('delete', $purchase)
        <flux:modal name="delete-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Eliminar Compra</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Está seguro de que desea eliminar esta compra?</p>
                        <p class="mt-1 text-red-600 dark:text-red-400 font-medium">Esta acción no se puede deshacer.</p>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="delete">Eliminar</flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan
</div>
