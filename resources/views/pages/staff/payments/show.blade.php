{{-- resources/views/pages/staff/payments/show.blade.php --}}

@extends('layouts.app') {{-- Use your main layout file --}}

@php
    use App\Helpers\Qs; // Assuming Qs helper is available and used if needed.
    // Variable passed from controller:
    // $payment (with loaded student, fees->pivot, creator, refunds relationships)
    // $totalRefundedForThis (calculated in controller: sum of amounts of refunds linked to this payment)
    // $currentUser (should be passed from controller or use Auth::user() directly if available globally in views)
    // For simplicity, let's assume $currentUser is available or use Auth::user()
    $currentUser = Auth::user(); // Make sure this is appropriate for your app's context
    $isRefund = $payment->amount < 0;
    $isOriginalPayment = !$isRefund && $payment->refunded_payment_id === null; // Is it an original payment (not a refund itself)

    // Calculate remaining balance of the original payment if it's not a refund itself
    $remainingOriginalPaymentBalance = 0;
    if ($isOriginalPayment) {
        $remainingOriginalPaymentBalance = $payment->amount - $totalRefundedForThis;
    }
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', ($isRefund ? 'Refund Details: #' : 'Payment Details: #') . $payment->id)

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            {{-- Payment Information Card --}}
            <div class="col-lg-7 col-md-12">
                <div class="card card-info card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-money-check-alt mr-1"></i> {{-- mr-1 for BS4 margin --}}
                            @if($isRefund)
                                Refund #{{ $payment->id }}
                            @else
                                Payment #{{ $payment->id }}
                            @endif
                        </h3>
                        <div class="card-tools">
                            {{-- Edit Button --}}
                            @if (!$isRefund && $currentUser->can('manage finances')) {{-- Assuming 'manage finances' implies update permission or use PaymentPolicy @can('update', $payment) --}}
                                <a href="{{ route('staff.payments.edit', $payment->id) }}" class="btn btn-sm btn-info" title="Edit Payment Details (Date, Comments)">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            @endif

                            {{-- Reallocate Button (for same student, different fees) --}}
                            @if ($isOriginalPayment && $payment->fees->isNotEmpty() && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances'))
                                <a href="{{ route('staff.payments.reallocate.form', $payment->id) }}" class="btn btn-sm btn-primary" title="Reallocate this payment to different fees for {{ $payment->student->first_name }}">
                                    <i class="fas fa-random"></i> Reallocate
                                </a>
                            @endif

                            {{-- Transfer to Another Student Button --}}
                            @if ($isOriginalPayment && $payment->fees->isNotEmpty() && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances'))
                                <button type="button" class="btn btn-sm btn-purple" title="Transfer this payment to another student"
                                        data-toggle="modal" data-target="#transferPaymentModal"> {{-- BS4 attributes --}}
                                    <i class="fas fa-exchange-alt"></i> Transfer
                                </button>
                            @endif

                            {{-- Refund Button --}}
                            @if ($isOriginalPayment && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances'))
                                <button type="button" class="btn btn-sm btn-warning" title="Refund this payment"
                                        data-toggle="modal" data-target="#refundPaymentModal" {{-- BS4 attributes --}}
                                        data-payment-id="{{ $payment->id }}"
                                        data-max-refundable="{{ number_format($remainingOriginalPaymentBalance, 2, '.', '') }}">
                                    <i class="fas fa-undo"></i> Refund
                                </button>
                            @endif

                            {{-- Delete Button --}}
                            @php
                                $canDelete = $currentUser->can('manage finances') && // Or specific delete permission
                                             $payment->fees->isEmpty() &&
                                             !$isRefund &&
                                             !$payment->refunds()->exists() && // Check if any refunds made against this payment
                                             $payment->refunded_payment_id === null; // Check if this payment itself is not a refund record
                            @endphp
                            @if ($canDelete)
                                <form action="{{ route('staff.payments.destroy', $payment->id) }}" method="POST" class="d-inline" onsubmit="return confirm('WARNING: Deleting payments can cause irreversible data integrity issues. This payment is unallocated and has no refunds. Are you absolutely sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Payment"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">Payment ID</dt>
                            <dd class="col-sm-8">{{ $payment->id }}</dd>

                            @if($isRefund)
                                <dt class="col-sm-4">Type</dt>
                                <dd class="col-sm-8"><span class="badge badge-warning">Refund</span></dd> {{-- BS4 badge --}}
                                @if($payment->refunded_payment_id && $payment->refundedPayment) {{-- Use corrected relationship name --}}
                                    <dt class="col-sm-4">Original Payment</dt>
                                    <dd class="col-sm-8">
                                        <a href="{{ route('staff.payments.show', $payment->refunded_payment_id) }}">Payment #{{ $payment->refunded_payment_id }}</a>
                                        (Amount: {{ Qs::formatCurrency($payment->refundedPayment->amount) }})
                                    </dd>
                                @endif
                            @else
                                <dt class="col-sm-4">Type</dt>
                                <dd class="col-sm-8"><span class="badge badge-success">Payment</span></dd> {{-- BS4 badge --}}
                            @endif


                            <dt class="col-sm-4">Student</dt>
                            <dd class="col-sm-8">
                                @if($payment->student)
                                    <a href="{{ route('staff.students.show', $payment->student->id) }}">
                                        {{ $payment->student->first_name ?? '' }} {{ $payment->student->middle_name ?? '' }} {{ $payment->student->last_name ?? '' }}
                                        (ID: {{ $payment->student->prem_number ?? ($payment->student->username ?? 'N/A') }})
                                    </a>
                                @else
                                    N/A
                                @endif
                            </dd>

                            <dt class="col-sm-4">Amount</dt>
                            <dd class="col-sm-8 font-weight-bold {{ $isRefund ? 'text-danger' : 'text-success' }}"> {{-- BS4 font-weight-bold --}}
                                {{ Qs::formatCurrency($payment->amount) }}
                            </dd>
                             @if ($isOriginalPayment && $totalRefundedForThis > 0)
                                <dt class="col-sm-4 text-muted">Total Refunded</dt>
                                <dd class="col-sm-8 text-muted">{{ Qs::formatCurrency($totalRefundedForThis) }}</dd>
                                <dt class="col-sm-4 text-info">Remaining Balance</dt>
                                <dd class="col-sm-8 text-info font-weight-bold">{{ Qs::formatCurrency($remainingOriginalPaymentBalance) }}</dd> {{-- BS4 font-weight-bold --}}
                            @endif


                            <dt class="col-sm-4">Payment Date</dt>
                            <dd class="col-sm-8">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') : 'N/A' }}</dd>

                            <dt class="col-sm-4">Payment Method</dt>
                            <dd class="col-sm-8">{{ $payment->payment_method ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Transaction ID/Ref</dt>
                            <dd class="col-sm-8">{{ $payment->transaction_id ?? ($payment->reference_number ?? 'N/A') }}</dd>

                            <dt class="col-sm-4">Lunch Payment</dt>
                            <dd class="col-sm-8">{{ $payment->lunch_payment ? 'Yes' : 'No' }}</dd>

                            <dt class="col-sm-4">Comments/Notes</dt>
                            <dd class="col-sm-8">{!! nl2br(e($payment->comments ?? 'N/A')) !!}</dd>

                            <dt class="col-sm-4">Recorded By</dt>
                            <dd class="col-sm-8">
                                {{ $payment->creator->first_name ?? '' }} {{ $payment->creator->last_name ?? ($payment->created_by_user->name ?? ($payment->created_by ?? 'System/N/A')) }}
                            </dd>

                            <dt class="col-sm-4">Recorded At</dt>
                            <dd class="col-sm-8">{{ $payment->created_at ? \Carbon\Carbon::parse($payment->created_at)->format('M d, Y H:i A') : 'N/A' }}</dd>

                            <dt class="col-sm-4">Last Updated</dt>
                            <dd class="col-sm-8">{{ $payment->updated_at ? \Carbon\Carbon::parse($payment->updated_at)->format('M d, Y H:i A') : 'N/A' }}</dd>

                        </dl>
                    </div>
                    <div class="card-footer text-center">
                        @if($payment->student)
                            <a href="{{ route('staff.students.payments.index', $payment->student_id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-user-tag mr-1"></i> View All Payments & Fees for {{ $payment->student->first_name }} {{-- BS4 mr-1 --}}
                            </a>
                        @endif
                        <a href="{{ route('staff.payments.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list-ul mr-1"></i> View All Payments {{-- BS4 mr-1 --}}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Fee Allocations Card --}}
            <div class="col-lg-5 col-md-12">
                <div class="card card-secondary card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-tasks mr-1"></i> {{-- BS4 mr-1 --}}
                            Fee Allocations
                        </h3>
                         @if ($isOriginalPayment && $payment->fees->isNotEmpty() && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances'))
                            <div class="card-tools">
                                <a href="{{ route('staff.payments.reallocate.form', $payment->id) }}" class="btn btn-xs btn-outline-primary" title="Reallocate this payment to different fees for {{ $payment->student->first_name }}">
                                    <i class="fas fa-random mr-1"></i> Manage Allocations {{-- BS4 mr-1 --}}
                                </a>
                            </div>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @if($payment->fees->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th>Fee Title</th>
                                        <th>Due Date</th>
                                        <th class="text-right">Amount Applied</th> {{-- BS4 text-right --}}
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($payment->fees as $fee) {{-- $fee is an Fee model --}}
                                    <tr>
                                        <td>
                                            <a href="{{ route('staff.fees.show', $fee->id) }}">{{ $fee->title }}</a>
                                            <small class="d-block text-muted">{{ $fee->feeDefinition->fee_name ?? 'General Fee' }}</small>
                                        </td>
                                        <td>{{ $fee->due_date ? \Carbon\Carbon::parse($fee->due_date)->format('Y-m-d') : 'N/A' }}</td>
                                        <td class="text-right">{{ Qs::formatCurrency($fee->pivot->amount_applied) }}</td> {{-- BS4 text-right --}}
                                        <td>
                                            <a href="{{ route('staff.fees.show', $fee->id) }}" class="btn btn-xs btn-outline-info" title="View Fee Installment"><i class="fas fa-eye"></i></a>
                                        </td>
                                    </tr>
                                    @endforeach
                                    </tbody>
                                    <tfoot>
                                    <tr class="table-light font-weight-bold"> {{-- BS4 font-weight-bold --}}
                                        <td colspan="2" class="text-right">Total Allocated:</td> {{-- BS4 text-right --}}
                                        <td class="text-right" colspan="2">{{ Qs::formatCurrency($payment->fees->sum('pivot.amount_applied')) }}</td> {{-- BS4 text-right --}}
                                    </tr>
                                    @php
                                        $totalAllocated = $payment->fees->sum('pivot.amount_applied');
                                        $unallocatedAmountDisplay = 0;
                                        if (!$isRefund) {
                                            $unallocatedAmountDisplay = $payment->amount - $totalAllocated;
                                        }
                                    @endphp
                                    @if(!$isRefund && $unallocatedAmountDisplay > 0.005)
                                        <tr class="table-warning font-weight-bold"> {{-- BS4 font-weight-bold --}}
                                            <td colspan="2" class="text-right">Unallocated Amount:</td> {{-- BS4 text-right --}}
                                            <td class="text-right" colspan="2">{{ Qs::formatCurrency($unallocatedAmountDisplay) }}</td> {{-- BS4 text-right --}}
                                        </tr>
                                    @endif
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <p class="text-center text-muted p-3">
                                @if($isRefund)
                                    This refund has not been specifically allocated against any fee installments.
                                @else
                                    This payment has not been allocated to any specific fee installments.
                                @endif
                            </p>
                            @if(!$isRefund && $payment->amount > 0)
                                <p class="text-center p-3">
                                    <strong>Unallocated Amount: {{ Qs::formatCurrency($payment->amount) }}</strong>
                                </p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Refund Payment Modal --}}
    @if ($isOriginalPayment && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances'))
    <div class="modal fade" id="refundPaymentModal" tabindex="-1" role="dialog" aria-labelledby="refundPaymentModalLabel" aria-hidden="true"> {{-- BS4 role="dialog" --}}
        <div class="modal-dialog" role="document"> {{-- BS4 role="document" --}}
            <form action="{{ route('staff.payments.refund', $payment->id) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="refundPaymentModalLabel">Refund Payment #{{ $payment->id }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"> {{-- BS4 close button --}}
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Original Payment Amount: <strong>{{ Qs::formatCurrency($payment->amount) }}</strong></p>
                        <p>Total Already Refunded: <strong>{{ Qs::formatCurrency($totalRefundedForThis) }}</strong></p>
                        <p>Max Refundable Amount: <strong id="maxRefundableText">{{ Qs::formatCurrency($remainingOriginalPaymentBalance) }}</strong></p>

                        @if(session('errors') && session('errors')->refundBag->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach (session('errors')->refundBag->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group"> {{-- BS4 form-group --}}
                            <label for="refund_amount">Refund Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="refund_amount" name="refund_amount"
                                   value="{{ old('refund_amount', number_format($remainingOriginalPaymentBalance, 2, '.', '')) }}"
                                   step="0.01" min="0.01" max="{{ number_format($remainingOriginalPaymentBalance, 2, '.', '') }}" required>
                        </div>
                        <div class="form-group"> {{-- BS4 form-group --}}
                            <label for="refund_date">Refund Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="refund_date" name="refund_date" value="{{ old('refund_date', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="form-group"> {{-- BS4 form-group --}}
                            <label for="refund_comment">Refund Comment/Reason</label>
                            <textarea class="form-control" id="refund_comment" name="refund_comment" rows="3">{{ old('refund_comment') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> {{-- BS4 data-dismiss --}}
                        <button type="submit" class="btn btn-warning"><i class="fas fa-undo"></i> Process Refund</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif


    {{-- Transfer Payment Modal --}}
    @if ($isOriginalPayment && $payment->fees->isNotEmpty() && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances'))
    <div class="modal fade" id="transferPaymentModal" tabindex="-1" role="dialog" aria-labelledby="transferPaymentModalLabel" aria-hidden="true"> {{-- BS4 role="dialog" --}}
        <div class="modal-dialog modal-lg" role="document"> {{-- BS4 role="document" --}}
            <form action="{{ route('staff.payments.transfer', $payment->id) }}" method="POST" id="transferPaymentForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="transferPaymentModalLabel">Transfer Payment #{{ $payment->id }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"> {{-- BS4 close button --}}
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>You are about to transfer the payment of <strong>{{ Qs::formatCurrency($payment->amount) }}</strong> made by
                           <strong>{{ $payment->student->first_name ?? '' }} {{ $payment->student->last_name ?? '' }}</strong>.</p>
                        <p class="text-muted small">This will reverse the allocations for the current student and create a new unallocated payment for the target student.</p>

                        @if(session('errors') && session('errors')->transferBag->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach (session('errors')->transferBag->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group"> {{-- BS4 form-group --}}
                            <label for="target_student_id">Transfer To Student <span class="text-danger">*</span></label>
                            <select class="form-control select2-students" id="target_student_id" name="target_student_id" required style="width: 100%;">
                                <option value="">Search and select target student...</option>
                                {{-- Options will be populated by Select2 AJAX --}}
                            </select>
                            <small class="form-text text-muted">The payment will be transferred to this student.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group"> {{-- BS4 form-group --}}
                                <label for="transfer_date">Transfer Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="transfer_date" name="transfer_date" value="{{ old('transfer_date', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6 form-group"> {{-- BS4 form-group --}}
                                <label for="transfer_amount_display">Transfer Amount</label>
                                <input type="text" class="form-control" id="transfer_amount_display" value="{{ Qs::formatCurrency($payment->amount) }}" readonly>
                                {{-- The actual amount is taken from $payment->amount in the controller --}}
                            </div>
                        </div>

                        <div class="form-group"> {{-- BS4 form-group --}}
                            <label for="transfer_comment">Transfer Comment/Reason</label>
                            <textarea class="form-control" id="transfer_comment" name="transfer_comment" rows="3">{{ old('transfer_comment') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button> {{-- BS4 data-dismiss --}}
                        <button type="submit" class="btn btn-purple"><i class="fas fa-exchange-alt"></i> Confirm Transfer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

@endsection

@push('styles')
    {{-- Add any specific CSS for this page if needed --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    {{-- Removed Select2 Bootstrap 5 theme link. Add a BS4 theme if you use one, e.g., select2-bootstrap4-theme --}}
    {{-- <link rel="stylesheet" href="path/to/your/select2-bootstrap4-theme.min.css" /> --}}
    <style>
        .dl-row dt {
            font-weight: 500; 
        }
        .card-tools .btn, .card-tools .d-inline, .card-tools .dropdown {
            margin-left: 5px;
        }
        .btn-purple { 
            color: #fff;
            background-color: #6f42c1;
            border-color: #6f42c1;
        }
        .btn-purple:hover {
            color: #fff;
            background-color: #5a359e;
            border-color: #533191;
        }
        /* Basic Select2 styling for BS4, adjust if you have a theme */
        .select2-container .select2-selection--single {
            height: calc(1.5em + .75rem + 2px); /* Default BS4 input height */
            padding: .375rem .75rem;
            line-height: 1.5;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .75rem); /* Adjust arrow height */
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for student search in Transfer Modal
            if ($('#transferPaymentModal').length) {
                $('.select2-students').select2({
                    // theme: "bootstrap4", // Uncomment and use if you have select2-bootstrap4-theme
                    dropdownParent: $('#transferPaymentModal'), 
                    ajax: {
                        url: "{{ route('staff.students.search_json') }}", 
                        dataType: 'json',
                        delay: 250, 
                        data: function (params) {
                            return {
                                q: params.term, 
                                page: params.page || 1,
                                current_student_id: {{ $payment->student_id ?? 'null' }} 
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: $.map(data.data, function (item) { 
                                    return {
                                        text: item.first_name + ' ' + (item.middle_name ? item.middle_name + ' ' : '') + item.last_name + ' (ID: ' + (item.prem_number || item.username) + ')',
                                        id: item.id
                                    }
                                }),
                                pagination: {
                                    more: (params.page * data.per_page) < data.total 
                                }
                            };
                        },
                        cache: true
                    },
                    placeholder: 'Search for a student by name or ID',
                    minimumInputLength: 2, 
                });
            }

            var refundModal = document.getElementById('refundPaymentModal');
            if (refundModal) {
                $(refundModal).on('show.bs.modal', function (event) { // BS4 event name
                    var button = $(event.relatedTarget); // jQuery object for relatedTarget
                    var maxRefundable = parseFloat(button.data('max-refundable')).toFixed(2); // Use .data()

                    var modalMaxRefundableText = $(this).find('#maxRefundableText'); // Use jQuery find
                    var modalRefundAmountInput = $(this).find('#refund_amount');

                    if (modalMaxRefundableText.length) modalMaxRefundableText.text('{{ Qs::getCurrencySymbol() ?? '$' }}' + maxRefundable);
                    if (modalRefundAmountInput.length) {
                        modalRefundAmountInput.val(maxRefundable);
                        modalRefundAmountInput.attr('max', maxRefundable); // Use .attr() for max
                    }
                });
            }

            // If there are validation errors for a modal, automatically re-open it using Bootstrap 4 jQuery.
            @if(session('errors') && session('errors')->refundBag->any())
                $('#refundPaymentModal').modal('show');
            @endif

            @if(session('errors') && session('errors')->transferBag->any())
                $('#transferPaymentModal').modal('show');
            @endif
        });
    </script>
@endpush
