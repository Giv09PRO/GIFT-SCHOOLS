{{-- resources/views/pages/staff/finance/index.blade.php --}}
{{-- This view lists students with their financial summaries and provides filtering options. --}}

@extends('adminlte::page') {{-- Or your main layout, e.g., @extends('layouts.app') --}}

@php
    // Using Qs helper for application-specific utilities if available.
    // use App\Helpers\Qs;
    // Logging can be useful for debugging view rendering if needed.
    // use Illuminate\Support\Facades\Log;
    // Log::info('Rendering student statements index view with syear: ' . ($syear ?? 'Not Set'));
@endphp

@section('title', 'Student Financial Statements')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Student Financial Statements</h1>
            </div>
            <div class="col-sm-6">
                {{-- Link to Batch Statement Generation Form --}}
                @can('view finances') {{-- Or a more specific permission like 'generate batch statements' --}}
                    <a href="{{ route('staff.finance.statements.batch.form') }}" class="btn btn-sm btn-info float-sm-right">
                        <i class="fas fa-users-cog mr-1"></i> Batch Generate Statements
                    </a>
                @endcan
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">

        {{-- Display Session Flash Messages (e.g., warnings, errors, success messages) --}}
        {{-- Ensure this partial exists and is styled appropriately --}}
        @include('layouts.partials.flash_messages')

        {{-- Filters Card --}}
        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-filter mr-1"></i>Filter Student Statements</h3>
            </div>
            <div class="card-body">
                {{-- The form submits to the current route (index) with GET parameters for filtering --}}
                <form method="GET" action="{{ route('staff.finance.statements.index') }}" class="row g-3 align-items-end">
                    {{-- Student Search Input --}}
                    <div class="col-md-4 mb-3">
                        <label for="student_search" class="form-label">Student Name/ID/Username</label>
                        <input type="text" name="student_search" id="student_search"
                               class="form-control form-control-sm"
                               value="{{ old('student_search', request('student_search')) }}"
                               placeholder="Enter name, ID, or username">
                    </div>

                    {{-- Grade Level Filter --}}
                    <div class="col-md-3 mb-3">
                        <label for="grade_id" class="form-label">Grade Level</label>
                        <select name="grade_id" id="grade_id" class="form-control form-control-sm @if(config('adminlte.plugins.Select2.active', false)) select2 @endif"
                                {{-- Disable grade filter if no specific school context is active --}}
                                @if(!($gradeFilterEnabled ?? false)) disabled @endif
                                title="{{ ($gradeFilterEnabled ?? false) ? 'Select grade level' : 'Select a specific school context to enable grade filtering' }}"
                                aria-describedby="{{ ($gradeFilterEnabled ?? false) ? '' : 'grade-filter-disabled-help' }}">
                            @foreach ($grades ?? [] as $id => $title)
                                <option value="{{ $id }}" @selected(old('grade_id', request('grade_id')) == $id)>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                        @if (!($gradeFilterEnabled ?? false))
                            <small id="grade-filter-disabled-help" class="form-text text-muted">
                                Grade filter is available when a specific school context is active.
                            </small>
                        @endif
                    </div>

                    {{-- School Year Filter --}}
                    <div class="col-md-3 mb-3">
                        <label for="syear" class="form-label">School Year</label>
                        <select name="syear" id="syear" class="form-control form-control-sm">
                            {{-- Ensure $years (array of year values) and $syear (current selected year) are passed from controller --}}
                            @foreach ($years ?? [date('Y')] as $year_option) {{-- Default to current year if $years is not set --}}
                                <option value="{{ $year_option }}" @selected(old('syear', $syear ?? null) == $year_option)>
                                    {{ $year_option }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Button --}}
                    <div class="col-md-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search mr-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Student Statements List Card --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Student List</h3>
                {{-- Display context if viewing statements across all schools --}}
                @if(isset($selectedSchoolId) && $selectedSchoolId === null)
                    <span class="ml-2 text-muted text-sm">(Showing students from all schools for year {{ $syear ?? 'N/A' }})</span>
                @elseif(isset($selectedSchoolName))
                    <span class="ml-2 text-muted text-sm">(Showing students for {{ $selectedSchoolName }} - Year {{ $syear ?? 'N/A' }})</span>
                @endif
            </div>
            <div class="card-body">
                {{-- AdminLTE Datatable Component --}}
                {{-- Ensure $heads and $config (for datatable) are correctly passed from the controller --}}
                @if(isset($heads) && isset($config))
                    <x-adminlte-datatable id="studentsStatementTable" :heads="$heads" :config="$config" striped hoverable bordered compressed with-buttons>
                    </x-adminlte-datatable>
                @else
                    <div class="alert alert-warning">
                        Table configuration is missing. Please ensure <code>$heads</code> and <code>$config</code> are passed to the view.
                    </div>
                @endif
            </div>
        </div>
    </div>
@stop

@section('css')
    {{-- Include Select2 CSS if AdminLTE plugin is active and you are using the .select2 class --}}
    @if(config('adminlte.plugins.Select2.active', false))
        {{-- The Select2 CSS is usually bundled with AdminLTE if the plugin is enabled in config/adminlte.php --}}
        {{-- If not, you might need to link it manually:
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css" rel="stylesheet" />
        --}}
    @endif
    <style>
        /* Custom styles for this page */
        .select2-container .select2-selection--single {
            height: calc(1.8125rem + 2px); /* Match form-control-sm height */
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.8125rem + 2px);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.8125rem;
        }
        #studentsStatementTable td .btn {
            margin-bottom: 0; /* Prevent extra margin on buttons in table cells */
        }
        #studentsStatementTable .btn-group .btn,
        #studentsStatementTable nobr .btn {
            margin-right: 3px; /* Consistent spacing for action buttons */
        }
        #studentsStatementTable td.text-right { text-align: right !important; }
        #studentsStatementTable td.text-center { text-align: center !important; }

        /* Style for disabled select elements to make it more obvious */
        select:disabled,
        .form-control:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
            opacity: 0.7;
        }
    </style>
@stop

@section('js')
    {{-- Include Select2 JS if AdminLTE plugin is active and you are using the .select2 class --}}
    @if(config('adminlte.plugins.Select2.active', false))
        {{-- The Select2 JS is usually bundled with AdminLTE if the plugin is enabled --}}
        {{-- If not, manual link:
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        --}}
    @endif
    <script>
        $(document).ready(function () {
            // Initialize Select2 for elements with the .select2 class
            // Ensure the theme is compatible with your Bootstrap version (e.g., 'bootstrap4' for AdminLTE 3)
            if ($.fn.select2 && $('.select2').length) {
                 $('.select2').select2({
                    theme: 'bootstrap4' // Common theme for AdminLTE with Bootstrap 4
                 });
            }

            console.log('Student statements index page JavaScript loaded.');

            // Any other page-specific JavaScript can go here.
            // For example, handling dynamic changes or interactions.
        });
    </script>
@stop
