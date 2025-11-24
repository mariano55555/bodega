# 📊 Cierres de Inventario - Guía Completa

## ¿Qué es un Cierre de Inventario?

Un **cierre de inventario** es el proceso formal de **consolidar y validar** los saldos de inventario al final de un período (generalmente mensual). Es como tomar una "foto" del inventario en un momento específico para:

- Confirmar que los saldos del sistema coinciden con el inventario físico
- Generar reportes financieros precisos
- Establecer el **saldo inicial** del siguiente período
- Detectar y documentar discrepancias

---

## 🔄 Flujo de Trabajo de Cierres

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         FLUJO DE CIERRE                                  │
└─────────────────────────────────────────────────────────────────────────┘

1. CREAR CIERRE
   │  └─ Se crea con status: "en_proceso"
   │     Datos: año, mes, bodega, período (fecha inicio/fin)
   ▼
2. PROCESAR (botón "Procesar")
   │  └─ Calcula saldos de TODOS los productos con movimientos
   │     - Saldo inicial (del cierre anterior o primer movimiento)
   │     - Entradas del período
   │     - Salidas del período
   │     - Saldo final calculado
   │  └─ NO cambia el estado, solo genera/actualiza datos
   │  └─ Puede ejecutarse múltiples veces para recalcular
   ▼
3. APROBAR (botón "Aprobar")
   │  └─ Marca: is_approved = true
   │     Registra: approved_by, approved_at
   │  └─ Sigue en status "en_proceso" pero ahora está validado
   │  └─ Habilita el botón "Cerrar"
   ▼
4. CERRAR (botón "Cerrar")
   │  └─ Cambia status: "en_proceso" → "cerrado"
   │     Registra: closed_by, closed_at
   │  └─ Los saldos finales se convierten en saldos iniciales
   │     del siguiente período
   │  └─ Ya no se pueden modificar los datos
   ▼
5. CERRADO (estado final)
   │  └─ Solo disponible: botón "Reabrir" (requiere justificación)
   │
   └──► REABRIR (si es necesario)
        └─ Cambia status: "cerrado" → "reabierto"
           Requiere: razón de reapertura (mínimo 10 caracteres)
           Registra: reopened_by, reopened_at, reopening_reason
```

---

## 📋 Estados del Cierre

| Estado | Descripción | Botones Disponibles |
|--------|-------------|---------------------|
| `en_proceso` | Cierre en preparación, aún no aprobado | Procesar, Aprobar, Cancelar |
| `en_proceso` + aprobado | Cierre validado, listo para cerrar | Procesar, **Cerrar**, Cancelar |
| `cerrado` | Período finalizado oficialmente | Reabrir |
| `reabierto` | Período reabierto para correcciones | Procesar, Aprobar, Cancelar |
| `cancelado` | Cierre descartado | Ninguno |

---

## 🔘 Descripción de Cada Botón

### 1. **Procesar**
**¿Qué hace?**
- Busca TODOS los productos que tuvieron movimientos en el período
- Para cada producto calcula:
  - **Saldo inicial**: Del cierre anterior o del último movimiento antes del período
  - **Entradas**: Suma de quantity_in durante el período
  - **Salidas**: Suma de quantity_out durante el período
  - **Saldo final**: Inicial + Entradas - Salidas
- Guarda los resultados en `inventory_closure_details`
- Actualiza totales en el cierre (total_products, total_movements, total_value)

**¿Cuándo usarlo?**
- Después de crear el cierre para generar los datos
- Si hubo cambios en movimientos y quieres recalcular
- Puede ejecutarse múltiples veces sin problema

**¿Cambia el estado?**
- NO - Solo calcula datos, el estado sigue siendo `en_proceso`

---

### 2. **Aprobar**
**¿Qué hace?**
- Valida que los datos del cierre son correctos
- Marca `is_approved = true`
- Registra quién aprobó y cuándo

**¿Cuándo usarlo?**
- Después de revisar los datos calculados y confirmar que son correctos
- Típicamente lo hace un supervisor o gerente

**¿Cambia el estado?**
- NO cambia el status (sigue en `en_proceso`)
- PERO habilita el botón "Cerrar"

---

### 3. **Cerrar**
**¿Qué hace?**
- Finaliza oficialmente el período
- Cambia status a `cerrado`
- Los saldos finales quedan como referencia para el siguiente período

**¿Cuándo usarlo?**
- Solo después de aprobar
- Cuando estés seguro de que no hay más correcciones pendientes

**Requisitos:**
- El cierre debe estar aprobado (`is_approved = true`)

---

### 4. **Cancelar**
**¿Qué hace?**
- Descarta el cierre completamente
- Cambia status a `cancelado`

**¿Cuándo usarlo?**
- Si el cierre se creó por error
- Si decides no proceder con este cierre

**Restricciones:**
- NO se puede cancelar un cierre ya cerrado

---

### 5. **Reabrir**
**¿Qué hace?**
- Reabre un período cerrado para hacer correcciones
- Cambia status de `cerrado` a `reabierto`
- Requiere una justificación obligatoria

**¿Cuándo usarlo?**
- Se detectaron errores después de cerrar
- Llegaron documentos atrasados que afectan el período

**Consideraciones:**
- Usar con precaución - afecta la integridad de los datos
- Requiere justificación documentada

---

## 📊 Datos que Calcula el Cierre

### Por Producto (inventory_closure_details)

| Campo | Descripción |
|-------|-------------|
| `opening_quantity` | Cantidad al inicio del período |
| `opening_unit_cost` | Costo unitario al inicio |
| `opening_total_value` | Valor total inicial |
| `quantity_in` | Total de entradas en el período |
| `quantity_out` | Total de salidas en el período |
| `movement_count` | Número de movimientos |
| `calculated_closing_quantity` | Saldo final calculado |
| `calculated_closing_value` | Valor final calculado |
| `physical_count_quantity` | Conteo físico (si se realizó) |
| `discrepancy_quantity` | Diferencia entre calculado y físico |
| `has_discrepancy` | Si hay diferencia |

### Totales del Cierre (inventory_closures)

| Campo | Descripción |
|-------|-------------|
| `total_products` | Productos con movimientos |
| `total_movements` | Total de movimientos en el período |
| `total_quantity` | Suma de cantidades finales |
| `total_value` | Valor total del inventario |
| `products_with_discrepancies` | Productos con diferencias |
| `total_discrepancy_value` | Valor total de discrepancias |

---

## 🎬 Ejemplo Práctico

### Escenario: Cierre de Noviembre 2025

```
1. CREAR CIERRE
   ┌────────────────────────────────────┐
   │ Número: CLS-202511-0001            │
   │ Bodega: Bodega Central             │
   │ Período: 01/11/2025 - 30/11/2025   │
   │ Estado: en_proceso                 │
   └────────────────────────────────────┘

2. PROCESAR (click en botón)
   El sistema calcula:
   ┌─────────────────────────────────────────────────────────────┐
   │ Producto: Arroz 50lb                                        │
   │ Saldo Inicial: 100 unidades (del cierre de Octubre)        │
   │ Entradas: +200 (compras) +50 (traslados recibidos)         │
   │ Salidas: -80 (despachos) -20 (traslados enviados)          │
   │ Saldo Final: 100 + 250 - 100 = 250 unidades                │
   └─────────────────────────────────────────────────────────────┘

   Resultado:
   - Total productos: 45
   - Total movimientos: 320
   - Valor total: $125,000.00

3. REVISAR DATOS
   El supervisor revisa los saldos calculados
   Compara con conteo físico si es necesario

4. APROBAR (click en botón)
   ┌────────────────────────────────────┐
   │ is_approved: true                  │
   │ approved_by: Juan Pérez            │
   │ approved_at: 2025-11-21 14:30:00   │
   │ Estado: en_proceso (sin cambio)    │
   │ Botón "Cerrar" ahora disponible    │
   └────────────────────────────────────┘

5. CERRAR (click en botón)
   ┌────────────────────────────────────┐
   │ status: cerrado                    │
   │ closed_by: Juan Pérez              │
   │ closed_at: 2025-11-21 14:35:00     │
   └────────────────────────────────────┘

   ✅ El cierre de Noviembre está completo
   Los saldos finales serán los iniciales de Diciembre
```

---

## ⚠️ Preguntas Frecuentes

### ¿Por qué "Procesar" no hace nada visible?
**Procesar** calcula datos en segundo plano. Si ya procesaste antes y no hubo cambios en los movimientos, los resultados serán iguales. Revisa la sección de "Resumen" en la página del cierre para ver los totales calculados.

### ¿Puedo procesar múltiples veces?
Sí, puedes procesar cuantas veces quieras antes de cerrar. Esto recalcula todos los datos.

### ¿Por qué no veo el botón "Cerrar"?
El botón "Cerrar" solo aparece cuando:
- El status es `en_proceso` Y
- El cierre está aprobado (`is_approved = true`)

Primero debes hacer click en "Aprobar".

### ¿Qué pasa si cierro y luego encuentro un error?
Puedes usar el botón "Reabrir" para volver a abrir el período. Esto cambiará el status a `reabierto` y podrás hacer correcciones. Luego deberás aprobar y cerrar nuevamente.

### ¿Los cierres afectan el inventario actual?
No directamente. Los cierres son un **registro histórico** de los saldos en un momento dado. No crean ni modifican movimientos de inventario.

---

## 🔗 Relación con Otros Módulos

### Movimientos → Cierres
- Los cierres **leen** los movimientos del período para calcular saldos
- Un cierre NO crea movimientos nuevos

### Cierres → Siguiente Período
- El saldo final de un cierre se convierte en el saldo inicial del siguiente
- Si no hay cierre anterior, se toma el último movimiento antes del período

### Ajustes durante el Período
- Si hay discrepancias en el conteo físico, se deben crear **Ajustes** antes de cerrar
- Los ajustes crean movimientos que afectan los cálculos del cierre

---

## 📌 Mejores Prácticas

1. **Procesa antes de aprobar** - Siempre ejecuta "Procesar" al menos una vez antes de aprobar
2. **Revisa los totales** - Verifica que los números tengan sentido antes de aprobar
3. **Cierra a tiempo** - Cierra los períodos de manera oportuna para mantener la integridad
4. **Documenta reaperturas** - Si reabres un cierre, explica claramente por qué
5. **Conteo físico** - Para mayor precisión, realiza conteos físicos antes de cerrar

---

## 🔗 Enlaces Relacionados

- **Lista de Cierres**: [http://bodega.test/closures](http://bodega.test/closures)
- **Crear Cierre**: [http://bodega.test/closures/create](http://bodega.test/closures/create)
- **Movimientos**: [http://bodega.test/inventory/movements](http://bodega.test/inventory/movements)
- **Ajustes**: [http://bodega.test/adjustments](http://bodega.test/adjustments)

---

**Última actualización:** 21 de Noviembre, 2025
**Versión del Sistema:** 1.0
