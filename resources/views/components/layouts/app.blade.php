<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>

    <!-- Global Keyboard Shortcuts -->
    @auth
        <livewire:components.keyboard-shortcuts />
    @endauth
</x-layouts.app.sidebar>
