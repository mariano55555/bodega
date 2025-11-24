<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public string $typeFilter = '';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function notifications()
    {
        $query = auth()->user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($this->typeFilter) {
            $query->where('data->type', $this->typeFilter);
        }

        return $query->latest()->paginate(20);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function notificationTypes(): array
    {
        return [
            'low_stock' => 'Stock Bajo',
            'product_expiring' => 'Productos por Vencer',
            'closure_completed' => 'Cierres Completados',
            'adjustment_created' => 'Ajustes de Inventario',
            'purchase_approved' => 'Compras Aprobadas',
            'transfer_approved' => 'Traslados Aprobados',
            'transfer_shipped' => 'Traslados Enviados',
            'transfer_received' => 'Traslados Recibidos',
        ];
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function deleteNotification(string $notificationId): void
    {
        auth()->user()
            ->notifications()
            ->where('id', $notificationId)
            ->delete();
    }

    public function deleteAllRead(): void
    {
        auth()->user()
            ->notifications()
            ->whereNotNull('read_at')
            ->delete();
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Notificaciones</flux:heading>
            <flux:text class="mt-1">Administra tus notificaciones del sistema</flux:text>
        </div>
        <div class="flex gap-2">
            @if($this->unreadCount > 0)
                <flux:button variant="outline" icon="check" wire:click="markAllAsRead">
                    Marcar todas como leídas
                </flux:button>
            @endif
            <flux:button variant="ghost" icon="trash" wire:click="deleteAllRead" wire:confirm="¿Estás seguro de eliminar todas las notificaciones leídas?">
                Eliminar leídas
            </flux:button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:card class="p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="bell" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">Total</flux:text>
                    <flux:text class="text-2xl font-bold">{{ auth()->user()->notifications()->count() }}</flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-yellow-100 dark:bg-yellow-900/30">
                    <flux:icon name="envelope" class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">Sin Leer</flux:text>
                    <flux:text class="text-2xl font-bold">{{ $this->unreadCount }}</flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/30">
                    <flux:icon name="check-circle" class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">Leídas</flux:text>
                    <flux:text class="text-2xl font-bold">{{ auth()->user()->notifications()->whereNotNull('read_at')->count() }}</flux:text>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:field>
                <flux:label>Estado</flux:label>
                <flux:select wire:model.live="filter">
                    <flux:select.option value="all">Todas</flux:select.option>
                    <flux:select.option value="unread">Sin leer</flux:select.option>
                    <flux:select.option value="read">Leídas</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Tipo</flux:label>
                <flux:select wire:model.live="typeFilter">
                    <flux:select.option value="">Todos los tipos</flux:select.option>
                    @foreach($this->notificationTypes as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>
    </flux:card>

    <!-- Notifications List -->
    <flux:card>
        <div class="divide-y dark:divide-zinc-700">
            @forelse($this->notifications as $notification)
                @php
                    $data = $notification->data;
                    $iconColors = [
                        'yellow' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
                        'orange' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
                        'red' => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
                        'green' => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
                        'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
                        'indigo' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
                        'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
                    ];
                    $iconColorClass = $iconColors[$data['color'] ?? 'blue'] ?? $iconColors['blue'];
                    $typeLabels = $this->notificationTypes;
                @endphp
                <div class="flex items-start gap-4 p-4 {{ is_null($notification->read_at) ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}">
                    <!-- Icon -->
                    <div class="p-2 rounded-lg {{ $iconColorClass }} shrink-0">
                        <flux:icon :name="$data['icon'] ?? 'bell'" class="h-5 w-5" />
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <flux:text class="font-medium {{ is_null($notification->read_at) ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-700 dark:text-zinc-300' }}">
                                    {{ $data['message'] ?? 'Nueva notificación' }}
                                </flux:text>
                                <div class="flex items-center gap-2 mt-1">
                                    <flux:badge size="sm" color="{{ $data['color'] ?? 'blue' }}">
                                        {{ $typeLabels[$data['type'] ?? ''] ?? 'Sistema' }}
                                    </flux:badge>
                                    <flux:text class="text-xs text-zinc-500">
                                        {{ $notification->created_at->format('d/m/Y H:i') }}
                                        ({{ $notification->created_at->diffForHumans() }})
                                    </flux:text>
                                </div>
                            </div>

                            <!-- Unread indicator -->
                            @if(is_null($notification->read_at))
                                <span class="h-2 w-2 bg-blue-500 rounded-full shrink-0 mt-2"></span>
                            @endif
                        </div>

                        <!-- Additional details based on type -->
                        @if(isset($data['product_name']))
                            <flux:text class="text-sm text-zinc-500 mt-2">
                                Producto: {{ $data['product_name'] }}
                                @if(isset($data['product_sku']))
                                    ({{ $data['product_sku'] }})
                                @endif
                            </flux:text>
                        @endif

                        @if(isset($data['warehouse_name']))
                            <flux:text class="text-sm text-zinc-500">
                                Bodega: {{ $data['warehouse_name'] }}
                            </flux:text>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1 shrink-0">
                        @if(isset($data['url']))
                            <flux:button variant="ghost" size="sm" icon="arrow-top-right-on-square" :href="$data['url']" wire:navigate>
                                Ver
                            </flux:button>
                        @endif

                        @if(is_null($notification->read_at))
                            <flux:button variant="ghost" size="sm" icon="check" wire:click="markAsRead('{{ $notification->id }}')" title="Marcar como leída">
                            </flux:button>
                        @endif

                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteNotification('{{ $notification->id }}')" wire:confirm="¿Eliminar esta notificación?" title="Eliminar">
                        </flux:button>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center">
                    <flux:icon name="bell-slash" class="h-12 w-12 text-zinc-400 mx-auto mb-4" />
                    <flux:heading size="lg">No hay notificaciones</flux:heading>
                    <flux:text class="text-zinc-500 mt-2">
                        @if($filter === 'unread')
                            No tienes notificaciones sin leer
                        @elseif($filter === 'read')
                            No tienes notificaciones leídas
                        @else
                            No has recibido ninguna notificación aún
                        @endif
                    </flux:text>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($this->notifications->hasPages())
            <div class="mt-4 pt-4 border-t dark:border-zinc-700">
                {{ $this->notifications->links() }}
            </div>
        @endif
    </flux:card>
</div>
