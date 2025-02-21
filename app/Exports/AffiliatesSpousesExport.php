<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AffiliatesSpousesExport implements FromCollection, WithHeadings, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    /**
     * Define the headings for the Excel file.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'NUP',
            'CI TITULAR',
            'PRIMER NOMBRE',
            'SEGUNDO NOMBRE',
            'AP. PATERNO',
            'AP. MATERNO',
            'AP. CASADA',
            'FECHA DE INGRESO',
            'FECHA DE NACIMIENTO',
            'CI VIUDA(O)',
            'PRIMER NOMBRE',
            'SEGUNDO NOMBRE',
            'AP. PATERNO',
            'AP. MATERNO',
            'AP. CASADA',
            'FECHA REGISTRO VIUDA',
            'FECHA DE NACIMIENTO',
            'MATRÍCULA TITULAR'
        ];
    }

    /**
     * Define the styles for the Excel file.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        foreach (range('A', 'S') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('A1:S1')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(-1);
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $cell->getStyle()->getAlignment()->setWrapText(true);
            }
            if ($row->getRowIndex() != 1) {
                $sheet->getRowDimension($row->getRowIndex())->setRowHeight(-1);
            }
        }
        return [];
    }
}
