{{-- resources/views/pages/staff/results/index.blade.php --}}

@extends('layouts.app')

@php
    // Variables passed from ResultController:
    // $heads, $config, $results (original collection, $config['data'] is used by datatable)
    // $examsForFilter, $studentsForFilter (currently empty, using search term instead), $finalizedStatuses
    // $currentSchoolId, $currentSyear
@endphp

{{-- Page Title (Browser Tab) & Subtitle for Content Header --}}
@section('title', 'Manage Exam Results')
@section('subtitle', 'List of student exam results')

{{-- Main Page Content --}}
@section('content_body')
    <div class="container-fluid">

        {{-- Filter Card --}}
        <div class="card card-outline card-primary mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Results</h3>
                <div class="card-tools">
                    {{-- Add New Result Button (Consider linking to a specific exam's result entry page or a batch entry) --}}
                    @can('create results')
                        <a href="{{ route('staff.results.create') }}" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i> Add Single Result
                        </a>
                        {{-- Example: Link to a batch entry page if you implement one --}}
                        {{-- <a href="{{ route('staff.results.batchCreateForm') }}" class="btn btn-sm btn-info">
                            <i class="fas fa-layer-group me-1"></i> Batch Entry
                        </a> --}}
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('staff.results.index') }}" class="row g-3 align-items-end">

                    {{-- Row 1: Exam, Student Search --}}
                    <div class="col-md-6">
                        <label for="exam_id_filter" class="form-label">Exam ({{ $currentSyear ?? 'Current Year' }})</label>
                        <select name="exam_id_filter" id="exam_id_filter" class="form-select form-select-sm select2">
                            <option value="">All Exams</option>
                            @foreach ($examsForFilter as $id => $title)
                                <option value="{{ $id }}" {{ request('exam_id_filter') == $id ? 'selected' : '' }}>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="student_search_term" class="form-label">Student Name/Username</label>
                        <input type="text" name="student_search_term" id="student_search_term" class="form-control form-control-sm"
                               value="{{ request('student_search_term') }}" placeholder="Enter student name or username...">
                    </div>

                    {{-- Row 2: Finalized, Min Score, Max Score --}}
                    <div class="col-md-4 mt-2">
                        <label for="is_finalized_filter" class="form-label">Finalized Status</label>
                        <select name="is_finalized_filter" id="is_finalized_filter" class="form-select form-select-sm">
                            @foreach ($finalizedStatuses as $value => $label)
                                <option value="{{ $value }}" {{ request('is_finalized_filter') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mt-2">
                        <label for="min_score" class="form-label">Min Score</label>
                        <input type="number" name="min_score" id="min_score" class="form-control form-control-sm"
                               value="{{ request('min_score') }}" placeholder="e.g., 50" step="0.01">
                    </div>
                    <div class="col-md-4 mt-2">
                        <label for="max_score" class="form-label">Max Score</label>
                        <input type="number" name="max_score" id="max_score" class="form-control form-control-sm"
                               value="{{ request('max_score') }}" placeholder="e.g., 85" step="0.01">
                    </div>

                    {{-- Row 3: Buttons --}}
                    <div class="col-12 mt-3 text-center">
                        <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search me-1"></i>Filter Results</button>
                        <a href="{{ route('staff.results.index') }}" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results Card using AdminLTE Datatable --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Exam Results List</h3>
            </div>
            <div class="card-body">
                <x-adminlte-datatable id="resultsTable" :heads="$heads" :config="$config" striped hoverable bordered compressed with-buttons>
                    {{-- Data is passed via $config['data'] from the controller --}}
                </x-adminlte-datatable>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    {{-- Link Select2 CSS if you choose to use it --}}
    {{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> --}}
    <style>
        .form-label {
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }
        .card-tools .btn {
            margin-left: 0.25rem;
        }
        /* Adjust Select2 height if using form-control-sm */
        .select2-container .select2-selection--single {
            height: calc(1.5em + .5rem + 2px) !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(1.5em + .5rem) !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .5rem) !important;
        }
    </style>
@endpush

@push('scripts')
    {{-- Link Select2 JS if you choose to use it --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
    <script>
        $(document).ready(function() {
            // Initialize Select2 for filter dropdowns
            // if ($.fn.select2) {
            //     $('#exam_id_filter').select2({
            //         theme: 'bootstrap-5',
            //         placeholder: 'Select an Exam',
            //         allowClear: true,
            //         width: '100%'
            //     });
            //     // Add other select2 initializations if needed
            // }

            // JavaScript for delete confirmation
            $('body').on('click', '.delete-result-btn', function(e) {
                e.preventDefault();
                var resultId = $(this).data('id');
                if (confirm('Are you sure you want to delete this result? This action cannot be undone.')) {
                    var deleteForm = $('<form>', {
                        'method': 'POST',
                        'action': '{{ url('staff/results') }}/' + resultId
                    }).append(
                        $('<input>', {'name': '_method', 'value': 'DELETE', 'type': 'hidden'}),
                        $('<input>', {'name': '_token', 'value': '{{ csrf_token() }}', 'type': 'hidden'})
                    );
                    $('body').append(deleteForm);
                    deleteForm.submit();
                }
            });

            console.log('Results index page with AdminLTE Datatable loaded!');
        });
    </script>
@endpush
