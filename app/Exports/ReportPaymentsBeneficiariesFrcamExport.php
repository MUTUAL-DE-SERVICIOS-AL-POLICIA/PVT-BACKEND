<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportPaymentsBeneficiariesFrcamExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStyles
{
    protected $data;
    protected $type;

    public function __construct(array $data, $type)
    {
        $this->data = $data;
        $this->type = $type;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        $headings = [
            'NRO',
            'COD_TRÁMITE',
            'TIPO_TRÁMITE',
            'MODALIDAD',
            'SIGLA',
            'FECHA_RECEPCIÓN',
            'REGIONAL',
            'NUP',
            'TIT_CI',
            'TIT_PRIMER_NOMBRE',
            'TIT_SEGUNDO_NOMBRE',
            'TIT_AP_PATERNO',
            'TIT_AP_MATERNO',
            'FECHA_INGRESO',
            'FECHA_DESVINCULACIÓN',
            'GRADO',
            'FECHA_NACIMIENTO',
            'FECHA_FALLECIMIENTO',
            'ESTADO_CIVIL',
            'GÉNERO',
        ];

        if ($this->type === 'Fondo de Retiro Policial') {
            $headings = array_merge($headings, [
                'TOTAL FONDO DE RETIRO',
                'TOTAL DISPONIBILIDAD',
                'FECHA RESOLUCIÓN',
                'FECHA LIQUIDACIÓN',
                'ANTICIPO FONDO DE RETIRO',
                'RETENCIÓN PAGO PRÉSTAMO',
                'RETENCIÓN GARANTES',
            ]);
        } else {
            $headings = array_merge($headings, [
                'FECHA RESOLUCIÓN',
                'FECHA LIQUIDACIÓN',
            ]);
        }

        $headings = array_merge($headings, [
            'BEN_CI',
            'BEN_PRIMER_NOMBRE',
            'BEN_SEGUNDO_NOMBRE',
            'BEN_AP_PATERNO',
            'BEN_AP_MATERNO',
            'BEN_PARENTESCO',
            'BEN_MONTO',
            'BEN_CIUDAD',
            'BEN_DIRECCIÓN',
            'BEN_TELÉFONO',
            'BEN_CELULAR',
            'UBICACIÓN',
        ]);

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();

        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
            'A1:' . $lastColumn . '1' => [
                'alignment' => [
                    'vertical' => 'center',
                    'wrapText' => true,
                ],
            ],
        ];
    }
}
