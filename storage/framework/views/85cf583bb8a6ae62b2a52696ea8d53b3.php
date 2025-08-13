<?php $__env->startSection('content'); ?>
    <?php echo $__env->make("layouts.front.common.district_header", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="box-2" style="">
            <div class="box-2-1" style="">
                <div class="back-box" style="">
                    <div class="form-group text-right">
                        <div class="">
                            
                        </div>
                    </div>    
                </div>
                <div class="card">
                    <div class="card-header">Step <?php echo e($page_id); ?> - <?php echo e(getFormPageTitle($data[0]->form_id, $page_id)); ?></div>
                     <div class="card-body">
                        <?php echo $__env->make("layouts.front.preview_form_fields", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3"></label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <a href="javascript:void(0)" onclick="alert('This functionality does not work in preview mode')" class="btn btn-secondary step-2-1-btn">Submit</a>
                                </div>
                            </div>
                     </div>
                </div>
                
            </div>
        </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\resources\views/layouts/front/preview_form.blade.php ENDPATH**/ ?>