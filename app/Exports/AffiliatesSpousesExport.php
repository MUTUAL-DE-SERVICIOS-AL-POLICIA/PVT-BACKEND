<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class AffiliatesSpousesExport implements FromGenerator, WithHeadings, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function generator(): \Generator
    {
        $counter = 1;
        foreach ($this->data->cursor() as $row) {
            yield [
                $counter++,
                $row->nup,
                $row->identity_card,
                $row->first_name,
                $row->second_name,
                $row->last_name,
                $row->mothers_last_name,
                $row->surname_husband,
                $row->date_entry,
                $row->birth_date,
                $row->spouse_identity_card,
                $row->spouse_first_name,
                $row->spouse_second_name,
                $row->spouse_last_name,
                $row->spouse_mothers_last_name,
                $row->spouse_surname_husband,
                $row->spouse_create_date,
                $row->spouse_birth_date,
                $row->registration,
                $row->registration_spouse
            ];
        }
    }

    public function headings(): array
    {
        return [
            'NRO',
            'NUP',
            'CI TITULAR',
            'PRIMER NOMBRE TITULAR',
            'SEGUNDO NOMBRE TITULAR',
            'AP. PATERNO TITULAR',
            'AP. MATERNO TITULAR',
            'AP. CASADA TITULAR',
            'FECHA DE INGRESO TITULAR',
            'FECHA DE NACIMIENTO TITULAR',
            'CI CÓNYUGE',
            'PRIMER NOMBRE CÓNYUGE',
            'SEGUNDO NOMBRE CÓNYUGE',
            'AP. PATERNO CÓNYUGE',
            'AP. MATERNO CÓNYUGE',
            'AP. CASADA CÓNYUGE',
            'FECHA REGISTRO CÓNYUGE',
            'FECHA DE NACIMIENTO CÓNYUGE',
            'MATRÍCULA TITULAR',
            'MATRÍCULA CÓNYUGE'
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
        $headings = $this->headings();
        $columnCount = count($headings);
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);

        foreach (range(1, $columnCount) as $colIndex) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $headerRange = "A1:{$lastColumn}1";
        $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(-1);
        return [];
    }
}
