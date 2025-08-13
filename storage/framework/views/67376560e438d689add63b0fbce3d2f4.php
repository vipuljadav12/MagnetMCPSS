
<?php $__env->startSection('title'); ?>
Edit Text | <?php echo e(config('APP_NAME',env("APP_NAME"))); ?>

<?php $__env->stopSection(); ?> 
<?php $__env->startSection('content'); ?>
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Edit Text</div>
        <div class="">
            <a href="<?php echo e(url('admin/Configuration')); ?>" class="btn btn-sm btn-secondary" title="Go Back">Go Back</a>

        </div>
    </div>
</div>
<?php echo $__env->make("layouts.admin.common.alerts", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<form action="<?php echo e(url('admin/Configuration/update',$configuration->id)); ?>" method="post" id="editTranslation">
    <?php echo e(csrf_field()); ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="form-group">
                <label for="">Short Code : </label>
                <div class="">
                     <input type="text" id="config_name" class="form-control" value="<?php echo e($configuration->config_name); ?>" name="config_name" readonly="readonly"> 
                    
                </div>
                <?php if($errors->any()): ?>
                <div class="text-danger">
                  <strong><?php echo e($errors->first('config_name')); ?></strong>
                </div>

                <?php endif; ?>    
            </div>
 
            <div class="form-group">
                <label for="">Text Description : </label>
                <div class="">
                    <textarea class="form-control" name="config_value" id="config_value"><?php echo e($configuration->config_value ?? ''); ?></textarea>
                </div>
                <?php if($errors->any()): ?>
                <div class="text-danger">
                  <strong><?php echo e($errors->first('config_value')); ?></strong>
                </div>
                <?php endif; ?> 
            </div>
            <div class="box content-header-floating" id="listFoot">
                <div class="row">
                    <div class="col-lg-12 text-right hidden-xs float-right">
                        <button type="submit" class="btn btn-warning btn-xs submitBtn" name="save"  value="save">
                            <i class="fa fa-save"></i> Save
                        </button>
                        <button type="submit" class="btn btn-success btn-xs" name="save_exit" value="save_exit">
                            <i class="fa fa-save"></i> Save &amp; Exit</button>
                        <a class="btn btn-danger btn-xs" href="<?php echo e(url('/admin/Configuration')); ?>"><i class="fa fa-times"></i> Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script type="text/javascript" src="<?php echo e(url('/')); ?>/resources/assets/admin/plugins/laravel-ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="<?php echo e(url('/resources/assets/admin/plugins/laravel-ckeditor/adapters/jquery.js')); ?>"></script>
<script type="text/javascript" src="<?php echo e(url('/')); ?>/resources/assets/admin/js/additional-methods.min.js"></script>
<script type="text/javascript">
 CKEDITOR.replace('config_value');
 $.validator.addMethod(
    "regex",
    function(value, element, regexp) {
      return this.optional(element) || regexp.test(value);
    },      
    "Invalid number."
  );
   $("#editTranslation").validate({
    ignore: [],
    rules: {
      config_name:{
        required: true
      },
      config_value:{
      required: function(textarea) {
       CKEDITOR.instances[textarea.id].updateElement();
       var editorcontent = textarea.value.replace(/<[^>]*>/gi, '');
       return editorcontent.length === 0;
       }
      },
    }, 
    messages:{
    config_name:{
      required:"Description is required.",
    },
    config_value:{
      required:"Text is required.",
    },
  },
  errorPlacement: function(error, element)
    {
    error.appendTo( element.parents('.form-group'));
    error.css('color','red');
    }, 
  submitHandler: function(form){
   form.submit();
 }
});  

</script> 
<?php $__env->stopSection(); ?>
<?php echo $__env->make("layouts.admin.app", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Configuration/Views/edit.blade.php ENDPATH**/ ?>