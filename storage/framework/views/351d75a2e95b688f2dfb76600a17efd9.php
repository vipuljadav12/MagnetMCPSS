
<?php $__env->startSection('title'); ?>
User Role
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<div class="content-wrapper-in"> <!-- InstanceBeginEditable name="Content-Part" -->
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">User Role</div>
        <div class=""><a href="<?php echo e(url('admin/Role/create')); ?>" class="btn btn-sm btn-secondary" title="Add UserType">Add User Role</a>
         
         <?php if(Auth::user()->role_id == 1): ?>   
            <a href="<?php echo e(url('admin/Permission')); ?>" class="btn btn-sm btn-info" title="Permission">Permission</a>
         <?php endif; ?>
        </div>
    </div>
</div>
<?php echo $__env->make('layouts.admin.common.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped mb-0" id="roleList">
                <thead>
                    <tr>
                        <th class="align-middle">User Role</th>
                        
                        <th class="align-middle text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(isset($roles) && $roles != '[]'): ?>    
                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>    
                        <tr>
                            <td class=""><?php echo e($value->name); ?></td>
                            
                            <td class="text-center">
                                <a href="<?php echo e(url('admin/Role/edit/').'/'.$value->id); ?>" class="font-18 ml-5 mr-5" title="Edit"><i class="far fa-edit"></i>
                                </a>

                                
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
<!-- InstanceEndEditable --> </div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>

<script type="text/javascript">
$('#roleList').DataTable({
    columnDefs: [
            { width: 200, targets: [1,2] }
        ],
});
$(document).on('click', '.deleteRole', function(){
    var role_id = $(this).attr('data-value');
    swal({
        title: "Are you sure?",
        text: "you want to move this record to trash.",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "Yes",
        closeOnConfirm: false
    },
    function(){
        $.ajax({                        
            url: "<?php echo e(url('/')); ?>/admin/Role/trash/"+role_id,
            success:function(response){
                if(response == 'Success')
                    location.reload();
            }
        });     
    });

});
    $(document).on('change','.statuschnge',function(){
        var role_id = $(this).attr('id');
        if(this.checked == true){
            var status = 'Y';           
        }else{
            var status = 'N';
        }
        $.ajax({            
            data: {status},         
            url: "<?php echo e(url('/')); ?>/admin/Role/status_change/"+role_id
        });
    })
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Role/Views/index.blade.php ENDPATH**/ ?>