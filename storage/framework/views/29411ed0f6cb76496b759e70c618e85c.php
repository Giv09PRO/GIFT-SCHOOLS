
 


<?php $__env->startSection('title'); ?>
    Enrolled Students - <?php echo e($grade->title); ?>

<?php $__env->stopSection(); ?>


<?php $__env->startSection('subtitle'); ?>
    <?php echo e($grade->school_syear); ?> Academic Year
<?php $__env->stopSection(); ?>


<?php $__env->startSection('plugins.Sweetalert2', true); ?>
 


<?php $__env->startSection('content_body'); ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                
                <?php if(session('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo e(session('success')); ?>

                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if(session('warning')): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <?php echo e(session('warning')); ?>

                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if(session('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo e(session('error')); ?>

                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-users mr-1"></i>
                            Students in <?php echo e($grade->title); ?>

                        </h3>
                        <div class="card-tools">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage grades')): ?>
                                <a href="<?php echo e(route('staff.grades.assign.form', $grade->id)); ?>" class="btn btn-sm btn-primary" title="Assign More Students">
                                    <i class="fas fa-user-plus"></i> Assign Students
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo e(route('staff.grades.index')); ?>" class="btn btn-sm btn-secondary" title="Back to Grades List">
                                <i class="fas fa-arrow-left"></i> Back to Grades
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if($enrollments->isEmpty()): ?>
                            <div class="alert alert-info" id="no-students-message"> 
                                <i class="icon fas fa-info"></i>
                                No students are currently enrolled in this grade (<?php echo e($grade->title); ?>) for the <?php echo e($grade->school_syear); ?> academic year.
                            </div>
                            
                            <div class="table-responsive" style="display: none;" id="students-table-container">
                                <table id="enrolledStudentsTable" class="table table-bordered table-striped table-hover">
                                     
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info" id="no-students-message" style="display: none;">
                                <i class="icon fas fa-info"></i>
                                No students are currently enrolled in this grade.
                            </div>
                            <div class="table-responsive" id="students-table-container">
                                <table id="enrolledStudentsTable" class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>PREM No.</th>
                                            <th>Full Name</th>
                                            <th>Gender</th>
                                            <th>Enrollment Start Date</th>
                                            <th class="text-center" style="width: 15%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $enrollments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $enrollment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr id="enrollment-row-<?php echo e($enrollment->id); ?>">
                                                <td><?php echo e($index + 1); ?></td>
                                                <td><?php echo e($enrollment->student->prem_number ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php echo e($enrollment->student->first_name ?? ''); ?> <?php echo e($enrollment->student->last_name ?? ''); ?>

                                                    <?php if($enrollment->student && $enrollment->student->deleted_at): ?>
                                                        <span class="badge badge-danger">Deactivated</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo e($enrollment->student->gender ?? 'N/A'); ?></td>
                                                <td><?php echo e(\Carbon\Carbon::parse($enrollment->start_date)->format('M d, Y')); ?></td>
                                                <td class="text-center">
                                                    <?php if($enrollment->student): ?>
                                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $enrollment->student)): ?>
                                                            <a href="<?php echo e(route('staff.students.show', $enrollment->student_id)); ?>" class="btn btn-xs btn-info mr-1" title="View Student Profile">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        <?php else: ?>
                                                             <button class="btn btn-xs btn-info mr-1 disabled" title="You do not have permission to view this student"><i class="fas fa-eye"></i></button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage grades')): ?>
                                                    <button type="button" class="btn btn-xs btn-danger remove-enrollment-btn"
                                                            data-enrollment-id="<?php echo e($enrollment->id); ?>"
                                                            data-url="<?php echo e(route('staff.grades.enrollments.remove', $enrollment->id)); ?>"
                                                            title="Remove Student from Grade">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-xs btn-danger disabled" title="You do not have permission to remove students"><i class="fas fa-user-minus"></i></button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        $(document).ready(function() {
            console.log("Document ready. SweetAlert2 and jQuery should be loaded.");

            let enrolledTable;
            if ($.fn.DataTable) {
                console.log("DataTable plugin found. Initializing #enrolledStudentsTable.");
                try {
                    enrolledTable = $('#enrolledStudentsTable').DataTable({
                        "responsive": true,
                        "autoWidth": false,
                        "columnDefs": [
                            { "orderable": false, "targets": 5 } // Actions column
                        ]
                    });
                    console.log("DataTable initialized successfully.");
                } catch (e) {
                    console.error("Error initializing DataTable:", e);
                }
            } else {
                console.warn("DataTable plugin not found.");
            }

            function checkTableEmpty() {
                const isDataTableEmpty = enrolledTable && enrolledTable.rows().count() === 0;
                const isStaticTableEmpty = !enrolledTable && $('#enrolledStudentsTable tbody tr').length === 0;

                if (isDataTableEmpty || isStaticTableEmpty) {
                    $('#students-table-container').hide();
                    $('#no-students-message').show();
                    console.log("Table is empty, showing 'no students' message.");
                } else {
                    $('#students-table-container').show();
                    $('#no-students-message').hide();
                    console.log("Table has students, showing table.");
                }
            }
            // Initial check might be useful if the table could be empty after Blade rendering but before JS.
            // checkTableEmpty();


            $('#enrolledStudentsTable').on('click', '.remove-enrollment-btn', function () {
                var button = $(this);
                var enrollmentId = button.data('enrollment-id');
                var url = button.data('url');

                console.log("Remove button clicked. Enrollment ID:", enrollmentId, "URL:", url);

                if (typeof Swal === 'undefined') {
                    console.error("SweetAlert2 (Swal) is not loaded!");
                    alert("Error: Alerting library not loaded. Please contact support.");
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to remove this student's enrollment from the grade. This action will end their current enrollment.",
                    type: 'warning', // Changed from icon: 'warning' to type: 'warning' for SweetAlert2 v8
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, remove it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    // For SweetAlert v8, result is an object like { value: true } for confirm, or { dismiss: 'cancel' / 'overlay' / etc. } for dismiss
                    // The .isConfirmed property might not exist or behave differently than in v9+
                    // We should check for result.value for confirmation in older versions
                    console.log("SweetAlert confirmation result:", result);

                    if (result.value) { // Check result.value for SweetAlert2 v8 confirmation
                        console.log("User confirmed (result.value is true). Proceeding with AJAX request.");

                        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                        console.log("Button disabled, spinner shown.");

                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {
                                _token: "<?php echo e(csrf_token()); ?>"
                            },
                            dataType: 'json',
                            success: function(response) {
                                console.log("AJAX success response:", response);
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Removed!',
                                        text: response.success,
                                        type: 'success', // Changed from icon: 'success'
                                        timer: 2500,
                                        showConfirmButton: false
                                    });

                                    if (enrolledTable) {
                                        console.log("Removing row from DataTable.");
                                        enrolledTable.row(button.closest('tr')).remove().draw(false);
                                    } else {
                                        console.log("DataTable not found, removing row directly from DOM.");
                                        button.closest('tr').fadeOut(500, function() { $(this).remove(); });
                                    }
                                    checkTableEmpty();

                                } else {
                                    console.error("AJAX success but logical error:", response.error);
                                    Swal.fire('Error!', response.error || 'An unexpected error occurred on the server.', 'error'); // 'error' is a valid type
                                    button.prop('disabled', false).html('<i class="fas fa-user-minus"></i>');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error("AJAX error. Status:", status, "Error:", error, "XHR:", xhr);
                                var errorMessage = 'An error occurred while removing the enrollment.';
                                if (xhr.responseJSON && xhr.responseJSON.error) {
                                    errorMessage = xhr.responseJSON.error;
                                } else if (xhr.responseText) {
                                    try {
                                        var parsedError = JSON.parse(xhr.responseText);
                                        if(parsedError.message) errorMessage = parsedError.message;
                                    } catch(e){ /* Ignore parsing error, use default */ }
                                }
                                Swal.fire('Request Failed!', errorMessage, 'error'); // 'error' is a valid type
                                button.prop('disabled', false).html('<i class="fas fa-user-minus"></i>');
                            },
                            complete: function() {
                                console.log("AJAX request complete.");
                                // Fallback to re-enable button if not handled in success/error
                                // if (button.prop('disabled')) {
                                //    button.prop('disabled', false).html('<i class="fas fa-user-minus"></i>');
                                // }
                            }
                        });
                    } else {
                        console.log("User cancelled or dismissed the dialog. Result:", result);
                        // If result.dismiss exists, it means the dialog was dismissed (e.g., by clicking cancel, escape, or outside)
                        if (result.dismiss) {
                             console.log("Dialog dismissed with reason:", result.dismiss);
                        }
                    }
                });
            });
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\GIFT\resources\views/pages/staff/grades/enrolled_students.blade.php ENDPATH**/ ?>