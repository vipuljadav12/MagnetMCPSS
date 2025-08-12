@php        

    if(isset($eligibility))
    {

        $content = json_decode($eligibility->content) ?? null;
        $scoring = json_decode($eligibility->content)->scoring ?? null;
    }
    $label = "";
    if(isset($scoring->method))
        if($scoring->method == "YN")
            $label = " - Yes/No";
        elseif($scoring->method == "DD")
            $label = " - Data Display";
        elseif($scoring->method == "NR")
            $label = " - Numeric Ranking";
    $subjects = Config::get('variables.subjects');

@endphp
<form id="extraValueForm" action="{{url('admin/SetEligibility/extra_values/save')}}" method="post">
    {{csrf_field()}}
    <input type="hidden" name="program_id" value="{{$req['program_id']}}">
    <input type="hidden" name="eligibil`ity_id" value="{{$req['eligibility_id']}}">
    <input type="hidden" name="eligibility_type" value="{{$req['eligibility_type']}}">
    <input type="hidden" name="application_id" value="{{$req['application_id']}}">

    @if(isset($scoring->type) && ($scoring->type == "DD" || $scoring->type == "GA" || $scoring->type == "CLSG"))
        <p>Student's Grades should be above or equal to entered Grades</p>
        @if($scoring->type == "CLSG" || $scoring->type == "DD")
            @foreach($content->subjects as $value)
                @php $sb_name = $value; @endphp
                <div class="col-12 col-lg-12">
                    <div class="form-group row mr-10">
                        <label class="control-label col-12 col-md-12">{{$subjects[$value]}}</label>
                    
                        <div class="col-12 col-md-12">
                            <input type="text" class="form-control" name="value[{{$sb_name}}][]" value="{{$extraValue[$sb_name][0] ?? ""}}">
                        </div>
                    </div>
                </div>
            @endforeach
        @elseif($scoring->type == "GA")
            <div class="card shadow">
                <div class="card-header">Grade Average</div>
                <div class="card-body">
                    <div class="form-group row">
                        <label class="control-label col-12 col-md-12">Grade Average Score : </label>
                        <div class="col-12 col-md-12">
                            <input type="text" class="form-control" name="value[grade_average_score]" value="{{$extraValue['grade_average_score'] ?? ''}}">
                        </div>
                    </div>
                </div>
            </div>

        @endif
    @endif    
</form>

@if(isset($scoring->type) && $scoring->type == "GA1")
<div class="card shadow">
    <div class="card-header">Grade Average{{$label}}</div>
    <div class="card-body">
        <div class="form-group row">
            <label class="control-label col-12 col-md-12">Grade Average Score : </label>
            <div class="@if(isset($scoring->method) && $scoring->method == "DD") col-12 col-md-12 @else col-6 col-md-6 @endif">
                <input type="text" class="form-control" value="">
            </div>
            @if(isset($scoring->method) && $scoring->method != "DD")
            <div class="col-6">
                <select class="form-control custom-select">
                    @if($scoring->method == "YN")
                        @foreach($scoring->YN as $i=>$single)
                            <option value="">{{$single ?? ""}}</option>
                        @endforeach
                    @endif
                    @if($scoring->method == "NR")
                        @foreach($scoring->NR as $i=>$single)
                            <option value="">{{$single ?? ""}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            @endif
        </div>
    </div>
</div>
@endif
@if(isset($scoring->type) && $scoring->type == "GPA")
<div class="card shadow">
    <div class="card-header">GPA{{$label}}</div>
    <div class="card-body">
        <div class="form-group row">
            
            <div class="card-body d-flex align-items-start">
                <div class="form-group row mr-10">
                    <label class="control-label col-12 col-md-12">A </label>
                    <div class="col-12 col-md-12 mb-10">
                        <input type="text" class="form-control" value="{{$content->GPA->A ?? ""}}">
                    </div>
                    @if(isset($scoring->method) && $scoring->method != "DD")
                        <div class="col-12">
                            <select class="form-control custom-select">
                                @if($scoring->method == "YN")
                                    @foreach($scoring->YN as $i=>$single)
                                        <option value="">{{$single ?? ""}}</option>
                                    @endforeach
                                @endif
                                @if($scoring->method == "NR")
                                    @foreach($scoring->NR as $i=>$single)
                                        <option value="">{{$single ?? ""}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    @endif
                </div>
                <div class="form-group row mr-10">
                    <label class="control-label col-12 col-md-12">B </label>
                    <div class="col-12 col-md-12">
                        <input type="text" class="form-control" value="{{$content->GPA->B ?? ""}}">
                    </div>
                </div>
                <div class="form-group row mr-10">
                    <label class="control-label col-12 col-md-12">C </label>
                    <div class="col-12 col-md-12">
                        <input type="text" class="form-control" value="{{$content->GPA->C ?? ""}}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="control-label col-12 col-md-12">D </label>
                    <div class="col-12 col-md-12">
                        <input type="text" class="form-control" value="{{$content->GPA->D ?? ""}}">
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>
@endif
@if(isset($scoring->type) && $scoring->type == "CLSG1")
<div class="card shadow">
    <div class="card-header">Counts of Letters / Standard Grades{{$label}}</div>
    <div class="card-body">
        <div class="form-group row">
            <label class="control-label col-12 col-md-12">Counts of Letters / Standard Grades : :</label>
            <div class="@if(isset($scoring->method) && $scoring->method == "DD") col-12 col-md-12 @else col-6 col-md-6 @endif">
                <input type="text" class="form-control" value="">
            </div>
            @if(isset($scoring->method) && $scoring->method != "DD")
            <div class="col-6">
                <select class="form-control custom-select">
                    @if($scoring->method == "YN")
                        @foreach($scoring->YN as $i=>$single)
                            <option value="">{{$single ?? ""}}</option>
                        @endforeach
                    @endif
                    @if($scoring->method == "NR")
                        @foreach($scoring->NR as $i=>$single)
                            <option value="">{{$single ?? ""}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

<script type="text/javascript">
    $(document).ready(function(){

                $(document).find("#exampleModalLabel1").html("Academic Grades Calculation");
        }
        );
</script>