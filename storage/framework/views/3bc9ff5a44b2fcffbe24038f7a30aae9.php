

 

<?php
    // Variables passed from controller:
    // $unlinkedPayments (paginated collection of unlinked payments)
    // $syear (current school year)
    // Old input: $request->old()
?>


<?php $__env->startSection('title', 'Allocate Unlinked Payments'); ?>

<?php $__env->startPush('styles'); ?>
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
    <style>
        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(2.25rem + 2px) !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding: .375rem .75rem;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px) !important;
        }
        .select2-container {
            z-index: 9999 !important;
        }
        .payment-amount-display {
            font-size: 1.2em;
            font-weight: bold;
        }
        .fee-allocation-table th, .fee-allocation-table td {
            vertical-align: middle;
        }
        .allocation-summary strong {
            display: inline-block;
            width: 150px; /* Adjust as needed */
        }
    </style>
<?php $__env->stopPush(); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-9"> 

                <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> 

                
                <div class="card card-primary card-outline shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-search-dollar mr-1"></i> 1. Find and Select Unlinked Payment</h3>
                    </div>
                    <div class="card-body">
                        
                        <form method="GET" action="<?php echo e(route('staff.payments.allocate_unlinked.form')); ?>" class="mb-3 p-3 border rounded bg-light">
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label for="search_term_unlinked">Search (ID, Comment, Student)</label>
                                    <input type="text" name="search_term_unlinked" id="search_term_unlinked" class="form-control form-control-sm" value="<?php echo e(request('search_term_unlinked')); ?>" placeholder="Payment ID, comment, student name/ID">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="unlinked_date_from">Date From</label>
                                    <input type="date" name="unlinked_date_from" id="unlinked_date_from" class="form-control form-control-sm" value="<?php echo e(request('unlinked_date_from')); ?>">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="unlinked_date_to">Date To</label>
                                    <input type="date" name="unlinked_date_to" id="unlinked_date_to" class="form-control form-control-sm" value="<?php echo e(request('unlinked_date_to')); ?>">
                                </div>
                                <div class="col-md-2 form-group d-flex align-items-end">
                                    <button type="submit" class="btn btn-sm btn-info btn-block"><i class="fas fa-filter mr-1"></i> Filter</button>
                                </div>
                            </div>
                        </form>

                        <?php if($unlinkedPayments->isEmpty()): ?>
                            <div class="alert alert-info">No unlinked payments found matching your criteria.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Date</th>
                                            <th class="text-right">Amount</th>
                                            <th>Original Student</th>
                                            <th>Comments</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $unlinkedPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr id="payment_row_<?php echo e($payment->id); ?>">
                                            <td><?php echo e($payment->id); ?></td>
                                            <td><?php echo e($payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : 'N/A'); ?></td>
                                            <td class="text-right"><?php echo e(number_format($payment->amount, 2)); ?></td>
                                            <td>
                                                <?php if($payment->student): ?>
                                                    <?php echo e($payment->student->first_name); ?> <?php echo e($payment->student->last_name); ?>

                                                    <small class="d-block text-muted">ID: <?php echo e($payment->student->prem_number ?? 'N/A'); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo e(Str::limit($payment->comments, 50)); ?></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-xs btn-success btn-select-payment"
                                                        data-payment-id="<?php echo e($payment->id); ?>"
                                                        data-payment-amount="<?php echo e($payment->amount); ?>"
                                                        data-payment-date="<?php echo e($payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : ''); ?>"
                                                        data-payment-comments="<?php echo e(e($payment->comments)); ?>"
                                                        data-original-student-info="<?php echo e($payment->student ? e($payment->student->first_name . ' ' . $payment->student->last_name . ' (ID: ' . ($payment->student->prem_number ?? 'N/A') . ')') : 'None'); ?>">
                                                    <i class="fas fa-check-circle mr-1"></i> Select
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <?php echo e($unlinkedPayments->links()); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                
                <form method="POST" action="<?php echo e(route('staff.payments.allocate_unlinked.process')); ?>" id="allocate_unlinked_payment_form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="payment_id" id="selected_payment_id_hidden" value="<?php echo e(old('payment_id')); ?>">

                    
                    <div id="target_student_card" class="card card-info card-outline shadow-sm mb-4 d-none">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-check mr-1"></i> 2. Select Target Student for Payment #<span id="display_payment_id"></span> (<span id="display_payment_amount" class="payment-amount-display"></span>)</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="target_student_search_select">Search and Select Target Student <span class="text-danger">*</span></label>
                                <select id="target_student_search_select" name="target_student_id" class="form-control">
                                    
                                    <?php if(old('target_student_id')): ?>
                                        
                                    <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">Type to search by name or Permanent #.</small>
                                <?php $__errorArgs = ['target_student_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="d-block invalid-feedback" role="alert"><strong><?php echo e($message); ?></strong></span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>

                    
                    <div id="fee_allocation_card" class="card card-success card-outline shadow-sm mb-4 d-none">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tasks mr-1"></i> 3. Allocate Payment to Fees for: <strong id="display_target_student_name"></strong></h3>
                        </div>
                        <div class="card-body">
                            <div id="student_fees_container">
                                
                                <p class="text-center text-muted" id="loading_fees_placeholder">Loading student fees...</p>
                                <div id="fees_table_content" class="d-none">
                                     <div class="table-responsive">
                                        <table class="table table-sm table-bordered fee-allocation-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Fee Title</th>
                                                    <th class="text-right">Total Due</th>
                                                    <th class="text-right">Already Paid</th>
                                                    <th class="text-right">Balance</th>
                                                    <th class="text-center" style="width: 150px;">Allocate Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody id="student_fees_tbody">
                                                
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div id="no_outstanding_fees_message" class="alert alert-info d-none">
                                    This student has no outstanding fees for the current school year (<?php echo e($syear); ?>).
                                    The payment will be linked to the student but remain unallocated.
                                </div>
                                <?php $__errorArgs = ['allocations'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> 
                                    <div class="alert alert-danger mt-2 py-2"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <hr>
                            <div id="allocation_summary_section" class="mt-3 p-3 border rounded bg-light d-none">
                                <h4>Allocation Summary</h4>
                                <div class="allocation-summary">
                                    <p><strong>Payment Amount:</strong> <span id="summary_payment_amount">0.00</span></p>
                                    <p><strong>Total Allocated:</strong> <span id="summary_total_allocated" class="font-weight-bold">0.00</span></p>
                                    <p><strong>Remaining/Over:</strong> <span id="summary_remaining_amount">0.00</span></p>
                                </div>
                            </div>


                            <div class="form-group mt-3">
                                <label for="allocation_comment">Comments for this Allocation</label>
                                <textarea name="allocation_comment" id="allocation_comment" class="form-control <?php $__errorArgs = ['allocation_comment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                          rows="3" placeholder="Optional: Add comments about this specific allocation/linking process."><?php echo e(old('allocation_comment')); ?></textarea>
                                <?php $__errorArgs = ['allocation_comment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="invalid-feedback" role="alert"><strong><?php echo e($message); ?></strong></span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="text-center border-top pt-3 mt-4">
                                <button type="submit" class="btn btn-success btn-lg" id="submit_allocation_button" disabled>
                                    <i class="fas fa-save mr-1"></i> Process Allocation
                                </button>
                                <button type="button" class="btn btn-secondary ml-2" id="cancel_allocation_button">
                                    <i class="fas fa-times-circle mr-1"></i> Cancel / Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div id="initial_selection_placeholder" class="alert alert-info text-center">
                    Please select an unlinked payment from the list above to proceed.
                </div>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    
    
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        $(document).ready(function() {
            let selectedPayment = null;
            let selectedStudentId = null;
            let studentFeesData = []; // To store fee details for calculations

            // Initialize Select2 for target student search
            $('#target_student_search_select').select2({
                theme: 'bootstrap4',
                placeholder: 'Search by Name or Permanent #...',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: '<?php echo e(route("staff.students.search_json")); ?>', // Ensure this route exists and returns students
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { search_term: params.term, page: params.page || 1, syear: '<?php echo e($syear); ?>' };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: $.map(data.data, function (student) {
                                return {
                                    id: student.id,
                                    text: `${student.last_name}, ${student.first_name} ${student.middle_name || ''} (ID: ${student.prem_number || 'N/A'}) - ${student.grade_level || 'N/A'}`,
                                    full_student_data: student
                                }
                            }),
                            pagination: { more: (params.page * (data.per_page || 15)) < data.total }
                        };
                    },
                    cache: true
                }
            });

            // 1. Handle Unlinked Payment Selection
            $('.btn-select-payment').on('click', function() {
                selectedPayment = {
                    id: $(this).data('payment-id'),
                    amount: parseFloat($(this).data('payment-amount')),
                    date: $(this).data('payment-date'),
                    comments: $(this).data('payment-comments'),
                    originalStudent: $(this).data('original-student-info')
                };

                $('#selected_payment_id_hidden').val(selectedPayment.id);
                $('#display_payment_id').text(selectedPayment.id);
                $('#display_payment_amount').text(selectedPayment.amount.toFixed(2));
                $('#summary_payment_amount').text(selectedPayment.amount.toFixed(2));


                $('.payment-row-selected').removeClass('payment-row-selected table-info');
                $(this).closest('tr').addClass('payment-row-selected table-info');

                $('#target_student_card').removeClass('d-none');
                $('#initial_selection_placeholder').addClass('d-none');
                $('#fee_allocation_card').addClass('d-none'); // Hide allocation card if previously shown
                $('#student_fees_tbody').empty(); // Clear previous fees
                $('#target_student_search_select').val(null).trigger('change'); // Reset student search
                $('#submit_allocation_button').prop('disabled', true);
                updateAllocationSummary();

                // Scroll to target student card
                $('html, body').animate({ scrollTop: $("#target_student_card").offset().top - 70 }, 500);
            });

            // 2. Handle Target Student Selection
            $('#target_student_search_select').on('select2:select', function (e) {
                var data = e.params.data;
                if (data && data.id) {
                    selectedStudentId = data.id;
                    $('#display_target_student_name').text(data.text.split(' (ID:')[0]);
                    $('#fee_allocation_card').removeClass('d-none');
                    $('#loading_fees_placeholder').removeClass('d-none');
                    $('#fees_table_content').addClass('d-none');
                    $('#no_outstanding_fees_message').addClass('d-none');
                    $('#student_fees_tbody').empty();
                    $('#submit_allocation_button').prop('disabled', true);
                    fetchStudentFees(selectedStudentId);
                }
            });

            $('#target_student_search_select').on('select2:unselect', function (e) {
                selectedStudentId = null;
                $('#fee_allocation_card').addClass('d-none');
                $('#student_fees_tbody').empty();
                $('#submit_allocation_button').prop('disabled', true);
                updateAllocationSummary();
            });

            // 3. Fetch Student Fees
            function fetchStudentFees(studentId) {
                // IMPORTANT: Create this route: GET /staff/students/{student}/fees-json?syear=<?php echo e($syear); ?>

                // It should return JSON like: { fees: [ {id, title, amount, total_paid, balance, due_date}, ... ] }
                // Ensure Fee model has 'balance' accessor.
                const feesUrl = `<?php echo e(url('staff/students')); ?>/${studentId}/fees-json?syear=<?php echo e($syear); ?>`;

                $.ajax({
                    url: feesUrl,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#loading_fees_placeholder').addClass('d-none');
                        studentFeesData = response.fees || [];
                        populateFeesTable(studentFeesData);
                        updateAllocationSummary();
                        $('#submit_allocation_button').prop('disabled', studentFeesData.length === 0 && selectedPayment.amount > 0); // Enable if no fees but payment has amount (for linking)
                                                                                                                                    // Or enable if there are fees.
                        if (studentFeesData.length > 0 || selectedPayment.amount > 0.005) {
                             $('#submit_allocation_button').prop('disabled', false);
                        }

                    },
                    error: function(xhr) {
                        $('#loading_fees_placeholder').addClass('d-none');
                        $('#student_fees_tbody').html('<tr><td colspan="5" class="text-danger text-center">Error loading fees. Please try again.</td></tr>');
                        Swal.fire('Error', 'Could not load student fees. ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText) , 'error');
                    }
                });
            }

            function populateFeesTable(fees) {
                const tbody = $('#student_fees_tbody');
                tbody.empty();
                if (fees.length === 0) {
                    $('#no_outstanding_fees_message').removeClass('d-none');
                    $('#fees_table_content').addClass('d-none');
                    return;
                }

                $('#no_outstanding_fees_message').addClass('d-none');
                $('#fees_table_content').removeClass('d-none');
                $('#allocation_summary_section').removeClass('d-none');


                fees.forEach(function(fee) {
                    // Only add fees with a balance > 0.005
                    if (parseFloat(fee.balance) <= 0.005) {
                        return; // Skip this fee
                    }
                    let row = `<tr>
                        <td>${fee.title} <small class="d-block text-muted">Due: ${fee.due_date || 'N/A'}</small></td>
                        <td class="text-right">${parseFloat(fee.amount).toFixed(2)}</td>
                        <td class="text-right">${parseFloat(fee.total_paid).toFixed(2)}</td>
                        <td class="text-right font-weight-bold">${parseFloat(fee.balance).toFixed(2)}</td>
                        <td class="text-center">
                            <input type="number" name="allocations[${fee.id}]" class="form-control form-control-sm allocation-input"
                                   step="0.01" min="0" max="${parseFloat(fee.balance).toFixed(2)}"
                                   data-fee-id="${fee.id}" data-fee-balance="${parseFloat(fee.balance).toFixed(2)}"
                                   placeholder="0.00">
                            <div class="invalid-feedback"></div>
                        </td>
                    </tr>`;
                    tbody.append(row);
                });

                $('.allocation-input').on('input change', function() {
                    validateAllocationInput($(this));
                    updateAllocationSummary();
                });
            }

            function validateAllocationInput(inputElement) {
                let val = parseFloat(inputElement.val());
                const max = parseFloat(inputElement.attr('max'));
                inputElement.removeClass('is-invalid');
                inputElement.next('.invalid-feedback').text('');

                if (isNaN(val) || val < 0) {
                    inputElement.val(''); // Clear if invalid
                    // Optionally show error, but usually just clearing is fine for on-input
                    return;
                }
                if (val > max) {
                    inputElement.addClass('is-invalid');
                    inputElement.next('.invalid-feedback').text(`Max: ${max.toFixed(2)}`);
                    // inputElement.val(max.toFixed(2)); // Optionally cap at max
                }
            }

            function updateAllocationSummary() {
                if (!selectedPayment) return;

                let totalAllocated = 0;
                $('.allocation-input').each(function() {
                    let val = parseFloat($(this).val());
                    if (!isNaN(val) && val > 0) {
                        totalAllocated += val;
                    }
                });
                totalAllocated = parseFloat(totalAllocated.toFixed(2));

                $('#summary_total_allocated').text(totalAllocated.toFixed(2));
                const paymentAmount = selectedPayment.amount;
                const remaining = paymentAmount - totalAllocated;

                $('#summary_remaining_amount').text(remaining.toFixed(2));

                if (Math.abs(remaining) < 0.005 && totalAllocated > 0) { // Allow if fully allocated
                     $('#summary_remaining_amount').removeClass('text-danger text-warning').addClass('text-success');
                } else if (remaining < 0) { // Over-allocated
                    $('#summary_remaining_amount').removeClass('text-success text-warning').addClass('text-danger');
                } else if (totalAllocated > 0 && remaining > 0) { // Partially allocated
                     $('#summary_remaining_amount').removeClass('text-success text-danger').addClass('text-warning');
                } else { // Not allocated or fully unallocated
                     $('#summary_remaining_amount').removeClass('text-success text-danger text-warning');
                }

                // Enable/disable submit button based on whether allocations match payment amount (if any allocations made)
                // Or if no fees and payment has amount (just linking)
                const studentHasOutstandingFeesForAllocation = studentFeesData.some(fee => parseFloat(fee.balance) > 0.005);

                if (totalAllocated > 0) { // If user tried to allocate something
                    $('#submit_allocation_button').prop('disabled', Math.abs(remaining) > 0.005);
                } else if (paymentAmount > 0.005 && !studentHasOutstandingFeesForAllocation) {
                    // Allow submission if payment has amount and student has no fees (just linking)
                    $('#submit_allocation_button').prop('disabled', false);
                }
                 else { // No allocations made yet, and student might have fees
                    $('#submit_allocation_button').prop('disabled', true);
                }
            }


            // 4. Form Submission and Cancellation
            $('#allocate_unlinked_payment_form').on('submit', function(e) {
                if (!selectedPayment || !selectedStudentId) {
                    e.preventDefault();
                    Swal.fire('Missing Information', 'Please select an unlinked payment and a target student.', 'warning');
                    return;
                }

                let totalAllocated = 0;
                let allocationError = false;
                 $('.allocation-input').each(function() {
                    let inputElement = $(this);
                    let val = parseFloat(inputElement.val());
                    const max = parseFloat(inputElement.attr('max'));
                    if (!isNaN(val) && val > 0) {
                        totalAllocated += val;
                        if (val > max) {
                            inputElement.addClass('is-invalid');
                            inputElement.next('.invalid-feedback').text(`Amount exceeds fee balance of ${max.toFixed(2)}.`);
                            allocationError = true;
                        }
                    } else if (val < 0) {
                         inputElement.addClass('is-invalid');
                         inputElement.next('.invalid-feedback').text(`Amount cannot be negative.`);
                         allocationError = true;
                    }
                });
                totalAllocated = parseFloat(totalAllocated.toFixed(2));

                const paymentAmount = selectedPayment.amount;
                const studentHasOutstandingFeesForAllocation = studentFeesData.some(fee => parseFloat(fee.balance) > 0.005);

                if (totalAllocated > 0 && Math.abs(paymentAmount - totalAllocated) > 0.005) {
                     e.preventDefault();
                     Swal.fire('Allocation Mismatch', `Total allocated amount (${totalAllocated.toFixed(2)}) must match the payment amount (${paymentAmount.toFixed(2)}). Please adjust.`, 'error');
                     return;
                }
                if (totalAllocated === 0 && paymentAmount > 0.005 && studentHasOutstandingFeesForAllocation) {
                    e.preventDefault();
                    Swal.fire('No Allocations Made', 'This payment has an amount to allocate and the student has outstanding fees. Please specify allocations or confirm if you intend to only link the payment without allocating.', 'warning', {
                        showCancelButton: true,
                        confirmButtonText: 'Proceed (Link Only)',
                        cancelButtonText: 'Adjust Allocations'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // User confirmed to proceed without allocations
                            // Clear any potentially invalid allocation inputs before submitting
                            $('.allocation-input').val('');
                            $(this).off('submit').submit(); // Resubmit form
                        }
                    });
                    return; // Stop initial submission
                }


                if (allocationError) {
                    e.preventDefault();
                    Swal.fire('Invalid Allocations', 'One or more allocated amounts are invalid. Please check the highlighted fields.', 'error');
                    return;
                }

                // If all checks pass, show processing
                Swal.fire({
                    title: 'Processing Allocation...',
                    text: 'Please wait.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            });

            $('#cancel_allocation_button').on('click', function() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will reset the current allocation process. Selected payment and student will be cleared.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Reset!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        selectedPayment = null;
                        selectedStudentId = null;
                        studentFeesData = [];
                        $('#selected_payment_id_hidden').val('');
                        $('#target_student_search_select').val(null).trigger('change');
                        $('#student_fees_tbody').empty();
                        $('#allocation_comment').val('');

                        $('#target_student_card').addClass('d-none');
                        $('#fee_allocation_card').addClass('d-none');
                        $('#initial_selection_placeholder').removeClass('d-none');
                        $('.payment-row-selected').removeClass('payment-row-selected table-info');
                        $('#submit_allocation_button').prop('disabled', true);
                        updateAllocationSummary();
                         $('html, body').animate({ scrollTop: 0 }, 500);
                    }
                });
            });

            // Handle old input if validation fails on backend
            <?php if(old('payment_id') && old('target_student_id')): ?>
                // Simulate selection to re-populate form state
                // This is a simplified version. A robust solution might involve passing more data back.
                const oldPaymentId = <?php echo e(old('payment_id')); ?>;
                const oldPaymentButton = $(`.btn-select-payment[data-payment-id="${oldPaymentId}"]`);
                if (oldPaymentButton.length) {
                    oldPaymentButton.trigger('click'); // Re-select payment

                    // If target student data was passed back (e.g. $targetStudent variable)
                    // you would re-initialize the Select2 for target student and fetch fees.
                    // This part is complex to fully restore without more context from controller on validation fail.
                    // For now, it just re-selects the payment. User would need to re-select student.
                    // A better approach would be to pass the $targetStudent object back from the controller
                    // on validation failure and use it to pre-populate the Select2 and trigger fee loading.
                    // Example:
                    /*
                    <?php if(session('target_student_for_repopulation')): ?>
                        var studentData = <?php echo json_encode(session('target_student_for_repopulation'), 15, 512) ?>;
                        var option = new Option(studentData.text, studentData.id, true, true);
                        $('#target_student_search_select').append(option).trigger('change');
                        $('#target_student_search_select').trigger({
                            type: 'select2:select',
                            params: { data: studentData }
                        });
                    <?php endif; ?>
                    */
                   // If allocations old data exists, try to repopulate
                    <?php if(is_array(old('allocations'))): ?>
                        setTimeout(function() { // Wait for fees to potentially load
                            <?php $__currentLoopData = old('allocations'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fee_id => $amount): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                $(`input.allocation-input[data-fee-id="<?php echo e($fee_id); ?>"]`).val(<?php echo e($amount); ?>);
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            updateAllocationSummary();
                        }, 1500); // Adjust delay if needed
                    <?php endif; ?>
                }
            <?php endif; ?>


            // Trigger SweetAlerts for session flash messages (from layouts.partials.alerts or similar)
            <?php if(session('flash_success')): ?>
                Swal.fire('Success!', "<?php echo e(session('flash_success')); ?>", 'success');
            <?php endif; ?>
            <?php if(session('flash_danger')): ?>
                Swal.fire('Error!', "<?php echo e(session('flash_danger')); ?>", 'error');
            <?php endif; ?>
            <?php if(session('flash_warning')): ?>
                Swal.fire('Warning!', "<?php echo e(session('flash_warning')); ?>", 'warning');
            <?php endif; ?>
            <?php if(session('flash_info')): ?>
                Swal.fire('Info!', "<?php echo e(session('flash_info')); ?>", 'info');
            <?php endif; ?>

        });
    </script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/payments/allocate_unlinked_form.blade.php ENDPATH**/ ?>