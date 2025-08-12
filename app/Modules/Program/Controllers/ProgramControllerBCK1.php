<?php

namespace App\Modules\Program\Controllers;

use App\Modules\Program\Models\Program;
use App\Modules\Program\Models\ProgramEligibility;
use Session;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProgramController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $programs=Program::where('status','!=','T')->get();
        return view("Program::index",compact('programs'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('Program::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        //
        return $request;
        $msg=['priority.required'=>'The priority field is required. ','grade_lavel.required'=>'The grade lavel field is required.','applicant_filter1.required'=>'The Applicant Group Filter 1 is required. '];
        $request->validate([
            'name'=>'required|max:255',
            'applicant_filter1'=>'required|max:255',
            'applicant_filter2'=>'max:255',
            'applicant_filter3'=>'max:255',
            'grade_lavel'=>'required',
            'parent_submission_form'=>'required',
            'priority'=>'required',
            'selection_method'=>'required',
            'selection_by'=>'required',
            'seat_availability_enter_by'=>'required',

        ],$msg);
        $currentdate=date("Y-m-d h:m:s", time());
        $priority='';
        $grade_lavel='';
        foreach ($request->priority as $key=>$value)
        {
            if ($key==0)
            {
                $priority=$value;
                continue;
            }
            $priority=$priority.','.$value;
        }
        foreach ($request->grade_lavel as $key=>$value)
        {
            if ($key==0)
            {
                $grade_lavel=$value;
                continue;
            }
            $grade_lavel=$grade_lavel.','.$value;
        }
        $programdata=[
            'name'=>$request->name,
            'applicant_filter1'=>$request->applicant_filter1,
            'applicant_filter2'=>$request->applicant_filter2,
            'applicant_filter3'=>$request->applicant_filter3,
            'grade_lavel'=>$grade_lavel,
            'parent_submission_form'=>$request->parent_submission_form,
            'priority'=>$priority,
            'committee_score'=>$request->committee_score,
            'audition_score'=>$request->audition_score,
            'rating_priority'=>$request->rating_priority,
            'combine_score'=>$request->combine_score,
            'final_score'=>$request->final_score,
            'lottery_number'=>$request->lottery_number,
            'selection_method'=>$request->selection_method,
            'selection_by'=>$request->selection_by,
            'seat_availability_enter_by'=>$request->seat_availability_enter_by,
            'basic_method_only'=>$request->basic_method_only=='on'?'Y':'N',
            'combined_scoring'=>$request->combined_scoring=='on'?'Y':'N',
            'combined_eligibility'=>$request->combined_eligibility,
            'eligibility_info'=>json_encode($request['eligibility'],true),
            'created_at'=>$currentdate,
            'updated_at'=>$currentdate
        ];
        $programresult=Program::create($programdata);
        if (isset($programresult) && isset($eligibilityresult)) {
            Session::flash("success", "Program Data add successfully.");
        } else {
            Session::flash("error", "Please Try Again.");
        }

        if (isset($request->save_edit))
        {
//            return 'edit';
            return redirect('admin/Program/edit/'.$programresult->id);
        }
        return redirect('admin/Program');

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
        $program=Program::where('id',$id)->first();
        $programeligibilities=ProgramEligibility::where('program_id',$id)->get();
//        return  $program;
        return view('Program::edit',compact('program','programeligibilities'));
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
        $msg=['priority.required'=>'The priority field is required. ','grade_lavel.required'=>'The grade lavel field is required.','applicant_filter1.required'=>'The Applicant Group Filter 1 is required. '];
        $request->validate([
            'name'=>'required|max:255',
            'applicant_filter1'=>'required|max:255',
            'applicant_filter2'=>'max:255',
            'applicant_filter3'=>'max:255',
            'grade_lavel'=>'required',
            'parent_submission_form'=>'required',
            'priority'=>'required',
            'selection_method'=>'required',
            'selection_by'=>'required',
            'seat_availability_enter_by'=>'required'
        ],$msg);
        $currentdate=date("Y-m-d h:m:s", time());
        $priority='';
        $grade_lavel='';
        foreach ($request->priority as $key=>$value)
        {
            if ($key==0)
            {
                $priority=$value;
                continue;
            }
            $priority=$priority.','.$value;
        }
        foreach ($request->grade_lavel as $key=>$value)
        {
            if ($key==0)
            {
                $grade_lavel=$value;
                continue;
            }
            $grade_lavel=$grade_lavel.','.$value;
        }
        $data=[
            'name'=>$request->name,
            'applicant_filter1'=>$request->applicant_filter1,
            'applicant_filter2'=>$request->applicant_filter2,
            'applicant_filter3'=>$request->applicant_filter3,
            'grade_lavel'=>$grade_lavel,
            'parent_submission_form'=>$request->parent_submission_form,
            'priority'=>$priority,
            'committee_score'=>$request->committee_score,
            'audition_score'=>$request->audition_score,
            'rating_priority'=>$request->rating_priority,
            'combine_score'=>$request->combine_score,
            'final_score'=>$request->final_score,
            'lottery_number'=>$request->lottery_number,
            'selection_method'=>$request->selection_method,
            'selection_by'=>$request->selection_by,
            'seat_availability_enter_by'=>$request->seat_availability_enter_by,
            'created_at'=>$currentdate,
            'updated_at'=>$currentdate
        ];

//        return $data;
        $result=Program::where('id',$id)->update($data);
        if (isset($result)) {
            Session::flash("success", "Program Updated successfully.");
        } else {
            Session::flash("error", "Please Try Again.");
        }

        if (isset($request->save_edit))
        {
            return redirect('admin/Program/edit/'.$id);
        }
        return redirect('admin/Program');
    }

    public function delete($id)
    {
        $currentdate=date("Y-m-d h:m:s", time());
        $result=Program::where('id',$id)->update(['status'=>'T','updated_at'=>$currentdate]);
        if (isset($result)) {
            Session::flash("success", "Program Data Move into Trash successfully.");
        } else {
            Session::flash("error", "Please Try Again.");
        }
        return redirect('admin/Program');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function trash()
    {
        //
        $programs=Program::where('status','T')->get();
        return view('Program::trash',compact('programs'));
    }
    public function restore($id)
    {
        $currentdate=date("Y-m-d h:m:s", time());
        $result=Program::where('id',$id)->update(['status'=>'Y','updated_at'=>$currentdate]);
        if (isset($result)) {
            Session::flash("success", "Program Data restore successfully.");
        } else {
            Session::flash("error", "Please Try Again.");
        }
        return redirect('admin/Program');
    }
}
