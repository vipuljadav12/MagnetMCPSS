<ul class="nav nav-tabs d-none" id="myTab_eligibility" role="tablist">
    <li class="nav-item"><a class="nav-link active" id="submissions-tab" data-toggle="tab" href="#submissions" role="tab" aria-controls="submissions" aria-selected="true">Submissions</a></li>
    <li class="nav-item"><a class="nav-link" id="late_submissions-tab" data-toggle="tab" href="#late_submissions" role="tab" aria-controls="late_submissions" aria-selected="false">Late Submissions</a></li>
</ul>

<div class="tab-content bordered" id="myTab_eligibilityContent">
    <div class="tab-pane fade show active" id="submissions" role="tabpanel" aria-labelledby="submissions-tab">
        <div class="">
            <div class="card shadow">
                <div class="card-header d-flex flex-wrap justify-content-between">
                    <div class="">Eligibility Determination Method</div>
                    
                </div>
                <div class="card-body">
                    <?php if(count($applications) > 0): ?>
                        <div class="">
                            <div class="form-group">
                                <label class="control-label">Select Application : </label>
                                <div class="">
                                    <select class="form-control custom-select" id="application_id" name="application_id" onchange="changeApplication(this.value)">
                                        <?php $__currentLoopData = $applications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($value->id); ?>" <?php if($application_id == $value->id): ?> selected <?php endif; ?>><?php echo e($value->application_name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="pb-10 d-flex flex-wrap justify-content-center align-items-center">
                            <div class="d-flex mb-10 mr-30">
                                <div class="mr-10">Basic Method Active : </div>
                                <input disabled="" id="basic_method_only" type="checkbox" class="js-switch js-switch-1 js-switch-xs" name="basic_method_only"  data-size="Small" <?php echo e($program->basic_method_only=='Y'?'checked':''); ?>>
                            </div>
                            <div class="d-flex mb-10 mr-30">
                                <div class="mr-10">Combined Scoring Active : </div>
                                <input disabled="" id="combined_scoring" type="checkbox" class="js-switch js-switch-1 js-switch-xs"  name="combined_scoring" data-size="Small" <?php echo e($program->combined_scoring=='Y'?'checked':''); ?>>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                <tr>
                                    <th class="align-middle">Eligibility Type</th>
                                    <th class="align-middle">Used in Determination Method</th>
                                    <th class="align-middle text-center">Eligibility Value Required?</th>
                                    <th class="align-middle text-center">Minimum Eligibility Value</th>
                                    <th class="align-middle text-center">Active/Inactive</th>
                                    <th class="align-middle text-center w-120">Applied to Grades</th>
                                </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $eligibilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$eligibility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td class="">
                                                <?php echo e($eligibility['name']); ?>

                                                
                                                <input type="hidden" id="" name="eligibility_type[]" value="<?php echo e($eligibility['id']); ?>">
                                                <input type="hidden" id="" name="eligibility_id[<?php echo e($eligibility['id']); ?>][]" value="<?php if(isset($eligibility['program_eligibility']['assigned_eigibility_name'])): ?> <?php echo e($eligibility['program_eligibility']['assigned_eigibility_name']); ?> <?php endif; ?>">
                                                <?php
                                                    if(isset($eligibility['program_eligibility']['assigned_eigibility_name']))
                                                    {
                                                        if(isset($setEligibility[$eligibility['id']]->eligibility_value))
                                                        {
                                                            $methodCheck  = getEligibilityContentType($eligibility['program_eligibility']['assigned_eigibility_name'],$setEligibility[$eligibility['id']]->eligibility_value) ?? null ;
                                                        }
                                                        else
                                                        {
                                                            $methodCheck = getEligibilityContentType($eligibility['program_eligibility']['assigned_eigibility_name']) ?? null;
                                                        }
                                                    }
                                                    $isST = getEligibilityTypeById($eligibility['id']);
                                                     //print_r($isST->content_html);
                                                ?>
                                                
                                                <?php
                                                    $required = isset($setEligibility[$eligibility['id']]->required) ? $setEligibility[$eligibility['id']]->required : null;
                                                ?>
                                               
             
                                             <td class="">
                                                <select class="form-control custom-select" name="determination_method[]" disabled>
                                                   <option value="">Choose an Option</option>
                                                    <option value="Basic" <?php echo e(isset($eligibility['program_eligibility']['determination_method']) && $eligibility['program_eligibility']['determination_method']=='Basic'?'selected':''); ?>>Basic</option>
                                                    <option value="Combined" <?php echo e(isset($eligibility['program_eligibility']['determination_method']) &&    $eligibility['program_eligibility']['determination_method']=='Combined'?'selected':''); ?>>Combined</option>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <select class="form-control custom-select valueRequiredSelect" name="required[<?php echo e($eligibility['id']); ?>][]">
                                                    <?php
                                                        $required = isset($setEligibility[$eligibility['id']]->required) ? $setEligibility[$eligibility['id']]->required : null;
                                                    ?>
                                                    <option value="X">Choose an Option</option>
                                                    <option value="Y" <?php if(isset($required) && $required == "Y"): ?> selected="" <?php endif; ?>>Yes</option>
                                                    <option value="N" <?php if(isset($required) && $required == "N"): ?> selected="" <?php endif; ?>>No</option>
                                                </select>

                                            </td>
                                            <td class="text-center">                                    
                                                <div class="MinimumEligibility ForSelectedY <?php if(isset($required) && $required == "N" || $required == "X"): ?> d-none <?php endif; ?>">
                                                    <div class="align-items-center text-center">
                                                        <div class="mr-10 d-none">
                                                             <select class="form-control d-none" name="eligibility_value[<?php echo e($eligibility['id']); ?>][]" <?php if(isset($required) && $required == "N"): ?> disabled="" <?php endif; ?> style="width: 200px !important">
                                                                <?php if(isset($eligibility['program_eligibility']['assigned_eigibility_name'])): ?>
                                                                    <?php if(isset($setEligibility[$eligibility['id']]->eligibility_value)): ?>
                                                                        <?php echo getEligibilityContent($eligibility['program_eligibility']['assigned_eigibility_name'],$setEligibility[$eligibility['id']]->eligibility_value) ?? ""; ?>

                                                                    <?php else: ?>
                                                                        <?php echo getEligibilityContent($eligibility['program_eligibility']['assigned_eigibility_name']) ?? ""; ?>

                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </select>
                                                        </div>
                                                        <div class="">
                                                            <?php if(isset($eligibility['program_eligibility'])): ?>
                                                                 <?php if(isset($methodCheck) && $methodCheck == "NA" || $isST->content_html == "standardized_testing" || $isST->content_html == "conduct_disciplinary" || $isST->content_html == "academic_grade_calculation" ): ?>
                                                                        
                                                                    <a class="openPopUpForData editPopBtn  <?php if(!isset($required) || $required != "Y"): ?> d-none <?php endif; ?>" data-id="<?php echo e($eligibility['id']); ?>" data-eligibility-id="<?php echo e($eligibility['program_eligibility']['assigned_eigibility_name']); ?>" data-program-id="<?php echo e($program->id); ?>"> <i class="font-18 far fa-edit"></i> </a>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                   
                                                
                                                </div>
                                                <div class="MinimumEligibility ForSelectedX  <?php if(isset($required) && $required == "Y" || $required == "N" || !isset($required)): ?> d-none <?php endif; ?>">
                                                    <select class="form-control"></select>
                                                </div>
                                                <div class="MinimumEligibility ForSelectedN  <?php if(isset($required) && $required == "Y" || !isset($required) || $required == "X"): ?> d-none <?php endif; ?>">
                                                    N/A
                                                </div>
                                                
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                    $status = isset($setEligibility[$eligibility['id']]->status) ? $setEligibility[$eligibility['id']]->status : null;
                                                ?>
                                                <input id="chk_09" name="status[<?php echo e($eligibility['id']); ?>][]" type="checkbox" value="Y" class="js-switch js-switch-1 js-switch-xs eligibility_status" data-size="Small" <?php if(isset($status) && $status == "Y"): ?> checked  <?php endif; ?>>
                                            </td>
                                            <td class="text-center">
                                                <input type="text" disabled="" class="form-control" name="" value="<?php echo e($eligibility['program_eligibility']['grade_lavel_or_recommendation_by'] ?? ""); ?>">                                    
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>
                        <p class="text-center">There Application setup yet.</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    
</div><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/SetEligibility/Views/eligibility_edit.blade.php ENDPATH**/ ?>