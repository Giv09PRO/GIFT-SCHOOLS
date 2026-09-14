


 


<?php $__env->startSection('title', 'Batch Statement History'); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">

        
        <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="card card-info card-outline">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Batch Statement History</h3>
                    <a href="<?php echo e(route('staff.finance.statements.batch.form')); ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus mr-1"></i> Generate New Batch
                    </a>
                </div>
            </div>
            <div class="card-body p-0"> 
                <?php if($batches->isEmpty()): ?>
                    <div class="alert alert-light m-3 text-center">
                        <i class="fas fa-folder-open fa-3x mb-2"></i>
                        <p>No batch statement jobs found in the history.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Batch ID</th>
                                    <th>Name / Description</th>
                                    <th class="text-center">Total Jobs</th>
                                    <th class="text-center">Pending</th>
                                    <th class="text-center">Failed</th>
                                    <th>Created At</th>
                                    <th>Finished At</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        // Attempt to get the live batch instance.
                                        // $batch here is an item from the $batches collection, likely from the job_batches table.
                                        $batchInstance = $batch->id ? \Illuminate\Support\Facades\Bus::findBatch($batch->id) : null;

                                        $status = 'Unknown';
                                        $statusClass = 'secondary';
                                        // Default pending jobs count from the job_batches table record.
                                        $pendingJobsDisplay = $batch->pending_jobs;

                                        if ($batchInstance) {
                                            // If live batch instance exists, use its data.
                                            $pendingJobsDisplay = $batchInstance->pendingJobs;

                                            if ($batchInstance->finished()) {
                                                if ($batchInstance->hasFailures()) {
                                                    $status = 'Completed with Failures';
                                                    $statusClass = 'warning';
                                                } else {
                                                    $status = 'Completed Successfully';
                                                    $statusClass = 'success';
                                                }
                                            } elseif ($batchInstance->cancelled()) {
                                                $status = 'Cancelled';
                                                $statusClass = 'danger';
                                            } else { // Still running or pending (live instance)
                                                $progress = $batchInstance->progress();
                                                $status = 'Processing (' . $progress . '%)';
                                                $statusClass = 'info';
                                            }
                                        } else {
                                            // Batch instance not found (e.g., pruned). Rely on data from the job_batches table.
                                            if ($batch->cancelled_at) {
                                                $status = 'Cancelled (Pruned)';
                                                $statusClass = 'danger';
                                            } elseif ($batch->finished_at) {
                                                if ($batch->failed_jobs > 0) {
                                                    if ($batch->failed_jobs == $batch->total_jobs && $batch->total_jobs > 0) {
                                                        $status = 'All Failed (Pruned)';
                                                        $statusClass = 'danger';
                                                    } else {
                                                        $status = 'Completed with Failures (Pruned)';
                                                        $statusClass = 'warning';
                                                    }
                                                } else {
                                                    $status = 'Completed Successfully (Pruned)';
                                                    $statusClass = 'success';
                                                }
                                            } elseif ($batch->total_jobs > 0 && $batch->pending_jobs == 0 && !$batch->finished_at && !$batch->cancelled_at) {
                                                if($batch->failed_jobs > 0) {
                                                    $status = 'Likely Finished with Failures (Pruned)';
                                                    $statusClass = 'warning';
                                                } else {
                                                    $status = 'Likely Completed (Pruned)';
                                                    $statusClass = 'secondary';
                                                }
                                            } elseif ($batch->total_jobs > 0 && $batch->pending_jobs > 0 && $batch->pending_jobs < $batch->total_jobs) {
                                                $processedCountDb = $batch->total_jobs - $batch->pending_jobs;
                                                $progressDb = ($batch->total_jobs > 0) ? round(($processedCountDb / $batch->total_jobs) * 100) : 0;
                                                $status = 'Processing (' . $progressDb . '%) (Pruned)';
                                                $statusClass = 'info';
                                            } elseif ($batch->total_jobs > 0 && $batch->pending_jobs == $batch->total_jobs && !$batch->finished_at && !$batch->cancelled_at) {
                                                $status = 'Pending (Pruned)';
                                                $statusClass = 'primary';
                                            } elseif ($batch->total_jobs == 0) {
                                                 $status = 'Empty Batch (Pruned)';
                                                 $statusClass = 'secondary';
                                            } else {
                                                $status = 'Status Unavailable (Pruned)';
                                                $statusClass = 'secondary';
                                            }
                                        }

                                        // --- Logic to determine if download button should show ---
                                        $showDownloadButton = false;
                                        $downloadButtonTitle = 'Download ZIP';
                                        $zipPathToCheck = null;

                                        if ($status === 'Completed Successfully' && $batchInstance) {
                                            // Case 1: Live batch instance is available
                                            $liveBatchOptions = $batchInstance->options; // options is a public property (array) // options() method returns the array
                                            if (isset($liveBatchOptions['zipPath'])) {
                                                $zipPathToCheck = $liveBatchOptions['zipPath'];
                                            }
                                        } elseif (Str::startsWith($status, 'Completed Successfully (Pruned)')) {
                                            // Case 2: Batch instance is pruned, rely on $batch->options from DB
                                            if ($batch->options) { // $batch->options is the JSON string from DB
                                                $prunedBatchOptionsArray = json_decode($batch->options, true);
                                                if (is_array($prunedBatchOptionsArray) && isset($prunedBatchOptionsArray['zipPath'])) {
                                                    $zipPathToCheck = $prunedBatchOptionsArray['zipPath'];
                                                    $downloadButtonTitle = 'Download ZIP (Pruned)';
                                                }
                                            }
                                        }

                                        // Check if file exists on 'local' disk (adjust disk name if necessary)
                                        if ($zipPathToCheck && Storage::disk('local')->exists($zipPathToCheck)) {
                                            $showDownloadButton = true;
                                        }

                                    ?>
                                    <tr>
                                        <td>
                                            <code style="font-size: 0.8rem; display: block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo e($batch->id); ?>">
                                                <?php echo e($batch->id); ?>

                                            </code>
                                        </td>
                                        <td><?php echo e($batch->name ?: 'N/A'); ?></td>
                                        <td class="text-center"><?php echo e($batch->total_jobs); ?></td>
                                        <td class="text-center"><?php echo e($pendingJobsDisplay); ?></td>
                                        <td class="text-center">
                                            <?php if($batch->failed_jobs > 0): ?>
                                                <span class="badge badge-danger"><?php echo e($batch->failed_jobs); ?></span>
                                            <?php else: ?>
                                                <?php echo e($batch->failed_jobs); ?>

                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($batch->created_at): ?>
                                                <?php echo e(\Carbon\Carbon::createFromTimestamp($batch->created_at)->format('Y-m-d H:i:s')); ?>

                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($batch->finished_at): ?>
                                                <?php echo e(\Carbon\Carbon::createFromTimestamp($batch->finished_at)->format('Y-m-d H:i:s')); ?>

                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-<?php echo e($statusClass); ?>"><?php echo e($status); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-xs btn-info check-status-btn" data-batch-id="<?php echo e($batch->id); ?>" title="Check Live Status">
                                                <i class="fas fa-sync-alt"></i> Status
                                            </button>

                                            <?php if($showDownloadButton): ?>
                                                <a href="<?php echo e(route('staff.finance.statements.batch.download', $batch->id)); ?>"
                                                   class="btn btn-xs btn-success"
                                                   title="<?php echo e($downloadButtonTitle); ?>">
                                                    <i class="fas fa-download"></i> ZIP
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div> 

            <?php if($batches->hasPages()): ?>
                <div class="card-footer clearfix">
                    <?php echo e($batches->links()); ?> 
                </div>
            <?php endif; ?>
        </div> 

        
        <div class="modal fade" id="batchStatusModal" tabindex="-1" role="dialog" aria-labelledby="batchStatusModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="batchStatusModalLabel">Batch Status Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="modal_batch_status_loading" class="text-center" style="display: none;">
                            <i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading status...</p>
                        </div>
                        <pre id="modal_batch_status_result_json" class="p-3 bg-light border rounded" style="white-space: pre-wrap; word-break: break-all; display: none;"></pre>
                        <div id="modal_batch_status_error" class="alert alert-danger" style="display: none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    </div> 
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        $(document).ready(function() {
            $('.check-status-btn').on('click', function() {
                const batchId = $(this).data('batch-id');
                const modal = $('#batchStatusModal');
                const resultJsonPre = modal.find('#modal_batch_status_result_json');
                const errorDiv = modal.find('#modal_batch_status_error');
                const loadingDiv = modal.find('#modal_batch_status_loading');

                resultJsonPre.hide().empty();
                errorDiv.hide().empty();
                loadingDiv.show();
                modal.modal('show');
                modal.find('#batchStatusModalLabel').text('Batch Status Details for ID: ' + batchId);

                let statusUrl = "<?php echo e(route('staff.finance.statements.batch.status', ['batchId' => 'PLACEHOLDER_BATCH_ID'])); ?>";
                statusUrl = statusUrl.replace('PLACEHOLDER_BATCH_ID', batchId);

                fetch(statusUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().catch(() => {
                            throw { status: response.status, data: { message: response.statusText } };
                        }).then(errData => {
                             throw { status: response.status, data: errData };
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    resultJsonPre.text(JSON.stringify(data, null, 2)).show();
                })
                .catch(error => {
                    console.error('Error fetching batch status:', error);
                    let errorMessage = 'An error occurred while fetching batch status.';
                    if (error && error.status && error.data && error.data.message) {
                        errorMessage = `Error ${error.status}: ${error.data.message}`;
                    } else if (error && error.status) {
                        errorMessage = `Error ${error.status}: Could not retrieve specific error message.`;
                    } else if (error && error.message) {
                        errorMessage = error.message;
                    }
                    errorDiv.text(errorMessage).show();
                })
                .finally(() => {
                    loadingDiv.hide();
                });
            });
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/finance/batch_history.blade.php ENDPATH**/ ?>