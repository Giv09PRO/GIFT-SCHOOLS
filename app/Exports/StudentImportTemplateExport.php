<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
//use Maatwebsite\Excel\Concerns\WithTitle;
//use Maatwebsite\Excel\Concerns\FromArray;
//use Maatwebsite\Excel\Concerns\WithHeadings;
//use Maatwebsite\Excel\Concerns\ShouldAutoSize;
//use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
//use Maatwebsite\Excel\Concerns\WithColumnFormatting;
//use Maatwebsite\Excel\Concerns\WithStyles; // Import for styling
//use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet; // Import for styling

class StudentImportTemplateExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        // First sheet: The actual template with headers
        $sheets[] = new StudentTemplateSheet();
        // Second sheet: Instructions/Legend
        $sheets[] = new InstructionsSheet();
        return $sheets;
    }
}
