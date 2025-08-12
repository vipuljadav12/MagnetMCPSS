<div class="table-responsive">
    <table id="datatable-general" class="table table-striped mb-0">
        <thead>
        <tr>
            <th class="align-middle w-120 text-center">ID</th>
			<th class="text-center">Module</th>
            <th class="align-middle">Old Data</th>
            <th class="align-middle">New Data</th>
            <th class="align-middle">Updated On</th>
            <th class="align-middle text-center">User</th>
            
        </tr>
        </thead>
        <tbody>
        	<?php if(isset($audit_trails['general']) && count($audit_trails['general']) > 0): ?>
				<?php $__currentLoopData = $audit_trails['general']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a => $audit_trail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
					<?php if(isset($audit_trail->changed_fields) && $audit_trail->changed_fields != "[]"): ?>
					<tr>
						<td class="text-center"><?php echo e($loop->index +1); ?></td>
						<td class="text-center"><?php echo e($audit_trail->module ?? '-'); ?></td>
						<td>
							<div>	
								<?php $__currentLoopData = $audit_trail->old_values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o => $old): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
									<?php if(isset($old) && isset($o)): ?>
										<span class="text-strong"><?php echo e(ucwords(str_replace("_"," ",($o)))); ?> : </span>
										<?php if(in_array($o,$audit_trail->changed_fields)): ?>
											<span class="text-danger">
										<?php else: ?>
											<span class="text">
										<?php endif; ?>
											 <?php echo e($old); ?><br>
										</span>
									<?php endif; ?>
								<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
							</div>
						</td>
						<td>
							<div>	
								<?php $__currentLoopData = $audit_trail->new_values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n => $new): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
									<?php if(isset($new)): ?>
										<span class="text-strong"><?php echo e(ucwords(str_replace("_"," ",($n)))); ?> : </span>
										<?php if(in_array($n,$audit_trail->changed_fields)): ?>
											<span class="text-success">
										<?php else: ?>
											<span class="text">
										<?php endif; ?>
											 <?php echo e($new); ?><br>
										</span>
									<?php endif; ?>
								<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
							</div>
						</td>
						<td>	
							<?php echo e(getDateTimeFormat($audit_trail->created_at)); ?>

						</td>
						<td>	
							<?php echo e($audit_trail->user->full_name ?? ""); ?>

						</td>

					</tr>
					<?php endif; ?>
				<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
			<?php endif; ?>
        </tbody>
    </table>
</div><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/AuditTrailData/Views/admin/general.blade.php ENDPATH**/ ?>