# 🔄 Traslados vs 📝 Movimientos - Guía Completa

## Diferencias Clave entre Traslados y Movimientos

Este documento explica las diferencias fundamentales entre los módulos de **Traslados** y **Movimientos** en el sistema de gestión de bodega.

---

## 🔄 **Traslados (Transfers)** - `/inventory/transfers`

### **Propósito**
Mover inventario **entre diferentes bodegas/ubicaciones**

### **Características**
- Involucra **dos bodegas**: origen (fuente) y destino
- Requiere **flujo de aprobación**: Borrador → Pendiente → En Tránsito → Recibido
- Rastrea el **movimiento físico** de productos entre ubicaciones
- Tiene un **impacto dual** en el inventario:
  - Disminuye el stock en la bodega origen
  - Aumenta el stock en la bodega destino
- Requiere que la bodega destino **confirme la recepción**
- Se utiliza para **logística inter-bodegas**

### **Ejemplo de Escenario**
```
Traslado #TRF-001
Desde: Bodega Central → Hacia: Bodega Sucursal Norte
Producto: Arroz 50lb
Cantidad: 100 unidades

Flujo de Estados:
1. Borrador → Creado
2. Pendiente → Esperando aprobación
3. En Tránsito → Enviado desde origen
4. Recibido → Confirmada llegada al destino
```

### **Casos de Uso**
- Mover inventario entre sucursales
- Redistribuir stock para balancear niveles de bodega
- Abastecer una sucursal desde la bodega central
- Reubicar productos para mejor distribución

---

## 📝 **Movimientos (Movements)** - `/inventory/movements`

### **Propósito**
**Registrar/visualizar** todas las transacciones de inventario que han ocurrido

### **Características**
- **Registro de solo lectura** de todos los cambios de inventario
- Muestra el **historial completo** de movimientos de stock
- Incluye **todos los tipos de transacciones**:
  - ✅ Compras (ingresos por compra)
  - ✅ Despachos (salidas por despacho)
  - ✅ Traslados (tanto enviados como recibidos)
  - ✅ Donaciones (ingresos por donación)
  - ✅ **Ajustes (ajustes de inventario)** - *Ver sección de Ajustes más abajo*
  - ✅ Cierres (cierres mensuales)
- Muestra **saldo corriente** (estilo kardex)
- **No se pueden crear** movimientos directamente - se generan automáticamente por otros módulos
- Se utiliza para **auditoría y reportes**
- **Cuando el movimiento proviene de un Ajuste**, se muestra:
  - El **tipo de ajuste** (badge morado): Producto Vencido, Producto Dañado, Pérdida/Robo, etc.
  - La **razón** del ajuste (título del ajuste)
  - La **justificación** (explicación detallada)
  - El **número de ajuste** (ej: ADJ-20250121-ABC123)

### **Ejemplo de Vista**
```
Historial de Movimientos - Producto: Arroz 50lb, Bodega: Central

Fecha      | Tipo      | Entrada | Salida | Saldo | Referencia
-----------|-----------|---------|--------|-------|------------
2025-01-15 | Compra    | 500     | 0      | 500   | PUR-001
2025-01-18 | Traslado  | 0       | 100    | 400   | TRF-001 (a Norte)
2025-01-20 | Despacho  | 0       | 50     | 350   | DSP-001
2025-01-22 | Ajuste    | 10      | 0      | 360   | ADJ-001
```

### **Casos de Uso**
- Revisar todas las transacciones de un producto
- Auditar cambios de inventario
- Generar reportes kardex
- Investigar discrepancias
- Rastrear quién hizo cambios y cuándo

---

## 🔧 **Ajustes (Adjustments)** - `/adjustments`

### **Propósito**
**Corregir diferencias** entre el inventario físico y el sistema, con documentación y justificación.

### **¿Por qué existen los Ajustes?**
Los ajustes son la forma **formal y auditada** de corregir el inventario cuando hay discrepancias. A diferencia de los movimientos manuales, los ajustes:
- Requieren **aprobación** antes de aplicarse
- Tienen **tipos predefinidos** que categorizan la razón
- Documentan **razón y justificación** detallada
- Permiten agregar **acciones correctivas**
- Mantienen **trazabilidad completa** (quién solicitó, quién aprobó, cuándo)

### **Tipos de Ajuste (adjustment_type)**

| Tipo | Descripción | Impacto | Cuándo usar |
|------|-------------|---------|-------------|
| **positive** | Ajuste Positivo | +Stock | Inventario encontrado, sobrantes detectados |
| **negative** | Ajuste Negativo | -Stock | Faltantes detectados en conteo |
| **damage** | Producto Dañado | -Stock | Productos rotos, deteriorados, mojados |
| **expiry** | Producto Vencido | -Stock | Productos que pasaron fecha de vencimiento |
| **loss** | Pérdida/Robo | -Stock | Productos desaparecidos sin explicación |
| **correction** | Corrección de Conteo | +/- Stock | Errores de captura o conteo previo |
| **return** | Devolución | +/- Stock | Devoluciones de cliente (no vendibles) |
| **other** | Otro | +/- Stock | Casos especiales no categorizados |

### **Flujo de Trabajo de Ajustes**

```
1. BORRADOR (draft)
   │  └─ Usuario crea el ajuste con razón y justificación
   ▼
2. PENDIENTE (pending)
   │  └─ Enviado para aprobación
   ▼
3. APROBADO (approved)        ◄─── Rechazado? → RECHAZADO (rejected)
   │  └─ Supervisor aprueba con notas opcionales
   ▼
4. PROCESADO (processed)
   │  └─ Sistema aplica el ajuste y CREA EL MOVIMIENTO
   ▼
✅ El movimiento de inventario se genera automáticamente
```

### **Relación Ajuste → Movimiento**

Cuando un ajuste es **procesado**, el sistema:
1. Crea un **InventoryMovement** con los datos del ajuste
2. El movimiento incluye referencia al `adjustment_number`
3. En la vista de movimientos, se muestra:
   - **Badge morado** con el tipo de ajuste en español
   - La **razón** (título corto)
   - La **justificación** (explicación detallada)
   - El **número de ajuste** como referencia

### **Ejemplo de Ajuste Procesado**

```
Ajuste: ADJ-2024-0123
Tipo: Producto Vencido (expiry)
Razón: "Producto vencido detectado en conteo físico"
Justificación: "Durante la inspección mensual de inventario se
               detectaron 2 kg de levadura fresca que superaron
               su fecha de vencimiento. Producto destruido según protocolo."
Producto: Levadura fresca
Cantidad: -2 kg
Aprobado por: Supervisor Bodega

→ Genera Movimiento:
   Tipo: Salida (out)
   Cantidad: -2 kg
   Referencia: ADJ-2024-0123
   Razón mostrada: "Producto Vencido" + descripción completa
```

### **En la Vista de Movimientos**

Cuando ves un movimiento que vino de un ajuste, la columna **Referencia/Razón** muestra:

```
┌─────────────────────────────────────────────┐
│ [Producto Vencido]                          │  ← Badge morado con tipo
│ Producto vencido detectado en conteo físico │  ← Razón (título)
│ Durante la inspección mensual...            │  ← Justificación (truncada)
│ Ajuste: ADJ-2024-0123                       │  ← Número de referencia
└─────────────────────────────────────────────┘
```

### **Diferencia: Ajuste Formal vs Movimiento Manual**

| Aspecto | Ajuste Formal (`/adjustments`) | Movimiento Manual (Reg. Entrada/Salida) |
|---------|-------------------------------|----------------------------------------|
| **Aprobación** | ✅ RequierLoe aprobación | ❌ Inmediato |
| **Tipo categorizado** | ✅ 8 tipos predefinidos | ❌ Solo entrada/salida |
| **Justificación** | ✅ Campos obligatorios | ⚠️ Solo notas opcionales |
| **Acciones correctivas** | ✅ Campo disponible | ❌ No disponible |
| **Trazabilidad** | ✅ Completa (solicitante, aprobador, fechas) | ⚠️ Solo creador |
| **Auditoría** | ✅ Excelente | ⚠️ Limitada |
| **Caso de uso** | Proceso formal, conteos físicos | Correcciones rápidas/emergencia |

### **¿Cuándo usar Ajustes vs Movimientos Manuales?**

**Usar AJUSTES cuando:**
- Detectas discrepancias en conteo físico
- Productos vencidos o dañados requieren baja formal
- Necesitas documentar la causa y acciones correctivas
- Quieres que un supervisor apruebe el cambio
- Necesitas trazabilidad completa para auditoría

**Usar MOVIMIENTOS MANUALES cuando:**
- Es una emergencia que no puede esperar aprobación
- El monto es menor y no justifica el proceso formal
- Estás en pruebas o corrigiendo datos de test

---

## 🎯 **Resumen de Diferencias Clave**

| Aspecto | Traslados | Ajustes | Movimientos |
|---------|-----------|---------|-------------|
| **Acción** | ✍️ Crear y Ejecutar | ✍️ Crear y Aprobar | 👁️ Ver y Revisar |
| **Propósito** | Mover stock entre bodegas | Corregir discrepancias | Auditar transacciones |
| **Impacto** | Crea 2 movimientos | Crea 1 movimiento | Solo muestra historial |
| **Alcance** | Solo inter-bodegas | Correcciones/Ajustes | Todos los tipos |
| **Flujo** | Borrador→Tránsito→Recibido | Borrador→Aprobado→Procesado | Sin flujo (lectura) |
| **Aprobación** | ✅ Sí | ✅ Sí | ❌ N/A |
| **Justificación** | Notas opcionales | ✅ Razón + Justificación | N/A |
| **Tipos** | Único (traslado) | 8 tipos predefinidos | Muestra origen |
| **Bodegas** | 2 (origen + destino) | 1 (donde está el producto) | Vista de 1 bodega |

---

## 🔗 **Cómo se Relacionan**

Cuando creas un **Traslado**, automáticamente genera **Movimientos**:

```
1. Crear Traslado (TRF-001):
   Bodega Central → Bodega Norte
   Producto: Arroz, Cant: 100

2. Esto crea DOS movimientos:

   Movimiento #1 (en registro de Movimientos):
   - Bodega: Bodega Central
   - Tipo: Salida por Traslado
   - Cantidad: -100
   - Saldo: 400 (si era 500)
   - Referencia: TRF-001

   Movimiento #2 (en registro de Movimientos):
   - Bodega: Bodega Norte
   - Tipo: Entrada por Traslado
   - Cantidad: +100
   - Saldo: 100 (si era 0)
   - Referencia: TRF-001
```

---

## 📌 **Botones de Acción en la Página de Movimientos**

En la página de Movimientos (`/inventory/movements`), encontrarás tres botones de acción:

### 1. **"Crear Traslado"** - Navega a `/inventory/transfers`
**Cuándo usar:**
- Necesitas mover productos entre bodegas
- Requieres aprobación y seguimiento formal
- Quieres rastrear productos en tránsito

### 2. **"Registrar Entrada"** - Entrada Manual
**Cuándo usar:**
- **Ajustes rápidos** para aumentar inventario
- **Productos encontrados** durante conteo físico
- **Correcciones de emergencia**
- Un proveedor dejó productos pero no tienes la factura aún

**⚠️ Importante:** Esto crea un movimiento manual que aumenta el stock directamente, sin pasar por el flujo formal de Compra/Donación.

### 3. **"Registrar Salida"** - Salida Manual
**Cuándo usar:**
- **Ajustes rápidos** para disminuir inventario
- **Productos dañados, perdidos o robados**
- **Donaciones pequeñas** sin crear Despacho formal
- **Correcciones de emergencia**

**⚠️ Importante:** Esto crea un movimiento manual que disminuye el stock directamente, sin pasar por el flujo formal de Despacho.

---

## ⚖️ **Transacciones Formales vs Manuales**

### **Transacciones Formales** (Flujo de trabajo recomendado)
Estas pasan por flujos de aprobación apropiados y generan movimientos automáticamente:

| Tipo de Transacción | Módulo | Flujo de Trabajo | Genera Movimientos |
|---------------------|--------|------------------|---------------------|
| Compra | `/purchases` | Borrador → Pendiente → Aprobado → Recibido | ✅ Movimiento de entrada |
| Despacho | `/dispatches` | Borrador → Pendiente → Aprobado → Despachado → Entregado | ✅ Movimiento de salida |
| Traslado | `/transfers` | Borrador → Pendiente → En Tránsito → Recibido | ✅ Dos movimientos (salida + entrada) |
| Donación | `/donations` | Borrador → Pendiente → Aprobado → Recibido | ✅ Movimiento de entrada |
| Ajuste | `/adjustments` | Pendiente → Aprobado → Aplicado | ✅ Movimiento de entrada o salida |

### **Movimientos Manuales** (Rápido y Directo)
Estos omiten los flujos de trabajo y afectan el inventario directamente:

| Botón | Acción | Impacto | Caso de Uso |
|-------|--------|---------|-------------|
| Registrar Entrada | Entrada Manual | +Stock | Aumentos rápidos, inventario encontrado |
| Registrar Salida | Salida Manual | -Stock | Disminuciones rápidas, pérdidas, daños |

---

## ⚠️ **Cuándo Usar Cada Enfoque**

### ✅ Usar **Transacciones Formales** (Compras, Despachos, Traslados) cuando:
- Tienes **documentación apropiada** (facturas, órdenes de despacho, solicitudes de traslado)
- Necesitas **flujos de aprobación**
- Quieres **trazabilidad completa** y pista de auditoría
- Estás siguiendo **procesos de negocio estándar**
- Necesitas rastrear **quién aprobó qué y cuándo**

### ⚠️ Usar **Movimientos Manuales** (Registrar Entrada/Salida) cuando:
- Necesitas una **corrección rápida**
- La documentación **no está disponible aún** o no existe
- Manejas **situaciones excepcionales** (pérdidas, artículos encontrados, daños)
- Haces **ajustes de emergencia**
- Estás **probando** o haciendo **correcciones de datos**

---

## 🎬 **Escenarios del Mundo Real**

### **Escenario 1: Compra Normal**
```
❌ NO HACER: Ir a Movimientos → Registrar Entrada
✅ SÍ HACER: Ir a Compras → Crear Compra → Aprobar → Recibir
Por qué: Tienes documentación apropiada y necesitas pista de auditoría
```

### **Escenario 2: Inventario Extra Encontrado**
```
✅ SÍ HACER: Ir a Movimientos → Registrar Entrada
Razón: "Se encontraron 5 unidades extra durante conteo físico"
Por qué: Sin documentación formal, se necesita ajuste inmediato
```

### **Escenario 3: Productos Dañados**
```
✅ SÍ HACER: Ir a Movimientos → Registrar Salida
Razón: "3 unidades dañadas por fuga de agua"
Por qué: No es un despacho formal, se necesita baja inmediata
```

### **Escenario 4: Traslado Entre Bodegas**
```
❌ NO HACER:
  - Ir a Movimientos → Registrar Salida (desde Central)
  - Ir a Movimientos → Registrar Entrada (a Norte)
✅ SÍ HACER: Ir a Traslados → Crear Traslado
Por qué: Los traslados necesitan aprobación y seguimiento apropiado del inventario en tránsito
```

---

## 📊 **Comparación Visual**

```
FLUJO FORMAL (Recomendado):
┌─────────────┐
│   Compra    │ ──[Crea]──> [Movimiento de Entrada]
│  Despacho   │ ──[Crea]──> [Movimiento de Salida]
│  Traslado   │ ──[Crea]──> [Movimientos de Salida + Entrada]
│  Donación   │ ──[Crea]──> [Movimiento de Entrada]
└─────────────┘

FLUJO MANUAL (Corrección Rápida):
┌─────────────────────┐
│ Registrar Entrada   │ ──[Directo]──> [Movimiento de Entrada]
│ Registrar Salida    │ ──[Directo]──> [Movimiento de Salida]
└─────────────────────┘
```

---

## 🎯 **Mejores Prácticas**

1. **Siempre prefiere transacciones formales** cuando existe documentación
2. **Usa movimientos manuales con moderación** solo para excepciones
3. **Agrega notas detalladas** cuando uses movimientos manuales para propósitos de auditoría
4. **Revisa los movimientos manuales regularmente** para asegurar que son legítimos
5. **Considera crear ajustes formales** en lugar de movimientos manuales para mejor trazabilidad

---

## 🔐 **Nota de Seguridad**

En un sistema de producción, deberías:
- **Restringir permisos** para los botones "Registrar Entrada/Salida" solo a supervisores/gerentes
- **Requerir razón de autorización** para todos los movimientos manuales
- **Marcar movimientos manuales** en reportes para revisión gerencial
- **Configurar alertas** cuando los movimientos manuales excedan ciertos umbrales

---

## 📝 **En Resumen**

| Característica | Descripción |
|---------------|-------------|
| **Traslados** | Mover entre bodegas (formal, con aprobación) |
| **Registrar Entrada** | Aumento manual rápido de inventario (omite flujo de trabajo) |
| **Registrar Salida** | Disminución manual rápida de inventario (omite flujo de trabajo) |

Los botones manuales existen para **situaciones excepcionales y correcciones rápidas**, pero las **transacciones formales** (Compras, Despachos, Traslados) deben ser tu **flujo de trabajo predeterminado** para operaciones normales! 🎯

---

## 🔗 **Enlaces Rápidos**

- **Crear Traslado**: [http://bodega.test/inventory/transfers](http://bodega.test/inventory/transfers)
- **Ver Movimientos**: [http://bodega.test/inventory/movements](http://bodega.test/inventory/movements)
- **Crear Compra**: [http://bodega.test/purchases/create](http://bodega.test/purchases/create)
- **Crear Despacho**: [http://bodega.test/dispatches/create](http://bodega.test/dispatches/create)
- **Crear Ajuste**: [http://bodega.test/adjustments/create](http://bodega.test/adjustments/create)

---

**Última actualización:** 21 de Noviembre, 2025
**Versión del Sistema:** 1.0 (97% completitud)
