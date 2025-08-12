<?php

namespace App\Modules\Program\Controllers;

use App\Modules\District\Models\District;
use App\Modules\Application\Models\Application;
use App\Modules\Eligibility\Models\Eligibility;
use App\Modules\Eligibility\Models\EligibilityTemplate;
use App\Modules\Program\Models\Program;
use App\Modules\Program\Models\ProgramEligibility;
use App\Modules\Program\Models\ProgramEligibilityLateSubmission;
use App\Modules\Priority\Models\Priority;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\Form\Models\Form;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ProgramController extends Controller
{
    use AuditTrail;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Session::get("district_id") != '0')
            $programs = Program::where('status', '!=', 'T')->where('district_id', Session::get('district_id'))->where("enrollment_id", Session::get("enrollment_id"))->get();
        else
            $programs = Program::where('status', '!=', 'T')->where("enrollment_id", Session::get("enrollment_id"))->get();
        return view("Program::index", compact('programs'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $forms = Form::where("district_id", Session::get("district_id"))->get();
        $priorities = Priority::where('district_id', session('district_id'))->where('status', '!=', 'T')->get();
        $schools = DB::table("student")->where('current_school', '!=', '')->select(DB::raw("DISTINCT(current_school)"))->orderBy('current_school')->get();
        $eligibility_templates = EligibilityTemplate::all();
        $eligibility_types = Eligibility::where('status', 'Y')->get();
        $eligibilities = null;
        foreach ($eligibility_templates as $k => $eligibility_template) {
            $eligibility = null;
            foreach ($eligibility_types as $key => $eligibility_type) {
                if ($eligibility_template->id == $eligibility_type->template_id) {
                    $eligibility[] = $eligibility_type;
                }
            }
            if ($eligibility != null) {
                $eligibilities[] = array_merge($eligibility_template->toArray(), array('eligibility_types' => $eligibility));
            }
        }
        return view('Program::create', compact('eligibilities', 'priorities', 'schools', 'forms'));
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
        $msg = ['priority.required' => 'The priority field is required. ', 'grade_lavel.required' => 'The grade lavel field is required.', 'applicant_filter1.required' => 'The Applicant Group Filter 1 is required. '];
        $request->validate([
            'name' => 'required|max:255',
            // 'applicant_filter1'=>'required|max:255',
            'applicant_filter1' => 'max:255',
            'applicant_filter2' => 'max:255',
            'applicant_filter3' => 'max:255',
            'grade_lavel' => 'required',
            'parent_submission_form' => 'required',

        ], $msg);
        $currentdate = date("Y-m-d h:m:s", time());
        $priority = '';
        $grade_lavel = '';
        if (isset($request->priority)) {
            foreach ($request->priority as $key => $value) {
                if ($key == 0) {
                    $priority = $value;
                    continue;
                }
                $priority = $priority . ',' . $value;
            }
        }
        if (isset($request->grade_lavel)) {
            foreach ($request->grade_lavel as $key => $value) {
                if ($key == 0) {
                    $grade_lavel = $value;
                    continue;
                }
                $grade_lavel = $grade_lavel . ',' . $value;
            }
        }

        if (isset($request->feeder_priorities) && !empty($request->feeder_priorities)) {
            $feeder_priorities = implode(',', $request->feeder_priorities);
        } else {
            $feeder_priorities = "";
        }

        if (isset($request->magnet_priorities) && !empty($request->magnet_priorities)) {
            $magnet_priorities = implode(',', $request->magnet_priorities);
        } else {
            $magnet_priorities = "";
        }
        $programdata = [
            'district_id' => Session::get("district_id"),
            'enrollment_id' => Session::get("enrollment_id") ?? 0,
            'name' => $request->name,
            'applicant_filter1' => $request->applicant_filter1,
            'applicant_filter2' => $request->applicant_filter2,
            'applicant_filter3' => $request->applicant_filter3,
            'grade_lavel' => $grade_lavel,
            'parent_submission_form' => str_replace("form", "", $request->parent_submission_form),
            'magnet_school' => $request->magnet_school,
            'sibling_enabled' => $request->sibling_enabled == 'on' ? 'Y' : 'N',
            'silbling_check' => $request->silbling_check == 'on' ? 'Y' : 'N',
            'existing_magnet_program_alert' => $request->existing_magnet_program_alert == 'on' ? 'Y' : 'N',
            'priority' => $priority,
            'feeder_priorities' => $feeder_priorities,
            'magnet_priorities' => $magnet_priorities,
            'created_at' => $currentdate,
            'updated_at' => $currentdate
        ];

        $programresult = Program::create($programdata);
        if (isset($programresult)) {
            $prog_data = Program::where('id', $programresult->id)->first();
            $this->modelCreate($prog_data, "program");
            Session::flash("success", "Program data added successfully.");
        } else {
            Session::flash("error", "Please Try Again.");
        }

        if (isset($request->save_exit)) {
            return redirect('admin/Program/');
        }

        return redirect('admin/Program/edit/' . $programresult->id);
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
    public function edit($id, $application_id = 0)
    {
        $forms = Form::where("district_id", Session::get("district_id"))->get();
        $district = District::where('id', session('district_id'))->first();
        $applications = Application::where("enrollment_id", Session::get("enrollment_id"))->get();

        $enrollment_id = Session::get("enrollment_id");

        if ($application_id == 0) {
            if (count($applications) > 0)
                $application_id = $applications[0]->id;
        }
        $priorities = Priority::where('district_id', Session::get('district_id'))->where('enrollment_id', $enrollment_id)->where('status', '!=', 'T')->get();
        $schools = DB::table("student")->where('current_school', '!=', '')->select(DB::raw("DISTINCT(current_school)"))->orderBy('current_school')->get();

        $schools = DB::table("student")->select(DB::raw("DISTINCT(current_school)"))->get();
        //print_r($schools);exit;

        //return $priorities;
        $program = Program::where('id', $id)->first();
        $programeligibilities = ProgramEligibility::where('program_id', $id)->where("application_id", $application_id)->get();
        $programeligibilities_late_submission = ProgramEligibilityLateSubmission::where('program_id', $id)->get();
        $eligibility_templates = EligibilityTemplate::all()->toArray();
        // $eligibility_templates[] = array("id"=>0,"name"=>"Template 2");
        // return $eligibility_templates;
        $eligibility_types = Eligibility::where('status', 'Y')->where('enrollment_id', Session::get("enrollment_id"))->where('district_id', Session::get('district_id'))->get();
        $eligibilities = null;
        foreach ($eligibility_templates as $k => $eligibility_template) {
            $eligibility = null;
            foreach ($eligibility_types as $key => $eligibility_type) {
                if ($eligibility_template['id'] == $eligibility_type->template_id) {
                    $eligibility[] = $eligibility_type;
                }
                /*if($eligibility_type->template_id == 0){
                    $eligibility[]=$eligibility_type;
                }*/
            }
            if ($eligibility != null) {
                $eligibilities[] = array_merge($eligibility_template, array('eligibility_types' => $eligibility));
            }
        }
        if (!empty($eligibilities)) {
            foreach ($eligibilities as $key => $eligibility) {
                foreach ($programeligibilities as $k => $programeligibility) {
                    if ($programeligibility->eligibility_type == $eligibility['id']) {
                        $eligibilities[$key]['program_eligibility'] = $programeligibility;
                    }
                }
                // For late submission
                foreach ($programeligibilities_late_submission as $k => $programeligibility) {
                    if ($programeligibility->eligibility_type == $eligibility['id']) {
                        $eligibilities[$key]['program_eligibility_ls'] = $programeligibility;
                    }
                }
            }
        } else
            $eligibilities = array();

        // return $eligibilities;
        return view('Program::edit', compact('program', 'eligibilities', 'priorities', 'district', 'schools', 'forms', 'applications', 'application_id'));
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
        $msg = ['priority.required' => 'The priority field is required. ', 'grade_lavel.required' => 'The grade lavel field is required.', 'applicant_filter1.required' => 'The Applicant Group Filter 1 is required. '];
        $request->validate([
            'name' => 'required|max:255',
            // 'applicant_filter1'=>'required|max:255',
            'applicant_filter1' => 'max:255',
            'applicant_filter2' => 'max:255',
            'applicant_filter3' => 'max:255',
            'grade_lavel' => 'required',
            'parent_submission_form' => 'required',
        ], $msg);
        $currentdate = date("Y-m-d h:m:s", time());
        $priority = '';
        $grade_lavel = $exclude_grade_lavel = '';
        $remove_eligibility_ids = [];
        if (isset($request->priority)) {
            foreach ($request->priority as $key => $value) {
                if ($key == 0) {
                    $priority = $value;
                    continue;
                }
                $priority = $priority . ',' . $value;
            }
        }
        if (isset($request->grade_lavel)) {
            foreach ($request->grade_lavel as $key => $value) {
                if ($key == 0) {
                    $grade_lavel = $value;
                    continue;
                }
                $grade_lavel = $grade_lavel . ',' . $value;
            }
        }
        if (isset($request->exclude_grade_lavel) && $request->existing_magnet_program_alert == "on") {
            foreach ($request->exclude_grade_lavel as $key => $value) {
                if ($key == 0) {
                    $exclude_grade_lavel = $value;
                    continue;
                }
                $exclude_grade_lavel = $exclude_grade_lavel . ',' . $value;
            }
        }

        if (isset($request->feeder_priorities) && !empty($request->feeder_priorities)) {
            $feeder_priorities = implode(',', $request->feeder_priorities);
        } else {
            $feeder_priorities = "";
        }

        if (isset($request->magnet_priorities) && !empty($request->magnet_priorities)) {
            $magnet_priorities = implode(',', $request->magnet_priorities);
        } else {
            $magnet_priorities = "";
        }

        $data = [
            'name' => $request->name,
            'applicant_filter1' => $request->applicant_filter1,
            'applicant_filter2' => $request->applicant_filter2,
            'applicant_filter3' => $request->applicant_filter3,
            'grade_lavel' => $grade_lavel,
            'exclude_grade_lavel' => $exclude_grade_lavel,
            'parent_submission_form' => str_replace("form", "", $request->parent_submission_form),
            'priority' => $priority,
            'current_over_new' => $request->current_over_new,
            'committee_score' => $request->committee_score,
            'audition_score' => $request->audition_score,
            'rating_priority' => $request->rating_priority,
            'combine_score' => $request->combine_score,
            'final_score' => $request->final_score,
            'lottery_number' => $request->lottery_number,
            'selection_method' => $request->selection_method,
            'selection_by' => $request->selection_by,
            'seat_availability_enter_by' => $request->seat_availability_enter_by,
            'sibling_enabled' => $request->sibling_enabled == 'on' ? 'Y' : 'N',
            'basic_method_only' => $request->basic_method_only == 'on' ? 'Y' : 'N',
            'basic_method_only_ls' => $request->basic_method_only_ls == 'on' ? 'Y' : 'N',
            'combined_scoring' => $request->combined_scoring == 'on' ? 'Y' : 'N',
            'combined_scoring_ls' => $request->combined_scoring_ls == 'on' ? 'Y' : 'N',
            'combined_eligibility' => $request->combined_eligibility,
            'combined_eligibility_ls' => $request->combined_eligibility_ls,
            'magnet_school' => $request->magnet_school,
            'created_at' => $currentdate,
            'silbling_check' => $request->silbling_check == 'on' ? 'Y' : 'N',
            'existing_magnet_program_alert' => $request->existing_magnet_program_alert == 'on' ? 'Y' : 'N',
            'feeder_priorities' => $feeder_priorities,
            'magnet_priorities' => $magnet_priorities,
            'updated_at' => $currentdate
        ];

        // return $data;
        // return $request->eligibility_type;
        $initObj = Program::where('id', $id)->first();
        $result = Program::where('id', $id)->update($data);
        $newObj = Program::where('id', $id)->first();

        $this->modelChanges($initObj, $newObj, "program");
        $application_id = $request->application_id;
        // return [$request->eligibility_type, $application_id, $id];
        $remove_eligibility_ids = ProgramEligibility::where('application_id', $request->application_id)->where('program_id', $id)->whereNotIn('assigned_eigibility_name', array_filter($request->assigned_eigibility_name))->pluck('assigned_eigibility_name');

        foreach ($request->eligibility_type as $key => $value) {
            $grade = null;
            $eligibilitydata = [
                'program_id' => $id,
                'application_id' => $application_id,
                'eligibility_type' => $value,
                'determination_method' => $request->determination_method[$key],
                'eligibility_define' => $request->eligibility_define[$key],
                'assigned_eigibility_name' => $request->assigned_eigibility_name[$key],
                'weight' => (isset($request->{"weight" . $value}) ? $request->{"weight" . $value} : ""),
                'grade_lavel_or_recommendation_by' => '',
                'status' => isset($request->status[$value][0]) && $request->status[$value][0] == 'on' ? 'Y' : 'N',
            ];
            /*if (isset($request->eligibility_grade_lavel[$value]))
            {
                foreach ($request->eligibility_grade_lavel[$value] as $index => $grade_levels) {
                    if ($index == 0) {
                        $grade = $grade_levels;
                        continue;
                    }
                    $grade = $grade . ',' . $grade_levels;
                }
                $eligibilitydata['grade_lavel_or_recommendation_by']=$grade;
            }*/
            $grade_lavel_or_recommendation_by = str_replace("-", ",", $request->grade_lavel_or_recommendation_by[$key]);
            $grade_lavel_or_recommendation_by = trim($grade_lavel_or_recommendation_by, ",");
            $eligibilitydata['grade_lavel_or_recommendation_by'] = $grade_lavel_or_recommendation_by;
            $avilableeligibility = ProgramEligibility::where('program_id', $id)->where('application_id', $application_id)->where('eligibility_type', $value)->first();
            if (isset($avilableeligibility)) {
                $eligibilityresult = ProgramEligibility::where('program_id', $id)->where('eligibility_type', $value)->where('application_id', $application_id)->update($eligibilitydata);
                $neweligibility = ProgramEligibility::where('program_id', $id)->where('eligibility_type', $value)->where('application_id', $application_id)->first();

                $this->modelChanges($avilableeligibility, $neweligibility, "program-eligibility");
                //                return $eligibilityresult;
            } else {
                $eligibilityresult = ProgramEligibility::create($eligibilitydata);
                $elig_data = ProgramEligibility::where('id', $eligibilityresult->id)->first();
                $this->modelCreate($elig_data, "program-eligibility");
            }
        }

        //Remove or unset Eligibility Start
        if (!empty($remove_eligibility_ids))
            ProgramEligibility::where('application_id', $request->application_id)->where('program_id', $id)->whereIn('assigned_eigibility_name', $remove_eligibility_ids)->delete();
        //Remove or unset Eligibility End

        // return $request;
        // For late submission
        /*
        $application_id = $request->application_id;
        foreach ($request->eligibility_type_ls as $key=>$value) {
            $grade = null;
            $eligibilitydata = [
                'program_id' => $id,
                'eligibility_type' => $value,
                'application_id' => $application_id,
                
                'determination_method' => $request->determination_method_ls[$key],
                'eligibility_define' => $request->eligibility_define_ls[$key],
                'assigned_eigibility_name' => $request->assigned_eigibility_name_ls[$key],
                'weight' => (isset($request->{"weight_ls".$value}) ? $request->{"weight_ls".$value} : ""),
                'grade_lavel_or_recommendation_by' =>'',
                'status' => isset($request->status_ls[$value][0])&&$request->status_ls[$value][0] == 'on' ? 'Y' : 'N',
            ];

            $grade_lavel_or_recommendation_by_ls = str_replace("-", ",", $request->grade_lavel_or_recommendation_by_ls[$key]);
            $grade_lavel_or_recommendation_by_ls = trim($grade_lavel_or_recommendation_by_ls, ",");
            $eligibilitydata['grade_lavel_or_recommendation_by'] = $grade_lavel_or_recommendation_by_ls;

            $avilableeligibility=ProgramEligibilityLateSubmission::where('program_id',$id)->where('eligibility_type',$value)->first();
            if (isset($avilableeligibility)){
                $eligibilityresult=ProgramEligibilityLateSubmission::where('program_id',$id)->where('eligibility_type',$value)->update($eligibilitydata);
                $neweligibility=ProgramEligibilityLateSubmission::where('program_id',$id)->where('eligibility_type',$value)->first();

                $this->modelChanges($avilableeligibility,$neweligibility,"program-eligibility-late-submission");
            }
            else{
                $eligibilityresult=ProgramEligibilityLateSubmission::create($eligibilitydata);
                $elig_data = ProgramEligibilityLateSubmission::where('id',$eligibilityresult->id)->first();
                $this->modelCreate($elig_data,"program-eligibility-late-submission");
            }
        }
        */


        if (isset($result)) {
            Session::flash("success", "Program Updated successfully.");
        } else {
            Session::flash("error", "Please Try Again.");
        }

        if (isset($request->save_exit)) {
            return redirect('admin/Program');
        }
        return redirect('admin/Program/edit/' . $id . '/' . $application_id);
    }

    public function delete($id)
    {
        $currentdate = date("Y-m-d h:m:s", time());
        $result = Program::where('id', $id)->update(['status' => 'T', 'updated_at' => $currentdate]);
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
        $programs = Program::where('status', 'T')->get();
        return view('Program::trash', compact('programs'));
    }
    public function restore($id)
    {
        $currentdate = date("Y-m-d h:m:s", time());
        $result = Program::where('id', $id)->update(['status' => 'Y', 'updated_at' => $currentdate]);
        if (isset($result)) {
            Session::flash("success", "Program Data restore successfully.");
        } else {
            Session::flash("error", "Please Try Again.");
        }
        return redirect('admin/Program');
    }
    public function status(Request $request)
    {
        $currentdate = date("Y-m-d h:m:s", time());
        //        return $request;
        $result = Program::where('id', $request->id)->update(['status' => $request->status, 'updated_at' => $currentdate]);
        if (isset($result)) {
            return json_encode(true);
        } else {
            return json_encode(false);
        }
    }

    public function fetchProgramByEnrollment($enrollment_id = 0)
    {
        if ($enrollment_id == 0) {
            $enrollment_ids = Enrollment::where("district_id", Session::get('district_id'))->pluck('id')->all();
            $programs = Program::where("district_id", Session::get('district_id'))->whereIn('enrollment_id', $enrollment_ids)->where('status', 'Y')->orderBy('name')->get();
        } else {
            $programs = Program::where("district_id", Session::get('district_id'))->where('enrollment_id', $enrollment_id)->where('status', 'Y')->orderBy('name')->get();
        }

        return json_encode($programs);
    }
}
