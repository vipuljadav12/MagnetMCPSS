
<?php $__env->startSection('title'); ?>Edit Zone Address | <?php echo e(config('APP_NAME',env("APP_NAME"))); ?> <?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
   <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.min.css">

<style type="text/css">
    .loader
    {
        background: url("<?php echo e(url('/resources/assets/front/images/loader.gif')); ?>");
        background-repeat: no-repeat;
        background-position: right;
    }
        .select2-container .select2-choice {border-radius: 0 !important;  height: 30px !important}
    .select2-container{width: 100% !important; height: 30px !important}

</style>

    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Edit Zone Address</div>
            <?php
                ($zonedschool->added_by == 'manual') ? $url = url('admin/ZonedSchool/overrideAddress') : $url = url('admin/ZonedSchool') ;
            ?>
            
                <div class=""><a href="<?php echo e($url); ?>" class="btn btn-sm btn-secondary" title="Go Back">Go Back</a></div>
        </div>
    </div>
    <?php echo $__env->make("layouts.admin.common.alerts", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <form action="<?php echo e(url('admin/ZonedSchool/update',$zonedschool->id)); ?>" method="post" name="add_zone_address" enctype= "multipart/form-data">
        <?php echo e(csrf_field()); ?>


        <input type="hidden" name="added_by" value="<?php echo e($zonedschool->added_by); ?>">
        
        <div class="raw">
            <div class="card shadow">
                <div class="card-body">
                    <div class="text-right"><em>Please enter all information as provided on the district's zoning map page.</em>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Building/House No<span class="required">*</span> : </label>
                        <div class=""><input type="text" class="form-control" name="bldg_num" value="<?php echo e($zonedschool->bldg_num); ?>"></div>
                        <?php if($errors->first('bldg_num')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('bldg_num')); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Prefix Direction : <small><em>(If Applicable)</em></small> </label>
                        <div class="">
                             <select name="prefix_dir" id="prefix_dir" class="custom-sel2">
                                <option value="">Select Direction</option>
                                <option value="N"  <?php if("N" == $zonedschool->prefix_dir): ?> selected <?php endif; ?>>North</option>
                                <option value="S" <?php if("S" == $zonedschool->prefix_dir): ?> selected <?php endif; ?>>South</option>
                                <option value="E" <?php if("E" == $zonedschool->prefix_dir): ?> selected <?php endif; ?>>East</option>
                                <option value="W" <?php if("W" == $zonedschool->prefix_dir): ?> selected <?php endif; ?>>West</option>
                            </select>
                        </div>
                        <?php if($errors->first('prefix_dir')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('prefix_dir')); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                
                    <div class="form-group">
                        <label class="control-label">Street Name<span class="required">*</span> : </label>
                        <div class="">
                            <input type="text" class="form-control" name="street_name"  id="street_name" value="<?php echo e($zonedschool->street_name); ?>"></div>
                        <?php if($errors->first('street_name')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('street_name')); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                
                    <div class="form-group">
                        <label class="control-label">Street Type : </label>
                        <div class="">
                            <select name="street_type" id="street_type" class="custom-sel2" onchange="showOther('street_type');"> 
                                <option value="">Select Street Type</option>
                                <?php $__currentLoopData = $street_type; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($value != ""): ?>
                                        <option value="<?php echo e($value); ?>" <?php if($value == $zonedschool->street_type): ?> selected <?php endif; ?>><?php echo e($value); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" class="form-control mt-10 d-none" name="street_type_other"  id="street_type_other"></div>
                        <?php if($errors->first('street_type')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('street_type')); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Unit Info : </label>
                        <div class=""><input type="text" class="form-control" name="unit_info" value="<?php echo e($zonedschool->unit_info); ?>"></div>
                        <?php if($errors->first('unit_info')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('unit_info')); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Suffix Direction : <small><em>(If Applicable)</em></small></label>
                        <div class="">
                            <select name="suffix_dir" id="suffix_dir" class="custom-sel2">
                                <option value="">Select Direction</option>
                                <option value="N"  <?php if("N" == $zonedschool->suffix_dir): ?> selected <?php endif; ?>>North</option>
                                <option value="S" <?php if("S" == $zonedschool->suffix_dir): ?> selected <?php endif; ?>>South</option>
                                <option value="E" <?php if("E" == $zonedschool->suffix_dir): ?> selected <?php endif; ?>>East</option>
                                <option value="W" <?php if("W" == $zonedschool->suffix_dir): ?> selected <?php endif; ?>>West</option>
                            </select>
                        </div>
                        <?php if($errors->first('suffix_dir')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('suffix_dir')); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                
                    <div class="form-group">
                        <label class="control-label">City<span class="required">*</span> : </label>
                        <div class="">
                            <select name="city" id="city" class="custom-sel2" onchange="showOther('city');">
                                <option value="">Select City</option>
                                <?php $__currentLoopData = $city; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($value != ""): ?>
                                        <option value="<?php echo e($value); ?>" <?php if(strtolower($value) == strtolower($zonedschool->city)): ?> selected <?php endif; ?>><?php echo e($value); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" class="form-control mt-10 d-none" name="city_other"  id="city_other">
                        </div>
                        <?php if($errors->first('city')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('city')); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="control-label">State<span class="required">*</span> : </label>
                        <div class="">
                            <select name="state" id="state" class="custom-sel2">
                                <?php $stateArray = Config::get('variables.states') ?>

                                <?php $__currentLoopData = $stateArray; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stkey=>$stvalue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($stkey); ?>" <?php if($stvalue == $zonedschool->state): ?> selected <?php endif; ?>><?php echo e($stvalue); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <?php if($errors->first('state')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('state')); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                
                    <div class="form-group">
                        <label class="control-label">ZIP Code<span class="required">*</span> : </label>
                        <div class="">
                            <select name="zip" id="zip" class="custom-sel2" onchange="showOther('zip');">
                                <option value="">Select ZIP Code</option>
                                <?php $__currentLoopData = $zip; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($value != ""): ?>
                                        <option value="<?php echo e($value); ?>" <?php if($value == $zonedschool->zip): ?> selected <?php endif; ?>><?php echo e($value); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" class="form-control mt-10 d-none" name="zip_other"  id="zip_other">

                        </div>
                        <?php if($errors->first('zip')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('zip')); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Elementary School<span class="required">*</span> : </label>
                        <div class="">
                            <select name="elementary_school" id="elementary_school" class="custom-sel2" onchange="showOther('elementary_school');">
                                <option value="">Select Elementary School</option>
                                <?php $__currentLoopData = $elementary_school; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($value != ""): ?>
                                        <option value="<?php echo e($value); ?>" <?php if($value == $zonedschool->elementary_school): ?> selected <?php endif; ?>><?php echo e($value); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" class="form-control mt-10 d-none" name="elementary_school_other"  id="elementary_school_other">
                        </div>
                        <?php if($errors->first('elementary_school')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('elementary_school')); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Intermediate School<span class="required">*</span> : </label>
                        <div class="">
                            <select name="intermediate_school" id="intermediate_school" class="custom-sel2" onchange="showOther('intermediate_school');">
                                <option value="">Select Intermediate School</option>
                                <?php $__currentLoopData = $intermediate_school; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($value != ""): ?>
                                        <option value="<?php echo e($value); ?>" <?php if($value == $zonedschool->intermediate_school): ?> selected <?php endif; ?>><?php echo e($value); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" class="form-control mt-10 d-none" name="intermediate_school_other"  id="intermediate_school_other">
                        </div>
                        <?php if($errors->first('intermediate_school')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('intermediate_school')); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Middle School<span class="required">*</span> : </label>
                        <div class="">
                            <select name="middle_school" id="middle_school" class="custom-sel2" onchange="showOther('middle_school');">
                                <option value="">Select Middle School</option>
                                <?php $__currentLoopData = $middle_school; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($value != ""): ?>
                                        <option value="<?php echo e($value); ?>" <?php if($value == $zonedschool->middle_school): ?> selected <?php endif; ?>><?php echo e($value); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" class="form-control mt-10 d-none" name="middle_school_other"  id="middle_school_other">
                        </div>
                        <?php if($errors->first('middle_school')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('middle_school')); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="control-label">High School<span class="required">*</span> : </label>
                        <div class="">
                            <select name="high_school" id="high_school" class="custom-sel2" onchange="showOther('high_school');">
                                <option value="">Select High School</option>
                                <?php $__currentLoopData = $high_school; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($value != ""): ?>
                                        <option value="<?php echo e($value); ?>" <?php if($value == $zonedschool->high_school): ?> selected <?php endif; ?>><?php echo e($value); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" class="form-control mt-10 d-none" name="high_school_other"  id="high_school_other">
                        </div>
                        <?php if($errors->first('high_school')): ?>
                            <div class="mb-1 text-danger">
                                <?php echo e($errors->first('high_school')); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="box content-header-floating" id="listFoot">
            <div class="row">
                <div class="col-lg-12 text-right hidden-xs float-right">
                    <button type="Submit" class="btn btn-warning btn-xs submit" title="Save"><i class="fa fa-save"></i> Save </button>
                    <button type="Submit" name="save_exit" value="save_exit" class="btn btn-success btn-xs submit" title="Save & Exit"><i class="fa fa-save"></i> Save &amp; Exit</button>
                    <a class="btn btn-danger btn-xs" href="<?php echo e(url('/admin/ZonedSchool')); ?>" title="Cancel"><i class="fa fa-times"></i> Cancel</a>
                </div>
            </div>
        </div>
    </form>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
 <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.min.js"></script>

    <script type="text/javascript">
        $("form[name='add_zone_address']").validate({
            ignore: "",
            rules:{
                bldg_num:{
                    required:true,
                },
                "street_name":{
                  required: true
                },
                street_type:{
                  required:true,  
                },
                street_type_other:{
                  required: function(){
                    if($("#street_type").val()=="Other")
                        return true;
                    else
                        return false;
                  },  
                },
                city:{
                    required:true,
                },
                city_other:{
                  required: function(){
                    if($("#city").val()=="Other")
                        return true;
                    else
                        return false;
                  },  
                },
                zip:{
                    required:true,
                    
                },
                zip_other:{
                  required: function(){
                    if($("#zip").val()=="Other")
                        return true;
                    else
                        return false;
                  },  
                  maxlength: 11,
                    minlength: 5,
                    },
                state:{
                    required:true,
                },
                elementary_school:{
                    required: function() {
                        //returns true if email is empty
                        if(!$("#high_school").val() && !$("#intermediate_school").val() && !$("#middle_school").val())
                            return true;
                        else
                            return false;
                     },
                },
                elementary_school_other:{
                    required: function() {
                        //returns true if email is empty
                        if(!$("#high_school").val() && !$("#intermediate_school").val() && !$("#middle_school").val() && !$("#high_school_other").val() && !$("#intermediate_school_other").val() && !$("#middle_school_other").val() && $("#elementary_school").val() == "Other")
                            return true;
                        else
                            return false;
                     },
                },
                middle_school:{
                    required: function() {
                        //returns true if email is empty
                        if(!$("#high_school").val() && !$("#intermediate_school").val() && !$("#elementary_school").val())
                            return true;
                        else
                            return false;
                     },
                },
                middle_school_other:{
                    required: function() {
                        //returns true if email is empty
                        if(!$("#high_school").val() && !$("#intermediate_school").val() && !$("#elementary_school").val() && !$("#high_school_other").val() && !$("#intermediate_school_other").val() && !$("#elementary_school_other").val() && $("#middle_school").val() == "Other")
                            return true;
                        else
                            return false;
                     },
                },
                intermediate_school:{
                    required: function() {
                        //returns true if email is empty
                        if(!$("#high_school").val() && !$("#elementary_school").val() && !$("#middle_school").val())
                            return true;
                        else
                            return false;
                     },
                },
                intermediate_school_other:{
                    required: function() {
                        //returns true if email is empty
                        if(!$("#high_school").val() && !$("#elementary_school").val() && !$("#middle_school").val() && !$("#high_school_other").val() && !$("#elementary_school_other").val() && !$("#middle_school_other").val() && $("#intermediate_school").val() == "Other")
                            return true;
                        else
                            return false;
                     },
                },
                high_school:{
                    required: function() {
                        //returns true if email is empty
                        if(!$("#intermediate_school").val() && !$("#elementary_school").val() && !$("#middle_school").val())
                            return true;
                        else
                            return false;
                     },
                },
                high_school_other:{
                    required: function() {
                        //returns true if email is empty
                        if(!$("#intermediate_school").val() && !$("#elementary_school").val() && !$("#middle_school").val() && !$("#intermediate_school_other").val() && !$("#elementary_school_other").val() && !$("#middle_school_other").val() && $("#high_school").val() == "Other")
                            return true;
                        else
                            return false;
                     },
                },
            },
            messages:{
                elementary_school:{
                    required:'Atleast one school is required.'
                },
                intermediate_school:{
                    required:'Atleast one school is required.'
                },
                middle_school:{
                    required:'Atleast one school is required.'
                },
                higl_school:{
                    required:'Atleast one school is required.'
                },
                /*recommendation_due_date:{
                    required:'The Recommendation due date field is required.',
                    date:'The Date formate is not valid',
                },*/
                transcript_due_date:{
                    required:'The Transcript due date field is required.',
                    date:'The Date formate is not valid',
                },
                'program_grade_id[]':{
                  required:'The Program is required.',
                }
            },errorPlacement: function(error, element)
            {
                error.appendTo( element.parents('.form-group'));
                error.css('color','red');
            },
            submitHandler: function (form) {
                form.submit();
            }
          });
         $(".custom-sel2").select2();

         
         function showOther(objid)
         {
            if($("#"+objid).val() == "Other")
            {
                $("#"+objid+"_other").removeClass("d-none");
            }
            else
            {
                $("#"+objid+"_other").val("");
                $("#"+objid+"_other").addClass("d-none");
            }

         }

    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\vipuljadav\www\projects\laravel\MagnetMCPSS\app/Modules/ZonedSchool/Views/edit.blade.php ENDPATH**/ ?>