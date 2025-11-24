<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                <flux:icon name="lock-closed" class="h-8 w-8 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Sistema de Autenticación
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Acceso seguro y gestión de sesiones de usuario
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Login Process -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🔐 Proceso de Inicio de Sesión
        </flux:heading>

        <div class="space-y-4">
            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3">Acceso al Sistema</flux:heading>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <flux:icon name="envelope" class="h-5 w-5 text-blue-500" />
                        <flux:text><strong>Email:</strong> Ingrese su dirección de correo electrónico registrada</flux:text>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:icon name="key" class="h-5 w-5 text-blue-500" />
                        <flux:text><strong>Contraseña:</strong> Ingrese su contraseña personal</flux:text>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:icon name="check-circle" class="h-5 w-5 text-green-500" />
                        <flux:text><strong>Recordarme:</strong> Opción para mantener la sesión activa</flux:text>
                    </div>
                </div>
            </flux:card>

            <div class="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                <flux:heading size="sm" class="text-blue-800 dark:text-blue-200 mb-2">
                    📱 Acceso desde Dispositivos Móviles
                </flux:heading>
                <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                    El sistema está optimizado para dispositivos móviles. Puede acceder desde cualquier navegador
                    en su teléfono o tablet con la misma funcionalidad completa.
                </flux:text>
            </div>
        </div>
    </section>

    <!-- Password Recovery -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🔄 Recuperación de Contraseña
        </flux:heading>

        <div class="space-y-4">
            <flux:text>
                Si olvida su contraseña, puede recuperarla siguiendo estos pasos:
            </flux:text>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:card class="p-4 text-center">
                    <div class="bg-orange-100 dark:bg-orange-900 p-3 rounded-full w-fit mx-auto mb-3">
                        <flux:icon name="envelope" class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                    </div>
                    <flux:heading size="sm" class="mb-2">1. Solicitar Reset</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Haga clic en "¿Olvidaste tu contraseña?" e ingrese su email
                    </flux:text>
                </flux:card>

                <flux:card class="p-4 text-center">
                    <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full w-fit mx-auto mb-3">
                        <flux:icon name="paper-airplane" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <flux:heading size="sm" class="mb-2">2. Revisar Email</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Recibirá un enlace de recuperación en su correo electrónico
                    </flux:text>
                </flux:card>

                <flux:card class="p-4 text-center">
                    <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full w-fit mx-auto mb-3">
                        <flux:icon name="key" class="h-6 w-6 text-green-600 dark:text-green-400" />
                    </div>
                    <flux:heading size="sm" class="mb-2">3. Nueva Contraseña</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Siga el enlace y establezca una nueva contraseña segura
                    </flux:text>
                </flux:card>
            </div>
        </div>
    </section>

    <!-- Security Features -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🛡️ Características de Seguridad
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:icon name="shield-check" class="h-6 w-6 text-green-600" />
                    <flux:heading size="md">Encriptación de Datos</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Todas las contraseñas están encriptadas usando algoritmos seguros.
                    La comunicación se realiza a través de HTTPS.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:icon name="clock" class="h-6 w-6 text-blue-600" />
                    <flux:heading size="md">Sesiones Seguras</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Las sesiones tienen tiempo de expiración automático por inactividad.
                    Puede cerrar sesión manualmente en cualquier momento.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:icon name="eye-slash" class="h-6 w-6 text-purple-600" />
                    <flux:heading size="md">Privacidad de Datos</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Sistema multi-tenant que garantiza el aislamiento completo de datos
                    entre diferentes empresas.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:icon name="computer-desktop" class="h-6 w-6 text-orange-600" />
                    <flux:heading size="md">Múltiples Dispositivos</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Puede iniciar sesión desde múltiples dispositivos simultáneamente
                    manteniendo sincronización en tiempo real.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Best Practices -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            💡 Mejores Prácticas de Seguridad
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-3 p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                <flux:icon name="check-circle" class="h-5 w-5 text-green-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">Use contraseñas fuertes</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Combine letras mayúsculas, minúsculas, números y símbolos especiales
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <flux:icon name="check-circle" class="h-5 w-5 text-blue-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">Cierre sesión al terminar</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Especialmente importante en dispositivos compartidos o públicos
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-purple-950 rounded-lg">
                <flux:icon name="check-circle" class="h-5 w-5 text-purple-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">Mantenga su información actualizada</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Verifique que su email de contacto esté actualizado para recuperación de contraseña
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-amber-50 dark:bg-amber-950 rounded-lg">
                <flux:icon name="exclamation-triangle" class="h-5 w-5 text-amber-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">No comparta credenciales</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Cada usuario debe tener su propia cuenta para mantener la trazabilidad
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Troubleshooting -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🛠️ Solución de Problemas Comunes
        </flux:heading>

        <div class="space-y-4">
            <flux:card class="p-4 border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950">
                <flux:heading size="md" class="text-red-800 dark:text-red-200 mb-2">
                    ❌ No puedo iniciar sesión
                </flux:heading>
                <ul class="space-y-2 text-sm text-red-700 dark:text-red-300">
                    <li>• Verifique que su email esté escrito correctamente</li>
                    <li>• Asegúrese de que las mayúsculas/minúsculas de la contraseña sean correctas</li>
                    <li>• Intente usar la recuperación de contraseña si la olvidó</li>
                    <li>• Contacte al administrador si su cuenta está desactivada</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950">
                <flux:heading size="md" class="text-amber-800 dark:text-amber-200 mb-2">
                    ⚠️ Mi sesión se cierra automáticamente
                </flux:heading>
                <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                    Esto es normal por seguridad. El sistema cierra sesiones inactivas después de un tiempo determinado.
                    Manténgase activo en el sistema o vuelva a iniciar sesión cuando sea necesario.
                </flux:text>
            </flux:card>
        </div>
    </section>
</div>
