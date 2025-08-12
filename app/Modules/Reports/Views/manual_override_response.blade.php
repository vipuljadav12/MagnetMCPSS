<div class="card-body">
    <div class="row col-md-12 pull-left pb-10">
        
    </div>
    <div class=" mb-10">
        <div id="submission_filters" class="pull-left col-md-6 pl-0" style="float: left !important;"></div> 
        <div class="text-right">    
                                              
            <a href="javascript:void(0)" onclick="exportMissing()" title="Export Missing Grade" class="btn btn-secondary">Export Grade Eligibility Grade</a>
            </div>
    </div>
    
    @php 
        $config_subjects = Config::get('variables.subjects');
        $subject_count = count($subjects) ?? 0;
        $colspan = 8;
    @endphp
    
    @if(!empty($final_data))

    <div class="table-responsive">
        <table class="table table-striped mb-0 w-100" id="datatable">
            <thead>
                <tr>
                    <th class="align-middle" rowspan="3">Submission ID</th>
                    <th class="align-middle" rowspan="3">State ID</th>
                    <th class="align-middle" rowspan="3">Last Name</th>
                    <th class="align-middle" rowspan="3">First Name</th>
                    <th class="align-middle" rowspan="3">Next Grade</th>
                    <th class="align-middle notexport" rowspan="3">Override</th>
                    @foreach ($terms as $tyear => $tvalue)
                        <th class="align-middle text-center" colspan="{{$subject_count*count($tvalue)}}">{{$tyear}}</th>
                    @endforeach
                </tr>
                <tr>
                     @foreach ($terms as $tyear => $tvalue)
                        @foreach($subjects as $value)
                            @php
                                $sub = $config_subjects[$value] ?? $value;
                            @endphp
                            <th class="align-middle text-center" colspan="{{count($tvalue)}}">{{$sub}}</th>
                        @endforeach
                    @endforeach
                </tr>
                <tr>
                    @foreach ($terms as $tyear => $tvalue)
                        @foreach($subjects as $value)
                            @foreach($tvalue as $value1)
                                <th class="align-middle text-center">{{$value1}}</th>
                            @endforeach
                        @endforeach
                    @endforeach
                </tr>

            </thead>
            <tbody>
                @foreach($final_data as $key=>$value)
                    <tr id="row{{$value['id']}}">
                        <td class="text-center"><a href="{{url('/admin/Submissions/edit/'.$value['id'])}}">{{$value['id']}}</a></td>
                        <td class="">{{$value['student_id']}}</td>
                        <td class="">{{$value['first_name']}}</td>
                        <td class="">{{$value['last_name']}}</td>
                        <td class="text-center">{{$value['next_grade']}}</td>
                        <td class="text-center notexport"><div>
                            <input id="radio{{$value['id']}}" name="status[{{$value['id']}}][]" type="checkbox" class="js-switch js-switch-1 js-switch-xs grade_override" data-plugin="switchery" data-size="Small" {{$value['grade_override']=='Y'?'checked':''}}  data-secondary-color="#ff0000">
                            </div>
                        </td>
                        @foreach ($terms as $tyear => $tvalue)
                            @foreach($subjects as $svalue)
                                @foreach($tvalue as $tvalue1)
                                    <td class="align-middle">
                                        @php
                                            //echo $tyear . " - ". $svalue . " - ".$tvalue1;
                                            $marks = $value['grade'][$tyear][$svalue][$tvalue1] ?? '';
                                        @endphp
                                        <div class="text-center">
                                            <span @if(!is_numeric($marks)) class="scorelabel" @endif>
                                                    {!! $marks !!}
                                            </span> 
                                            @if(!is_numeric($marks))
                                                <input type="text"  class="form-control numbersOnly d-none scoreinput" value="0" maxlength="3" min="0" max="100" id="{{$value['id'].','.$svalue.','.$tvalue1.','.$tyear}}">
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            @endforeach
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
        <div class="table-responsive text-center"><p>No records found.</div>
    @endif
</div>