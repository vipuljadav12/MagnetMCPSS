@php global $hidefurther @endphp
<div class="table-responsive">
	@php $prevFormData = Session::get('form_data') @endphp
    
    @php $fieldSequence = getFieldSequence($field_id) @endphp
    @if(!empty($fieldSequence))
        @php $displayArr = array() @endphp
        @php $fieldArr = $prevFormData[0]['formdata'] @endphp
        @foreach($fieldSequence as $key=>$value)
            @if(isset($fieldArr[$value->id]))
                @php $displayArr[$value->id] = $fieldArr[$value->id] @endphp
            @endif
        @endforeach

    @else
        @php $displayArr = $prevFormData[0]['formdata'] @endphp
    @endif

	     <table class="table table-striped table-bordered">
            <tbody>
            	@foreach($displayArr as $key1=>$value1)
                        @php $fieldtype = getFormElementType($key1) @endphp

                        @php $display = true @endphp
                        @if(is_array($value1) && $value1[0]=="")
                            @php $display = false @endphp
                        @elseif($value1 == "")
                            @php $display = false @endphp
                        @endif
                        
                        @php $display = getViewEnable($key1) @endphp
                        @if($display && $fieldtype != "termscheck")
                         <tr>
                            <td class="b-600 w-110">{{getFormElementLabel($key1)}} :</td>
                            <td class="">
                                @if($fieldtype == "date")
                                    @php $tmpdate = explode("-", $value1) @endphp
                                    {{" ".date("F", mktime(0, 0, 0, $tmpdate[1], 10))." ".date("d", mktime(0, 0, 0, 0, $tmpdate[2])).", ".$tmpdate[0]}} 
                                @else
                                    {{(is_array($value1) ? $value1[0] : $value1)}}
                                @endif
                            </td>
                        </tr>
                        @endif
                    @endforeach
            </tbody>
        </table>
        @php $student_id = fetch_student_field_id($prevFormData[0]['form_id']) @endphp
        @if(isset($prevFormData[0]['formdata'][$student_id]))
            @php $hidefurther = "Yes"; @endphp
            <div class="form-group d-flex flex-wrap justify-content-between" id="correctdiv">
                    <a href="{{url('/incorrectinfo/'.$prevFormData[0]['formdata'][$student_id])}}" class="btn btn-danger w-200" title="Incorrect Information"><i class="fa fa-times pr-5"></i>  Incorrect Information</a>
                     <button type="button" class="btn btn-success step-2-2-btn w-200" title="" value="Correct Information" id="correctinfo" onclick="showHideCorrect()">Correct Information  <i class="fa fa-check pl-5"></i></button>
            </div>
        @endif
    </div>
                            