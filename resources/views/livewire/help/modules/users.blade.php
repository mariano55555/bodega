<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                <flux:icon name="users" class="h-8 w-8 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Control de Usuarios
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Gestión completa de usuarios, roles y permisos del sistema
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Users Management -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            👥 Gestión de Usuarios
        </flux:heading>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="user-plus" class="h-5 w-5 text-green-600" />
                    Crear Usuarios
                </flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>Información personal (nombre, email, teléfono)</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>Asignación de empresa y sucursal</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>Configuración de roles y permisos</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>Días de operación personalizados</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="pencil" class="h-5 w-5 text-blue-600" />
                    Editar Usuarios
                </flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500" />
                        <flux:text>Actualizar datos personales</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500" />
                        <flux:text>Modificar roles asignados</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500" />
                        <flux:text>Cambiar contraseña</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500" />
                        <flux:text>Activar/desactivar usuario</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>

        <div class="bg-indigo-50 dark:bg-indigo-950 p-4 rounded-lg border border-indigo-200 dark:border-indigo-800">
            <flux:heading size="sm" class="text-indigo-800 dark:text-indigo-200 mb-2">
                📋 Información del Usuario
            </flux:heading>
            <flux:text class="text-sm text-indigo-700 dark:text-indigo-300">
                Cada usuario muestra estadísticas en tiempo real: total de movimientos realizados,
                última actividad, roles asignados y estado de la cuenta.
            </flux:text>
        </div>
    </section>

    <!-- Roles Management -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🎭 Gestión de Roles
        </flux:heading>

        <div class="space-y-4">
            <flux:text>
                Los roles definen conjuntos de permisos que pueden asignarse a los usuarios para controlar
                su acceso a diferentes funcionalidades del sistema.
            </flux:text>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:card class="p-4 border-l-4 border-l-emerald-500">
                    <flux:heading size="md" class="text-emerald-700 dark:text-emerald-300 mb-2">
                        Crear Roles
                    </flux:heading>
                    <ul class="space-y-2 text-sm">
                        <li>• Nombre técnico único</li>
                        <li>• Nombre para mostrar</li>
                        <li>• Descripción detallada</li>
                        <li>• Selección de permisos por módulo</li>
                    </ul>
                </flux:card>

                <flux:card class="p-4 border-l-4 border-l-blue-500">
                    <flux:heading size="md" class="text-blue-700 dark:text-blue-300 mb-2">
                        Editar Roles
                    </flux:heading>
                    <ul class="space-y-2 text-sm">
                        <li>• Modificar información básica</li>
                        <li>• Agregar/quitar permisos</li>
                        <li>• Ver usuarios asignados</li>
                        <li>• Análisis de impacto</li>
                    </ul>
                </flux:card>

                <flux:card class="p-4 border-l-4 border-l-red-500">
                    <flux:heading size="md" class="text-red-700 dark:text-red-300 mb-2">
                        Eliminar Roles
                    </flux:heading>
                    <ul class="space-y-2 text-sm">
                        <li>• Solo roles sin usuarios</li>
                        <li>• Confirmación requerida</li>
                        <li>• Verificación de dependencias</li>
                        <li>• Acción irreversible</li>
                    </ul>
                </flux:card>
            </div>
        </div>
    </section>

    <!-- Permissions Management -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🔑 Gestión de Permisos
        </flux:heading>

        <div class="space-y-4">
            <flux:text>
                Los permisos son las acciones específicas que un usuario puede realizar en el sistema.
                Se organizan por módulos para facilitar su gestión.
            </flux:text>

            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                <flux:heading size="md" class="mb-3">Módulos de Permisos Disponibles</flux:heading>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 text-sm">
                    <div class="flex items-center gap-2">
                        <flux:icon name="users" class="h-4 w-4 text-purple-500" />
                        <flux:text>Usuarios</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="building-office" class="h-4 w-4 text-indigo-500" />
                        <flux:text>Empresas</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="map-pin" class="h-4 w-4 text-cyan-500" />
                        <flux:text>Sucursales</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="building-storefront" class="h-4 w-4 text-orange-500" />
                        <flux:text>Bodegas</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="cube" class="h-4 w-4 text-emerald-500" />
                        <flux:text>Inventario</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="cog" class="h-4 w-4 text-gray-500" />
                        <flux:text>Roles</flux:text>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <flux:card class="p-4">
                    <flux:heading size="md" class="mb-3">Tipos de Permisos Comunes</flux:heading>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" color="green">view</flux:badge>
                            <flux:text>Ver/listar elementos</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" color="blue">create</flux:badge>
                            <flux:text>Crear nuevos elementos</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" color="yellow">edit</flux:badge>
                            <flux:text>Modificar elementos existentes</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" color="red">delete</flux:badge>
                            <flux:text>Eliminar elementos</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" color="purple">manage</flux:badge>
                            <flux:text>Acciones especiales de gestión</flux:text>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="p-4">
                    <flux:heading size="md" class="mb-3">Funciones Avanzadas</flux:heading>
                    <ul class="space-y-2 text-sm">
                        <li>• Asignación masiva a roles</li>
                        <li>• Filtrado por módulo</li>
                        <li>• Búsqueda de permisos</li>
                        <li>• Selección por grupos</li>
                        <li>• Análisis de uso por rol</li>
                    </ul>
                </flux:card>
            </div>
        </div>
    </section>

    <!-- Activity Logs -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            📊 Bitácora de Actividades
        </flux:heading>

        <div class="space-y-4">
            <flux:text>
                La bitácora registra todas las actividades importantes del sistema para mantener
                un historial completo y facilitar auditorías.
            </flux:text>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:card class="p-4">
                    <flux:heading size="md" class="mb-3 flex items-center gap-2">
                        <flux:icon name="eye" class="h-5 w-5 text-blue-600" />
                        Información Registrada
                    </flux:heading>
                    <ul class="space-y-2 text-sm">
                        <li>• Usuario que realizó la acción</li>
                        <li>• Fecha y hora exacta</li>
                        <li>• Tipo de acción realizada</li>
                        <li>• Dirección IP del usuario</li>
                        <li>• Empresa/contexto de la acción</li>
                        <li>• Detalles específicos del cambio</li>
                    </ul>
                </flux:card>

                <flux:card class="p-4">
                    <flux:heading size="md" class="mb-3 flex items-center gap-2">
                        <flux:icon name="funnel" class="h-5 w-5 text-green-600" />
                        Filtros Disponibles
                    </flux:heading>
                    <ul class="space-y-2 text-sm">
                        <li>• Por usuario específico</li>
                        <li>• Por empresa</li>
                        <li>• Por tipo de acción</li>
                        <li>• Por rango de fechas</li>
                        <li>• Solo actividades sensibles</li>
                        <li>• Búsqueda por texto</li>
                    </ul>
                </flux:card>
            </div>

            <div class="bg-amber-50 dark:bg-amber-950 p-4 rounded-lg border border-amber-200 dark:border-amber-800">
                <flux:heading size="sm" class="text-amber-800 dark:text-amber-200 mb-2 flex items-center gap-2">
                    <flux:icon name="shield-exclamation" class="h-5 w-5" />
                    Actividades Sensibles
                </flux:heading>
                <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                    Algunas actividades se marcan como "sensibles" por su importancia para la seguridad
                    (cambios de contraseña, modificación de permisos, eliminaciones, etc.).
                </flux:text>
            </div>
        </div>
    </section>

    <!-- Best Practices -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            💡 Mejores Prácticas
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-3 p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                <flux:icon name="shield-check" class="h-5 w-5 text-green-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">Principio del Menor Privilegio</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Asigne solo los permisos mínimos necesarios para que cada usuario pueda cumplir su trabajo
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <flux:icon name="users" class="h-5 w-5 text-blue-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">Roles por Función</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Cree roles basados en las funciones laborales reales (Almacenista, Supervisor, Gerente, etc.)
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-purple-950 rounded-lg">
                <flux:icon name="clock" class="h-5 w-5 text-purple-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">Revisión Periódica</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Revise regularmente los permisos y roles asignados, especialmente después de cambios de personal
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-orange-50 dark:bg-orange-950 rounded-lg">
                <flux:icon name="document-text" class="h-5 w-5 text-orange-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">Documentación Clara</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Mantenga descripciones claras en roles y permisos para facilitar la gestión futura
                    </flux:text>
                </div>
            </div>
        </div>
    </section>
</div>
