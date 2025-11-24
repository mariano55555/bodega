<?php

use Livewire\Volt\Component;

new class extends Component
{
    public bool $showModal = false;

    protected $listeners = ['openKeyboardShortcuts' => 'openModal'];

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }
}; ?>

<div>
    <!-- Keyboard shortcut listener -->
    <div
        x-data="{
            init() {
                document.addEventListener('keydown', (e) => {
                    // Show shortcuts modal with ? or Ctrl+/
                    if ((e.key === '?' && !e.ctrlKey && !e.metaKey) ||
                        (e.key === '/' && (e.ctrlKey || e.metaKey))) {
                        e.preventDefault();
                        $wire.openModal();
                    }

                    // Global navigation shortcuts (only when not in input/textarea)
                    if (!['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
                        // g + d = Go to Dashboard
                        if (e.key === 'd' && this.lastKey === 'g') {
                            e.preventDefault();
                            window.location.href = '{{ route('dashboard') }}';
                        }
                        // g + i = Go to Inventory
                        if (e.key === 'i' && this.lastKey === 'g') {
                            e.preventDefault();
                            window.location.href = '{{ route('inventory.dashboard') }}';
                        }
                        // g + p = Go to Products
                        if (e.key === 'p' && this.lastKey === 'g') {
                            e.preventDefault();
                            window.location.href = '{{ route('inventory.products.index') }}';
                        }
                        // g + t = Go to Transfers
                        if (e.key === 't' && this.lastKey === 'g') {
                            e.preventDefault();
                            window.location.href = '{{ route('transfers.index') }}';
                        }
                        // g + c = Go to Purchases (Compras)
                        if (e.key === 'c' && this.lastKey === 'g') {
                            e.preventDefault();
                            window.location.href = '{{ route('purchases.index') }}';
                        }
                        // g + r = Go to Reports
                        if (e.key === 'r' && this.lastKey === 'g') {
                            e.preventDefault();
                            window.location.href = '{{ route('reports.kardex') }}';
                        }
                        // g + h = Go to Help
                        if (e.key === 'h' && this.lastKey === 'g') {
                            e.preventDefault();
                            window.location.href = '{{ route('help.index') }}';
                        }
                        // g + n = Go to Notifications
                        if (e.key === 'n' && this.lastKey === 'g') {
                            e.preventDefault();
                            window.location.href = '{{ route('notifications.index') }}';
                        }

                        // / = Focus search (if exists)
                        if (e.key === '/' && !e.ctrlKey && !e.metaKey) {
                            const searchInput = document.querySelector('[data-search-input]') ||
                                               document.querySelector('input[type=search]') ||
                                               document.querySelector('input[placeholder*=Buscar]');
                            if (searchInput) {
                                e.preventDefault();
                                searchInput.focus();
                            }
                        }

                        // Escape = Close modals, clear search
                        if (e.key === 'Escape') {
                            const searchInput = document.querySelector('[data-search-input]');
                            if (searchInput && document.activeElement === searchInput) {
                                searchInput.value = '';
                                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }

                        this.lastKey = e.key;
                        setTimeout(() => { this.lastKey = null; }, 500);
                    }
                });
            },
            lastKey: null
        }"
    ></div>

    <!-- Shortcuts Modal -->
    <flux:modal :open="$showModal" wire:model.boolean="showModal" class="max-w-2xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <flux:heading size="lg">Atajos de Teclado</flux:heading>
                    <flux:text class="text-zinc-500">Navega el sistema de forma rápida</flux:text>
                </div>
                <flux:button variant="ghost" icon="x-mark" wire:click="closeModal" />
            </div>

            <div class="space-y-6">
                <!-- General -->
                <div>
                    <flux:heading size="sm" class="mb-3 text-zinc-600 dark:text-zinc-400">General</flux:heading>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <span class="text-sm">Mostrar atajos</span>
                            <div class="flex gap-1">
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">?</kbd>
                                <span class="text-zinc-400">o</span>
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">Ctrl</kbd>
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">/</kbd>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <span class="text-sm">Enfocar búsqueda</span>
                            <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">/</kbd>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <span class="text-sm">Cerrar / Limpiar</span>
                            <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">Esc</kbd>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div>
                    <flux:heading size="sm" class="mb-3 text-zinc-600 dark:text-zinc-400">
                        Navegación <span class="text-xs font-normal">(presiona g + tecla)</span>
                    </flux:heading>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <span class="text-sm">Dashboard</span>
                            <div class="flex gap-1">
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">g</kbd>
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">d</kbd>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <span class="text-sm">Inventario</span>
                            <div class="flex gap-1">
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">g</kbd>
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">i</kbd>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <span class="text-sm">Productos</span>
                            <div class="flex gap-1">
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">g</kbd>
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">p</kbd>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <span class="text-sm">Traslados</span>
                            <div class="flex gap-1">
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">g</kbd>
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">t</kbd>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <span class="text-sm">Compras</span>
                            <div class="flex gap-1">
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">g</kbd>
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">c</kbd>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <span class="text-sm">Reportes</span>
                            <div class="flex gap-1">
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">g</kbd>
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">r</kbd>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <span class="text-sm">Notificaciones</span>
                            <div class="flex gap-1">
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">g</kbd>
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">n</kbd>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <span class="text-sm">Ayuda</span>
                            <div class="flex gap-1">
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">g</kbd>
                                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">h</kbd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Consejo:</strong> Los atajos de navegación funcionan presionando <kbd class="px-1 bg-blue-100 dark:bg-blue-800 rounded">g</kbd> seguido de la letra correspondiente dentro de medio segundo.
                    </flux:text>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t dark:border-zinc-700 flex justify-end">
                <flux:button wire:click="closeModal">Cerrar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
