<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-violet-100 dark:bg-violet-900 rounded-lg">
                <flux:icon name="document-arrow-up" class="h-8 w-8 text-violet-600 dark:text-violet-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Importar DTE
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Importación de facturas electrónicas (DTE) desde archivos JSON del Ministerio de Hacienda
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
                El módulo de <strong>Importar DTE</strong> permite cargar archivos JSON de Documentos Tributarios Electrónicos (DTE)
                emitidos por el Ministerio de Hacienda de El Salvador. Esta funcionalidad automatiza el proceso de registro de compras,
                validando proveedores, mapeando productos y generando automáticamente las entradas de inventario.
            </p>
            <p>
                El sistema extrae la información del JSON, identifica o crea el proveedor, permite mapear los productos del documento
                con los productos del catálogo interno, y finalmente crea una compra que puede recibirse automáticamente en la bodega destino.
            </p>
        </div>
    </section>

    <!-- ¿Qué es un DTE? -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            ¿Qué es un DTE?
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            <strong>DTE</strong> significa Documento Tributario Electrónico. Es el formato estándar de El Salvador para facturas electrónicas,
            emitido a través del sistema del Ministerio de Hacienda.
        </flux:callout>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-violet-500">
                <flux:heading size="sm" class="mb-2">Tipos de DTE Soportados</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li><strong>01</strong> - Factura Electrónica</li>
                    <li><strong>03</strong> - Comprobante de Crédito Fiscal (CCF)</li>
                    <li><strong>11</strong> - Factura de Exportación</li>
                    <li><strong>14</strong> - Factura de Sujeto Excluido</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2">Información Extraída</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Código de generación (identificador único)</li>
                    <li>Datos del emisor (proveedor)</li>
                    <li>Detalle de productos/servicios</li>
                    <li>Totales, impuestos y descuentos</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Estados de Importación -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estados de una Importación
        </flux:heading>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Estado</th>
                        <th class="px-4 py-3 text-left font-medium">Descripción</th>
                        <th class="px-4 py-3 text-left font-medium">Acción Requerida</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="yellow" size="sm">En Revisión</flux:badge></td>
                        <td class="px-4 py-3">DTE cargado, pendiente de mapear productos</td>
                        <td class="px-4 py-3">Revisar y mapear productos</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Procesado</flux:badge></td>
                        <td class="px-4 py-3">Compra creada exitosamente</td>
                        <td class="px-4 py-3">Ninguna - completado</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">Error</flux:badge></td>
                        <td class="px-4 py-3">Error durante el procesamiento</td>
                        <td class="px-4 py-3">Revisar y reintentar</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Flujo de Trabajo Completo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Flujo de Trabajo Completo
        </flux:heading>

        <div class="p-4 bg-violet-50 dark:bg-violet-900/20 rounded-lg">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <flux:badge color="zinc">1. Subir JSON</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="yellow">2. Revisar DTE</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="blue">3. Mapear Productos</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="purple">4. Seleccionar Bodega</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="green">5. Crear Compra</flux:badge>
            </div>
        </div>

        <div class="space-y-3 mt-4">
            <div class="flex items-start gap-4 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-violet-100 dark:bg-violet-900 text-violet-600 dark:text-violet-400 font-bold text-sm">
                    1
                </div>
                <div>
                    <flux:heading size="sm">Subir Archivo JSON</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Vaya a <strong>Compras → Importar DTE</strong> y suba el archivo JSON del DTE.
                        El sistema validará automáticamente el formato y extraerá la información.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-yellow-50 dark:bg-yellow-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400 font-bold text-sm">
                    2
                </div>
                <div>
                    <flux:heading size="sm">Revisar Datos del DTE</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Verifique la información extraída: proveedor, número de documento, fecha, y lista de productos.
                        Si el proveedor no existe, el sistema puede crearlo automáticamente usando el NIT/NRC del emisor.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm">
                    3
                </div>
                <div>
                    <flux:heading size="sm">Mapear Productos</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Para cada producto del DTE, debe indicar a qué producto del catálogo interno corresponde.
                        Tiene tres opciones:
                    </flux:text>
                    <ul class="text-sm text-zinc-600 dark:text-zinc-400 mt-2 space-y-1 list-disc list-inside">
                        <li><strong>Auto-detectado:</strong> El sistema encontró una coincidencia automática por código del proveedor</li>
                        <li><strong>Vincular manualmente:</strong> Seleccione el producto interno correspondiente</li>
                        <li><strong>Crear nuevo:</strong> Cree un nuevo producto en el catálogo con los datos del DTE</li>
                    </ul>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 font-bold text-sm">
                    4
                </div>
                <div>
                    <flux:heading size="sm">Seleccionar Bodega Destino</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Elija la bodega donde se recibirán los productos. Opcionalmente puede activar
                        <strong>"Recibir automáticamente"</strong> para que el inventario se actualice inmediatamente al crear la compra.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-green-50 dark:bg-green-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 font-bold text-sm">
                    5
                </div>
                <div>
                    <flux:heading size="sm">Crear la Compra</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Al confirmar, el sistema creará una compra con todos los detalles del DTE.
                        Si activó "Recibir automáticamente", el stock se actualizará inmediatamente.
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Mapeo de Productos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Sistema de Mapeo de Productos
        </flux:heading>

        <div class="prose dark:prose-invert max-w-none">
            <p>
                El mapeo de productos es clave para vincular los códigos del proveedor con los productos de su catálogo interno.
                Una vez que vincule un producto, el sistema recordará esta asociación para futuras importaciones del mismo proveedor.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="check-circle" class="h-5 w-5 text-green-500" />
                    <flux:heading size="sm">Auto-detectado</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    El sistema encontró automáticamente el producto interno basándose en el código del proveedor
                    registrado previamente en la relación producto-proveedor.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="link" class="h-5 w-5 text-blue-500" />
                    <flux:heading size="sm">Vincular Manualmente</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Seleccione el producto interno correspondiente de una lista desplegable.
                    Esta vinculación se guardará para futuras importaciones.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-amber-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="plus-circle" class="h-5 w-5 text-amber-500" />
                    <flux:heading size="sm">Crear Nuevo</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Si el producto no existe en su catálogo, puede crearlo automáticamente
                    usando la descripción y código del DTE.
                </flux:text>
            </flux:card>
        </div>

        <flux:callout variant="warning" icon="exclamation-triangle">
            <strong>Importante:</strong> Todos los productos del DTE deben estar mapeados antes de poder crear la compra.
            Los productos sin mapear se mostrarán con un indicador de alerta.
        </flux:callout>
    </section>

    <!-- Estructura del JSON -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estructura del Archivo JSON
        </flux:heading>

        <div class="prose dark:prose-invert max-w-none">
            <p>
                El sistema espera un archivo JSON con la estructura estándar del Ministerio de Hacienda.
                Los campos principales que se extraen son:
            </p>
        </div>

        <div class="p-4 bg-zinc-900 dark:bg-zinc-950 rounded-lg font-mono text-sm overflow-x-auto">
            <pre class="text-zinc-300"><code>{
  "identificacion": {
    "codigoGeneracion": "9E876CBC-...",  // ID único del DTE
    "tipoDte": "03",                      // Tipo de documento
    "fecEmi": "2024-01-15",              // Fecha de emisión
    "numeroControl": "DTE-03-..."        // Número de control
  },
  "emisor": {
    "nit": "06141234567890",             // NIT del proveedor
    "nrc": "123456-7",                   // NRC del proveedor
    "nombre": "Proveedor S.A. de C.V."  // Nombre comercial
  },
  "cuerpoDocumento": [                   // Detalle de productos
    {
      "numItem": 1,
      "codigo": "PROD-001",              // Código del proveedor
      "descripcion": "Producto ABC",
      "cantidad": 10,
      "precioUni": 25.50,
      "ventaGravada": 255.00
    }
  ],
  "resumen": {
    "totalGravada": 255.00,
    "totalIva": 33.15,
    "totalPagar": 288.15
  }
}</code></pre>
        </div>
    </section>

    <!-- Relación con Compras -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Relación con el Módulo de Compras
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            Al procesar un DTE, se crea una <strong>Compra</strong> regular en el sistema. Esta compra puede gestionarse
            normalmente desde el módulo de Compras (editar, aprobar, recibir, cancelar, etc.).
        </flux:callout>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Campo DTE</th>
                        <th class="px-4 py-3 text-left font-medium">Campo Compra</th>
                        <th class="px-4 py-3 text-left font-medium">Notas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">emisor.nit</td>
                        <td class="px-4 py-3">Proveedor</td>
                        <td class="px-4 py-3">Se busca o crea el proveedor</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">identificacion.numeroControl</td>
                        <td class="px-4 py-3">Número de Documento</td>
                        <td class="px-4 py-3">Referencia del DTE</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">identificacion.fecEmi</td>
                        <td class="px-4 py-3">Fecha de Compra</td>
                        <td class="px-4 py-3">Fecha de emisión del DTE</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">cuerpoDocumento[]</td>
                        <td class="px-4 py-3">Detalle de Compra</td>
                        <td class="px-4 py-3">Productos mapeados</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">resumen.totalPagar</td>
                        <td class="px-4 py-3">Total</td>
                        <td class="px-4 py-3">Monto total de la compra</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Opción de Auto-Recibir -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Opción: Recibir Automáticamente
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="check" class="h-5 w-5 text-green-500" />
                    <flux:heading size="sm">Con Auto-Recibir Activado</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    La compra se crea directamente en estado <strong>"Recibida"</strong> y el inventario
                    se actualiza inmediatamente. Ideal para documentar compras ya recibidas físicamente.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="clock" class="h-5 w-5 text-blue-500" />
                    <flux:heading size="sm">Sin Auto-Recibir</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    La compra se crea en estado <strong>"Pendiente"</strong>. Deberá marcarla como recibida
                    manualmente desde el módulo de Compras cuando los productos lleguen físicamente.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Validaciones -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Validaciones del Sistema
        </flux:heading>

        <div class="space-y-2">
            <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                <flux:icon name="check-circle" class="h-5 w-5 text-green-500" />
                <span class="text-sm text-zinc-700 dark:text-zinc-300">Formato JSON válido del Ministerio de Hacienda</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                <flux:icon name="check-circle" class="h-5 w-5 text-green-500" />
                <span class="text-sm text-zinc-700 dark:text-zinc-300">Código de generación único (no duplicado)</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                <flux:icon name="check-circle" class="h-5 w-5 text-green-500" />
                <span class="text-sm text-zinc-700 dark:text-zinc-300">Todos los productos mapeados correctamente</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                <flux:icon name="check-circle" class="h-5 w-5 text-green-500" />
                <span class="text-sm text-zinc-700 dark:text-zinc-300">Bodega destino seleccionada</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                <flux:icon name="check-circle" class="h-5 w-5 text-green-500" />
                <span class="text-sm text-zinc-700 dark:text-zinc-300">Proveedor identificado o creado</span>
            </div>
        </div>
    </section>

    <!-- Errores Comunes -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Errores Comunes y Soluciones
        </flux:heading>

        <div class="space-y-3">
            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <flux:icon name="exclamation-circle" class="h-5 w-5 text-red-500 mt-0.5" />
                    <div>
                        <flux:heading size="sm" class="text-red-600 dark:text-red-400">Formato JSON inválido</flux:heading>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            <strong>Solución:</strong> Verifique que el archivo sea un JSON válido descargado del sistema del Ministerio de Hacienda.
                            No modifique el archivo manualmente.
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <flux:icon name="exclamation-circle" class="h-5 w-5 text-red-500 mt-0.5" />
                    <div>
                        <flux:heading size="sm" class="text-red-600 dark:text-red-400">DTE ya importado anteriormente</flux:heading>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            <strong>Solución:</strong> Cada DTE solo puede importarse una vez. Verifique en el historial de importaciones
                            si ya fue procesado. Si necesita reimportarlo, primero elimine la importación anterior.
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <flux:icon name="exclamation-circle" class="h-5 w-5 text-red-500 mt-0.5" />
                    <div>
                        <flux:heading size="sm" class="text-red-600 dark:text-red-400">Productos sin mapear</flux:heading>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            <strong>Solución:</strong> Debe mapear todos los productos del DTE antes de crear la compra.
                            Vincule cada producto a uno existente o cree nuevos productos según sea necesario.
                        </flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Consejos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Consejos y Mejores Prácticas
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <flux:icon name="light-bulb" class="h-5 w-5 text-blue-500 mt-0.5" />
                <div>
                    <flux:heading size="sm">Configure códigos de proveedor</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        En el catálogo de productos, registre los códigos que usa cada proveedor. Esto permitirá que futuras importaciones detecten automáticamente los productos.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <flux:icon name="light-bulb" class="h-5 w-5 text-blue-500 mt-0.5" />
                <div>
                    <flux:heading size="sm">Revise antes de confirmar</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Siempre verifique que los productos mapeados sean correctos y que la bodega destino sea la adecuada antes de crear la compra.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <flux:icon name="light-bulb" class="h-5 w-5 text-blue-500 mt-0.5" />
                <div>
                    <flux:heading size="sm">Use auto-recibir con cuidado</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Active esta opción solo si los productos ya están físicamente en la bodega. De lo contrario, el inventario mostrará existencias que aún no han llegado.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <flux:icon name="light-bulb" class="h-5 w-5 text-blue-500 mt-0.5" />
                <div>
                    <flux:heading size="sm">Mantenga el historial</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        El sistema guarda un historial de todas las importaciones. Use los filtros para buscar importaciones anteriores por fecha, proveedor o estado.
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Enlaces Rápidos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Enlaces Rápidos
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:button variant="outline" icon="document-arrow-up" :href="route('dte-imports.index')" wire:navigate class="justify-start">
                Ir a Importar DTE
            </flux:button>
            <flux:button variant="outline" icon="shopping-cart" :href="route('purchases.index')" wire:navigate class="justify-start">
                Ver Compras
            </flux:button>
            <flux:button variant="outline" icon="building-storefront" :href="route('purchases.suppliers.index')" wire:navigate class="justify-start">
                Ver Proveedores
            </flux:button>
        </div>
    </section>
</div>
