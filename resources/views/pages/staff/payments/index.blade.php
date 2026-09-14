@extends('layouts.app')

@php
    use App\Helpers\Qs;
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Manage Payments')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid"> {{-- container-fluid is standard --}}

        {{-- Filter Card --}}
        <div class="card card-outline card-info mb-4"> {{-- Changed color to info --}}
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Payments</h3>
                <a href="{{ route('staff.students.index') }}" class="btn btn-sm btn-success">
                    <i class="fas fa-search me-1"></i> Find Student to Add Payment
                </a>
                 <a href="{{ route('staff.payments.allocate_unlinked.form') }}" class="btn btn-sm btn-warning">
                    <i class="fas fa-link me-1"></i> Allocate Unlinked Payments
                </a>
            </div>
            <div class="card-body">
                {{-- Note: This form filters the data *before* it's passed to the client-side datatable --}}
                <form method="GET" action="{{ route('staff.payments.index') }}" class="row g-3 align-items-end">

                    {{-- Row 1: Search and Dates --}}
                    <div class="col-md-4">
                        <label for="student_search" class="form-label">Student Name/ID</label>
                        <input type="text" name="student_search" id="student_search" class="form-control form-control-sm"
                               value="{{ request('student_search') }}" placeholder="Enter name or ID">
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Payment Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control form-control-sm"
                               value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Payment Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control form-control-sm"
                               value="{{ request('date_to') }}">
                    </div>

                    {{-- Row 2: Amounts and Statuses --}}
                    <div class="col-md-2">
                        <label for="amount_from" class="form-label">Amount From</label>
                        <input type="number" step="0.01" name="amount_from" id="amount_from" class="form-control form-control-sm"
                               value="{{ request('amount_from') }}" placeholder="0.00">
                    </div>
                    <div class="col-md-2">
                        <label for="amount_to" class="form-label">Amount To</label>
                        <input type="number" step="0.01" name="amount_to" id="amount_to" class="form-control form-control-sm"
                               value="{{ request('amount_to') }}" placeholder="e.g., 500.00">
                    </div>
                    <div class="col-md-3">
                        <label for="payment_type" class="form-label">Payment Type</label>
                        <select name="payment_type" id="payment_type" class="form-select form-select-sm select2">
                            @foreach ($paymentTypes as $value => $label)
                                <option value="{{ $value }}" {{ request('payment_type') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="allocation_status" class="form-label">Allocation Status</label>
                        <select name="allocation_status" id="allocation_status" class="form-select form-select-sm select2">
                            @foreach ($allocationStatuses as $value => $label)
                                <option value="{{ $value }}" {{ request('allocation_status') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    {{-- Row 3: Buttons --}}
                    <div class="col-12 mt-3 text-center">
                        <button type="submit" class="btn btn-info me-2"><i class="fas fa-search me-1"></i>Filter Payments</button>
                        <a href="{{ route('staff.payments.index') }}" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results Card using AdminLTE Datatable --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Payment List</h3>
            </div>
            <div class="card-body">
                <x-adminlte-datatable id="paymentsTable" :heads="$heads" :config="$config" striped hoverable bordered compressed>
                </x-adminlte-datatable>
            </div>
        </div>
    </div>

    {{-- Include Refund Modal if needed --}}
    {{-- @include('pages.staff.payments.partials.refund_modal') --}}

@stop

{{-- Optional: Add page-specific CSS/JS if needed --}}
@section('css')
    {{-- Add any custom CSS for this page --}}
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });
        });
    </script>
    <script>
        console.log('Payment index with Datatable loaded!');
        // Add JS for refund modal interaction if implementing refund button here
        // $(document).ready(function() {
        //     $('#paymentsTable').on('click', '.btn-refund', function() {
        //         const paymentId = $(this).data('payment-id');
        //         const maxAmount = $(this).data('max-amount');
        //         // Populate and show your refund modal here
        //         // Ensure the modal ID and input IDs match your modal partial
        //         $('#refundModal').find('form').attr('action', '/staff/payments/' + paymentId + '/refund'); // Adjust route generation if needed
        //         $('#refundModal').find('#refund_amount').val(maxAmount.toFixed(2)).attr('max', maxAmount.toFixed(2));
        //         $('#refundModal').modal('show');
        //     });
        // });
    </script>
@stop
