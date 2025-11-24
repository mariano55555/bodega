<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-pink-100 dark:bg-pink-900 rounded-lg">
                <flux:icon name="gift" class="h-8 w-8 text-pink-600 dark:text-pink-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Donaciones
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Recepción de donaciones e ingreso al inventario
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Descripción -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Descripción General
        </flux:heading>
        <div class="prose dark:prose-invert max-w-none">
            <p>
                El módulo de <strong>Donaciones</strong> permite registrar las donaciones recibidas de instituciones,
                organizaciones o individuos. Al igual que las compras, las donaciones generan ingresos al inventario,
                pero se registran con un donante en lugar de un proveedor.
            </p>
        </div>
    </section>

    <!-- Estados -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estados de una Donación
        </flux:heading>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Estado</th>
                        <th class="px-4 py-3 text-left font-medium">Descripción</th>
                        <th class="px-4 py-3 text-left font-medium">Inventario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">Borrador</flux:badge></td>
                        <td class="px-4 py-3">En proceso de edición</td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="yellow" size="sm">Pendiente</flux:badge></td>
                        <td class="px-4 py-3">Esperando aprobación</td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="blue" size="sm">Aprobada</flux:badge></td>
                        <td class="px-4 py-3">Aprobada, lista para recibir</td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Recibida</flux:badge></td>
                        <td class="px-4 py-3">Productos recibidos</td>
                        <td class="px-4 py-3 text-green-600 font-medium">+Stock</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Flujo de Trabajo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Flujo de Trabajo
        </flux:heading>

        <div class="p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <flux:badge color="zinc">Borrador</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="yellow">Pendiente</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="blue">Aprobada</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="green">Recibida</flux:badge>
            </div>
        </div>
    </section>

    <!-- Información Requerida -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Información Requerida
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-pink-500">
                <flux:heading size="sm" class="mb-2">Datos de Cabecera</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Donante (obligatorio)</li>
                    <li>Bodega de destino (obligatorio)</li>
                    <li>Fecha de donación</li>
                    <li>Número de documento/acta</li>
                    <li>Propósito de la donación</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2">Detalle de Productos</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Producto (obligatorio)</li>
                    <li>Cantidad (obligatorio)</li>
                    <li>Valor estimado (opcional)</li>
                    <li>Número de lote (opcional)</li>
                    <li>Fecha de vencimiento (opcional)</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Movimientos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Movimientos de Inventario
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            Al recibir una donación, se generan movimientos de tipo <strong>"donation"</strong> que incrementan el stock.
        </flux:callout>
    </section>

    <!-- Enlaces Rápidos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Enlaces Rápidos
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:button variant="outline" icon="gift" :href="route('donations.index')" wire:navigate class="justify-start">
                Ir a Donaciones
            </flux:button>
            <flux:button variant="outline" icon="plus" :href="route('donations.create')" wire:navigate class="justify-start">
                Nueva Donación
            </flux:button>
            <flux:button variant="outline" icon="heart" :href="route('donors.index')" wire:navigate class="justify-start">
                Ver Donantes
            </flux:button>
        </div>
    </section>
</div>
