<?php $__env->startSection('title'); ?>Process Waitlist | <?php echo e(config('APP_NAME',env("APP_NAME"))); ?> <?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Process Waitlist</div>
        </div>
    </div>
    
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="preview02-tab" data-toggle="tab" href="#preview02" role="tab" aria-controls="preview02" aria-selected="true">Form Type</a></li>
            <?php if($displayother > 0): ?>
                 <li class="nav-item"><a class="nav-link" href="<?php echo e(url('/admin/Waitlist/Availability/Show')); ?>">All Programs</a></li>
                

                <li class="nav-item"><a class="nav-link" href="<?php echo e(url('/admin/Waitlist/Population/Form')); ?>">Population changes</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo e(url('/admin/Waitlist/Submission/Result')); ?>">Submission Result</a></li>
            <?php endif; ?>
        </ul>
        <div class="tab-content bordered" id="myTabContent">
            <?php echo $__env->make('Waitlist::Template.processing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="form-group" id="error_msg"></div>
        </div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
    <div id="wrapperloading" style="display:none;"><div id="loading"><i class='fa fa-spinner fa-spin fa-4x'></i> <br> Process is started.<br>It will take approx 15 minutes to finish. </div></div>

<script type="text/javascript">

     $('#process_selection').submit(function(event) {
        event.preventDefault();
        if($("#form_field").val() == "")
        {
            alert("Please select Form to proceed");
            return false;
        }
        document.location.href = "<?php echo e(url('/admin/Waitlist/Availability/Show/')); ?>/" + $("#form_field").val();

     });

    function rollBackStatus()
    {
        $("#wrapperloading").show();
        $.ajax({
            url:'<?php echo e(url('/admin/Waitlist/Revert/list')); ?>',
            type:"post",
            data: {"_token": "<?php echo e(csrf_token()); ?>"},
            success:function(response){
                alert("All Statuses Reverted.");
                document.location.href = "<?php echo e(url('/admin/Waitlist')); ?>";
                $("#wrapperloading").hide();

            }
        })
    }

    $("#form_field").change(function()
    {
        if($(this).val() != "")
        {
            $("#wrapperloading").show();
            $.ajax({
                url:'<?php echo e(url('admin/Waitlist/Process/Selection/validate/application/')); ?>/'+$(this).val(),
                type:"GET",
                success:function(response){
                    $("#wrapperloading").hide();
                    if(response != "OK")
                    {
                        $("#error_msg").html('<div class="alert1 alert-danger pl-20 pt-20"><ul>'+response+'</ul></div>');
                        $("#submit_btn").addClass("d-none");
                    }
                    else
                    {
                        $("#submit_btn").removeClass("d-none");
                        $("#error_msg").html("");
                    }
                    
                }
            })
        }
        else
        {
            $("#submit_btn").addClass("d-none");  
        }
    })

</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Waitlist/Views/index.blade.php ENDPATH**/ ?>