@extends('layouts.admin.app')
@section('title')
	View/Edit Submissions
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
.custom-select{
    margin: 5px !important;
}
</style>
<link href="https://cdn.datatables.net/buttons/1.5.1/css/buttons.dataTables.min.css" rel="stylesheet" />
	<div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">View/Edit Submissions</div>
            <div class=" d-none">
                <div class="d-inline-block position-relative">
                    <a href="javascript:void(0);" onClick="custfilter();" class="d-inline-block border pt-5 pb-5 pl-10 pr-10" title=""><span class="d-inline-block mr-10">Filter</span> <i class="fas fa-caret-down"></i></a>
                    <div class="filter-box border shadow" style="display: none;">
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline01" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline01">Submission ID</label>
                            </div>
                        </div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline001" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline001">State ID</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline02" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline02">Open Enrollment</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline03" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline03">First Name</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline04" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline04">Last Name</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline05" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline05">Race</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline06" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline06">Birthday</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline07" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline07">Current School</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline08" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline08">Current Grade</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline09" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline09">Next Grade</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline10" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline10">Status</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline11" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline11">Awarded School</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline12" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline12">First Choice</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline13" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline13">Second Choice</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline14" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline14">Third Choice</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline15" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline15">Form</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline16" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline16">Student Status</label></div></div>
                        <div class="">
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" id="customRadioInline17" name="customRadioInline00" class="custom-control-input" value="">
                                <label class="custom-control-label" for="customRadioInline17">Special Accommodations</label></div></div>
                    </div>
                </div>
            </div>
    	</div>
    </div>
    <div class="card shadow">
        <div class="card-body">
    	@include("layouts.admin.common.alerts")
        <div class="row col-md-12" id="submission_filters"></div>
            <div class="pt-20 pb-20">
                <div class="table-responsive">
                    <table id="datatable" class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th class="align-middle">Submission ID</th>
                                <th class="align-middle">State ID</th>
                                <th class="align-middle">Enrollment</th>
                                <th class="align-middle">Name</th>
                                <th class="align-middle">Race</th>
                                <th class="align-middle">Birthday</th>
                                <th class="align-middle">Current School</th>
                                <th class="align-middle">Current Grade</th>
                                <th class="align-middle">Next Grade</th>
                                <th class="align-middle">Created at</th>
                                <th class="align-middle">Form</th>
                                <th class="align-middle">Status</th>
                                <th class="align-middle">Awarded School</th>
                                <th class="align-middle">New/Current</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($submissions as $key=>$submission)
                                <tr>
                                    <td class="text-center">
                                        @if((checkPermission(Auth::user()->role_id,'Submissions/edit') == 1))
                                        <a href="{{ url('admin/Submissions/edit',$submission->id)}}" title="edit">
                                            {{$submission->id}}</a>
                                        <div class="">
                                            <a href="{{ url('admin/Submissions/edit',$submission->id)}}" class="font-18 ml-5 mr-5" title=""><i class="far fa-edit"></i></a>
                                        </div>
                                        @else
                                            {{$submission->id}}
                                        @endif
                                    </td>
                                    <td class="">{{$submission->student_id}}</td>
                                    <td class="">{{$submission->school_year}}</td>
                                    <td class="">{{$submission->first_name}} {{$submission->last_name}}</td>
                                    <td class="">{{$submission->race}}</td>
                                    <td class="">{{$submission->birthday}}</td>
                                    <td class="">{{$submission->current_school}}</td>
                                    <td class="">{{$submission->next_grade}}</td>
                                    <td class="">{{$submission->current_grade}}</td>
                                    <td class="">{{getDateTimeFormat($submission->created_at)}}</td>
                                    <td class="">{{findSubmissionForm($submission->application_id)}}</td>
                                    <td class="">
                                            @if($submission->submission_status == "Active")
                                                <div class="alert1 alert-success p-10 text-center d-block">{{$submission->submission_status}}</div> 
                                            @else
                                                    <div class="alert1 alert-warning p-10 text-center d-block">{{$submission->submission_status}}</div>
                                            @endif
                                    </td>
                                    <td>{{$submission->zoned_school}}</td>
                                    <td class="">
                                        @if($submission->student_id != "")
                                            <div class="alert1 alert-success p-10 text-center d-block">Current</div> 
                                        @else
                                                <div class="alert1 alert-warning p-10 text-center d-block">New</div>
                                        @endif
                                    
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
<script src="{{url('/resources/assets/admin/js/boostrap/dataTables.buttons.min.js')}}"></script>
<script src="{{url('/resources/assets/admin/js/boostrap/jszip.min.js')}}"></script>
<script src="{{url('/resources/assets/admin/js/boostrap/buttons.html5.min.js')}}"></script>
	<script type="text/javascript">
		var dtbl_submission_list = $("#datatable").DataTable({"aaSorting": [],
             dom: 'Bfrtip',
             buttons: [
                    {
                        extend: 'excelHtml5',
                        title: 'SubmissionData',
                        text:'Export to Excel'
                        //Columns to export
                        //exportOptions: {
                       //     columns: [0, 1, 2, 3,4,5,6]
                       // }
                    }
                ]
            });

         // Each column dropdown filter
        $("#datatable thead th").each( function ( i ) {
            // Disable dropdown filter for disalble_dropdown_ary (index=0)
            var disalble_dropdown_ary = [0, 1, 3, 5, 9, 13];
            if ($.inArray(i, disalble_dropdown_ary) == -1) {
                var column_title = $(this).text();
                var select = $('<select class="form-control col-md-3 custom-select custom-select2"><option value="">Select '+column_title+'</option></select>')
                    .appendTo( $('#submission_filters') )
                    .on( 'change', function () {
                        dtbl_submission_list.column( i )
                            .search( $(this).val() )
                            .draw();
                    } );
         
                dtbl_submission_list.column( i ).data().unique().sort().each( function ( d, j ) {
                    select.append( '<option value="'+d+'">'+d+'</option>' )
                } );
            }
        } );
        // Hide Columns
        dtbl_submission_list.columns([2, 4, 5, 8, 10, 12]).visible(false);
	</script>

@endsection