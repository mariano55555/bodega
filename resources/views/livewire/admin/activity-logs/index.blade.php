<?php

use Livewire\Volt\Component;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'user')]
    public string $userFilter = '';

    #[Url(as: 'log_name')]
    public string $logNameFilter = '';

    #[Url(as: 'event')]
    public string $eventFilter = '';

    #[Url(as: 'desde')]
    public string $dateFrom = '';

    #[Url(as: 'hasta')]
    public string $dateTo = '';

    #[Url(as: 'ordenar')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    #[Url(as: 'por_pagina')]
    public int $perPage = 25;

    // Modal states
    public bool $showDetailsModal = false;
    public ?int $selectedLogId = null;

    // Cleanup options
    public int $cleanupDays = 30;

    public function mount(): void
    {
        // Set default date range (last 7 days)
        if (empty($this->dateFrom)) {
            $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        }
        if (empty($this->dateTo)) {
            $this->dateTo = now()->format('Y-m-d');
        }
    }

    public function cleanupLogs(): void
    {
        // Only super-admin can cleanup logs
        if (! auth()->user()->hasRole('super-admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }

        $countBefore = Activity::count();

        // Run the cleanup command with the specified days
        Artisan::call('activitylog:clean', [
            '--days' => $this->cleanupDays,
            '--force' => true,
        ]);

        $countAfter = Activity::count();
        $deleted = $countBefore - $countAfter;

        // Log this action
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'deleted_count' => $deleted,
                'days_threshold' => $this->cleanupDays,
            ])
            ->log('Limpieza de bitácora de actividades');

        session()->flash('success', "Se eliminaron {$deleted} registros anteriores a {$this->cleanupDays} días.");

        // Reset cleanup days to default
        $this->cleanupDays = 30;
    }

    #[Computed]
    public function selectedLog()
    {
        return $this->selectedLogId ? Activity::find($this->selectedLogId) : null;
    }

    #[Computed]
    public function activityLogs()
    {
        return Activity::query()
            ->with(['causer'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('description', 'like', '%' . $this->search . '%')
                        ->orWhere('properties', 'like', '%' . $this->search . '%')
                        ->orWhereHasMorph('causer', [User::class], function ($userQuery) {
                            $userQuery->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->userFilter, function ($query) {
                $query->where('causer_type', User::class)
                    ->where('causer_id', $this->userFilter);
            })
            ->when($this->logNameFilter, function ($query) {
                $query->where('log_name', $this->logNameFilter);
            })
            ->when($this->eventFilter, function ($query) {
                if ($this->eventFilter === '_null') {
                    $query->whereNull('event');
                } else {
                    $query->where('event', $this->eventFilter);
                }
            })
            ->when($this->dateFrom && $this->dateTo, function ($query) {
                $query->whereBetween('created_at', [
                    Carbon::parse($this->dateFrom)->startOfDay(),
                    Carbon::parse($this->dateTo)->endOfDay(),
                ]);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function users()
    {
        return User::orderBy('name')->get();
    }

    #[Computed]
    public function availableLogNames()
    {
        return Activity::distinct()->pluck('log_name')->filter()->sort()->values();
    }

    #[Computed]
    public function availableEvents(): array
    {
        // Return all event types including manual logs (null event shows as "Actividad")
        // and standard model events from Spatie ActivityLog
        return [
            '_null' => 'Actividad',  // Manual logs without event type (login, logout, etc.)
            'created' => 'Creado',
            'updated' => 'Actualizado',
            'deleted' => 'Eliminado',
            'restored' => 'Restaurado',
        ];
    }

    #[Computed]
    public function stats()
    {
        $baseQuery = Activity::query();

        if ($this->dateFrom && $this->dateTo) {
            $baseQuery->whereBetween('created_at', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ]);
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'users' => (clone $baseQuery)->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id'),
            'events' => (clone $baseQuery)->whereNotNull('event')->distinct('event')->count('event'),
            'subjects' => (clone $baseQuery)->whereNotNull('subject_type')->distinct('subject_type')->count('subject_type'),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedUserFilter(): void
    {
        $this->resetPage();
    }

    public function updatedLogNameFilter(): void
    {
        $this->resetPage();
    }

    public function updatedEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->userFilter = '';
        $this->logNameFilter = '';
        $this->eventFilter = '';
        $this->dateTo = now()->format('Y-m-d');
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->resetPage();
    }

    public function setDateRange(string $range): void
    {
        $this->dateTo = now()->format('Y-m-d');

        match ($range) {
            'today' => $this->dateFrom = now()->format('Y-m-d'),
            'yesterday' => $this->dateFrom = now()->subDay()->format('Y-m-d'),
            'week' => $this->dateFrom = now()->subWeek()->format('Y-m-d'),
            'month' => $this->dateFrom = now()->subMonth()->format('Y-m-d'),
            'quarter' => $this->dateFrom = now()->subQuarter()->format('Y-m-d'),
            'year' => $this->dateFrom = now()->subYear()->format('Y-m-d'),
            default => $this->dateFrom = now()->subDays(7)->format('Y-m-d'),
        };

        $this->resetPage();
    }

    public function showDetails(int $logId): void
    {
        $this->selectedLogId = $logId;
        $this->showDetailsModal = true;
    }

    public function sort($field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function getEventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Creado',
            'updated' => 'Actualizado',
            'deleted' => 'Eliminado',
            'restored' => 'Restaurado',
            default => ucfirst($event),
        };
    }

    public function getEventColor(string $event): string
    {
        return match ($event) {
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'restored' => 'yellow',
            default => 'zinc',
        };
    }

    public function with(): array
    {
        return [
            'title' => 'Bitácora de Actividades',
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Bitácora de Actividades
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Registro completo de actividades del sistema y auditoría de usuarios
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="outline" icon="arrow-path" wire:click="$refresh">
                    Actualizar
                </flux:button>

                @if(auth()->user()->hasRole('super-admin'))
                    <flux:modal.trigger name="cleanup-modal">
                        <flux:button variant="danger" icon="trash">
                            Limpiar Registros
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" class="mb-6">
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle" class="mb-6">
            {{ session('error') }}
        </flux:callout>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <flux:card class="p-4">
            <div class="flex items-center gap-3">
                <flux:icon name="clipboard-document-list" class="h-8 w-8 text-blue-500" />
                <div>
                    <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                        {{ number_format($this->stats['total']) }}
                    </flux:heading>
                    <flux:text class="text-sm text-zinc-500">Actividades Totales</flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-center gap-3">
                <flux:icon name="users" class="h-8 w-8 text-green-500" />
                <div>
                    <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                        {{ number_format($this->stats['users']) }}
                    </flux:heading>
                    <flux:text class="text-sm text-zinc-500">Usuarios Activos</flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-center gap-3">
                <flux:icon name="cog" class="h-8 w-8 text-purple-500" />
                <div>
                    <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">
                        {{ number_format($this->stats['events']) }}
                    </flux:heading>
                    <flux:text class="text-sm text-zinc-500">Tipos de Eventos</flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-center gap-3">
                <flux:icon name="cube" class="h-8 w-8 text-amber-500" />
                <div>
                    <flux:heading size="lg" class="text-amber-600 dark:text-amber-400">
                        {{ number_format($this->stats['subjects']) }}
                    </flux:heading>
                    <flux:text class="text-sm text-zinc-500">Tipos de Objetos</flux:text>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters and Search -->
    <flux:card class="mb-6">
        <div class="space-y-4">
            <!-- Search -->
            <div>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por descripción, IP, usuario o email..."
                    icon="magnifying-glass"
                />
            </div>

            <!-- Filters Row 1 -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- User Filter -->
                <flux:field>
                    <flux:label>Usuario</flux:label>
                    <flux:select wire:model.live="userFilter" placeholder="Todos los usuarios">
                        <flux:select.option value="">Todos los usuarios</flux:select.option>
                        @foreach($this->users as $user)
                            <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Log Name Filter -->
                <flux:field>
                    <flux:label>Registro</flux:label>
                    <flux:select wire:model.live="logNameFilter" placeholder="Todos los registros">
                        <flux:select.option value="">Todos los registros</flux:select.option>
                        @foreach($this->availableLogNames as $logName)
                            <flux:select.option value="{{ $logName }}">{{ ucfirst($logName) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Event Filter -->
                <flux:field>
                    <flux:label>Tipo de Evento</flux:label>
                    <flux:select wire:model.live="eventFilter" placeholder="Todos los eventos">
                        <flux:select.option value="">Todos los eventos</flux:select.option>
                        @foreach($this->availableEvents as $eventKey => $eventLabel)
                            <flux:select.option value="{{ $eventKey }}">{{ $eventLabel }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Per Page -->
                <flux:field>
                    <flux:label>Por Página</flux:label>
                    <flux:select wire:model.live="perPage">
                        <flux:select.option value="10">10 por página</flux:select.option>
                        <flux:select.option value="25">25 por página</flux:select.option>
                        <flux:select.option value="50">50 por página</flux:select.option>
                        <flux:select.option value="100">100 por página</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>

            <!-- Date Range and Controls -->
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-end">
                <!-- Date Range -->
                <div class="grid grid-cols-2 gap-4 flex-1">
                    <flux:field>
                        <flux:label>Fecha Desde</flux:label>
                        <flux:input type="date" wire:model.live="dateFrom" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Fecha Hasta</flux:label>
                        <flux:input type="date" wire:model.live="dateTo" />
                    </flux:field>
                </div>

                <!-- Quick Date Ranges -->
                <div class="flex flex-wrap gap-2">
                    <flux:button size="sm" variant="outline" wire:click="setDateRange('today')">Hoy</flux:button>
                    <flux:button size="sm" variant="outline" wire:click="setDateRange('yesterday')">Ayer</flux:button>
                    <flux:button size="sm" variant="outline" wire:click="setDateRange('week')">7 días</flux:button>
                    <flux:button size="sm" variant="outline" wire:click="setDateRange('month')">30 días</flux:button>
                </div>

                <!-- Clear Filters -->
                <flux:button variant="outline" icon="x-mark" wire:click="clearFilters">
                    Limpiar
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Activity Logs Table -->
    @if($this->activityLogs->count() > 0)
        <flux:card>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-zinc-200 dark:border-zinc-700">
                        <tr class="text-left">
                            <th class="pb-3 cursor-pointer" wire:click="sort('created_at')">
                                <div class="flex items-center gap-1">
                                    Fecha
                                    @if($sortBy === 'created_at')
                                        <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-4 w-4" />
                                    @endif
                                </div>
                            </th>
                            <th class="pb-3 cursor-pointer" wire:click="sort('causer_id')">
                                <div class="flex items-center gap-1">
                                    Usuario
                                    @if($sortBy === 'causer_id')
                                        <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-4 w-4" />
                                    @endif
                                </div>
                            </th>
                            <th class="pb-3 cursor-pointer" wire:click="sort('event')">
                                <div class="flex items-center gap-1">
                                    Evento
                                    @if($sortBy === 'event')
                                        <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-4 w-4" />
                                    @endif
                                </div>
                            </th>
                            <th class="pb-3">Descripción</th>
                            <th class="pb-3">Objeto</th>
                            <th class="pb-3">Registro</th>
                            <th class="pb-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->activityLogs as $log)
                            <tr wire:key="log-{{ $log->id }}" class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="py-4">
                                    <div class="text-sm">
                                        <div class="font-medium">{{ $log->created_at->format('d/m/Y') }}</div>
                                        <div class="text-zinc-500">{{ $log->created_at->format('H:i:s') }}</div>
                                    </div>
                                </td>
                                <td class="py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-medium text-xs">
                                            {{ $log->causer?->initials() ?? '?' }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-medium text-sm truncate">{{ $log->causer?->name ?? 'Sistema' }}</div>
                                            <div class="text-xs text-zinc-500 truncate">{{ $log->causer?->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4">
                                    <div class="flex items-center gap-2">
                                        @if($log->event)
                                            <flux:badge
                                                color="{{ $this->getEventColor($log->event) }}"
                                                variant="outline"
                                                size="sm"
                                            >
                                                {{ $this->getEventLabel($log->event) }}
                                            </flux:badge>
                                        @else
                                            <flux:badge color="zinc" variant="outline" size="sm">Actividad</flux:badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4">
                                    <div class="text-sm max-w-xs truncate" title="{{ $log->description }}">
                                        {{ $log->description }}
                                    </div>
                                </td>
                                <td class="py-4">
                                    @if($log->subject_type)
                                        <div class="text-sm">
                                            <div class="font-medium">{{ class_basename($log->subject_type) }}</div>
                                            <div class="text-xs text-zinc-500">#{{ $log->subject_id }}</div>
                                        </div>
                                    @else
                                        <flux:text class="text-sm text-zinc-400">-</flux:text>
                                    @endif
                                </td>
                                <td class="py-4">
                                    <flux:badge color="zinc" variant="outline" size="sm">
                                        {{ ucfirst($log->log_name ?? 'default') }}
                                    </flux:badge>
                                </td>
                                <td class="py-4 text-center">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="eye"
                                        wire:click="showDetails({{ $log->id }})"
                                    >
                                        Ver
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500">
                    Mostrando {{ $this->activityLogs->firstItem() ?? 0 }} a {{ $this->activityLogs->lastItem() ?? 0 }}
                    de {{ $this->activityLogs->total() }} actividades
                </flux:text>

                {{ $this->activityLogs->links() }}
            </div>
        </flux:card>
    @else
        <!-- Empty State -->
        <flux:card class="text-center py-12">
            <flux:icon name="clipboard-document-list" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">
                {{ $search || $userFilter || $logNameFilter || $eventFilter ? 'No se encontraron actividades' : 'No hay actividades registradas' }}
            </flux:heading>
            <flux:text class="text-zinc-500 mb-6">
                @if($search || $userFilter || $logNameFilter || $eventFilter)
                    No hay actividades que coincidan con los filtros aplicados
                @else
                    Las actividades del sistema aparecerán aquí cuando los usuarios interactúen con la plataforma
                @endif
            </flux:text>
            @if($search || $userFilter || $logNameFilter || $eventFilter)
                <flux:button variant="outline" wire:click="clearFilters">
                    Limpiar filtros
                </flux:button>
            @endif
        </flux:card>
    @endif

    <!-- Activity Details Modal -->
    <flux:modal wire:model="showDetailsModal">
        @if($this->selectedLog)
            <div class="p-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <flux:heading size="lg" class="mb-2">Detalles de la Actividad</flux:heading>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                            {{ $this->selectedLog->description }}
                        </flux:text>
                    </div>
                    @if($this->selectedLog->event)
                        <flux:badge color="{{ $this->getEventColor($this->selectedLog->event) }}">
                            {{ $this->getEventLabel($this->selectedLog->event) }}
                        </flux:badge>
                    @endif
                </div>

                <div class="space-y-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Usuario</flux:text>
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-medium text-xs">
                                    {{ $this->selectedLog->causer?->initials() ?? '?' }}
                                </div>
                                <div>
                                    <div class="font-medium text-sm">{{ $this->selectedLog->causer?->name ?? 'Sistema' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $this->selectedLog->causer?->email }}</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Fecha y Hora</flux:text>
                            <flux:text class="text-sm">{{ $this->selectedLog->created_at->format('d/m/Y H:i:s') }}</flux:text>
                            <flux:text class="text-xs text-zinc-500">{{ $this->selectedLog->created_at->diffForHumans() }}</flux:text>
                        </div>

                        <div>
                            <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Registro</flux:text>
                            <flux:badge color="zinc" variant="outline">
                                {{ ucfirst($this->selectedLog->log_name ?? 'default') }}
                            </flux:badge>
                        </div>

                        <div>
                            <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Batch UUID</flux:text>
                            <flux:text class="text-sm font-mono">{{ $this->selectedLog->batch_uuid ?? 'N/A' }}</flux:text>
                        </div>
                    </div>

                    <!-- Subject Details -->
                    @if($this->selectedLog->subject_type)
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Objeto Afectado</flux:text>
                            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-zinc-500">Tipo de Objeto:</span>
                                    <span class="font-mono">{{ class_basename($this->selectedLog->subject_type) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-zinc-500">ID del Objeto:</span>
                                    <span class="font-mono">{{ $this->selectedLog->subject_id }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Properties (includes old/new values for Spatie) -->
                    @if($this->selectedLog->properties && $this->selectedLog->properties->count() > 0)
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Propiedades</flux:text>

                            @if($this->selectedLog->properties->has('old') || $this->selectedLog->properties->has('attributes'))
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @if($this->selectedLog->properties->has('old'))
                                        <div>
                                            <flux:text class="text-xs font-medium text-zinc-500 mb-2">Valores Anteriores</flux:text>
                                            <pre class="text-xs bg-red-50 dark:bg-red-900/20 p-3 rounded overflow-x-auto max-h-48">{{ json_encode($this->selectedLog->properties->get('old'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                    @if($this->selectedLog->properties->has('attributes'))
                                        <div>
                                            <flux:text class="text-xs font-medium text-zinc-500 mb-2">Valores Nuevos</flux:text>
                                            <pre class="text-xs bg-green-50 dark:bg-green-900/20 p-3 rounded overflow-x-auto max-h-48">{{ json_encode($this->selectedLog->properties->get('attributes'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <pre class="text-xs bg-zinc-50 dark:bg-zinc-800 p-4 rounded overflow-x-auto max-h-64">{{ json_encode($this->selectedLog->properties->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex justify-end mt-6">
                    <flux:button variant="outline" wire:click="$set('showDetailsModal', false)">
                        Cerrar
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- Cleanup Modal (Super Admin Only) -->
    @if(auth()->user()->hasRole('super-admin'))
        <flux:modal name="cleanup-modal" class="max-w-md">
            <div class="p-6 space-y-6">
                <div>
                    <flux:heading size="lg">Limpiar Bitácora de Actividades</flux:heading>
                    <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                        Esta acción eliminará permanentemente los registros antiguos de la bitácora.
                    </flux:text>
                </div>

                <flux:callout variant="warning" icon="exclamation-triangle">
                    <strong>Advertencia:</strong> Esta acción no se puede deshacer. Los registros eliminados no podrán ser recuperados.
                </flux:callout>

                <flux:field>
                    <flux:label>Eliminar registros anteriores a:</flux:label>
                    <flux:select wire:model="cleanupDays">
                        <flux:select.option value="7">7 días</flux:select.option>
                        <flux:select.option value="14">14 días</flux:select.option>
                        <flux:select.option value="30">30 días</flux:select.option>
                        <flux:select.option value="60">60 días</flux:select.option>
                        <flux:select.option value="90">90 días</flux:select.option>
                        <flux:select.option value="180">180 días</flux:select.option>
                        <flux:select.option value="365">1 año</flux:select.option>
                    </flux:select>
                    <flux:description>
                        Se eliminarán todos los registros creados hace más de {{ $cleanupDays }} días.
                    </flux:description>
                </flux:field>

                <div class="flex justify-end gap-3">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" icon="trash" wire:click="cleanupLogs" wire:confirm="¿Está seguro de que desea eliminar los registros antiguos? Esta acción no se puede deshacer.">
                        Eliminar Registros
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
