
<?php $__env->startSection('title'); ?>
	Add Users | <?php echo e(config('APP_NAME',env("APP_NAME"))); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Add User</div>
        <div class=""><a href="<?php echo e(url('admin/Users/')); ?>" class="btn btn-sm btn-primary" title=""><i class="fa fa-arrow-left"></i> Back</a></div>
    </div>
</div>
<form class="" id="UserForm" action="<?php echo e(url('admin/Users')); ?>" method="post" >
    <?php echo e(csrf_field()); ?>

    <div class="card shadow">
        <div class="card-body">
            
            <div class="form-group">
                <label for="" class="control-label">First Name : </label>
                <div class="">
                    <input type="text" class="form-control" value="<?php echo e(old("first_name")); ?>" name="first_name">
                </div>
                <?php if($errors->has("first_name")): ?>
                    <div class="alert alert-danger m-t-5">
                       <?php echo e($errors->first('first_name')); ?>

                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="" class="control-label">Last Name : </label>
                <div class="">
                    <input type="text" class="form-control" value="<?php echo e(old("last_name")); ?>" name="last_name">
                </div>
                <?php if($errors->has("last_name")): ?>
                    <div class="alert alert-danger m-t-5">
                       <?php echo e($errors->first('last_name')); ?>

                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="" class="control-label">Email : </label>
                <div class="">
                    <input type="email" class="form-control" value="<?php echo e(old("email")); ?>" name="email" id="email">
                </div>
                <?php if($errors->has("email")): ?>
                    <div class="alert alert-danger m-t-5">
                       <?php echo e($errors->first('email')); ?>

                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="" class="control-label">Confirm Email : </label>
                <div class="">
                    <input type="email" class="form-control" value="<?php echo e(old("email_confirmation")); ?>" name="email_confirmation">
                </div>
                
            </div>
            <div class="form-group">
                <label for="" class="control-label">Plain Password : </label>
                <div class="">
                    <input type="text" class="form-control" value="<?php echo e(old("password")); ?>" name="password" >
                </div>
                <div class="small">To update a user's password, provide one here</div>
                <?php if($errors->has("password")): ?>
                    <div class="alert alert-danger m-t-5">
                       <?php echo e($errors->first('password')); ?>

                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="" class="control-label">User Type : </label>
                <div class="">
                    <select class="form-control custom-select" name="role_id">
                        <option value="">Select</option>
                        <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r=>$role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <option value="<?php echo e($role->id); ?>"><?php echo e(($role->name)); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if($errors->has("role_id")): ?>
                    <div class="alert alert-danger m-t-5">
                       <?php echo e($errors->first('role_id')); ?>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    

    <div class="box content-header-floating" id="listFoot">
        <div class="row">
            <div class="col-lg-12 text-right hidden-xs float-right">
                
                
                
                <button type="submit" class="btn btn-warning btn-xs submitBtn" >
                    <i class="fa fa-save"></i> Save
                </button>
                <button type="submit" class="btn btn-success btn-xs" name="save_edit" value="save_edit">
                    <i class="fa fa-save"></i> Save &amp; Edit
                </button>
            </div>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
    
    
<script type="text/javascript">
    jQuery.validator.addMethod( "email", function( value, element ) {
        return this.optional(element) || /^\w+@[a-zA-Z_]+?\.[a-zA-Z]{2,3}$/.test(value);
    }, "The email address is not valid" );
    $("#UserForm").validate({
        rules:{
            first_name:{
                required:true,
                maxlength:100,
            },
            last_name:{
                required:true,
                maxlength:100,
            },
            email:{
                required:true,
                email:true,
                maxlength:255,
                remote:{
                    url:'<?php echo e(url('admin/Users/uniqueemail')); ?>',
                    type:'get',
                    data:{

                    }
                }
            },
            email_confirmation:{
                required:true,
                equalTo:"#email",
            },
            password:{
                required:true,
                minlength: 8,
                maxlength:255,
            },
            role_id:{
                required:true,
            }

        },
        messages:{
            first_name:{
                required: 'First Name is required.',
                maxlength:'The first name may not be greater than 255 characters.'
            },
            last_name:{
                required: 'Last Name is required.',
                maxlength:'The last name may not be greater than 255 characters.'
            },
            email:{
                required: 'Email is required.',
                remote:'The email has already been taken.',
                maxlength:'The Email may not be greater than 255 characters.',
            },
            email_confirmation:{
                required:'Email Confirmation is required.',
                equalTo:"Email Confirmation is not match.",
            },
            password:{
                required:'Password is required.',
                minlength: "The password must be at least 8 characters long",
                maxlength:'The password may not be greater than 255 characters.',
            },
            role_id:{
                required:'Please select User Type.',
            }
        },errorPlacement: function(error, element)
        {
            error.appendTo( element.parents('.form-group'));
            error.css('color','red');
        },submitHandler:function(form){
            form.submit();
        }
    });
    $(document).on("click",".add-school",function()
    {
        var obj =  $(this).parent().clone();
        $(this).parent().after(obj);
        showHideBtn();
    });
    $(document).on("click",".remove-school",function()
    {
        $(this).parent().remove();
        showHideBtn();
    });
    function showHideBtn()
    {
        var count = $(".add-school").length;
        if(count > 1)
        {
            $(document).find(".remove-school").removeClass("d-none");
        }
        else
        {
            $(document).find(".remove-school").addClass("d-none");
        }
        // alert(count);
    }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make("layouts.admin.app", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Users/Views/create.blade.php ENDPATH**/ ?>