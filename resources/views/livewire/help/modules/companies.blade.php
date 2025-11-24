<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                <flux:icon name="building-office" class="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Gestión de Empresas
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Administración de empresas y configuración corporativa
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Company Overview -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🏢 Concepto de Empresa
        </flux:heading>

        <div class="space-y-4">
            <flux:text>
                En el sistema, las <strong>empresas</strong> representan el nivel más alto de organización.
                Cada empresa puede tener múltiples sucursales y cada sucursal puede tener múltiples bodegas.
            </flux:text>

            <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-950 dark:to-blue-950 p-6 rounded-lg">
                <flux:heading size="md" class="mb-4 text-center">Jerarquía Organizacional</flux:heading>
                <div class="flex items-center justify-center space-x-8">
                    <div class="text-center">
                        <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg shadow-sm border-2 border-indigo-200 dark:border-indigo-800">
                            <flux:icon name="building-office" class="h-12 w-12 text-indigo-600 mx-auto mb-2" />
                            <flux:text class="font-bold text-indigo-800 dark:text-indigo-200">EMPRESA</flux:text>
                            <flux:text class="text-xs text-zinc-500">Nivel superior</flux:text>
                        </div>
                    </div>
                    <flux:icon name="arrow-right" class="h-6 w-6 text-zinc-400" />
                    <div class="text-center">
                        <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg shadow-sm border-2 border-cyan-200 dark:border-cyan-800">
                            <flux:icon name="map-pin" class="h-12 w-12 text-cyan-600 mx-auto mb-2" />
                            <flux:text class="font-bold text-cyan-800 dark:text-cyan-200">SUCURSALES</flux:text>
                            <flux:text class="text-xs text-zinc-500">Ubicaciones</flux:text>
                        </div>
                    </div>
                    <flux:icon name="arrow-right" class="h-6 w-6 text-zinc-400" />
                    <div class="text-center">
                        <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg shadow-sm border-2 border-orange-200 dark:border-orange-800">
                            <flux:icon name="building-storefront" class="h-12 w-12 text-orange-600 mx-auto mb-2" />
                            <flux:text class="font-bold text-orange-800 dark:text-orange-200">BODEGAS</flux:text>
                            <flux:text class="text-xs text-zinc-500">Almacenes</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Company Management -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🛠️ Gestión de Empresas
        </flux:heading>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="plus-circle" class="h-5 w-5 text-green-600" />
                    Crear Empresa
                </flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start gap-2">
                        <flux:icon name="identification" class="h-4 w-4 text-blue-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Información Legal</flux:text>
                            <flux:text class="text-xs text-zinc-500">Nombre, RUC/NIT, tipo de empresa</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="map-pin" class="h-4 w-4 text-green-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Ubicación</flux:text>
                            <flux:text class="text-xs text-zinc-500">Dirección completa, ciudad, país</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="phone" class="h-4 w-4 text-purple-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Contacto</flux:text>
                            <flux:text class="text-xs text-zinc-500">Teléfono, email, sitio web</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="cog" class="h-5 w-5 text-blue-600" />
                    Configuración
                </flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start gap-2">
                        <flux:icon name="calendar" class="h-4 w-4 text-orange-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Días de Operación</flux:text>
                            <flux:text class="text-xs text-zinc-500">Lunes a domingo configurables</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="clock" class="h-4 w-4 text-red-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Horarios</flux:text>
                            <flux:text class="text-xs text-zinc-500">Hora de inicio y cierre por día</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="power" class="h-4 w-4 text-gray-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Estado</flux:text>
                            <flux:text class="text-xs text-zinc-500">Activa o inactiva</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        <div class="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
            <flux:heading size="sm" class="text-blue-800 dark:text-blue-200 mb-2 flex items-center gap-2">
                <flux:icon name="information-circle" class="h-5 w-5" />
                Información Importante
            </flux:heading>
            <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                Una vez creada una empresa, no puede ser eliminada si tiene sucursales, bodegas o usuarios asignados.
                Solo puede ser desactivada para mantener la integridad de los datos históricos.
            </flux:text>
        </div>
    </section>

    <!-- Multi-tenancy -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🏠 Sistema Multi-tenant
        </flux:heading>

        <div class="space-y-4">
            <flux:text>
                El sistema está diseñado como <strong>multi-tenant</strong>, lo que significa que múltiples empresas
                pueden usar la misma plataforma manteniendo sus datos completamente separados y seguros.
            </flux:text>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:card class="p-4 border-l-4 border-l-green-500">
                    <flux:heading size="md" class="text-green-700 dark:text-green-300 mb-3">
                        ✅ Beneficios
                    </flux:heading>
                    <ul class="space-y-2 text-sm">
                        <li>• Aislamiento completo de datos</li>
                        <li>• Configuraciones independientes</li>
                        <li>• Usuarios separados por empresa</li>
                        <li>• Reportes exclusivos por empresa</li>
                        <li>• Escalabilidad automática</li>
                    </ul>
                </flux:card>

                <flux:card class="p-4 border-l-4 border-l-blue-500">
                    <flux:heading size="md" class="text-blue-700 dark:text-blue-300 mb-3">
                        🔒 Seguridad
                    </flux:heading>
                    <ul class="space-y-2 text-sm">
                        <li>• Datos privados por empresa</li>
                        <li>• Acceso controlado por contexto</li>
                        <li>• Auditoría independiente</li>
                        <li>• Backup segregado</li>
                        <li>• Compliance empresarial</li>
                    </ul>
                </flux:card>
            </div>
        </div>
    </section>

    <!-- Operating Days Configuration -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            📅 Configuración de Días de Operación
        </flux:heading>

        <div class="space-y-4">
            <flux:text>
                Cada empresa puede configurar sus días y horarios de operación, lo que afecta
                la disponibilidad del sistema y la programación de procesos automatizados.
            </flux:text>

            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                <flux:heading size="md" class="mb-3">Configuración por Día</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:heading size="sm" class="mb-2">Días Laborales</flux:heading>
                        <div class="space-y-1 text-sm">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" checked disabled class="rounded">
                                <flux:text>Lunes - Viernes</flux:text>
                            </div>
                            <flux:text class="text-xs text-zinc-500 ml-6">
                                Horario: 08:00 - 18:00
                            </flux:text>
                        </div>
                    </div>
                    <div>
                        <flux:heading size="sm" class="mb-2">Fin de Semana</flux:heading>
                        <div class="space-y-1 text-sm">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" disabled class="rounded">
                                <flux:text>Sábado - Domingo</flux:text>
                            </div>
                            <flux:text class="text-xs text-zinc-500 ml-6">
                                Opcional según necesidades
                            </flux:text>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-amber-50 dark:bg-amber-950 p-4 rounded-lg border border-amber-200 dark:border-amber-800">
                <flux:heading size="sm" class="text-amber-800 dark:text-amber-200 mb-2">
                    ⏰ Impacto en el Sistema
                </flux:heading>
                <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                    Los días y horarios configurados afectan: envío de reportes automáticos,
                    procesamiento de alertas, disponibilidad de funciones y programación de tareas.
                </flux:text>
            </div>
        </div>
    </section>

    <!-- Company Statistics -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            📊 Información y Estadísticas
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card class="p-4 text-center">
                <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full w-fit mx-auto mb-3">
                    <flux:icon name="map-pin" class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
                <flux:heading size="lg" class="text-green-600 dark:text-green-400">5</flux:heading>
                <flux:text class="text-sm text-zinc-500">Sucursales Activas</flux:text>
            </flux:card>

            <flux:card class="p-4 text-center">
                <div class="bg-orange-100 dark:bg-orange-900 p-3 rounded-full w-fit mx-auto mb-3">
                    <flux:icon name="building-storefront" class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                </div>
                <flux:heading size="lg" class="text-orange-600 dark:text-orange-400">12</flux:heading>
                <flux:text class="text-sm text-zinc-500">Bodegas Totales</flux:text>
            </flux:card>

            <flux:card class="p-4 text-center">
                <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-full w-fit mx-auto mb-3">
                    <flux:icon name="users" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                </div>
                <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">25</flux:heading>
                <flux:text class="text-sm text-zinc-500">Usuarios Activos</flux:text>
            </flux:card>

            <flux:card class="p-4 text-center">
                <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full w-fit mx-auto mb-3">
                    <flux:icon name="cube" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">1,250</flux:heading>
                <flux:text class="text-sm text-zinc-500">Productos Únicos</flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Coming Soon -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🚧 Funcionalidades en Desarrollo
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-950 dark:to-pink-950 border border-purple-200 dark:border-purple-800">
                <flux:heading size="md" class="text-purple-800 dark:text-purple-200 mb-2">
                    📄 Configuración Fiscal
                </flux:heading>
                <flux:text class="text-sm text-purple-700 dark:text-purple-300">
                    Configuración de impuestos, monedas y aspectos fiscales específicos por país/región.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-950 dark:to-cyan-950 border border-blue-200 dark:border-blue-800">
                <flux:heading size="md" class="text-blue-800 dark:text-blue-200 mb-2">
                    🔗 Integraciones API
                </flux:heading>
                <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                    Conectores con sistemas ERP, contables y de facturación externos.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950 border border-green-200 dark:border-green-800">
                <flux:heading size="md" class="text-green-800 dark:text-green-200 mb-2">
                    📊 Dashboard Ejecutivo
                </flux:heading>
                <flux:text class="text-sm text-green-700 dark:text-green-300">
                    Panel de control con KPIs y métricas en tiempo real para la dirección ejecutiva.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-950 dark:to-red-950 border border-orange-200 dark:border-orange-800">
                <flux:heading size="md" class="text-orange-800 dark:text-orange-200 mb-2">
                    🔔 Centro de Notificaciones
                </flux:heading>
                <flux:text class="text-sm text-orange-700 dark:text-orange-300">
                    Sistema avanzado de alertas y notificaciones por email, SMS y push.
                </flux:text>
            </flux:card>
        </div>
    </section>
</div>
