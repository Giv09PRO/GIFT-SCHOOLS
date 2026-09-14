{{-- resources/views/pages/staff/student/bulk_enroll.blade.php --}}

@extends('layouts.app') {{-- Use your main layout file --}}

@php
    use App\Helpers\Qs;
    // Variables passed from controller:
    // $sourceGrades, $targetGrades, $targetSchools, $previousYear, $currentYear
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Bulk Enroll Students')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        {{-- Display Session Flash Messages --}}
        @include('layouts.partials.flash_messages')

        <div class="row justify-content-center">
            <div class="col-md-8"> {{-- Adjust column width as needed --}}

                {{-- Form Card --}}
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Enroll Students from Previous Year</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">This tool enrolls students who were active in a selected grade last year ({{ $previousYear }}) into a selected grade and school for the current year ({{ $currentYear }}). Students who already have an enrollment record for {{ $currentYear }} will be skipped.</p>
                        <hr>
                        <form method="POST" action="{{ route('staff.students.bulk_enroll.process') }}">
                            @csrf

                            {{-- Source Grade --}}
                            <div class="form-group mb-3">
                                <label for="source_grade_id">Source Grade Level (from {{ $previousYear }}) <span class="text-danger">*</span></label>
                                <select name="source_grade_id" id="source_grade_id" class="form-control select2 @error('source_grade_id') is-invalid @enderror" required>
                                    <option value="" disabled {{ old('source_grade_id') ? '' : 'selected' }}>Select Previous Year Grade...</option>
                                    @foreach ($sourceGrades as $id => $title)
                                        <option value="{{ $id }}" {{ old('source_grade_id') == $id ? 'selected' : '' }}>
                                            {{ $title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('source_grade_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            <hr>
                            <h5 class="text-primary">Target Enrollment for {{ $currentYear }}</h5>

                            {{-- Target School --}}
                            <div class="form-group mb-3">
                                <label for="target_school_id">Target School (for {{ $currentYear }}) <span class="text-danger">*</span></label>
                                <select name="target_school_id" id="target_school_id" class="form-control select2 @error('target_school_id') is-invalid @enderror" required>
                                    <option value="" disabled {{ old('target_school_id') ? '' : 'selected' }}>Select Target School...</option>
                                    @foreach ($targetSchools as $id => $title)
                                        <option value="{{ $id }}" {{ old('target_school_id') == $id ? 'selected' : '' }}>
                                            {{ $title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('target_school_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Target Grade --}}
                            <div class="form-group mb-3">
                                <label for="target_grade_id">Target Grade Level (for {{ $currentYear }}) <span class="text-danger">*</span></label>
                                <select name="target_grade_id" id="target_grade_id" class="form-control select2 @error('target_grade_id') is-invalid @enderror" required>
                                    <option value="" disabled {{ old('target_grade_id') ? '' : 'selected' }}>Select Target Grade...</option>
                                    @foreach ($targetGrades as $id => $title)
                                        <option value="{{ $id }}" {{ old('target_grade_id') == $id ? 'selected' : '' }}>
                                            {{ $title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('target_grade_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Start Date --}}
                            <div class="form-group mb-3">
                                <label for="start_date">Enrollment Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                       value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
                                @error('start_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Enrollment Code --}}
                            <div class="form-group mb-3">
                                <label for="enrollment_code">Enrollment Code</label>
                                <input type="text" name="enrollment_code" id="enrollment_code" class="form-control @error('enrollment_code') is-invalid @enderror"
                                       value="{{ old('enrollment_code', 'Re-enroll') }}" placeholder="e.g., Re-enroll, Promoted">
                                @error('enrollment_code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>


                            {{-- Submit Button --}}
                            <div class="text-center border-top pt-3 mt-4">
                                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to enroll students from the selected grade into the target grade/school for {{ $currentYear }}? This will skip students already enrolled this year.')">
                                    <i class="fas fa-check-double me-1"></i> Process Bulk Enrollment
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

@section('css')
    {{-- Add CSS for Select2 if using it --}}
    {{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> --}}
@stop

@section('js')
    {{-- Add JS for Select2 if using it --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
    <script>
        $(document).ready(function() {
            // Initialize Select2 if using the plugin
            // if ($.fn.select2) {
            //     $('.select2').select2({ theme: 'bootstrap-5' }); // Or appropriate theme
            // }
            console.log('Bulk enroll page loaded!');
        });
    </script>
@stop

