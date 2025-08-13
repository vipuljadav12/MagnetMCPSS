<?php $__env->startSection('title'); ?>
    Configuration | <?php echo e(config('APP_NAME', env('APP_NAME'))); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('styles'); ?>
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
            <div class="page-title mt-5 mb-5">Text</div>
            <div class="">
                <?php if(checkPermission(Auth::user()->role_id, 'Configuration/create') == 1): ?>
                    <a href="<?php echo e(url('admin/Configuration/create')); ?>" class="btn btn-sm btn-secondary d-none"
                        title="Add Text">Add Text</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card shadow">
        <div class="card-body">
            <?php echo $__env->make('layouts.admin.common.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="table-responsive">
                <table id="datatable" class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th class="align-middle text-center">ID</th>
                            <th class="align-middle w-120 text-center">Short Code</th>
                            <th class="align-middle">Text Description</th>
                            <!--<th class="align-middle text-center">Status</th>-->
                            <th class="align-middle text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $configurations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $configuration): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="text-center"><?php echo e($key + 1); ?></td>
                                <td><?php echo e($configuration->config_name); ?></td>
                                <td>
                                    <?php echo e(\Illuminate\Support\Str::limit(strip_tags($configuration->config_value), 200)); ?>

                                </td>
                                <!-- <td class="text-center">
                                    <input id="<?php echo e($configuration->id); ?>" type="checkbox"  class="js-switch js-switch-1 js-switch-xs status"  data-size="Small" <?php echo e($configuration->status == 'Y' ? 'checked' : ''); ?> />
                                </td>-->
                                <td class="text-center">
                                    <a href="<?php echo e(url('admin/Configuration/edit', $configuration->id)); ?>"
                                        class="font-18 ml-5 mr-5" title="Edit"><i class="far fa-edit"></i></a>
                                    <a href="javascript:void(0)" onclick="deletefunction(<?php echo e($configuration->id); ?>)"
                                        class="font-18 ml-5 mr-5 text-danger d-none" title="Delete"><i
                                            class="far fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
    <script src="<?php echo e(asset('resources/assets/admin/js/jquery.validate.min.js')); ?>"></script>
    <script src="<?php echo e(asset('resources/assets/admin/js/additional-methods.min.js')); ?>"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $(".alert").delay(2000).fadeOut(1000);
            $('#datatable').DataTable({
                'columnDefs': [{
                        'targets': [1, 2], // column index (start from 0)
                        'orderable': false,
                    },
                    {
                        'width': 100,
                        'targets': 3
                    },
                    {
                        'width': 10,
                        'targets': 0
                    }
                ]
            });
            //Buttons examples

        });
        $('.status').change(function() {
            var click = $(this).prop('checked') == true ? 'Y' : 'N';
            $.ajax({
                type: "get",
                url: '<?php echo e(url('admin/Configuration/status')); ?>',
                data: {
                    id: $(this).attr('id'),
                    status: click
                },
                complete: function(data) {
                    console.log('success');
                }
            });
        });

        //delete confermation
        var deletefunction = function(id) {
            swal({
                title: "Are you sure you would like to delete this?",
                text: "",
                // type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes",
                closeOnConfirm: false
            }).then(function() {
                window.location.href = '<?php echo e(url('/')); ?>/admin/Configuration/delete/' + id;
            });
        };
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Configuration/Views/index.blade.php ENDPATH**/ ?>