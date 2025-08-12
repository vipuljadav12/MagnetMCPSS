@extends('layouts.admin.app')
@section('title')
    Seat Status Report
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
</style>
@endsection
@section('content')

<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Seats Status Report <span class="font-16">[{{getDateTimeFormat($version_data->created_at)}}]</span></div>
            <div class=""><a class=" btn btn-secondary btn-sm" href="{{url('/admin/Reports/process/logs')}}" title="Go Back">Go Back</a></div>
        </div>
</div>


<div class="">
    <div class="tab-content bordered" id="myTabContent">
        <div class="tab-pane fade show active" id="needs1" role="tabpanel" aria-labelledby="needs1-tab">
            
            <div class="tab-content" id="myTabContent1">
                <div class="tab-pane fade show active" id="grade1" role="tabpanel" aria-labelledby="grade1-tab">
                    <div class="">
                        <div class="card shadow">
                            <div class="card-body">
                                <div class="" align="right">
                                   
                                </div> 
                                <div class="row col-md-12 pull-left" id="submission_filters"></div>

                                @if(!empty($final_data))
                                <div class="table-responsive" style="height: 704px; overflow-y: auto;">
                                    <table class="table table-striped mb-0" id="datatable">
                                        <thead>
                                            <tr>
                                                <th class="align-middle" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Name of Magnet Program/School/ Grade</th>
                                                <th class="align-middle" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Total Number of Available Seats</th>
                                                <th class="align-middle" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Total Number of Applicants  (1st &amp; 2nd Choice)</th>
                                                <th class="align-middle" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Number of Students Offered</th>
                                                <th class="align-middle" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Number of Students Declined</th>
                                                <th class="align-middle" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Number of Students Waitlist/ Declined Waitlisted for Other</th>
                                                <th class="align-middle" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Total Number of Offered and Accepted</th>
                                                <th class="align-middle" style="position: sticky; top: 0; background-color: #fff !important; z-index: 9999 !important">Total Number of Remaining Seats</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($final_data as $key=>$value)
                                                <tr>
                                                    <td class="text-center">{{$value['program_name']}}</td>
                                                    <td class="text-center">{{$value['total_seats']}}</td>
                                                    <td class="text-center">{{$value['total_applicants']}}</td>
                                                    <td class="text-center">{{$value['offered']}}</td>
                                                    <td class="text-center">{{$value['Decline']}}</td>
                                                    <td class="text-center">{{$value['Waitlisted']}}</td>
                                                    <td class="text-center">{{$value['Accepted']}}</td>
                                                    <td class="text-center">{{$value['remaining']}}</td>
                                                </tr>
                                            @endforeach

                                        </tbody>
                                    </table>
                                </div>
                                @else
                                    <div class="table-responsive text-center"><p>No Records found.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script src="{{url('/resources/assets/admin')}}/js/bootstrap/dataTables.buttons.min.js"></script>

<script src="{{url('/resources/assets/admin')}}/js/bootstrap/buttons.html5.min.js"></script>
<script type="text/javascript">
    var dtbl_submission_list = $("#datatable").DataTable({
        order: [],
        dom: 'Bfrtip',
        searching: false,
         buttons: [
                    {
                        extend: 'excelHtml5',
                        title: 'Seat-Status',
                        text:'Export to Excel',
                        //Columns to export
                   }
                ]
    });
   

    function showReport()
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
</script>

@endsection