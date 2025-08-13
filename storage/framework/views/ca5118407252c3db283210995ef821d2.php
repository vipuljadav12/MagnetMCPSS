<div class="form-group row">
	
	
	<?php if((isset($data['option_1']) && $data['option_1']!='') || (isset($data['db_field']) && $data['db_field']=="state")): ?>
		<?php if(isset($data['label'])): ?>
			<label class="control-label col-12 col-md-4 col-xl-3"><?php echo e($data['label']); ?><?php if(isset($data['required']) && $data['required']=='yes'): ?><span class="text-danger">*</span><?php endif; ?>
			</label>
				
		<?php endif; ?>
		<div class="col-12 col-md-6 col-xl-6">
			<select <?php if(isset($data['required_field']) && $data['required_field']=='yes'): ?> required <?php endif; ?> class="form-control custom-select" <?php if(isset($data['text_label'])): ?> thisname="<?php echo e($data['text_label']); ?>" <?php endif; ?> name="formdata[<?php echo e($field_id); ?>]" <?php if(isset($data['db_field']) && $data['db_field']=="current_grade"): ?> onchange="changeNextGrade(this)" <?php endif; ?>  <?php if(isset($data['db_field']) && $data['db_field']=="next_grade"): ?> id="next_grade" <?php endif; ?> >
				<option value="">Select an Option</option>

			<?php if(isset($data['db_field']) && $data['db_field']=="state"): ?>
				<?php $stateArray = Config::get('variables.states') ?>

				<?php $__currentLoopData = $stateArray; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stkey=>$stvalue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
					<option value="<?php echo e($stvalue); ?>"><?php echo e($stvalue); ?></option>
				<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
				

			<?php else: ?>
				<?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
					<?php if(substr($key, 0, 7)=="option_"): ?>
						<option value="<?php echo e($value); ?>"><?php echo e($value); ?></option>
					<?php endif; ?>
				<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
			<?php endif; ?>
		</select>
	</div>
	<?php if(isset($data['help_text'])): ?>
		<div class="col-12 col-md-2 col-xl-3">
			<span class="help" data-toggle="tooltip" data-html="true" title="<?php echo e($data['help_text']); ?>">
		        <?php if($data['help_text'] != ""): ?>
		            <i class="fas fa-question"></i>
		        <?php endif; ?>
		    </span>
		</div>
	<?php endif; ?>
	<!--<fieldset>-->
		
    <?php endif; ?>
</div><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\resources\views/layouts/front/Field/preview/Select.blade.php ENDPATH**/ ?>