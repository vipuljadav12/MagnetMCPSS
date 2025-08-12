<?php $__env->startSection('title'); ?>
	Generate Application Data Sheets
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
            <div class="page-title mt-5 mb-5">Generate Application Data Sheets</div>
        </div>
    </div>

  
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="new-tab" data-toggle="tab" href="#new" role="tab" aria-controls="new" aria-selected="false">Generate Application Data Sheets</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo e(url('/admin/GenerateApplicationData/generated')); ?>">Application Data Sheet Log</a></li>
        </ul>
            <div class="tab-content bordered" id="myTabContent">
                <div class="tab-pane fade show active" id="new" role="tabpanel" aria-labelledby="new-tab">
                    <form class="" action="<?php echo e(url('/admin/GenerateApplicationData/generate')); ?>" method="post" id="generateform">
                        <?php echo e(csrf_field()); ?>

                        <div class="form-group">
                            <label for="">Select Enrollment Year : </label>
                            <div class="">
                                <select class="form-control custom-select" id="enrollment" name="enrollment" onchange="changeProgram(this.value)">
                                    <option value="">Select</option>
                                    <?php $__currentLoopData = $enrollment; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($value->id == Session::get("enrollment_id")): ?>
                                            <option value="<?php echo e($value->id); ?>"><?php echo e($value->school_year); ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="">Select First Program of Choice : </label>
                            <div class="">
                                <select class="form-control custom-select program_list" id="first_program" name="first_program" onchange="changeGrade(this.value)">
                                    <option value="">Select</option>
                                    <option value="0">All Programs</option>
                                    <?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($value->id); ?>" data-value="<?php echo e($value->enrollment_id); ?>"><?php echo e($value->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="">Select Second Program of Choice : </label>
                            <div class="">
                                <select class="form-control custom-select program_list" id="second_program" name="second_program" onchange="changeGrade(this.value)">
                                    <option value="">Select</option>
                                    <option value="0">All Programs</option>
                                    <?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($value->id); ?>"><?php echo e($value->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="">Select Awarded Program : </label>
                            <div class="">
                                <select class="form-control custom-select program_list" id="awarded_program" name="awarded_program" onchange="changeAwardGrade(this.value)">
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
                        <div class="form-group">
                            <label for="">Select Status : </label>
                            <div class="">
                                <select class="form-control custom-select" id="status" name="status">
                                    <option value="">Select</option>
                                    <option value="All" class="d-none">All</option>
                                    <?php $__currentLoopData = $submission_status; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($value->submission_status); ?>"><?php echo e($value->submission_status); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                        <div class=""><a href="javascript:void(0);" onclick="showReport()" title="" class="btn btn-secondary generate_report">Generate Application Data Sheets</a></div>
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

        function changeProgram(value){

            if(value == "")
                var value = 0

            $.ajax({
                url:'<?php echo e(url('/admin/Program/get/program/')); ?>/'+value,
                type:"get",
                async: false,
                success:function(response){
                    $('.program_list').children('option').remove();
                    var data = JSON.parse(response);

                    if(data.length > 0)
                        $(".program_list").append('<option value="0">All</option>');
                    else
                        $(".program_list").append('<option value="">Select</option>');

                    for(i=0; i < data.length; i++)
                    {
                        $(".program_list").append('<option value="'+data[i].id+'">'+data[i].name+'</option>');
                    }
                }
            })
        }


	</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/GenerateApplicationData/Views/index.blade.php ENDPATH**/ ?>