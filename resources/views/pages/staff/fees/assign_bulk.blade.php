@extends('layouts.app')

@php
    use App\Helpers\Qs;
    // Variables passed from FeeController@showAssignStructureForm:
    // $feeStructures (Collection of FeeDefinition models)
    // $gradeLevels (Array or Collection for select dropdown)
    // $currentSchoolYear
@endphp

@section('title', 'Assign Fee Structure to Grades')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8"> {{-- Consistent width for better form layout --}}
                @include('layouts.partials.alerts') {{-- For displaying success/error messages --}}

                <div class="card card-info card-outline shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-sitemap me-2"></i>Assign Multi-Installment Fee Structure</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.fees.assign_bulk.store') }}" id="assignStructureForm">
                            @csrf

                            {{-- Fee Structure (FeeDefinition) Selection --}}
                            <div class="form-group mb-3">
                                <label for="fee_definition_id" class="form-label">Select Fee Structure <span class="text-danger">*</span></label>
                                <select name="fee_definition_id" id="fee_definition_id" class="form-control select2 @error('fee_definition_id') is-invalid @enderror" required data-placeholder="-- Select a Fee Structure --">
                                    <option value=""></option> {{-- Empty option for placeholder --}}
                                    @foreach ($feeStructures ?? [] as $structure)
                                        <option value="{{ $structure->id }}"
                                                {{ old('fee_definition_id') == $structure->id ? 'selected' : '' }}
                                                data-installments="{{ $structure->number_of_installments }}"
                                                data-total-amount="{{ $structure->total_amount }}">
                                            {{ $structure->fee_name }}
                                            (Total: {{ Qs::formatCurrency($structure->total_amount) }},
                                            {{ $structure->number_of_installments }} Installment{{ $structure->number_of_installments !== 1 ? 's' : '' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('fee_definition_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Grade Level Multi-select --}}
                            <div class="form-group mb-3">
                                <label for="grade_level_ids" class="form-label">Target Grade Level(s) <span class="text-danger">*</span></label>
                                <select name="grade_level_ids[]" id="grade_level_ids" class="form-control select2 @error('grade_level_ids') is-invalid @enderror @error('grade_level_ids.*') is-invalid @enderror" multiple required data-placeholder="Select Grade Levels">
                                    @foreach ($gradeLevels ?? [] as $id => $title)
                                        <option value="{{ $id }}" {{ in_array($id, old('grade_level_ids', [])) ? 'selected' : '' }}>
                                            {{ $title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('grade_level_ids') {{-- General error for the array itself --}}
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                                @error('grade_level_ids.*') {{-- Error for a specific item in the array --}}
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="alert alert-info py-2 mb-3">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    All installments defined in the selected fee structure will be assigned to active students in the chosen grade(s) for the <strong>{{ $currentSchoolYear ?? Qs::getCurrentSchoolYear() }}</strong> school year.
                                </small>
                            </div>

                            {{-- Assignment Date --}}
                            <div class="form-group mb-3">
                                <label for="assignment_date" class="form-label">Assignment Date <span class="text-danger">*</span></label>
                                <input type="date" name="assignment_date" id="assignment_date" class="form-control @error('assignment_date') is-invalid @enderror" value="{{ old('assignment_date', now()->toDateString()) }}" required>
                                <small class="form-text text-muted">This date will be used as the 'assigned_date' for all created installments and can be used as a base for due dates.</small>
                                @error('assignment_date')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Container for Dynamically Added Due Date Fields --}}
                            <div id="term-due-dates-container" class="mb-3 p-3 border rounded bg-light" style="display: none;">
                                {{-- Due date fields will be dynamically added here by JavaScript --}}
                            </div>


                            {{-- Allow Duplicates --}}
                            <div class="form-group mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_duplicates" id="allow_duplicates" value="1" {{ old('allow_duplicates') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_duplicates">
                                        Allow assigning this fee structure even if students already have existing installments for it?
                                    </label>
                                    <small class="form-text text-muted d-block">If unchecked, students who already have any installment of this fee structure for the current year will be skipped.</small>
                                </div>
                                @error('allow_duplicates')
                                <span class="invalid-feedback d-block">{{ $message }}</span> {{-- Ensure this shows if there's an error --}}
                                @enderror
                            </div>

                            {{-- Submit --}}
                            <div class="text-center border-top pt-3 mt-3">
                                <button type="submit" class="btn btn-info btn-lg px-4">
                                    <i class="fas fa-cogs me-2"></i>Assign Fee Structure
                                </button>
                                <a href="{{ route('staff.fees.index') }}" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times-circle me-1"></i> Cancel
                                </a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('styles')
    {{-- Select2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    {{-- Select2 Bootstrap 4 Theme (ensure this matches your AdminLTE theme or Bootstrap version) --}}
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    <style>
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff; /* Bootstrap primary blue */
            border-color: #006fe6;
            color: #fff;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff; /* White remove icon for better contrast */
        }
        .select2-container--bootstrap4 .select2-results__option--highlighted {
            background-color: #007bff; /* Match selection color */
            color: white;
        }
    </style>
@endpush

@push('scripts')
    {{-- jQuery (ensure it's loaded before Select2) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    {{-- Select2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    {{-- Pass Laravel validation errors and old input to JavaScript --}}
    <script>
        // It's good practice to namespace your global JS variables
        window.laravelData = {
            errors: @json($errors->toArray()),
            oldInput: @json(session()->getOldInput())
        };
    </script>

    <script>
        $(document).ready(function () {
            // Initialize Select2
            $('.select2').each(function() {
                $(this).select2({
                    theme: 'bootstrap4', // Or 'bootstrap5' if you have the corresponding theme
                    placeholder: $(this).data('placeholder') || 'Select options',
                    allowClear: Boolean($(this).data('allow-clear')) || ($(this).attr('multiple') ? false : true) // Allow clear for single selects by default
                });
            });

            const feeDefinitionSelect = $('#fee_definition_id');
            const dueDateContainer = $('#term-due-dates-container');
            // const assignmentDateInput = $('#assignment_date'); // Not directly used in current JS logic but good to have reference

            // Helper function to format currency (basic client-side version)
            // Ensure Qs or a similar helper is available if you use Qs.formatCurrency in JS
            function formatCurrency(amount, currencySymbol = '') { // Default to no symbol or configure as needed
                if (typeof Qs !== 'undefined' && typeof Qs.formatCurrency === 'function') {
                    return Qs.formatCurrency(amount); // Use your global Qs helper if available
                }
                const num = parseFloat(amount);
                if (isNaN(num)) return 'N/A';
                // Basic formatting, you might want a more robust library for complex needs
                return currencySymbol + num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            }


            function generateDueDateFields() {
                const selectedOption = feeDefinitionSelect.find('option:selected');
                const numInstallments = parseInt(selectedOption.data('installments'), 10);
                const totalAmount = parseFloat(selectedOption.data('total-amount')) || 0;
                dueDateContainer.empty().hide(); // Clear previous fields and hide

                if (isNaN(numInstallments) || numInstallments <= 1) {
                    return; // No need for individual due dates if 0, 1, or NaN installments
                }

                const amountPerInstallment = totalAmount > 0 && numInstallments > 0 ? (totalAmount / numInstallments) : 0;

                let fieldsHtml = `<h6 class="mb-3 text-info">Installment Due Dates (Optional)</h6>
                                  <p class="text-muted small mb-3">
                                      Specify due dates for each installment. If left blank, the system may use default logic based on the assignment date.
                                      Approx. amount per installment: <strong>${formatCurrency(amountPerInstallment)}</strong> (actual amounts are calculated server-side to handle rounding).
                                  </p>`;

                for (let i = 1; i <= numInstallments; i++) {
                    const fieldName = `term_due_dates[${i}]`;
                    const errorKey = `term_due_dates.${i}`; // Key used in Laravel validation for array items

                    let oldDueDateValue = '';
                    if (window.laravelData.oldInput && window.laravelData.oldInput.term_due_dates && window.laravelData.oldInput.term_due_dates[i]) {
                        oldDueDateValue = window.laravelData.oldInput.term_due_dates[i];
                    }

                    let isInvalidClass = '';
                    let errorMessageHtml = '';
                    if (window.laravelData.errors && window.laravelData.errors[errorKey]) {
                        isInvalidClass = 'is-invalid';
                        // Laravel errors for arrays might be an array of messages
                        const errorMessages = Array.isArray(window.laravelData.errors[errorKey]) ? window.laravelData.errors[errorKey].join('<br>') : window.laravelData.errors[errorKey];
                        errorMessageHtml = `<span class="invalid-feedback d-block">${errorMessages}</span>`;
                    }

                    fieldsHtml += `
                        <div class="form-group mb-3 row">
                            <label for="term_due_dates_${i}" class="col-sm-4 col-form-label fw-normal">Due Date - Inst. ${i}</label>
                            <div class="col-sm-8">
                                <input type="date" name="${fieldName}" id="term_due_dates_${i}"
                                       class="form-control form-control-sm ${isInvalidClass}" value="${oldDueDateValue}">
                                ${errorMessageHtml}
                            </div>
                        </div>
                    `;
                }
                dueDateContainer.html(fieldsHtml).show();
            }

            feeDefinitionSelect.on('change', generateDueDateFields);

            // Trigger on load if a fee_definition_id is already selected (e.g., from old input after validation failure)
            if (feeDefinitionSelect.val() && feeDefinitionSelect.find('option:selected').data('installments')) {
                generateDueDateFields();
            }
        });
    </script>
@endpush
