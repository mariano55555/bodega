<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-lg">
                <flux:icon name="truck" class="h-8 w-8 text-orange-600 dark:text-orange-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Despachos
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Salida de productos hacia clientes, beneficiarios o destinos externos
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
                El módulo de <strong>Despachos</strong> gestiona la salida de productos del inventario hacia clientes,
                beneficiarios, proyectos u otros destinos externos. Cada despacho reduce el stock de la bodega origen
                y genera la trazabilidad completa del movimiento.
            </p>
        </div>
    </section>

    <!-- Estados de un Despacho -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estados de un Despacho
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
                        <td class="px-4 py-3">Despacho en proceso de edición</td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="yellow" size="sm">Pendiente</flux:badge></td>
                        <td class="px-4 py-3">Esperando aprobación</td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="blue" size="sm">Aprobado</flux:badge></td>
                        <td class="px-4 py-3">Aprobado, listo para procesar</td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Despachado</flux:badge></td>
                        <td class="px-4 py-3">Productos entregados</td>
                        <td class="px-4 py-3 text-red-600 font-medium">-Stock</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">Cancelado</flux:badge></td>
                        <td class="px-4 py-3">Despacho cancelado</td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Modalidades de Despacho -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Modalidades de Despacho
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="md" class="mb-2">Despacho con Workflow</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                    Proceso completo con aprobación y seguimiento de estados.
                </flux:text>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Requiere aprobación antes de procesar</li>
                    <li>Permite seguimiento detallado</li>
                    <li>Ideal para entregas programadas</li>
                    <li>Incluye historial de cambios</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="md" class="mb-2">Despacho Rápido</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                    Proceso simplificado para entregas inmediatas.
                </flux:text>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>No requiere aprobación</li>
                    <li>Procesamiento instantáneo</li>
                    <li>Ideal para entregas urgentes</li>
                    <li>Un solo paso: crear y despachar</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Despacho Rápido -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Despacho Rápido (Quick Dispatch)
        </flux:heading>

        <flux:callout variant="warning" icon="bolt">
            El despacho rápido procesa inmediatamente la salida de productos sin flujo de aprobación.
            Use esta opción solo cuando la entrega sea urgente y esté autorizado.
        </flux:callout>

        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <flux:heading size="sm" class="mb-3">Proceso de Despacho Rápido</flux:heading>
            <ol class="text-sm space-y-2 text-zinc-600 dark:text-zinc-400 list-decimal list-inside">
                <li>Haga clic en el botón "Despacho Rápido" desde el listado</li>
                <li>Seleccione la bodega de origen</li>
                <li>Seleccione el cliente o destino</li>
                <li>Agregue el producto y la cantidad</li>
                <li>Opcionalmente agregue notas</li>
                <li>Confirme el despacho</li>
            </ol>
            <flux:text class="text-xs mt-3 text-orange-600 dark:text-orange-400">
                El stock se actualiza inmediatamente al confirmar.
            </flux:text>
        </div>
    </section>

    <!-- Flujo de Trabajo Completo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Flujo de Trabajo Completo
        </flux:heading>

        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <flux:badge color="zinc">Borrador</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="yellow">Pendiente</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="blue">Aprobado</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="green">Despachado</flux:badge>
            </div>
            <flux:text class="text-xs mt-2 text-zinc-500">
                El inventario solo se reduce cuando el despacho se marca como "Despachado".
            </flux:text>
        </div>
    </section>

    <!-- Movimientos Generados -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Movimientos de Inventario
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            Al procesar un despacho, se generan movimientos de tipo <strong>"sale"</strong> (venta/salida) que reducen el stock de la bodega origen.
        </flux:callout>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg font-mono text-sm">
            <div class="text-zinc-500 mb-2">Ejemplo de movimiento generado:</div>
            <div class="space-y-1 text-zinc-700 dark:text-zinc-300">
                <div>Tipo: <span class="text-red-600">sale (salida)</span></div>
                <div>Producto: Fertilizante NPK 15-15-15</div>
                <div>Cantidad: <span class="text-red-600">-20 sacos</span></div>
                <div>Saldo anterior: 100 sacos</div>
                <div>Nuevo saldo: <span class="font-bold">80 sacos</span></div>
                <div>Destino: Cliente ABC</div>
            </div>
        </div>
    </section>

    <!-- Validaciones -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Validaciones Importantes
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-400">Validaciones de Stock</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>La cantidad no puede exceder el stock disponible</li>
                    <li>Se verifica disponibilidad al crear y al procesar</li>
                    <li>Alerta si el stock queda por debajo del mínimo</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-400">Restricciones</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>No se puede editar un despacho procesado</li>
                    <li>No se puede cancelar un despacho despachado</li>
                    <li>Solo borradores y rechazados son editables</li>
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
            <flux:button variant="outline" icon="truck" :href="route('dispatches.index')" wire:navigate class="justify-start">
                Ir a Despachos
            </flux:button>
            <flux:button variant="outline" icon="plus" :href="route('dispatches.create')" wire:navigate class="justify-start">
                Nuevo Despacho
            </flux:button>
            <flux:button variant="outline" icon="users" :href="route('customers.index')" wire:navigate class="justify-start">
                Ver Clientes
            </flux:button>
        </div>
    </section>
</div>
