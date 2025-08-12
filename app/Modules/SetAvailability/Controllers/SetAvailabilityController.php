<?php

namespace App\Modules\SetAvailability\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Program\Models\Program;
use App\Modules\SetAvailability\Models\Availability;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\ProcessSelection\Models\ProcessSelection;
use App\Modules\Submissions\Models\SubmissionsStatusUniqueLog;
use Illuminate\Support\Facades\Session;

class SetAvailabilityController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // return Availability::get();
        $enrollment_id = Session::get('enrollment_id');
        if(Session::get("district_id") != '0')
            $programs=Program::where('status','!=','T')->where('district_id', Session::get('district_id'))->where('enrollment_id', Session::get('enrollment_id'))->get();
        else
            $programs=Program::where('status','!=','T')->where('enrollment_id', Session::get('enrollment_id'))->get();

        // return $programs;
        return view("SetAvailability::index",compact("programs"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getOptionsByProgram(Program $program)
    {
        // $display_outcome = ProcessSelection::where('enrollment_id', Session::get("enrollment_id"))->where('form_id', $program->parent_submission_form)->where('commited', 'Yes')->count();
        $display_outcome = SubmissionsStatusUniqueLog::where("enrollment_id", Session::get("enrollment_id"))->count();

        $availabilities =  Availability::where("program_id",$program->id)->where('district_id',$program->district_id)->where('enrollment_id',$program->enrollment_id)->get()->keyBy('grade');
        $enrollment = Enrollment::where('status','Y')->where("district_id",$program->district_id)->where('id',$program->enrollment_id)->first();
        // return $availabilities;
        return view("SetAvailability::options",compact("program","availabilities","enrollment","display_outcome"));
    }   

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // return $request;
        if(isset($request['grades']) && !empty($request['grades']))
        {
            foreach($request['grades'] as $g => $grade) 
            {
                $ins = array();
                $ins['program_id'] = $request['program_id'];
                $ins['grade'] = $g;
                $ins['district_id'] = Session::get("district_id");
                $ins['available_seats'] = $request["grades"][$g]['available_seats'];
                $ins['total_seats'] = $request["grades"][$g]['total_seats'];
                $ins['year'] = $request["year"];
                $ins['enrollment_id'] = $request["enrollment_id"];
                // $ins['year'] = "2020-2021";
                $newData[] = $ins;
                $exist = Availability::where("program_id",$ins['program_id'])->where('district_id',$ins['district_id'])->where("grade",$ins['grade'])->where('enrollment_id',$ins['enrollment_id'])->first();
                if(isset($exist->id))
                {
                    // $result[] = Availability::where("program_id",$ins['program_id'])->where('district_id',$ins['district_id'])->where("grade",$ins['grade'])->update($ins);
                    $exist->available_seats = $ins['available_seats'];
                    $exist->total_seats = $ins['total_seats'];
                    $result[] = $exist->save();
                }
                else
                {
                    $result[] = Availability::create($ins);
                }
            }
            //exit;
        }
        if(count($result) > 0)
        {
            Session::flash("success","Availability saved successfully");
        }
        else
        {

            Session::flash("error","Something went wrong,Please try again.");
        }
        return redirect('admin/Availability');
        return $newData;

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
