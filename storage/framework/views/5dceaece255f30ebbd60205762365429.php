

<?php $__env->startSection('title'); ?>
Add School | <?php echo e(config('APP_NAME',env("APP_NAME"))); ?>

<?php $__env->stopSection(); ?> 
<?php $__env->startSection('content'); ?>
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Add School Master</div>
        <div class="">
            <a href="<?php echo e(url('admin/School')); ?>" class="btn btn-sm btn-secondary" title="">Back</a>

        </div>
    </div>
</div>
<form action="<?php echo e(url('admin/School/store')); ?>" method="post" id="addschool">
    <?php echo e(csrf_field()); ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="form-group">
                <label for="">School Name : </label>
                <div class="">
                    <input type="text" id="name" class="form-control" value="<?php echo e(old("name")); ?>" name="name">
                </div>
                <?php if($errors->any()): ?>
                <div class="text-danger">
                    <ul>
                        <li><?php echo e($errors->first('name')); ?></li>
                    </ul>
                </div>
                <?php endif; ?>    
            </div>
            <div class="form-group">
                <label for="">Grade Levels : </label>
                <div class="row flex-wrap grade">
                    <?php $__currentLoopData = $grades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grade): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-12 col-sm-4 col-lg-2  ">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" value="<?php echo e($grade->name); ?>" name="grade[]" id="<?php echo e($grade->id); ?>">
                            <label for="<?php echo e($grade->id); ?>" class="custom-control-label"><?php echo e($grade->name); ?></label>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                </div>
                <?php if($errors->any()): ?>
                <div class="text-danger">
                    <ul>
                        <li><?php echo e($errors->first('grade')); ?></li>
                    </ul>
                </div>
                <?php endif; ?>    
            </div>
            <div class="form-group">
                <label for="" class="control-label">Magnet : </label>
                <div class="">
                    <select class="form-control custom-select" name="magnet">
                        <option value="">Select</option>
                        <option value="Yes" <?php echo e(old('magnet')=='Yes'?'selected':''); ?>>Yes</option>
                        <option value="No" <?php echo e(old('magnet')=='No'?'selected':''); ?>>No</option>
                    </select>
                </div>
                <?php if($errors->any()): ?>
                <div class="text-danger">
                    <ul>
                        <li><?php echo e($errors->first('magnet')); ?></li>
                    </ul>
                </div>
                <?php endif; ?>    
            </div>
            <div class="form-group">
                <label for="">Zoning API Name if different : </label>
                <div class="">
                    <input type="text" class="form-control" value="<?php echo e(old("zoning_api_name")); ?>" name="zoning_api_name">
                </div>
                <?php if($errors->any()): ?>
                <div class="text-danger">
                    <ul>
                        <li><?php echo e($errors->first('zoning_api_name')); ?></li>
                    </ul>
                </div>
                <?php endif; ?>    
            </div>
            <div class="form-group">
                <label for="">SIS Name if different : </label>
                <div class="">
                    <input type="text" class="form-control" value="<?php echo e(old("sis_name")); ?>" name="sis_name">
                </div>
                <?php if($errors->any()): ?>
                <div class="text-danger">
                    <ul>
                        <li><?php echo e($errors->first('sis_name')); ?></li>
                    </ul>
                </div>
                <?php endif; ?>    
            </div>
            <div class="box content-header-floating" id="listFoot">
                <div class="row">
                    <div class="col-lg-12 text-right hidden-xs float-right">
                        <button type="submit" class="btn btn-warning btn-xs submitBtn" >
                            <i class="fa fa-save"></i> Save
                        </button> 
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script src="<?php echo e(asset('resources/assets/admin/js/additional-methods.min.js')); ?>"></script>

<script type="text/javascript">
    $(document).ready(function(){

$('#addschool').validate({ // initialize the plugin

    ignore:[],
    rules: {
        name: {
            required: true,
            maxlength:255,
            remote:{
                url:"<?php echo e(url('admin/School/checkunique')); ?>",
                type:"get",
                data:{
                    name:function()
                    {
                        return jQuery('#name').val();
                    }
                }

            }
        },
        'grade[]':
        {
            required:true,
        },
        magnet:
        {
            required:true,
        },
       /* zoning_api_name:
        {
            required:true,
            maxlength:255
        },
        sis_name:
        {
            required:true,
            maxlength:255
        },*/
    },
    messages:
    {
        name:
        {
            required: 'The Name field is required.',
            maxlength:'The Name may not be greater than 100 characters.',
            remote:'The Name has already been taken.',
        },
        'grade[]':
        {
            required:"The Grade field is required."
        },
        magnet:
        {
            required:"The Magnet field is required"
        },
        zoning_api_name:
        {
            required:'The Zoning API name field is required.',
            maxlength:'The Zoning API name name may not be greater than 255 characters.'
        },
        sis_name:
        {
           required:'The SIS name field is required.',
           maxlength:'The SIS name name may not be greater than 255 characters.'
       }
   },
   errorPlacement: function(error, element) 
   {
    if ( element.is(":checkbox") ) 
    {
        error.appendTo( element.parents('.form-group'));

    }
    else 
    { 
        error.insertAfter( element );
    }
    error.css('color','red');
},
submitHandler: function(form) {
    form.submit();
}
});
});    


</script> 
<?php $__env->stopSection(); ?>
<?php echo $__env->make("layouts.admin.app", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/School/Views/create.blade.php ENDPATH**/ ?>