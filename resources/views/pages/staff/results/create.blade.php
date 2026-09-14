{{-- resources/views/pages/staff/results/create.blade.php --}}

@extends('layouts.app') {{-- Or your main AdminLTE layout --}}

@php
    // Variables passed from ResultController:
    // $exams, $students, $staffList, $selectedExam
@endphp

@section('title', 'Add Exam Result')
@section('subtitle', 'Record a new student exam result')

@section('content_body')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Enter Result Details</h3>
                    </div>
                    <form method="POST" action="{{ route('staff.results.store') }}" id="createResultForm">
                        @csrf
                        <div class="card-body">
                            {{-- Display validation errors --}}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Exam Selection --}}
                            <div class="form-group mb-3">
                                <label for="exam_id">Exam <span class="text-danger">*</span></label>
                                <select name="exam_id" id="exam_id" class="form-control select2 @error('exam_id') is-invalid @enderror" required>
                                    <option value="">Select an Exam</option>
                                    @foreach ($exams as $id => $examDisplay)
                                        {{-- $examDisplay already contains "Subject - Type (Date) - Max: Score" --}}
                                        <option value="{{ $id }}"
                                                {{ old('exam_id', $selectedExam ? $selectedExam->id : '') == $id ? 'selected' : '' }}
                                                data-max-score="{{ \App\Models\Exam::find($id)->max_score ?? '' }}">
                                            {{ $examDisplay }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('exam_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            {{-- Student Selection --}}
                            <div class="form-group mb-3">
                                <label for="student_id">Student <span class="text-danger">*</span></label>
                                <select name="student_id" id="student_id" class="form-control select2 @error('student_id') is-invalid @enderror" required>
                                    <option value="">Select a Student</option>
                                    {{-- Students are filtered by controller if $selectedExam and $selectedExam->gradelevel_id exist --}}
                                    {{-- If no exam selected, this list might be long or all students for the school/year --}}
                                    @foreach ($students as $id => $studentDisplay)
                                        <option value="{{ $id }}" {{ old('student_id') == $id ? 'selected' : '' }}>
                                            {{ $studentDisplay }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('student_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                                <small id="studentFilterNote" class="form-text text-muted" style="display: none;">
                                    Student list filtered by selected exam's grade level.
                                </small>
                            </div>

                            {{-- Score --}}
                            <div class="form-group mb-3">
                                <label for="score">Score</label>
                                <input type="number" name="score" id="score" class="form-control @error('score') is-invalid @enderror"
                                       value="{{ old('score') }}" step="0.01" min="0"
                                       placeholder="Enter score">
                                <small id="maxScoreHint" class="form-text text-muted"></small>
                                @error('score')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            {{-- Comments --}}
                            <div class="form-group mb-3">
                                <label for="comments">Comments</label>
                                <textarea name="comments" id="comments" class="form-control @error('comments') is-invalid @enderror"
                                          rows="3" placeholder="Enter any comments">{{ old('comments') }}</textarea>
                                @error('comments')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            {{-- Graded By --}}
                            <div class="form-group mb-3">
                                <label for="graded_by">Graded By</label>
                                <select name="graded_by" id="graded_by" class="form-control select2 @error('graded_by') is-invalid @enderror">
                                    <option value="">Select Grader (Optional, defaults to you)</option>
                                    @foreach ($staffList as $id => $staffDisplay)
                                        <option value="{{ $id }}" {{ old('graded_by') == $id ? 'selected' : '' }}>
                                            {{ $staffDisplay }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('graded_by')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            {{-- Is Finalized --}}
                            <div class="form-group mb-3">
                                <label for="is_finalized">Mark as Finalized? <span class="text-danger">*</span></label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input @error('is_finalized') is-invalid @enderror" type="radio" name="is_finalized" id="is_finalized_yes" value="1" {{ old('is_finalized') == '1' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="is_finalized_yes">Yes</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input @error('is_finalized') is-invalid @enderror" type="radio" name="is_finalized" id="is_finalized_no" value="0" {{ old('is_finalized', '0') == '0' ? 'checked' : '' }} required> {{-- Default to No --}}
                                        <label class="form-check-label" for="is_finalized_no">No</label>
                                    </div>
                                </div>
                                @error('is_finalized')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Result</button>
                            <a href="{{ $selectedExam ? route('staff.exams.show', $selectedExam->id) : route('staff.results.index') }}" class="btn btn-secondary">
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
    </style>
@endpush

@push('scripts')
    {{-- Link Select2 JS if not globally available --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
    <script>
        $(document).ready(function() {
            // Initialize Select2
            if ($.fn.select2) {
                $('#exam_id').select2({
                    placeholder: "Select an Exam",
                    allowClear: true,
                    theme: 'bootstrap-5', // Ensure this theme is loaded or use 'default'
                    width: '100%'
                });
                $('#student_id').select2({
                    placeholder: "Select a Student",
                    allowClear: true,
                    theme: 'bootstrap-5',
                    width: '100%'
                });
                $('#graded_by').select2({
                    placeholder: "Select Grader (Optional)",
                    allowClear: true,
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }

            function updateScoreHint() {
                var selectedExamOption = $('#exam_id').find('option:selected');
                var maxScore = selectedExamOption.data('max-score');
                var scoreInput = $('#score');
                var scoreHint = $('#maxScoreHint');

                if (maxScore) {
                    scoreInput.attr('max', maxScore);
                    scoreHint.text('Max score: ' + maxScore);
                } else {
                    scoreInput.removeAttr('max');
                    scoreHint.text('');
                }
            }

            // Initial call to set hint if an exam is pre-selected
            updateScoreHint();

            // When exam selection changes
            $('#exam_id').on('change', function() {
                updateScoreHint();
                var examId = $(this).val();
                var studentSelect = $('#student_id');
                var studentFilterNote = $('#studentFilterNote');

                // Clear current student options and selection
                studentSelect.val(null).empty().append($('<option>', {value: '', text: 'Loading students...'})).trigger('change');


                if (examId) {
                    // AJAX call to fetch students for the selected exam's grade level
                    // This requires a new route and controller method.
                    // For now, we rely on the initial student list provided by the controller
                    // which is already filtered if $selectedExam had a gradelevel_id.
                    // If you want dynamic filtering without page reload, an AJAX endpoint is needed.
                    // The controller's create method already provides a filtered student list IF $selectedExam is set.
                    // If exam is changed client-side, a full page reload with new exam_id query param
                    // or an AJAX call to a dedicated endpoint to get students is better.

                    // Simple approach: reload page with new exam_id to get filtered students
                    // This is not ideal UX but simpler than full AJAX for student list here.
                    // window.location.href = '{{ route('staff.results.create') }}?exam_id=' + examId;

                    // For now, just show a note if the initial list was filtered.
                    // The controller already filters the $students list if $selectedExam is present.
                    @if ($selectedExam && $selectedExam->gradelevel_id)
                    if (examId == '{{ $selectedExam->id }}') {
                        studentFilterNote.show();
                    } else {
                        studentFilterNote.hide(); // Hide if a different exam is chosen
                        // Optionally, you could inform the user that the student list might not be filtered for the *newly* selected exam
                        // and they might need to save and re-edit or use a more advanced filter.
                        // Or, disable student selection until an exam with grade level is chosen,
                        // or provide all students and let server-side validation handle it.
                        // For now, the $students list is static based on initial load.
                    }
                    @else
                    studentFilterNote.hide();
                    @endif
                    // If you want to dynamically load students via AJAX:
                    // $.ajax({
                    //     url: '{{ url('staff/api/students-for-exam') }}/' + examId, // Create this route
                    //     type: 'GET',
                    //     success: function(data) {
                    //         studentSelect.empty().append($('<option>', {value: '', text: 'Select a Student'}));
                    //         $.each(data, function(id, studentDisplay) {
                    //             studentSelect.append($('<option>', {value: id, text: studentDisplay}));
                    //         });
                    //         studentSelect.trigger('change');
                    //         studentFilterNote.show();
                    //     },
                    //     error: function() {
                    //         studentSelect.empty().append($('<option>', {value: '', text: 'Error loading students'}));
                    //         studentFilterNote.hide();
                    //     }
                    // });

                } else {
                    studentSelect.val(null).empty().append($('<option>', {value: '', text: 'Select an Exam first'})).trigger('change');
                    studentFilterNote.hide();
                }
            });

            // Trigger change if exam is pre-selected to populate student list correctly (if AJAX was used)
            // or to ensure note visibility is correct.
            if ($('#exam_id').val()) {
                $('#exam_id').trigger('change');
            }


        });
    </script>
@endpush
