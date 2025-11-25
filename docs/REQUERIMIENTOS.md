
# Sistema de Gestión de Bodega

## Descripción General de Módulos

### 🏭 Procesos Bodega General

- **Ingreso de compras al sistema**
- **Traslados entre bodegas**
- **Recepción y registro de donaciones**
- **Despachos desde bodega general**
- **Cierre de inventario mensual**
- **Control de Kardex**
- **Ajustes de inventario**

### 📦 Procesos Bodegas Fraccionarias

- **Recepción de traslados desde bodega general**
- **Traslados entre bodegas fraccionarias**
- **Despachos internos**
- **Cierre mensual de movimientos**

### ⚙️ Módulos Adicionales

#### 👥 Control de Usuarios
- Roles
- Permisos
- Bitácora

#### 🔍 Consultas
- Existencias
- Kardex
- Movimientos
- Búsquedas

#### 📊 Reportería
- Inventario consolidado
- Kardex
- Movimientos
- Administrativos
- Personalizados

#### 🛠️ Otras Funcionalidades
- Exportación/importación
- Alertas
- Dashboard
- Gestión documental
- Trazabilidad histórica


---

## Descripción Detallada de Módulos

### 🏭 Procesos Bodega General

#### 📋 Catálogo de Productos

**Funcionalidades requeridas:**

- **Creación de producto en sistema** con los siguientes campos:
  - Nombre
  - Descripción
  - Precio
  - Número específico
  - Unidad de medida
  - Código de línea
  - Costo unitario
  - Entre otros campos relevantes

- **Búsqueda cronológica** para localizar códigos o conceptos y minimizar tiempos de consulta

- **Migración de inventario** para la alimentación inicial del sistema

#### 🛒 Ingreso de Compras al Sistema

**Funcionalidades requeridas:**

- **Registro de documentos** (Factura o CCF)
- **Generación automática** de número de documento
- **Tipos de compra**: efectivo o crédito
- **Registro completo** de:
  - Proveedor
  - Fechas
  - Origen de fondos
  - Notas administrativas
- **Clasificación de productos** por:
  - Código
  - Descripción
  - Precio
- **Ingreso y visualización** de:
  - Precio unitario
  - Total
  - Unidad de medida
  - Otros datos relevantes

#### 🔄 Traslados entre Bodegas

**Funcionalidades requeridas:**

- **Registro de bodega de origen** (Bodega General)
- **Selección de bodega de destino** (Fraccionaria)
- **Registro detallado** de:
  - Productos
  - Cantidades
  - Precios unitarios
- **Control de existencias** por cada traslado realizado

#### 🎁 Recepción y Registro de Donaciones

**Funcionalidades requeridas:**

- **Ingreso de productos donados** (animales, abonos, medicamentos, etc.)
- **Registro completo** de:
  - Fecha de donación
  - Donante (persona o institución)
  - Destino de utilización
  - Otros datos relevantes
- **Asociación de documentos** de donación
- **Traslado automático** al inventario de la bodega fraccionaria correspondiente

#### 📝 Otros Registros

**Funcionalidades requeridas:**

- **Ingreso de productos** adquiridos por:
  - Convenios
  - Proyectos
  - Otras modalidades especiales

- **Registro retroactivo** de facturas y transacciones que no pudieron ser ingresadas en el mes actual por atrasos en proyectos

- **Flexibilidad** en fechas de registro para adaptarse a los tiempos de los proyectos

#### 📤 Despachos desde Bodega General

**Funcionalidades requeridas:**

- **Registro de despachos** por las diferentes unidades operativas
- **Control de existencias** en tiempo real para validar despachos
- **Seguimiento** de productos despachados por unidad solicitante

#### 🔒 Cierre de Inventario Mensual

**Funcionalidades requeridas:**

- **Cierre de inventario** del mes en curso
- **Reversión de cierre** cuando sea necesario realizar ajustes
- **Validaciones** antes del cierre para garantizar integridad de datos

#### 📊 Control de Kardex

**Funcionalidades requeridas:**

- **Control detallado** de:
  - Entradas
  - Salidas
  - Saldos actuales
  - Fechas de movimientos
  - Costo unitario
  - Otros datos de trazabilidad

#### ⚖️ Ajustes de Inventario

**Funcionalidades requeridas:**

- **Registro de ajustes** por:
  - Deterioro de productos
  - Vencimiento
  - Pérdidas detectadas
  - Sobrantes encontrados en inventarios físicos
- **Documentación** de causas y justificaciones de cada ajuste
- **Aprobación** de ajustes según niveles de autorización


### 📦 Procesos Bodegas Fraccionarias

#### 📥 Recepción de Traslados desde Bodega General

**Funcionalidades requeridas:**

- **Validación de inventario recibido** vs. documentos de traslado
- **Control de documentos de soporte** para cada recepción
- **Confirmación** de recepción con diferencias si las hubiera

#### 🔄 Traslados entre Bodegas Fraccionarias

**Funcionalidades requeridas:**

- **Registro de bodega de origen y destino** (ej. Zootecnia → Cocina)
- **Ingreso de productos** con:
  - Código del producto
  - Cantidad a trasladar
  - Precio unitario
- **Validación de existencias** antes del traslado

#### 📤 Despachos Internos

**Funcionalidades requeridas:**

- **Control de salidas** de productos según necesidades operativas
- **Registro de beneficiario** o área que recibe los productos
- **Justificación** de cada despacho realizado

#### 🔒 Cierre Mensual de Movimientos

**Funcionalidades requeridas:**

- **Consolidación de documentos** incluyendo:
  - Traslados recibidos
  - Traslados enviados
  - Despachos realizados
- **Generación de reportes** de cierre mensual


### 🛠️ Otras Funcionalidades

#### 👥 Control de Usuarios

**Funcionalidades requeridas:**

- **Gestión de roles y permisos** con niveles de acceso diferenciados
- **Alta, baja y modificación** de usuarios del sistema
- **Control de accesos** incluyendo:
  - Registro de inicios y cierres de sesión
  - Bloqueo automático de usuarios inactivos
  - Políticas de seguridad de contraseñas
- **Bitácora de actividades** con registro detallado de:
  - Ingresos al sistema
  - Traslados realizados
  - Despachos ejecutados
  - Ajustes de inventario
  - Consultas realizadas
  - Reportes generados
  - Timestamp y usuario responsable de cada acción


#### 🔍 Consultas

**Funcionalidades requeridas:**

- **Consulta de existencias** en tiempo real por bodega
- **Consulta de Kardex** histórico y actual
- **Consulta de movimientos** por períodos y tipos
- **Búsquedas avanzadas** por:
  - Proveedor
  - Número de factura
  - Despachos específicos
  - Traslados realizados
  - Código de producto
  - Usuario que realizó la transacción
  - Rangos de fechas
  - Otros criterios de filtrado

#### 📊 Reportería

**Funcionalidades requeridas:**

##### Reportes de Inventario
- **Inventario consolidado** por:
  - Bodega individual
  - Bodegas fraccionarias
  - Inventario global

##### Reportes de Movimientos
- **Movimientos mensuales** incluyendo:
  - Ingresos por período
  - Consumo mensual por línea de productos
  - Traslados entre bodegas
  - Despachos realizados
  - Ajustes de inventario
  - Desglose por bodega

##### Reportes Kardex
- **Kardex detallado** con:
  - Exportación en formatos PDF y Excel
  - Filtros por producto, categoría o período
  - Histórico completo de movimientos

##### Reportes Administrativos y Financieros
- **Informes para UFI y Gerencia Administrativa**:
  - Valor de inventarios actuales
  - Resumen de movimientos financieros
  - Análisis de consumo y rotación

##### Reportes Especializados
- **Resumen de transacciones** por línea de producto
- **Resumen de compras** por línea de producto y proveedor
- **Compras por proveedor** con análisis comparativo
- **Autoconsumo** y utilización interna
- **Donaciones** recibidas y su destino
- **Diferencias de inventario** para consultas pre-cierre y durante cierre

##### Reportes Personalizados
- **Generación de reportes** bajo parámetros definidos por el usuario
- **Configuración flexible** de campos y filtros
- **Programación** de reportes automáticos

#### 📚 Histórico

**Funcionalidades requeridas:**

- **Registro completo en bitácora** de cada transacción con:
  - Usuario responsable
  - Fecha y hora exacta
  - Acción realizada
  - Detalles de la operación
  - Garantía de control y transparencia

- **Línea de tiempo por producto** mostrando:
  - Trazabilidad desde ingreso hasta consumo final
  - Todos los traslados intermedios
  - Ubicaciones por las que ha pasado
  - Fechas y responsables de cada movimiento

#### 🔧 Funcionalidades Adicionales

##### Exportación e Importación
- **Exportación** a formatos PDF y XLSX
- **Importación de datos** masiva mediante:
  - Archivos Excel o CSV
  - Carga de productos iniciales
  - Inventarios de inicio
  - Ajustes masivos

##### Sistema de Alertas
- **Alertas y notificaciones** automáticas para:
  - Intentos de salida superior a existencias
  - Fechas de mes cerrado
  - Stock mínimo alcanzado
  - Productos próximos a vencer
  - Otros eventos críticos del sistema

##### Dashboard Gráfico
- **Panel de control** con:
  - Indicadores clave de gestión (KPIs)
  - Métricas de inventario en tiempo real
  - Alertas visuales prioritarias
- **Gráficas dinámicas** mostrando:
  - Tendencias de consumo
  - Rotación de inventarios
  - Movimientos por período
  - Análisis comparativos

##### Gestión Documental
- **Adjuntar documentos digitales** en formato PDF e imagen:
  - Facturas de compra
  - Comprobantes de Crédito Fiscal (CCF)
  - Documentos de donaciones
  - Actas de ajustes de inventario
  - Otros documentos de soporte
- **Organización** y **búsqueda** eficiente de documentos adjuntos
