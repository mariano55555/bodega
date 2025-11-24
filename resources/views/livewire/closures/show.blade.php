<?php

use App\Models\InventoryClosure;
use App\Models\InventoryClosureDetail;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public InventoryClosure $closure;

    public string $reopeningReason = '';

    // Physical count modal
    public bool $showCountModal = false;
    public ?int $countingDetailId = null;
    public ?float $physicalQuantity = null;
    public ?float $physicalUnitCost = null;

    public function mount(InventoryClosure $closure): void
    {
        $this->closure = $closure->load(['warehouse', 'details.product.unitOfMeasure', 'approver', 'closer', 'reopener']);
    }

    public function process(): void
    {
        if ($this->closure->process()) {
            session()->flash('success', 'Cierre procesado exitosamente.');
            $this->closure->refresh();
        } else {
            session()->flash('error', 'No se pudo procesar el cierre.');
        }
    }

    public function approve(): void
    {
        if ($this->closure->approve(auth()->id())) {
            session()->flash('success', 'Cierre aprobado exitosamente.');
            $this->closure->refresh();
        } else {
            session()->flash('error', 'No se pudo aprobar el cierre.');
        }
    }

    public function close(): void
    {
        if ($this->closure->close(auth()->id())) {
            session()->flash('success', 'Período cerrado exitosamente.');
            $this->closure->refresh();
        } else {
            session()->flash('error', 'No se pudo cerrar el período.');
        }
    }

    public function reopen(): void
    {
        $this->validate(['reopeningReason' => 'required|string|min:10|max:1000']);

        if ($this->closure->reopen(auth()->id(), $this->reopeningReason)) {
            session()->flash('success', 'Cierre reabierto exitosamente.');
            $this->reopeningReason = '';
            $this->closure->refresh();
        } else {
            session()->flash('error', 'No se pudo reabrir el cierre.');
        }
    }

    public function cancel(): void
    {
        if ($this->closure->cancel()) {
            session()->flash('success', 'Cierre cancelado exitosamente.');
            $this->closure->refresh();
        } else {
            session()->flash('error', 'No se pudo cancelar el cierre.');
        }
    }

    public function openCountModal(int $detailId): void
    {
        $detail = $this->closure->details->find($detailId);
        if ($detail) {
            $this->countingDetailId = $detailId;
            $this->physicalQuantity = $detail->physical_count_quantity ?? $detail->calculated_closing_quantity;
            $this->physicalUnitCost = $detail->physical_count_unit_cost ?? $detail->calculated_closing_unit_cost;
            $this->showCountModal = true;
        }
    }

    public function savePhysicalCount(): void
    {
        $this->validate([
            'physicalQuantity' => 'required|numeric|min:0',
            'physicalUnitCost' => 'required|numeric|min:0',
        ]);

        $detail = InventoryClosureDetail::find($this->countingDetailId);
        if ($detail && $this->closure->canBeEdited()) {
            $detail->recordPhysicalCount(
                (float) $this->physicalQuantity,
                (float) $this->physicalUnitCost,
                auth()->id()
            );

            $this->closure->calculateTotals();
            $this->closure->refresh();

            session()->flash('success', 'Conteo físico registrado exitosamente.');
        }

        $this->closeCountModal();
    }

    public function closeCountModal(): void
    {
        $this->showCountModal = false;
        $this->countingDetailId = null;
        $this->physicalQuantity = null;
        $this->physicalUnitCost = null;
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Cierre {{ $closure->closure_number }}</flux:heading>
            <flux:text class="mt-1">{{ $closure->monthName }} {{ $closure->year }} - {{ $closure->warehouse->name }}</flux:text>
        </div>
        <flux:button variant="ghost" href="{{ route('closures.index') }}" wire:navigate>Volver</flux:button>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif
    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <flux:heading size="lg">Información General</flux:heading>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500">Número</flux:text>
                        <flux:text class="mt-1">{{ $closure->closure_number }}</flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500">Estado</flux:text>
                        <div class="mt-1">
                            @php $statusColors = ['en_proceso' => 'yellow', 'cerrado' => 'green', 'reabierto' => 'blue', 'cancelado' => 'red']; @endphp
                            <flux:badge :color="$statusColors[$closure->status]">{{ ucfirst($closure->status) }}</flux:badge>
                        </div>
                    </div>
                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500">Período</flux:text>
                        <flux:text class="mt-1">{{ $closure->period_start_date->format('d/m/Y') }} - {{ $closure->period_end_date->format('d/m/Y') }}</flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="font-medium text-gray-500">Fecha Cierre</flux:text>
                        <flux:text class="mt-1">{{ $closure->closure_date->format('d/m/Y') }}</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">Resumen</flux:heading>
                <div class="mt-4 grid grid-cols-4 gap-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <flux:text size="sm" class="text-gray-600">Productos</flux:text>
                        <flux:text class="mt-1 text-2xl font-bold">{{ $closure->total_products }}</flux:text>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <flux:text size="sm" class="text-gray-600">Movimientos</flux:text>
                        <flux:text class="mt-1 text-2xl font-bold">{{ $closure->total_movements }}</flux:text>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <flux:text size="sm" class="text-gray-600">Cantidad</flux:text>
                        <flux:text class="mt-1 text-2xl font-bold">{{ number_format($closure->total_quantity, 2) }}</flux:text>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <flux:text size="sm" class="text-gray-600">Valor Total</flux:text>
                        <flux:text class="mt-1 text-2xl font-bold">${{ number_format($closure->total_value, 2) }}</flux:text>
                    </div>
                </div>
            </flux:card>

            @if ($closure->notes || $closure->observations)
                <flux:card>
                    <flux:heading size="lg">Notas</flux:heading>
                    <div class="mt-4 space-y-4">
                        @if ($closure->notes)
                            <div>
                                <flux:text size="sm" class="font-medium text-gray-500">Notas</flux:text>
                                <flux:text class="mt-1">{{ $closure->notes }}</flux:text>
                            </div>
                        @endif
                        @if ($closure->observations)
                            <div>
                                <flux:text size="sm" class="font-medium text-gray-500">Observaciones</flux:text>
                                <flux:text class="mt-1">{{ $closure->observations }}</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif
        </div>

        <div class="space-y-6">
            <flux:card>
                <flux:heading size="lg">Acciones</flux:heading>
                <div class="mt-4 space-y-2">
                    @if ($closure->canBeProcessed())
                        <flux:button variant="primary" class="w-full" icon="cog" wire:click="process">Procesar</flux:button>
                    @endif
                    @if ($closure->canBeApproved())
                        <flux:button variant="primary" class="w-full" icon="check" wire:click="approve">Aprobar</flux:button>
                    @endif
                    @if ($closure->canBeClosed())
                        <flux:button variant="primary" class="w-full" icon="lock-closed" wire:click="close">Cerrar Período</flux:button>
                    @endif
                    @if ($closure->canBeReopened())
                        <flux:modal.trigger name="reopen-modal">
                            <flux:button variant="filled" class="w-full !bg-amber-500 hover:!bg-amber-600 !text-white" icon="lock-open">Reabrir Período</flux:button>
                        </flux:modal.trigger>
                    @endif
                    @if ($closure->canBeCancelled())
                        <flux:modal.trigger name="cancel-modal">
                            <flux:button variant="danger" class="w-full" icon="x-circle">Cancelar</flux:button>
                        </flux:modal.trigger>
                    @endif

                    {{-- Acciones siempre disponibles --}}
                    <flux:separator class="my-2" />

                    <flux:button variant="ghost" class="w-full" icon="printer" onclick="window.print()">
                        Imprimir
                    </flux:button>

                    <flux:button variant="ghost" class="w-full" icon="document-arrow-down" href="{{ route('closures.export', $closure) }}">
                        Exportar Excel
                    </flux:button>

                    @if ($closure->status === 'cerrado')
                        <flux:separator class="my-2" />
                        <flux:text size="sm" class="text-center text-gray-500 dark:text-gray-400">
                            Este período está cerrado. Use "Reabrir" si necesita hacer modificaciones.
                        </flux:text>
                    @elseif ($closure->status === 'cancelado')
                        <flux:separator class="my-2" />
                        <flux:text size="sm" class="text-center text-gray-500 dark:text-gray-400">
                            Este cierre fue cancelado.
                        </flux:text>
                    @endif
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">Historial</flux:heading>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-2">
                        <flux:icon.check-circle class="h-5 w-5 text-green-500" />
                        <div>
                            <flux:text size="sm" class="font-medium">Creado</flux:text>
                            <flux:text size="sm" class="text-gray-500">{{ $closure->created_at->format('d/m/Y H:i') }}</flux:text>
                        </div>
                    </div>
                    @if ($closure->is_approved)
                        <div class="flex items-start gap-2">
                            <flux:icon.check-circle class="h-5 w-5 text-green-500" />
                            <div>
                                <flux:text size="sm" class="font-medium">Aprobado</flux:text>
                                <flux:text size="sm" class="text-gray-500">{{ $closure->approved_at->format('d/m/Y H:i') }}</flux:text>
                            </div>
                        </div>
                    @endif
                    @if ($closure->closed_at)
                        <div class="flex items-start gap-2">
                            <flux:icon.check-circle class="h-5 w-5 text-green-500" />
                            <div>
                                <flux:text size="sm" class="font-medium">Cerrado</flux:text>
                                <flux:text size="sm" class="text-gray-500">{{ $closure->closed_at->format('d/m/Y H:i') }}</flux:text>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>
    </div>

    <!-- Detalle de Productos -->
    @if ($closure->details->count() > 0)
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">Detalle de Productos</flux:heading>
                @if ($closure->canBeEdited())
                    <flux:badge color="yellow">Puede registrar conteos físicos</flux:badge>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100 dark:bg-zinc-800 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Producto</th>
                            <th class="px-4 py-3 font-medium text-right">Saldo Inicial</th>
                            <th class="px-4 py-3 font-medium text-right">Entradas</th>
                            <th class="px-4 py-3 font-medium text-right">Salidas</th>
                            <th class="px-4 py-3 font-medium text-right">Saldo Calculado</th>
                            <th class="px-4 py-3 font-medium text-right">Conteo Físico</th>
                            <th class="px-4 py-3 font-medium text-right">Diferencia</th>
                            <th class="px-4 py-3 font-medium text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($closure->details as $detail)
                            <tr @class(['bg-red-50 dark:bg-red-900/20' => $detail->has_discrepancy])>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $detail->product->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $detail->product->sku }}</div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ number_format($detail->opening_quantity, 2) }}
                                    <span class="text-xs text-zinc-500">{{ $detail->product->unitOfMeasure?->abbreviation }}</span>
                                </td>
                                <td class="px-4 py-3 text-right text-green-600">
                                    +{{ number_format($detail->quantity_in, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-red-600">
                                    -{{ number_format($detail->quantity_out, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium">
                                    {{ number_format($detail->calculated_closing_quantity, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($detail->physical_count_quantity !== null)
                                        <span class="font-medium text-blue-600">
                                            {{ number_format($detail->physical_count_quantity, 2) }}
                                        </span>
                                        <div class="text-xs text-zinc-500">
                                            {{ $detail->physical_count_date?->format('d/m/Y H:i') }}
                                        </div>
                                    @else
                                        <span class="text-zinc-400">Sin conteo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($detail->has_discrepancy)
                                        <span @class([
                                            'font-medium',
                                            'text-red-600' => $detail->discrepancy_quantity < 0,
                                            'text-green-600' => $detail->discrepancy_quantity > 0,
                                        ])>
                                            {{ $detail->discrepancy_quantity > 0 ? '+' : '' }}{{ number_format($detail->discrepancy_quantity, 2) }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($closure->canBeEdited())
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            icon="pencil"
                                            wire:click="openCountModal({{ $detail->id }})"
                                            title="Registrar conteo físico"
                                        />
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($closure->products_with_discrepancies > 0)
                <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                    <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                        <strong>{{ $closure->products_with_discrepancies }}</strong> producto(s) con discrepancias.
                        Valor total de discrepancia: <strong>${{ number_format($closure->total_discrepancy_value, 2) }}</strong>
                    </flux:text>
                </div>
            @endif
        </flux:card>
    @else
        <flux:card>
            <div class="text-center py-8">
                <flux:icon name="cube" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                <flux:heading size="md" class="mb-2">Sin productos procesados</flux:heading>
                <flux:text class="text-zinc-500">
                    Presione el botón "Procesar" para generar el detalle de productos del período.
                </flux:text>
            </div>
        </flux:card>
    @endif

    <!-- Modal de Conteo Físico -->
    <flux:modal wire:model="showCountModal" class="max-w-md">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">Registrar Conteo Físico</flux:heading>

            @php
                $currentDetail = $countingDetailId ? $closure->details->find($countingDetailId) : null;
            @endphp

            @if ($currentDetail)
                <div class="mb-4 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <flux:text class="font-medium">{{ $currentDetail->product->name }}</flux:text>
                    <flux:text class="text-sm text-zinc-500">
                        Cantidad calculada: {{ number_format($currentDetail->calculated_closing_quantity, 2) }}
                        {{ $currentDetail->product->unitOfMeasure?->abbreviation }}
                    </flux:text>
                </div>
            @endif

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Cantidad Física</flux:label>
                    <flux:input
                        type="number"
                        step="0.0001"
                        min="0"
                        wire:model="physicalQuantity"
                        placeholder="0.00"
                    />
                    <flux:error name="physicalQuantity" />
                </flux:field>

                <flux:field>
                    <flux:label>Costo Unitario ($)</flux:label>
                    <flux:input
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="physicalUnitCost"
                        placeholder="0.00"
                    />
                    <flux:error name="physicalUnitCost" />
                </flux:field>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeCountModal">
                    Cancelar
                </flux:button>
                <flux:button variant="primary" wire:click="savePhysicalCount">
                    Guardar Conteo
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Reopen Confirmation Modal --}}
    @if ($closure->canBeReopened())
        <flux:modal name="reopen-modal" class="min-w-[22rem] max-w-lg">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Reabrir Período de Cierre</flux:heading>
                    <flux:text class="mt-2">
                        <p>Está a punto de reabrir el período de cierre <strong>{{ $closure->closure_number }}</strong>.</p>
                        <p class="mt-2">Esta acción permitirá realizar modificaciones al cierre. Debe proporcionar una justificación.</p>
                    </flux:text>
                </div>

                <flux:field>
                    <flux:label>Motivo de Reapertura</flux:label>
                    <flux:textarea
                        wire:model="reopeningReason"
                        placeholder="Ingrese el motivo por el cual necesita reabrir este período..."
                        rows="3"
                    />
                    <flux:error name="reopeningReason" />
                    <flux:description>Mínimo 10 caracteres</flux:description>
                </flux:field>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="filled" class="!bg-amber-500 hover:!bg-amber-600 !text-white" wire:click="reopen">Reabrir Período</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Cancel Confirmation Modal --}}
    @if ($closure->canBeCancelled())
        <flux:modal name="cancel-modal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Cancelar Cierre</flux:heading>
                    <flux:text class="mt-2">
                        <p>¿Está seguro de que desea cancelar este cierre?</p>
                        <p class="mt-1">Esta acción marcará el cierre como cancelado y no podrá ser procesado.</p>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Volver</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="cancel">Confirmar Cancelación</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
