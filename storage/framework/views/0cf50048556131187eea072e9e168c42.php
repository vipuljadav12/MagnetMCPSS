<link rel="stylesheet" href="http://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<form action="<?php echo e(url('admin/Waitlist/store')); ?>" method="post" name="process_selection" id="process_selection">
    <?php echo e(csrf_field()); ?>


<div class="tab-pane fade show active" id="preview02" role="tabpanel" aria-labelledby="preview02-tab">
    <div class="">
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
        <div class="text-right"><?php if($display_outcome == 0): ?><input type="submit" class="btn btn-success d-none" title="Save Form" value="Save Form"><?php else: ?> <button type="button" class="btn btn-danger disabled" title="Save Form">Save Form</button><?php endif; ?></div>
    </div>
</div>
</form><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Waitlist/Views/Template/processing.blade.php ENDPATH**/ ?>