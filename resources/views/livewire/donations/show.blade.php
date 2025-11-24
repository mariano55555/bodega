<?php

use App\Models\Donation;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Donation $donation;

    public function mount(Donation $donation): void
    {
        $this->donation = $donation->load(['warehouse', 'details.product', 'approver', 'receiver']);
    }

    public function submit(): void
    {
        if ($this->donation->submit()) {
            session()->flash('success', 'Donación enviada para aprobación.');
            $this->donation->refresh();
        } else {
            session()->flash('error', 'No se pudo enviar la donación.');
        }
    }

    public function approve(): void
    {
        if ($this->donation->approve(auth()->id())) {
            session()->flash('success', 'Donación aprobada exitosamente.');
            $this->donation->refresh();
        } else {
            session()->flash('error', 'No se pudo aprobar la donación.');
        }
    }

    public function receive(): void
    {
        if ($this->donation->receive(auth()->id())) {
            session()->flash('success', 'Donación recibida exitosamente. Inventario actualizado.');
            $this->donation->refresh();
        } else {
            session()->flash('error', 'No se pudo recibir la donación.');
        }
    }

    public function cancel(): void
    {
        if ($this->donation->cancel()) {
            session()->flash('success', 'Donación cancelada exitosamente.');
            $this->donation->refresh();
        } else {
            session()->flash('error', 'No se pudo cancelar la donación.');
        }
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Donación {{ $donation->donation_number }}</flux:heading>
            <flux:text class="mt-1">Detalles de la donación</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" href="{{ route('donations.index') }}" wire:navigate>
                Volver
            </flux:button>

            @if ($donation->canBeEdited())
                <flux:button variant="primary" icon="pencil" href="{{ route('donations.edit', $donation->slug) }}" wire:navigate>
                    Editar
                </flux:button>
            @endif
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Información General -->
            <flux:card>
                <flux:heading size="lg">Información General</flux:heading>

                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Número de Donación</flux:text>
                            <flux:text class="mt-1">{{ $donation->donation_number }}</flux:text>
                        </div>

                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Estado</flux:text>
                            <div class="mt-1">
                                @php
                                    $statusColors = [
                                        'borrador' => 'zinc',
                                        'pendiente' => 'yellow',
                                        'aprobado' => 'blue',
                                        'recibido' => 'green',
                                        'cancelado' => 'red',
                                    ];
                                    $statusLabels = [
                                        'borrador' => 'Borrador',
                                        'pendiente' => 'Pendiente',
                                        'aprobado' => 'Aprobado',
                                        'recibido' => 'Recibido',
                                        'cancelado' => 'Cancelado',
                                    ];
                                @endphp
                                <flux:badge :color="$statusColors[$donation->status] ?? 'zinc'">
                                    {{ $statusLabels[$donation->status] ?? $donation->status }}
                                </flux:badge>
                            </div>
                        </div>

                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Bodega</flux:text>
                            <flux:text class="mt-1">{{ $donation->warehouse->name }}</flux:text>
                        </div>

                        @if ($donation->project_name)
                            <div>
                                <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Proyecto</flux:text>
                                <flux:text class="mt-1">{{ $donation->project_name }}</flux:text>
                            </div>
                        @endif

                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Fecha de Documento</flux:text>
                            <flux:text class="mt-1">{{ $donation->document_date->format('d/m/Y') }}</flux:text>
                        </div>

                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Fecha de Recepción</flux:text>
                            <flux:text class="mt-1">{{ $donation->reception_date->format('d/m/Y') }}</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>

            <!-- Información del Donante -->
            <flux:card>
                <flux:heading size="lg">Información del Donante</flux:heading>

                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Nombre</flux:text>
                            <flux:text class="mt-1">{{ $donation->donor_name }}</flux:text>
                        </div>

                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Tipo de Donante</flux:text>
                            <flux:text class="mt-1 capitalize">
                                {{ $donation->donor_type === 'individual' ? 'Individual' : ($donation->donor_type === 'organization' ? 'Organización' : 'Gobierno') }}
                            </flux:text>
                        </div>

                        @if ($donation->donor_contact)
                            <div>
                                <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Persona de Contacto</flux:text>
                                <flux:text class="mt-1">{{ $donation->donor_contact }}</flux:text>
                            </div>
                        @endif

                        @if ($donation->donor_email)
                            <div>
                                <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Email</flux:text>
                                <flux:text class="mt-1">{{ $donation->donor_email }}</flux:text>
                            </div>
                        @endif

                        @if ($donation->donor_phone)
                            <div>
                                <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Teléfono</flux:text>
                                <flux:text class="mt-1">{{ $donation->donor_phone }}</flux:text>
                            </div>
                        @endif

                        @if ($donation->donor_address)
                            <div class="col-span-2">
                                <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Dirección</flux:text>
                                <flux:text class="mt-1">{{ $donation->donor_address }}</flux:text>
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>

            <!-- Documento -->
            <flux:card>
                <flux:heading size="lg">Información del Documento</flux:heading>

                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Tipo de Documento</flux:text>
                            <flux:text class="mt-1 capitalize">{{ $donation->document_type }}</flux:text>
                        </div>

                        @if ($donation->document_number)
                            <div>
                                <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Número de Documento</flux:text>
                                <flux:text class="mt-1">{{ $donation->document_number }}</flux:text>
                            </div>
                        @endif

                        @if ($donation->tax_receipt_required)
                            <div>
                                <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Recibo Fiscal</flux:text>
                                <flux:badge color="green" size="sm" class="mt-1">Requerido</flux:badge>
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>

            <!-- Productos -->
            <flux:card>
                <flux:heading size="lg">Productos Donados</flux:heading>

                <div class="mt-4 overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Producto</flux:table.column>
                            <flux:table.column>Cantidad</flux:table.column>
                            <flux:table.column>Condición</flux:table.column>
                            <flux:table.column>Valor Unit.</flux:table.column>
                            <flux:table.column>Valor Total</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($donation->details as $detail)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <div>
                                            <div class="font-medium">{{ $detail->product->name }}</div>
                                            @if ($detail->lot_number)
                                                <div class="text-sm text-gray-500">Lote: {{ $detail->lot_number }}</div>
                                            @endif
                                            @if ($detail->condition_notes)
                                                <div class="text-sm text-gray-500">{{ $detail->condition_notes }}</div>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ number_format($detail->quantity, 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge :color="match($detail->condition) {
                                            'nuevo' => 'green',
                                            'usado' => 'yellow',
                                            'reacondicionado' => 'blue',
                                            default => 'zinc'
                                        }" size="sm">
                                            {{ ucfirst($detail->condition) }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        ${{ number_format($detail->estimated_unit_value, 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        ${{ number_format($detail->estimated_total_value, 2) }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                @if ($donation->estimated_value)
                    <div class="mt-4 space-y-2 border-t pt-4">
                        <div class="flex justify-between text-lg font-bold">
                            <flux:text class="font-bold">Valor Total Estimado:</flux:text>
                            <flux:text class="font-bold">${{ number_format($donation->estimated_value, 2) }}</flux:text>
                        </div>
                    </div>
                @endif
            </flux:card>

            <!-- Propósito y Uso -->
            @if ($donation->purpose || $donation->intended_use || $donation->conditions)
                <flux:card>
                    <flux:heading size="lg">Propósito y Uso</flux:heading>

                    <div class="mt-4 space-y-4">
                        @if ($donation->purpose)
                            <div>
                                <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Propósito</flux:text>
                                <flux:text class="mt-1">{{ $donation->purpose }}</flux:text>
                            </div>
                        @endif

                        @if ($donation->intended_use)
                            <div>
                                <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Uso Previsto</flux:text>
                                <flux:text class="mt-1">{{ $donation->intended_use }}</flux:text>
                            </div>
                        @endif

                        @if ($donation->conditions)
                            <div>
                                <flux:text size="sm" class="font-medium text-gray-500 dark:text-gray-400">Condiciones</flux:text>
                                <flux:text class="mt-1">{{ $donation->conditions }}</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Acciones de Workflow -->
            <flux:card>
                <flux:heading size="lg">Acciones</flux:heading>

                <div class="mt-4 flex flex-col gap-3">
                    @if ($donation->canBeSubmitted())
                        <flux:modal.trigger name="submit-modal">
                            <flux:button variant="primary" class="w-full" icon="paper-airplane">
                                Enviar para Aprobación
                            </flux:button>
                        </flux:modal.trigger>
                    @endif

                    @if ($donation->canBeApproved())
                        <flux:modal.trigger name="approve-modal">
                            <flux:button variant="primary" class="w-full" icon="check">
                                Aprobar Donación
                            </flux:button>
                        </flux:modal.trigger>
                    @endif

                    @if ($donation->canBeReceived())
                        <flux:modal.trigger name="receive-modal">
                            <flux:button variant="filled" class="w-full" icon="check-circle">
                                Recibir Donación
                            </flux:button>
                        </flux:modal.trigger>
                    @endif

                    @if ($donation->canBeCancelled())
                        <flux:modal.trigger name="cancel-modal">
                            <flux:button variant="danger" class="w-full" icon="x-circle">
                                Cancelar Donación
                            </flux:button>
                        </flux:modal.trigger>
                    @endif

                    {{-- Acciones siempre disponibles --}}
                    <flux:separator class="my-2" />

                    <flux:button variant="ghost" class="w-full" icon="printer" onclick="window.print()">
                        Imprimir
                    </flux:button>

                    <flux:button variant="ghost" class="w-full" icon="document-duplicate" href="{{ route('donations.create', ['duplicate' => $donation->id]) }}" wire:navigate>
                        Duplicar Donación
                    </flux:button>

                    @if ($donation->status === 'recibido' || $donation->status === 'cancelado')
                        <flux:separator class="my-2" />
                        <flux:text size="sm" class="text-center text-gray-500 dark:text-gray-400">
                            @if ($donation->status === 'recibido')
                                Esta donación ya fue recibida y procesada.
                            @else
                                Esta donación fue cancelada.
                            @endif
                        </flux:text>
                    @endif
                </div>
            </flux:card>

            <!-- Tracking de Workflow -->
            <flux:card>
                <flux:heading size="lg">Estado del Proceso</flux:heading>

                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-2">
                        <div class="mt-1">
                            <flux:icon.check-circle class="h-5 w-5 text-green-500" />
                        </div>
                        <div>
                            <flux:text size="sm" class="font-medium">Creado</flux:text>
                            <flux:text size="sm" class="text-gray-500">
                                {{ $donation->created_at->format('d/m/Y H:i') }}
                            </flux:text>
                        </div>
                    </div>

                    @if (in_array($donation->status, ['pendiente', 'aprobado', 'recibido']))
                        <div class="flex items-start gap-2">
                            <div class="mt-1">
                                <flux:icon.check-circle class="h-5 w-5 text-green-500" />
                            </div>
                            <div>
                                <flux:text size="sm" class="font-medium">Enviado para Aprobación</flux:text>
                                <flux:text size="sm" class="text-gray-500">
                                    Pendiente de aprobación
                                </flux:text>
                            </div>
                        </div>
                    @endif

                    @if ($donation->approved_at)
                        <div class="flex items-start gap-2">
                            <div class="mt-1">
                                <flux:icon.check-circle class="h-5 w-5 text-green-500" />
                            </div>
                            <div>
                                <flux:text size="sm" class="font-medium">Aprobado</flux:text>
                                <flux:text size="sm" class="text-gray-500">
                                    {{ $donation->approved_at->format('d/m/Y H:i') }}
                                    @if ($donation->approver)
                                        por {{ $donation->approver->name }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endif

                    @if ($donation->received_at)
                        <div class="flex items-start gap-2">
                            <div class="mt-1">
                                <flux:icon.check-circle class="h-5 w-5 text-green-500" />
                            </div>
                            <div>
                                <flux:text size="sm" class="font-medium">Recibido</flux:text>
                                <flux:text size="sm" class="text-gray-500">
                                    {{ $donation->received_at->format('d/m/Y H:i') }}
                                    @if ($donation->receiver)
                                        por {{ $donation->receiver->name }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Notas -->
            @if ($donation->notes)
                <flux:card>
                    <flux:heading size="lg">Notas</flux:heading>
                    <flux:text class="mt-2">{{ $donation->notes }}</flux:text>
                </flux:card>
            @endif
        </div>
    </div>

    {{-- Submit Confirmation Modal --}}
    @if ($donation->canBeSubmitted())
        <flux:modal name="submit-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Enviar Donación para Aprobación</flux:heading>
                    <flux:text class="mt-2">
                        <p>Está a punto de enviar esta donación para su aprobación.</p>
                        <p class="mt-1">Una vez enviada, pasará a estado pendiente de revisión.</p>
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
    @if ($donation->canBeApproved())
        <flux:modal name="approve-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Aprobar Donación</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Confirma que desea aprobar esta donación?</p>
                        <p class="mt-1">Una vez aprobada, la donación estará lista para ser recibida.</p>
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

    {{-- Receive Confirmation Modal --}}
    @if ($donation->canBeReceived())
        <flux:modal name="receive-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Recibir Donación</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Confirma que ha recibido todos los productos de esta donación?</p>
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
    @endif

    {{-- Cancel Confirmation Modal --}}
    @if ($donation->canBeCancelled())
        <flux:modal name="cancel-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Cancelar Donación</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Está seguro de que desea cancelar esta donación?</p>
                        <p class="mt-1">Esta acción marcará la donación como cancelada.</p>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Volver</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="cancel">Cancelar Donación</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
