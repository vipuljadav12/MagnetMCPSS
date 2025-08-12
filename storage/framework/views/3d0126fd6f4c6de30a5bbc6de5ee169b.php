<?php $__env->startSection('title'); ?>Process Selection | <?php echo e(config('APP_NAME',env("APP_NAME"))); ?> <?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Process Selection</div>
        </div>
    </div>
    
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="preview02-tab" data-toggle="tab" href="#preview02" role="tab" aria-controls="preview02" aria-selected="true">Processing</a></li>
            <?php if($displayother > 0): ?>
                 <li class="nav-item"><a class="nav-link" href="<?php echo e(url('/admin/Process/Selection/Population')); ?>">Population Changes</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo e(url('/admin/Process/Selection/Results/Form')); ?>">Submissions Result</a></li>
            <?php endif; ?>
        </ul>
        <div class="tab-content bordered" id="myTabContent">
            <?php echo $__env->make('ProcessSelection::Template.processing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
    <div id="wrapperloading" style="display:none;"><div id="loading"><i class='fa fa-spinner fa-spin fa-4x'></i> <br> Process is started.<br>It will take approx 15 minutes to finish. </div></div>

<script type="text/javascript">
	/* DataTables start */
	$('#tbl_population_changes').DataTable();
	/* DataTables end */

    $("#form_field").change(function()
    {
        if($(this).val() != "")
        {
            $("#programs_select").val("");
        }
    })

    $("#programs_select").change(function()
    {
        if($(this).val() != "")
        {
            $("#form_field").val("");
        }
    })

     $('#process_selection').submit(function(event) {
        event.preventDefault();
            if($("#last_date_online_acceptance").val() == "")
            {
                alert("Please select Last date of online acceptance");
                return false;
            }

            if($("#last_date_offline_acceptance").val() == "")
            {
                alert("Please select Last date of offline acceptance");
                return false;
            }

            <?php if($display_outcome == 0): ?>
                if($("#form_field").val() == "" && $("#programs_select").val() == "")
                {
                    alert("Please select Program or Form to proceed");
                    return false;
                }
            <?php endif; ?>
            $("#wrapperloading").show();
            $.ajax({
                url:'<?php echo e(url('admin/Process/Selection/store')); ?>',
                type:"POST",
                data: {"_token": "<?php echo e(csrf_token()); ?>", "form_field": $("#form_field").val(), "programs_select": $("#programs_select").val(), "last_date_online_acceptance": $("#last_date_online_acceptance").val(), "last_date_offline_acceptance": $("#last_date_offline_acceptance").val()},
                success:function(response){
                    $("#wrapperloading").hide();
                    <?php if($display_outcome == 0): ?>
                        document.location.href = "<?php echo e(url('/admin/Process/Selection/Population/Form/')); ?>/" + $("#form_field").val();
                    <?php else: ?>
                        document.location.href = "<?php echo e(url('/admin/Process/Selection/Population/Form/')); ?>";
                    <?php endif; ?>

                }
            })

     });

    $("#last_date_online_acceptance").datetimepicker({
        numberOfMonths: 1,
        autoclose: true,
         startDate:new Date(),
        dateFormat: 'mm/dd/yy hh:ii'
    })

    $("#last_date_offline_acceptance").datetimepicker({
        numberOfMonths: 1,
        autoclose: true,
         startDate:new Date(),
        dateFormat: 'mm/dd/yy hh:ii'
    })

    function rollBackStatus()
    {
        $("#wrapperloading").show();
        $.ajax({
            url:'<?php echo e(url('/admin/Process/Selection/Revert/list')); ?>',
            type:"post",
            data: {"_token": "<?php echo e(csrf_token()); ?>"},
            success:function(response){
                alert("All Statuses Reverted.");
                document.location.href = "<?php echo e(url('/admin/Process/Selection')); ?>";
                $("#wrapperloading").hide();

            }
        })
    }


</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/ProcessSelection/Views/index.blade.php ENDPATH**/ ?>