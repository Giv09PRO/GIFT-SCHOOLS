<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class defining the main template sheet
 */
class StudentTemplateSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    public function array(): array
    {
        // Return an empty array - we only want the headers for the template
        return [];
    }

    public function headings(): array
    {
        // Define the exact column headers expected in the import file
        // *** UPDATED to include enrollment columns ***
        return [
            // Required Student Fields
            'last_name',        // Required
            'first_name',       // Required
            'gender',           // Required (e.g., Male, Female)
            // Unique Identifier (at least one required)
            'username',         // Required if no Permanent Number
            'prem_number',      // Required if no Username
            // Required Enrollment Fields (from file)
            'syear',            // Required (e.g., 2024) - Year for this enrollment
            'school_identifier',// Required (Provide School Name OR School ID for the syear)
            'grade_identifier', // Required (Provide Grade Title OR Grade ID for the school/syear)
            'start_date',       // Required (Format: YYYY-MM-DD) - Enrollment start date
            // Optional Student Fields
            'middle_name',      // Optional
            'name_suffix',      // Optional
            'dob',              // Optional (Format: YYYY-MM-DD)
            'email',            // Optional (Must be unique if provided)
            'phone',            // Optional
            'address',          // Optional
            // Optional Enrollment Fields (from file)
            'enrollment_code',  // Optional
            // Custom Fields (Add headers for your specific custom fields)
            'custom_family_id', // Example: custom_200000004
            // 'custom_field_5',   // Example: custom_200000005
            // ... etc
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Student Import Template';
    }

    /**
     * Define specific column formats.
     * Adjust column letters based on the new headings order.
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER,      // syear (Year) - Treat as number
            'H' => NumberFormat::FORMAT_TEXT,        // grade_identifier - Treat as text
            'I' => NumberFormat::FORMAT_DATE_YYYYMMDD, // start_date
            'L' => NumberFormat::FORMAT_DATE_YYYYMMDD, // dob
            // Add formats for other columns if needed (e.g., text for IDs)
            'E' => NumberFormat::FORMAT_TEXT, // prem_number as Text
            'D' => NumberFormat::FORMAT_TEXT, // username as Text
        ];
    }

    /**
     * Style the header row.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the first row (headers)
            1 => ['font' => ['bold' => true]],
        ];
    }
}
