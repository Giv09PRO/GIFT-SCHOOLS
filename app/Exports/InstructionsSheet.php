<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class defining the instructions sheet
 */
class InstructionsSheet implements FromArray, WithTitle, WithStyles
{
    public function array(): array
    {
        // *** UPDATED Instructions ***
        return [
            ['Column Header', 'Required?', 'Description / Example'],
            ['last_name', 'Yes', 'Student\'s Last Name'],
            ['first_name', 'Yes', 'Student\'s First Name'],
            ['gender', 'Yes', 'Student\'s Gender (e.g., Male, Female)'],
            ['username', 'Yes*', 'Unique username for the student. *Required if Permanent Number is not used as the primary identifier.'],
            ['prem_number', 'Yes*', 'Unique Permanent Number (Student ID). *Required if Username is not used as the primary identifier.'],
            ['syear', 'Yes', 'The School Year (e.g., 2024) this enrollment record applies to.'],
            ['school_identifier', 'Yes', 'The Name (e.g., "Central High") OR the Database ID (e.g., 1) of the school for the specified School Year.'],
            ['grade_identifier', 'Yes', 'The Title (e.g., "Grade 9") OR the Database ID (e.g., 15) of the grade level within the specified School and Year.'],
            ['start_date', 'Yes', 'The first day of the student\'s enrollment for this record (Format: YYYY-MM-DD).'],
            ['middle_name', 'No', 'Student\'s Middle Name (Optional)'],
            ['name_suffix', 'No', 'Name Suffix (e.g., Jr., III) (Optional)'],
            ['dob', 'No', 'Date of Birth (Format: YYYY-MM-DD) (Optional)'],
            ['email', 'No', 'Student\'s Email Address (Must be unique if provided) (Optional)'],
            ['phone', 'No', 'Student\'s Phone Number (Optional)'],
            ['address', 'No', 'Student\'s Full Address (Optional)'],
            ['enrollment_code', 'No', 'Enrollment Code (e.g., New, Transfer, Re-enroll) (Optional)'],
            ['custom_family_id', 'No', 'Family ID, if applicable (Optional)'],
            // ['custom_field_5', 'No', 'Description of custom field 5 (Optional)'],
            ['---', '---', '---'],
            ['IMPORTANT NOTES:', '', ''],
            ['', '', '1. Do not change the header row (Row 1) in the "Student Import Template" sheet.'],
            ['', '', '2. Enter student data starting from Row 2.'],
            ['', '', '3. The importer will try to find existing students based on Username or Permanent Number to update them. Otherwise, a new student will be created.'],
            ['', '', '4. An enrollment record will be created/updated for the specified School Year, School, and Grade for each student.'],
            ['', '', '5. Use School Name/Grade Title OR their Database IDs for `school_identifier` and `grade_identifier`.'],
            ['', '', '6. Dates MUST be in YYYY-MM-DD format.'],
            ['', '', '7. Save the file as .xlsx or .csv before uploading.'],
            ['', '', '8. For Admins: If you select override options on the upload form, those values will be used instead of the data in the corresponding file columns.'],
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Instructions';
    }

    /**
     * Style the instruction sheet.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the first row (headers)
            1 => ['font' => ['bold' => true]],
            // Make notes section bold
            'A15' => ['font' => ['bold' => true]],
            'A16' => ['font' => ['bold' => true]],
        ];
    }
}
