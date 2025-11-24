<?php

use Livewire\Volt\Component;
use App\Models\{UserActivityLog, User};
use Livewire\Attributes\{Computed, Layout};
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $action = '';
    public string $userId = '';
    public string $subjectType = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public bool $sensitiveOnly = false;

    public function mount(): void
    {
        $this->dateFrom = now()->subWeek()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    #[Computed]
    public function activityLogs()
    {
        $query = UserActivityLog::query()
            ->with(['user', 'company', 'subject']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                  ->orWhere('ip_address', 'like', "%{$this->search}%");
            });
        }

        if ($this->action) {
            $query->where('action', $this->action);
        }

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        if ($this->subjectType) {
            $query->where('subject_type', 'like', "%{$this->subjectType}%");
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        if ($this->sensitiveOnly) {
            $query->where('is_sensitive', true);
        }

        return $query->orderBy('created_at', 'desc')->paginate(25);
    }

    #[Computed]
    public function users()
    {
        return User::whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function activitySummary()
    {
        $logs = UserActivityLog::query()
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->get();

        return [
            'total_activities' => $logs->count(),
            'total_users' => $logs->pluck('user_id')->unique()->count(),
            'sensitive_count' => $logs->where('is_sensitive', true)->count(),
            'actions_count' => $logs->groupBy('action')->count(),
        ];
    }

    #[Computed]
    public function availableActions()
    {
        return UserActivityLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->action = '';
        $this->userId = '';
        $this->subjectType = '';
        $this->sensitiveOnly = false;
        $this->dateFrom = now()->subWeek()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function with(): array
    {
        return ['title' => __('Bitácora del Sistema')];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">Bitácora del Sistema</flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            Registro completo de todas las actividades realizadas en el sistema
        </flux:text>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <flux:card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-blue-600">Total Actividades</flux:text>
                    <flux:heading size="2xl" class="text-blue-900">{{ number_format($this->activitySummary['total_activities']) }}</flux:heading>
                </div>
                <flux:icon name="clipboard-document-list" class="h-8 w-8 text-blue-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-green-600">Usuarios Activos</flux:text>
                    <flux:heading size="2xl" class="text-green-900">{{ number_format($this->activitySummary['total_users']) }}</flux:heading>
                </div>
                <flux:icon name="users" class="h-8 w-8 text-green-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-red-600">Actividades Sensibles</flux:text>
                    <flux:heading size="2xl" class="text-red-900">{{ number_format($this->activitySummary['sensitive_count']) }}</flux:heading>
                </div>
                <flux:icon name="shield-exclamation" class="h-8 w-8 text-red-500" />
            </div>
        </flux:card>

        <flux:card class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-purple-600">Tipos de Acciones</flux:text>
                    <flux:heading size="2xl" class="text-purple-900">{{ number_format($this->activitySummary['actions_count']) }}</flux:heading>
                </div>
                <flux:icon name="document-duplicate" class="h-8 w-8 text-purple-500" />
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <div class="space-y-4">
            <flux:heading size="lg">Filtros</flux:heading>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Búsqueda</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Descripción, IP..."
                        icon="magnifying-glass" />
                </flux:field>

                <flux:field>
                    <flux:label>Acción</flux:label>
                    <flux:select wire:model.live="action" placeholder="Todas las acciones">
                        @foreach($this->availableActions as $act)
                        <flux:select.option value="{{ $act }}">{{ $act }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Usuario</flux:label>
                    <flux:select wire:model.live="userId" placeholder="Todos los usuarios">
                        @foreach($this->users as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de Entidad</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="subjectType"
                        placeholder="Ej: Product, Purchase..." />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha Desde</flux:label>
                    <flux:input type="date" wire:model.live="dateFrom" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha Hasta</flux:label>
                    <flux:input type="date" wire:model.live="dateTo" />
                </flux:field>
            </div>

            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <flux:switch wire:model.live="sensitiveOnly">
                    <flux:text size="sm">Mostrar solo actividades sensibles</flux:text>
                </flux:switch>

                <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                    Limpiar Filtros
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Activity Log Table -->
    <flux:card>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Fecha/Hora</flux:table.column>
                    <flux:table.column>Usuario</flux:table.column>
                    <flux:table.column>Acción</flux:table.column>
                    <flux:table.column>Descripción</flux:table.column>
                    <flux:table.column>Entidad</flux:table.column>
                    <flux:table.column>IP</flux:table.column>
                    <flux:table.column>Detalles</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->activityLogs as $log)
                    <flux:table.row wire:key="log-{{ $log->id }}" class="{{ $log->is_sensitive ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                        <flux:table.cell>
                            <flux:text class="font-medium">{{ $log->created_at->format('d/m/Y') }}</flux:text>
                            <flux:text class="text-sm text-zinc-500 block">{{ $log->created_at->format('H:i:s') }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($log->user)
                            <flux:text class="font-medium">{{ $log->user->name }}</flux:text>
                            <flux:text class="text-sm text-zinc-500 block">{{ $log->user->email }}</flux:text>
                            @else
                            <flux:text class="text-zinc-400">Sistema</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                            $actionColors = [
                                'created' => 'green',
                                'updated' => 'blue',
                                'deleted' => 'red',
                                'viewed' => 'zinc',
                                'exported' => 'purple',
                                'login' => 'green',
                                'logout' => 'zinc'
                            ];
                            @endphp
                            <flux:badge color="{{ $actionColors[$log->action] ?? 'zinc' }}" size="sm">
                                {{ ucfirst($log->action) }}
                            </flux:badge>
                            @if($log->is_sensitive)
                            <flux:badge color="red" size="xs" class="ml-1">Sensible</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text class="text-sm">{{ $log->description }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($log->subject_type)
                            <flux:text class="text-sm font-medium">{{ class_basename($log->subject_type) }}</flux:text>
                            @if($log->subject_id)
                            <flux:text class="text-xs text-zinc-500 block">ID: {{ $log->subject_id }}</flux:text>
                            @endif
                            @else
                            <flux:text class="text-zinc-400 text-sm">N/A</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text class="text-sm font-mono">{{ $log->ip_address ?? 'N/A' }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($log->old_values || $log->new_values || $log->properties)
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="eye" />
                                <flux:menu class="min-w-[300px]">
                                    <div class="p-3 max-h-96 overflow-y-auto">
                                        @if($log->old_values)
                                        <div class="mb-3">
                                            <flux:text class="font-medium text-sm">Valores Anteriores:</flux:text>
                                            <pre class="text-xs mt-1 p-2 bg-zinc-100 dark:bg-zinc-800 rounded">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                        @endif
                                        @if($log->new_values)
                                        <div class="mb-3">
                                            <flux:text class="font-medium text-sm">Valores Nuevos:</flux:text>
                                            <pre class="text-xs mt-1 p-2 bg-zinc-100 dark:bg-zinc-800 rounded">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                        @endif
                                        @if($log->properties)
                                        <div>
                                            <flux:text class="font-medium text-sm">Propiedades:</flux:text>
                                            <pre class="text-xs mt-1 p-2 bg-zinc-100 dark:bg-zinc-800 rounded">{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                        @endif
                                        @if($log->user_agent)
                                        <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                            <flux:text class="font-medium text-sm">User Agent:</flux:text>
                                            <flux:text class="text-xs text-zinc-600 mt-1">{{ $log->user_agent }}</flux:text>
                                        </div>
                                        @endif
                                    </div>
                                </flux:menu>
                            </flux:dropdown>
                            @else
                            <flux:text class="text-zinc-400 text-sm">-</flux:text>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-12">
                            <flux:icon name="clipboard-document-list" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                            <flux:text class="text-zinc-500">No se encontraron registros de actividad</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if($this->activityLogs->hasPages())
        <div class="mt-6 px-6 pb-6">
            {{ $this->activityLogs->links() }}
        </div>
        @endif
    </flux:card>
</div>
