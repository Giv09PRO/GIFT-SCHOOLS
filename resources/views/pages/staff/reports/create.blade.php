{{-- resources/views/pages/staff/reportcards/create.blade.php --}}

@extends('layouts.app')

@php
    // Variables passed from ReportCardController's create method:
    // $students, $markingPeriods, $gradeLevels
    // $currentSchoolId, $currentSyear
@endphp

@section('title', 'Generate New Report Card')
@section('subtitle', 'Create a report card for a student')

@section('content_body')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 offset-md-1"> {{-- Increased width for more space --}}
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Report Card Details</h3>
                    </div>
                    <form method="POST" action="{{ route('staff.reportcards.store') }}" id="createReportCardForm">
                        @csrf
                        {{-- Hidden fields for school and year context --}}
                        <input type="hidden" name="school_id" value="{{ $currentSchoolId }}">
                        <input type="hidden" name="syear" value="{{ $currentSyear }}">

                        <div class="card-body">
                            {{-- Display validation errors --}}
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                    <h5><i class="icon fas fa-ban"></i> Validation Errors!</h5>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row">
                                {{-- Student Selection --}}
                                <div class="col-md-6 form-group mb-3">
                                    <label for="student_id">Student <span class="text-danger">*</span></label>
                                    <select name="student_id" id="student_id" class="form-control select2 @error('student_id') is-invalid @enderror" required>
                                        <option value="">Select a Student</option>
                                        @if(isset($students))
                                            @foreach ($students as $id => $studentDisplay)
                                                <option value="{{ $id }}" {{ old('student_id') == $id ? 'selected' : '' }}>
                                                    {{ $studentDisplay }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('student_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                {{-- Marking Period Selection --}}
                                <div class="col-md-6 form-group mb-3">
                                    <label for="marking_period_id">Marking Period <span class="text-danger">*</span></label>
                                    <select name="marking_period_id" id="marking_period_id" class="form-control select2 @error('marking_period_id') is-invalid @enderror" required>
                                        <option value="">Select a Marking Period</option>
                                        @if(isset($markingPeriods))
                                            @foreach ($markingPeriods as $id => $title)
                                                <option value="{{ $id }}" {{ old('marking_period_id') == $id ? 'selected' : '' }}>
                                                    {{ $title }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('marking_period_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                {{-- Grade Level (of student at time of report card) --}}
                                <div class="col-md-6 form-group mb-3">
                                    <label for="grade_id">Student's Grade Level (at time of report)</label>
                                    <select name="grade_id" id="grade_id" class="form-control select2 @error('grade_id') is-invalid @enderror">
                                        <option value="">Select Grade Level (Optional)</option>
                                        @if(isset($gradeLevels))
                                            @foreach ($gradeLevels as $id => $title)
                                                <option value="{{ $id }}" {{ old('grade_id') == $id ? 'selected' : '' }}>
                                                    {{ $title }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <small class="form-text text-muted">Select the student's grade level for this marking period.</small>
                                    @error('grade_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                {{-- Is Published --}}
                                <div class="col-md-6 form-group mb-3">
                                    <label for="is_published">Publish Status <span class="text-danger">*</span></label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input @error('is_published') is-invalid @enderror" type="radio" name="is_published" id="is_published_no" value="0" {{ old('is_published', '0') == '0' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="is_published_no">Not Published</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input @error('is_published') is-invalid @enderror" type="radio" name="is_published" id="is_published_yes" value="1" {{ old('is_published') == '1' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="is_published_yes">Published</label>
                                        </div>
                                    </div>
                                    @error('is_published')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>


                            {{-- Subject Grades --}}
                            <div class="form-group mb-3">
                                <label for="subject_grades">Subject Grades (JSON Format)</label>
                                <textarea name="subject_grades" id="subject_grades" class="form-control @error('subject_grades') is-invalid @enderror"
                                          rows="8" placeholder='Enter subject grades as JSON, e.g., [{"subject_id":1,"grade":"A","comment":"Excellent work!"},{"subject_id":2,"grade":"B+","comment":"Good effort."}]'>{{ old('subject_grades') }}</textarea>
                                <small class="form-text text-muted">
                                    Enter as a JSON array of objects. Each object should have "subject_id", "grade", and "comment". <br>
                                    Example: <code>[{"subject_id": 1, "grade": "A", "comment": "Great job!"}, {"subject_id": 2, "grade": "B"}]</code><br>
                                    <strong>Note:</strong> For a more user-friendly input, this section can be enhanced with dynamic rows for each subject using JavaScript.
                                    If you use a structured input (e.g., dynamic rows) that submits an array named <code>subject_grades_structured</code>, the controller will prioritize it.
                                </small>
                                @error('subject_grades')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                @error('subject_grades_structured.*') {{-- Catch errors from structured input validation --}}
                                <span class="invalid-feedback d-block" role="alert"><strong>Error in subject grades input: {{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- General Comments --}}
                            <div class="form-group mb-3">
                                <label for="comments">General Comments</label>
                                <textarea name="comments" id="comments" class="form-control @error('comments') is-invalid @enderror"
                                          rows="4" placeholder="Enter any overall comments for the report card">{{ old('comments') }}</textarea>
                                @error('comments')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Report Card</button>
                            <a href="{{ route('staff.reportcards.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    {{-- Link Select2 CSS if not globally available --}}
    {{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> --}}
    <style>
        .select2-container .select2-selection--single {
            height: calc(1.5em + .75rem + 2px); /* Adjust to match Bootstrap's default input height */
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(1.5em + .75rem);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .75rem - 1px);
        }
        #subject_grades {
            font-family: monospace;
        }
    </style>
@endpush

@push('scripts')
    {{-- Link Select2 JS if not globally available --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
    <script>
        $(document).ready(function() {
            // Initialize Select2
            if ($.fn.select2) { // Check if Select2 is loaded
                $('#student_id, #marking_period_id, #grade_id').select2({
                    theme: 'bootstrap-5', // Ensure this theme CSS is loaded for AdminLTE 3/Bootstrap 5
                    placeholder: $(this).data('placeholder') || 'Select an option',
                    allowClear: true,
                    width: '100%'
                });
            }

            // Logic to dynamically fetch students based on grade_id selection (if needed for filtering student list further)
            $('#grade_id').on('change', function() {
                var gradeId = $(this).val();
                var studentSelect = $('#student_id');
            //     // AJAX call to fetch students for this grade and current school/year
            //     // This would require a dedicated route and controller method.
            //     // For now, the controller provides a list of all enrolled students for the school/year.
            //     // The create method in controller can already filter by grade_id if passed as a query param.
                 if(gradeId) {
            //         // To refilter on client side, you might reload the page with grade_id
            window.location.href = '{{ route('staff.reportcards.create') }}?grade_id=' + gradeId;
                }
            });

            console.log('Report Cards create page loaded!');
        });
    </script>
@endpush
