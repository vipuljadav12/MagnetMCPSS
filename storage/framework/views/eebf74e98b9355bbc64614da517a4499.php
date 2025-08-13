    <div class="mt-20">
        <div class="card bg-light p-20">
            <div class="text-center font-20 b-600 mb-10"><?php echo getconfig()['welcome_text'] ?? ''; ?></div>
            <div class="">
                <?php echo getconfig()['welcome_message'] ?? ''; ?>

            </div>
            <div class="text-center font-20 b-600 mb-10"><?php echo e(getApplicationName($application_data->id ?? ($application_id ?? ''))); ?></div>
        </div>
    </div>
<?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\resources\views/layouts/front/common/district_header.blade.php ENDPATH**/ ?>