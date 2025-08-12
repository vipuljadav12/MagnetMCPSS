<?php $__env->startSection('title'); ?>
	View/Edit Submissions
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Custom Communication</div>
            <?php if((checkPermission(Auth::user()->role_id,'CustomCommunication/create') == 1)): ?>
                <div class="w-auto"><a href="<?php echo e(url('admin/CustomCommunication/create')); ?>" title="Add Custom Communication" class="btn btn-secondary ml-5">Add Custom Communication</a> </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow">
            <div class="card-body">
                <div class="pt-20 pb-20">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0" id="datatable">
                            <thead>
                                <tr>
                                    <th class="align-middle">Template Name</th>                                                
                                    <?php if((checkPermission(Auth::user()->role_id,'CustomCommunication/edit') == 1)): ?>
                                    <th class="align-middle text-center w-120">Action</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class=""><?php echo e($value->template_name); ?></td>
                                        <?php if((checkPermission(Auth::user()->role_id,'CustomCommunication/edit') == 1)): ?>

                                        <td class="text-center"><a href="<?php echo e(url('/admin/CustomCommunication/edit/'.$value->id)); ?>" class="font-18 ml-5 mr-5" title=""><i class="far fa-edit"></i></a></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
               
            </div>
        </div>

  
       
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
	<script type="text/javascript">
		
       $(document).ready(function() {
            $('#datatable').DataTable();
            });

        $('.status').change(function() {
            var click=$(this).prop('checked')==true ? 'Y' : 'N' ;
            $.ajax({
                type: "get",
                url: '<?php echo e(url('admin/CustomCommunication/status')); ?>',
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
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/CustomCommunication/Views/index.blade.php ENDPATH**/ ?>