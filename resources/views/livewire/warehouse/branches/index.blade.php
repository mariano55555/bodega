<?php

use Livewire\Volt\Component;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
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
    public string $companyFilter = 'all';
    public string $typeFilter = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', Branch::class);
    }

    #[Computed]
    public function branches()
    {
        return Branch::query()
            ->with(['company', 'manager', 'warehouses'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('code', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhere('address', 'like', '%' . $this->search . '%')
                        ->orWhere('city', 'like', '%' . $this->search . '%')
                        ->orWhereHas('company', function ($companyQuery) {
                            $companyQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'active') {
                    $query->active();
                } else {
                    $query->where('is_active', false);
                }
            })
            ->when($this->companyFilter !== 'all', function ($query) {
                $query->where('company_id', $this->companyFilter);
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $query->whereJsonContains('settings->type', $this->typeFilter);
            })
            ->withCount(['warehouses'])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(12);
    }

    #[Computed]
    public function companies()
    {
        return Company::active()->orderBy('name')->get();
    }

    #[Computed]
    public function branchTypes()
    {
        return [
            'warehouse' => __('warehouse.branch_type_warehouse'),
            'retail' => __('warehouse.branch_type_retail'),
            'office' => __('warehouse.branch_type_office'),
            'distribution' => __('warehouse.branch_type_distribution'),
            'manufacturing' => __('warehouse.branch_type_manufacturing'),
            'service' => __('warehouse.branch_type_service')
        ];
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

    public function deleteBranch(Branch $branch): void
    {
        $this->authorize('delete', $branch);

        $branch->delete();

        $this->dispatch('branch-deleted', [
            'message' => __('warehouse.branch_deleted_successfully')
        ]);
    }

    public function toggleStatus(Branch $branch): void
    {
        $this->authorize('update', $branch);

        $branch->update(['is_active' => !$branch->is_active]);

        $this->dispatch('branch-status-toggled', [
            'message' => $branch->is_active
                ? __('warehouse.branch_activated_successfully')
                : __('warehouse.branch_deactivated_successfully')
        ]);
    }

    public function with(): array
    {
        return [
            'title' => __('warehouse.branch_management'),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    {{ __('warehouse.branch_management') }}
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.manage_branches_description') }}
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="outline" icon="arrow-path" wire:click="$refresh">
                    {{ __('ui.refresh') }}
                </flux:button>
                @can('create', App\Models\Branch::class)
                    <flux:button variant="primary" icon="plus" :href="route('warehouse.branches.create')" wire:navigate>
                        {{ __('warehouse.new_branch') }}
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
                    :placeholder="__('warehouse.search_branches_placeholder')"
                    icon="magnifying-glass"
                />
            </div>

            <!-- Company Filter -->
            <div class="lg:w-48">
                <flux:select wire:model.live="companyFilter" :placeholder="__('warehouse.filter_by_company')">
                    <flux:select.option value="all">{{ __('warehouse.all_companies') }}</flux:select.option>
                    @foreach($this->companies as $company)
                        <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Type Filter -->
            <div class="lg:w-48">
                <flux:select wire:model.live="typeFilter" :placeholder="__('warehouse.filter_by_type')">
                    <flux:select.option value="all">{{ __('ui.all_types') }}</flux:select.option>
                    @foreach($this->branchTypes as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Status Filter -->
            <div class="lg:w-40">
                <flux:select wire:model.live="statusFilter" :placeholder="__('ui.status')">
                    <flux:select.option value="all">{{ __('ui.all') }}</flux:select.option>
                    <flux:select.option value="active">{{ __('ui.active') }}</flux:select.option>
                    <flux:select.option value="inactive">{{ __('ui.inactive') }}</flux:select.option>
                </flux:select>
            </div>

            <!-- Sort Options -->
            <div class="lg:w-48">
                <flux:select wire:model.live="sortBy" :placeholder="__('ui.sort_by')">
                    <flux:select.option value="name">{{ __('ui.name') }}</flux:select.option>
                    <flux:select.option value="created_at">{{ __('ui.created_at') }}</flux:select.option>
                    <flux:select.option value="updated_at">{{ __('ui.updated_at') }}</flux:select.option>
                    <flux:select.option value="warehouses_count">{{ __('warehouse.warehouses_count') }}</flux:select.option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    <!-- Branches Grid -->
    @if($this->branches->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($this->branches as $branch)
                <flux:card class="hover:shadow-lg transition-shadow duration-200">
                    <flux:heading>
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:heading size="lg" class="truncate">
                                        {{ $branch->name }}
                                    </flux:heading>
                                    <flux:badge :color="$branch->is_active ? 'green' : 'red'" size="sm">
                                        {{ $branch->is_active ? __('ui.active') : __('ui.inactive') }}
                                    </flux:badge>
                                </div>
                                @if($branch->code)
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ __('ui.code') }}: {{ $branch->code }}
                                    </flux:text>
                                @endif
                                <flux:text class="text-sm text-blue-600 dark:text-blue-400 font-medium">
                                    {{ $branch->company->name }}
                                </flux:text>
                                @if($branch->settings && isset($branch->settings['type']))
                                    <flux:badge color="zinc" size="sm" class="mt-1">
                                        {{ $this->branchTypes[$branch->settings['type']] ?? ucfirst($branch->settings['type']) }}
                                    </flux:badge>
                                @endif
                            </div>

                            <!-- Action Dropdown -->
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                <flux:menu>
                                    @can('view', $branch)
                                        <flux:menu.item icon="eye" :href="route('warehouse.branches.edit', $branch)" wire:navigate>
                                            {{ __('ui.view') }}
                                        </flux:menu.item>
                                    @endcan
                                    @can('update', $branch)
                                        <flux:menu.item icon="pencil" :href="route('warehouse.branches.edit', $branch)" wire:navigate>
                                            {{ __('ui.edit') }}
                                        </flux:menu.item>
                                        <flux:menu.item
                                            icon="{{ $branch->is_active ? 'eye-slash' : 'eye' }}"
                                            wire:click="toggleStatus({{ $branch->id }})"
                                        >
                                            {{ $branch->is_active ? __('ui.deactivate') : __('ui.activate') }}
                                        </flux:menu.item>
                                    @endcan
                                    @can('delete', $branch)
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            icon="trash"
                                            variant="danger"
                                            wire:click="deleteBranch({{ $branch->id }})"
                                            :wire:confirm="__('warehouse.confirm_delete_branch')"
                                        >
                                            {{ __('ui.delete') }}
                                        </flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </flux:heading>

                    <!-- Branch Details -->
                    <div class="space-y-3">
                        @if($branch->description)
                            <flux:text class="text-sm line-clamp-2">
                                {{ $branch->description }}
                            </flux:text>
                        @endif

                        <!-- Manager Info -->
                        @if($branch->manager)
                            <div class="flex items-center gap-2">
                                <flux:icon name="user" class="h-4 w-4 text-zinc-400" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ __('warehouse.manager') }}: {{ $branch->manager->name }}
                                </flux:text>
                            </div>
                        @endif

                        <!-- Stats -->
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <div class="text-center">
                                <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                                    {{ $branch->warehouses_count }}
                                </flux:heading>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ __('warehouse.warehouses') }}
                                </flux:text>
                            </div>
                            <div class="text-center">
                                <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">
                                    {{ $branch->warehouses->sum('total_capacity') ?? 0 }}
                                </flux:heading>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ __('warehouse.total_capacity') }}
                                </flux:text>
                            </div>
                        </div>

                        <!-- Address -->
                        @if($branch->address || $branch->city)
                            <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="map-pin" class="h-4 w-4 text-zinc-400" />
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ collect([$branch->address, $branch->city, $branch->state])->filter()->implode(', ') }}
                                    </flux:text>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs text-zinc-500">
                            <span>{{ __('ui.created') }}: {{ $branch->created_at->format('M d, Y') }}</span>
                            @if($branch->updated_at != $branch->created_at)
                                <span>{{ __('ui.updated') }}: {{ $branch->updated_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            {{ $this->branches->links() }}
        </div>
    @else
        <!-- Empty State -->
        <flux:card class="text-center py-12">
            <flux:icon name="building-office" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">
                {{ $search ? __('ui.no_results') : __('warehouse.no_branches') }}
            </flux:heading>
            <flux:text class="text-zinc-500 mb-6">
                @if($search)
                    {{ __('warehouse.no_branches_found_for') }} "{{ $search }}"
                @else
                    {{ __('warehouse.no_branches_description') }}
                @endif
            </flux:text>
            @if(!$search)
                @can('create', App\Models\Branch::class)
                    <flux:button variant="primary" icon="plus" :href="route('warehouse.branches.create')" wire:navigate>
                        {{ __('warehouse.create_first_branch') }}
                    </flux:button>
                @endcan
            @else
                <flux:button variant="outline" wire:click="$set('search', '')">
                    {{ __('ui.clear_search') }}
                </flux:button>
            @endif
        </flux:card>
    @endif
</div>
