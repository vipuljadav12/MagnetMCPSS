
<div class="card-body">
    <div class=" mb-10">
        <div id="submission_filters" class="pull-left col-md-6 pl-0" style="float: left !important;"></div> 
        
        <div class="text-right">
            
            <a href="{{url('admin/Reports/missing/'.$enrollment_id.'/all_powerschool_cdi')}}" class="btn btn-secondary" title="All PowerSchool Data">All PowerSchool Data</a>
            <a href="javascript:void(0)" onclick="exportMissing()" class="btn btn-secondary" title="Export Missing CDI">Export Missing CDI</a>
            </div>
    </div>
   
    @if(!empty($firstdata))
    <div class="table-responsive">
        <table class="table table-striped mb-0 w-100" id="datatable">
            <thead>
                <tr>
                    <th class="align-middle">Submission ID</th>
                    <th class="align-middle">State ID</th>
                    <th class="align-middle notexport">Student Type</th>
                    <th class="align-middle">Last Name</th>
                    <th class="align-middle">First Name</th>
                    <th class="align-middle">Next Grade</th>
                    <th class="align-middle">Current School</th>
                    <th class="align-middle notexport">Action</th>
                    <th class="align-middle">B Info</th>
                    <th class="align-middle">C Info</th>
                    <th class="align-middle">D Info</th>
                    <th class="align-middle">E Info</th>
                    <th class="align-middle">Susp</th>
                    <th class="align-middle"># Days Susp</th>
                </tr>
            </thead>
            <tbody>
                @foreach($firstdata as $key=>$value)
                    @php $cdata = array() @endphp
                     @foreach($value['cdi'] as $vkey=>$vcdi)
                        @php $cdata[$vkey] = $value['cdi'][$vkey] @endphp
                    @endforeach
                    <tr id="row{{$value['submission_id']}}">
                        <td class="text-center"><a href="{{url('/admin/Submissions/edit/'.$value['id'])}}">{{$value['id']}}</a></td>
                        <td class="">{{$value['student_id']}}</td>
                        <td class="notexport">{{($value['student_id'] != "" ? "Current" : "Non-Current")}}</td>
                        <td class="">{{$value['first_name']}}</td>
                        <td class="">{{$value['last_name']}}</td>
                        <td class="text-center">{{$value['next_grade']}}</td>
                        <td class="">{{$value['current_school']}}</td>
                        <td class="text-center">
                            <div>
                                <a href="javascript:void(0)" id="edit{{$value['submission_id']}}" onclick="editRow({{$value['submission_id']}})" title="Edit"><i class="far fa-edit"></i></a>&nbsp;<a href="javascript:void(0)" class="d-none" onclick="saveScore({{$value['submission_id']}})" id="save{{$value['submission_id']}}" title="Save"><i class="fa fa-save"></i></a>&nbsp;<a href="javascript:void(0)" class="d-none" id="cancel{{$value['submission_id']}}" onclick="hideEditRow({{$value['submission_id']}})" title="Cancel"><i class="fa fa-times"></i></a>
                            </div>
                        </td> 

                        <!-- B Info !-->
                        <td class="align-middle">
                                    <div class="text-center">
                                        <span class="scorelabel">
                                                {!! $cdata['b_info'] !!}
                                        </span>
                                        <input type="text"  class="form-control numbersOnly d-none scoreinput" value="{{ $cdata['b_info'] ?? 0 }}" maxlength="2" min="0" max="99" id="id_{{$value['submission_id'].'_b_info'}}">
                                    </div>
                        </td>

                        <!-- C Info !-->
                        <td class="align-middle">
                                    <div class="text-center">
                                        <span class="scorelabel">
                                                {!! $cdata['c_info'] !!}
                                        </span>
                                        <input type="text"  class="form-control numbersOnly d-none scoreinput" value="{{ $cdata['c_info'] ?? 0}}" maxlength="2" min="0" max="99" id="id_{{$value['submission_id'].'_c_info'}}">
                                        
                                    </div>
                        </td>

                        <!-- D Info !-->
                        <td class="align-middle">
                                    <div class="text-center">
                                        <span class="scorelabel">
                                            {!! $cdata['d_info'] !!}
                                        </span>
                                        <input type="text"  class="form-control numbersOnly d-none scoreinput" value="{{ $cdata['d_info'] ?? 0}}" maxlength="2" min="0" max="99" id="id_{{$value['submission_id'].'_d_info'}}">
                                        
                                    </div>
                        </td>

                        <!-- E Info !-->
                        <td class="align-middle">
                                    <div class="text-center">
                                        <span class="scorelabel">
                                                {!! $cdata['e_info'] !!}
                                        </span>
                                        <input type="text"  class="form-control numbersOnly d-none scoreinput" value="0" maxlength="2" min="0" max="99" id="id_{{$value['submission_id'].'_e_info'}}">
                                        
                                    </div>
                        </td>

                        <!-- Susp Info !-->
                        <td class="align-middle">
                                    <div class="text-center">
                                        <span class="scorelabel">
                                                {!! $cdata['susp'] !!}
                                        </span>
                                        <input type="text"  class="form-control numbersOnly d-none scoreinput" value="{{ $cdata['susp'] ?? 0}}" maxlength="2" min="0" max="99" id="id_{{$value['submission_id'].'_susp'}}">
                                        
                                    </div>
                        </td>

                        <!-- Susp Days Info !-->
                        <td class="align-middle">
                                    <div class="text-center">
                                        <span class="scorelabel">
                                                {!! $cdata['susp_days'] !!}
                                        </span>
                                        <input type="text"  class="form-control numbersOnly d-none scoreinput" value="{{ $cdata['susp_days'] ?? 0}}" maxlength="2" min="0" max="99" id="id_{{$value['submission_id'].'_susp_days'}}">
                                         
                                    </div>
                        </td>
                        
                    </tr>
                @endforeach
                
            </tbody>
        </table>
    </div>
    @else
        <div class="table-responsive text-center"><p>No Records found.</div>
    @endif
</div>
