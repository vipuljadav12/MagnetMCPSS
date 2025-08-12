<?php $__env->startSection('title'); ?>
	Users
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">User</div>
        <div class="">
            <a href="<?php echo e(url('admin/Users/create')); ?>" class="btn btn-sm btn-secondary" title="">Add User</a>
            <a href="<?php echo e(url('admin/Users/trash')); ?>" class="btn btn-sm btn-danger" title="">Trash</a>
        </div>
    </div>
</div>
<div class="card shadow">
    <div class="card-body">
        <?php echo $__env->make("layouts.admin.common.alerts", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="table-responsive">
            <table class="table table-striped mb-0" id="userTable">
                <thead>
                    <tr>
                        <th class="align-middle">Name</th>
                        <th class="align-middle">Email</th>
                        <th class="align-middle text-center">Status</th>
                        <th class="align-middle text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                	<?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u=>$user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
	                    <tr>
	                        <td class=""><?php echo e($user->full_name); ?></td>
	                        <td class=""><?php echo e($user->email); ?></td>
	                        <td class="text-center">
                                <input  type="checkbox" data-plugin="switchery"  class=" js-switch js-switch-1 js-switch-xs  userStatus" data-size="Small"  <?php if(isset($user->status) && $user->status == "Y"): ?> checked <?php endif; ?> data-id="<?php echo e($user->id); ?>"/>
                            </td>
	                        <td class="text-center">
                                <a href="<?php echo e(url('admin/Users/edit').'/'.$user->id); ?>" class="font-18 ml-5 mr-5" title=""><i class="far fa-edit"></i></a>
                                
                                <a href="javascript:void(0);" onclick="deletefunction(<?php echo e($user->id); ?>)"  class="font-18 ml-5 mr-5 text-danger" title=""><i class="far fa-trash-alt"></i></a>
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
<?php $__env->startSection("scripts"); ?>

<script type="text/javascript">
    $(document).ready(function() {
        $(".alert").delay(2000).fadeOut(1000);
        $('#userTable').DataTable({
            'columnDefs': [ {
                'targets': [2,3], // column index (start from 0)
                'orderable': false, // set orderable false for selected columns
            }]
        });
        //Buttons examples
        var table = $('#datatable-buttons').DataTable({
            lengthChange: false,
            buttons: ['copy', 'excel', 'pdf', 'colvis'],
        });
        table.buttons().container()
            .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');
    });

    //status change
    $(document).on("change",".userStatus",function()
    {
        // alert();
        var user_id = $(this).attr("data-id");
        $.ajax({
            url: '<?php echo e(url('admin/Users/status/')); ?>',
            type: 'POST',
            data: {
                _token : "<?php echo e(csrf_token()); ?>",
                user_id : user_id
            },
            success: function(data) {
                return data;
            },
        });
    });

    //delete confermation
    var deletefunction = function(id){
        swal({ 
          title: "Are you sure you would like to move this User to trash?",
            text: "",
            // type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes",
            closeOnConfirm: false
          }).then(function() {
            window.location.href = '<?php echo e(url('/')); ?>/admin/Users/trash/'+id;
        });
    };





</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin.app", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Users/Views/index.blade.php ENDPATH**/ ?>