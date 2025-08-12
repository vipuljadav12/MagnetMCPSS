@extends('layouts.admin.app')
@section('title')
	Grade Eligibility Report
@endsection
@section('content')
<style type="text/css">
    .alert1 {
    position: relative;
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
        border-top-color: transparent;
        border-right-color: transparent;
        border-bottom-color: transparent;
        border-left-color: transparent;
    border-radius: 0.25rem;
}
.dt-buttons {position: absolute !important; padding-top: 5px !important;}
</style>
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Submissions Grade Verify Report</div>
        </div>
    </div>

    <div class="card shadow">
        @include("Reports::display_report_options", ["selection"=>$selection, "enrollment"=>$enrollment, "enrollment_id"=>$enrollment_id])
    </div>


    <div class="">
            
            <div class="tab-content bordered" id="myTabContent">
                <div class="tab-pane fade show active" id="needs1" role="tabpanel" aria-labelledby="needs1-tab">
                    
                    <div class="tab-content" id="myTabContent1">
                        <div class="tab-pane fade show active" id="grade1" role="tabpanel" aria-labelledby="grade1-tab">
                            <div class="">
                                <div class="conf_main_container mb-3">
                                    @php
                                        $academic_year_ary = getAcademicYears();
                                        $academic_grade_ary = config('variables.academic_grades');
                                    @endphp
                                    <div>
                                        <button class="btn btn-secondary mb-2" id="mgo_conf_btn" title="Set Needed Grade Configuration">Set Configuration</button>
                                    </div>
                                    <div id="mgo_conf_container" class="d-none">
                                        <div class="row col-12">
                                            <select class="form-control custom-select valid col-2 mr-2" id="academic_year" aria-invalid="false">
                                                <option value="">Select Academic Year</option>
                                                @foreach($academic_year_ary as $ayear)
                                                    <option value="{{$ayear}}"
                                                        @if($disctict_conf['academic_year'] == $ayear)
                                                            selected
                                                        @endif
                                                    >{{$ayear}}</option>
                                                @endforeach
                                            </select>
                                            <select class="form-control custom-select valid col-2 mr-3" id="academic_grade" aria-invalid="false">
                                                <option value="">Select Academic Grade</option>
                                                @foreach($academic_grade_ary as $agrade)
                                                    <option value="{{$agrade}}"
                                                        @if($disctict_conf['academic_grade'] == $agrade)
                                                            selected
                                                        @endif
                                                    >{{$agrade}}</option>
                                                @endforeach
                                            </select>
                                            <button id="save_conf_generate_report" class="btn btn-success">Save & Generate Report</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card shadow" id="response">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
@section('scripts')
        <div id="wrapperloading" style="display:none;"><div id="loading"><i class='fa fa-spinner fa-spin fa-4x'></i> <br> Loading Data...<br>It will take approx 5-10 minutes to finish. </div></div>
         <div class="modal fade" id="overrideAcademicGrade" tabindex="-1" role="dialog" aria-labelledby="employeependingLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="employeependingLabel">Alert</h5>
                            <button type="button" class="close overrideAcademicGradeNo" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
                        </div>
                        <div class="modal-body">
                                <div class="form-group">
                                    <label class="control-label">Comment : </label>
                                    <textarea class="form-control" name="grade_override_comment" id="grade_override_comment"></textarea>
                                    <input type="hidden" name="grade_override_status" id="grade_override_status">
                                    <input type="hidden" name="sid" id="sid">
                                </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" data-value="" id="overrideAcademicGradeYes" onclick="overrideAcademicGrade()">Submit</button>
                            <button type="button" class="btn btn-danger overrideAcademicGradeNo">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

	<script src="{{url('/resources/assets/admin')}}/js/bootstrap/dataTables.buttons.min.js"></script>
    <script src="{{url('/resources/assets/admin')}}/js/bootstrap/buttons.html5.min.js"></script>

    <script type="text/javascript">
        let confcontainer = $(document).find('#mgo_conf_container');
        getReport();
        function getReport(academic_year='', academic_grade='') {
            $("#wrapperloading").show();
            var gradeState = false;            
            $.ajax({
                type: 'get',
                dataType: 'JSON',
                async: false,
                url: "{{url('admin/Reports/missing/'.$enrollment_id.'/manual_grade_check/response')}}",
                data: {
                    academic_year: academic_year,
                    academic_grade: academic_grade
                },
                success: function(response) {
                        if (response.html == 'config_not_set') {
                            let msg = '<span class="m-2 text-center">Please set needed grade configurations first.</span>';
                            confcontainer.removeClass('d-none');
                            $("#response").html(msg);
                            // $('.conf_main_container').removeClass('d-none');
                        } else {
                            $("#response").html(response.html);
                            // $("#response").removeClass('d-none');
                            var dtbl_submission_list = $("#datatable").on( 'draw.dt',   function () { 
                                // console.log('page changed.');
                                $(document).find('input[data-plugin="switchery"]').each(function (idx, obj) {
                                    if($(this).css('display') != 'none'){
                                        new Switchery($(this)[0], $(this).data());
                                    }
                                });
                            }).DataTable({
                                "aaSorting": [],
                                // "pageLength": 2,
                                /*dom: 'Bfrtip',
                                buttons: [
                                        {
                                            extend: 'excelHtml5',
                                            title: 'Missing-Grade',
                                            text:'Export to Excel',

                                            //Columns to export
                                            exportOptions: {
                                                columns: ':not(.notexport)'
                                            }
                                        }
                                    ]*/
                            });

                             $(document).on('change','.grade_override', function() {
                                console.log('s');
                                  var click=$(this).prop('checked')==true ? 'Y' : 'N' ;
                                  $('#grade_override_status').val(click);
                                  $('#sid').val($(this).attr("id").replace("radio", ""));
                                  $('#grade_override_comment').val('');
                                  $('#overrideAcademicGrade').modal({
                                                                backdrop: 'static',
                                                                keyboard: false
                                                              });
                            });
                        }
                        // $(document).find(".js-switch").each(function(k,v) {
                        //     var elems = $(document).find('.js-switch'+k);
                        //     var switchery = new Switchery(elems[0]);
                        // });

                        // dtbl_submission_list.reload();

                      // Each column dropdown filter
                    
                }
            });
            $("#wrapperloading").hide();
        }
        


           function overrideAcademicGrade(){
              var comment = $('#grade_override_comment').val();
              var click = $('#grade_override_status').val();

              if(comment == ""){
                window.alert("Please enter comment.");
                $('#grade_override_comment').focus()
                return false; 
              }else{

                $.ajax({
                  type: "get",
                  url: '{{url('admin/Submissions/override/grade')}}',
                  data: {
                    id: $("#sid").val(),
                    status:click,
                    comment:comment
                  },
                  complete: function(data) {
                    console.log('success');
                    $('#overrideAcademicGrade').modal('hide');
                  }
                });
              }
            }

            $(document).on('click','.overrideAcademicGradeNo',function(){
              $('#overrideAcademicGrade').modal('hide');
              var rid = "radio" + $("#sid").val();
              if($("#"+rid).prop('checked')==true){
                $("#"+rid).trigger('click').prop('checked', false);
              }
              else{
                $("#"+rid).trigger('click').prop('checked', true);
              }

            });  
    
		

        function showMissingReport()
        {
            if($("#enrollment").val() == "")
            {
                alert("Please select enrollment year");
            }
            else if($("#reporttype").val() == "")
            {
                alert("Please select report type");
            }
            else
            {
                var link = "{{url('/')}}/admin/Reports/missing/"+$("#enrollment").val()+"/"+$("#reporttype").val();
                document.location.href = link;
            }
        }

      

        function exportMissing()
        {
            document.location.href="{{url('/admin/Reports/export/manual_grade_check')}}/"+$("#enrollment").val();
/*            if($("#filter_option").val() == "")
                document.location.href="{{url('/admin/Reports/export/manual_override')}}/"+$("#enrollment").val();
            else
                document.location.href="{{url('/admin/Reports/export/manual_override')}}/"+$("#enrollment").val()+"/"+$("#filter_option").val();
*/
        }


        /* Academic year-term Configuration start */
        // let confcontainer = $(document).find('#mgo_conf_container');
        $(document).on('click', '#mgo_conf_btn', function() {
            // let confcontainer = $(document).find('#mgo_conf_container');
            confcontainer.toggleClass('d-none');
        });
        $('#save_conf_generate_report').click(function() {
            let academic_year = $(document).find('#academic_year').val();
            if (academic_year == ''){
                alert('Academic Year is required');
                return false;
            }
            let academic_grade = $(document).find('#academic_grade').val();
            if (academic_grade == ''){
                alert('Academic Grade is required');
                return false;
            }
            confcontainer.addClass('d-none');
            getReport(academic_year, academic_grade);
        });
        /* Academic year-term Configuration end */

	</script>

@endsection