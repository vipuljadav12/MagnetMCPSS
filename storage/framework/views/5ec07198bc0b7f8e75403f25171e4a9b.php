
<?php $__env->startSection('title'); ?> District Configuration | <?php echo e(config('APP_NAME',env("APP_NAME"))); ?> <?php $__env->stopSection(); ?>
<?php $__env->startSection('styles'); ?>
<style type="text/css">
    .error {
        color: red;
    }
</style>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">District Configuration</div>
    </div>
</div>

<?php echo $__env->make("layouts.admin.common.alerts", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<form id="frm_index" action="<?php echo e(url('admin/DistrictConfiguration/store')); ?>" method="post" enctype= "multipart/form-data">
<?php echo e(csrf_field()); ?>

    <div class="card shadow">
        <div class="card-body">

            <div class="form-group">
                <label class="control-label">Letter Signature : </label>
                <div class="row">
                    <div class="col-md-11">
                         <textarea class="form-control" id="editor00" name="letter_signature">
                            <?php echo ($old_letter_signature_value != '' ? $old_letter_signature_value : ''); ?>

                        </textarea>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label">Email Signature : </label>
                <div class="row">
                    <div class="col-md-11">
                         <textarea class="form-control" id="editor01" name="email_signature">
                            <?php echo ($old_email_signature_value ? $old_email_signature_value : ''); ?>

                        </textarea>
                    </div>
                </div>
            </div>

        </div>
    </div>
            <div class="box content-header-floating" id="listFoot">
            <div class="row">
                <div class="col-lg-12 text-right hidden-xs float-right">
                    <button type="Submit" class="btn btn-warning btn-xs submit"><i class="fa fa-save"></i> Save </button>
                    <a class="btn btn-danger btn-xs" href="<?php echo e(url('/admin/DistrictConfiguration')); ?>"><i class="fa fa-times"></i> Cancel</a>
                </div>
            </div>
        </div>

</form>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script type="text/javascript" src="<?php echo e(url('/')); ?>/resources/assets/admin/plugins/laravel-ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="<?php echo e(url('/resources/assets/admin/plugins/laravel-ckeditor/adapters/jquery.js')); ?>"></script>

<script type="text/javascript"> 


   /* jQuery.validator.addMethod("imageDimension", function(value, element,options) {
        var myImg = document.querySelector("#email_signature_thumb");
        var realWidth = myImg.naturalWidth;
        var realHeight = myImg.naturalHeight;

        if(realWidth > 500 || realHeight > 500){
            return false;
        }else{
            return true;
        }
     }, "");


    $('input[name="signature"]').change(function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#signature_thumb')
                    .attr('src', e.target.result)
            };
            reader.readAsDataURL(this.files[0]);
        }
    });*/


   /* $('#frm_index').validate({
        rules: {
            letter_signature: {
                imageDimension: true,
                // required: true,
                extension: 'png,jpg,gif'
            },
            email_signature: {
                imageDimension: true,
                // required: true,
                extension: 'png,jpg,gif'
            }
        },
        messages: {
            letter_signature: {
                imageDimension: 'Maximum image dimensions are 500x500.',
                required: 'Signature Image File is required.',
                extension: 'Signature Image File is the file of type .png/.jpg/.gif'
            },
            email_signature: {
                imageDimension: 'Maximum image dimensions are 500x500.',
                required: 'Signature Image File is required.',
                extension: 'Signature Image File is the file of type .png/.jpg/.gif'
            }

        } 
    });*/
        CKEDITOR.replace('editor00',{
             filebrowserImageBrowseUrl: '<?php echo e(url("/")); ?>/resources/assets/admin/plugins/laravel-ckeditor/imageBrowser.php?path=<?php echo e(url("/")); ?>',
            filebrowserBrowseUrl: '<?php echo e(url("/")); ?>/resources/assets/admin/plugins/laravel-ckeditor/imageupload.php?type=Files',
            filebrowserUploadUrl: '<?php echo e(url("/")); ?>/resources/assets/admin/plugins/laravel-ckeditor/imageupload.php?command=QuickUpload&type=Files',
            filebrowserWindowWidth: (screen.width/1.5),
            filebrowserWindowHeight: (screen.height/1.5),
        });
        CKEDITOR.replace('editor01', {
             filebrowserImageBrowseUrl: '<?php echo e(url("/")); ?>/resources/assets/admin/plugins/laravel-ckeditor/imageBrowser.php?path=<?php echo e(url("/")); ?>',
            filebrowserBrowseUrl: '<?php echo e(url("/")); ?>/resources/assets/admin/plugins/laravel-ckeditor/imageupload.php?type=Files',
            filebrowserUploadUrl: '<?php echo e(url("/")); ?>/resources/assets/admin/plugins/laravel-ckeditor/imageupload.php?command=QuickUpload&type=Files',
            filebrowserWindowWidth: (screen.width/1.5),
            filebrowserWindowHeight: (screen.height/1.5),
        });

</script> 
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/DistrictConfiguration/Views/index.blade.php ENDPATH**/ ?>