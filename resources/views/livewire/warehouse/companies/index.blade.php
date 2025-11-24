<?php

use Livewire\Volt\Component;
use App\Models\Company;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';
    public string $statusFilter = 'all';
    public ?int $companyToDelete = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Company::class);
    }

    #[Computed]
    public function companies()
    {
        return Company::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('tax_id', 'like', '%' . $this->search . '%')
                        ->orWhere('registration_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'active') {
                    $query->active();
                } else {
                    $query->where('is_active', false);
                }
            })
            ->withCount(['branches', 'warehouses', 'users'])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(12);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy($field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function confirmDelete(int $companyId): void
    {
        $this->companyToDelete = $companyId;
        $this->modal('delete-company')->show();
    }

    public function deleteCompany(): void
    {
        if (! $this->companyToDelete) {
            return;
        }

        $company = Company::findOrFail($this->companyToDelete);
        $this->authorize('delete', $company);

        $company->delete();

        $this->companyToDelete = null;
        $this->modal('delete-company')->close();

        $this->dispatch('company-deleted', [
            'message' => __('warehouse.company_deleted')
        ]);
    }

    public function toggleStatus(int $companyId): void
    {
        $company = Company::findOrFail($companyId);
        $this->authorize('update', $company);

        $company->update(['is_active' => !$company->is_active]);

        $this->dispatch('company-status-toggled', [
            'message' => $company->is_active
                ? __('warehouse.company_activated')
                : __('warehouse.company_deactivated')
        ]);
    }

    public function with(): array
    {
        return [
            'title' => __('warehouse.company_management'),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    {{ __('warehouse.company_management') }}
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.manage_companies_description') }}
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="outline" icon="arrow-path" wire:click="$refresh">
                    {{ __('ui.refresh') }}
                </flux:button>
                @can('create', App\Models\Company::class)
                    <flux:button variant="primary" icon="plus" :href="route('warehouse.companies.create')" wire:navigate>
                        {{ __('warehouse.add_company') }}
                    </flux:button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <flux:card class="mb-6">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('ui.search_placeholder') }} {{ strtolower(__('warehouse.companies')) }}..."
                    icon="magnifying-glass"
                />
            </div>

            <!-- Status Filter -->
            <div class="lg:w-48">
                <flux:select wire:model.live="statusFilter" placeholder="{{ __('ui.filter_by') }} {{ strtolower(__('ui.status')) }}">
                    <flux:select.option value="all">{{ __('ui.all') }} {{ __('warehouse.companies') }}</flux:select.option>
                    <flux:select.option value="active">{{ __('ui.active') }}</flux:select.option>
                    <flux:select.option value="inactive">{{ __('ui.inactive') }}</flux:select.option>
                </flux:select>
            </div>

            <!-- Sort Options -->
            <div class="lg:w-48">
                <flux:select wire:model.live="sortBy" placeholder="{{ __('ui.sort_by') }}">
                    <flux:select.option value="name">{{ __('warehouse.company_name') }}</flux:select.option>
                    <flux:select.option value="created_at">{{ __('ui.created_at') }}</flux:select.option>
                    <flux:select.option value="updated_at">{{ __('ui.updated_at') }}</flux:select.option>
                    <flux:select.option value="branches_count">{{ __('warehouse.branches') }}</flux:select.option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    <!-- Companies Grid -->
    @if($this->companies->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($this->companies as $company)
                <flux:card class="hover:shadow-lg transition-shadow duration-200">
                    <flux:heading>
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:heading size="lg" class="truncate">
                                        {{ $company->name }}
                                    </flux:heading>
                                    <flux:badge :color="$company->is_active ? 'green' : 'red'" size="sm">
                                        {{ $company->is_active ? __('ui.active') : __('ui.inactive') }}
                                    </flux:badge>
                                </div>
                                @if($company->tax_id)
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ __('warehouse.tax_id') }}: {{ $company->tax_id }}
                                    </flux:text>
                                @endif
                                @if($company->email)
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ $company->email }}
                                    </flux:text>
                                @endif
                            </div>

                            <!-- Action Dropdown -->
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                <flux:menu>
                                    @can('view', $company)
                                        <flux:menu.item icon="eye" :href="route('warehouse.companies.edit', $company)" wire:navigate>
                                            {{ __('ui.view') }}
                                        </flux:menu.item>
                                    @endcan
                                    @can('update', $company)
                                        <flux:menu.item icon="pencil" :href="route('warehouse.companies.edit', $company)" wire:navigate>
                                            {{ __('ui.edit') }}
                                        </flux:menu.item>
                                        <flux:menu.item
                                            icon="{{ $company->is_active ? 'eye-slash' : 'eye' }}"
                                            wire:click="toggleStatus({{ $company->id }})"
                                        >
                                            {{ $company->is_active ? __('ui.deactivate') : __('ui.activate') }}
                                        </flux:menu.item>
                                    @endcan
                                    @can('delete', $company)
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            icon="trash"
                                            variant="danger"
                                            wire:click="confirmDelete({{ $company->id }})"
                                        >
                                            {{ __('ui.delete') }}
                                        </flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </flux:heading>

                    <!-- Company Stats -->
                    <div class="space-y-3">
                        @if($company->description)
                            <flux:text class="text-sm line-clamp-2">
                                {{ $company->description }}
                            </flux:text>
                        @endif

                        <div class="grid grid-cols-3 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <div class="text-center">
                                <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                                    {{ $company->branches_count }}
                                </flux:heading>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ __('warehouse.branches') }}
                                </flux:text>
                            </div>
                            <div class="text-center">
                                <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                                    {{ $company->warehouses_count }}
                                </flux:heading>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ __('warehouse.warehouses') }}
                                </flux:text>
                            </div>
                            <div class="text-center">
                                <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">
                                    {{ $company->users_count }}
                                </flux:heading>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ __('warehouse.users') }}
                                </flux:text>
                            </div>
                        </div>

                        @if($company->address || $company->city)
                            <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="map-pin" class="h-4 w-4 text-zinc-400" />
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $company->full_address }}
                                    </flux:text>
                                </div>
                            </div>
                        @endif

                        @if($company->website)
                            <div class="pt-2">
                                <a href="{{ $company->website }}" target="_blank" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    <flux:icon name="globe-alt" class="h-4 w-4" />
                                    {{ __('warehouse.website') }}
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="pt-4 mt-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between text-xs text-zinc-500">
                        <span>{{ __('ui.created_at') }}: {{ $company->created_at->format('M d, Y') }}</span>
                        @if($company->updated_at != $company->created_at)
                            <span>{{ __('ui.updated_at') }}: {{ $company->updated_at->diffForHumans() }}</span>
                        @endif
                    </div>
                </flux:card>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            {{ $this->companies->links() }}
        </div>
    @else
        <!-- Empty State -->
        <flux:card class="text-center py-12">
            <flux:icon name="building-office-2" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">
                {{ $search ? __('ui.no_results') : __('warehouse.no_companies') }}
            </flux:heading>
            <flux:text class="text-zinc-500 mb-6">
                @if($search)
                    {{ __('ui.no_results_for') }} "{{ $search }}"
                @else
                    {{ __('warehouse.no_companies_description') }}
                @endif
            </flux:text>
            @if(!$search)
                @can('create', App\Models\Company::class)
                    <flux:button variant="primary" icon="plus" :href="route('warehouse.companies.create')" wire:navigate>
                        {{ __('warehouse.create_company') }}
                    </flux:button>
                @endcan
            @else
                <flux:button variant="outline" wire:click="$set('search', '')">
                    {{ __('ui.clear_search') }}
                </flux:button>
            @endif
        </flux:card>
    @endif

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-company" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('warehouse.delete_company_title') }}</flux:heading>
                <flux:text class="mt-2">
                    <p>{{ __('warehouse.delete_company_confirmation') }}</p>
                    <p class="mt-2 text-red-600 dark:text-red-400">{{ __('ui.this_action_cannot_be_undone') }}</p>
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('ui.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteCompany">{{ __('ui.delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
