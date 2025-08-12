<?php $__env->startSection('title'); ?>
	Report Master
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
.dt-buttons {position: absolute !important;}
.w-50{width: 50px !important}
.content-wrapper.active {z-index: 9999 !important}
</style>
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Report</div>
        </div>
    </div>
    <div class="">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item"><a class="nav-link active" id="needs1-tab" data-toggle="tab" href="#needs1" role="tab" aria-controls="needs1" aria-selected="true">Application Process Report</a></li>
            </ul>
            <div class="tab-content bordered" id="myTabContent">
                <div class="tab-pane fade show active" id="needs1" role="tabpanel" aria-labelledby="needs1-tab">
                    <ul class="nav nav-tabs" id="myTab1" role="tablist1">
                        <?php $__currentLoopData = $gradeTab; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($value==$existGrade): ?>
                                <li class="nav-item"><a class="nav-link active" id="grade1-tab" data-toggle="tab" href="#grade1" role="tab" aria-controls="grade1" aria-selected="true">Grade <?php echo e($value); ?></a></li>
                            <?php else: ?>
                                <li class="nav-item"><a class="nav-link" href="<?php echo e(url('admin/Reports/'.$value)); ?>">Grade <?php echo e($value); ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                    <div class="tab-content bordered" id="myTabContent1">
                        <div class="tab-pane fade show active" id="grade1" role="tabpanel" aria-labelledby="grade1-tab">
                            <div class="">
                                <div class="card shadow">
                                    <div class="card-body">
                                        <div class="text-right mb-10 d-flex justify-content-end align-items-center">
                                            <input type="checkbox" class="js-switch js-switch-1 js-switch-xs status" data-size="Small"  id="hideRace" <?php if($settings->race == "Y"): ?> checked <?php endif; ?> />&nbsp;Hide Race&nbsp;&nbsp;<input type="checkbox" class="js-switch js-switch-1 js-switch-xs status" data-size="Small"  id="hideZone" <?php if($settings->zoned_school == "Y"): ?> checked <?php endif; ?> />&nbsp;Hide Zone School&nbsp;&nbsp;<input type="checkbox" class="js-switch js-switch-1 js-switch-xs status" data-size="Small"  id="hideCDI" <?php if($settings->cdi == "Y"): ?> checked <?php endif; ?>/>&nbsp;Hide CDI&nbsp;&nbsp;<input type="checkbox" class="js-switch js-switch-1 js-switch-xs status" data-size="Small"  id="hideGrade" <?php if($settings->grade == "Y"): ?> checked <?php endif; ?> />&nbsp;Hide Grade
                                            <div class="d-none" style="padding-left: 5px;"><a href="<?php echo e(url('/CDI-All.xls')); ?>" class="btn btn-secondary">Export</a></div>
                                        </div>
                                        <?php $config_subjects = Config::get('variables.subjects') ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped mb-0 w-100" id="datatable">
                                                <thead>
                                                    <tr>
                                                        <th class="align-middle text-center">Sub ID</th>
                                                        <th class="align-middle text-center">Submission Status</th>
                                                        <th class="align-middle hiderace text-center">Race</th>
                                                        <th class="align-middle text-center">Student Status</th>
                                                        <th class="align-middle text-center">First Name</th>
                                                        <th class="align-middle text-center">Last Name</th>
                                                        <th class="align-middle text-center">MCPSS Employee</th>
                                                        <th class="align-middle text-center">Next Grade</th>
                                                        <th class="align-middle text-center">Current School</th>
                                                        <th class="align-middle hidezone text-center">Zoned School</th>
                                                        <th class="align-middle text-center">First Choice</th>
                                                        <th class="align-middle text-center">Second Choice</th>
                                                        <th class="align-middle text-center">Sibling ID</th>
                                                        <th class="align-middle text-center">Lottery Number</th>
                                                        <th class="align-middle text-center">Priority</th>
                                                        <?php $__currentLoopData = $subjects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sbjct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <?php $__currentLoopData = $terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <th class="align-middle grade-col text-center"><?php echo e($config_subjects[$sbjct]); ?> <?php echo e($term); ?></th>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        <th class="align-middle cdi-col text-center">B Info</th>
                                                        <th class="align-middle cdi-col text-center">C Info</th>
                                                        <th class="align-middle cdi-col text-center">D Info</th>
                                                        <th class="align-middle cdi-col text-center">E Info</th>
                                                        <th class="align-middle cdi-col text-center">Susp</th>
                                                        <th class="align-middle cdi-col text-center"># Days Susp</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $__currentLoopData = $firstdata; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <tr>
                                                            <td class=""><?php echo e($value['id']); ?></td>
                                                            <td class="text-center"><?php echo e($value['submission_status']); ?></td>
                                                            <td class="hiderace"><?php echo e($value['race']); ?></td>
                                                            <td class="">
                                                                <?php if($value['student_id'] != ''): ?>
                                                                    Current
                                                                <?php else: ?>
                                                                    New
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class=""><?php echo e($value['first_name']); ?></td>
                                                            <td class=""><?php echo e($value['last_name']); ?></td>
                                                            <td class="text-center">
                                                                <?php if($value['magnet_employee'] == "Yes"): ?>
                                                                    <?php if($value['magnet_program_employee'] == "Y"): ?>
                                                                        <div class="alert1 alert-success p-10 text-center">Yes</div>
                                                                    <?php else: ?>
                                                                         <div class="alert1 alert-danger p-10 text-center">No</div>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                        -
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center"><?php echo e($value['next_grade']); ?></td>
                                                            <td class=""><?php echo e($value['current_school']); ?></td>
                                                            <td class="hidezone"><?php echo e($value['zoned_school']); ?></td>
                                                            <td class=""><?php echo e($value['first_program']); ?></td>
                                                            <td class="text-center"><?php echo e($value['second_program']); ?></td>
                                                            <td class="">
                                                                <?php if($value['first_sibling'] != ''): ?>
                                                                    <div class="alert1 alert-success p-10 text-center"><?php echo e($value['first_sibling']); ?></div>
                                                                <?php else: ?>
                                                                    <div class="alert1 alert-warning p-10 text-center">NO</div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class=""><?php echo e($value['lottery_number']); ?></td>
                                                            <td class="text-center">
                                                                <div class="alert1 alert-success">
                                                                    <?php echo e($value['rank']); ?>

                                                                </div>
                                                            </td>
                                                            <?php $__currentLoopData = $value['score']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skey=>$sbjct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <?php $__currentLoopData = $terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <td class="grade-col text-center">
                                                                        <?php if(isset($sbjct[$term])): ?>
                                                                            <?php if($sbjct[$term] != ""): ?>
                                                                                <?php if(isset($setEligibilityData[$value['first_choice']][$skey.'-'.$term])): ?>
                                                                                    <?php if($value['grade_status'] == "Pass"): ?>
                                                                                        <?php $class = "alert1 alert-success" ?>
                                                                                    <?php else: ?>
                                                                                        <?php $class = "alert1 alert-danger" ?>
                                                                                    <?php endif; ?>
                                                                                <?php else: ?>
                                                                                        <?php $class = "alert1 alert-warning" ?>
                                                                                <?php endif; ?>
                                                                                <div class="<?php echo e($class); ?>">
                                                                                    <?php echo e($sbjct[$term]); ?>

                                                                                </div>
                                                                            <?php endif; ?>
                                                                        <?php else: ?>
                                                                            <?php echo e("NA"); ?>

                                                                        <?php endif; ?>
                                                                    </td>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                                            <?php $__currentLoopData = $value['cdi']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vkey=>$vcdi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                                                <td class="cdi-col text-center">

                                                                        <?php if($value['cdi'][$vkey] != "" || $value['cdi'][$vkey] == 0): ?>
                                                                            <?php if(isset($setCDIEligibilityData[$value['first_choice']][$vkey])): ?>
                                                                                <?php if($value['cdi_status'] == "Pass"): ?>
                                                                                     <?php $class = "alert1 alert-success" ?>
                                                                                <?php else: ?>
                                                                                     <?php $class = "alert1 alert-danger" ?>
                                                                                 <?php endif; ?>
                                                                            <?php else: ?>
                                                                                <?php $class = "alert1 alert-warning" ?>
                                                                            <?php endif; ?>
                                                                            <div class="<?php echo e($class); ?>">
                                                                                <?php echo e($value['cdi'][$vkey]); ?>

                                                                            </div>
                                                                        
                                                                        <?php endif; ?>                                                                    
                                                                </td>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </tr>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php $__currentLoopData = $seconddata; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <tr>
                                                            <td class=""><?php echo e($value['id']); ?></td>
                                                            <td class="text-center"><?php echo e($value['submission_status']); ?></td>
                                                            <td class="hiderace"><?php echo e($value['race']); ?></td>
                                                            <td class="">
                                                                <?php if($value['student_id'] != ''): ?>
                                                                    Current
                                                                <?php else: ?>
                                                                    New
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class=""><?php echo e($value['first_name']); ?></td>
                                                            <td class=""><?php echo e($value['last_name']); ?></td>
                                                             <td class="text-center">
                                                                <?php if($value['magnet_employee'] == "Yes"): ?>
                                                                    <?php if($value['magnet_program_employee'] == "Y"): ?>
                                                                        <div class="alert1 alert-success p-10 text-center">Yes</div>
                                                                    <?php else: ?>
                                                                         <div class="alert1 alert-danger p-10 text-center">No</div>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                        -
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center"><?php echo e($value['next_grade']); ?></td>
                                                            <td class=""><?php echo e($value['current_school']); ?></td>
                                                            <td class="hidezone"><?php echo e($value['zoned_school']); ?></td>
                                                            <td class=""><?php echo e($value['first_program']); ?></td>
                                                            <td class="text-center"><?php echo e($value['second_program']); ?></td>
                                                            <td class="">
                                                                <?php if($value['second_sibling'] != ''): ?>
                                                                    <div class="alert1 alert-success p-10 text-center"><?php echo e($value['second_sibling']); ?></div>
                                                                <?php else: ?>
                                                                    <div class="alert1 alert-warning p-10 text-center">NO</div>
                                                                <?php endif; ?>

                                                            </td>
                                                            <td class=""><?php echo e($value['lottery_number']); ?></td>
                                                             <td class="text-center">
                                                                <div class="alert1 alert-success">
                                                                    <?php echo e($value['rank']); ?>

                                                                </div>
                                                            </td>

                                                             <?php $__currentLoopData = $value['score']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skey=>$sbjct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <?php $__currentLoopData = $terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <td class="grade-col text-center">
                                                                        <?php if(isset($sbjct[$term])): ?>
                                                                        
                                                                            <?php if(isset($setEligibilityData[$value['second_choice']][$skey.'-'.$term])): ?>

                                                                                <?php if($value['grade_status'] == "Pass"): ?>
                                                                                    <?php $class = "alert1 alert-success" ?>
                                                                                    
                                                                                    
                                                                                <?php else: ?>
                                                                                    <?php $class = "alert1 alert-danger" ?>
                                                                                <?php endif; ?>
                                                                            <?php else: ?>
                                                                                    <?php $class = "alert1 alert-warning" ?>
                                                                            <?php endif; ?>
                                                                            <div class="<?php echo e($class); ?>">
                                                                                <?php echo e($sbjct[$term]); ?>

                                                                            </div>
                                                                        <?php else: ?>
                                                                            <?php echo e("NA"); ?>

                                                                        <?php endif; ?>
                                                                    </td>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                                            <?php $__currentLoopData = $value['cdi']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vkey=>$vcdi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <td class="cdi-col text-center">
                                                                    
                                                                        <?php if(isset($setCDIEligibilityData[$value['second_choice']][$vkey])): ?>
                                                                            <?php if($value['grade_status'] == "Pass"): ?>
                                                                                 <?php $class = "alert1 alert-success" ?>
                                                                            <?php else: ?>
                                                                                 <?php $class = "alert1 alert-danger" ?>
                                                                             <?php endif; ?>
                                                                        <?php else: ?>
                                                                            <?php $class = "alert1 alert-warning" ?>
                                                                        <?php endif; ?>
                                                                        <div class="<?php echo e($class); ?>">
                                                                            <?php echo e($value['cdi'][$vkey]); ?>

                                                                        </div>
                                                                    
                                                                </td>

                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                                        </tr>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script src="<?php echo e(url('/resources/assets/admin')); ?>/js/bootstrap/dataTables.buttons.min.js"></script>
<!--<script src="<?php echo e(url('/resources/assets/admin')); ?>/js/bootstrap/jszip.min.js"></script>
<script src="<?php echo e(url('/resources/assets/admin')); ?>/js/bootstrap/pdfmake.min.js"></script>
<script src="<?php echo e(url('/resources/assets/admin')); ?>/js/bootstrap/vfs_fonts.js"></script>-->
<script src="<?php echo e(url('/resources/assets/admin')); ?>/js/bootstrap/buttons.html5.min.js"></script>

	<script type="text/javascript">
		//$("#datatable").DataTable({"aaSorting": []});
        var dtbl_submission_list = $("#datatable").DataTable({"aaSorting": [],
            "bSort" : false,
             "dom": 'Bfrtip',
             "autoWidth": true,
            // "scrollX": true,
             buttons: [
                    {
                        extend: 'excelHtml5',
                        title: 'Reports',
                        text:'Export to Excel',
                        //Columns to export
                        exportOptions: {
                                columns: "thead th:not(.d-none)"
                        }
                    }
                ]
            });

        $("#hideGrade").change(function(){
            if($(this).prop("checked") == true)
            {
                $('.grade-col').addClass("d-none");
                dtbl_submission_list.$('.grade-col').addClass("d-none");
                var update = "Y";
            }
            else
            {
                $('.grade-col').removeClass("d-none");
                dtbl_submission_list.$('.grade-col').removeClass("d-none");
                var update = "N";
            }
            $.ajax({
                    url : "<?php echo e(url('/admin/Reports/setting/update/grade/')); ?>/"+update,
                    type: "GET"
            });
        })

        $("#hideCDI").change(function(){
            if($(this).prop("checked") == true)
            {
                $('.cdi-col').addClass("d-none");
                dtbl_submission_list.$('.cdi-col').addClass("d-none");
                var update = "Y";
            }
            else
            {
                $('.cdi-col').removeClass("d-none");
                dtbl_submission_list.$('.cdi-col').removeClass("d-none");

                var update = "N";

            }
            $.ajax({
                    url : "<?php echo e(url('/admin/Reports/setting/update/cdi/')); ?>/"+update,
                    type: "GET"
            });

        })        

        $("#hideRace").change(function(){
            if($(this).prop("checked") == true)
            {
                $('.hiderace').addClass("d-none");
                dtbl_submission_list.$('.hiderace').addClass("d-none");

                var update = "Y";        
            }
            else
            {
                $('.hiderace').removeClass("d-none");
                dtbl_submission_list.$('.hiderace').removeClass("d-none");
                var update = "N";
            }
            $.ajax({
                    url : "<?php echo e(url('/admin/Reports/setting/update/race/')); ?>/"+update,
                    type: "GET"
            });
        })        

        $("#hideZone").change(function(){
            if($(this).prop("checked") == true)
            {
                $('.hidezone').addClass("d-none");
                dtbl_submission_list.$('.hidezone').addClass("d-none");
                var update = "Y";            
            }
            else
            {
                $('.hidezone').removeClass("d-none");
                dtbl_submission_list.$('.hidezone').removeClass("d-none");
                var update = "N";
            }
            $.ajax({
                    url : "<?php echo e(url('/admin/Reports/setting/update/zoned_school/')); ?>/"+update,
                    type: "GET"
            });
        })

        $(document).ready(function(){
            var hideArr = new Array();
            <?php if($settings->race == "Y"): ?> 
                $('.hiderace').addClass("d-none");
               dtbl_submission_list.$('.hiderace').addClass("d-none");

            <?php endif; ?>       

            <?php if($settings->zoned_school == "Y"): ?>         
                $('.hidezone').addClass("d-none");
               dtbl_submission_list.$('.hidezone').addClass("d-none");

            <?php endif; ?>       

            <?php if($settings->grade == "Y"): ?> 
                $('.grade-col').addClass("d-none");
                dtbl_submission_list.$('.grade-col').addClass("d-none");

            <?php endif; ?>       

            <?php if($settings->cdi == "Y"): ?> 
                $('.cdi-col').addClass("d-none");
                dtbl_submission_list.$('.cdi-col').addClass("d-none");
                 

            <?php endif; ?>   
        });    
	</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Reports/Views/index.blade.php ENDPATH**/ ?>