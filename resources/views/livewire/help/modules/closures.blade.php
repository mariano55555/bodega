<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                <flux:icon name="lock-closed" class="h-8 w-8 text-zinc-600 dark:text-zinc-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Cierres Mensuales
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Cierre contable mensual del inventario
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
                El módulo de <strong>Cierres Mensuales</strong> permite generar un corte contable del inventario
                al final de cada período. El cierre captura el estado del inventario en un momento específico,
                sirviendo como punto de referencia para auditorías y reportes financieros.
            </p>
        </div>
    </section>

    <!-- Propósito -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Propósito del Cierre
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2">Control Contable</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Captura el valor del inventario</li>
                    <li>Facilita la conciliación contable</li>
                    <li>Genera datos para estados financieros</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2">Auditoría</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Punto de referencia histórico</li>
                    <li>Comparación entre períodos</li>
                    <li>Trazabilidad de cambios</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Proceso -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Proceso de Cierre
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm">
                    1
                </div>
                <div>
                    <flux:heading size="sm">Verificar Operaciones Pendientes</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Asegúrese de que no haya compras, despachos o traslados pendientes de procesar.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-green-50 dark:bg-green-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 font-bold text-sm">
                    2
                </div>
                <div>
                    <flux:heading size="sm">Generar Cierre</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Seleccione el mes y año, luego ejecute el cierre. El sistema captura el estado actual.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 font-bold text-sm">
                    3
                </div>
                <div>
                    <flux:heading size="sm">Revisar y Aprobar</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Revise el resumen del cierre y apruebe para finalizar el período.
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Información Capturada -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Información Capturada
        </flux:heading>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
            <ul class="text-sm space-y-2 text-zinc-600 dark:text-zinc-400">
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Stock por producto y bodega
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Valor del inventario (costo unitario x cantidad)
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Resumen de movimientos del período
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Comparación con cierre anterior
                </li>
            </ul>
        </div>
    </section>

    <!-- Advertencias -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Consideraciones Importantes
        </flux:heading>

        <flux:callout variant="warning" icon="exclamation-triangle">
            <strong>Advertencia:</strong> Una vez ejecutado y aprobado el cierre, no se pueden registrar movimientos
            con fecha anterior al período cerrado. Asegúrese de completar todas las operaciones pendientes antes de cerrar.
        </flux:callout>
    </section>

    <!-- Enlaces Rápidos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Enlaces Rápidos
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:button variant="outline" icon="lock-closed" :href="route('closures.index')" wire:navigate class="justify-start">
                Ir a Cierres Mensuales
            </flux:button>
            <flux:button variant="outline" icon="chart-bar" :href="route('reports.inventory.consolidated')" wire:navigate class="justify-start">
                Ver Inventario Consolidado
            </flux:button>
        </div>
    </section>
</div>
