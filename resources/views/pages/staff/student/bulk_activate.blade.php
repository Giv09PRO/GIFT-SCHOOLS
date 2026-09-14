@extends('layouts.app') {{-- Use your main layout file --}}

@php
    use App\Helpers\Qs;
    // Variables passed from controller:
    // $targetGrades, $targetSchools, $currentYear, $unEnrolledCount
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Bulk Activate Students')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        {{-- Display Session Flash Messages --}}
        @include('layouts.partials.flash_messages')

        <div class="row justify-content-center">
            <div class="col-md-8"> {{-- Adjust column width as needed --}}

                {{-- Form Card --}}
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Create Initial Enrollment for Unenrolled Students</h3>
                    </div>
                    <div class="card-body">
                        @if($unEnrolledCount > 0)
                            <p class="text-info">This tool will create the <strong>first enrollment record</strong> for the <strong>{{ $unEnrolledCount }}</strong> students currently in the system who have no enrollment history.</p>
                            <p>Select the target school, grade, and start date for their initial enrollment in the <strong>{{ $currentYear }}</strong> school year.</p>
                            <hr>
                            <form method="POST" action="{{ route('staff.students.bulk_activate.process') }}">
                                @csrf

                                <h5 class="text-primary">Target Enrollment for {{ $currentYear }}</h5>

                                {{-- Target School --}}
                                <div class="form-group mb-3">
                                    <label for="target_school_id">Target School <span class="text-danger">*</span></label>
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
                                    <label for="target_grade_id">Target Grade Level <span class="text-danger">*</span></label>
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
                                           value="{{ old('enrollment_code', 'Initial Enroll') }}" placeholder="e.g., Initial Enroll, New">
                                    @error('enrollment_code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>


                                {{-- Submit Button --}}
                                <div class="text-center border-top pt-3 mt-4">
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to create the first enrollment record for all {{ $unEnrolledCount }} unenrolled students using these details?');">
                                        <i class="fas fa-user-check me-1"></i> Process Initial Enrollment
                                    </button>
                                    <a href="{{ route('staff.students.index') }}" class="btn btn-secondary ms-2">
                                        <i class="fas fa-times-circle me-1"></i> Cancel
                                    </a>
                                </div>

                            </form>
                        @else
                            <div class="alert alert-warning text-center">
                                There are currently no students in the system without any enrollment records. This tool is not needed.
                            </div>
                            <div class="text-center">
                                <a href="{{ route('staff.students.index') }}" class="btn btn-secondary ms-2">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Student List
                                </a>
                            </div>
                        @endif
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
            console.log('Bulk activate page loaded!');
        });
    </script>
@stop
