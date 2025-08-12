<?php $__env->startSection('title'); ?>
	Form | <?php echo e(config('APP_NAME',env("APP_NAME"))); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>	
	<div class="card shadow">
	    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
	        <div class="page-title mt-5 mb-5">Form Master</div>
	        <div class="">
	        	<a href="<?php echo e(url('admin/Form/create')); ?>" class="btn btn-sm btn-secondary" title="">Add Form</a>
	        	<a href="<?php echo e(url('admin/Form/trash')); ?>" class="btn btn-sm btn-danger" title="">Trash</a>
	        </div>
	    </div>
	</div>
	<div class="card shadow">
	    <div class="card-body">
	    	<?php echo $__env->make("layouts.admin.common.alerts", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
	        <div class="table-responsive">
	            <table id="formTable" class="table table-striped mb-0">
	                    <thead>
	                        <tr>
	                            <th class="align-middle w-90 text-center">Sr. No.</th>
	                            <th class="align-middle">Form Name</th>
	                            <th class="align-middle w-20">Update Date &amp; Time</th>
	                            <th class="align-middle w-120 text-center">Status</th>
	                            <th class="align-middle w-200 text-center">Action</th>
	                        </tr>
	                    </thead>
	                    <tbody>
	                    	<?php $__empty_1 = true; $__currentLoopData = $forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$form): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
		                        <tr>
		                            <td class="text-center"><?php echo e($key+1); ?></td>
		                            <td class=""><?php echo e($form->name); ?></td>
		                            <td class=""><?php echo e($form->updated_at); ?></td>
		                            <td class="text-center">
		                            	<input type="checkbox" id="<?php echo e($form->id); ?>" class="js-switch js-switch-1 js-switch-xs status" data-size="Small" <?php echo e($form->status=='y'?'checked':''); ?> />
		                            </td>
		                            <td class="text-center">
		                            	<a href="<?php echo e(url('/previewform/1'.'/'.$form->id)); ?>" target="_blank" class="font-18 ml-5 mr-5" title="Preview"><i class="fas fa-external-link-alt"></i></a>
		                            	<a href="<?php echo e(url('admin/Form/edit/1',$form->id)); ?>" class="font-18 ml-5 mr-5" title=""><i class="far fa-edit"></i></a>
		                            	<!--<a href="javascript:void(0)" onclick="deletefunction(<?php echo e($form->id); ?>)" class="font-18 ml-5 mr-5 text-danger" title=""><i class="far fa-trash-alt"></i></a>-->
		                            </td>
		                        </tr>
	                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
	                        <?php endif; ?>
	                    </tbody>
	                </table>
	        </div>
	    </div>
	</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
	<script type="text/javascript">
	$(".alert").delay(2000).fadeOut(1000);
	$('#formTable').DataTable({
            'columnDefs': [ {
                'targets': [3,4], // column index (start from 0)
                'orderable': false, // set orderable false for selected columns
            }]
        });	
	 //delete confermation
        var deletefunction = function(id){
            swal({
                title: "Are you sure you would like to move this Form to trash?",
                text: "",
                // type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes",
                closeOnConfirm: false
            }).then(function() {
                window.location.href = '<?php echo e(url('/')); ?>/admin/Form/delete/'+id;
            });
        };

        $('.status').change(function(){
        	var click=$(this).prop('checked')==true ? 'y' : 'n' ;
            $.ajax({
                type: "get",
                url: '<?php echo e(url('admin/Form/status')); ?>',
                data: {
                    id:$(this).attr('id'),
                    status:click
                },
                complete: function(data) {
                    console.log('success');
                }
            });
        });

	</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Form/Views/index.blade.php ENDPATH**/ ?>