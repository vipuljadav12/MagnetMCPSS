@extends('layouts.admin.app')
@section('title')
	All PowerSchool CDI Report
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


    /*read more start*/
    .readmore {color: #85aded;}
    .readmore:hover {cursor: pointer;}
    /*read more end*/
</style>
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">All PowerSchool CDI Report</div>
            <div>
                <a href="{{url('admin/Reports/missing/'.$enrollment_id.'/allcdi')}}" class="btn btn-secondary" title="Go Back">Go Back</a>
            </div>
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
    <script src="{{url('/resources/assets/admin')}}/js/bootstrap/buttons.html5.min.js"></script>
	<script type="text/javascript">
        $("#wrapperloading").show();

        $.ajax({
            type: 'get',
            dataType: 'JSON',
            url: "{{url('admin/Reports/missing/'.$enrollment_id.'/all_powerschool_cdi/response')}}",
            success: function(response) {

                    $("#response").html(response.html);
                    $("#wrapperloading").hide();
                    var dtbl_submission_list = $("#datatable").DataTable({
                        
                        columnDefs: [{
                          targets: [8],
                          createdCell: function(cell) {
                            var $cell = $(cell);
                            console.log($cell.text().length);
                            if($cell.text().length > 60) {
                                $(cell).contents().wrapAll("<div class='content'></div>");
                                var $content = $cell.find(".content");
                                $(cell).append($("<span class='readmore'>...Read more</span>"));
                                $btn = $(cell).find("span");
                                $content.css({
                                  "height": "49px",
                                  "overflow": "hidden"
                                })
                                $cell.data("isLess", true);
                                $btn.click(function() {
                                  var isLess = $cell.data("isLess");
                                  $content.css("height", isLess ? "auto" : "50px")
                                  $(this).text(isLess ? "Read less" : "...Read more")
                                  $cell.data("isLess", !isLess)
                                })
                            }
                          }
                        }],

                        "aaSorting": [],
                        dom: 'Bfrtip',
                        buttons: [{
                            // extend: 'excel',
                            extend: 'excelHtml5',
                            title: 'Submissions-CDI-PowerSchool',
                            text:'Export to Excel',
                            //Columns to export
                            exportOptions: {
                                columns: ':not(.notexport)'
                            }
                        }]
                    });

                   $("#datatable thead th").each( function ( i ) {
                    // Disable dropdown filter for disalble_dropdown_ary (index=0)
                    var disalble_dropdown_ary = [2];//13
                    if ($.inArray(i, disalble_dropdown_ary) >= 0) {
                        var column_title = $(this).text();
                        
                        var select = $('<select class="form-control custom-select2 submission_filters col-md-4" id="filter_option"><option value="">Select '+column_title+'</option></select>')
                            .appendTo( $('#submission_filters') )
                            .on( 'change', function () {
                                if($(this).val() != '')
                                {
                                    dtbl_submission_list.column( i )
                                        .search("^"+$(this).val()+"$",true,false)
                                        .draw();
                                }
                                else
                                {
                                    dtbl_submission_list.column( i )
                                        .search('')
                                        .draw();
                                }
                            } );
                 
                        dtbl_submission_list.column( i ).data().unique().sort().each( function ( d, j ) {
                            select.append( '<option value="'+d+'">'+d+'</option>' )
                        } );
                    }
                } );
                // Hide Columns
                dtbl_submission_list.columns([2]).visible(false);

                }
            });


	</script>

@endsection