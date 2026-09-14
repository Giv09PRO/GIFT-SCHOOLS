@extends('layouts.app') {{-- Use your main layout file --}}

@php
    // Assuming $fee is an instance of App\Models\Fee (an installment)
    // and $hasPayments (boolean indicating if any payments are applied to this installment)
    // and $fee->feeDefinition (the parent FeeDefinition model) are passed from the controller.
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Edit Fee Installment: ' . $fee->title)

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7"> {{-- Adjusted column width --}}

                {{-- Edit Form Card --}}
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Editing Fee Installment: {{ $fee->title }}</h3>
                        @if($fee->invoice_no)
                            <strong class="ms-2 text-muted"> - Invoice: {{ $fee->invoice_no }}</strong>
                        @endif
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.fees.update', $fee->id) }}">
                            @csrf
                            @method('PUT') {{-- Use PUT method for updates --}}

                            {{-- Student Information (Read Only) --}}
                            <div class="form-group mb-3">
                                <label>Student</label>
                                <input type="text" class="form-control"
                                       value="{{ $fee->student->last_name ?? 'N/A' }}, {{ $fee->student->first_name ?? 'N/A' }} (ID: {{ $fee->student->username ?? $fee->student->prem_number ?? 'N/A' }})"
                                       disabled readonly>
                            </div>

                            {{-- Parent Fee Definition Information (Read Only) --}}
                            @if($fee->feeDefinition)
                                <div class="form-group mb-3">
                                    <label>Parent Fee Structure</label>
                                    <input type="text" class="form-control"
                                           value="{{ $fee->feeDefinition->fee_name }} (Installment {{ $fee->installment_number ?? 'N/A' }} of {{ $fee->feeDefinition->number_of_installments ?? 'N/A' }})"
                                           disabled readonly>
                                    <small class="form-text text-muted">
                                        Total for structure: {{ number_format($fee->feeDefinition->total_amount, 2) }}
                                    </small>
                                </div>
                            @else
                                <div class="form-group mb-3">
                                    <label>Parent Fee Structure</label>
                                    <input type="text" class="form-control" value="N/A (Standalone Fee Installment)" disabled readonly>
                                </div>
                            @endif


                            {{-- Installment Title --}}
                            <div class="form-group mb-3">
                                <label for="title">Installment Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title', $fee->title) }}" placeholder="e.g., Term 1 Fees, Activity Fee" required>
                                @error('title')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            {{-- Amount for this Installment --}}
                            <div class="form-group mb-3">
                                <label for="amount">Installment Amount <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount', $fee->amount) }}" placeholder="0.00" required step="0.01" min="0"
                                    {{ $hasPayments ? 'disabled readonly' : '' }}> {{-- Disable if payments applied --}}
                                @if($hasPayments)
                                    <small class="form-text text-warning">Amount cannot be changed because payments have been applied to this installment.</small>
                                @endif
                                @error('amount')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            {{-- Assigned Date --}}
                            <div class="form-group mb-3">
                                <label for="assigned_date">Assigned Date</label>
                                <input type="date" name="assigned_date" id="assigned_date" class="form-control @error('assigned_date') is-invalid @enderror"
                                       value="{{ old('assigned_date', $fee->assigned_date ? $fee->assigned_date->format('Y-m-d') : '') }}">
                                @error('assigned_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            {{-- Due Date --}}
                            <div class="form-group mb-3">
                                <label for="due_date">Due Date</label>
                                <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror"
                                       value="{{ old('due_date', $fee->due_date ? $fee->due_date->format('Y-m-d') : '') }}">
                                @error('due_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            {{-- Comments --}}
                            <div class="form-group mb-3">
                                <label for="comments">Comments</label>
                                <textarea name="comments" id="comments" class="form-control @error('comments') is-invalid @enderror"
                                          rows="3" placeholder="Optional comments about this installment">{{ old('comments', $fee->comments) }}</textarea>
                                @error('comments')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            {{-- Submit Button --}}
                            <div class="text-center pt-2"> {{-- Added padding-top for spacing --}}
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Installment
                                </button>
                                <a href="{{ route('staff.fees.show', $fee->id) }}" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times-circle me-1"></i> Cancel
                                </a>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    {{-- Add any specific CSS if needed --}}
@stop

@section('js')
    <script> console.log('Fee installment edit page loaded!'); </script>
@stop
