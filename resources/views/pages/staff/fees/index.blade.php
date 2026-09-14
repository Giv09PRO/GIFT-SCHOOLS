@php use App\Helpers\Qs; @endphp
@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Manage Fee Installments')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        {{-- Placeholder for AdminLTE Alerts --}}
        <div id="alert-container" class="mb-3"></div>

        {{-- Filter Card --}}
        <div class="card card-outline card-primary mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Fee Installments</h3>
    <div>
        {{-- Link to Manage Fee Definitions page --}}
        <a href="{{ route('staff.fees.definitions.index') }}" class="btn btn-sm btn-secondary me-2">
            <i class="fas fa-cogs me-1"></i> Manage Fee Definitions
        </a>
        {{-- Link to Assign Fee Structure page --}}
        <a href="{{ route('staff.fees.structure.assign_create') }}" class="btn btn-sm btn-info me-2">
            <i class="fas fa-sitemap me-1"></i> Assign Fee Structure
        </a>
        <a href="{{ route('staff.fees.assign_bulk.form') }}" class="btn btn-sm btn-success me-2">
            <i class="fas fa-users me-1"></i> Assign Single Fee (Bulk)
        </a>
        <a href="{{ route('staff.fees.create') }}"
           class="btn btn-sm btn-primary me-2"> {{-- Assuming a route for creating individual fees --}}
            <i class="fas fa-plus me-1"></i> Create Single Fee
        </a>
        {{-- New Button for Allocating Unlinked Payments --}}
        <a href="{{ route('staff.payments.allocate_unlinked.form') }}" class="btn btn-sm btn-warning">
            <i class="fas fa-link me-1"></i> Allocate Unlinked Payments
        </a>
    </div>
</div>
            <div class="card-body">
                <form method="GET" action="{{ route('staff.fees.index') }}" id="filterForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="student_search" class="form-label">Student Name/ID</label>
                            <input type="text" name="student_search" id="student_search"
                                   class="form-control form-control-sm"
                                   value="{{ request('student_search') }}" placeholder="Enter name or ID (e.g. P001)">
                        </div>
                        <div class="col-md-4">
                            <label for="title" class="form-label">Installment Title</label>
                            <input type="text" name="title" id="title" class="form-control form-control-sm"
                                   value="{{ request('title') }}" placeholder="e.g., Term 1 Fees">
                        </div>
                        <div class="col-md-4">
                            <label for="invoice_no" class="form-label">Invoice Number</label>
                            <input type="text" name="invoice_no" id="invoice_no" class="form-control form-control-sm"
                                   value="{{ request('invoice_no') }}" placeholder="Enter invoice number">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Assigned From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control form-control-sm"
                                   value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Assigned To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control form-control-sm"
                                   value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select name="payment_status" id="payment_status"
                                    class="form-control form-control-sm select2">
                                @foreach ($paymentStatuses ?? ['' => 'All Payment Statuses', 'paid' => 'Balance Zero (Paid/Waived)', 'unpaid' => 'Outstanding Balance'] as $value => $label)
                                    <option
                                        value="{{ $value }}" {{ request('payment_status') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Installment Status</label>
                            <select name="status" id="status" class="form-control form-control-sm select2">
                                @foreach ($feeStatuses ?? ['' => 'All Fee Statuses', 'active' => 'Active (Outstanding)', 'waived' => 'Any Waiver Applied', 'fully_waived' => 'Fully Waived (Balance Zero)'] as $value => $label)
                                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="balance_above" class="form-label">Balance Greater Than</label>
                            <input type="number" name="balance_above" id="balance_above"
                                   class="form-control form-control-sm"
                                   value="{{ request('balance_above') }}" placeholder="e.g., 100.00" step="0.01">
                        </div>
                        <div class="col-md-3">
                            <label for="balance_below" class="form-label">Balance Less Than</label>
                            <input type="number" name="balance_below" id="balance_below"
                                   class="form-control form-control-sm"
                                   value="{{ request('balance_below') }}" placeholder="e.g., 500.00" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label for="parent_fee_name" class="form-label">Parent Fee Name</label>
                            <input type="text" name="parent_fee_name" id="parent_fee_name"
                                   class="form-control form-control-sm"
                                   value="{{ request('parent_fee_name') }}" placeholder="Search by parent fee name">
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary btn-sm me-2"><i class="fas fa-search me-1"></i>Filter
                            </button>
                            <a href="{{ route('staff.fees.index') }}" class="btn btn-secondary btn-sm me-2"><i
                                    class="fas fa-times me-1"></i>Clear Filters</a>
                            <button type="button" id="printFilteredNamesBtn" class="btn btn-info btn-sm"><i
                                    class="fas fa-print me-1"></i>Print Filtered Names
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results Card using AdminLTE Datatable --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Fee Installment List</h3>
            </div>
            <div class="table-responsive">
                <div class="card-body">
                    {{-- Ensure $heads and $config are passed from the controller --}}
                    <x-adminlte-datatable id="feesTable" :heads="$heads ?? []" :config="$config ?? []" striped hoverable
                                          responsive bordered compressed/>
                </div>
            </div>
        </div>
    </div>

    {{-- Waive Fee Modal (Bootstrap 4 Markup) --}}
    <div class="modal fade" id="waiveFeeModal" tabindex="-1" role="dialog" aria-labelledby="waiveFeeModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="waiveFeeModalLabel">Waive Fee Amount</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="waiveFeeForm" method="POST" action=""> {{-- Action will be set by JavaScript --}}
                    @csrf
                    <div class="modal-body">
                        <p>Waiving fee: <strong id="modalWaiveFeeTitle"></strong><br>For student: <strong
                                id="modalWaiveStudentName"></strong>.</p>
                        <p>Current Outstanding Balance: <strong id="modalWaiveFeeBalance" class="text-danger"></strong>
                        </p>
                        <hr>
                        <div class="form-group mb-3">
                            <label for="waive_amount_input" class="form-label">Amount to Waive</label>
                            <input type="number" class="form-control form-control-sm" id="waive_amount_input"
                                   name="waive_amount" step="0.01" placeholder="Enter amount (e.g., 50.00)">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="waive_full_balance_confirmation"
                                   name="waive_full_balance_confirmation" value="true">
                            <label class="form-check-label" for="waive_full_balance_confirmation">
                                Waive Full Remaining Balance (<span id="waiveFullBalanceAmount"></span>)
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            If "Amount to Waive" is empty and "Waive Full Remaining Balance" is checked, the entire
                            outstanding balance will be waived.
                            If an amount is entered, only that amount will be waived (it cannot exceed the current
                            balance).
                        </small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-strikethrough me-1"></i>Confirm
                            Waive
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Adjust Waiver Modal (Bootstrap 4 Markup) --}}
    <div class="modal fade" id="adjustWaiverModal" tabindex="-1" role="dialog" aria-labelledby="adjustWaiverModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adjustWaiverModalLabel">Adjust Waived Amount</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="adjustWaiverForm" method="POST" action=""> {{-- Action will be set by JavaScript --}}
                    @csrf
                    <div class="modal-body">
                        <p>Adjusting waiver for fee: <strong id="modalAdjustFeeTitle"></strong><br>
                            For student: <strong id="modalAdjustStudentName"></strong>.</p>
                        <hr>
                        <div class="row mb-2">
                            <div class="col-sm-6">Original Fee Amount:</div>
                            <div class="col-sm-6 text-right"><strong id="modalAdjustOriginalFeeAmount"></strong></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-6">Total Paid:</div>
                            <div class="col-sm-6 text-right"><strong id="modalAdjustTotalPaid"></strong></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-6">Current Waived Amount:</div>
                            <div class="col-sm-6 text-right"><strong id="modalAdjustCurrentWaived"
                                                                     class="text-info"></strong></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">Current Balance:</div>
                            <div class="col-sm-6 text-right"><strong id="modalAdjustFeeBalance"
                                                                     class="text-danger"></strong></div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="new_waived_amount_input" class="form-label">New Waived Amount</label>
                            <input type="number" class="form-control form-control-sm" id="new_waived_amount_input"
                                   name="new_waived_amount" step="0.01" min="0"
                                   placeholder="Enter new total waived amount (e.g., 25.00 or 0)">
                            <small class="form-text text-muted">
                                Enter the desired total waived amount. To remove the waiver completely, enter 0.
                                The system will prevent setting a waiver that results in a negative balance considering
                                direct payments.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-purple btn-sm"><i class="fas fa-sliders-h me-1"></i>Confirm
                            Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .form-label {
            margin-bottom: 0.3rem;
            font-weight: 500;
        }

        .card-header.d-flex > div {
            margin-left: auto; /* Pushes buttons to the right */
        }

        /* Ensure Select2 dropdown is above Bootstrap 4 modal (default z-index 1050) */
        .select2-container--bootstrap4 .select2-dropdown {
            z-index: 1060;
        }

        .dt-buttons .btn {
            margin-right: 0.25rem;
        }

        .modal-body {
            max-height: 75vh; /* Increased slightly */
            overflow-y: auto;
        }

        .btn-purple {
            color: #fff;
            background-color: #6f42c1; /* Bootstrap purple */
            border-color: #6f42c1;
        }

        .btn-purple:hover {
            color: #fff;
            background-color: #5a32a3;
            border-color: #532f91;
        }

        .actions-column { /* Class for actions column if needed for specific styling */
            white-space: nowrap;
        }

        .text-right {
            text-align: right;
        }

        /* Styles for AdminLTE-like alerts */
        #alert-container .alert {
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }

        #alert-container .alert.fade-out {
            opacity: 0;
        }
    </style>
@stop

@section('js')
    <script>
        // Helper to format currency
        function formatCurrency(amount) {
            const formatter = new Intl.NumberFormat('en-US', { // Adjust locale as needed
                style: 'currency',
                currency: '{{ Qs::getSetting("currency_code", "TZS") }}', // Use system currency setting or fallback
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            return formatter.format(amount);
        }

        // Helper function to show AdminLTE-like alerts
        function showAdminLTEAlert(type, message, title = null, autoDismissDelay = 5000) {
            const alertContainer = $('#alert-container');
            alertContainer.empty(); // Clear previous alerts

            let alertClass = '';
            let iconClass = '';
            let alertTitle = title;

            switch (type) {
                case 'success':
                    alertClass = 'alert-success';
                    iconClass = 'fa-check';
                    alertTitle = title || 'Success!';
                    break;
                case 'error':
                    alertClass = 'alert-danger';
                    iconClass = 'fa-ban';
                    alertTitle = title || 'Error!';
                    break;
                case 'warning':
                    alertClass = 'alert-warning';
                    iconClass = 'fa-exclamation-triangle';
                    alertTitle = title || 'Warning!';
                    break;
                case 'info':
                default:
                    alertClass = 'alert-info';
                    iconClass = 'fa-info';
                    alertTitle = title || 'Info';
                    break;
            }

            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas ${iconClass}"></i> ${alertTitle}</h5>
                    ${message}
                </div>
            `;
            const alertElement = $(alertHtml).appendTo(alertContainer);

            // Auto-dismiss
            if (autoDismissDelay > 0) {
                setTimeout(function () {
                    alertElement.addClass('fade-out');
                    setTimeout(function () {
                        alertElement.remove();
                    }, 500); // Match CSS transition time
                }, autoDismissDelay);
            }
        }


        $(document).ready(function () {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: $(this).data('placeholder') || 'Select an option',
                allowClear: Boolean($(this).data('allow-clear'))
            });

            // Handle Waive Fee Modal Trigger
            $(document).on('click', '.open-waive-modal', function () {
                var feeId = $(this).data('fee-id');
                var feeTitle = $(this).data('fee-title');
                var studentName = $(this).data('student-name');
                var feeBalance = parseFloat($(this).data('fee-balance'));

                $('#modalWaiveFeeTitle').text(feeTitle || 'N/A');
                $('#modalWaiveStudentName').text(studentName || 'N/A');
                $('#modalWaiveFeeBalance').text(formatCurrency(feeBalance));
                $('#waiveFullBalanceAmount').text(formatCurrency(feeBalance));
                $('#waive_amount_input').val('').attr('max', feeBalance.toFixed(2)).prop('disabled', false);
                $('#waive_full_balance_confirmation').prop('checked', false).prop('disabled', false);
                $('#waiveFeeForm').attr('action', "{{ url('staff/fees') }}/" + feeId + "/waive");
                $('#waiveFeeModal').modal('show');
            });

            $('#waive_full_balance_confirmation').on('change', function () {
                $('#waive_amount_input').prop('disabled', $(this).is(':checked')).val($(this).is(':checked') ? '' : $('#waive_amount_input').val());
            });

            $('#waive_amount_input').on('input', function () {
                $('#waive_full_balance_confirmation').prop('disabled', $(this).val() !== '' && parseFloat($(this).val()) > 0)
                    .prop('checked', false);
            });

            // Handle Adjust Waiver Modal Trigger
            $(document).on('click', '.open-adjust-waiver-modal', function () {
                var feeId = $(this).data('fee-id');
                var feeTitle = $(this).data('fee-title');
                var studentName = $(this).data('student-name');
                var currentWaivedAmount = parseFloat($(this).data('current-waived-amount'));
                var feeAmount = parseFloat($(this).data('fee-amount'));
                var totalPaid = parseFloat($(this).data('total-paid'));
                var feeBalance = parseFloat($(this).data('fee-balance'));

                $('#modalAdjustFeeTitle').text(feeTitle || 'N/A');
                $('#modalAdjustStudentName').text(studentName || 'N/A');
                $('#modalAdjustOriginalFeeAmount').text(formatCurrency(feeAmount));
                $('#modalAdjustTotalPaid').text(formatCurrency(totalPaid));
                $('#modalAdjustCurrentWaived').text(formatCurrency(currentWaivedAmount));
                $('#modalAdjustFeeBalance').text(formatCurrency(feeBalance));
                $('#new_waived_amount_input').val(currentWaivedAmount.toFixed(2));
                $('#adjustWaiverForm').attr('action', "{{ url('staff/fees') }}/" + feeId + "/adjust-waiver");
                $('#adjustWaiverModal').modal('show');
            });

            // Print Filtered Names Button
            const printButton = document.getElementById('printFilteredNamesBtn');
            if (printButton) {
                printButton.addEventListener('click', function () {
                    const printUrlBase = "{{ route('staff.fees.print_filtered_names') }}";
                    const params = new URLSearchParams();
                    const appendParam = (key, elementId) => {
                        const element = document.getElementById(elementId);
                        if (element && element.value) params.append(key, element.value);
                    };
                    ['student_search', 'title', 'invoice_no', 'parent_fee_name', 'date_from', 'date_to', 'payment_status', 'status', 'balance_above', 'balance_below'].forEach(id => appendParam(id, id));
                    const fullPrintUrl = params.toString() ? `${printUrlBase}?${params.toString()}` : printUrlBase;
                    var newTab = window.open(fullPrintUrl, '_blank');
                    if (newTab) newTab.focus(); else showAdminLTEAlert('warning', 'Please allow pop-ups for this site to print the report.');
                });
            }

            // AJAX form submission for Waive Fee Modal
            $('#waiveFeeForm').on('submit', function (e) {
                e.preventDefault();
                var form = $(this);
                var url = form.attr('action');
                var formData = form.serialize();

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    dataType: 'json',
                    success: function (response) {
                        $('#waiveFeeModal').modal('hide');
                        showAdminLTEAlert('success', response.message || 'Waiver applied successfully!');
                        if (typeof $('#feesTable').DataTable === 'function' && $.fn.DataTable.isDataTable('#feesTable')) {
                            $('#feesTable').DataTable().ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }
                    },
                    error: function (xhr) {
                        var errors = xhr.responseJSON;
                        var errorMessage = "An error occurred.";
                        if (errors) {
                            if (errors.message) errorMessage = errors.message;
                            if (errors.errors) {
                                $.each(errors.errors, function (key, value) {
                                    errorMessage += "<br>- " + value;
                                });
                            }
                        }
                        showAdminLTEAlert('error', errorMessage);
                    }
                });
            });

            // AJAX form submission for Adjust Waiver Modal
            $('#adjustWaiverForm').on('submit', function (e) {
                e.preventDefault();
                var form = $(this);
                var url = form.attr('action');
                var formData = form.serialize();

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    dataType: 'json',
                    success: function (response) {
                        $('#adjustWaiverModal').modal('hide');
                        showAdminLTEAlert('success', response.message || 'Waiver adjusted successfully!');
                        if (typeof $('#feesTable').DataTable === 'function' && $.fn.DataTable.isDataTable('#feesTable')) {
                            $('#feesTable').DataTable().ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }
                    },
                    error: function (xhr) {
                        var errors = xhr.responseJSON;
                        var errorMessage = "An error occurred during waiver adjustment.";
                        if (errors) {
                            if (errors.message) errorMessage = errors.message;
                            if (errors.errors) {
                                $.each(errors.errors, function (key, value) {
                                    errorMessage += "<br>- " + value;
                                });
                            }
                        }
                        showAdminLTEAlert('error', errorMessage);
                    }
                });
            });

            console.log('Fee installment index: JS loaded. Modals, print logic, and AJAX submissions initialized with AdminLTE alerts.');
        });
    </script>
@stop
