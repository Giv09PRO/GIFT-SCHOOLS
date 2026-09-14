{{-- resources/views/pages/staff/student/create.blade.php --}}

@extends('layouts.app')

@php
    use App\Helpers\Qs; // Make sure Qs helper is available if used elsewhere in the view/layout
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Add New Student')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-15">

                {{-- Create Form Card --}}
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Enter Student Details</h3>
                    </div>
                    <div class="card-body">
                        @include('layouts.partials.alerts') {{-- Display Alerts --}}

                        <form method="POST" action="{{ route('staff.students.store') }}">
                            @csrf

                            {{-- Student Demographics Section --}}
                            <h5 class="mb-3 mt-2 text-primary">Student Information</h5>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label for="first_name">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror"
                                           value="{{ old('first_name') }}" required>
                                    @error('first_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-3 form-group mb-3">
                                    <label for="middle_name">Middle Name</label>
                                    <input type="text" name="middle_name" id="middle_name" class="form-control @error('middle_name') is-invalid @enderror"
                                           value="{{ old('middle_name') }}">
                                    @error('middle_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror"
                                           value="{{ old('last_name') }}" required>
                                    @error('last_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-1 form-group mb-3">
                                    <label for="name_suffix">Suffix</label>
                                    <input type="text" name="name_suffix" id="name_suffix" class="form-control @error('name_suffix') is-invalid @enderror"
                                           value="{{ old('name_suffix') }}">
                                    @error('name_suffix') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label for="prem_number">PReM Number</label>
                                    <input type="text" name="prem_number" id="prem_number" class="form-control @error('prem_number') is-invalid @enderror"
                                           value="{{ old('prem_number') }}">
                                    @error('prem_number') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label for="gender">Gender <span class="text-danger">*</span></label>
                                    <select name="gender" id="gender" class="form-select select2 @error('gender') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('gender') ? '' : 'selected' }}>Select Gender</option>
                                        <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                    </select>
                                    @error('gender') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label for="dob">Date of Birth</label>
                                    {{-- Changed type to text for JS datepicker consistency --}}
                                    <input type="text" name="dob" id="dob" class="form-control date-picker @error('dob') is-invalid @enderror"
                                           value="{{ old('dob') }}" placeholder="YYYY-MM-DD">
                                    @error('dob') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label for="email">Email Address</label>
                                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email') }}">
                                    @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone') }}">
                                    @error('phone') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="address">Address</label>
                                <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                                @error('address') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            <hr>

                            {{-- Initial Enrollment Section --}}
                            <h5 class="mb-3 mt-4 text-primary">Initial Enrollment Information</h5>
                            <div class="row">
                                {{-- School Year (Only for Admins/Elevated Privileges) --}}
                                @if($hasElevatedPrivileges)
                                    <div class="col-md-4 form-group mb-3">
                                        <label for="syear">School Year <span class="text-danger">*</span></label>
                                        <select name="syear" id="syear" class="form-select select2 @error('syear') is-invalid @enderror" required> {{-- Use form-select --}}
                                            @foreach($years as $year)
                                                <option value="{{ $year }}" {{ old('syear', $currentYear) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                        @error('syear') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        <small class="form-text text-muted">Select the year for this enrollment.</small>
                                    </div>
                                @else
                                    {{-- For non-admins, the year is fixed to current year --}}
                                    <div class="col-md-4 form-group mb-3">
                                        <label>School Year</label>
                                        <input type="text" class="form-control" value="{{ $currentYear }}" disabled>
                                        {{-- No need to submit syear, controller will use currentYear --}}
                                    </div>
                                @endif

                                {{-- School --}}
                                <div class="col-md-4 form-group mb-3">
                                    <label for="school_id">School <span class="text-danger">*</span></label>
                                    <select name="school_id" id="school_id" class="form-select select2 @error('school_id') is-invalid @enderror" required> {{-- Use form-select --}}
                                        <option value="" disabled {{ old('school_id') ? '' : 'selected' }}>Select School...</option>
                                        @if($hasElevatedPrivileges)
                                            {{-- Admins see all schools, grouped by year --}}
                                            @php
                                                // Group schools by year for the dropdown
                                                $schoolsGrouped = $schools->groupBy('syear');
                                            @endphp
                                            @foreach($schoolsGrouped as $year => $schoolsInYear)
                                                <optgroup label="Year: {{ $year }}">
                                                    @foreach($schoolsInYear as $school)
                                                        <option value="{{ $school->id }}" data-year="{{ $school->syear }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>
                                                            {{ $school->title }} ({{ $year }})
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        @else
                                            {{-- Regular users see only current year schools (plucked list) --}}
                                            @foreach($schools as $id => $title)
                                                <option value="{{ $id }}" {{ old('school_id') == $id ? 'selected' : '' }}>{{ $title }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('school_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>

                                {{-- Grade Level --}}
                                <div class="col-md-4 form-group mb-3">
                                    <label for="grade_id">Grade Level <span class="text-danger">*</span></label>
                                    <select name="grade_id" id="grade_id" class="form-select select2 @error('grade_id') is-invalid @enderror" required> {{-- Use form-select --}}
                                        <option value="" disabled {{ old('grade_id') ? '' : 'selected' }}>Select Grade...</option>
                                        @if($hasElevatedPrivileges)
                                            {{-- Admins see all grades, maybe indicate school/year --}}
                                            {{-- Note: Backend validation ensures grade matches selected school/year --}}
                                            @php
                                                // Group grades by year for the dropdown
                                                $gradesGrouped = $grades->groupBy('school_syear');
                                            @endphp
                                            @foreach($gradesGrouped as $year => $gradesInYear)
                                                <optgroup label="Year: {{ $year }}">
                                                    @foreach($gradesInYear as $grade)
                                                        {{-- Add data attributes for potential JS filtering --}}
                                                        <option value="{{ $grade->id }}" data-year="{{ $grade->school_syear }}" data-school="{{ $grade->school_id }}" {{ old('grade_id') == $grade->id ? 'selected' : '' }}>
                                                            {{ $grade->title }} ({{ $grade->school->title ?? 'Unknown School' }} - {{ $year }})
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        @else
                                            {{-- Regular users see only current year grades (plucked list) --}}
                                            @foreach($grades as $id => $title)
                                                <option value="{{ $id }}" {{ old('grade_id') == $id ? 'selected' : '' }}>{{ $title }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('grade_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                    <input type="text" name="start_date" id="start_date" class="form-control date-picker @error('start_date') is-invalid @enderror"
                                           value="{{ old('start_date', now()->format('Y-m-d')) }}" required placeholder="YYYY-MM-DD">
                                    @error('start_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label for="enrollment_code">Enrollment Code</label>
                                    <input type="text" name="enrollment_code" id="enrollment_code" class="form-control @error('enrollment_code') is-invalid @enderror"
                                           value="{{ old('enrollment_code') }}" placeholder="e.g., New, Transfer In">
                                    @error('enrollment_code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <hr>

                            {{-- Custom Fields Section --}}
                            <h5 class="mb-3 mt-4 text-primary">Custom Fields (Optional)</h5>
                            {{-- Add custom fields based on Student model's fillable array --}}
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label for="custom_200000004">Custom Date Field 4</label> {{-- Example Label --}}
                                    <input type="text" name="custom_200000004" id="custom_200000004" class="form-control date-picker @error('custom_200000004') is-invalid @enderror"
                                           value="{{ old('custom_200000004') }}" placeholder="YYYY-MM-DD">
                                    @error('custom_200000004') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label for="custom_200000005">Custom Field 5</label>
                                    <input type="text" name="custom_200000005" id="custom_200000005" class="form-control @error('custom_200000005') is-invalid @enderror"
                                           value="{{ old('custom_200000005') }}">
                                    @error('custom_200000005') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label for="custom_200000006">Custom Field 6</label>
                                    <input type="text" name="custom_200000006" id="custom_200000006" class="form-control @error('custom_200000006') is-invalid @enderror"
                                           value="{{ old('custom_200000006') }}">
                                    @error('custom_200000006') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 form-group mb-3">
                                    <label for="custom_200000007">Custom Field 7</label>
                                    <input type="text" name="custom_200000007" id="custom_200000007" class="form-control @error('custom_200000007') is-invalid @enderror"
                                           value="{{ old('custom_200000007') }}">
                                    @error('custom_200000007') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label for="custom_200000008">Custom Field 8</label>
                                    <input type="text" name="custom_200000008" id="custom_200000008" class="form-control @error('custom_200000008') is-invalid @enderror"
                                           value="{{ old('custom_200000008') }}">
                                    @error('custom_200000008') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label for="custom_200000010">Custom Field 10 (Char 1)</label>
                                    <input type="text" name="custom_200000010" id="custom_200000010" maxlength="1" class="form-control @error('custom_200000010') is-invalid @enderror"
                                           value="{{ old('custom_200000010') }}">
                                    @error('custom_200000010') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label for="custom_200000009">Custom Field 9 (Long Text)</label>
                                    <textarea name="custom_200000009" id="custom_200000009" class="form-control @error('custom_200000009') is-invalid @enderror" rows="2">{{ old('custom_200000009') }}</textarea>
                                    @error('custom_200000009') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label for="custom_200000011">Custom Field 11 (Long Text)</label>
                                    <textarea name="custom_200000011" id="custom_200000011" class="form-control @error('custom_200000011') is-invalid @enderror" rows="2">{{ old('custom_200000011') }}</textarea>
                                    @error('custom_200000011') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>


                            {{-- Submit Button --}}
                            <div class="text-center border-top pt-3 mt-3">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus-circle me-1"></i> Add Student
                                </button>
                                <a href="{{ route('staff.students.index') }}" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times-circle me-1"></i> Cancel
                                </a>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

{{-- Keep existing @push sections for scripts/styles --}}
@push('scripts')
    <script src="{{ asset('vendor/moment/moment.min.js') }}"></script>
    <script src="{{ asset('vendor/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(function () {
            // Initialize Select2 Elements
            $('.select2').select2({
                theme: "bootstrap4",
                width: "100%",
            });

            // Initialize Tempus Dominus Date Picker
            $('.date-picker').datetimepicker({
                format: 'YYYY-MM-DD',
                useCurrent: false,
                icons: {
                    time: 'far fa-clock',
                    date: 'far fa-calendar-alt',
                    up: 'fas fa-chevron-up',
                    down: 'fas fa-chevron-down',
                    previous: 'fas fa-chevron-left',
                    next: 'fas fa-chevron-right',
                    today: 'far fa-calendar-check',
                    clear: 'far fa-trash-alt',
                    close: 'fas fa-times'
                }
            });

            // Optional: Add JS to filter grades based on selected school/year for admins
            // This requires more complex logic, potentially AJAX
             if ({{ $hasElevatedPrivileges ? 'true' : 'false' }}) {
                const allGrades = $('#grade_id option').clone(); // Store all grade options initially

                function filterGrades() {
                    const selectedYear = $('#syear').val();
                    const selectedSchool = $('#school_id').val();
                    const gradeSelect = $('#grade_id');
                    const currentGradeVal = gradeSelect.val(); // Preserve selection if possible

                    gradeSelect.empty().append('<option value="" disabled selected>Select Grade...</option>'); // Clear and add default

                    allGrades.each(function() {
                        const gradeOption = $(this);
                        const gradeYear = gradeOption.data('year');
                        const gradeSchool = gradeOption.data('school');

            //             // Check if the option's year and school match the selections
                        if (gradeOption.val() && // Ignore the placeholder
                            (!selectedYear || gradeYear === selectedYear) &&
                            (!selectedSchool || gradeSchool === selectedSchool))
                        {
                            gradeSelect.append(gradeOption.clone()); // Add matching option
                        }
                    });

            //         // Try to reselect the previously selected grade if it's still valid
                    gradeSelect.val(currentGradeVal).trigger('change.select2');
                }

                $('#syear, #school_id').on('change', filterGrades);

            //     // Initial filter on page load if values are pre-selected (e.g., validation failure)
                filterGrades(); // Uncomment if needed, ensure data attributes are present
            }
        });
    </script>
@endpush

@push('styles')
    {{-- Include styles for Select2 and Date Picker if not already in layout --}}
    {{-- Ensure these paths are correct for your project setup --}}
    <link rel="stylesheet" href="{{ asset('vendor/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush
