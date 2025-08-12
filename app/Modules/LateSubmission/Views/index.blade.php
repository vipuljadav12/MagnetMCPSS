@extends('layouts.admin.app')
@section('title') Late Submission Process Selection @endsection
@section('content')
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Late Submission Process Selection</div>@if($display_outcome > 0 && Config::get("variables.rollback_process_selection") == 1) <div class="text-right d-none"><a href="javascript:void(0)" class="btn btn-secondary" onclick="rollBackStatus();">Roll Back Status</a>@endif</div>
        </div>
    </div>
    
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="preview02-tab" data-toggle="tab" href="#preview02" role="tab" aria-controls="preview02" aria-selected="true">Form Type</a></li>
            @if($displayother > 0)
                 <li class="nav-item"><a class="nav-link" href="{{url('/admin/LateSubmission/Availability/Show')}}">All Programs</a></li>
                 <li class="nav-item"><a class="nav-link" href="{{url('/admin/LateSubmission/Individual/Show')}}">Individual Program</a></li>
                <li class="nav-item"><a class="nav-link" href="{{url('/admin/LateSubmission/Population/Form')}}">Population Changes</a></li>
                <li class="nav-item"><a class="nav-link" href="{{url('/admin/LateSubmission/Submission/Result/1')}}">Submission Result</a></li>
            @endif
        </ul>
        <div class="tab-content bordered" id="myTabContent">
            @include('LateSubmission::Template.processing')
            <div class="form-group" id="error_msg"></div>
        </div>
@endsection
@section('scripts')
    <div id="wrapperloading" style="display:none;"><div id="loading"><i class='fa fa-spinner fa-spin fa-4x'></i> <br> Process is started.<br>It will take approx 15 minutes to finish. </div></div>

<script type="text/javascript">

     $('#process_selection').submit(function(event) {
        event.preventDefault();
        if($("#form_field").val() == "")
        {
            alert("Please select Form to proceed");
            return false;
        }
        document.location.href = "{{url('/admin/LateSubmission/Availability/Show/')}}/" + $("#form_field").val();

     });

     function fetchCDIStatus()
     {
        $("#wrapperloading").show();
        $.ajax({
            url:'{{url('/admin/LateSubmission/Generate/CDI/status')}}',
            type:"get",
            success:function(response){
                alert("CDI status verified");
                $("#wrapperloading").hide();
            }
        })
     }
     function fetchGradeStatus()
     {
        $("#wrapperloading").show();
        $.ajax({
            url:'{{url('/admin/LateSubmission/Generate/Grade/status')}}',
            type:"get",
            success:function(response){
                alert("Grade status verified.");
                $("#wrapperloading").hide();

            }
        })
        
     }
     function fetchRankStatus()
     {
        $("#wrapperloading").show();
        $.ajax({
            url:'{{url('/admin/LateSubmission/Generate/Priority/status')}}',
            type:"get",
            success:function(response){
                alert("Priority rank generated");
                $("#wrapperloading").hide();

            }
        })        
     }

    function rollBackStatus()
    {
        $("#wrapperloading").show();
        $.ajax({
            url:'{{url('/admin/LateSubmission/Revert/list')}}',
            type:"post",
            data: {"_token": "{{csrf_token()}}"},
            success:function(response){
                alert("All Statuses Reverted.");
                document.location.href = "{{url('/admin/LateSubmission')}}";
                $("#wrapperloading").hide();

            }
        })
    }

    $("#form_field").change(function()
    {
        if($(this).val() != "")
        {
            $("#wrapperloading").show();
            $.ajax({
                url:'{{ url('admin/LateSubmission/Process/Selection/validate/application/')}}/'+$(this).val(),
                type:"GET",
                success:function(response){
                    $("#wrapperloading").hide();
                    if(response != "OK")
                    {
                        $("#error_msg").html('<div class="alert1 alert-danger pl-20 pt-20"><ul>'+response+'</ul></div>');
                        $("#submit_btn").addClass("d-none");
                    }
                    else
                    {
                        $("#submit_btn").removeClass("d-none");
                        $("#error_msg").html("");
                    }
                    
                }
            })
        }
        else
        {
            $("#submit_btn").addClass("d-none");  
        }
    })

</script>
@endsection