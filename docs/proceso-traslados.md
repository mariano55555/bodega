# Proceso de Traslados

Este documento describe el flujo de trabajo completo para la gestion de traslados de inventario entre bodegas en el sistema.

## Estados de un Traslado

Un traslado puede tener los siguientes estados:

| Estado | Descripcion | Color |
|--------|-------------|-------|
| **Pendiente** | Traslado recien creado, esperando aprobacion | Amarillo |
| **Aprobado** | Traslado aprobado, listo para enviar | Azul |
| **En Transito** | Productos enviados, esperando recepcion | Naranja |
| **Recibido** | Productos recibidos, inventario actualizado | Verde |
| **Cancelado** | Traslado cancelado, no se proceso | Rojo |

## Diagrama de Flujo

```
+---------------+
|   PENDIENTE   | <-- Crear traslado
+-------+-------+
        |
        | Aprobar
        v
+---------------+
|   APROBADO    | <-- Listo para enviar
+-------+-------+
        |
        | Enviar (Ship)
        v
+---------------+
|  EN TRANSITO  | <-- Productos en camino
+-------+-------+
        |
        | Recibir
        v
+---------------+
|   RECIBIDO    | <-- Inventario actualizado en ambas bodegas
+---------------+

* Desde PENDIENTE o APROBADO se puede CANCELAR
* EN TRANSITO y RECIBIDO NO pueden cancelarse
* Solo PENDIENTE puede ser EDITADO
```

## Tipos de Traslado

El sistema soporta dos modalidades de traslado:

### 1. Traslado Completo (Workflow)
- Requiere aprobacion antes del envio
- Permite seguimiento de estado paso a paso
- Ideal para traslados entre bodegas distantes
- Incluye datos de transportista y numero de seguimiento
- Permite registrar discrepancias al recibir

### 2. Traslado Rapido (Quick Transfer)
- Proceso simplificado en un solo paso
- No requiere aprobacion
- Ideal para traslados inmediatos entre bodegas cercanas
- El inventario se actualiza instantaneamente
- No incluye seguimiento de envio

## Actores del Sistema

### 1. Super Administrador
- **Rol**: Acceso completo al sistema
- **Permisos en Traslados**:
  - Crear traslados en cualquier empresa
  - Ver todos los traslados
  - Editar traslados pendientes
  - Aprobar traslados pendientes
  - Enviar traslados aprobados
  - Recibir traslados en transito
  - Cancelar traslados (pendiente/aprobado)
  - Ejecutar traslados rapidos

### 2. Administrador de Empresa
- **Rol**: Gestiona todas las operaciones de su empresa
- **Permisos en Traslados**:
  - Crear traslados entre bodegas de su empresa
  - Ver traslados de su empresa
  - Editar traslados pendientes de su empresa
  - Aprobar traslados pendientes de su empresa
  - Enviar traslados aprobados de su empresa
  - Recibir traslados en transito de su empresa
  - Cancelar traslados de su empresa
  - Ejecutar traslados rapidos

### 3. Gerente de Bodega
- **Rol**: Administra las operaciones de bodegas asignadas
- **Permisos en Traslados**:
  - Crear traslados desde sus bodegas
  - Ver traslados de sus bodegas (origen o destino)
  - Editar traslados pendientes de sus bodegas
  - Aprobar traslados donde su bodega es origen
  - Enviar traslados desde sus bodegas
  - Recibir traslados hacia sus bodegas
  - Cancelar traslados de sus bodegas
  - Ejecutar traslados rapidos desde sus bodegas

### 4. Operador de Bodega
- **Rol**: Ejecuta operaciones diarias en la bodega
- **Permisos en Traslados**:
  - Ver traslados de sus bodegas
  - Enviar traslados aprobados desde sus bodegas
  - Recibir traslados hacia sus bodegas

## Flujo de Trabajo Completo

### Paso 1: Crear Traslado (Estado: Pendiente)

**Quien puede crear**: Super Admin, Admin de Empresa, Gerente de Bodega

1. El usuario accede a "Traslados" > "Nuevo Traslado"
2. Completa la informacion basica:
   - Bodega de origen (desde donde salen los productos)
   - Bodega de destino (hacia donde van los productos)
   - Motivo del traslado
   - Notas adicionales (opcional)
3. Agrega los productos a trasladar:
   - Selecciona el producto
   - Indica la cantidad a trasladar
   - Verifica disponibilidad en bodega origen
4. Guarda el traslado

**Resultado**: El traslado queda en estado `pendiente` y puede ser editado.

**Validaciones**:
- La bodega origen y destino deben ser diferentes
- Debe haber al menos un producto en el detalle
- La cantidad no puede exceder el stock disponible

### Paso 2: Aprobar Traslado (Pendiente -> Aprobado)

**Quien puede aprobar**: Super Admin, Admin de Empresa, Gerente de Bodega (de la bodega origen)

1. El aprobador revisa el traslado pendiente
2. Verifica:
   - Productos y cantidades correctos
   - Disponibilidad de stock
   - Justificacion del traslado
3. Puede agregar notas de aprobacion
4. Hace clic en "Aprobar Traslado"
5. Confirma la accion

**Resultado**:
- El traslado cambia a estado `aprobado`
- Se registra quien aprobo y cuando
- Se notifica al solicitante
- El traslado esta listo para ser enviado

### Paso 3: Enviar Traslado (Aprobado -> En Transito)

**Quien puede enviar**: Super Admin, Admin de Empresa, Gerente de Bodega, Operador de Bodega (de la bodega origen)

1. El encargado prepara fisicamente los productos
2. Accede al traslado aprobado
3. Completa la informacion de envio:
   - Numero de seguimiento (opcional)
   - Transportista/Carrier (opcional)
4. Hace clic en "Enviar Traslado"
5. Confirma la accion

**Resultado**:
- El traslado cambia a estado `en_transito`
- Se registra quien envio y cuando
- **Se actualiza el inventario de la bodega origen**:
  - Se crean movimientos de salida (`transfer_out`)
  - Se resta la cantidad del stock de origen
- Se notifica al solicitante y a la bodega destino

### Paso 4: Recibir Traslado (En Transito -> Recibido)

**Quien puede recibir**: Super Admin, Admin de Empresa, Gerente de Bodega, Operador de Bodega (de la bodega destino)

1. Cuando llegan los productos a la bodega destino
2. El receptor verifica fisicamente los productos
3. Si hay diferencias, puede registrar discrepancias:
   - Producto
   - Cantidad esperada vs. cantidad recibida
   - Motivo de la diferencia
4. Puede agregar notas de recepcion
5. Hace clic en "Recibir Traslado"
6. Confirma la accion

**Resultado**:
- El traslado cambia a estado `recibido`
- Se registra quien recibio y cuando
- **Se actualiza el inventario de la bodega destino**:
  - Se crean movimientos de entrada (`transfer_in`)
  - Se suma la cantidad al stock de destino
- Se notifica al solicitante y al aprobador
- Se registran las discrepancias si las hubo

### Cancelar Traslado (Pendiente/Aprobado -> Cancelado)

**Quien puede cancelar**: Super Admin, Admin de Empresa, Gerente de Bodega

1. Solo se puede cancelar antes de enviar
2. El usuario hace clic en "Cancelar Traslado"
3. Confirma la accion

**Resultado**: El traslado cambia a estado `cancelado` y no afecta el inventario.

> **Nota**: Los traslados en transito o recibidos NO pueden cancelarse porque ya afectaron el inventario.

## Traslado Rapido (Quick Transfer)

El traslado rapido es una alternativa simplificada para movimientos inmediatos entre bodegas.

### Caracteristicas

| Aspecto | Traslado Completo | Traslado Rapido |
|---------|-------------------|-----------------|
| Estados | 5 (pendiente -> recibido) | 1 (completado inmediatamente) |
| Aprobacion | Requerida | No requerida |
| Seguimiento | Numero de tracking, carrier | No disponible |
| Discrepancias | Se pueden registrar | No aplica |
| Movimientos | Separados (envio y recepcion) | Simultaneos |
| Uso ideal | Bodegas distantes, auditorias | Bodegas cercanas, urgencias |

### Proceso de Traslado Rapido

1. El usuario accede a "Traslados" > "Traslado Rapido"
2. Selecciona:
   - Bodega de origen
   - Bodega de destino
   - Producto a trasladar
   - Cantidad
   - Notas (opcional)
3. Hace clic en "Ejecutar Traslado"
4. Confirma la accion

**Resultado inmediato**:
- Se crea movimiento de salida en bodega origen (`transfer_out`)
- Se crea movimiento de entrada en bodega destino (`transfer_in`)
- El stock se actualiza en ambas bodegas instantaneamente

## Impacto en el Inventario

### Al Enviar (Ship)

Se crea un movimiento de salida en la bodega origen:

```
Tipo: transfer_out (Transferencia Salida)
Cantidad: -[cantidad_enviada]
Referencia: Numero de traslado
```

El stock de la bodega origen se reduce.

### Al Recibir (Receive)

Se crea un movimiento de entrada en la bodega destino:

```
Tipo: transfer_in (Transferencia Entrada)
Cantidad: +[cantidad_recibida]
Referencia: Numero de traslado
```

El stock de la bodega destino aumenta.

### Ejemplo de Movimientos

```
TRASLADO: TRF-20241122-ABC123
Producto: Fertilizante NPK 15-15-15
Cantidad: 10 sacos

BODEGA ORIGEN (Almacen General):
+------------------+----------+------------+--------------+
| Movimiento       | Cantidad | Saldo Ant. | Nuevo Saldo  |
+------------------+----------+------------+--------------+
| Envio Traslado   | -10      | 40         | 30           |
+------------------+----------+------------+--------------+

BODEGA DESTINO (Bodega Cultivos):
+------------------+----------+------------+--------------+
| Movimiento       | Cantidad | Saldo Ant. | Nuevo Saldo  |
+------------------+----------+------------+--------------+
| Recepcion Trasl. | +10      | 8          | 18           |
+------------------+----------+------------+--------------+
```

## Resumen de Permisos por Estado

| Accion | Pendiente | Aprobado | En Transito | Recibido | Cancelado |
|--------|-----------|----------|-------------|----------|-----------|
| Ver | Todos* | Todos* | Todos* | Todos* | Todos* |
| Editar | Creador, Admins, Gerente | - | - | - | - |
| Aprobar | Admins, Gerente (origen) | - | - | - | - |
| Enviar | - | Admins, Gerente, Operador (origen) | - | - | - |
| Recibir | - | - | Admins, Gerente, Operador (destino) | - | - |
| Cancelar | Admins, Gerente | Admins, Gerente | - | - | - |

*Segun permisos de empresa/bodega

## Notificaciones

El sistema envia notificaciones en los siguientes eventos:

| Evento | Destinatarios |
|--------|---------------|
| Traslado aprobado | Solicitante del traslado |
| Traslado enviado | Solicitante, personal de bodega destino |
| Traslado recibido | Solicitante, aprobador |
| Traslado cancelado | Solicitante |

## Discrepancias en Recepcion

Cuando la cantidad recibida difiere de la enviada, se puede registrar:

```
{
  "product_id": 12,
  "expected": 10,
  "received": 9,
  "reason": "Una unidad danada en transito"
}
```

Las discrepancias quedan registradas para:
- Investigacion de perdidas
- Reclamos a transportistas
- Auditorias de inventario
- Mejora de procesos logisticos

---

## Caso Practico: Traslado de Insumos en la ENA

### Escenario
La Escuela Nacional de Agricultura (ENA) necesita trasladar fertilizante desde el Almacen General hacia la Bodega del Area de Cultivos para las practicas de campo.

### Actores Involucrados
- **Maria Garcia** - Encargada de Almacen General (Gerente de Bodega)
- **Pedro Sanchez** - Coordinador de Cultivos (Gerente de Bodega)
- **Juan Martinez** - Bodeguero del Almacen General (Operador de Bodega)

### Flujo del Proceso

#### 1. Creacion del Traslado (Maria Garcia)

Maria detecta que la Bodega de Cultivos necesita fertilizante para las practicas del semestre:

```
INFORMACION DEL TRASLADO:
Numero: TRF-20241122-XYZ789
Bodega Origen: Almacen General ENA
Bodega Destino: Bodega Area de Cultivos
Motivo: Reabastecimiento para practicas de campo

PRODUCTOS A TRASLADAR:
+---------------------------+----------+----------------+
| Producto                  | Cantidad | Stock Actual   |
+---------------------------+----------+----------------+
| Fertilizante NPK 15-15-15 | 15 sacos | 40 sacos       |
| Semilla Maiz Hibrido H-59 | 5 kg     | 200 kg         |
+---------------------------+----------+----------------+
```

**Estado**: `Pendiente`

#### 2. Aprobacion del Traslado (Maria Garcia)

Maria, como encargada de la bodega origen, revisa y aprueba el traslado:

1. Verifica disponibilidad de stock
2. Confirma que las cantidades son correctas
3. Agrega nota: "Aprobado para practicas del 2do semestre"
4. Hace clic en **"Aprobar Traslado"**

**Estado**: `Aprobado`

*Se registra: Aprobado por Maria Garcia - 22/11/2024 9:00 AM*

#### 3. Envio del Traslado (Juan Martinez)

Juan, el bodeguero, prepara los productos para envio:

1. Ubica los 15 sacos de fertilizante en el almacen
2. Prepara los 5 kg de semilla
3. Carga los productos en el vehiculo
4. Ingresa al sistema:
   - Numero de seguimiento: INT-2024-0892
   - Transportista: Vehiculo institucional
5. Hace clic en **"Enviar Traslado"**

**Estado**: `En Transito`

*Se registra: Enviado por Juan Martinez - 22/11/2024 10:30 AM*

**Movimientos generados en Almacen General**:
```
+---------------------------+----------+------------+--------------+
| Producto                  | Salida   | Saldo Ant. | Nuevo Saldo  |
+---------------------------+----------+------------+--------------+
| Fertilizante NPK 15-15-15 | -15 sacos| 40 sacos   | 25 sacos     |
| Semilla Maiz Hibrido H-59 | -5 kg    | 200 kg     | 195 kg       |
+---------------------------+----------+------------+--------------+
```

#### 4. Recepcion del Traslado (Pedro Sanchez)

Pedro recibe los productos en la Bodega de Cultivos:

1. Verifica fisicamente los productos:
   - Fertilizante NPK: 15 sacos OK
   - Semilla de Maiz: 5 kg OK
2. No hay discrepancias
3. Agrega nota: "Productos recibidos en buen estado"
4. Hace clic en **"Recibir Traslado"**

**Estado**: `Recibido`

*Se registra: Recibido por Pedro Sanchez - 22/11/2024 11:15 AM*

**Movimientos generados en Bodega Cultivos**:
```
+---------------------------+----------+------------+--------------+
| Producto                  | Entrada  | Saldo Ant. | Nuevo Saldo  |
+---------------------------+----------+------------+--------------+
| Fertilizante NPK 15-15-15 | +15 sacos| 8 sacos    | 23 sacos     |
| Semilla Maiz Hibrido H-59 | +5 kg    | 0 kg       | 5 kg         |
+---------------------------+----------+------------+--------------+
```

### Caso Alternativo: Recepcion con Discrepancia

Si Pedro hubiera encontrado que solo llegaron 14 sacos de fertilizante (uno danado):

```
DISCREPANCIA REGISTRADA:
Producto: Fertilizante NPK 15-15-15
Esperado: 15 sacos
Recibido: 14 sacos
Motivo: Un saco danado durante el transporte - bolsa rota

Accion: Se genero reporte de incidencia para revision
```

### Caso Alternativo: Traslado Rapido

Si el traslado fuera urgente y las bodegas estan en el mismo campus:

1. Maria accede a "Traslado Rapido"
2. Selecciona:
   - Origen: Almacen General ENA
   - Destino: Bodega Area de Cultivos
   - Producto: Fertilizante NPK 15-15-15
   - Cantidad: 15 sacos
3. Ejecuta el traslado

**Resultado inmediato**:
- Stock Almacen General: 40 -> 25 sacos
- Stock Bodega Cultivos: 8 -> 23 sacos
- No requirio aprobacion ni seguimiento

### Trazabilidad Completa

En cualquier momento, los administradores pueden ver:

- Quien solicito el traslado
- Quien lo aprobo y cuando
- Quien lo envio y cuando
- Quien lo recibio y cuando
- Todos los movimientos de inventario relacionados
- Discrepancias registradas (si las hubo)
- Datos de transporte (si se registraron)

Esta informacion es esencial para:
- Control de inventario multi-bodega
- Auditorias internas
- Seguimiento de productos
- Analisis de tiempos de traslado
- Identificacion de perdidas

---

## Preguntas Frecuentes

### ¿Puedo cancelar un traslado que ya fue enviado?
No. Una vez que el traslado cambia a estado "En Transito", ya se han generado los movimientos de salida en la bodega origen. Para revertirlo, seria necesario crear un traslado en sentido contrario.

### ¿Que pasa si el stock no es suficiente al momento de enviar?
El sistema validara la disponibilidad. Si el stock disponible es menor a la cantidad del traslado, el envio fallara y se mostrara un mensaje de error.

### ¿Se puede trasladar entre bodegas de diferentes empresas?
No. Los traslados solo estan permitidos entre bodegas de la misma empresa para mantener el control y la trazabilidad.

### ¿Cual es la diferencia entre "Traslado" y "Despacho"?
- **Traslado**: Movimiento entre bodegas de la misma empresa
- **Despacho**: Salida de productos hacia un destino externo (cliente, beneficiario, proyecto)

### ¿Se puede editar un traslado aprobado?
No. Una vez aprobado, el traslado no puede editarse. Si hay errores, debe cancelarse y crear uno nuevo.

---

*Ultima actualizacion: Noviembre 2024*
