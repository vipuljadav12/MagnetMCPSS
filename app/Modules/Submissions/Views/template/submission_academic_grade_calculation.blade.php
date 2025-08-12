    @php 
            $acd_eligibility_id = getEligibilitiesDynamic($submission->first_choice, 'Academic Grades');
            if(count($acd_eligibility_id) <= 0)
            {
                $acd_eligibility_id = getEligibilitiesDynamic($submission->second_choice, 'Academic Grades');
            }

            $acd_eligibility_data = getEligibilityContent1($acd_eligibility_id[0]->assigned_eigibility_name); 
            $term_calc = $term_calc_n = [];
            if(isset($acd_eligibility_data->terms_calc))
            {
                foreach($acd_eligibility_data->terms_calc as $akey => $tvalue){
                    foreach($tvalue as $k1 => $term){
                        if(isset($term_calc_n[$akey]))
                            array_push($term_calc_n[$akey], $term);
                        else
                            $term_calc_n[$akey] = array($term);
                    }
                }
            }
            $grade_year = array_keys($term_calc_n);
            $acdTerms = $term_calc_n;
            /*$grade_year = [];
            if(isset($gradeInfo)){
                $grade_year = explode(',', $gradeInfo->year);
            }*/


        $eligibility_data = getEligibilityContent1($value->assigned_eigibility_name);
        $content = $eligibility_data ?? null;

        $scoring = $eligibility_data->scoring ?? null;
       if($scoring->type == "GA")
       {
            $avgSum = $avgCnt = 0;
            foreach($acdTerms as $acdkey=>$acdvalue)
            {
                if(in_array($acdkey, $grade_year))
                {
                    $submission_data = DB::table("submission_grade")->where("submission_id", $submission->id)->where('academicYear', $acdkey)->where("GradeName", $acdvalue)->whereIn('courseTypeID', array(11,30,35,39,18))->get();
                    if(count($submission_data) > 0)
                    {
                        $avgSum += $submission_data->sum('numericGrade');
                        $avgCnt += $submission_data->count();
                    }

                }
            }


            $finalAvg = 0;
            if($avgCnt > 0 && $avgSum > 0)
            {
                $finalAvg = number_format($avgSum/$avgCnt, 2);
            }

       }
       $clsgArr = [];
       if($scoring->type == "CLSG")
       {
            $subjects = Config::get('variables.subjects');
            foreach($content->subjects as $svalue)
            {
                $marks = $subcnt = 0;
                foreach($acdTerms as $acdkey=>$acdvalue)
                {
                    if(in_array($acdkey, $grade_year))
                    {
                        foreach($acdvalue as $value1)
                        {
                            $marks += getAcademicScoreDynamic($submission->student_id, $subjects[$svalue], $value1, $acdkey, $submission->id); 

                            $subcnt++;                          

                        }

                    }
                }
                if($subcnt > 0)
                    $clsgArr[$svalue] = number_format($marks/$subcnt, 2);
                else
                    $clsgArr[$svalue] = 0;
            }

       }
       //dd($clsgArr, $acdTerms);

    @endphp

    {{-- FOR GRADE AVERAGE --}}
    @if(isset($scoring->type) && $scoring->type == "GA")

            @if(isset($submission_data) && $submission_data != '')
            <form id="store_grades_form" method="post" action="{{ url('admin/Submissions/update/AcademicGradeCalculation',$submission->id) }}">
                {{csrf_field()}}
                <div class="card shadow">
                    <div class="card-header">{{$value->eligibility_name}}</div>
                    <div class="card-body">
                        <div class="form-group row">
                            <label class="control-label col-12 col-md-12">Grade Average : </label>
                            <div class="col-12 col-md-12">
                                <input type="text" class="form-control" name="gpa" value="{{$finalAvg}}">
                            </div>
                        </div>

                        @if(isset($scoring->method) && $scoring->method == "NR")
                            @php $options = $scoring->NR @endphp
                                    <div class="form-group row">
                                        <label class="control-label col-12 col-md-12">Grade Average Score: </label>
                                        <div class="col-12 col-md-12">
                                                <select class="form-control custom-select template-type" name="given_score">
                                                    <option value="">Select Option</option>
                                                    @foreach($options as $k=>$v)
                                                        <option value="{{$v}}">{{$v}}</option>
                                                    @endforeach
                                                </select>

                                        </div>
                                    </div>
                                
                        @endif

                        <div class="text-right"> 
                        <button type="submit" class="btn btn-success">    
                            <i class="fa fa-save"></i>
                        </button>
                    </div>
                    </div>
                     
                </div>
                </form>
            @endif

           

    @endif

    @if(isset($scoring->type) && $scoring->type == "DD")
            @if(isset($content->terms_calc) && isset($content->subjects))
                @php $total_year = $content->academic_year_calc @endphp
                @php $subjects = Config::get('variables.subjects') @endphp

                @foreach($total_year as $tky=>$tyv)
                    @php $year = $tyv @endphp
                    
                    <div class="card shadow">
                        <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="">Year - {{$year}}</div>
                                @if($value->override == 'Y')
                                    <div class="d-flex align-items-center">
                                        <div class="mr-10">Override Grades</div> 
                                        <input id="chk_acd" type="checkbox" class="js-switch js-switch-1 js-switch-xs grade_override" data-size="Small"  {{$submission->grade_override=='Y'?'checked':''}}/>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body d-flex">

                                @foreach($content->subjects as $svalue)
                                    @foreach($content->terms_calc as $value1)
                                        <div class="form-group row mr-10">
                                            <label class="control-label col-12 col-md-12">{{$subjects[$svalue]}} - {{$value1}} </label>
                                            <div class="col-12 col-md-12">
                                                <input type="text" class="form-control" value="{{getAcademicScoreDynamic($submission->student_id, $subjects[$svalue], $value1, $year, $submission->id)}}">
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                @endforeach
            @endif
        @endif


    @if(isset($scoring->type) && $scoring->type == "CLSG")
                        <div class="card shadow">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>{{$value->eligibility_name}}</div>
                                @if($value->override == 'Y')
                                    <div class="d-flex align-items-center">
                                        <div class="mr-10">Override Grades</div> 
                                        <input id="chk_acd" type="checkbox" class="js-switch js-switch-1 js-switch-xs grade_override" data-size="Small"  {{$submission->grade_override=='Y'?'checked':''}}/>
                                    </div>
                                @endif

                            </div>


                            


                            <div class="card-body d-flex">

                                @foreach($clsgArr as $skey=>$svalue)
                                        <div class="form-group row mr-10">
                                            <label class="control-label col-12 col-md-12">{{$subjects[$skey]}}</label>
                                            <div class="col-12 col-md-12">
                                                <input type="text" class="form-control" value="{{$svalue}}">
                                            </div>
                                        </div>
                                @endforeach
                            </div>
                        </div>

           
        @endif


        <div class="modal fade" id="overrideAcademicGrade" tabindex="-1" role="dialog" aria-labelledby="employeependingLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="employeependingLabel">Alert</h5>
                            <button type="button" class="close overrideAcademicGradeNo" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
                        </div>
                        <div class="modal-body">
                                <div class="form-group">
                                    <label class="control-label">Comment : </label>
                                    <textarea class="form-control" name="grade_override_comment" id="grade_override_comment"></textarea>
                                    <input type="hidden" name="grade_override_status" id="grade_override_status">
                                </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" data-value="" id="overrideAcademicGradeYes" onclick="overrideAcademicGrade()">Submit</button>
                            <button type="button" class="btn btn-danger overrideAcademicGradeNo">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>