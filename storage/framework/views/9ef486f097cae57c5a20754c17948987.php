<link rel="stylesheet" href="http://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<form action="<?php echo e(url('admin/Process/Selection/store')); ?>" method="post" name="process_selection" id="process_selection">
    <?php echo e(csrf_field()); ?>


<div class="tab-pane fade show active" id="preview02" role="tabpanel" aria-labelledby="preview02-tab">
    <div class="">
        <?php if($display_outcome == 0): ?>
            <div class="form-group">
                <label for="">Select Application Form : </label>
                <div class="">
                    <select class="form-control custom-select" id="form_field" name="form_field">
                        <option value="">Select</option>
                        <?php $__currentLoopData = $forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value->id); ?>"><?php echo e($value->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>
        <div class="form-group d-none"><a href="javascript:void(0);" class="btn btn-secondary" title="">OR</a></div>
        <div class="form-group d-none">
            <label for="">Select Program : </label>
            <div class="">
                <select class="form-control custom-select" id="programs_select" name="programs_select">
                    <option value="">Select</option>
                    <?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value->id); ?>"><?php echo e($value->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-header">Acceptance Window</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-lg-6">
                        <label class="">Last day and time to accept ONLINE</label>

                        <div class="input-append date form_datetime">
                        <input class="form-control datetimepicker" name="last_date_online_acceptance" id="last_date_online_acceptance"  value="<?php echo e($last_date_online_acceptance); ?>" data-date-format="mm/dd/yyyy hh:ii">
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="">Last day and time to accept OFFLINE</label>
                        <div class="input-append date form_datetime"> <input class="form-control datetimepicker" name="last_date_offline_acceptance" id="last_date_offline_acceptance"  value="<?php echo e($last_date_offline_acceptance); ?>" data-date-format="mm/dd/yyyy hh:ii"></div>
                    </div>
                </div>    
            </div>
        </div>
        <div class="text-right"><?php if($display_outcome == 0): ?><input type="submit" class="btn btn-success" title="Process Submissions Now" value="Process Submissions Now"> <?php else: ?> <input type="button" class="btn btn-danger" disabled title="Process Submissions Now" value="Process Submissions Now"> <input type="button" class="btn btn-success disabled" title="Save Dates" value="Save Dates"> <?php endif; ?></div>
    </div>
</div>
</form><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/ProcessSelection/Views/Template/processing.blade.php ENDPATH**/ ?>