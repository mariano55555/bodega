# Guía de Proveedores - ENA

## Resumen

Se importaron **1,369 proveedores** desde el archivo `plantilla_proveedores.xlsx` del sistema anterior de ENA.

---

## Estadísticas de Importación

| Métrica | Valor |
|---------|-------|
| Total importados | 1,369 |
| Omitidos (duplicados) | 8 |
| Omitidos (datos vacíos) | 4 |
| Rango de IDs | 7 - 1383 |

### Proveedores Duplicados Detectados

Los siguientes proveedores aparecían duplicados en el Excel (mismo nombre, diferente NIT):

1. SANTOS MONTOYA RAMOS
2. PRICESMART EL SALVADOR, S.A. DE C.V.
3. DIAGRI, S.A. DE C.V.
4. JUAN CARLOS PARADA JUAREZ
5. DISTRIBUIDORA FERRETERA ALAS SA DE CV
6. PRIAL, S.A DE C.V.
7. Grupo Mallo, SA DE CV
8. JOSE ROBERTO EVORA CEA

---

## Estructura de Datos

### Campos Importados

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| name | Nombre del proveedor | ESPERANZA VANEGAS AVALOS |
| legal_name | Razón social | (opcional) |
| tax_id | NIT/NRC | 00017673-8 |
| email | Correo electrónico | (opcional) |
| phone | Teléfono | 7499-5369 |
| website | Sitio web | (opcional) |
| address | Dirección | CTON LOMAS DE SANTIAGO |
| city | Ciudad | (opcional) |
| state | Departamento | (opcional) |
| country | País | El Salvador |
| postal_code | Código postal | 01101 |
| contact_person | Persona de contacto | (opcional) |
| contact_phone | Teléfono contacto | (opcional) |
| contact_email | Correo contacto | (opcional) |
| payment_terms | Términos de pago | 30 días |
| credit_limit | Límite de crédito | 5000.00 |
| rating | Calificación (1-5) | 5 |
| notes | Notas | Proveedor principal |

---

## Uso en el Código

### Obtener Proveedores Activos

```php
use App\Models\Supplier;

// Todos los proveedores activos
$suppliers = Supplier::active()->get();

// Por compañía
$suppliers = Supplier::forCompany($companyId)->active()->get();

// Por calificación
$suppliers = Supplier::byRating(5)->get();
```

### Buscar por NIT

```php
$supplier = Supplier::where('tax_id', '00017673-8')->first();
```

### En Blade (Flux UI)

```blade
<flux:select wire:model="supplier_id" label="Proveedor">
    <flux:select.option value="">Seleccione proveedor</flux:select.option>
    @foreach($suppliers as $supplier)
        <flux:select.option value="{{ $supplier->id }}">
            {{ $supplier->name }} ({{ $supplier->tax_id }})
        </flux:select.option>
    @endforeach
</flux:select>
```

---

## Relaciones con Otros Modelos

### Productos

```php
// Obtener productos de un proveedor (via pivot)
$supplier->products;

// Proveedor principal de un producto
$product->primarySupplier;
```

### Movimientos de Inventario

```php
// Movimientos asociados a un proveedor
$supplier->inventoryMovements;
```

---

## Seeder

Para reimportar los proveedores:

```bash
php artisan db:seed --class=ENASuppliersImportSeeder
```

> **Nota**: El seeder elimina (soft delete) los proveedores existentes antes de importar.

---

## Identificadores

- **ID (autoincrement)**: Usado como FK en todas las relaciones
- **tax_id (NIT/NRC)**: Identificador único del proveedor, usado para búsquedas y validación
- **slug**: Generado automáticamente desde el nombre, usado en URLs
