<?php

use App\Models\{Warehouse, Product, Donation, DonationDetail};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Donation $donation;
    public $warehouse_id = '';
    public $donor_name = '';
    public $donor_type = 'individual';
    public $donor_contact = '';
    public $donor_email = '';
    public $donor_phone = '';
    public $donor_address = '';
    public $document_type = 'acta';
    public $document_number = '';
    public $document_date = '';
    public $reception_date = '';
    public $purpose = '';
    public $intended_use = '';
    public $project_name = '';
    public $notes = '';
    public $conditions = '';
    public $tax_receipt_required = false;
    public $status = 'borrador';

    public array $details = [];

    public function mount(Donation $donation): void
    {
        // Only allow editing drafts and pending
        if (! $donation->canBeEdited()) {
            session()->flash('error', 'Solo se pueden editar donaciones en estado borrador o pendiente.');
            $this->redirect(route('donations.show', $donation->slug), navigate: true);

            return;
        }

        $this->donation = $donation;
        $this->warehouse_id = $donation->warehouse_id;
        $this->donor_name = $donation->donor_name;
        $this->donor_type = $donation->donor_type;
        $this->donor_contact = $donation->donor_contact;
        $this->donor_email = $donation->donor_email;
        $this->donor_phone = $donation->donor_phone;
        $this->donor_address = $donation->donor_address;
        $this->document_type = $donation->document_type;
        $this->document_number = $donation->document_number;
        $this->document_date = $donation->document_date?->format('Y-m-d');
        $this->reception_date = $donation->reception_date?->format('Y-m-d');
        $this->purpose = $donation->purpose;
        $this->intended_use = $donation->intended_use;
        $this->project_name = $donation->project_name;
        $this->notes = $donation->notes;
        $this->conditions = $donation->conditions;
        $this->tax_receipt_required = $donation->tax_receipt_required;
        $this->status = $donation->status;

        // Load existing details
        foreach ($donation->details as $detail) {
            $this->details[] = [
                'id' => $detail->id,
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'estimated_unit_value' => $detail->estimated_unit_value,
                'condition' => $detail->condition,
                'condition_notes' => $detail->condition_notes,
                'lot_number' => $detail->lot_number,
                'expiration_date' => $detail->expiration_date?->format('Y-m-d'),
                'notes' => $detail->notes,
            ];
        }

        if (empty($this->details)) {
            $this->addDetail();
        }
    }

    public function addDetail(): void
    {
        $this->details[] = [
            'id' => null,
            'product_id' => '',
            'quantity' => 1,
            'estimated_unit_value' => 0,
            'condition' => 'nuevo',
            'condition_notes' => '',
            'lot_number' => '',
            'expiration_date' => '',
            'notes' => '',
        ];
    }

    public function removeDetail(int $index): void
    {
        unset($this->details[$index]);
        $this->details = array_values($this->details);
    }

    public function save(): void
    {
        $this->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'donor_name' => 'required|string|max:255',
            'donor_type' => 'required|in:individual,organization,government,ngo,international',
            'document_type' => 'required|in:acta,carta,convenio,otro',
            'document_date' => 'required|date',
            'reception_date' => 'required|date',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:0.0001',
            'details.*.estimated_unit_value' => 'required|numeric|min:0',
            'details.*.condition' => 'required|in:nuevo,usado,reacondicionado',
        ]);

        \DB::transaction(function () {
            $this->donation->update([
                'warehouse_id' => $this->warehouse_id,
                'donor_name' => $this->donor_name,
                'donor_type' => $this->donor_type,
                'donor_contact' => $this->donor_contact,
                'donor_email' => $this->donor_email,
                'donor_phone' => $this->donor_phone,
                'donor_address' => $this->donor_address,
                'document_type' => $this->document_type,
                'document_number' => $this->document_number,
                'document_date' => $this->document_date,
                'reception_date' => $this->reception_date,
                'purpose' => $this->purpose,
                'intended_use' => $this->intended_use,
                'project_name' => $this->project_name,
                'notes' => $this->notes,
                'conditions' => $this->conditions,
                'tax_receipt_required' => $this->tax_receipt_required,
                'status' => $this->status,
                'updated_by' => auth()->id(),
            ]);

            // Get existing detail IDs
            $existingIds = collect($this->details)->pluck('id')->filter()->toArray();

            // Delete removed details
            $this->donation->details()->whereNotIn('id', $existingIds)->delete();

            // Update existing and create new details
            foreach ($this->details as $detail) {
                if ($detail['id']) {
                    // Update existing
                    DonationDetail::find($detail['id'])->update([
                        'product_id' => $detail['product_id'],
                        'quantity' => $detail['quantity'],
                        'estimated_unit_value' => $detail['estimated_unit_value'],
                        'condition' => $detail['condition'],
                        'condition_notes' => $detail['condition_notes'] ?? null,
                        'lot_number' => $detail['lot_number'] ?? null,
                        'expiration_date' => $detail['expiration_date'] ?? null,
                        'notes' => $detail['notes'] ?? null,
                    ]);
                } else {
                    // Create new
                    DonationDetail::create([
                        'donation_id' => $this->donation->id,
                        'product_id' => $detail['product_id'],
                        'quantity' => $detail['quantity'],
                        'estimated_unit_value' => $detail['estimated_unit_value'],
                        'condition' => $detail['condition'],
                        'condition_notes' => $detail['condition_notes'] ?? null,
                        'lot_number' => $detail['lot_number'] ?? null,
                        'expiration_date' => $detail['expiration_date'] ?? null,
                        'notes' => $detail['notes'] ?? null,
                    ]);
                }
            }

            $this->donation->calculateTotals();

            session()->flash('success', 'Donación actualizada exitosamente.');
            $this->redirect(route('donations.show', $this->donation->slug), navigate: true);
        });
    }

    public function getProductUnit($productId): array
    {
        if (! $productId) {
            return ['abbreviation' => '', 'name' => ''];
        }

        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? $this->donation->company_id : $user->company_id;

        $product = Product::with('unitOfMeasure')
            ->where('company_id', $companyId)
            ->where('id', $productId)
            ->first();

        return [
            'abbreviation' => $product?->unitOfMeasure?->abbreviation ?? '',
            'name' => $product?->unitOfMeasure?->name ?? '',
        ];
    }

    public function with(): array
    {
        $user = auth()->user();

        // For super admin, show warehouses and products from the donation's company
        // For regular users, show from their company
        $companyId = $user->isSuperAdmin() ? $this->donation->company_id : $user->company_id;

        return [
            'warehouses' => Warehouse::where('company_id', $companyId)->active()->orderBy('name')->get(),
            'products' => Product::with('unitOfMeasure')->where('company_id', $companyId)->active()->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Editar Donación {{ $donation->donation_number }}</flux:heading>
            <flux:text class="mt-1">Modificar información de la donación</flux:text>
        </div>

        <flux:button variant="ghost" href="{{ route('donations.show', $donation->slug) }}" wire:navigate>
            Cancelar
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Información Básica -->
        <flux:card>
            <flux:heading size="lg">Información Básica</flux:heading>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Bodega *</flux:label>
                    <flux:select wire:model="warehouse_id" required>
                        <option value="">Seleccione bodega</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('warehouse_id') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Nombre del Proyecto</flux:label>
                    <flux:input wire:model="project_name" placeholder="Opcional" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Información del Donante -->
        <flux:card>
            <flux:heading size="lg">Información del Donante</flux:heading>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Nombre del Donante *</flux:label>
                    <flux:input wire:model="donor_name" required />
                    @error('donor_name') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de Donante *</flux:label>
                    <flux:select wire:model="donor_type" required>
                        <option value="individual">Individual</option>
                        <option value="organization">Organización</option>
                        <option value="government">Gobierno</option>
                        <option value="ngo">ONG</option>
                        <option value="international">Organismo Internacional</option>
                    </flux:select>
                    <flux:error name="donor_type" />
                </flux:field>

                <flux:field>
                    <flux:label>Persona de Contacto</flux:label>
                    <flux:input wire:model="donor_contact" />
                </flux:field>

                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="donor_email" />
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono</flux:label>
                    <flux:input wire:model="donor_phone" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Dirección</flux:label>
                    <flux:textarea wire:model="donor_address" rows="2" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Información del Documento -->
        <flux:card>
            <flux:heading size="lg">Información del Documento</flux:heading>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Tipo de Documento *</flux:label>
                    <flux:select wire:model="document_type" required>
                        <option value="acta">Acta</option>
                        <option value="carta">Carta</option>
                        <option value="convenio">Convenio</option>
                        <option value="otro">Otro</option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Número de Documento</flux:label>
                    <flux:input wire:model="document_number" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha del Documento *</flux:label>
                    <flux:input type="date" wire:model="document_date" required />
                    @error('document_date') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de Recepción *</flux:label>
                    <flux:input type="date" wire:model="reception_date" required />
                    @error('reception_date') <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text> @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Propósito y Uso -->
        <flux:card>
            <flux:heading size="lg">Propósito y Uso</flux:heading>

            <div class="mt-4 grid grid-cols-1 gap-4">
                <flux:field>
                    <flux:label>Propósito</flux:label>
                    <flux:textarea wire:model="purpose" rows="2" placeholder="Describa el propósito de la donación" />
                </flux:field>

                <flux:field>
                    <flux:label>Uso Previsto</flux:label>
                    <flux:textarea wire:model="intended_use" rows="2" placeholder="Describa cómo se utilizará la donación" />
                </flux:field>

                <flux:field>
                    <flux:label>Condiciones</flux:label>
                    <flux:textarea wire:model="conditions" rows="2" placeholder="Condiciones especiales de la donación" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="tax_receipt_required">
                        Requiere recibo fiscal
                    </flux:checkbox>
                </flux:field>
            </div>
        </flux:card>

        <!-- Productos Donados -->
        <flux:card>
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Productos Donados</flux:heading>
                <flux:button type="button" size="sm" icon="plus" wire:click="addDetail">
                    Agregar Producto
                </flux:button>
            </div>

            <div class="mt-4 space-y-4">
                @foreach ($details as $index => $detail)
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div class="flex items-center justify-between mb-3">
                            <flux:text class="font-medium">Producto {{ $index + 1 }}</flux:text>
                            @if (count($details) > 1)
                                <flux:button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    wire:click="removeDetail({{ $index }})"
                                >
                                    Eliminar
                                </flux:button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:field class="md:col-span-3">
                                <flux:label>Producto *</flux:label>
                                <flux:select wire:model.live="details.{{ $index }}.product_id" required>
                                    <option value="">Seleccione producto</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </flux:select>
                                @error("details.{$index}.product_id")
                                    <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text>
                                @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Cantidad *</flux:label>
                                <div class="flex items-center gap-2">
                                    <flux:input
                                        type="number"
                                        step="0.0001"
                                        wire:model="details.{{ $index }}.quantity"
                                        required
                                        class="flex-1"
                                    />
                                    @if($detail['product_id'])
                                        @php $unit = $this->getProductUnit($detail['product_id']); @endphp
                                        @if($unit['abbreviation'])
                                            <flux:tooltip content="{{ $unit['name'] }}">
                                                <flux:badge color="zinc" class="whitespace-nowrap cursor-help">
                                                    {{ $unit['abbreviation'] }}
                                                </flux:badge>
                                            </flux:tooltip>
                                        @endif
                                    @endif
                                </div>
                                @error("details.{$index}.quantity")
                                    <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text>
                                @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Valor Unitario Estimado *</flux:label>
                                <flux:input
                                    type="number"
                                    step="0.01"
                                    wire:model="details.{{ $index }}.estimated_unit_value"
                                    required
                                />
                                @error("details.{$index}.estimated_unit_value")
                                    <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text>
                                @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Condición *</flux:label>
                                <flux:select wire:model="details.{{ $index }}.condition" required>
                                    <option value="nuevo">Nuevo</option>
                                    <option value="usado">Usado</option>
                                    <option value="reacondicionado">Reacondicionado</option>
                                </flux:select>
                            </flux:field>

                            <flux:field>
                                <flux:label>Número de Lote</flux:label>
                                <flux:input wire:model="details.{{ $index }}.lot_number" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Fecha de Vencimiento</flux:label>
                                <flux:input type="date" wire:model="details.{{ $index }}.expiration_date" />
                            </flux:field>

                            <flux:field class="md:col-span-3">
                                <flux:label>Notas de Condición</flux:label>
                                <flux:textarea wire:model="details.{{ $index }}.condition_notes" rows="2" />
                            </flux:field>

                            <flux:field class="md:col-span-3">
                                <flux:label>Notas Adicionales</flux:label>
                                <flux:textarea wire:model="details.{{ $index }}.notes" rows="2" />
                            </flux:field>
                        </div>
                    </div>
                @endforeach

                @error('details')
                    <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text>
                @enderror
            </div>
        </flux:card>

        <!-- Notas Generales -->
        <flux:card>
            <flux:heading size="lg">Notas Generales</flux:heading>

            <div class="mt-4">
                <flux:field>
                    <flux:label>Notas Administrativas</flux:label>
                    <flux:textarea wire:model="notes" rows="4" placeholder="Información adicional sobre la donación" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('donations.show', $donation->slug) }}" wire:navigate>
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary">
                Guardar Cambios
            </flux:button>
        </div>
    </form>
</div>
