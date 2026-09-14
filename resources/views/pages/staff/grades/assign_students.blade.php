{{-- resources/views/pages/staff/grades/assign_students.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Assign Students to Class - ' . e($grade->title))

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Assign Students Form Card --}}
                <div class="card card-warning"> {{-- Changed color for distinction --}}
                    <div class="card-header">
                        <h3 class="card-title">Select Students to Assign (Year: {{ $currentYear }})</h3>
                    </div>
                    {{-- Form Start --}}
                    <form method="POST" action="{{ route('staff.grades.assign.process', $grade) }}"> {{-- Use $grade object for route model binding --}}
                        @csrf {{-- CSRF Protection --}}

                        <div class="card-body">
                            {{-- Display Alerts --}}
                            @include('layouts.partials.alerts') {{-- Use the correct path to your alerts partial --}}

                            <div class="callout callout-info">
                                <p>Select one or more students from the list below who are not currently assigned to an active classfor the <strong>{{ $currentYear }}</strong> school year. They will be enrolled in <strong>{{ e($grade->title) }}</strong> at <strong>{{ e($grade->school->title ?? 'N/A') }}</strong>.</p>
                            </div>

                            {{-- Student Selection --}}
                            <div class="form-group">
                                <label for="student_ids">Select Students <span class="text-danger">*</span></label>
                                <select name="student_ids[]" id="student_ids" class="form-control select2-students @error('student_ids') is-invalid @enderror" multiple="multiple" required data-placeholder="Select students...">
                                    @foreach($unassignedStudents as $student)
                                        {{-- Format option text for clarity --}}
                                        <option value="{{ $student->id }}" {{ in_array($student->id, old('student_ids', [])) ? 'selected' : '' }}>
                                            {{ e($student->last_name) }}, {{ e($student->first_name) }} ({{ e($student->prem_number ?: $student->username) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('student_ids')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                @error('student_ids.*') {{-- Catch errors for individual array elements --}}
                                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                @if($unassignedStudents->isEmpty())
                                    <small class="form-text text-muted">No students found needing assignment for the {{ $currentYear }} school year.</small>
                                @endif
                            </div>

                            <div class="row">
                                {{-- Start Date --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="start_date">Enrollment Start Date <span class="text-danger">*</span></label>
                                        <input type="text" name="start_date" id="start_date" class="form-control date-picker @error('start_date') is-invalid @enderror" value="{{ old('start_date', now()->format('Y-m-d')) }}" required placeholder="YYYY-MM-DD">
                                        @error('start_date')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Enrollment Code --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="enrollment_code">Enrollment Code</label>
                                        <input type="text" name="enrollment_code" id="enrollment_code" class="form-control @error('enrollment_code') is-invalid @enderror" value="{{ old('enrollment_code', 'Assigned') }}" placeholder="e.g., Assigned, Re-enrolled">
                                        @error('enrollment_code')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                        </div>
                        <div class="card-footer text-right">
                            <a href="{{ route('staff.grades.index') }}" class="btn btn-default">Cancel</a>
                            {{-- Disable button if no students are available --}}
                            <button type="submit" class="btn btn-warning" {{ $unassignedStudents->isEmpty() ? 'disabled' : '' }}>Assign Selected Students</button>
                        </div>
                    </form>
                    {{-- Form End --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Include scripts for Select2 and Date Picker if not already in layout --}}
    <script src="{{ asset('vendor/moment/moment.min.js') }}"></script> {{-- Adjust path as needed --}}
    <script src="{{ asset('vendor/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script> {{-- Adjust path as needed --}}
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script> {{-- Adjust path as needed --}}
    <script>
        $(function () {
            // Initialize Select2 Element for students
            $('.select2-students').select2({
                theme: 'bootstrap4', // Optional: Use Bootstrap 4 theme
                allowClear: true // Optional: Add a clear button
            });

            // Initialize Tempus Dominus Date Picker
            $('.date-picker').datetimepicker({
                format: 'YYYY-MM-DD', // Date only format
                useCurrent: false,
                icons: {
                    time: 'far fa-clock', date: 'far fa-calendar-alt', up: 'fas fa-chevron-up',
                    down: 'fas fa-chevron-down', previous: 'fas fa-chevron-left', next: 'fas fa-chevron-right',
                    today: 'far fa-calendar-check', clear: 'far fa-trash-alt', close: 'fas fa-times'
                }
            });
        });
    </script>
@endpush

@push('styles')
    {{-- Include styles for Select2 and Date Picker if not already in layout --}}
    <link rel="stylesheet" href="{{ asset('vendor/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}"> {{-- Adjust path as needed --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}"> {{-- Adjust path as needed --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}"> {{-- Adjust path as needed --}}
@endpush
