{{-- resources/views/pages/staff/student/edit.blade.php --}}

@extends('layouts.app')

@php
    use App\Helpers\Qs;
    // Determine the year for which enrollment details are being edited.
    // Priority: old input 'syear', then current enrollment's year, then current school year.
    $enrollmentYearForForm = old('syear', $enrollmentToEdit?->syear ?: Qs::getCurrentSchoolYear());
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Edit Student: ' . $student->first_name . ' ' . $student->last_name)

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        {{-- Display Session Flash Messages --}}
        @include('layouts.partials.flash_messages')

        <div class="row justify-content-center">
            {{-- Edit Form Card --}}
            <div class="card card-info card-outline"  style="width: 95%;">
                <div class="card-header">
                    <h3 class="card-title">Editing Profile for: {{ $student->first_name }} {{ $student->last_name }} (ID: {{ $student->id }})</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('staff.students.update', $student->id) }}">
                        @csrf
                        @method('PUT') {{-- Use PUT method for updates --}}

                        {{-- Student Demographics Section --}}
                        <h5 class="mb-3 mt-2 text-primary">Student Information</h5>
                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name', $student->last_name) }}" required>
                                @error('last_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="first_name">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name', $student->first_name) }}" required>
                                @error('first_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" name="middle_name" id="middle_name" class="form-control @error('middle_name') is-invalid @enderror"
                                       value="{{ old('middle_name', $student->middle_name) }}">
                                @error('middle_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-1 form-group mb-3">
                                <label for="name_suffix">Suffix</label>
                                <input type="text" name="name_suffix" id="name_suffix" class="form-control @error('name_suffix') is-invalid @enderror"
                                       value="{{ old('name_suffix', $student->name_suffix) }}">
                                @error('name_suffix') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="row">
                          <div class="col-md-4 form-group mb-3">
                                <label for="prem_number">PReM Number</label>
                                <input type="text" name="prem_number" id="prem_number" class="form-control @error('prem_number') is-invalid @enderror"
                                       value="{{ old('prem_number', $student->prem_number) }}">
                                @error('prem_number') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="gender">Gender <span class="text-danger">*</span></label>
                                <select name="gender" id="gender" class="form-control @error('gender') is-invalid @enderror" required>
                                    <option value="" disabled {{ old('gender', $student->gender) ? '' : 'selected' }}>Select Gender</option>
                                    <option value="Male" {{ old('gender', $student->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender', $student->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                                @error('gender') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label for="dob">Date of Birth</label>
                                <input type="date" name="dob" id="dob" class="form-control @error('dob') is-invalid @enderror"
                                       value="{{ old('dob', $student->dob ? \Carbon\Carbon::parse($student->dob)->format('Y-m-d') : '') }}">
                                @error('dob') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $student->email) }}">
                                @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="phone">Phone Number</label>
                                <input type="tel" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $student->phone) }}">
                                @error('phone') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address', $student->address) }}</textarea>
                            @error('address') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <hr>

                        {{-- Enrollment Information Section --}}
                        {{-- This section edits/creates an enrollment record for the selected 'syear' --}}
                        <h5 class="mb-3 mt-4 text-primary">Enrollment Information</h5>
                        <p class="text-muted small">
                            Edit the enrollment record for the selected school year.
                            If an enrollment for this student in the selected year already exists, it will be updated.
                            Otherwise, a new enrollment record will be created for the selected year.
                        </p>

                        <div class="row">
                            <div class="col-md-3 form-group mb-3">
                                <label for="syear">Enrollment Year <span class="text-danger">*</span></label>
                                <select name="syear" id="syear" class="form-control @error('syear') is-invalid @enderror" required>
                                    @foreach($years as $yearOption)
                                        <option value="{{ $yearOption }}" {{ $enrollmentYearForForm == $yearOption ? 'selected' : '' }}>
                                            {{ $yearOption }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('syear') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-5 form-group mb-3">
                                <label for="school_id">School <span class="text-danger">*</span></label>
                                <select name="school_id" id="school_id" class="form-control select2 @error('school_id') is-invalid @enderror" required>
                                    <option value="">Loading Schools...</option> {{-- Placeholder --}}
                                </select>
                                @error('school_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="grade_id">Grade Level <span class="text-danger">*</span></label>
                                <select name="grade_id" id="grade_id" class="form-control select2 @error('grade_id') is-invalid @enderror" required>
                                    <option value="">Select School First...</option> {{-- Placeholder --}}
                                </select>
                                @error('grade_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 form-group mb-3">
                                <label for="start_date">Enrollment Start<span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                       value="{{ old('start_date', $enrollmentToEdit?->start_date ? \Carbon\Carbon::parse($enrollmentToEdit->start_date)->format('Y-m-d') : '') }}" required>
                                @error('start_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="end_date">Enrollment End</label>
                                <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror"
                                       value="{{ old('end_date', $enrollmentToEdit?->end_date ? \Carbon\Carbon::parse($enrollmentToEdit->end_date)->format('Y-m-d') : '') }}">
                                <small class="form-text text-muted">Set this to mark student as inactive/quit for this year.</small>
                                @error('end_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="enrollment_code">Enrollment Code</label>
                                <input type="text" name="enrollment_code" id="enrollment_code" class="form-control @error('enrollment_code') is-invalid @enderror"
                                       value="{{ old('enrollment_code', $enrollmentToEdit?->enrollment_code) }}">
                                @error('enrollment_code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="drop_code">Drop Code</label>
                                <input type="text" name="drop_code" id="drop_code" class="form-control @error('drop_code') is-invalid @enderror"
                                       value="{{ old('drop_code', $enrollmentToEdit?->drop_code) }}">
                                <small class="form-text text-muted">Reason for leaving, if applicable.</small>
                                @error('drop_code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <hr>

                        {{-- Custom Fields Section (Example) --}}
                        <h5 class="mb-3 mt-4 text-primary">Custom Fields</h5>
                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label for="custom_200000004">Family ID</label> {{-- Example Label --}}
                                <input type="text" name="custom_200000004" id="custom_200000004" class="form-control @error('custom_200000004') is-invalid @enderror"
                                       value="{{ old('custom_200000004', $student->custom_200000004) }}">
                                @error('custom_200000004') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="custom_200000005">Custom Field 5</label>
                                <input type="text" name="custom_200000005" id="custom_200000005" class="form-control @error('custom_200000005') is-invalid @enderror"
                                       value="{{ old('custom_200000005', $student->custom_200000005) }}">
                                @error('custom_200000005') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="custom_200000006">Custom Field 6</label>
                                <input type="text" name="custom_200000006" id="custom_200000006" class="form-control @error('custom_200000006') is-invalid @enderror"
                                       value="{{ old('custom_200000006', $student->custom_200000006) }}">
                                @error('custom_200000006') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            {{-- Add other custom fields based on your StudentController's validation and Student model's fillable properties --}}
                            {{-- Example for date custom field if it exists and needs Y-m-d format
                            <div class="col-md-4 form-group mb-3">
                                <label for="custom_date_field">Custom Date</label>
                                <input type="date" name="custom_date_field" id="custom_date_field" class="form-control @error('custom_date_field') is-invalid @enderror"
                                       value="{{ old('custom_date_field', $student->custom_date_field ? \Carbon\Carbon::parse($student->custom_date_field)->format('Y-m-d') : '') }}">
                                @error('custom_date_field') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            --}}
                        </div>


                        {{-- Submit Button --}}
                        <div class="text-center border-top pt-3 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Student & Enrollment
                            </button>
                            <a href="{{ route('staff.students.show', $student->id) }}" class="btn btn-secondary ms-2">
                                <i class="fas fa-times-circle me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@stop


@section('css')
    {{-- Add CSS for Select2 if you are using it, e.g., from a CDN or local asset --}}
    {{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> --}}
@stop
@section('js')
    <script>
        $(document).ready(function () {
            const initialYear = $('#syear').val();
            const initialSchoolId = '{{ old('school_id', $enrollmentToEdit?->school_id) }}';
            const initialGradeId = '{{ old('grade_id', $enrollmentToEdit?->grade_id) }}';

            const schoolsUrl = "{{ route('staff.students.schools_by_syear') }}";
            const gradesUrl = "{{ route('staff.students.grades_by_school_syear') }}";

            function loadSchools(year, selectedSchoolId = null, callback) {
                const $schoolSelect = $('#school_id');
                $schoolSelect.empty().append('<option value="">Loading Schools...</option>');
                $('#grade_id').empty().append('<option value="">Select School First...</option>');

                if (!year) {
                    $schoolSelect.empty().append('<option value="">Select Year First...</option>');
                    if (typeof callback === 'function') callback();
                    return;
                }

                $.ajax({
                    url: schoolsUrl,
                    method: 'GET',
                    data: { syear: year },
                    dataType: 'json',
                    success: function (response) {
                        $schoolSelect.empty().append('<option value="">Select School for Year ' + year + '...</option>');
                        if (response && !$.isEmptyObject(response)) {
                            $.each(response, function (id, title) {
                                $schoolSelect.append($('<option>', {
                                    value: id,
                                    text: title,
                                    selected: id == selectedSchoolId
                                }));
                            });
                        } else {
                            $schoolSelect.empty().append('<option value="">No schools found for ' + year + '</option>');
                        }
                        $schoolSelect.trigger('change');
                        if (typeof callback === 'function') callback();
                    },
                    error: function (xhr) {
                        console.error("Error loading schools:", xhr.responseText);
                        $schoolSelect.empty().append('<option value="">Error loading schools</option>');
                        if (typeof callback === 'function') callback();
                    }
                });
            }

            function loadGrades(year, schoolId, selectedGradeId = null) {
                const $gradeSelect = $('#grade_id');
                $gradeSelect.empty().append('<option value="">Loading Grades...</option>');

                if (!year || !schoolId) {
                    $gradeSelect.empty().append('<option value="">Select Year and School First...</option>');
                    return;
                }

                $.ajax({
                    url: gradesUrl,
                    method: 'GET',
                    data: { syear: year, school_id: schoolId },
                    dataType: 'json',
                    success: function (response) {
                        $gradeSelect.empty().append('<option value="">Select Grade for Year ' + year + '...</option>');
                        if (response && !$.isEmptyObject(response)) {
                            $.each(response, function (id, title) {
                                $gradeSelect.append($('<option>', {
                                    value: id,
                                    text: title,
                                    selected: id == selectedGradeId
                                }));
                            });
                        } else {
                            $gradeSelect.empty().append('<option value="">No grades found</option>');
                        }
                    },
                    error: function (xhr) {
                        console.error("Error loading grades:", xhr.responseText);
                        $gradeSelect.empty().append('<option value="">Error loading grades</option>');
                    }
                });
            }

            $('#syear').change(function () {
                const selectedYear = $(this).val();
                loadSchools(selectedYear);
            });

            $('#school_id').change(function () {
                const selectedYear = $('#syear').val();
                const selectedSchoolId = $(this).val();
                if (selectedSchoolId) {
                    loadGrades(selectedYear, selectedSchoolId);
                } else {
                    $('#grade_id').empty().append('<option value="">Select School First...</option>');
                }
            });

            if (initialYear) {
                loadSchools(initialYear, initialSchoolId, function() {
                    if (initialSchoolId && $('#school_id').val() == initialSchoolId) {
                        loadGrades(initialYear, initialSchoolId, initialGradeId);
                    } else if (initialSchoolId) {
                        $('#grade_id').empty().append('<option value="">Select School First...</option>');
                    }
                });
            } else {
                $('#school_id').empty().append('<option value="">Select Year First...</option>');
                $('#grade_id').empty().append('<option value="">Select Year First...</option>');
            }

            // 👇 Updated username suggestion button click handler
            $('#suggest_username_btn').click(function () {
                const firstName = $('#first_name').val().trim();
                const schoolId = $('#school_id').val();

                if (!firstName) {
                    alert('Please enter the Last Name to suggest a username.');
                    return;
                }

                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: "{{ route('staff.students.ajax.suggest_username') }}",
                    method: 'GET',
                    data: {
                        first_name: firstName,
                        school_id: schoolId
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.username) {
                            $('#username').val(response.username);
                        } else {
                            $('#username').val(firstName.toLowerCase().replace(/[^a-z0-9]/gi, '') + '01');
                        }
                    },
                    error: function (xhr) {
                        console.error("Error suggesting username:", xhr.responseText);
                        alert('An error occurred while suggesting the username. Please try again or enter manually.');
                        $('#username').val(firstName.toLowerCase().replace(/[^a-z0-9]/gi, '') + '01');
                    },
                    complete: function () {
                        $('#suggest_username_btn').prop('disabled', false).html('<i class="fas fa-magic"></i>');
                    }
                });
            });
        });
    </script>
@stop

