<?php

use Livewire\Volt\Component;
use App\Models\Area;
use App\Models\Company;
use App\Http\Requests\StoreAreaRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public $company_id = '';
    public $name = '';
    public $code = '';
    public $description = '';
    public $is_active = true;

    public function mount(): void
    {
        if (! $this->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->hasRole('super-admin');
    }

    #[Computed]
    public function companies()
    {
        return Company::active()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate((new StoreAreaRequest())->rules(), (new StoreAreaRequest())->messages());

        if (! $this->isSuperAdmin()) {
            $validated['company_id'] = auth()->user()->company_id;
        }

        Area::create($validated);

        \Flux\Flux::toast(
            'Área creada exitosamente',
            variant: 'success'
        );

        $this->redirect(route('admin.areas.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('admin.areas.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => __('Crear Área'),
        ];
    }
}; ?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="cancel">
                Volver
            </flux:button>
        </div>
        <flux:heading size="xl">Crear Nueva Área</flux:heading>
        <flux:text class="mt-2">Agrega un área organizacional para tu empresa</flux:text>
    </div>

    <form wire:submit="save">
        <flux:card class="space-y-6">
            @if($this->isSuperAdmin())
                <flux:field>
                    <flux:label badge="Requerido">Empresa</flux:label>
                    <flux:select variant="listbox" wire:model="company_id" placeholder="Seleccione una empresa">
                        @foreach($this->companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="company_id" />
                </flux:field>
            @endif

            <flux:field>
                <flux:label badge="Requerido">Nombre del Área</flux:label>
                <flux:input wire:model="name" placeholder="Ej: Contabilidad, Informática, Colecturía" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Código (Opcional)</flux:label>
                <flux:input wire:model="code" placeholder="Ej: CONT, INFO, COLE" />
                <flux:description>Código único interno del área</flux:description>
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>Descripción (Opcional)</flux:label>
                <flux:textarea wire:model="description" rows="3" placeholder="Descripción del área..." />
                <flux:error name="description" />
            </flux:field>

            <flux:switch wire:model="is_active" description="Las áreas inactivas no estarán disponibles para asignar personas">
                <flux:text>Área activa</flux:text>
            </flux:switch>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="cancel">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Crear Área
                </flux:button>
            </div>
        </flux:card>
    </form>
</div>
