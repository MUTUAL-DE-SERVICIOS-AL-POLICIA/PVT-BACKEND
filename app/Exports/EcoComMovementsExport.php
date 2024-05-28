<?php
namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EcoComMovementsExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
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
            'Nup',
            'Concepto',
            'Nombre Completo',
            'CI',
            'Monto',
            'Deuda pendiente'
        ];
    }

    /**
     * Define the column widths for the Excel file.
     *
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 25,
            'C' => 40,
            'D' => 20,
            'E' => 15,
            'F' => 20,
            // Agrega aquí los anchos de columna que necesites
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
        $sheet->getDefaultColumnDimension()->setAutoSize(false);
        return [];
    }
}
