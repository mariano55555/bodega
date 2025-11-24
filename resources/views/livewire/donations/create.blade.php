<?php

use App\Models\{Warehouse, Product, Donation, DonationDetail, Company, Donor};
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public $company_id = '';

    public $warehouse_id = '';
    public $donor_id = '';
    public $use_existing_donor = true;
    public $show_donor_modal = false;
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

    public function mount(): void
    {
        $this->document_date = now()->format('Y-m-d');
        $this->reception_date = now()->format('Y-m-d');

        // If not super admin, set company_id to user's company
        if (!$this->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }

        // Check if duplicating from an existing donation
        $duplicateId = request()->query('duplicate');
        if ($duplicateId) {
            $sourceDonation = Donation::with('details')->find($duplicateId);
            if ($sourceDonation) {
                $this->company_id = $sourceDonation->warehouse?->company_id ?? $this->company_id;
                $this->warehouse_id = $sourceDonation->warehouse_id;
                $this->donor_id = $sourceDonation->donor_id ?? '';
                $this->donor_name = $sourceDonation->donor_name;
                $this->donor_type = $sourceDonation->donor_type;
                $this->donor_contact = $sourceDonation->donor_contact ?? '';
                $this->donor_email = $sourceDonation->donor_email ?? '';
                $this->donor_phone = $sourceDonation->donor_phone ?? '';
                $this->donor_address = $sourceDonation->donor_address ?? '';
                $this->document_type = $sourceDonation->document_type;
                $this->purpose = $sourceDonation->purpose ?? '';
                $this->intended_use = $sourceDonation->intended_use ?? '';
                $this->project_name = $sourceDonation->project_name ?? '';
                $this->conditions = $sourceDonation->conditions ?? '';
                $this->tax_receipt_required = $sourceDonation->tax_receipt_required;
                $this->use_existing_donor = !empty($sourceDonation->donor_id);

                // Copy details
                $this->details = [];
                foreach ($sourceDonation->details as $detail) {
                    $this->details[] = [
                        'product_id' => $detail->product_id,
                        'quantity' => $detail->quantity,
                        'estimated_unit_value' => $detail->estimated_unit_value,
                        'condition' => $detail->condition,
                        'condition_notes' => $detail->condition_notes ?? '',
                        'lot_number' => '',
                        'expiration_date' => '',
                        'notes' => $detail->notes ?? '',
                    ];
                }

                if (empty($this->details)) {
                    $this->addDetail();
                }

                return;
            }
        }

        $this->addDetail();
    }

    public function updatedCompanyId(): void
    {
        // Reset warehouse selection when company changes
        $this->warehouse_id = '';
    }

    public function updatedDonorId(): void
    {
        if ($this->donor_id) {
            $donor = Donor::find($this->donor_id);
            if ($donor) {
                $this->donor_name = $donor->name;
                $this->donor_type = $donor->donor_type ?? 'organization';
                $this->donor_contact = $donor->contact_person ?? '';
                $this->donor_email = $donor->email ?? '';
                $this->donor_phone = $donor->phone ?? '';
                $this->donor_address = $donor->address ?? '';
            }
        }
    }

    public function toggleDonorMode(): void
    {
        $this->use_existing_donor = !$this->use_existing_donor;
        if (!$this->use_existing_donor) {
            $this->donor_id = '';
        }
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->hasRole('super-admin');
    }

    public function addDetail(): void
    {
        $this->details[] = [
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
        $rules = [
            'warehouse_id' => 'required|exists:warehouses,id',
            'donor_name' => 'required_without:donor_id|string|max:255',
            'donor_id' => 'nullable|exists:donors,id',
            'donor_type' => 'required|in:individual,organization,government,ngo,international',
            'document_type' => 'required|in:acta,carta,convenio,otro',
            'document_date' => 'required|date',
            'reception_date' => 'required|date',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:0.0001',
            'details.*.estimated_unit_value' => 'required|numeric|min:0',
            'details.*.condition' => 'required|in:nuevo,usado,reacondicionado',
        ];

        // Add company_id validation for super admins
        if ($this->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $customAttributes = [
            'warehouse_id' => 'bodega',
            'donor_name' => 'nombre del donante',
            'donor_type' => 'tipo de donante',
            'document_type' => 'tipo de documento',
            'document_date' => 'fecha del documento',
            'reception_date' => 'fecha de recepción',
            'company_id' => 'empresa',
            'details.*.product_id' => 'producto',
            'details.*.quantity' => 'cantidad',
            'details.*.estimated_unit_value' => 'valor unitario estimado',
            'details.*.condition' => 'condición',
        ];

        $this->validate($rules, [], $customAttributes);

        \DB::transaction(function () {
            // Use selected company_id or user's company_id for non-super-admins
            $companyId = $this->isSuperAdmin() ? $this->company_id : auth()->user()->company_id;

            $donation = Donation::create([
                'company_id' => $companyId,
                'warehouse_id' => $this->warehouse_id,
                'donor_id' => $this->donor_id ?: null,
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
                'created_by' => auth()->id(),
            ]);

            foreach ($this->details as $detail) {
                DonationDetail::create([
                    'donation_id' => $donation->id,
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'estimated_unit_value' => $detail['estimated_unit_value'],
                    'condition' => $detail['condition'],
                    'condition_notes' => !empty($detail['condition_notes']) ? $detail['condition_notes'] : null,
                    'lot_number' => !empty($detail['lot_number']) ? $detail['lot_number'] : null,
                    'expiration_date' => !empty($detail['expiration_date']) ? $detail['expiration_date'] : null,
                    'notes' => !empty($detail['notes']) ? $detail['notes'] : null,
                ]);
            }

            $donation->calculateTotals();

            session()->flash('success', 'Donación creada exitosamente.');
            $this->redirect(route('donations.show', $donation->slug), navigate: true);
        });
    }

    #[Computed]
    public function companies()
    {
        if ($this->isSuperAdmin()) {
            return Company::active()->orderBy('name')->get();
        }

        return collect([]);
    }

    #[Computed]
    public function warehouses()
    {
        if ($this->isSuperAdmin()) {
            if (!$this->company_id) {
                return collect([]);
            }
            return Warehouse::where('company_id', $this->company_id)->active()->orderBy('name')->get();
        }

        // Non-super-admin users see only their company's warehouses
        return Warehouse::where('company_id', auth()->user()->company_id)->active()->orderBy('name')->get();
    }

    #[Computed]
    public function products()
    {
        if ($this->isSuperAdmin()) {
            if (!$this->company_id) {
                return collect([]);
            }
            return Product::with('unitOfMeasure')->where('company_id', $this->company_id)->active()->orderBy('name')->get();
        }

        // Non-super-admin users see only their company's products
        return Product::with('unitOfMeasure')->where('company_id', auth()->user()->company_id)->active()->orderBy('name')->get();
    }

    public function getProductUnit($productId): array
    {
        if (! $productId) {
            return ['abbreviation' => '', 'name' => ''];
        }

        $product = $this->products->firstWhere('id', $productId);

        return [
            'abbreviation' => $product?->unitOfMeasure?->abbreviation ?? '',
            'name' => $product?->unitOfMeasure?->name ?? '',
        ];
    }

    #[Computed]
    public function donors()
    {
        if ($this->isSuperAdmin()) {
            if (!$this->company_id) {
                return collect([]);
            }
            return Donor::where('company_id', $this->company_id)->active()->orderBy('name')->get();
        }

        // Non-super-admin users see only their company's donors
        return Donor::where('company_id', auth()->user()->company_id)->active()->orderBy('name')->get();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nueva Donación</flux:heading>
            <flux:text class="mt-1">Registrar una nueva donación recibida</flux:text>
        </div>

        <flux:button variant="ghost" href="{{ route('donations.index') }}" wire:navigate>
            Cancelar
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-8">
        <!-- Información Básica -->
        <flux:card>
            <flux:heading size="lg" class="mb-6">Información Básica</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($this->isSuperAdmin())
                    <flux:field class="md:col-span-2">
                        <flux:label badge="Requerido">Empresa</flux:label>
                        <flux:select wire:model.live="company_id">
                            <option value="">Seleccione una empresa</option>
                            @foreach ($this->companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="company_id" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label badge="Requerido">Bodega</flux:label>
                    <flux:select wire:model="warehouse_id" :disabled="$this->isSuperAdmin() && !$company_id" :description="$this->isSuperAdmin() && !$company_id ? 'Primero selecciona una empresa' : 'Bodega donde se recibirá la donación'">
                        <option value="">Seleccione bodega</option>
                        @foreach ($this->warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="warehouse_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Nombre del Proyecto</flux:label>
                    <flux:input wire:model="project_name" placeholder="Opcional" description="Nombre del proyecto o programa al que se destinará la donación" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Información del Donante -->
        <flux:card>
            <div class="flex items-center justify-between mb-6">
                <flux:heading size="lg">Información del Donante</flux:heading>
                <flux:button
                    type="button"
                    size="sm"
                    variant="ghost"
                    wire:click="toggleDonorMode"
                >
                    {{ $use_existing_donor ? 'Ingresar Manualmente' : 'Seleccionar Donante Existente' }}
                </flux:button>
            </div>

            @if($use_existing_donor)
                <!-- Donor Selector Mode -->
                <div class="space-y-6">
                    <flux:field>
                        <flux:label badge="Requerido">Donante</flux:label>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <flux:select wire:model.live="donor_id" :disabled="$this->isSuperAdmin() && !$company_id">
                                    <option value="">Seleccione un donante</option>
                                    @foreach ($this->donors as $donor)
                                        <option value="{{ $donor->id }}">
                                            {{ $donor->name }}
                                            @if($donor->donor_type)
                                                ({{ $donor->getDonorTypeLabel() }})
                                            @endif
                                        </option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <flux:button
                                type="button"
                                icon="plus"
                                href="{{ route('donors.create') }}"
                                target="_blank"
                                variant="primary"
                            >
                                Nuevo
                            </flux:button>
                        </div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                            @if($this->isSuperAdmin() && !$company_id)
                                Primero selecciona una empresa
                            @else
                                Seleccione el donante de la lista o cree uno nuevo
                            @endif
                        </flux:text>
                        <flux:error name="donor_id" />
                    </flux:field>

                    @if($donor_id)
                        <!-- Show selected donor details as read-only -->
                        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Nombre:</span>
                                    <span class="text-zinc-900 dark:text-zinc-100">{{ $donor_name }}</span>
                                </div>
                                @if($donor_type)
                                <div>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Tipo:</span>
                                    <span class="text-zinc-900 dark:text-zinc-100">{{ ucfirst($donor_type) }}</span>
                                </div>
                                @endif
                                @if($donor_contact)
                                <div>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Contacto:</span>
                                    <span class="text-zinc-900 dark:text-zinc-100">{{ $donor_contact }}</span>
                                </div>
                                @endif
                                @if($donor_email)
                                <div>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Email:</span>
                                    <span class="text-zinc-900 dark:text-zinc-100">{{ $donor_email }}</span>
                                </div>
                                @endif
                                @if($donor_phone)
                                <div>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Teléfono:</span>
                                    <span class="text-zinc-900 dark:text-zinc-100">{{ $donor_phone }}</span>
                                </div>
                                @endif
                                @if($donor_address)
                                <div class="md:col-span-2">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Dirección:</span>
                                    <span class="text-zinc-900 dark:text-zinc-100">{{ $donor_address }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <!-- Manual Entry Mode -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:field>
                        <flux:label badge="Requerido">Nombre del Donante</flux:label>
                        <flux:input wire:model="donor_name" />
                        <flux:error name="donor_name" />
                    </flux:field>

                    <flux:field>
                        <flux:label badge="Requerido">Tipo de Donante</flux:label>
                        <flux:select wire:model="donor_type">
                            <option value="individual">Persona Individual</option>
                            <option value="organization">Organización</option>
                            <option value="government">Gobierno</option>
                            <option value="ngo">ONG</option>
                            <option value="international">Organización Internacional</option>
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
            @endif
        </flux:card>

        <!-- Información del Documento -->
        <flux:card>
            <flux:heading size="lg" class="mb-6">Información del Documento</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label badge="Requerido">Tipo de Documento</flux:label>
                    <flux:select wire:model="document_type">
                        <option value="acta">Acta</option>
                        <option value="carta">Carta</option>
                        <option value="convenio">Convenio</option>
                        <option value="otro">Otro</option>
                    </flux:select>
                    <flux:error name="document_type" />
                </flux:field>

                <flux:field>
                    <flux:label>Número de Documento</flux:label>
                    <flux:input wire:model="document_number" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Fecha del Documento</flux:label>
                    <flux:input type="date" wire:model="document_date" />
                    <flux:error name="document_date" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Fecha de Recepción</flux:label>
                    <flux:input type="date" wire:model="reception_date" />
                    <flux:error name="reception_date" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Propósito y Uso -->
        <flux:card>
            <flux:heading size="lg" class="mb-6">Propósito y Uso</flux:heading>

            <div class="grid grid-cols-1 gap-6">
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
                    <flux:checkbox
                        wire:model="tax_receipt_required"
                        label="Requiere recibo fiscal"
                    />
                </flux:field>
            </div>
        </flux:card>

        <!-- Productos Donados -->
        <flux:card>
            <div class="flex items-center justify-between mb-6">
                <flux:heading size="lg" badge="Requerido">Productos Donados</flux:heading>
                <flux:button type="button" variant="primary" size="sm" icon="plus" wire:click="addDetail">
                    Agregar Producto
                </flux:button>
            </div>

            <div class="space-y-4">
                @foreach ($details as $index => $detail)
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg" wire:key="detail-{{ $index }}">
                        <div class="flex items-start justify-between mb-4">
                            <flux:heading size="sm">Producto #{{ $index + 1 }}</flux:heading>
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

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <flux:field class="md:col-span-2">
                                <flux:label badge="Requerido">Producto</flux:label>
                                <flux:select wire:model.live="details.{{ $index }}.product_id" :disabled="$this->isSuperAdmin() && !$company_id">
                                    <option value="">Seleccione producto</option>
                                    @foreach ($this->products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="details.{{ $index }}.product_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Requerido">Cantidad</flux:label>
                                <div class="flex items-center gap-2">
                                    <flux:input type="number" step="0.0001" wire:model="details.{{ $index }}.quantity" class="flex-1" />
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
                                <flux:error name="details.{{ $index }}.quantity" />
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Requerido">Valor Unitario Estimado</flux:label>
                                <flux:input type="number" step="0.01" wire:model="details.{{ $index }}.estimated_unit_value" />
                                <flux:error name="details.{{ $index }}.estimated_unit_value" />
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Requerido">Condición</flux:label>
                                <flux:select wire:model="details.{{ $index }}.condition">
                                    <option value="nuevo">Nuevo</option>
                                    <option value="usado">Usado</option>
                                    <option value="reacondicionado">Reacondicionado</option>
                                </flux:select>
                                <flux:error name="details.{{ $index }}.condition" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Número de Lote</flux:label>
                                <flux:input wire:model="details.{{ $index }}.lot_number" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Fecha de Vencimiento</flux:label>
                                <flux:input type="date" wire:model="details.{{ $index }}.expiration_date" />
                            </flux:field>

                            <flux:field class="md:col-span-2">
                                <flux:label>Notas de Condición</flux:label>
                                <flux:textarea wire:model="details.{{ $index }}.condition_notes" rows="2" />
                            </flux:field>

                            <flux:field class="md:col-span-2">
                                <flux:label>Notas Adicionales</flux:label>
                                <flux:textarea wire:model="details.{{ $index }}.notes" rows="2" />
                            </flux:field>
                        </div>
                    </div>
                @endforeach

                <flux:error name="details" />
            </div>
        </flux:card>

        <!-- Notas Generales -->
        <flux:card>
            <flux:heading size="lg" class="mb-6">Notas Generales</flux:heading>

            <flux:field>
                <flux:label>Notas Administrativas</flux:label>
                <flux:textarea wire:model="notes" rows="4" placeholder="Información adicional sobre la donación" />
            </flux:field>
        </flux:card>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-between">
            <flux:button variant="ghost" href="{{ route('donations.index') }}" wire:navigate type="button">
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                Guardar Donación
            </flux:button>
        </div>
    </form>
</div>
