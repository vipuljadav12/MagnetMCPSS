<div class='form-group form-group-input row buildBox<?php echo e($formContent->id); ?>' data-build-id="<?php echo e($formContent->id); ?>" id="<?php echo e($formContent->id); ?>">
    <label class='control-label col-12 col-md-5' id="label<?php echo e($formContent->id); ?>"><?php echo e(getContentValue($formContent->id,"label") ?? "Label :"); ?> </label>
    <div class='col-12 col-md-6 col-xl-6'>
    	<?php
			$required = getContentValue($formContent->id,"required");
			$required = isset($required) ?  "required" : "";
		?>
        
        <select class='form-control' id="input<?php echo e($formContent->id); ?>">
            <?php
                $currentOptions = getContentOptions($formContent->id);
            ?>
            <?php if(isset($currentOptions) && !empty($currentOptions)): ?>
                <?php $__currentLoopData = $currentOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c=>$currentOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> 
                    <option><?php echo e($currentOption->field_value); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </select>
    </div>
    <div>
    	<a class="btn text-danger removeField"><i class="fa fa-times"></i></a>
    </div>
</div><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Form/Views/container/select.blade.php ENDPATH**/ ?>