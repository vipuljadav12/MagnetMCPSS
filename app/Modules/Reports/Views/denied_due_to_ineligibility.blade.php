@extends('layouts.admin.app')
@section('title')
    Denied due to Ineligibilities Submissions
@endsection
@section('styles')
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
    .custom-select2{
    margin: 5px !important;
}
.dt-buttons {position: absolute !important; padding-top: 5px !important;}

</style>
@endsection
@section('content')

<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5"> Denied due to Ineligibility Submissions</div>
        <div class=""><a class=" btn btn-secondary btn-sm" href="{{url('/admin/Reports/admin_review')}}" title="Go Back">Go Back</a></div>

    </div>
</div>

<div class="card shadow">
    <div class="card-body">
        <form class="">
            <div class="form-group">
                <label for="">Enrollment Year : </label>
                <div class="">
                    <select class="form-control custom-select" id="enrollment">
                        <option value="">Select Enrollment Year</option>
                        @foreach($enrollment as $key=>$value)
                            <option value="{{$value->id}}" @if($enrollment_id == $value->id) selected @endif>{{$value->school_year}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="">Report : </label>
                <div class="">
                        <select class="form-control custom-select" id="reporttype">
                            <option value="">Select Report</option>
                            <option value="grade">Missing Grade Report</option>
                            <option value="cdi">Missing CDI Report</option>
                            <option value="mcpss">Employee Verification Report</option>
                            <option value="manual_grade_check">Grade Eligibility Report</option>
                            <option value="denied_due_to_ineligibility" selected>Denied due to Ineligibility</option>

                        </select>
                </div>
            </div>
            <div class=""><a href="javascript:void(0);" onclick="showReport()" title="Generate Report" class="btn btn-success generate_report">Generate Report</a></div>
        </form>
    </div>
</div>
  <div class="">
            
            <div class="tab-content bordered" id="myTabContent">
                <div class="tab-pane fade show active" id="needs1" role="tabpanel" aria-labelledby="needs1-tab">
                    
                    <div class="tab-content" id="myTabContent1">
                        <div class="tab-pane fade show active" id="grade1" role="tabpanel" aria-labelledby="grade1-tab">
                            <div class="">
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
<script src="{{url('/resources/assets/admin')}}/js/bootstrap/dataTables.buttons.min.js"></script>

<script type="text/javascript">

    $("#wrapperloading").show();
        $.ajax({
            type: 'get',
            dataType: 'JSON',
            url: "{{url('admin/Reports/missing/'.$enrollment_id.'/denied_due_to_ineligibility/'.$application_id.'/response')}}",
            success: function(response) {

                    $("#response").html(response.html);
                    $("#wrapperloading").hide();
                    var dtbl_submission_list = $("#datatable").DataTable({"aaSorting": [],
                     dom: 'Bfrtip',
                     buttons: [
                            {
                                extend: 'excelHtml5',
                                title: '',
                                text:'Export to Excel',

                                //Columns to export
                                exportOptions: {
                                    columns: ':not(.notexport)'
                                }
                            }
                        ]
                    });

                  // Each column dropdown filter
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

    function reloadData(val)
    {
        var link = "{{url('/')}}/admin/Reports/missing/"+$("#enrollment").val()+"/duplicatestudent/"+val;
        document.location.href = link;
    }


    function exportMissing()
    {
        document.location.href="{{url('/admin/Reports/export/denied_due_to_ineligibility')}}/"+$("#enrollment").val()+"/{{$application_id}}";

    }
</script>

@endsection