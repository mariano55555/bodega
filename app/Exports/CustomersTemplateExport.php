<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'CLI-001',
                'Empresa Ejemplo S.A.',
                'empresa',
                'Empresa Ejemplo S.A. de C.V.',
                '0614-111111-111-1',
                'contacto@ejemplo.com',
                '2222-1111',
                '7777-1111',
                'www.ejemplo.com',
                'Colonia Escalón #100',
                'San Salvador',
                'San Salvador',
                'El Salvador',
                '01101',
                'Colonia Escalón #100',
                'San Salvador',
                'San Salvador',
                'El Salvador',
                '01101',
                'Si',
                'Carlos Martínez',
                '7788-1234',
                'carlos@ejemplo.com',
                'Gerente de Compras',
                '30 días',
                'transferencia',
                'USD',
                10000.00,
                5.00,
                'Cliente corporativo principal',
            ],
            [
                'CLI-002',
                'Juan López',
                'individual',
                '',
                '01234567-8',
                'juan.lopez@email.com',
                '2233-5566',
                '7766-5544',
                '',
                'Residencial Las Flores #50',
                'Santa Tecla',
                'La Libertad',
                'El Salvador',
                '01501',
                '',
                '',
                '',
                '',
                '',
                'No',
                '',
                '',
                '',
                '',
                'contado',
                'efectivo',
                'USD',
                0,
                0,
                'Cliente individual',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Código *',
            'Nombre *',
            'Tipo * (empresa/individual/gobierno/ong)',
            'Razón Social',
            'NIT/DUI',
            'Correo Electrónico',
            'Teléfono',
            'Celular',
            'Sitio Web',
            'Dirección Facturación',
            'Ciudad Facturación',
            'Departamento Facturación',
            'País Facturación',
            'Código Postal Facturación',
            'Dirección Envío',
            'Ciudad Envío',
            'Departamento Envío',
            'País Envío',
            'Código Postal Envío',
            'Mismo que Facturación (Si/No)',
            'Persona de Contacto',
            'Teléfono Contacto',
            'Correo Contacto',
            'Cargo Contacto',
            'Términos de Pago',
            'Método de Pago',
            'Moneda',
            'Límite de Crédito',
            '% Descuento',
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
