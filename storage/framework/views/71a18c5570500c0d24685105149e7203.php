

 

<?php
    // Variables passed from controller:
    // $selectedStudent (the student model if one is pre-selected via query param, optional)
?>


<?php $__env->startSection('title', 'Create New Fee'); ?>

<?php $__env->startPush('styles'); ?>
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
    <style>
        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(2.25rem + 2px) !important; /* Adjust height to match form-control-lg if needed */
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding: .375rem .75rem;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px) !important;
        }
        /* Ensure Select2 dropdown is above other elements if z-index issues occur */
        .select2-container {
            z-index: 9999 !important;
        }
    </style>
<?php $__env->stopPush(); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7"> 

                <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> 

                
                <div class="card card-info card-outline shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="card-title">1. Find Student</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="student_search_select">Search and Select Student <span class="text-danger">*</span></label>
                            <select id="student_search_select" name="student_search_select" class="form-control">
                                
                                <?php if($selectedStudent): ?>
                                    <option value="<?php echo e($selectedStudent->id); ?>" selected="selected">
                                        <?php echo e($selectedStudent->last_name); ?>, <?php echo e($selectedStudent->first_name); ?> (ID: <?php echo e($selectedStudent->prem_number ?? 'N/A'); ?>)
                                    </option>
                                <?php endif; ?>
                            </select>
                            <small class="form-text text-muted">Type to search by name or Permanent #.</small>
                        </div>
                        <?php if($selectedStudent): ?>
                            <div class="mt-2">
                                <a href="<?php echo e(route('staff.fees.create')); ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear Selected Student
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div id="fee_creation_form_card" class="card card-success card-outline shadow-sm <?php echo e(!$selectedStudent ? 'd-none' : ''); ?>">
                    <div class="card-header">
                        <h3 class="card-title">2. Enter Fee Details for: <strong id="selected_student_name_display"><?php echo e($selectedStudent ? ($selectedStudent->last_name . ', ' . $selectedStudent->first_name) : ''); ?></strong></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo e(route('staff.fees.store')); ?>" id="create_fee_form">
                            <?php echo csrf_field(); ?>
                            
                            <input type="hidden" name="student_id" id="student_id_hidden" value="<?php echo e($selectedStudent ? $selectedStudent->id : ''); ?>">

                            
                            <div class="form-group mb-3">
                                <label for="title">Fee Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('title')); ?>" placeholder="e.g., Tuition Fee, Exam Fee" required>
                                <?php $__errorArgs = ['title'];
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

                            
                            <div class="form-group mb-3">
                                <label for="amount">Amount <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="amount" class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('amount')); ?>" placeholder="0.00" required step="0.01" min="0">
                                <?php $__errorArgs = ['amount'];
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

                            
                            <div class="form-group mb-3">
                                <label for="assigned_date">Assigned Date</label>
                                <input type="date" name="assigned_date" id="assigned_date" class="form-control <?php $__errorArgs = ['assigned_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('assigned_date', now()->toDateString())); ?>"> 
                                <?php $__errorArgs = ['assigned_date'];
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

                            
                            <div class="form-group mb-3">
                                <label for="due_date">Due Date</label>
                                <input type="date" name="due_date" id="due_date" class="form-control <?php $__errorArgs = ['due_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('due_date')); ?>">
                                <?php $__errorArgs = ['due_date'];
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

                            
                            <div class="form-group mb-3">
                                <label for="comments">Comments</label>
                                <textarea name="comments" id="comments" class="form-control <?php $__errorArgs = ['comments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                          rows="3" placeholder="Optional comments about this fee"><?php echo e(old('comments')); ?></textarea>
                                <?php $__errorArgs = ['comments'];
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

                            
                            <div class="text-center border-top pt-3 mt-3">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save mr-1"></i> Create Fee
                                </button>
                                <a href="<?php echo e(route('staff.fees.index')); ?>" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times-circle mr-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if(!$selectedStudent): ?>
                    <div id="select_student_placeholder" class="alert alert-warning text-center">
                        Please search for and select a student above to create a fee.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    
     
    
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for student search
            $('#student_search_select').select2({
                theme: 'bootstrap4', // Optional: if you're using Bootstrap 4 theme
                placeholder: 'Search by Name or Permanent #...',
                allowClear: true,
                minimumInputLength: 2, // Minimum characters to start searching
                ajax: {
                    url: '<?php echo e(route("staff.students.search_json")); ?>', // IMPORTANT: Create this route and controller method
                    dataType: 'json',
                    delay: 250, // Wait 250ms after typing before triggering the request
                    data: function (params) {
                        return {
                            search_term: params.term, // Search term
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: $.map(data.data, function (student) { // Assuming your JSON returns data in 'data' property
                                return {
                                    id: student.id,
                                    text: student.last_name + ', ' + student.first_name + (student.prem_number ? ' (ID: ' + student.prem_number + ')' : '') + (student.grade_level ? ' - ' + student.grade_level : ''),
                                    // You can pass the full student object if needed for display or other purposes
                                    full_student_data: student
                                }
                            }),
                            pagination: {
                                more: (params.page * data.per_page) < data.total // Assuming pagination info from server
                            }
                        };
                    },
                    cache: true
                }
            });

            // Handle student selection
            $('#student_search_select').on('select2:select', function (e) {
                var data = e.params.data;
                if (data && data.id) {
                    $('#student_id_hidden').val(data.id);
                    $('#selected_student_name_display').text(data.text.split(' (ID:')[0]); // Extract name part
                    $('#fee_creation_form_card').removeClass('d-none');
                    $('#select_student_placeholder').addClass('d-none');

                    // Optional: Scroll to the fee creation form
                    // $('html, body').animate({
                    //     scrollTop: $("#fee_creation_form_card").offset().top - 70 // Adjust offset as needed
                    // }, 500);

                }
            });

            // Handle clearing student selection
            $('#student_search_select').on('select2:unselect', function (e) {
                $('#student_id_hidden').val('');
                $('#selected_student_name_display').text('');
                $('#fee_creation_form_card').addClass('d-none');
                $('#select_student_placeholder').removeClass('d-none');
            });

            // If a student was pre-selected (e.g., from query param), ensure the form is visible
            <?php if($selectedStudent): ?>
                $('#fee_creation_form_card').removeClass('d-none');
                $('#select_student_placeholder').addClass('d-none');
            <?php endif; ?>

            // SweetAlert for form submission (Example)
            // You might want to integrate this with how your `layouts.partials.alerts` handles flash messages
            // For example, if your backend redirects with session('success_swal', 'Message'),
            // your main layout could have JS to pick that up and display SweetAlert.
            $('#create_fee_form').on('submit', function(e) {
                // You can add client-side validation here before showing a "processing" SweetAlert
                // For instance, if using jQuery validation: if (!$(this).valid()) return;

                // Example: Show a processing alert (optional)
                // Swal.fire({
                //   title: 'Processing...',
                //   text: 'Please wait while the fee is being created.',
                //   allowOutsideClick: false,
                //   didOpen: () => {
                //     Swal.showLoading();
                //   }
                // });
                // The form will submit normally. Server-side validation errors will be shown via Laravel's default
                // mechanism (or your custom alert partial). Success should ideally trigger a SweetAlert on redirect.
            });

            // Example of how your `layouts.partials.alerts` might trigger SweetAlerts
            // This is conceptual. Your actual implementation in `alerts.blade.php` or main layout would differ.
            <?php if(session('flash_success_swal')): ?>
                Swal.fire({
                    title: 'Success!',
                    text: '<?php echo e(session('flash_success_swal')); ?>',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            <?php endif; ?>
            <?php if(session('flash_error_swal')): ?>
                Swal.fire({
                    title: 'Error!',
                    text: '<?php echo e(session('flash_error_swal')); ?>',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            <?php endif; ?>
            <?php if(session('flash_warning_swal')): ?>
                Swal.fire({
                    title: 'Warning!',
                    text: '<?php echo e(session('flash_warning_swal')); ?>',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            <?php endif; ?>
            <?php if(session('flash_info_swal')): ?>
                Swal.fire({
                    title: 'Info!',
                    text: '<?php echo e(session('flash_info_swal')); ?>',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            <?php endif; ?>

        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/fees/create.blade.php ENDPATH**/ ?>