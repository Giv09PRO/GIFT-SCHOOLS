{{-- resources/views/pages/staff/payments/student_payments.blade.php --}}

@extends('layouts.app') {{-- Use your main layout file --}}

@php
    use App\Helpers\Qs;
    // Variables passed from controller:
    // $student (with loaded fees->payments->pivot, payments->fees->pivot)
    // $totalDueAllInstallments
    // $totalGrossPayments
    // $totalRefunds
    // $netPayments
    // $overallStudentBalance
    // $outstandingFeesData
    // $currentUser (Added)
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Payments & Fees: ' . $student->first_name . ' ' . $student->last_name)


{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">

        {{-- Student Info & Summary Card --}}
        <div class="card card-primary card-outline mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-graduate me-1"></i>
                    {{ $student->first_name }} {{ $student->last_name }} (ID: {{ $student->prem_number ?? 'N/A' }}) - {{ Qs::getCurrentSchoolYear() }} Financial Summary
                </h3>
                <div class="card-tools">
                    {{-- Add Payment Button --}}
                    <a href="{{ route('staff.students.payments.create', $student->id) }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i> Add Payment
                    </a>

                    {{-- Manage Fees Dropdown --}}
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> {{-- Changed data-bs-toggle to data-toggle for Bootstrap 4 --}}
                            <i class="fas fa-file-invoice-dollar me-1"></i> Manage Fees
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right"> {{-- Changed dropdown-menu-end to dropdown-menu-right for Bootstrap 4 --}}
                            <li>
                                <a class="dropdown-item" href="{{ route('staff.fees.create', ['student_id' => $student->id]) }}">
                                    <i class="fas fa-plus-circle me-2"></i>Create Ad-Hoc Fee for Student
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('staff.fees.structure.assign_create', ['student_id' => $student->id]) }}">
                                    <i class="fas fa-tasks me-2"></i>Assign Fee Structure
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-6 mb-3">
                        <strong>Total Fees Assigned:</strong>
                        <span class="h5 d-block">{{ number_format($totalDueAllInstallments, 2) }}</span>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <strong>Gross Payments Received:</strong>
                        <span class="h5 d-block text-success">{{ number_format($totalGrossPayments, 2) }}</span>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <strong>Total Refunds Issued:</strong>
                        <span class="h5 d-block text-warning">{{ number_format($totalRefunds, 2) }}</span>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <strong>Net Payments Received:</strong>
                        <span class="h5 d-block text-primary">{{ number_format($netPayments, 2) }}</span>
                    </div>
                </div>
                <hr>
                <div class="row justify-content-center">
                    <div class="col-md-4 text-center">
                        <strong>Overall Account Balance:</strong>
                        <span class="h4 d-block {{ $overallStudentBalance > 0.005 ? 'text-danger' : 'text-success' }} fw-bold">
                         {{ number_format($overallStudentBalance, 2) }}
                        </span>
                        <small class="text-muted">{{ $overallStudentBalance > 0.005 ? 'Amount Due' : 'Credit/Cleared' }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Outstanding Fees Card --}}
            <div class="col-lg-6">
                <div class="card card-danger card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-exclamation-triangle me-1"></i> Outstanding Fee Installments</h3>
                    </div>
                    <div class="card-body p-0">
                        @if(!empty($outstandingFeesData))
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th>Fee Title</th>
                                        <th>Parent Fee</th>
                                        <th>Due Date</th>
                                        <th class="text-end">Balance</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($outstandingFeesData as $feeData)
                                        <tr>
                                            <td>
                                                <a href="{{ route('staff.fees.show', $feeData['id']) }}">{{ $feeData['title'] }}</a>
                                            </td>
                                            <td>{{ $feeData['parent_fee_name'] }}</td>
                                            <td>{{ $feeData['due_date'] ? \Carbon\Carbon::parse($feeData['due_date'])->format('Y-m-d') : 'N/A' }}</td>
                                            <td class="text-end text-danger font-weight-bold">{{ number_format($feeData['balance'], 2) }}</td> {{-- Changed fw-bold to font-weight-bold for BS4 --}}
                                            <td>
                                                {{-- Determine badge class based on status text --}}
                                                @php
                                                    $statusText = strtolower($feeData['status'] ?? '');
                                                    $badgeClass = 'secondary'; // Default
                                                    if (str_contains($statusText, 'paid') && !str_contains($statusText, 'partially')) $badgeClass = 'success';
                                                    elseif (str_contains($statusText, 'partially paid')) $badgeClass = 'warning';
                                                    elseif (str_contains($statusText, 'overdue')) $badgeClass = 'danger';
                                                    elseif (str_contains($statusText, 'unpaid')) $badgeClass = 'danger';
                                                    // Add more specific conditions if needed (e.g., for waived statuses)
                                                @endphp
                                                <span class="badge badge-{{ $badgeClass }}"> {{-- Changed bg- to badge- for BS4 --}}
                                                    {{ $feeData['status'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <nobr>
                                                <a href="{{ route('staff.fees.show', $feeData['id']) }}" class="btn btn-xs btn-primary" title="View Fee Installment"><i class="fas fa-eye"></i></a>
                                                {{-- Add other relevant actions like waive, edit (if applicable) --}}
                                                </nobr>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-center text-success p-3">No outstanding fee installments found for this student in the current school year.</p>
                        @endif
                    </div>
                    <div class="card-footer text-center">
                        <a href="{{ route('staff.fees.index', ['student_search' => ($student->prem_number ?? $student->first_name . ' ' . $student->last_name)]) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list-alt mr-1"></i> View All Fee Installments for Student {{-- Changed me-1 to mr-1 for BS4 --}}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Payment History Card --}}
            <div class="col-lg-6">
                <div class="card card-success card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Payment History ({{ Qs::getCurrentSchoolYear() }})</h3> {{-- Changed me-1 to mr-1 for BS4 --}}
                    </div>
                    <div class="card-body p-0">
                        @if($student->payments->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($student->payments()->where('syear', Qs::getCurrentSchoolYear())->orderBy('payment_date', 'desc')->orderBy('id', 'desc')->get() as $payment) {{-- Filter by year and order --}}
                                        @php $isRefund = $payment->amount < 0; @endphp
                                        <tr class="{{ $isRefund ? 'table-warning' : '' }}">
                                            <td>
                                                <a href="{{ route('staff.payments.show', $payment->id) }}">{{ $payment->id }}</a>
                                                @if($isRefund) <small class="d-block text-danger">(Refund)</small> @endif
                                            </td>
                                            <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : 'N/A' }}</td>
                                            <td class="text-end {{ $isRefund ? 'text-danger' : 'text-success' }}">{{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ Str::limit($payment->comments, 30) }}</td>
                                            <td>
                                                <nobr>
                                                    <a href="{{ route('staff.payments.show', $payment->id) }}" class="btn btn-xs btn-primary" title="View Payment"><i class="fas fa-eye"></i></a>
                                                    @if(!$isRefund && $payment->amount > 0 && isset($currentUser) && $currentUser->can('process refunds'))
                                                        <form action="{{ route('staff.payments.refund', $payment->id) }}" method="POST" class="d-inline refund-form" data-payment-amount="{{$payment->amount}}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-xs btn-warning" title="Refund Payment"><i class="fas fa-undo"></i></button>
                                                        </form>
                                                    @endif
                                                </nobr>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-center text-muted p-3">No payments recorded for this student in the current school year.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .table-responsive {
            overflow-x: auto;
        }
        .card-body .row .col-md-3 span.h5, .card-body .row .col-md-4 span.h5 {
            font-size: 1.1rem;
        }
        .card-body .row .col-md-4 span.h4 {
            font-size: 1.4rem;
        }
        .card-tools .btn-group .dropdown-menu {
            min-width: 250px;
        }
        .dropdown-item i {
            width: 20px;
            margin-right: 0.5rem; /* Added margin for BS4 compatibility with me-2 */
        }
        .fas.me-1, .fas.mr-1 { /* Ensure FontAwesome spacing works for BS4 */
             margin-right: 0.25rem !important;
        }
        .fas.me-2 {
            margin-right: 0.5rem !important;
        }

    </style>
@endpush

@push('scripts')
    {{-- Ensure jQuery is loaded before Bootstrap JS if not already handled by your layout --}}
    {{-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script> --}} {{-- Or your project's jQuery --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script> --}} {{-- Bootstrap 4 JS Bundle --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        $(document).ready(function() {
            // console.log('Student payments page loaded with Bootstrap 4 compatibility!');

            $('.refund-form').on('submit', function(e) {
                e.preventDefault();
                var form = this;
                var paymentAmount = $(this).data('payment-amount');

                Swal.fire({
                    title: 'Confirm Refund',
                    html: `Are you sure you want to refund this payment of <strong>${parseFloat(paymentAmount).toFixed(2)}</strong>?<br><small>This will process a full refund.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, refund it!',
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            @if(session('flash_success_swal'))
                Swal.fire({ title: 'Success!', text: '{{ session('flash_success_swal') }}', icon: 'success' });
            @endif
            @if(session('flash_error_swal'))
                Swal.fire({ title: 'Error!', text: '{{ session('flash_error_swal') }}', icon: 'error' });
            @endif
            @if(session('flash_info_swal'))
                Swal.fire({ title: 'Info!', text: '{{ session('flash_info_swal') }}', icon: 'info' });
            @endif
        });
    </script>
@endpush
