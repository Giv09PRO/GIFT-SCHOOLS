{{-- *** UPDATED: Use the app layout that includes AdminLTE setup *** --}}
@extends('layouts.app')

{{-- *** UPDATED: Page Title (Browser Tab) *** --}}
@section('title', 'Fee Report Results')

{{-- *** UPDATED: Main Page Content Wrapper *** --}}
@section('content')

    {{-- Session Messages and Validation Errors --}}
    <div class="row">
        <div class="col-md-12">
            {{-- Display Standard Validation Errors --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Alert!</h5>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Display Session Flash Messages --}}
            @foreach (['success', 'danger', 'warning', 'info'] as $msgType)
                @if(session('flash_' . $msgType))
                    <div class="alert alert-{{ $msgType == 'danger' ? 'danger' : ($msgType == 'success' ? 'success' : $msgType) }} alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5>
                            @if($msgType == 'success')<i class="icon fas fa-check"></i> Success!
                            @elseif($msgType == 'danger')<i class="icon fas fa-ban"></i> Error!
                            @elseif($msgType == 'warning')<i class="icon fas fa-exclamation-triangle"></i> Warning!
                            @else<i class="icon fas fa-info"></i> Info!
                            @endif
                        </h5>
                        {{ session('flash_' . $msgType) }}
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- 1. Filter Form Card (Repopulated) --}}
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Filter Report</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('staff.fees.report.generate') }}">
                @csrf
                <div class="row">
                    {{-- Report Year --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="report_syear">Report Year:</label>
                            <select name="report_syear" id="report_syear" class="form-control select2 @error('report_syear') is-invalid @enderror" required>
                                @foreach($formFilterData['years'] as $year)
                                    <option value="{{ $year }}" {{ (old('report_syear', $validated['report_syear'] ?? $syear ?? '')) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                            @error('report_syear') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Report Type --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="report_type">Report Type:</label>
                            <select name="report_type" id="report_type" class="form-control select2 @error('report_type') is-invalid @enderror" required>
                                @foreach($formFilterData['reportTypes'] as $key => $value)
                                    <option value="{{ $key }}" {{ (old('report_type', $validated['report_type'] ?? $reportType ?? '')) == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                             @error('report_type') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Group By --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="group_by">Group By:</label>
                            <select name="group_by" id="group_by" class="form-control select2 @error('group_by') is-invalid @enderror" required>
                                @foreach($formFilterData['groupingOptions'] as $key => $value)
                                    <option value="{{ $key }}" {{ (old('group_by', $validated['group_by'] ?? $groupBy ?? '')) == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('group_by') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- School Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_school_id">School:</label>
                            <select name="filter_school_id" id="filter_school_id" class="form-control select2 @error('filter_school_id') is-invalid @enderror">
                                @foreach($formFilterData['schools'] as $id => $title)
                                    <option value="{{ $id }}" {{ (old('filter_school_id', $validated['filter_school_id'] ?? '')) == $id ? 'selected' : '' }}>{{ $title }}</option>
                                @endforeach
                            </select>
                            @error('filter_school_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                     {{-- Grade Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_grade_id">Grade Level:</label>
                            <select name="filter_grade_id" id="filter_grade_id" class="form-control select2 @error('filter_grade_id') is-invalid @enderror">
                                @foreach($formFilterData['grades'] as $id => $title)
                                    <option value="{{ $id }}" {{ (old('filter_grade_id', $validated['filter_grade_id'] ?? '')) == $id ? 'selected' : '' }}>{{ $title }}</option>
                                @endforeach
                            </select>
                            @error('filter_grade_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Student Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_student_id">Student ID (Optional):</label>
                            <input type="text" name="filter_student_id" id="filter_student_id" class="form-control @error('filter_student_id') is-invalid @enderror" value="{{ old('filter_student_id', $validated['filter_student_id'] ?? '') }}" placeholder="Enter Student ID">
                            @error('filter_student_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Parent Fee Name Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_parent_fee_name">Parent Fee Type:</label>
                            <select name="filter_parent_fee_name" id="filter_parent_fee_name" class="form-control select2 @error('filter_parent_fee_name') is-invalid @enderror">
                                @foreach($formFilterData['parentFeeNames'] as $name)
                                     <option value="{{ $name }}" {{ (old('filter_parent_fee_name', $validated['filter_parent_fee_name'] ?? '')) == $name ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('filter_parent_fee_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Fee Installment Title Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_fee_title">Fee Installment Title:</label>
                            <select name="filter_fee_title" id="filter_fee_title" class="form-control select2 @error('filter_fee_title') is-invalid @enderror">
                                @foreach($formFilterData['feeInstallmentTitles'] as $title)
                                    <option value="{{ $title }}" {{ (old('filter_fee_title', $validated['filter_fee_title'] ?? '')) == $title ? 'selected' : '' }}>{{ $title }}</option>
                                @endforeach
                            </select>
                             @error('filter_fee_title') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Invoice No Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_invoice_no">Invoice No.:</label>
                            <input type="text" name="filter_invoice_no" id="filter_invoice_no" class="form-control @error('filter_invoice_no') is-invalid @enderror" value="{{ old('filter_invoice_no', $validated['filter_invoice_no'] ?? '') }}" placeholder="Enter Invoice No.">
                            @error('filter_invoice_no') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Receipt No Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_receipt_no">Receipt No.:</label>
                            <input type="text" name="filter_receipt_no" id="filter_receipt_no" class="form-control @error('filter_receipt_no') is-invalid @enderror" value="{{ old('filter_receipt_no', $validated['filter_receipt_no'] ?? '') }}" placeholder="Enter Receipt No.">
                            @error('filter_receipt_no') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    {{-- Created By Staff Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_created_by_staff_id">Fee Created By:</label>
                            <select name="filter_created_by_staff_id" id="filter_created_by_staff_id" class="form-control select2 @error('filter_created_by_staff_id') is-invalid @enderror">
                                @foreach($formFilterData['staffUsers'] as $id => $name)
                                    <option value="{{ $id }}" {{ (old('filter_created_by_staff_id', $validated['filter_created_by_staff_id'] ?? '')) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('filter_created_by_staff_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Status Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_status">Status:</label>
                            <select name="filter_status" id="filter_status" class="form-control select2 @error('filter_status') is-invalid @enderror">
                                @foreach($formFilterData['statuses'] as $key => $value)
                                    <option value="{{ $key }}" {{ (old('filter_status', $validated['filter_status'] ?? '')) == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('filter_status') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Due Date Range --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_due_date_from">Due Date From:</label>
                            <input name="filter_due_date_from" id="filter_due_date_from" type="text" class="form-control date-picker @error('filter_due_date_from') is-invalid @enderror" value="{{ old('filter_due_date_from', $validated['filter_due_date_from'] ?? '') }}" placeholder="Select Date">
                            @error('filter_due_date_from') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_due_date_to">Due Date To:</label>
                            <input name="filter_due_date_to" id="filter_due_date_to" type="text" class="form-control date-picker @error('filter_due_date_to') is-invalid @enderror" value="{{ old('filter_due_date_to', $validated['filter_due_date_to'] ?? '') }}" placeholder="Select Date">
                            @error('filter_due_date_to') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Payment Date Range --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_payment_date_from">Payment Date From:</label>
                            <input name="filter_payment_date_from" id="filter_payment_date_from" type="text" class="form-control date-picker @error('filter_payment_date_from') is-invalid @enderror" value="{{ old('filter_payment_date_from', $validated['filter_payment_date_from'] ?? '') }}" placeholder="Select Date">
                            @error('filter_payment_date_from') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_payment_date_to">Payment Date To:</label>
                            <input name="filter_payment_date_to" id="filter_payment_date_to" type="text" class="form-control date-picker @error('filter_payment_date_to') is-invalid @enderror" value="{{ old('filter_payment_date_to', $validated['filter_payment_date_to'] ?? '') }}" placeholder="Select Date">
                            @error('filter_payment_date_to') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Generate Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Check if $groupedData is set and not empty. $groupedData comes from $reportResults['reportData'] --}}
    @if(isset($groupedData) && count($groupedData) > 0)
        {{-- 2. Group Summary Table Card --}}
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Report Summary (Grouped by: {{ $formFilterData['groupingOptions'][$groupBy] ?? 'N/A' }})</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>{{ $formFilterData['groupingOptions'][$groupBy] ?? 'Group' }}</th>
                        <th class="text-center">Fee Count</th>
                        <th class="text-right">Total Expected ({{ Qs::getSetting('currency_symbol', '$') }})</th>
                        <th class="text-right">Total Waived ({{ Qs::getSetting('currency_symbol', '$') }})</th>
                        <th class="text-right">Total Paid ({{ Qs::getSetting('currency_symbol', '$') }})</th>
                        <th class="text-right">Total Balance ({{ Qs::getSetting('currency_symbol', '$') }})</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($groupedData as $groupKey => $groupDetails)
                        <tr>
                            <td>{{ $groupDetails['groupTitle'] }}</td>
                            <td class="text-center">{{ number_format($groupDetails['totals']['record_count']) }}</td>
                            <td class="text-right">{{ Qs::formatCurrency($groupDetails['totals']['group_expected']) }}</td>
                            <td class="text-right">{{ Qs::formatCurrency($groupDetails['totals']['group_waived']) }}</td>
                            <td class="text-right">{{ Qs::formatCurrency($groupDetails['totals']['group_paid']) }}</td>
                            <td class="text-right font-weight-bold {{ $groupDetails['totals']['group_balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                {{ Qs::formatCurrency($groupDetails['totals']['group_balance']) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    @if(isset($overallTotals))
                    <tfoot>
                    <tr class="bg-gradient-secondary">
                        <th>Overall Totals:</th>
                        <th class="text-center">{{ number_format($overallTotals['fee_count']) }}</th>
                        <th class="text-right">{{ Qs::formatCurrency($overallTotals['total_expected']) }}</th>
                        <th class="text-right">{{ Qs::formatCurrency($overallTotals['total_waived']) }}</th>
                        <th class="text-right">{{ Qs::formatCurrency($overallTotals['total_paid']) }}</th>
                        <th class="text-right font-weight-bold {{ $overallTotals['total_balance'] > 0 ? 'text-danger' : 'text-success' }}">
                            {{ Qs::formatCurrency($overallTotals['total_balance']) }}
                        </th>
                    </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- 3. Detailed Fee Records Card --}}
        <div class="card card-purple"> 
            <div class="card-header">
                 <h3 class="card-title">Detailed Fee Records (Report Type: {{ $formFilterData['reportTypes'][$reportType] ?? 'N/A' }})</h3>
            </div>
            <div class="card-body">
                @if(isset($config) && isset($heads))
                    <table id="detailedFeesTable" class="table table-bordered table-striped table-hover display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                @foreach($heads as $header)
                                    <th class="{{ is_array($header) && isset($header['class']) ? $header['class'] : '' }}">
                                        {{ is_array($header) ? $header['label'] : $header }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            {{-- DataTables will populate this --}}
                        </tbody>
                         @if(isset($overallTotals) && $reportType !== 'summary') 
                            <tfoot>
                                <tr>
                                    {{-- Calculate colspan dynamically based on position of 'Expected Amt.' --}}
                                    @php
                                        $expectedColIndex = -1;
                                        foreach($heads as $index => $header) {
                                            if((is_array($header) ? $header['label'] : $header) === 'Expected Amt.') {
                                                $expectedColIndex = $index;
                                                break;
                                            }
                                        }
                                        $colspanBeforeTotals = $expectedColIndex > -1 ? $expectedColIndex : 7; // Default if not found
                                    @endphp
                                    <th colspan="{{ $colspanBeforeTotals }}" class="text-right">Overall Totals:</th>
                                    <th class="text-right">{{ Qs::formatCurrency($overallTotals['total_expected'] ?? 0) }}</th>
                                    <th class="text-right">{{ Qs::formatCurrency($overallTotals['total_paid'] ?? 0) }}</th>
                                    <th class="text-right">{{ Qs::formatCurrency($overallTotals['total_waived'] ?? 0) }}</th>
                                    <th class="text-right font-weight-bold {{ ($overallTotals['total_balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ Qs::formatCurrency($overallTotals['total_balance'] ?? 0) }}
                                    </th>
                                    {{-- Calculate colspan for remaining columns after Balance --}}
                                    @php
                                        $balanceColIndex = -1;
                                         foreach($heads as $index => $header) {
                                            if((is_array($header) ? $header['label'] : $header) === 'Balance') {
                                                $balanceColIndex = $index;
                                                break;
                                            }
                                        }
                                        $colspanAfterTotals = $balanceColIndex > -1 ? (count($heads) - ($balanceColIndex + 1)) : 0;
                                    @endphp
                                    @if($colspanAfterTotals > 0)
                                    <th colspan="{{ $colspanAfterTotals }}"></th>
                                    @endif
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                @else
                    <p class="text-muted">Detailed report configuration is not available.</p>
                @endif
            </div>
        </div>


        {{-- 4. Charts Card --}}
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Report Charts</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container" style="position: relative; height:40vh; width:100%"><canvas id="amountComparisonChart"></canvas></div>
                        <p class="text-center mt-2">Amount Expected vs Paid vs Balance by {{ $formFilterData['groupingOptions'][$groupBy] ?? 'Group' }}</p>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container" style="position: relative; height:40vh; width:100%"><canvas id="feeCountDistributionChart"></canvas></div>
                        <p class="text-center mt-2">Fee Count Distribution by {{ $formFilterData['groupingOptions'][$groupBy] ?? 'Group' }}</p>
                    </div>
                </div>
                <hr>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="chart-container" style="position: relative; height:40vh; width:100%"><canvas id="outstandingBalanceChart"></canvas></div>
                        <p class="text-center mt-2">Outstanding Balance Distribution by {{ $formFilterData['groupingOptions'][$groupBy] ?? 'Group' }}</p>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container" style="position: relative; height:40vh; width:100%"><canvas id="totalPaidChart"></canvas></div>
                        <p class="text-center mt-2">Total Paid Amount by {{ $formFilterData['groupingOptions'][$groupBy] ?? 'Group' }}</p>
                    </div>
                </div>
            </div>
        </div>
    @elseif(isset($validated) && request()->isMethod('post')) {{-- Only show "No data" if form was submitted --}}
        <div class="alert alert-warning" role="alert">
            No fee data found matching the selected criteria for the year {{ $syear ?? 'N/A' }}. Please try adjusting your filters.
        </div>
    @endif {{-- End check for $groupedData or $validated on POST --}}

@endsection

@push('scripts')
    {{-- DataTables --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

    {{-- Chart.js library --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Date Pickers & Select2 --}}
    <script src="{{ asset('vendor/moment/moment.min.js') }}"></script>
    <script src="{{ asset('vendor/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(function () {
            // Initialize Select2 Elements
            $('.select2').select2({ theme: 'bootstrap4' });

            // Initialize Tempus Dominus Date Pickers
            $('.date-picker').datetimepicker({
                format: 'YYYY-MM-DD', useCurrent: false,
                icons: {
                    time: 'far fa-clock', date: 'far fa-calendar-alt',
                    up: 'fas fa-chevron-up', down: 'fas fa-chevron-down',
                    previous: 'fas fa-chevron-left', next: 'fas fa-chevron-right',
                    today: 'far fa-calendar-check', clear: 'far fa-trash-alt', close: 'fas fa-times'
                }
            });

            @if(isset($config) && isset($heads) && isset($groupedData) && count($groupedData) > 0)
                // --- Initialize DataTables for Detailed Fee Records ---
                const dtConfig = @json($config);
                
                if (!dtConfig.columns || dtConfig.columns.length === 0) {
                    dtConfig.columns = @json($heads).map(header => {
                        let colDef = { title: (typeof header === 'string' ? header : header.label) };
                        if (typeof header === 'object' && header.class) {
                            colDef.className = header.class;
                        }
                         if (typeof header === 'object' && typeof header.orderable !== 'undefined') {
                            colDef.orderable = header.orderable;
                        }
                        if ((typeof header === 'string' ? header : header.label) === 'Payment Details') {
                            colDef.orderable = false;
                        }
                        return colDef;
                    });
                }
                if (!dtConfig.buttons) {
                    dtConfig.buttons = [
                        { extend: 'copy', className: 'btn-sm btn-secondary', text: '<i class="fas fa-copy"></i> Copy' },
                        { extend: 'csv', className: 'btn-sm btn-secondary', text: '<i class="fas fa-file-csv"></i> CSV' },
                        { extend: 'excel', className: 'btn-sm btn-secondary', text: '<i class="fas fa-file-excel"></i> Excel' },
                        { extend: 'pdf', className: 'btn-sm btn-secondary', text: '<i class="fas fa-file-pdf"></i> PDF', orientation: 'landscape', pageSize: 'LEGAL' },
                        { extend: 'print', className: 'btn-sm btn-secondary', text: '<i class="fas fa-print"></i> Print' },
                        { extend: 'colvis', className: 'btn-sm btn-secondary', text: 'Columns' }
                    ];
                }
                if (!dtConfig.dom) {
                     dtConfig.dom =  "<'row'<'col-sm-12 col-md-auto'l><'col-sm-12 col-md-auto'B><'col-sm-12 col-md'f>>" +
                                   "<'row'<'col-sm-12'tr>>" +
                                   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>";
                }
                // Default sort by student name (index 0) if not otherwise specified by service
                if(!dtConfig.order || dtConfig.order.length === 0) {
                    dtConfig.order = [[0, 'asc']]; 
                }


                $('#detailedFeesTable').DataTable(dtConfig);


                // --- Charting Logic ---
                const groupedReportData = Object.values(@json($groupedData)); 
                const groupingLabel = @json($formFilterData['groupingOptions'][$groupBy] ?? 'Group');
                const currencySymbol = @json(Qs::getSetting('currency_symbol', '$'));

                const labels = groupedReportData.map(group => group.groupTitle);
                const groupExpectedAmounts = groupedReportData.map(group => parseFloat(group.totals.group_expected) || 0);
                const groupPaidAmounts = groupedReportData.map(group => parseFloat(group.totals.group_paid) || 0);
                const groupBalanceAmounts = groupedReportData.map(group => parseFloat(group.totals.group_balance) || 0);
                const groupFeeCounts = groupedReportData.map(group => parseInt(group.totals.record_count) || 0);

                const getRandomColor = () => `rgb(${Math.floor(Math.random()*200)}, ${Math.floor(Math.random()*200)}, ${Math.floor(Math.random()*200)})`;
                const backgroundColors = labels.map(() => getRandomColor());
                const chartFontColor = $('body').hasClass('dark-mode') ? '#fff' : '#333';


                // Chart 1: Amount Comparison (Expected, Paid, Balance)
                const amountCtx = document.getElementById('amountComparisonChart').getContext('2d');
                new Chart(amountCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: `Expected (${currencySymbol})`, data: groupExpectedAmounts, backgroundColor: 'rgba(54, 162, 235, 0.7)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1 },
                            { label: `Paid (${currencySymbol})`, data: groupPaidAmounts, backgroundColor: 'rgba(75, 192, 192, 0.7)', borderColor: 'rgba(75, 192, 192, 1)', borderWidth: 1 },
                            { label: `Balance (${currencySymbol})`, data: groupBalanceAmounts, backgroundColor: 'rgba(255, 99, 132, 0.7)', borderColor: 'rgba(255, 99, 132, 1)', borderWidth: 1 }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false, color: chartFontColor, scales: { y: { beginAtZero: true, ticks: { color: chartFontColor, callback: value => currencySymbol + value.toLocaleString() }}}, plugins: { title: { display: true, text: `Fee Amounts by ${groupingLabel}`, color: chartFontColor}, legend: {labels: {color: chartFontColor}}, tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${currencySymbol}${ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`}}}}
                });

                // Chart 2: Fee Count Distribution
                const feeCountCtx = document.getElementById('feeCountDistributionChart').getContext('2d');
                new Chart(feeCountCtx, {
                    type: 'pie',
                    data: { labels: labels, datasets: [{ label: 'Fee Count', data: groupFeeCounts, backgroundColor: backgroundColors, hoverOffset: 4 }] },
                    options: { responsive: true, maintainAspectRatio: false, color: chartFontColor, plugins: { title: { display: true, text: `Fee Count Distribution by ${groupingLabel}`, color: chartFontColor}, legend: {labels: {color: chartFontColor}}, tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.parsed.toLocaleString()} (${(ctx.parsed / ctx.dataset.data.reduce((a,b)=>a+b,0) * 100).toFixed(1)}%)` }}}}
                });

                // Chart 3: Outstanding Balance Distribution (Doughnut)
                const outstandingCtx = document.getElementById('outstandingBalanceChart').getContext('2d');
                const positiveBalanceLabels = [];
                const positiveBalanceData = [];
                const positiveBalanceColors = [];
                groupedReportData.forEach((group, index) => {
                    const balance = parseFloat(group.totals.group_balance) || 0;
                    if (balance > 0) {
                        positiveBalanceLabels.push(group.groupTitle);
                        positiveBalanceData.push(balance);
                        positiveBalanceColors.push(backgroundColors[index % backgroundColors.length]);
                    }
                });
                if (positiveBalanceData.length > 0) {
                    new Chart(outstandingCtx, {
                        type: 'doughnut',
                        data: { labels: positiveBalanceLabels, datasets: [{ label: `Outstanding Balance (${currencySymbol})`, data: positiveBalanceData, backgroundColor: positiveBalanceColors, hoverOffset: 4 }] },
                        options: { responsive: true, maintainAspectRatio: false, color: chartFontColor, plugins: { title: { display: true, text: `Outstanding Balance Distribution by ${groupingLabel}`, color: chartFontColor}, legend: {labels: {color: chartFontColor}}, tooltip: { callbacks: { label: ctx => `${ctx.label}: ${currencySymbol}${ctx.parsed.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})} (${(ctx.parsed / ctx.dataset.data.reduce((a,b)=>a+b,0) * 100).toFixed(1)}%)` }}}}
                    });
                } else {
                    outstandingCtx.canvas.parentNode.innerHTML = '<p class="text-center text-muted">No outstanding balances to display.</p>';
                }

                // Chart 4: Total Paid Amount (Horizontal Bar)
                const paidCtx = document.getElementById('totalPaidChart').getContext('2d');
                new Chart(paidCtx, {
                    type: 'bar',
                    data: { labels: labels, datasets: [{ label: `Total Paid (${currencySymbol})`, data: groupPaidAmounts, backgroundColor: 'rgba(153, 102, 255, 0.7)', borderColor: 'rgba(153, 102, 255, 1)', borderWidth: 1 }] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, color: chartFontColor, scales: { x: { beginAtZero: true, ticks: { color: chartFontColor, callback: value => currencySymbol + value.toLocaleString() }}}, plugins: { title: { display: true, text: `Total Paid by ${groupingLabel}`, color: chartFontColor}, legend: {labels: {color: chartFontColor}}, tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${currencySymbol}${ctx.parsed.x.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`}}}}
                });
            @endif
        });
    </script>
@endpush

@push('styles')
    {{-- DataTables --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

    {{-- Date Pickers & Select2 --}}
    <link rel="stylesheet" href="{{ asset('vendor/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <style>
        .chart-container { margin-bottom: 20px; }
        .dt-buttons .btn { margin-right: 5px; }
        .table th, .table td { white-space: nowrap; /* Prevent text wrapping in table cells */ }
    </style>
@endp