{{-- resources/views/partials/import_errors.blade.php --}}

{{-- Check if import_errors session data exists --}}
@if (session()->has('import_errors') && is_array(session('import_errors')) && count(session('import_errors')) > 0)
    @php
        $allErrors = session('import_errors');
        // Limit displayed errors to avoid overwhelming the page (e.g., show first 20)
        $errorsToShow = array_slice($allErrors, 0, 20);
        $totalErrorCount = count($allErrors);
        $showingCount = count($errorsToShow);
    @endphp
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h4 class="alert-heading"><i class="icon fas fa-exclamation-triangle"></i> Import Issues Found!</h4>
        <p>
            There were issues processing some rows during the last import.
            Showing details for the first {{ $showingCount }} of {{ $totalErrorCount }} problematic row(s).
            Please review these issues, correct your import file, and try again if necessary.
        </p>
        <hr>
        <ul class="list-unstyled mb-0" style="max-height: 300px; overflow-y: auto;">
            @foreach ($errorsToShow as $error)
                <li>
                    <strong>Row {{ $error['row'] ?? 'N/A' }}:</strong>
                    <ul>
                        @if(isset($error['attribute']) && isset($error['errors']))
                            {{-- Format for Maatwebsite ValidationException Failures --}}
                            <li>Attribute `{{ $error['attribute'] }}`: {{ implode(', ', $error['errors']) }} (Value: `{{ $error['value'] ?? 'N/A' }}`)</li>
                        @elseif(isset($error['message']))
                            {{-- Format for custom errors added in StudentsImport --}}
                            <li>{{ $error['message'] }}</li>
                        @else
                            {{-- Generic fallback --}}
                            <li>{{ json_encode($error) }}</li>
                        @endif
                    </ul>
                </li>
            @endforeach
        </ul>
        @if ($totalErrorCount > $showingCount)
            <p class="mt-2 mb-0"><em>... and {{ $totalErrorCount - $showingCount }} more row(s) with issues.</em></p>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button> {{-- Use data-bs-dismiss for Bootstrap 5 --}}
    </div>
@endif

{{-- Also check for Maatwebsite ValidationException Failures if flashed separately --}}
@if (session()->has('import_validation_failures'))
    @php
        $allFailures = session('import_validation_failures');
        $failuresToShow = array_slice($allFailures, 0, 20); // Limit display
        $totalFailureCount = count($allFailures);
        $showingFailureCount = count($failuresToShow);
    @endphp
    <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
        <h4 class="alert-heading"><i class="icon fas fa-times-circle"></i> Import Validation Failed!</h4>
        <p>
            The import file contained validation errors. Showing details for the first {{ $showingFailureCount }} of {{ $totalFailureCount }} failure(s).
            Please correct the file based on these errors and try uploading again.
        </p>
        <hr>
        <ul class="list-unstyled mb-0" style="max-height: 300px; overflow-y: auto;">
            @foreach ($failuresToShow as $failure)
                <li>
                    <strong>Row {{ $failure->row() }}:</strong> Attribute `{{ $failure->attribute() }}`
                    <ul>
                        @foreach ($failure->errors() as $errorMsg)
                            <li>{{ $errorMsg }} (Value: `{{ $failure->values()[$failure->attribute()] ?? 'N/A' }}`)</li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
        @if ($totalFailureCount > $showingFailureCount)
            <p class="mt-2 mb-0"><em>... and {{ $totalFailureCount - $showingFailureCount }} more validation failure(s).</em></p>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
