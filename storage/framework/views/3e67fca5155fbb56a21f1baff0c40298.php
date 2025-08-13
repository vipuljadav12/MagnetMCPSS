<div class="tab-pane fade show active" id="preview03" role="tabpanel" aria-labelledby="preview03-tab">
    <form action="<?php echo e(url('/admin/LateSubmission/Availability/store')); ?>" method="post" id="process_selection">
             <?php echo e(csrf_field()); ?>

             <input type="hidden" name="save_type" id="save_type" value="">
    <div class="table-responsive" style="height: 395px; overflow-y: auto;">
        
       <table class="table m-0" id="tbl_population_changes">
                <thead>
                    <tr>
                        <th class="" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Program Name</th>
                        <th class="text-center" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Original Entered Available Seats</th>
                        <th class="text-center" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Actual Available Seats</th>
                        <th class="text-center" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Current Offered and Accepted</th>
                        <th class="text-center" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Waitlisted</th>
                        <th class="text-center" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Late Applications</th>
                        <th class="text-center" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Remaining Available Seats</th>
                        <th class="text-center" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Withdrawn Students Count to Add</th>
                        <th class="text-center" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Seats to Process</th>
                        <th class="text-center" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Updated Available Seats</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($data_ary)): ?>
                        <?php $__currentLoopData = $data_ary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <?php
                                $available_seats = $value['available_seats'] ?? 0;
                            ?>
                            <td class=""><?php echo e(getProgramName($value['program_id'])); ?> - Grade <?php echo e($value['grade']); ?></td>
                            <td class="text-center"><span><?php echo e($value['available_seats']); ?></span></td>
                            <td class="text-center"><span id="available_seats-<?php echo e($value['program_id'].'-'.$value['grade']); ?>"><?php echo e($value['available_seats']); ?></span></td>
                            <td class="text-center"><span id="offer_count-<?php echo e($value['program_id'].'-'.$value['grade']); ?>"><?php echo e($value['offer_count'] ?? 0); ?></span></td>
                            <td class="text-center"><span id="waitlist_count-<?php echo e($value['program_id'].'-'.$value['grade']); ?>"><?php echo e($value['waitlist_count'] ?? 0); ?></span></td>
                            <td class="text-center"><span><?php echo e($value['late_submission_count'] ?? 0); ?></span></td>
                            <td class="text-center"><?php echo e($value['available_seats'] - $value['offer_count']); ?></td>
                            <td class="text-center"><input type="text" class="form-control numberinput" value="<?php echo e($value['withdrawn_seats']); ?>" name="WS-<?php echo e($value['program_id'].'-'.$value['grade']); ?>" id="WS-<?php echo e($value['program_id'].'-'.$value['grade']); ?>" onblur="updateProcessSeats('<?php echo e($value['program_id'].'-'.$value['grade']); ?>')" onkeypress="return onlyNumberKey(event)" <?php if($display_outcome > 0): ?> disabled <?php endif; ?>></td>
                            <td class="text-center"><span class="process_seats-<?php echo e($value['program_id'].'-'.$value['grade']); ?>"><?php echo e($value['available_seats']  - $value['offer_count'] + $value['withdrawn_seats']); ?></span></td>
                            <td class="text-center"><input type="text" disabled class="form-control updated_seats-<?php echo e($value['program_id'].'-'.$value['grade']); ?>" value="<?php echo e($value['available_seats'] - $value['offer_count'] + $value['withdrawn_seats']); ?>"></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </tbody>
            </table>

            
        
        
    </div>
    <?php if($display_outcome == 0): ?>
                <div class="text-right"><button type="button" name="value_save" value="value_save" class="btn btn-success mt-10" onclick="saveData()">Save</button></div>
            <?php endif; ?>
    <div class="form-group mt-20">
        <label for="">Last day and time to accept ONLINE</label>
        <div class=""><input class="form-control datetimepicker" name="last_date_late_submission_online_acceptance" id="last_date_late_submission_online_acceptance" value="<?php echo e($last_date_late_submission_online_acceptance); ?>" data-date-format="mm/dd/yyyy hh:ii"></div>
    </div>
    <div class="form-group">
        <label for="">Last day and time to accept OFFLINE</label>
        <div class=""><input class="form-control datetimepicker" name="last_date_late_submission_offline_acceptance" id="last_date_late_submission_offline_acceptance" value="<?php echo e($last_date_late_submission_offline_acceptance); ?>" data-date-format="mm/dd/yyyy hh:ii"></div>
    </div>
    <div class="text-right"><?php if($display_outcome == 0): ?><input type="submit" class="btn btn-success" value="Process Submissions Now"> <?php else: ?> <input type="button" class="btn btn-danger disabled" value="Process Submissions Now"> <?php endif; ?></div>
    </form>
</div>

<?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/LateSubmission/Views/Template/all_availability.blade.php ENDPATH**/ ?>