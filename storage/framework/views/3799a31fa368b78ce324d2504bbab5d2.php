    <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="row">
            <div class="col-12">
                <?php echo getPreviewFieldByTypeandId($row->type,$row->id,$row->form_id, $page_id); ?>

            </div>
        </div>
        
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\resources\views/layouts/front/preview_form_fields.blade.php ENDPATH**/ ?>