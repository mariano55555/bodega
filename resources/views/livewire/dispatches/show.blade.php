<?php

use App\Models\Dispatch;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Dispatch $dispatch;

    public function mount(Dispatch $dispatch): void
    {
        $this->dispatch = $dispatch->load(['customer', 'warehouse', 'details.product', 'details.unitOfMeasure', 'approver', 'dispatcher', 'deliverer', 'creator']);
    }

    public function submit(): void
    {
        if ($this->dispatch->submit()) {
            session()->flash('success', 'Despacho enviado para aprobación.');
            $this->dispatch->refresh();
        } else {
            session()->flash('error', 'No se pudo enviar el despacho.');
        }
    }

    public function approve(): void
    {
        if ($this->dispatch->approve(auth()->id())) {
            session()->flash('success', 'Despacho aprobado exitosamente.');
            $this->dispatch->refresh();
        } else {
            session()->flash('error', 'No se pudo aprobar el despacho.');
        }
    }

    public function processDispatch(): void
    {
        if ($this->dispatch->dispatch(auth()->id())) {
            session()->flash('success', 'Despacho procesado exitosamente. Stock actualizado.');
            $this->dispatch->refresh();
        } else {
            session()->flash('error', 'No se pudo procesar el despacho.');
        }
    }

    public function deliver(): void
    {
        if ($this->dispatch->deliver(auth()->id(), auth()->user()->name)) {
            session()->flash('success', 'Despacho marcado como entregado.');
            $this->dispatch->refresh();
        } else {
            session()->flash('error', 'No se pudo marcar como entregado.');
        }
    }

    public function cancel(): void
    {
        if ($this->dispatch->cancel()) {
            session()->flash('success', 'Despacho cancelado exitosamente.');
            $this->dispatch->refresh();
        } else {
            session()->flash('error', 'No se pudo cancelar el despacho.');
        }
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl">{{ $dispatch->dispatch_number }}</flux:heading>
                <flux:badge size="lg" :variant="match($dispatch->status) {
                    'borrador' => 'neutral',
                    'pendiente' => 'warning',
                    'aprobado' => 'info',
                    'despachado' => 'purple',
                    'entregado' => 'success',
                    'cancelado' => 'danger',
                    default => 'neutral'
                }">
                    {{ $dispatch->getStatusSpanishAttribute() }}
                </flux:badge>
            </div>
            <flux:text class="mt-1">{{ $dispatch->getDispatchTypeSpanishAttribute() }} - {{ $dispatch->warehouse->name }}</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" href="{{ route('dispatches.index') }}" wire:navigate icon="arrow-left">
                Volver
            </flux:button>

            @if ($dispatch->canBeEdited())
                <flux:button variant="primary" icon="pencil" href="{{ route('dispatches.edit', $dispatch) }}" wire:navigate>
                    Editar
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
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

    {{-- Workflow Progress --}}
    @if ($dispatch->status !== 'cancelado')
        <flux:card class="!p-4">
            <div class="flex items-center justify-between">
                @php
                    $steps = [
                        ['key' => 'borrador', 'label' => 'Borrador', 'icon' => 'document-text'],
                        ['key' => 'pendiente', 'label' => 'Pendiente', 'icon' => 'clock'],
                        ['key' => 'aprobado', 'label' => 'Aprobado', 'icon' => 'check-circle'],
                        ['key' => 'despachado', 'label' => 'Despachado', 'icon' => 'truck'],
                        ['key' => 'entregado', 'label' => 'Entregado', 'icon' => 'check-badge'],
                    ];
                    $currentIndex = array_search($dispatch->status, array_column($steps, 'key'));
                @endphp

                @foreach ($steps as $index => $step)
                    <div class="flex items-center {{ $index < count($steps) - 1 ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 {{ $index <= $currentIndex ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 dark:border-gray-600 text-gray-400' }}">
                                <flux:icon :name="$step['icon']" class="w-5 h-5" />
                            </div>
                            <span class="mt-1 text-xs font-medium {{ $index <= $currentIndex ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400' }}">
                                {{ $step['label'] }}
                            </span>
                        </div>
                        @if ($index < count($steps) - 1)
                            <div class="flex-1 h-0.5 mx-2 {{ $index < $currentIndex ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </flux:card>
    @else
        <flux:callout variant="danger" icon="x-circle">
            Este despacho ha sido cancelado.
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- General Info --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Información General</flux:heading>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Número de Despacho</flux:text>
                        <flux:text class="mt-1 font-mono">{{ $dispatch->dispatch_number }}</flux:text>
                    </div>

                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Tipo de Despacho</flux:text>
                        <flux:text class="mt-1">{{ $dispatch->getDispatchTypeSpanishAttribute() }}</flux:text>
                    </div>

                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Bodega Origen</flux:text>
                        <flux:text class="mt-1">{{ $dispatch->warehouse->name }}</flux:text>
                    </div>

                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Fecha de Creación</flux:text>
                        <flux:text class="mt-1">{{ $dispatch->created_at->format('d/m/Y H:i') }}</flux:text>
                    </div>

                    @if ($dispatch->customer)
                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Cliente</flux:text>
                            <flux:text class="mt-1">{{ $dispatch->customer->name }}</flux:text>
                        </div>
                    @endif

                    @if ($dispatch->creator)
                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Creado por</flux:text>
                            <flux:text class="mt-1">{{ $dispatch->creator->name }}</flux:text>
                        </div>
                    @endif
                </div>

                @if ($dispatch->recipient_name || $dispatch->recipient_phone || $dispatch->recipient_email || $dispatch->delivery_address)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <flux:heading size="sm" class="mb-4">Información del Receptor</flux:heading>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @if ($dispatch->recipient_name)
                                <div>
                                    <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Nombre</flux:text>
                                    <flux:text class="mt-1">{{ $dispatch->recipient_name }}</flux:text>
                                </div>
                            @endif

                            @if ($dispatch->recipient_phone)
                                <div>
                                    <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Teléfono</flux:text>
                                    <flux:text class="mt-1">{{ $dispatch->recipient_phone }}</flux:text>
                                </div>
                            @endif

                            @if ($dispatch->recipient_email)
                                <div>
                                    <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Email</flux:text>
                                    <flux:text class="mt-1">{{ $dispatch->recipient_email }}</flux:text>
                                </div>
                            @endif

                            @if ($dispatch->delivery_address)
                                <div class="sm:col-span-2">
                                    <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Dirección de Entrega</flux:text>
                                    <flux:text class="mt-1">{{ $dispatch->delivery_address }}</flux:text>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </flux:card>

            {{-- Products --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Productos ({{ $dispatch->details->count() }})</flux:heading>

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Producto</flux:table.column>
                            <flux:table.column class="text-right">Cantidad</flux:table.column>
                            <flux:table.column class="text-right">Precio Unit.</flux:table.column>
                            <flux:table.column class="text-right">Subtotal</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($dispatch->details as $detail)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <div>
                                            <flux:text class="font-medium">{{ $detail->product->name }}</flux:text>
                                            @if ($detail->notes)
                                                <flux:text size="sm" class="text-gray-500">{{ $detail->notes }}</flux:text>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right">
                                        {{ number_format($detail->quantity, 2) }} {{ $detail->unitOfMeasure->abbreviation ?? $detail->unitOfMeasure->code }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right">
                                        ${{ number_format($detail->unit_price, 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right font-medium">
                                        ${{ number_format($detail->subtotal, 2) }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Totals --}}
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col items-end space-y-2">
                        <div class="flex justify-between w-full sm:w-64">
                            <flux:text class="text-gray-500">Subtotal:</flux:text>
                            <flux:text>${{ number_format($dispatch->subtotal, 2) }}</flux:text>
                        </div>
                        @if ($dispatch->tax_amount > 0)
                            <div class="flex justify-between w-full sm:w-64">
                                <flux:text class="text-gray-500">Impuesto:</flux:text>
                                <flux:text>${{ number_format($dispatch->tax_amount, 2) }}</flux:text>
                            </div>
                        @endif
                        @if ($dispatch->discount_amount > 0)
                            <div class="flex justify-between w-full sm:w-64">
                                <flux:text class="text-gray-500">Descuento:</flux:text>
                                <flux:text class="text-red-600">-${{ number_format($dispatch->discount_amount, 2) }}</flux:text>
                            </div>
                        @endif
                        @if ($dispatch->shipping_cost > 0)
                            <div class="flex justify-between w-full sm:w-64">
                                <flux:text class="text-gray-500">Envío:</flux:text>
                                <flux:text>${{ number_format($dispatch->shipping_cost, 2) }}</flux:text>
                            </div>
                        @endif
                        <div class="flex justify-between w-full sm:w-64 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <flux:text class="font-bold text-lg">Total:</flux:text>
                            <flux:text class="font-bold text-lg">${{ number_format($dispatch->total, 2) }}</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions Card --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Acciones</flux:heading>

                <div class="space-y-3">
                    @if ($dispatch->canBeSubmitted())
                        <flux:modal.trigger name="submit-modal">
                            <flux:button variant="primary" class="w-full" icon="paper-airplane">
                                Enviar para Aprobación
                            </flux:button>
                        </flux:modal.trigger>
                        <flux:text size="sm" class="text-gray-500 text-center">
                            El despacho será revisado antes de procesarse.
                        </flux:text>
                    @endif

                    @if ($dispatch->canBeApproved())
                        <flux:modal.trigger name="approve-modal">
                            <flux:button variant="primary" class="w-full" icon="check">
                                Aprobar Despacho
                            </flux:button>
                        </flux:modal.trigger>
                        <flux:text size="sm" class="text-gray-500 text-center">
                            Una vez aprobado, podrá ser despachado.
                        </flux:text>
                    @endif

                    @if ($dispatch->canBeDispatched())
                        <flux:modal.trigger name="process-dispatch-modal">
                            <flux:button variant="filled" class="w-full" icon="truck">
                                Procesar Despacho
                            </flux:button>
                        </flux:modal.trigger>
                        <flux:callout variant="warning" class="!p-2 !text-sm" icon="exclamation-triangle">
                            Esta acción descontará los productos del inventario.
                        </flux:callout>
                    @endif

                    @if ($dispatch->canBeDelivered())
                        <flux:modal.trigger name="deliver-modal">
                            <flux:button variant="primary" class="w-full" icon="check-badge">
                                Marcar como Entregado
                            </flux:button>
                        </flux:modal.trigger>
                    @endif

                    @if ($dispatch->canBeCancelled())
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <flux:modal.trigger name="cancel-modal">
                                <flux:button variant="danger" class="w-full" icon="x-mark">
                                    Cancelar Despacho
                                </flux:button>
                            </flux:modal.trigger>
                        </div>
                    @endif

                    @if ($dispatch->status === 'entregado')
                        <flux:callout variant="success" icon="check-badge">
                            Despacho completado exitosamente.
                        </flux:callout>
                    @endif

                    @if ($dispatch->status === 'cancelado')
                        <flux:callout variant="danger" icon="x-circle">
                            Este despacho fue cancelado.
                        </flux:callout>
                    @endif
                </div>
            </flux:card>

            {{-- Timeline Card --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Historial</flux:heading>

                <div class="space-y-4">
                    {{-- Created --}}
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                            <flux:icon name="plus" class="w-4 h-4 text-gray-500" />
                        </div>
                        <div>
                            <flux:text size="sm" class="font-medium">Creado</flux:text>
                            <flux:text size="sm" class="text-gray-500">
                                {{ $dispatch->created_at->format('d/m/Y H:i') }}
                                @if ($dispatch->creator)
                                    por {{ $dispatch->creator->name }}
                                @endif
                            </flux:text>
                        </div>
                    </div>

                    {{-- Approved --}}
                    @if ($dispatch->approved_at)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                <flux:icon name="check" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <flux:text size="sm" class="font-medium">Aprobado</flux:text>
                                <flux:text size="sm" class="text-gray-500">
                                    {{ $dispatch->approved_at->format('d/m/Y H:i') }}
                                    @if ($dispatch->approver)
                                        por {{ $dispatch->approver->name }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endif

                    {{-- Dispatched --}}
                    @if ($dispatch->dispatched_at)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                                <flux:icon name="truck" class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <flux:text size="sm" class="font-medium">Despachado</flux:text>
                                <flux:text size="sm" class="text-gray-500">
                                    {{ $dispatch->dispatched_at->format('d/m/Y H:i') }}
                                    @if ($dispatch->dispatcher)
                                        por {{ $dispatch->dispatcher->name }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endif

                    {{-- Delivered --}}
                    @if ($dispatch->delivered_at)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                <flux:icon name="check-badge" class="w-4 h-4 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <flux:text size="sm" class="font-medium">Entregado</flux:text>
                                <flux:text size="sm" class="text-gray-500">
                                    {{ $dispatch->delivered_at->format('d/m/Y H:i') }}
                                    @if ($dispatch->received_by_name)
                                        - Recibido por {{ $dispatch->received_by_name }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Notes --}}
            @if ($dispatch->notes)
                <flux:card>
                    <flux:heading size="lg" class="mb-2">Notas</flux:heading>
                    <flux:text class="text-gray-600 dark:text-gray-400">{{ $dispatch->notes }}</flux:text>
                </flux:card>
            @endif
        </div>
    </div>

    {{-- Submit Confirmation Modal --}}
    @if ($dispatch->canBeSubmitted())
        <flux:modal name="submit-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Enviar Despacho para Aprobación</flux:heading>
                    <flux:text class="mt-2">
                        <p>Está a punto de enviar este despacho para su aprobación.</p>
                        <p class="mt-1">Una vez enviado, pasará a estado pendiente de revisión.</p>
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

    {{-- Approve Confirmation Modal --}}
    @if ($dispatch->canBeApproved())
        <flux:modal name="approve-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Aprobar Despacho</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Confirma que desea aprobar este despacho?</p>
                        <p class="mt-1">Una vez aprobado, el despacho estará listo para ser procesado.</p>
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

    {{-- Process Dispatch Confirmation Modal --}}
    @if ($dispatch->canBeDispatched())
        <flux:modal name="process-dispatch-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Procesar Despacho</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Confirma que desea procesar este despacho?</p>
                        <p class="mt-1 font-semibold text-amber-600 dark:text-amber-400">Esta acción descontará los productos del inventario.</p>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" wire:click="processDispatch">Procesar Despacho</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Deliver Confirmation Modal --}}
    @if ($dispatch->canBeDelivered())
        <flux:modal name="deliver-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Marcar como Entregado</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Confirma que el despacho ha sido entregado?</p>
                        <p class="mt-1">Esta acción finalizará el proceso de despacho.</p>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" wire:click="deliver">Confirmar Entrega</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Cancel Confirmation Modal --}}
    @if ($dispatch->canBeCancelled())
        <flux:modal name="cancel-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Cancelar Despacho</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Está seguro de que desea cancelar este despacho?</p>
                        <p class="mt-1 font-semibold text-red-600 dark:text-red-400">Esta acción no se puede deshacer.</p>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Volver</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="cancel">Cancelar Despacho</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
