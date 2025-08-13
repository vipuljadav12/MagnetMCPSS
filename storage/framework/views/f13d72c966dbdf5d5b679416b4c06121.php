
<?php $__env->startSection('title'); ?>
Permission
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Permission</div>
        <div class=""><a href="<?php echo e(url('admin/Permission/create')); ?>" class="btn btn-sm btn-secondary" title="Add Permission">Add Permission</a>
        <a href="<?php echo e(url('admin/Permission/trashindex')); ?>" title="View Trash" class="btn btn-danger btn-sm">View Trash</a>
        </div>
    </div>
</div>
<?php echo $__env->make('layouts.admin.common.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped mb-0" id="example">
                <thead>
                    <tr>
                        <th class="align-middle text-center">Sr. No.</th>
                        <th class="align-middle text-center">Slug</th>
                        <th class="align-middle text-center">Display Name</th>
                        <th class="align-middle text-center">Module Name</th>
                        <th class="align-middle text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($permission)): ?>
                        <?php $__currentLoopData = $permission; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="text-center"><?php echo e($key+1); ?></td>
                                <td class=""><?php echo e($value->slug ?? ''); ?></td>
                                <td class=""><?php echo e($value->display_name ?? ''); ?></td>
                                <td class=""><?php echo e((isset($value->modules) && $value->modules != '[]') ? ucfirst($value->modules[0]->name) : ''); ?></td>
                                <td class="text-center">
                                    <a href="<?php echo e(url('admin/Permission/edit/').'/'.$value->id); ?>" class="font-18 ml-5 mr-5" title="Edit"><i class="far fa-edit"></i>
                                   </a>

                                   <a href="javascript:void(0);" class="font-18 ml-5 mr-5 text-danger deletePermission"  title="Delete" data-value="<?php echo e($value->id); ?>"><i class="far fa-trash-alt"></i>
                                   </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script type="text/javascript">
    $(document).ready(function (){
        $('#example').DataTable();
        // alert();
    });
$(document).on('click', '.deletePermission', function(){
    var role_id = $(this).attr('data-value');
    swal({
        // title: "Are you sure?",
        // text: "you want to move this permission to trash.",
        // type: "warning",
        // showCancelButton: true,
        // confirmButtonClass: "btn-danger",
        // confirmButtonText: "Yes",
        // closeOnConfirm: false
         title: "Are you sure you would like to move this District to Trash ?",
            text: "",
            // type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes",
            closeOnConfirm: false
    })
    .then(() => {
        // alert();
            var url = "<?php echo e(url('/')); ?>/admin/Permission/trash/"+role_id;
            window.location.href = url;
        /*$.ajax({                        
            url: "<?php echo e(url('/')); ?>/admin/Permission/trash/"+role_id,
            success:function(response){
                if(response == 'Success')
                    location.reload();
            }
        }); */    
    });

});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Permission/Views/index.blade.php ENDPATH**/ ?>