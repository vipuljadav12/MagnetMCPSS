<div class="card-body">
    <style type="text/css" media="screen">
        .media_screen { display: none !important; }
    </style>
    <style type="text/css" media="print">
        .excel_screen { display: none !important; }
    </style>
    
    @php 
        $config_subjects = Config::get('variables.subjects');
        $subject_count = count($subjects) ?? 0;
        $colspan = 8;
    @endphp
    
    @if(!empty($firstdata))

    <div class="table-responsive">
        <table class="table table-striped mb-0 w-100" id="datatable">
            <thead>
                <tr>
                    <th class="align-middle" rowspan="3">Submission ID</th>
                    <th class="align-middle" rowspan="3">State ID</th>
                    <th class="align-middle notexport" rowspan="3">Student Type</th>
                    <th class="align-middle" rowspan="3">Last Name</th>
                    <th class="align-middle" rowspan="3">First Name</th>
                    <th class="align-middle" rowspan="3">Next Grade</th>
                    <th class="align-middle" rowspan="3">Current School</th>
                    @foreach ($academic_year as $tyear => $tvalue)
                        <th class="align-middle text-center" colspan="{{$subject_count*(count($terms)+1)}}">{{$tvalue}}</th>
                    @endforeach
                    <th class="align-middle" rowspan="3">B Info</th>
                    <th class="align-middle" rowspan="3">C Info</th>
                    <th class="align-middle" rowspan="3">D Info</th>
                    <th class="align-middle" rowspan="3">E Info</th>
                    <th class="align-middle" rowspan="3">Susp</th>
                    <th class="align-middle" rowspan="3"># Day Susp</th>
                </tr>
                <tr class="excel_screen">
                    @foreach($subjects as $value)
                        @php
                            $sub = $config_subjects[$value] ?? $value;
                        @endphp
                        <th class="align-middle text-center" colspan="{{count($terms)+1}}">{{$sub}}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($subjects as $value)
                        @foreach ($terms as $tyear => $tvalue)
                        
                           <th class="align-middle text-center"><span class="media_screen">{{$config_subjects[$value]}} <br></span>{{$tvalue}}</th>
                        @endforeach
                        <th class="align-middle text-center"><span class="media_screen">{{$config_subjects[$value]}} <br></span>Final Average</th>
                    @endforeach
                </tr>

            </thead>
            
            <tbody>
                @foreach($firstdata as $key=>$value)
                    <tr id="row{{$value['id']}}">
                        <td class="text-center"><a href="{{url('/admin/Submissions/edit/'.$value['id'])}}">{{$value['id']}}</a></td>
                        <td class="">{{$value['student_id']}}</td>
                        <td class="notexport">{{($value['student_id'] != "" ? "Current" : "Non-Current")}}</td>
                        <td class="">{{$value['first_name']}}</td>
                        <td class="">{{$value['last_name']}}</td>
                        <td class="text-center">{{$value['next_grade']}}</td>
                        <td class="">{{$value['current_school']}}</td>
                       
                        @foreach ($value['score'] as $tyear => $tvalue)
                            @foreach($tvalue as $tkvalue1=>$tvalue1)
                                @foreach($tvalue1 as $tvalue2)
                                    <td class="align-middle">
                                        <div class="text-center">
                                            <span class="scorelabel">
                                                    {!! $tvalue2 !!}
                                            </span> 
                                            
                                        </div>
                                    </td>
                                @endforeach
                                @endforeach
                        @endforeach

                        @php $cdata = $value['cdi'] @endphp
                        <!-- B Info !-->
                        <td class="align-middle">
                            <div class="text-center">
                                <span @if(!is_numeric($cdata['b_info'])) class="scorelabel" @endif>
                                        {!! $cdata['b_info'] !!}
                                </span>
                            </div>
                        </td>

                        <!-- C Info !-->
                        <td class="align-middle">
                            <div class="text-center">
                                <span @if(!is_numeric($cdata['c_info'])) class="scorelabel" @endif>
                                        {!! $cdata['c_info'] !!}
                                </span>
                            </div>
                        </td>

                        <!-- D Info !-->
                        <td class="align-middle">
                            <div class="text-center">
                                <span @if(!is_numeric($cdata['d_info'])) class="scorelabel" @endif>
                                        {!! $cdata['d_info'] !!}
                                </span>
                            </div>
                        </td>

                        <!-- E Info !-->
                        <td class="align-middle">
                            <div class="text-center">
                                <span @if(!is_numeric($cdata['e_info'])) class="scorelabel" @endif>
                                        {!! $cdata['e_info'] !!}
                                </span>
                            </div>
                        </td>

                        <!-- Susp Info !-->
                        <td class="align-middle">
                            <div class="text-center">
                                <span @if(!is_numeric($cdata['susp'])) class="scorelabel" @endif>
                                        {!! $cdata['susp'] !!}
                                </span>
                            </div>
                        </td>

                        <!-- Susp Days Info !-->
                        <td class="align-middle">
                            <div class="text-center">
                                <span @if(!is_numeric($cdata['susp_days'])) class="scorelabel" @endif>
                                        {!! $cdata['susp_days'] !!}
                                </span>
                           </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
        <div class="table-responsive text-center"><p>No records found.</div>
    @endif
</div>