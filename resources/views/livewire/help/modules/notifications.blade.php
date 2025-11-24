<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                <flux:icon name="bell" class="h-8 w-8 text-red-600 dark:text-red-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Sistema de Notificaciones
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Alertas y notificaciones en tiempo real
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Introduccion -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Descripcion General
        </flux:heading>
        <div class="prose dark:prose-invert max-w-none">
            <p>
                El sistema de notificaciones te mantiene informado sobre eventos importantes que ocurren
                en el sistema, como alertas de stock bajo, productos proximos a vencer, aprobaciones pendientes y mas.
            </p>
        </div>
    </section>

    <!-- Tipos de Notificaciones -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tipos de Notificaciones
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-yellow-500">
                <div class="flex items-center gap-3 mb-2">
                    <flux:icon name="exclamation-triangle" class="h-6 w-6 text-yellow-600" />
                    <flux:heading size="md">Stock Bajo</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Se genera cuando el stock de un producto cae por debajo del minimo configurado.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <div class="flex items-center gap-3 mb-2">
                    <flux:icon name="clock" class="h-6 w-6 text-orange-600" />
                    <flux:heading size="md">Productos por Vencer</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Alerta cuando un producto esta proximo a su fecha de vencimiento.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <div class="flex items-center gap-3 mb-2">
                    <flux:icon name="check-circle" class="h-6 w-6 text-green-600" />
                    <flux:heading size="md">Compras Aprobadas</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Notificacion cuando una compra ha sido aprobada.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <div class="flex items-center gap-3 mb-2">
                    <flux:icon name="arrows-right-left" class="h-6 w-6 text-blue-600" />
                    <flux:heading size="md">Traslados</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Alertas sobre traslados enviados, recibidos o aprobados.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <div class="flex items-center gap-3 mb-2">
                    <flux:icon name="adjustments-horizontal" class="h-6 w-6 text-purple-600" />
                    <flux:heading size="md">Ajustes de Inventario</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Notificacion cuando se crea o aprueba un ajuste de inventario.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-indigo-500">
                <div class="flex items-center gap-3 mb-2">
                    <flux:icon name="calendar" class="h-6 w-6 text-indigo-600" />
                    <flux:heading size="md">Cierres Completados</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Alerta cuando se completa un cierre mensual de inventario.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Acceder a Notificaciones -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Como Acceder a las Notificaciones
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <flux:heading size="sm" class="mb-3">Campana de Notificaciones</flux:heading>
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-white dark:bg-zinc-800 rounded-lg shadow-sm">
                        <flux:icon name="bell" class="h-6 w-6 text-zinc-600" />
                    </div>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Haz clic en el icono de campana en la barra lateral para ver las notificaciones
                        recientes. Un badge rojo indica el numero de notificaciones sin leer.
                    </flux:text>
                </div>
            </div>

            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <flux:heading size="sm" class="mb-3">Pagina de Notificaciones</flux:heading>
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-white dark:bg-zinc-800 rounded-lg shadow-sm">
                        <flux:icon name="bell-alert" class="h-6 w-6 text-zinc-600" />
                    </div>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Visita la pagina completa de notificaciones para ver todas, filtrar por tipo
                        o estado, y gestionar tus notificaciones.
                    </flux:text>
                </div>
            </div>
        </div>

        <flux:callout variant="info" icon="light-bulb">
            <strong>Atajo rapido:</strong> Presiona <kbd class="px-2 py-1 bg-blue-100 dark:bg-blue-800 rounded text-sm">g</kbd> seguido de <kbd class="px-2 py-1 bg-blue-100 dark:bg-blue-800 rounded text-sm">n</kbd> para ir directamente a las notificaciones.
        </flux:callout>
    </section>

    <!-- Gestion de Notificaciones -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Gestion de Notificaciones
        </flux:heading>

        <div class="space-y-4">
            <div class="flex items-start gap-4 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 font-bold text-sm">
                    1
                </div>
                <div>
                    <flux:heading size="sm">Marcar como Leida</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Haz clic en el icono de check junto a una notificacion para marcarla como leida.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 font-bold text-sm">
                    2
                </div>
                <div>
                    <flux:heading size="sm">Marcar Todas como Leidas</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Usa el boton "Marcar todas como leidas" para limpiar todas las notificaciones pendientes.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900 text-red-600 font-bold text-sm">
                    3
                </div>
                <div>
                    <flux:heading size="sm">Eliminar Notificaciones</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Puedes eliminar notificaciones individuales o todas las leidas a la vez.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 font-bold text-sm">
                    4
                </div>
                <div>
                    <flux:heading size="sm">Filtrar Notificaciones</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Filtra por estado (todas, sin leer, leidas) o por tipo de notificacion.
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Notificaciones por Email -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Notificaciones por Email
        </flux:heading>

        <flux:card class="p-4">
            <div class="flex items-start gap-4">
                <flux:icon name="envelope" class="h-8 w-8 text-blue-600 shrink-0" />
                <div>
                    <flux:heading size="sm" class="mb-2">Recibe alertas importantes en tu correo</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Ademas de las notificaciones en el sistema, las alertas criticas como stock bajo
                        y productos por vencer tambien se envian por correo electronico para que no pierdas
                        ninguna informacion importante.
                    </flux:text>
                </div>
            </div>
        </flux:card>
    </section>

    <!-- Enlace Rapido -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acceso Rapido
        </flux:heading>

        <flux:button variant="primary" icon="bell" :href="route('notifications.index')" wire:navigate>
            Ir a Notificaciones
        </flux:button>
    </section>
</div>
