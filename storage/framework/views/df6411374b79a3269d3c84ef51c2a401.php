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
            <div class="page-title mt-5 mb-5">Generate Applicant Data Sheets</div>
        </div>
    </div>

    
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link" href="<?php echo e(url('/admin/GenerateApplicationData')); ?>">Generate Application Data Sheets</a></li>
            <li class="nav-item"><a class="nav-link active" id="generated-tab" data-toggle="tab" href="#generated" role="tab" aria-controls="generated" aria-selected="true">Application Data Sheet Log</a></li>
        </ul>
          <div class="tab-content bordered" id="myTabContent">
                <div class="tab-pane fade show active" id="generated" role="tabpanel" aria-labelledby="generated-tab">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0" id="datatable">
                            <thead>
                                <tr>
                                    <th class="align-middle text-center">#</th>
                                    <th class="align-middle text-center">Enrollment Year</th>
                                    <th class="align-middle text-center">First Choice Program</th>
                                    <th class="align-middle text-center">Second Choice Program</th>
                                    <th class="align-middle text-center">Grade</th>
                                    <th class="align-middle text-center">Status</th>
                                    <th class="align-middle text-center">Total Data Sheets</th>
                                    <th class="align-middle text-center">Download</th>
                                    <th class="align-middle text-center">Generated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="text-center"><?php echo e($key+1); ?></td>
                                        <td class="text-center"><?php echo e($value->school_year); ?></td>
                                        <td class="text-center"><?php echo e(($value->first_program == 0 ? "All" : getProgramName($value->first_program))); ?></td>
                                        <td class="text-center"><?php echo e(($value->second_program == 0 ? "All" : getProgramName($value->second_program))); ?></td>
                                        <td class="text-center"><?php echo e($value->grade); ?></td>
                                        <td class="text-center"><?php echo e($value->status); ?></td>
                                        <td class="text-center"><?php echo e($value->total_records); ?></td>
                                        <td class="text-center"><a href="<?php echo e(url('/admin/GenerateApplicationData/download/'.$value->id)); ?>"><i class="fa fa-download  text-success"></i></a></td>
                                        <td class="text-center"><?php echo e(getDateTimeFormat($value->created_at)); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
   


    
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
	<script type="text/javascript">
		
        function showReport()
        {
            if($("#enrollment").val() == "")
            {
                alert("Please select enrollment year");
            }
            else if($("#program").val() == "")
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

	</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/GenerateApplicationData/Views/generated.blade.php ENDPATH**/ ?>