<div class="table-responsive">
	<div class="row col-md-12 pb-20" id="submission_filters"></div>

    <table id="datatable" class="table table-striped mb-0">
        <thead>
        <tr>
            <th class="align-middle w-120 text-center">ID</th>
			<th class="text-center">Enrollment Year</th>
			<th class="text-center">Application Name</th>
            <th class="align-middle">Old Data</th>
            <th class="align-middle">New Data</th>
            <th class="align-middle">Updated On</th>
            <th class="align-middle text-center">User</th>
            
        </tr>
        </thead>
        <tbody>
        	<?php if(isset($audit_trails['submission']) && count($audit_trails['submission']) > 0): ?>
				<?php $__currentLoopData = $audit_trails['submission']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a => $audit_trail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

					<?php if(isset($audit_trail->changed_fields) && $audit_trail->changed_fields != "[]" && $audit_trail->changed_fields != ""): ?>
					<tr>
						<td class="text-center"><?php echo e($loop->index +1); ?></td>
						<td class="text-center"><?php echo e(getEnrollmentYear($audit_trail->enrollment_id)); ?></td>
						<td class="text-center"><?php echo e(getApplicationName($audit_trail->application_id)); ?></td>

						<td>
							<div>	
								<?php $__currentLoopData = $audit_trail->old_values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o => $old): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
									<?php if(isset($old) && isset($o)): ?>
										<?php if($o == "gender"): ?>
											<?php continue; ?>
										<?php endif; ?>
										<?php if($o=="id" || $o=="submission_id"): ?>
											<span class="text-strong">Submission ID : </span>
										
										<?php elseif($o == "employee_id"): ?>
											<span class="text-strong">Employee ID : </span>
										<?php elseif($o == "mcp_employee"): ?>
											<span class="text-strong">MCPSS Employee : </span>
										<?php else: ?>
											<span class="text-strong"><?php echo e(ucwords(str_replace("_"," ",($o)))); ?> : </span>
										<?php endif; ?>

										<?php if($audit_trail->changed_fields != '' && in_array($o,$audit_trail->changed_fields)): ?>
											<span class="text-danger">
										<?php else: ?>
											<span class="text">
										<?php endif; ?>
										<?php if($o=="id" || $o=="submission_id"): ?>
											<a href="<?php echo e(url('/admin/Submissions/edit/'.$old)); ?>"  target="_blank"><?php echo e($old); ?></a>
										<?php elseif($o == "gender"): ?>
										<?php else: ?>
											 <?php echo e($old); ?>

										<?php endif; ?><br>

										<?php if($o=="id" || $o=="submission_id"): ?>
											<?php if(getSubmissionStudentName($old) != ""): ?>
												<span class="text-strong">Student Name : </span>
												<span class="text"><?php echo e(getSubmissionStudentName($old)); ?></span><br>
											<?php endif; ?>
										<?php endif; ?>
										</span>
									<?php endif; ?>
								<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
							</div>
						</td>
						<td>
							<div>	
								<?php $__currentLoopData = $audit_trail->new_values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n => $new): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
									<?php if(isset($new)): ?>
										<?php if($n=="id" || $n=="submission_id"): ?>
											<span class="text-strong">Submission ID : </span>
										<?php elseif($n == "gender"): ?>	
											<span class="text-strong">Comment : </span>
										<?php elseif($n == "employee_id"): ?>
												<span class="text-strong">Employee ID : </span>
											<?php elseif($n == "mcp_employee"): ?>
												<span class="text-strong">MCPSS Employee : </span>
											
										<?php else: ?>
											<span class="text-strong"><?php echo e(ucwords(str_replace("_"," ",($n)))); ?> : </span>
										<?php endif; ?>

										<?php if(in_array($n,$audit_trail->changed_fields)): ?>
											<span class="text-success">
										<?php else: ?>
											<span class="text">
										<?php endif; ?>

										<?php if($n=="id" || $n =="submission_id"): ?>
											<a href="<?php echo e(url('/admin/Submissions/edit/'.$new)); ?>"  target="_blank"><?php echo e($new); ?></a>
										<?php else: ?>
											 <?php echo e($new); ?>

										<?php endif; ?><br>

										<?php if($n=="id" || $n =="submission_id"): ?>
											<?php if(getSubmissionStudentName($new) != ""): ?>
												<span class="text-strong">Student Name : </span>
												<span class="text"><?php echo e(getSubmissionStudentName($new)); ?></span><br>
											<?php endif; ?>
										<?php endif; ?>
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
					<?php elseif($audit_trail->changed_fields == "" && $audit_trail->old_values != ""): ?>
						<tr>
							<td class="text-center"><?php echo e($loop->index +1); ?></td>
							<td class="text-center"><?php echo e(getEnrollmentYear($audit_trail->enrollment_id)); ?></td>
							<td class="text-center"><?php echo e(getApplicationName($audit_trail->application_id)); ?></td>
							<td></td>
							<td>
								<div>	
									<?php $__currentLoopData = $audit_trail->old_values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o => $old): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
										<?php if(isset($old) && isset($o)): ?>
											<?php if($o=="id" || $o=="submission_id"): ?>
												<span class="text-strong">Submission ID : </span>
											<?php elseif($o == "employee_id"): ?>
												<span class="text-strong">Employee ID : </span>
											<?php elseif($o == "mcp_employee"): ?>
												<span class="text-strong">MCPSS Employee : </span>
											<?php else: ?>
												<span class="text-strong"><?php echo e(ucwords(str_replace("_"," ",($o)))); ?> : </span>
											<?php endif; ?>
											
												
												<span class="text">
												<?php if($o=="id" || $o=="submission_id"): ?>
													<a href="<?php echo e(url('/admin/Submissions/edit/'.$old)); ?>" target="_blank"><?php echo e($old); ?></a>
												<?php else: ?>
													 <?php echo e($old); ?>

												<?php endif; ?>
												 <br>
											</span>
											<?php if($o=="id" || $o=="submission_id"): ?>
												<?php if(getSubmissionStudentName($old) != ""): ?>
													<span class="text-strong">Student Name : </span>
													<span class="text"><?php echo e(getSubmissionStudentName($old)); ?></span><br>
												<?php endif; ?>
											<?php endif; ?>
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
					<?php else: ?>
					<?php echo e($audit_trail->changed_fields); ?>

					<?php endif; ?>
				<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
			<?php endif; ?>
        </tbody>
    </table>
</div><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/AuditTrailData/Views/admin/submission.blade.php ENDPATH**/ ?>