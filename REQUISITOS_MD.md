# REQUISITOS DEL SISTEMA DE BODEGA

## Procesos Bodega General

### 1. Catálogo de productos

Creación de producto en sistema, donde se detalle: nombre, descripción, precio, numero de especifico, unidad de medida, código de línea, costo unitario, entre otros.

Contar con facilidad cronológica para buscar códigos o conceptos, para minimizar tiempos.

Que el sistema a adquirir permita realizar una migración de inventario para la alimentación inicial del mismo.

### 2. Ingreso de compras al sistema

- Registro de documentos (Factura o CCF)
- Generación automática de número de documento
- Tipos de compra: efectivo o crédito
- Registro de proveedor, fechas, origen de fondos y notas administrativas
- Clasificación de productos por código, descripción y precio
- Ingreso y visualización del precio unitario, total, unidad de medida, etc.

### 3. Traslados entre bodegas

- Registro de bodega de origen (Bodega General)
- Selección de bodega de destino (Fraccionaria)
- Registro de productos, cantidades y precios unitarios
- Control de existencias por cada traslado

### 4. Recepción y registro de donaciones

- Ingreso de productos donados (animales, abonos, medicamentos, etc.) y registro de fecha de donación, quien o que institución hizo la donación donde se utilizará, etc.
- Asociación de documentos de donación
- Traslado al inventario de la bodega fraccionaria correspondiente

### 5. Otros registros

Que permita el ingreso de productos que se han adquirido por convenios, proyectos, entre otros.

Al registrar productos adquiridos por modalidad de convenio, proyecto, etc. Que permita el ingreso de facturas u otras transacciones que por motivo de atraso de los proyectos no pudieron ser ingresadas en el mes actual.

### 6. Despachos desde bodega general

- Ingresos de despachos por las diferentes unidades
- Control de existencias para despachos

### 7. Cierre de inventario mensual

- Cierre de inventario del mes
- Reversión de cierre del mes

### 8. Control de Kardex

Llevar el control detallado de entradas, salidas, saldos, fechas, costo unitario, etc.

### 9. Ajustes de inventario

Registro de ajustes por deterioro, vencimiento, pérdidas o sobrantes detectados en inventarios físicos.

## Procesos Bodegas Fraccionarias

### 10. Recepción de traslados desde bodega general

- Validación de inventario recibido
- Control de documentos de soporte

### 11. Traslados entre bodegas fraccionarias

- Registro de bodega de origen y destino (ej. Zootecnia → Cocina)
- Ingreso de productos por código, cantidad y precio unitario

### 12. Despachos internos

Control de salidas de productos según necesidades operativas

### 13. Cierre mensual de movimientos

Consolidación de documentos (traslados y despachos).

## Otras funcionalidades

### 14. Control de usuarios

- Gestión de roles y permisos
- Alta, baja y modificación de usuarios
- Control de accesos: registro de inicios y cierres de sesión, así como bloqueo de usuarios inactivos
- Bitácora de actividades: registro detallado de todas las acciones realizadas por cada usuario (ingresos, traslados, despachos, ajustes, consultas, reportes generados)

### 15. Consultas

- Consulta de existencias en tiempo real
- Consulta de Kardex
- Consulta de movimientos
- Búsquedas avanzadas: por proveedor, número de factura, despachos, traslado, código de producto o usuario que realizó la transacción, etc

### 16. Reportería

- **Reportes de inventario consolidado:** por bodega, fraccionaria o global
- **Reportes de movimientos mensuales:** ingresos, consumo mensual por la línea de productos, traslados, despachos y ajustes, con desglose por bodega
- **Kardex detallado:** exportación en formatos PDF y Excel, con filtros por producto, categoría o período
- **Reportes administrativos y financieros:** informes solicitados por la UFI y Gerencia Administrativa, incluyendo valor de inventarios, movimientos y consumo
- **Reportes:** resumen de transacciones por la línea de producto, resumen de compras por la línea de producto, compras por proveedor, autoconsumo, donaciones
- **Reportes que reflejen las diferencias** para poder hacer consultas antes y en el momento de cierre de mes
- **Reportes personalizados:** generación de reportes bajo parámetros definidos por el usuario

### 17. Histórico

Cada transacción quedará registrada en bitácora, indicando usuario, fecha, hora y acción realizada, garantizando control y transparencia.

Línea de tiempo por producto: desde su ingreso hasta su consumo o traslado final

### 18. Otras funcionalidades

- **Exportación** a formatos PDF y XLSX
- **Importación de datos:** Permitir la carga masiva de productos, inventarios iniciales o ajustes mediante archivos Excel o CSV
- **Alertas y notificaciones:** por ejemplo, que el sistema notifique por medio de avisos cuando se desee dar salida más de las existencias, fechas de mes cerrado y otros
- **Dashboard gráfico:**
    - Panel de control con indicadores clave
    - Gráficas dinámicas
- **Gestión documental:**
    - Opción de adjuntar facturas, CCF, donaciones, actas de ajustes en formato digital (PDF, imagen)
