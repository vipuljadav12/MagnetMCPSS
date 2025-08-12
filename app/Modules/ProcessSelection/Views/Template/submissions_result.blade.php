<div class="tab-pane fade show active" id="preview04" role="tabpanel" aria-labelledby="preview04-tab">
    <div class=" @if($display_outcome > 0) d-none @endif" style="height: 704px; overflow-y: auto;">
        <div class="table-responsive">
                    <div class="row col-md-12" id="submission_filters"></div>

            <table class="table" id="tbl_submission_results">
                <thead>
                    <tr>
                        <th class="">Submission ID</th>
                        <th class="">Student Name</th>
                        <th class="">Next Grade</th>
                        <th class="notexport">Program</th>
                        <th class="notexport">Outcome</th>
                        <th class="">Race</th>
                        <th class="">School</th>
                        <th class="">Program</th>
                        <th class="text-center">Choice</th>
                        <th class="text-center">Outcome</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($final_data as $key=>$value)
                        <tr>
                            <td class="">{{$value['id']}}</td>
                            <td class="">{{$value['name']}}</td>
                            <td class="text-center">{{$value['grade']}}</td>
                            <td class="">{{$value['program_name']}}</td>
                            <td class="">{{$value['offered_status']}}</td>
                            <td class="">{{$value['race']}}</td>
                            <td class="">{{$value['school']}}</td>
                            <td class="">{{$value['program']}}</td>
                            <td class="text-center">{{$value['choice']}}</td>
                            <td class="">{!! $value['outcome'] !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
    </div>
    <div class="d-flex flex-wrap justify-content-between pt-20"><a href="javascript:void(0);" class="btn btn-secondary" title="" id="ExportReporttoExcel">Download Submissions Result</a>@if($display_outcome == 0) <a href="javascript:void(0);" class="btn btn-success" title="" onclick="updateFinalStatus()">Accept Outcome and Commit Result</a> @else <a href="javascript:void(0);" class="btn btn-danger" title="" onclick="alert('Already Outcome Commited')">Accept Outcome and Commit Result</a>  @endif</div>
</div>