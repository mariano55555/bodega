<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-violet-100 dark:bg-violet-900 rounded-lg">
                <flux:icon name="arrows-right-left" class="h-8 w-8 text-violet-600 dark:text-violet-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Flujos de Trabajo
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Traslados, Ajustes y diferencias con Movimientos
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Diferencias Clave -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Diferencias Clave entre Traslados, Ajustes y Movimientos
        </flux:heading>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Aspecto</th>
                        <th class="px-4 py-3 text-left font-medium">Traslados</th>
                        <th class="px-4 py-3 text-left font-medium">Ajustes</th>
                        <th class="px-4 py-3 text-left font-medium">Movimientos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3 font-medium">Accion</td>
                        <td class="px-4 py-3">Crear y Ejecutar</td>
                        <td class="px-4 py-3">Crear y Aprobar</td>
                        <td class="px-4 py-3">Ver y Revisar</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Proposito</td>
                        <td class="px-4 py-3">Mover stock entre bodegas</td>
                        <td class="px-4 py-3">Corregir discrepancias</td>
                        <td class="px-4 py-3">Auditar transacciones</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Impacto</td>
                        <td class="px-4 py-3">Crea 2 movimientos</td>
                        <td class="px-4 py-3">Crea 1 movimiento</td>
                        <td class="px-4 py-3">Solo muestra historial</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Aprobacion</td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Si</flux:badge></td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Si</flux:badge></td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">N/A</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Bodegas</td>
                        <td class="px-4 py-3">2 (origen + destino)</td>
                        <td class="px-4 py-3">1 (donde esta el producto)</td>
                        <td class="px-4 py-3">Vista de 1 bodega</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Traslados -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Traslados (Transfers)
        </flux:heading>

        <flux:card class="p-4 border-l-4 border-l-blue-500">
            <flux:heading size="md" class="mb-2">Proposito</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                Mover inventario <strong>entre diferentes bodegas/ubicaciones</strong>
            </flux:text>
        </flux:card>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <flux:heading size="sm" class="mb-2">Caracteristicas</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Involucra <strong>dos bodegas</strong>: origen y destino</li>
                    <li>Requiere flujo de aprobacion</li>
                    <li>Rastrea el movimiento fisico de productos</li>
                    <li>Requiere que la bodega destino confirme la recepcion</li>
                </ul>
            </div>
            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <flux:heading size="sm" class="mb-2">Flujo de Estados</flux:heading>
                <div class="flex flex-col gap-2 text-sm">
                    <div class="flex items-center gap-2">
                        <flux:badge color="zinc" size="sm">1. Borrador</flux:badge>
                        <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge color="yellow" size="sm">2. Pendiente</flux:badge>
                        <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge color="blue" size="sm">3. En Transito</flux:badge>
                        <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge color="green" size="sm">4. Recibido</flux:badge>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
            <flux:heading size="sm" class="mb-2">Casos de Uso</flux:heading>
            <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                <li>Mover inventario entre sucursales</li>
                <li>Redistribuir stock para balancear niveles de bodega</li>
                <li>Abastecer una sucursal desde la bodega central</li>
                <li>Reubicar productos para mejor distribucion</li>
            </ul>
        </div>
    </section>

    <!-- Ajustes -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Ajustes (Adjustments)
        </flux:heading>

        <flux:card class="p-4 border-l-4 border-l-purple-500">
            <flux:heading size="md" class="mb-2">Proposito</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                <strong>Corregir diferencias</strong> entre el inventario fisico y el sistema, con documentacion y justificacion.
            </flux:text>
        </flux:card>

        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300">Tipos de Ajuste</flux:heading>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded text-center">
                <flux:badge color="green" size="sm">Positivo</flux:badge>
                <flux:text class="text-xs mt-1">+Stock</flux:text>
            </div>
            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded text-center">
                <flux:badge color="red" size="sm">Negativo</flux:badge>
                <flux:text class="text-xs mt-1">-Stock</flux:text>
            </div>
            <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded text-center">
                <flux:badge color="orange" size="sm">Danado</flux:badge>
                <flux:text class="text-xs mt-1">-Stock</flux:text>
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded text-center">
                <flux:badge color="amber" size="sm">Vencido</flux:badge>
                <flux:text class="text-xs mt-1">-Stock</flux:text>
            </div>
            <div class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded text-center">
                <flux:badge color="rose" size="sm">Perdida/Robo</flux:badge>
                <flux:text class="text-xs mt-1">-Stock</flux:text>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-center">
                <flux:badge color="blue" size="sm">Correccion</flux:badge>
                <flux:text class="text-xs mt-1">+/- Stock</flux:text>
            </div>
            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded text-center">
                <flux:badge color="indigo" size="sm">Devolucion</flux:badge>
                <flux:text class="text-xs mt-1">+/- Stock</flux:text>
            </div>
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded text-center">
                <flux:badge color="zinc" size="sm">Otro</flux:badge>
                <flux:text class="text-xs mt-1">+/- Stock</flux:text>
            </div>
        </div>

        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <flux:heading size="sm" class="mb-2">Flujo de Trabajo de Ajustes</flux:heading>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <flux:badge color="zinc">Borrador</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="yellow">Pendiente</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="green">Aprobado</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="blue">Procesado</flux:badge>
            </div>
            <flux:text class="text-xs mt-2 text-zinc-500">
                Cuando un ajuste es procesado, el sistema crea automaticamente un movimiento de inventario.
            </flux:text>
        </div>
    </section>

    <!-- Movimientos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Movimientos (Movements)
        </flux:heading>

        <flux:card class="p-4 border-l-4 border-l-amber-500">
            <flux:heading size="md" class="mb-2">Proposito</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                <strong>Registrar/visualizar</strong> todas las transacciones de inventario que han ocurrido (solo lectura).
            </flux:text>
        </flux:card>

        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
            <flux:heading size="sm" class="mb-2">Incluye todos los tipos de transacciones</flux:heading>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                <div class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    <span>Compras (ingresos)</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    <span>Despachos (salidas)</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    <span>Traslados</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    <span>Donaciones</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    <span>Ajustes</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    <span>Cierres</span>
                </div>
            </div>
        </div>

        <flux:callout variant="warning" icon="exclamation-triangle">
            <strong>Importante:</strong> No se pueden crear movimientos directamente. Se generan automaticamente por otros modulos (Compras, Despachos, Traslados, Ajustes, etc.).
        </flux:callout>
    </section>

    <!-- Mejores Practicas -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Mejores Practicas
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-400">SI Hacer</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Siempre prefiere transacciones formales</li>
                    <li>Usa movimientos manuales con moderacion</li>
                    <li>Agrega notas detalladas para auditoria</li>
                    <li>Usa Ajustes para conteos fisicos</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-400">NO Hacer</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>No uses entrada/salida manual para compras normales</li>
                    <li>No hagas traslados con entradas/salidas manuales</li>
                    <li>No omitas la justificacion en ajustes</li>
                    <li>No ignores el flujo de aprobacion</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Enlaces Rapidos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Enlaces Rapidos
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:button variant="outline" icon="arrows-right-left" :href="route('transfers.index')" wire:navigate class="justify-start">
                Ir a Traslados
            </flux:button>
            <flux:button variant="outline" icon="adjustments-horizontal" :href="route('adjustments.index')" wire:navigate class="justify-start">
                Ir a Ajustes
            </flux:button>
            <flux:button variant="outline" icon="arrow-path" :href="route('inventory.movements.index')" wire:navigate class="justify-start">
                Ver Movimientos
            </flux:button>
        </div>
    </section>
</div>
