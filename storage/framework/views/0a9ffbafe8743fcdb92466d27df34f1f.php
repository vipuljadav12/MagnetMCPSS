<div class="form-group row" <?php if($data['db_field']=="employee_id" || $data['db_field']=="work_location"): ?> id="<?php echo e($data['db_field']); ?>" style="display: none" <?php endif; ?>>
	<?php if(isset($data['label'])): ?>
		<label class="control-label col-12 col-md-4 col-xl-3"><?php echo e($data['label']); ?><?php if(isset($data['required']) && $data['required']=='yes'): ?><span class="text-danger">*</span><?php endif; ?>

			</label>
		
	<?php endif; ?>
	<div class="col-12 col-md-6 col-xl-6">
		<input type="text" class="form-control" <?php if(isset($data['label'])): ?> thisname="<?php echo e($data['label']); ?>" <?php endif; ?> name="formdata[<?php echo e($field_id); ?>]"  <?php if(isset($data['required']) && $data['required']=='yes'): ?> required <?php endif; ?> value="" <?php if(isset($data['db_field']) && ($data['db_field']=="phone_number" || $data['db_field']=="alternate_number")): ?> placeholder="(___) ___-____" <?php else: ?> <?php if(isset($data['placeholder'])): ?> placeholder="<?php echo e($data['placeholder']); ?>" <?php endif; ?> <?php endif; ?> id="<?php echo e($data['db_field']); ?>" > 
		
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
</div><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\resources\views/layouts/front/Field/preview/Textbox.blade.php ENDPATH**/ ?>