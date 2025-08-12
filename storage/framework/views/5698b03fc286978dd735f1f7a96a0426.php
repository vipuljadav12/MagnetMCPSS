


<?php $__env->startSection('styles'); ?>
    <style type="text/css">
        .error{
            color: #e33d2d;
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Address Override</div>
        <div>
            
            <a href="<?php echo e(url('/admin/ZonedSchool/create')); ?>" title="Import" class="btn btn-secondary">Add Address</a>
            
        </div>
    </div>
</div>

    
        <div class="card shadow">
            <div class="card-body">
                <?php echo $__env->make("layouts.admin.common.alerts", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <div class="pt-20 pb-20">
                    <div class="table-responsive">
                        <table id="zonedAddressList" class="table table-striped mb-0 w-100">
                            <thead>
                                <tr>
                                    <th class="text-middle">Building/House No</th>
                                    <th class="text-middle">Prefix Direction</th>
                                    <th class="text-middle">Street Name</th>
                                    <th class="text-middle">Street Type</th>
                                    <th class="text-middle">Unit Info</th>
                                    <th class="text-middle">Suffix Direction</th>
                                    <th class="text-middle">City</th>
                                    <th class="text-middle">State</th>
                                    <th class="text-middle">ZIP Code</th>
                                    <th class="text-middle">Elementary School</th>
                                    <th class="text-middle">Intermediate School</th>
                                    <th class="text-middle">Middle School</th>
                                    <th class="text-middle">High School</th>
                                    <th class="text-middle">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $zonedSchool; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$address): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="text-middle"><?php echo e($address->bldg_num ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->prefix_dir ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->street_name ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->street_type ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->unit_info ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->suffix_dir ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->city ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->state ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->zip ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->elementary_school ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->intermediate_school ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->middle_school ?? ""); ?></td>
                                        <td class="text-middle"><?php echo e($address->high_school ?? ""); ?></td>
                                        <td class="text-center"><a href="<?php echo e(url('admin/ZonedSchool/edit',$address->id)); ?>" title='Edit' class='font-18'><i class='far fa-edit'></i></a></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    
</form>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
    <script src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
    
    <script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
    <script type="text/javascript">
        var dtbl_zoned_list = $("#zonedAddressList").DataTable({
            "columnDefs": [
                    {"className": "dt-center", "targets": "_all"}
                ],
        });
    </script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/ZonedSchool/Views/address_override_index.blade.php ENDPATH**/ ?>