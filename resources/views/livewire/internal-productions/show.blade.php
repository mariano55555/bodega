<?php

use App\Models\InternalProduction;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public InternalProduction $internalProduction;
    public bool $createAutoDispatch = true;
    public string $dispatchDocumentNumber = '';

    public function mount(InternalProduction $internalProduction): void
    {
        $this->internalProduction = $internalProduction->load([
            'area',
            'warehouse',
            'employee',
            'details.product',
            'details.unitOfMeasure',
            'approver',
            'completer',
            'creator',
        ]);
    }

    public function submit(): void
    {
        if ($this->internalProduction->submit()) {
            session()->flash('success', 'Producción enviada para aprobación.');
            $this->internalProduction->refresh();
        } else {
            session()->flash('error', 'No se pudo enviar la producción.');
        }
    }

    public function approve(): void
    {
        if ($this->internalProduction->approve(auth()->id())) {
            session()->flash('success', 'Producción aprobada exitosamente.');
            $this->internalProduction->refresh();
        } else {
            session()->flash('error', 'No se pudo aprobar la producción.');
        }
    }

    public function complete(): void
    {
        // Validate dispatch document number if auto dispatch is enabled
        if ($this->createAutoDispatch) {
            $this->validate([
                'dispatchDocumentNumber' => 'required|string|max:100|unique:dispatches,physical_document_number',
            ], [], [
                'dispatchDocumentNumber' => 'número de documento del despacho',
            ]);
        }

        if ($this->internalProduction->complete(auth()->id())) {
            $message = 'Producción completada exitosamente. Stock actualizado.';

            // Create automatic dispatch if checkbox is checked
            if ($this->createAutoDispatch) {
                $dispatch = $this->internalProduction->createAutoDispatch(auth()->id(), $this->dispatchDocumentNumber);
                if ($dispatch) {
                    $message .= ' Despacho '.$dispatch->dispatch_number.' creado automáticamente.';
                } else {
                    $message .= ' Error al crear el despacho automático.';
                }
            }

            // Close modal on success
            $this->js("\$flux.modal('complete-modal').close()");

            session()->flash('success', $message);
            $this->internalProduction->refresh();
        } else {
            session()->flash('error', 'No se pudo completar la producción.');
        }
    }

    public function cancel(): void
    {
        if ($this->internalProduction->cancel()) {
            session()->flash('success', 'Producción anulada exitosamente.');
            $this->internalProduction->refresh();
        } else {
            session()->flash('error', 'No se pudo anular la producción.');
        }
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl">{{ $internalProduction->production_number }}</flux:heading>
                <flux:badge size="lg" :variant="match($internalProduction->status) {
                    'borrador' => 'neutral',
                    'pendiente' => 'warning',
                    'aprobado' => 'info',
                    'completado' => 'success',
                    'cancelado' => 'danger',
                    default => 'neutral'
                }">
                    {{ $internalProduction->getStatusSpanishAttribute() }}
                </flux:badge>
            </div>
            <flux:text class="mt-1">{{ $internalProduction->area->name ?? 'N/A' }} → {{ $internalProduction->warehouse->name ?? 'N/A' }}</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" href="{{ route('internal-productions.index') }}" wire:navigate icon="arrow-left">
                Volver
            </flux:button>

            @if ($internalProduction->status === 'completado')
                <flux:button variant="primary" href="{{ route('internal-productions.create') }}" wire:navigate icon="plus">
                    Nueva Producción
                </flux:button>
            @endif

            @if ($internalProduction->canBeEdited())
                <flux:button variant="primary" icon="pencil" href="{{ route('internal-productions.edit', $internalProduction) }}" wire:navigate>
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
    @if ($internalProduction->status !== 'cancelado')
        <flux:card class="!p-4">
            <div class="flex items-center justify-between">
                @php
                    $steps = [
                        ['key' => 'borrador', 'label' => 'Borrador', 'icon' => 'document-text'],
                        ['key' => 'pendiente', 'label' => 'Pendiente', 'icon' => 'clock'],
                        ['key' => 'aprobado', 'label' => 'Aprobado', 'icon' => 'check-circle'],
                        ['key' => 'completado', 'label' => 'Completado', 'icon' => 'check-badge'],
                    ];
                    $currentIndex = array_search($internalProduction->status, array_column($steps, 'key'));
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
            Esta producción ha sido anulada.
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
                        <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Número de Producción</flux:text>
                        <flux:text class="mt-1 font-mono">{{ $internalProduction->production_number }}</flux:text>
                    </div>

                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Número Documento Físico</flux:text>
                        <flux:text class="mt-1">{{ $internalProduction->physical_document_number }}</flux:text>
                    </div>

                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Unidad de Origen</flux:text>
                        <flux:text class="mt-1">{{ $internalProduction->area->name ?? 'N/A' }}</flux:text>
                    </div>

                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Bodega Destino</flux:text>
                        <flux:text class="mt-1">{{ $internalProduction->warehouse->name ?? 'N/A' }}</flux:text>
                    </div>

                    @if ($internalProduction->employee)
                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Persona que Entrega</flux:text>
                            <flux:text class="mt-1">{{ $internalProduction->employee->name }}</flux:text>
                            @if ($internalProduction->employee->position)
                                <flux:text size="sm" class="text-gray-500">({{ $internalProduction->employee->position }})</flux:text>
                            @endif
                        </div>
                    @endif

                    @if ($internalProduction->document_date)
                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Fecha del Documento</flux:text>
                            <flux:text class="mt-1">{{ $internalProduction->document_date->format('d/m/Y') }}</flux:text>
                        </div>
                    @endif

                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Fecha de Creación</flux:text>
                        <flux:text class="mt-1">{{ $internalProduction->created_at->format('d/m/Y H:i') }}</flux:text>
                    </div>

                    @if ($internalProduction->creator)
                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Creado por</flux:text>
                            <flux:text class="mt-1">{{ $internalProduction->creator->name }}</flux:text>
                        </div>
                    @endif
                </div>

                @if ($internalProduction->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Notas</flux:text>
                        <flux:text class="mt-1">{{ $internalProduction->notes }}</flux:text>
                    </div>
                @endif
            </flux:card>

            {{-- Products --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Productos ({{ $internalProduction->details->count() }})</flux:heading>

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Producto</flux:table.column>
                            <flux:table.column>Descripción</flux:table.column>
                            <flux:table.column class="text-right">Cantidad</flux:table.column>
                            <flux:table.column class="text-right">Precio Unit.</flux:table.column>
                            <flux:table.column class="text-right">Total</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($internalProduction->details as $detail)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <div>
                                            <flux:text class="font-medium">{{ $detail->product->name }}</flux:text>
                                            @if ($detail->notes)
                                                <flux:text size="sm" class="text-gray-500">{{ $detail->notes }}</flux:text>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $detail->description ?? '-' }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right">
                                        {{ number_format($detail->quantity, 5) }} {{ $detail->unitOfMeasure->abbreviation ?? '' }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right">
                                        ${{ number_format($detail->unit_price, 5) }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right font-medium">
                                        ${{ number_format($detail->total, 5) }}
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
                            <flux:text>${{ number_format($internalProduction->subtotal, 5) }}</flux:text>
                        </div>
                        <div class="flex justify-between w-full sm:w-64 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <flux:text class="font-bold text-lg">Total:</flux:text>
                            <flux:text class="font-bold text-lg">${{ number_format($internalProduction->total, 5) }}</flux:text>
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
                    @if ($internalProduction->canBeSubmitted())
                        <flux:modal.trigger name="submit-modal">
                            <flux:button variant="primary" class="w-full" icon="paper-airplane">
                                Enviar para Aprobación
                            </flux:button>
                        </flux:modal.trigger>
                        <flux:text size="sm" class="text-gray-500 text-center">
                            La producción será revisada antes de procesarse.
                        </flux:text>
                    @endif

                    @if ($internalProduction->canBeApproved())
                        <flux:modal.trigger name="approve-modal">
                            <flux:button variant="primary" class="w-full" icon="check">
                                Aprobar Producción
                            </flux:button>
                        </flux:modal.trigger>
                        <flux:text size="sm" class="text-gray-500 text-center">
                            Una vez aprobada, podrá ser completada.
                        </flux:text>
                    @endif

                    @if ($internalProduction->canBeCompleted())
                        <flux:modal.trigger name="complete-modal">
                            <flux:button variant="filled" class="w-full" icon="check-badge">
                                Completar Producción
                            </flux:button>
                        </flux:modal.trigger>
                        <flux:callout variant="warning" class="!p-2 !text-sm" icon="exclamation-triangle">
                            Esta acción agregará los productos al inventario de la bodega destino.
                        </flux:callout>
                    @endif

                    @if ($internalProduction->canBeCancelled())
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <flux:modal.trigger name="cancel-modal">
                                <flux:button variant="danger" class="w-full" icon="x-mark">
                                    Anular Producción
                                </flux:button>
                            </flux:modal.trigger>
                        </div>
                    @endif

                    @if ($internalProduction->status === 'completado')
                        <flux:callout variant="success" icon="check-badge">
                            Producción completada exitosamente.
                        </flux:callout>
                    @endif

                    @if ($internalProduction->status === 'cancelado')
                        <flux:callout variant="danger" icon="x-circle">
                            Esta producción fue anulada.
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
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                            <flux:icon name="plus" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <flux:text size="sm" class="font-medium">Creado</flux:text>
                            <flux:text size="sm" class="text-gray-500">
                                {{ $internalProduction->created_at->format('d/m/Y H:i') }}
                                @if ($internalProduction->creator)
                                    por {{ $internalProduction->creator->name }}
                                @endif
                            </flux:text>
                        </div>
                    </div>

                    {{-- Approved --}}
                    @if ($internalProduction->approved_at)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                <flux:icon name="check" class="w-4 h-4 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <flux:text size="sm" class="font-medium">Aprobado</flux:text>
                                <flux:text size="sm" class="text-gray-500">
                                    {{ $internalProduction->approved_at->format('d/m/Y H:i') }}
                                    @if ($internalProduction->approver)
                                        por {{ $internalProduction->approver->name }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endif

                    {{-- Completed --}}
                    @if ($internalProduction->completed_at)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-emerald-100 dark:bg-emerald-900 rounded-full flex items-center justify-center">
                                <flux:icon name="check-badge" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <flux:text size="sm" class="font-medium">Completado</flux:text>
                                <flux:text size="sm" class="text-gray-500">
                                    {{ $internalProduction->completed_at->format('d/m/Y H:i') }}
                                    @if ($internalProduction->completer)
                                        por {{ $internalProduction->completer->name }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Confirmation Modals --}}
    <flux:modal name="submit-modal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Confirmar Envío</flux:heading>
            <flux:text>¿Está seguro de enviar esta producción para aprobación?</flux:text>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('submit-modal').close()">
                    Cancelar
                </flux:button>
                <flux:button variant="primary" wire:click="submit" x-on:click="$flux.modal('submit-modal').close()">
                    Enviar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="approve-modal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Confirmar Aprobación</flux:heading>
            <flux:text>¿Está seguro de aprobar esta producción?</flux:text>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('approve-modal').close()">
                    Cancelar
                </flux:button>
                <flux:button variant="primary" wire:click="approve" x-on:click="$flux.modal('approve-modal').close()">
                    Aprobar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="complete-modal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Confirmar Completar</flux:heading>
            <flux:text>¿Está seguro de completar esta producción? Los productos serán agregados al inventario de la bodega destino.</flux:text>

            <div class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800 space-y-4">
                <flux:checkbox
                    wire:model.live="createAutoDispatch"
                    label="Crear despacho automáticamente"
                    description="Se creará un despacho a Tienda Ena con los productos de esta producción."
                />

                @if($createAutoDispatch)
                    <flux:field>
                        <flux:label badge="Requerido">Número de Documento del Despacho</flux:label>
                        <flux:input
                            wire:model="dispatchDocumentNumber"
                            placeholder="Ej: 0031430"
                            maxlength="100"
                        />
                        <flux:description>Este número se usará como documento físico del despacho automático.</flux:description>
                        <flux:error name="dispatchDocumentNumber" />
                    </flux:field>
                @endif
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('complete-modal').close()">
                    Cancelar
                </flux:button>
                <flux:button variant="primary" wire:click="complete">
                    Completar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="cancel-modal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Confirmar Anulación</flux:heading>
            <flux:text>¿Está seguro de anular esta producción? Esta acción no se puede deshacer.</flux:text>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('cancel-modal').close()">
                    Cancelar
                </flux:button>
                <flux:button variant="danger" wire:click="cancel" x-on:click="$flux.modal('cancel-modal').close()">
                    Anular
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
