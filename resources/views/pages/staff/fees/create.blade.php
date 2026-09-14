{{-- resources/views/pages/staff/fees/create.blade.php --}}

@extends('layouts.app') {{-- Use your main application layout --}}

@php
    // Variables passed from controller:
    // $selectedStudent (the student model if one is pre-selected via query param, optional)
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Create New Fee')

@push('styles')
    {{-- Select2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    {{-- SweetAlert2 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    {{-- Optional: Select2 Bootstrap 4 Theme (if you are using Bootstrap 4) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
    <style>
        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(2.25rem + 2px) !important; /* Adjust height to match form-control-lg if needed */
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding: .375rem .75rem;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px) !important;
        }
        /* Ensure Select2 dropdown is above other elements if z-index issues occur */
        .select2-container {
            z-index: 9999 !important;
        }
    </style>
@endpush

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7"> {{-- Adjust column width as needed --}}

                @include('layouts.partials.alerts') {{-- For displaying server-side flash messages (ideally also uses SweetAlert) --}}

                {{-- Student Search Section with Select2 --}}
                <div class="card card-info card-outline shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="card-title">1. Find Student</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="student_search_select">Search and Select Student <span class="text-danger">*</span></label>
                            <select id="student_search_select" name="student_search_select" class="form-control">
                                {{-- This will be populated by Select2 --}}
                                @if($selectedStudent)
                                    <option value="{{ $selectedStudent->id }}" selected="selected">
                                        {{ $selectedStudent->last_name }}, {{ $selectedStudent->first_name }} (ID: {{ $selectedStudent->prem_number ?? 'N/A' }})
                                    </option>
                                @endif
                            </select>
                            <small class="form-text text-muted">Type to search by name or Permanent #.</small>
                        </div>
                        @if($selectedStudent)
                            <div class="mt-2">
                                <a href="{{ route('staff.fees.create') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear Selected Student
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Fee Creation Form (Initially hidden, shown when a student is selected) --}}
                <div id="fee_creation_form_card" class="card card-success card-outline shadow-sm {{ !$selectedStudent ? 'd-none' : '' }}">
                    <div class="card-header">
                        <h3 class="card-title">2. Enter Fee Details for: <strong id="selected_student_name_display">{{ $selectedStudent ? ($selectedStudent->last_name . ', ' . $selectedStudent->first_name) : '' }}</strong></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.fees.store') }}" id="create_fee_form">
                            @csrf
                            {{-- Hidden input to store the selected student's ID --}}
                            <input type="hidden" name="student_id" id="student_id_hidden" value="{{ $selectedStudent ? $selectedStudent->id : '' }}">

                            {{-- Fee Title --}}
                            <div class="form-group mb-3">
                                <label for="title">Fee Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title') }}" placeholder="e.g., Tuition Fee, Exam Fee" required>
                                @error('title')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Amount --}}
                            <div class="form-group mb-3">
                                <label for="amount">Amount <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount') }}" placeholder="0.00" required step="0.01" min="0">
                                @error('amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Assigned Date --}}
                            <div class="form-group mb-3">
                                <label for="assigned_date">Assigned Date</label>
                                <input type="date" name="assigned_date" id="assigned_date" class="form-control @error('assigned_date') is-invalid @enderror"
                                       value="{{ old('assigned_date', now()->toDateString()) }}"> {{-- Default to today --}}
                                @error('assigned_date')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Due Date --}}
                            <div class="form-group mb-3">
                                <label for="due_date">Due Date</label>
                                <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror"
                                       value="{{ old('due_date') }}">
                                @error('due_date')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Comments --}}
                            <div class="form-group mb-3">
                                <label for="comments">Comments</label>
                                <textarea name="comments" id="comments" class="form-control @error('comments') is-invalid @enderror"
                                          rows="3" placeholder="Optional comments about this fee">{{ old('comments') }}</textarea>
                                @error('comments')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Submit Button --}}
                            <div class="text-center border-top pt-3 mt-3">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save mr-1"></i> Create Fee
                                </button>
                                <a href="{{ route('staff.fees.index') }}" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times-circle mr-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                @if(!$selectedStudent)
                    <div id="select_student_placeholder" class="alert alert-warning text-center">
                        Please search for and select a student above to create a fee.
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- jQuery (ensure it's loaded before Select2 and SweetAlert2) --}}
    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}} {{-- Already loaded by Laravel UI typically --}}
    {{-- Select2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    {{-- SweetAlert2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for student search
            $('#student_search_select').select2({
                theme: 'bootstrap4', // Optional: if you're using Bootstrap 4 theme
                placeholder: 'Search by Name or Permanent #...',
                allowClear: true,
                minimumInputLength: 2, // Minimum characters to start searching
                ajax: {
                    url: '{{ route("staff.students.search_json") }}', // IMPORTANT: Create this route and controller method
                    dataType: 'json',
                    delay: 250, // Wait 250ms after typing before triggering the request
                    data: function (params) {
                        return {
                            search_term: params.term, // Search term
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: $.map(data.data, function (student) { // Assuming your JSON returns data in 'data' property
                                return {
                                    id: student.id,
                                    text: student.last_name + ', ' + student.first_name + (student.prem_number ? ' (ID: ' + student.prem_number + ')' : '') + (student.grade_level ? ' - ' + student.grade_level : ''),
                                    // You can pass the full student object if needed for display or other purposes
                                    full_student_data: student
                                }
                            }),
                            pagination: {
                                more: (params.page * data.per_page) < data.total // Assuming pagination info from server
                            }
                        };
                    },
                    cache: true
                }
            });

            // Handle student selection
            $('#student_search_select').on('select2:select', function (e) {
                var data = e.params.data;
                if (data && data.id) {
                    $('#student_id_hidden').val(data.id);
                    $('#selected_student_name_display').text(data.text.split(' (ID:')[0]); // Extract name part
                    $('#fee_creation_form_card').removeClass('d-none');
                    $('#select_student_placeholder').addClass('d-none');

                    // Optional: Scroll to the fee creation form
                    // $('html, body').animate({
                    //     scrollTop: $("#fee_creation_form_card").offset().top - 70 // Adjust offset as needed
                    // }, 500);

                }
            });

            // Handle clearing student selection
            $('#student_search_select').on('select2:unselect', function (e) {
                $('#student_id_hidden').val('');
                $('#selected_student_name_display').text('');
                $('#fee_creation_form_card').addClass('d-none');
                $('#select_student_placeholder').removeClass('d-none');
            });

            // If a student was pre-selected (e.g., from query param), ensure the form is visible
            @if($selectedStudent)
                $('#fee_creation_form_card').removeClass('d-none');
                $('#select_student_placeholder').addClass('d-none');
            @endif

            // SweetAlert for form submission (Example)
            // You might want to integrate this with how your `layouts.partials.alerts` handles flash messages
            // For example, if your backend redirects with session('success_swal', 'Message'),
            // your main layout could have JS to pick that up and display SweetAlert.
            $('#create_fee_form').on('submit', function(e) {
                // You can add client-side validation here before showing a "processing" SweetAlert
                // For instance, if using jQuery validation: if (!$(this).valid()) return;

                // Example: Show a processing alert (optional)
                // Swal.fire({
                //   title: 'Processing...',
                //   text: 'Please wait while the fee is being created.',
                //   allowOutsideClick: false,
                //   didOpen: () => {
                //     Swal.showLoading();
                //   }
                // });
                // The form will submit normally. Server-side validation errors will be shown via Laravel's default
                // mechanism (or your custom alert partial). Success should ideally trigger a SweetAlert on redirect.
            });

            // Example of how your `layouts.partials.alerts` might trigger SweetAlerts
            // This is conceptual. Your actual implementation in `alerts.blade.php` or main layout would differ.
            @if(session('flash_success_swal'))
                Swal.fire({
                    title: 'Success!',
                    text: '{{ session('flash_success_swal') }}',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            @endif
            @if(session('flash_error_swal'))
                Swal.fire({
                    title: 'Error!',
                    text: '{{ session('flash_error_swal') }}',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            @endif
            @if(session('flash_warning_swal'))
                Swal.fire({
                    title: 'Warning!',
                    text: '{{ session('flash_warning_swal') }}',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            @endif
            @if(session('flash_info_swal'))
                Swal.fire({
                    title: 'Info!',
                    text: '{{ session('flash_info_swal') }}',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            @endif

        });
    </script>
@endpush
