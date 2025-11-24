# Proceso de Cierres Mensuales de Inventario

Este documento describe el flujo de trabajo completo para la gestion de cierres mensuales de inventario en el sistema.

## Que es un Cierre Mensual

Un cierre mensual de inventario es un proceso contable y operativo que:

1. **Captura el estado del inventario** en un momento especifico (fin de mes)
2. **Calcula los saldos** de todos los productos en una bodega
3. **Permite registrar conteos fisicos** y detectar discrepancias
4. **Genera un punto de referencia** para auditorias y reportes financieros
5. **Bloquea movimientos** con fecha anterior al periodo cerrado

## Por que son Importantes los Cierres

| Aspecto | Beneficio |
|---------|-----------|
| **Control Contable** | Genera datos precisos para estados financieros |
| **Auditoria** | Establece puntos de referencia historicos |
| **Conciliacion** | Permite comparar inventario fisico vs. sistema |
| **Trazabilidad** | Documenta quien aprobo y cuando se cerro |
| **Analisis** | Facilita comparacion entre periodos |

## Estados de un Cierre

Un cierre de inventario puede tener los siguientes estados:

| Estado | Descripcion | Color |
|--------|-------------|-------|
| **En Proceso** | Cierre recien creado, puede editarse y procesarse | Amarillo |
| **Cerrado** | Periodo finalizado, no se pueden agregar movimientos | Verde |
| **Reabierto** | Cierre previamente cerrado que fue reabierto para ajustes | Azul |
| **Cancelado** | Cierre anulado, no tiene efecto | Rojo |

## Diagrama de Flujo

```
+----------------+
|  EN PROCESO    | <-- Crear cierre (seleccionar bodega y periodo)
+-------+--------+
        |
        | Procesar (calcular saldos)
        v
+----------------+
|  EN PROCESO    | <-- Saldos calculados, listo para revision
|  (procesado)   |
+-------+--------+
        |
        | Aprobar (validar resultados)
        v
+----------------+
|  EN PROCESO    | <-- Aprobado, listo para cerrar
|  (aprobado)    |
+-------+--------+
        |
        | Cerrar periodo
        v
+----------------+
|    CERRADO     | <-- Periodo finalizado
+-------+--------+
        |
        | Reabrir (con justificacion)
        v
+----------------+
|   REABIERTO    | <-- Permite ajustes adicionales
+----------------+

* Solo se puede cancelar un cierre EN PROCESO
* CERRADO puede reabrirse con justificacion
* REABIERTO puede cerrarse nuevamente
```

## Numero de Cierre

Cada cierre genera un numero unico con el formato:

```
CLS-YYYYMM-XXXX

Donde:
- CLS: Prefijo fijo (Closure)
- YYYY: Ano (ej: 2024)
- MM: Mes (01-12)
- XXXX: Secuencia del periodo

Ejemplo: CLS-202411-0001 (Primer cierre de Noviembre 2024)
```

## Actores del Sistema

### 1. Super Administrador
- **Permisos Completos**:
  - Crear cierres en cualquier empresa
  - Ver todos los cierres
  - Procesar, aprobar y cerrar cualquier cierre
  - Reabrir cierres cerrados
  - Cancelar cierres en proceso

### 2. Administrador de Empresa
- **Permisos de Empresa**:
  - Crear cierres en bodegas de su empresa
  - Ver cierres de su empresa
  - Procesar y aprobar cierres
  - Cerrar periodos de su empresa
  - Reabrir cierres (con justificacion)

### 3. Gerente de Bodega
- **Permisos de Bodega**:
  - Crear cierres en sus bodegas asignadas
  - Ver cierres de sus bodegas
  - Procesar cierres (calcular saldos)
  - Registrar conteos fisicos
  - Enviar para aprobacion

### 4. Operador de Bodega
- **Permisos Limitados**:
  - Ver cierres de sus bodegas
  - Registrar conteos fisicos (si esta asignado)

## Flujo de Trabajo Completo

### Paso 1: Crear el Cierre (Estado: En Proceso)

**Quien puede crear**: Super Admin, Admin de Empresa, Gerente de Bodega

1. El usuario accede a "Cierres de Inventario" > "Nuevo Cierre"
2. Completa la informacion:
   - **Bodega**: Selecciona la bodega a cerrar
   - **Ano**: Ano del periodo (ej: 2024)
   - **Mes**: Mes del periodo (ej: Noviembre)
   - **Fecha de Cierre**: Cuando se realiza el cierre
   - **Notas**: Observaciones opcionales
3. Guarda el cierre

**Resultado**: El cierre queda en estado `en_proceso` con periodo definido automaticamente (del 1 al ultimo dia del mes).

**Validaciones**:
- No puede existir otro cierre para el mismo periodo y bodega
- La bodega debe estar activa

### Paso 2: Procesar el Cierre (Calcular Saldos)

**Quien puede procesar**: Super Admin, Admin de Empresa, Gerente de Bodega

1. El usuario accede al cierre creado
2. Hace clic en "Procesar Cierre"
3. El sistema automaticamente:
   - Identifica todos los productos con movimientos en el periodo
   - Calcula el saldo inicial (del cierre anterior o primer movimiento)
   - Suma las entradas del periodo
   - Resta las salidas del periodo
   - Calcula el saldo final
4. Genera el detalle de cada producto

**Calculo de Saldos**:
```
Saldo Final = Saldo Inicial + Entradas - Salidas

Para cada producto:
- Saldo Inicial: Del cierre anterior o ultimo movimiento antes del periodo
- Entradas: Suma de quantity_in durante el periodo
- Salidas: Suma de quantity_out durante el periodo
- Saldo Final: balance_quantity del ultimo movimiento del periodo
```

**Resultado**: Se crean registros de detalle (InventoryClosureDetail) para cada producto con:
- Cantidad inicial y valor
- Entradas del periodo
- Salidas del periodo
- Cantidad final calculada y valor
- Numero de movimientos

### Paso 3: Revisar y Registrar Conteos Fisicos (Opcional)

**Quien puede registrar conteos**: Super Admin, Admin de Empresa, Gerente de Bodega

1. El usuario revisa los saldos calculados
2. Para cada producto puede:
   - Verificar el saldo calculado
   - Registrar un conteo fisico (cantidad real encontrada)
   - El sistema calcula automaticamente la discrepancia

**Calculo de Discrepancia**:
```
Discrepancia = Cantidad Fisica - Cantidad Calculada

Si Discrepancia != 0:
  - Se marca como "tiene discrepancia"
  - El saldo ajustado toma el valor del conteo fisico
```

### Paso 4: Aprobar el Cierre

**Quien puede aprobar**: Super Admin, Admin de Empresa

1. El aprobador revisa:
   - Todos los saldos calculados
   - Discrepancias encontradas
   - Valor total del inventario
2. Puede agregar notas de aprobacion
3. Hace clic en "Aprobar Cierre"

**Resultado**:
- Se registra quien aprobo y cuando
- El cierre queda listo para ser cerrado
- Se notifica al creador

### Paso 5: Cerrar el Periodo

**Quien puede cerrar**: Super Admin, Admin de Empresa

1. El usuario verifica que el cierre este aprobado
2. Hace clic en "Cerrar Periodo"
3. Confirma la accion

**Resultado**:
- El cierre cambia a estado `cerrado`
- Se registra quien cerro y cuando
- **Se actualizan los campos last_count en el inventario**
- **Se bloquean movimientos con fecha anterior al periodo**

**Importante**: Una vez cerrado, no se pueden registrar movimientos de inventario con fecha dentro del periodo cerrado.

### Reabrir un Cierre (Excepcional)

**Quien puede reabrir**: Super Admin, Admin de Empresa

Solo disponible para cierres en estado `cerrado`:

1. El usuario proporciona una justificacion obligatoria
2. Hace clic en "Reabrir Cierre"
3. Confirma la accion

**Resultado**:
- El cierre cambia a estado `reabierto`
- Se registra quien reabrio, cuando y por que
- Se pueden realizar ajustes adicionales
- Se debe cerrar nuevamente al terminar

## Informacion Capturada por Producto

Para cada producto en el cierre se registra:

| Campo | Descripcion |
|-------|-------------|
| **Saldo Inicial** | Cantidad y valor al inicio del periodo |
| **Entradas** | Total de quantity_in durante el periodo |
| **Salidas** | Total de quantity_out durante el periodo |
| **Saldo Calculado** | Resultado matematico: inicial + entradas - salidas |
| **Conteo Fisico** | Cantidad real encontrada (si se registro) |
| **Discrepancia** | Diferencia entre fisico y calculado |
| **Saldo Ajustado** | Saldo final considerando conteo fisico |
| **Movimientos** | Numero de transacciones del producto |

## Totales del Cierre

El cierre consolida:

- **Total de Productos**: Cantidad de productos diferentes
- **Total de Movimientos**: Suma de todas las transacciones
- **Valor Total**: Suma del valor de todo el inventario
- **Cantidad Total**: Suma de unidades de todos los productos
- **Productos con Discrepancia**: Cantidad de productos con diferencias
- **Valor de Discrepancias**: Valor total de las diferencias

---

## Caso Practico: Cierre de Noviembre en la ENA

### Escenario
La Escuela Nacional de Agricultura (ENA) necesita realizar el cierre mensual de inventario de su Almacen General correspondiente a Noviembre 2024.

### Actores Involucrados
- **Maria Garcia** - Encargada de Almacen General (Gerente de Bodega)
- **Roberto Fernandez** - Contador de la ENA (Administrador de Empresa)
- **Carlos Mendez** - Auditor Interno

### Flujo del Proceso

#### 1. Creacion del Cierre (Maria Garcia)

El 30 de Noviembre, Maria inicia el proceso de cierre:

```
INFORMACION DEL CIERRE:
Numero: CLS-202411-0001
Bodega: Almacen General ENA
Ano: 2024
Mes: Noviembre
Fecha de Cierre: 30/11/2024
Periodo: 01/11/2024 al 30/11/2024
Notas: Cierre mensual regular
```

**Estado**: `En Proceso`

#### 2. Procesamiento del Cierre (Maria Garcia)

Maria hace clic en "Procesar Cierre" y el sistema calcula:

```
RESUMEN DEL PROCESAMIENTO:
+---------------------------+-------------+----------+----------+-------------+
| Producto                  | Saldo Inic. | Entradas | Salidas  | Saldo Final |
+---------------------------+-------------+----------+----------+-------------+
| Fertilizante NPK 15-15-15 | 40 sacos    | 50 sacos | 20 sacos | 70 sacos    |
| Semilla Maiz Hibrido H-59 | 200 kg      | 0 kg     | 55 kg    | 145 kg      |
| Herramienta Pala de Mano  | 15 uds      | 10 uds   | 3 uds    | 22 uds      |
| Manguera de Riego 100m    | 8 rollos    | 5 rollos | 2 rollos | 11 rollos   |
+---------------------------+-------------+----------+----------+-------------+

TOTALES:
- Productos: 4
- Movimientos: 23
- Valor Total: $15,450.00
- Cantidad Total: 248 unidades
```

#### 3. Conteo Fisico (Maria Garcia)

Maria realiza el conteo fisico y encuentra una discrepancia:

```
CONTEO FISICO - Fertilizante NPK 15-15-15:
Sistema muestra: 70 sacos
Conteo fisico: 68 sacos
Discrepancia: -2 sacos
Valor discrepancia: -$50.00

Notas: "Se detectaron 2 sacos con dano por humedad que no fueron
        dados de baja. Se requiere crear ajuste de inventario."
```

Los demas productos coinciden con el sistema.

**Resultado del Conteo**:
```
RESUMEN DE DISCREPANCIAS:
+---------------------------+-----------+----------+-------------+
| Producto                  | Calculado | Fisico   | Diferencia  |
+---------------------------+-----------+----------+-------------+
| Fertilizante NPK 15-15-15 | 70 sacos  | 68 sacos | -2 sacos    |
| (Otros productos)         | OK        | OK       | 0           |
+---------------------------+-----------+----------+-------------+

Productos con discrepancia: 1
Valor total discrepancias: -$50.00
```

#### 4. Aprobacion del Cierre (Roberto Fernandez)

Roberto revisa el cierre y la discrepancia encontrada:

1. Verifica los calculos de saldos
2. Revisa la discrepancia del fertilizante
3. Confirma que Maria anotara el hallazgo
4. Agrega nota: "Aprobado. Pendiente ajuste de inventario para regularizar los 2 sacos de fertilizante."
5. Hace clic en **"Aprobar Cierre"**

**Estado**: `En Proceso` (Aprobado)

*Se registra: Aprobado por Roberto Fernandez - 02/12/2024 10:00 AM*

#### 5. Cierre del Periodo (Roberto Fernandez)

Roberto procede a cerrar el periodo:

1. Verifica que todas las revisiones esten completas
2. Hace clic en **"Cerrar Periodo"**
3. Confirma la accion

**Estado**: `Cerrado`

*Se registra: Cerrado por Roberto Fernandez - 02/12/2024 10:15 AM*

**Efectos del Cierre**:
- Se actualiza `last_count_quantity` en tabla inventory
- Se actualiza `last_counted_at` con la fecha del conteo
- No se pueden crear movimientos con fecha del 1-30 de Noviembre

#### 6. Accion Correctiva (Maria Garcia)

Despues del cierre, Maria crea un ajuste de inventario:

```
AJUSTE DE INVENTARIO:
Numero: ADJ-20241202-XYZ123
Tipo: Producto Danado
Bodega: Almacen General ENA
Producto: Fertilizante NPK 15-15-15
Cantidad: -2 sacos
Motivo: Producto danado por humedad detectado en cierre Nov-2024
Referencia: CLS-202411-0001
```

Este ajuste se procesara en Diciembre 2024.

### Reporte Final del Cierre

```
====================================================
CIERRE DE INVENTARIO - NOVIEMBRE 2024
====================================================
Numero: CLS-202411-0001
Bodega: Almacen General ENA
Periodo: 01/11/2024 - 30/11/2024

RESUMEN:
-------------------------------------------------
Total de Productos:           4
Total de Movimientos:         23
Valor Total del Inventario:   $15,400.00
Productos con Discrepancia:   1
Valor de Discrepancias:       -$50.00

APROBACION:
-------------------------------------------------
Aprobado por:     Roberto Fernandez
Fecha Aprobacion: 02/12/2024 10:00 AM

CIERRE:
-------------------------------------------------
Cerrado por:      Roberto Fernandez
Fecha Cierre:     02/12/2024 10:15 AM

OBSERVACIONES:
-------------------------------------------------
Se detecto discrepancia en Fertilizante NPK.
Ajuste de inventario programado para Diciembre.
====================================================
```

---

## Caso Alternativo: Reapertura de Cierre

### Escenario
Carlos Mendez (Auditor) detecta que se omitio registrar una compra de Noviembre que llego tarde.

### Proceso de Reapertura

1. Roberto solicita la reapertura con justificacion:
   *"Se requiere reabrir para registrar compra #PO-2024-450 que llego el 29/11 pero no se registro en el sistema."*

2. El sistema cambia el cierre a estado `reabierto`

3. Se registra la compra omitida

4. Se reprocesa el cierre para recalcular saldos

5. Se aprueba y cierra nuevamente

**Registro de Reapertura**:
```
Motivo: Se requiere reabrir para registrar compra #PO-2024-450
        que llego el 29/11 pero no se registro en el sistema.
Reabierto por: Roberto Fernandez
Fecha: 05/12/2024 09:30 AM
```

---

## Mejores Practicas

### Antes del Cierre
- Verificar que no haya operaciones pendientes (compras, despachos, traslados)
- Confirmar que todos los movimientos esten registrados
- Revisar alertas de stock pendientes

### Durante el Cierre
- Realizar conteo fisico de productos criticos
- Documentar todas las discrepancias encontradas
- Agregar notas explicativas para anomalias

### Despues del Cierre
- Crear ajustes para regularizar discrepancias
- Archivar documentacion de respaldo
- Comunicar resultados a las areas interesadas

---

## Preguntas Frecuentes

### ¿Que pasa si necesito registrar un movimiento de fecha anterior a un cierre?
No es posible. Una vez cerrado el periodo, el sistema bloquea movimientos con fecha dentro de ese rango. Si es absolutamente necesario, debe reabrir el cierre con justificacion.

### ¿Puedo tener multiples cierres para el mismo mes?
No para la misma bodega. Solo puede existir un cierre activo por bodega y periodo (mes/ano).

### ¿Que sucede si no registro conteo fisico?
El sistema usa el saldo calculado como saldo final. El conteo fisico es opcional pero recomendado para detectar discrepancias.

### ¿Quien puede ver los cierres?
Depende del rol:
- Super Admin: Todos los cierres
- Admin de Empresa: Cierres de su empresa
- Gerente de Bodega: Cierres de sus bodegas
- Operador: Solo visualizacion de sus bodegas

### ¿Puedo cancelar un cierre ya cerrado?
No. Solo se pueden cancelar cierres en estado `en_proceso`. Los cierres cerrados solo pueden ser reabiertos para ajustes y luego cerrados nuevamente.

### ¿Como afecta el cierre a los reportes de kardex?
El cierre no modifica los movimientos existentes. Solo establece un punto de referencia que puede usarse para conciliacion y como saldo inicial del siguiente periodo.

### ¿Cada cuanto debo hacer cierres?
Se recomienda mensualmente al finalizar cada periodo contable. Sin embargo, algunas organizaciones prefieren cierres trimestrales o semestrales segun sus necesidades.

---

*Ultima actualizacion: Noviembre 2024*
