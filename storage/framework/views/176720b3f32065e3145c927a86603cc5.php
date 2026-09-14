


 

<?php
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

?>

<?php $__env->startSection('title', __("Financial Statement: :name (:year)", ['name' => $studentFullName, 'year' => $statementPeriodDisplay])); ?>

<?php $__env->startSection('content_header'); ?>
    
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    <?php echo e(__('Financial Statement')); ?>

                    <small class="text-muted"><?php echo e(__('for :name', ['name' => $studentFullName])); ?></small>
                </h1>
            </div>
            <div class="col-sm-6">
                <div class="float-sm-right">
                    <a href="<?php echo e(route('staff.finance.statements.index', ['syear' => $syear])); ?>" class="btn btn-sm btn-outline-secondary mr-2">
                        <i class="fas fa-arrow-left mr-1"></i> <?php echo e(__('Back to List')); ?>

                    </a>
                    <a href="<?php echo e(route('staff.students.finance.statement.pdf', ['student' => $student->id, 'syear' => $syear])); ?>" target="_blank" class="btn btn-sm btn-danger">
                        <i class="fas fa-file-pdf mr-1"></i> <?php echo e(__('Generate PDF')); ?>

                    </a>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> 

    
    <div class="row mb-3">
        <div class="col-md-6 offset-md-6">
            <form method="GET" action="<?php echo e(route('staff.students.finance.statement.show', ['student' => $student->id])); ?>" class="form-inline float-md-right">
                <label for="syear" class="my-1 mr-2 font-weight-normal"><?php echo e(__('View Statement for Year:')); ?></label>
                <select name="syear" id="syear" class="form-control form-control-sm my-1 mr-sm-2" onchange="this.form.submit()" style="min-width: 100px;">
                    <?php $__currentLoopData = $years ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year_option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($year_option); ?>" <?php if(($syear ?? null) == $year_option): echo 'selected'; endif; ?>>
                            <?php echo e($year_option); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </form>
        </div>
    </div>

    
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-file-invoice-dollar mr-1"></i>
                <strong><?php echo e(__('Statement for :name', ['name' => $studentFullName])); ?></strong>
                <span class="text-muted"> (ID: <?php echo e($student->username ?? $student->id); ?>)</span>
            </h3>
            <div class="card-tools">
                <span class="badge badge-light p-2"><?php echo e(__('Issued:')); ?> <?php echo e(($issueDate ?? now())->format('M d, Y H:i A')); ?></span>
            </div>
        </div>

        <div class="card-body">
            
            <div class="row mb-4 p-3 bg-light rounded">
                <div class="col-md-6">
                    <h5><?php echo e($schoolDisplayName); ?></h5>
                    <?php if($schoolAddress): ?><address class="mb-1"><i class="fas fa-map-marker-alt mr-1 text-muted"></i> <?php echo e($schoolAddress); ?></address><?php endif; ?>
                    <?php if($schoolPhone): ?><p class="mb-1"><i class="fas fa-phone mr-1 text-muted"></i> <?php echo e($schoolPhone); ?></p><?php endif; ?>
                    <?php if($schoolEmail): ?><p class="mb-0"><i class="fas fa-envelope mr-1 text-muted"></i> <?php echo e($schoolEmail); ?></p><?php endif; ?>
                </div>
                <div class="col-md-6 text-md-right">
                    <h5 class="mb-1"><?php echo e(__('Statement Period:')); ?> <span class="font-weight-bold"><?php echo e($statementPeriodDisplay); ?></span></h5>
                    <p class="mb-1">
                        <?php echo e(__('Opening Balance:')); ?> <strong class="text-primary"><?php echo e(number_format($displayOpeningBalance, 2)); ?></strong>
                        <br><small class="text-muted"><?php echo e(__('(Carried forward from previous periods)')); ?></small>
                    </p>
                    <p class="mb-0">
                        <?php echo e(__('Closing Balance for Period:')); ?> <strong class="text-danger"><?php echo e(number_format($displayClosingBalance, 2)); ?></strong>
                        <br><small class="text-muted"><?php echo e(__('(As of end of :year)', ['year' => $statementPeriodDisplay])); ?></small>
                    </p>
                </div>
            </div>

            
            <h4 class="mb-3 text-center"><?php echo e(__('Transaction Details')); ?></h4>
            <div class="table-responsive">
                <table id="statementTransactionsTable" class="table table-striped table-bordered table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            <?php $__currentLoopData = $heads ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $head): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(is_array($head)): ?>
                                    <th width="<?php echo e($head['width'] ?? 'auto'); ?>%" class="<?php echo e($head['class'] ?? ''); ?>"><?php echo e(__($head['label'])); ?></th>
                                <?php else: ?>
                                    <th><?php echo e(__($head)); ?></th>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $config['data'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="<?php echo e(isset($heads[0]) && is_array($heads[0]) && !empty($heads[0]['class']) ? $heads[0]['class'] : ''); ?>"><?php echo $row['date'] ?? 'N/A'; ?></td>
                                <td class="<?php echo e(isset($heads[1]) && is_array($heads[1]) && !empty($heads[1]['class']) ? $heads[1]['class'] : (is_string($heads[1] ?? null) ? '' : '')); ?>"><?php echo $row['description_html'] ?? ''; ?></td>
                                <td class="<?php echo e(isset($heads[2]) && is_array($heads[2]) && !empty($heads[2]['class']) ? $heads[2]['class'] : 'text-right'); ?>"><?php echo $row['charge'] ?? ''; ?></td>
                                <td class="<?php echo e(isset($heads[3]) && is_array($heads[3]) && !empty($heads[3]['class']) ? $heads[3]['class'] : 'text-right'); ?>"><?php echo $row['credit'] ?? ''; ?></td>
                                <td class="<?php echo e(isset($heads[4]) && is_array($heads[4]) && !empty($heads[4]['class']) ? $heads[4]['class'] : 'text-right'); ?>"><?php echo $row['running_balance'] ?? ''; ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="<?php echo e(count($heads ?? [1])); ?>" class="text-center py-4">
                                    <i class="fas fa-info-circle mr-1"></i> <?php echo e(__('No transactions found for this period.')); ?>

                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            
            <div class="mt-4 pt-3 border-top">
                <h4 class="mb-3 text-center"><?php echo e(__('Summary for :year', ['year' => $statementPeriodDisplay])); ?></h4>
                <div class="row justify-content-center">
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?php echo e(__('Total Fees Charged')); ?></span>
                                <span class="info-box-number h5"><?php echo e(number_format($displayTotalFees, 2)); ?></span>
                                <small class="text-muted"><?php echo e(__('(Net fees this period)')); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-primary"><i class="fas fa-receipt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?php echo e(__('Total Payments Received')); ?></span>
                                <span class="info-box-number h5"><?php echo e(number_format($displayTotalPaymentsMadeThisPeriod, 2)); ?></span>
                                <small class="text-muted"><?php echo e(__('(During this period)')); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-info"><i class="fas fa-hand-holding-usd"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?php echo e(__('Payments Applied')); ?></span>
                                <span class="info-box-number h5"><?php echo e(number_format($displayTotalPaymentsAppliedToPeriodFees, 2)); ?></span>
                                <small class="text-muted"><?php echo e(__('(To this period\'s fees)')); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-danger"><i class="fas fa-balance-scale"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?php echo e(__('Closing Balance')); ?></span>
                                <span class="info-box-number h5"><?php echo e(number_format($displayClosingBalance, 2)); ?></span>
                                <small class="text-muted"><?php echo e(__('(As of end of :year)', ['year' => $statementPeriodDisplay])); ?></small> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> 

        <div class="card-footer text-muted text-sm">
            <p class="mb-1"><?php echo e(__('If you have any questions concerning this statement, please contact the school office at :phone.', ['phone' => $schoolPhone ?? __('the school phone number')])); ?></p>
            <p class="mb-0"><?php echo e(__('Thank you for your prompt attention to your account.')); ?></p>
        </div>
    </div> 

</div> 
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
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
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/finance/show.blade.php ENDPATH**/ ?>