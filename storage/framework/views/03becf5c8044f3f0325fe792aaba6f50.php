
<?php $__env->startSection('title'); ?> Edit User Role <?php $__env->stopSection(); ?>
<?php $__env->startSection("styles"); ?>
<style type="text/css">
    .error
    {
        color:#721c24;
    }
</style>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Edit User Role</div>
        <div class=""><a href="<?php echo e(url('admin/Role')); ?>" class="btn btn-success btn-sm" title="Back"> Go Back</a></div>
    </div>
</div>
<?php echo $__env->make('layouts.admin.common.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<form method="POST" action="<?php echo e(url('/admin/Role/update')); ?>" id="roleSubmitForm">
    <?php echo e(csrf_field()); ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="form-group">
                <label class="control-label">User Role *</label>
                <div class="">
                    <input type="hidden" name="id" value="<?php echo e($data['role']->id); ?>">
                    <input type="text" name="name" id="role" class="form-control" value="<?php echo e($data['role']->name); ?>">
                    <?php if($errors->has('name')): ?>
                    <div class="error col-sm-4 col-lg-8"><?php echo e($errors->first('name')); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if(isset($data['permission'])): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <?php $__currentLoopData = $data['display_name']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if(isset($permission)): ?>
                            <tr>
                                <td class="b-600">
                                    <input name="" type="checkbox" class="checkAll" data-size="Small"  value="Y" id="<?php echo e("all".$key); ?>">
                                    <label for="<?php echo e("all".$key); ?>"><?php echo e(" ".ucfirst($key)); ?> </label>
                                </td>
                                <?php $__currentLoopData = $permission; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td class="text-center">
                                    <label class="" style="height: auto;"><?php echo e(\App\Modules\Module\Models\Module::Show($permission)->display_name ?? \App\Modules\Module\Models\Module::Show($permission)->name); ?></label>
                                    <div>
                                        <input type="checkbox" name="permission[<?php echo e($key); ?>]" class="js-switch js-switch-1 js-switch-xs checkboxSingle" data-size="Small" value="Y" <?php echo e((isset($data["permission_id"]) && in_array($key, $data["permission_id"])) ? 'checked' : ''); ?>>
                                    </div>
                                </td>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        
                    </tbody>
                </table>
            </div>
             <?php endif; ?>
        </div>
    </div>
<div class="box content-header-floating" id="listFoot">
<div class="row">
        <div class="col-lg-12 text-right hidden-xs float-right">
            <button class="btn btn-warning btn-xs" type="submit" name="save" value="save"><i class="fa fa-save mr-2"></i>Save </button>
            <button class="btn btn-success btn-xs" type="submit" name="save_exit" value="save_exit"><i class="fa fa-save mr-2"></i>Save &amp; Exit</button>
                
            <a class="btn btn-danger btn-xs" href="<?php echo e(url('admin/Role')); ?>">
                <i class="far fa-trash-alt"></i> Cancel
            </a> 
        </div>
</div>
</div>
</form>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script type="text/javascript">
$(function()
{
    adjCheckbox();
});
$(document).on("change",".checkboxSingle",function()
{
    adjCheckbox();
});
function adjCheckbox()
{
    $(document).find(".checkboxRow").each(function(){
        var numberofcheckbox = $(this).find(".checkboxSingle").length;
        var checkedLength = $(this).find(".checkboxSingle:checked").length;
        // console.log(numberofcheckbox+" -- "+checkedLength);
        var switchry = new Switchery( $(this).find(".checkAll"), { size: 'small' });
        if(numberofcheckbox == checkedLength)
        {
            // $(this).find(".checkAll").prop("checked",true).change("no");
            $(this).find(".checkAll").prop("checked",true);
            // switchry.handleOnchange(true);   
        }
        else{
            $(this).find(".checkAll").prop("checked",false);
            // switchry.handleOnchange(false);  
        }
    });
}
    $(document).on("change",".checkAll",function(){

        let checked = $(this).prop("checked");
        // console.log( $(this).parent().parent().find(".checkboxSingle"));
        $(this).parent().parent().find(".checkboxSingle").prop("checked",(checked = !checked)).trigger("click");
        // $(this).parent().parent().find(".checkboxSingle").trigger("click");
    });
    $("#roleSubmitForm").validate({
        
        rules: {
            role: {required: true},
        },
        messages: {
            role: {
                required: "User Role is required.",
            },
         
        },
        submitHandler: function (form) {
            form.submit();
        }
    });
    
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Role/Views/edit.blade.php ENDPATH**/ ?>