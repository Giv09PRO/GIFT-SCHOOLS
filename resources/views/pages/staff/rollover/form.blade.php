{{-- resources/views/pages/staff/rollover/form.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'School Year Rollover')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2"> {{-- Center the card --}}
                {{-- Rollover Form Card --}}
                <div class="card card-danger card-outline"> {{-- Use danger color for caution --}}
                    <div class="card-header">
                        <h3 class="card-title">Initiate New School Year Setup</h3>
                    </div>
                    {{-- Form Start --}}
                    <form method="POST" action="{{ route('staff.rollover.process') }}" id="rolloverForm" onsubmit="return confirmRollover();">
                        @csrf {{-- CSRF Protection --}}

                        <div class="card-body">
                            {{-- Display Alerts --}}
                            @include('layouts.partials.alerts') {{-- Use the correct path --}}

                            <div class="callout callout-warning">
                                <h5><i class="icon fas fa-exclamation-triangle"></i> Important Note!</h5>
                                <p>The rollover process copies essential data (Schools, Grades, Settings) from a previous year to create records for a new academic year. It also attempts to promote active students based on the 'Next Grade' settings.</p>
                                <p><strong>This process can take time and should only be run ONCE per year transition. It's highly recommended to perform a full database backup before proceeding.</strong></p>
                                <p>Student enrollments for the 'To Year' will be created based on active enrollments in the 'From Year' and the 'Next Grade ID' set on Classes. Students without a 'Next Grade ID' will be skipped (considered graduated or needing manual placement).</p>
                            </div>

                            <div class="row">
                                {{-- From Year --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="from_year">Rollover Data From Year: <span class="text-danger">*</span></label>
                                        <select name="from_year" id="from_year" class="form-control select2 @error('from_year') is-invalid @enderror" required>
                                            <option value="" disabled>Select Year</option>
                                            @foreach($existingYears as $year)
                                                {{-- Select the current year by default --}}
                                                <option value="{{ $year }}" {{ old('from_year', $currentYear) == $year ? 'selected' : '' }}>
                                                    {{ $year }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('from_year')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                        <small class="form-text text-muted">Select the academic year containing the data you want to copy.</small>
                                    </div>
                                </div>

                                {{-- To Year --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="to_year">Create Data For Year: <span class="text-danger">*</span></label>
                                        {{-- Pre-fill with the suggested next year --}}
                                        <input type="number" name="to_year" id="to_year" class="form-control @error('to_year') is-invalid @enderror" value="{{ old('to_year', $nextYear) }}" required placeholder="YYYY" min="{{ $currentYear + 1 }}" max="{{ $currentYear + 5 }}">
                                        @error('to_year')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                        <small class="form-text text-muted">Enter the new academic year you are setting up.</small>
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            {{-- Optional: School Selection (for partial rollover - keep commented for now) --}}
                            {{--
                            <div class="form-group">
                                <label for="school_ids">Select Specific Schools (Optional):</label>
                                <select name="school_ids[]" id="school_ids" class="form-control select2" multiple="multiple" data-placeholder="Leave blank to rollover all schools for the 'From Year'">
                                    @php
                                        // Fetch schools for the default 'from_year' to populate this initially if needed
                                        $schoolsForDefaultYear = \App\Models\School::where('syear', $currentYear)->orderBy('title')->get();
                                    @endphp
                                    @foreach($schoolsForDefaultYear as $school)
                                        <option value="{{ $school->id }}">{{ $school->title }} ({{ $school->syear }})</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">If left blank, all schools from the selected 'From Year' will be processed.</small>
                            </div>
                            --}}

                        </div>
                        <div class="card-footer text-center">
                            <button type="submit" class="btn btn-danger btn-lg" id="rolloverSubmitBtn">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Initiate Rollover Process
                            </button>
                        </div>
                    </form>
                    {{-- Form End --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Include scripts for select2 if not already in layout --}}
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script> {{-- Adjust path as needed --}}
    <script>
        $(function () {
            // Initialize Select2 Elements
            $('.select2').select2({
                theme: 'bootstrap4' // Optional: Use Bootstrap 4 theme
            });
        });

        // Confirmation dialog
        function confirmRollover() {
            const fromYear = $('#from_year').val();
            const toYear = $('#to_year').val();
            const message = `You are about to initiate the rollover process from year ${fromYear} to ${toYear}.\n\nThis will:\n- Copy School records and settings.\n- Copy Grade Level structures.\n- Enroll active students from ${fromYear} into the next grade for ${toYear} (if configured).\n\nTHIS ACTION CANNOT BE EASILY UNDONE. Ensure you have a database backup!\n\nAre you absolutely sure you want to proceed?`;

            // Disable button after first click to prevent multiple submissions
            $('#rolloverSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');

            return confirm(message); // Show native confirm dialog
        }

        // Re-enable button if the form submission fails validation client-side or user cancels confirm
        $('#rolloverForm').on('submit', function(e) {
            // If native confirm returns false, re-enable
            if (!window.confirmResult) { // Check a flag set by confirm (or lack thereof) - this part is tricky with native confirm
                // A better approach might be to use a custom modal for confirmation
                // For now, we'll just re-enable if the browser prevents submission after confirm('cancel')
                // This might not reliably work across all browsers after native confirm cancel.
                setTimeout(() => { // Delay slightly
                    if (!e.isDefaultPrevented()) { // Check if submission was prevented
                        $('#rolloverSubmitBtn').prop('disabled', false).html('<i class="fas fa-exclamation-triangle mr-1"></i> Initiate Rollover Process');
                    }
                }, 100);
            }
        });
        // A more reliable way for re-enabling requires preventing default, showing a modal,
        // and then submitting programmatically if confirmed in the modal.


    </script>
@endpush

@push('styles')
    {{-- Include styles for select2 if not already in layout --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}"> {{-- Adjust path as needed --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}"> {{-- Adjust path as needed --}}
@endpush
