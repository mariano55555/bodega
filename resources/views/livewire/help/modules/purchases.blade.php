<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <flux:icon name="shopping-cart" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Compras
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Registro de compras e ingreso de productos al inventario
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
                El módulo de <strong>Compras</strong> permite registrar las adquisiciones de productos que ingresan al inventario.
                Cada compra genera automáticamente los movimientos de inventario correspondientes y actualiza el stock disponible.
            </p>
        </div>
    </section>

    <!-- Estados de una Compra -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estados de una Compra
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
                        <td class="px-4 py-3">Compra en proceso de edición</td>
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
                        <td class="px-4 py-3">Productos recibidos en bodega</td>
                        <td class="px-4 py-3 text-green-600 font-medium">+Stock</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">Cancelada</flux:badge></td>
                        <td class="px-4 py-3">Compra cancelada</td>
                        <td class="px-4 py-3">No afecta</td>
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

        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <flux:badge color="zinc">Borrador</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="yellow">Pendiente</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="blue">Aprobada</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="green">Recibida</flux:badge>
            </div>
            <flux:text class="text-xs mt-2 text-zinc-500">
                El inventario solo se actualiza cuando la compra es marcada como "Recibida".
            </flux:text>
        </div>
    </section>

    <!-- Crear una Compra -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Crear una Compra
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-4 p-4 bg-green-50 dark:bg-green-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 font-bold text-sm">
                    1
                </div>
                <div>
                    <flux:heading size="sm">Información Básica</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Seleccione el proveedor, bodega de destino, fecha de compra y número de factura.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm">
                    2
                </div>
                <div>
                    <flux:heading size="sm">Agregar Productos</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Seleccione los productos, cantidades y precios unitarios. Puede agregar múltiples líneas.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 font-bold text-sm">
                    3
                </div>
                <div>
                    <flux:heading size="sm">Guardar y Enviar</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Guarde como borrador o envíe directamente para aprobación.
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Información Requerida -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Información Requerida
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2">Datos de Cabecera</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Proveedor (obligatorio)</li>
                    <li>Bodega de destino (obligatorio)</li>
                    <li>Fecha de compra</li>
                    <li>Número de factura</li>
                    <li>Notas adicionales</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2">Detalle de Productos</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Producto (obligatorio)</li>
                    <li>Cantidad (obligatorio)</li>
                    <li>Precio unitario</li>
                    <li>Número de lote (opcional)</li>
                    <li>Fecha de vencimiento (opcional)</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Movimientos Generados -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Movimientos de Inventario
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            Al recibir una compra, se generan movimientos de tipo <strong>"purchase"</strong> (compra) que incrementan el stock de la bodega destino.
        </flux:callout>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg font-mono text-sm">
            <div class="text-zinc-500 mb-2">Ejemplo de movimiento generado:</div>
            <div class="space-y-1 text-zinc-700 dark:text-zinc-300">
                <div>Tipo: <span class="text-green-600">purchase (entrada)</span></div>
                <div>Producto: Fertilizante NPK 15-15-15</div>
                <div>Cantidad: <span class="text-green-600">+50 sacos</span></div>
                <div>Saldo anterior: 100 sacos</div>
                <div>Nuevo saldo: <span class="font-bold">150 sacos</span></div>
            </div>
        </div>
    </section>

    <!-- Enlaces Rápidos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Enlaces Rápidos
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:button variant="outline" icon="shopping-cart" :href="route('purchases.index')" wire:navigate class="justify-start">
                Ir a Compras
            </flux:button>
            <flux:button variant="outline" icon="plus" :href="route('purchases.create')" wire:navigate class="justify-start">
                Nueva Compra
            </flux:button>
            <flux:button variant="outline" icon="building-storefront" :href="route('purchases.suppliers.index')" wire:navigate class="justify-start">
                Ver Proveedores
            </flux:button>
        </div>
    </section>
</div>
