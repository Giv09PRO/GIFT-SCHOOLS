{{-- resources/views/pages/staff/reportcards/index.blade.php --}}

@extends('layouts.app')

@php
    // Variables passed from ReportCardController:
    // $heads, $config, $reportCards (original collection)
    // $markingPeriodsForFilter, $gradeLevelsForFilter, $publishStatuses
    // $currentSchoolId, $currentSyear
@endphp

{{-- Page Title (Browser Tab) & Subtitle for Content Header --}}
@section('title', 'Manage Report Cards')
@section('subtitle', 'List of student report cards')

{{-- Main Page Content --}}
@section('content_body')
    <div class="container-fluid">

        {{-- Filter Card --}}
        <div class="card card-outline card-primary mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Report Cards</h3>
                <div class="card-tools">
                    @can('create report_cards')
                        <a href="{{ route('staff.reportcards.create') }}" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i> Generate New Report Card
                        </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('staff.reportcards.index') }}" class="row g-3 align-items-end">

                    {{-- Row 1: Student Search, Marking Period --}}
                    <div class="col-md-4">
                        <label for="student_search_term" class="form-label">Student Name/Username</label>
                        <input type="text" name="student_search_term" id="student_search_term" class="form-control form-control-sm"
                               value="{{ request('student_search_term') }}" placeholder="Enter student name or username...">
                    </div>
                    <div class="col-md-4">
                        <label for="marking_period_id_filter" class="form-label">Marking Period ({{ $currentSyear ?? 'Current Year' }})</label>
                        <select name="marking_period_id_filter" id="marking_period_id_filter" class="form-select form-select-sm select2">
                            <option value="">All Marking Periods</option>
                            @if(isset($markingPeriodsForFilter))
                                @foreach ($markingPeriodsForFilter as $id => $title)
                                    <option value="{{ $id }}" {{ request('marking_period_id_filter') == $id ? 'selected' : '' }}>
                                        {{ $title }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="grade_id_filter" class="form-label">Grade Level ({{ $currentSyear ?? 'Current Year' }})</label>
                        <select name="grade_id_filter" id="grade_id_filter" class="form-select form-select-sm select2">
                            <option value="">All Grade Levels</option>
                            @if(isset($gradeLevelsForFilter))
                                @foreach ($gradeLevelsForFilter as $id => $title)
                                    <option value="{{ $id }}" {{ request('grade_id_filter') == $id ? 'selected' : '' }}>
                                        {{ $title }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- Row 2: Published Status & Buttons --}}
                    <div class="col-md-4 mt-2">
                        <label for="is_published_filter" class="form-label">Published Status</label>
                        <select name="is_published_filter" id="is_published_filter" class="form-select form-select-sm">
                            @if(isset($publishStatuses))
                                @foreach ($publishStatuses as $value => $label)
                                    <option value="{{ $value }}" {{ request('is_published_filter') === (string)$value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-8 mt-2 text-md-end"> {{-- Align buttons to the right on medium screens and up --}}
                        <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search me-1"></i>Filter Report Cards</button>
                        <a href="{{ route('staff.reportcards.index') }}" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results Card using AdminLTE Datatable --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Report Card List</h3>
            </div>
            <div class="card-body">
                @if(isset($heads) && isset($config))
                    <x-adminlte-datatable id="reportCardsTable" :heads="$heads" :config="$config" striped hoverable bordered compressed>
                        {{-- Data is passed via $config['data'] from the controller --}}
                    </x-adminlte-datatable>
                @else
                    <div class="alert alert-warning">
                        Table configuration data is missing. Please ensure the controller is passing 'heads' and 'config' variables.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    {{-- Link Select2 CSS if you choose to use it and it's not globally available --}}
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
            height: calc(1.5em + .5rem + 2px) !important; /* For form-select-sm */
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
    {{-- Link Select2 JS if you choose to use it and it's not globally available --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
    <script>
        $(document).ready(function() {
            // Initialize Select2 for filter dropdowns
            if ($.fn.select2) { // Check if Select2 is loaded
                $('#marking_period_id_filter, #grade_id_filter').select2({
                    theme: 'bootstrap-5', // Ensure this theme CSS is loaded for AdminLTE 3/Bootstrap 5
                    placeholder: $(this).data('placeholder') || 'Select an option',
                    allowClear: true,
                    width: '100%'
                });
            }

            // JavaScript for delete confirmation
            $('body').on('click', '.delete-reportcard-btn', function(e) {
                e.preventDefault();
                var reportCardId = $(this).data('id');
                if (confirm('Are you sure you want to delete this report card? This action cannot be undone.')) {
                    var deleteForm = $('<form>', {
                        'method': 'POST',
                        'action': '{{ url('staff/reportcards') }}/' + reportCardId // Ensure this matches your route structure
                    }).append(
                        $('<input>', {'name': '_method', 'value': 'DELETE', 'type': 'hidden'}),
                        $('<input>', {'name': '_token', 'value': '{{ csrf_token() }}', 'type': 'hidden'})
                    );
                    $('body').append(deleteForm);
                    deleteForm.submit();
                }
            });

            // Handle the toggle publish form submission if it's not handled by default form submission
            // This is usually handled by the browser submitting the form, but if you wanted AJAX:
            // $('body').on('submit', 'form[action*="togglePublish"]', function(e) {
            //     e.preventDefault();
            //     var form = $(this);
            //     if (confirm(form.find('button[type="submit"]').attr('title') + '?')) {
            //         // AJAX submission logic here if preferred over full page reload
            //         // For now, standard form submission is fine.
            //         this.submit(); // Proceed with normal form submission
            //     }
            // });

            console.log('Report Cards index page with AdminLTE Datatable loaded!');
        });
    </script>
@endpush
