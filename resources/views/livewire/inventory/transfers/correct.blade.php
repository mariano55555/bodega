<?php

use App\Exceptions\InventoryReversalException;
use App\Livewire\Concerns\CorrectsDocumentLines;
use App\Models\InventoryTransfer;
use App\Services\DocumentCorrectionService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use CorrectsDocumentLines;

    public InventoryTransfer $transfer;

    public int|string $from_warehouse_id = '';

    public int|string $to_warehouse_id = '';

    public function mount(InventoryTransfer $transfer): void
    {
        $this->transfer = $transfer->load('details', 'fromWarehouse', 'toWarehouse');
        $this->authorizeCorrection($this->companyId());

        if (! in_array($transfer->status, ['in_transit', 'received'])) {
            session()->flash('error', 'Solo se pueden corregir traslados enviados o recibidos. Use Editar para los pendientes.');
            $this->redirect(route('transfers.show', $transfer), navigate: true);

            return;
        }

        $this->from_warehouse_id = $transfer->from_warehouse_id;
        $this->to_warehouse_id = $transfer->to_warehouse_id;
        $this->lines = $transfer->details->map(fn ($detail) => [
            'product_id' => (string) $detail->product_id,
            'quantity' => (float) $detail->quantity,
            'notes' => $detail->notes ?? '',
        ])->values()->all();
    }

    public function save(): void
    {
        $this->authorizeCorrection($this->companyId());
        $this->withoutEmptyLines();

        $this->validate($this->correctionRules() + [
            'from_warehouse_id' => ['required', 'integer'],
            'to_warehouse_id' => ['required', 'integer', 'different:from_warehouse_id'],
        ], $this->correctionMessages() + [
            'from_warehouse_id.required' => 'Seleccione la bodega de origen.',
            'to_warehouse_id.required' => 'Seleccione la bodega de destino.',
            'to_warehouse_id.different' => 'La bodega de destino debe ser distinta a la de origen.',
        ]);

        try {
            app(DocumentCorrectionService::class)->correctTransfer($this->transfer, [
                'from_warehouse_id' => $this->from_warehouse_id,
                'to_warehouse_id' => $this->to_warehouse_id,
                'lines' => $this->lines,
            ], $this->reason, auth()->id());
        } catch (InventoryReversalException $e) {
            $this->addError('correction', $e->getMessage());

            return;
        }

        session()->flash('success', 'Traslado corregido. El inventario se ajustó solo por la diferencia.');
        $this->redirect(route('transfers.show', $this->transfer), navigate: true);
    }

    protected function companyId(): int
    {
        return (int) $this->transfer->fromWarehouse->company_id;
    }

    public function with(): array
    {
        $keepWarehouses = [(int) $this->transfer->from_warehouse_id, (int) $this->transfer->to_warehouse_id];

        return [
            'title' => 'Corregir traslado '.$this->transfer->transfer_number,
            'products' => $this->productOptions($this->companyId(), $this->transfer->details->pluck('product_id')->all()),
            'warehouses' => $this->warehouseOptions($this->companyId(), $keepWarehouses),
        ];
    }
}; ?>

<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Corregir traslado {{ $transfer->transfer_number }}</flux:heading>
            <flux:text class="mt-1">
                Estado: {{ $transfer->status === 'received' ? 'Recibido' : 'En tránsito' }} · Documento {{ $transfer->physical_document_number }} · {{ $transfer->document_date?->format('d/m/Y') }}
            </flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('transfers.show', $transfer) }}" wire:navigate>Volver</flux:button>
    </div>

    <flux:callout icon="information-circle" color="blue">
        <flux:callout.heading>Cómo funciona la corrección</flux:callout.heading>
        <flux:callout.text>
            El sistema compara el traslado registrado con la corrección y mueve solo la diferencia: devuelve a cada bodega lo que no debió moverse y mueve lo correcto, sin volver a descontar lo que ya salió.
            Los movimientos se registran con la fecha del documento, por lo que el mes debe estar abierto en todas las bodegas involucradas.
            @if ($transfer->status === 'in_transit')
                Como el traslado aún no se recibe, solo se ajusta la bodega de origen.
            @endif
        </flux:callout.text>
    </flux:callout>

    <flux:card>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label badge="Requerido">Bodega de origen</flux:label>
                <flux:select wire:model="from_warehouse_id">
                    @foreach ($warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="from_warehouse_id" />
            </flux:field>

            <flux:field>
                <flux:label badge="Requerido">Bodega de destino</flux:label>
                <flux:select wire:model="to_warehouse_id">
                    @foreach ($warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="to_warehouse_id" />
            </flux:field>
        </div>
    </flux:card>

    @include('partials.document-correction-form', [
        'withPrice' => false,
        'cancelUrl' => route('transfers.show', $transfer),
    ])
</div>
