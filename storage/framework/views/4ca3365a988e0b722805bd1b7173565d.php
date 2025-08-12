<?php if(isset($formContents) && !empty($formContents)): ?>
	<?php $__currentLoopData = $formContents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f=>$formContent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
		
		
		<?php echo $__env->make("Form::container.".getFieldBox($formContent->field_id)->type,compact("formContent"), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
	<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Form/Views/formcontent.blade.php ENDPATH**/ ?>