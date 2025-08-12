
<?php $__env->startSection('title'); ?> Files <?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Front Page Links</div>
        <div class="">
            <a href="<?php echo e(url('admin/Files/create')); ?>" class="btn btn-sm btn-secondary" title="">Add New</a>
        </div>
    </div>
</div>
<?php echo $__env->make("layouts.admin.common.alerts", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div class="card shadow">
   <div class="card-body">
        <div class="table-responsive">
            <table id="tbl_index" class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th class="align-middle text-center">#</th>
                    	<th class="align-middle">Front Page Link Name</th>
                        <th class="align-middle">Description</th>
                        <th class="align-middle text-center">Status</th>
                        <th class="align-middle text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="">
                    <?php $__currentLoopData = $files; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="" id="<?php echo e($value->link_id); ?>">
                        <td class="text-center"><?php echo e($value->link_id); ?></td>
                        <td><?php echo e($value->link_title); ?></td>
                        <td class="card-group">
                            <?php if($value->link_filename != ""): ?>
                                <?php
                                    $file_path = url('/resources/filebrowser/'.$district->district_slug.'/documents/'.$value->link_filename);
                                    $file_name = explode('.',$value->link_filename);
                                    $file_type = "";
                                    if (isset($file_name[1])) {
                                        if ($file_name[1] == 'pdf') {
                                            $file_type = 'pdf';
                                        }
                                    }
                                ?>
                                <?php if($file_type == 'pdf'): ?>
                                    <!--<a href="<?php echo e($file_path); ?>" target="_blank" href="">--><?php echo e($value->link_filename); ?><!--<i class="far fa-file-pdf fa-lg ml-5"></i></a>-->
                                <?php else: ?>
                                    <?php echo e($value->link_filename); ?>

                                    <!--<div class="ml-5" style="height: 30px; width: 30px;"><img src="<?php echo e($file_path); ?>" style="height: 100%; width: 100%"></div>-->
                                <?php endif; ?>
                            <?php elseif($value->popup_text != ""): ?>
                                <?php echo $value->popup_text; ?>

                            <?php elseif($value->link_url != ""): ?>
                                <div class="text-info"><?php echo e($value->link_url); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <input id="<?php echo e($value->link_id); ?>" type="checkbox"  class="js-switch js-switch-1 js-switch-xs file_status" data-size="Small" <?php echo e($value->status=='Y'?'checked':''); ?> />
                        </td>
                        <td class="text-center">
                            <a href="<?php echo e(url('/admin/Files/edit/'.$value->link_id)); ?>"  class="font-18 ml-5 mr-5" title=""><i class="far fa-edit"></i></a>
                            <a href="javascript:void(0)" onclick="deletefunction(<?php echo e($value->link_id); ?>)" class="font-18 ml-5 mr-5 text-danger" title=""><i class="far fa-trash-alt"></i></a>
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
<script type="text/javascript"> 
	var table = $('#tbl_index').DataTable({
        order: [],
        'columnDefs': [
            {
                'orderable': false,
                'targets': [2, 3]
            }
        ],
        'rowReorder':true,
        'rowReorder': {
            update: true
        }
    });

    //table.rowReordering();
    var dataArr = new Array();
    table.on( 'row-reordered', function ( e, diff, edit ) {
        table.rows().eq(0).each( function ( index ) {
            var row = table.row( index );
            dataArr[dataArr.length] = row.data()[0];
            // ... do something with data(), or row.node(), etc
        } );
         $.ajax({
            url: "<?php echo e(url('admin/Files/sort_update')); ?>",
            method: "post",
            data: {
                "_token": "<?php echo e(csrf_token()); ?>",
                "data": dataArr
            },
             success: function(){
                document.location.href = "<?php echo e(url('/admin/Files')); ?>"
            }
        });


    } );

    // File status
    $(document).on('change', '.file_status', function() {
        var status = $(this).prop('checked') == true ? 'Y' : 'N';
        var id  = $(this).attr('id');
        $.ajax({
            url: "<?php echo e(url('admin/Files/status_update')); ?>",
            data: {
                id: id,
                status: status
            }

        });
    });

    //delete confermation
    var deletefunction = function(id){
        swal({
            title: "Are you sure you would like to delete this file?",
            text: "",
            // type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes",
            closeOnConfirm: false
        }).then(function() {
            window.location.href = '<?php echo e(url('/')); ?>/admin/Files/delete/'+id;
        });
    };

</script> 
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Files/Views/index.blade.php ENDPATH**/ ?>