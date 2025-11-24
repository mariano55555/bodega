<?php

use App\Models\InventoryTransfer;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public InventoryTransfer $transfer;
    public $approvalNotes = '';
    public $trackingNumber = '';
    public $carrier = '';
    public $receivingNotes = '';
    public $discrepancies = [];

    public function mount(InventoryTransfer $transfer): void
    {
        $this->transfer = $transfer->load([
            'fromWarehouse',
            'toWarehouse',
            'details.product.unitOfMeasure',
            'requestedBy',
            'approvedBy',
            'shippedBy',
            'receivedBy',
            'creator',
        ]);

        // Initialize discrepancies array for receiving
        foreach ($this->transfer->details as $detail) {
            $this->discrepancies[] = [
                'product_id' => $detail->product_id,
                'expected' => $detail->quantity,
                'received' => $detail->quantity,
                'reason' => '',
            ];
        }
    }

    public function approve(): void
    {
        if ($this->transfer->approve(auth()->id(), $this->approvalNotes)) {
            \Flux::toast(variant: 'success', text: 'Traslado aprobado exitosamente.');
            $this->modal('approve-modal')->close();
            $this->transfer->refresh();
        } else {
            \Flux::toast(variant: 'danger', text: 'No se puede aprobar el traslado en su estado actual.');
        }
    }

    public function ship(): void
    {
        if ($this->transfer->ship(auth()->id(), $this->trackingNumber, $this->carrier)) {
            \Flux::toast(variant: 'success', text: 'Traslado enviado exitosamente. Se han creado los movimientos de inventario.');
            $this->modal('ship-modal')->close();
            $this->transfer->refresh();
        } else {
            \Flux::toast(variant: 'danger', text: 'No se puede enviar el traslado en su estado actual.');
        }
    }

    public function receive(): void
    {
        // Filter discrepancies that have a reason (only include actual discrepancies)
        $actualDiscrepancies = array_filter($this->discrepancies, function ($disc) {
            return $disc['expected'] != $disc['received'] && !empty($disc['reason']);
        });

        if ($this->transfer->receive(auth()->id(), $actualDiscrepancies ?: null, $this->receivingNotes)) {
            \Flux::toast(variant: 'success', text: 'Traslado recibido exitosamente. Se han actualizado los inventarios.');
            $this->modal('receive-modal')->close();
            $this->transfer->refresh();
        } else {
            \Flux::toast(variant: 'danger', text: 'No se puede recibir el traslado en su estado actual.');
        }
    }

    public function cancel(): void
    {
        if ($this->transfer->cancel()) {
            \Flux::toast(variant: 'success', text: 'Traslado cancelado exitosamente.');
            $this->transfer->refresh();
        } else {
            \Flux::toast(variant: 'danger', text: 'No se puede cancelar el traslado en su estado actual.');
        }
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl">Traslado {{ $transfer->transfer_number }}</flux:heading>
                @php
                    $statusColors = [
                        'draft' => 'zinc',
                        'pending' => 'yellow',
                        'approved' => 'blue',
                        'in_transit' => 'purple',
                        'received' => 'cyan',
                        'completed' => 'green',
                        'cancelled' => 'red',
                        // Spanish fallbacks
                        'pendiente' => 'yellow',
                        'aprobado' => 'blue',
                        'en_transito' => 'purple',
                        'recibido' => 'cyan',
                        'completado' => 'green',
                        'cancelado' => 'red',
                    ];
                    $statusLabels = [
                        'draft' => 'Borrador',
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'in_transit' => 'En Tránsito',
                        'received' => 'Recibido',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        // Spanish fallbacks
                        'pendiente' => 'Pendiente',
                        'aprobado' => 'Aprobado',
                        'en_transito' => 'En Tránsito',
                        'recibido' => 'Recibido',
                        'completado' => 'Completado',
                        'cancelado' => 'Cancelado',
                    ];
                @endphp
                <flux:badge :color="$statusColors[$transfer->status] ?? 'zinc'" size="lg">
                    {{ $statusLabels[$transfer->status] ?? ucfirst($transfer->status) }}
                </flux:badge>
            </div>
            <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                <span class="inline-flex items-center gap-2">
                    <flux:icon name="building-office-2" class="w-4 h-4" />
                    {{ $transfer->fromWarehouse->name }}
                    <flux:icon name="arrow-right" class="w-4 h-4" />
                    {{ $transfer->toWarehouse->name }}
                </span>
            </flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('update', $transfer)
                @if (in_array($transfer->status, ['pending', 'pendiente', 'draft']))
                    <flux:button variant="outline" icon="pencil" href="{{ route('transfers.edit', $transfer) }}" wire:navigate>
                        Editar
                    </flux:button>
                @endif
            @endcan

            @can('approve', $transfer)
                @if (in_array($transfer->status, ['pending', 'pendiente']))
                    <flux:modal.trigger name="approve-modal">
                        <flux:button variant="primary" icon="check">
                            Aprobar
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            @endcan

            @can('ship', $transfer)
                @if (in_array($transfer->status, ['approved', 'aprobado']))
                    <flux:modal.trigger name="ship-modal">
                        <flux:button variant="primary" icon="truck">
                            Enviar
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            @endcan

            @can('receive', $transfer)
                @if (in_array($transfer->status, ['in_transit', 'en_transito']))
                    <flux:modal.trigger name="receive-modal">
                        <flux:button variant="filled" icon="check-circle" class="bg-green-600 hover:bg-green-700">
                            Recibir
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            @endcan

            @can('cancel', $transfer)
                @if (in_array($transfer->status, ['pending', 'pendiente', 'approved', 'aprobado']))
                    <flux:button variant="danger" icon="x-circle" wire:click="cancel" wire:confirm="¿Está seguro de cancelar este traslado?">
                        Cancelar
                    </flux:button>
                @endif
            @endcan

            <flux:button variant="ghost" icon="arrow-left" href="{{ route('transfers.index') }}" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Transfer Information -->
            <flux:card>
                <div class="p-1">
                    <flux:heading size="lg">Información del Traslado</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400">Detalles generales del traslado</flux:text>
                </div>

                <flux:separator class="my-4" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 p-1">
                    <div class="space-y-1">
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Número de Traslado</flux:text>
                        <flux:text class="text-base font-semibold">{{ $transfer->transfer_number }}</flux:text>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Fecha de Solicitud</flux:text>
                        <flux:text class="text-base font-semibold">{{ $transfer->requested_at?->format('d/m/Y H:i') ?? $transfer->created_at->format('d/m/Y H:i') }}</flux:text>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Bodega de Origen</flux:text>
                        <div class="flex items-center gap-2">
                            <flux:icon name="building-office-2" class="w-5 h-5 text-red-500" />
                            <flux:text class="text-base font-semibold">{{ $transfer->fromWarehouse->name }}</flux:text>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Bodega de Destino</flux:text>
                        <div class="flex items-center gap-2">
                            <flux:icon name="building-office-2" class="w-5 h-5 text-green-500" />
                            <flux:text class="text-base font-semibold">{{ $transfer->toWarehouse->name }}</flux:text>
                        </div>
                    </div>

                    @if ($transfer->tracking_number)
                        <div class="space-y-1">
                            <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Número de Seguimiento</flux:text>
                            <div class="flex items-center gap-2">
                                <flux:icon name="map-pin" class="w-5 h-5 text-purple-500" />
                                <flux:text class="text-base font-semibold">{{ $transfer->tracking_number }}</flux:text>
                            </div>
                        </div>
                    @endif

                    @if ($transfer->carrier)
                        <div class="space-y-1">
                            <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Transportista</flux:text>
                            <div class="flex items-center gap-2">
                                <flux:icon name="truck" class="w-5 h-5 text-blue-500" />
                                <flux:text class="text-base font-semibold">{{ $transfer->carrier }}</flux:text>
                            </div>
                        </div>
                    @endif

                    @if ($transfer->shipping_cost > 0)
                        <div class="space-y-1">
                            <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Costo de Envío</flux:text>
                            <flux:text class="text-base font-semibold text-green-600">${{ number_format($transfer->shipping_cost, 2) }}</flux:text>
                        </div>
                    @endif

                    @if ($transfer->reason)
                        <div class="space-y-1 sm:col-span-2">
                            <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Motivo del Traslado</flux:text>
                            <flux:text class="text-base">{{ $transfer->reason }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Products -->
            <flux:card>
                <div class="p-1">
                    <flux:heading size="lg">Productos a Trasladar</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        {{ $transfer->details->count() }} {{ $transfer->details->count() === 1 ? 'producto' : 'productos' }} en este traslado
                    </flux:text>
                </div>

                <flux:separator class="my-4" />

                @if ($transfer->details->count() > 0)
                    <div class="overflow-x-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Producto</flux:table.column>
                                <flux:table.column class="text-right">Cantidad</flux:table.column>
                                <flux:table.column>Notas</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($transfer->details as $detail)
                                    <flux:table.row :key="$detail->id">
                                        <flux:table.cell>
                                            <div>
                                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $detail->product->name }}</div>
                                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $detail->product->sku }}</div>
                                            </div>
                                        </flux:table.cell>

                                        <flux:table.cell class="text-right">
                                            <span class="font-semibold">{{ number_format($detail->quantity, 2) }}</span>
                                            <span class="text-zinc-500 dark:text-zinc-400 ml-1">{{ $detail->product->unitOfMeasure?->abbreviation ?? 'unid.' }}</span>
                                        </flux:table.cell>

                                        <flux:table.cell>
                                            @if ($detail->notes)
                                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ $detail->notes }}</flux:text>
                                            @else
                                                <flux:text class="text-sm text-zinc-400">-</flux:text>
                                            @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <flux:icon name="cube" class="h-12 w-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" />
                        <flux:text class="text-zinc-500 dark:text-zinc-400">No hay productos en este traslado</flux:text>
                    </div>
                @endif
            </flux:card>

            <!-- Notes Section -->
            @if ($transfer->notes || $transfer->approval_notes || $transfer->receiving_notes)
                <flux:card>
                    <div class="p-1">
                        <flux:heading size="lg">Notas y Observaciones</flux:heading>
                    </div>

                    <flux:separator class="my-4" />

                    <div class="space-y-4 p-1">
                        @if ($transfer->notes)
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg">
                                <flux:text class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Notas Generales</flux:text>
                                <flux:text class="text-zinc-600 dark:text-zinc-400">{{ $transfer->notes }}</flux:text>
                            </div>
                        @endif

                        @if ($transfer->approval_notes)
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                <flux:text class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-2">Notas de Aprobación</flux:text>
                                <flux:text class="text-blue-600 dark:text-blue-400">{{ $transfer->approval_notes }}</flux:text>
                            </div>
                        @endif

                        @if ($transfer->receiving_notes)
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <flux:text class="text-sm font-semibold text-green-700 dark:text-green-300 mb-2">Notas de Recepción</flux:text>
                                <flux:text class="text-green-600 dark:text-green-400">{{ $transfer->receiving_notes }}</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif

            <!-- Discrepancies -->
            @if ($transfer->receiving_discrepancies && count($transfer->receiving_discrepancies) > 0)
                <flux:card>
                    <div class="p-1">
                        <flux:heading size="lg">Discrepancias en Recepción</flux:heading>
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Diferencias encontradas al recibir el traslado</flux:text>
                    </div>

                    <flux:separator class="my-4" />

                    <div class="space-y-3 p-1">
                        @foreach ($transfer->receiving_discrepancies as $discrepancy)
                            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:icon name="exclamation-triangle" class="w-5 h-5 text-amber-500" />
                                    <flux:text class="font-medium text-amber-700 dark:text-amber-300">Producto ID: {{ $discrepancy['product_id'] }}</flux:text>
                                </div>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-zinc-500 dark:text-zinc-400">Esperado:</span>
                                        <span class="font-semibold ml-1">{{ $discrepancy['expected'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-zinc-500 dark:text-zinc-400">Recibido:</span>
                                        <span class="font-semibold ml-1">{{ $discrepancy['received'] }}</span>
                                    </div>
                                </div>
                                @if (!empty($discrepancy['reason']))
                                    <flux:text class="text-sm text-amber-600 dark:text-amber-400 mt-2">
                                        <strong>Razón:</strong> {{ $discrepancy['reason'] }}
                                    </flux:text>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Workflow Card -->
            <flux:card>
                <div class="p-1">
                    <flux:heading size="lg">Responsables</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400">Personas involucradas</flux:text>
                </div>

                <flux:separator class="my-4" />

                <div class="space-y-5 p-1">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                            <flux:icon name="user" class="w-5 h-5 text-zinc-500" />
                        </div>
                        <div class="flex-1">
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Solicitado por</flux:text>
                            <flux:text class="font-semibold">{{ $transfer->requestedBy->name ?? 'Sistema' }}</flux:text>
                            <flux:text class="text-xs text-zinc-400">{{ $transfer->requested_at?->format('d/m/Y H:i') }}</flux:text>
                        </div>
                    </div>

                    @if ($transfer->approved_at)
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                <flux:icon name="check" class="w-5 h-5 text-blue-500" />
                            </div>
                            <div class="flex-1">
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Aprobado por</flux:text>
                                <flux:text class="font-semibold">{{ $transfer->approvedBy->name ?? 'N/A' }}</flux:text>
                                <flux:text class="text-xs text-zinc-400">{{ $transfer->approved_at->format('d/m/Y H:i') }}</flux:text>
                            </div>
                        </div>
                    @endif

                    @if ($transfer->shipped_at)
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                <flux:icon name="truck" class="w-5 h-5 text-purple-500" />
                            </div>
                            <div class="flex-1">
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Enviado por</flux:text>
                                <flux:text class="font-semibold">{{ $transfer->shippedBy->name ?? 'N/A' }}</flux:text>
                                <flux:text class="text-xs text-zinc-400">{{ $transfer->shipped_at->format('d/m/Y H:i') }}</flux:text>
                            </div>
                        </div>
                    @endif

                    @if ($transfer->received_at)
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                <flux:icon name="check-circle" class="w-5 h-5 text-green-500" />
                            </div>
                            <div class="flex-1">
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Recibido por</flux:text>
                                <flux:text class="font-semibold">{{ $transfer->receivedBy->name ?? 'N/A' }}</flux:text>
                                <flux:text class="text-xs text-zinc-400">{{ $transfer->received_at->format('d/m/Y H:i') }}</flux:text>
                            </div>
                        </div>
                    @endif

                    @if ($transfer->cancelled_at)
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                <flux:icon name="x-circle" class="w-5 h-5 text-red-500" />
                            </div>
                            <div class="flex-1">
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Cancelado</flux:text>
                                <flux:text class="text-xs text-zinc-400">{{ $transfer->cancelled_at->format('d/m/Y H:i') }}</flux:text>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Status Timeline -->
            <flux:card>
                <div class="p-1">
                    <flux:heading size="lg">Historial de Estados</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400">Progreso del traslado</flux:text>
                </div>

                <flux:separator class="my-4" />

                <div class="relative p-1">
                    <!-- Timeline line -->
                    <div class="absolute left-[11px] top-2 bottom-2 w-0.5 bg-zinc-200 dark:bg-zinc-700"></div>

                    <div class="space-y-6 relative">
                        <!-- Pending/Created -->
                        <div class="flex items-start gap-4">
                            <div class="relative z-10 w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                <flux:icon name="plus" class="w-3 h-3 text-white" />
                            </div>
                            <div class="flex-1 pt-0.5">
                                <flux:text class="text-sm font-medium">Creado</flux:text>
                                <flux:text class="text-xs text-zinc-400">{{ $transfer->created_at->format('d/m/Y H:i') }}</flux:text>
                            </div>
                        </div>

                        @if ($transfer->approved_at)
                            <div class="flex items-start gap-4">
                                <div class="relative z-10 w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center">
                                    <flux:icon name="check" class="w-3 h-3 text-white" />
                                </div>
                                <div class="flex-1 pt-0.5">
                                    <flux:text class="text-sm font-medium">Aprobado</flux:text>
                                    <flux:text class="text-xs text-zinc-400">{{ $transfer->approved_at->format('d/m/Y H:i') }}</flux:text>
                                </div>
                            </div>
                        @endif

                        @if ($transfer->shipped_at)
                            <div class="flex items-start gap-4">
                                <div class="relative z-10 w-6 h-6 rounded-full bg-purple-500 flex items-center justify-center">
                                    <flux:icon name="truck" class="w-3 h-3 text-white" />
                                </div>
                                <div class="flex-1 pt-0.5">
                                    <flux:text class="text-sm font-medium">En Tránsito</flux:text>
                                    <flux:text class="text-xs text-zinc-400">{{ $transfer->shipped_at->format('d/m/Y H:i') }}</flux:text>
                                </div>
                            </div>
                        @endif

                        @if ($transfer->received_at)
                            <div class="flex items-start gap-4">
                                <div class="relative z-10 w-6 h-6 rounded-full bg-green-600 flex items-center justify-center">
                                    <flux:icon name="check-circle" class="w-3 h-3 text-white" />
                                </div>
                                <div class="flex-1 pt-0.5">
                                    <flux:text class="text-sm font-medium">Recibido</flux:text>
                                    <flux:text class="text-xs text-zinc-400">{{ $transfer->received_at->format('d/m/Y H:i') }}</flux:text>
                                </div>
                            </div>
                        @endif

                        @if ($transfer->completed_at && !$transfer->received_at)
                            <div class="flex items-start gap-4">
                                <div class="relative z-10 w-6 h-6 rounded-full bg-green-600 flex items-center justify-center">
                                    <flux:icon name="check-circle" class="w-3 h-3 text-white" />
                                </div>
                                <div class="flex-1 pt-0.5">
                                    <flux:text class="text-sm font-medium">Completado</flux:text>
                                    <flux:text class="text-xs text-zinc-400">{{ $transfer->completed_at->format('d/m/Y H:i') }}</flux:text>
                                </div>
                            </div>
                        @endif

                        @if ($transfer->cancelled_at)
                            <div class="flex items-start gap-4">
                                <div class="relative z-10 w-6 h-6 rounded-full bg-red-500 flex items-center justify-center">
                                    <flux:icon name="x-mark" class="w-3 h-3 text-white" />
                                </div>
                                <div class="flex-1 pt-0.5">
                                    <flux:text class="text-sm font-medium text-red-600 dark:text-red-400">Cancelado</flux:text>
                                    <flux:text class="text-xs text-zinc-400">{{ $transfer->cancelled_at->format('d/m/Y H:i') }}</flux:text>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    <!-- Approve Modal -->
    <flux:modal name="approve-modal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Aprobar Traslado</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    ¿Confirma que desea aprobar este traslado de {{ $transfer->fromWarehouse->name }} a {{ $transfer->toWarehouse->name }}?
                </flux:text>
            </div>

            <flux:field>
                <flux:label>Notas de Aprobación (Opcional)</flux:label>
                <flux:textarea wire:model="approvalNotes" rows="3" placeholder="Observaciones sobre la aprobación..." />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="approve">
                    Aprobar Traslado
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Ship Modal -->
    <flux:modal name="ship-modal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Enviar Traslado</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Registre la información de envío para este traslado.
                </flux:text>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Número de Seguimiento</flux:label>
                    <flux:input wire:model="trackingNumber" placeholder="Ej: ABC123456789" />
                </flux:field>

                <flux:field>
                    <flux:label>Transportista</flux:label>
                    <flux:input wire:model="carrier" placeholder="Ej: DHL, FedEx, UPS, Interno" />
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="ship">
                    Confirmar Envío
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Receive Modal -->
    <flux:modal name="receive-modal" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Recibir Traslado</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Verifique las cantidades recibidas y registre cualquier discrepancia.
                </flux:text>
            </div>

            <div class="space-y-4">
                @foreach ($transfer->details as $index => $detail)
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-2 mb-3">
                            <flux:icon name="cube" class="w-5 h-5 text-zinc-400" />
                            <flux:text class="font-medium">{{ $detail->product->name }}</flux:text>
                            <flux:badge color="zinc" size="sm">{{ $detail->product->sku }}</flux:badge>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <flux:field>
                                <flux:label>Esperado</flux:label>
                                <flux:input type="number" step="0.01" wire:model="discrepancies.{{ $index }}.expected" readonly class="bg-zinc-100 dark:bg-zinc-700" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Recibido</flux:label>
                                <flux:input type="number" step="0.01" wire:model="discrepancies.{{ $index }}.received" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Razón (si difiere)</flux:label>
                                <flux:input wire:model="discrepancies.{{ $index }}.reason" placeholder="Ej: Dañado, Faltante" />
                            </flux:field>
                        </div>
                    </div>
                @endforeach

                <flux:field>
                    <flux:label>Notas de Recepción</flux:label>
                    <flux:textarea wire:model="receivingNotes" rows="3" placeholder="Observaciones sobre la recepción..." />
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="filled" class="bg-green-600 hover:bg-green-700" wire:click="receive">
                    Confirmar Recepción
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
