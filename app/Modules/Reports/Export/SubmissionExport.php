<?php

namespace App\Modules\Reports\Export;

use Maatwebsite\Excel\Concerns\{Exportable,WithEvents,FromCollection,ShouldAutoSize,WithHeadings};
use Maatwebsite\Excel\Events\AfterSheet;

class SubmissionExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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

    public function headings(): array
    {
        return $headings = [];
/*	        "Submission ID",
			"Submission Status",
			"Race",
			"State ID",
			"Last Name",
			"First Name",
			"Current School",
			"Zoned School",
			"First Choice",
			"Second Choice",
			"Sibling ID",
            "Lottery Number",
			// "Device Name / SIM Number / IMEI Number / MSISDN",
		];*/
    }

    public function registerEvents(): array
    {
    	return [
    		AfterSheet::class    => function(AfterSheet $event) {
    			$event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(25);
    			$styleArray = [
                    'font' => [
                        'family' => 'Open Sans',
                        'size' =>  13,
                        'bold' => true,
                        ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ];
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:Z1')->applyFromArray($styleArray);
    		}
        ];
    }

}
