<div class="card shadow">
    <div class="card-header"><?php echo e($program->name); ?>- Available seats for <?php echo e($enrollment->school_year ?? (date("Y")-1)."-".date("Y")); ?></div>
    <input type="hidden" name="year" value="<?php echo e($enrollment->school_year ?? (date("Y")-1)."-".date("Y")); ?>">
    <input type="hidden" name="enrollment_id" value="<?php echo e($enrollment->id); ?>">
	<?php
		$grades = isset($program->grade_lavel) && !empty($program->grade_lavel) ? explode(',', $program->grade_lavel) : array();

	?>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <tbody>
                	<?php $__empty_1 = true; $__currentLoopData = $grades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g=>$grade): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
	                    <tr>
	                        <td class="w-10"><?php echo e($grade); ?></td>
	                        <td class="w-30">
	                        	<input type="text" class="form-control numbersOnly availableSeat" data-id="<?php echo e($grade); ?>"  name="grades[<?php echo e($grade); ?>][available_seats]" value="<?php echo e($availabilities[$grade]->available_seats ?? ""); ?>"  <?php if($display_outcome > 0): ?> disabled <?php endif; ?>>
	                        	<label class="error text-danger d-none">Available Seats should not exceed the Total Seats</label>
	                        </td>
	                        <td class="w-30"></td>
	                        <td class="w-30"></td>
	                    </tr>
	                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
	                    <tr>
	                     	<td class="text-center">No Grades</td>
	                    </tr>
	                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="card shadow">
    <div class="card-header"><?php echo e($program->name); ?> - Total Capacity for <?php echo e($enrollment->school_year ?? (date("Y")-1)."-".date("Y")); ?></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <tbody>
                	<?php $__empty_1 = true; $__currentLoopData = $grades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g=>$grade): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
	                    <tr>
	                        <td class="w-10"><?php echo e($grade); ?></td>
	                        <td class="w-30">
	                        	<input type="text" class="form-control numbersOnly totalSeat"  name="grades[<?php echo e($grade); ?>][total_seats]" value="<?php echo e($availabilities[$grade]->total_seats ?? ""); ?>" data-id="<?php echo e($grade); ?>" <?php if($display_outcome > 0): ?> disabled <?php endif; ?>>
	                        </td>
	                        <td class="w-30"></td>
	                        <td class="w-30"></td>
	                    </tr>
	                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
	                    <tr>
	                     	<td class="text-center">No Grades</td>
	                    </tr>
	                <?php endif; ?>
                    
                </tbody>
            </table>
        </div>
         <?php if($display_outcome == 0): ?>
        <div class="text-right"> 
            
            <div class="box content-header-floating" id="listFoot">
                <div class="row">
                    <div class="col-lg-12 text-right hidden-xs float-right">
                        <button type="submit" class="btn btn-warning btn-xs" title="Save" id="optionSubmit"><i class="fa fa-save"></i> Save </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/SetAvailability/Views/options.blade.php ENDPATH**/ ?>