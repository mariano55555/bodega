<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DonorsTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'Fundación Ayuda',
                'Fundación Ayuda El Salvador',
                'organizacion',
                '0614-999999-999-9',
                'donaciones@fundacionayuda.org',
                '2555-6666',
                'www.fundacionayuda.org',
                'Avenida Solidaridad #200',
                'San Salvador',
                'San Salvador',
                'El Salvador',
                '01101',
                'Ana Rodríguez',
                '7700-1122',
                'ana@fundacionayuda.org',
                5,
                'Donante corporativo principal',
            ],
            [
                'Roberto Hernández',
                '',
                'individual',
                '01234567-8',
                'roberto.h@email.com',
                '7788-9900',
                '',
                'Colonia San Benito',
                'San Salvador',
                'San Salvador',
                'El Salvador',
                '01101',
                '',
                '',
                '',
                4,
                'Donante individual recurrente',
            ],
            [
                'Gobierno Municipal',
                'Alcaldía Municipal de San Salvador',
                'gobierno',
                '0614-000000-000-0',
                'donaciones@alcaldia.gob.sv',
                '2222-0000',
                'www.alcaldia.gob.sv',
                'Plaza Barrios, Centro Histórico',
                'San Salvador',
                'San Salvador',
                'El Salvador',
                '01101',
                'Pedro González',
                '2222-0001',
                'pedro.g@alcaldia.gob.sv',
                5,
                'Donaciones de gobierno local',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Nombre *',
            'Razón Social',
            'Tipo * (individual/organizacion/empresa/gobierno)',
            'NIT/DUI',
            'Correo Electrónico',
            'Teléfono',
            'Sitio Web',
            'Dirección',
            'Ciudad',
            'Departamento',
            'País',
            'Código Postal',
            'Persona de Contacto',
            'Teléfono Contacto',
            'Correo Contacto',
            'Calificación (1-5)',
            'Notas',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => Color::COLOR_WHITE],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F46E5'],
                ],
            ],
        ];
    }
}
