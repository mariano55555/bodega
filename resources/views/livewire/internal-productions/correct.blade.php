<?php

use App\Exceptions\InventoryReversalException;
use App\Livewire\Concerns\CorrectsDocumentLines;
use App\Models\InternalProduction;
use App\Services\DocumentCorrectionService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use CorrectsDocumentLines;

    public InternalProduction $internalProduction;

    public int|string $warehouse_id = '';

    public function mount(InternalProduction $internalProduction): void
    {
        $this->internalProduction = $internalProduction->load('details', 'warehouse');
        $this->authorizeCorrection((int) $internalProduction->company_id);

        if ($internalProduction->status !== 'completado') {
            session()->flash('error', 'Solo se pueden corregir producciones completadas. Use Editar para las demás.');
            $this->redirect(route('internal-productions.show', $internalProduction), navigate: true);

            return;
        }

        $this->warehouse_id = $internalProduction->warehouse_id;
        $this->lines = $internalProduction->details->map(fn ($detail) => [
            'product_id' => (string) $detail->product_id,
            'quantity' => (float) $detail->quantity,
            'unit_price' => (float) $detail->unit_price,
            'notes' => $detail->notes ?? '',
        ])->values()->all();
    }

    public function save(): void
    {
        $this->authorizeCorrection((int) $this->internalProduction->company_id);
        $this->withoutEmptyLines();

        $this->validate($this->correctionRules(withPrice: true) + [
            'warehouse_id' => ['required', 'integer'],
        ], $this->correctionMessages() + [
            'warehouse_id.required' => 'Seleccione la bodega destino.',
        ]);

        try {
            app(DocumentCorrectionService::class)->correctProduction($this->internalProduction, [
                'warehouse_id' => $this->warehouse_id,
                'lines' => $this->lines,
            ], $this->reason, auth()->id());
        } catch (InventoryReversalException $e) {
            $this->addError('correction', $e->getMessage());

            return;
        }

        session()->flash('success', 'Producción corregida junto con su despacho automático. El inventario se ajustó solo por la diferencia.');
        $this->redirect(route('internal-productions.show', $this->internalProduction), navigate: true);
    }

    public function with(): array
    {
        $companyId = (int) $this->internalProduction->company_id;

        return [
            'title' => 'Corregir producción '.$this->internalProduction->production_number,
            'products' => $this->productOptions($companyId, $this->internalProduction->details->pluck('product_id')->all()),
            'warehouses' => $this->warehouseOptions($companyId, [(int) $this->internalProduction->warehouse_id]),
            'autoDispatches' => $this->internalProduction->autoDispatches()->where('status', '!=', 'cancelado')->get(['id', 'dispatch_number', 'status']),
        ];
    }
}; ?>

<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Corregir producción {{ $internalProduction->production_number }}</flux:heading>
            <flux:text class="mt-1">
                Documento {{ $internalProduction->physical_document_number }} · {{ $internalProduction->document_date?->format('d/m/Y') }}
            </flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('internal-productions.show', $internalProduction) }}" wire:navigate>Volver</flux:button>
    </div>

    <flux:callout icon="information-circle" color="blue">
        <flux:callout.heading>Cómo funciona la corrección</flux:callout.heading>
        <flux:callout.text>
            El sistema compara la producción registrada con la corrección y mueve solo la diferencia en la bodega destino.
            Los movimientos se registran con la fecha del documento, por lo que el mes debe estar abierto.
        </flux:callout.text>
    </flux:callout>

    @if ($autoDispatches->isNotEmpty())
        <flux:callout icon="truck" color="amber">
            <flux:callout.heading>También se corregirá el despacho automático</flux:callout.heading>
            <flux:callout.text>
                Esta producción generó {{ $autoDispatches->count() === 1 ? 'el despacho' : 'los despachos' }} {{ $autoDispatches->pluck('dispatch_number')->join(', ') }}.
                Se ajustará con los mismos productos, cantidades y bodega.
            </flux:callout.text>
        </flux:callout>
    @endif

    <flux:card>
        <flux:field class="max-w-md">
            <flux:label badge="Requerido">Bodega destino</flux:label>
            <flux:select wire:model="warehouse_id">
                @foreach ($warehouses as $warehouse)
                    <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="warehouse_id" />
        </flux:field>
    </flux:card>

    @include('partials.document-correction-form', [
        'withPrice' => true,
        'cancelUrl' => route('internal-productions.show', $internalProduction),
    ])
</div>
