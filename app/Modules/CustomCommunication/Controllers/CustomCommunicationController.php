<?php

namespace App\Modules\CustomCommunication\Controllers;

use App\Modules\School\Models\School;
use App\Modules\District\Models\District;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\School\Models\Grade;
use App\Modules\Application\Models\Application;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\Program\Models\Program;
use App\Modules\Submissions\Models\{Submissions,SubmissionGrade,SubmissionConductDisciplinaryInfo};
use App\Modules\CustomCommunication\Models\CustomCommunication;
use App\Modules\CustomCommunication\Models\CustomCommunicationData;
use Maatwebsite\Excel\Facades\Excel;
use App\Modules\CustomCommunication\Export\{CustomCommunicationEmails};
use Config;
use Session;
use DB;
use Validator;  
use PDF;
use Auth;
use Response;
use App\Modules\Submissions\Models\{SubmissionsFinalStatus,SubmissionsWaitlistFinalStatus,LateSubmissionFinalStatus};
use App\Modules\DistrictConfiguration\Models\DistrictConfiguration;
use App\Traits\AuditTrail;

class CustomCommunicationController extends Controller
{
    use AuditTrail;
	public function index()
	{
		$data = CustomCommunication::where("district_id", Session::get('district_id'))->where("enrollment_id", Session::get('enrollment_id'))->get();
		return view("CustomCommunication::index", compact('data'));
	}

    public function create()
    {
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $programs = Program::where("district_id", Session::get('district_id'))->where('status','Y')->orderBy('name')->get();
        $grades = Submissions::where("district_id", Session::get('district_id'))->orderBy('next_grade')->select(DB::raw("DISTINCT(next_grade)"))->get();
        $submission_status = Submissions::where("district_id", Session::get('district_id'))->orderBy('submission_status')->select(DB::raw("DISTINCT(submission_status)"))->get();

        return view("CustomCommunication::create", compact('enrollment','programs','grades', 'submission_status'));
    }

    public function edit($id)
    {
        $data = CustomCommunication::where("id", $id)->first();
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $programs = Program::where("district_id", Session::get('district_id'))->where('status','Y')->orderBy('name')->get();
        $grades = Submissions::where("district_id", Session::get('district_id'))->orderBy('next_grade')->select(DB::raw("DISTINCT(next_grade)"))->get();
        $submission_status = Submissions::where("district_id", Session::get('district_id'))->orderBy('submission_status')->select(DB::raw("DISTINCT(submission_status)"))->get();


        return view("CustomCommunication::edit", compact('enrollment','programs','grades', 'data', 'submission_status'));
    }

    public function store(Request $request)
    {
        //print_r($request->all());exit;
        $validateData = $request->validate([
            'template_name' =>'required|max:255',
            'enrollment_id' =>'required',
            'program' =>'required',
            'grade' =>'required',
            'submission_status' =>'required',
            //'mail_subject' =>'required',
            //'mail_body' =>'required',
            //'letter_subject' =>'required',
            //'letter_body' =>'required'
        ]);
        $req = $request->all();
        $data = array();
        $data['district_id'] = Session::get('district_id');
        $data['template_name'] = $req['template_name']; 
        $data['enrollment_id'] = $req['enrollment_id']; 
        $data['program'] = $req['program']; 
        $data['grade'] = $req['grade']; 
        $data['submission_status'] = $req['submission_status']; 
        if(isset($request->send_email_now) || isset($request->save_email))
        {
            $data['mail_subject'] = $req['mail_subject']; 
            $data['mail_body'] = $req['mail_body']; 
        }
        if(isset($request->generate_letter_now) || isset($request->save_letter))
        {
            $data['letter_body'] = $req['letter_body']; 
        }

        $id=CustomCommunication::create($data)->id;
        //$newObj=CustomCommunication::where('id',$id)->first();
        //$this->modelCreate($newObj,"custom_communication");

        if(isset($request->generate_letter_now))
        {
            return $this->generate_letter_now($id);
        }
        elseif(isset($request->send_email_now))
        {
            $this->send_email_now($id);
        }

        if (isset($id)) {
            Session::flash("success", "Custom Communication added successfully.");
        } else {
            Session::flash("error", "Please Try Again.");
        }
        return redirect('admin/CustomCommunication/edit/'.$id);

    }


    public function update(Request $request, $id)
    {
        //print_r($request->all());exit;
        $validateData = $request->validate([
            'template_name' =>'required|max:255',
            'enrollment_id' =>'required',
            'program' =>'required',
            'grade' =>'required',
            'submission_status' =>'required',
            //'mail_subject' =>'required',
            //'mail_body' =>'required',
            //'letter_subject' =>'required',
            //'letter_body' =>'required'
        ]);
        $req = $request->all();

        $data = array();
        $data['district_id'] = Session::get('district_id');
        $data['template_name'] = $req['template_name']; 
        $data['enrollment_id'] = $req['enrollment_id']; 
        $data['program'] = $req['program']; 
        $data['grade'] = $req['grade']; 
        $data['submission_status'] = $req['submission_status']; 
        if(isset($request->send_email_now) || isset($request->save_email))
        {
            $data['mail_subject'] = $req['mail_subject']; 
            $data['mail_body'] = $req['mail_body']; 
        }
        if(isset($request->generate_letter_now) || isset($request->save_letter))
        {
            $data['letter_body'] = $req['letter_body']; 
        }

        $initObj = CustomCommunication::where("id",$id)->first();
        $result=CustomCommunication::where("id",$id)->update($data);
        $newObj = CustomCommunication::where("id",$id)->first();
        //$this->modelChanges($initObj,$newObj,"custom-communication");

        if(isset($request->generate_letter_now))
        {
            return $this->generate_letter_now($id);
            Session::flash("success", "Custom Communication generated successfully.");
        }
        elseif(isset($request->send_email_now))
        {
            $this->send_email_now($id);
            Session::flash("success", "Custom Communication emails sent successfully.");
        }
        else
        {
             Session::flash("success", "Custom Communication updated successfully.");
        }

       
        return redirect('admin/CustomCommunication/edit/'.$id);
    }

    public function generate_letter_now($id, $preview=false)
    {
        set_time_limit(0);

        $cdata = CustomCommunication::where("id", $id)->first();
        $program_id = $cdata->program;
        $enrollment_id = $cdata->enrollment_id;
        $status = $cdata->submission_status;
        $grade = $cdata->grade;
        

        $last_type = app('App\Modules\Waitlist\Controllers\WaitlistController')->check_last_process();

        $online_date = "last_date_online_acceptance";
        $offline_date = "last_date_offline_acceptance";

        if($last_type == "waitlist")
        {
            $online_date = "last_date_late_submission_online_acceptance";
            $offline_date = "last_date_late_submission_offline_acceptance";
        }
        elseif($last_type == "waitlist")
        {
            $online_date = "last_date_waitlist_online_acceptance";
            $offline_date = "last_date_waitlist_offline_acceptance";
        }


        $rs = DistrictConfiguration::where("name", $online_date)->where("enrollment_id", $enrollment_id)->select("value")->first();
        if(!empty($rs))
            $last_date_online_acceptance = getDateTimeFormat($rs->value);
        else
            $last_date_online_acceptance = "";

        $rs = DistrictConfiguration::where("name", $offline_date)->where("enrollment_id", $enrollment_id)->select("value")->first();
         if(!empty($rs))
            $last_date_offline_acceptance = getDateTimeFormat($rs->value);
        else
            $last_date_offline_acceptance = "";


//        $application_data = Application::where('district_id', Session::get('district_id'))->where("status", "Y")->first();
        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

        $data = Submissions::where("submissions.district_id", Session::get("district_id"))->join("application", "application.id","submissions.application_id")->select("submissions.*")->where("application.enrollment_id", $enrollment_id);
        if($program_id != 0)
        {
            $data->where(function($q) use ($program_id) {
                $q->where("first_choice_program_id", $program_id)
                  ->orWhere("second_choice_program_id", $program_id)->get();
                });
        }
        if($grade != 'All')
        {
            $data->where('next_grade', $grade);
        }
        if($status != 'All')
        {
            if($status == "Offer Accepted and Contract Pending")
                $data->where('submission_status', "Offered and Accepted");
            elseif($status == "Pending Employee Verification")
            {
                $data->where(function($q) {
                    $q->where("mcp_employee", "Yes")->where(function($q1) {
                        $q1->whereNull('employee_first_name')->whereNull('employee_last_name')->whereNull('employee_id')->whereNull('work_location');
                    });
                });
                
            }
            else
                $data->where('submission_status', $status);
        }
        
        $final_data = $data->get();
        $student_data = array();
        foreach($final_data as $key=>$value)
        {
            //$application = Application::where('id', $value->application_id)->first();
            $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();

            if($last_type == "regular")
            {
                $subms = SubmissionsFinalStatus::where("submission_id", $value->id)->first();
            }
            elseif($last_type == "waitlist")
            {
                $subms = SubmissionsWaitlistFinalStatus::where("submission_id", $value->id)->where(function($q) {
                    $q->where("first_offer_status", "Accepted")->orWhere("second_offer_status", "Accepted")->get();
                })->first();
            }
            else
            {
                $subms = LateSubmissionFinalStatus::where("submission_id", $value->id)->where(function($q) {
                    $q->where("first_offer_status", "Accepted")->orWhere("second_offer_status", "Accepted")->get();
                })->first();
            }

            if($status == "Offer Accepted and Contract Pending")
            {
                if(!empty($subms1))
                {
                    if($subms1->contract_status != "UnSigned")
                    {
                        continue;
                    }
                }
                 if(!empty($subms))
                 {
                    if($subms->contract_status != "UnSigned")
                    {
                        continue;
                    }
                 }
                 else
                    continue;

            }

            $tmp = array();
            $tmp['id'] = $value->id;
            $tmp['student_id'] = $value->student_id;
            $tmp['confirmation_no'] = $value->confirmation_no;
            $tmp['name'] = $value->first_name." ".$value->last_name;
            $tmp['grade'] = $tmp['next_grade'] = $value->next_grade;
            $tmp['current_grade'] = $value->current_grade;
            $tmp['current_school'] = $value->current_school;
            $tmp['zoned_school'] = $value->zoned_school;
            $tmp['created_at'] = getDateFormat($value->created_at);
            $tmp['first_choice'] = getProgramName($value->first_choice_program_id);
            $tmp['second_choice'] = getProgramName($value->second_choice_program_id);
            $tmp['birth_date'] = getDateFormat($value->birthday);
            $tmp['student_name'] = $value->first_name." ".$value->last_name;
            $tmp['parent_name'] = $value->parent_first_name." ".$value->parent_last_name;
            $tmp['parent_email'] = $value->parent_email;
            $tmp['student_id'] = $value->student_id;
            $tmp['submission_date'] = getDateTimeFormat($value->created_at);
            $tmp['transcript_due_date'] = getDateTimeFormat($application_data1->transcript_due_date);
            $tmp['application_url'] = url('/');
            $tmp['signature'] = get_signature('letter_signature');

            $program_name = "";
            $program_name_with_grade = "";
            $offer_program = "";
            $offer_program_with_grade = "";
            $waitlist_program = "";
            $waitlist_program_with_grade = "";
            $waitlist_program_1 = "";
            $waitlist_program_2 = "";
            $waitlist_program_1_with_grade = "";
            $waitlist_program_2_with_grade = "";

            $tmp['program_name'] = getProgramName($value->first_choice_program_id);
            $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
            $tmp['parent_address'] = $value->address."<br>". $value->city.", ".$value->state." ".$value->zip;


            if($status != "Denied due to Incomplete Records" && $status != "Denied due to Ineligibility")
            {
                if(!empty($subms))
                {
                    if($subms->first_choice_final_status == "Offered")
                    {
                        $program_name = getProgramName($value->first_choice_program_id);
                        $program_name_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                        $offer_program = $program_name;
                        $offer_program_with_grade = $program_name_with_grade;

                        if($subms->second_choice_final_status == "Waitlisted")
                        {
                            $waitlist_program = getProgramName($value->second_choice_program_id);
                            $waitlist_program_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                        }
                    }
                    elseif($subms->second_choice_final_status == "Offered")
                    {
                        $program_name = getProgramName($value->second_choice_program_id);
                        $program_name_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;
                        $offer_program = $program_name;
                        $offer_program_with_grade = $program_name_with_grade;

                        if($subms->first_choice_final_status == "Waitlisted")
                        {
                            $waitlist_program = getProgramName($value->first_choice_program_id);
                            $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                        }

                    }
                    elseif($subms->first_choice_final_status == "Waitlisted" && $subms->second_choice_final_status == "Waitlisted")
                    {
                            $waitlist_program = getProgramName($value->first_choice_program_id);
                            $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                            $waitlist_program_1 = getProgramName($value->first_choice_program_id);
                            $waitlist_program_1_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                            $waitlist_program_2 = getProgramName($value->first_choice_program_id);
                            $waitlist_program_2_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                    }
                    elseif($subms->first_choice_final_status == "Waitlisted" && $subms->second_choice_final_status == "Pending")
                    {
                            $waitlist_program = getProgramName($value->first_choice_program_id);
                            $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                            $waitlist_program_1 = getProgramName($value->first_choice_program_id);
                            $waitlist_program_1_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                    }
                    elseif($subms->second_choice_final_status == "Waitlisted")
                    {
                            $waitlist_program = getProgramName($value->second_choice_program_id);
                            $waitlist_program_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                            $waitlist_program_2 = getProgramName($value->second_choice_program_id);
                            $waitlist_program_2_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                    }



                }
            }

            if($program_name != "")
            {
                $tmp['program_name'] = $program_name;
                $tmp['program_name_with_grade'] = $program_name_with_grade;
            }
            $tmp['offer_program'] = $offer_program;
            $tmp['offer_program_with_grade'] = $offer_program_with_grade;
            $tmp['waitlist_program'] = $waitlist_program;
            $tmp['waitlist_program_with_grade'] = $waitlist_program_with_grade;
            $tmp['waitlist_program_1'] = $waitlist_program_1;
            $tmp['waitlist_program_2'] = $waitlist_program_2;
            $tmp['waitlist_program_1_with_grade'] = $waitlist_program_1_with_grade;
            $tmp['waitlist_program_2_with_grade'] = $waitlist_program_2_with_grade;





            $tmp['school_year'] = $application_data1->school_year;
            $tmp['enrollment_period'] = $tmp['school_year'];
            $t1 = explode("-", $tmp['school_year']);
            $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
            $tmp['next_year'] = date("Y")+1;

            /* Offer link */
            $tmp['offer_link'] = "";
            $tmp['online_offer_last_date'] = $last_date_online_acceptance;
            $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;

            if(!empty($subms))
            {
                $tmp['offer_link'] = url('/Offers/'.$subms->offer_slug);
                $tmp['contract_link'] = url('/Offers/Contract/Fill/'.$subms->offer_slug);
                if($subms->last_date_online_acceptance != '')
                {
                    $tmp['online_offer_last_date'] = $subms->last_date_online_acceptance;
                    $tmp['offline_offer_last_date'] = $subms->last_date_offline_acceptance;
                }
            }

            $msg = find_replace_string($cdata->letter_body,$tmp);
            $msg = str_replace("{","",$msg);
            $msg = str_replace("}","",$msg);
            $tmp['letter_body'] = $msg;
            $student_data[] = $tmp;
        }

        if($preview == true)
        {
            $student_data = array();
            $tmp = array();
            $tmp['id'] = "9999";
            $tmp['student_id'] = "1234567890";
            $tmp['confirmation_no'] = "MAGNET-2122-00000";
            $tmp['name'] = "Johnson William";
            $tmp['grade'] = $tmp['next_grade'] = "8";
            $tmp['current_grade'] = "7";
            $tmp['current_school'] = "MCPSS Elementary";
            $tmp['zoned_school'] = "Zoned School";
            $tmp['created_at'] = getDateFormat(date("Y-m-d H:i:S"));
            $tmp['first_choice'] = "Magnet Program 1";
            $tmp['second_choice'] = "Magnet Program 2";
            $tmp['birth_date'] = getDateFormat(date("Y-m-d"));
            $tmp['student_name'] = "Johnson William";
            $tmp['parent_name'] = "Mark William";
            $tmp['parent_email'] = "mark.william@gmail.com";
            $tmp['student_id'] = "1234567890";
            $tmp['submission_date'] = getDateTimeFormat(date("Y-m-d H:i:S"));
            $tmp['transcript_due_date'] = getDateTimeFormat(date("Y-m-d H:i:S"));
            $tmp['signature'] = get_signature('letter_signature');
            $tmp['application_url'] = url('/');

            //$msg = strtr($cdata->letter_body,$tmp);
            $msg = $cdata->letter_body;
           // $msg = str_replace("{","",$msg);
            //$msg = str_replace("}","",$msg);
            $tmp['letter_body'] = $msg;
            $student_data[] = $tmp;

        }
        view()->share('student_data',$student_data);
        view()->share("application_data", $application_data);

        $fileName =  "CustomCommunication-".strtotime(date("Y-m-d H:i:s")) . '.pdf';
        $path = "resources/assets/admin/custom_communication";
        if($preview)
        {
            $pdf = PDF::loadView('CustomCommunication::letterview',['student_data','application_data']);
            $fileName = "preview.pdf";
            $pdf->save($path . '/' . $fileName);
            return response()->file($path."/".$fileName);
        }
        else
        {
            $pdf = PDF::loadView('CustomCommunication::letterview',['student_data','application_data']);
            $pdf->save($path . '/' . $fileName);

            $data = array();
            $data['template_id'] = $id;
            $data['total_count'] = count($student_data);
            $data['generated_by'] = Auth::user()->id;
            $data['file_name'] = $fileName;
            $data['type'] = "Letter";
            $data['status'] = $status;
            CustomCommunicationData::create($data);
            return $pdf->download($fileName);
        }
        
    }


    public function generate_letter_now_individual(Request $request)
    {
        set_time_limit(0);
        $req = $request->all();

        $last_date_online_acceptance = $last_date_offline_acceptance = "";
        $rs = DistrictConfiguration::where("name", "last_date_online_acceptance")->select("value")->first();
        $last_date_online_acceptance = getDateTimeFormat($rs->value);

        $rs = DistrictConfiguration::where("name", "last_date_offline_acceptance")->select("value")->first();
        $last_date_offline_acceptance = getDateTimeFormat($rs->value);


        //$application_data = Application::where('district_id', Session::get('district_id'))->where("status", "Y")->first();
        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();
        $id = $req['id'];
        if (isset($request->save))
        {
            $data = ['letter_body' => $req['letter_body']];
            Submissions::where("id", $req['id'])->update($data);
            Session::flash("success", "Custom Communication saved successfully.");
            return redirect('admin/Submissions/edit/'.$req['id']);
        }
        if (isset($request->preview))
        {
            $student_data = array();
            $tmp = array();
            $tmp['id'] = "9999";
            $tmp['student_id'] = "1234567890";
            $tmp['confirmation_no'] = "MAGNET-2122-00000";
            $tmp['name'] = "Johnson William";
            $tmp['grade'] = $tmp['next_grade'] = "8";
            $tmp['current_grade'] = "7";
            $tmp['current_school'] = "MCPSS Elementary";
            $tmp['zoned_school'] = "Zoned School";
            $tmp['created_at'] = getDateFormat(date("Y-m-d H:i:S"));
            $tmp['first_choice'] = "Magnet Program 1";
            $tmp['second_choice'] = "Magnet Program 2";
            $tmp['birth_date'] = getDateFormat(date("Y-m-d"));
            $tmp['student_name'] = "Johnson William";
            $tmp['parent_name'] = "Mark William";
            $tmp['parent_email'] = "mark.william@gmail.com";
            $tmp['student_id'] = "1234567890";
            $tmp['submission_date'] = getDateTimeFormat(date("Y-m-d H:i:S"));
            $tmp['transcript_due_date'] = getDateTimeFormat(date("Y-m-d H:i:S"));
            $tmp['signature'] = get_signature('letter_signature');
            $tmp['application_url'] = url('/');

            //$msg = strtr($cdata->letter_body,$tmp);
            $msg = $req['letter_body'];
           // $msg = str_replace("{","",$msg);
            //$msg = str_replace("}","",$msg);
            $tmp['letter_body'] = $msg;
            $student_data[] = $tmp;
        }
        else
        {
            

            $final_data = Submissions::where("submissions.district_id", Session::get("district_id"))->join("application", "application.id","submissions.application_id")->select("submissions.*")->where('submissions.id', $id)->get();

            $data = ['letter_body' => $req['letter_body']];
            Submissions::where("id", $req['id'])->update($data);

            $student_data = array();
            foreach($final_data as $key=>$value)
            {
                //$application = Application::where('id', $value->application_id)->first();
                $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();

                $subms = SubmissionsFinalStatus::where("submission_id", $value->id)->first();

                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['student_id'] = $value->student_id;
                $tmp['confirmation_no'] = $value->confirmation_no;
                $tmp['name'] = $value->first_name." ".$value->last_name;
                $tmp['grade'] = $tmp['next_grade'] = $value->next_grade;
                $tmp['current_grade'] = $value->current_grade;
                $tmp['current_school'] = $value->current_school;
                $tmp['zoned_school'] = $value->zoned_school;
                $tmp['created_at'] = getDateFormat($value->created_at);
                $tmp['first_choice'] = getProgramName($value->first_choice_program_id);
                $tmp['second_choice'] = getProgramName($value->second_choice_program_id);
                $tmp['birth_date'] = getDateFormat($value->birthday);
                $tmp['student_name'] = $value->first_name." ".$value->last_name;
                $tmp['parent_name'] = $value->parent_first_name." ".$value->parent_last_name;
                $tmp['parent_email'] = $value->parent_email;
                $tmp['student_id'] = $value->student_id;
                $tmp['submission_date'] = getDateTimeFormat($value->created_at);
                $tmp['transcript_due_date'] = getDateTimeFormat($application_data1->transcript_due_date);
                $tmp['application_url'] = url('/');

                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
                $tmp['parent_address'] = $value->address."<br>". $value->city.", ".$value->state." ".$value->zip;

                $tmp['school_year'] = $application_data1->school_year;
                $tmp['enrollment_period'] = $tmp['school_year'];
                $t1 = explode("-", $tmp['school_year']);
                $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
                $tmp['next_year'] = date("Y")+1;

                $program_name = "";
                $program_name_with_grade = "";
                $offer_program = "";
                $offer_program_with_grade = "";
                $waitlist_program = "";
                $waitlist_program_with_grade = "";
                $waitlist_program_1 = "";
                $waitlist_program_2 = "";
                $waitlist_program_1_with_grade = "";
                $waitlist_program_2_with_grade = "";

                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];



                if($value->submission_status != "Denied due to Incomplete Records" && $value->submission_status != "Denied due to Ineligibility")
                {
                    if(!empty($subms))
                    {
                        if($subms->first_choice_final_status == "Offered")
                        {
                            $program_name = getProgramName($value->first_choice_program_id);
                            $program_name_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                            $offer_program = $program_name;
                            $offer_program_with_grade = $program_name_with_grade;

                            if($subms->second_choice_final_status == "Waitlisted")
                            {
                                $waitlist_program = getProgramName($value->second_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                            }
                        }
                        elseif($subms->second_choice_final_status == "Offered")
                        {
                            $program_name = getProgramName($value->second_choice_program_id);
                            $program_name_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;
                            $offer_program = $program_name;
                            $offer_program_with_grade = $program_name_with_grade;

                            if($subms->first_choice_final_status == "Waitlisted")
                            {
                                $waitlist_program = getProgramName($value->first_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                            }

                        }
                        elseif($subms->first_choice_final_status == "Waitlisted" && $subms->second_choice_final_status == "Waitlisted")
                        {
                                $waitlist_program = getProgramName($value->first_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_1 = getProgramName($value->first_choice_program_id);
                                $waitlist_program_1_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_2 = getProgramName($value->first_choice_program_id);
                                $waitlist_program_2_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                        }
                        elseif($subms->first_choice_final_status == "Waitlisted" && $subms->second_choice_final_status == "Pending")
                        {
                                $waitlist_program = getProgramName($value->first_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_1 = getProgramName($value->first_choice_program_id);
                                $waitlist_program_1_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                        }
                        elseif($subms->second_choice_final_status == "Waitlisted")
                        {
                                $waitlist_program = getProgramName($value->second_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_2 = getProgramName($value->second_choice_program_id);
                                $waitlist_program_2_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                        }



                    }
                }

                if($program_name != "")
                {
                    $tmp['program_name'] = $program_name;
                    $tmp['program_name_with_grade'] = $program_name_with_grade;
                }
                $tmp['offer_program'] = $offer_program;
                $tmp['offer_program_with_grade'] = $offer_program_with_grade;
                $tmp['waitlist_program'] = $waitlist_program;
                $tmp['waitlist_program_with_grade'] = $waitlist_program_with_grade;
                $tmp['waitlist_program_1'] = $waitlist_program_1;
                $tmp['waitlist_program_2'] = $waitlist_program_2;
                $tmp['waitlist_program_1_with_grade'] = $waitlist_program_1_with_grade;
                $tmp['waitlist_program_2_with_grade'] = $waitlist_program_2_with_grade;

                /* Offer link */
                $tmp['offer_link'] = "";
                $tmp['online_offer_last_date'] = $last_date_online_acceptance;
                $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;

                if(!empty($subms))
                {
                    $tmp['offer_link'] = url('/Offers/'.$subms->offer_slug);
                    $tmp['contract_link'] = url('/Offers/Contract/Fill/'.$subms->offer_slug);

                    if($subms->last_date_online_acceptance != '')
                    {
                        $tmp['online_offer_last_date'] = $subms->last_date_online_acceptance;
                        $tmp['offline_offer_last_date'] = $subms->last_date_offline_acceptance;
                    }
                }


                $tmp['signature'] = get_signature('letter_signature');
                $msg = find_replace_string($req['letter_body'],$tmp);
                $msg = str_replace("{","",$msg);
                $msg = str_replace("}","",$msg);
                $tmp['letter_body'] = $msg;
                $student_data[] = $tmp;
            }
        }
        echo count($student_data);exit;
        view()->share('student_data',$student_data);
        view()->share("application_data", $application_data);

        $fileName =  "CustomCommunication-".strtotime(date("Y-m-d H:i:s")) . '.pdf';
        $path = "resources/assets/admin/custom_communication";
        if (isset($request->preview))
        {
            $pdf = PDF::loadView('CustomCommunication::letterview',['student_data','application_data']);
            $fileName = "preview.pdf";
            $pdf->save($path . '/' . $fileName);
            return response()->file($path."/".$fileName);
        }
        else
        {
            $pdf = PDF::loadView('CustomCommunication::letterview',['student_data','application_data']);
            $pdf->save($path . '/' . $fileName);

            $data = array();
            $data['template_id'] = 0;
            $data['submission_id'] = $id;
            $data['total_count'] = 1;
            $data['generated_by'] = Auth::user()->id;
            $data['file_name'] = $fileName;
            $data['type'] = "Letter";
            CustomCommunicationData::create($data);
            return $pdf->download($fileName);
        }
        
    }

    public function send_email_now($id, $preview=false)
    {
        $cdata = CustomCommunication::where("id", $id)->first();
        $program_id = $cdata->program;
        $enrollment_id = $cdata->enrollment_id;
        $status = $cdata->submission_status;
        $grade = $cdata->grade;

        $last_type = app('App\Modules\Waitlist\Controllers\WaitlistController')->check_last_process();

        $online_date = "last_date_online_acceptance";
        $offline_date = "last_date_offline_acceptance";

        if($last_type == "waitlist")
        {
            $online_date = "last_date_late_submission_online_acceptance";
            $offline_date = "last_date_late_submission_offline_acceptance";
        }
        elseif($last_type == "waitlist")
        {
            $online_date = "last_date_waitlist_online_acceptance";
            $offline_date = "last_date_waitlist_offline_acceptance";
        }


        $rs = DistrictConfiguration::where("name", $online_date)->where("enrollment_id", $enrollment_id)->select("value")->first();
        if(!empty($rs))
            $last_date_online_acceptance = getDateTimeFormat($rs->value);
        else
            $last_date_online_acceptance = "";

        $rs = DistrictConfiguration::where("name", $offline_date)->where("enrollment_id", $enrollment_id)->select("value")->first();
         if(!empty($rs))
            $last_date_offline_acceptance = getDateTimeFormat($rs->value);
        else
            $last_date_offline_acceptance = "";


        /*$last_date_online_acceptance = $last_date_offline_acceptance = "";
        $rs = DistrictConfiguration::where("name", "last_date_online_acceptance")->select("value")->first();
        $last_date_online_acceptance = getDateTimeFormat($rs->value);

        $rs = DistrictConfiguration::where("name", "last_date_offline_acceptance")->select("value")->first();
        $last_date_offline_acceptance = getDateTimeFormat($rs->value);
        */

        //$application_data = Application::where('district_id', Session::get('district_id'))->where("status", "Y")->first();
        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where('application.enrollment_id', $enrollment_id)->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

        $data = Submissions::where("submissions.district_id", Session::get("district_id"))->join("application", "application.id","submissions.application_id")->select("submissions.*")->where("application.enrollment_id", $enrollment_id);
        if($program_id != 0)
        {
            $data->where(function($q) use ($program_id) {
                $q->where("first_choice_program_id", $program_id)
                  ->orWhere("second_choice_program_id", $program_id)->get();
                });
        }
        if($grade != 'All')
        {
            $data->where('next_grade', $grade);
        }
        if($status != 'All')
        {
            if($status == "Offer Accepted and Contract Pending")
                $data->where('submission_status', "Offered and Accepted");
            elseif($status == "Pending Employee Verification")
            {
                $data->where(function($q) {
                    $q->where("mcp_employee", "Yes")->where(function($q1) {
                        $q1->whereNull('employee_first_name')->whereNull('employee_last_name')->whereNull('employee_id')->whereNull('work_location')->get();
                    })->get();
                });
                
            }
            else
                $data->where('submission_status', $status);

        }
        $final_data = $data->get();

        $student_data = array();

        if($preview == true)
        {
            $tmp = array();
            $tmp['id'] = "9999";
            $tmp['student_id'] = "1234567890";
            $tmp['confirmation_no'] = "MAGNET-2122-00000";
            $tmp['name'] = "Johnson William";
            $tmp['grade'] = $tmp['next_grade'] = "8";
            $tmp['current_grade'] = "7";
            $tmp['current_school'] = "MCPSS Elementary";
            $tmp['zoned_school'] = "Zoned School";
            $tmp['created_at'] = getDateFormat(date("Y-m-d H:i:S"));
            $tmp['first_choice'] = "Magnet Program 1";
            $tmp['second_choice'] = "Magnet Program 2";
            $tmp['birth_date'] = getDateFormat(date("Y-m-d"));
            $tmp['student_name'] = "Johnson William";
            $tmp['parent_name'] = "Mark William";
            $tmp['parent_email'] = "mark.william@gmail.com";
            $tmp['student_id'] = "1234567890";
            $tmp['submission_date'] = getDateTimeFormat(date("Y-m-d H:i:S"));
            $tmp['transcript_due_date'] = getDateTimeFormat(date("Y-m-d H:i:S"));
            $tmp['application_url'] = url('/');
            $tmp['signature'] = get_signature('email_signature');


            $msg = find_replace_string($cdata->mail_body,$tmp);
            $msg = str_replace("{","",$msg);
            $msg = str_replace("}","",$msg);
            $msg = $cdata->mail_body;
            $tmp['email_text'] = $msg;
            $tmp['logo'] = getDistrictLogo();


            $msg = find_replace_string($cdata->mail_subject,$tmp);
            $msg = str_replace("{","",$msg);
            $msg = str_replace("}","",$msg);
            $tmp['subject'] = $msg;
            $data = $tmp;
            
            return view("emails.index", compact('data'));
        }
        else
        {
            $cnt = 0;
            foreach($final_data as $key=>$value)
            {
                //$application = Application::where('id', $value->application_id)->first();
                $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();

                if($last_type == "regular")
                {
                    $subms = SubmissionsFinalStatus::where("submission_id", $value->id)->first();
                }
                elseif($last_type == "waitlist")
                {
                    $subms = SubmissionsWaitlistFinalStatus::where("submission_id", $value->id)->where(function($q) {
                        $q->where("first_offer_status", "Accepted")->orWhere("second_offer_status", "Accepted")->get();
                    })->first();
                }
                else
                {
                    $subms = LateSubmissionFinalStatus::where("submission_id", $value->id)->where(function($q) {
                        $q->where("first_offer_status", "Accepted")->orWhere("second_offer_status", "Accepted")->get();
                    })->first();
                }

                if($status == "Offer Accepted and Contract Pending")
                {
                    if(!empty($subms1))
                    {
                        if($subms1->contract_status != "UnSigned")
                        {
                            continue;
                        }
                    }
                     if(!empty($subms))
                     {
                        if($subms->contract_status != "UnSigned")
                        {
                            continue;
                        }
                     }
                     else
                        continue;

                }

                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['student_id'] = $value->student_id;
                $tmp['confirmation_no'] = $value->confirmation_no;
                $tmp['name'] = $value->first_name." ".$value->last_name;
                $tmp['grade'] = $tmp['next_grade'] = $value->next_grade;
                $tmp['current_grade'] = $value->current_grade;
                $tmp['current_school'] = $value->current_school;
                $tmp['zoned_school'] = $value->zoned_school;
                $tmp['created_at'] = getDateFormat($value->created_at);
                $tmp['first_choice'] = getProgramName($value->first_choice_program_id);
                $tmp['second_choice'] = getProgramName($value->second_choice_program_id);
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

                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
                $tmp['parent_address'] = $value->address."<br>". $value->city.", ".$value->state." ".$value->zip;

                $tmp['school_year'] = $application_data1->school_year;
                $tmp['enrollment_period'] = $tmp['school_year'];
                $t1 = explode("-", $tmp['school_year']);
                $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
                $tmp['next_year'] = date("Y")+1;

                /* Offer link */
                $program_name = "";
                $program_name_with_grade = "";
                $offer_program = "";
                $offer_program_with_grade = "";
                $waitlist_program = "";
                $waitlist_program_with_grade = "";
                $waitlist_program_1 = "";
                $waitlist_program_2 = "";
                $waitlist_program_1_with_grade = "";
                $waitlist_program_2_with_grade = "";

                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];



                if($status != "Denied due to Incomplete Records" && $status != "Denied due to Ineligibility")
                {
                    if(!empty($subms))
                    {
                        if($subms->first_choice_final_status == "Offered")
                        {
                            $program_name = getProgramName($value->first_choice_program_id);
                            $program_name_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                            $offer_program = $program_name;
                            $offer_program_with_grade = $program_name_with_grade;

                            if($subms->second_choice_final_status == "Waitlisted")
                            {
                                $waitlist_program = getProgramName($value->second_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                            }
                        }
                        elseif($subms->second_choice_final_status == "Offered")
                        {
                            $program_name = getProgramName($value->second_choice_program_id);
                            $program_name_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;
                            $offer_program = $program_name;
                            $offer_program_with_grade = $program_name_with_grade;

                            if($subms->first_choice_final_status == "Waitlisted")
                            {
                                $waitlist_program = getProgramName($value->first_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                            }

                        }
                        elseif($subms->first_choice_final_status == "Waitlisted" && $subms->second_choice_final_status == "Waitlisted")
                        {
                                $waitlist_program = getProgramName($value->first_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_1 = getProgramName($value->first_choice_program_id);
                                $waitlist_program_1_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_2 = getProgramName($value->first_choice_program_id);
                                $waitlist_program_2_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                        }
                        elseif($subms->first_choice_final_status == "Waitlisted" && $subms->second_choice_final_status == "Pending")
                        {
                                $waitlist_program = getProgramName($value->first_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_1 = getProgramName($value->first_choice_program_id);
                                $waitlist_program_1_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                        }
                        elseif($subms->second_choice_final_status == "Waitlisted")
                        {
                                $waitlist_program = getProgramName($value->second_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_2 = getProgramName($value->second_choice_program_id);
                                $waitlist_program_2_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                        }



                    }
                }

                if($program_name != "")
                {
                    $tmp['program_name'] = $program_name;
                    $tmp['program_name_with_grade'] = $program_name_with_grade;
                }
                $tmp['offer_program'] = $offer_program;
                $tmp['offer_program_with_grade'] = $offer_program_with_grade;
                $tmp['waitlist_program'] = $waitlist_program;
                $tmp['waitlist_program_with_grade'] = $waitlist_program_with_grade;
                $tmp['waitlist_program_1'] = $waitlist_program_1;
                $tmp['waitlist_program_2'] = $waitlist_program_2;
                $tmp['waitlist_program_1_with_grade'] = $waitlist_program_1_with_grade;
                $tmp['waitlist_program_2_with_grade'] = $waitlist_program_2_with_grade;

                /* Offer link */
                $tmp['offer_link'] = "";
                $tmp['online_offer_last_date'] = $last_date_online_acceptance;
                $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;

                if(!empty($subms))
                {
                    $tmp['offer_link'] = url('/Offers/'.$subms->offer_slug);
                    $tmp['contract_link'] = url('/Offers/Contract/Fill/'.$subms->offer_slug);

                    if($subms->last_date_online_acceptance != '')
                    {
                        $tmp['online_offer_last_date'] = $subms->last_date_online_acceptance;
                        $tmp['offline_offer_last_date'] = $subms->last_date_offline_acceptance;
                    }
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
                $tmp['module'] = "Custom Communication";
                $student_data[] = array($value->id, $tmp['name'], $tmp['parent_name'], $tmp['parent_email'], $tmp['grade']);
                
                //$tmp['email'] = "chasitie@theleanleap.com";
                sendMail($tmp, true);

                
                $cnt++;
                //exit;
            }
            ob_end_clean();
            ob_start();
            $fileName =  "CustomCommunication-".strtotime(date("Y-m-d H:i:s")).".xlsx";

            $data = array();
            $data['template_id'] = $id;
            $data['total_count'] = count($student_data);
            $data['user_id'] = Auth::user()->id;
            $data['file_name'] = $fileName;
            $data['type'] = 'Email';
            CustomCommunicationData::create($data);


            Excel::store(new CustomCommunicationEmails(collect($student_data)), $fileName, 'custom_application');

        }


        //Excel::download(new SubmissionExport(collect($data_ary)), 'Submissions.xlsx');

    }


    public function send_email_now_individual(Request $request)
    {
        $req = $request->all();

        $last_date_online_acceptance = $last_date_offline_acceptance = "";
        $rs = DistrictConfiguration::where("name", "last_date_online_acceptance")->select("value")->first();
        $last_date_online_acceptance = getDateTimeFormat($rs->value);

        $rs = DistrictConfiguration::where("name", "last_date_offline_acceptance")->select("value")->first();
        $last_date_offline_acceptance = getDateTimeFormat($rs->value);


        $application_data = Application::where('district_id', Session::get('district_id'))->where("status", "Y")->first();

        $student_data = array();
        if (isset($request->save))
        {
            $data = ['email_body' => $req['mail_body'], 'email_subject' => $req['mail_subject']];
            Submissions::where("id", $req['id'])->update($data);
            Session::flash("success", "Custom Communication saved successfully.");
            return redirect('admin/Submissions/edit/'.$req['id']);
        }

        if (isset($request->preview))
        {
            $tmp = array();
            $tmp['id'] = "9999";
            $tmp['student_id'] = "1234567890";
            $tmp['confirmation_no'] = "MAGNET-2122-00000";
            $tmp['name'] = "Johnson William";
            $tmp['grade'] = $tmp['next_grade'] = "8";
            $tmp['current_grade'] = "7";
            $tmp['current_school'] = "MCPSS Elementary";
            $tmp['zoned_school'] = "Zoned School";
            $tmp['created_at'] = getDateFormat(date("Y-m-d H:i:S"));
            $tmp['first_choice'] = "Magnet Program 1";
            $tmp['second_choice'] = "Magnet Program 2";
            $tmp['birth_date'] = getDateFormat(date("Y-m-d"));
            $tmp['student_name'] = "Johnson William";
            $tmp['parent_name'] = "Mark William";
            $tmp['parent_email'] = "mark.william@gmail.com";
            $tmp['student_id'] = "1234567890";
            $tmp['submission_date'] = getDateTimeFormat(date("Y-m-d H:i:S"));
            $tmp['transcript_due_date'] = getDateTimeFormat(date("Y-m-d H:i:S"));
            $tmp['application_url'] = url('/');
            $tmp['signature'] = get_signature('email_signature');


            $msg = find_replace_string($req['mail_body'],$tmp);
            $msg = str_replace("{","",$msg);
            $msg = str_replace("}","",$msg);
            $msg = $req['mail_body'];
            $tmp['email_text'] = $msg;
            $tmp['logo'] = getDistrictLogo();


            $msg = find_replace_string($req['mail_subject'],$tmp);
            $msg = str_replace("{","",$msg);
            $msg = str_replace("}","",$msg);
            $tmp['subject'] = $msg;
            $data = $tmp;
            
            return view("emails.index", compact('data'));
        }
        else
        {
            $final_data = Submissions::where("submissions.district_id", Session::get("district_id"))->join("application", "application.id","submissions.application_id")->select("submissions.*")->where("submissions.id", $request['id'])->get();

            $data = ['email_body' => $req['mail_body'], 'email_subject' => $req['mail_subject']];
            Submissions::where("id", $req['id'])->update($data);


            foreach($final_data as $key=>$value)
            {
                //$application = Application::where('id', $value->application_id)->first();
                $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();

                $subms = SubmissionsFinalStatus::where("submission_id", $value->id)->first();

                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['student_id'] = $value->student_id;
                $tmp['confirmation_no'] = $value->confirmation_no;
                $tmp['name'] = $value->first_name." ".$value->last_name;
                $tmp['grade'] = $tmp['next_grade'] = $value->next_grade;
                $tmp['current_grade'] = $value->current_grade;
                $tmp['current_school'] = $value->current_school;
                $tmp['zoned_school'] = $value->zoned_school;
                $tmp['created_at'] = getDateFormat($value->created_at);
                $tmp['first_choice'] = getProgramName($value->first_choice_program_id);
                $tmp['second_choice'] = getProgramName($value->second_choice_program_id);
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

                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
                $tmp['parent_address'] = $value->address."<br>". $value->city.", ".$value->state." ".$value->zip;

                $tmp['school_year'] = $application_data1->school_year;
                $tmp['enrollment_period'] = $tmp['school_year'];
                $t1 = explode("-", $tmp['school_year']);
                $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
                $tmp['next_year'] = date("Y")+1;

                $program_name = "";
                $program_name_with_grade = "";
                $offer_program = "";
                $offer_program_with_grade = "";
                $waitlist_program = "";
                $waitlist_program_with_grade = "";
                $waitlist_program_1 = "";
                $waitlist_program_2 = "";
                $waitlist_program_1_with_grade = "";
                $waitlist_program_2_with_grade = "";

                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];



                if($value->submission_status != "Denied due to Incomplete Records" && $value->submission_status != "Denied due to Ineligibility")
                {
                    if(!empty($subms))
                    {
                        if($subms->first_choice_final_status == "Offered")
                        {
                            $program_name = getProgramName($value->first_choice_program_id);
                            $program_name_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                            $offer_program = $program_name;
                            $offer_program_with_grade = $program_name_with_grade;

                            if($subms->second_choice_final_status == "Waitlisted")
                            {
                                $waitlist_program = getProgramName($value->second_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                            }
                        }
                        elseif($subms->second_choice_final_status == "Offered")
                        {
                            $program_name = getProgramName($value->second_choice_program_id);
                            $program_name_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;
                            $offer_program = $program_name;
                            $offer_program_with_grade = $program_name_with_grade;

                            if($subms->first_choice_final_status == "Waitlisted")
                            {
                                $waitlist_program = getProgramName($value->first_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                            }

                        }
                        elseif($subms->first_choice_final_status == "Waitlisted" && $subms->second_choice_final_status == "Waitlisted")
                        {
                                $waitlist_program = getProgramName($value->first_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_1 = getProgramName($value->first_choice_program_id);
                                $waitlist_program_1_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_2 = getProgramName($value->first_choice_program_id);
                                $waitlist_program_2_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                        }
                        elseif($subms->first_choice_final_status == "Waitlisted" && $subms->second_choice_final_status == "Pending")
                        {
                                $waitlist_program = getProgramName($value->first_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_1 = getProgramName($value->first_choice_program_id);
                                $waitlist_program_1_with_grade = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                        }
                        elseif($subms->second_choice_final_status == "Waitlisted")
                        {
                                $waitlist_program = getProgramName($value->second_choice_program_id);
                                $waitlist_program_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                                $waitlist_program_2 = getProgramName($value->second_choice_program_id);
                                $waitlist_program_2_with_grade = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                        }



                    }
                }

                if($program_name != "")
                {
                    $tmp['program_name'] = $program_name;
                    $tmp['program_name_with_grade'] = $program_name_with_grade;
                }
                $tmp['offer_program'] = $offer_program;
                $tmp['offer_program_with_grade'] = $offer_program_with_grade;
                $tmp['waitlist_program'] = $waitlist_program;
                $tmp['waitlist_program_with_grade'] = $waitlist_program_with_grade;
                $tmp['waitlist_program_1'] = $waitlist_program_1;
                $tmp['waitlist_program_2'] = $waitlist_program_2;
                $tmp['waitlist_program_1_with_grade'] = $waitlist_program_1_with_grade;
                $tmp['waitlist_program_2_with_grade'] = $waitlist_program_2_with_grade;

                /* Offer link */
                $tmp['offer_link'] = "";
                $tmp['online_offer_last_date'] = $last_date_online_acceptance;
                $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;

                if(!empty($subms))
                {
                    $tmp['offer_link'] = url('/Offers/'.$subms->offer_slug);
                    $tmp['contract_link'] = url('/Offers/Contract/Fill/'.$subms->offer_slug);

                    if($subms->last_date_online_acceptance != '')
                    {
                        $tmp['online_offer_last_date'] = $subms->last_date_online_acceptance;
                        $tmp['offline_offer_last_date'] = $subms->last_date_offline_acceptance;
                    }
                }



                $msg = find_replace_string($req['mail_body'],$tmp);
                $msg = str_replace("{","",$msg);
                $msg = str_replace("}","",$msg);
                $tmp['msg'] = $msg;

                $msg = find_replace_string($req['mail_subject'],$tmp);
                $msg = str_replace("{","",$msg);
                $msg = str_replace("}","",$msg);
                $tmp['subject'] = $msg;
                
                $tmp['email'] = $value->parent_email;
                $student_data[] = array($value->id, $tmp['name'], $tmp['parent_name'], $tmp['parent_email'], $tmp['grade']);

                $data = array();
                $data['email_body'] = $tmp['msg'];
                $data['email_subject'] = $tmp['subject'];
                $data['email'] = $value->parent_email;
                $data['submission_id'] = $value->id;
                $data['generated_by'] = Auth::user()->id;
                $data['total_count'] = 1;
                CustomCommunicationData::create($data);
                $tmp['module'] = 'Custom Communication';



                sendMail($tmp, true);
                Session::flash("success", "Custom Communication email sent successfully.");
                return redirect('admin/Submissions/edit/'.$req['id']);

            }
        }


        //Excel::download(new SubmissionExport(collect($data_ary)), 'Submissions.xlsx');

    }

    public function preview_email_now_individual($id)
    {
        $data1 = CustomCommunicationData::where("id", $id)->first();
        
        $tmp['email_text'] = $data1->email_body;
        $tmp['logo'] = getDistrictLogo();
        $tmp['subject'] = $data1->subject;
        $data = $tmp;
            


        return view("emails.index", compact('data'));

    }

    public function fetchEmails(Request $request)
    {
        $program_id = $request->program;
        $enrollment_id = $request->enrollment_id;
        $status = $request->submission_status;
        $grade = $request->grade;

        $data = Submissions::where("submissions.district_id", Session::get("district_id"))->join("application", "application.id","submissions.application_id")->select("submissions.*")->where("application.enrollment_id", $enrollment_id);
        if($program_id != 0)
        {
            $data->where(function($q) use ($program_id) {
                $q->where("first_choice_program_id", $program_id)
                  ->orWhere("second_choice_program_id", $program_id)->get();
                });
        }
        if($grade != 'All')
        {
            $data->where('next_grade', $grade);
        }
        if($status != 'All')
        {
            if($status == "Offer Accepted and Contract Pending")
            {
                $data->where('submission_status', "Offered and Accepted");
            }
            elseif($status == "Pending Employee Verification")
            {
                $data->where(function($q) {
                    $q->where("mcp_employee", "Yes")->where(function($q1) {
                        $q1->whereNull('employee_first_name')->whereNull('employee_last_name')->whereNull('employee_id')->whereNull('work_location')->get();
                    })->get();
                });
                
            }
            else
            {
                $data->where('submission_status', $status);
            }
        }
        $final_data = $data->get();

        $student_data = array();
        foreach($final_data as $key=>$value)
        {
            $subms = SubmissionsFinalStatus::where("submission_id", $value->id)->first();
            $subms1 = SubmissionsWaitlistFinalStatus::where("submission_id", $value->id)->where(function($q) {
                $q->where("first_offer_status", "Accepted")->orWhere("second_offer_status", "Accepted")->get();
                })->first();

            if($status == "Offer Accepted and Contract Pending")
            {
                if(!empty($subms1))
                {
                    if($subms1->contract_status != "UnSigned")
                    {
                        continue;
                    }
                }
                 if(!empty($subms))
                 {
                    if($subms->contract_status != "UnSigned")
                    {
                        continue;
                    }
                 }
                 else
                    continue;

            }


            $tmp = array();
            $tmp['id'] = $value->id;
            $tmp['student_name'] = $value->first_name." ".$value->last_name;
            $tmp['grade'] = $value->next_grade;
            $tmp['parent_name'] = $value->parent_first_name." ".$value->parent_last_name;
            $tmp['parent_email'] = $value->parent_email;
            $student_data[] = $tmp;
        }
        return json_encode($student_data);
    }
   

    public function existingData($type, $template_id)
    {
        $data = CustomCommunicationData::where("template_id", $template_id)->orderByDesc("custom_communication_data.created_at")->join("custom_communication", "custom_communication.id", "custom_communication_data.template_id")->join('enrollments', 'enrollments.id', 'custom_communication.enrollment_id')->select('custom_communication_data.*', 'custom_communication.program', 'custom_communication.program', 'custom_communication.enrollment_id', 'custom_communication.grade', 'custom_communication.submission_status', 'custom_communication.template_name','enrollments.school_year')->where('type', $type)->where("custom_communication.enrollment_id", Session::get("enrollment_id"))->limit(10)->get();
        $download_data = array();
        foreach($data as $key=>$value)
        {
            $tmp = array();
            $tmp['id'] = $value->id;
            $tmp['template_name'] = $value->template_name;
            $tmp['program'] = ($value->program != 0 ? getProgramName($value->program) : "All");
            $tmp['grade'] = $value->grade;
            //$tmp['status'] = $value->submission_status;
            $tmp['school_year'] = $value->school_year;
            $tmp['total_count'] = $value->total_count;
            $tmp['file_name'] = $value->file_name;
            $tmp['created_at'] = getDateTimeFormat($value->created_at);
            if($value->status == '')
                $tmp['status'] = $value->submission_status;
            else
                $tmp['status'] = $value->status;
            $download_data[] = $tmp;
        }
        return view("CustomCommunication::generated", compact("download_data","template_id","type"));
    }

    public function downloadFile($id)
    {
        $data = CustomCommunicationData::where("id", $id)->first();
        if(!empty($data))
        {
            $file_path = 'resources/assets/admin/custom_communication/'.$data->file_name;

            if($data->type=="Email")
            {
                $headers = array(
                  'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                );
            }
            else
            {
                $headers = array(
                  'Content-Type: application/pdf',
                );
            }
            return Response::download($file_path, $data->file_name, $headers);
        }
    }

    

    public function status(Request $request)
    {
        $result=CustomCommunication::where('id',$request->id)->update(['status'=> $request->status]);
        if(isset($result))
        {
            return json_encode(true);
        }
        else {
            return json_encode(false);
        }
    }

    public function previewPDF($template_id)
    {
        return $this->generate_letter_now($template_id, true);
    }

    public function previewEmail($template_id)
    {
        return $this->send_email_now($template_id, true);
    }

}
