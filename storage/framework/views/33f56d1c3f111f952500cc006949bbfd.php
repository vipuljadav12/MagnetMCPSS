<div class='form-group form-group-input row buildBox<?php echo e($formContent->id); ?>' data-build-id="<?php echo e($formContent->id); ?>" i1d="<?php echo e($formContent->id); ?>">
    <label class='control-label col-12 col-md-5' id="label<?php echo e($formContent->id); ?>"><?php echo e(getContentValue($formContent->id,"label") ?? "Label :"); ?> </label>
    <div class='col-12 col-md-6 col-xl-6'>
    	<?php
			$required = getContentValue($formContent->id,"required");
			$required = isset($required) ?  "required" : "";
		?>
        <input type='text' class='form-control' id="input<?php echo e($formContent->id); ?>" placeholder="<?php echo e(getContentValue($formContent->id,"placeholder") ?? ""); ?>" >
    </div>
    <div>
    	<a class="btn text-danger removeField"><i class="fa fa-times"></i></a>
    </div>
</div><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Form/Views/container/text.blade.php ENDPATH**/ ?>