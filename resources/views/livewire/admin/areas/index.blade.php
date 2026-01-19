<?php

use Livewire\Volt\Component;
use App\Models\Area;
use App\Models\Company;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $company_id = null;

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

    #[Computed]
    public function areas()
    {
        $query = Area::query()
            ->with(['company', 'createdBy']);

        if ($this->company_id) {
            $query->where('company_id', $this->company_id);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            });
        }

        return $query->withCount('employees')
            ->orderBy('name')
            ->paginate(20);
    }

    public function delete(Area $area): void
    {
        if ($area->employees()->exists()) {
            \Flux\Flux::toast(
                'No se puede eliminar el área porque tiene personas asignadas.',
                variant: 'danger'
            );
            return;
        }

        $area->delete();
        \Flux\Flux::toast(
            'Área eliminada exitosamente.',
            variant: 'success'
        );
    }

    public function toggleStatus(int $areaId): void
    {
        $area = Area::find($areaId);

        if (! $area) {
            \Flux\Flux::toast('Área no encontrada.', variant: 'danger');
            return;
        }

        $area->is_active = ! $area->is_active;
        $area->active_at = $area->is_active ? now() : null;
        $area->save();

        \Flux\Flux::toast(
            $area->is_active ? 'Área activada.' : 'Área desactivada.',
            variant: 'success'
        );
    }

    public function updatedCompanyId(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'title' => __('Áreas'),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Áreas</flux:heading>
                <flux:text class="mt-2">Gestiona las áreas organizacionales de tu empresa</flux:text>
            </div>
            <flux:button variant="primary" icon="plus" href="{{ route('admin.areas.create') }}" wire:navigate>
                Nueva Área
            </flux:button>
        </div>
    </div>

    <flux:card>
        <div class="mb-6 space-y-4">
            @if($this->isSuperAdmin())
                <flux:field>
                    <flux:label>Filtrar por Empresa</flux:label>
                    <flux:select variant="listbox" wire:model.live="company_id" placeholder="Todas las empresas">
                        @foreach($this->companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <flux:input wire:model.live.debounce.300ms="search"
                       placeholder="Buscar áreas..."
                       icon="magnifying-glass" />
        </div>

        <flux:table>
            <flux:table.columns>
                @if($this->isSuperAdmin())
                    <flux:table.column>Empresa</flux:table.column>
                @endif
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Código</flux:table.column>
                <flux:table.column>Personas</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($this->areas as $area)
                <flux:table.row wire:key="area-{{ $area->id }}">
                    @if($this->isSuperAdmin())
                        <flux:table.cell>
                            <flux:text class="font-medium">{{ $area->company->name }}</flux:text>
                        </flux:table.cell>
                    @endif
                    <flux:table.cell>
                        <flux:text class="font-medium">{{ $area->name }}</flux:text>
                        @if($area->description)
                            <flux:text size="sm" class="text-zinc-500 block">{{ Str::limit($area->description, 50) }}</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($area->code)
                            <flux:badge color="zinc">{{ $area->code }}</flux:badge>
                        @else
                            <flux:text class="text-zinc-400 text-sm">—</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:text>{{ $area->employees_count }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($area->is_active)
                            <flux:badge color="green">Activo</flux:badge>
                        @else
                            <flux:badge color="red">Inactivo</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-1">
                            <flux:button variant="ghost" size="sm" icon="pencil" href="{{ route('admin.areas.edit', $area->slug) }}" wire:navigate title="Editar" />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                :icon="$area->is_active ? 'x-circle' : 'check-circle'"
                                wire:click="toggleStatus({{ $area->id }})"
                                :title="$area->is_active ? 'Desactivar' : 'Activar'"
                            />
                            @if($area->employees_count === 0)
                                <flux:button variant="ghost" size="sm" icon="trash" wire:click="delete({{ $area->id }})" wire:confirm="¿Estás seguro de eliminar esta área?" title="Eliminar" class="text-red-600 hover:text-red-700 dark:text-red-500 dark:hover:text-red-400" />
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell :colspan="$this->isSuperAdmin() ? 6 : 5" class="text-center py-12">
                        <flux:text class="text-zinc-500">
                            @if($this->isSuperAdmin() && !$this->company_id)
                                Selecciona una empresa para ver sus áreas
                            @else
                                No se encontraron áreas
                            @endif
                        </flux:text>
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($this->areas->hasPages())
        <div class="mt-6">
            {{ $this->areas->links() }}
        </div>
        @endif
    </flux:card>
</div>
