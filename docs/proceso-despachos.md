# Proceso de Despachos

Este documento describe el flujo de trabajo completo para la gestion de despachos (salidas de inventario) en el sistema de bodega.

## Que es un Despacho?

Un **despacho** es la salida de productos desde una bodega hacia un destino externo. A diferencia de un traslado (que mueve productos entre bodegas de la misma empresa), un despacho representa una **salida definitiva** del inventario.

### Diferencia entre Traslado y Despacho

| Aspecto | Traslado | Despacho |
|---------|----------|----------|
| **Proposito** | Mover productos entre bodegas internas | Entregar productos hacia afuera |
| **Destino** | Otra bodega de la empresa | Cliente, beneficiario, uso interno |
| **Retorno** | Los productos permanecen en la empresa | Los productos SALEN definitivamente |
| **Inventario** | Se mueve de bodega A a bodega B | Sale de la bodega y no regresa |
| **Ejemplo** | Fertilizante del Almacen General a Bodega de Cultivos | Fertilizante entregado a estudiantes para practica |

## Tipos de Despacho

El sistema soporta cuatro tipos de despacho:

| Tipo | Descripcion | Uso Tipico |
|------|-------------|------------|
| **Venta** | Entrega de productos por venta comercial | Venta a clientes externos |
| **Interno** | Uso dentro de la institucion | Practicas, mantenimiento, consumo interno |
| **Externo** | Entrega a unidades o personas externas | Entregas a otras instituciones |
| **Donacion** | Salida por donacion a terceros | Apoyo a comunidades, programas sociales |

## Estados de un Despacho

Un despacho puede tener los siguientes estados:

| Estado | Descripcion | Color |
|--------|-------------|-------|
| **Borrador** | Despacho recien creado, puede ser editado | Gris |
| **Pendiente** | Enviado para aprobacion, esperando revision | Amarillo |
| **Aprobado** | Aprobado, listo para despachar | Azul |
| **Despachado** | Productos han salido de bodega | Naranja |
| **Entregado** | Productos entregados al destinatario | Verde |
| **Cancelado** | Despacho cancelado | Rojo |

## Diagrama de Flujo

```
+---------------+
|   BORRADOR    | <-- Crear despacho
+-------+-------+
        |
        | Enviar para Aprobacion
        v
+---------------+
|   PENDIENTE   | <-- Esperando aprobacion
+-------+-------+
        |
        | Aprobar
        v
+---------------+
|   APROBADO    | <-- Listo para despachar
+-------+-------+
        |
        | Despachar (productos salen de bodega)
        v
+---------------+
|  DESPACHADO   | <-- Productos en camino / entregandose
+-------+-------+
        |
        | Confirmar Entrega
        v
+---------------+
|   ENTREGADO   | <-- Proceso completado
+---------------+

* Desde BORRADOR, PENDIENTE o APROBADO se puede CANCELAR
* DESPACHADO y ENTREGADO NO pueden cancelarse (ya afectaron inventario)
* BORRADOR y PENDIENTE pueden ser EDITADOS
```

## Actores del Sistema

### 1. Super Administrador
- **Rol**: Acceso completo al sistema
- **Permisos en Despachos**:
  - Crear despachos en cualquier empresa
  - Ver todos los despachos
  - Editar despachos en borrador/pendiente
  - Aprobar despachos pendientes
  - Despachar productos (generar salida)
  - Confirmar entregas
  - Cancelar despachos (antes de despachar)

### 2. Administrador de Empresa
- **Rol**: Gestiona todas las operaciones de su empresa
- **Permisos en Despachos**:
  - Crear despachos para su empresa
  - Ver despachos de su empresa
  - Editar despachos de su empresa en borrador/pendiente
  - Aprobar despachos pendientes de su empresa
  - Despachar productos de sus bodegas
  - Confirmar entregas de su empresa
  - Cancelar despachos de su empresa

### 3. Gerente de Bodega
- **Rol**: Administra las operaciones de bodegas asignadas
- **Permisos en Despachos**:
  - Crear despachos desde sus bodegas
  - Ver despachos de sus bodegas
  - Editar despachos de sus bodegas en borrador/pendiente
  - Aprobar despachos de sus bodegas
  - Despachar productos de sus bodegas
  - Confirmar entregas de sus bodegas
  - Cancelar despachos de sus bodegas

### 4. Operador de Bodega
- **Rol**: Ejecuta operaciones diarias en la bodega
- **Permisos en Despachos**:
  - Ver despachos de sus bodegas
  - Despachar productos (preparar salida)
  - Confirmar entregas

## Flujo de Trabajo Detallado

### Paso 1: Crear Despacho (Estado: Borrador)

**Quien puede crear**: Super Admin, Admin de Empresa, Gerente de Bodega

1. El usuario accede a "Despachos" > "Nuevo Despacho"
2. Selecciona el tipo de despacho:
   - Venta
   - Interno (uso dentro de la institucion)
   - Externo
   - Donacion
3. Completa la informacion del destinatario:
   - Cliente (si es venta)
   - Unidad destino (si es interno/externo)
   - Nombre del receptor
   - Telefono y email de contacto
   - Direccion de entrega
4. Informacion del documento:
   - Tipo de documento
   - Numero de documento
   - Fecha del documento
5. Si es uso interno:
   - Razon del uso interno (ej: "Practicas de campo 2do ano")
6. Campos adicionales:
   - Justificacion del despacho
   - Codigo de proyecto (opcional)
   - Centro de costo (opcional)
   - Notas
7. Agrega los productos a despachar:
   - Producto
   - Cantidad
   - Precio unitario (para valoracion)
   - Descuento (opcional)
   - Impuesto (opcional)
8. Guarda como borrador

**Resultado**: El despacho queda en estado `borrador` y puede ser editado.

### Paso 2: Enviar para Aprobacion (Borrador -> Pendiente)

**Quien puede enviar**: Creador del despacho, Super Admin, Admin de Empresa, Gerente de Bodega

1. El usuario revisa el despacho en estado borrador
2. Verifica que toda la informacion este correcta
3. Hace clic en "Enviar para Aprobacion"
4. Confirma la accion

**Resultado**: El despacho cambia a estado `pendiente`.

### Paso 3: Aprobar Despacho (Pendiente -> Aprobado)

**Quien puede aprobar**: Super Admin, Admin de Empresa, Gerente de Bodega

1. El aprobador revisa el despacho pendiente
2. Verifica:
   - Tipo de despacho correcto
   - Destinatario valido
   - Productos y cantidades apropiados
   - Justificacion adecuada
   - Disponibilidad de stock
3. Puede agregar notas de aprobacion
4. Hace clic en "Aprobar"
5. Confirma la accion

**Resultado**:
- El despacho cambia a estado `aprobado`
- Se registra quien aprobo y cuando
- El despacho esta listo para ser despachado

### Paso 4: Despachar Productos (Aprobado -> Despachado)

**Quien puede despachar**: Super Admin, Admin de Empresa, Gerente de Bodega, Operador de Bodega

1. El encargado de bodega prepara fisicamente los productos
2. Accede al despacho aprobado
3. Opcionalmente completa informacion de envio:
   - Transportista/Carrier
   - Numero de seguimiento
4. Hace clic en "Despachar"
5. Confirma la accion

**Resultado**:
- El despacho cambia a estado `despachado`
- Se registra quien despacho y cuando
- **Se actualiza automaticamente el inventario**:
  - Se crean movimientos de salida para cada producto
  - Se resta la cantidad del stock de la bodega
  - El tipo de movimiento depende del tipo de despacho:
    - Venta: `DISPATCH_SALE`
    - Interno: `DISPATCH_INTERNAL`
    - Externo: `DISPATCH_EXTERNAL`
    - Donacion: `DISPATCH_DONATION`

### Paso 5: Confirmar Entrega (Despachado -> Entregado)

**Quien puede confirmar**: Super Admin, Admin de Empresa, Gerente de Bodega, Operador de Bodega

1. Cuando los productos han sido entregados al destinatario
2. El usuario registra:
   - Nombre de quien recibio los productos
   - Notas de entrega (opcional)
3. Hace clic en "Confirmar Entrega"
4. Confirma la accion

**Resultado**:
- El despacho cambia a estado `entregado`
- Se registra quien confirmo y cuando
- Se registra el nombre del receptor
- El proceso queda completado

### Cancelar Despacho (Borrador/Pendiente/Aprobado -> Cancelado)

**Quien puede cancelar**: Super Admin, Admin de Empresa, Gerente de Bodega

1. Solo se puede cancelar ANTES de despachar
2. El usuario hace clic en "Cancelar"
3. Confirma la accion

**Resultado**: El despacho cambia a estado `cancelado` y no afecta el inventario.

> **IMPORTANTE**: Los despachos en estado `despachado` o `entregado` NO pueden cancelarse porque ya generaron movimientos de inventario.

## Impacto en el Inventario

### Cuando se afecta el inventario?

El inventario se actualiza unicamente cuando el despacho pasa a estado **despachado**. En ese momento:

1. Se crean movimientos de salida (`movement_type: out`) para cada producto
2. Se registra la cantidad despachada
3. Se actualiza el saldo de la bodega
4. Se vincula el movimiento al despacho para trazabilidad

### Tipos de Movimiento segun Tipo de Despacho

| Tipo Despacho | Codigo Movimiento | Descripcion |
|---------------|-------------------|-------------|
| Venta | `DISPATCH_SALE` | Salida por venta comercial |
| Interno | `DISPATCH_INTERNAL` | Salida por uso interno |
| Externo | `DISPATCH_EXTERNAL` | Salida para entidad externa |
| Donacion | `DISPATCH_DONATION` | Salida por donacion |

### Ejemplo de Movimiento Generado

```
DESPACHO: DIS-20241122-ABC123
Tipo: Interno
Bodega: Bodega Area de Cultivos

MOVIMIENTO DE INVENTARIO:
+---------------------------+----------+------------+--------------+
| Producto                  | Salida   | Saldo Ant. | Nuevo Saldo  |
+---------------------------+----------+------------+--------------+
| Fertilizante NPK 15-15-15 | -2 sacos | 23 sacos   | 21 sacos     |
+---------------------------+----------+------------+--------------+

Referencia: DIS-20241122-ABC123
Razon: Despacho interno - Practicas de campo
```

## Resumen de Permisos por Estado

| Accion | Borrador | Pendiente | Aprobado | Despachado | Entregado | Cancelado |
|--------|----------|-----------|----------|------------|-----------|-----------|
| Ver | Todos* | Todos* | Todos* | Todos* | Todos* | Todos* |
| Editar | Si | Si | - | - | - | - |
| Enviar | Si | - | - | - | - | - |
| Aprobar | - | Si | - | - | - | - |
| Despachar | - | - | Si | - | - | - |
| Confirmar Entrega | - | - | - | Si | - | - |
| Cancelar | Si | Si | Si | - | - | - |

*Segun permisos de empresa/bodega

## Campos Especiales

### Uso Interno

Cuando el despacho es de tipo `interno`, se habilitan campos adicionales:

- **Es uso interno**: Marca booleana que indica uso dentro de la institucion
- **Razon de uso interno**: Descripcion del proposito (ej: "Mantenimiento edificio A", "Practicas agricolas")

### Proyecto y Centro de Costo

Para control presupuestario y reportes:

- **Codigo de proyecto**: Vincula el despacho a un proyecto especifico
- **Centro de costo**: Asocia el despacho a un centro de costo contable

### Documentacion

- **Tipo de documento**: Tipo de documento que respalda el despacho
- **Numero de documento**: Numero de referencia del documento
- **Fecha del documento**: Fecha del documento de respaldo
- **Adjuntos**: Archivos adjuntos (facturas, remisiones, etc.)

---

## Caso Practico 1: Despacho Interno - Practicas de Campo

### Escenario
La Escuela Nacional de Agricultura (ENA) necesita entregar insumos agricolas a los estudiantes de 2do ano para sus practicas de campo en la Parcela #3.

### Actores Involucrados
- **Pedro Sanchez** - Coordinador de Cultivos (Gerente de Bodega)
- **Ana Martinez** - Docente de Agronomia (Solicitante)
- **Carlos Ramos** - Auxiliar de Bodega (Operador)

### Flujo del Proceso

#### 1. Solicitud y Creacion (Pedro Sanchez)

Ana Martinez, docente, solicita insumos para la practica. Pedro crea el despacho:

```
INFORMACION DEL DESPACHO:
Numero: DIS-20241122-PRC001
Tipo: Interno
Bodega Origen: Bodega Area de Cultivos

DESTINATARIO:
Unidad Destino: Practicas Agricolas - 2do Ano
Receptor: Lic. Ana Martinez
Telefono: 7777-8888
Email: amartinez@ena.edu.sv
Direccion: Parcela #3, Campus ENA

DOCUMENTO:
Tipo: Requisicion Interna
Numero: REQ-2024-0892
Fecha: 22/11/2024

USO INTERNO:
Razon: Practicas de campo - Fertilizacion de cultivos
Proyecto: Formacion Agricola 2024
Centro de Costo: CC-DOCENCIA-AGR

PRODUCTOS:
+---------------------------+----------+---------------+------------+
| Producto                  | Cantidad | Valor Unit.   | Total      |
+---------------------------+----------+---------------+------------+
| Fertilizante NPK 15-15-15 | 2 sacos  | $45.00        | $90.00     |
| Semilla Maiz Hibrido H-59 | 1 kg     | $9.00         | $9.00      |
+---------------------------+----------+---------------+------------+
                                        TOTAL:         $99.00
```

**Estado**: `Borrador`

#### 2. Envio y Aprobacion (Pedro Sanchez)

Pedro revisa la solicitud, verifica disponibilidad y aprueba:

1. Verifica stock disponible:
   - Fertilizante NPK: 23 sacos disponibles OK
   - Semilla Maiz: 5 kg disponibles OK
2. Confirma que la solicitud es para uso academico autorizado
3. Envia para aprobacion y aprueba directamente (tiene ambos permisos)
4. Agrega nota: "Aprobado para practica de fertilizacion Parcela #3"

**Estado**: `Aprobado`

#### 3. Preparacion y Despacho (Carlos Ramos)

Carlos, auxiliar de bodega, prepara los productos:

1. Ubica los 2 sacos de fertilizante
2. Pesa 1 kg de semilla de maiz
3. Prepara el paquete para entrega
4. Ingresa al sistema
5. Hace clic en "Despachar"

**Estado**: `Despachado`

**Movimientos generados**:
```
BODEGA: Bodega Area de Cultivos

+---------------------------+----------+------------+--------------+
| Producto                  | Salida   | Saldo Ant. | Nuevo Saldo  |
+---------------------------+----------+------------+--------------+
| Fertilizante NPK 15-15-15 | -2 sacos | 23 sacos   | 21 sacos     |
| Semilla Maiz Hibrido H-59 | -1 kg    | 5 kg       | 4 kg         |
+---------------------------+----------+------------+--------------+

Tipo Movimiento: DISPATCH_INTERNAL
Referencia: DIS-20241122-PRC001
```

#### 4. Confirmacion de Entrega (Carlos Ramos)

Carlos entrega los productos a la docente Ana Martinez:

1. Ana firma el documento de recepcion
2. Carlos registra en el sistema:
   - Recibido por: "Lic. Ana Martinez - Docente Agronomia"
   - Notas: "Entregado completo, productos verificados"
3. Hace clic en "Confirmar Entrega"

**Estado**: `Entregado`

*Proceso completado*

---

## Caso Practico 2: Despacho por Venta

### Escenario
La ENA vende productos procesados (mermeladas y encurtidos) elaborados en la Planta de Procesamiento a un cliente externo.

### Actores Involucrados
- **Maria Lopez** - Encargada de Planta de Procesamiento (Gerente de Bodega)
- **Roberto Diaz** - Vendedor (Creador)
- **Juan Perez** - Bodeguero (Operador)

### Flujo del Proceso

#### 1. Creacion del Despacho (Roberto Diaz)

```
INFORMACION DEL DESPACHO:
Numero: DIS-20241122-VTA001
Tipo: Venta
Bodega Origen: Bodega Planta de Procesamiento

CLIENTE:
Nombre: Supermercado La Economia S.A. de C.V.
Contacto: Gerente de Compras
Telefono: 2222-3333
Email: compras@laeconomia.com.sv
Direccion: Boulevard Los Heroes #123, San Salvador

DOCUMENTO:
Tipo: Factura
Numero: FAC-2024-0456
Fecha: 22/11/2024

PRODUCTOS:
+---------------------------+----------+---------------+------------+
| Producto                  | Cantidad | Precio Unit.  | Total      |
+---------------------------+----------+---------------+------------+
| Mermelada de Fresa 250ml  | 24 und   | $3.50         | $84.00     |
| Encurtido Mixto 500ml     | 12 und   | $4.00         | $48.00     |
| Miel de Abeja 350ml       | 6 und    | $8.00         | $48.00     |
+---------------------------+----------+---------------+------------+
                              Subtotal:                $180.00
                              IVA 13%:                 $23.40
                              TOTAL:                   $203.40
```

**Estado**: `Borrador` -> `Pendiente`

#### 2. Aprobacion (Maria Lopez)

Maria revisa la venta:
- Cliente autorizado
- Precios correctos segun lista
- Stock disponible
- Factura emitida correctamente

**Estado**: `Aprobado`

#### 3. Despacho (Juan Perez)

Juan prepara el pedido:
- Empaca 24 frascos de mermelada
- Empaca 12 frascos de encurtido
- Empaca 6 frascos de miel
- Registra: Transportista "Cargo Express", Tracking "CE-2024-8976"

**Estado**: `Despachado`

#### 4. Entrega Confirmada

El transportista entrega y obtiene firma:
- Recibido por: "Lic. Carmen Flores - Recepcion Bodega"
- Notas: "Entrega completa, productos en buen estado"

**Estado**: `Entregado`

---

## Caso Practico 3: Despacho por Donacion

### Escenario
La ENA dona semillas a una comunidad rural como parte de un programa de apoyo agricola.

### Actores Involucrados
- **Maria Garcia** - Encargada de Almacen General (Gerente de Bodega)
- **Director Academico** - Aprobador
- **Juan Martinez** - Bodeguero (Operador)

### Flujo del Proceso

#### 1. Creacion del Despacho

```
INFORMACION DEL DESPACHO:
Numero: DIS-20241122-DON001
Tipo: Donacion
Bodega Origen: Almacen General ENA

DESTINATARIO:
Nombre: Comunidad El Progreso, Chalatenango
Contacto: Jose Hernandez (Presidente ADESCO)
Telefono: 7890-1234
Direccion: Canton El Progreso, Chalatenango

DOCUMENTO:
Tipo: Acta de Donacion
Numero: ACTA-DON-2024-015
Fecha: 22/11/2024

JUSTIFICACION:
Programa de Apoyo a Comunidades Rurales 2024
Beneficiarios: 25 familias agricultoras

PRODUCTOS:
+---------------------------+----------+---------------+------------+
| Producto                  | Cantidad | Valor Est.    | Total      |
+---------------------------+----------+---------------+------------+
| Semilla Maiz Hibrido H-59 | 25 kg    | $9.00         | $225.00    |
| Semilla Frijol Rojo       | 15 kg    | $6.00         | $90.00     |
+---------------------------+----------+---------------+------------+
                              VALOR TOTAL DONACION:    $315.00
```

#### 2. Aprobacion

El Director Academico revisa y aprueba:
- Beneficiarios identificados
- Programa autorizado
- Documentacion completa

**Estado**: `Aprobado`

#### 3. Despacho y Entrega

Los productos se entregan en evento comunitario:
- Recibido por: "Jose Hernandez - Presidente ADESCO El Progreso"
- Notas: "Entrega realizada en asamblea comunitaria con presencia de 25 familias beneficiarias"

**Estado**: `Entregado`

---

## Reportes y Trazabilidad

### Informacion Disponible

Para cada despacho, el sistema registra:

- Quien creo el despacho y cuando
- Quien lo envio para aprobacion
- Quien lo aprobo y cuando
- Quien despacho los productos y cuando
- Quien confirmo la entrega y cuando
- Nombre de la persona que recibio
- Todos los movimientos de inventario generados

### Reportes Utiles

1. **Despachos por Tipo**: Cuantos despachos de venta, internos, externos, donaciones
2. **Despachos por Bodega**: Salidas por cada bodega
3. **Despachos por Proyecto**: Consumos asociados a proyectos especificos
4. **Despachos por Centro de Costo**: Para control presupuestario
5. **Valoracion de Salidas**: Valor total de productos despachados

---

## Preguntas Frecuentes

### Puedo cancelar un despacho que ya fue despachado?
No. Una vez que el despacho cambia a estado "Despachado", ya se generaron los movimientos de inventario (salidas). Para corregir errores, seria necesario crear un movimiento de ajuste o devolucion.

### Cual es la diferencia entre "Despachado" y "Entregado"?
- **Despachado**: Los productos salieron de la bodega (ya no estan en inventario)
- **Entregado**: Los productos fueron recibidos por el destinatario final

Esta distincion permite dar seguimiento a productos en transito.

### Cuando uso despacho tipo "Interno" vs crear un traslado?
- **Despacho Interno**: Cuando los productos se consumen o usan y NO regresan (ej: insumos para practicas)
- **Traslado**: Cuando los productos van a otra bodega y siguen siendo parte del inventario

### Puedo editar un despacho aprobado?
No. Una vez aprobado, el despacho no puede editarse. Si hay errores, debe cancelarse y crear uno nuevo.

### Como se valoran los productos en un despacho interno?
Se usa el precio unitario registrado en el detalle del despacho. Esto permite valorar el consumo interno para efectos contables y de control.

---

## Despacho Rapido (Quick Dispatch)

El sistema incluye una funcionalidad de **Despacho Rapido** que permite crear y procesar un despacho de un solo producto de manera inmediata, **sin pasar por el flujo de aprobacion**.

### Que es el Despacho Rapido?

Es una forma simplificada de crear despachos que:
- Procesa un solo producto a la vez
- No requiere flujo de aprobacion
- Actualiza el inventario inmediatamente
- Ideal para operaciones urgentes o de bajo volumen

### Cuando usar Despacho Rapido?

| Usar Despacho Rapido | Usar Despacho Normal |
|----------------------|----------------------|
| Salidas urgentes de un producto | Despachos con multiples productos |
| Cantidades pequenas | Requiere revision/aprobacion |
| Operaciones recurrentes simples | Documentacion formal requerida |
| Usuario con permisos adecuados | Control de flujo de trabajo |

### Como funciona?

1. **Acceso**: Desde el listado de Despachos, hacer clic en el boton "Despacho Rapido"
2. **Seleccionar datos basicos**:
   - Empresa (solo Super Admin)
   - Bodega de origen
   - Tipo de despacho (Venta, Interno, Externo, Donacion)
   - Cliente (opcional)
   - Nombre del receptor (opcional)
3. **Seleccionar producto**:
   - El sistema muestra el stock disponible
   - Ingresar la cantidad a despachar
   - Seleccionar la unidad de medida
4. **Confirmar**: Al hacer clic en "Crear y Procesar Despacho":
   - Se crea el despacho directamente en estado `despachado`
   - Se genera el movimiento de inventario
   - Se actualiza el stock de la bodega

### Validaciones del Despacho Rapido

- **Stock disponible**: No se puede despachar mas de lo que hay en existencia
- **Producto activo**: Solo productos activos aparecen en la lista
- **Bodega activa**: Solo bodegas activas de la empresa
- **Unidad de medida**: Se autocompleta desde el producto pero puede modificarse

### Diagrama de Flujo Simplificado

```
+-------------------+
|  DESPACHO RAPIDO  |
+--------+----------+
         |
         | Crear y Procesar
         v
+-------------------+
|    DESPACHADO     | <-- Estado final directo
+-------------------+
         |
         v
  Inventario Actualizado
  Movimiento Generado
```

### Movimientos Generados

El tipo de movimiento generado depende del tipo de despacho:

| Tipo Despacho | Tipo Movimiento | Codigo |
|---------------|-----------------|--------|
| Venta | `sale` | DISPATCH_SALE |
| Interno | `transfer_out` | DISPATCH_INTERNAL |
| Externo | `transfer_out` | DISPATCH_EXTERNAL |
| Donacion | `sale` | DISPATCH_DONATION |

### Ejemplo de Uso

```
DESPACHO RAPIDO:

Bodega: Almacen General
Tipo: Interno
Producto: Fertilizante NPK 15-15-15
Stock Disponible: 50.00 sacos
Cantidad a Despachar: 5.00 sacos

[Crear y Procesar Despacho]

RESULTADO:
- Despacho DIS-20241122-QD001 creado
- Estado: Despachado
- Nuevo stock: 45.00 sacos
- Movimiento de salida registrado
```

### Restricciones

- Solo permite un producto por despacho
- No permite adjuntar documentos
- No tiene flujo de aprobacion
- No permite edicion posterior (ya esta procesado)
- Solo usuarios con permisos de despacho pueden usarlo

---

*Ultima actualizacion: Noviembre 2024*
