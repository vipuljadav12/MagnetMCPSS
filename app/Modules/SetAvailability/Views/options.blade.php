<div class="card shadow">
    <div class="card-header">{{$program->name}}- Available seats for {{$enrollment->school_year ?? (date("Y")-1)."-".date("Y")}}</div>
    <input type="hidden" name="year" value="{{$enrollment->school_year ?? (date("Y")-1)."-".date("Y")}}">
    <input type="hidden" name="enrollment_id" value="{{$enrollment->id}}">
	@php
		$grades = isset($program->grade_lavel) && !empty($program->grade_lavel) ? explode(',', $program->grade_lavel) : array();

	@endphp
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <tbody>
                	@forelse($grades as $g=>$grade)
	                    <tr>
	                        <td class="w-10">{{$grade}}</td>
	                        <td class="w-30">
	                        	<input type="text" class="form-control numbersOnly availableSeat" data-id="{{$grade}}"  name="grades[{{$grade}}][available_seats]" value="{{$availabilities[$grade]->available_seats ?? ""}}"  @if($display_outcome > 0) disabled @endif>
	                        	<label class="error text-danger d-none">Available Seats should not exceed the Total Seats</label>
	                        </td>
	                        <td class="w-30"></td>
	                        <td class="w-30"></td>
	                    </tr>
	                @empty
	                    <tr>
	                     	<td class="text-center">No Grades</td>
	                    </tr>
	                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="card shadow">
    <div class="card-header">{{$program->name}} - Total Capacity for {{$enrollment->school_year ?? (date("Y")-1)."-".date("Y")}}</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <tbody>
                	@forelse($grades as $g=>$grade)
	                    <tr>
	                        <td class="w-10">{{$grade}}</td>
	                        <td class="w-30">
	                        	<input type="text" class="form-control numbersOnly totalSeat"  name="grades[{{$grade}}][total_seats]" value="{{$availabilities[$grade]->total_seats ?? ""}}" data-id="{{$grade}}" @if($display_outcome > 0) disabled @endif>
	                        </td>
	                        <td class="w-30"></td>
	                        <td class="w-30"></td>
	                    </tr>
	                @empty
	                    <tr>
	                     	<td class="text-center">No Grades</td>
	                    </tr>
	                @endforelse
                    
                </tbody>
            </table>
        </div>
         @if($display_outcome == 0)
        <div class="text-right"> 
            {{-- <button class="btn btn-success">    
                <i class="fa fa-save"></i> Save
            </button> --}}
            <div class="box content-header-floating" id="listFoot">
                <div class="row">
                    <div class="col-lg-12 text-right hidden-xs float-right">
                        <button type="submit" class="btn btn-warning btn-xs" title="Save" id="optionSubmit"><i class="fa fa-save"></i> Save </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>