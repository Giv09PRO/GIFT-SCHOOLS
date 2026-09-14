{{-- resources/views/pages/staff/finance/show.blade.php --}}
{{-- This view displays the detailed financial statement for a specific student and school year. --}}

@extends('layouts.app') {{-- Assuming this is your main application layout, potentially based on AdminLTE --}}

@php
    // Prepare display variables for clarity
    $studentFullName = $student->full_name ?? ($student->first_name . ' ' . $student->last_name);
    $statementYear = $syear ?? 'N/A';
    // Format the statement period string, e.g., "2023-2024" or just "2023"
    // $statementPeriodDisplay = $statementYear . (is_numeric($statementYear) ? '-' . ($statementYear + 1) : '');
    $statementPeriodDisplay = $statementYear; // Simpler for now, adjust if needed
    $schoolDisplayName = $school->display_name ?? ($school->name ?? $school->title ?? 'N/A');
    $schoolAddress = $school->address ?? '';
    $schoolPhone = $school->phone ?? '';
    $schoolEmail = $school->www_address ?? ''; // As per table structure, www_address holds email

    // Ensure numeric values for formatting, default to 0.00
    // These variables are expected to be passed from FinancialStatementService's getStudentStatementViewData method
    // which merges $statementData from Pay.php helper.
    $displayOpeningBalance = $openingBalance ?? 0.00;
    $displayClosingBalance = $closingBalance ?? 0.00;
    // 'totalFeesAmount' is set to netFeesForYear by FinancialStatementService for the view
    $displayTotalFees = $totalFeesAmount ?? 0.00; 
    // 'totalAmountAppliedToFeesThisYear' from Pay.php for payments applied to current period's fees
    $displayTotalPaymentsAppliedToPeriodFees = $totalAmountAppliedToFeesThisYear ?? 0.00; 
    // 'totalPaymentsMadeThisYear' from Pay.php for all payments made/recorded in this period
    $displayTotalPaymentsMadeThisPeriod = $totalPaymentsMadeThisYear ?? 0.00; 

@endphp

@section('title', __("Financial Statement: :name (:year)", ['name' => $studentFullName, 'year' => $statementPeriodDisplay]))

@section('content_header')
    {{-- Content header can be customized or removed if layout.app handles it --}}
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    {{ __('Financial Statement') }}
                    <small class="text-muted">{{ __('for :name', ['name' => $studentFullName]) }}</small>
                </h1>
            </div>
            <div class="col-sm-6">
                <div class="float-sm-right">
                    <a href="{{ route('staff.finance.statements.index', ['syear' => $syear]) }}" class="btn btn-sm btn-outline-secondary mr-2">
                        <i class="fas fa-arrow-left mr-1"></i> {{ __('Back to List') }}
                    </a>
                    <a href="{{ route('staff.students.finance.statement.pdf', ['student' => $student->id, 'syear' => $syear]) }}" target="_blank" class="btn btn-sm btn-danger">
                        <i class="fas fa-file-pdf mr-1"></i> {{ __('Generate PDF') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
<div class="container-fluid">

    @include('layouts.partials.alerts') {{-- For flash messages --}}

    {{-- Year Selection --}}
    <div class="row mb-3">
        <div class="col-md-6 offset-md-6">
            <form method="GET" action="{{ route('staff.students.finance.statement.show', ['student' => $student->id]) }}" class="form-inline float-md-right">
                <label for="syear" class="my-1 mr-2 font-weight-normal">{{ __('View Statement for Year:') }}</label>
                <select name="syear" id="syear" class="form-control form-control-sm my-1 mr-sm-2" onchange="this.form.submit()" style="min-width: 100px;">
                    @foreach ($years ?? [] as $year_option)
                        <option value="{{ $year_option }}" @selected(($syear ?? null) == $year_option)>
                            {{ $year_option }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- Main Statement Card --}}
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-file-invoice-dollar mr-1"></i>
                <strong>{{ __('Statement for :name', ['name' => $studentFullName]) }}</strong>
                <span class="text-muted"> (ID: {{ $student->username ?? $student->id }})</span>
            </h3>
            <div class="card-tools">
                <span class="badge badge-light p-2">{{ __('Issued:') }} {{ ($issueDate ?? now())->format('M d, Y H:i A') }}</span>
            </div>
        </div>

        <div class="card-body">
            {{-- School and Period Information --}}
            <div class="row mb-4 p-3 bg-light rounded">
                <div class="col-md-6">
                    <h5>{{ $schoolDisplayName }}</h5>
                    @if($schoolAddress)<address class="mb-1"><i class="fas fa-map-marker-alt mr-1 text-muted"></i> {{ $schoolAddress }}</address>@endif
                    @if($schoolPhone)<p class="mb-1"><i class="fas fa-phone mr-1 text-muted"></i> {{ $schoolPhone }}</p>@endif
                    @if($schoolEmail)<p class="mb-0"><i class="fas fa-envelope mr-1 text-muted"></i> {{ $schoolEmail }}</p>@endif
                </div>
                <div class="col-md-6 text-md-right">
                    <h5 class="mb-1">{{ __('Statement Period:') }} <span class="font-weight-bold">{{ $statementPeriodDisplay }}</span></h5>
                    <p class="mb-1">
                        {{ __('Opening Balance:') }} <strong class="text-primary">{{ number_format($displayOpeningBalance, 2) }}</strong>
                        <br><small class="text-muted">{{ __('(Carried forward from previous periods)') }}</small>
                    </p>
                    <p class="mb-0">
                        {{ __('Closing Balance for Period:') }} <strong class="text-danger">{{ number_format($displayClosingBalance, 2) }}</strong>
                        <br><small class="text-muted">{{ __('(As of end of :year)', ['year' => $statementPeriodDisplay]) }}</small>
                    </p>
                </div>
            </div>

            {{-- Transactions Table --}}
            <h4 class="mb-3 text-center">{{ __('Transaction Details') }}</h4>
            <div class="table-responsive">
                <table id="statementTransactionsTable" class="table table-striped table-bordered table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            @foreach($heads ?? [] as $head)
                                @if(is_array($head))
                                    <th width="{{ $head['width'] ?? 'auto' }}%" class="{{ $head['class'] ?? '' }}">{{ __($head['label']) }}</th>
                                @else
                                    <th>{{ __($head) }}</th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($config['data'] ?? [] as $row)
                            <tr>
                                <td class="{{ isset($heads[0]) && is_array($heads[0]) && !empty($heads[0]['class']) ? $heads[0]['class'] : '' }}">{!! $row['date'] ?? 'N/A' !!}</td>
                                <td class="{{ isset($heads[1]) && is_array($heads[1]) && !empty($heads[1]['class']) ? $heads[1]['class'] : (is_string($heads[1] ?? null) ? '' : '') }}">{!! $row['description_html'] ?? '' !!}</td>
                                <td class="{{ isset($heads[2]) && is_array($heads[2]) && !empty($heads[2]['class']) ? $heads[2]['class'] : 'text-right' }}">{!! $row['charge'] ?? '' !!}</td>
                                <td class="{{ isset($heads[3]) && is_array($heads[3]) && !empty($heads[3]['class']) ? $heads[3]['class'] : 'text-right' }}">{!! $row['credit'] ?? '' !!}</td>
                                <td class="{{ isset($heads[4]) && is_array($heads[4]) && !empty($heads[4]['class']) ? $heads[4]['class'] : 'text-right' }}">{!! $row['running_balance'] ?? '' !!}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($heads ?? [1]) }}" class="text-center py-4">
                                    <i class="fas fa-info-circle mr-1"></i> {{ __('No transactions found for this period.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Financial Summary for the Period --}}
            <div class="mt-4 pt-3 border-top">
                <h4 class="mb-3 text-center">{{ __('Summary for :year', ['year' => $statementPeriodDisplay]) }}</h4>
                <div class="row justify-content-center">
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ __('Total Fees Charged') }}</span>
                                <span class="info-box-number h5">{{ number_format($displayTotalFees, 2) }}</span>
                                <small class="text-muted">{{ __('(Net fees this period)') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-primary"><i class="fas fa-receipt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ __('Total Payments Received') }}</span>
                                <span class="info-box-number h5">{{ number_format($displayTotalPaymentsMadeThisPeriod, 2) }}</span>
                                <small class="text-muted">{{ __('(During this period)') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-info"><i class="fas fa-hand-holding-usd"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ __('Payments Applied') }}</span>
                                <span class="info-box-number h5">{{ number_format($displayTotalPaymentsAppliedToPeriodFees, 2) }}</span>
                                <small class="text-muted">{{ __('(To this period\'s fees)') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-danger"><i class="fas fa-balance-scale"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ __('Closing Balance') }}</span>
                                <span class="info-box-number h5">{{ number_format($displayClosingBalance, 2) }}</span>
                                <small class="text-muted">{{ __('(As of end of :year)', ['year' => $statementPeriodDisplay]) }}</small> {{-- Corrected Key --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> {{-- /.card-body --}}

        <div class="card-footer text-muted text-sm">
            <p class="mb-1">{{ __('If you have any questions concerning this statement, please contact the school office at :phone.', ['phone' => $schoolPhone ?? __('the school phone number')]) }}</p>
            <p class="mb-0">{{ __('Thank you for your prompt attention to your account.') }}</p>
        </div>
    </div> {{-- /.card --}}

</div> {{-- /.container-fluid --}}
@endsection

@push('styles')
<style>
    .table-sm th, .table-sm td {
        padding: 0.5rem; /* Slightly more padding for readability */
    }
    .info-box {
        min-height: 100px; /* Ensure consistent height */
    }
    .info-box-icon {
        width: 70px; /* Adjust icon box size */
        font-size: 1.8rem;
    }
    .info-box-content {
        padding: 10px 15px;
    }
    .card-title strong {
        font-size: 1.15rem;
    }
    address {
        font-style: normal;
        line-height: 1.6;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        console.log('Student financial statement page initialized.');
        // Initialize DataTable for the transactions table if needed,
        // but for a statement, often a simple table is preferred for printing.
        // If DataTables is used, ensure it's configured for a statement (e.g., no search/paging).
        if ($.fn.DataTable) {
            $('#statementTransactionsTable').DataTable({
                "paging": false,
                "searching": false,
                "info": false,
                "ordering": true, // Allow column sorting
                "order": [[0, "asc"]], // Default order by date
                responsive: true,
                autoWidth: false,
                // "buttons": ['copy', 'csv', 'excel', 'print'] // Add if needed for this table
            });
        }
    });
</script>
@endpush
