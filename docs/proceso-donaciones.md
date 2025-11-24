# Proceso de Donaciones

Este documento describe el flujo de trabajo completo para la gestion de donaciones en el sistema de bodega.

## Estados de una Donacion

Una donacion puede tener los siguientes estados:

| Estado | Descripcion | Color |
|--------|-------------|-------|
| **Borrador** | Donacion recien creada, puede ser editada o eliminada | Gris |
| **Pendiente** | Donacion enviada para aprobacion, esperando revision | Amarillo |
| **Aprobado** | Donacion aprobada, lista para recibir los productos | Azul |
| **Recibido** | Productos recibidos, inventario actualizado | Verde |
| **Cancelado** | Donacion cancelada, no se procesara | Rojo |

## Diagrama de Flujo

```
+---------------+
|   BORRADOR    | <-- Crear donacion
+-------+-------+
        |
        | Enviar
        v
+---------------+
|  PENDIENTE    | <-- Esperando aprobacion
+-------+-------+
        |
        | Aprobar
        v
+---------------+
|   APROBADO    | <-- Listo para recibir
+-------+-------+
        |
        | Recibir
        v
+---------------+
|   RECIBIDO    | <-- Inventario actualizado
+---------------+

* Desde BORRADOR, PENDIENTE o APROBADO se puede CANCELAR
* Solo BORRADOR y PENDIENTE pueden ser EDITADOS
* Solo BORRADOR puede ser ELIMINADO
```

## Diferencias con el Proceso de Compras

| Aspecto | Compras | Donaciones |
|---------|---------|------------|
| Origen | Proveedor comercial | Donante (individual, organizacion, gobierno) |
| Documento | Factura, CCF, Ticket | Acta, Carta, Convenio |
| Costo | Precio de compra real | Valor estimado |
| Pago | Efectivo/Credito | Sin costo |
| Campos adicionales | Terminos de pago, descuentos | Proposito, uso previsto, condiciones |
| Recibo fiscal | No aplica | Puede ser requerido por el donante |

## Actores del Sistema

### 1. Super Administrador
- **Rol**: Acceso completo al sistema
- **Permisos en Donaciones**:
  - Crear donaciones en cualquier empresa
  - Ver todas las donaciones
  - Editar donaciones en borrador/pendiente
  - Enviar donaciones para aprobacion
  - Aprobar donaciones pendientes
  - Recibir donaciones aprobadas
  - Cancelar donaciones (borrador/pendiente/aprobado)
  - Eliminar donaciones en borrador
  - Restaurar donaciones eliminadas

### 2. Administrador de Empresa
- **Rol**: Gestiona todas las operaciones de su empresa
- **Permisos en Donaciones**:
  - Crear donaciones para su empresa
  - Ver donaciones de su empresa
  - Editar donaciones en borrador/pendiente de su empresa
  - Enviar donaciones para aprobacion
  - Aprobar donaciones pendientes de su empresa
  - Recibir donaciones aprobadas de su empresa
  - Cancelar donaciones de su empresa
  - Eliminar donaciones en borrador de su empresa

### 3. Gerente de Bodega
- **Rol**: Administra las operaciones de bodegas asignadas
- **Permisos en Donaciones**:
  - Crear donaciones para sus bodegas
  - Ver donaciones de sus bodegas
  - Editar donaciones en borrador/pendiente de sus bodegas
  - Enviar donaciones para aprobacion
  - Aprobar donaciones pendientes de sus bodegas
  - Recibir donaciones aprobadas de sus bodegas
  - Cancelar donaciones de sus bodegas
  - Eliminar donaciones en borrador de sus bodegas

### 4. Operador de Bodega
- **Rol**: Ejecuta operaciones diarias en la bodega
- **Permisos en Donaciones**:
  - Ver donaciones de sus bodegas
  - Recibir donaciones aprobadas de sus bodegas

### 5. Creador de la Donacion
- **Rol**: Usuario que creo la donacion (cualquier rol)
- **Permisos Adicionales**:
  - Editar su propia donacion en borrador/pendiente
  - Enviar su propia donacion para aprobacion
  - Cancelar su propia donacion en borrador
  - Eliminar su propia donacion en borrador

## Flujo de Trabajo Detallado

### Paso 1: Crear Donacion (Estado: Borrador)

**Quien puede crear**: Super Admin, Admin de Empresa, Gerente de Bodega

1. El usuario accede a "Donaciones" > "Nueva Donacion"
2. Completa la informacion del donante:
   - Nombre del donante
   - Tipo de donante (Individual, Organizacion, Gobierno)
   - Datos de contacto (email, telefono, direccion)
   - Persona de contacto
3. Completa la informacion del documento:
   - Tipo de documento (Acta, Carta, Convenio, Otro)
   - Numero de documento
   - Fecha del documento
   - Fecha de recepcion planificada
4. Informacion adicional:
   - Proposito de la donacion
   - Uso previsto
   - Nombre del proyecto (opcional)
   - Condiciones especiales
   - Requiere recibo fiscal (si/no)
5. Agrega los productos donados con:
   - Producto
   - Cantidad
   - Valor unitario estimado
   - Condicion (Nuevo, Usado, Reacondicionado)
   - Notas de condicion
   - Numero de lote (opcional)
   - Fecha de vencimiento (opcional)
6. Guarda como borrador

**Resultado**: La donacion queda en estado `borrador` y puede ser editada.

### Paso 2: Enviar para Aprobacion (Borrador -> Pendiente)

**Quien puede enviar**: Creador de la donacion, Super Admin, Admin de Empresa, Gerente de Bodega

1. El usuario revisa la donacion en estado borrador
2. Verifica que toda la informacion este correcta
3. Hace clic en "Enviar para Aprobacion"
4. Confirma la accion en el modal de confirmacion

**Resultado**: La donacion cambia a estado `pendiente`.

### Paso 3: Aprobar Donacion (Pendiente -> Aprobado)

**Quien puede aprobar**: Super Admin, Admin de Empresa, Gerente de Bodega (de la bodega correspondiente)

1. El aprobador revisa la donacion pendiente
2. Verifica:
   - Informacion del donante correcta
   - Productos y cantidades
   - Valores estimados razonables
   - Documentacion completa
   - Condiciones aceptables
3. Hace clic en "Aprobar"
4. Confirma la accion en el modal de confirmacion

**Resultado**:
- La donacion cambia a estado `aprobado`
- Se registra quien aprobo y cuando
- La donacion esta lista para recibir los productos

### Paso 4: Recibir Donacion (Aprobado -> Recibido)

**Quien puede recibir**: Super Admin, Admin de Empresa, Gerente de Bodega, Operador de Bodega

1. Cuando llegan los productos fisicamente a la bodega
2. El usuario verifica los productos recibidos contra la documentacion
3. Verifica la condicion de los productos
4. Hace clic en "Recibir"
5. Confirma la accion en el modal de confirmacion

**Resultado**:
- La donacion cambia a estado `recibido`
- Se registra quien recibio y cuando
- **Se actualiza automaticamente el inventario**:
  - Se crean movimientos de entrada para cada producto
  - Se actualiza el saldo de cada producto en la bodega
  - Se registra el valor estimado como costo unitario
  - Se registran lotes y fechas de vencimiento si aplica

### Cancelar Donacion (Borrador/Pendiente/Aprobado -> Cancelado)

**Quien puede cancelar**: Super Admin, Admin de Empresa, Gerente de Bodega, Creador (solo borrador)

1. El usuario puede cancelar una donacion que aun no ha sido recibida
2. Hace clic en "Cancelar"
3. Confirma la accion en el modal de confirmacion

**Resultado**: La donacion cambia a estado `cancelado` y no afecta el inventario.

> **Nota**: Las donaciones recibidas NO pueden ser canceladas porque ya afectaron el inventario.

### Eliminar Donacion (Solo Borrador)

**Quien puede eliminar**: Super Admin, Admin de Empresa, Gerente de Bodega, Creador

1. Solo las donaciones en borrador pueden ser eliminadas
2. El usuario hace clic en "Eliminar"
3. Confirma la accion en el modal de confirmacion

**Resultado**: La donacion se elimina (soft delete) del sistema.

## Tipos de Donante

| Tipo | Descripcion | Ejemplos |
|------|-------------|----------|
| **Individual** | Persona natural que realiza donacion | Ciudadano, ex-alumno, benefactor |
| **Organizacion** | Entidad privada o ONG | Empresa, fundacion, asociacion |
| **Gobierno** | Entidad gubernamental | Ministerio, alcaldia, cooperacion internacional |

## Tipos de Documento

| Tipo | Descripcion | Uso Tipico |
|------|-------------|------------|
| **Acta** | Documento formal de entrega-recepcion | Donaciones gubernamentales |
| **Carta** | Comunicacion escrita del donante | Donaciones individuales |
| **Convenio** | Acuerdo formal entre partes | Donaciones de organizaciones |
| **Otro** | Cualquier otro tipo de documento | Casos especiales |

## Condicion de los Productos

| Condicion | Descripcion |
|-----------|-------------|
| **Nuevo** | Producto sin uso previo, en empaque original |
| **Usado** | Producto con uso previo pero funcional |
| **Reacondicionado** | Producto reparado o restaurado |

## Impacto en el Inventario

El inventario solo se actualiza cuando una donacion es **recibida**. En ese momento:

1. Se crea un movimiento de inventario tipo "Entrada por Donacion" para cada producto
2. El movimiento incluye:
   - Cantidad recibida
   - Valor estimado como costo unitario
   - Numero de lote (si aplica)
   - Fecha de vencimiento (si aplica)
   - Referencia al documento de donacion
   - Nombre del donante en las notas
3. Se actualiza el saldo del producto en la bodega

## Resumen de Permisos por Estado

| Accion | Borrador | Pendiente | Aprobado | Recibido | Cancelado |
|--------|----------|-----------|----------|----------|-----------|
| Ver | Todos* | Todos* | Todos* | Todos* | Todos* |
| Editar | Creador, Admins, Gerente | Creador, Admins, Gerente | - | - | - |
| Enviar | Creador, Admins, Gerente | - | - | - | - |
| Aprobar | - | Admins, Gerente | - | - | - |
| Recibir | - | - | Admins, Gerente, Operador | - | - |
| Cancelar | Creador**, Admins, Gerente | Admins, Gerente | Admins, Gerente | - | - |
| Eliminar | Creador, Admins, Gerente | - | - | - | - |

*Segun permisos de empresa/bodega
**Solo el creador puede cancelar sus propios borradores

## Recibos Fiscales

Cuando un donante requiere recibo fiscal:

1. Se marca la opcion "Requiere recibo fiscal" al crear la donacion
2. Despues de recibir la donacion, se puede registrar:
   - Numero de recibo fiscal emitido
   - Fecha de emision del recibo
3. Esta informacion queda registrada para fines contables y de auditoria

---

## Caso Practico: Donacion de Insumos Agricolas en la ENA

### Escenario
La Escuela Nacional de Agricultura (ENA) recibe una donacion de semillas y herramientas agricolas de la Fundacion Pro-Agricultura.

### Actores Involucrados
- **Maria Garcia** - Encargada de Bodega Central (Gerente de Bodega)
- **Carlos Lopez** - Jefe Administrativo (Administrador de Empresa)
- **Juan Martinez** - Bodeguero (Operador de Bodega)

### Flujo del Proceso

#### 1. Creacion de la Donacion (Maria Garcia)

Maria, como encargada de bodega, recibe la notificacion de que llegara una donacion. Crea el registro en el sistema:

```
INFORMACION DEL DONANTE:
Nombre: Fundacion Pro-Agricultura
Tipo: Organizacion
Contacto: Lic. Roberto Hernandez
Email: rhernandez@proagri.org
Telefono: 2222-3333
Direccion: Colonia Escalon, San Salvador

INFORMACION DEL DOCUMENTO:
Tipo: Convenio
Numero: CONV-FPA-ENA-2024-008
Fecha Documento: 20/11/2024
Fecha Recepcion: 25/11/2024

PROPOSITO Y USO:
Proposito: Apoyo al programa de formacion agricola
Uso Previsto: Practicas de campo de estudiantes de agronomia
Proyecto: Huertos Escolares 2024
Condiciones: Los productos deben usarse exclusivamente para fines educativos
Requiere Recibo Fiscal: Si

PRODUCTOS DONADOS:
+---------------------------+----------+---------------+------------+------------+
| Producto                  | Cantidad | Valor Est.    | Condicion  | Total      |
+---------------------------+----------+---------------+------------+------------+
| Semilla de Maiz Mejorada  | 10 qq    | $75.00        | Nuevo      | $750.00    |
| Semilla de Frijol Negro   | 5 qq     | $60.00        | Nuevo      | $300.00    |
| Azadon Forjado            | 20 und   | $15.00        | Nuevo      | $300.00    |
| Pala Cuadrada             | 20 und   | $12.00        | Nuevo      | $240.00    |
| Machete 24"               | 30 und   | $8.00         | Nuevo      | $240.00    |
+---------------------------+----------+---------------+------------+------------+
                                        VALOR TOTAL ESTIMADO:       $1,830.00
```

**Estado**: `Borrador`

#### 2. Revision y Envio (Maria Garcia)

Maria verifica que toda la informacion coincida con el convenio firmado:

1. Revisa datos del donante
2. Verifica productos y cantidades del convenio
3. Confirma valores estimados razonables
4. Hace clic en **"Enviar para Aprobacion"**
5. Confirma en el modal

**Estado**: `Pendiente`

#### 3. Aprobacion (Carlos Lopez)

Carlos, como Jefe Administrativo, revisa la donacion pendiente:

- Verificacion de convenio vigente
- Productos autorizados para recibir
- Valores estimados correctos
- Condiciones aceptables
- Documentacion completa

Hace clic en **"Aprobar"** y confirma en el modal.

**Estado**: `Aprobado`

*Se registra: Aprobado por Carlos Lopez - 22/11/2024 3:45 PM*

#### 4. Recepcion de Productos (Juan Martinez)

El dia 25 de noviembre llega el camion de la Fundacion. Juan, el bodeguero:

1. Recibe los productos con el representante de la fundacion
2. Verifica fisicamente cada item contra el convenio:
   - Semilla de Maiz: 10 quintales
   - Semilla de Frijol: 5 quintales
   - Azadones: 20 unidades
   - Palas: 20 unidades
   - Machetes: 30 unidades
3. Firma el acta de recepcion
4. Ingresa al sistema y busca la donacion
5. Hace clic en **"Recibir"**
6. Confirma en el modal:
   > "¿Confirma que ha recibido todos los productos de esta donacion?
   > Esta accion actualizara el inventario con los productos recibidos."

**Estado**: `Recibido`

*Se registra: Recibido por Juan Martinez - 25/11/2024 10:30 AM*

#### 5. Actualizacion Automatica del Inventario

Al confirmar la recepcion, el sistema automaticamente:

```
MOVIMIENTOS DE INVENTARIO GENERADOS:

Bodega: Bodega Central - ENA
Tipo: Entrada por Donacion
Referencia: DON-20241122-ABC123
Donante: Fundacion Pro-Agricultura

+---------------------------+----------+------------+--------------+
| Producto                  | Entrada  | Saldo Ant. | Nuevo Saldo  |
+---------------------------+----------+------------+--------------+
| Semilla de Maiz Mejorada  | +10 qq   | 0 qq       | 10 qq        |
| Semilla de Frijol Negro   | +5 qq    | 2 qq       | 7 qq         |
| Azadon Forjado            | +20 und  | 5 und      | 25 und       |
| Pala Cuadrada             | +20 und  | 8 und      | 28 und       |
| Machete 24"               | +30 und  | 15 und     | 45 und       |
+---------------------------+----------+------------+--------------+
```

#### 6. Emision de Recibo Fiscal

Como la fundacion requirio recibo fiscal:

1. El area contable emite el recibo por $1,830.00
2. Se registra en el sistema:
   - Numero de Recibo: RF-2024-0892
   - Fecha: 26/11/2024

### Caso Alternativo: Donacion Individual

Si fuera una donacion individual de un ex-alumno:

```
Tipo de Donante: Individual
Nombre: Ing. Pedro Ramirez (Promocion 1995)
Tipo de Documento: Carta
Numero: N/A
Proposito: Apoyo a nuevas generaciones
```

### Caso Alternativo: Donacion Gubernamental

Si fuera una donacion del Ministerio de Agricultura:

```
Tipo de Donante: Gobierno
Nombre: Ministerio de Agricultura y Ganaderia (MAG)
Tipo de Documento: Acta
Numero: ACTA-MAG-2024-0234
Proposito: Programa de fortalecimiento institucional
```

### Trazabilidad Completa

En cualquier momento, los administradores pueden ver:

- Quien creo la donacion
- Quien la envio para aprobacion
- Quien la aprobo y cuando
- Quien recibio los productos y cuando
- Todos los movimientos de inventario relacionados
- Estado del recibo fiscal (si aplica)

Esta informacion es esencial para:
- Auditorias internas y externas
- Reportes a donantes
- Rendicion de cuentas
- Control de bienes donados

---

*Ultima actualizacion: Noviembre 2024*
