<?php

namespace App\Modules\ProcessSelection\Controllers;

use Illuminate\Http\Request;
use App\Modules\ProcessSelection\Models\ProcessSelection;
use App\Http\Controllers\Controller;
use App\Modules\ProcessSelection\Models\Availability;
use App\Modules\Form\Models\Form;
use App\Modules\Program\Models\Program;
use App\Modules\DistrictConfiguration\Models\DistrictConfiguration;
use App\Modules\Application\Models\ApplicationProgram;
use App\Modules\Application\Models\Application;
use App\Modules\Submissions\Models\{Submissions, SubmissionGrade, SubmissionConductDisciplinaryInfo, SubmissionsFinalStatus, SubmissionsStatusLog, SubmissionsStatusUniqueLog};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ProcessSelectionController extends Controller
{

    //public $eligibility_grade_pass = array();

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $display_outcome = SubmissionsStatusUniqueLog::where("enrollment_id", Session::get("enrollment_id"))->count();
        $displayother = SubmissionsFinalStatus::join("submissions", "submissions.id", "submissions_final_status.submission_id")->where("submissions.enrollment_id", Session::get("enrollment_id"))->count();

        //echo $display_outcome."<BR>".$displayother;exit;

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_online_acceptance")->first();
        if (!empty($tmp))
            $last_date_online_acceptance = $tmp->value;
        else
            $last_date_online_acceptance = date('m/d/Y H:i', strtotime('+1 day'));

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_offline_acceptance")->first();
        if (!empty($tmp))
            $last_date_offline_acceptance = $tmp->value;
        else
            $last_date_offline_acceptance = date('m/d/Y H:i', strtotime('+1 day'));
        $forms = Form::where("district_id", Session::get("district_id"))->where('status', 'y')->get();
        $programs = Program::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where('status', 'Y')->get();

        return view("ProcessSelection::index", compact("forms", "programs", "last_date_online_acceptance", "last_date_offline_acceptance", "display_outcome", "displayother"));
    }

    public function store(Request $request)
    {
        set_time_limit(0);
        $display_outcome = SubmissionsStatusUniqueLog::where("enrollment_id", Session::get("enrollment_id"))->count();

        if ($display_outcome == 0) {
            app('App\Modules\Reports\Controllers\ReportsController')->generateStatus();
        }
        $req = $request->all();
        /*
        if($req['form_field'] != "")
            app('App\Modules\Reports\Controllers\ReportsController')->generateStatus();
        else
            app('App\Modules\Reports\Controllers\ReportsController')->generateStatus($req['programs_select']);
        */
        $data = array();
        $data['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
        $data['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
        $data['district_id'] = Session::get("district_id");

        $rs = DistrictConfiguration::updateOrCreate(["name" => "last_date_online_acceptance", "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")], ["name" => "last_date_online_acceptance", "value" => $data['last_date_online_acceptance'], "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")]);

        $rs = DistrictConfiguration::updateOrCreate(["name" => "last_date_offline_acceptance", "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")], ["name" => "last_date_offline_acceptance", "value" => $data['last_date_offline_acceptance'], "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")]);

        $data = [];
        $data['enrollment_id'] = Session::get("enrollment_id");
        $data['form_id'] = $req['form_field'];
        $data['application_id']  = $req['form_field'];
        $data['district_id']  = Session::get("district_id");
        $data['updated_by']  = getUserName(Auth::user()->id);
        $rs = ProcessSelection::updateOrCreate(["enrollment_id" => Session::get("enrollment_id")], $data);


        /* if($req['form_field'] != "")
        {
            return redirect("/admin/Process/Selection/Population/Form/".$req['form_field']);
        }
        else
        {
            return redirect("/admin/Process/Selection/Population/".$req['programs_select']);
        }*/
        echo "done";
    }


    public function population_change($program_id)
    {
        $display_outcome = SubmissionsStatusUniqueLog::where("enrollment_id", Session::get("enrollment_id"))->count();
        // Processing
        $pid = $program_id;

        // Population Changes
        $programs = [];
        $district_id = \Session('district_id');

        $submissions = Submissions::where('district_id', $district_id)->where(function ($q) {
            $q->where("first_choice_final_status", "Offered")
                ->orWhere("second_choice_final_status", "Offered");
        })
            ->where('enrollment_id', $_SESSION['enrollment_id'])->where(function ($q) use ($program_id) {
                $q->where("first_waitlist_for", $program_id)
                    ->orWhere("second_waitlist_for", $program_id);
            })->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);


        $choices = ['first_choice_program_id', 'second_choice_program_id'];
        if (isset($submissions)) {
            foreach ($choices as $choice) {
                foreach ($submissions as $key => $value) {
                    if ($value->$choice == $program_id) {
                        if (!isset($programs[$value->$choice])) {
                            $programs[$value->$choice] = [];
                        }
                        if (!in_array($value->next_grade, $programs[$value->$choice])) {
                            array_push($programs[$value->$choice], $value->next_grade);
                        }
                    }
                }
            }
        }

        ksort($programs);

        $data_ary = [];
        $race_ary = [];

        foreach ($programs as $program_id => $grades) {
            foreach ($grades as $grade) {
                $availability = Availability::where('program_id', $program_id)
                    ->where('grade', $grade)->where("enrollment_id", Session::get("enrollment_id"))->first(['total_seats', 'available_seats']);
                $race_count = [];
                if (!empty($availability)) {
                    foreach ($choices as $choice) {
                        if ($choice == "first_choice_program_id") {
                            $submission_race_data = $submissions->where($choice, $program_id)->where('first_choice_final_status', "Offered")
                                ->where('next_grade', $grade);
                        } else {
                            $submission_race_data = $submissions->where($choice, $program_id)->where('second_choice_final_status', "Offered")
                                ->where('next_grade', $grade);
                        }
                        $race = $submission_race_data->groupBy('race')->map->count();
                        if (count($race) > 0) {
                            $race_ary = array_merge($race_ary, $race->toArray());

                            if (count($race_count) > 0) {
                                foreach ($race as $key => $value) {

                                    if (isset($race_count[$key])) {
                                        $race_count[$key] = $race_count[$key] + $value;
                                    } else {
                                        $race_count[$key] = 1;
                                    }
                                }
                            } else {


                                $race_count = $race;
                            }
                        }
                    }

                    $data = [
                        'program_id' => $program_id,
                        'grade' => $grade,
                        'total_seats' => $availability->total_seats ?? 0,
                        'available_seats' => $availability->available_seats ?? 0,
                        'race_count' => $race_count,
                    ];
                    $data_ary[] = $data;
                    // sorting race in ascending
                    ksort($race_ary);
                }
            }
            // exit;
        }
        $from = "program";
        //        exit;
        // Submissions Result
        return view("ProcessSelection::population_change", compact('data_ary', 'race_ary', 'pid', "from", "display_outcome"));
    }


    public function population_change_form($form_id = 1)
    {
        // Processing
        $pid = $form_id;
        $from = "form";

        $display_outcome = SubmissionsStatusUniqueLog::where("enrollment_id", Session::get("enrollment_id"))->count();

        // Population Changes
        $programs = [];
        $district_id = \Session('district_id');

        $ids = array('"PreK"', '"K"', '"1"', '"2"', '"3"', '"4"', '"5"', '"6"', '"7"', '"8"', '"9"', '"10"', '"11"', '"12"');
        $ids_ordered = implode(',', $ids);

        $rawOrder = DB::raw(sprintf('FIELD(submissions.next_grade, %s)', "'" . implode(',', $ids) . "'"));

        $submissions = Submissions::where('district_id', $district_id)->where(function ($q) {
            $q->where("first_choice_final_status", "Offered")
                ->orWhere("second_choice_final_status", "Offered");
        })
            ->where('submissions.enrollment_id', Session::get("enrollment_id"))->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
            ->orderByRaw('FIELD(next_grade,' . implode(",", $ids) . ')')
            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);


        $choices = ['first_choice_program_id', 'second_choice_program_id'];
        if (isset($submissions)) {
            foreach ($choices as $choice) {
                foreach ($submissions as $key => $value) {
                    if (!isset($programs[$value->$choice])) {
                        $programs[$value->$choice] = [];
                    }
                    if (!in_array($value->next_grade, $programs[$value->$choice])) {
                        array_push($programs[$value->$choice], $value->next_grade);
                    }
                }
            }
        }
        ksort($programs);
        $data_ary = [];
        $race_ary = [];

        foreach ($programs as $program_id => $grades) {
            foreach ($grades as $grade) {
                $availability = Availability::where('program_id', $program_id)->where('enrollment_id', Session::get("enrollment_id"))
                    ->where('grade', $grade)->first(['total_seats', 'available_seats']);
                $race_count = [];
                if (!empty($availability)) {
                    foreach ($choices as $choice) {
                        if ($choice == "first_choice_program_id") {
                            $submission_race_data = $submissions->where($choice, $program_id)->where('first_choice_final_status', "Offered")
                                ->where('next_grade', $grade);
                        } else {
                            $submission_race_data = $submissions->where($choice, $program_id)->where('second_choice_final_status', "Offered")
                                ->where('next_grade', $grade);
                        }
                        $race = $submission_race_data->groupBy('race')->map->count();
                        if (count($race) > 0) {
                            $race_ary = array_merge($race_ary, $race->toArray());

                            if (count($race_count) > 0) {
                                foreach ($race as $key => $value) {

                                    if (isset($race_count[$key])) {
                                        $race_count[$key] = $race_count[$key] + $value;
                                    } else {
                                        $race_count[$key] = 1;
                                    }
                                }
                            } else {


                                $race_count = $race;
                            }
                        }
                    }

                    $data = [
                        'program_id' => $program_id,
                        'grade' => $grade,
                        'total_seats' => $availability->total_seats ?? 0,
                        'available_seats' => $availability->available_seats ?? 0,
                        'race_count' => $race_count,
                    ];
                    $data_ary[] = $data;
                    // sorting race in ascending
                    ksort($race_ary);
                }
            }
            // exit;
        }
        //        exit;
        // Submissions Result
        return view("ProcessSelection::population_change", compact('data_ary', 'race_ary', 'pid', 'from', "display_outcome"));
    }

    public function submissions_results($program_id)
    {
        $pid = $program_id;
        $from = "program";
        $programs = [];
        $district_id = \Session('district_id');
        $submissions = Submissions::where('district_id', $district_id)
            ->where('submissions.enrollment_id', Session::get("enrollment_id"))
            ->where(function ($q) use ($program_id) {
                $q->where("first_choice_program_id", $program_id)
                    ->orWhere("second_choice_program_id", $program_id);
            })->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
            ->get(['submissions.id', 'first_name', 'last_name', 'current_school', 'first_offered_rank', 'second_offered_rank', 'first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status']);

        $final_data = array();
        foreach ($submissions as $key => $value) {
            if ($value->first_choice_program_id == $program_id) {
                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['name'] = $value->first_name . " " . $value->last_name;
                $tmp['grade'] = $value->next_grade;
                $tmp['school'] = $value->current_school;
                $tmp['choice'] = 1;
                $tmp['race'] = $value->race;
                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['offered_status'] = $value->first_choice_final_status;
                $tmp['program'] = getProgramName($value->first_choice_program_id) . " - Grade " . $value->next_grade;
                if ($value->first_choice_final_status == "Offered")
                    $tmp['outcome'] = "<div class='alert1 alert-success text-center'>Offered</div>";
                elseif ($value->first_choice_final_status == "Denied due to Ineligibility")
                    $tmp['outcome'] = "<div class='alert1 alert-info text-center'>Denied due to Ineligibility</div>";
                elseif ($value->first_choice_final_status == "Waitlisted")
                    $tmp['outcome'] = "<div class='alert1 alert-warning text-center'>Waitlist</div>";
                elseif ($value->first_choice_final_status == "Denied due to Incomplete Records")
                    $tmp['outcome'] = "<div class='alert1 alert-danger text-center'>Denied due to Incomplete Records</div>";
                else
                    $tmp['outcome'] = "";
                $final_data[] = $tmp;
            } elseif ($value->second_choice_program_id == $program_id) {
                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['name'] = $value->first_name . " " . $value->last_name;
                $tmp['grade'] = $value->next_grade;
                $tmp['school'] = $value->current_school;
                $tmp['race'] = $value->race;
                $tmp['program_name'] = getProgramName($value->second_choice_program_id);
                $tmp['offered_status'] = $value->second_choice_final_status;
                if ($value->second_choice_final_status == "Offered")
                    $tmp['outcome'] = "<div class='alert1 alert-success text-center'>Offered</div>";
                elseif ($value->second_choice_final_status == "Denied due to Ineligibility")
                    $tmp['outcome'] = "<div class='alert1 alert-info text-center'>Denied due to Ineligibility</div>";
                elseif ($value->second_choice_final_status == "Waitlisted")
                    $tmp['outcome'] = "<div class='alert1 alert-warning text-center'>Waitlist</div>";
                elseif ($value->second_choice_final_status == "Denied due to Incomplete Records")
                    $tmp['outcome'] = "<div class='alert1 alert-danger text-center'>Denied due to Incomplete Records</div>";
                else
                    $tmp['outcome'] = "";
                $tmp['program'] = getProgramName($value->second_choice_program_id) . " - Grade " . $value->next_grade;

                if ($value->second_choice_final_status == "Offered")
                    $tmp['outcome'] = "<div class='alert1 alert-success text-center'>Offered</div>";
                else
                    $tmp['outcome'] = "<div class='alert1 alert-danger text-center'>Denied</div>";
                $final_data[] = $tmp;
            }
        }
        $grade = $outcome = array();
        foreach ($final_data as $key => $value) {
            $grade['grade'][] = $value['grade'];
            $outcome['outcome'][] = $value['outcome'];
        }
        array_multisort($grade['grade'], SORT_ASC, $outcome['outcome'], SORT_DESC, $final_data);

        return view("ProcessSelection::submissions_result", compact('final_data', 'pid', 'from'));
    }


    public function submissions_results_form($form_id = 1)
    {
        $pid = $form_id;
        $from = "form";
        $programs = [];
        $district_id = Session::get('district_id');
        $display_outcome = SubmissionsStatusUniqueLog::where("enrollment_id", Session::get("enrollment_id"))->count();
        $submissions = Submissions::where('district_id', $district_id)
            ->where('submissions.enrollment_id', Session::get("enrollment_id"))
            ->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
            ->get(['submissions.id', 'first_name', 'last_name', 'current_school', 'first_offered_rank', 'second_offered_rank', 'first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status']);

        $final_data = array();
        foreach ($submissions as $key => $value) {
            $tmp = array();
            $tmp['id'] = $value->id;
            $tmp['name'] = $value->first_name . " " . $value->last_name;
            $tmp['grade'] = $value->next_grade;
            $tmp['school'] = $value->current_school;
            $tmp['choice'] = 1;
            $tmp['race'] = $value->race;
            $tmp['program'] = getProgramName($value->first_choice_program_id) . " - Grade " . $value->next_grade;
            $tmp['program_name'] = getProgramName($value->first_choice_program_id);
            $tmp['offered_status'] = $value->first_choice_final_status;
            if ($value->first_choice_final_status == "Offered")
                $tmp['outcome'] = "<div class='alert1 alert-success text-center'>Offered</div>";
            elseif ($value->first_choice_final_status == "Denied due to Ineligibility")
                $tmp['outcome'] = "<div class='alert1 alert-info text-center'>Denied due to Ineligibility</div>";
            elseif ($value->first_choice_final_status == "Waitlisted")
                $tmp['outcome'] = "<div class='alert1 alert-warning text-center'>Waitlist</div>";
            elseif ($value->first_choice_final_status == "Denied due to Incomplete Records")
                $tmp['outcome'] = "<div class='alert1 alert-danger text-center'>Denied due to Incomplete Records</div>";
            else
                $tmp['outcome'] = "";

            $final_data[] = $tmp;

            if ($value->second_choice_program_id != 0) {
                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['name'] = $value->first_name . " " . $value->last_name;
                $tmp['grade'] = $value->next_grade;
                $tmp['school'] = $value->current_school;
                $tmp['race'] = $value->race;
                $tmp['choice'] = 2;
                $tmp['program'] = getProgramName($value->second_choice_program_id) . " - Grade " . $value->next_grade;
                $tmp['program_name'] = getProgramName($value->second_choice_program_id);
                $tmp['offered_status'] = $value->second_choice_final_status;

                if ($value->second_choice_final_status == "Offered")
                    $tmp['outcome'] = "<div class='alert1 alert-success text-center'>Offered</div>";
                elseif ($value->second_choice_final_status == "Denied due to Ineligibility")
                    $tmp['outcome'] = "<div class='alert1 alert-info text-center'>Denied due to Ineligibility</div>";
                elseif ($value->second_choice_final_status == "Waitlisted")
                    $tmp['outcome'] = "<div class='alert1 alert-warning text-center'>Waitlist</div>";
                elseif ($value->second_choice_final_status == "Denied due to Incomplete Records")
                    $tmp['outcome'] = "<div class='alert1 alert-danger text-center'>Denied due to Incomplete Records</div>";
                else
                    $tmp['outcome'] = "";
                $final_data[] = $tmp;
            }
        }
        $grade = $outcome = array();
        foreach ($final_data as $key => $value) {
            $grade['grade'][] = $value['grade'];
            $outcome['outcome'][] = $value['outcome'];
        }
        array_multisort($grade['grade'], SORT_ASC, $outcome['outcome'], SORT_DESC, $final_data);

        return view("ProcessSelection::submissions_result", compact('final_data', 'pid', 'from', 'display_outcome'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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

    public function selection_accept(Request $request)
    {
        $data = SubmissionsFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->get();
        foreach ($data as $key => $value) {
            $status = $value->first_choice_final_status;
            if ($value->second_choice_final_status == "Offered")
                $status = "Offered";
            $submission_id = $value->submission_id;
            $rs = Submissions::where("id", $submission_id)->select("submission_status")->first();
            $old_status = $rs->submission_status;

            $comment = "By Accept and Commit Event";
            if ($status == "Offered") {
                $submission = Submissions::where("id", $value->submission_id)->first();
                if ($value->first_choice_final_status == "Offered") {
                    $program_name = getProgramName($submission->first_choice_program_id);
                } else if ($value->second_choice_final_status == "Offered") {
                    $program_name = getProgramName($submission->second_choice_program_id);
                } else {
                    $program_name = "";
                }

                $program_name .= " - Grade " . $submission->next_grade;
                $comment = "System has Offered " . $program_name . " to Parent";
            } else if ($status == "Denied due to Ineligibility") {
                if ($value->first_choice_eligibility_reason != '') {
                    if ($value->first_choice_eligibility_reason == "Both") {
                        $comment = "System has denied the application because of Grades and CDI Ineligibility";
                    } else if ($value->first_choice_eligibility_reason == "Grade") {
                        $comment = "System has denied the application because of Grades Ineligibility";
                    } else {
                        $comment = "System has denied the application because of CDI Ineligibility";
                    }
                }
            } else if ($status == "Denied due to Incomplete Records") {
                if ($value->incomplete_reason != '') {
                    if ($value->incomplete_reason == "Both") {
                        $comment = "System has denied the application because of Grades and CDI Ineligibility";
                    } else if ($value->incomplete_reason == "Grade") {
                        $comment = "System has denied the application because of Incomplete Grades";
                    } else {
                        $comment = "System has denied the application because of Incomplete CDI";
                    }
                }
            }
            $rs = SubmissionsStatusLog::create(array("submission_id" => $submission_id, "new_status" => $status, "old_status" => $old_status, "updated_by" => Auth::user()->id, "comment" => $comment));
            $rs = SubmissionsStatusUniqueLog::updateOrCreate(["submission_id" => $submission_id, "enrollment_id" => Session::get("enrollment_id")], array("submission_id" => $submission_id, "new_status" => $status, "old_status" => $old_status, "updated_by" => Auth::user()->id, "enrollment_id" => Session::get("enrollment_id")));
            $rs = Submissions::where("id", $submission_id)->update(["submission_status" => $status]);
        }
        echo "Done";
        exit;
    }

    public function selection_revert()
    {
        $quotations = SubmissionsStatusLog::orderBy('created_at', 'ASC')
            ->get()
            ->unique('submission_id');

        foreach ($quotations as $key => $value) {
            $rs = Submissions::where("id", $value->submission_id)->update(array("submission_status" => $value->old_status));
        }
        SubmissionsStatusUniqueLog::truncate();
        SubmissionsFinalStatus::truncate();
        //SubmissionsStatusUniquesLog::truncate();

    }
}
