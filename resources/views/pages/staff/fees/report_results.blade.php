@extends('layouts.app')

@section('title', 'Fee Report Results')
@php use App\Helpers\Qs; @endphp
@section('content')

    {{-- Session Messages and Validation Errors --}}
    <div class="row">
        <div class="col-md-12">
            {{-- Display Standard Validation Errors --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Alert!</h5>
                    <span>Please check the form below for errors:</span>
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

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Fee Payment Report Results</h5>
            <a href="{{ route('staff.fees.report.form') }}" class="btn btn-sm btn-secondary"> <i class="fas fa-arrow-left"></i> Back to Report Form</a>
        </div>

        <div class="card-body">
            {{-- Filter Form --}}
            <form method="POST" action="{{ route('staff.fees.report.generate') }}" class="mb-4 p-3 border rounded bg-light">
                @csrf
                <div class="row">
                    {{-- Report Year --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="report_syear">Report Year <span class="text-danger">*</span></label>
                            <select name="report_syear" id="report_syear" class="form-control select2 @error('report_syear') is-invalid @enderror" required>
                                @foreach($formFilterData['years'] as $year_option)
                                    <option value="{{ $year_option }}" {{ (old('report_syear', $syear ?? '')) == $year_option ? 'selected' : '' }}>{{ $year_option }}</option>
                                @endforeach
                            </select>
                            @error('report_syear') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Report Type --}}
                     <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="report_type">Report Type <span class="text-danger">*</span></label>
                            <select name="report_type" id="report_type" class="form-control select2 @error('report_type') is-invalid @enderror" required>
                                @foreach($formFilterData['reportTypes'] as $key => $value)
                                    <option value="{{ $key }}" {{ (old('report_type', $reportType ?? '')) == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                             @error('report_type') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Group By --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="group_by">Group By <span class="text-danger">*</span></label>
                            <select name="group_by" id="group_by" class="form-control select2 @error('group_by') is-invalid @enderror" required>
                                @foreach($formFilterData['groupingOptions'] as $key => $label)
                                    <option value="{{ $key }}" {{ (old('group_by', $groupBy ?? '')) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('group_by') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    {{-- School Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_school_id">Filter by School</label>
                            <select name="filter_school_id" id="filter_school_id" class="form-control select2 @error('filter_school_id') is-invalid @enderror">
                                {{-- Assuming $formFilterData['schools'] is ['' => 'All Schools'] + actual schools --}}
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
                            <label for="filter_grade_id">Filter by Grade</label>
                            <select name="filter_grade_id" id="filter_grade_id" class="form-control select2 @error('filter_grade_id') is-invalid @enderror">
                                {{-- Assuming $formFilterData['grades'] is ['' => 'All Grades'] + actual grades --}}
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
                                @foreach($formFilterData['parentFeeNames'] as $name_opt)
                                     <option value="{{ $name_opt }}" {{ (old('filter_parent_fee_name', $validated['filter_parent_fee_name'] ?? '')) == $name_opt ? 'selected' : '' }}>{{ $name_opt }}</option>
                                @endforeach
                            </select>
                            @error('filter_parent_fee_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Fee Installment Title Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_fee_title">Fee Installment Title</label>
                            <select name="filter_fee_title" id="filter_fee_title" class="form-control select2 @error('filter_fee_title') is-invalid @enderror">
                                @foreach($formFilterData['feeInstallmentTitles'] as $title_opt)
                                    <option value="{{ $title_opt }}" {{ (old('filter_fee_title', $validated['filter_fee_title'] ?? '')) == $title_opt ? 'selected' : '' }}>{{ $title_opt }}</option>
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
                                @foreach($formFilterData['staffUsers'] as $id => $name_staff)
                                    <option value="{{ $id }}" {{ (old('filter_created_by_staff_id', $validated['filter_created_by_staff_id'] ?? '')) == $id ? 'selected' : '' }}>{{ $name_staff }}</option>
                                @endforeach
                            </select>
                            @error('filter_created_by_staff_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    {{-- Status Filter --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_status">Filter by Status</label>
                            <select name="filter_status" id="filter_status" class="form-control select2 @error('filter_status') is-invalid @enderror">
                                @foreach($formFilterData['statuses'] as $key => $label)
                                    <option value="{{ $key }}" {{ (old('filter_status', $validated['filter_status'] ?? '')) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('filter_status') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Due Date From --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_due_date_from">Due Date From</label>
                            <input type="text" class="form-control date-picker @error('filter_due_date_from') is-invalid @enderror" id="filter_due_date_from" name="filter_due_date_from" value="{{ old('filter_due_date_from', $validated['filter_due_date_from'] ?? '') }}" placeholder="YYYY-MM-DD" autocomplete="off">
                             @error('filter_due_date_from') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    {{-- Due Date To --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_due_date_to">Due Date To</label>
                            <input type="text" class="form-control date-picker @error('filter_due_date_to') is-invalid @enderror" id="filter_due_date_to" name="filter_due_date_to" value="{{ old('filter_due_date_to', $validated['filter_due_date_to'] ?? '') }}" placeholder="YYYY-MM-DD" autocomplete="off">
                            @error('filter_due_date_to') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                     {{-- Payment Date From --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_payment_date_from">Payment Date From</label>
                            <input type="text" class="form-control date-picker @error('filter_payment_date_from') is-invalid @enderror" id="filter_payment_date_from" name="filter_payment_date_from" value="{{ old('filter_payment_date_from', $validated['filter_payment_date_from'] ?? '') }}" placeholder="YYYY-MM-DD" autocomplete="off">
                            @error('filter_payment_date_from') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    {{-- Payment Date To --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_payment_date_to">Payment Date To</label>
                            <input type="text" class="form-control date-picker @error('filter_payment_date_to') is-invalid @enderror" id="filter_payment_date_to" name="filter_payment_date_to" value="{{ old('filter_payment_date_to', $validated['filter_payment_date_to'] ?? '') }}" placeholder="YYYY-MM-DD" autocomplete="off">
                            @error('filter_payment_date_to') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                {{-- Submit Button --}}
                <div class="row">
                    <div class="col-12 text-right mt-3">
                        <button type="submit" class="btn btn-primary"> <i class="fas fa-filter"></i> Apply Filters / Regenerate</button>
                    </div>
                </div>
            </form>

            <hr>

            {{-- Display Overall Totals --}}
            @if(isset($overallTotals))
            <h5 class="mt-4">Overall Totals</h5>
            <div class="row mb-4">
                <div class="col-md-2dot4 col-sm-6 mb-2"> {{-- Using custom class for 5 columns or adjust col-md- usage --}}
                    <div class="alert alert-secondary" role="alert">
                        <strong>Gross Expected:</strong> {{ Qs::formatCurrency($overallTotals['total_expected_gross'] ?? 0) }}
                    </div>
                </div>
                <div class="col-md-2dot4 col-sm-6 mb-2">
                    <div class="alert alert-warning" role="alert">
                        <strong>Total Waived:</strong> {{ Qs::formatCurrency($overallTotals['total_waived_effective'] ?? 0) }}
                    </div>
                </div>
                <div class="col-md-2dot4 col-sm-6 mb-2">
                    <div class="alert alert-info" role="alert">
                        <strong>Net Expected:</strong> {{ Qs::formatCurrency($overallTotals['total_expected_net'] ?? 0) }}
                    </div>
                </div>
                <div class="col-md-2dot4 col-sm-6 mb-2">
                    <div class="alert alert-success" role="alert">
                        <strong>Total Paid:</strong> {{ Qs::formatCurrency($overallTotals['total_paid'] ?? 0) }}
                    </div>
                </div>
                <div class="col-md-2dot4 col-sm-6 mb-2">
                    <div class="alert alert-danger" role="alert">
                        <strong>Total Balance:</strong> {{ Qs::formatCurrency($overallTotals['total_balance'] ?? 0) }}
                    </div>
                </div>
            </div>
            @endif

            {{-- Display Data using AdminLTE Datatable --}}
            @if(isset($config) && isset($heads))
                <h5 class="mt-4">Detailed Report Data</h5>
                <p class="text-muted">Report Type: {{ $formFilterData['reportTypes'][$reportType] ?? 'N/A' }} | Grouped by: {{ $formFilterData['groupingOptions'][$groupBy] ?? 'N/A' }}</p>
                
                <x-adminlte-datatable id="feeReportTable" :heads="$heads" :config="$config" theme="bootstrap4" striped hoverable bordered compressed with-buttons/>
            @elseif(request()->isMethod('post')) 
                 <div class="alert alert-warning mt-4" role="alert">
                    No fee data found matching the selected criteria for the year {{ $syear ?? 'N/A' }}. Please try adjusting your filters.
                </div>
            @endif

        </div> {{-- End card-body --}}
    </div> {{-- End card --}}
@endsection

@push('scripts')
    {{-- Ensure jQuery, Bootstrap, Select2, Tempus Dominus, DataTables, and Buttons are loaded --}}
    {{-- These are often included in a master layout (layouts.app) or a dedicated assets file --}}

    {{-- Select2 JS --}}
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    {{-- Moment JS (dependency for Tempus Dominus) --}}
    <script src="{{ asset('vendor/moment/moment.min.js') }}"></script>
    {{-- Tempus Dominus Bootstrap 4 JS --}}
    <script src="{{ asset('vendor/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    
    {{-- DataTables Core JS --}}
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    {{-- DataTables Responsive Extension JS --}}
    <script src="{{ asset('vendor/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    {{-- DataTables Buttons Extension JS --}}
    <script src="{{ asset('vendor/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('vendor/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/buttons.colVis.min.js') }}"></script>

    <script>
        $(function () {
            // Initialize Select2 Elements
            $('.select2').select2({
                theme: 'bootstrap4' 
            });

            // Initialize DateTimePickers
            $('.date-picker').datetimepicker({
                format: 'YYYY-MM-DD', // Only date
                useCurrent: false, // Important to prevent auto-setting if field is empty
                icons: {
                    time: 'far fa-clock',
                    date: 'far fa-calendar-alt',
                    up: 'fas fa-chevron-up',
                    down: 'fas fa-chevron-down',
                    previous: 'fas fa-chevron-left',
                    next: 'fas fa-chevron-right',
                    today: 'far fa-calendar-check',
                    clear: 'far fa-trash-alt',
                    close: 'fas fa-times'
                }
            });
             // Clear datepicker fields if they were empty on load, to prevent auto-fill with today's date by some browsers/pickers
            $('.date-picker').each(function() {
                if ($(this).val() === '') {
                    $(this).datetimepicker('clear');
                }
            });
        });
    </script>
@endpush

@push('styles')
    {{-- Select2 CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    {{-- Tempus Dominus Bootstrap 4 CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    
    {{-- DataTables Core CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    {{-- DataTables Responsive Extension CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    {{-- DataTables Buttons Extension CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <style>
        .table th, .table td { white-space: nowrap; }

        /* Status Styling Classes */
        .status-badge {
            padding: 0.3em 0.6em;
            border-radius: 0.25rem;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-block;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
        }
        .status-paid {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .status-unpaid {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .status-partially-paid {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
        }
        .status-overdue {
            color: #721c24; /* Same as unpaid, but can be different */
            background-color: #f8d7da; /* Same as unpaid */
            border: 1px solid #f5c6cb;
            font-weight: bold;
        }
        .status-waived-fully {
            color: #0c5460;
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
        }
        .status-partially-waived {
            color: #464a4e;
            background-color: #e2e3e5;
            border: 1px solid #d6d8db;
        }
        .status-zero-expected {
            color: #383d41;
            background-color: #e2e3e5;
            border: 1px solid #d6d8db;
        }
        .status-default {
            color: #383d41;
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
        }

        /* For 5 columns in a row */
        .col-md-2dot4 {
            -ms-flex: 0 0 20%;
            flex: 0 0 20%;
            max-width: 20%;
            position: relative;
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }
    </style>
@endpush
