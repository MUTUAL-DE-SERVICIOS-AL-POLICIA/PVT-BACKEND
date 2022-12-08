<?php

namespace App\Exports;

use App\Models\Notification\NotificationSend;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\EconomicComplement\EconomicComplement;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class NotificacitonSendExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithCustomStartCell, WithColumnWidths
{
    use Exportable;

    // public function __construct($start_date, $end_date)
    // {
    //     $this->start_date = $start_date;
    //     $this->end_date = $end_date;
    // }
    public function __construct($data, $type) {
        $this->count = count($data);
        $this->data = $data;
        $this->type = $type;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->data;
    }

    public function startCell(): string
    {
        return 'B2';
    }

    public function headings(): array {
        if($this->type == 1) {
            return [
                'USUARIO',
                'ESTADO',
                'SMS/APP',
                'TIPO',
                'CÓDIGO',
                'NUP',
                'FECHA DE ENVÍO',
                'MENSAJE',   
            ];
        } else {
            return [
                'USUARIO',
                'ESTADO',
                'SMS/APP',
                'NÚMERO',
                'TIPO',
                'CÓDIGO',
                'NUP',
                'FECHA DE ENVÍO',
                'MENSAJE',
            ];
        }
    }

    public function styles(Worksheet $sheet)
    {        
        $rows = $this->count + 2;
        if($this->type == 1) {
            $cels = 'B2:I2';
            $line = 'B2:I';
        } else {
            $cels = 'B2:J2';
            $line = 'B2:J';
        }
        $return =  [            
            $cels => [
                'font' => [
                    'bold' => true, 
                    'italic' => true
                ], 
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ],
                'fill' => [
                    'fillType' => Fill::FILL_GRADIENT_LINEAR,
                    'rotation' => 90,
                    'startColor' => [
                        'argb' => 'FFA0A0A0',
                    ],
                    'endColor' => [
                        'argb' => 'FFFFFFFF',
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'wrapText' => true,
                ],
            ],
            $line.$rows => [
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN
                    ],                
                ]
            ],
            'B2:B'.$rows => [
                'borders' => [
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ]
            ],
            'C2:C'.$rows => [
                'borders' => [
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ]
            ],
            'D2:D'.$rows => [
                'borders' => [
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ]
            ],
            'E2:E'.$rows => [
                'borders' => [
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ]
            ],
            'F2:F'.$rows => [
                'borders' => [
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ]
            ],
            'G2:G'.$rows => [
                'borders' => [
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ]
            ],
            'H2:H'.$rows => [
                'borders' => [
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ]
            ],
            'I' => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
        if($this->type == 2) {
            $return['I2:I'.$rows] = [
                'borders' => [
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ]
            ];
        }
        logger($return);
        return $return;
    }

    public function columnWidths(): array
    {
        if($this->type == 1) {
            return [
                'I' => 60,            
            ];
        } else {
            return [
                'J' => 60,
            ];
        }
    }
}
