<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <flux:icon name="home" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Sistema de Gestión de Bodegas
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Plataforma integral para el control y administración de inventarios
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Introduction -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🎯 Descripción General
        </flux:heading>
        <div class="prose dark:prose-invert max-w-none">
            <p>
                El <strong>Sistema de Gestión de Bodegas</strong> es una plataforma web integral diseñada para optimizar
                y automatizar todos los procesos relacionados con el manejo de inventarios, desde el control de stock
                hasta la generación de reportes avanzados.
            </p>
            <p>
                Este sistema permite a las empresas gestionar múltiples ubicaciones, controlar el flujo de productos,
                mantener trazabilidad completa de los movimientos y generar insights valiosos para la toma de decisiones.
            </p>
        </div>
    </section>

    <!-- Key Features -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            ✨ Características Principales
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <div class="flex items-center gap-3 mb-3">
                    <flux:icon name="shield-check" class="h-6 w-6 text-green-600" />
                    <flux:heading size="md">Multi-tenancy Seguro</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Soporte para múltiples empresas con aislamiento completo de datos y configuraciones personalizadas.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <div class="flex items-center gap-3 mb-3">
                    <flux:icon name="users" class="h-6 w-6 text-blue-600" />
                    <flux:heading size="md">Control de Acceso Granular</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Sistema de roles y permisos flexible que permite controlar exactamente qué puede hacer cada usuario.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <div class="flex items-center gap-3 mb-3">
                    <flux:icon name="cube-transparent" class="h-6 w-6 text-purple-600" />
                    <flux:heading size="md">Trazabilidad Completa</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Seguimiento detallado de todos los movimientos de inventario con fechas de vencimiento y lotes.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <div class="flex items-center gap-3 mb-3">
                    <flux:icon name="chart-bar" class="h-6 w-6 text-orange-600" />
                    <flux:heading size="md">Reportes Inteligentes</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Informes automáticos y análisis en tiempo real para optimizar la gestión de inventarios.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- System Architecture -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🏗️ Arquitectura del Sistema
        </flux:heading>

        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 p-6 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                <div>
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg shadow-sm">
                        <flux:icon name="building-office" class="h-8 w-8 text-indigo-600 mx-auto mb-2" />
                        <flux:heading size="sm">Empresas</flux:heading>
                        <flux:text class="text-xs text-zinc-500">Nivel superior de organización</flux:text>
                    </div>
                </div>
                <div>
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg shadow-sm">
                        <flux:icon name="map-pin" class="h-8 w-8 text-cyan-600 mx-auto mb-2" />
                        <flux:heading size="sm">Sucursales</flux:heading>
                        <flux:text class="text-xs text-zinc-500">Ubicaciones geográficas</flux:text>
                    </div>
                </div>
                <div>
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg shadow-sm">
                        <flux:icon name="building-storefront" class="h-8 w-8 text-orange-600 mx-auto mb-2" />
                        <flux:heading size="sm">Bodegas</flux:heading>
                        <flux:text class="text-xs text-zinc-500">Espacios de almacenamiento</flux:text>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Start -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🚀 Inicio Rápido
        </flux:heading>

        <div class="space-y-4">
            <div class="flex items-start gap-4 p-4 bg-green-50 dark:bg-green-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 font-bold text-sm">
                    1
                </div>
                <div>
                    <flux:heading size="sm">Configurar Empresa y Sucursales</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Comience creando su empresa y definiendo las sucursales donde operará.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm">
                    2
                </div>
                <div>
                    <flux:heading size="sm">Crear Bodegas y Usuarios</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Configure las bodegas de almacenamiento e invite usuarios asignando roles apropiados.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 font-bold text-sm">
                    3
                </div>
                <div>
                    <flux:heading size="sm">Configurar Catálogo de Productos</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Agregue productos, categorías y defina unidades de medida para su inventario.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-orange-50 dark:bg-orange-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400 font-bold text-sm">
                    4
                </div>
                <div>
                    <flux:heading size="sm">Comenzar a Gestionar Inventario</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Registre entradas de inventario, configure alertas y comience a operar el sistema.
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Navigation Help -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🧭 Navegación por la Documentación
        </flux:heading>

        <div class="bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <flux:text class="text-sm text-amber-800 dark:text-amber-200">
                <strong>💡 Consejo:</strong> Utilice el menú lateral para navegar entre los diferentes módulos del sistema.
                Cada módulo contiene información detallada sobre sus funcionalidades, casos de uso y guías paso a paso.
            </flux:text>
        </div>
    </section>
</div>
