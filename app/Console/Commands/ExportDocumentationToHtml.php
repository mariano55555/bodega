<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class ExportDocumentationToHtml extends Command
{
    protected $signature = 'docs:export-html';

    protected $description = 'Exportar la documentacion del sistema a un archivo HTML estatico';

    /**
     * @return array<string, array{title: string, group: string}>
     */
    private function getModules(): array
    {
        return [
            'overview' => ['title' => 'Resumen General', 'group' => 'General'],
            'authentication' => ['title' => 'Autenticacion', 'group' => 'General'],
            'products' => ['title' => 'Catalogo de Productos', 'group' => 'Operaciones de Bodega'],
            'purchases' => ['title' => 'Compras', 'group' => 'Operaciones de Bodega'],
            'dte-imports' => ['title' => 'Importar DTE', 'group' => 'Operaciones de Bodega'],
            'donations' => ['title' => 'Donaciones', 'group' => 'Operaciones de Bodega'],
            'transfers' => ['title' => 'Traslados', 'group' => 'Operaciones de Bodega'],
            'dispatches' => ['title' => 'Despachos', 'group' => 'Operaciones de Bodega'],
            'adjustments' => ['title' => 'Ajustes de Inventario', 'group' => 'Operaciones de Bodega'],
            'closures' => ['title' => 'Cierres Mensuales', 'group' => 'Operaciones de Bodega'],
            'suppliers' => ['title' => 'Proveedores', 'group' => 'Catalogos'],
            'donors' => ['title' => 'Donantes', 'group' => 'Catalogos'],
            'customers' => ['title' => 'Clientes', 'group' => 'Catalogos'],
            'companies' => ['title' => 'Empresas', 'group' => 'Gestion de Almacenes'],
            'branches' => ['title' => 'Sucursales', 'group' => 'Gestion de Almacenes'],
            'warehouses' => ['title' => 'Bodegas', 'group' => 'Gestion de Almacenes'],
            'storage-locations' => ['title' => 'Ubicaciones de Almacenamiento', 'group' => 'Gestion de Almacenes'],
            'inventory' => ['title' => 'Resumen de Inventario', 'group' => 'Consultas e Inventario'],
            'stock-query' => ['title' => 'Consulta de Existencias', 'group' => 'Consultas e Inventario'],
            'movements' => ['title' => 'Consulta de Movimientos', 'group' => 'Consultas e Inventario'],
            'alerts' => ['title' => 'Alertas de Stock', 'group' => 'Consultas e Inventario'],
            'traceability' => ['title' => 'Trazabilidad Historica', 'group' => 'Consultas e Inventario'],
            'reports' => ['title' => 'Reportes y Analisis', 'group' => 'Reporteria'],
            'users' => ['title' => 'Gestion de Usuarios', 'group' => 'Control de Usuarios'],
            'roles' => ['title' => 'Gestion de Roles', 'group' => 'Control de Usuarios'],
            'permissions' => ['title' => 'Gestion de Permisos', 'group' => 'Control de Usuarios'],
            'shortcuts' => ['title' => 'Atajos de Teclado', 'group' => 'Extras'],
            'notifications' => ['title' => 'Notificaciones', 'group' => 'Extras'],
        ];
    }

    public function handle(): int
    {
        $this->info('Iniciando exportacion de documentacion a HTML...');

        $modules = $this->getModules();
        $processedCount = 0;
        $currentGroup = '';

        // Construir tabla de contenidos
        $toc = $this->buildTableOfContents($modules);

        // Construir contenido
        $content = '';
        $this->output->progressStart(count($modules));

        foreach ($modules as $moduleKey => $moduleInfo) {
            if ($currentGroup !== $moduleInfo['group']) {
                $currentGroup = $moduleInfo['group'];
                $groupId = $this->slugify($currentGroup);
                $content .= "<h2 id=\"{$groupId}\" style=\"color: #1f2937; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-top: 40px;\">{$currentGroup}</h2>\n";
            }

            $moduleId = $this->slugify($moduleKey);
            $content .= "<h3 id=\"{$moduleId}\" style=\"color: #374151; margin-top: 30px;\">{$moduleInfo['title']}</h3>\n";

            $viewPath = "livewire.help.modules.{$moduleKey}";

            if (View::exists($viewPath)) {
                try {
                    $html = View::make($viewPath)->render();
                    $cleanHtml = $this->cleanHtml($html);
                    $content .= "<div style=\"margin-left: 20px;\">{$cleanHtml}</div>\n";
                } catch (\Exception $e) {
                    $content .= "<p style=\"color: #dc2626;\">Error al procesar este modulo: {$e->getMessage()}</p>\n";
                }
            } else {
                $content .= "<p style=\"color: #9ca3af; font-style: italic;\">Contenido no disponible.</p>\n";
            }

            $processedCount++;
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        // Construir documento HTML completo
        $html = $this->buildHtmlDocument($toc, $content);

        // Crear directorio si no existe
        $exportPath = storage_path('app/exports');
        if (! File::exists($exportPath)) {
            File::makeDirectory($exportPath, 0755, true);
        }

        // Guardar el documento
        $filename = 'documentacion-sistema-'.now()->format('Y-m-d-His').'.html';
        $fullPath = $exportPath.'/'.$filename;

        File::put($fullPath, $html);

        $this->newLine();
        $this->info('Documento exportado exitosamente!');
        $this->info("Archivo: {$fullPath}");
        $this->info("Modulos procesados: {$processedCount}");

        return Command::SUCCESS;
    }

    private function buildTableOfContents(array $modules): string
    {
        $toc = "<div style=\"background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 30px;\">\n";
        $toc .= "<h2 style=\"margin-top: 0; color: #1f2937;\">Tabla de Contenidos</h2>\n";
        $toc .= "<ul style=\"list-style: none; padding-left: 0;\">\n";

        $currentGroup = '';
        foreach ($modules as $moduleKey => $moduleInfo) {
            if ($currentGroup !== $moduleInfo['group']) {
                if ($currentGroup !== '') {
                    $toc .= "</ul></li>\n";
                }
                $currentGroup = $moduleInfo['group'];
                $groupId = $this->slugify($currentGroup);
                $toc .= "<li style=\"margin-top: 15px;\"><a href=\"#{$groupId}\" style=\"font-weight: bold; color: #1f2937; text-decoration: none;\">{$currentGroup}</a>\n";
                $toc .= "<ul style=\"list-style: disc; padding-left: 25px; margin-top: 5px;\">\n";
            }

            $moduleId = $this->slugify($moduleKey);
            $toc .= "<li><a href=\"#{$moduleId}\" style=\"color: #4b5563; text-decoration: none;\">{$moduleInfo['title']}</a></li>\n";
        }

        $toc .= "</ul></li>\n";
        $toc .= "</ul>\n</div>\n";

        return $toc;
    }

    private function buildHtmlDocument(string $toc, string $content): string
    {
        $date = now()->format('d/m/Y H:i');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentacion del Sistema de Gestion de Bodegas</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            background: #fff;
        }
        h1 { color: #111827; font-size: 2em; margin-bottom: 10px; }
        h2 { color: #1f2937; font-size: 1.5em; }
        h3 { color: #374151; font-size: 1.25em; }
        h4 { color: #4b5563; font-size: 1.1em; }
        p { margin: 10px 0; }
        ul, ol { margin: 10px 0; padding-left: 25px; }
        li { margin: 5px 0; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 10px;
            text-align: left;
        }
        th { background: #f3f4f6; font-weight: 600; }
        tr:nth-child(even) { background: #f9fafb; }
        .header {
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .subtitle { color: #6b7280; font-style: italic; margin: 5px 0; }
        .date { color: #9ca3af; font-size: 0.9em; }
        .card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .card-title { font-weight: 600; margin-bottom: 5px; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .callout {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid;
        }
        .callout-info { background: #eff6ff; border-color: #3b82f6; }
        .callout-warning { background: #fffbeb; border-color: #f59e0b; }
        .callout-success { background: #f0fdf4; border-color: #22c55e; }
        a { color: #3b82f6; }
        @media print {
            body { max-width: 100%; padding: 20px; }
            .header { page-break-after: avoid; }
            h2 { page-break-before: always; page-break-after: avoid; }
            h3 { page-break-after: avoid; }
            .card, table { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DOCUMENTACION DEL SISTEMA DE GESTION DE BODEGAS</h1>
        <p class="subtitle">Manual completo de usuario para el sistema de gestion de bodegas</p>
        <p class="date">Generado el: {$date}</p>
    </div>

    {$toc}

    {$content}

    <hr style="margin-top: 50px; border: none; border-top: 1px solid #e5e7eb;">
    <p style="text-align: center; color: #9ca3af; font-size: 0.9em;">
        Documento generado automaticamente - Sistema de Gestion de Bodegas
    </p>
</body>
</html>
HTML;
    }

    private function cleanHtml(string $html): string
    {
        // Eliminar componentes Flux UI y reemplazarlos con equivalentes HTML
        $html = preg_replace_callback('/<flux:heading[^>]*size="([^"]*)"[^>]*>(.*?)<\/flux:heading>/is', function ($matches) {
            $size = $matches[1];
            $content = $matches[2];
            $tag = match ($size) {
                'xl' => 'h2',
                'lg' => 'h3',
                'md' => 'h4',
                'sm' => 'h5',
                default => 'h4',
            };

            return "<{$tag}>{$content}</{$tag}>";
        }, $html);

        // flux:text -> p
        $html = preg_replace('/<flux:text[^>]*>(.*?)<\/flux:text>/is', '<p>$1</p>', $html);

        // flux:card -> div.card
        $html = preg_replace('/<flux:card[^>]*>/i', '<div class="card">', $html);
        $html = preg_replace('/<\/flux:card>/i', '</div>', $html);

        // flux:badge -> span.badge
        $html = preg_replace_callback('/<flux:badge[^>]*color="([^"]*)"[^>]*>(.*?)<\/flux:badge>/is', function ($matches) {
            $color = $matches[1];
            $content = $matches[2];
            $class = match ($color) {
                'green' => 'badge badge-green',
                'red' => 'badge badge-red',
                'blue' => 'badge badge-blue',
                'yellow' => 'badge badge-yellow',
                default => 'badge',
            };

            return "<span class=\"{$class}\">{$content}</span>";
        }, $html);

        // flux:callout -> div.callout
        $html = preg_replace_callback('/<flux:callout[^>]*variant="([^"]*)"[^>]*>(.*?)<\/flux:callout>/is', function ($matches) {
            $variant = $matches[1];
            $content = $matches[2];
            $class = match ($variant) {
                'info' => 'callout callout-info',
                'warning' => 'callout callout-warning',
                'success' => 'callout callout-success',
                default => 'callout',
            };

            return "<div class=\"{$class}\">{$content}</div>";
        }, $html);

        // flux:icon -> (eliminar, solo decorativos)
        $html = preg_replace('/<flux:icon[^>]*\/?>/i', '', $html);

        // Eliminar otros componentes flux restantes
        $html = preg_replace('/<flux:[^>]*\/?>/i', '', $html);
        $html = preg_replace('/<\/flux:[^>]*>/i', '', $html);

        // Eliminar atributos de Alpine.js y Livewire
        $html = preg_replace('/\s*(x-[a-z\-:]+|wire:[a-z\-:]+|@[a-z\.]+)="[^"]*"/i', '', $html);

        // Eliminar clases de Tailwind
        $html = preg_replace('/\s*class="[^"]*"/i', '', $html);

        // section -> div
        $html = preg_replace('/<section[^>]*>/i', '<div>', $html);
        $html = preg_replace('/<\/section>/i', '</div>', $html);

        // Limpiar divs vacios
        $html = preg_replace('/<div>\s*<\/div>/i', '', $html);

        // Eliminar scripts
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);

        // Eliminar SVGs
        $html = preg_replace('/<svg\b[^>]*>(.*?)<\/svg>/is', '', $html);

        // Limpiar espacios multiples pero mantener saltos de linea
        $html = preg_replace('/[ \t]+/', ' ', $html);
        $html = preg_replace('/\n\s*\n/', "\n", $html);

        return trim($html);
    }

    private function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);

        return trim($text, '-');
    }
}
