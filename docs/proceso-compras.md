# Proceso de Compras

Este documento describe el flujo de trabajo completo para la gestión de compras en el sistema de bodega.

## Estados de una Compra

Una compra puede tener los siguientes estados:

| Estado | Descripción | Color |
|--------|-------------|-------|
| **Borrador** | Compra recién creada, puede ser editada o eliminada | Gris |
| **Pendiente** | Compra enviada para aprobación, esperando revisión | Amarillo |
| **Aprobado** | Compra aprobada, lista para recibir los productos | Azul |
| **Recibido** | Productos recibidos, inventario actualizado | Verde |
| **Cancelado** | Compra cancelada, no se procesará | Rojo |

## Diagrama de Flujo

```
┌─────────────┐
│  BORRADOR   │ ◄── Crear compra
└──────┬──────┘
       │
       │ Enviar
       ▼
┌─────────────┐
│  PENDIENTE  │ ◄── Esperando aprobación
└──────┬──────┘
       │
       │ Aprobar
       ▼
┌─────────────┐
│  APROBADO   │ ◄── Listo para recibir
└──────┬──────┘
       │
       │ Recibir
       ▼
┌─────────────┐
│  RECIBIDO   │ ◄── Inventario actualizado
└─────────────┘

* Desde BORRADOR o PENDIENTE se puede CANCELAR
* Solo BORRADOR puede ser EDITADO o ELIMINADO
```

## Actores del Sistema

### 1. Super Administrador
- **Rol**: Acceso completo al sistema
- **Permisos en Compras**:
  - Crear compras en cualquier empresa
  - Ver todas las compras
  - Editar compras en borrador
  - Enviar compras para aprobación
  - Aprobar compras pendientes
  - Recibir compras aprobadas
  - Cancelar compras (borrador/pendiente)
  - Eliminar compras en borrador
  - Restaurar compras eliminadas

### 2. Administrador de Empresa
- **Rol**: Gestiona todas las operaciones de su empresa
- **Permisos en Compras**:
  - Crear compras para su empresa
  - Ver compras de su empresa
  - Editar compras en borrador de su empresa
  - Enviar compras para aprobación
  - Aprobar compras pendientes de su empresa
  - Recibir compras aprobadas de su empresa
  - Cancelar compras de su empresa
  - Eliminar compras en borrador de su empresa

### 3. Gerente de Bodega
- **Rol**: Administra las operaciones de bodegas asignadas
- **Permisos en Compras**:
  - Crear compras para sus bodegas
  - Ver compras de sus bodegas
  - Editar compras en borrador de sus bodegas
  - Enviar compras para aprobación
  - Aprobar compras pendientes de sus bodegas
  - Recibir compras aprobadas de sus bodegas
  - Cancelar compras de sus bodegas
  - Eliminar compras en borrador de sus bodegas

### 4. Operador de Bodega
- **Rol**: Ejecuta operaciones diarias en la bodega
- **Permisos en Compras**:
  - Ver compras de sus bodegas
  - Recibir compras aprobadas de sus bodegas

### 5. Creador de la Compra
- **Rol**: Usuario que creó la compra (cualquier rol)
- **Permisos Adicionales**:
  - Editar su propia compra en borrador
  - Enviar su propia compra para aprobación
  - Cancelar su propia compra en borrador
  - Eliminar su propia compra en borrador

## Flujo de Trabajo Detallado

### Paso 1: Crear Compra (Estado: Borrador)

**Quién puede crear**: Super Admin, Admin de Empresa, Gerente de Bodega

1. El usuario accede a "Compras" > "Nueva Compra"
2. Completa la información del documento:
   - Selecciona la bodega destino
   - Selecciona el proveedor
   - Tipo de documento (Factura, CCF, Ticket, Otro)
   - Número y fecha del documento
   - Tipo de compra (Efectivo/Crédito)
   - Método de pago
   - Tipo de adquisición (Normal, Convenio, Proyecto)
3. Agrega los productos con:
   - Cantidad
   - Costo unitario
   - Descuento (opcional)
   - IVA (opcional)
   - Número de lote (opcional)
   - Fecha de vencimiento (opcional)
4. Guarda como borrador

**Resultado**: La compra queda en estado `borrador` y puede ser editada.

### Paso 2: Enviar para Aprobación (Borrador → Pendiente)

**Quién puede enviar**: Creador de la compra, Super Admin, Admin de Empresa, Gerente de Bodega

1. El usuario revisa la compra en estado borrador
2. Verifica que toda la información esté correcta
3. Hace clic en "Enviar"
4. Confirma la acción en el modal de confirmación

**Resultado**: La compra cambia a estado `pendiente` y ya no puede ser editada.

### Paso 3: Aprobar Compra (Pendiente → Aprobado)

**Quién puede aprobar**: Super Admin, Admin de Empresa, Gerente de Bodega (de la bodega correspondiente)

1. El aprobador revisa la compra pendiente
2. Verifica:
   - Proveedor correcto
   - Productos y cantidades
   - Precios y totales
   - Documentación
3. Hace clic en "Aprobar"
4. Confirma la acción en el modal de confirmación

**Resultado**:
- La compra cambia a estado `aprobado`
- Se registra quién aprobó y cuándo
- La compra está lista para recibir los productos

### Paso 4: Recibir Compra (Aprobado → Recibido)

**Quién puede recibir**: Super Admin, Admin de Empresa, Gerente de Bodega, Operador de Bodega

1. Cuando llegan los productos físicamente a la bodega
2. El usuario verifica los productos recibidos
3. Hace clic en "Recibir"
4. Confirma la acción en el modal de confirmación

**Resultado**:
- La compra cambia a estado `recibido`
- Se registra quién recibió y cuándo
- **Se actualiza automáticamente el inventario**:
  - Se crean movimientos de entrada para cada producto
  - Se actualiza el saldo de cada producto en la bodega
  - Se registra el costo unitario para valuación

### Cancelar Compra (Borrador/Pendiente → Cancelado)

**Quién puede cancelar**: Super Admin, Admin de Empresa, Gerente de Bodega, Creador (solo borrador)

1. El usuario puede cancelar una compra que aún no ha sido aprobada
2. Hace clic en "Cancelar"
3. Confirma la acción en el modal de confirmación

**Resultado**: La compra cambia a estado `cancelado` y no afecta el inventario.

> **Nota**: Las compras aprobadas o recibidas NO pueden ser canceladas.

### Eliminar Compra (Solo Borrador)

**Quién puede eliminar**: Super Admin, Admin de Empresa, Gerente de Bodega, Creador

1. Solo las compras en borrador pueden ser eliminadas
2. El usuario hace clic en "Eliminar"
3. Confirma la acción en el modal de confirmación

**Resultado**: La compra se elimina (soft delete) del sistema.

## Compras Retroactivas

Cuando la fecha del documento es anterior al mes actual, la compra se marca automáticamente como **retroactiva**. Esto permite:

- Registrar compras de períodos anteriores
- Mantener trazabilidad de cuándo se registró vs. cuándo ocurrió
- Facilitar auditorías y cierres de inventario

## Tipos de Adquisición

| Tipo | Descripción | Campos Adicionales |
|------|-------------|-------------------|
| **Normal** | Compra regular de productos | Ninguno |
| **Convenio** | Compra bajo convenio marco | Número de Convenio (requerido) |
| **Proyecto** | Compra para proyecto específico | Nombre del Proyecto (requerido) |
| **Otro** | Otros tipos de adquisición | Ninguno |

## Impacto en el Inventario

El inventario solo se actualiza cuando una compra es **recibida**. En ese momento:

1. Se crea un movimiento de inventario tipo "Entrada" para cada producto
2. El movimiento incluye:
   - Cantidad recibida
   - Costo unitario
   - Número de lote (si aplica)
   - Fecha de vencimiento (si aplica)
   - Referencia al documento de compra
3. Se actualiza el saldo del producto en la bodega

## Resumen de Permisos por Estado

| Acción | Borrador | Pendiente | Aprobado | Recibido | Cancelado |
|--------|----------|-----------|----------|----------|-----------|
| Ver | Todos* | Todos* | Todos* | Todos* | Todos* |
| Editar | Creador, Admins, Gerente | - | - | - | - |
| Enviar | Creador, Admins, Gerente | - | - | - | - |
| Aprobar | - | Admins, Gerente | - | - | - |
| Recibir | - | - | Admins, Gerente, Operador | - | - |
| Cancelar | Creador**, Admins, Gerente | Admins, Gerente | - | - | - |
| Eliminar | Creador, Admins, Gerente | - | - | - | - |

*Según permisos de empresa/bodega
**Solo el creador puede cancelar sus propios borradores

## Notificaciones

El sistema puede configurarse para enviar notificaciones en los siguientes eventos:

- Nueva compra creada
- Compra enviada para aprobación
- Compra aprobada
- Compra recibida
- Compra cancelada

---

## Caso Práctico: Compra de Insumos Agrícolas en la ENA

### Escenario
La Escuela Nacional de Agricultura (ENA) necesita adquirir fertilizantes y semillas para el ciclo de siembra del próximo semestre.

### Actores Involucrados
- **María García** - Encargada de Bodega Central (Gerente de Bodega)
- **Carlos López** - Jefe de Compras (Administrador de Empresa)
- **Juan Martínez** - Bodeguero (Operador de Bodega)

### Flujo del Proceso

#### 1. Creación de la Compra (María García)

María, como encargada de bodega, detecta que el inventario de fertilizante NPK está bajo. Crea una nueva compra:

```
Proveedor: Agroinsumos S.A. de C.V.
Bodega: Bodega Central - ENA
Tipo de Documento: Factura
Número: FAC-2024-0847
Fecha: 22/11/2024
Tipo de Compra: Crédito (30 días)
Tipo de Adquisición: Normal

Productos:
┌─────────────────────────┬──────────┬────────────┬──────────┐
│ Producto                │ Cantidad │ Costo Unit.│ Total    │
├─────────────────────────┼──────────┼────────────┼──────────┤
│ Fertilizante NPK 15-15-15│ 50 qq   │ $45.00     │ $2,250.00│
│ Semilla de Maíz H-59    │ 20 qq    │ $85.00     │ $1,700.00│
│ Insecticida Cipermetrina│ 10 lt    │ $35.00     │ $350.00  │
└─────────────────────────┴──────────┴────────────┴──────────┘
                                        Subtotal: $4,300.00
                                        IVA 13%:  $559.00
                                        TOTAL:    $4,859.00
```

**Estado**: `Borrador`

#### 2. Revisión y Envío (María García)

María revisa que los productos, cantidades y precios sean correctos según la cotización del proveedor. Una vez verificado:

1. Hace clic en **"Enviar"**
2. Aparece el modal de confirmación:
   > "Está a punto de enviar esta compra para su aprobación.
   > Una vez enviada, no podrá editarla hasta que sea rechazada."
3. Confirma el envío

**Estado**: `Pendiente`

#### 3. Aprobación (Carlos López)

Carlos, como Jefe de Compras, recibe la notificación de compra pendiente. Revisa:

- ✓ Proveedor autorizado
- ✓ Precios según convenio vigente
- ✓ Presupuesto disponible
- ✓ Documentación completa

Hace clic en **"Aprobar"** y confirma en el modal.

**Estado**: `Aprobado`

*Se registra: Aprobado por Carlos López - 22/11/2024 10:30 AM*

#### 4. Recepción de Productos (Juan Martínez)

Tres días después, llega el camión de Agroinsumos. Juan, el bodeguero:

1. Verifica físicamente los productos contra la factura
2. Cuenta los quintales de fertilizante: 50 ✓
3. Verifica las semillas: 20 quintales ✓
4. Revisa los litros de insecticida: 10 ✓
5. Ingresa al sistema y busca la compra
6. Hace clic en **"Recibir"**
7. Confirma en el modal:
   > "¿Confirma que ha recibido todos los productos de esta compra?
   > Esta acción actualizará el inventario con los productos recibidos."

**Estado**: `Recibido`

*Se registra: Recibido por Juan Martínez - 25/11/2024 2:15 PM*

#### 5. Actualización Automática del Inventario

Al confirmar la recepción, el sistema automáticamente:

```
MOVIMIENTOS DE INVENTARIO GENERADOS:

Bodega: Bodega Central - ENA
Tipo: Entrada por Compra
Referencia: PUR-20241122-ABC123

┌─────────────────────────┬──────────┬──────────┬──────────────┐
│ Producto                │ Entrada  │ Saldo Ant│ Nuevo Saldo  │
├─────────────────────────┼──────────┼──────────┼──────────────┤
│ Fertilizante NPK 15-15-15│ +50 qq  │ 15 qq    │ 65 qq        │
│ Semilla de Maíz H-59    │ +20 qq   │ 5 qq     │ 25 qq        │
│ Insecticida Cipermetrina│ +10 lt   │ 3 lt     │ 13 lt        │
└─────────────────────────┴──────────┴──────────┴──────────────┘
```

### Caso Alternativo: Compra por Proyecto

Si la compra fuera para un proyecto específico como "Proyecto de Huertos Escolares 2024":

```
Tipo de Adquisición: Proyecto
Nombre del Proyecto: Proyecto de Huertos Escolares 2024
Origen de Fondos: Donación USAID
```

Esto permite generar reportes filtrados por proyecto para rendición de cuentas.

### Caso Alternativo: Compra por Convenio

Si existiera un convenio con el Ministerio de Agricultura:

```
Tipo de Adquisición: Convenio
Número de Convenio: CONV-MAG-ENA-2024-015
```

### Trazabilidad Completa

En cualquier momento, los administradores pueden ver:

- Quién creó la compra
- Quién la envió para aprobación
- Quién la aprobó y cuándo
- Quién recibió los productos y cuándo
- Todos los movimientos de inventario relacionados

Esta información es esencial para auditorías y controles internos de la institución.

---

*Última actualización: Noviembre 2024*
