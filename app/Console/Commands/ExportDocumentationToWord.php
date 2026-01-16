<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

class ExportDocumentationToWord extends Command
{
    protected $signature = 'docs:export-word';

    protected $description = 'Exportar la documentacion del sistema a un archivo Word (.docx)';

    /**
     * @return array<string, array{title: string, icon: string, color: string, description: string, group: string}>
     */
    private function getModules(): array
    {
        return [
            // General
            'overview' => [
                'title' => 'Resumen General',
                'group' => 'General',
            ],
            'authentication' => [
                'title' => 'Autenticacion',
                'group' => 'General',
            ],

            // Operaciones de Bodega
            'products' => [
                'title' => 'Catalogo de Productos',
                'group' => 'Operaciones de Bodega',
            ],
            'purchases' => [
                'title' => 'Compras',
                'group' => 'Operaciones de Bodega',
            ],
            'dte-imports' => [
                'title' => 'Importar DTE',
                'group' => 'Operaciones de Bodega',
            ],
            'donations' => [
                'title' => 'Donaciones',
                'group' => 'Operaciones de Bodega',
            ],
            'transfers' => [
                'title' => 'Traslados',
                'group' => 'Operaciones de Bodega',
            ],
            'dispatches' => [
                'title' => 'Despachos',
                'group' => 'Operaciones de Bodega',
            ],
            'adjustments' => [
                'title' => 'Ajustes de Inventario',
                'group' => 'Operaciones de Bodega',
            ],
            'closures' => [
                'title' => 'Cierres Mensuales',
                'group' => 'Operaciones de Bodega',
            ],

            // Catalogos
            'suppliers' => [
                'title' => 'Proveedores',
                'group' => 'Catalogos',
            ],
            'donors' => [
                'title' => 'Donantes',
                'group' => 'Catalogos',
            ],
            'customers' => [
                'title' => 'Clientes',
                'group' => 'Catalogos',
            ],

            // Gestion de Almacenes
            'companies' => [
                'title' => 'Empresas',
                'group' => 'Gestion de Almacenes',
            ],
            'branches' => [
                'title' => 'Sucursales',
                'group' => 'Gestion de Almacenes',
            ],
            'warehouses' => [
                'title' => 'Bodegas',
                'group' => 'Gestion de Almacenes',
            ],
            'storage-locations' => [
                'title' => 'Ubicaciones de Almacenamiento',
                'group' => 'Gestion de Almacenes',
            ],

            // Consultas e Inventario
            'inventory' => [
                'title' => 'Resumen de Inventario',
                'group' => 'Consultas e Inventario',
            ],
            'stock-query' => [
                'title' => 'Consulta de Existencias',
                'group' => 'Consultas e Inventario',
            ],
            'movements' => [
                'title' => 'Consulta de Movimientos',
                'group' => 'Consultas e Inventario',
            ],
            'alerts' => [
                'title' => 'Alertas de Stock',
                'group' => 'Consultas e Inventario',
            ],
            'traceability' => [
                'title' => 'Trazabilidad Historica',
                'group' => 'Consultas e Inventario',
            ],

            // Reporteria
            'reports' => [
                'title' => 'Reportes y Analisis',
                'group' => 'Reporteria',
            ],

            // Control de Usuarios
            'users' => [
                'title' => 'Gestion de Usuarios',
                'group' => 'Control de Usuarios',
            ],
            'roles' => [
                'title' => 'Gestion de Roles',
                'group' => 'Control de Usuarios',
            ],
            'permissions' => [
                'title' => 'Gestion de Permisos',
                'group' => 'Control de Usuarios',
            ],

            // Extras
            'shortcuts' => [
                'title' => 'Atajos de Teclado',
                'group' => 'Extras',
            ],
            'notifications' => [
                'title' => 'Notificaciones',
                'group' => 'Extras',
            ],
        ];
    }

    public function handle(): int
    {
        $this->info('Iniciando exportacion de documentacion a Word...');

        $phpWord = new PhpWord;

        // Configurar estilos por defecto
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        // Agregar estilos
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 24, 'color' => '1F2937']);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 18, 'color' => '374151']);
        $phpWord->addTitleStyle(3, ['bold' => true, 'size' => 14, 'color' => '4B5563']);

        $section = $phpWord->addSection();

        // Titulo principal
        $section->addTitle('DOCUMENTACION DEL SISTEMA DE GESTION DE BODEGAS', 1);
        $section->addTextBreak(1);
        $section->addText(
            'Manual completo de usuario para el sistema de gestion de bodegas',
            ['italic' => true, 'color' => '6B7280']
        );
        $section->addText('Generado el: '.now()->format('d/m/Y H:i'), ['size' => 10, 'color' => '9CA3AF']);
        $section->addTextBreak(2);

        $modules = $this->getModules();
        $currentGroup = '';
        $processedCount = 0;

        $this->output->progressStart(count($modules));

        foreach ($modules as $moduleKey => $moduleInfo) {
            // Si cambio de grupo, agregar encabezado de grupo
            if ($currentGroup !== $moduleInfo['group']) {
                $currentGroup = $moduleInfo['group'];
                $section->addPageBreak();
                $section->addTitle($currentGroup, 2);
                $section->addTextBreak(1);
            }

            // Agregar titulo del modulo
            $section->addTitle($moduleInfo['title'], 3);
            $section->addTextBreak(1);

            // Intentar cargar y procesar el contenido del modulo
            $viewPath = "livewire.help.modules.{$moduleKey}";

            if (View::exists($viewPath)) {
                try {
                    $html = View::make($viewPath)->render();
                    $cleanHtml = $this->cleanHtmlForWord($html);

                    if (! empty(trim($cleanHtml))) {
                        Html::addHtml($section, $cleanHtml, false, false);
                    }
                } catch (\Exception $e) {
                    $section->addText(
                        "Error al procesar este modulo: {$e->getMessage()}",
                        ['color' => 'DC2626']
                    );
                }
            } else {
                $section->addText('Contenido no disponible.', ['italic' => true, 'color' => '9CA3AF']);
            }

            $section->addTextBreak(2);
            $processedCount++;
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        // Crear directorio si no existe
        $exportPath = storage_path('app/exports');
        if (! File::exists($exportPath)) {
            File::makeDirectory($exportPath, 0755, true);
        }

        // Guardar el documento
        $filename = 'documentacion-sistema-'.now()->format('Y-m-d-His').'.docx';
        $fullPath = $exportPath.'/'.$filename;

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fullPath);

        $this->newLine();
        $this->info('Documento exportado exitosamente!');
        $this->info("Archivo: {$fullPath}");
        $this->info("Modulos procesados: {$processedCount}");

        return Command::SUCCESS;
    }

    private function cleanHtmlForWord(string $html): string
    {
        // Eliminar componentes Blade/Livewire que no se renderizan bien
        $html = preg_replace('/<flux:[^>]*\/?>/i', '', $html);
        $html = preg_replace('/<\/flux:[^>]*>/i', '', $html);

        // Eliminar atributos de Alpine.js y Livewire
        $html = preg_replace('/\s*(x-[a-z\-:]+|wire:[a-z\-:]+|@[a-z\.]+)="[^"]*"/i', '', $html);

        // Eliminar clases de Tailwind (para simplificar)
        $html = preg_replace('/\s*class="[^"]*"/i', '', $html);

        // Convertir algunos elementos comunes
        $html = preg_replace('/<section[^>]*>/i', '<div>', $html);
        $html = preg_replace('/<\/section>/i', '</div>', $html);

        // Limpiar divs vacios anidados
        $html = preg_replace('/<div>\s*<\/div>/i', '', $html);

        // Convertir strong/bold
        $html = str_replace(['<strong>', '</strong>'], ['<b>', '</b>'], $html);

        // Eliminar scripts y styles
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        // Limpiar espacios multiples
        $html = preg_replace('/\s+/', ' ', $html);

        return trim($html);
    }
}
