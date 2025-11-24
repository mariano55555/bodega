<?php

use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public int $limit = 5;

    #[Computed]
    public function notifications()
    {
        return auth()->user()
            ->notifications()
            ->latest()
            ->take($this->limit)
            ->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
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
}; ?>

<div>
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" class="relative" icon="bell">
            @if($this->unreadCount > 0)
                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-medium text-white">
                    {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
                </span>
            @endif
        </flux:button>

        <flux:menu class="w-80">
            <div class="flex items-center justify-between px-4 py-2 border-b dark:border-zinc-700">
                <flux:text class="font-semibold">Notificaciones</flux:text>
                @if($this->unreadCount > 0)
                    <flux:button variant="ghost" size="xs" wire:click="markAllAsRead">
                        Marcar todas como leídas
                    </flux:button>
                @endif
            </div>

            <div class="max-h-80 overflow-y-auto">
                @forelse($this->notifications as $notification)
                    @php
                        $data = $notification->data;
                        $iconColors = [
                            'yellow' => 'text-yellow-500',
                            'orange' => 'text-orange-500',
                            'red' => 'text-red-500',
                            'green' => 'text-green-500',
                            'blue' => 'text-blue-500',
                            'indigo' => 'text-indigo-500',
                            'purple' => 'text-purple-500',
                        ];
                        $iconColor = $iconColors[$data['color'] ?? 'blue'] ?? 'text-blue-500';
                    @endphp
                    <flux:menu.item
                        :href="$data['url'] ?? '#'"
                        wire:click="markAsRead('{{ $notification->id }}')"
                        wire:navigate
                        class="{{ is_null($notification->read_at) ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                    >
                        <div class="flex items-start gap-3 py-1">
                            <flux:icon :name="$data['icon'] ?? 'bell'" class="h-5 w-5 {{ $iconColor }} shrink-0 mt-0.5" />
                            <div class="flex-1 min-w-0">
                                <flux:text class="text-sm {{ is_null($notification->read_at) ? 'font-semibold' : '' }}">
                                    {{ $data['message'] ?? 'Nueva notificación' }}
                                </flux:text>
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $notification->created_at->diffForHumans() }}
                                </flux:text>
                            </div>
                            @if(is_null($notification->read_at))
                                <span class="h-2 w-2 bg-blue-500 rounded-full shrink-0 mt-2"></span>
                            @endif
                        </div>
                    </flux:menu.item>
                @empty
                    <div class="px-4 py-8 text-center">
                        <flux:icon name="bell-slash" class="h-10 w-10 text-zinc-400 mx-auto mb-2" />
                        <flux:text class="text-zinc-500">No hay notificaciones</flux:text>
                    </div>
                @endforelse
            </div>

            @if($this->notifications->count() > 0)
                <flux:menu.separator />
                <flux:menu.item :href="route('notifications.index')" wire:navigate class="text-center">
                    <flux:text class="text-sm text-blue-600 dark:text-blue-400">Ver todas las notificaciones</flux:text>
                </flux:menu.item>
            @endif
        </flux:menu>
    </flux:dropdown>
</div>
