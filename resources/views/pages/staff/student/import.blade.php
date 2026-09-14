{{-- resources/views/pages/staff/student/import.blade.php --}}

@extends('layouts.app') {{-- Use your main layout file --}}

@php
    // Variables passed from controller:
    // $requiredDbFields, $optionalDbFields (for legend)
    // $hasElevatedPrivileges (boolean)
    // $years (Collection - only if elevated)
    // $schools (Collection - only if elevated)
    // $grades (Collection - only if elevated)
    // $currentYear (for default)
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Import Students')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">

        {{-- Display Session Flash Messages --}}
        @include('layouts.partials.alerts')

        {{-- Display Import-Specific Errors from previous attempt (if any) --}}
        @if(session('import_errors'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <strong>Import Errors Encountered on Previous Attempt:</strong>
                <ul class="mb-0 mt-2" style="max-height: 200px; overflow-y: auto;">
                    @foreach(session('import_errors') as $rowNum => $errors)
                        <li><strong>Row {{ $rowNum }}:</strong> {{ implode('; ', $errors) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Display Validation Errors from Laravel Excel --}}
        @if($errors->hasBag('importValidation'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <strong>Import Validation Failed:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->importValidation->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <p class="mt-2 mb-0">Please correct the errors in your file and try again, or <a href="{{ route('staff.students.import.template') }}" class="alert-link">download the template</a>.</p>
            </div>
        @endif


        <div class="row">
            {{-- Form Column --}}
            <div class="col-md-8">
                <div class="card card-warning card-outline">
                    <div class="card-header d-flex justify-content-between align-items-center"> {{-- Use flex for alignment --}}
                        <h3 class="card-title mb-0">Upload Student Data File</h3>
                        {{-- Template Download Link/Button --}}
                        <div class="card-tools">
                            <a href="{{ route('staff.students.import.template') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file-excel mr-1"></i> Download Excel Template
                            </a>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('staff.students.import.process') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">

                            {{-- Instructions --}}
                            <p class="text-muted">Upload an Excel (xlsx, xls) or CSV file. The file **must** contain columns for required student and enrollment information (see Required Fields legend). The system will create new students and enroll them based on the file data.</p>
                            <p class="text-muted">Existing students (matched by Username or Permanent Number) will be updated with the data from the file, and a new enrollment record for the specified year/school/grade will be created if one doesn't already exist.</p>

                            <hr>

                            {{-- File Upload --}}
                            <div class="form-group mb-4"> {{-- Increased bottom margin --}}
                                <label for="student_import_file">Select File <span class="text-danger">*</span></label>
                                <input type="file" name="student_import_file" id="student_import_file" class="form-control @error('student_import_file') is-invalid @enderror" required accept=".xlsx, .xls, .csv">
                                <small class="form-text text-muted">Allowed formats: XLSX, XLS, CSV. Max size: 5MB.</small>
                                @error('student_import_file')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Optional Overrides for Elevated Users --}}
                            @if($hasElevatedPrivileges)
                                <hr>
                                <h5 class="text-primary">Enrollment Overrides (Optional - For Admins)</h5>
                                <p class="text-muted">You can optionally select a year, school, and grade here to apply to **all** students in the uploaded file, overriding any corresponding data within the file itself. Leave blank to use the data from the file for each student.</p>

                                <div class="row">
                                    {{-- Override School Year --}}
                                    <div class="col-md-4 form-group mb-3">
                                        <label for="override_syear">Override School Year</label>
                                        <select name="override_syear" id="override_syear" class="form-control select2 @error('override_syear') is-invalid @enderror">
                                            <option value="">(Use Year From File)</option>
                                            @foreach($years as $year)
                                                <option value="{{ $year }}" {{ old('override_syear') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                        @error('override_syear') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Override School --}}
                                    <div class="col-md-4 form-group mb-3">
                                        <label for="override_school_id">Override School</label>
                                        <select name="override_school_id" id="override_school_id" class="form-control select2 @error('override_school_id') is-invalid @enderror">
                                            <option value="">(Use School From File)</option>
                                            {{-- Options populated by JS based on selected year --}}
                                            {{-- Initial population (optional, can be empty) --}}
                                            @php $schoolsGrouped = $schools->groupBy('syear'); @endphp
                                            @foreach($schoolsGrouped as $year => $schoolsInYear)
                                                <optgroup label="Year: {{ $year }}">
                                                    @foreach($schoolsInYear as $school)
                                                        <option value="{{ $school->id }}" data-year="{{ $school->syear }}" {{ old('override_school_id') == $school->id ? 'selected' : '' }}>
                                                            {{ $school->title }} ({{ $year }})
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        @error('override_school_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Override Grade --}}
                                    <div class="col-md-4 form-group mb-3">
                                        <label for="override_grade_id">Override Grade Level</label>
                                        <select name="override_grade_id" id="override_grade_id" class="form-control select2 @error('override_grade_id') is-invalid @enderror">
                                            <option value="">(Use Grade From File)</option>
                                            {{-- Options populated by JS based on selected school/year --}}
                                            @php $gradesGrouped = $grades->groupBy(['school_syear', 'school.title']); @endphp
                                            @foreach($gradesGrouped as $year => $schoolsWithGrades)
                                                <optgroup label="Year: {{ $year }}">
                                                    @foreach($schoolsWithGrades as $schoolTitle => $gradesInSchool)
                                                        <option disabled style="font-weight:bold; background-color:#e9ecef;">&nbsp;&nbsp;{{ $schoolTitle }}</option>
                                                        @foreach($gradesInSchool as $grade)
                                                            <option value="{{ $grade->id }}" data-school-id="{{ $grade->school_id }}" data-year="{{ $grade->school_syear }}" {{ old('override_grade_id') == $grade->id ? 'selected' : '' }}>
                                                                &nbsp;&nbsp;&nbsp;&nbsp;{{ $grade->title }}
                                                            </option>
                                                        @endforeach
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        @error('override_grade_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                {{-- Validation error specifically for override combinations --}}
                                @error('override_grade_id')
                                @if(Str::contains($message, 'not valid for the selected school and year'))
                                    <div class="text-danger mb-2 small"><strong>Override Error: {{ $message }}</strong></div>
                                @endif
                                @enderror
                                @error('override_school_id')
                                @if(Str::contains($message, 'not valid for the selected year'))
                                    <div class="text-danger mb-2 small"><strong>Override Error: {{ $message }}</strong></div>
                                @endif
                                @enderror
                            @endif
                            {{-- End Optional Overrides --}}

                        </div>
                        <div class="card-footer text-center border-top pt-3">
                            <button type="submit" class="btn btn-warning btn-lg"> {{-- Larger button --}}
                                <i class="fas fa-cloud-upload-alt mr-1"></i> Upload & Proceed to Mapping
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Legend Column --}}
            <div class="col-md-4">
                <div class="card card-secondary">
                    <div class="card-header"><h3 class="card-title">Required File Columns</h3></div>
                    <div class="card-body">
                        <p>Your import file **must** contain columns that can be mapped to the following database fields (unless providing an override above):</p>
                        <ul>
                            @foreach($requiredDbFields as $key => $label)
                                <li><strong>{{ $label }}</strong> (<code>{{ $key }}</code>)</li>
                            @endforeach
                        </ul>
                        <p>Use the <a href="{{ route('staff.students.import.template') }}">provided template</a> for the correct format.</p>
                    </div>
                </div>
                <div class="card card-secondary">
                    <div class="card-header"><h3 class="card-title">Optional File Columns</h3></div>
                    <div class="card-body">
                        <p>You can also include columns for:</p>
                        <ul>
                            @foreach($optionalDbFields as $key => $label)
                                <li><strong>{{ $label }}</strong> (<code>{{ $key }}</code>)</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('scripts')
    {{-- Include scripts for Select2 if not already in layout --}}
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script> {{-- Adjust path as needed --}}
    <script>
        $(function () {
            // Initialize Select2 Elements
            $('.select2').select2({
                theme: 'bootstrap4', // Optional: Use Bootstrap 4 theme
                allowClear: true, // Allow clearing selection for overrides
                placeholder: $(this).data('placeholder') || 'Select...'
            });

            // --- Dynamic Filtering for Override Dropdowns ---
            @if($hasElevatedPrivileges)
            const allSchools = @json($schools->keyBy('id')); // Key by ID
            const allGrades = @json($grades->keyBy('id')); // Key by ID

            function filterOverrideSchools() {
                const selectedYear = $('#override_syear').val();
                const $schoolSelect = $('#override_school_id');
                const currentSchoolVal = $schoolSelect.val();
                // Preserve the currently selected value if it's still valid for the new year
                let preservedValue = null;
                // Check if currentSchoolVal exists in allSchools before accessing its properties
                if (currentSchoolVal && allSchools[currentSchoolVal] && allSchools[currentSchoolVal].syear == selectedYear) {
                    preservedValue = currentSchoolVal;
                }

                $schoolSelect.empty().append('<option value="">(Use School From File)</option>'); // Clear and add placeholder

                if (selectedYear) {
                    let hasOptions = false;
                    // Group schools by title for display (optional, simple list might be okay)
                    let schoolsInYear = [];
                    for (const schoolId in allSchools) {
                        if (allSchools[schoolId].syear == selectedYear) {
                            schoolsInYear.push(allSchools[schoolId]);
                        }
                    }
                    // Sort schools alphabetically by title
                    schoolsInYear.sort((a, b) => a.title.localeCompare(b.title));

                    schoolsInYear.forEach(school => {
                        const isSelected = (preservedValue == school.id); // Check against preserved value
                        $schoolSelect.append($('<option>', {
                            value: school.id,
                            text: school.title + ' (' + selectedYear + ')',
                            selected: isSelected
                        }));
                        hasOptions = true;
                    });

                    if (!hasOptions) {
                        $schoolSelect.append('<option value="" disabled>No schools found for selected year</option>');
                    }
                } else {
                    $schoolSelect.append('<option value="" disabled>Select Year first</option>');
                }
                // If a value was preserved, set it again after populating
                if(preservedValue){
                    $schoolSelect.val(preservedValue);
                }
                $schoolSelect.trigger('change.select2'); // Update Select2 display
                // Trigger grade filter when school list is repopulated
                filterOverrideGrades();
            }

            function filterOverrideGrades() {
                const selectedYear = $('#override_syear').val();
                const selectedSchoolId = $('#override_school_id').val();
                const $gradeSelect = $('#override_grade_id');
                const currentGradeVal = $gradeSelect.val();
                // Preserve the currently selected value if it's still valid for the new school/year
                let preservedValue = null;
                // Check if currentGradeVal exists in allGrades before accessing its properties
                if (currentGradeVal && allGrades[currentGradeVal] && allGrades[currentGradeVal].school_id == selectedSchoolId && allGrades[currentGradeVal].school_syear == selectedYear) {
                    preservedValue = currentGradeVal;
                }

                $gradeSelect.empty().append('<option value="">(Use Grade From File)</option>'); // Clear and add placeholder

                if (selectedSchoolId && selectedYear) {
                    let hasOptions = false;
                    let gradesInSchool = [];
                    for (const gradeId in allGrades) {
                        if (allGrades[gradeId].school_id == selectedSchoolId && allGrades[gradeId].school_syear == selectedYear) {
                            gradesInSchool.push(allGrades[gradeId]);
                            hasOptions = true; // Set flag inside the loop where options are found
                        }
                    }
                    // Sort grades by sort_order, then title
                    gradesInSchool.sort((a, b) => (a.sort_order ?? 999) - (b.sort_order ?? 999) || a.title.localeCompare(b.title));

                    gradesInSchool.forEach(grade => {
                        const isSelected = (preservedValue == grade.id);
                        $gradeSelect.append($('<option>', {
                            value: grade.id,
                            text: grade.title,
                            selected: isSelected
                        }));
                    });

                    if (!hasOptions) { // Check the flag after the loop
                        $gradeSelect.append('<option value="" disabled>No grades found for selected school/year</option>');
                    }
                } else {
                    $gradeSelect.append('<option value="" disabled>Select Year and School first</option>');
                }
                // If a value was preserved, set it again after populating
                if(preservedValue){
                    $gradeSelect.val(preservedValue);
                }
                $gradeSelect.trigger('change.select2'); // Update Select2 display
            }

            // Add event listeners for override dropdowns
            $('#override_syear').on('change', filterOverrideSchools);
            $('#override_school_id').on('change', filterOverrideGrades);

            // Initial filter on page load to ensure consistency if using old() values
            filterOverrideSchools();

            @endif
            // --- End Dynamic Filtering ---

        });
    </script>
@endpush

@push('styles')
    {{-- Include styles for Select2 if not already in layout --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}"> {{-- Adjust path as needed --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}"> {{-- Adjust path as needed --}}
@endpush
