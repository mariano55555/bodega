# Proceso de Ajustes de Inventario

Este documento describe el flujo de trabajo completo para la gestion de ajustes de inventario en el sistema. Los ajustes permiten corregir diferencias entre el inventario fisico y el registrado en el sistema.

## Que es un Ajuste de Inventario

Un ajuste de inventario es un registro que documenta y procesa cambios en las cantidades de productos almacenados debido a:

- **Sobrantes**: Producto encontrado que no estaba registrado
- **Faltantes**: Producto registrado que no existe fisicamente
- **Danos**: Producto que ya no puede utilizarse
- **Vencimientos**: Producto con fecha de caducidad expirada
- **Perdidas/Robos**: Producto extraviado o sustraido
- **Correcciones de Conteo**: Errores detectados durante inventarios fisicos
- **Devoluciones**: Producto que regresa al inventario

## Estados de un Ajuste

Un ajuste de inventario puede tener los siguientes estados:

| Estado | Descripcion | Color |
|--------|-------------|-------|
| **Borrador** | Ajuste recien creado, puede editarse | Gris |
| **Pendiente** | Enviado para aprobacion, en espera de revision | Amarillo |
| **Aprobado** | Ajuste revisado y aprobado, listo para procesar | Azul |
| **Procesado** | Ajuste aplicado al inventario, movimiento creado | Verde |
| **Rechazado** | Ajuste no aprobado, requiere correccion | Rojo |
| **Cancelado** | Ajuste cancelado, no se procesara | Gris oscuro |

## Diagrama de Flujo

```
+---------------+
|   BORRADOR    | <-- Crear ajuste
+-------+-------+
        |
        | Enviar para Aprobacion
        v
+---------------+
|   PENDIENTE   | <-- En espera de revision
+-------+-------+
        |
   +----+----+
   |         |
   v         v
+-------+ +----------+
|APROBADO| |RECHAZADO|
+---+---+ +----+-----+
    |          |
    |          | Editar y reenviar
    |          v
    |     [Vuelve a BORRADOR]
    |
    | Procesar
    v
+---------------+
|   PROCESADO   | <-- Inventario actualizado
+---------------+

* BORRADOR puede cancelarse directamente
* RECHAZADO puede editarse y reenviarse o cancelarse
* Solo PROCESADO no puede modificarse ni cancelarse
```

## Tipos de Ajuste

El sistema soporta los siguientes tipos de ajuste:

| Tipo | Descripcion | Efecto en Inventario | Color |
|------|-------------|---------------------|-------|
| **Ajuste Positivo** | Sobrante encontrado en conteo | +Cantidad | Verde |
| **Ajuste Negativo** | Faltante detectado en conteo | -Cantidad | Rojo |
| **Producto Danado** | Producto inutilizable por dano fisico | -Cantidad | Rojo |
| **Producto Vencido** | Producto con fecha de caducidad expirada | -Cantidad | Rojo |
| **Perdida/Robo** | Producto extraviado o sustraido | -Cantidad | Rojo |
| **Correccion de Conteo** | Error en registros previos | +/- Cantidad | Azul |
| **Devolucion** | Producto que regresa al inventario | +Cantidad | Verde |
| **Otro** | Cualquier otra situacion | +/- Cantidad | Gris |

### Ajustes Positivos vs Negativos

La cantidad del ajuste determina si es positivo o negativo:

- **Cantidad positiva** (>= 0): Incrementa el inventario
- **Cantidad negativa** (< 0): Reduce el inventario

> **Nota importante**: En el formulario, siempre ingrese la cantidad como valor positivo. El sistema la convertira a negativa automaticamente segun el tipo de ajuste seleccionado.

## Actores del Sistema

### 1. Super Administrador
- **Rol**: Acceso completo al sistema
- **Permisos en Ajustes**:
  - Crear ajustes en cualquier empresa
  - Ver todos los ajustes
  - Editar ajustes en borrador o rechazados
  - Aprobar/Rechazar ajustes pendientes
  - Procesar ajustes aprobados
  - Cancelar ajustes no procesados

### 2. Administrador de Empresa
- **Rol**: Gestiona todas las operaciones de su empresa
- **Permisos en Ajustes**:
  - Crear ajustes en bodegas de su empresa
  - Ver ajustes de su empresa
  - Editar ajustes propios en borrador o rechazados
  - Aprobar/Rechazar ajustes pendientes de su empresa
  - Procesar ajustes aprobados de su empresa
  - Cancelar ajustes de su empresa

### 3. Gerente de Bodega
- **Rol**: Administra las operaciones de bodegas asignadas
- **Permisos en Ajustes**:
  - Crear ajustes en sus bodegas
  - Ver ajustes de sus bodegas
  - Editar ajustes propios en borrador o rechazados
  - Aprobar ajustes de sus bodegas
  - Procesar ajustes aprobados de sus bodegas

### 4. Operador de Bodega
- **Rol**: Ejecuta operaciones diarias en la bodega
- **Permisos en Ajustes**:
  - Crear ajustes en sus bodegas
  - Ver ajustes de sus bodegas
  - Enviar ajustes para aprobacion

## Flujo de Trabajo Completo

### Paso 1: Crear Ajuste (Estado: Borrador)

**Quien puede crear**: Todos los usuarios con acceso a la bodega

1. El usuario accede a "Ajustes de Inventario" > "Nuevo Ajuste"
2. Completa la informacion basica:
   - **Bodega**: Donde se encuentra el producto
   - **Producto**: El articulo a ajustar
   - **Tipo de Ajuste**: Selecciona de la lista (positivo, negativo, dano, etc.)
   - **Cantidad**: Unidades a ajustar (siempre positivo)
   - **Costo Unitario**: Se autocompleta del ultimo movimiento
3. Proporciona justificacion:
   - **Motivo**: Breve descripcion del ajuste (obligatorio)
   - **Justificacion Detallada**: Explicacion completa
   - **Acciones Correctivas**: Medidas preventivas propuestas
4. Opcionalmente agrega:
   - Documento de referencia (acta, informe)
   - Numero de lote
   - Fecha de vencimiento (para productos vencidos)
   - Centro de costo / Proyecto / Departamento
   - Notas adicionales
5. Guarda el ajuste

**Resultado**: El ajuste queda en estado `borrador` y puede ser editado.

**Validaciones**:
- La bodega debe estar activa
- El producto debe existir en el catalogo
- La cantidad debe ser mayor a cero
- El motivo es obligatorio

### Paso 2: Enviar para Aprobacion (Borrador -> Pendiente)

**Quien puede enviar**: El creador del ajuste

1. El usuario revisa su ajuste en borrador
2. Verifica que toda la informacion sea correcta
3. Hace clic en "Enviar para Aprobacion"
4. Confirma la accion

**Resultado**:
- El ajuste cambia a estado `pendiente`
- Se registra quien envio y cuando
- Ya no puede editarse hasta ser aprobado o rechazado
- Los aprobadores son notificados

### Paso 3: Aprobar o Rechazar (Pendiente -> Aprobado/Rechazado)

**Quien puede aprobar**: Super Admin, Admin de Empresa, Gerente de Bodega

#### Aprobar el Ajuste

1. El aprobador revisa el ajuste pendiente
2. Verifica:
   - Justificacion adecuada para el tipo de ajuste
   - Cantidad razonable
   - Documentacion de soporte (si aplica)
3. Puede agregar notas de aprobacion
4. Hace clic en "Aprobar Ajuste"
5. Confirma la accion

**Resultado**:
- El ajuste cambia a estado `aprobado`
- Se registra quien aprobo y cuando
- El ajuste esta listo para ser procesado

#### Rechazar el Ajuste

1. El aprobador determina que el ajuste no es valido
2. Escribe el motivo del rechazo (obligatorio)
3. Hace clic en "Rechazar Ajuste"
4. Confirma la accion

**Resultado**:
- El ajuste cambia a estado `rechazado`
- Se registra quien rechazo, cuando y por que
- El creador es notificado
- El ajuste puede editarse y reenviarse

### Paso 4: Procesar Ajuste (Aprobado -> Procesado)

**Quien puede procesar**: Super Admin, Admin de Empresa, Gerente de Bodega

1. El usuario accede al ajuste aprobado
2. Verifica una ultima vez la informacion
3. Hace clic en "Procesar Ajuste"
4. Confirma la accion

**Resultado**:
- El ajuste cambia a estado `procesado`
- Se crea un movimiento de inventario tipo `adjustment`
- **Se actualiza el stock del producto**:
  - Si es positivo: Aumenta el balance
  - Si es negativo: Reduce el balance
- Se vincula el movimiento al ajuste
- El ajuste no puede modificarse ni revertirse

### Cancelar Ajuste

**Quien puede cancelar**: Super Admin, Admin de Empresa, Gerente de Bodega

Solo disponible para ajustes en estado `borrador` o `rechazado`:

1. El usuario accede al ajuste
2. Hace clic en "Cancelar Ajuste"
3. Confirma la accion

**Resultado**: El ajuste cambia a estado `cancelado` y no afecta el inventario.

> **Nota**: Los ajustes procesados NO pueden cancelarse ni revertirse.

## Impacto en el Inventario

### Al Procesar un Ajuste

Se crea un movimiento de inventario con los siguientes datos:

```
Tipo: adjustment
Motivo: ADJ_POS (positivo) o ADJ_NEG (negativo)
Referencia: Numero del ajuste (ADJ-YYYYMMDD-XXXXXX)
```

### Calculo del Nuevo Saldo

```
Nuevo Saldo = Saldo Anterior + Cantidad del Ajuste

Ejemplos:
- Saldo: 100, Ajuste: +10 -> Nuevo Saldo: 110
- Saldo: 100, Ajuste: -15 -> Nuevo Saldo: 85
```

### Ejemplo de Movimiento Generado

```
AJUSTE: ADJ-20241122-ABC123
Tipo: Ajuste Negativo (Faltante)
Producto: Fertilizante NPK 15-15-15
Cantidad: -5 sacos
Costo Unitario: $25.00
Valor Total: $125.00

MOVIMIENTO GENERADO:
+------------------+----------+------------+--------------+
| Tipo             | Cantidad | Saldo Ant. | Nuevo Saldo  |
+------------------+----------+------------+--------------+
| adjustment (out) | 5 sacos  | 45 sacos   | 40 sacos     |
+------------------+----------+------------+--------------+

Razon del Movimiento: ADJ_NEG (Ajuste Negativo)
Documento: ADJ-20241122-ABC123
```

## Resumen de Permisos por Estado

| Accion | Borrador | Pendiente | Aprobado | Procesado | Rechazado | Cancelado |
|--------|----------|-----------|----------|-----------|-----------|-----------|
| Ver | Todos* | Todos* | Todos* | Todos* | Todos* | Todos* |
| Editar | Creador | - | - | - | Creador | - |
| Enviar | Creador | - | - | - | - | - |
| Aprobar | - | Admins, Gerente | - | - | - | - |
| Rechazar | - | Admins, Gerente | - | - | - | - |
| Procesar | - | - | Admins, Gerente | - | - | - |
| Cancelar | Todos* | - | - | - | Todos* | - |
| Eliminar | Creador, Admins | - | - | - | Creador, Admins | - |

*Segun permisos de empresa/bodega

## Informacion Requerida por Tipo de Ajuste

### Ajuste Positivo (Sobrante)
- **Motivo**: Por que hay mas producto del registrado
- **Justificacion**: Como se descubrio el sobrante
- **Recomendado**: Documento de conteo fisico

### Ajuste Negativo (Faltante)
- **Motivo**: Por que hay menos producto del registrado
- **Justificacion**: Investigacion realizada
- **Acciones Correctivas**: Medidas para evitar futuros faltantes
- **Recomendado**: Acta de inventario

### Producto Danado
- **Motivo**: Tipo de dano (humedad, golpe, plagas, etc.)
- **Justificacion**: Circunstancias del dano
- **Acciones Correctivas**: Mejoras en almacenamiento
- **Recomendado**: Fotos del dano, acta de baja

### Producto Vencido
- **Motivo**: Producto con fecha de caducidad expirada
- **Justificacion**: Por que no se uso a tiempo
- **Fecha de Vencimiento**: Obligatoria
- **Numero de Lote**: Recomendado
- **Acciones Correctivas**: Mejoras en rotacion FIFO
- **Recomendado**: Acta de baja, plan de disposicion

### Perdida/Robo
- **Motivo**: Circunstancias de la perdida
- **Justificacion**: Investigacion detallada
- **Acciones Correctivas**: Medidas de seguridad
- **Recomendado**: Denuncia policial (si aplica), informe de seguridad

### Correccion de Conteo
- **Motivo**: Error detectado en registros
- **Justificacion**: Como se identifico el error
- **Documento de Referencia**: Conteo fisico anterior

---

## Caso Practico: Ajuste por Producto Danado

### Escenario
Durante la revision mensual de inventario en el Almacen General de la ENA, se detectaron 3 sacos de Fertilizante NPK 15-15-15 con dano por humedad y no pueden utilizarse.

### Actores Involucrados
- **Maria Garcia** - Encargada de Almacen (Gerente de Bodega)
- **Juan Martinez** - Bodeguero (Operador de Bodega)
- **Roberto Fernandez** - Administrador de la ENA

### Flujo del Proceso

#### 1. Creacion del Ajuste (Juan Martinez)

Juan detecta el problema durante su revision:

```
INFORMACION DEL AJUSTE:
Numero: ADJ-20241122-XYZ789
Bodega: Almacen General ENA
Producto: Fertilizante NPK 15-15-15
Tipo: Producto Danado
Cantidad: 3 sacos
Costo Unitario: $25.00
Valor Total: $75.00

JUSTIFICACION:
Motivo: Sacos danados por humedad
Justificacion: Durante revision mensual se detectaron 3 sacos
              con evidente dano por filtracion de agua en el
              area de almacenamiento cerca de ventana.
Acciones Correctivas: Reubicar productos a zona central del
                      almacen. Reparar sellado de ventana.
Documento Referencia: Acta de inspeccion
Numero Referencia: ACTA-2024-0089
```

**Estado**: `Borrador`

#### 2. Envio para Aprobacion (Juan Martinez)

Juan revisa el ajuste y lo envia para aprobacion:

**Estado**: `Pendiente`

*Se registra: Enviado por Juan Martinez - 22/11/2024 9:30 AM*

#### 3. Aprobacion del Ajuste (Maria Garcia)

Maria revisa el ajuste pendiente:

1. Verifica el acta de inspeccion ACTA-2024-0089
2. Confirma visualmente el dano de los sacos
3. Valida las acciones correctivas propuestas
4. Agrega nota: "Verificado fisicamente. Coordinar reparacion de ventana."
5. Hace clic en **"Aprobar Ajuste"**

**Estado**: `Aprobado`

*Se registra: Aprobado por Maria Garcia - 22/11/2024 10:15 AM*

#### 4. Procesamiento del Ajuste (Maria Garcia)

Maria procede a aplicar el ajuste al inventario:

1. Revisa una vez mas los datos
2. Hace clic en **"Procesar Ajuste"**
3. Confirma la accion

**Estado**: `Procesado`

*Se registra: Procesado por Maria Garcia - 22/11/2024 10:20 AM*

**Movimiento generado**:
```
+---------------------------+----------+------------+--------------+
| Producto                  | Cantidad | Saldo Ant. | Nuevo Saldo  |
+---------------------------+----------+------------+--------------+
| Fertilizante NPK 15-15-15 | -3 sacos | 43 sacos   | 40 sacos     |
+---------------------------+----------+------------+--------------+

Tipo de Movimiento: adjustment
Razon: ADJ_NEG (Ajuste Negativo)
Documento: ADJ-20241122-XYZ789
Notas: Sacos danados por humedad
```

### Caso Alternativo: Ajuste Rechazado

Si Maria considerara que la justificacion es insuficiente:

1. Escribe el motivo de rechazo:
   *"Falta documentacion fotografica del dano. Por favor adjuntar fotos de los sacos afectados."*
2. Hace clic en **"Rechazar Ajuste"**

**Estado**: `Rechazado`

Juan podria entonces:
1. Editar el ajuste
2. Agregar las fotos solicitadas
3. Reenviar para aprobacion

---

## Caso Practico: Ajuste Positivo por Sobrante

### Escenario
Durante el inventario fisico trimestral, se encontraron 5 unidades de Herramienta Pala de Mano que no estaban registradas en el sistema.

### Flujo del Proceso

```
INFORMACION DEL AJUSTE:
Numero: ADJ-20241122-SOB456
Bodega: Almacen General ENA
Producto: Herramienta Pala de Mano
Tipo: Ajuste Positivo (Sobrante)
Cantidad: 5 unidades
Costo Unitario: $8.50
Valor Total: $42.50

JUSTIFICACION:
Motivo: Sobrante detectado en inventario fisico
Justificacion: Al realizar conteo fisico trimestral se
              encontraron 5 unidades adicionales. Posible
              error de registro en recepcion de compra
              anterior.
Documento Referencia: Conteo fisico
Numero Referencia: INV-2024-Q3-001
```

**Resultado al Procesar**:
```
+---------------------------+----------+------------+--------------+
| Producto                  | Cantidad | Saldo Ant. | Nuevo Saldo  |
+---------------------------+----------+------------+--------------+
| Herramienta Pala de Mano  | +5 uds   | 12 uds     | 17 uds       |
+---------------------------+----------+------------+--------------+

Tipo de Movimiento: adjustment
Razon: ADJ_POS (Ajuste Positivo)
```

---

## Reportes y Trazabilidad

### Informacion Disponible en Cada Ajuste

- Numero unico de ajuste
- Quien creo el ajuste y cuando
- Quien envio para aprobacion y cuando
- Quien aprobo/rechazo y cuando (con notas)
- Quien proceso y cuando
- Movimiento de inventario generado
- Toda la documentacion de soporte

### Consultas Utiles

El sistema permite filtrar ajustes por:
- Estado (borrador, pendiente, aprobado, procesado, rechazado, cancelado)
- Tipo de ajuste (positivo, negativo, dano, vencimiento, etc.)
- Bodega
- Producto
- Rango de fechas
- Creador

---

## Preguntas Frecuentes

### ¿Se puede revertir un ajuste procesado?
No. Una vez procesado, el movimiento de inventario queda registrado permanentemente. Si se cometio un error, debe crearse un nuevo ajuste en sentido contrario.

### ¿Quien puede aprobar mis ajustes?
Los ajustes pueden ser aprobados por usuarios con rol de Gerente de Bodega (de la misma bodega), Administrador de Empresa, o Super Administrador.

### ¿Que pasa si un ajuste negativo deja el stock en negativo?
El sistema permite saldos negativos para reflejar situaciones excepcionales. Sin embargo, esto debe corregirse mediante compras, traslados o ajustes positivos.

### ¿Puedo editar un ajuste que ya envie para aprobacion?
No. Mientras el ajuste este en estado `pendiente`, no puede editarse. Si necesita cambios, debe esperar a que sea rechazado.

### ¿Cual es la diferencia entre "Ajuste" y "Movimiento"?
- **Ajuste**: Documento que registra y justifica un cambio en el inventario, con workflow de aprobacion.
- **Movimiento**: Registro en el kardex del cambio efectivo de stock. Se genera automaticamente al procesar un ajuste.

### ¿Se pueden adjuntar archivos a un ajuste?
El sistema permite registrar referencias a documentos (actas, informes, fotos) en los campos de referencia. Los archivos fisicos deben almacenarse segun los procedimientos de la organizacion.

### ¿Por que mi ajuste fue rechazado?
El motivo del rechazo se muestra en el detalle del ajuste. Generalmente se debe a:
- Justificacion insuficiente
- Falta de documentacion de soporte
- Cantidad no verificada
- Tipo de ajuste incorrecto

---

*Ultima actualizacion: Noviembre 2024*
