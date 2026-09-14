@extends('layouts.app') {{-- Use your main layout file (e.g., layouts.app or adminlte::page) --}}

@php
    // Assuming $fee is an instance of App\Models\Fee (an installment)
    // and $totalPaid (total paid for this installment)
    // and $balance (balance for this installment)
    // and $fee->feeDefinition (the parent FeeDefinition model) are passed from the controller.
    $isWaived = $fee->is_waived; // Use the accessor from the Fee model
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Fee Installment Details: ' . $fee->title)

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            {{-- Fee Installment Information Card --}}
            <div class="col-md-6">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-file-invoice-dollar me-1"></i>
                            Installment: {{ $fee->title }}
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('staff.fees.edit', $fee->id) }}" class="btn btn-sm btn-info" title="Edit Installment">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="{{ route('staff.fees.print_invoice', $fee->id) }}" class="btn btn-sm btn-secondary" title="Print Invoice for this Installment" target="_blank">
                                <i class="fas fa-print"></i> Print
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            {{-- Parent Fee Definition Info --}}
                            @if($fee->feeDefinition)
                                <dt class="col-sm-4 bg-light py-1">Parent Fee</dt>
                                <dd class="col-sm-8 bg-light py-1">{{ $fee->feeDefinition->fee_name }}</dd>

                                <dt class="col-sm-4 bg-light py-1">Total Structure</dt>
                                <dd class="col-sm-8 bg-light py-1">
                                    {{ number_format($fee->feeDefinition->total_amount, 2) }}
                                    (@ {{ $fee->feeDefinition->number_of_installments }} installment{{ $fee->feeDefinition->number_of_installments > 1 ? 's' : '' }})
                                </dd>

                                <dt class="col-sm-4 bg-light py-1">This Installment</dt>
                                <dd class="col-sm-8 bg-light py-1">#{{ $fee->installment_number ?? 'N/A' }}</dd>
                                <hr class="col-12 my-2">
                            @endif

                            <dt class="col-sm-4">Invoice #</dt>
                            <dd class="col-sm-8">{{ $fee->invoice_no ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Installment Title</dt>
                            <dd class="col-sm-8">{{ $fee->title }}</dd>

                            <dt class="col-sm-4">Student</dt>
                            <dd class="col-sm-8">
                                @if($fee->student)
                                    <a href="{{ route('staff.students.show', $fee->student->id) }}">
                                        {{ $fee->student->last_name ?? 'N/A' }}, {{ $fee->student->first_name ?? 'N/A' }} (ID: {{ $fee->student->username ?? $fee->student->prem_number ?? 'N/A' }})
                                    </a>
                                @else
                                    N/A
                                @endif
                            </dd>

                            <dt class="col-sm-4">Installment Amount</dt>
                            <dd class="col-sm-8">{{ number_format($fee->amount, 2) }}</dd>

                            <dt class="col-sm-4">Assigned Date</dt>
                            <dd class="col-sm-8">{{ $fee->assigned_date ? $fee->assigned_date->format('M d, Y') : 'N/A' }}</dd>

                            <dt class="col-sm-4">Due Date</dt>
                            <dd class="col-sm-8">{{ $fee->due_date ? $fee->due_date->format('M d, Y') : 'N/A' }}</dd>

                            <dt class="col-sm-4">Total Paid (This Installment)</dt>
                            <dd class="col-sm-8">{{ number_format($totalPaid, 2) }}</dd>

                            <dt class="col-sm-4">Balance (This Installment)</dt>
                            <dd class="col-sm-8 fw-bold {{ $balance > 0 && !$isWaived ? 'text-danger' : ($balance == 0 && !$isWaived ? 'text-success' : 'text-secondary') }}">
                                {{ number_format($balance, 2) }}
                            </dd>

                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                @if($isWaived)
                                    <span class="badge bg-secondary">Waived</span> {{-- Using Bootstrap 5 badge classes --}}
                                @elseif(abs($balance) < 0.005 && $fee->amount > 0) {{-- Ensure amount is positive to be considered "Paid" vs 0.00 fee --}}
                                <span class="badge bg-success">Paid</span>
                                @elseif($totalPaid > 0)
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @else
                                    <span class="badge bg-danger">Unpaid</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4">Comments</dt>
                            <dd class="col-sm-8">{!! nl2br(e($fee->comments ?? 'N/A')) !!}</dd>

                            <dt class="col-sm-4">Created By</dt>
                            <dd class="col-sm-8">
                                @if ($fee->creator)
                                    {{ $fee->creator->first_name }} {{ $fee->creator->last_name }}
                                @else
                                    N/A
                                @endif
                            </dd>
                            <dt class="col-sm-4">Created At</dt>
                            <dd class="col-sm-8">{{ $fee->created_at ? $fee->created_at->format('M d, Y H:i') : 'N/A' }}</dd>

                            <dt class="col-sm-4">Last Updated</dt>
                            <dd class="col-sm-8">{{ $fee->updated_at ? $fee->updated_at->format('M d, Y H:i') : 'N/A' }}</dd>
                        </dl>

                        {{-- Action Buttons (Waive/Delete if applicable) --}}
                        <div class="mt-3 border-top pt-3 text-center">
                            @if(!$isWaived && abs($balance) > 0.005) {{-- Can only waive if there's a balance and not already waived --}}
                            <form action="{{ route('staff.fees.waive', $fee->id) }}" method="POST" class="d-inline me-2" onsubmit="return confirm('Are you sure you want to waive this fee installment? This cannot be easily undone.');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning" title="Waive Fee Installment"><i class="fas fa-strikethrough"></i> Waive Installment</button>
                            </form>
                            @endif

                            @if(!$fee->payments()->exists() && !$isWaived) {{-- Can only delete if no payments AND not waived --}}
                            <form action="{{ route('staff.fees.destroy', $fee->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this fee installment?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Fee Installment"><i class="fas fa-trash"></i> Delete Installment</button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Applied Payments Card --}}
            <div class="col-md-6">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history me-1"></i>
                            Applied Payments (to this Installment)
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        @if($fee->payments->isNotEmpty())
                            <table class="table table-sm table-striped table-hover"> {{-- Added table-hover --}}
                                <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Payment Date</th>
                                    <th>Method/Comment</th>
                                    <th class="text-end">Amount Applied</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($fee->payments as $payment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('staff.payments.show', $payment->id) }}">{{ $payment->id }}</a>
                                        </td>
                                        <td>{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : ($payment->pivot->created_at ? $payment->pivot->created_at->format('M d, Y') : 'N/A') }}</td>
                                        <td>{{ $payment->payment_method ?? Str::limit($payment->comments, 30) ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($payment->pivot->amount_applied, 2) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-center text-muted p-3">No payments have been applied to this installment yet.</p>
                        @endif
                    </div>
                    <div class="card-footer text-center">
                        <a href="{{ route('staff.students.payments.index', $fee->student_id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-user-tag me-1"></i> View All Student Payments & Fees
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        /* Optional: Add a bit more distinction for the parent fee info block */
        .bg-light.py-1 {
            /* background-color: #f8f9fa !important; */ /* Ensure this overrides if needed */
            padding-top: 0.25rem !important;
            padding-bottom: 0.25rem !important;
            font-size: 0.875em; /* Slightly smaller font for parent fee info */
        }
        dl.row dt.bg-light, dl.row dd.bg-light {
            border-bottom: 1px dotted #e9ecef; /* Light dotted line between parent fee info items */
        }
        dl.row dt.bg-light:last-of-type, dl.row dd.bg-light:last-of-type {
            border-bottom: none; /* Remove border for the last item in the block */
        }
        hr.col-12.my-2 {
            border-top-color: #dee2e6; /* Make hr more visible if needed */
        }
    </style>
@stop

@section('js')
    <script> console.log('Fee installment show page loaded!'); </script>
@stop
