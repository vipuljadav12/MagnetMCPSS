
<?php $__env->startSection('title'); ?>
	Edit Users | <?php echo e(config('APP_NAME',env("APP_NAME"))); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Edit User</div>
        <div class=""><a href="<?php echo e(url('admin/Users/')); ?>" class="btn btn-sm btn-primary" title=""><i class="fa fa-arrow-left"></i> Back</a></div>
    </div>
</div>
<?php echo $__env->make('layouts.admin.common.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<form class="" id="UserForm" action="<?php echo e(url('admin/Users/update/'.$user->id)); ?>" method="post">
    <?php echo e(csrf_field()); ?>

    
    <div class="card shadow">
        <div class="card-body">
            <div class="form-group">
                <label for="" class="control-label">First Name : </label>
                <div class="">
                    <input type="text" class="form-control" value="<?php echo e($user->first_name ?? old("first_name")); ?>" name="first_name">
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
                    <input type="text" class="form-control" value="<?php echo e($user->last_name ?? old("last_name")); ?>" name="last_name">
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
                    <input type="email" class="form-control" value="<?php echo e($user->email ?? old("email")); ?>" name="email" disabled="">
                </div>
                <?php if($errors->has("email")): ?>
                    <div class="alert alert-danger m-t-5">
                       <?php echo e($errors->first('email')); ?>

                    </div>
                <?php endif; ?>
            </div>
            
            
            <div class="form-group">
                <label for="" class="control-label">User Type : </label>
                <div class="">
                    <select class="form-control custom-select" name="role_id">
                        <option value="">Select</option>
                        <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r=>$role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <option value="<?php echo e($role->id); ?>" <?php if($user->role_id == $role->id): ?> selected <?php endif; ?>><?php echo e(($role->name)); ?></option>
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
            <div class="form-group">
              <label class="">Change Password </label>
              <div class="">
                  <input type="checkbox" class="js-switch js-switch-1 js-switch-xs" id="changePassword" data-plugin="switchery" data-size="small"  data-color="#c82333"/>
              </div>
            </div>

            <div class="form-group changePassword">
              <label for="" class="">Password <span class="required">*</span> </label>
              <div class="">
                <input type="password" class="form-control" name="password" id="id_password" value="<?php echo e(old('password')); ?>" maxlength="20">
                <?php if($errors->has('password')): ?>
                <span class="help-block">
                  <strong><?php echo e($errors->first('password') ?? ''); ?></strong>
                </span>
                <?php endif; ?>
              </div>
            </div>
            <div class="form-group changePassword">
                <label for="" class="">Confirm Password  <span class="required">*</span> </label>
                <div class="">
                  <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" value="<?php echo e(old('password_confirmation')); ?>">
                  <?php if($errors->has('password_confirmation')): ?>
                  <span class="help-block">
                    <strong><?php echo e($errors->first('password_confirmation') ?? ''); ?></strong>
                  </span>
                  <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    
    <div class="box content-header-floating" id="listFoot">
        <div class="row">
            <div class="col-lg-12 text-right hidden-xs float-right">
                
                
                
               
                <button type="submit" class="btn btn-warning btn-xs" name="submit" value="Save"><i class="fa fa-save"></i> Save </button>
                   <button type="submit" name="save_exit" value="save_exit" class="btn btn-success btn-xs submit"><i class="fa fa-save"></i> Save &amp; Exit</button>
                   <a class="btn btn-danger btn-xs" href="<?php echo e(url('/admin/Users')); ?>"><i class="fa fa-times"></i> Cancel</a>
            </div>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
    
    
<script type="text/javascript">
     $(function(){
      $('.changePassword').css("display", "none");
  });

  $(document).on('change','#changePassword',function(){
      $('.changePassword').toggle();
  });

    var deletefunction = function(id){
        swal({
            title: "Are you sure you would like to move this User to trash?",
            text: "",
            // type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes",
            closeOnConfirm: false
        }).then(function() {
            window.location.href = '<?php echo e(url('/')); ?>/admin/Users/trash/'+id;
        });
    };
   /* $(function()
    {
        $(".submitBtn").on("click",function()
        {
            $("#UserForm").submit();
        });
    });*/
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
            role_id:{
                required:true,
            },
            password:{
                required:true,
                minlength:8
            },
            password_confirmation:{
                minlength:8,
                required: true,
                equalTo : "#id_password",

            },

        },
        messages:{
            first_name:{
                required: 'First Name is required.',
                maxlength:'The first name may not be greater than 255 characters.'
            },
            last_name:{
                required: 'Last Name is required.',
                maxlength:'The last name may not be greaterr than 255 characters.'
            },
            role_id:{
                required:'Please select User Type',
            }
        },errorPlacement: function(error, element)
        {
            error.appendTo( element.parents('.form-group'));
            error.css('color','red');
        },submitHandler:function(form){
            $("#UserForm").submit();
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
<?php echo $__env->make("layouts.admin.app", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Users/Views/edit.blade.php ENDPATH**/ ?>