
<?php $__env->startSection('title'); ?>
Edit Permission
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Edit Permission</div>
        <div class="">
            <a href="<?php echo e(url('admin/Permission')); ?>" class="btn btn-success btn-sm" title="Back">Go Back</a>
        </div>
    </div>
</div>
<?php echo $__env->make('layouts.admin.common.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<form id="permissionSubmitForm" method="POST" action="<?php echo e(url('/admin/Permission/update/'.$data['permission']->id)); ?>">
    <?php echo e(csrf_field()); ?>

    <div class="card shadow">
        <div class="card-body">
             <div class="form-group">
                <label for="" class="">Slug Name *  </label>
                <div class="">
                    <input type="text" class="form-control" name="slug"  value="<?php echo e($data['permission']->slug ?? ''); ?>">
                    <?php if($errors->has('slug')): ?>
                    <div class="error col-sm-4 col-lg-8"><?php echo e($errors->first('slug')); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="" class="">Display Name * </label>
                <div class="">
                    <input type="text" class="form-control" name="display_name"  value="<?php echo e($data['permission']->display_name ?? ''); ?>">
                    <?php if($errors->has('display_name')): ?>
                    <div class="error col-sm-4 col-lg-8"><?php echo e($errors->first('display_name')); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="" class="">Module * </label>
                <div class="">
                    <select name="module_id" class="form-control custom-select">
                        <option value="">Select Module</option>
                        <?php $__currentLoopData = $data['module']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($module->id); ?>" <?php echo e(($module->id == $data['permission']->module_id) ? 'selected' : ''); ?>><?php echo e($module->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php if($errors->has('module_id')): ?>
                    <div class="error col-sm-4 col-lg-8"><?php echo e($errors->first('module_id')); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<div class="box content-header-floating" id="listFoot">
<div class="row">
        <div class="col-lg-12 text-right hidden-xs float-right">
            <button class="btn btn-warning btn-xs" type="submit" name="save" value="save"><i class="fa fa-save mr-2"></i>Save </button>
            <button class="btn btn-success btn-xs" type="submit" name="save_exit" value="save_exit"><i class="fa fa-save mr-2"></i>Save &amp; Exit</button>
                
            <a class="btn btn-danger btn-xs" href="<?php echo e(url('admin/Permission')); ?>">
                <i class="far fa-trash-alt"></i> Cancel
            </a> 
        </div>
</div>
</div>
</form>
<!-- InstanceEndEditable --> 
<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
<script type="text/javascript">
    $("#permissionSubmitForm").validate({
        
        // Specify validation rules for required
        rules: {
            slug: {required: true},
            display_name:{required:true},
            module_name:{required:true},         
        },
        // Specify validation error messages
        messages: {
            slug: {
                required: "Slug is required.",
            },
            display_name: {
                required: "Display Name is required.",
            },
            module_name: {
                required: "Module Name is required.",
            },
         
        },
        // Make sure the form is submitted to the destination defined
        submitHandler: function (form) {
            form.submit();
        }
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Permission/Views/edit.blade.php ENDPATH**/ ?>