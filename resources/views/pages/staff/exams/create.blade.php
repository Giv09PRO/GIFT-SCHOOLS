```blade
@extends('layouts.app')

@section('title', 'Create Exam')
@section('subtitle', 'Add New Exam')

@section('content_body')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create New Exam</h3>
                <div class="card-tools">
                    <a href="{{ route('staff.exams.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Exams
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.exams.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <!-- School Selection -->
                        <div class="col-md-6 form-group">
                            <label for="school_id">School <span class="text-danger">*</span></label>
                            <select name="school_id" id="school_id" class="form-control @error('school_id') is-invalid @enderror">
                                @if($allSchoolsAccess)
                                    <option value="">Select School</option>
                                @endif
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}" {{ old('school_id', auth()->user()->current_school_id) == $school->id ? 'selected' : '' }}>
                                        {{ $school->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('school_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- School Year -->
                        <div class="col-md-6 form-group">
                            <label for="syear">School Year <span class="text-danger">*</span></label>
                            <input type="number" name="syear" id="syear" class="form-control @error('syear') is-invalid @enderror" value="{{ old('syear', now()->year) }}" min="2000" max="2099">
                            @error('syear')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Marking Period -->
                        <div class="col-md-6 form-group">
                            <label for="marking_period_id">Marking Period <span class="text-danger">*</span></label>
                            <select name="marking_period_id" id="marking_period_id" class="form-control @error('marking_period_id') is-invalid @enderror">
                                <option value="">Select Marking Period</option>
                                @foreach($markingPeriods as $markingPeriod)
                                    <option value="{{ $markingPeriod->marking_period_id }}" {{ old('marking_period_id') == $markingPeriod->marking_period_id ? 'selected' : '' }}>
                                        {{ $markingPeriod->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('marking_period_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Exam Type -->
                        <div class="col-md-6 form-group">
                            <label for="type">Exam Type <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-control @error('type') is-invalid @enderror">
                                <option value="midterm" {{ old('type') == 'midterm' ? 'selected' : '' }}>Midterm</option>
                                <option value="final" {{ old('type') == 'final' ? 'selected' : '' }}>Final</option>
                                <option value="quiz" {{ old('type') == 'quiz' ? 'selected' : '' }}>Quiz</option>
                            </select>
                            @error('type')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Description -->
                        <div class="col-md-12 form-group">
                            <label for="description">Description</label>
                            <input type="text" name="description" id="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description') }}" maxlength="255">
                            @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Weight -->
                        <div class="col-md-6 form-group">
                            <label for="weight">Weight <span class="text-danger">*</span></label>
                            <input type="number" name="weight" id="weight" class="form-control @error('weight') is-invalid @enderror" value="{{ old('weight', 1.00) }}" step="0.01" min="0" max="100">
                            @error('weight')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Max Score -->
                        <div class="col-md-6 form-group">
                            <label for="max_score">Max Score</label>
                            <input type="number" name="max_score" id="max_score" class="form-control @error('max_score') is-invalid @enderror" value="{{ old('max_score') }}" step="0.01" min="0">
                            @error('max_score')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Exam Start Date -->
                        <div class="col-md-6 form-group">
                            <label for="exam_start_date">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="exam_start_date" id="exam_start_date" class="form-control @error('exam_start_date') is-invalid @enderror" value="{{ old('exam_start_date') }}">
                            @error('exam_start_date')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Exam End Date -->
                        <div class="col-md-6 form-group">
                            <label for="exam_end_date">End Date</label>
                            <input type="date" name="exam_end_date" id="exam_end_date" class="form-control @error('exam_end_date') is-invalid @enderror" value="{{ old('exam_end_date') }}">
                            @error('exam_end_date')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Start Time -->
                        <div class="col-md-6 form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" name="start_time" id="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time') }}">
                            @error('start_time')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- End Time -->
                        <div class="col-md-6 form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" name="end_time" id="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time') }}">
                            @error('end_time')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Duration -->
                        <div class="col-md-6 form-group">
                            <label for="duration_minutes">Duration (Minutes)</label>
                            <input type="number" name="duration_minutes" id="duration_minutes" class="form-control @error('duration_minutes') is-invalid @enderror" value="{{ old('duration_minutes') }}" min="1">
                            @error('duration_minutes')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Status -->
                        <div class="col-md-6 form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                                <option value="scheduled" {{ old('status', 'scheduled') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="ongoing" {{ old('status') == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                                <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="canceled" {{ old('status') == 'canceled' ? 'selected' : '' }}>Canceled</option>
                            </select>
                            @error('status')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Grade Level -->
                        <div class="col-md-6 form-group">
                            <label for="gradelevel_id">Grade Level</label>
                            <select name="gradelevel_id" id="gradelevel_id" class="form-control @error('gradelevel_id') is-invalid @enderror">
                                <option value="">All Grades</option>
                                @foreach($gradelevels as $gradelevel)
                                    <option value="{{ $gradelevel->id }}" {{ old('gradelevel_id') == $gradelevel->id ? 'selected' : '' }}>
                                        {{ $gradelevel->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('gradelevel_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Subjects -->
                        <div class="col-md-6 form-group">
                            <label for="subjects">Subjects <span class="text-danger">*</span></label>
                            <select name="subjects[]" id="subjects" class="form-control @error('subjects') is-invalid @enderror" multiple>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->subject_id }}" {{ in_array($subject->subject_id, old('subjects', [])) ? 'selected' : '' }}>
                                        {{ $subject->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subjects')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Instructions -->
                        <div class="col-md-12 form-group">
                            <label for="instructions">Instructions</label>
                            <textarea name="instructions" id="instructions" class="form-control @error('instructions') is-invalid @enderror" rows="5">{{ old('instructions') }}</textarea>
                            @error('instructions')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Is Published -->
                        <div class="col-md-12 form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_published" id="is_published" class="form-check-input" value="1" {{ old('is_published') ? 'checked' : '' }}>
                                <label for="is_published" class="form-check-label">Publish Exam</label>
                            </div>
                            @error('is_published')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Exam
                        </button>
                        <a href="{{ route('staff.exams.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <!-- Select2 for multi-select -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <!-- Bootstrap Datepicker for date inputs -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
@endsection

@section('scripts')
    <!-- Select2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- Bootstrap Datepicker -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script>
        $(document).ready(function () {
            // Initialize Select2 for subjects
            $('#subjects').select2({
                placeholder: 'Select Subjects',
                allowClear: true,
                width: '100%'
            });

            // Initialize Datepicker for start and end dates
            $('#exam_start_date, #exam_end_date').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });

            // Dynamic Marking Periods and Subjects based on School selection
            $('#school_id').on('change', function () {
                const schoolId = $(this).val();
                if (schoolId) {
                    // Fetch marking periods
                    $.get('{{ route('staff.exams.marking-periods') }}', { school_id: schoolId, syear: $('#syear').val() }, function (data) {
                        $('#marking_period_id').empty().append('<option value="">Select Marking Period</option>');
                        $.each(data, function (index, mp) {
                            $('#marking_period_id').append(`<option value="${mp.marking_period_id}">${mp.title}</option>`);
                        });
                    });

                    // Fetch subjects
                    $.get('{{ route('staff.exams.subjects') }}', { school_id: schoolId, syear: $('#syear').val() }, function (data) {
                        $('#subjects').empty();
                        $.each(data, function (index, subject) {
                            $('#subjects').append(`<option value="${subject.subject_id}">${subject.title}</option>`);
                        });
                        $('#subjects').trigger('change');
                    });
                }
            });

            // Trigger school_id change on page load to populate marking periods and subjects
            $('#school_id').trigger('change');
        });
    </script>
@endsection
```
