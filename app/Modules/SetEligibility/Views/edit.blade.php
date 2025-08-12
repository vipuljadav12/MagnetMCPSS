@extends('layouts.admin.app')
@section('title')Edit Eligibility Value  | {{config('APP_NAME',env("APP_NAME"))}}  @endsection
@section('styles')
    <script type="text/javascript">var BASE_URL = '{{url('/')}}';</script>
    <style>
        input[type="checkbox"].styled-checkbox + label.label-xs {padding-left: 1.5rem;}
        .tooltip1 {
            position: relative;
            display: inline-block;
        }
        .tooltip1 .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: black;
            color: #fff;
            text-align: center;
            padding: 5px 0;
            border-radius: 6px;
            position: absolute;
            z-index: 1;
            margin-left: -60px;
            bottom: 115%;
            left: 50%;
        }
        .tooltip1:hover .tooltiptext {
            visibility: visible;
        }
        .tooltip1 .tooltiptext::after {
            content: " ";
            position: absolute;
            top: 100%; /* At the bottom of the tooltip */
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: black transparent transparent transparent;
        }
        .tooltip1 .tooltiptext-btm {top: 115%; bottom: auto;}
        .tooltip1 .tooltiptext-btm::after {
            content: "";
            position: absolute;
            top: -9px;
            bottom: auto;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: transparent transparent black transparent;
        }
    </style>
@endsection
@section('content')
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Edit Eligibility Value</div>
            <div class=""><a href="{{url('admin/SetEligibility')}}" class="btn btn-sm btn-secondary" title="Go Back">Go Back</a></div>
        </div>
    </div>
    @include("layouts.admin.common.alerts")
    <form action="{{url('admin/SetEligibility/update',$program->id)}}" method="post" name="" enctype= "multipart/form-data">
        {{csrf_field()}}
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">General</a></li>
            <li class="nav-item"><a class="nav-link" id="eligibility-tab" data-toggle="tab" href="#eligibility" role="tab" aria-controls="eligibility" aria-selected="false">Eligibility</a></li>
            <!--<li class="nav-item"><a class="nav-link" id="config-tab" data-toggle="tab" href="#config" role="tab" aria-controls="config" aria-selected="false">Configurations</a></li>-->
            <li class="nav-item"><a class="nav-link" id="process-tab" data-toggle="tab" href="#process" role="tab" aria-controls="process" aria-selected="false">Selection</a></li>
            <!--<li class="nav-item"><a class="nav-link" id="recommendation-tab" data-toggle="tab" href="#recommendation" role="tab" aria-controls="recommendation" aria-selected="true">Add Recommendation</a></li>-->
        </ul>
        <div class="tab-content bordered" id="myTabContent">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="">
                    <div class="card shadow">
                        <div class="card-header">Program Set Up</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 col-lg-6">
                                    <div class="form-group">
                                        <label class="control-label">Program Name : </label>
                                        <div class="">
                                            <input type="text" class="form-control" name="name" disabled="" value="{{$program->name}}">
                                        </div>
                                        @if($errors->first('name'))
                                            <div class="mb-1 text-danger">
                                                {{ $errors->first('name')}}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Applicant Group Filter 1 : </label>
                                        <div class="">
                                            <input type="text" class="form-control" name="applicant_filter1" disabled="" value="{{$program->applicant_filter1}}">
                                        </div>
                                        @if($errors->first('applicant_filter1'))
                                            <div class="mb-1 text-danger">
                                                {{ $errors->first('applicant_filter1')}}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Applicant Group Filter 2 : </label>
                                        <div class="">
                                            <input type="text" class="form-control" name="applicant_filter2" disabled="" value="{{$program->applicant_filter2}}">
                                        </div>
                                        @if($errors->first('applicant_filter2'))
                                            <div class="mb-1 text-danger">
                                                {{ $errors->first('applicant_filter2')}}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Applicant Group Filter 3 : </label>
                                        <div class="">
                                            <input type="text" class="form-control" name="applicant_filter3" disabled="" value="{{$program->applicant_filter3}}">
                                        </div>
                                        @if($errors->first('applicant_filter3'))
                                            <div class="mb-1 text-danger">
                                                {{ $errors->first('applicant_filter3')}}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="form-group">
                                        <label class="control-label"><strong>Available Grade Level (Check all that apply) :</strong> </label>
                                        <div class="row flex-wrap program_grade">
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" disabled="" id="table25" name="grade_lavel[]" value="PreK" {{in_array('PreK',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table25" class="custom-control-label">PreK</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="K" class="custom-control-input" disabled="" id="table06" {{in_array('K',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table06" class="custom-control-label">K</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="1" class="custom-control-input" disabled="" id="table07" {{in_array('1',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table07" class="custom-control-label">1</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="2" class="custom-control-input" disabled="" id="table08" {{in_array('2',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table08" class="custom-control-label">2</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="3" class="custom-control-input" disabled="" id="table09" {{in_array('3',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table09" class="custom-control-label">3</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="4" class="custom-control-input" disabled="" id="table10" {{in_array('4',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table10" class="custom-control-label">4</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="5" class="custom-control-input" disabled="" id="table11" {{in_array('5',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table11" class="custom-control-label">5</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="6" class="custom-control-input" disabled="" id="table12" {{in_array('6',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table12" class="custom-control-label">6</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="7" class="custom-control-input" disabled="" id="table13" {{in_array('7',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table13" class="custom-control-label">7</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="8" class="custom-control-input" disabled="" id="table14" {{in_array('8',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table14" class="custom-control-label">8</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="9" class="custom-control-input" disabled="" id="table15" {{in_array('9',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table15" class="custom-control-label">9</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="10" class="custom-control-input" disabled="" id="table16" {{in_array('10',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table16" class="custom-control-label">10</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]" value="11" class="custom-control-input" disabled="" id="table17" {{in_array('11',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table17" class="custom-control-label">11</label></div>
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="grade_lavel[]"value="12" class="custom-control-input" disabled="" id="table18" {{in_array('12',explode(',',$program->grade_lavel))?'checked':''}}> 
                                                    <label for="table18" class="custom-control-label">12</label></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label"><strong>Parent Submission Form :</strong> </label>
                                        <div class="">
                                            <select class="form-control custom-select" disabled="">
                                                <option>{{findFormName($program->parent_submission_form)}}</option>
                                            </select>
                                        </div>
                                    </div>

                                     <div class="form-group d-flex justify-content-between">
                                        <label for="" class="control-label">Sibling Enabled : </label>
                                        <div class=""><input id="chk_100" type="checkbox" class="js-switch js-switch-1 js-switch-xs" data-size="Small" name="sibling_enabled"  {{$program->sibling_enabled=='Y'?'checked':''}}  disabled /></div>
                                    </div>
                                    <div class="form-group d-flex justify-content-between @if($program->sibling_enabled == "N") d-none @endif" id="sibling_check">
                                        <label for="" class="control-label">Sibling Program Check : </label>
                                        <div class=""><input id="chk_03" type="checkbox" class="js-switch js-switch-1 js-switch-xs" data-size="Small" name="silbling_check"  {{$program->silbling_check=='Y'?'checked':''}} disabled /></div>
                                    </div>

                                    <div class="form-group d-flex justify-content-between">
                                        <label for="" class="control-label">Existing Program Check : </label>
                                        <div class=""><input id="chk_03" type="checkbox" class="js-switch js-switch-1 js-switch-xs" data-size="Small" name="existing_magnet_program_alert"  {{$program->existing_magnet_program_alert=='Y'?'checked':''}} disabled /></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label"><strong>Select School :</strong> </label>
                                        <div class="">
                                            <select class="form-control custom-select" disabled>
                                                <option>{{$program->magnet_school}}</option>
                                            </select>
                                        </div>
                                    </div>



                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow">
                        <div class="card-header">Priority Set Up</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 col-lg-6">
                                    <div class="form-group">
                                        <label class="control-label"><strong>Select Priority :</strong> </label>
                                        <div class="d-flex flex-wrap">
                                            <div class="mr-20 w-90">
                                                <div class="custom-control custom-radio"><input type="radio" name="priority[]" value="none" class="custom-control-input" disabled="" id="table28" {{in_array('none',explode(',',$program->priority))?'checked':''}}> 
                                                    <label for="table28" class="custom-control-label">None</label></div>
                                            </div>
                                            @forelse($priorities as $p=>$priority)
                                                <div class="mr-20 w-90">
                                                    <div class="custom-control custom-radio">
                                                        <input type="radio" name="priority[]" value="{{$priority->id}}" class="custom-control-input" disabled="" id="priority{{$p}}" {{in_array($priority->id,explode(',',$program->priority))?'checked':''}}> 
                                                        <label for="priority{{$p}}" class="custom-control-label">{{$priority->name}}</label></div>
                                                </div>
                                            @empty
                                            @endforelse
                                            {{-- <div class="mr-20 w-90">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="priority[]" value="1" class="custom-control-input" disabled="" id="table29" {{in_array('1',explode(',',$program->priority))?'checked':''}}>
                                                    <label for="table29" class="custom-control-label">1</label></div>
                                            </div>
                                            <div class="mr-20 w-90">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="priority[]" value="2" class="custom-control-input" disabled="" id="table30" {{in_array('2',explode(',',$program->priority))?'checked':''}}>
                                                    <label for="table30" class="custom-control-label">2</label></div>
                                            </div>
                                            <div class="mr-20 w-90">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" name="priority[]" value="3" class="custom-control-input" disabled="" id="table31" {{in_array('3',explode(',',$program->priority))?'checked':''}}>
                                                    <label for="table31" class="custom-control-label">3</label></div>
                                            </div> --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="eligibility" role="tabpanel" aria-labelledby="eligibility-tab">
                @include("SetEligibility::eligibility_edit")
            </div>
            <div class="tab-pane fade" id="process" role="tabpanel" aria-labelledby="process-tab">
                @include("SetEligibility::selection")
            </div>
           
        </div>

        <div class="box content-header-floating" id="listFoot">
            <div class="row">
                <div class="col-lg-12 text-right hidden-xs float-right">
                   {{--  <button type="submit" class="btn btn-warning btn-xs"><i class="fa fa-save"></i> Save </button>
                    <button type="submit" class="btn btn-success btn-xs" name="save_edit" value="save_edit"><i class="fa fa-save"></i> Save &amp; Edit</button> --}}
                    <button type="submit" class="btn btn-warning btn-xs" name="submit" value="Save"><i class="fa fa-save"></i> Save </button>
                   <button type="submit" name="save_exit" value="save_exit" class="btn btn-success btn-xs submit"><i class="fa fa-save"></i> Save &amp; Exit</button>
                   <a class="btn btn-danger btn-xs" href="{{url('/admin/SetEligibility')}}"><i class="fa fa-times"></i> Cancel</a>
                    {{-- <a class="btn btn-danger btn-xs" onclick="deletefunction({{$program->id}})" href="javascript:void(0)"><i class="fa fa-trash"></i> Delete</a> --}}
                </div>
            </div>
        </div>
        

            @include("Program::Template.eligibility_modal_grade")
            @forelse($eligibilities as $key=>$eligibility)
            @if($eligibility['name']=='Recommendation Form111')
                <div class="modal fade" id="modal_4" tabindex="-1" role="dialog" aria-labelledby="modal_4Label" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div class="modal-title" id="modal_4Label">Edit Eligibility - Recommendation Form 1</div>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
                            </div>
                            <div class="modal-body">
                                <div class="card shadow mb-20 d-none">
                                    <div class="card-header">Used in Determination Method</div>
                                    <div class="card-body">
                                        <div class="d-flex flex-wrap">
                                            <div class="d-flex mb-10 mr-30">
                                                <div class="mr-10">Basic Method Only Active : </div>
                                                <input id="chk_3" type="checkbox" class="js-switch js-switch-1 js-switch-xs" data-size="Small">
                                            </div>
                                            <div class="d-flex mb-10 mr-30">
                                                <div class="mr-10">Combined Scoring Active : </div>
                                                <input id="chk_4" type="checkbox" class="js-switch js-switch-1 js-switch-xs" data-size="Small" checked>
                                            </div>
                                            <div class="d-flex mb-10">
                                                <div class="mr-10">Final Scoring Active : </div>
                                                <input id="chk_5" type="checkbox" class="js-switch js-switch-1 js-switch-xs" data-size="Small">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card shadow">
                                    <div class="card-body">
                                        <div class="">
                                            <div class="form-group">
                                                <label class="control-label">Select Prior Developed Recommendation Form: : </label>
                                                <div class="">
                                                    <select class="form-control custom-select" name="eligibility_grade_lavel[{{$eligibility['id']}}][]">
                                                        <option value="HCS STEM Teacher Recommendation">HCS STEM Teacher Recommendation</option>
                                                        <option value="HCS Principal Recommendation">HCS Principal Recommendation</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary " data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-success " data-dismiss="modal">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        @empty
        @endforelse
        

    </form>
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel1">Set Values</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" id="modalContent">
            
          </div>
          <div class="modal-footer">
            <button type="button" id="extraValueFormBtn" {{-- form="extraValueForm" --}} class="btn btn-success extraValueFormBtn">Save</button>
            <!--<button type="button" class="btn btn-secondary modalDismiss" data-dismiss="modal">Close</button>-->
          </div>
        </div>
      </div>
    </div>
@endsection
@section('scripts')
    <script src="{{asset('resources/assets/common/js/jquery.validate.min.js')}}"></script>
    <script src="{{asset('resources/assets/common/js/additional-methods.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/assets/admin/plugins/jquery-ui/jquery-ui.min.js')}}"></script>
    <script type="text/javascript">
        //44 for seteligibility script start
        $(document).on("change",".valueRequiredSelect",function()
        {
            let selected = $(this).val();
            // alert(selected);
            if(selected == "Y")
            {
                $(this).parent().parent().find(".editPopBtn").removeClass("d-none");
            }
            if(selected == "N" || selected == "X")
            {
                $(this).parent().parent().find(".editPopBtn").addClass("d-none");
            }
            $(this).parent().parent().find(".MinimumEligibility.ForSelected"+selected).removeClass("d-none");
            $(this).parent().parent().find(".MinimumEligibility.ForSelected"+selected).find("select").attr('disabled',false);
            $(this).parent().parent().find(".MinimumEligibility.ForSelected"+selected).siblings().addClass("d-none");
            $(this).parent().parent().find(".MinimumEligibility.ForSelected"+selected).siblings().find("select").attr('disabled',true);
        });

        $(document).on("click",".openPopUpForData",function()
        {
            setDataForPopUp($(this));           
        });
        $(document).on("click",".openPopUpForData_ls",function()
        {
            // For late submission
            setDataForPopUp($(this), 1);           
        });

        function setDataForPopUp(element, extra_val=0) {
            $(document).find("#modalContent").html("");
            var eligibility_type  = element.attr("data-id");
            var program_id  = element.attr("data-program-id");
            var eligibility_id  = element.attr("data-eligibility-id");
            $.ajax({
                url:"{{url('admin/SetEligibility/extra_values')}}",
                data:{
                    eligibility_type:eligibility_type,
                    program_id:program_id,
                    eligibility_id:eligibility_id,
                    late_submission:extra_val,
                    application_id: $("#application_id").val()
                },
                success:function(result)
                {
                    $(document).find("#modalContent").html(result);
                }
            });
            $(document).find("#exampleModal").modal();
        }

        $(document).on("click","#extraValueFormBtn",function()
        {   
            var formData = $('#extraValueForm').serialize();
            // alert();
            $.ajax({
                url:"{{url('admin/SetEligibility/extra_values/save')}}",
                data:formData,
                method:"post",
                success:function(result)
                {
                    $('#exampleModal').modal('hide');
                }
            });
            {{-- $("#extraValueForm").ajaxForm({url: '{{url('admin/SetEligibility/extra_values/save')}}', type: 'post'}) --}}
            // $(document).find("#extraValueForm").submit();
        });
        //44 for seteligibility script End
        $(".alert").delay(2000).fadeOut(1000);
        $(document).on("click",".add-option-list-custome",function(){
            var i = $(this).parent().siblings(".option-list-custome").children(".form-group").length + 1;
            var a = '<div class="form-group">'+
                '<label class="control-label">Option '+i+' : </label>'+
                '<div class=""><input type="text" class="form-control" value=""></div>'+
                '</div>';
            $(this).parent().siblings(".option-list-custome").append(a);
        });
        $(".chk_6").on("change", function(){
            if($(this).is(":checked")) {
                $(".custom-field-list").show();
            }
            else {
                $(".custom-field-list").hide();
            }
        })
        $(".chk_7").on("change", function(){
            if($(this).is(":checked")) {
                $(".option-list-outer").show();
            }
            else {
                $(".option-list-outer").hide();
            }
        })
        $(document).on("click", ".add-new" , function(){
            var cc = $("#first").clone().addClass('list').removeAttr("id");
            $("#inowtable tbody").append(cc);
        });
        function del(id){
            $(id).parents(".list").remove();
        }
        //$(document).ready(function(){
        //$('#cp2').colorpicker({
        //
        //});

        $(function () {
            $('#cp2').colorpicker().on('changeColor', function (e) {
                $('#chgcolor')[0].style.backgroundColor = e.color.toHex();
            });
        });
        $("#chk_03").on("change",function(){
            if($("#chk_03").is(":checked")) {
                $("#zone").show();
            }
            else {
                $("#zone").hide();
            }
        });


        function changeApplication(value)
        {
            document.location.href = "{{url('/admin/SetEligibility/edit/'.$program->id)}}/"+value;
        }
        //});
    </script>
    <script>
        /*$(".template-select").on("change",function(){
            var a = $(this).val();
            if(a == 4){
                $(".option4").addClass("d-none");
            }
            else {
                $(".option4").removeClass("d-none");
            }
        });
        $(".template-type").on("change",function(){
            var a = $(this).val();
            if(a == 1){
                $(".template-type-1").removeClass("d-none");
                $(".template-type-2").addClass("d-none");
            }
            else if(a == 2){
                $(".template-type-1").addClass("d-none");
                $(".template-type-2").removeClass("d-none");
            }
            else {
                $(".template-type-1").addClass("d-none");
                $(".template-type-2").addClass("d-none");
            }
        });
        $(document).on("click",".first-click",function(){
            var a = $(".template-select").val();
            if(a == 1) {
                $(".interview-list").removeClass('d-none');
                $(".audition-list").addClass('d-none');
                $(".committee-list").addClass('d-none');
                $(".academic-list").addClass('d-none');
            }
            else if(a == 2) {
                $(".interview-list").addClass('d-none');
                $(".audition-list").removeClass('d-none');
                $(".committee-list").addClass('d-none');
                $(".academic-list").addClass('d-none');
            }
            else if(a == 3) {
                $(".interview-list").addClass('d-none');
                $(".audition-list").addClass('d-none');
                $(".committee-list").removeClass('d-none');
                $(".academic-list").addClass('d-none');
            }
            else if(a == 4) {
                $(".interview-list").addClass('d-none');
                $(".audition-list").addClass('d-none');
                $(".committee-list").addClass('d-none');
                $(".academic-list").removeClass('d-none');
            }
            else {
                $(".interview-list").addClass('d-none');
                $(".audition-list").addClass('d-none');
                $(".committee-list").addClass('d-none');
                $(".academic-list").addClass('d-none');
            }
        });
        function custsort() {
            $(".form-list").sortable({
                handle: ".handle"
            });
            $(".form-list").disableSelection();
        };
        function custsort1() {
            $(".question-list").sortable({
                handle: ".handle1"
            });
            $(".question-list").disableSelection();
        };
        function custsort2() {
            $(".option-list").sortable({
                handle: ".handle2"
            });
            $(".option-list").disableSelection();
        };


        $(document).on("click", ".add-ranking" , function(){
            var i = $(this).parents(".template-type-2").find(".form-group").length + 1;
            var a = '<div class="form-group">'+
                '<label class="">Numeric Ranking '+i+' : </label>'+
                '<div class=""><input type="text" class="form-control"></div>'+
                '</div>';
            var cc = $(this).parents(".template-type-2").find(".mb-20");
            $(a).insertBefore(cc);
        });
        $(document).on("click", ".add-question" , function(){
            var i = $(this).parent().parent(".card-body").find(".question-list").children(".form-group").length + 1;
            var question =  '<div class="form-group border p-15">'+
                '<label class="control-label d-flex flex-wrap justify-content-between"><span><a href="javascript:void(0);" class="mr-10 handle1" title=""><i class="fas fa-arrows-alt"></i></a>Question '+i+' : </span>'+
                '<a href="javascript:void(0);" class="btn btn-secondary btn-sm add-option" title="">Add Option</a>'+
                '</label>'+
                '<div class=""><input type="text" class="form-control" value=""></div>'+
                '<div class="option-list mt-10"></div>'+
                '</div>';
            $(this).parent().parent(".card-body").find(".question-list").append(question);
            custsort1();
        });
        $(document).on("click", ".add-header" , function(){
            var i = $(".form-list").children(".card").length + 1;
            var header =    '<div class="card shadow">'+
                '<div class="card-header">'+
                '<div class="form-group">'+
                '<label class="control-label"><a href="javascript:void(0);" class="mr-10 handle" title=""><i class="fas fa-arrows-alt"></i></a> Header Name '+i+': </label>'+
                '<div class=""><input type="text" class="form-control" value=""></div>'+
                '</div>'+
                '</div>'+
                '<div class="card-body">'+
                '<div class="form-group text-right"><a href="javascript:void(0);" class="btn btn-secondary btn-sm add-question" title="">Add Question</a></div>'+
                '<div class="question-list p-15"></div>'+
                '</div>'+
                '</div>';
            $(this).parents(".card-body").find(".form-list").append(header);
            custsort();
        });
        $(document).on("click", ".add-option" , function(){
            var i = $(this).parent().parent(".form-group").children(".option-list").children(".form-group").length + 1;
            var option =    '<div class="form-group border p-10">'+
                '<div class="row">'+
                '<div class="col-12 col-md-7 d-flex flex-wrap align-items-center">'+
                '<a href="javascript:void(0);" class="mr-10 handle2" title=""><i class="fas fa-arrows-alt"></i></a>'+
                '<label for="" class="mr-10">Option '+i+' : </label>'+
                '<div class="flex-grow-1"><input type="text" class="form-control"></div>'+
                '</div>'+
                '<div class="col-10 col-md-5 d-flex flex-wrap align-items-center">'+
                '<label for="" class="mr-10">Point : </label>'+
                '<div class="flex-grow-1"><input type="text" class="form-control"></div>'+
                '</div>'+
                '</div>'+
                '</div>';
            $(this).parent().parent(".form-group").children(".option-list").append(option);
            custsort2();
        });
        ///method slection in selection tab
        $(function () {
            selectionMethod($('#table27:checked'));
            selectionMethod($('#table23:checked'));
            selectionMethod($('#table24:checked'));
        });
        $("input[name='selection_method']").click(function () {
            selectionMethod(this);
        });
        function selectionMethod(method) {
            if (($(method).attr('id') == 'table24' && $(method).is(":checked"))) {
                $('#seat_availability_enter_by').find("option[value='Manual Entry']").css('display','');
            } else if (($(method).attr('id') == 'table23' && $(method).is(":checked")) || $(method).attr('id') == 'table27' && $(method).is(":checked")) {

                $('#seat_availability_enter_by').find("option[value='Manual Entry']").css('display','none');
            }
        }
        var committee_score_id;
        var rating_priority_id;
        var lottery_number_id;
        var combine_score_id;
        var audition_score_id;
        var final_score_id;
        $(function () {
            committee_score_id= $('#committee_score').children("option:selected").text();
            rating_priority_id = $('#rating_priority').children("option:selected").text();
            lottery_number_id = $('#final_score').children("option:selected").text();
            combine_score_id = $('#lottery_number').children("option:selected").text();
            audition_score_id = $('#combine_score').children("option:selected").text();
            final_score_id = $('#audition_score').children("option:selected").text();
            $('option').each(function () {
                $(this).removeClass('d-none');
            });
            $("option[value=" + rating_priority_id + "] ,option[value=" + committee_score_id + "],option[value=" + lottery_number_id + "],option[value=" + combine_score_id + "],option[value=" + audition_score_id + "],option[value=" + final_score_id + "] ").each(function () {
                $(this).addClass('d-none');
            });
        });
        $('.ranking_system').on('change',function () {
            rakingSystem(this);
        });
        function rakingSystem(attr)
        {
            if($(attr).attr('id')=='committee_score') {
                committee_score_id = $("#committee_score option:selected").text();
            }
            else if($(attr).attr('id')=='rating_priority')
            {
                rating_priority_id = $("#rating_priority option:selected").text();
            }
            else if($(attr).attr('id')=='final_score')
            {
                final_score_id = $("#final_score option:selected").text();
            }
            else if($(attr).attr('id')=='lottery_number')
            {
                lottery_number_id = $("#lottery_number option:selected").text();
            }
            else if($(attr).attr('id')=='combine_score')
            {
                combine_score_id = $("#combine_score option:selected").text();
            }
            else if($(attr).attr('id')=='audition_score')
            {
                audition_score_id = $("#audition_score option:selected").text();
            }
            $('option').each(function () {
                $(this).removeClass('d-none');
            });
            $("option[value=" + rating_priority_id + "] ,option[value=" + committee_score_id + "],option[value=" + lottery_number_id + "],option[value=" + combine_score_id + "],option[value=" + audition_score_id + "],option[value=" + final_score_id + "] ").each(function () {
                $(this).addClass('d-none');
            });
        }
        //eligibility tab
        $(function () {
            disableCombinedScoring($('#combined_scoring'));
            $('#combined_scoring').on('change',function () {
                if ($('#basic_method_only').prop('checked')!=true)
                {
                    $('#basic_method_only').trigger('click');
                }
                disableCombinedScoring(this);
            });
            function disableCombinedScoring(checkbox) {
                if ($(checkbox).prop('checked')!=true)
                {
                    $("input[name='weight[]']").attr('disabled','disabled').val('');
                    $("select[name='determination_method[]']").each(function () {
                        $(this).find("option[value='Combined']").addClass('d-none').prop("selected", false);
                    });
                   // $("#combined_eligibility").find("option[value='Weighted Scores']").addClass('d-none').prop("selected", false);
                    $("#combined_eligibility").find("option").addClass('d-none').prop("selected", false);
                }
                else{
                    $('.determination_method').each(function () {
                        if ($(this).val() != 'Basic') {
                            $(this).parent().parent().find('.weight').removeAttr('disabled');
                        }

                    });
                    // $("input[name='weight[]']").removeAttr('disabled');
                    $("select[name='determination_method[]']").each(function () {
                        $(this).find("option[value='Combined']").removeClass('d-none');
                    });
                    //$("#combined_eligibility").find("option[value='Weighted Scores']").removeClass('d-none');
                    $("#combined_eligibility").find("option").removeClass('d-none');
                }
            }

            disableBasicMethod($('#basic_method_only'));
            $('#basic_method_only').on('change',function () {
                if ($('#combined_scoring').prop('checked')!=true)
                {
                    $('#combined_scoring').trigger('click');
                }
                disableBasicMethod(this);
            });
            function disableBasicMethod(checkbox) {
                if ($(checkbox).prop('checked')!=true)
                {
                    $("select[name='determination_method[]']").each(function () {
                        $(this).find("option[value='Basic']").addClass('d-none').prop("selected", false);
                    });
                   // $("#combined_eligibility").find("option[value='Weighted Scores']").addClass('d-none').prop("selected", false);
                    $("#combined_eligibility").find("option").addClass('d-none').prop("selected", false);
                }
                else{
                    $("select[name='determination_method[]']").each(function () {
                        $(this).find("option[value='Basic']").removeClass('d-none');
                    });
                    //$("#combined_eligibility").find("option[value='Weighted Scores']").removeClass('d-none');
                    //$("#combined_eligibility").find("option").removeClass('d-none');
                }
            }

           

        });

        $(".program_grade").find(".custom-control-input").each(function()
        {
            $(this).change(function() { gradeLavel(this);})
            gradeLavel($('#'+$(this).attr("id")+':checked'));
        })
        */
 
    /* gradeLavel($('#table25:checked'));
        gradeLavel($('#table06:checked'));
        gradeLavel($('#table07:checked'));
        gradeLavel($('#table08:checked'));
        gradeLavel($('#table09:checked'));
        gradeLavel($('#table10:checked'));
        gradeLavel($('#table11:checked'));
        gradeLavel($('#table12:checked'));
        gradeLavel($('#table13:checked'));
        gradeLavel($('#table14:checked'));
        gradeLavel($('#table15:checked'));
        gradeLavel($('#table16:checked'));
        gradeLavel($('#table17:checked'));
        gradeLavel($('#table18:checked'));*/
      /*  $('#table25,#table06,#table07,#table08,#table09,#table10,#table11,#table12,#table13,#table14,#table15,#table16,#table17,#table18').change(function () {
            gradeLavel(this);
        });*/
        /*function gradeLavel(check) {

            if($(check).prop('checked')==true)
            {
                // $('.prek').removeClass('d-none');
                // alert($(check).val());
                $("."+$(check).val()).each(function () {
                    $(this).removeClass('d-none');
                });

            }
            else{
                $("."+$(check).val()).each(function () {
                    $(this).addClass('d-none');
                });
            }
        }

        $('.determination_method').each(function () {
            disableWeight1($(this).children("option:selected"));
        });
        $('.determination_method').change(function () {
            if ($(this).val() == 'Basic') {
                $(this).parent().parent().find('.weight').attr('disabled', 'disabled');
            }
            else{
                $(this).parent().parent().find('.weight').removeAttr('disabled');
            }
        });
        function disableWeight1(select) {
            if ($(select).val() == 'Basic') {
                // alert($(select).val())
                $(select).parent().parent().parent().find('.weight').attr('disabled', 'disabled');
            }
            else{
                $(select).parent().parent().parent().find('.weight').removeAttr('disabled');
            }
        }
        var deletefunction = function(id){
            swal({
                title: "Are you sure you would like to delete this Program ?",
                text: "",
                // type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes",
                closeOnConfirm: false
            }).then(function() {
                window.location.href = '{{url('/')}}/admin/SetEligibility/delete/'+id;
            });
        };*/
    </script>
    <script src="{{asset('resources/assets/admin/js/program_eligibility.js?'.rand())}}"></script>
    <script type="text/javascript">
        $(".table-striped").find("tr").each(function() {
            changeIcon($(this), 1);    
        })
        
    </script>
@endsection
