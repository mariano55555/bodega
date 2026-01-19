<?php

use Livewire\Volt\Component;
use App\Models\Area;
use App\Models\Company;
use App\Http\Requests\UpdateAreaRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public Area $area;
    public ?int $company_id = null;
    public $name;
    public $code;
    public $description;
    public $is_active;

    public function mount(Area $area): void
    {
        $this->area = $area;
        $this->fill($area->only(['company_id', 'name', 'code', 'description', 'is_active']));
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

    public function update(): void
    {
        $validated = $this->validate((new UpdateAreaRequest())->rules(), (new UpdateAreaRequest())->messages());

        $this->area->update($validated);

        \Flux\Flux::toast(
            'Área actualizada exitosamente',
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
            'title' => __('Editar Área'),
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
        <flux:heading size="xl">Editar Área</flux:heading>
        <flux:text class="mt-2">Modifica {{ $area->name }}</flux:text>
    </div>

    <form wire:submit="update">
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
                <flux:input wire:model="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Código (Opcional)</flux:label>
                <flux:input wire:model="code" />
                <flux:description>Código único interno del área</flux:description>
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>Descripción (Opcional)</flux:label>
                <flux:textarea wire:model="description" rows="3" />
                <flux:error name="description" />
            </flux:field>

            <flux:switch wire:model="is_active" description="Las áreas inactivas no estarán disponibles para asignar personas">
                <flux:text>Área activa</flux:text>
            </flux:switch>

            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-500">
                    <strong>Personas asignadas:</strong> {{ $area->employees()->count() }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="cancel">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar Cambios</flux:button>
            </div>
        </flux:card>
    </form>
</div>
