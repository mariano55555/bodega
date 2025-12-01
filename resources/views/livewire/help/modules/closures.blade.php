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

    <!-- Proceso de Cierre - Guía Completa -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Proceso de Cierre de Inventario
        </flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            Guía paso a paso del flujo de trabajo completo
        </flux:text>

        <div class="space-y-4">
            {{-- Step 1: Crear --}}
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-white font-bold text-sm shadow-sm">
                        1
                    </div>
                    <div class="w-0.5 flex-1 bg-zinc-200 dark:bg-zinc-700 mt-2"></div>
                </div>
                <div class="flex-1 pb-6">
                    <div class="flex items-center gap-2 mb-1">
                        <flux:badge color="blue" size="sm">Inicio</flux:badge>
                        <span class="font-semibold text-zinc-900 dark:text-white">Crear Cierre</span>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                        Selecciona la bodega y el período mensual. El sistema verificará que no exista un cierre previo para evitar duplicados.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <div class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 text-xs">
                            <flux:icon.building-storefront class="size-3.5 text-zinc-500" />
                            <span>Bodega</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 text-xs">
                            <flux:icon.calendar class="size-3.5 text-zinc-500" />
                            <span>Período</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 text-xs">
                            <flux:icon.document-text class="size-3.5 text-zinc-500" />
                            <span>Notas</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 2: Procesar --}}
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-500 text-white font-bold text-sm shadow-sm">
                        2
                    </div>
                    <div class="w-0.5 flex-1 bg-zinc-200 dark:bg-zinc-700 mt-2"></div>
                </div>
                <div class="flex-1 pb-6">
                    <div class="flex items-center gap-2 mb-1">
                        <flux:badge color="amber" size="sm">Procesamiento</flux:badge>
                        <span class="font-semibold text-zinc-900 dark:text-white">Procesar Saldos</span>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                        El sistema calcula automáticamente los saldos iniciales, entradas, salidas y saldos finales de cada producto en la bodega para el período seleccionado.
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-2 text-center">
                            <flux:icon.arrow-down-tray class="size-4 mx-auto text-green-500 mb-1" />
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Entradas</span>
                        </div>
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-2 text-center">
                            <flux:icon.arrow-up-tray class="size-4 mx-auto text-red-500 mb-1" />
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Salidas</span>
                        </div>
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-2 text-center">
                            <flux:icon.scale class="size-4 mx-auto text-blue-500 mb-1" />
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Ajustes</span>
                        </div>
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-2 text-center">
                            <flux:icon.calculator class="size-4 mx-auto text-purple-500 mb-1" />
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Saldo Final</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 3: Revisar --}}
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-cyan-500 text-white font-bold text-sm shadow-sm">
                        3
                    </div>
                    <div class="w-0.5 flex-1 bg-zinc-200 dark:bg-zinc-700 mt-2"></div>
                </div>
                <div class="flex-1 pb-6">
                    <div class="flex items-center gap-2 mb-1">
                        <flux:badge color="cyan" size="sm">Revisión</flux:badge>
                        <span class="font-semibold text-zinc-900 dark:text-white">Revisar y Validar</span>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                        Revisa los saldos calculados. Puedes registrar conteos físicos para detectar diferencias y realizar ajustes de inventario antes de cerrar el período.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <div class="inline-flex items-center gap-1.5 rounded-md bg-cyan-50 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-300 px-2.5 py-1 text-xs">
                            <flux:icon.clipboard-document-check class="size-3.5" />
                            <span>Conteo Físico</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 px-2.5 py-1 text-xs">
                            <flux:icon.exclamation-triangle class="size-3.5" />
                            <span>Detectar Diferencias</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 rounded-md bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 px-2.5 py-1 text-xs">
                            <flux:icon.adjustments-horizontal class="size-3.5" />
                            <span>Ajustes</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 4: Aprobar --}}
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-lime-500 text-white font-bold text-sm shadow-sm">
                        4
                    </div>
                    <div class="w-0.5 flex-1 bg-zinc-200 dark:bg-zinc-700 mt-2"></div>
                </div>
                <div class="flex-1 pb-6">
                    <div class="flex items-center gap-2 mb-1">
                        <flux:badge color="lime" size="sm">Aprobación</flux:badge>
                        <span class="font-semibold text-zinc-900 dark:text-white">Aprobar Cierre</span>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        Una vez validados los saldos, un supervisor puede aprobar el cierre. Este paso es requerido antes del cierre final y queda registrado para auditoría.
                    </p>
                </div>
            </div>

            {{-- Step 5: Cerrar --}}
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-500 text-white font-bold text-sm shadow-sm">
                        5
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <flux:badge color="green" size="sm">Cerrado</flux:badge>
                        <span class="font-semibold text-zinc-900 dark:text-white">Cerrar Período</span>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                        El cierre queda registrado permanentemente. Los saldos finales se convierten automáticamente en saldos iniciales del siguiente período.
                    </p>
                    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3">
                        <div class="flex items-start gap-2">
                            <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400 mt-0.5 shrink-0" />
                            <div class="text-sm text-green-700 dark:text-green-300">
                                <span class="font-medium">Una vez cerrado:</span> Los movimientos del período quedan bloqueados y el historial se preserva para auditorías.
                            </div>
                        </div>
                    </div>
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
