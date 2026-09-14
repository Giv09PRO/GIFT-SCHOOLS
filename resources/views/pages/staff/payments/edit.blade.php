@extends('layouts.app') {{-- Use your main layout file --}}

{{-- Page Title (Browser Tab) --}}
@section('title', 'Edit Payment #' . $payment->id)

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            Edit Payment #{{ $payment->id }}
                            @if($payment->student)
                                for {{ $payment->student->first_name }} {{ $payment->student->last_name }} ({{ $payment->student->prem_number ?? 'N/A' }})
                            @endif
                        </h3>
                    </div>
                    {{-- Use the update route, likely needing the payment ID --}}
                    <form method="POST" action="{{ route('staff.payments.update', $payment->id) }}">
                        @csrf
                        @method('PUT') {{-- Use PUT or PATCH for updates --}}
                        <div class="card-body">
                            {{-- Display Student Info (Readonly) --}}
                            @if($payment->student)
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Student</label>
                                    <div class="col-sm-9">
                                        <input type="text" readonly class="form-control-plaintext" value="{{ $payment->student->first_name }} {{ $payment->student->last_name }} (ID: {{ $payment->student->id }})">
                                    </div>
                                </div>
                            @endif

                            {{-- Payment Date --}}
                            <div class="form-group row">
                                <label for="payment_date" class="col-sm-3 col-form-label">Payment Date <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control @error('payment_date') is-invalid @enderror" id="payment_date" name="payment_date"
                                           value="{{ old('payment_date', $payment->payment_date?->format('Y-m-d')) }}" required>
                                    @error('payment_date')
                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Amount --}}
                            {{-- Note: Editing amount might need careful consideration/validation, especially if allocations exist --}}
                            <div class="form-group row">
                                <label for="amount" class="col-sm-3 col-form-label">Amount <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    {{-- Check if it's a refund - refunds usually shouldn't be edited directly, maybe only cancelled/reversed --}}
                                    @if($payment->refunded_payment_id)
                                        <input type="number" readonly class="form-control-plaintext" value="{{ number_format($payment->amount, 2) }}">
                                        <small class="form-text text-muted">Refund amounts cannot be edited directly.</small>
                                        <input type="hidden" name="amount" value="{{ $payment->amount }}"> {{-- Still submit original amount --}}
                                    @else
                                        <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount"
                                               value="{{ old('amount', number_format($payment->amount, 2, '.', '')) }}" required {{ $payment->refunds()->exists() ? 'readonly' : '' }}>
                                        @if($payment->refunds()->exists())
                                            <small class="form-text text-muted">Amount cannot be edited because refunds have been issued against this payment.</small>
                                        @endif
                                        @error('amount')
                                        <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    @endif
                                </div>
                            </div>

                            {{-- Comments --}}
                            <div class="form-group row">
                                <label for="comments" class="col-sm-3 col-form-label">Comments / Notes</label>
                                <div class="col-sm-9">
                                    <textarea class="form-control @error('comments') is-invalid @enderror" id="comments" name="comments" rows="3">{{ old('comments', $payment->comments) }}</textarea>
                                    @error('comments')
                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            {{-- School Year (Readonly Recommended) --}}
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">School Year</label>
                                <div class="col-sm-9">
                                    <input type="text" readonly class="form-control-plaintext" value="{{ $payment->syear }}">
                                    <input type="hidden" name="syear" value="{{ $payment->syear }}"> {{-- Might need hidden field if validation requires it --}}
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-warning">Update Payment</button>
                            {{-- Link back to the student's payment list --}}
                            <a href="{{ route('staff.payments.index', $payment->student_id) }}" class="btn btn-secondary float-right">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    {{-- Add any specific CSS if needed --}}
@stop

@section('js')
    <script> console.log('Edit payment page loaded!'); </script>
    {{-- Add any specific JS if needed --}}
@stop
