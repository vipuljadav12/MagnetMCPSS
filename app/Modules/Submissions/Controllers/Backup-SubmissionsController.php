<?php

namespace App\Modules\Submissions\Controllers;

use App\Modules\School\Models\School;
use App\Modules\District\Models\District;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\School\Models\Grade;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\Application\Models\ApplicationProgram;
use App\Modules\Application\Models\Application;
use App\Modules\Program\Models\Program;
use App\Modules\Submissions\Models\Submissions;
use App\Modules\Submissions\Models\{SubmissionGrade,SubmissionComment,SubmissionsStatusUniqueLog,SubmissionsFinalStatus,SubmissionsStatusLog,SubmissionGradeChange,SubmissionsWaitlistFinalStatus,SubmissionsWaitlistStatusUniqueLog,LateSubmissionsStatusUniqueLog,LateSubmissionFinalStatus,EmailActivityLog};
use App\Modules\Submissions\Models\SubmissionAudition;
use App\Modules\Submissions\Models\SubmissionWritingPrompt;
use App\Modules\Submissions\Models\SubmissionInterviewScore;
use App\Modules\Submissions\Models\SubmissionCommitteeScore;
use App\Modules\Submissions\Models\SubmissionConductDisciplinaryInfo;
use App\Modules\Submissions\Models\SubmissionStandardizedTesting;
use App\Modules\Submissions\Models\SubmissionAcademicGradeCalculation;
use App\Modules\Application\Models\ApplicationConfiguration;
use App\Modules\Eligibility\Models\SubjectManagement;
use App\Modules\ProcessSelection\Models\Availability;
use App\Modules\EditCommunication\Models\{EditCommunication,EditCommunicationLog};
use App\Modules\DistrictConfiguration\Models\DistrictConfiguration;
use App\Modules\Waitlist\Models\{WaitlistProcessLogs,WaitlistAvailabilityLog,WaitlistAvailabilityProcessLog,WaitlistIndividualAvailability,WaitlistEditCommunication};
use App\Modules\LateSubmission\Models\{LateSubmissionProcessLogs,LateSubmissionEditCommunication};
use App\StudentGrade;
use App\StudentCDI;
use Config;
use Session;
use DB;
use App\Traits\AuditTrail;
use Auth;
use Illuminate\Support\Str;


class SubmissionsController extends Controller
{
    use AuditTrail;

    public $eligibility_grade_pass = array();    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     public function __construct(){
        $this->submission = new Submissions();
    }

    public function index()
    {


        /*$today="2021-01-16 01:05:09";
        $nextday=date("Y-m-d H:i:s", strtotime("$today +4 hour"));

        $submissions = Submissions::where("id", ">", 3018)->get();
        foreach($submissions as $key=>$value)
        {
            echo $nextday."<BR>";
            $rs = Submissions::where("id", $value->id)->update(array("grade_override"=>"N", "cdi_override"=>"N"));
            $nextday=date("Y-m-d H:i:s", strtotime("$nextday +1 hour"));

        }
        exit;*/

        $programs = Auth::user()->programs;
        if($programs == "22")
        {
            $submissions=Submissions::
                join('application','application.id','submissions.application_id')
                ->join('enrollments','enrollments.id','application.enrollment_id')
                ->where('submissions.district_id', Session::get('district_id'))
                 ->where(function($query) use($programs) {
                        $query->whereRaw('FIND_IN_SET(submissions.first_choice_program_id, "'.implode(",", $programs).'")')
                               ->orWhereRaw('FIND_IN_SET(submissions.second_choice_program_id, "'.implode(",", $programs).'")');
                })
                ->select('submissions.*','enrollments.school_year')
                ->orderBy('created_at','desc')
                ->get();
        }
        else
        {
            $submissions=Submissions::
                join('application','application.id','submissions.application_id')
                ->join('enrollments','enrollments.id','application.enrollment_id')
                ->where('submissions.district_id', Session::get('district_id'))
                ->select('submissions.*','enrollments.school_year')
                ->orderBy('created_at','desc')
                ->get();
        }

        $all_data = $this->submission->getSearhData();
        return view("Submissions::index",compact('all_data'));



        //return view("Submissions::index",compact('submissions'));
    }


    public function getSubmissions(Request $request){
        $send_arr = $data_arr = array();

      /*  $offer_data = SubmissionsFinalStatus::join("submissions", "submissions.id", "submissions_final_status.submission_id")->select("submissions_final_status.*", "submissions.*")->get();

        foreach($offer_data as $key=>$value)
        {

            if($value->first_choice_final_status == "Offered")
                $awarded_school = getProgramName($value->first_waitlist_for);
            elseif($value->second_choice_final_status == "Offered")
                $awarded_school = getProgramName($value->second_waitlist_for);
            else
                $awarded_school = "";
            $ts = Submissions::where("id", $value->submission_id)->update(array("awarded_school"=>$awarded_school));
        }*/

        $submissions = $this->submission->getSubmissionList($request->all(),1);
        $total = $this->submission->getSubmissionList($request->all(),0);

        foreach ($submissions as $value) {
            
            $sub_id_edit=$sub_status=$student_id='';
            if((checkPermission(Auth::user()->role_id,'Submissions/edit') == 1)){
               $sub_id_edit = "<a href=".url('admin/Submissions/edit',$value->id)." title='edit'>".$value->id."</a>";
                $sub_id_edit .= "<div class=''> <a href=".url('admin/Submissions/edit',$value->id)." class='font-18 ml-5 mr-5' title='Edit'><i class='far fa-edit'></i></a> </div>";
            }else{
                $sub_id_edit = $value->id;
            }

            if($value->submission_status == "Active" || $value->submission_status == "Offered and Accepted"){
                $sub_status="<div class='alert1 alert-success p-10 text-center d-block'>".$value->submission_status."</div>";
            }
            elseif($value->submission_status == "Auto Decline"){
                $sub_status="<div class='alert1 alert-secondary p-10 text-center d-block'>".$value->submission_status."</div>";
            }elseif($value->submission_status == "Application Withdrawn" || $value->submission_status == "Offered and Declined" || $value->submission_status == "Denied due to Ineligibility"){
                $sub_status="<div class='alert1 alert-danger p-10 text-center d-block'>".$value->submission_status."</div>";
            }elseif($value->submission_status == "Denied due to Incomplete Records"){
                $sub_status="<div class='alert1 alert-info p-10 text-center d-block'>".$value->submission_status."</div>";
            }else{
                $sub_status="<div class='alert1 alert-warning p-10 text-center d-block'>".$value->submission_status."</div>";
            }
            if($value->student_id != ""){
                $student_id = "<div class='alert1 alert-success p-10 text-center d-block'>Current</div>";
            }else{
                $student_id = "<div class='alert1 alert-warning p-10 text-center d-block'>New</div>";
            }

            if($value->late_submission == "Y")
            {
                $late_submission = "<div class='alert1 alert-success text-center'>Yes</div>";
            }
            else
            {
                $late_submission = "<div class='alert1 alert-danger text-center'>No</div>";
            }

            $send_arr[] = [
                $sub_id_edit,
                $value->student_id,
                $value->school_year,
                $value->first_name.' '.$value->last_name,
                $value->parent_first_name.' '.$value->parent_last_name,
                $value->phone_number,
                $value->address.", ".$value->city.", ".$value->state." - ".$value->zip,
                $value->parent_email,
                $value->race,
                getDateFormat($value->birthday),
                $value->current_school,
                $value->current_grade,
                $value->next_grade,
                getProgramName($value->first_choice_program_id),
                getProgramName($value->second_choice_program_id),
                getDateTimeFormat($value->created_at),
                findSubmissionForm($value->application_id),
                $sub_status,
                $value->zoned_school,
                $student_id,
                $value->confirmation_no,
                $value->employee_id,
                $value->mcp_employee,
                $value->work_location,
                $value->employee_first_name,
                $value->employee_last_name,
                $value->awarded_school,
                $late_submission

            ];
        }

        $data_arr['recordsTotal']=$total;
        $data_arr['recordsFiltered']=$total;
        $data_arr['data']=$send_arr;
        return json_encode($data_arr);
    }

    public function testindex()
    {
        $submissions=Submissions::
            join('application','application.id','submissions.application_id')
            ->join('enrollments','enrollments.id','application.enrollment_id')
            ->where('submissions.district_id', Session::get('district_id'))
            ->select('submissions.*','enrollments.school_year')
            ->orderBy('created_at','desc')
            ->get();
        // return $submissions;
        return view("Submissions::testindex",compact('submissions'));
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
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

        $from = "ProcessSelection";
        $manually_updated = "N";
        $district = District::where("id", Session::get("district_id"))->first();
        $submission=Submissions::where('id',$id)->first();

        $display_outcome = SubmissionsStatusUniqueLog::count();

        $last_type = app('App\Modules\Waitlist\Controllers\WaitlistController')->check_last_process();

        if($last_type == "waitlist")
        {
            $offer_data = SubmissionsWaitlistFinalStatus::where("submission_id", $id)->join("submissions", "submissions.id", "submissions_waitlist_final_status.submission_id")->select("submissions_waitlist_final_status.*", "submissions.*")->orderBy("submissions_waitlist_final_status.created_at", "desc")->first();
            if(empty($offer_data))
            {
                if($submission->late_submission == "Y")
                {
                    $offer_data = LateSubmissionFinalStatus::where("submission_id", $id)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.*", "submissions.*")->orderBy("late_submissions_final_status.created_at", "desc")->first();
                    $from = "LateSubmission";
                }
                else
                {
                    $offer_data = SubmissionsFinalStatus::where("submission_id", $id)->join("submissions", "submissions.id", "submissions_final_status.submission_id")->select("submissions_final_status.*", "submissions.*")->first();
                    $from = "ProcessSelection";
                }
            }
            else
            {
                $from = "Waitlist";
            }
       }
       elseif($last_type == "late_submission")
       {
            $offer_data = LateSubmissionFinalStatus::where("submission_id", $id)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.*", "submissions.*")->orderBy("late_submissions_final_status.created_at", "desc")->first();
            if(empty($offer_data))
            {
                $offer_data = SubmissionsFinalStatus::where("submission_id", $id)->join("submissions", "submissions.id", "submissions_final_status.submission_id")->select("submissions_final_status.*", "submissions.*")->first();
                $from = "ProcessSelection";
            }
            else
            {
                $from = "LateSubmission";
            }
       }
       else
       {
            $offer_data = SubmissionsFinalStatus::where("submission_id", $id)->join("submissions", "submissions.id", "submissions_final_status.submission_id")->select("submissions_final_status.*", "submissions.*")->first();
            $from = "ProcessSelection";
       }
        /*$offer_data = SubmissionsFinalStatus::where("submission_id", $id)->join("submissions", "submissions.id", "submissions_final_status.submission_id")->select("submissions_final_status.*", "submissions.*")->first();

        $waitlist_data = SubmissionsWaitlistFinalStatus::where("submission_id", $id)->join("submissions", "submissions.id", "submissions_waitlist_final_status.submission_id")->join("process_selection", "process_selection.application_id", "submissions.application_id")->where("commited", "Yes")->select("submissions_waitlist_final_status.*", "submissions.*")->orderBy("submissions_waitlist_final_status.created_at", "desc")->first();

        $late_submission_data = LateSubmissionFinalStatus::where("submission_id", $id)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.*", "submissions.*")->orderBy("late_submissions_final_status.created_at", "desc")->first();*/

        $late_submission_data = $waitlist_data = array();


        $grade_change_data = SubmissionGradeChange::where("submission_id", $id)->whereNotNull('old_contract_file_name')->orderBy('old_contract_date', 'desc')->get();
        $grade_change_count = SubmissionGradeChange::where("submission_id", $id)->count();

        $last_date_online_acceptance = $last_date_offline_acceptance = "";
       

        if(!empty($offer_data))
        {
           if($offer_data->last_date_online_acceptance != "")
                $last_date_online_acceptance = $offer_data->last_date_online_acceptance;
           if($offer_data->last_date_offline_acceptance != "")
                $last_date_offline_acceptance = $offer_data->last_date_offline_acceptance;
        }

        if(!empty($waitlist_data))
        {
           if($waitlist_data->last_date_online_acceptance != "")
                $last_date_online_acceptance = $waitlist_data->last_date_online_acceptance;
           if($waitlist_data->last_date_offline_acceptance != "")
                $last_date_offline_acceptance = $waitlist_data->last_date_offline_acceptance;
        }



        /*if($last_date_online_acceptance == "")
            $last_date_online_acceptance = date('m/d/Y H:i', strtotime('+1 day'));
        
        if($last_date_offline_acceptance == "")
            $last_date_offline_acceptance = date('m/d/Y H:i', strtotime('+1 day'));
        */

        $gradeInfo = SubjectManagement::where("grade", $submission->next_grade)->where("application_id", $submission->application_id)->first();
        $submission->open_enrollment = Enrollment::join('application', 'application.enrollment_id', 'enrollments.id')->where('application.id',$submission->application_id)->select("enrollments.id")->first()->id;

        $data['grades']=Grade::get();
        $data['enrollments']=Enrollment::where('status','Y')->where('district_id',Session::get('district_id'))->get();
        $data['schools']=School::where('status','Y')->where('district_id',Session::get('district_id'))->get();
        $applicationPrograms=Application::join('application_programs','application_programs.application_id','=','application.id')
            ->where('application_id',$submission->application_id)
            ->select('application_programs.*')->get();
//         return $data['schools'];
//            print_r($applicationPrograms);exit;
        foreach ($applicationPrograms  as $key => $applicationProgram) 
        {
            // echo $applicationProgram->program_id."<BR>";
            $applicationPrograms[$key]->grade_id=Grade::where('id',$applicationProgram->grade_id)->first()->name;
            $applicationPrograms[$key]->program_id=Program::where('id',$applicationProgram->program_id)->first()->name;
        }
        $data['applicationPrograms']=$applicationPrograms;
        $data['comments'] = SubmissionComment::where('submission_id', $id)
           // ->where('user_id', \Auth::user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $data['status_logs'] = SubmissionsStatusLog::where('submission_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

         $data['email_communication'] = EmailActivityLog::where('submission_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();            

        $rsNxt = Program::join("application_programs", "application_programs.program_id", "program.id")->where("application_programs.id", $submission->first_choice)->select('grade_lavel')->first();
        $nxt_grades = explode(",",$rsNxt->grade_lavel);


        /* */
        $manual_processing = "N";
        if($submission->submission_status == "Denied due to Incomplete Records" || $submission->submission_status == "Denied due to Ineligibility")
        {
            $manually_updated = "Y";
            if($submission->cdi_override == "Y" && $submission->grade_override == "Y")
            {
                $manual_processing = "Y";
            }
            else
            {
                $manual_processing = $this->checkSubmissionEligibility($submission);
            }


        }
        // return $submission;
        return view('Submissions::edit_singletab',compact('data','submission','district','gradeInfo','display_outcome','offer_data', 'waitlist_data', 'manual_processing', 'last_date_online_acceptance', 'last_date_offline_acceptance', "nxt_grades", "grade_change_data","grade_change_count","late_submission_data", "from", "manually_updated"));
    }

    public function checkSubmissionEligibility($submission)
    {
        $subjects = $terms = array();
        $eligibilityArr = array();

        $manual_processing = "N";
        
        $eligibilityData = getEligibilities($submission->first_choice, 'Academic Grade Calculation');
        if(count($eligibilityData) > 0)
        {
            if(!in_array($eligibilityData[0]->id, $eligibilityArr))
            {
                $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
               // echo $eligibilityData[0]->id;exit;
                $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                if(!empty($content))
                {
                    if($content->scoring->type=="DD")
                    {
                        $tmp = array();
                        
                        foreach($content->subjects as $value)
                        {
                            if(!in_array($value, $subjects))
                            {
                                $subjects[] = $value;
                            }
                        }

                        foreach($content->terms_calc as $value)
                        {
                            if(!in_array($value, $terms))
                            {
                                $terms[] = $value;
                            }
                        }
                    }
                }                        
            }

        }

        if($submission->second_choice != "")
        {
            $eligibilityData = getEligibilities($submission->second_choice, 'Academic Grade Calculation');
            if(count($eligibilityData) > 0)
            {
                $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);
                if(!empty($content))
                {
                    if($content->scoring->type=="DD")
                    {
                        $tmp = array();
                        
                        foreach($content->subjects as $value)
                        {
                            if(!in_array($value, $subjects))
                            {
                                $subjects[] = $value;
                            }
                        }

                        foreach($content->terms_calc as $value)
                        {
                            if(!in_array($value, $terms))
                            {
                                $terms[] = $value;
                            }
                        }
                    }
                }
            }
        }

        $setEligibilityData = array();
        $data = getSetEligibilityData($submission->first_choice, 3);
        foreach($subjects as $svalue)
        {
            foreach($terms as $tvalue)
            {
                if(isset($data->{$svalue."-".$tvalue}))
                {
                    $setEligibilityData[$submission->first_choice][$svalue."-".$tvalue] = $data->{$svalue."-".$tvalue}[0];
                }
/*                        else
                    $setEligibilityData[$value->first_choice][$svalue."-".$tvalue] = 50;*/
            }
        }

        if($submission->second_choice != '')
        {
            $data = getSetEligibilityData($submission->second_choice, 3);
            foreach($subjects as $svalue)
            {
                foreach($terms as $tvalue)
                {
                    if(isset($data->{$svalue."-".$tvalue}))
                    {
                        $setEligibilityData[$submission->second_choice][$svalue."-".$tvalue] = $data->{$svalue."-".$tvalue}[0];
                    }
                 /*   else
                        $setEligibilityData[$value->second_choice][$svalue."-".$tvalue] = 50;*/
                }
            }
        }

        $setCDIEligibilityData = array();
        $data = getSetEligibilityData($submission->first_choice, 8);
        if(!empty($data))
        {
            $setCDIEligibilityData[$submission->first_choice]['b_info'] = $data->B[0];
            $setCDIEligibilityData[$submission->first_choice]['c_info'] = $data->C[0];
            $setCDIEligibilityData[$submission->first_choice]['d_info'] = $data->D[0];
            $setCDIEligibilityData[$submission->first_choice]['e_info'] = $data->E[0];
            $setCDIEligibilityData[$submission->first_choice]['susp'] = $data->Susp[0];
            $setCDIEligibilityData[$submission->first_choice]['susp_days'] = $data->SuspDays[0];
        }

        if($submission->second_choice != '')
        {
            $data = getSetEligibilityData($submission->second_choice, 8);
            if(!empty($data))
            {
                $setCDIEligibilityData[$submission->second_choice]['b_info'] = $data->B[0];
                $setCDIEligibilityData[$submission->second_choice]['c_info'] = $data->C[0];
                $setCDIEligibilityData[$submission->second_choice]['d_info'] = $data->D[0];
                $setCDIEligibilityData[$submission->second_choice]['e_info'] = $data->E[0];
                $setCDIEligibilityData[$submission->second_choice]['susp'] = $data->Susp[0];
                $setCDIEligibilityData[$submission->second_choice]['susp_days'] = $data->SuspDays[0];
            }
        }
        $score =  $this->collectionStudentGradeReport($submission, $subjects, $terms, $submission->next_grade, $setEligibilityData);

        if(!empty($score))
        {
            if($submission->cdi_override == "Y")
            {
                $manual_processing = "Y";
            }
            else
            {
                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $submission->id)->first();
                if(!empty($cdi_data))
                {
                    $cdiArr = array();
                    $cdiArr['b_info'] = $cdi_data->b_info;
                    $cdiArr['c_info'] = $cdi_data->c_info;
                    $cdiArr['d_info'] = $cdi_data->d_info;
                    $cdiArr['e_info'] = $cdi_data->e_info;
                    $cdiArr['susp'] = $cdi_data->susp;
                    $cdiArr['susp_days'] = $cdi_data->susp_days;
                    if(isset($setCDIEligibilityData[$submission->first_choice]['b_info']))
                    {
                        if(!is_numeric($cdiArr['b_info']))
                        {
                            $manual_processing = "Y";
                        }
                        elseif($cdiArr['b_info'] > $setCDIEligibilityData[$submission->first_choice]['b_info'] || $cdiArr['c_info'] > $setCDIEligibilityData[$submission->first_choice]['c_info'] || $cdiArr['d_info'] > $setCDIEligibilityData[$submission->first_choice]['d_info'] || $cdiArr['e_info'] > $setCDIEligibilityData[$submission->first_choice]['e_info'] || $cdiArr['susp'] > $setCDIEligibilityData[$submission->first_choice]['susp'] || $cdiArr['susp_days'] > $setCDIEligibilityData[$submission->first_choice]['susp_days'])
                        {
                        }
                        else
                        {
                            $manual_processing = "Y";
                        }
                    }

                }
                elseif($submission->cdi_override == "Y")
                {
                    $manual_processing = "Y";
                }

            }


            if($this->eligibility_grade_pass[$submission->id]['first'] != "Pass")
            {
                $manual_processing = "N";
            }
            if($submission->second_choice != "")
            {
                if($this->eligibility_grade_pass[$submission->id]['second'] != "Pass")
                {
                    $manual_processing = "N";
                }
            }


        }
        return $manual_processing;

    }

    public function collectionStudentGradeReport($submission, $subjects, $terms, $next_grade=0, $setEligibilityData)
    {
        $config_subjects = Config::get('variables.subjects');
        $score = array();
        $missing = false;

        $gradeInfo = SubjectManagement::where("grade", $next_grade)->first();
        $import_academic_year = Config::get('variables.import_academic_year');
        $first_failed = $second_failed = 0;
        foreach($subjects as $value)
        {
            foreach($terms as $value1)
            {
                
                $marks = getSubmissionAcademicScoreMissing($submission->id, $config_subjects[$value], $value1, $import_academic_year, $import_academic_year);
                /* Here copy above function if condition  for NA */

                if($marks == "NA")
                {
                    if($submission->grade_override == "Y")
                    {
                        $score[$value][$value1] = "NA";
                    }
                    else
                    {
                        if(!empty($gradeInfo))
                        {
                            $field = strtolower(str_replace(" ","_", $config_subjects[$value]));    
                            if($gradeInfo->{$field} == "N")
                            {
                                $score[$value][$value1] = "NA"; 
                            }
                            else
                            {
                                return array();
                            }

                        }
                        else
                        {
                            return array();
                        }
                    }
                }
                else
                {
                    if(isset($setEligibilityData[$submission->first_choice][$value."-".$value1]))
                    {
                        if($setEligibilityData[$submission->first_choice][$value."-".$value1] > $marks)
                        {
                            $first_failed++;
                        }
                    }

                    if(isset($setEligibilityData[$submission->second_choice][$value."-".$value1]))
                    {
                        if($setEligibilityData[$submission->second_choice][$value."-".$value1] > $marks)
                        {
                            $second_failed++;
                        }
                    }
                    $score[$value][$value1] = $marks;

                }
            }
        }

        if($first_failed > 0 && $submission->grade_override == "N")
        {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Fail";
        }
        else
        {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Pass";
        }

        if($second_failed > 0 && $submission->grade_override == "N")
        {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Fail";
        }
        else
        {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Pass";
        }
        return $score;
    }

    public function getProgramGrades($choice_id)
    {
        $rs = Program::join("application_programs", "application_programs.program_id", "program.id")->where("application_programs.id", $choice_id)->select('grade_lavel')->first();
        return explode($rs->grade_lavel);
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
        $req = $request->all();
        $first_choice_program_id = $second_choice_program_id = 0;
        if($request->first_choice != "")
        {
            $rs = ApplicationProgram::where("id", $request->first_choice)->select("program_id")->first();
            if(!empty($rs))
                $first_choice_program_id = $rs->program_id;
            else
                $first_choice_program_id = 0;
        }

        if($request->second_choice != "")
        {
            $rs = ApplicationProgram::where("id", $request->second_choice)->select("program_id")->first();
            if(!empty($rs))
                $second_choice_program_id = $rs->program_id;
            else
                $second_choice_program_id = 0;
        }
        else
        {
            $request->second_choice = 0;
            $second_choice_program_id = 0;
        }


        // return $request;
        $data=[
             'student_id'=>$request->student_id,
            //'state_id'=>$request->state_id,
            // 'application_id'=>$request->application_id,
            'first_choice_program_id' => $first_choice_program_id,
            'second_choice_program_id' => $second_choice_program_id,
            'first_name'=>$request->first_name,
            'last_name'=>$request->last_name,
            'race'=>$request->race,
            'gender'=>$request->gender,
            'birthday'=>$request->birthday,
            'address'=>$request->address,
            'city'=>$request->city,
            'state'=>$request->state,
            'zip'=>$request->zip,
            'current_school'=>$request->current_school,
            'current_grade'=>$request->current_grade,
            'next_grade'=>$request->next_grade,
            // 'non_hsv_student'=>$request->non_hsv_student,
            'special_accommodations'=>$request->special_accommodations,
            'parent_first_name'=>$request->parent_first_name,
            'parent_last_name'=>$request->parent_last_name,
            'parent_email'=>$request->parent_email,
            /*'emergency_contact'=>$request->emergency_contact,
            'emergency_contact_phone'=>$request->emergency_contact_phone,
            'emergency_contact_relationship'=>$request->emergency_contact_relationship,*/
            'phone_number'=>$request->phone_number,
            'alternate_number'=>$request->alternate_number,
            'zoned_school'=>$request->zoned_school,
            // 'lottery_number'=>$request->lottery_number,
            'first_choice'=>$request->first_choice,
            'second_choice'=>$request->second_choice,
            'open_enrollment'=>$request->open_enrollment,
            'submission_status'=>$request->submission_status,
            'mcp_employee'=>$request->mcp_employee,
            'employee_first_name'=>$request->employee_first_name,
            'employee_last_name'=>$request->employee_last_name,
            'work_location'=>$request->work_location,
            'employee_id'=>$request->employee_id,
            'manual_grade_change' =>$request->manual_grade_change=="on"?'Y':'N',
            'override_student'=>$request->override_student=='on'?'Y':'N',
        ];
        // return $data;
        if(!isset($request->current_grade))
            unset($data['current_grade']);

        if($first_choice_program_id == 0)
            unset($data['first_choice_program_id']);

        if($second_choice_program_id == 0)
            unset($data['second_choice_program_id']);

        if(!isset($request->next_grade))
            unset($data['next_grade']);

        if(!isset($request->first_choice))
            unset($data['first_choice']);

        if(!isset($request->second_choice))
        {
            unset($data['second_choice']);
        }
        


        /*  Code Audit Trail to Get Original Value */
        $initSubmission = Submissions::where('submissions.id',$id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();
        $result=Submissions::where('id',$id)->update($data);

        $initSubmission->gender = "";
        $initSubmission->letter_body = "";
        
        /*  Code Audit Trail to Get New Value */
       $newObj =  Submissions::where('submissions.id',$id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();
       
       if(($initSubmission->first_choice != $request->first_choice && $request->first_choice != '')|| ($initSubmission->second_choice != $request->second_choice &&  $request->second_choice != '')){
            $submission_event = "";
            if($initSubmission->first_choice != $request->first_choice)
            {
                $submission_event .= "First Choice Program : <span class='text-danger'>".getProgramName($initSubmission->first_choice_program_id)."</span> TO <span class='text-success'>".getProgramName($newObj->first_choice_program_id)."<br></span>";
            }
            if($initSubmission->second_choice != $request->second_choice && $request->second_choice != "")
            {
                if($initSubmission->second_choice != "")
                {
                    $submission_event .= "Second Choice Program : <span class='text-danger'>".getProgramName($initSubmission->second_choice_program_id)."</span> TO <span class='text-success'>".getProgramName($newObj->second_choice_program_id)."<br></span>";
                }
                else
                {
                    $submission_event .= "Second Choice Program : <span class='text-success'>".getProgramName($newObj->second_choice_program_id)."<br></span>";
                }
            }
            $comment_data = [
                'submission_id' => $id,
                'user_id' => \Auth::user()->id,
                'comment' => $request->choice_comment,
                'submission_event' => $submission_event
            ];
            SubmissionComment::create($comment_data);
            $newObj->gender = $request->choice_comment;

        }

        if($initSubmission->manual_grade_change != $newObj->manual_grade_change)
        {
                $submission_event = "Manualy Grade Change  : <span class='text-danger'>".$initSubmission->manual_grade_change."</span> TO <span class='text-success'>".$newObj->manual_grade_change."<br></span>";
                $comment_data = [
                    'submission_id' => $id,
                    'user_id' => \Auth::user()->id,
                    'comment' => $request->grade_change_comment,
                    'submission_event' => $submission_event
                ];
                SubmissionComment::create($comment_data);
                $newObj->gender = $request->choice_comment;

        }

        if($initSubmission->submission_status != $request->submission_status)
        {
                $submission_event = "Submission Status : <span class='text-danger'>".$initSubmission->submission_status."</span> TO <span class='text-success'>".$newObj->submission_status."<br></span>";
                $comment_data = [
                    'submission_id' => $id,
                    'user_id' => \Auth::user()->id,
                    'comment' => $request->status_comment,
                    'submission_event' => $submission_event
                ];
                SubmissionComment::create($comment_data);
                $newObj->letter_body = $request->status_comment;

                $commentObj = array();
                $commentObj['old_status'] = $initSubmission->submission_status;
                $commentObj['new_status'] = $newObj->submission_status;
                $commentObj['updated_by'] = Auth::user()->id;
                $commentObj['comment'] = $request->status_comment;
                $commentObj['submission_id'] = $id;
                SubmissionsStatusLog::create($commentObj);

        }

        /*if($initSubmission->submission_status != "Offered" && $newObj->submission_status == "Offered")
        {
            $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_choice_final_status"=>"Offered", "first_waitlist_for"=>$newObj->first_choice_program_id));        
        }*/


        if($initSubmission->submission_status != 'Auto Decline' && !in_array($initSubmission->submission_status, array('Active', 'Pending', 'Application Withdrawn')))
        {
            $version = LateSubmissionFinalStatus::where("submission_id", $id)->orderBy("created_at", "desc")->first();
            if(!empty($version))
            {
                /* */
                    if($initSubmission->submission_status == "Declined / Waitlist for other")
                        {

                            if($newObj->submission_status == "Offered")
                            {
                                if($initSubmission->first_choice_program_id == $request->newofferprogram)
                                {
                                    $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                    $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Pending", "second_offer_status"=>"Pending"));

                                }
                                else
                                {
                                    $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                    if($initSubmission->first_choice_program_id > 0)
                                    {
                                        $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Waitlisted", "first_offer_status"=>"Pending"));

                                    }   
                                    else
                                    {
                                        $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Pending", "first_offer_status"=>"Pending"));
                                    } 

                                }

                               do
                                {
                                    $code = mt_rand(100000, 999999);
                                    $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                    $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                    $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                                }
                                while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));   

                                $rssub = Submissions::where("id", $id)->update(["awarded_school" => getProgramName($request->newofferprogram)]);   

                                $data1 = array();
                                $data1['offer_slug'] = $code;
                                $data1['manually_updated'] = "Y";    
                                $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                                $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                                $data1['communication_sent'] = 'N';
                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update($data1);

                               

                                $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                                $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                                $rs = SubmissionsStatusLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>$initSubmission->submission_status, "updated_by"=>Auth::user()->id));


                            }

                        }
                    

                    if($initSubmission->submission_status == "Offered and Declined")
                    {
                        if($newObj->submission_status == "Offered")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Pending", "second_offer_status"=>"Pending"));
                                 

                            }
                            else
                            {
                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                if($initSubmission->first_choice_program_id > 0)
                                {
                                    $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Waitlisted", "first_offer_status"=>"Pending"));
                                }   
                                else
                                {
                                    $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Pending", "first_offer_status"=>"Pending"));
                                } 

                            }
                            do
                            {
                                $code = mt_rand(100000, 999999);
                                $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                            }
                            while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3)); 

                            $data1 = array();
                            $data1['offer_slug'] = $code;
                            $data1['manually_updated'] = "Y";    
                            $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                            $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                            $data1['communication_sent'] = 'N';
                            $rs = LateSubmissionFinalStatus::where("id", $version->id)->update($data1);

                            $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                            $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                           // $rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = SubmissionsWaitlistStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "version"=>$version->version));

                            $rsNew = Submissions::where("id", $id)->update(["awarded_school" =>getProgramName($request->newofferprogram)]);


                        }

                        if($newObj->submission_status == "Declined / Waitlist for Other")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("second_offer_status"=>"Declined & Waitlisted","first_offer_status"=>"Waitlisted","second_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for Other'";

                            }
                            else
                            {
                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_offer_status"=>"Declined & Waitlisted","second_offer_status"=>"Waitlisted","first_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for Other'";

                            }

                            //$rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for Other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = SubmissionsWaitlistStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for Other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "version"=>$version->version));


                
                        }
                    }



                    if($initSubmission->submission_status == "Offered and Accepted")
                    {
                        if($newObj->submission_status == "Offered and Declined")
                        {
                            $rs = LateSubmissionFinalStatus::where("id", $version->id)->where("first_choice_final_status", "Offered")->update(array("first_offer_status"=>"Declined & Waitlisted", "contract_signed"=>"Pending"));        
                            $rs = LateSubmissionFinalStatus::where("id", $version->id)->where("second_choice_final_status", "Offered")->update(array("second_offer_status"=>"Declined & Waitlisted", "contract_signed"=>"Pending"));

                            $comment = getUserName(Auth::user()->id)." has changed status to 'Offered and Declined'";
                            //$rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered and Declined", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = LateSubmissionsStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered and Declined", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "version"=>$version->version));

                        }

                        if($newObj->submission_status == "Declined / Waitlist for other")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("second_offer_status"=>"Declined & Waitlisted","first_offer_status"=>"Waitlisted","second_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for other'";

                            }
                            else
                            {
                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_offer_status"=>"Declined & Waitlisted","second_offer_status"=>"Waitlisted","first_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for other'";

                            }

                            //$rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = LateSubmissionsStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "version"=>$version->version));


                
                        }
                    }

                    if($initSubmission->submission_status == "Waitlisted")
                    {
                        if($newObj->submission_status == "Offered")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Pending", "second_offer_status"=>"Pending"));
                                 

                            }
                            else
                            {
                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                if($initSubmission->first_choice_program_id > 0)
                                {
                                    $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Waitlisted", "first_offer_status"=>"Pending"));
                                }   
                                else
                                {
                                    $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Pending", "first_offer_status"=>"Pending"));
                                } 

                            }

                            do
                            {
                                $code = mt_rand(100000, 999999);
                                $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                            }
                            while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));     

                            $data1 = array();
                            $data1['offer_slug'] = $code;
                            $data1['manually_updated'] = "Y";    
                            $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                            $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                            $data1['communication_sent'] = 'N';
                            $rs = LateSubmissionFinalStatus::where("id", $version->id)->update($data1);

                            $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                            $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                           // $rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = LateSubmissionsStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "version"=>$version->version));

                            $rsNew = Submissions::where("id", $id)->update(["awarded_school" =>getProgramName($request->newofferprogram)]);


                        }

                    }

                    if($initSubmission->submission_status == "Denied due to Ineligibility" || $initSubmission->submission_status == "Denied due to Incomplete Records")
                    {
                        if($newObj->submission_status == "Offered")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {

                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                            }
                            else
                            {
                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                            }
                            do
                            {
                                $code = mt_rand(100000, 999999);
                                $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                            }
                            while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));   
                            $data1 = array();
                            $data1['offer_slug'] = $code;
                            $data1['manually_updated'] = "Y";    
                            $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                            $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                            $data1['communication_sent'] = 'N';
                            $rs = LateSubmissionFinalStatus::where("id", $version->id)->update($data1);

                            $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                            $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                           // $rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = LateSubmissionsStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "version"=>$version->version));
                            $rsNew = Submissions::where("id", $id)->update(["awarded_school" =>getProgramName($request->newofferprogram)]);




                        }
                    }

                    if($initSubmission->submission_status == "Offered")
                    {
                        if($newObj->submission_status == "Offered and Declined")
                        {
                            $rs = LateSubmissionFinalStatus::where("id", $version->id)->where("first_choice_final_status", "Offered")->update(array("first_offer_status"=>"Declined", "second_offer_status"=>"Declined", ));        
                            $rs = LateSubmissionFinalStatus::where("id", $version->id)->where("second_choice_final_status", "Offered")->update(array("second_offer_status"=>"Declined", "first_offer_status"=>'Declined'));

                            $comment = getUserName(Auth::user()->id)." has changed status to 'Offered and Declined'";
                            

                        }

                    }
                /* */
            }
            else
            {
                $version = SubmissionsWaitlistFinalStatus::where("submission_id", $id)->orderBy("created_at", "desc")->first();

                if(!empty($version))
                {
                    if($initSubmission->submission_status == "Declined / Waitlist for other")
                        {

                            if($newObj->submission_status == "Offered")
                            {
                                if($initSubmission->first_choice_program_id == $request->newofferprogram)
                                {
                                    $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));
                                    
                                    $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Pending", "second_offer_status"=>"Pending"));

                                }
                                else
                                {
                                    $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                    if($initSubmission->first_choice_program_id > 0)
                                    {
                                        $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Waitlisted", "first_offer_status"=>"Pending"));

                                    }   
                                    else
                                    {
                                        $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Pending", "first_offer_status"=>"Pending"));
                                    } 

                                }

                               do
                                {
                                    $code = mt_rand(100000, 999999);
                                    $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                    $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                    $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                                }
                                while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));   

                                $rssub = Submissions::where("id", $id)->update(["awarded_school" => getProgramName($request->newofferprogram)]);   

                                $data1 = array();
                                $data1['offer_slug'] = $code;
                                $data1['manually_updated'] = "Y";    
                                $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                                $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                                $data1['communication_sent'] = 'N';
                                $rs = LateSubmissionFinalStatus::where("id", $version->id)->update($data1);
                               
                               

                                $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                                $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                                $rs = SubmissionsStatusLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>$initSubmission->submission_status, "updated_by"=>Auth::user()->id));


                            }

                        }
                    
                    if($initSubmission->submission_status == "Offered and Declined")
                    {
                        if($newObj->submission_status == "Offered")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Pending", "second_offer_status"=>"Pending"));
                                 

                            }
                            else
                            {
                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                if($initSubmission->first_choice_program_id > 0)
                                {
                                    $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Waitlisted", "first_offer_status"=>"Pending"));
                                }   
                                else
                                {
                                    $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Pending", "first_offer_status"=>"Pending"));
                                } 

                            }
                            do
                            {
                                $code = mt_rand(100000, 999999);
                                $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                            }
                            while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3)); 

                            $data1 = array();
                            $data1['offer_slug'] = $code;
                            $data1['manually_updated'] = "Y";    
                            $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                            $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                            $data1['communication_sent'] = 'N';
                            $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update($data1);

                            $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                            $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                           // $rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = SubmissionsWaitlistStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "version"=>$version->version));

                            $rsNew = Submissions::where("id", $id)->update(["awarded_school" =>getProgramName($request->newofferprogram)]);


                        }

                        if($newObj->submission_status == "Declined / Waitlist for Other")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("second_offer_status"=>"Declined & Waitlisted","first_offer_status"=>"Waitlisted","second_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for Other'";

                            }
                            else
                            {
                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_offer_status"=>"Declined & Waitlisted","second_offer_status"=>"Waitlisted","first_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for Other'";

                            }

                            //$rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for Other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = SubmissionsWaitlistStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for Other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "version"=>$version->version));


                
                        }
                    }

                    if($initSubmission->submission_status == "Offered and Accepted")
                    {
                        if($newObj->submission_status == "Offered and Declined")
                        {
                            $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->where("first_choice_final_status", "Offered")->update(array("first_offer_status"=>"Declined & Waitlisted", "contract_signed"=>"Pending"));        
                            $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->where("second_choice_final_status", "Offered")->update(array("second_offer_status"=>"Declined & Waitlisted", "contract_signed"=>"Pending"));

                            $comment = getUserName(Auth::user()->id)." has changed status to 'Offered and Declined'";
                            //$rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered and Declined", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = SubmissionsWaitlistStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered and Declined", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "version"=>$version->version));

                        }

                        if($newObj->submission_status == "Declined / Waitlist for other")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("second_offer_status"=>"Declined & Waitlisted","first_offer_status"=>"Waitlisted","second_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for other'";

                            }
                            else
                            {
                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_offer_status"=>"Declined & Waitlisted","second_offer_status"=>"Waitlisted","first_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for other'";

                            }

                            //$rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = SubmissionsWaitlistStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "version"=>$version->version));


                
                        }
                    }

                    if($initSubmission->submission_status == "Waitlisted")
                    {
                        if($newObj->submission_status == "Offered")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Pending", "second_offer_status"=>"Pending"));
                                 

                            }
                            else
                            {
                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                if($initSubmission->first_choice_program_id > 0)
                                {
                                    $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Waitlisted", "first_offer_status"=>"Pending"));
                                }   
                                else
                                {
                                    $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Pending", "first_offer_status"=>"Pending"));
                                } 

                            }

                            do
                            {
                                $code = mt_rand(100000, 999999);
                                $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                            }
                            while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));      

                            $data1 = array();
                            $data1['offer_slug'] = $code;
                            $data1['manually_updated'] = "Y";    
                            $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                            $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                            $data1['communication_sent'] = 'N';
                            $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update($data1);

                            $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                            $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                           // $rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = SubmissionsWaitlistStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "version"=>$version->version));

                            $rsNew = Submissions::where("id", $id)->update(["awarded_school" =>getProgramName($request->newofferprogram)]);


                        }

                    }

                    if($initSubmission->submission_status == "Denied due to Ineligibility" || $initSubmission->submission_status == "Denied due to Incomplete Records")
                    {
                        if($newObj->submission_status == "Offered")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {

                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                            }
                            else
                            {
                                $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                            }
                            do
                            {
                                $code = mt_rand(100000, 999999);
                                $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                            }
                            while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));   
                            $data1 = array();
                            $data1['offer_slug'] = $code;
                            $data1['manually_updated'] = "Y";    
                            $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                            $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                            $data1['communication_sent'] = 'N';
                            $rs = SubmissionsWaitlistFinalStatus::where("id", $version->id)->update($data1);

                            $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                            $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                           // $rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = SubmissionsWaitlistStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "version"=>$version->version));




                        }
                    }
                }
                else
                {
                    if($initSubmission->submission_status == "Declined / Waitlist for other")
                        {

                            if($newObj->submission_status == "Offered")
                            {
                                if($initSubmission->first_choice_program_id == $request->newofferprogram)
                                {
                                    $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                    $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("second_choice_final_status"=>"Pending", "second_offer_status"=>"Pending"));

                                }
                                else
                                {
                                    $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                    if($initSubmission->first_choice_program_id > 0)
                                    {
                                        $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_choice_final_status"=>"Waitlisted", "first_offer_status"=>"Pending"));

                                    }   
                                    else
                                    {
                                        $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_choice_final_status"=>"Pending", "first_offer_status"=>"Pending"));
                                    } 

                                }

                               do
                                {
                                    $code = mt_rand(100000, 999999);
                                    $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                    $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                    $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                                }
                                while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));   

                                $rssub = Submissions::where("id", $id)->update(["awarded_school" => getProgramName($request->newofferprogram)]);   

                                $data1 = array();
                                $data1['offer_slug'] = $code;
                                $data1['manually_updated'] = "Y";    
                                $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                                $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                                $data1['communication_sent'] = 'N';
                                $rs = SubmissionsFinalStatus::where("submission_id", $id)->update($data1);

                               

                                $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                                $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                                $rs = SubmissionsStatusLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>$initSubmission->submission_status, "updated_by"=>Auth::user()->id));


                            }

                        }
                    
                    if($initSubmission->submission_status == "Offered and Declined")
                    {
                        if($newObj->submission_status == "Offered")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("second_choice_final_status"=>"Pending", "second_offer_status"=>"Pending"));
                                 
                            }
                            else
                            {
                                $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                if($initSubmission->first_choice_program_id > 0)
                                {
                                    $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_choice_final_status"=>"Waitlisted", "first_offer_status"=>"Pending"));

                                }   
                                else
                                {
                                    $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_choice_final_status"=>"Pending", "first_offer_status"=>"Pending"));

                                } 

                            }
                            do
                            {
                                $code = mt_rand(100000, 999999);
                                $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                            }
                            while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));  

                            $data1 = array();
                            $data1['offer_slug'] = $code;
                            $data1['manually_updated'] = "Y";    
                            $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                            $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                            $data1['communication_sent'] = 'N';
                            $rs = SubmissionsFinalStatus::where("submission_id", $id)->update($data1);  

                            $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                            $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                            //$rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "comment"=>$comment));
                            $rs = SubmissionsStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id));

                            $rsNew = Submissions::where("id", $id)->update(["awarded_school" =>getProgramName($request->newofferprogram)]);

                        }


                        if($newObj->submission_status == "Declined / Waitlist for other")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = SubmissionsFinalStatus::where("id", $version->id)->update(array("second_offer_status"=>"Declined & Waitlisted","first_offer_status"=>"Waitlisted","second_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for other'";

                            }
                            else
                            {
                                $rs = SubmissionsFinalStatus::where("id", $version->id)->update(array("first_offer_status"=>"Declined & Waitlisted","second_offer_status"=>"Waitlisted","first_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for other'";

                            }

                            //$rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = SubmissionsStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "version"=>$version->version));


                
                        }


                    }

                    if($initSubmission->submission_status == "Offered and Accepted")
                    {
                        if($newObj->submission_status == "Offered and Declined")
                        {
                            $rs = SubmissionsFinalStatus::where("submission_id", $id)->where("first_choice_final_status", "Offered")->update(array("first_offer_status"=>"Declined & Waitlisted", "contract_signed"=>"Pending"));        
                            $rs = SubmissionsFinalStatus::where("submission_id", $id)->where("second_choice_final_status", "Offered")->update(array("second_offer_status"=>"Declined & Waitlisted", "contract_signed"=>"Pending"));

                            $comment = getUserName(Auth::user()->id)." has changed status to 'Offered and Declined'";
                            //$rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered and Declined", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "comment"=>$comment));
                            $rs = SubmissionsStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered and Declined", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id));

                        }

                        if($newObj->submission_status == "Declined / Waitlist for other")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("second_offer_status"=>"Declined & Waitlisted","first_offer_status"=>"Waitlisted","second_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for other'";

                            }
                            else
                            {
                                $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_offer_status"=>"Declined & Waitlisted","second_offer_status"=>"Waitlisted","first_choice_final_status"=>"Offered"));   
                                $comment = getUserName(Auth::user()->id)." has changed status to 'Declined / Waitlist for other'";

                            }  

                           // $rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id, "comment"=>$comment));
                            $rs = SubmissionsStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Declined / Waitlist for other", "old_status"=>"Offered and Accepted", "updated_by"=>Auth::user()->id));

                        }
                    }

                    if($initSubmission->submission_status == "Waitlisted")
                    {
                        if($newObj->submission_status == "Offered")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {
                                $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("second_choice_final_status"=>"Pending", "second_offer_status"=>"Pending"));
                                 
                            }
                            else
                            {
                                $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                                if($initSubmission->first_choice_program_id > 0)
                                {
                                    $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_choice_final_status"=>"Waitlisted", "first_offer_status"=>"Pending"));

                                }   
                                else
                                {
                                    $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_choice_final_status"=>"Pending", "first_offer_status"=>"Pending"));

                                } 

                            }
                            do
                            {
                                $code = mt_rand(100000, 999999);
                                $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                            }
                            while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));       
                            $data1 = array();
                            $data1['offer_slug'] = $code;
                            $data1['manually_updated'] = "Y";    
                            $data1['last_date_online_acceptance'] = $request->last_date_online_acceptance;
                            $data1['last_date_offline_acceptance'] = $request->last_date_offline_acceptance;
                            $data1['communication_sent'] = 'N';
                            $rs = SubmissionsFinalStatus::where("submission_id", $id)->update($data1);

                            $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                            $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                            //$rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "comment"=>$comment));
                            $rs = SubmissionsStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id));

                            $rsNew = Submissions::where("id", $id)->update(["awarded_school" =>getProgramName($request->newofferprogram)]);
                        }
                        


                    }


                    if($initSubmission->submission_status == "Denied due to Ineligibility" || $initSubmission->submission_status == "Denied due to Incomplete Records")
                    {
                        if($newObj->submission_status == "Offered")
                        {
                            if($initSubmission->first_choice_program_id == $request->newofferprogram)
                            {

                                $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                            }
                            else
                            {
                                $rs = SubmissionsFinalStatus::where("submission_id", $id)->update(array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                            }
                            do
                            {
                                $code = mt_rand(100000, 999999);
                                $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                                $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                                $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                            }
                            while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));   
                            $data1 = array();
                            $data1['offer_slug'] = $code;
                            $data1['manually_updated'] = "Y";    
                            $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                            $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                            $data1['communication_sent'] = 'N';
                            $rs = SubmissionsFinalStatus::where("submission_id", $id)->update($data1);

                            $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                            $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                           // $rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                            $rs = SubmissionsStatusUniqueLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id));




                        }
                    }
                }
            }
        }
        elseif(!in_array($initSubmission->submission_status, array('Active')))
        {
            $date = strtotime("+7 day");
            $last_date_online_acceptance = $last_date_offline_acceptance = date('Y-m-d H:i:s', $date);
            $rsData = LateSubmissionFinalStatus::where("submission_id", $id)->orderBy("created_at", "desc")->first();
            $tmp = [];
            if(!empty($rsData))
            {
                $snid = $rsData->id;
                if($rsData->first_choice_final_status == "Offered")
                {
                    $tmp['first_offer_status'] = "Accepted";
                    $tmp['second_offer_status'] = "NoAction";
                }
                elseif($rsData->second_choice_final_status == "Offered")
                {
                    $tmp['first_offer_status'] = "NoAction";
                    $tmp['second_offer_status'] = "Accepted";
                }

                $rsData = LateSubmissionFinalStatus::where("submission_id", $id)->update($tmp);
                //$last_date_online_acceptance = $last_date_offline_acceptance = date()
            }
            else
            {
                $rsData = SubmissionsWaitlistFinalStatus::where("submission_id", $id)->orderBy("created_at", "desc")->first();
                if(!empty($rsData))
                {
                    $snid = $rsData->id;
                    if($rsData->first_choice_final_status == "Offered")
                    {
                        $tmp['first_offer_status'] = "Accepted";
                        $tmp['second_offer_status'] = "NoAction";
                    }
                    elseif($rsData->second_choice_final_status == "Offered")
                    {
                        $tmp['first_offer_status'] = "NoAction";
                        $tmp['second_offer_status'] = "Accepted";
                    }
                    $rsData = SubmissionsWaitlistFinalStatus::where("id", $snid)->update($tmp);
                }
                else
                {
                    $rsData = SubmissionsFinalStatus::where("submission_id", $id)->first();
                    
                    if($rsData->first_choice_final_status == "Offered")
                    {
                        $tmp['first_offer_status'] = "Accepted";
                        $tmp['second_offer_status'] = "NoAction";
                    }
                    elseif($rsData->second_choice_final_status == "Offered")
                    {
                        $tmp['first_offer_status'] = "NoAction";
                        $tmp['second_offer_status'] = "Accepted";
                    }
                    $rsData = SubmissionsFinalStatus::where("submission_id", $id)->update($tmp);
                }
            }
        }
        elseif($initSubmission->submission_status == 'Active')
        {
            /* If offered */
                if($initSubmission->late_submission == 'Y')
                {
                    $rs = WaitlistAvailabilityProcessLog::where("type", "Late Submission")->orderBy("id", "DESC")->first();
                    if(!empty($rs))
                    {
                        $newversion = $rs->version;
                    }
                    else
                    {
                        $newversion = 1;
                    }
                    $from = new LateSubmissionFinalStatus();
                }
                else
                {
                    $newversion = 0;
                    $from = new SubmissionsFinalStatus();                    
                }
                    if($newObj->submission_status == "Offered")
                    {
                        if($initSubmission->first_choice_program_id == $request->newofferprogram)
                        {
                            $rs = $from::updateOrCreate(["submission_id" =>  $initSubmission->id, "version"=>$newversion], array("first_choice_final_status"=>"Offered", "first_offer_status"=>"Pending", "contract_status"=>"UnSigned", "second_choice_final_status"=>"Pending", "second_offer_status"=>"Pending"));

                        }
                        else
                        {
                            $rs = $from::updateOrCreate(["submission_id" =>  $initSubmission->id, "version"=>$newversion], array("second_choice_final_status"=>"Offered", "second_offer_status"=>"Pending", "contract_status"=>"UnSigned"));

                            if($initSubmission->first_choice_program_id > 0)
                            {
                                $rs = $from::updateOrCreate(["submission_id" =>  $initSubmission->id, "version"=>$newversion], array("first_choice_final_status"=>"Waitlisted", "first_offer_status"=>"Pending"));

                            }   
                            else
                            {
                                $rs = $from::updateOrCreate(["submission_id" =>  $initSubmission->id, "version"=>$newversion], array("first_choice_final_status"=>"Pending", "first_offer_status"=>"Pending"));
                            } 

                        }

                       do
                        {
                            $code = mt_rand(100000, 999999);
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code3 = SubmissionsFinalStatus::where('offer_slug', $code)->first();

                        }
                        while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));   

                        $rssub = Submissions::where("id", $id)->update(["awarded_school" => getProgramName($request->newofferprogram)]);   

                        $data1 = array();
                        $data1['offer_slug'] = $code;
                        $data1['manually_updated'] = "Y";    
                        $data1['last_date_online_acceptance'] = $req['last_date_online_acceptance'];
                        $data1['last_date_offline_acceptance'] = $req['last_date_offline_acceptance'];
                        $data1['communication_sent'] = 'N';
                        $rs = $from::updateOrCreate(["submission_id" =>  $initSubmission->id, "version"=>$newversion], $data1);

                       

                        $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                        $comment = getUserName(Auth::user()->id)." has Offered ".$program_name." to Parent";
                        $rs = SubmissionsStatusLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>$initSubmission->submission_status, "updated_by"=>Auth::user()->id));


                    }

                    if($newObj->submission_status == "Denied due to Ineligibility")
                    {
                        $data = [];
                        $data['submission_id'] = $initSubmission->id;
                        $data['enrollment_id'] = $initSubmission->enrollment_id;
                        $data['application_id'] = $initSubmission->application_id;
                        $data['first_choice_final_status'] = $newObj->submission_status;
                        $data['first_waitlist_for'] = 0;
                        $data['second_waitlist_for'] = 0;
                        $data['second_choice_final_status'] = $newObj->submission_status;

                        $rs = $from::updateOrCreate(["submission_id" => $id], $data);


                        $program_name = getProgramName($request->newofferprogram)." - Grade ".$initSubmission->next_grade;
                        
                       // $rs = SubmissionsStatusLog::create(array("submission_id"=>$id, "new_status"=>"Offered", "old_status"=>"Waitlisted", "updated_by"=>Auth::user()->id, "comment"=>"Waitlist Process :: " . $comment));
                        $rs = SubmissionsStatusLog::updateOrCreate(["submission_id" => $id], array("submission_id"=>$id, "new_status"=> $newObj->submission_status, "old_status"=>$initSubmission->submission_status, "updated_by"=>Auth::user()->id));
                    }

                }



       $this->modelChanges($initSubmission, $newObj, "Submission - General");

       $result =  $newObj;
       if (isset($result)) {
            Session::flash("success", "Submission Updated successfully.");
        } else {
            Session::flash("error", "Please Try Again.");
        }
        if (isset($request->save_exit))
        {
            return redirect('admin/Submissions');
        }
        return redirect('admin/Submissions/edit/'.$id);

    }

    public function resendConfirmationEmail($id)
    {
        $submission_data = Submissions::where('id', $id)->first();
        $msg_data = ApplicationConfiguration::where("application_id", $submission_data['application_id'])->first();
        $application_data = Application::where("id", $submission_data['application_id'])->first();

        $emailArr = array();
        $emailArr['application_id'] = $submission_data['application_id'];
        $emailArr['id'] = $id;
        $emailArr['first_name'] = $submission_data['first_name'];
        $emailArr['last_name'] = $submission_data['last_name'];
        $emailArr['parent_first_name'] = $submission_data['parent_first_name'];
        $emailArr['parent_last_name'] = $submission_data['parent_last_name'];
        $emailArr['email'] = $submission_data['parent_email'];
        $emailArr['confirm_number'] = $submission_data['confirmation_no'];
        $emailArr['transcript_due_date'] = getDateTimeFormat($application_data->transcript_due_date);

        if($submission_data->submission_status == "Active")
        {
            $student_type = "active";
            $emailArr['type'] = "active_email";
            $emailArr['msg'] = $msg_data->active_email;
            $confirm_msg = $msg_data->active_screen;
            $msg_type = "exists_success_application_msg";
            $emailArr['email'] =    $submission_data['parent_email'];
            $subject = $msg_data->active_email_subject;
            $confirm_title = $msg_data->active_screen_title;
            $confirm_subject = $msg_data->active_screen_subject;
        }
        else
        {
            $emailArr['type'] = "pending_email";
            $student_type = "pending";
            $msg_type = "new_success_application_msg";
            $emailArr['email'] = $submission_data['parent_email'];
            $emailArr['msg'] = $msg_data->pending_email;
            $confirm_msg = $msg_data->pending_screen;
            $subject = $msg_data->pending_email_subject;
            $confirm_title = $msg_data->pending_screen_title;
            $confirm_subject = $msg_data->pending_screen_subject;
        }
        $subject = str_replace("{student_name}", $emailArr['first_name']." ".$emailArr['last_name'], $subject);
            $subject = str_replace("{parent_name}", $emailArr['parent_first_name']." ".$emailArr['parent_last_name'], $subject);
            $subject = str_replace("{confirm_number}", $emailArr['confirm_number'], $subject);
        $emailArr['subject'] = $subject;
        $emailArr['module'] = "Resend Order Confirmation";

        $mail = sendMail($emailArr, true);
        if($mail){
            Session::flash('success','Confirmation Mail Sent Successfully.');
        }

        return redirect('/admin/Submissions/edit/'.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateAudition(Request $req,$id)
    {
        $data  = array(
            'submission_id' => $id,
            'data' => isset($req['data']) ? $req['data'] : null
        );
        $checkExist = SubmissionAudition::where("submission_id",$id)->first();
        if(isset($checkExist->id))
        {
            $checkExist->data = $req['data'];
            $result = $checkExist->save();
        }
        else
        {
            $result = SubmissionAudition::create($data);
        }
        if(isset($result))
        {
            Session::flash("success","Data Updated successfully.");
        }
        else
        {
            Session::flash("warning","Something went wrong , Please try again.");
        }
        return redirect()->back();
    }

    public function updateWritingPrompt(Request $req,$id)
    {
        $data  = array(
            'submission_id' => $id,
            'data' => isset($req['data']) ? $req['data'] : null
        );
        $checkExist = SubmissionWritingPrompt::where("submission_id",$id)->first();
        if(isset($checkExist->id))
        {
            $checkExist->data = $req['data'];
            $result = $checkExist->save();
        }
        else
        {
            $result = SubmissionWritingPrompt::create($data);
        }
        if(isset($result))
        {
            Session::flash("success","Data Updated successfully.");
        }
        else
        {
            Session::flash("warning","Something went wrong , Please try again.");
        }
        return redirect()->back();
    }
    public function updateInterviewScore(Request $req,$id)
    {
        // return $req;
        $data  = array(
            'submission_id' => $id,
            'data' => isset($req['data']) ? $req['data'] : null
        );
        $checkExist = SubmissionInterviewScore::where("submission_id",$id)->first();
        if(isset($checkExist->id))
        {
            $checkExist->data = $req['data'];
            $result = $checkExist->save();
        }
        else
        {
            $result = SubmissionInterviewScore::create($data);
        }
        if(isset($result))
        {
            Session::flash("success","Data Updated successfully.");
        }
        else
        {
            Session::flash("warning","Something went wrong , Please try again.");
        }
        return redirect()->back();
    }
    public function updateCommitteeScore(Request $req,$id)
    {
        // return $req;
        $data  = array(
            'submission_id' => $id,
            'data' => isset($req['data']) ? $req['data'] : null
        );
        $checkExist = SubmissionCommitteeScore::where("submission_id",$id)->first();
        if(isset($checkExist->id))
        {
            $checkExist->data = $req['data'];
            $result = $checkExist->save();
        }
        else
        {
            $result = SubmissionCommitteeScore::create($data);
        }
        if(isset($result))
        {
            Session::flash("success","Data Updated successfully.");
        }
        else
        {
            Session::flash("warning","Something went wrong , Please try again.");
        }
        return redirect()->back();
    }
    public function updateConductDisciplinaryInfo(Request $req,$id)
    {
        // return $req;
        $data  = [
            'b_info' => $req->b_info ?? 0,
            'c_info' => $req->c_info ?? 0,
            'd_info' => $req->d_info ?? 0,
            'e_info' => $req->e_info ?? 0,
            'susp' => $req->susp ?? 0,
            'susp_days' => $req->susp_days ?? 0,
        ];

        $conduct_discplinary_info = SubmissionConductDisciplinaryInfo::where("submission_id",$id)->join("submissions", "submissions.id", "submission_conduct_discplinary_info.submission_id")->join("application", "application.id", "submissions.application_id")->select("submission_conduct_discplinary_info.*", "submissions.application_id", "application.enrollment_id")->first();
        if(isset($conduct_discplinary_info))
        {
            $result = SubmissionConductDisciplinaryInfo::where("submission_id", $id)->update($data);
            $newconduct_discplinary_info = SubmissionConductDisciplinaryInfo::where("submission_id",$id)->join("submissions", "submissions.id", "submission_conduct_discplinary_info.submission_id")->join("application", "application.id", "submissions.application_id")->select("submission_conduct_discplinary_info.*", "submissions.application_id", "application.enrollment_id")->first();
            $this->modelChanges($conduct_discplinary_info,$newconduct_discplinary_info,"Submission - CDI");
        }
        else
        {   
            $submission = Submissions::findOrFail($id);
            if (isset($submission)) {
                $new_data = [
                    'submission_id' => $id,
                    'stateID' => $submission->student_id ?? null,
                    // 'student_id' => $submission->student_id ?? null,
                ];
                $data = array_merge($data, $new_data);

                $result = SubmissionConductDisciplinaryInfo::create($data);
                $app_data = SubmissionConductDisciplinaryInfo::where("submission_id",$id)->join("submissions", "submissions.id", "submission_conduct_discplinary_info.submission_id")->join("application", "application.id", "submissions.application_id")->select("submission_conduct_discplinary_info.*", "submissions.application_id", "application.enrollment_id")->first();

                $this->modelCDICreate($app_data,"Submission - CDI");
            }
        }

        $display_outcome = SubmissionsStatusUniqueLog::count();
        $initSubmission = Submissions::where("id", $id)->first();
        if($initSubmission->submission_status == "Pending")
        {
            $rsGradeData = SubmissionGrade::where("submission_id", $id)->first();
            if(!empty($rsGradeData))
            {
                $ins = array();
                $ins['submission_status'] = "Active";
                $rsD = Submissions::where("id", $id)->update($ins);
            }
        }
        if(isset($result))
        {
            Session::flash("success","Data Updated successfully.");
        }
        else
        {
            Session::flash("warning","Something went wrong , Please try again.");
        }
        if (isset($request->save_exit))
        {
            return redirect('admin/Submissions');
        }

        return redirect()->back();
    }
    public function updateStandardizedTesting(Request $req,$id)
    {
        return  $req;
        foreach ($req['data'] as $k => $v) 
        {
            $data  = array(
                'submission_id' => $id,
                'data' => isset($req['data'][$k]) ? $req['data'][$k] : null,
                'subject' => isset($req['subject'][$k]) ? $req['subject'][$k] : null,
                'method' => isset($req['method'][$k]) ? $req['method'][$k] : null,
            );
            $checkExist = SubmissionStandardizedTesting::where("submission_id",$id)->where('subject',$data['subject'])->first();
            if(isset($checkExist->id))
            {
                $checkExist->data = $data['data'];
                $checkExist->method = $data['method'];
                $result = $checkExist->save();
            }
            else
            {
                $result = SubmissionStandardizedTesting::create($data);
            }
        }
        // return $data;
        if(isset($result))
        {
            Session::flash("success","Data Updated successfully.");
        }
        else
        {
            Session::flash("warning","Something went wrong , Please try again.");
        }
        return redirect()->back();
    }
    public function updateAcademicGradeCalculation(Request $req,$id)
    {
         //return $req;
        unset($req['_token']);

        if(isset($req['subjects']))
        {
            $data['subjects'] = json_encode($req['subjects']);
        }
        $data['scoring_type'] = $req['scoring_type'];
        $data['score'] = $req['score'];
        if(isset($req['GradeAverageScore']))
        {
            $data['average_score'] = json_encode($req['GradeAverageScore']);
        }
        if(isset($req['GPA']))
        {
            $data['gpa'] = json_encode($req['GPA']);
        }
        $data['submission_id'] = $id;
        //print_r($data);exit;
        $checkExist = SubmissionAcademicGradeCalculation::where("submission_id",$id)->delete();
        /*if(isset($checkExist->id))
        {
            $checkExist->data = $req['data'];
            $checkExist->value = $req['value'];
            $result = $checkExist->save();
        }
        else
        {*/
            //  foreach()
        $result = SubmissionAcademicGradeCalculation::create($data);
        //}
        if(isset($result))
        {
            Session::flash("success","Data Updated successfully.");
        }
        else
        {
            Session::flash("warning","Something went wrong , Please try again.");
        }
        return redirect()->back();
    }
    
    public function destroy($id)
    {
        //
    }
    public function storeGrades($id, Request $request)
    {

        // return $request;
        $submission_grade = SubmissionGrade::where("submission_id",$id)->join("submissions", "submissions.id", "submission_grade.submission_id")->join("application", "application.id", "submissions.application_id")->select("submission_grade.*", "submissions.application_id", "application.enrollment_id")->get();
        $current_grade = array();
        foreach($submission_grade as $key=>$value)
        {
            $tmp = array();
            $tmp['submission_id'] = $value->submission_id;
            $tmp['application_id'] = $value->application_id;
            $tmp['enrollment_id'] = $value->enrollment_id;
            $tmp['academicYear'] = $value->academicYear;
            $tmp['academicTerm'] = $value->academicTerm;
            $tmp['GradeName'] = $value->GradeName;
            $tmp['courseTypeID'] = $value->courseTypeID;
            $tmp['numericGrade'] = $value->numericGrade;
            $tmp['courseName'] = $value->courseName;
            $current_grade[] = $tmp;
        }

        SubmissionGrade::where('submission_id', $id)->delete();
        $courseType = Config::get('variables.courseType');
        $new_grade = array();
        if (isset($request->academicYear) && count($request->academicYear) > 0) {
            $grades_data = [];
            foreach ($request->academicYear as $key => $value) {
                $grade_data = [
                    'submission_id' => $id,
                    'academicYear' => $request->academicYear[$key] ?? null,
                    'academicTerm' => $request->academicTerm[$key] ?? null,
                    'courseTypeID' => $request->courseTypeID[$key] ?? null,
                    'courseName' => $request->courseName[$key] ?? null,
                    'numericGrade' => $request->numericGrade[$key] ?? null,
                    'sectionNumber' => $request->sectionNumber[$key] ?? null,
                    'courseType' => $request->courseType[$key] ?? $courseType[$request->courseTypeID[$key]],
                    'stateID' => $request->stateID[$key] ?? null,
                    'GradeName' => $request->academicTerm[$key] ?? null,
                    'sequence' => $request->sequence[$key] ?? null,
                    'courseFullName' => $request->courseFullName[$key] ?? null,
                    'fullsection_number' => $request->fullsection_number[$key] ?? null,
                ];

                $grades_data[] = $grade_data;
                $initSubmission = Submissions::where('submissions.id',$id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();
                $grade_data['enrollment_id'] = $initSubmission->enrollment_id;
                $grade_data['application_id'] = $initSubmission->application_id;
                $new_grade[] = $grade_data;
            }
            if(isset($grades_data)){
                $result = SubmissionGrade::insert($grades_data);    
            }

            $this->modelGradeChanges($current_grade, $new_grade, "Submission Academic Grade");
        }else{
            $result = 1;
        }

        $display_outcome = SubmissionsStatusUniqueLog::count();
        if($initSubmission->submission_status == "Pending")
        {
            $rsCDIData = SubmissionConductDisciplinaryInfo::where("submission_id", $id)->first();
            if(!empty($rsCDIData))
            {
                $ins = array();
                $ins['submission_status'] = "Active";
                $rsD = Submissions::where("id", $id)->update($ins);
            }
        }

        if (isset($result)) {
            Session::flash("success","Submission grades successfully.");
        }else {
            Session::flash("warning","Something went wrong , Please try again.");
        }
        if (isset($request->save_exit))
        {
            return redirect('admin/Submissions');
        }
        return redirect('admin/Submissions/edit/'.$id);
    }

    public function storeComments($id, Request $request) {
        // return $id;
        $rules = ['comment' => 'required'];
        $messages = ['comment.required' => 'Please write few words into comment box.'];
        $this->validate($request, $rules, $messages);
        $data = [
            'submission_id' => $id,
            'user_id' => \Auth::user()->id,
            'comment' => $request->comment,
        ];
        $comment = SubmissionComment::create($data);
        if (!empty($comment)) {
            Session::flash('success', "Comment added successfully.");
        }else{
            Session::flash('warning', "Something went wrong , Please try again.");
        }

 

        return redirect('admin/Submissions/edit/'.$id);
    }

    public function transferGradeStudentToSubmission()
    {
        $submission_data = Submissions::whereNotNull('student_id')->where('data_in_submission','N')->where('grade_exists', 'Y')->get();

        if(isset($submission_data) && count($submission_data) > 0){
            foreach($submission_data as $key => $submission){
                $submission_grade = SubmissionGrade::where('submission_id',$submission->id)->get();
                if(count($submission_grade) == 0){
                    $student_grade = StudentGrade::where('stateID',$submission->student_id)->get();
                    if(isset($student_grade) && count($student_grade) > 0){
                        $grades_data = [];
                        foreach ($student_grade as $key => $value) {
                            // return $value;
                            $array = [];
                            $grade_data = [
                                'submission_id' => $submission->id,
                                'stateID' => $submission->student_id,
                                'academicYear' => $value->academicYear ?? null,
                                'academicTerm' => $value->academicTerm ?? null,
                                'courseTypeID' => $value->courseTypeID ?? null,
                                'courseName' => $value->courseName ?? null,
                                'numericGrade' => $value->numericGrade ?? null,
                                'sectionNumber' => $value->sectionNumber ?? null,
                                'courseType' => $value->courseType ?? null,
                                'stateID' => $value->stateID ?? null,
                                'GradeName' => $value->GradeName ?? null,
                                'sequence' => $value->sequence ?? null,
                                'courseFullName' => $value->courseFullName ?? null,
                                'fullsection_number' => $value->fullsection_number ?? null,
                            ];
                            $grades_data[] = $grade_data;
                        }

                        if(isset($grades_data)){
                            SubmissionGrade::insert($grades_data);
                            Submissions::where('id',$submission->id)->update(['data_in_submission'=>'Y']);
                        }
                        
                    }
                }else{
                    Submissions::where('id',$submission->id)->update(['data_in_submission'=>'Y']);
                }


            }
        } 

        $submission_data = Submissions::whereNotNull('student_id')->where('conduct_disc_in_submission','N')->where('cdi_exists', 'Y')->get();

        if(isset($submission_data) && count($submission_data) > 0){
            foreach($submission_data as $key => $submission){
                $submission_grade = SubmissionConductDisciplinaryInfo::where('submission_id',$submission->id)->get();
                if(count($submission_grade) == 0){
                    $student_cdi = StudentCDI::where('stateID',$submission->student_id)->get();
                    if(isset($student_cdi) && count($student_cdi) > 0){
                        $cdi_data = [];
                        foreach ($student_cdi as $key => $value) {
                            // return $value;
                            $array = [];
                            $data = [
                                'submission_id' => $submission->id,
                                'stateID' => $submission->student_id,
                                'b_info' => $value->b_info ?? 0,
                                'c_info' => $value->c_info ?? 0,
                                'd_info' => $value->d_info ?? 0,
                                'e_info' => $value->e_info ?? 0,
                                'susp' => $value->susp ?? 0,
                                'susp_days' => $value->susp_days ?? 0,
                            ];
                            $cdi_data[] = $data;
                        }

                        if(isset($cdi_data)){
                            SubmissionConductDisciplinaryInfo::insert($cdi_data);
                            Submissions::where('id',$submission->id)->update(['conduct_disc_in_submission'=>'Y']);
                        }
                        
                    }
                }else{
                    Submissions::where('id',$submission->id)->update(['conduct_disc_in_submission'=>'Y']);
                }

                
            }
        }   
    }

    public function overrideCDI(Request $request)
    {
        $initSubmission = Submissions::where('submissions.id',$request->id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();
        $result=Submissions::where('id',$request->id)->update(['cdi_override'=> $request->status]);
        $newObj =  Submissions::where('submissions.id',$request->id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();
        if($request->status == "Y")
            $submission_event = "CDI Override - <span class='text-danger'>N</span> TO <span class='text-success'>Y</span>";
        else
            $submission_event = "CDI Override - <span class='text-danger'>Y</span> TO <span class='text-success'>N</span>";
        

        if(isset($request->comment) && $request->comment != ''){
            $initSubmission->gender = "";
            $comment_data = [
                'submission_id' => $request->id,
                'user_id' => \Auth::user()->id,
                'comment' => $request->comment,
                'submission_event' => $submission_event
            ];
            SubmissionComment::create($comment_data);
            $newObj->gender = $request->comment;
        }
        $this->modelChanges($initSubmission,$newObj,"Submission - CDI Override");

        $display_outcome = SubmissionsStatusUniqueLog::count();
        if($display_outcome == 0 && $initSubmission->submission_status == "Pending")
        {
            $rsGradeData = SubmissionGrade::where("submission_id", $request->id)->first();
            if(!empty($rsGradeData) || ($request->status == "Y" && $initSubmission->grade_override == "Y"))
            {
                $ins = array();
                $ins['submission_status'] = "Active";
                $rsD = Submissions::where("id", $request->id)->update($ins);
            }
        }

        if(isset($result))
        {
            return json_encode(true);
        }
        else {
            return json_encode(false);
        }
    }

    public function overrideGrade(Request $request)
    {
        $initSubmission = Submissions::where('submissions.id',$request->id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();
        if($request->status == "Y")
            $submission_event = "Academic Grade Override - <span class='text-danger'>N</span> TO <span class='text-success'>Y</span>";
        else
            $submission_event = "Academic Grade Override - <span class='text-danger'>Y</span> TO <span class='text-success'>N</span>";

        $result=Submissions::where('id',$request->id)->update(['grade_override'=> $request->status]);
        $newObj =  Submissions::where('submissions.id',$request->id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();

        if(isset($request->comment) && $request->comment != ''){
            $initSubmission->gender = "";
            $comment_data = [
                'submission_id' => $request->id,
                'user_id' => \Auth::user()->id,
                'comment' => $request->comment,
                'submission_event' => $submission_event
            ];
            SubmissionComment::create($comment_data);
            $newObj->gender = $request->comment;
        }
        $this->modelChanges($initSubmission,$newObj,"Submission - Grade Override");

        $display_outcome = SubmissionsStatusUniqueLog::count();
        if($display_outcome == 0 && $initSubmission->submission_status == "Pending")
        {
     
            $rsCDIData = SubmissionConductDisciplinaryInfo::where("submission_id", $request->id)->first();
            if(!empty($rsCDIData) || ($request->status == "Y" && $initSubmission->cdi_override == "Y"))
            {
                $ins = array();
                $ins['submission_status'] = "Active";
                $rsD = Submissions::where("id", $request->id)->update($ins);
            }
        }

        if(isset($result))
        {
            return json_encode(true);
        }
        else {
            return json_encode(false);
        }

    }
    

    public function fetchProgramGrade($first_program_id=0, $second_program_id=0)
    {
        if($first_program_id == 0 && $second_program_id == 0)
        {
             $data = Submissions::select(DB::raw("DISTINCT(next_grade)"))->orderByDesc("next_grade")->where("district_id", Session::get("district_id"))->get();
        }
        else
        {
            $data = Submissions::where(function($q) use ($first_program_id, $second_program_id) {
                if($first_program_id == 0 && $second_program_id != 0)
                {
                    $q->where("second_choice_program_id", $second_program_id);
                }
                elseif($second_program_id == 0 && $first_program_id != 0)
                {
                    $q->where("first_choice_program_id", $first_program_id);
                }
                else
                {
                    $q->where("second_choice_program_id", $second_program_id)->orWhere('first_choice_program_id', $first_program_id);
                }
            })->where("district_id", Session::get("district_id"))->select(DB::raw("DISTINCT(next_grade)"))->orderByDesc("next_grade")->get();
        }
        if(!empty($data))
        {
                return json_encode($data);
        }
        else
        {
            $data = Submissions::select(DB::raw("DISTINCT(next_grade)"))->orderByDesc("next_grade")->where("district_id", Session::get("district_id"))->get();
            return json_encode($data);
        }
        
    }

    public function checkAvailability($choice_id, $grade)
    {
     //   $application_programs=ApplicationProgram::where('id',$choice_id)
      //      ->select('program_id')->first();
        $program_id = $choice_id;//$application_programs->program_id;
        $totalOffered = app('App\Modules\Waitlist\Controllers\WaitlistController')->get_offer_count($program_id, $grade, Session::get("district_id"), 1);



        $availability = Availability::where("program_id", $program_id)->where("grade", $grade)->first();
        $pending = $availability->available_seats - $totalOffered;
        echo "Available Seats are ".$pending.". Do you wish to continue ?"; 
    }

    public function updateNextgrade(Request $request, $id)
    {
        $req = $request->all();
        $initSubmission = Submissions::where('submissions.id',$id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();

        $next_grade = $req['manual_next_grade'];
        if($next_grade == "K")
            $current_grade = "PreK";
        elseif($next_grade == "1")
            $current_grade = "K";
        else
            $current_grade = $next_grade-1;

        if($initSubmission->next_grade != $next_grade)
        {
            $first_choice_program_id = $initSubmission->first_choice_program_id;
            $second_choice_program_id = $initSubmission->second_choice_program_id;

            $grade = Grade::where("name", $next_grade)->first();
            $grade_id = $grade->id;
            $rs = ApplicationProgram::where("application_id", $initSubmission->application_id)->where("program_id", $first_choice_program_id)->where("grade_id", $grade_id)->first();

            $first_choice = $rs->id;
            $second_choice = "";
            if($second_choice_program_id != 0)
            {
                $rs = ApplicationProgram::where("application_id", $initSubmission->application_id)->where("program_id", $second_choice_program_id)->where("grade_id", $grade_id)->first();
                $second_choice = $rs->id;
            }
            Submissions::where("id", $id)->update(array("next_grade"=>$next_grade, "current_grade"=>$current_grade));
            $newObj =  Submissions::where('submissions.id',$id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();
            $this->modelChanges($initSubmission, $newObj, "Submission - General");

            $data = array();
            $data['first_choice'] = $first_choice;
            if($second_choice != "")
            {
                $data['second_choice'] = $second_choice;
            }
            Submissions::where("id", $id)->update($data);

            $newdata = array();
            $newdata['old_grade'] = $initSubmission->next_grade;
            $newdata['updated_by'] = Auth::user()->id;
            $newdata['submission_id'] = $id;
            do
            {
                $code = Str::random(10);
                $user_code = SubmissionsFinalStatus::where('offer_slug', $code)->first();
            }
            while(!empty($user_code));  
            

            $statusData = array();
            $statusData['offer_slug'] = $code;
            $offer_data = SubmissionsFinalStatus::where("submission_id", $id)->first();
            if(!empty($offer_data))
            {
                
                if($offer_data->first_offer_status == "Accepted" || $offer_data->second_offer_status == "Accepted")
                {
                   
                    $newdata['offer_slug'] = $offer_data->offer_slug;
                    
                    //$statusData['first_offer_status'] = "Pending";
                    //$statusData['second_offer_status'] = "Pending";

                    if($offer_data->contract_status == "Signed")
                    {
                        $statusData['contract_status'] = "UnSigned";
                        $statusData['contract_signed_on'] = null;
                        $statusData['contract_status_by'] = 0;
                        $statusData['contract_mode'] = "Pending";
                        $statusData['contract_name'] = null;

                        $file_path = "resources/assets/admin/online_contract/Contract-".$initSubmission->confirmation_no.".pdf";
                        $new_file_name = $initSubmission->confirmation_no."_".strtotime(date("Y-m-d H:i:s"));
                        $new_path = "resources/assets/admin/online_contract/".$new_file_name.".pdf";
                        $newdata['old_contract_file_name'] = $new_file_name;
                        $newdata['old_contract_date'] = $offer_data->contract_signed_on;
                        $success = \File::copy($file_path, $new_path);
                    }
                    $statusData['first_offer_update_at'] = null;
                    $statusData['second_offer_update_at'] = null;
                    
                    

                    //Submissions::where("id", $id)->update(array("submission_status"=>"Offered and Accepted"));
                }
            }
            SubmissionsFinalStatus::where("submission_id", $id)->update($statusData);
            SubmissionGradeChange::create($newdata);    
            Submissions::where("id", $id)->update(array("manual_grade_change"=>"N"));    

        }        
        Session::flash("success", "Submission Updated successfully.");
        return redirect('admin/Submissions/edit/'.$id);


    }

    public function updateManualStatus(Request $request, $id)
    {
        $req = $request->all();
        $last_date_online_acceptance = $req['last_date_online_acceptance'];
        $last_date_offline_acceptance = $req['last_date_offline_acceptance'];

        $initSubmission = Submissions::where('submissions.id',$id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();

        $data = $data1 = array();
        
        $first_choice_final_status = $req['first_choice_final_status'];
        $data['submission_status'] = $first_choice_final_status;

        $data1['first_waitlist_for'] = $req['first_choice'];
        $data1['first_choice_final_status'] = $req['first_choice_final_status'];

        $first_offered = false;
        if($first_choice_final_status == "Offered")
        {
            $first_offered = true;
            $data['awarded_school'] = getProgramName($req['first_choice']);  
        }

        if(isset($req['second_choice']))
        {
            if($first_offered)
            {
                $data1['second_waitlist_for'] = 0;
                $data1['second_choice_final_status'] = "Pending";
                $req['second_offer_status'] = "NoAction";
            }
            else
            {
                $data1['second_waitlist_for'] = $req['second_choice'];
                $data1['second_choice_final_status'] = $req['second_choice_final_status'];
                if($req['second_choice_final_status'] == "Offered")
                {
                    $data['submission_status'] = "Offered";
                    $data['awarded_school'] = getProgramName($req['second_choice']);
                }

            }
        }
        else
        {
            if($initSubmission->second_choice_program_id != 0 && $first_offered)
            {
                $data1['second_waitlist_for'] = 0;
                $data1['second_choice_final_status'] = "Pending";
                $data1['second_offer_status'] = "NoAction";

            }
        }
        Submissions::where("id", $id)->update($data);
        

        do
        {
            $code = Str::random(10);
            $user_code1 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
            $user_code2 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
            $user_code3 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
        }
        while(!empty($user_code1) && !empty($user_code2) && !empty($user_code3));  
        $data1['offer_slug'] = $code;
        $data1['manually_updated'] = "Y";    
        $data1['last_date_online_acceptance'] = $last_date_online_acceptance;
        $data1['last_date_offline_acceptance'] = $last_date_offline_acceptance;
        $last_type = app('App\Modules\Waitlist\Controllers\WaitlistController')->check_last_process();
        if($last_type == "late_submission")
        {
            $update = "late_submission";
            $rs = LateSubmissionProcessLogs::orderBy("created_at", "DESC")->first();
            $version = $rs->version;
            $data1['version'] = $version;
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id"=>$id, "version"=>$version], $data1);
        }
        elseif($last_type == "waitlist")
        {
            $rs = WaitlistProcessLogs::orderBy("created_at", "DESC")->first();
            $version = $rs->version;
            $data1['version'] = $version;
            $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id"=>$id, "version"=>$version], $data1);
        }
        elseif($last_type == "regular")
        {
            $rs = SubmissionsFinalStatus::updateOrCreate(["submission_id"=>$id], $data1);
        }

        

        $newObj =  Submissions::where('submissions.id',$id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();
        $this->modelChanges($initSubmission, $newObj, "Submission - General");

        

        Session::flash("success", "Submission Updated successfully.");
        return redirect('admin/Submissions/edit/'.$id);

    }


    public function sendGeneralCommunicationEmailPost(Request $request, $type, $id)
    {
        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where('application.enrollment_id', Session::get('enrollment_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();
        $district_id = Session::get('district_id');

        $last_date_online_acceptance = $last_date_offline_acceptance = "";

        if($type == "Waitlist")
        {
            $rs = DistrictConfiguration::where("name", "last_date_waitlist_online_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            if(!empty($rs))
                $last_date_online_acceptance = getDateTimeFormat($rs->value);

            $rs = DistrictConfiguration::where("name", "last_date_waitlist_offline_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            if(!empty($rs))
               $last_date_offline_acceptance = getDateTimeFormat($rs->value);
            $submissions = Submissions::where('submissions.id', $id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->orderBy("submissions_waitlist_final_status.created_at", "desc")
                ->first(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'last_date_online_acceptance', 'last_date_offline_acceptance', 'offer_slug']);
             $cdata = WaitlistEditCommunication::where("status", "Offered")->first();

        }
        elseif($type == "LateSubmission")
        {
            $rs = DistrictConfiguration::where("name", "last_date_late_submission_online_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            if(!empty($rs))
                $last_date_online_acceptance = getDateTimeFormat($rs->value);

            $rs = DistrictConfiguration::where("name", "last_date_late_submission_offline_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            if(!empty($rs))
               $last_date_offline_acceptance = getDateTimeFormat($rs->value);
            $submissions = Submissions::where('submissions.id', $id)
                                ->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")
                                ->orderBy("late_submissions_final_status.id", "desc")
                ->first(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'last_date_online_acceptance', 'last_date_offline_acceptance', 'offer_slug']);

             $cdata = LateSubmissionEditCommunication::where("status", "Offered")->first();

        }
        else
        {
            $rs = DistrictConfiguration::where("name", "last_date_online_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            $last_date_online_acceptance = getDateTimeFormat($rs->value);

            $rs = DistrictConfiguration::where("name", "last_date_offline_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            $last_date_offline_acceptance = getDateTimeFormat($rs->value);
            $submissions = Submissions::where('submissions.id', $id)
                                ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                                ->orderBy("next_grade")
                ->first(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'last_date_online_acceptance', 'last_date_offline_acceptance', 'offer_slug']);
             $cdata = EditCommunication::where("status", "Offered")->first();

        }
        $value = $submissions;
        $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();


        $tmp = array();
        $tmp['id'] = $value->id;
        $tmp['student_id'] = $value->student_id;
        $tmp['confirmation_no'] = $value->confirmation_no;
        $tmp['name'] = $value->first_name." ".$value->last_name;
        $tmp['first_name'] = $value->first_name;
        $tmp['last_name'] = $value->last_name;
        $tmp['current_grade'] = $value->current_grade;
        $tmp['grade'] = $tmp['next_grade'] = $value->next_grade;
        $tmp['current_school'] = $value->current_school;
        $tmp['zoned_school'] = $value->zoned_school;
        $tmp['created_at'] = getDateFormat($value->created_at);
        $tmp['first_choice'] = getProgramName($value->first_choice_program_id);
        $tmp['second_choice'] = getProgramName($value->second_choice_program_id);

        if($value->first_choice_final_status == "Offered")
        {
            $program_id = $value->first_choice_program_id;
        }
        else
        {
            $program_id = $value->second_choice_program_id;
        }

        $tmp['program_name'] = getProgramName($program_id);
        $tmp['program_name_with_grade'] = getProgramName($program_id) . " - Grade " . $tmp['next_grade'];

        $tmp['offer_program'] = getProgramName($program_id);
        $tmp['offer_program_with_grade'] = getProgramName($program_id). " - Grade ".$value->next_grade;

        

        $tmp['waitlist_program_1'] = "";
        $tmp['waitlist_program_1_with_grade'] = "";
        $tmp['waitlist_program_2'] = "";
        $tmp['waitlist_program_2_with_grade'] = "";


        $tmp['birth_date'] = getDateFormat($value->birthday);
        $tmp['student_name'] = $value->first_name." ".$value->last_name;
        $tmp['parent_name'] = $value->parent_first_name." ".$value->parent_last_name;
        $tmp['parent_email'] = $value->parent_email;
        $tmp['student_id'] = $value->student_id;
        $tmp['parent_email'] = $value->parent_email;
        $tmp['student_id'] = $value->student_id;
        $tmp['submission_date'] = getDateTimeFormat($value->created_at);
        $tmp['transcript_due_date'] = getDateTimeFormat($application_data1->transcript_due_date);
        $tmp['application_url'] = url('/');
        $tmp['signature'] = get_signature('email_signature');
        $tmp['school_year'] = $application_data1->school_year;
        $tmp['enrollment_period'] = $tmp['school_year'];
        $t1 = explode("-", $tmp['school_year']);
        $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
        $tmp['next_year'] = date("Y")+1;
        if($value->offer_slug != "")
        {
            $tmp['offer_link'] = url('/Offers/'.$value->offer_slug);
        }
        else
        {
            $tmp['offer_link'] = "";
        }

        if($value->last_date_online_acceptance != '')
        {
            $tmp['online_offer_last_date'] = getDateTimeFormat($value->last_date_online_acceptance);
            $tmp['offline_offer_last_date'] = getDateTimeFormat($value->last_date_offline_acceptance);
        }
        else
        {
            $tmp['online_offer_last_date'] = $last_date_online_acceptance;
            $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;
        }

        $msg = find_replace_string($request->mail_body, $tmp);
        $msg = str_replace("{","",$msg);
        $msg = str_replace("}","",$msg);
        $tmp['msg'] = $msg;

        $msg = find_replace_string($cdata->mail_subject,$tmp);
        $msg = str_replace("{","",$msg);
        $msg = str_replace("}","",$msg);
        $tmp['subject'] = $msg;
        
        $tmp['email'] = $value->parent_email;
        $tmp['module'] = "Manual Offer Email";
        $student_data[] = array($value->id, $tmp['name'], $tmp['parent_name'], $tmp['parent_email'], $tmp['grade']);

        sendMail($tmp, true);
        if($type=="Waitlist")
        {
            $rs = WaitlistProcessLogs::orderBy("created_at", "DESC")->first();
            $version = $rs->version;
            SubmissionsWaitlistFinalStatus::where("submission_id", $value->id)->where("version", $version)->update(array("communication_sent"=>"Y",'communication_text' => $msg));
        }
        elseif($type=="LateSubmission")
        {
            $rs = LateSubmissionProcessLogs::orderBy("created_at", "DESC")->first();
            $version = $rs->version;
            LateSubmissionFinalStatus::where("submission_id", $value->id)->where("version", $version)->update(array("communication_sent"=>"Y",'communication_text' => $msg));

        }
        else
        {
            SubmissionsFinalStatus::where("submission_id", $value->id)->update(array("communication_sent"=>"Y",'communication_text' => $msg));
            
        }

        
        
        Session::flash("success", "Mail sent successfully.");
        return redirect("/admin/Submissions/edit/".$id);


    }
    public function sendGeneralCommunicationEmail($type, $id, $preview="")
    {
        $district_id = Session::get('district_id');

        $last_date_online_acceptance = $last_date_offline_acceptance = "";

        
        if($type == "Waitlist")
        {
            $rs = DistrictConfiguration::where("name", "last_date_waitlist_online_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            if(!empty($rs))
                $last_date_online_acceptance = getDateTimeFormat($rs->value);

            $rs = DistrictConfiguration::where("name", "last_date_waitlist_offline_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            if(!empty($rs))
               $last_date_offline_acceptance = getDateTimeFormat($rs->value);
            $submissions = Submissions::where('submissions.id', $id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->orderBy("submissions_waitlist_final_status.created_at", "desc")
                ->first(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'last_date_online_acceptance', 'last_date_offline_acceptance', 'offer_slug']);
             $cdata = WaitlistEditCommunication::where("status", "Offered")->first();

        }
        elseif($type == "LateSubmission")
        {
            $rs = DistrictConfiguration::where("name", "last_date_late_submission_online_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            if(!empty($rs))
                $last_date_online_acceptance = getDateTimeFormat($rs->value);

            $rs = DistrictConfiguration::where("name", "last_date_late_submission_offline_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            if(!empty($rs))
               $last_date_offline_acceptance = getDateTimeFormat($rs->value);
            $submissions = Submissions::where('submissions.id', $id)
                                ->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")
                                ->orderBy("late_submissions_final_status.id", "desc")
                ->first(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'last_date_online_acceptance', 'last_date_offline_acceptance', 'offer_slug']);
             $cdata = LateSubmissionEditCommunication::where("status", "Offered")->first();

        }
        else
        {
            $rs = DistrictConfiguration::where("name", "last_date_online_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            $last_date_online_acceptance = getDateTimeFormat($rs->value);

            $rs = DistrictConfiguration::where("name", "last_date_offline_acceptance")->where('enrollment_id', Session::get('enrollment_id'))->select("value")->first();
            $last_date_offline_acceptance = getDateTimeFormat($rs->value);
            $submissions = Submissions::where('submissions.id', $id)
                                ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                                ->orderBy("next_grade")
                ->first(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'last_date_online_acceptance', 'last_date_offline_acceptance', 'offer_slug']);
             $cdata = EditCommunication::where("status", "Offered")->first();

        }
        $value = $submissions;
        $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();


        $tmp = array();
        $tmp['id'] = $value->id;
        $tmp['student_id'] = $value->student_id;
        $tmp['confirmation_no'] = $value->confirmation_no;
        $tmp['name'] = $value->first_name." ".$value->last_name;
        $tmp['first_name'] = $value->first_name;
        $tmp['last_name'] = $value->last_name;
        $tmp['current_grade'] = $value->current_grade;
        $tmp['grade'] = $tmp['next_grade'] = $value->next_grade;
        $tmp['current_school'] = $value->current_school;
        $tmp['zoned_school'] = $value->zoned_school;
        $tmp['created_at'] = getDateFormat($value->created_at);
        $tmp['first_choice'] = getProgramName($value->first_choice_program_id);
        $tmp['second_choice'] = getProgramName($value->second_choice_program_id);

        if($value->first_choice_final_status == "Offered")
        {
            $program_id = $value->first_choice_program_id;
        }
        else
        {
            $program_id = $value->second_choice_program_id;
        }

        $tmp['program_name'] = getProgramName($program_id);
        $tmp['program_name_with_grade'] = getProgramName($program_id) . " - Grade " . $tmp['next_grade'];

        $tmp['offer_program'] = getProgramName($program_id);
        $tmp['offer_program_with_grade'] = getProgramName($program_id). " - Grade ".$value->next_grade;

        

        $tmp['waitlist_program_1'] = "";
        $tmp['waitlist_program_1_with_grade'] = "";
        $tmp['waitlist_program_2'] = "";
        $tmp['waitlist_program_2_with_grade'] = "";


        $tmp['birth_date'] = getDateFormat($value->birthday);
        $tmp['student_name'] = $value->first_name." ".$value->last_name;
        $tmp['parent_name'] = $value->parent_first_name." ".$value->parent_last_name;
        $tmp['parent_email'] = $value->parent_email;
        $tmp['student_id'] = $value->student_id;
        $tmp['parent_email'] = $value->parent_email;
        $tmp['student_id'] = $value->student_id;
        $tmp['submission_date'] = getDateTimeFormat($value->created_at);
        $tmp['transcript_due_date'] = getDateTimeFormat($application_data1->transcript_due_date);
        $tmp['application_url'] = url('/');
        $tmp['signature'] = get_signature('email_signature');
        $tmp['school_year'] = $application_data1->school_year;
        $tmp['enrollment_period'] = $tmp['school_year'];
        $t1 = explode("-", $tmp['school_year']);
        $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
        $tmp['next_year'] = date("Y")+1;
        if($value->offer_slug != "")
        {
            $tmp['offer_link'] = url('/Offers/'.$value->offer_slug);
        }
        else
        {
            $tmp['offer_link'] = "";
        }
        if($value->last_date_online_acceptance != '')
        {
            $tmp['online_offer_last_date'] = getDateTimeFormat($value->last_date_online_acceptance);
            $tmp['offline_offer_last_date'] = getDateTimeFormat($value->last_date_offline_acceptance);
        }
        else
        {
            $tmp['online_offer_last_date'] = $last_date_online_acceptance;
            $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;
        }

        $msg = find_replace_string($cdata->mail_body,$tmp);
        $msg = str_replace("{","",$msg);
        $msg = str_replace("}","",$msg);
        $tmp['msg'] = $msg;

        $msg = find_replace_string($cdata->mail_subject,$tmp);
        $msg = str_replace("{","",$msg);
        $msg = str_replace("}","",$msg);
        $tmp['subject'] = $msg;
        
        $tmp['email'] = $value->parent_email;
        $student_data[] = array($value->id, $tmp['name'], $tmp['parent_name'], $tmp['parent_email'], $tmp['grade']);

        if($preview != "" && $preview != "Grade")
        {
            $msg = $tmp['msg'];
            return view("Submissions::preview_offer_email",compact('msg', "type", "id"));
        }
        else
        {
            sendMail($tmp);
            SubmissionsFinalStatus::where("submission_id", $value->id)->update(array("communication_sent"=>"Y"));
            SubmissionsWaitlistFinalStatus::where("submission_id", $value->id)->update(array("communication_sent"=>"Y"));
            Session::flash("success", "Mail sent successfully.");
            return redirect("/admin/Submissions/edit/".$id);
        }


    }


    public function sendCommunicationEmail($id, $preview="")
    {
        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();
        $district_id = Session::get('district_id');

        $last_date_online_acceptance = $last_date_offline_acceptance = "";
        $rs = DistrictConfiguration::where("name", "last_date_online_acceptance")->select("value")->first();
        $last_date_online_acceptance = getDateTimeFormat($rs->value);

        $rs = DistrictConfiguration::where("name", "last_date_offline_acceptance")->select("value")->first();
        $last_date_offline_acceptance = getDateTimeFormat($rs->value);

        $submission = Submissions::where("id", $id)->first();
        if($submission->first_choice != "" && $submission->second_choice != "")
            $status = "Offered and Waitlisted";
        else
            $status = "Offered";
        
        $cdata = EditCommunication::where("status", $status)->first();

        
        if($status == "Offered and Waitlisted")
        {
            $submissions = Submissions::where('submissions.id', $id)
                                ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                                ->orderBy("next_grade")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'last_date_online_acceptance', 'last_date_offline_acceptance', 'offer_slug']);
        }
        elseif($status == "Offered")
        {
            $submissions = Submissions::where('submissions.id', $id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'offer_slug', 'last_date_online_acceptance', 'last_date_offline_acceptance']);
        }
        $student_data = array();
        foreach($submissions as $key=>$value)
        {
            $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();
        
            $generated = false;
            if(($value->first_choice_final_status == $status && $status == "Offered") || ($value->first_choice_final_status == "Offered" && $status == "Offered and Waitlisted") || ($value->first_choice_final_status == $status))
            {
                $generated = true;
                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['student_id'] = $value->student_id;
                $tmp['confirmation_no'] = $value->confirmation_no;
                $tmp['name'] = $value->first_name." ".$value->last_name;
                $tmp['first_name'] = $value->first_name;
                $tmp['last_name'] = $value->last_name;
                $tmp['current_grade'] = $value->current_grade;
                $tmp['grade'] = $tmp['next_grade'] = $value->next_grade;
                $tmp['current_school'] = $value->current_school;
                $tmp['zoned_school'] = $value->zoned_school;
                $tmp['created_at'] = getDateFormat($value->created_at);
                $tmp['first_choice'] = getProgramName($value->first_choice_program_id);
                $tmp['second_choice'] = getProgramName($value->second_choice_program_id);
                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];

                $tmp['offer_program'] = getProgramName($value->first_choice_program_id);
                $tmp['offer_program_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                if($value->second_choice_program_id != 0)
                {
                    $tmp['waitlist_program'] = getProgramName($value->second_choice_program_id);
                    $tmp['waitlist_program_with_grade'] = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;
                }
                else
                {
                    $tmp['waitlist_program'] = "";
                    $tmp['waitlist_program_with_grade'] = "";
                }

                if($status == "Waitlisted")
                {
                    $tmp['waitlist_program_1'] = getProgramName($value->first_choice_program_id);
                    $tmp['waitlist_program_1_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                    if($value->second_choice_program_id != 0)
                    {
                        $tmp['waitlist_program_2'] = getProgramName($value->second_choice_program_id);
                        $tmp['waitlist_program_2_with_grade'] = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;
                    }
                    else
                    {
                        $tmp['waitlist_program_2'] = "";
                        $tmp['waitlist_program_2_with_grade'] = "";
                    }
                }
                else
                {
                        $tmp['waitlist_program_1'] = "";
                        $tmp['waitlist_program_1_with_grade'] = "";
                        $tmp['waitlist_program_2'] = "";
                        $tmp['waitlist_program_2_with_grade'] = "";

                }




                $tmp['birth_date'] = getDateFormat($value->birthday);
                $tmp['student_name'] = $value->first_name." ".$value->last_name;
                $tmp['parent_name'] = $value->parent_first_name." ".$value->parent_last_name;
                $tmp['parent_email'] = $value->parent_email;
                $tmp['student_id'] = $value->student_id;
                $tmp['parent_email'] = $value->parent_email;
                $tmp['student_id'] = $value->student_id;
                $tmp['submission_date'] = getDateTimeFormat($value->created_at);
                $tmp['transcript_due_date'] = getDateTimeFormat($application_data1->transcript_due_date);
                $tmp['application_url'] = url('/');
                $tmp['signature'] = get_signature('email_signature');
                $tmp['school_year'] = $application_data1->school_year;
                $tmp['enrollment_period'] = $tmp['school_year'];
                $t1 = explode("-", $tmp['school_year']);
                $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
                $tmp['next_year'] = date("Y")+1;
                if(($status == "Offered"  || $status == "Offered and Waitlisted") && $value->offer_slug != "")
                {
                    $tmp['offer_link'] = url('/Offers/'.$value->offer_slug);
                }
                else
                {
                    $tmp['offer_link'] = "";
                }

                if($value->last_date_online_acceptance != '')
                {
                    $tmp['online_offer_last_date'] = getDateTimeFormat($value->last_date_online_acceptance);
                    $tmp['offline_offer_last_date'] = getDateTimeFormat($value->last_date_offline_acceptance);
                }
                else
                {
                    $tmp['online_offer_last_date'] = $last_date_online_acceptance;
                    $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;
                }

                $msg = find_replace_string($cdata->mail_body,$tmp);
                $msg = str_replace("{","",$msg);
                $msg = str_replace("}","",$msg);
                $tmp['msg'] = $msg;

                $msg = find_replace_string($cdata->mail_subject,$tmp);
                $msg = str_replace("{","",$msg);
                $msg = str_replace("}","",$msg);
                $tmp['subject'] = $msg;
                
                $tmp['email'] = $value->parent_email;
                $student_data[] = array($value->id, $tmp['name'], $tmp['parent_name'], $tmp['parent_email'], $tmp['grade']);

                if($preview != "" && $preview != "Grade")
                {
                     echo $tmp['msg'];exit;
                }
                else
                {
                    sendMail($tmp);
                    SubmissionsFinalStatus::where("submission_id", $value->id)->update(array("communication_sent"=>"Y"));
                    if($preview == "Grade")
                    {
                         Submissions::where("id", $value->id)->update(array("manual_grade_change"=>"N"));   
                    }

                }
            }

            if((($value->second_choice_final_status == $status && $status == "Offered") || ($value->second_choice_final_status == "Offered" && $status == "Offered and Waitlisted") || ($value->second_choice_final_status == $status)) && !$generated)
            {
                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['student_id'] = $value->student_id;
                $tmp['confirmation_no'] = $value->confirmation_no;
                $tmp['name'] = $value->first_name." ".$value->last_name;
                $tmp['first_name'] = $value->first_name;
                $tmp['last_name'] = $value->last_name;
                $tmp['current_grade'] = $value->current_grade;
                $tmp['grade'] = $tmp['next_grade'] = $value->next_grade;
                $tmp['current_school'] = $value->current_school;
                $tmp['zoned_school'] = $value->zoned_school;
                $tmp['created_at'] = getDateFormat($value->created_at);
                $tmp['first_choice'] = getProgramName($value->first_choice_program_id);
                $tmp['second_choice'] = getProgramName($value->second_choice_program_id);
                $tmp['program_name'] = getProgramName($value->second_choice_program_id);
                $tmp['program_name_with_grade'] = getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'];

                $tmp['birth_date'] = getDateFormat($value->birthday);
                $tmp['student_name'] = $value->first_name." ".$value->last_name;
                $tmp['parent_name'] = $value->parent_first_name." ".$value->parent_last_name;
                $tmp['parent_email'] = $value->parent_email;
                $tmp['student_id'] = $value->student_id;
                $tmp['parent_email'] = $value->parent_email;
                $tmp['student_id'] = $value->student_id;
                $tmp['submission_date'] = getDateTimeFormat($value->created_at);
                $tmp['transcript_due_date'] = getDateTimeFormat($application->transcript_due_date);
                $tmp['application_url'] = url('/');
                $tmp['signature'] = get_signature('email_signature');
                $tmp['school_year'] = $application_data->school_year;
                $tmp['enrollment_period'] = $tmp['school_year'];
                $t1 = explode("-", $tmp['school_year']);
                $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
                $tmp['next_year'] = date("Y")+1;
    
                $tmp['offer_program'] = getProgramName($value->second_choice_program_id);
                $tmp['offer_program_with_grade'] = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                if($value->first_choice_program_id != 0)
                {
                    $tmp['waitlist_program'] = getProgramName($value->first_choice_program_id);
                    $tmp['waitlist_program_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                }
                else
                {
                    $tmp['waitlist_program'] = "";
                    $tmp['waitlist_program_with_grade'] = "";
                }

                if($status == "Waitlisted")
                {
                    $tmp['waitlist_program_1'] = getProgramName($value->first_choice_program_id);
                    $tmp['waitlist_program_1_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                    if($value->second_choice_program_id != 0)
                    {
                        $tmp['waitlist_program_2'] = getProgramName($value->second_choice_program_id);
                        $tmp['waitlist_program_2_with_grade'] = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;
                    }
                    else
                    {
                        $tmp['waitlist_program_2'] = "";
                        $tmp['waitlist_program_2_with_grade'] = "";
                    }
                }
                else
                {
                        $tmp['waitlist_program_1'] = "";
                        $tmp['waitlist_program_1_with_grade'] = "";
                        $tmp['waitlist_program_2'] = "";
                        $tmp['waitlist_program_2_with_grade'] = "";

                }                    

                if(($status == "Offered"  || $status == "Offered and Waitlisted") && $value->offer_slug != "")
                {
                    $tmp['offer_link'] = url('/Offers/'.$value->offer_slug);
                }
                else
                {
                    $tmp['offer_link'] = "";
                }
                $tmp['program_name_with_grade'] = getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'];


                if($value->last_date_online_acceptance != '')
                {
                    $tmp['online_offer_last_date'] = getDateTimeFormat($value->last_date_online_acceptance);
                    $tmp['offline_offer_last_date'] = getDateTimeFormat($value->last_date_offline_acceptance);
                }
                else
                {
                    $tmp['online_offer_last_date'] = $last_date_online_acceptance;
                    $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;
                }



                $msg = find_replace_string($cdata->mail_body,$tmp);
                $msg = str_replace("{","",$msg);
                $msg = str_replace("}","",$msg);
                $tmp['msg'] = $msg;

                $msg = find_replace_string($cdata->mail_subject,$tmp);
                $msg = str_replace("{","",$msg);
                $msg = str_replace("}","",$msg);
                $tmp['subject'] = $msg;
                
                $tmp['email'] = $value->parent_email;
                $student_data[] = array($value->id, $tmp['name'], $tmp['parent_name'], $tmp['parent_email'], $tmp['grade']);

                if($preview != "" && $preview != "Grade")
                {
                    echo $tmp['msg'];exit;
                }
                else
                {
                    sendMail($tmp);
                    SubmissionsFinalStatus::where("submission_id", $value->id)->update(array("communication_sent"=>"Y"));
                    if($preview == "Grade")
                    {
                         Submissions::where("id", $value->id)->update(array("manual_grade_change"=>"N"));   
                    }
                }
                $countMail++;
            }

            
        }
        ob_end_clean();
        ob_start();

        $fileName =  "EditCustomCommunication-".strtotime(date("Y-m-d H:i:s")).".xlsx";
        $data = array();
        $data['district_id'] = Session::get("district_id");
        $data['communication_type'] = "Email";
        $data['mail_subject'] = $cdata->mail_subject;
        $data['mail_body'] = $cdata->mail_body;
        $data['status'] = $status;
        $data['file_name'] = $fileName;
        $data['total_count'] = count($student_data);
        $data['generated_by'] = Auth::user()->id;
        EditCommunicationLog::create($data);
        echo "Done";
    
    } 


    public function previewCommunicationEmail($id)
    {
        $data = EmailActivityLog::where("id", $id)->first();
        if(!empty($data))
        {
            return view("Submissions::preview_email",compact('data'));
        }
    }

    public function resendEmailCommunication($id)
    {
        //echo $id;exit;
        $edata = EmailActivityLog::where('id',$id)->first();
        $submission_id = $edata->submission_id;
        $program_id = $edata->program_id;
        $submission = Submissions::where('id', $submission_id)->first();
        // Email data
        if (isset($edata)) {
    
            $application_data = \App\Modules\Application\Models\Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where("application.status", "Y")->where("application.id", $submission->application_id)->select("application.*", "enrollments.school_year")->first();
            $logo = getDistrictLogo($application_data->display_logo) ?? '';

            $data['submission_id'] = $submission_id;
            $data['program_id'] = $program_id;
            $data['email_text'] = $data['email_body'] = $edata->email_body;
            $data['logo'] = $logo;
            $data['email_to'] = $edata->email_to;
            $data['email_subject'] = $edata->email_subject ?? '';

            try{
                \Mail::send('emails.index', ['data' => $data], function($message) use ($data){
                    $message->to($data['email_to']);    
                    $message->subject($data['email_subject']);
                });
                $data['status'] = "Success";
            }
            catch(\Exception $e){
                $data['status'] = $e;
                $msg = 'Mail not sent.';
            }
            $data['module'] = "Resend From Log";
            $data['user_id'] = Auth::user()->id;
            createEmailActivityLog($data);
            $msg = "Email resend successfully.";
        }
        Session::flash("warning", $msg);
        return redirect('admin/Submissions/edit/'.$submission_id);

    }    


    public function priorityCalculate($submission, $choice="first")
    {
        $str = $choice."_choice_program_id";
        $rank_counter = 0;
        $priority_data = [];
        if($submission->{$str} != 0 && $submission->{$str} != '')
        {
            $priority_details = DB::table("priorities")->join("program", "program.priority", "priorities.id")->join("priority_details", "priority_details.priority_id", "priorities.id")->where("program.id", $submission->{$str})->select('priorities.*', 'priority_details.*', 'program.feeder_priorities',  'program.magnet_priorities')->get();

            foreach ($priority_details as $count => $priority) {
                $flag = false;
                if ($priority->sibling == 'Y'){
                    if (isset($submission->{$choice.'_sibling'}) && $submission->{$choice.'_sibling'} != '') {
                        $priority_data['Sibling'] = 'Yes';
                    }
                    else
                    {
                        $priority_data['Sibling'] = 'No';
                    }
                }

                // Magnet Employee
                $flag = false;
                if ($priority->magnet_employee == 'Y'){
                    if (isset($submission->magnet_program_employee) && $submission->magnet_program_employee == 'Y') {
                        $priority_data['Magnet Employee'] = 'Yes';   
                    }
                    else
                    {
                        $priority_data['Magnet Employee'] = 'No';    
                    }
                }

                // Feeder
                $flag = false;
                if ($priority->feeder == 'Y'){
                    if($priority->feeder_priorities != '')
                        {
                            $tmp = explode(",", $priority->feeder_priorities);
                            foreach($tmp as $tk=>$tv)
                            {
                                $tmp[] = $tv;
                                $rsSchool = School::where("sis_name", $tv)->orWhere("name", $tv)->first();
                                if(!empty($rsSchool))
                                {
                                    $tmp[] = $rsSchool->sis_name;
                                    $tmp[] = $rsSchool->name;
                                }
                            }
                            
                            $field = "current_school";
                            if(in_array($submission->{$field}, $tmp)) 
                            {
                                $priority_data['Feeder'] = "Yes";
                            }
                            else 
                            {
                                $priority_data['Feeder'] = "No";
                            }
                        }
                        else
                        {
                            $priority_data['Feeder'] = "No";
                        }

                 }

                 // Magnet School
                if ($priority->magnet_student == 'Y'){
                    if($priority->magnet_priorities != '')
                    {
                        $tmp = explode(",", $priority->magnet_priorities);
                        foreach($tmp as $tk=>$tv)
                        {
                            $tmp[] = $tv;
                            $rsSchool = School::where("sis_name", $tv)->orWhere("name", $tv)->first();
                            if(!empty($rsSchool))
                            {
                                $tmp[] = $rsSchool->sis_name;
                                $tmp[] = $rsSchool->name;
                            }
                        }

                        if(in_array($submission->current_school, $tmp)) 
                        {
                            $priority_data['Magnet Student'] = "Yes";
                        }
                        else 
                        {
                            $priority_data['Magnet Student'] = "No";
                        }
                    }
                    else
                    {
                            $priority_data['Magnet Student'] = "No";
                    }


                 }
            }

        }
        return $priority_data;
    }

}
