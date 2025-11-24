<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-cyan-100 dark:bg-cyan-900 rounded-lg">
                <flux:icon name="arrow-path" class="h-8 w-8 text-cyan-600 dark:text-cyan-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Traslados
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Movimiento de productos entre bodegas de la misma empresa
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
                El módulo de <strong>Traslados</strong> gestiona el movimiento de productos entre diferentes bodegas
                de la misma empresa. Cada traslado involucra una bodega de origen (donde sale el producto) y una
                bodega de destino (donde llega el producto).
            </p>
        </div>
    </section>

    <!-- Estados de un Traslado -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estados de un Traslado
        </flux:heading>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Estado</th>
                        <th class="px-4 py-3 text-left font-medium">Descripción</th>
                        <th class="px-4 py-3 text-left font-medium">Bodega Origen</th>
                        <th class="px-4 py-3 text-left font-medium">Bodega Destino</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="yellow" size="sm">Pendiente</flux:badge></td>
                        <td class="px-4 py-3">Esperando aprobación</td>
                        <td class="px-4 py-3">Sin cambios</td>
                        <td class="px-4 py-3">Sin cambios</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="blue" size="sm">Aprobado</flux:badge></td>
                        <td class="px-4 py-3">Listo para enviar</td>
                        <td class="px-4 py-3">Sin cambios</td>
                        <td class="px-4 py-3">Sin cambios</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="orange" size="sm">En Tránsito</flux:badge></td>
                        <td class="px-4 py-3">Productos en camino</td>
                        <td class="px-4 py-3 text-red-600 font-medium">-Stock</td>
                        <td class="px-4 py-3">Sin cambios</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Recibido</flux:badge></td>
                        <td class="px-4 py-3">Productos recibidos</td>
                        <td class="px-4 py-3">Ya descontado</td>
                        <td class="px-4 py-3 text-green-600 font-medium">+Stock</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">Cancelado</flux:badge></td>
                        <td class="px-4 py-3">Traslado cancelado</td>
                        <td class="px-4 py-3">Sin cambios</td>
                        <td class="px-4 py-3">Sin cambios</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Modalidades de Traslado -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Modalidades de Traslado
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="md" class="mb-2">Traslado Completo</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                    Proceso con aprobación y seguimiento de estados.
                </flux:text>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Requiere aprobación antes de enviar</li>
                    <li>Seguimiento de estado paso a paso</li>
                    <li>Datos de transportista y tracking</li>
                    <li>Permite registrar discrepancias</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-cyan-500">
                <flux:heading size="md" class="mb-2">Traslado Rápido</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                    Proceso simplificado en un solo paso.
                </flux:text>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>No requiere aprobación</li>
                    <li>Procesamiento instantáneo</li>
                    <li>Ideal para bodegas cercanas</li>
                    <li>Stock actualizado inmediatamente</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Flujo de Trabajo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Flujo de Trabajo Completo
        </flux:heading>

        <div class="p-4 bg-cyan-50 dark:bg-cyan-900/20 rounded-lg">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <flux:badge color="yellow">Pendiente</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="blue">Aprobado</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="orange">En Tránsito</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="green">Recibido</flux:badge>
            </div>
            <flux:text class="text-xs mt-2 text-zinc-500">
                El stock de origen se reduce al enviar. El stock de destino aumenta al recibir.
            </flux:text>
        </div>
    </section>

    <!-- Pasos del Proceso -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Pasos del Proceso
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-4 p-4 bg-yellow-50 dark:bg-yellow-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400 font-bold text-sm">
                    1
                </div>
                <div>
                    <flux:heading size="sm">Crear Traslado</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Seleccione bodega origen, destino, y agregue los productos a trasladar con sus cantidades.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm">
                    2
                </div>
                <div>
                    <flux:heading size="sm">Aprobar Traslado</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Un supervisor revisa y aprueba el traslado. Puede agregar notas de aprobación.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-orange-50 dark:bg-orange-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400 font-bold text-sm">
                    3
                </div>
                <div>
                    <flux:heading size="sm">Enviar (Ship)</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Se registra el envío con datos de transporte. <strong>El stock de origen se reduce.</strong>
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-green-50 dark:bg-green-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 font-bold text-sm">
                    4
                </div>
                <div>
                    <flux:heading size="sm">Recibir (Receive)</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        La bodega destino confirma la recepción. <strong>El stock de destino aumenta.</strong>
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Movimientos Generados -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Movimientos de Inventario
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <flux:heading size="sm" class="mb-2">Al Enviar (Bodega Origen)</flux:heading>
                <div class="font-mono text-sm text-zinc-700 dark:text-zinc-300">
                    <div>Tipo: <span class="text-red-600">transfer_out</span></div>
                    <div>Cantidad: <span class="text-red-600">-10 sacos</span></div>
                    <div>Saldo: 40 → 30 sacos</div>
                </div>
            </div>

            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <flux:heading size="sm" class="mb-2">Al Recibir (Bodega Destino)</flux:heading>
                <div class="font-mono text-sm text-zinc-700 dark:text-zinc-300">
                    <div>Tipo: <span class="text-green-600">transfer_in</span></div>
                    <div>Cantidad: <span class="text-green-600">+10 sacos</span></div>
                    <div>Saldo: 5 → 15 sacos</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Discrepancias -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Registro de Discrepancias
        </flux:heading>

        <flux:callout variant="warning" icon="exclamation-triangle">
            Si la cantidad recibida difiere de la enviada, se puede registrar una discrepancia al momento de recibir el traslado.
        </flux:callout>

        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                <strong>Ejemplo:</strong> Se enviaron 10 sacos pero solo llegaron 9. Al recibir, se registra:
            </flux:text>
            <ul class="text-sm mt-2 space-y-1 text-zinc-600 dark:text-zinc-400">
                <li>Cantidad esperada: 10 sacos</li>
                <li>Cantidad recibida: 9 sacos</li>
                <li>Motivo: "Un saco dañado en tránsito"</li>
            </ul>
        </div>
    </section>

    <!-- Restricciones -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Restricciones Importantes
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-400">No Permitido</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Trasladar entre empresas diferentes</li>
                    <li>Cancelar traslados en tránsito</li>
                    <li>Cancelar traslados recibidos</li>
                    <li>Editar traslados aprobados</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-400">Permitido</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Editar traslados pendientes</li>
                    <li>Cancelar antes de enviar</li>
                    <li>Registrar discrepancias al recibir</li>
                    <li>Agregar notas en cualquier paso</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Enlaces Rápidos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Enlaces Rápidos
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:button variant="outline" icon="arrow-path" :href="route('transfers.index')" wire:navigate class="justify-start">
                Ir a Traslados
            </flux:button>
            <flux:button variant="outline" icon="plus" :href="route('transfers.create')" wire:navigate class="justify-start">
                Nuevo Traslado
            </flux:button>
            <flux:button variant="outline" icon="building-office" :href="route('warehouse.warehouses.index')" wire:navigate class="justify-start">
                Ver Bodegas
            </flux:button>
        </div>
    </section>
</div>
