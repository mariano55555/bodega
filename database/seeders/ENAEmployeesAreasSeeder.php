<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ENAEmployeesAreasSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = base_path('plantilla_clientes.xlsx');

        if (! file_exists($filePath)) {
            $this->command->error("Archivo no encontrado: {$filePath}");

            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Remover encabezado
        array_shift($data);

        // Índices de columnas relevantes
        $tipoIndex = 2;   // Tipo * (Unidad/Depto)
        $nombreIndex = 1; // Nombre *
        $emailIndex = 3;  // Correo Electrónico

        // 1. Eliminar áreas con soft delete (restaurar y luego limpiar)
        $this->command->info('Eliminando áreas existentes con soft delete...');
        Area::withTrashed()->forceDelete();

        // 2. Recolectar áreas únicas
        $areasUnicas = [];
        foreach ($data as $row) {
            $tipo = trim($row[$tipoIndex] ?? '');
            if (! empty($tipo) && ! in_array($tipo, $areasUnicas)) {
                $areasUnicas[] = $tipo;
            }
        }

        $this->command->info('Encontradas '.count($areasUnicas).' áreas únicas');

        // 3. Insertar áreas únicas
        $areasMap = []; // nombre => id
        foreach ($areasUnicas as $areaNombre) {
            $area = Area::create([
                'company_id' => 1, // Ajustar según corresponda
                'name' => $areaNombre,
                'slug' => Str::slug($areaNombre),
                'is_active' => true,
                'active_at' => now(),
            ]);
            $areasMap[$areaNombre] = $area->id;
            $this->command->info("Área creada: {$areaNombre}");
        }

        // 4. Actualizar empleados según nombre o email
        $actualizados = 0;
        $noEncontrados = [];

        foreach ($data as $row) {
            $nombre = trim($row[$nombreIndex] ?? '');
            $email = trim($row[$emailIndex] ?? '');
            $tipo = trim($row[$tipoIndex] ?? '');

            if (empty($nombre) || empty($tipo)) {
                continue;
            }

            $areaId = $areasMap[$tipo] ?? null;

            if (! $areaId) {
                continue;
            }

            // Buscar empleado por email o nombre
            $employee = null;

            if (! empty($email)) {
                $employee = Employee::withTrashed()
                    ->where('email', $email)
                    ->first();
            }

            if (! $employee) {
                $employee = Employee::withTrashed()
                    ->where('name', 'LIKE', '%'.$nombre.'%')
                    ->first();
            }

            if ($employee) {
                $employee->update([
                    'area_id' => $areaId,
                    'is_active' => true,
                    'active_at' => $employee->active_at ?? now(),
                ]);
                $employee->restore(); // Por si estaba soft deleted
                $actualizados++;
                $this->command->info("Empleado actualizado: {$employee->name} -> Área: {$tipo}");
            } else {
                $noEncontrados[] = "{$nombre} ({$email})";
            }
        }

        $this->command->newLine();
        $this->command->info('Resumen:');
        $this->command->info('- Áreas creadas: '.count($areasUnicas));
        $this->command->info("- Empleados actualizados: {$actualizados}");

        if (! empty($noEncontrados)) {
            $this->command->warn('Empleados no encontrados ('.count($noEncontrados).'):');
            foreach ($noEncontrados as $nf) {
                $this->command->warn("  - {$nf}");
            }
        }
    }
}
