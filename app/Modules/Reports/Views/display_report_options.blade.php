<div class="card-body">
    <form class="">
        <div class="form-group">
            <label for="">Enrollment Year : </label>
            <div class="">
                <select class="form-control custom-select" id="enrollment">
                    <option value="">Select Enrollment Year </option>
                    @foreach($enrollment as $key=>$value)
                        <option value="{{$value->id}}" @if($enrollment_id == $value->id) selected @endif>{{$value->school_year}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="">Report : </label>
            <div class="">
                @php $report_arr = Config::get('variables.reportArr') @endphp
				<select class="form-control custom-select" id="reporttype" onChange="showHideGrade()">
				    <option value="">Select Report</option>
				    @foreach($report_arr as $rk=>$rv)
				    	<option value="{{$rk}}" @if($selection == $rk) selected @endif>{{$rv}}</option>
				    @endforeach
				</select>
            </div>
        </div>

                @php $garray = array(3,4,5,6,7,8,9,10,11,12); @endphp
                 <div class="form-group" id="cgradediv" @if($selection != 'grade') style="display: none;" @endif>
                    <label for="">Select Grade : </label>
                    <div class="">
                        <select class="form-control custom-select" id="cgrade" onChange="changeGradeData();">
                            <option value="">Select Report</option>
                            @foreach($garray as $rk=>$rv)
                                <option value="{{$rv}}" @if(isset($cgrade) && $cgrade == $rv) selected @endif>{{$rv}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
        
        <div class=""><a href="javascript:void(0);" onclick="showMissingReport()" title="Generate Report" class="btn btn-success generate_report">Generate Report</a></div>
    </form>
</div>
