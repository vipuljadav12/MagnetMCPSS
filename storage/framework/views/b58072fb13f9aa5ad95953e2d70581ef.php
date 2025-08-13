
<?php $__env->startSection('title'); ?>View Eligibility <?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">View Eligibility</div>
            <div class=""><a href="<?php echo e($module_url); ?>" class="btn btn-sm btn-secondary" title="">Back</a></div>
        </div>
    </div>

    <?php echo $__env->make("Eligibility::EligibilityView.".$eligibilityTemplate->content_html, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/Eligibility/Views/view.blade.php ENDPATH**/ ?>