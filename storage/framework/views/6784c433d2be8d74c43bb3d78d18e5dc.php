

<?php $__env->startSection('title'); ?>Priorities <?php $__env->stopSection(); ?>

<?php $__env->startSection('styles'); ?>
    <style type="text/css">
        .error {
            color: #e33d2d;
        }
    </style>
    <!-- DataTables -->
    <link
        href="<?php echo e(asset('resources/assets/admin/plugins/DataTables/DataTables-1.10.21/css/dataTables.bootstrap4.min.css')); ?>"
        rel="stylesheet" type="text/css" />
    <link href="<?php echo e(asset('resources/assets/admin/plugins/DataTables/Buttons-1.6.2/css/buttons.bootstrap4.min.css')); ?>"
        rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="<?php echo e(asset('resources/assets/admin/plugins/DataTables/Responsive-2.2.5/css/responsive.bootstrap4.min.css')); ?>"
        rel="stylesheet" type="text/css" />

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Priority Master</div>
            <div class="">
                <?php if(Session::get('district_id') != 0): ?>
                    <a href="<?php echo e(url('admin/Priority/create')); ?>" class="btn btn-sm btn-secondary" title="">Add
                        Priority</a>
                <?php endif; ?>
                <a href="<?php echo e(url('admin/Priority/trash')); ?>" class="btn btn-sm btn-danger" title="">Trash</a>
            </div>
        </div>
    </div>

    <!-- Show Error messsages -->
    

    <div class="card shadow">
        <div class="card-body">
            <?php echo $__env->make('layouts.admin.common.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="table-responsive">
                <table class="table table-striped mb-0" id="priority">
                    <thead>
                        <tr>
                            <th class="align-middle">Template Name</th>
                            <th class="align-middle text-center w-120">Status</th>
                            <th class="align-middle text-center w-120">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($priorities)): ?>
                            <?php $__currentLoopData = $priorities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $priority): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class=""><?php echo e($priority->name); ?></td>
                                    <td class="text-center"><input id="chk_00" type="checkbox"
                                            class="js-switch js-switch-1 js-switch-xs" value="<?php echo e($priority->id); ?>"
                                            data-size="Small" <?php if($priority->status == 'Y'): ?> checked <?php endif; ?> /></td>
                                    <td class="text-center">
                                        <a href="<?php echo e(url('admin/Priority/edit/' . $priority->id)); ?>" class="font-18 ml-5 mr-5"
                                            title=""><i class="far fa-edit"></i></a>
                                        <a href="javascript:void(0)" onclick="deletefunction(<?php echo e($priority->id); ?>)"
                                            class="font-18 ml-5 mr-5 text-danger" title=""><i
                                                class="far fa-trash-alt"></i></a>
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
        $(document).ready(function() {
            $("#priority").DataTable({
                'order': [],
                'columnDefs': [{
                    'targets': [1, 2],
                    'orderable': false
                }]
            });
        });

        $(document).on('change', '#chk_00', function() {
            $.ajax({
                type: "post",
                url: "<?php echo e(url('admin/Priority/updatestatus')); ?>",
                data: {
                    "_token": "<?php echo e(csrf_token()); ?>",
                    "id": $(this).attr('value'),
                    "status": $(this).prop('checked') == true ? "Y" : "N"
                }
            });
        });

        //delete confermation
        var deletefunction = function(id) {
            swal({
                title: "Are you sure you would like to move this Priority to trash?",
                text: "",
                // type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes",
                closeOnConfirm: false
            }).then(function() {
                window.location.href = '<?php echo e(url('/')); ?>/admin/Priority/delete/' + id;
            });
        };
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Priority/Views/index.blade.php ENDPATH**/ ?>