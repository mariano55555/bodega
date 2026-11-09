<?php

use App\Exceptions\InventoryReversalException;
use App\Livewire\Concerns\CorrectsDocumentLines;
use App\Models\Dispatch;
use App\Services\DocumentCorrectionService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use CorrectsDocumentLines;

    public Dispatch $dispatch;

    public int|string $warehouse_id = '';

    public function mount(Dispatch $dispatch): void
    {
        $this->dispatch = $dispatch->load('details', 'warehouse');
        $this->authorizeCorrection((int) $dispatch->company_id);

        if (! in_array($dispatch->status, ['despachado', 'entregado'])) {
            session()->flash('error', 'Solo se pueden corregir despachos despachados o entregados. Use Editar para los demás.');
            $this->redirect(route('dispatches.show', $dispatch), navigate: true);

            return;
        }

        $this->warehouse_id = $dispatch->warehouse_id;
        $this->lines = $dispatch->details->map(fn ($detail) => [
            'product_id' => (string) $detail->product_id,
            'quantity' => (float) $detail->quantity,
            'notes' => $detail->notes ?? '',
        ])->values()->all();
    }

    public function save(): void
    {
        $this->authorizeCorrection((int) $this->dispatch->company_id);
        $this->withoutEmptyLines();

        $this->validate($this->correctionRules() + [
            'warehouse_id' => ['required', 'integer'],
        ], $this->correctionMessages() + [
            'warehouse_id.required' => 'Seleccione la bodega.',
        ]);

        try {
            app(DocumentCorrectionService::class)->correctDispatch($this->dispatch, [
                'warehouse_id' => $this->warehouse_id,
                'lines' => $this->lines,
            ], $this->reason, auth()->id());
        } catch (InventoryReversalException $e) {
            $this->addError('correction', $e->getMessage());

            return;
        }

        session()->flash('success', 'Despacho corregido. El inventario se ajustó solo por la diferencia.');
        $this->redirect(route('dispatches.show', $this->dispatch), navigate: true);
    }

    public function with(): array
    {
        $companyId = (int) $this->dispatch->company_id;

        return [
            'title' => 'Corregir despacho '.$this->dispatch->dispatch_number,
            'products' => $this->productOptions($companyId, $this->dispatch->details->pluck('product_id')->all()),
            'warehouses' => $this->warehouseOptions($companyId, [(int) $this->dispatch->warehouse_id]),
        ];
    }
}; ?>

<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Corregir despacho {{ $dispatch->dispatch_number }}</flux:heading>
            <flux:text class="mt-1">
                Estado: {{ $dispatch->status_spanish }} · Documento {{ $dispatch->physical_document_number }} · {{ $dispatch->document_date?->format('d/m/Y') }}
            </flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('dispatches.show', $dispatch) }}" wire:navigate>Volver</flux:button>
    </div>

    <flux:callout icon="information-circle" color="blue">
        <flux:callout.heading>Cómo funciona la corrección</flux:callout.heading>
        <flux:callout.text>
            El sistema compara el despacho registrado con la corrección y mueve solo la diferencia: devuelve a la bodega lo que no debió salir y descuenta lo que faltó, sin volver a descontar lo que ya salió.
            Los movimientos se registran con la fecha del documento, por lo que el mes debe estar abierto. Para cambiar la unidad solicitante o las notas use Editar.
        </flux:callout.text>
    </flux:callout>

    <flux:card>
        <flux:field class="max-w-md">
            <flux:label badge="Requerido">Bodega</flux:label>
            <flux:select wire:model="warehouse_id">
                @foreach ($warehouses as $warehouse)
                    <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="warehouse_id" />
        </flux:field>
    </flux:card>

    @include('partials.document-correction-form', [
        'withPrice' => false,
        'cancelUrl' => route('dispatches.show', $dispatch),
    ])
</div>
