<?php $__env->startSection('title'); ?>
	Admin Review
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<style type="text/css">
    .alert1 {
    position: relative;
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
        border-top-color: transparent;
        border-right-color: transparent;
        border-bottom-color: transparent;
        border-left-color: transparent;
    border-radius: 0.25rem;
}
</style>
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Admin Review</div>
        </div>
    </div>
    
    <div class="card shadow">
        <?php echo $__env->make("Reports::display_report_options", ["selection"=>$selection, "enrollment"=>$enrollment, "enrollment_id"=>$enrollment_id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
	<script type="text/javascript">
        function showMissingReport()
        {
            var selection = "<?php echo e($selection); ?>";
            if($("#enrollment").val() == "")
            {
                alert("Please select enrollment year");
            }
            else if($("#reporttype").val() == "")
            {
                alert("Please select report type");
            }
            else
            {

                if(selection == "grade" || $("#reporttype").val() == "grade")
		     <?php if(isset($cgrade)): ?> 
                        var link = "<?php echo e(url('/')); ?>/admin/Reports/missing/"+$("#enrollment").val()+"/<?php echo e($cgrade); ?>/"+$("#reporttype").val();
		     <?php else: ?>
                        var link = "<?php echo e(url('/')); ?>/admin/Reports/missing/"+$("#enrollment").val()+"/"+$("#cgrade").val()+"/"+$("#reporttype").val();
		     <?php endif; ?>
		else
                     var link = "<?php echo e(url('/')); ?>/admin/Reports/missing/"+$("#enrollment").val()+"/"+$("#reporttype").val();
		

        
        document.location.href = link;
            }
        }

	function showHideGrade()
        {
		if($("#reporttype").val() == "grade")
			$("#cgradediv").show();
		else
			$("#cgradediv").hide();
        } 
	</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Reports/Views/admin_review_index.blade.php ENDPATH**/ ?>