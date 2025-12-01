# Guía de Destinatarios Internos - ENA

## Resumen

Se importaron **63 destinatarios internos** desde el archivo `plantilla_clientes.xlsx`. Estos representan usuarios/empleados de ENA organizados por departamento/unidad que reciben despachos de las bodegas.

> **Nota**: En el contexto de ENA, los "clientes" son destinatarios internos para despachos de bodega, no clientes externos de venta.

---

## Flujo de Despachos

```
Bodega General → Bodegas Fraccionarias → Departamentos/Usuarios (estos 63 registros)
```

---

## Estadísticas de Importación

| Métrica | Valor |
|---------|-------|
| Total importados | 63 |
| Omitidos (filas vacías) | 7 |
| Departamentos/Unidades | 63 |

---

## Estructura de Datos

### Campos Principales

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| code | Código único | USR-001 |
| name | Nombre completo | Carlos Manuel Cerón |
| type | Departamento/Unidad | Auditoría Interna |
| email | Correo institucional | cceron@ena.edu.sv |
| phone | Teléfono | 23664800 |

### Campos de Dirección

Todos los destinatarios comparten la dirección institucional de ENA:
- **Dirección**: km 33 1/2 carretera Santa Ana
- **Ciudad**: Ciudad Arce
- **Departamento**: La Libertad
- **País**: El Salvador

---

## Departamentos/Unidades

| Código | Nombre | Departamento |
|--------|--------|--------------|
| USR-001 | Carlos Manuel Cerón | Auditoría Interna |
| USR-002 | Gloria Esperanza Guerra De Deras | Dirección General |
| USR-003 | Yanneth Xiomara Diaz Interiano | Asesoría Jurídica |
| USR-004 | Rene Antonio Alvarado Hernández | Unidad Financiera Institucional |
| USR-005 | Consuelo Jeanneth Ascencio Vega | Contabilidad |
| USR-006 | Diana Abigail Amaya | Tesorería |
| USR-007 | Jairo Otoniel Delgado López | Colecturía |
| USR-008 | Blanca Celina Hernández Alfaro | Presupuesto |
| USR-009 | Ena Beatriz Orellana | UCP |
| USR-010 | Zobeyda Marisol Valencia de Toledo | Unidad Ambiental |
| USR-011 | Silvia Rebeca Rodríguez Alvarez | Unidad De Género |
| USR-012 | Karla Rosario Obispo Vides | UAIP |
| USR-013 | Blanca Elizabeth Sorto De Coto | Planificación |
| USR-014 | Jeannette Carolina Martínez Rosa | Departamento De Recursos Humanos |
| USR-015 | Xiomara Elizabeth Ticas Jiménez | Bienestar Laboral Y Capacitaciones |
| ... | ... | ... |

---

## Uso en el Código

### Obtener Destinatarios Activos

```php
use App\Models\Customer;

// Todos los destinatarios activos
$customers = Customer::active()->get();

// Por compañía
$customers = Customer::forCompany($companyId)->active()->get();

// Buscar por código
$customer = Customer::where('code', 'USR-001')->first();

// Buscar por departamento/unidad
$customers = Customer::where('type', 'Contabilidad')->get();
```

### En Blade (Flux UI) - Selector de Destinatario

```blade
<flux:select wire:model="customer_id" label="Destinatario">
    <flux:select.option value="">Seleccione destinatario</flux:select.option>
    @foreach($customers as $customer)
        <flux:select.option value="{{ $customer->id }}">
            {{ $customer->name }} ({{ $customer->type }})
        </flux:select.option>
    @endforeach
</flux:select>
```

### Agrupar por Departamento

```php
$customersByDepartment = Customer::active()
    ->orderBy('type')
    ->orderBy('name')
    ->get()
    ->groupBy('type');
```

---

## Seeder

Para reimportar los destinatarios:

```bash
php artisan db:seed --class=ENACustomersImportSeeder
```

> **Nota**: El seeder elimina permanentemente (force delete) los registros existentes antes de importar para evitar duplicados.

---

## Notas Importantes

1. **Uso interno**: Estos registros son para despachos internos de bodega, no ventas externas
2. **Campo `type`**: Contiene el departamento/unidad al que pertenece cada persona
3. **Dirección de envío**: Por defecto se copia de la dirección de facturación (institucional)
4. **Límite de crédito y descuento**: Se establecen en 0 por defecto para destinatarios internos
