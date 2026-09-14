<?php
    use App\Helpers\Qs;
?>


<?php $__env->startSection('title', 'Manage Payments'); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid"> 

        
        <div class="card card-outline card-info mb-4"> 
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Payments</h3>
                <a href="<?php echo e(route('staff.students.index')); ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-search me-1"></i> Find Student to Add Payment
                </a>
                 <a href="<?php echo e(route('staff.payments.allocate_unlinked.form')); ?>" class="btn btn-sm btn-warning">
                    <i class="fas fa-link me-1"></i> Allocate Unlinked Payments
                </a>
            </div>
            <div class="card-body">
                
                <form method="GET" action="<?php echo e(route('staff.payments.index')); ?>" class="row g-3 align-items-end">

                    
                    <div class="col-md-4">
                        <label for="student_search" class="form-label">Student Name/ID</label>
                        <input type="text" name="student_search" id="student_search" class="form-control form-control-sm"
                               value="<?php echo e(request('student_search')); ?>" placeholder="Enter name or ID">
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Payment Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control form-control-sm"
                               value="<?php echo e(request('date_from')); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Payment Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control form-control-sm"
                               value="<?php echo e(request('date_to')); ?>">
                    </div>

                    
                    <div class="col-md-2">
                        <label for="amount_from" class="form-label">Amount From</label>
                        <input type="number" step="0.01" name="amount_from" id="amount_from" class="form-control form-control-sm"
                               value="<?php echo e(request('amount_from')); ?>" placeholder="0.00">
                    </div>
                    <div class="col-md-2">
                        <label for="amount_to" class="form-label">Amount To</label>
                        <input type="number" step="0.01" name="amount_to" id="amount_to" class="form-control form-control-sm"
                               value="<?php echo e(request('amount_to')); ?>" placeholder="e.g., 500.00">
                    </div>
                    <div class="col-md-3">
                        <label for="payment_type" class="form-label">Payment Type</label>
                        <select name="payment_type" id="payment_type" class="form-select form-select-sm select2">
                            <?php $__currentLoopData = $paymentTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php echo e(request('payment_type') == $value ? 'selected' : ''); ?>>
                                    <?php echo e($label); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="allocation_status" class="form-label">Allocation Status</label>
                        <select name="allocation_status" id="allocation_status" class="form-select form-select-sm select2">
                            <?php $__currentLoopData = $allocationStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php echo e(request('allocation_status') == $value ? 'selected' : ''); ?>>
                                    <?php echo e($label); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>


                    
                    <div class="col-12 mt-3 text-center">
                        <button type="submit" class="btn btn-info me-2"><i class="fas fa-search me-1"></i>Filter Payments</button>
                        <a href="<?php echo e(route('staff.payments.index')); ?>" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>

        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Payment List</h3>
            </div>
            <div class="card-body">
                <?php if (isset($component)) { $__componentOriginal1f0f987500f76b1f57bfad21f77af286 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1f0f987500f76b1f57bfad21f77af286 = $attributes; } ?>
<?php $component = JeroenNoten\LaravelAdminLte\View\Components\Tool\Datatable::resolve(['id' => 'paymentsTable','heads' => $heads,'config' => $config,'striped' => true,'hoverable' => true,'bordered' => true,'compressed' => true] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('adminlte-datatable'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\JeroenNoten\LaravelAdminLte\View\Components\Tool\Datatable::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1f0f987500f76b1f57bfad21f77af286)): ?>
<?php $attributes = $__attributesOriginal1f0f987500f76b1f57bfad21f77af286; ?>
<?php unset($__attributesOriginal1f0f987500f76b1f57bfad21f77af286); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1f0f987500f76b1f57bfad21f77af286)): ?>
<?php $component = $__componentOriginal1f0f987500f76b1f57bfad21f77af286; ?>
<?php unset($__componentOriginal1f0f987500f76b1f57bfad21f77af286); ?>
<?php endif; ?>
            </div>
        </div>
    </div>

    
    

<?php $__env->stopSection(); ?>


<?php $__env->startSection('css'); ?>
    
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/payments/index.blade.php ENDPATH**/ ?>