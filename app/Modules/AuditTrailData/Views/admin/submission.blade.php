<div class="table-responsive">
	<div class="row col-md-12 pb-20" id="submission_filters"></div>

    <table id="datatable" class="table table-striped mb-0">
        <thead>
        <tr>
            <th class="align-middle w-120 text-center">ID</th>
			<th class="text-center">Enrollment Year</th>
			<th class="text-center">Application Name</th>
            <th class="align-middle">Old Data</th>
            <th class="align-middle">New Data</th>
            <th class="align-middle">Updated On</th>
            <th class="align-middle text-center">User</th>
            {{-- <th class="align-middle text-center">Action</th> --}}
        </tr>
        </thead>
        <tbody>
        	@if(isset($audit_trails['submission']) && count($audit_trails['submission']) > 0)
				@foreach($audit_trails['submission'] as $a => $audit_trail)

					@if(isset($audit_trail->changed_fields) && $audit_trail->changed_fields != "[]" && $audit_trail->changed_fields != "")
					<tr>
						<td class="text-center">{{$loop->index +1}}</td>
						<td class="text-center">{{getEnrollmentYear($audit_trail->enrollment_id)}}</td>
						<td class="text-center">{{getApplicationName($audit_trail->application_id)}}</td>

						<td>
							<div>	
								@foreach($audit_trail->old_values as $o => $old)
									@if(isset($old) && isset($o))
										@if($o == "gender")
											@php continue; @endphp
										@endif
										@if($o=="id" || $o=="submission_id")
											<span class="text-strong">Submission ID : </span>
										
										@elseif($o == "employee_id")
											<span class="text-strong">Employee ID : </span>
										@elseif($o == "mcp_employee")
											<span class="text-strong">MCPSS Employee : </span>
										@else
											<span class="text-strong">{{ucwords(str_replace("_"," ",($o)))}} : </span>
										@endif

										@if($audit_trail->changed_fields != '' && in_array($o,$audit_trail->changed_fields))
											<span class="text-danger">
										@else
											<span class="text">
										@endif
										@if($o=="id" || $o=="submission_id")
											<a href="{{url('/admin/Submissions/edit/'.$old)}}"  target="_blank">{{$old}}</a>
										@elseif($o == "gender")
										@else
											 {{$old}}
										@endif<br>

										@if($o=="id" || $o=="submission_id")
											@if(getSubmissionStudentName($old) != "")
												<span class="text-strong">Student Name : </span>
												<span class="text">{{getSubmissionStudentName($old)}}</span><br>
											@endif
										@endif
										</span>
									@endif
								@endforeach
							</div>
						</td>
						<td>
							<div>	
								@foreach($audit_trail->new_values as $n => $new)
									@if(isset($new))
										@if($n=="id" || $n=="submission_id")
											<span class="text-strong">Submission ID : </span>
										@elseif($n == "gender")	
											<span class="text-strong">Comment : </span>
										@elseif($n == "employee_id")
												<span class="text-strong">Employee ID : </span>
											@elseif($n == "mcp_employee")
												<span class="text-strong">MCPSS Employee : </span>
											
										@else
											<span class="text-strong">{{ucwords(str_replace("_"," ",($n)))}} : </span>
										@endif

										@if(in_array($n,$audit_trail->changed_fields))
											<span class="text-success">
										@else
											<span class="text">
										@endif

										@if($n=="id" || $n =="submission_id")
											<a href="{{url('/admin/Submissions/edit/'.$new)}}"  target="_blank">{{$new}}</a>
										@else
											 {{$new}}
										@endif<br>

										@if($n=="id" || $n =="submission_id")
											@if(getSubmissionStudentName($new) != "")
												<span class="text-strong">Student Name : </span>
												<span class="text">{{getSubmissionStudentName($new)}}</span><br>
											@endif
										@endif
										</span>
									@endif
								@endforeach
							</div>
						</td>
						<td>	
							{{getDateTimeFormat($audit_trail->created_at)}}
						</td>
						<td>	
							{{$audit_trail->user->full_name ?? ""}}
						</td>

					</tr>
					@elseif($audit_trail->changed_fields == "" && $audit_trail->old_values != "")
						<tr>
							<td class="text-center">{{$loop->index +1}}</td>
							<td class="text-center">{{getEnrollmentYear($audit_trail->enrollment_id)}}</td>
							<td class="text-center">{{getApplicationName($audit_trail->application_id)}}</td>
							<td></td>
							<td>
								<div>	
									@foreach($audit_trail->old_values as $o => $old)
										@if(isset($old) && isset($o))
											@if($o=="id" || $o=="submission_id")
												<span class="text-strong">Submission ID : </span>
											@elseif($o == "employee_id")
												<span class="text-strong">Employee ID : </span>
											@elseif($o == "mcp_employee")
												<span class="text-strong">MCPSS Employee : </span>
											@else
												<span class="text-strong">{{ucwords(str_replace("_"," ",($o)))}} : </span>
											@endif
											
												
												<span class="text">
												@if($o=="id" || $o=="submission_id")
													<a href="{{url('/admin/Submissions/edit/'.$old)}}" target="_blank">{{$old}}</a>
												@else
													 {{$old}}
												@endif
												 <br>
											</span>
											@if($o=="id" || $o=="submission_id")
												@if(getSubmissionStudentName($old) != "")
													<span class="text-strong">Student Name : </span>
													<span class="text">{{getSubmissionStudentName($old)}}</span><br>
												@endif
											@endif
										@endif
									@endforeach
								</div>
							</td>
							
							<td>	
								{{getDateTimeFormat($audit_trail->created_at)}}
							</td>
							<td>	
								{{$audit_trail->user->full_name ?? ""}}
							</td>

						</tr>
					@else
					{{$audit_trail->changed_fields}}
					@endif
				@endforeach
			@endif
        </tbody>
    </table>
</div>