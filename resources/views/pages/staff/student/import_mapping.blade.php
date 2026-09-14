{{-- resources/views/pages/staff/student/import_mapping.blade.php --}}

@extends('layouts.app') {{-- Use your main layout file --}}

@php
    // Variables passed from controller:
    // $fileHeaders (array of headers from the uploaded file)
    // $dbFields (array of [db_field => Display Name] for mapping dropdown)
    // $overrides (array of any enrollment overrides set by admin, e.g., ['override_syear' => 2025])
    // $hasElevatedPrivileges (boolean) - Not directly used in this view but passed for context
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Import Students - Column Mapping')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        @include('layouts.partials.alerts') {{-- Display Alerts --}}

        {{-- Display Validation Errors for Mapping --}}
        @error('mapping')
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <strong>Mapping Error:</strong> {{ $message }}
        </div>
        @enderror
        @error('importValidation') {{-- Errors from Laravel Excel validation --}}
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <strong>Import Validation Failed:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->importValidation->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @enderror


        <div class="row">
            <div class="col-12">
                {{-- Mapping Form Card --}}
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Map Your File Columns to Database Fields</h3>
                    </div>
                    <form method="POST" action="{{ route('staff.students.import.process_mapped') }}">
                        @csrf
                        <div class="card-body">
                            <p class="text-muted">Match the columns from your uploaded file (left) to the corresponding database fields (right). Required database fields must be mapped unless an override was provided on the previous step.</p>

                            {{-- Display Overrides if set --}}
                            @if(!empty($overrides))
                                <div class="callout callout-info">
                                    <h5><i class="fas fa-info-circle"></i> Enrollment Overrides Applied:</h5>
                                    <p class="mb-0">The following values will be used for **all** students in this import, overriding any corresponding data in your file:</p>
                                    <ul class="mb-0">
                                        {{-- Display override values --}}
                                        @if(isset($overrides['override_syear'])) <li><strong>School Year:</strong> {{ $overrides['override_syear'] }}</li> @endif
                                        @if(isset($overrides['override_school_id'])) <li><strong>School ID:</strong> {{ $overrides['override_school_id'] }}</li> @endif
                                        @if(isset($overrides['override_grade_id'])) <li><strong>Grade ID:</strong> {{ $overrides['override_grade_id'] }}</li> @endif
                                    </ul>
                                    <small>You may not need to map the corresponding columns (<code>syear</code>, <code>school_identifier</code>, <code>grade_identifier</code>) from your file if an override is set.</small>
                                </div>
                            @endif


                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>File Header Column</th>
                                        <th>Map to Database Field</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @if(!empty($fileHeaders))
                                        @foreach($fileHeaders as $header)
                                            @php
                                                // Attempt to guess the mapping based on common names
                                                $cleanHeader = strtolower(str_replace([' ', '_'], '', trim($header ?? '')));
                                                $guessedDbField = '';
                                                foreach ($dbFields as $dbKey => $dbLabel) {
                                                    $cleanDbKey = strtolower(str_replace([' ', '_'], '', $dbKey));
                                                    $variations = [$cleanDbKey];
                                                    // Add common variations for guessing
                                                    if ($dbKey === 'school_identifier') $variations = ['school', 'schoolid', 'schoolname'];
                                                    if ($dbKey === 'grade_identifier') $variations = ['grade', 'gradeid', 'gradename', 'gradelevel', 'gradelevelid', 'gradelevelname'];
                                                    if ($dbKey === 'prem_number') $variations = ['premnumber', 'permanentnumber', 'studentid'];
                                                    if ($dbKey === 'syear') $variations = ['year', 'schoolyear', 'academicyear'];
                                                    if ($dbKey === 'start_date') $variations = ['startdate', 'enrollmentdate'];
                                                    if ($dbKey === 'dob') $variations = ['dateofbirth', 'dob', 'birthdate'];

                                                    if (in_array($cleanHeader, $variations)) {
                                                        $guessedDbField = $dbKey;
                                                        break;
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ e($header) }}</td>
                                                <td>
                                                    <select name="mapping[{{ $header }}]" class="form-control form-control-sm select2-mapping">
                                                        <option value="_ignore_">(Ignore this column)</option>
                                                        <option value="" disabled>--- Select Field ---</option>
                                                        @foreach($dbFields as $dbKey => $dbLabel)
                                                            {{-- Check if this DB field was guessed for this header --}}
                                                            <option value="{{ $dbKey }}" {{ old('mapping.'.$header, $guessedDbField) == $dbKey ? 'selected' : '' }}>
                                                                {{ $dbLabel }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="2" class="text-danger">Could not read headers from file. Please go back and re-upload.</td></tr>
                                    @endif
                                    </tbody>
                                </table>
                            </div>

                            {{-- Submit Button --}}
                            @if(!empty($fileHeaders)) {{-- Only show button if headers were read --}}
                            <div class="text-center border-top pt-3 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg"> {{-- Larger button --}}
                                    <i class="fas fa-check mr-1"></i> Confirm Mapping & Start Import
                                </button>
                                <a href="{{ route('staff.students.import.form') }}" class="btn btn-secondary ml-2">Cancel Import</a>
                            </div>
                            @endif

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@push('scripts')
    {{-- Include scripts for select2 if not already in layout --}}
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script> {{-- Adjust path as needed --}}
    <script>
        $(function () {
            // Initialize Select2 Elements for mapping dropdowns
            $('.select2-mapping').select2({
                theme: 'bootstrap4',
                placeholder: '(Ignore this column)',
                allowClear: true
            });
        });
    </script>
@endpush

@push('styles')
    {{-- Include styles for select2 if not already in layout --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}"> {{-- Adjust path as needed --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}"> {{-- Adjust path as needed --}}
@endpush
