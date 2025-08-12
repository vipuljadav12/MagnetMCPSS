<?php $__env->startSection("title"); ?>
	Set Availability | <?php echo e(config('APP_NAME',env("APP_NAME"))); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection("content"); ?>
<div class="content-wrapper-in">
	<!-- InstanceBeginEditable name="Content-Part" -->
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Set Availability</div>
            <!--<div class=""><a href="add-district.html" class="btn btn-sm btn-secondary" title="">Add District</a></div>-->
        </div>
    </div>
    <form class="" action="<?php echo e(url("admin/Availability/store")); ?>" method="post">
        <?php echo csrf_field(); ?>

        <div class="tab-content bordered" id="myTabContent">
            <div class="tab-pane fade show active" id="preview01" role="tabpanel" aria-labelledby="preview01-tab">
                <div class="">
                    <?php echo $__env->make("layouts.admin.common.alerts", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div class="form-group">
                        <select class="form-control custom-select selectProgram" name="program_id">
                            <option value="">Choose Option</option>
                            <?php $__empty_1 = true; $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p=>$program): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            	<option value="<?php echo e($program->id); ?>"><?php echo e($program->name ?? ""); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="AjaxContent">
	                    
                    </div>
                </div>
            </div>
        </div>
    </form>    
	<!-- InstanceEndEditable --> 
</div>

<?php $__env->stopSection(); ?>
<?php $__env->startSection("scripts"); ?>
<script type="text/javascript">
    $(function()
    {
        generateContent();
        var lastSelected = $(document).find(".selectProgram option:selected");
    });
    $(document).on("click",".selectProgram",function(event)
    {
        lastSelected = $(document).find(".selectProgram option:selected");
    });
	$(document).on("change",".selectProgram",function(event)
	{
        event.preventDefault();
        let checkChanged = $(document).find(".changed").length;
        if(checkChanged == 0)
        {
            generateContent();
        }
        else
        {
            event.preventDefault();
            lastSelected.prop("selected",true);
            swal("Please save current changes");
        }
	});
    function generateContent()
    {
        let selected = $(document).find(".selectProgram").val();
        $.ajax(
        {
            url:"<?php echo e(url('admin/Availability/getOptionsByProgram')); ?>"+"/"+selected,
            success:function(result)
            {
                $(document).find(".AjaxContent").html(result);
            }
        });
        console.log(selected);
        matchWithTotal();
    };
    function matchWithTotal()
    {
        $(document).find(".availableSeat").each(function()
        {
            var grade = $(this).attr("data-id");
            var value = $(this).val();
            var total = $(document).find(".totalSeat[data-id="+grade+"]").val();
            if(parseInt(value) > parseInt(total))
            {
                $(this).parent().find("label").removeClass("d-none");
                $(this).addClass("notAllowed");
            }
            else
            {
                $(this).parent().find("label").addClass("d-none");
                $(this).removeClass("notAllowed");
            }
        });
        // $(document).find(".notAllowed:first").focus();
    }
    $(document).on("change input",".availableSeat,.totalSeat",function()
    {
        matchWithTotal();
        $(this).addClass("changed");
    });
    $(document).on("click","#optionSubmit",function(event)
    {
        let checkNotAllowed = $(document).find(".notAllowed").length;
        // alert(checkNotAllowed);
        if(checkNotAllowed > 0)
        {
            swal("Please review all errors");
            $(document).find(".notAllowed:first").focus();

            event.preventDefault();
            return false;
        }
            // event.preventDefault();
        $(document).find(".notAllowed:first").focus();
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make("layouts.admin.app", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/SetAvailability/Views/index.blade.php ENDPATH**/ ?>