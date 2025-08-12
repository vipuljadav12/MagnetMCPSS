
<?php $__env->startSection('title'); ?>
	Generate Contract Data Sheets
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
            <div class="page-title mt-5 mb-5">Generate Contract Sheets</div>
        </div>
    </div>

    
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item"><a class="nav-link active" id="new-tab" data-toggle="tab" href="#new" role="tab" aria-controls="new" aria-selected="false">Generate Contract Sheets</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo e(url('/admin/GenerateApplicationData/contract/generated')); ?>">Generated Contract Sheets Log</a></li>
    </ul>
    <div class="tab-content bordered" id="myTabContent">
        <div class="tab-pane fade show active" id="new" role="tabpanel" aria-labelledby="new-tab">
            <form class="" action="<?php echo e(url('/admin/GenerateApplicationData/contract/generate')); ?>" method="post" id="generateform">
                <?php echo e(csrf_field()); ?>

                <div class="form-group">
                    <label for="">Select Enrollment Year : </label>
                    <div class="">
                        <select class="form-control custom-select" id="enrollment" name="enrollment">
                            
                            <?php $__currentLoopData = $enrollment; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($value->id == Session::get("enrollment_id")): ?>
                                    <option value="<?php echo e($value->id); ?>" selected><?php echo e($value->school_year); ?></option>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="">Select Awarded Program : </label>
                    <div class="">
                        <select class="form-control custom-select" id="awarded_program" name="awarded_program" onchange="changeAwardGrade(this.value)">
                            <option value="">Select</option>
                            <option value="0">All Programs</option>
                            <?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value->name); ?>"><?php echo e($value->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="">Select Specific Grade : </label>
                    <div class="">
                        <select class="form-control custom-select" id="grade" name="grade">
                            <option value="">Select</option>
                            <option value="All">All Grades</option>
                            <?php $__currentLoopData = $grades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value->next_grade); ?>"><?php echo e($value->next_grade); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class=""><a href="javascript:void(0);" onclick="showReport()" title="" class="btn btn-secondary generate_report">Generate Contract Data Sheets</a></div>
            </form>
        </div>

    </div>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
	<script type="text/javascript">
        var arr = new Array();
		<?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            arr["<?php echo e($value->name); ?>"] = <?php echo e($value->id); ?>;
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        ;
        function showReport()
        {
            if($("#enrollment").val() == "")
            {
                alert("Please select enrollment year");
            }
            else if($("#first_program").val() == "" && $("#second_program").val() == "" && $("#awarded_program").val() == "")
            {
                alert("Please select program");
            }
            else if($("#grade").val() == "")
            {
                alert("Please select grade");
            }
            else if($("#status").val() == "")
            {
                alert("Please select status");
            }
            else
            {
                $("#generateform").submit();
            }
        }

       function changeGrade(value)
        {
            var val1 = $("#first_program").val();
            if(val1 == "")
                val1 = 0;
            var val2 = $("#second_program").val();
            if(val2 == "")
                val2 = 0;
            $.ajax({
                url:'<?php echo e(url('/admin/Submissions/get/grades/program/')); ?>/'+val1+'/'+val2,
                type:"get",
                async: false,
                success:function(response){
                    $('#grade').children('option').remove();
                    var data = JSON.parse(response);
                    $("#grade").append('<option value="All">All</option>');
                    for(i=0; i < data.length; i++)
                    {
                     
                         $("#grade").append('<option value="'+data[i].next_grade+'">'+data[i].next_grade+'</option>');
                    }
                    chk = response;
                }
            })
        }

        function changeAwardGrade(value)
        {
            var val1 = $("#awarded_program").val();
            if(val1 == "")
                val1 = 0;
            var val2 = 0;


            $.ajax({
                url:'<?php echo e(url('/admin/Submissions/get/grades/program/')); ?>/'+arr[val1]+'/'+val2,
                type:"get",
                async: false,
                success:function(response){
                    $('#grade').children('option').remove();
                    var data = JSON.parse(response);
                    $("#grade").append('<option value="All">All</option>');
                    for(i=0; i < data.length; i++)
                    {
                     
                         $("#grade").append('<option value="'+data[i].next_grade+'">'+data[i].next_grade+'</option>');
                    }
                    chk = response;
                }
            })
        }


	</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/GenerateApplicationData/Views/contract_index.blade.php ENDPATH**/ ?>