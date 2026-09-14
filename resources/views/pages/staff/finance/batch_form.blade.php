{{-- resources/views/pages/staff/finance/batch_form.blade.php --}}
@extends('layouts.app')

@section('title', 'Generate Batch Statements')

@section('content')
<div class="container-fluid">

    @include('layouts.partials.alerts')

    <div class="card card-primary card-outline">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Select Criteria to Generate Batch Statements</h3>
            <!--<a href="{{ route('staff.finance.statements.batch.history') }}" class="btn btn-sm btn-outline-info">-->
            <!--    <i class="fas fa-history mr-1"></i> View Full Batch History-->
            <!--</a>-->
        </div>

        <form method="POST" action="{{ route('staff.finance.statements.batch.process') }}">
            @csrf

            <div class="card-body">
                <div class="row">
                    {{-- School Year Selection --}}
                    <div class="col-md-6 form-group">
                        <label for="syear">School Year <span class="text-danger">*</span></label>
                        <select name="syear" id="syear" class="form-control select2bs4 @error('syear') is-invalid @enderror" required>
                            <option value="" disabled {{ old('syear') ? '' : 'selected' }}>Select Year...</option>
                            @foreach ($years ?? [] as $year)
                            <option value="{{ $year }}" {{ old('syear', \App\Helpers\Qs::getCurrentSchoolYear()) == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                            @endforeach
                        </select>
                        @error('syear')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    {{-- Grade Level Selection --}}
                    <div class="col-md-6 form-group">
                        <label for="grade_id">Grade Level (Optional)</label>
                        <select name="grade_id" id="grade_id" class="form-control select2bs4 @error('grade_id') is-invalid @enderror"
                            {{ ($gradeFilterEnabled ?? false) ? '' : 'disabled' }}
                            title="{{ ($gradeFilterEnabled ?? false) ? '' : 'Select a specific school context to filter by grade' }}">
                            @foreach ($grades ?? [] as $id => $title)
                            <option value="{{ $id }}" {{ old('grade_id', '') == $id ? 'selected' : '' }}>
                                {{ $title }}
                            </option>
                            @endforeach
                        </select>
                        @if (!($gradeFilterEnabled ?? false))
                            <small class="form-text text-muted">Grade filtering requires a specific school context to be active (via Admin Settings).</small>
                        @endif
                        @error('grade_id')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Generating batch statements can take a significant amount of time depending on the number of students.
                    The process will run in the background. You will receive the Batch ID upon successful submission.
                    Use the Batch ID to check status below or view full history.
                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary" id="btn-submit">
                    <i class="fas fa-cogs mr-1"></i> Start Batch Generation
                </button>
            </div>
        </form>
    </div>
    
    <!-- Progress Modal -->
    <div class="modal fade" id="progressModal" tabindex="-1" role="dialog" aria-labelledby="progressModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="progressModalLabel">Generating PDFs</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="progress">
          <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%;">0%</div>
        </div>
        <p id="progressText" class="mt-2">Waiting to start...</p>
      </div>
    </div>
  </div>
</div>

    


</div>
@endsection

@push('styles')
    {{-- Add Select2 CSS here if not already included --}}
 <!-- Also check Bootstrap CSS is included -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">

@endpush

@push('scripts')

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function () {
    // Initialize select2 if available
    if ($.fn.select2) {
        $('.select2bs4').select2({
            theme: 'bootstrap4',
            placeholder: "Select...",
            allowClear: true
        });
    }

    let pollInterval;

    function startPolling() {
        pollInterval = setInterval(() => {
            fetch('{{ route('staff.finance.statements.progress') }}')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'generating') {
                        let percent = data.total > 0 ? Math.floor((data.current / data.total) * 100) : 0;
                        $('#progressBar').css('width', percent + '%').text(percent + '%');
                        $('#progressText').text(`Processing ${data.current} of ${data.total}...`);
                    } else if (data.status === 'done') {
                        $('#progressBar').css('width', '100%').text('100%');
                        $('#progressText').text('Done!');
                        clearInterval(pollInterval);
                        $('#btn-submit').prop('disabled', false).html('<i class="fas fa-cogs mr-1"></i> Start Batch Generation');
                        // Allow closing modal now
                        $('#progressModalCloseBtn').show();
                    } else {
                        $('#progressText').text('Waiting to start...');
                    }
                })
                .catch(err => {
                    console.error('Progress check failed:', err);
                    clearInterval(pollInterval);
                    $('#progressText').text('Error checking progress.');
                    $('#btn-submit').prop('disabled', false).html('<i class="fas fa-cogs mr-1"></i> Start Batch Generation');
                    // Allow closing modal on error
                    $('#progressModalCloseBtn').show();
                });
        }, 2000);
    }

    $('form').on('submit', function(e) {
        e.preventDefault();

        $('#btn-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');
        $('#progressBar').css('width', '0%').text('0%');
        $('#progressText').text('Starting...');

        // Hide close button while processing to avoid accidental modal close
        $('#progressModalCloseBtn').hide();

        $('#progressModal').modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });

        startPolling();

        const form = this;
        const url = $(form).attr('action');
        const method = $(form).attr('method') || 'POST';
        const formData = new FormData(form);

        fetch(url, {
            method: method,
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(async response => {
            if (!response.ok) throw new Error('Network response was not OK');
            return response.blob();
        })
        .then(blob => {
            // Trigger download
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = 'financial_statements.zip';
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(downloadUrl);

            setTimeout(() => $('#progressModal').modal('hide'), 1500);
            clearInterval(pollInterval);
            $('#btn-submit').prop('disabled', false).html('<i class="fas fa-cogs mr-1"></i> Start Batch Generation');
            $('#progressModalCloseBtn').show();
        })
        .catch(error => {
            clearInterval(pollInterval);
            console.error('Batch generation failed:', error);
            $('#progressText').text('Error generating batch.');
            $('#btn-submit').prop('disabled', false).html('<i class="fas fa-cogs mr-1"></i> Start Batch Generation');
            $('#progressModalCloseBtn').show();
        });
    });
});
</script>

@endpush
