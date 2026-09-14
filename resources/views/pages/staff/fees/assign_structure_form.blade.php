@extends('layouts.app') {{-- Changed to AdminLTE layout --}}

@php
    use App\Helpers\Qs;
    // Variables passed from FeeStructureAssignmentController@create:
    // $feeStructures (Collection of FeeDefinition models)
    // $gradeLevels (Array or Collection for select dropdown)
    // $currentSchoolYear
    // $student_id (Optional, from query parameter if navigating from student page)
    // $selectedStudent (Optional, full model if $student_id is valid)
@endphp

@section('title', 'Assign Fee Structure') {{-- For HTML <title> tag --}}

@push('styles')
    {{-- Select2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    {{-- Select2 Bootstrap 4 Theme --}}
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    {{-- SweetAlert2 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff; /* Bootstrap primary blue */
            border-color: #006fe6;
            color: #fff;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff; /* White remove button for better contrast on blue background */
        }
        .nav-tabs .nav-link.active {
            background-color: #f8f9fa; /* Light background for active tab, good for AdminLTE */
            border-bottom-color: #f8f9fa;
        }
        .tab-content {
            border: 1px solid #dee2e6; /* Standard Bootstrap border color */
            border-top: 0;
            padding: 1.5rem; /* Ample padding inside tab content */
            background-color: #ffffff; /* White background for tab content for better contrast */
            border-radius: 0 0 0.25rem 0.25rem; /* Rounded bottom corners for the tab content */
        }
        /* Ensure Select2 dropdown is above other elements if z-index issues occur, common in AdminLTE */
        .select2-container {
            z-index: 9999 !important;
        }
        .form-label { /* Custom style for labels, if Bootstrap's default isn't preferred */
            font-weight: 500; /* Medium font weight for labels */
        }
        /* Responsive adjustments for the card to ensure it doesn't get too small on medium screens */
        @media (min-width: 768px) {
            .card-responsive-width {
                min-width: 700px; /* Ensures card has a minimum width on md screens and up */
            }
        }
    </style>
@endpush

{{-- AdminLTE Content Header for Page Title --}}
@section('content_header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12"> {{-- Full width as no breadcrumbs on the right --}}
                    <h1 class="m-0">Assign Fee Structure</h1>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    {{-- Main content section for AdminLTE --}}
    {{-- The <section class="content"> wrapper is usually provided by AdminLTE's master layout when using @section('content') --}}
    {{-- However, if your master layout doesn't include it, you might need to add it here or adjust your master layout. --}}
    {{-- For now, assuming the master layout handles the <section class="content"> part. --}}
    <div class="container-fluid">
        <div class="row justify-content-center">
            {{-- Adjusted column width for better responsiveness and wider view on desktops --}}
            {{-- Full width on smaller screens, then slightly less than full on larger screens --}}
            <div class="col-12 col-lg-11 col-xl-10">
                @include('layouts.partials.alerts') {{-- For displaying session flash messages --}}

                <div class="card card-info card-outline shadow-sm card-responsive-width">
                    <div class="card-header">
                        {{-- Card title is more specific, so it can remain if desired --}}
                        <h3 class="card-title"><i class="fas fa-sitemap mr-2"></i>Assign Multi-Installment Fee Structure</h3>
                    </div>
                    <div class="card-body">
                        {{-- Tab Navigation --}}
                        <ul class="nav nav-tabs" id="assignFeeStructureTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="assign-to-grades-tab" data-toggle="tab" href="#assign-to-grades" role="tab" aria-controls="assign-to-grades" aria-selected="true">
                                    <i class="fas fa-layer-group mr-1"></i> Assign to Grade Levels
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="assign-to-students-tab" data-toggle="tab" href="#assign-to-students" role="tab" aria-controls="assign-to-students" aria-selected="false">
                                    <i class="fas fa-user-friends mr-1"></i> Assign to Individual Students
                                </a>
                            </li>
                        </ul>

                        {{-- Tab Content --}}
                        <div class="tab-content" id="assignFeeStructureTabsContent">
                            {{-- Tab 1: Assign to Grade Levels --}}
                            <div class="tab-pane fade show active" id="assign-to-grades" role="tabpanel" aria-labelledby="assign-to-grades-tab">
                                <h5 class="mt-3 mb-3 text-primary">Assign Fee Structure to Selected Grade Levels</h5>
                                <form method="POST" action="{{ route('staff.fees.structure.assign.store') }}" class="assignStructureForm" id="assignToGradesForm">
                                    @csrf
                                    <input type="hidden" name="assignment_type" value="grades">

                                    {{-- Fee Structure (FeeDefinition) Selection --}}
                                    <div class="form-group mb-3">
                                        <label for="fee_definition_id_grades" class="form-label">Select Fee Structure <span class="text-danger">*</span></label>
                                        <select name="fee_definition_id" id="fee_definition_id_grades" class="form-control select2-dynamic @error('fee_definition_id', 'grades_form') is-invalid @enderror" required data-placeholder="-- Select a Fee Structure --">
                                            <option value=""></option> {{-- Placeholder for Select2 --}}
                                            @foreach ($feeStructures ?? [] as $structure)
                                                <option value="{{ $structure->id }}" {{ old('assignment_type') == 'grades' && old('fee_definition_id') == $structure->id ? 'selected' : '' }} data-installments="{{ $structure->number_of_installments }}" data-total-amount="{{ $structure->total_amount }}">
                                                    {{ $structure->fee_name }}
                                                    (Total: {{ Qs::formatCurrency($structure->total_amount) }}, {{ $structure->number_of_installments }} Installment{{ $structure->number_of_installments !== 1 ? 's' : '' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('fee_definition_id', 'grades_form') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Grade Level Multi-select --}}
                                    <div class="form-group mb-3">
                                        <label for="grade_level_ids" class="form-label">Target Grade Level(s) <span class="text-danger">*</span></label>
                                        <select name="grade_level_ids[]" id="grade_level_ids" class="form-control select2-dynamic @error('grade_level_ids', 'grades_form') is-invalid @enderror @error('grade_level_ids.*', 'grades_form') is-invalid @enderror" multiple="multiple" required data-placeholder="Select Grade Levels">
                                            @foreach ($gradeLevels ?? [] as $id => $title)
                                                <option value="{{ $id }}" {{ old('assignment_type') == 'grades' && is_array(old('grade_level_ids')) && in_array($id, old('grade_level_ids', [])) ? 'selected' : '' }}>
                                                    {{ $title }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('grade_level_ids', 'grades_form') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                                        @error('grade_level_ids.*', 'grades_form') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="alert alert-info py-2 mb-3">
                                        <small><i class="fas fa-info-circle mr-1"></i> All installments will be assigned to active students in the chosen grade(s) for <strong>{{ $currentSchoolYear ?? Qs::getCurrentSchoolYear() }}</strong>.</small>
                                    </div>

                                    {{-- Shared Fields for Grades Tab --}}
                                    @php
                                        $form_prefix_grades = 'grades';
                                        $form_type_grades = 'grades';
                                    @endphp
                                    <div class="form-group mb-3">
                                        <label for="assignment_date_{{ $form_prefix_grades }}" class="form-label">Assignment Date <span class="text-danger">*</span></label>
                                        <input type="date" name="assignment_date" id="assignment_date_{{ $form_prefix_grades }}"
                                               class="form-control @error('assignment_date', $form_type_grades.'_form') is-invalid @enderror @error('assignment_date') {{-- General error if not using named bags --}} is-invalid @enderror"
                                               value="{{ old('assignment_type') == $form_type_grades ? old('assignment_date', now()->toDateString()) : now()->toDateString() }}" required>
                                        <small class="form-text text-muted">This date will be used as the 'assigned_date' for all created installments and can be a base for due dates.</small>
                                        @error('assignment_date', $form_type_grades.'_form') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        @error('assignment_date') {{-- General error display --}}
                                            @if (!$errors->hasBag($form_type_grades.'_form')) {{-- Avoid double display if named bag also caught it. --}}
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @endif
                                        @enderror
                                    </div>

                                    <div id="term-due-dates-container-{{ $form_prefix_grades }}" class="mb-3 p-3 border rounded bg-light" style="display: none;">
                                        {{-- Due date fields will be dynamically added here by JavaScript --}}
                                    </div>

                                    <div class="form-group mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input @error('allow_duplicates', $form_type_grades.'_form') is-invalid @enderror @error('allow_duplicates') is-invalid @enderror"
                                                   type="checkbox" name="allow_duplicates" id="allow_duplicates_{{ $form_prefix_grades }}"
                                                   value="1" {{ old('assignment_type') == $form_type_grades && old('allow_duplicates') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="allow_duplicates_{{ $form_prefix_grades }}">
                                                Allow assigning this fee structure even if students already have existing installments for it?
                                            </label>
                                            <small class="form-text text-muted d-block">If unchecked, students who already have any installment of this fee structure for the current year will be skipped.</small>
                                        </div>
                                        @error('allow_duplicates', $form_type_grades.'_form') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                                        @error('allow_duplicates')
                                            @if (!$errors->hasBag($form_type_grades.'_form')) {{-- Avoid double display if named bag also caught it. --}}
                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @endif
                                        @enderror
                                    </div>
                                    {{-- End Shared Fields for Grades Tab --}}


                                    <div class="text-center border-top pt-3 mt-4">
                                        <button type="submit" class="btn btn-info btn-lg px-4">
                                            <i class="fas fa-cogs mr-2"></i>Assign to Grades
                                        </button>
                                        <a href="{{ route('staff.fees.index') }}" class="btn btn-secondary ml-2">Cancel</a>
                                    </div>
                                </form>
                            </div>

                            {{-- Tab 2: Assign to Individual Students --}}
                            <div class="tab-pane fade" id="assign-to-students" role="tabpanel" aria-labelledby="assign-to-students-tab">
                                <h5 class="mt-3 mb-3 text-primary">Assign Fee Structure to Selected Individual Students</h5>
                                <form method="POST" action="{{ route('staff.fees.structure.assign.store') }}" class="assignStructureForm" id="assignToStudentsForm">
                                    @csrf
                                    <input type="hidden" name="assignment_type" value="students">

                                    {{-- Fee Structure (FeeDefinition) Selection --}}
                                    <div class="form-group mb-3">
                                        <label for="fee_definition_id_students" class="form-label">Select Fee Structure <span class="text-danger">*</span></label>
                                        <select name="fee_definition_id" id="fee_definition_id_students" class="form-control select2-dynamic @error('fee_definition_id', 'students_form') is-invalid @enderror" required data-placeholder="-- Select a Fee Structure --">
                                            <option value=""></option> {{-- Placeholder for Select2 --}}
                                            @foreach ($feeStructures ?? [] as $structure)
                                                <option value="{{ $structure->id }}" {{ old('assignment_type') == 'students' && old('fee_definition_id') == $structure->id ? 'selected' : '' }} data-installments="{{ $structure->number_of_installments }}" data-total-amount="{{ $structure->total_amount }}">
                                                    {{ $structure->fee_name }}
                                                    (Total: {{ Qs::formatCurrency($structure->total_amount) }}, {{ $structure->number_of_installments }} Installment{{ $structure->number_of_installments !== 1 ? 's' : '' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('fee_definition_id', 'students_form') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Student Multi-select with AJAX search --}}
                                    <div class="form-group mb-3">
                                        <label for="student_ids" class="form-label">Target Student(s) <span class="text-danger">*</span></label>
                                        <select name="student_ids[]" id="student_ids" class="form-control student-search-select2 @error('student_ids', 'students_form') is-invalid @enderror @error('student_ids.*', 'students_form') is-invalid @enderror" multiple="multiple" required data-placeholder="Search and select students...">
                                            {{-- Pre-populate if a student_id was passed in query and is valid --}}
                                            @if(isset($student_id) && $selectedStudent)
                                                <option value="{{ $selectedStudent->id }}" selected>
                                                    {{ $selectedStudent->first_name }} {{ $selectedStudent->last_name }} (ID: {{ $selectedStudent->prem_number ?? 'N/A' }})
                                                </option>
                                            @endif
                                            {{-- Repopulate with old student_ids if validation failed on this tab --}}
                                            @if(old('assignment_type') == 'students' && is_array(old('student_ids')))
                                                @foreach(old('student_ids') as $old_student_id)
                                                    @php $oldStudent = \App\Models\Student::find($old_student_id); @endphp
                                                    @if($oldStudent && !(isset($student_id) && $selectedStudent && $selectedStudent->id == $old_student_id) ) {{-- Avoid duplicating pre-selected --}}
                                                        <option value="{{ $oldStudent->id }}" selected>
                                                            {{ $oldStudent->first_name }} {{ $oldStudent->last_name }} (ID: {{ $oldStudent->prem_number ?? 'N/A' }})
                                                        </option>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('student_ids', 'students_form') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                                        @error('student_ids.*', 'students_form') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="alert alert-info py-2 mb-3">
                                        <small><i class="fas fa-info-circle mr-1"></i> All installments will be assigned to the selected student(s) for <strong>{{ $currentSchoolYear ?? Qs::getCurrentSchoolYear() }}</strong>.</small>
                                    </div>

                                    {{-- Shared Fields for Students Tab --}}
                                    @php
                                        $form_prefix_students = 'students';
                                        $form_type_students = 'students';
                                    @endphp
                                    <div class="form-group mb-3">
                                        <label for="assignment_date_{{ $form_prefix_students }}" class="form-label">Assignment Date <span class="text-danger">*</span></label>
                                        <input type="date" name="assignment_date" id="assignment_date_{{ $form_prefix_students }}"
                                               class="form-control @error('assignment_date', $form_type_students.'_form') is-invalid @enderror @error('assignment_date') {{-- General error if not using named bags --}} is-invalid @enderror"
                                               value="{{ old('assignment_type') == $form_type_students ? old('assignment_date', now()->toDateString()) : now()->toDateString() }}" required>
                                        <small class="form-text text-muted">This date will be used as the 'assigned_date' for all created installments and can be a base for due dates.</small>
                                        @error('assignment_date', $form_type_students.'_form') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        @error('assignment_date') {{-- General error display --}}
                                            @if (!$errors->hasBag($form_type_students.'_form')) {{-- Avoid double display if named bag also caught it. --}}
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @endif
                                        @enderror
                                    </div>

                                    <div id="term-due-dates-container-{{ $form_prefix_students }}" class="mb-3 p-3 border rounded bg-light" style="display: none;">
                                        {{-- Due date fields will be dynamically added here by JavaScript --}}
                                    </div>

                                    <div class="form-group mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input @error('allow_duplicates', $form_type_students.'_form') is-invalid @enderror @error('allow_duplicates') is-invalid @enderror"
                                                   type="checkbox" name="allow_duplicates" id="allow_duplicates_{{ $form_prefix_students }}"
                                                   value="1" {{ old('assignment_type') == $form_type_students && old('allow_duplicates') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="allow_duplicates_{{ $form_prefix_students }}">
                                                Allow assigning this fee structure even if students already have existing installments for it?
                                            </label>
                                            <small class="form-text text-muted d-block">If unchecked, students who already have any installment of this fee structure for the current year will be skipped.</small>
                                        </div>
                                        @error('allow_duplicates', $form_type_students.'_form') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                                        @error('allow_duplicates')
                                            @if (!$errors->hasBag($form_type_students.'_form')) {{-- Avoid double display if named bag also caught it. --}}
                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @endif
                                        @enderror
                                    </div>
                                    {{-- End Shared Fields for Students Tab --}}

                                    <div class="text-center border-top pt-3 mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg px-4">
                                            <i class="fas fa-user-plus mr-2"></i>Assign to Students
                                        </button>
                                        <a href="{{ route('staff.fees.index') }}" class="btn btn-secondary ml-2">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- jQuery (ensure it's loaded before Bootstrap and other plugins) --}}
    {{-- Ensure jQuery is loaded by AdminLTE's master layout, or uncomment if needed --}}
    {{-- <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> --}}
    {{-- Bootstrap 4 JS Bundle (includes Popper.js) --}}
    {{-- Ensure Bootstrap JS is loaded by AdminLTE's master layout, or uncomment if needed --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script> --}}

    {{-- Select2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    {{-- SweetAlert2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        // Global object to hold data passed from Laravel, like validation errors and old input
        window.laravelData = {
            errors: @json($errors->toArray()),
            oldInput: @json(session()->getOldInput()),
            // Example of adding a route for JS:
            searchStudentsUrl: '{{ route("staff.students.search_json") }}' // Ensured this route is used below
        };

        // Simple Qs utility if not globally available (e.g., from main layout)
        if (typeof Qs === 'undefined') {
            window.Qs = {
                formatCurrency: function(amount, currencySymbol = '') {
                    if (isNaN(parseFloat(amount))) return amount; // Return original if not a number
                    // Format to 2 decimal places and add thousand separators
                    return currencySymbol + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                },
                // Example:
                // getCurrentSchoolYear: function() { return '{{ Qs::getCurrentSchoolYear() }}'; }
            };
        }

        $(document).ready(function () {
            // Function to initialize standard Select2 elements
            function initializeSelect2(selector) {
                $(selector).each(function() {
                    if (!$(this).data('select2')) { // Initialize only if not already initialized
                        $(this).select2({
                            theme: 'bootstrap4',
                            placeholder: $(this).data('placeholder') || 'Select options',
                            allowClear: Boolean($(this).data('allow-clear')) || $(this).prop('multiple') // Allow clear for multiple or if data-allow-clear is true
                        });
                    }
                });
            }

            // Initialize all elements with class 'select2-dynamic' on page load
            initializeSelect2('.select2-dynamic');

            // Initialize student search Select2 elements
            function initializeStudentSearchSelect2(selector) {
                 $(selector).each(function() {
                    if (!$(this).data('select2')) { // Initialize only if not already initialized
                        $(this).select2({
                            theme: 'bootstrap4',
                            placeholder: $(this).data('placeholder') || 'Search by Name or ID...',
                            allowClear: true,
                            minimumInputLength: 2, // User must type at least 2 characters
                            ajax: {
                                url: window.laravelData.searchStudentsUrl, // Using the URL from laravelData
                                dataType: 'json',
                                delay: 250, // Wait 250ms after typing before sending request
                                data: function (params) {
                                    return {
                                        search_term: params.term, // search term
                                        page: params.page || 1 // pagination
                                    };
                                },
                                processResults: function (data, params) {
                                    params.page = params.page || 1;
                                    return {
                                        results: $.map(data.data, function (student) {
                                            // Construct text for display in Select2
                                            let studentText = `${student.first_name} ${student.last_name}`;
                                            studentText += ` (ID: ${student.prem_number || 'N/A'})`;
                                            if (student.grade_level) {
                                                studentText += ` - ${student.grade_level}`;
                                            }
                                            return { id: student.id, text: studentText };
                                        }),
                                        pagination: {
                                            more: (params.page * data.per_page) < data.total // Check if more pages are available
                                        }
                                    };
                                },
                                cache: true // Cache AJAX results
                            }
                        });
                    }
                });
            }

            // Initialize student search Select2 on page load (if present in the initially active tab)
            initializeStudentSearchSelect2('.student-search-select2');


            // Function to generate due date input fields based on selected fee structure
            function generateDueDateFields(feeDefSelectElement, dueDateContainerId, assignmentDateId) {
                const $feeDefSelectElement = $(feeDefSelectElement);
                const selectedOption = $feeDefSelectElement.find('option:selected');
                const numInstallments = parseInt(selectedOption.data('installments'), 10);
                const totalAmount = parseFloat(selectedOption.data('total-amount')) || 0;
                const $dueDateContainer = $('#' + dueDateContainerId);
                const formType = dueDateContainerId.includes('grades') ? 'grades' : 'students'; // Determine form context for old input/errors

                $dueDateContainer.empty().hide(); // Clear previous fields and hide

                // Only show due date fields if number of installments is greater than 1
                if (isNaN(numInstallments) || numInstallments <= 1) {
                    return;
                }

                const amountPerInstallment = totalAmount > 0 && numInstallments > 0 ? (totalAmount / numInstallments) : 0;
                let fieldsHtml = `<h6 class="mb-3 text-info">Installment Due Dates (Optional)</h6>
                                  <p class="text-muted small mb-3">
                                      Specify due dates for each installment. If left blank, the system may use default logic or assignment date.
                                      Approx. amount per installment: <strong>${Qs.formatCurrency(amountPerInstallment)}</strong> (actual amounts are defined in the fee structure).
                                  </p>`;

                for (let i = 1; i <= numInstallments; i++) {
                    const fieldName = `term_due_dates[${i}]`; // Name for the input field
                    const errorKey = `term_due_dates.${i}`; // Key for retrieving validation errors
                    let oldDueDateValue = '';
                    let isInvalidClass = '';
                    let errorMessageHtml = '';

                    // Check for old input and errors if form was submitted and failed validation
                    // Ensure oldInput.assignment_type matches the current form's type
                    if (window.laravelData.oldInput && window.laravelData.oldInput.assignment_type === formType) {
                        if (window.laravelData.oldInput.term_due_dates && window.laravelData.oldInput.term_due_dates[i]) {
                            oldDueDateValue = window.laravelData.oldInput.term_due_dates[i];
                        }
                        // Check errors specific to the form's error bag (e.g., 'grades_form' or 'students_form')
                        // Also check general errors if not using named bags or for cross-cutting concerns
                        const formErrorBagKey = formType + '_form';
                        let specificError = null;
                        if (window.laravelData.errors[formErrorBagKey] && window.laravelData.errors[formErrorBagKey][errorKey]) {
                             specificError = window.laravelData.errors[formErrorBagKey][errorKey];
                        } else if (window.laravelData.errors && window.laravelData.errors[errorKey]) { // Fallback to general error
                             specificError = window.laravelData.errors[errorKey];
                        }

                        if (specificError) {
                            isInvalidClass = 'is-invalid';
                            errorMessageHtml = `<span class="invalid-feedback d-block">${Array.isArray(specificError) ? specificError.join(', ') : specificError}</span>`;
                        }
                    }

                    fieldsHtml += `
                        <div class="form-group mb-3 row">
                            <label for="term_due_dates_${formType}_${i}" class="col-sm-4 col-form-label">Due Date - Inst. ${i}</label>
                            <div class="col-sm-8">
                                <input type="date" name="${fieldName}" id="term_due_dates_${formType}_${i}"
                                       class="form-control ${isInvalidClass}" value="${oldDueDateValue}">
                                ${errorMessageHtml}
                            </div>
                        </div>`;
                }
                $dueDateContainer.html(fieldsHtml).show(); // Add fields to container and show
            }

            // Event listeners for fee definition select changes
            $('#fee_definition_id_grades').on('change', function() {
                generateDueDateFields(this, 'term-due-dates-container-grades', 'assignment_date_grades');
            });
            $('#fee_definition_id_students').on('change', function() {
                generateDueDateFields(this, 'term-due-dates-container-students', 'assignment_date_students');
            });

            // Trigger change on page load if old input exists for fee definition, to populate due dates
            // This handles repopulation after validation errors
            if (window.laravelData.oldInput && window.laravelData.oldInput.assignment_type === 'grades' && window.laravelData.oldInput.fee_definition_id) {
                $('#fee_definition_id_grades').val(window.laravelData.oldInput.fee_definition_id).trigger('change');
            } else if ($('#fee_definition_id_grades').val() && $('#fee_definition_id_grades option:selected').data('installments') > 1) {
                 // Also trigger if a value is pre-selected (e.g. not from old input but from controller)
                generateDueDateFields('#fee_definition_id_grades', 'term-due-dates-container-grades', 'assignment_date_grades');
            }

            if (window.laravelData.oldInput && window.laravelData.oldInput.assignment_type === 'students' && window.laravelData.oldInput.fee_definition_id) {
                $('#fee_definition_id_students').val(window.laravelData.oldInput.fee_definition_id).trigger('change');
            } else if ($('#fee_definition_id_students').val() && $('#fee_definition_id_students option:selected').data('installments') > 1 ) {
                // Also trigger if a value is pre-selected
                generateDueDateFields('#fee_definition_id_students', 'term-due-dates-container-students', 'assignment_date_students');
            }

            // Form submission confirmation using SweetAlert2
            $('.assignStructureForm').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                const form = this;
                const assignmentType = $(form).find('input[name="assignment_type"]').val();
                let targetText = '';

                if (assignmentType === 'grades') {
                    const numGrades = $('#grade_level_ids :selected').length;
                    targetText = numGrades > 0 ? `to ${numGrades} grade level(s)` : 'to selected grade levels';
                } else if (assignmentType === 'students') {
                    const numStudents = $('#student_ids :selected').length;
                    targetText = numStudents > 0 ? `to ${numStudents} student(s)` : 'to selected students';
                }

                Swal.fire({
                    title: 'Confirm Assignment',
                    text: `Are you sure you want to assign the selected fee structure ${targetText}? This may create multiple fee installments.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6', // Blue confirm button
                    cancelButtonColor: '#d33',   // Red cancel button
                    confirmButtonText: 'Yes, assign it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show a loading state while submitting
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while the fee structure is being assigned.',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });
                        form.submit(); // Submit the form programmatically
                    }
                });
            });

            // Tab persistence logic
            let activeTab = localStorage.getItem('activeAssignFeeTab');
            // If there was a validation error, switch to the tab that had the error
            if (window.laravelData.oldInput && window.laravelData.oldInput.assignment_type) {
                activeTab = (window.laravelData.oldInput.assignment_type === 'students') ? '#assign-to-students' : '#assign-to-grades';
                localStorage.setItem('activeAssignFeeTab', activeTab); // Update stored active tab
            }

            if (activeTab) {
                $('#assignFeeStructureTabs a[href="' + activeTab + '"]').tab('show');
            }

            // When a tab is shown, update localStorage and initialize Select2 if needed
            $('#assignFeeStructureTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                localStorage.setItem('activeAssignFeeTab', $(e.target).attr('href'));
                const targetTabPane = $($(e.target).attr('href'));

                // Initialize Select2 elements within the newly shown tab if they haven't been already
                // This is important because Select2 needs visible elements to initialize correctly
                if (!targetTabPane.data('select2-initialized')) {
                    initializeSelect2(targetTabPane.find('.select2-dynamic'));
                    initializeStudentSearchSelect2(targetTabPane.find('.student-search-select2'));
                    targetTabPane.data('select2-initialized', true);
                }
            });

            // Ensure Select2 on the initially active tab is initialized
            // (The 'shown.bs.tab' event won't fire for the default active tab on page load)
            const initiallyActiveTabPane = $('.tab-pane.active');
            if (initiallyActiveTabPane.length > 0 && !initiallyActiveTabPane.data('select2-initialized')) {
                 initializeSelect2(initiallyActiveTabPane.find('.select2-dynamic'));
                 initializeStudentSearchSelect2(initiallyActiveTabPane.find('.student-search-select2'));
                 initiallyActiveTabPane.data('select2-initialized', true);
            }


            // Display SweetAlert2 notifications for session flash messages
            @if(session('flash_success_swal'))
                Swal.fire({ title: 'Success!', text: '{{ session('flash_success_swal') }}', icon: 'success', confirmButtonText: 'OK' });
            @endif
            @if(session('flash_error_swal'))
                Swal.fire({ title: 'Error!', html: '{!! addslashes(session('flash_error_swal')) !!}', icon: 'error', confirmButtonText: 'OK' });
            @endif
            @if(session('flash_warning_swal'))
                Swal.fire({ title: 'Warning!', text: '{{ session('flash_warning_swal') }}', icon: 'warning', confirmButtonText: 'OK' });
            @endif
            @if(session('flash_info_swal'))
                Swal.fire({ title: 'Info!', text: '{{ session('flash_info_swal') }}', icon: 'info', confirmButtonText: 'OK' });
            @endif
        });
    </script>
@endpush
