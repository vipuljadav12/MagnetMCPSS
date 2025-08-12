<?php

namespace App\Modules\Waitlist\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Form\Models\Form;
use App\Modules\Program\Models\Program;
use App\Modules\DistrictConfiguration\Models\DistrictConfiguration;
use App\Modules\Application\Models\ApplicationProgram;
use App\Modules\Application\Models\Application;
use App\Modules\ProcessSelection\Models\Availability;
use App\Modules\SetAvailability\Models\WaitlistAvailability;
use App\Modules\Waitlist\Models\{WaitlistProcessLogs,WaitlistAvailabilityLog,WaitlistAvailabilityProcessLog,WaitlistIndividualAvailability};
use App\Modules\LateSubmission\Models\{LateSubmissionProcessLogs,LateSubmissionAvailabilityLog,LateSubmissionAvailabilityProcessLog,LateSubmissionIndividualAvailability};
use App\Modules\Submissions\Models\{Submissions,SubmissionGrade,SubmissionConductDisciplinaryInfo,SubmissionsFinalStatus,SubmissionsWaitlistFinalStatus,SubmissionsStatusLog,SubmissionsWaitlistStatusUniqueLog,LateSubmissionFinalStatus};
use Auth;
use DB;
use Session;

class WaitlistController extends Controller
{

    //public $eligibility_grade_pass = array();

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     public function validateApplication($application_id)
    {
        $rs = Submissions::where("enrollment_id", Session::get("enrollment_id"))->where("form_id", $application_id)->where("submission_status", "Offered")->count();
        if($rs > 0)
            echo "Selected Applications has still open offered submissions.";
        else
            echo "OK";
    }
    public function index()
    {
        $display_outcome = $this->checkWailistOpen();


        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("last_date_online", ">", date("Y-m-d H:i:s"))->first();
        if(!empty($rs))
            $displayother = 1;
        else
        {
            $t = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count()+1;
            $displayother = SubmissionsWaitlistFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $t)->count();
        }
        $forms = Form::where("district_id", Session::get("district_id"))->where('status', 'y')->get();
        $programs = Program::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where('status', 'Y')->get();

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_online_acceptance")->first();
        if(!empty($tmp))
            $last_date_waitlist_online_acceptance = $tmp->value;
        else
            $last_date_waitlist_online_acceptance = date('m/d/Y H:i', strtotime('+1 day'));

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_offline_acceptance")->first();
        if(!empty($tmp))
            $last_date_waitlist_offline_acceptance = $tmp->value;
        else
            $last_date_waitlist_offline_acceptance = date('m/d/Y H:i', strtotime('+1 day'));

        return view("Waitlist::index", compact("forms", "programs","last_date_waitlist_online_acceptance", "last_date_waitlist_offline_acceptance", "display_outcome", "displayother"));
    }

    public function show_all_individual($form_id=1)
    {
        $display_outcome = $this->checkWailistOpen();


        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("last_date_online", ">", date("Y-m-d H:i:s"))->first();
        if(!empty($rs))
            $displayother = 1;
        else
        {
            $t = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count()+1;
            $displayother = SubmissionsWaitlistFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $t)->count();
        }

        $programs = Program::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("status", "Y")->orderBy("id")->get();
        return view("Waitlist::individual_index", compact("programs","displayother","display_outcome"));
    }

    public function individual_program_show($program_id)
    {
        $district_id = Session::get("district_id");

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_online_acceptance")->first();
        if(!empty($tmp))
            $last_date_online = $tmp->value;
        else
            $last_date_online = "";//date('m/d/Y H:i', strtotime('+1 day'));

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_offline_acceptance")->first();
        if(!empty($tmp))
            $last_date_offline = $tmp->value;
        else
            $last_date_offline = "";//date('m/d/Y H:i', strtotime('+1 day'));

        $form_id = 1;
        $pg = explode("--", $program_id);

        $pid = $pg[0];
        $grade = $pg[1];

        $ids = array('"'.$grade.'"');
        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("last_date_online", ">", date("Y-m-d H:i:s"))->first();

        if(!empty($rs))
        {
            $disabled = 1;
            $version = $rs->version;
            $data1 = WaitlistAvailabilityProcessLog::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->where("program_id", $pid)->where("grade", $grade)->first();
            $data = [
                    'program_id' => $data1->program_id,
                    'grade' => $data1->grade,
                    'total_seats' => $data1->total_capacity,
                    'available_seats' => $data1->total_capacity,
                    'offer_count' => $data1->offered_count,
                    'withdrawn_seats' => $data1->withdrawn_seats,
                    'waitlist_count' => $data1->waitlist_count
                ];
                $data_ary[] = $data;
                // sorting race in ascending
        }
        else
        {
            $last_type = $this->check_last_process();
             $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')
                    ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
                    /*
            if($last_type == "waitlist")
            {
                $rs = WaitlistProcessLogs::orderBy("created_at", "DESC")->first();
                $version = $rs->version;
                $submissions = Submissions::where('district_id', $district_id)->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')->where("submissions_waitlist_final_status.version", $version)
                        ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
            }
            elseif($last_type == "late_submission")
            {
                $rs = LateSubmissionProcessLogs::orderBy("created_at", "DESC")->first();
                $version = $rs->version;
                $submissions = Submissions::where('district_id', $district_id)->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')->where("late_submissions_final_status.version", $version)
                        ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
            }
            else
            {
                $version = 0;
                $submissions = Submissions::where('district_id', $district_id)->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')
                    ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
            }
*/
            $ws = WaitlistIndividualAvailability::where("program_id", $pid)->where("grade", $grade)->first();
            if(!empty($ws))
                $p_withdrawn_seats = $ws->withdrawn_seats;
            else
                $p_withdrawn_seats = 0;         

            $ws = lateSubmissionIndividualAvailability::where("program_id", $pid)->where("grade", $grade)->first();
            if(!empty($ws))
                $p_withdrawn_seats += $ws->withdrawn_seats;

            $disabled = 0;
            $display_outcome = 0;
            $choices = ['first_choice_program_id', 'second_choice_program_id'];
            /*$submissions = Submissions::where('district_id', $district_id)->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')
            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
            */
            $programs = [];
             if (isset($submissions)) {
                foreach ($choices as $choice) {
                    foreach ($submissions as $key => $value) {
                        if($value->$choice == $pid && $value->next_grade == $grade)
                        {
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


            if(!empty($programs))
            {
                 ksort($programs);

                foreach ($programs as $program_id => $grades) { // 2
                    foreach ($grades as $grade) {

                        $rsP = WaitlistAvailabilityProcessLog::where("program_id", $program_id)->where("grade", $grade)->sum("withdrawn_seats");
                        $additional = $rsP;


                        $availability = Availability::where('program_id', $program_id)
                            ->where('grade', $grade)->first(['total_seats', 'available_seats']);



                $waitlist_count = $this->get_waitlist_count($last_type, $program_id, $grade, $district_id, $form_id);

                        
                        $total_seats = ($availability->available_seats ?? 0) + $additional;

                            $data = [
                                'program_id' => $program_id,
                                'grade' => $grade,
                                'total_seats' => $total_seats ?? 0,
                                'available_seats' => $total_seats,
                                'offer_count' => $this->get_offer_count($program_id, $grade, $district_id, $form_id),
                                'withdrawn_seats' => $p_withdrawn_seats,
                                'waitlist_count' => $waitlist_count
                            ];
                            $data_ary[] = $data;
                                // sorting race in ascending
                            ksort($data_ary);
                    }
                } //2

            }
            else
            {
                $data_ary = [];
            }
           
        }


        
        $display_outcome = $this->checkWailistOpen();
        $returnHTML =  view("Waitlist::Template.individual_programs_response",compact("data_ary", "last_date_online", "last_date_offline", "display_outcome","program_id","disabled"))->render();
        return response()->json(array('success' => true, 'html'=>$returnHTML));         

        $displayother = SubmissionsWaitlistFinalStatus::count();
        

        $programs = Program::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->orderBy("id")->get();
        return view("Waitlist::individual_index", compact("programs","displayother","display_outcome", "version", "last_date_online", "last_date_offline", "data_ary"));
    }

    public function show_all_availability($form_id = 1)
    {
       
        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_online_acceptance")->first();
        if(!empty($tmp))
            $last_date_waitlist_online_acceptance = $tmp->value;
        else
            $last_date_waitlist_online_acceptance = "";//ate('m/d/Y H:i', strtotime('+1 day'));

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_offline_acceptance")->first();
        if(!empty($tmp))
            $last_date_waitlist_offline_acceptance = $tmp->value;
        else
            $last_date_waitlist_offline_acceptance = "";//date('m/d/Y H:i', strtotime('+1 day'));


                $programs = [];
        $district_id = \Session('district_id');

        $ids = array('"PreK"', '"K"', '"1"', '"2"', '"3"', '"4"', '"5"', '"6"', '"7"', '"8"', '"9"', '"10"', '"11"', '"12"');
        $ids_ordered = implode(',', $ids);

        $rawOrder = DB::raw(sprintf('FIELD(submissions.next_grade, %s)', "'".implode(',', $ids)."'"));

        $data_ary = [];
        $race_ary = [];

        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("last_date_online", ">", date("Y-m-d H:i:s"))->first();
        if(!empty($rs))
        {
            $version = $rs->version;

            $data = WaitlistAvailabilityProcessLog::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->get();
            foreach($data as $key=>$value)
            {
                $data = [
                        'program_id' => $value->program_id,
                        'grade' => $value->grade,
                        'total_seats' => $value->total_capacity,
                        'available_seats' => $value->total_capacity,
                        'offer_count' => $value->offered_count,
                        'withdrawn_seats' => $value->withdrawn_seats,
                        'waitlist_count' => $value->waitlist_count
                    ];
                    $data_ary[] = $data;
                        // sorting race in ascending
            }
        }
        else
        {
            /* Here we need to current version data from waitlist_final_status table */

            $last_type = $this->check_last_process();
            $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')
                ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);

                /*
            if($last_type == "waitlist")
            {
                $rs = WaitlistProcessLogs::orderBy("created_at", "DESC")->first();
                $version = $rs->version;
                $submissions = Submissions::where('district_id', $district_id)->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')->where("submissions_waitlist_final_status.version", $version)
                ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
            }
            elseif($last_type == "late_submission")
            {
                $rs = LateSubmissionProcessLogs::orderBy("created_at", "DESC")->first();
                $version = $rs->version;
                $submissions = Submissions::where('district_id', $district_id)->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')->where("late_submissions_final_status.version", $version)
                ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
            }
            else
            {
                $version = 0;
                $submissions = Submissions::where('district_id', $district_id)->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')
                ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
            }*/ 

            
            $choices = ['first_choice_program_id', 'second_choice_program_id'];
        if (isset($submissions)) {
            foreach ($choices as $choice) {
                foreach ($submissions as $key => $value) {
                    if($value->$choice != 0)
                    {
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

        $count = 0;

        foreach ($programs as $program_id => $grades) {
            foreach ($grades as $grade) {

                $rsP = WaitlistAvailabilityProcessLog::where("enrollment_id", Session::get("enrollment_id"))->where("program_id", $program_id)->where("grade", $grade)->sum("withdrawn_seats");
                $additional = $rsP;

                $rsP = LateSubmissionAvailabilityProcessLog::where("program_id", $program_id)->where("grade", $grade)->sum("withdrawn_seats");
                $additional += $rsP;

                $availability = Availability::where('program_id', $program_id)
                    ->where('grade', $grade)->first(['total_seats', 'available_seats']);

                $d = WaitlistAvailabilityProcessLog::where("program_id", $program_id)->where("grade", $grade)->orderBy("created_at", "DESC")->first();
                if(!empty($d))
                {
                    if($d->type == "Late Submission")
                    {
                        $table = "late_submissions_final_status";
                        $version = $d->version;
                    }
                    elseif($d->type == "Waitlist")
                    {
                        $table = "submissions_waitlist_final_status";
                        $version = $d->version;
                    }
                    else
                    {
                        $table = "submissions_final_status";
                        $version = 0;
                    }

                    $waitlist_count1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('first_choice_final_status', 'Waitlisted')->where('first_offer_status', 'Pending')->where('next_grade', $grade)->join($table, $table.".submission_id", "submissions.id")->where("first_choice_program_id", $program_id)->where($table.".version", $version)->count();

                    $waitlist_count2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('second_choice_final_status', 'Waitlisted')->where('second_offer_status', 'Pending')->where('next_grade', $grade)->join($table, $table.".submission_id", "submissions.id")->where("second_choice_program_id", $program_id)->where($table.".version", $version)->count();
                    $waitlist_count = $waitlist_count1 + $waitlist_count2;

                }
                else
                    $waitlist_count = 0;


                $rs = WaitlistAvailability::where("program_id", $program_id)->where("grade", $grade)->first();
                if(!empty($rs))
                {
                    $withdrawn_seats = $rs->withdrawn_seats;
                }
                else
                {
                    $withdrawn_seats = 0;
                }
                $total_seats = ($availability->available_seats ?? 0) + $additional;
                    $data = [
                        'program_id' => $program_id,
                        'grade' => $grade,
                        'total_seats' => $availability->total_seats ?? 0,
                        'available_seats' => $total_seats,
                        'offer_count' => $this->get_offer_count($program_id, $grade, $district_id, $form_id),
                        'withdrawn_seats' => $withdrawn_seats,
                        'waitlist_count' => $waitlist_count
                    ];
                    $data_ary[] = $data;
                    //dd($data);
                        // sorting race in ascending
                    ksort($data_ary);
                }
            }
        }

        



//            echo $count;exit;
           // exit;
        
        $display_outcome = $this->checkWailistOpen();
        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("last_date_online", ">", date("Y-m-d H:i:s"))->first();
        if(!empty($rs))
            $displayother = 1;
        else
        {
            $t = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count()+1;
            $displayother = SubmissionsWaitlistFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $t)->count();
        }
/*echo "<pre>";
print_r($data_ary);
exit;
*/        return view("Waitlist::all_availability_index", compact("form_id", "data_ary","last_date_waitlist_online_acceptance", "last_date_waitlist_offline_acceptance", "display_outcome", "displayother"));

    }


    public function seatStatus($enrollment_id = 0)
    {
        $ids = array('"PreK"', '"K"', '"1"', '"2"', '"3"', '"4"', '"5"', '"6"', '"7"', '"8"', '"9"', '"10"', '"11"', '"12"');
         $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $district_id = Session::get("district_id");
         $submissions = Submissions::where("enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')
            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade']);


        $choices = ['first_choice_program_id', 'second_choice_program_id'];
        $prgCount = array();;
        if (isset($submissions)) {
            foreach ($choices as $choice) {
                foreach ($submissions as $key => $value) {
                    if($value->$choice != 0)
                    {
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
        $final_data = array();
        foreach($programs as $key=>$value)
        {
            foreach($value as $ikey=>$ivalue)
            {
                $tmp = array();
                $tmp['program_name'] = getProgramName($key) ." - Grade ".$ivalue;
                $rs = Availability::where("program_id", $key)->where("grade", $ivalue)->select("available_seats")->first();
                $tmp['total_seats'] = $rs->available_seats;
                $tmp['total_applicants'] = Submissions::where("enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where(function($query) use ($key){
                    $query->where('first_choice_program_id', $key);
                    $query->orWhere('second_choice_program_id', $key);
                })->where('next_grade', $ivalue)->get()->count();

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                                  ->where("first_choice_program_id", $key)
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                                  ->where("second_choice_program_id", $key)
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $tmp['offered'] = $rs1 + $rs2;


                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Declined due to Eligibility")
                                  ->where("first_choice_program_id", $key)
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Declined due to Eligibility")
                                  ->where("second_choice_program_id", $key)
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $tmp['noteligible'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Denied Due To Incomplete Records")
                                  ->where("first_choice_program_id", $key)
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Denied Due To Incomplete Records")
                                  ->where("second_choice_program_id", $key)
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $tmp['Incomplete'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                                  ->where("first_choice_program_id", $key)
                                  ->where("first_offer_status", 'Declined')
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                                  ->where("second_choice_program_id", $key)
                                  ->where("second_offer_status", 'Declined')
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $tmp['Decline'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                                  ->where("first_choice_program_id", $key)
                                  ->where("first_offer_status", 'Declined & Waitlisted')
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                                  ->where("second_choice_program_id", $key)
                                  ->where("second_offer_status", 'Declined & Waitlisted')
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $tmp['Waitlisted'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                                  ->where("first_choice_program_id", $key)
                                  ->where("first_offer_status", 'Accepted')
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                                  ->where("second_choice_program_id", $key)
                                  ->where("second_offer_status", 'Accepted')
                                  ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)
                            ->get()->count();
                $tmp['Accepted'] = $rs1 + $rs2;

                $tmp['remaining'] = $tmp['total_seats'] - $tmp['Accepted'];
                $final_data[] = $tmp;

            }

        }

        //print_r($final_data);exit;
        return view("Reports::seats_status",compact("enrollment_id", "enrollment", "final_data"));
    }

    public function population_change($form_id=1)
    {

        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $version = $rs + 1;

        // Processing
        $pid = $form_id;
        $from = "form";

        $display_outcome = $this->checkWailistOpen();

     
        $rs = WaitlistAvailability::get();
        $parray = [];
        if(count($rs) > 0)
        {
            foreach($rs as $key=>$value)
            {
                if(!isset($parray[$value->program_id]))
                {
                    $parray[$value->program_id] = [];
                }
                array_push($parray[$value->program_id], $value->grade);
            }
        }
        else
        {
            $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("last_date_online", ">", date("Y-m-d H:i:s"))->first();

            if(!empty($rs))
            {
                $version = $rs->version;
                $rs = WaitlistAvailabilityProcessLog::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->get();
                foreach($rs as $key=>$value)
                {
                    if(!isset($parray[$value->program_id]))
                    {
                        $parray[$value->program_id] = [];
                    }
                    array_push($parray[$value->program_id], $value->grade);
                }

            }
        }


        // Population Changes
        $programs = [];
        $district_id = \Session('district_id');

        $ids = array('"PreK"', '"K"', '"1"', '"2"', '"3"', '"4"', '"5"', '"6"', '"7"', '"8"', '"9"', '"10"', '"11"', '"12"');
        $ids_ordered = implode(',', $ids);

        $rawOrder = DB::raw(sprintf('FIELD(submissions.next_grade, %s)', "'".implode(',', $ids)."'"));

        $submissions1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where(function ($q) {
                                $q->where("first_choice_final_status", "Offered")
                                  ->orWhere("second_choice_final_status", "Offered");  
                            })
                            ->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                            ->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')
            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);


        $choices = ['first_choice_program_id', 'second_choice_program_id'];
        if (isset($submissions1)) {
            foreach ($choices as $choice) {
                foreach ($submissions1 as $key => $value) {
                        if (!isset($programs[$value->$choice]) && in_array($value->$choice, array_keys($parray))) {
                            $programs[$value->$choice] = [];
                        }
                        if (isset($programs[$value->$choice]) && !in_array($value->next_grade, $programs[$value->$choice])) {
                            if(in_array($value->next_grade, $parray[$value->$choice]))
                            {
                                array_push($programs[$value->$choice], $value->next_grade);
                            }
                        }
                }
            }
        }
        ksort($programs);

        $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where(function ($q) {
                                $q->where("first_choice_final_status", "Offered")
                                  ->orWhere("second_choice_final_status", "Offered");  
                            })
                            ->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')->where("submissions_waitlist_final_status.version", $version)
            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);


        $data_ary = [];
        $race_ary = [];


        foreach ($programs as $program_id => $grades) {
            foreach ($grades as $grade) {
                $availability = Availability::where('program_id', $program_id)
                    ->where('grade', $grade)->first(['total_seats', 'available_seats']);
                $race_count = [];
                if (!empty($availability)) {
                    foreach ($choices as $choice) {
                        if($choice == "first_choice_program_id")
                        {
                            $submission_race_data = $submissions->where($choice, $program_id)->where('first_choice_final_status', "Offered")
                                ->where('next_grade', $grade);
                         }
                         else
                         {
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
                                    }else{
                                        $race_count[$key] = 1;
                                    }
                                }
                            }else{
                                
                            
                             $race_count = $race;

                            }
                        }

                    }

                $offer_count = Submissions::where("submission_status", "Offered and Accepted")->where("awarded_school", getProgramName($program_id))->where("next_grade", $grade)->where("enrollment_id", Session::get('enrollment_id'))->count();


                $rs = WaitlistAvailability::where("program_id", $program_id)->where("grade", $grade)->first();
                if(!empty($rs))
                {
                    $withdrawn_seats  = $rs->withdrawn_seats ?? 0;
                }
                else
                {
                    $withdrawn_seats = 0;//($availability->available_seats ?? 0) - $offer_count;
                }


                $rsP = WaitlistAvailabilityProcessLog::where("program_id", $program_id)->where("grade", $grade)->sum('withdrawn_seats');

                $additional = $rsP;
                
                $availability = Availability::where('program_id', $program_id)
                    ->where('grade', $grade)->first(['total_seats', 'available_seats']);
                $total_seats = ($availability->available_seats ?? 0) + $additional + $withdrawn_seats;

                if($program_id == 20 && $grade == 4)
                {
                    //dd(getProgramName($program_id), $grade, $availability->available_seats, $withdrawn_seats, $total_seats, $offer_count);
                }

                    $data = [
                        'program_id' => $program_id,
                        'grade' => $grade,
                        'total_seats' => $total_seats,
                        'available_seats' => $total_seats - $offer_count,
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
        return view("Waitlist::population_change", compact('data_ary', 'race_ary', 'pid', 'from', "display_outcome","form_id"));
    }



    public function population_change_version($version=0)
    {
        $form_id = 1;
        $version = 1;
        $version_data = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->first();
        $display_outcome = $this->checkWailistOpen();
        $data_ary = [];
        $race_ary = [];
        $pid = $form_id;
        $from = "form";
       
        $display_outcome = $this->checkWailistOpen();

        $parray = array();
        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("last_date_online", ">", date("Y-m-d H:i:s"))->first();
            if(!empty($rs))
            {
                $version = $rs->version;
            }
                $rs = WaitlistAvailabilityProcessLog::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->get();
                foreach($rs as $key=>$value)
                {
                    if(!isset($parray[$value->program_id]))
                    {
                        $parray[$value->program_id] = [];
                    }
                    array_push($parray[$value->program_id], $value->grade);
                }

            

        // Population Changes
        $programs = [];
        $district_id = \Session('district_id');

        $ids = array('"PreK"', '"K"', '"1"', '"2"', '"3"', '"4"', '"5"', '"6"', '"7"', '"8"', '"9"', '"10"', '"11"', '"12"');
        $ids_ordered = implode(',', $ids);

        $rawOrder = DB::raw(sprintf('FIELD(submissions.next_grade, %s)', "'".implode(',', $ids)."'"));

        $submissions1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where(function ($q) {
                                $q->where("first_choice_final_status", "Offered")
                                  ->orWhere("second_choice_final_status", "Offered");  
                            })
                            ->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')->where("submissions_waitlist_final_status.version", $version)
            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);


        $choices = ['first_choice_program_id', 'second_choice_program_id'];
        if (isset($submissions1)) {
            foreach ($choices as $choice) {
                foreach ($submissions1 as $key => $value) {
                        if (!isset($programs[$value->$choice]) && in_array($value->$choice, array_keys($parray))) {
                            $programs[$value->$choice] = [];
                        }
                        if (isset($programs[$value->$choice]) && !in_array($value->next_grade, $programs[$value->$choice])) {
                            if(in_array($value->next_grade, $parray[$value->$choice]))
                            {
                                array_push($programs[$value->$choice], $value->next_grade);
                            }
                        }
                }
            }
        }
        ksort($programs);

        $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where(function ($q) {
                                $q->where("first_choice_final_status", "Offered")
                                  ->orWhere("second_choice_final_status", "Offered");  
                            })
                            ->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')
            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);


        

        foreach ($programs as $program_id => $grades) {
            foreach ($grades as $grade) {
                $availability = Availability::where('program_id', $program_id)
                    ->where('grade', $grade)->first(['total_seats', 'available_seats']);
                $race_count = [];
                if (!empty($availability)) {
                    foreach ($choices as $choice) {
                        if($choice == "first_choice_program_id")
                        {
                            $submission_race_data = $submissions->where($choice, $program_id)->where('first_choice_final_status', "Offered")
                                ->where('next_grade', $grade);
                         }
                         else
                         {
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
                                    }else{
                                        $race_count[$key] = 1;
                                    }
                                }
                            }else{
                                
                            
                             $race_count = $race;

                            }
                        }

                    }

                $offer_count = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', Session::get("district_id"))->where(function ($q) use ($program_id, $grade){
                                $q->where(function ($q1)  use ($program_id, $grade){
                                    $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $program_id)->where('next_grade', $grade);
                                })->orWhere(function ($q1) use ($program_id, $grade){
                                    $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $program_id)->where('next_grade', $grade);
                                });
                            })->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->count();


                $rs = WaitlistAvailability::where("program_id", $program_id)->where("grade", $grade)->first();
                if(!empty($rs))
                {
                    $withdrawn_seats  = ($availability->available_seats ?? 0) + $rs->withdrawn_seats - $offer_count;
                }
                else
                {
                    $withdrawn_seats = ($availability->available_seats ?? 0) - $offer_count;
                }

                 $rsP = WaitlistAvailabilityLog::where("program_id", $program_id)->where("grade", $grade)->first();
                if(!empty($rsP))
                {
                    $additional = $rsP->withdrawn_seats;
                }
                else
                {
                    $additional = 0;
                }
                $availability = Availability::where('program_id', $program_id)
                    ->where('grade', $grade)->first(['total_seats', 'available_seats']);
                $total_seats = ($availability->available_seats ?? 0) + $additional;



                    $data = [
                        'program_id' => $program_id,
                        'grade' => $grade,
                        'total_seats' => $total_seats,
                        'available_seats' => $withdrawn_seats+$additional,
                        'race_count' => $race_count,
                    ];
                   // dd($data);
                    $data_ary[] = $data;
                    // sorting race in ascending
                    ksort($race_ary);
                }
            }
           // exit;
        }
       

        // Processing

//        exit;
        // Submissions Result
        return view("Waitlist::population_change_report", compact('data_ary', 'race_ary', 'pid', 'from', "display_outcome","form_id", "version_data", "version"));
    }


    public function seatStatusVersion($version = 0)
    {
        $parray = [];
        $rs = WaitlistAvailabilityProcessLog::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->get();
        foreach($rs as $key=>$value)
        {
            if(!isset($parray[$value->program_id]))
            {
                $parray[$value->program_id] = [];
            }
            array_push($parray[$value->program_id], $value->grade);
        }

        $version_data = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->first();
        

        $ids = array('"PreK"', '"K"', '"1"', '"2"', '"3"', '"4"', '"5"', '"6"', '"7"', '"8"', '"9"', '"10"', '"11"', '"12"');
        $district_id = Session::get("district_id");
        $submissions = Submissions::where("enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')
            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade']);


        $choices = ['first_choice_program_id', 'second_choice_program_id'];
        $prgCount = array();;
        if (isset($submissions)) {
            foreach ($choices as $choice) {
                foreach ($submissions as $key => $value) {
                    if($value->$choice != 0)
                    {
                        if (!isset($programs[$value->$choice]) && in_array($value->$choice, array_keys($parray))) {
                            $programs[$value->$choice] = [];
                        }
                        if (isset($programs[$value->$choice]) && !in_array($value->next_grade, $programs[$value->$choice])) {
                            if(in_array($value->next_grade, $parray[$value->$choice]))
                            {
                                array_push($programs[$value->$choice], $value->next_grade);
                            }
                        }
                    }
                }
            }
        }

        ksort($programs);
        $final_data = array();
        foreach($programs as $key=>$value)
        {
            foreach($value as $ikey=>$ivalue)
            {
                $tmp = array();
                $seat_data = WaitlistAvailabilityProcessLog::where("program_id", $key)->where("grade", $ivalue)->where("version", $version)->first();
                if(!empty($seat_data))
                {
                    $tmp['total_applicants'] = $seat_data->waitlist_count;
                    $tmp['total_seats'] = $seat_data->total_capacity+$seat_data->withdrawn_seats-$seat_data->offered_count;
                }
                else
                {
                    $rs = Availability::where("program_id", $key)->where("grade", $ivalue)->select("available_seats")->first();
                    $tmp['total_seats'] = $rs->available_seats;
                    $tmp['total_applicants'] = Submissions::where("enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where(function($query) use ($key){
                        $query->where('first_choice_program_id', $key);
                        $query->orWhere('second_choice_program_id', $key);
                    })->where('next_grade', $ivalue)->get()->count();

                }
                
                $tmp['program_name'] = getProgramName($key) ." - Grade ".$ivalue;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                                  ->where("first_choice_program_id", $key)
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                                  ->where("second_choice_program_id", $key)
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $tmp['offered'] = $rs1 + $rs2;


                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Denied due to Ineligibility")
                                  ->where("first_choice_program_id", $key)
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Denied due to Ineligibility")
                                  ->where("second_choice_program_id", $key)
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $tmp['noteligible'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Denied due To Incomplete Records")
                                  ->where("first_choice_program_id", $key)
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Denied due To Incomplete Records")
                                  ->where("second_choice_program_id", $key)
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $tmp['Incomplete'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                                  ->where("first_choice_program_id", $key)
                                  ->where("first_offer_status", 'Declined')
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                                  ->where("second_choice_program_id", $key)
                                  ->where("second_offer_status", 'Declined')
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $tmp['Decline'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                                  ->where("first_choice_program_id", $key)
                                  ->where("first_offer_status", 'Declined & Waitlisted')
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                                  ->where("second_choice_program_id", $key)
                                  ->where("second_offer_status", 'Declined & Waitlisted')
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $tmp['Waitlisted'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                                  ->where("first_choice_program_id", $key)
                                  ->where("first_offer_status", 'Accepted')
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                                  ->where("second_choice_program_id", $key)
                                  ->where("second_offer_status", 'Accepted')
                                  ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                            ->where('next_grade',$ivalue)->where("submissions_waitlist_final_status.version", $version)
                            ->get()->count();
                $tmp['Accepted'] = $rs1 + $rs2;

                $tmp['remaining'] = $tmp['total_seats'] - $tmp['Accepted'];// -  $tmp['Decline'];
                $final_data[] = $tmp;

            }

        }
        



        //print_r($final_data);exit;
        return view("Waitlist::seats_status",compact("final_data", "version_data"));
    }

    public function submissions_results($form_id=1)
    {
        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $version = $rs + 1;

        $pid = $form_id;
        $from = "form";
        $programs = [];
        $parray = [];
        $district_id = \Session('district_id');
        $display_outcome = $this->checkWailistOpen();


        $rs = WaitlistAvailability::get();
        if(count($rs) > 0)
        {
            foreach($rs as $key=>$value)
            {
                if(!isset($parray[$value->program_id]))
                {
                    $parray[$value->program_id] = [];
                }
                array_push($parray[$value->program_id], $value->grade);
            }
        }
        else
        {
            $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("last_date_online", ">", date("Y-m-d H:i:s"))->first();
            if(!empty($rs))
            {
                $version = $rs->version;
                $rs = WaitlistAvailabilityProcessLog::where("version", $version)->get();
                foreach($rs as $key=>$value)
                {
                    if(!isset($parray[$value->program_id]))
                    {
                        $parray[$value->program_id] = [];
                    }
                    array_push($parray[$value->program_id], $value->grade);
                }

            }
        }

      //  echo $form_id;exit;


        $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))
            ->where('district_id', $district_id)
            ->where("form_id", $form_id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->where("submissions_waitlist_final_status.version", $version)
            ->get(['submissions.id', 'first_name', 'last_name', 'current_school', 'first_offered_rank', 'second_offered_rank', 'first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status']);


        $final_data = array();
        foreach($submissions as $key=>$value)
        {
                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['name'] = $value->first_name. " ". $value->last_name;
                $tmp['grade'] = $value->next_grade;
                $tmp['school'] = $value->current_school;
                $tmp['choice'] = 1;
                $tmp['race'] = $value->race;
                $tmp['program'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['offered_status'] = $value->first_choice_final_status;
                if($value->first_choice_final_status == "Offered")
                    $tmp['outcome'] = "<div class='alert1 alert-success text-center'>Offered</div>";
                elseif($value->first_choice_final_status == "Denied due to Ineligibility")
                    $tmp['outcome'] = "<div class='alert1 alert-info text-center'>Denied due to Ineligibility</div>";
                elseif($value->first_choice_final_status == "Waitlisted")
                    $tmp['outcome'] = "<div class='alert1 alert-warning text-center'>Waitlist</div>";
                elseif($value->first_choice_final_status == "Denied due to Incomplete Records")
                    $tmp['outcome'] = "<div class='alert1 alert-danger text-center'>Denied due to Incomplete Records</div>";
                else
                    $tmp['outcome'] = "";

                if($value->first_choice_final_status != "Pending")
                {
                    if(in_array($value->first_choice_program_id, array_keys($parray)))
                    {
                        if(in_array($value->next_grade, $parray[$value->first_choice_program_id]))
                        {
                            $final_data[] = $tmp;
                        }
                    }

                    
                }

                if($value->second_choice_program_id != 0)
                {
                    $tmp = array();
                    $tmp['id'] = $value->id;
                    $tmp['name'] = $value->first_name. " ". $value->last_name;
                    $tmp['grade'] = $value->next_grade;
                    $tmp['school'] = $value->current_school;
                    $tmp['race'] = $value->race;
                    $tmp['choice'] = 2;
                    $tmp['program'] = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;
                    $tmp['program_name'] = getProgramName($value->second_choice_program_id);
                    $tmp['offered_status'] = $value->second_choice_final_status;

                    if($value->second_choice_final_status == "Offered")
                        $tmp['outcome'] = "<div class='alert1 alert-success text-center'>Offered</div>";
                    elseif($value->second_choice_final_status == "Denied due to Ineligibility")
                        $tmp['outcome'] = "<div class='alert1 alert-info text-center'>Denied due to Ineligibility</div>";
                    elseif($value->second_choice_final_status == "Waitlisted")
                        $tmp['outcome'] = "<div class='alert1 alert-warning text-center'>Waitlist</div>";
                    elseif($value->second_choice_final_status == "Denied due to Incomplete Records")
                        $tmp['outcome'] = "<div class='alert1 alert-danger text-center'>Denied due to Incomplete Records</div>";
                    else
                        $tmp['outcome'] = "";
                    if($value->second_choice_final_status != "Pending")
                    {
                        if(in_array($value->second_choice_program_id, array_keys($parray)))
                        {
                            if(in_array($value->next_grade, $parray[$value->second_choice_program_id]))
                            {
                                $final_data[] = $tmp;
                            }
                        }
                    }


                    //$final_data[] = $tmp;
                }

        }
        $grade = $outcome = array();
        foreach($final_data as $key=>$value)
        {
            $grade['grade'][] = $value['grade']; 
            $outcome['outcome'][] = $value['outcome']; 
        }
        array_multisort($grade['grade'], SORT_ASC, $outcome['outcome'], SORT_DESC, $final_data);

        return view("Waitlist::submissions_result", compact('final_data', 'pid', 'from', 'display_outcome', 'form_id'));

    }

    public function submissions_results_version($version=0)
    {
        $form_id = 1;
        $pid = $form_id;
        $from = "form";
        $programs = [];
        $district_id = \Session('district_id');

        $version_data = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->first();

        $parray = array();
        $rs = WaitlistAvailabilityProcessLog::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->where("type", "Waitlist")->get();
        foreach($rs as $key=>$value)
        {
            if(!isset($parray[$value->program_id]))
            {
                $parray[$value->program_id] = [];
            }
            array_push($parray[$value->program_id], $value->grade);
        }

        $form_id = 1;
        $display_outcome = $this->checkWailistOpen();
        $data_ary = [];
        $race_ary = [];
        $pid = $form_id;
        $from = "form";

        $final_data = [];


        $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))
            ->where('district_id', $district_id)
            ->where("form_id", $form_id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->where("submissions_waitlist_final_status.version", $version)
            ->get(['submissions.id', 'first_name', 'last_name', 'current_school', 'first_offered_rank', 'second_offered_rank', 'first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status']);

        $final_data = array();
        foreach($submissions as $key=>$value)
        {
                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['name'] = $value->first_name. " ". $value->last_name;
                $tmp['grade'] = $value->next_grade;
                $tmp['school'] = $value->current_school;
                $tmp['choice'] = 1;
                $tmp['race'] = $value->race;
                $tmp['program'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['offered_status'] = $value->first_choice_final_status;
                if($value->first_choice_final_status == "Offered")
                    $tmp['outcome'] = "<div class='alert1 alert-success text-center'>Offered</div>";
                elseif($value->first_choice_final_status == "Denied due to Ineligibility")
                    $tmp['outcome'] = "<div class='alert1 alert-info text-center'>Denied due to Ineligibility</div>";
                elseif($value->first_choice_final_status == "Waitlisted")
                    $tmp['outcome'] = "<div class='alert1 alert-warning text-center'>Waitlist</div>";
                elseif($value->first_choice_final_status == "Denied due to Incomplete Records")
                    $tmp['outcome'] = "<div class='alert1 alert-danger text-center'>Denied due to Incomplete Records</div>";
                else
                    $tmp['outcome'] = "";

                if($value->first_choice_final_status != "Pending")
                {
                    if(in_array($value->first_choice_program_id, array_keys($parray)))
                    {
                        if(in_array($value->next_grade, $parray[$value->first_choice_program_id]))
                        {
                            $final_data[] = $tmp;
                        }
                    }

                    
                }

                if($value->second_choice_program_id != 0)
                {
                    $tmp = array();
                    $tmp['id'] = $value->id;
                    $tmp['name'] = $value->first_name. " ". $value->last_name;
                    $tmp['grade'] = $value->next_grade;
                    $tmp['school'] = $value->current_school;
                    $tmp['race'] = $value->race;
                    $tmp['choice'] = 2;
                    $tmp['program'] = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;
                    $tmp['program_name'] = getProgramName($value->second_choice_program_id);
                    $tmp['offered_status'] = $value->second_choice_final_status;

                    if($value->second_choice_final_status == "Offered")
                        $tmp['outcome'] = "<div class='alert1 alert-success text-center'>Offered</div>";
                    elseif($value->second_choice_final_status == "Denied due to Ineligibility")
                        $tmp['outcome'] = "<div class='alert1 alert-info text-center'>Denied due to Ineligibility</div>";
                    elseif($value->second_choice_final_status == "Waitlisted")
                        $tmp['outcome'] = "<div class='alert1 alert-warning text-center'>Waitlist</div>";
                    elseif($value->second_choice_final_status == "Denied due to Incomplete Records")
                        $tmp['outcome'] = "<div class='alert1 alert-danger text-center'>Denied due to Incomplete Records</div>";
                    else
                        $tmp['outcome'] = "";
                    if($value->second_choice_final_status != "Pending")
                    {
                        if(in_array($value->second_choice_program_id, array_keys($parray)))
                        {
                            if(in_array($value->next_grade, $parray[$value->second_choice_program_id]))
                            {
                                $final_data[] = $tmp;
                            }
                        }
                    }


                    //$final_data[] = $tmp;
                }

        }

        $grade = $outcome = array();
        foreach($final_data as $key=>$value)
        {
            $grade['grade'][] = $value['grade']; 
            $outcome['outcome'][] = $value['outcome']; 
        }
        array_multisort($grade['grade'], SORT_ASC, $outcome['outcome'], SORT_DESC, $final_data);

        return view("Waitlist::submissions_result_report", compact('final_data', 'pid', 'from', 'display_outcome', 'form_id', "version_data", "version"));
    }

    public function store(Request $request)
    {
        $display_outcome = $this->checkWailistOpen();

        if($display_outcome == 0)
        {
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
        $data['last_date_waitlist_online_acceptance'] = $req['last_date_waitlist_online_acceptance'];
        $data['last_date_waitlist_offline_acceptance'] = $req['last_date_waitlist_offline_acceptance'];
        $data['district_id'] = Session::get("district_id");

        $rs = DistrictConfiguration::updateOrCreate(["name" => "last_date_waitlist_online_acceptance", "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")], ["name"=>"last_date_waitlist_online_acceptance", "value"=> $data['last_date_waitlist_online_acceptance'], "district_id"=>Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")]);

        $rs = DistrictConfiguration::updateOrCreate(["name" => "last_date_waitlist_offline_acceptance", "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")], ["name"=>"last_date_waitlist_offline_acceptance", "value"=> $data['last_date_waitlist_offline_acceptance'], "district_id"=>Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")]);

       /* if($req['form_field'] != "")
        {
            return redirect("/admin/Process/Selection/Population/Form/".$req['form_field']);
        }
        else
        {
            return redirect("/admin/Process/Selection/Population/".$req['programs_select']);
        }*/

        app('App\Modules\Reports\Controllers\ReportsController')->generateStatus();
        echo "done";

    }


    public function storeAllAvailability(Request $request)
    {
        $req = $request->all();
        WaitlistAvailability::truncate();
        WaitlistIndividualAvailability::truncate();

        if($req['save_type'] == '')
        {
            $data = array();
            $data['last_date_waitlist_online_acceptance'] = $req['last_date_waitlist_online_acceptance'];
            $data['last_date_waitlist_offline_acceptance'] = $req['last_date_waitlist_offline_acceptance'];
            $data['district_id'] = Session::get("district_id");

            $rs = DistrictConfiguration::updateOrCreate(["name" => "last_date_waitlist_online_acceptance", "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")], ["name"=>"last_date_waitlist_online_acceptance", "value"=> $data['last_date_waitlist_online_acceptance'], "district_id"=>Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")]);

            $rs = DistrictConfiguration::updateOrCreate(["name" => "last_date_waitlist_offline_acceptance", "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")], ["name"=>"last_date_waitlist_offline_acceptance", "value"=> $data['last_date_waitlist_offline_acceptance'], "district_id"=>Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")]);

        }

        foreach($req as $key=>$value)
        {
            if(!in_array($key, array("_token", "last_date_waitlist_online_acceptance", "last_date_waitlist_offline_acceptance","save_type")))
            {
                //if($value != "0")
                //{
                    $tmp = str_replace("WS-", "", $key);
                    $tmp1 = explode("-", $tmp);
                    $data = array();
                    $data['program_id'] = $tmp1[0];
                    $data['district_id'] = Session::get("district_id");
                    $data['grade'] = $tmp1[1];
                    $data['withdrawn_seats'] = $value;
                    $data["year"] = "2021-22";
                    WaitlistAvailability::create($data);
               // }

            }
        }
        if($req['save_type'] == '')
        {
            app('App\Modules\Reports\Controllers\ReportsController')->generateWaitlistStatus();
        }
        echo "Done";
    }

// Pending Here

    public function saveIndividualAvailability($program_id, $grade, $seats)
    {
            $data = array();
            $district_id = Session::get("district_id");
            $form_id = 1;
            $data['program_id'] = $program_id;
            $data['district_id'] = Session::get("district_id");
            $data['grade'] = $grade;
            $data['withdrawn_seats'] = $seats;

            $data["year"] = "2021-22";
            WaitlistIndividualAvailability::updateOrCreate(["program_id" => $program_id, "grade" => $grade], $data);

            $rsData = WaitlistIndividualAvailability::get();
            $pid = $grade = $ids = $programs = array();
            foreach($rsData as $key=>$value)
            {
                if (!isset($programs[$value->program_id])) {
                    $programs[$value->program_id] = [];
                }
                if (!in_array($value->grade, $programs[$value->program_id])) {
                    array_push($programs[$value->program_id], $value->grade);
                }
                $grade[] = $value->grade;
                $pid[] = $value->program_id;
                $ids[] = '"'.$value->grade.'"';
            }
            $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("last_date_online", ">", date("Y-m-d H:i:s"))->first();

            $last_type = $this->check_last_process();
                if($last_type == "waitlist")
                {
                    $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first();
                    $version = $rs->version;
                    $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')->where("submissions_waitlist_final_status.version", $version)
                            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
                }
                elseif($last_type == "late_submission")
                {
                    $rs = LateSubmissionProcessLogs::orderBy("created_at", "DESC")->first();
                    $version = $rs->version;
                    $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')->where("late_submissions_final_status.version", $version)
                            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
                }
                else
                {
                    $version = 0;
                    $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))->where('form_id', $form_id)->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')
                        ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
                }
                $ws = WaitlistIndividualAvailability::where("program_id", $pid)->where("grade", $grade)->first();
                if(!empty($ws))
                    $p_withdrawn_seats = $ws->withdrawn_seats;
                else
                    $p_withdrawn_seats = 0;         

                $ws = lateSubmissionIndividualAvailability::where("program_id", $pid)->where("grade", $grade)->first();
                if(!empty($ws))
                    $p_withdrawn_seats += $ws->withdrawn_seats;

                $disabled = 0;
                $display_outcome = 0;
                $choices = ['first_choice_program_id', 'second_choice_program_id'];
     
     /*           $programs = [];
                 if (isset($submissions)) {
                    foreach ($choices as $choice) {
                        foreach ($submissions as $key => $value) {
                            if(in_array($value->$choice, $pid) && in_array($value->next_grade, $ids))
                            {
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
*/

                if(!empty($programs))
                {
                     ksort($programs);

                    foreach ($programs as $program_id => $grades) { // 2
                        foreach ($grades as $grade) {

                            $rsP = WaitlistAvailabilityProcessLog::where("program_id", $program_id)->where("grade", $grade)->sum("withdrawn_seats");
                            $additional = $rsP;

                            /*$rsP = LateSubmissionAvailabilityProcessLog::where("program_id", $program_id)->where("grade", $grade)->sum("withdrawn_seats");
                            $additional += $rsP;*/

                            $availability = Availability::where('program_id', $program_id)
                                ->where('grade', $grade)->first(['total_seats', 'available_seats']);



                    $waitlist_count = $this->get_waitlist_count($last_type, $program_id, $grade, $district_id, $form_id);
                    $rsI = WaitlistIndividualAvailability::where("program_id", $program_id)->where("grade", $grade)->first();
                    if(!empty($rsI))
                    {
                        $p_withdrawn_seats = $rsI->withdrawn_seats;
                    }
                    else
                    {
                        $p_withdrawn_seats = 0;
                    }

                            
                            $total_seats = ($availability->available_seats ?? 0) + $additional;
                                $data = [
                                    'program_id' => $program_id,
                                    'grade' => $grade,
                                    'total_seats' => $availability->total_seats ?? 0,
                                    'available_seats' => $total_seats,
                                    'offer_count' => $this->get_offer_count($program_id, $grade, $district_id, $form_id),
                                    'withdrawn_seats' => $p_withdrawn_seats,
                                    'waitlist_count' => $waitlist_count
                                ];
                                $data_ary[] = $data;
                                    // sorting race in ascending
                                ksort($data_ary);
                        }
                    } //2

                }
                else
                {
                    $data_ary = [];
                }
            //$data_ary = WaitlistAvailability::get();
            $returnHTML =  view("Waitlist::Template.individual_programs_queue",compact("data_ary"))->render();
            return response()->json(array('html'=>$returnHTML));         

            //echo "Done";exit;
    }

    public function storeIndividualAvailability(Request $request)
    {
//        return $request;
        $req = $request->all();


        if(isset($request->value_save))
        {
            echo "1";exit;
        }
        else
        {
            foreach($req as $key=>$value)
            {
                if(!in_array($key, array("_token", "last_date_waitlist_online_acceptance", "last_date_waitlist_offline_acceptance", "program_id")))
                {
                    //if($value != "0")
                    //{
                        $tmp = str_replace("WS-", "", $key);
                        $tmp1 = explode("-", $tmp);
                        $data = array();
                        $data['program_id'] = $tmp1[0];
                        $data['district_id'] = Session::get("district_id");
                        $data['grade'] = $tmp1[1];
                        $data['withdrawn_seats'] = $value;

                        $data["year"] = "2021-22";
                        WaitlistIndividualAvailability::updateOrCreate(["program_id" => $tmp1[0], "grade" => $tmp1[1]], $data);
                    //}

                }
            }


            $data = array();
            $data['last_date_waitlist_online_acceptance'] = $req['last_date_waitlist_online_acceptance'];
            $data['last_date_waitlist_offline_acceptance'] = $req['last_date_waitlist_offline_acceptance'];

            $rs = DistrictConfiguration::updateOrCreate(["name" => "last_date_waitlist_online_acceptance", "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")], ["name"=>"last_date_waitlist_online_acceptance", "value"=> $data['last_date_waitlist_online_acceptance'], "district_id"=>Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")]);

            $rs = DistrictConfiguration::updateOrCreate(["name" => "last_date_waitlist_offline_acceptance", "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")], ["name"=>"last_date_waitlist_offline_acceptance", "value"=> $data['last_date_waitlist_offline_acceptance'], "district_id"=>Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id")]);

            $data['district_id'] = Session::get("district_id");

            WaitlistAvailability::truncate();

            $rs = WaitlistIndividualAvailability::get();
            foreach($rs as $key=>$value)
            {
                $data = array();
                $data['program_id'] = $value->program_id;
                $data['district_id'] = Session::get("district_id");
                $data['grade'] = $value->grade;
                $data['withdrawn_seats'] = $value->withdrawn_seats;
                $data['last_date_waitlist_online_acceptance'] = date("Y-m-d H:i:s", strtotime($req['last_date_waitlist_online_acceptance']));
                $data['last_date_waitlist_offline_acceptance'] = date("Y-m-d H:i:s", strtotime($req['last_date_waitlist_offline_acceptance']));
                $data["year"] = "2021-22";
                WaitlistAvailability::updateOrCreate(["program_id" => $value->program_id, "grade" => $value->grade], $data);

               /* $ptid = $value->program_id;
                $submissions = Submissions::where("district_id", Session::get("district_id"))->where(function ($q) use ($ptid) {
                                $q->where(function ($q1)  use ($ptid) {
                                    $q1->where('first_choice_program_id', $ptid)->where('second_choice_program_id', '<>', 0);
                                })->orWhere(function ($q1)  use ($ptid) {
                                    $q1->where('second_choice_program_id', $ptid)->where('first_choice_program_id', '<>', 0);
                                });
                            })->where("next_grade", $value->grade)
                                ->get();
                    foreach($submissions as $k=>$v)
                    {
                        if($v->first_choice_program_id != 0 && $v->second_choice_program_id != 0)
                        {
                            if($v->first_choice_program_id != $value->program_id)
                            {
                                $data = array();
                                $data['program_id'] = $v->first_choice_program_id;
                                $data['district_id'] = Session::get("district_id");
                                $data['grade'] = $value->grade;
                                $data['withdrawn_seats'] = $value->withdrawn_seats;
                                $data['last_date_waitlist_online_acceptance'] = date("Y-m-d H:i:s", strtotime($req['last_date_waitlist_online_acceptance']));
                                $data['last_date_waitlist_offline_acceptance'] = date("Y-m-d H:i:s", strtotime($req['last_date_waitlist_offline_acceptance']));
                                $data["year"] = "2021-22";
                                WaitlistAvailability::updateOrCreate(["program_id" => $v->first_choice_program_id, "grade" => $value->grade], $data);
                            }
                            else
                            {
                                $data = array();
                                $data['program_id'] = $v->second_choice_program_id;
                                $data['district_id'] = Session::get("district_id");
                                $data['grade'] = $value->grade;
                                $data['withdrawn_seats'] = $value->withdrawn_seats;
                                $data['last_date_waitlist_online_acceptance'] = date("Y-m-d H:i:s", strtotime($req['last_date_waitlist_online_acceptance']));
                                $data['last_date_waitlist_offline_acceptance'] = date("Y-m-d H:i:s", strtotime($req['last_date_waitlist_offline_acceptance']));
                                $data["year"] = "2021-22";
                                WaitlistAvailability::updateOrCreate(["program_id" => $v->second_choice_program_id, "grade" => $value->grade], $data);
                            }


                        }
                    }
                    */
                
            }

            WaitlistIndividualAvailability::truncate();


            app('App\Modules\Reports\Controllers\ReportsController')->generateWaitlistIndividualStatus();
            echo "Done";
        }
    }

    public function selection_accept(Request $request)
    {

        $form_id = 1;
        $district_id = \Session('district_id');

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_online_acceptance")->first();
        if(!empty($tmp))
            $last_date_waitlist_online_acceptance = $tmp->value;
        else
            $last_date_waitlist_online_acceptance = date('m/d/Y H:i', strtotime('+1 day'));

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_offline_acceptance")->first();
        if(!empty($tmp))
            $last_date_waitlist_offline_acceptance = $tmp->value;
        else
            $last_date_waitlist_offline_acceptance = date('m/d/Y H:i', strtotime('+1 day'));

        $last_type = $this->check_last_process();

        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $version = $rs + 1;

        


        $programs = [];

        $ids = array('"PreK"', '"K"', '"1"', '"2"', '"3"', '"4"', '"5"', '"6"', '"7"', '"8"', '"9"', '"10"', '"11"', '"12"');
        $ids_ordered = implode(',', $ids);

        $rawOrder = DB::raw(sprintf('FIELD(submissions.next_grade, %s)', "'".implode(',', $ids)."'"));

            
$submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')->where("submissions_waitlist_final_status.version", $version)
                        ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);

        $choices = ['first_choice_program_id', 'second_choice_program_id'];
        if (isset($submissions)) {
            foreach ($choices as $choice) {
                foreach ($submissions as $key => $value) {
                    if($value->$choice != 0)
                    {
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

        $count = 0;

        foreach ($programs as $program_id => $grades) {
            foreach ($grades as $grade) 
            {

                $rsP = WaitlistAvailabilityLog::where("program_id", $program_id)->where("grade", $grade)->first();
                if(!empty($rsP))
                {
                    $additional = $rsP->withdrawn_seats;
                }
                else
                {
                    $additional = 0;
                }

                $rsP = LateSubmissionAvailabilityLog::where("program_id", $program_id)->where("grade", $grade)->first();
                if(!empty($rsP))
                {
                    $additional += $rsP->withdrawn_seats;
                }

                $availability = Availability::where('program_id', $program_id)
                    ->where('grade', $grade)->first(['total_seats', 'available_seats']);

                $total_seats = $availability->available_seats + $additional;
                $offer_count = $this->get_offer_count($program_id, $grade, $district_id, $form_id);
                $waitlist_count = $this->get_waitlist_count($last_type, $program_id, $grade, $district_id, $form_id);


                $rs = WaitlistAvailability::where("program_id", $program_id)->where("grade", $grade)->first();
                if(!empty($rs))
                {
                    $withdrawn_seats = $rs->withdrawn_seats;
                }
                else
                {
                    $withdrawn_seats = 0;
                }

                $data = array();
                $data["program_id"] = $program_id;
                $data['enrollment_id'] = Session::get("enrollment_id");
                $data["grade"] = $grade;
                $data["waitlist_count"] = $waitlist_count;
                $data["withdrawn_seats"] = $withdrawn_seats;
                $data["offered_count"] = $offer_count;
                $data["total_capacity"] = $total_seats;
                $data["version"] = $version;
                $data['type'] = "Waitlist";
                WaitlistAvailabilityProcessLog::create($data);    
            }
        }


        

        $tmp = array();
        $tmp['version'] = $version;

        $rs = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_online_acceptance")->first();
        if(!empty($tmp))
            $last_date_waitlist_online_acceptance = date("Y-m-d H:i:s", strtotime($rs->value));
        else
            $last_date_waitlist_online_acceptance = date('Y-m-d H:i:s', strtotime('+1 day'));

        $tmp['last_date_online'] = $last_date_waitlist_online_acceptance;
        $tmp['enrollment_id'] = Session::get("enrollment_id");
        $tmp['generated_by'] = Auth::user()->id;
        //$tmp['enrollment_id'] = Session::get("enrollment_id");
        $tmp['application_id'] = 62;
        $tmp['form_id'] = 1;
        WaitlistProcessLogs::create($tmp);

        $rs = WaitlistAvailability::get();
        foreach($rs as $key=>$value)
        {
            $grade = $value->grade;
            $program_id = $value->program_id;

            $rs1 = WaitlistAvailabilityLog::where("program_id", $program_id)->where("grade", $grade)->first();
            if(!empty($rs1))
            {
                $withdrawn_seats = $rs1->withdrawn_seats + $value->withdrawn_seats;
            }
            else
            {
                $withdrawn_seats = $value->withdrawn_seats;
            }
            $t = WaitlistAvailabilityLog::updateOrCreate(["program_id" => $program_id, "grade" => $grade], array("withdrawn_seats"=>$withdrawn_seats, "program_id" => $program_id, "grade" => $grade, "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id"), "year" => "2022-23", "user_id" => Auth::user()->id));


        }

        WaitlistAvailability::truncate();

        $data = SubmissionsWaitlistFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->get();
        foreach($data as $key=>$value)
        {

           /* $updData = array();
            $updData['first_choice_final_status'] = $value->first_choice_final_status;
            $updData['second_choice_final_status'] = $value->second_choice_final_status;
            $updData['first_waitlist_number'] = $value->first_waitlist_number;
            $updData['second_waitlist_number'] = $value->second_waitlist_number;
            $updData['first_offered_rank'] = $value->first_offered_rank;
            $updData['second_offered_rank'] = $value->second_offered_rank;
            $updData['first_waitlist_for'] = $value->first_waitlist_for;
            $updData['second_waitlist_for'] = $value->second_waitlist_for;
            $updData['offer_slug'] = $value->offer_slug;
            $updData['first_offer_status'] = $value->first_offer_status;
            $updData['second_offer_status'] = $value->second_offer_status;
            $updData['submission_id'] = $value->submission_id;
            $updData['version'] = $value->version;
            $t = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value->submission_id], $updData);
            */


            $status = $value->first_choice_final_status;
            if($value->second_choice_final_status == "Offered")
                $status = "Offered";

            if($value->first_choice_final_status == "Pending")
                $status = $value->second_choice_final_status;

            $submission_id = $value->submission_id;
            $rs = Submissions::where("id", $submission_id)->select("submission_status")->first();
            $old_status = $rs->submission_status;

            $comment = "By Accept and Commit Event";
            if($status == "Offered")
            {
                $submission = Submissions::where("id", $value->submission_id)->first();
                if($value->first_choice_final_status == "Offered")
                {
                    $program_name = getProgramName($submission->first_choice_program_id);
                }
                else if($value->second_choice_final_status == "Offered")
                {
                    $program_name = getProgramName($submission->second_choice_program_id);
                }
                else
                {
                    $program_name = "";
                }

                $program_name .= " - Grade ".$submission->next_grade;
                $comment = "System has Offered ".$program_name." to Parent";
            }
            else if($status == "Denied due to Ineligibility")
            {
                if($value->first_choice_eligibility_reason != '')
                {
                    if($value->first_choice_eligibility_reason == "Both")
                    {
                        $comment = "System has denied the application because of Grades and CDI Ineligibility";
                    }
                    else if($value->first_choice_eligibility_reason == "Grade")
                    {
                        $comment = "System has denied the application because of Grades Ineligibility";
                    }
                    else
                    {
                        $comment = "System has denied the application because of CDI Ineligibility";   
                    }
                }
            }
            else if($status == "Denied due to Incomplete Records")
            {
                if($value->incomplete_reason != '')
                {
                    if($value->incomplete_reason == "Both")
                    {
                       $comment = "System has denied the application because of Grades and CDI Ineligibility";
                    }
                    else if($value->incomplete_reason == "Grade")
                    {
                        $comment = "System has denied the application because of Incomplete Grades";
                    }
                    else
                    {
                        $comment = "System has denied the application because of Incomplete CDI";   
                    }
                }
            }
            $rs = SubmissionsStatusLog::create(array("submission_id"=>$submission_id, "new_status"=>$status, "old_status"=>$old_status, "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
            $rs = SubmissionsWaitlistStatusUniqueLog::updateOrCreate(["submission_id" => $submission_id], array("submission_id"=>$submission_id, "new_status"=>$status, "old_status"=>$old_status, "updated_by"=>Auth::user()->id, "version"=>$version));
            $rs = Submissions::where("id", $submission_id)->update(["submission_status" => $status]);
        }
        echo "Done";
        exit;
    }


    public function selection_accept_1()
    {

        $form_id = 1;
        $district_id = \Session('district_id');

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_online_acceptance")->first();
        if(!empty($tmp))
            $last_date_waitlist_online_acceptance = $tmp->value;
        else
            $last_date_waitlist_online_acceptance = date('m/d/Y H:i', strtotime('+1 day'));

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_offline_acceptance")->first();
        if(!empty($tmp))
            $last_date_waitlist_offline_acceptance = $tmp->value;
        else
            $last_date_waitlist_offline_acceptance = date('m/d/Y H:i', strtotime('+1 day'));

        $last_type = $this->check_last_process();

        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $version = $rs + 1;



        $programs = [];

        $ids = array('"PreK"', '"K"', '"1"', '"2"', '"3"', '"4"', '"5"', '"6"', '"7"', '"8"', '"9"', '"10"', '"11"', '"12"');
        $ids_ordered = implode(',', $ids);

        $rawOrder = DB::raw(sprintf('FIELD(submissions.next_grade, %s)', "'".implode(',', $ids)."'"));

            if($last_type == "waitlist")
            {
                $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first();
                $version = $rs->version;
                $submissions = Submissions::where("submission.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')->where("submissions_waitlist_final_status.version", $version)
                        ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
            }
            elseif($last_type == "late_submission")
            {
                $rs = LateSubmissionProcessLogs::orderBy("created_at", "DESC")->first();
                $version = $rs->version;
                $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("form_id", $form_id)->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')->where("late_submissions_final_status.version", $version)
                        ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
            }
            else
            {
                $version = 1;
                $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->orderByRaw('FIELD(next_grade,'.implode(",",$ids).')')
                    ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'first_waitlist_for', 'second_waitlist_for']);
            }


        $choices = ['first_choice_program_id', 'second_choice_program_id'];
        if (isset($submissions)) {
            foreach ($choices as $choice) {
                foreach ($submissions as $key => $value) {
                    if($value->$choice != 0)
                    {
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

        $count = 0;

        foreach ($programs as $program_id => $grades) {
            foreach ($grades as $grade) 
            {

                $rsP = WaitlistAvailabilityLog::where("program_id", $program_id)->where("grade", $grade)->first();
                if(!empty($rsP))
                {
                    $additional = $rsP->withdrawn_seats;
                }
                else
                {
                    $additional = 0;
                }

                $rsP = LateSubmissionAvailabilityLog::where("program_id", $program_id)->where("grade", $grade)->first();
                if(!empty($rsP))
                {
                    $additional += $rsP->withdrawn_seats;
                }

                $availability = Availability::where('program_id', $program_id)
                    ->where('grade', $grade)->first(['total_seats', 'available_seats']);

                $total_seats = $availability->available_seats + $additional;
                $offer_count = $this->get_offer_count($program_id, $grade, $district_id, $form_id);
                $waitlist_count = $this->get_waitlist_count($last_type, $program_id, $grade, $district_id, $form_id);


                $rs = WaitlistAvailability::where("program_id", $program_id)->where("grade", $grade)->first();
                if(!empty($rs))
                {
                    $withdrawn_seats = $rs->withdrawn_seats;
                }
                else
                {
                    $withdrawn_seats = 0;
                }

                $data = array();
                $data["program_id"] = $program_id;
                $data['enrollment_id'] = Session::get("enrollment_id");
                $data["grade"] = $grade;
                $data["waitlist_count"] = $waitlist_count;
                $data["withdrawn_seats"] = $withdrawn_seats;
                $data["offered_count"] = $offer_count;
                $data["total_capacity"] = $total_seats;
                $data["version"] = $version;
                $data['type'] = "Waitlist";
                WaitlistAvailabilityProcessLog::create($data);    
            }
        }


        

        $tmp = array();
        $tmp['version'] = $version;

        $rs = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_online_acceptance")->first();
        if(!empty($tmp))
            $last_date_waitlist_online_acceptance = date("Y-m-d H:i:s", strtotime($rs->value));
        else
            $last_date_waitlist_online_acceptance = date('Y-m-d H:i:s', strtotime('+1 day'));

        $tmp['last_date_online'] = $last_date_waitlist_online_acceptance;
        $tmp['enrollment_id'] = Session::get("enrollment_id");
        $tmp['generated_by'] = Auth::user()->id;
        //$tmp['enrollment_id'] = Session::get("enrollment_id");
        $tmp['application_id'] = 62;
        $tmp['form_id'] = 1;
        WaitlistProcessLogs::create($tmp);

        $rs = WaitlistAvailability::get();
        foreach($rs as $key=>$value)
        {
            $grade = $value->grade;
            $program_id = $value->program_id;

            $rs1 = WaitlistAvailabilityLog::where("program_id", $program_id)->where("grade", $grade)->first();
            if(!empty($rs1))
            {
                $withdrawn_seats = $rs1->withdrawn_seats + $value->withdrawn_seats;
            }
            else
            {
                $withdrawn_seats = $value->withdrawn_seats;
            }
            $t = WaitlistAvailabilityLog::updateOrCreate(["program_id" => $program_id, "grade" => $grade], array("withdrawn_seats"=>$withdrawn_seats, "program_id" => $program_id, "grade" => $grade, "district_id" => Session::get("district_id"), "enrollment_id" => Session::get("enrollment_id"), "year" => "2022-23", "user_id" => Auth::user()->id));


        }

        WaitlistAvailability::truncate();

        $data = SubmissionsWaitlistFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->get();
        foreach($data as $key=>$value)
        {

           /* $updData = array();
            $updData['first_choice_final_status'] = $value->first_choice_final_status;
            $updData['second_choice_final_status'] = $value->second_choice_final_status;
            $updData['first_waitlist_number'] = $value->first_waitlist_number;
            $updData['second_waitlist_number'] = $value->second_waitlist_number;
            $updData['first_offered_rank'] = $value->first_offered_rank;
            $updData['second_offered_rank'] = $value->second_offered_rank;
            $updData['first_waitlist_for'] = $value->first_waitlist_for;
            $updData['second_waitlist_for'] = $value->second_waitlist_for;
            $updData['offer_slug'] = $value->offer_slug;
            $updData['first_offer_status'] = $value->first_offer_status;
            $updData['second_offer_status'] = $value->second_offer_status;
            $updData['submission_id'] = $value->submission_id;
            $updData['version'] = $value->version;
            $t = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value->submission_id], $updData);
            */


            $status = $value->first_choice_final_status;
            if($value->second_choice_final_status == "Offered")
                $status = "Offered";

            if($value->first_choice_final_status == "Pending")
                $status = $value->second_choice_final_status;

            $submission_id = $value->submission_id;
            $rs = Submissions::where("id", $submission_id)->select("submission_status")->first();
            $old_status = $rs->submission_status;

            $comment = "By Accept and Commit Event";
            if($status == "Offered")
            {
                $submission = Submissions::where("id", $value->submission_id)->first();
                if($value->first_choice_final_status == "Offered")
                {
                    $program_name = getProgramName($submission->first_choice_program_id);
                }
                else if($value->second_choice_final_status == "Offered")
                {
                    $program_name = getProgramName($submission->second_choice_program_id);
                }
                else
                {
                    $program_name = "";
                }

                $program_name .= " - Grade ".$submission->next_grade;
                $comment = "System has Offered ".$program_name." to Parent";
            }
            else if($status == "Denied due to Ineligibility")
            {
                if($value->first_choice_eligibility_reason != '')
                {
                    if($value->first_choice_eligibility_reason == "Both")
                    {
                        $comment = "System has denied the application because of Grades and CDI Ineligibility";
                    }
                    else if($value->first_choice_eligibility_reason == "Grade")
                    {
                        $comment = "System has denied the application because of Grades Ineligibility";
                    }
                    else
                    {
                        $comment = "System has denied the application because of CDI Ineligibility";   
                    }
                }
            }
            else if($status == "Denied due to Incomplete Records")
            {
                if($value->incomplete_reason != '')
                {
                    if($value->incomplete_reason == "Both")
                    {
                       $comment = "System has denied the application because of Grades and CDI Ineligibility";
                    }
                    else if($value->incomplete_reason == "Grade")
                    {
                        $comment = "System has denied the application because of Incomplete Grades";
                    }
                    else
                    {
                        $comment = "System has denied the application because of Incomplete CDI";   
                    }
                }
            }
            $rs = SubmissionsStatusLog::create(array("submission_id"=>$submission_id, "new_status"=>$status, "old_status"=>$old_status, "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
            $rs = SubmissionsWaitlistStatusUniqueLog::updateOrCreate(["submission_id" => $submission_id], array("submission_id"=>$submission_id, "new_status"=>$status, "old_status"=>$old_status, "updated_by"=>Auth::user()->id, "version"=>$version));
            $rs = Submissions::where("id", $submission_id)->update(["submission_status" => $status]);
        }
        echo "Done";
        exit;
    }

    public function checkWailistOpen()
    {
        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("last_date_online", ">", date("Y-m-d H:i:s"))->first();
        if(!empty($rs))
            return 1;
        else
            return 0;
    }

// PENDING HERE
    public function selection_revert()
    {
        $version = $this->checkWailistOpen();
        $quotations = SubmissionsWaitlistStatusUniqueLog::where("enrollment_id", Session::get("enrollment_id"))->orderBy('created_at','ASC')->where("version", $version)
                ->get()
                ->unique('submission_id');

        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_online_acceptance")->delete();
        $tmp = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->where("name", "last_date_waitlist_offline_acceptance")->delete();


        foreach($quotations as $key=>$value)
        {
            $rs = Submissions::where("id", $value->submission_id)->update(array("submission_status"=>$value->old_status));
        }
        SubmissionsWaitlistStatusUniqueLog::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->delete();
        SubmissionsWaitlistFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->delete();
        WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->delete();
        WaitlistAvailabilityLog::truncate();
        WaitlistAvailabilityProcessLog::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->where("type", "Waitlist")->delete();
        //SubmissionsStatusUniquesLog::truncate();

    }


    public function check_last_process()
    {
        $count = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $count1 = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        if($count > 0 && $count1 > 0)
        {
            $rs = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at","desc")->first();
            $rs1 = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at","desc")->first();
            if($rs1->create_at > $rs->created_at)
                return "waitlist";
            else
                return "late_submission";
        }
        elseif($count1 > 0)
            return "waitlist";
        elseif($count > 0)
            return "late_submission";
        else
            return "regular";
    }

    public function get_offer_count($program_id, $grade, $district_id, $form_id, $version = 0)
    {
        if($version > 0)
        {
            $offer_count = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where(function ($q) use ($program_id, $grade){
                                        $q->where(function ($q1)  use ($program_id, $grade){
                                            $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $program_id)->where('next_grade', $grade);
                                        })->orWhere(function ($q1) use ($program_id, $grade){
                                            $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $program_id)->where('next_grade', $grade);
                                        });
                                    })->where("submission_status", "Offered and Accepted")->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->count();


            $offer_count1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where(function ($q) use ($program_id, $grade){
                            $q->where(function ($q1)  use ($program_id, $grade){
                                $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $program_id)->where('next_grade', $grade);
                            })->orWhere(function ($q1) use ($program_id, $grade){
                                $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $program_id)->where('next_grade', $grade);
                            });
                        })->where("submission_status", "Offered and Accepted")->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->count();

            $offer_count2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where(function ($q) use ($program_id, $grade){
                            $q->where(function ($q1)  use ($program_id, $grade){
                                $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $program_id)->where('next_grade', $grade);
                            })->orWhere(function ($q1) use ($program_id, $grade){
                                $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $program_id)->where('next_grade', $grade);
                            });
                        })->where("submission_status", "Offered and Accepted")->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->where("late_submissions_final_status.version", "<", $version-1)->count();
        }
        else
        {
            $offer_count = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where(function ($q) use ($program_id, $grade){
                            $q->where(function ($q1)  use ($program_id, $grade){
                                $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $program_id)->where('next_grade', $grade);
                            })->orWhere(function ($q1) use ($program_id, $grade){
                                $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $program_id)->where('next_grade', $grade);
                            });
                        })->where("submission_status", "Offered and Accepted")->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->count();


            $offer_count1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where(function ($q) use ($program_id, $grade){
                            $q->where(function ($q1)  use ($program_id, $grade){
                                $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $program_id)->where('next_grade', $grade);
                            })->orWhere(function ($q1) use ($program_id, $grade){
                                $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $program_id)->where('next_grade', $grade);
                            });
                        })->where("submission_status", "Offered and Accepted")->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->count();

            $offer_count2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where(function ($q) use ($program_id, $grade){
                            $q->where(function ($q1)  use ($program_id, $grade){
                                $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $program_id)->where('next_grade', $grade);
                            })->orWhere(function ($q1) use ($program_id, $grade){
                                $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $program_id)->where('next_grade', $grade);
                            });
                        })->where("submission_status", "Offered and Accepted")->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->count();  

            if($program_id == 1228 && $grade == "K")
            {
                        $offer_count1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where(function ($q) use ($program_id, $grade){
                            $q->where(function ($q1)  use ($program_id, $grade){
                                $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $program_id)->where('next_grade', $grade);
                            })->orWhere(function ($q1) use ($program_id, $grade){
                                $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $program_id)->where('next_grade', $grade);
                            });
                        })->where("submission_status", "Offered and Accepted")->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->get();


            $offer_count11 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where(function ($q) use ($program_id, $grade){
                            $q->where(function ($q1)  use ($program_id, $grade){
                                $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $program_id)->where('next_grade', $grade);
                            })->orWhere(function ($q1) use ($program_id, $grade){
                                $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $program_id)->where('next_grade', $grade);
                            });
                        })->where("submission_status", "Offered and Accepted")->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->get();

            $offer_count21 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where(function ($q) use ($program_id, $grade){
                            $q->where(function ($q1)  use ($program_id, $grade){
                                $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $program_id)->where('next_grade', $grade);
                            })->orWhere(function ($q1) use ($program_id, $grade){
                                $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $program_id)->where('next_grade', $grade);
                            });
                        })->where("submission_status", "Offered and Accepted")->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->get(); 

                        foreach($offer_count1 as $k=>$v)
                        {
                            echo $v->submission_id."<BR>";
                        }  
                        foreach($offer_count11 as $k=>$v)
                        {
                            echo $v->submission_id."<BR>";
                        }  
                        foreach($offer_count21 as $k=>$v)
                        {
                            echo $v->submission_id."<BR>";
                        }        
                        exit;                
            }


        }


        return $offer_count + $offer_count1 + $offer_count2;

    }

    public function get_waitlist_count($last_type, $program_id, $grade, $district_id, $form_id)
    {
        if($last_type == "regular")
        {
            $waitlist_count1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('first_choice_final_status', 'Waitlisted')->where('second_choice_final_status', 'Waitlisted')->where('next_grade', $grade)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->where("first_choice_program_id", $program_id)->count();

            $waitlist_count5 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('first_choice_final_status', 'Waitlisted')->where('second_choice_final_status', 'Waitlisted')->where('next_grade', $grade)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->where("second_choice_program_id", $program_id)->count();



            $waitlist_count2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('first_choice_final_status', 'Waitlisted')->where('second_choice_final_status', '<>', 'Waitlisted')->where('second_offer_status', 'Declined & Waitlisted')->where('next_grade', $grade)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->where("first_choice_program_id", $program_id)->count();

            $waitlist_count3 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('second_choice_final_status', 'Waitlisted')->where('first_choice_final_status', '<>', 'Waitlisted')->where('first_offer_status', 'Declined & Waitlisted')->where('next_grade', $grade)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->where("second_choice_program_id", $program_id)->count();


            $waitlist_count4 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('first_choice_final_status', 'Waitlisted')->where('second_choice_final_status', 'Pending')->where('next_grade', $grade)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->where("first_choice_program_id", $program_id)->count();

        }
        else
        {
            if($last_type == "late_submission")
             {
                $rsV = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first();
                $version = $rsV->version;
                $table_name = "late_submissions_final_status";
             }           
            else
            {
                $rsV = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first();
                $version = $rsV->version;
                $table_name = "submissions_waitlist_final_status";
            }

            $waitlist_count1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('first_choice_final_status', 'Waitlisted')->where('second_choice_final_status', 'Waitlisted')->where('next_grade', $grade)->join($table_name, $table_name.".submission_id", "submissions.id")->where("first_choice_program_id", $program_id)->where($table_name.".version", $version)->count();

            $waitlist_count5 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('first_choice_final_status', 'Waitlisted')->where('second_choice_final_status', 'Waitlisted')->where('next_grade', $grade)->join($table_name, $table_name.".submission_id", "submissions.id")->where("second_choice_program_id", $program_id)->where($table_name.".version", $version)->count();



            $waitlist_count2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('first_choice_final_status', 'Waitlisted')->where('second_choice_final_status', '<>', 'Waitlisted')->where('second_offer_status', 'Declined & Waitlisted')->where('next_grade', $grade)->join($table_name, $table_name.".submission_id", "submissions.id")->where("first_choice_program_id", $program_id)->where($table_name.".version", $version)->count();

            $waitlist_count3 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('second_choice_final_status', 'Waitlisted')->where('first_choice_final_status', '<>', 'Waitlisted')->where('first_offer_status', 'Declined & Waitlisted')->where('next_grade', $grade)->join($table_name, $table_name.".submission_id", "submissions.id")->where("second_choice_program_id", $program_id)->where($table_name.".version", $version)->count();


            $waitlist_count4 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where('form_id', $form_id)->where('first_choice_final_status', 'Waitlisted')->where('second_choice_final_status', 'Pending')->where('next_grade', $grade)->join($table_name, $table_name.".submission_id", "submissions.id")->where("first_choice_program_id", $program_id)->where($table_name.".version", $version)->count();
        }
        return $waitlist_count1 + $waitlist_count2 + $waitlist_count3 + $waitlist_count4 + $waitlist_count5;   

    }
}
