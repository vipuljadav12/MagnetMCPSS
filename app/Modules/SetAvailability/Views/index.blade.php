@extends("layouts.admin.app")
@section("title")
	Set Availability | {{config('APP_NAME',env("APP_NAME"))}}
@endsection
@section("content")
<div class="content-wrapper-in">
	<!-- InstanceBeginEditable name="Content-Part" -->
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Set Availability</div>
            <!--<div class=""><a href="add-district.html" class="btn btn-sm btn-secondary" title="">Add District</a></div>-->
        </div>
    </div>
    <form class="" action="{{url("admin/Availability/store")}}" method="post">
        {!! csrf_field() !!}
        <div class="tab-content bordered" id="myTabContent">
            <div class="tab-pane fade show active" id="preview01" role="tabpanel" aria-labelledby="preview01-tab">
                <div class="">
                    @include("layouts.admin.common.alerts")
                    <div class="form-group">
                        <select class="form-control custom-select selectProgram" name="program_id">
                            <option value="">Choose Option</option>
                            @forelse($programs as $p=>$program)
                            	<option value="{{$program->id }}">{{$program->name ?? ""}}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <div class="AjaxContent">
	                    
                    </div>
                </div>
            </div>
        </div>
    </form>    
	<!-- InstanceEndEditable --> 
</div>

@endsection
@section("scripts")
<script type="text/javascript">
    $(function()
    {
        generateContent();
        var lastSelected = $(document).find(".selectProgram option:selected");
    });
    $(document).on("click",".selectProgram",function(event)
    {
        lastSelected = $(document).find(".selectProgram option:selected");
    });
	$(document).on("change",".selectProgram",function(event)
	{
        event.preventDefault();
        let checkChanged = $(document).find(".changed").length;
        if(checkChanged == 0)
        {
            generateContent();
        }
        else
        {
            event.preventDefault();
            lastSelected.prop("selected",true);
            swal("Please save current changes");
        }
	});
    function generateContent()
    {
        let selected = $(document).find(".selectProgram").val();
        $.ajax(
        {
            url:"{{url('admin/Availability/getOptionsByProgram')}}"+"/"+selected,
            success:function(result)
            {
                $(document).find(".AjaxContent").html(result);
            }
        });
        matchWithTotal();
    };
    function matchWithTotal()
    {
        $(document).find(".availableSeat").each(function()
        {
            var grade = $(this).attr("data-id");
            var value = $(this).val();
            var total = $(document).find(".totalSeat[data-id="+grade+"]").val();
            if(parseInt(value) > parseInt(total))
            {
                $(this).parent().find("label").removeClass("d-none");
                $(this).addClass("notAllowed");
            }
            else
            {
                $(this).parent().find("label").addClass("d-none");
                $(this).removeClass("notAllowed");
            }
        });
        // $(document).find(".notAllowed:first").focus();
    }
    $(document).on("change input",".availableSeat,.totalSeat",function()
    {
        matchWithTotal();
        $(this).addClass("changed");
    });
    $(document).on("click","#optionSubmit",function(event)
    {
        let checkNotAllowed = $(document).find(".notAllowed").length;
        // alert(checkNotAllowed);
        if(checkNotAllowed > 0)
        {
            swal("Please review all errors");
            $(document).find(".notAllowed:first").focus();

            event.preventDefault();
            return false;
        }
            // event.preventDefault();
        $(document).find(".notAllowed:first").focus();
    });
</script>
@endsection