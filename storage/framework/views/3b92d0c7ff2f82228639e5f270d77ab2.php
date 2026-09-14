 

<?php
    use App\Helpers\Qs;
?>

<?php $__env->startSection('title', 'Manage Students'); ?>

<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="card card-outline card-primary mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Students</h3>
                <div class="card-tools">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit students')): ?>
                        <a href="<?php echo e(route('staff.students.bulk_activate.form')); ?>" class="btn btn-sm btn-primary me-1" title="Enroll students who have no enrollment history">
                            <i class="fas fa-user-check me-1"></i> Bulk Activate
                        </a>
                        <a href="<?php echo e(route('staff.students.bulk_enroll.form')); ?>" class="btn btn-sm btn-info me-1">
                            <i class="fas fa-layer-group me-1"></i> Bulk Enroll
                        </a>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('importStudents', App\Models\Student::class)): ?>
                        <a href="<?php echo e(route('staff.students.import.form')); ?>" class="btn btn-sm btn-warning me-1">
                            <i class="fas fa-file-import me-1"></i> Import Students
                        </a>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Student::class)): ?>
                        <a href="<?php echo e(route('staff.students.create')); ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i> Add New Student
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <form id="filter-form" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="student_search_input" class="form-label">Student Name/ID/Username</label>
                        <input type="text" id="student_search_input" class="form-control form-control-sm" placeholder="Enter name, ID, or username">
                    </div>
                    <div class="col-md-2">
                        <label for="grade_id_select" class="form-label">Grade Level (<?php echo e($currentYear); ?>)</label>
                        <select id="grade_id_select" class="form-select form-select-sm select2">
                            <option value="">All</option>
                            <?php $__currentLoopData = $grades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $title): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($id !== ''): ?>
                                    <option value="<?php echo e($id); ?>"><?php echo e($title); ?></option>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="school_id_select" class="form-label">School (<?php echo e($currentYear); ?>)</label>
                        <select id="school_id_select" class="form-select form-select-sm select2">
                            <option value="">All</option>
                            <?php $__currentLoopData = $schools; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $title): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($id !== ''): ?>
                                    <option value="<?php echo e($id); ?>"><?php echo e($title); ?></option>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status_select" class="form-label">Status (<?php echo e($currentYear); ?>)</label>
                        <select id="status_select" class="form-select form-select-sm select2">
                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" id="clear-filters-button" class="btn btn-sm btn-secondary w-100" title="Clear Filters"><i class="fas fa-times"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Student List</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive box">
                    <?php echo $__env->make('layouts.partials.import_errors', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('layouts.partials.flash_messages', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                    <table id="students-table" class="table table-bordered table-striped striped table-hover responsive bordered compressed" style="width:100%">
                        <thead>
                        <tr>
                            <?php $__currentLoopData = $heads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $head): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(is_array($head)): ?>
                                    <th style="width: <?php echo e($head['width'] ?? 'auto'); ?>%"
                                        <?php if(isset($head['no-export'])): ?> data-exportable="false" <?php endif; ?>
                                        class="<?php echo e($head['className'] ?? ''); ?>">
                                        <?php echo e($head['label']); ?>

                                    </th>
                                <?php else: ?>
                                    <th><?php echo e($head); ?></th>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('#grade_id_select, #school_id_select, #status_select').select2({
                theme: 'bootstrap4',
                placeholder: "All",
                allowClear: true,
                width: "100%"
            });
        }

        function debounce(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }

        const table = $('#students-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '<?php echo e(route("staff.students.data")); ?>',
                type: 'GET',
                data: function (d) {
                    d.student_search = $('#student_search_input').val();
                    d.grade_id = $('#grade_id_select').val();
                    d.school_id = $('#school_id_select').val();
                    d.status = $('#status_select').val();
                },
                error: function (xhr) {
                    console.error("DataTables Error:", xhr.responseText);
                    $('#students-table').html('<tr><td colspan="' + $('#students-table thead th').length + '">Error loading data. Please try refreshing or contact support.</td></tr>');
                    alert('Error fetching student data. Please check console or contact support.');
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center align-middle' },
                { data: 'name', name: 'name', className: 'align-middle' },
                { data: 'username', name: 'username', className: 'align-middle' },
                { data: 'gender', name: 'gender', className: 'align-middle' },
                { data: 'class', name: 'class', className: 'align-middle' },
                { data: 'school', name: 'school', searchable: false, className: 'align-middle' },
                { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center align-middle' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center align-middle' }
            ],
            order: [[1, 'asc']],
            paging: true,
            lengthChange: true,
            lengthMenu: [ [25, 50, 100, -1], [25, 50, 100, "All"] ],
            searching: false,
            info: true,
            autoWidth: false
        });

        $('#student_search_input').on('keyup', debounce(function() {
            table.draw();
        }, 500));

        $('#grade_id_select, #school_id_select, #status_select').on('change', function() {
            table.draw();
        });

        $('#clear-filters-button').on('click', function() {
            $('#filter-form').find('input[type="text"]').val('');
            $('#filter-form').find('select').prop('selectedIndex', 0).trigger('change');
        });

        $('#refresh-datatable').on('click', function() {
            table.ajax.reload(null, false);
        });

        console.log('Student index with Yajra DataTable initialized!');
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\GIFT\resources\views/pages/staff/student/index.blade.php ENDPATH**/ ?>