<?php

namespace App\Modules\Waitlist\Controllers;

use App\Modules\Waitlist\Models\WaitlistEditCommunicationLog;
use App\Modules\Waitlist\Models\WaitlistEditCommunication;
use App\Modules\Submissions\Models\{Submissions,SubmissionsStatusUniqueLog};
use App\Modules\Application\Models\Application;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Session;
use Response;
use PDF;
use File;
use Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Modules\CustomCommunication\Export\{CustomCommunicationEmails};
use App\Modules\CustomCommunication\Models\CustomCommunication;
use App\Modules\CustomCommunication\Models\CustomCommunicationData;
use App\Modules\Submissions\Models\{SubmissionsWaitlistFinalStatus,SubmissionsFinalStatus};
use App\Modules\DistrictConfiguration\Models\DistrictConfiguration;
use App\Modules\Waitlist\Models\WaitlistProcessLogs;



class WaitlistEditCommunicationController extends Controller
{
  public $statusArr = ["Offered" => "Offer Communication", "Waitlisted" => "Waitlist", "OfferedWaitlisted" => "Offered and Waitlist Option"];

  public $dbStatusArr = ["Offered" => "Offered", "Waitlisted" => "Waitlisted", "OfferedWaitlisted" => "Offered and Waitlisted"];

  public function index($status="Offered"){
    $display_outcome =  app('App\Modules\Waitlist\Controllers\WaitlistController')->checkWailistOpen();

    $statusArr = $this->statusArr;
    $dbStatusArr = $this->dbStatusArr;

    $data = WaitlistEditCommunicationLog::where("status", $dbStatusArr[$status])->where("district_id", Session::get("district_id"))->orderByDesc("created_at")->get();

    $mail_download_data = $letter_download_data = array();
    foreach($data as $key=>$value)
    {
        $tmp = array();
        $tmp['id'] = $value->id;
        $tmp['created_by'] = getUserName($value->generated_by);
        $tmp['file_name'] = $value->file_name;
        $tmp['total_count'] = $value->total_count;
        $tmp['created_at'] = getDateTimeFormat($value->created_at);
        if($value->communication_type == "Letter")
            $letter_download_data[] = $tmp;
        else
            $mail_download_data[] = $tmp;
    }


    $data=[];
    $district_id = Session::get('district_id');
    $data = WaitlistEditCommunication::where('district_id',$district_id)->where('status', $dbStatusArr[$status])->first();
    return view('Waitlist::edit_communication_index', compact('data', 'statusArr', 'status', 'dbStatusArr', 'letter_download_data', 'mail_download_data',"display_outcome"));
  }

  public function storeLetter(Request $request){

    $req = $request->all();
    $data = array();
    $data['district_id'] = Session::get('district_id');
    $data['letter_body'] = $req['letter_body'];
    $data['status'] = $req['status'];
    $data['created_by'] = Auth::user()->id;
    $rs = WaitlistEditCommunication::updateOrCreate(["status" => $data['status']], $data);
    if(isset($request->generate_letter_now))
    {
        return $this->generate_letter_now($data['status']);
    }

    Session::flash('success', 'Communication data saved successfully.');    
    return redirect('admin/Waitlist/EditCommunication/'.$req['redirect_status']);
  }


    public function generate_letter_now($status, $preview=false)
    {
        set_time_limit(0);

        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $version = $rs + 1;
        
        $rs = SubmissionsWaitlistFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->count();
        if($rs == 0)
        {
            $version = $version - 1;
            $rs = SubmissionsWaitlistFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->count();
        }


        $district_id = Session::get("district_id");
        $cdata = WaitlistEditCommunication::where("status", $status)->first();
        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where('application.enrollment_id', Session::get('enrollment_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

        if($status == "Waitlisted")
        {
            $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))->where(function ($q) use ($status) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Waitlisted")->where("second_choice_final_status", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("first_choice_final_status", "Waitlisted")->where("second_choice_final_status", "<>", "Offered");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Waitlisted")->where("first_choice_final_status", "<>", "Offered");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->orderBy("next_grade")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status']);

        }
        elseif($status == "Offered and Waitlisted")
        {
            $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))->where(function ($q) use ($status) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Offered")->where("second_choice_final_status", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Offered")->where("first_choice_final_status", "Waitlisted");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->where("submissions_waitlist_final_status.version", $version)
                                ->orderBy("next_grade")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status','offer_slug']);
        }
        elseif($status == "Offered")
        {
            $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))->where(function ($q) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Offered")->where("second_choice_final_status", "<>", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Offered")->where("first_choice_final_status", "<>", "Waitlisted");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->where('submissions_waitlist_final_status.version', $version)
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'offer_slug']);
        }
        else
        {
            $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))->where(function ($q)  use($status){
                                    $q->where("first_choice_final_status", $status)
                                      ->orWhere("second_choice_final_status", $status);
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->where('submissions_waitlist_final_status.version', $version)
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'offer_slug']);
        }

        $last_date_online_acceptance = $last_date_offline_acceptance = "";
        $rs = DistrictConfiguration::where('enrollment_id', Session::get("enrollment_id"))->where("name", "last_date_waitlist_online_acceptance")->select("value")->first();
        $last_date_online_acceptance = getDateTimeFormat($rs->value);

        $rs = DistrictConfiguration::where('enrollment_id', Session::get("enrollment_id"))->where("name", "last_date_waitlist_offline_acceptance")->select("value")->first();
        $last_date_offline_acceptance = getDateTimeFormat($rs->value);

        $student_data = array();
        foreach($submissions as $key=>$value)
        {
           // $application = Application::where('id', $value->application_id)->first();
            $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where('application.enrollment_id', Session::get("enrollment_id"))->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();
                $generated = false;
                if(($value->first_choice_final_status == $status && $status == "Offered") || ($value->first_choice_final_status == "Offered" && $status == "Offered and Waitlisted") || ($value->first_choice_final_status == $status))
                {
                    $generated = true;
                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['student_id'] = $value->student_id;
                $tmp['confirmation_no'] = $value->confirmation_no;
                $tmp['name'] = $value->first_name." ".$value->last_name;
                $tmp['current_grade'] = $value->current_grade;
                $tmp['grade'] = $tmp['next_grade'] = $value->next_grade;
                $tmp['first_name'] = $value->first_name;
                $tmp['last_name'] = $value->last_name;
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
                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
                $tmp['school_year'] = $application_data1->school_year;
                $tmp['enrollment_period'] = $tmp['school_year'];
                $t1 = explode("-", $tmp['school_year']);
                $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
                $tmp['next_year'] = date("Y")+1;

                $tmp['choice_program_1_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
                $tmp['choice_program_2_with_grade'] = ($value->second_choice_program_id > 0 ? getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'] : "");

                $tmp['offer_program'] = getProgramName($value->first_choice_program_id);
                $tmp['offer_program_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                $tmp['accepted_program_name_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                $tmp['parent_address'] = $value->address."<br>". $value->city.", ".$value->state." ".$value->zip;

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

                /* Offer link */
                if(($status == "Offered" || $status == "Offered and Waitlisted") && $value->offer_slug != "")
                {
                    $tmp['offer_link'] = url('/Offers/'.$value->offer_slug);
                    $tmp['contract_link'] = url('/Offers/Contract/Fill/'.$value->offer_slug);
                }
                else
                {
                    $tmp['offer_link'] = "";
                    $tmp['contract_link'] = "";
                }
                $tmp['online_offer_last_date'] = $last_date_online_acceptance;
                $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;
                $msg = find_replace_string($cdata->letter_body,$tmp);
                $msg = str_replace("{","",$msg);
                $msg = str_replace("}","",$msg);
                $tmp['letter_body'] = $msg;
                $student_data[] = $tmp;
            }


            if((($value->second_choice_final_status == $status && $status == "Offered") || ($value->second_choice_final_status == "Offered" && $status == "Offered and Waitlisted") || ($value->second_choice_final_status == $status)) && !$generated)
            {
                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['student_id'] = $value->student_id;
                $tmp['confirmation_no'] = $value->confirmation_no;
                $tmp['name'] = $value->first_name." ".$value->last_name;
                $tmp['grade'] = $tmp['next_grade'] = $value->next_grade;
                $tmp['current_grade'] = $value->current_grade;
                $tmp['first_name'] = $value->first_name;
                $tmp['last_name'] = $value->last_name;
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
                $tmp['program_name'] = getProgramName($value->second_choice_program_id);
                $tmp['program_name_with_grade'] = getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'];

                $tmp['school_year'] = $application_data1->school_year;
                $tmp['enrollment_period'] = $tmp['school_year'];
                $t1 = explode("-", $tmp['school_year']);
                $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
                $tmp['next_year'] = date("Y")+1;

                $tmp['choice_program_1_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
                $tmp['choice_program_2_with_grade'] = ($value->second_choice_program_id > 0 ? getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'] : "");

                $tmp['offer_program'] = getProgramName($value->second_choice_program_id);
                $tmp['offer_program_with_grade'] = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;

                $tmp['accepted_program_name_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                $tmp['parent_address'] = $value->address."<br>". $value->city.", ".$value->state." ".$value->zip;

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



                if(($status == "Offered" || $status == "Offered and Waitlisted")&& $value->offer_slug != "")
                {
                    $tmp['offer_link'] = url('/Offers/'.$value->offer_slug);
                    $tmp['contract_link'] = url('/Offers/Contract/Fill/'.$value->offer_slug);
                }
                else
                {
                    $tmp['offer_link'] = "";
                    $tmp['contract_link'] = "";
                }
                $tmp['online_offer_last_date'] = $last_date_online_acceptance;
                $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;
                $msg = find_replace_string($cdata->letter_body,$tmp);
                $msg = str_replace("{","",$msg);
                $msg = str_replace("}","",$msg);
                $tmp['letter_body'] = $msg;
                $student_data[] = $tmp;
            }
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

        $fileName =  "EditCustomCommunication-".strtotime(date("Y-m-d H:i:s")) . '.pdf';
        $path = "resources/assets/admin/edit_communication";
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
            $data['district_id'] = Session::get("district_id");
            $data['communication_type'] = "Letter";
            $data['status'] = $status;
            $data['file_name'] = $fileName;
            $data['total_count'] = count($student_data);
            $data['generated_by'] = Auth::user()->id;
            WaitlistEditCommunicationLog::create($data);
            return $pdf->download($fileName);
        }
        
    }


  public function storeEmail(Request $request){

    $req = $request->all();
    $data = array();
    $data['district_id'] = Session::get('district_id');
    $data['mail_subject'] = $req['mail_subject'];
    $data['mail_body'] = $req['mail_body'];
    $data['status'] = $req['status'];
    $data['created_by'] = Auth::user()->id;

    $rs = WaitlistEditCommunication::updateOrCreate(["status" => $data['status']], $data);

    if(isset($request->send_email_now))
    {
        $this->send_email_now($req['status']);
        Session::flash("success", "Custom Communication emails sent successfully.");
    }

    Session::flash('success', 'Communication data saved successfully.');    
    return redirect('admin/Waitlist/EditCommunication/'.$req['redirect_status']);
  }


    public function send_email_now($status, $preview=false)
    {

        $rs = WaitlistProcessLogs::where('enrollment_id', Session::get("enrollment_id"))->count();
        $version = $rs + 1;
        
        $rs = SubmissionsWaitlistFinalStatus::where('enrollment_id', Session::get("enrollment_id"))->where("version", $version)->count();
        if($rs == 0)
        {
            $version = $version - 1;
            $rs = SubmissionsWaitlistFinalStatus::where('enrollment_id', Session::get("enrollment_id"))->where("version", $version)->count();
        }


        $cdata = WaitlistEditCommunication::where("status", $status)->first();
        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where('application.enrollment_id', Session::get("enrollment_id"))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();
        $district_id = Session::get('district_id');

        $last_date_online_acceptance = $last_date_offline_acceptance = "";
        $rs = DistrictConfiguration::where('enrollment_id', Session::get("enrollment_id"))->where("name", "last_date_waitlist_online_acceptance")->select("value")->first();
        $last_date_online_acceptance = getDateTimeFormat($rs->value);

        $rs = DistrictConfiguration::where('enrollment_id', Session::get("enrollment_id"))->where("name", "last_date_waitlist_offline_acceptance")->select("value")->first();
        $last_date_offline_acceptance = getDateTimeFormat($rs->value);

        if($status == "Waitlisted")
        {
            $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))->where(function ($q) use ($status) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Waitlisted")->where("second_choice_final_status", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("first_choice_final_status", "Waitlisted")->where("second_choice_final_status", "<>", "Offered");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Waitlisted")->where("first_choice_final_status", "<>", "Offered");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->orderBy("next_grade")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status']);
        }
        elseif($status == "Offered and Waitlisted")
        {
             $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))->where(function ($q) use ($status) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Offered")->where("second_choice_final_status", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Offered")->where("first_choice_final_status", "Waitlisted");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->orderBy("next_grade")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status','offer_slug']);
        }
        elseif($status == "Offered")
        {
            $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))->where(function ($q) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Offered")->where("second_choice_final_status", "<>", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Offered")->where("first_choice_final_status", "<>", "Waitlisted");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'offer_slug']);
        }
        else
        {
            $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))->where(function ($q)  use($status){
                                    $q->where("first_choice_final_status", $status)
                                      ->orWhere("second_choice_final_status", $status);
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'offer_slug']);
        }

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


            $type = "waitlist";
            return view("emails.preview_index", compact('data','status', "type"));
        }
        else
        {
          $countMail = 0;
            foreach($submissions as $key=>$value)
            {
                $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where('application.enrollment_id', Session::get('enrollment_id'))->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();
            
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

                    $tmp['accepted_program_name_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                    $tmp['parent_address'] = $value->address."<br>". $value->city.", ".$value->state." ".$value->zip;


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
                        $tmp['contract_link'] = url('/Offers/Contract/Fill/'.$value->offer_slug);
                    }
                    else
                    {
                        $tmp['offer_link'] = "";
                        $tmp['contract_link'] = "";
                    }
                    $tmp['online_offer_last_date'] = $last_date_online_acceptance;
                    $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;


                    $msg = find_replace_string($cdata->mail_body,$tmp);
                    $msg = str_replace("{","",$msg);
                    $msg = str_replace("}","",$msg);
                    $tmp['msg'] = $msg;

                    $msg = find_replace_string($cdata->mail_subject,$tmp);
                    $msg = str_replace("{","",$msg);
                    $msg = str_replace("}","",$msg);
                    $tmp['subject'] = $msg;
                    
                    $tmp['email'] = $value->parent_email;
                    $tmp['module'] = "Edit Communication - Waitlist";
                    $student_data[] = array($value->id, $tmp['name'], $tmp['parent_name'], $tmp['parent_email'], $tmp['grade']);
                    //if($countMail == 0)
                    //{
                        sendMail($tmp, true);
                    //}
                    $countMail++;
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
                    $tmp['transcript_due_date'] = getDateTimeFormat($application_data1->transcript_due_date);
                    $tmp['application_url'] = url('/');
                    $tmp['signature'] = get_signature('email_signature');
                    $tmp['school_year'] = $application_data->school_year;
                    $tmp['enrollment_period'] = $tmp['school_year'];
                    $t1 = explode("-", $tmp['school_year']);
                    $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
                    $tmp['next_year'] = date("Y")+1;

                    $tmp['choice_program_1_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
                    $tmp['choice_program_2_with_grade'] = ($value->second_choice_program_id > 0 ? getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'] : "");

        
                    $tmp['offer_program'] = getProgramName($value->second_choice_program_id);
                    $tmp['offer_program_with_grade'] = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;
                    $tmp['accepted_program_name_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;

                    $tmp['parent_address'] = $value->address."<br>". $value->city.", ".$value->state." ".$value->zip;


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
                        $tmp['contract_link'] = url('/Offers/Contract/Fill/'.$value->offer_slug);
                    }
                    else
                    {
                        $tmp['offer_link'] = "";
                        $tmp['contract_link'] = "";
                    }
                    $tmp['program_name_with_grade'] = getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'];
                    $tmp['online_offer_last_date'] = $last_date_online_acceptance;
                    $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;



                    $msg = find_replace_string($cdata->mail_body,$tmp);
                    $msg = str_replace("{","",$msg);
                    $msg = str_replace("}","",$msg);
                    $tmp['msg'] = $msg;

                    $msg = find_replace_string($cdata->mail_subject,$tmp);
                    $msg = str_replace("{","",$msg);
                    $msg = str_replace("}","",$msg);
                    $tmp['subject'] = $msg;
                    $tmp['module'] = "Edit Communication - Waitlist";
                    
                    $tmp['email'] = $value->parent_email;
                    $student_data[] = array($value->id, $tmp['name'], $tmp['parent_name'], $tmp['parent_email'], $tmp['grade']);
                   // if($countMail == 0)
                   // {
                        sendMail($tmp, true);
                    //}
                    
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
            WaitlistEditCommunicationLog::create($data);

            Excel::store(new CustomCommunicationEmails(collect($student_data)), $fileName, 'edit_communication');

        }


        //Excel::download(new SubmissionExport(collect($data_ary)), 'Submissions.xlsx');

    }


  public function fetchEmails(Request $request)
  {
        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $version = $rs + 1;
        
        $rs = SubmissionsWaitlistFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->count();
        if($rs == 0)
        {
            $version = $version - 1;
            $rs = SubmissionsWaitlistFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->count();
        }

        $status = $request->status;
        $district_id = Session::get("district_id");

         if($status == "Waitlisted")
        {
            $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($status) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Waitlisted")->where("second_choice_final_status", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("first_choice_final_status", "Waitlisted")->where("second_choice_final_status", "<>", "Offered");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Waitlisted")->where("first_choice_final_status", "<>", "Offered");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->where('submissions_waitlist_final_status.version', $version)
                                ->orderBy("next_grade")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status']);
        }
        elseif($status == "Offered and Waitlisted")
        {
            $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($status) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Offered")->where("second_choice_final_status", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Offered")->where("first_choice_final_status", "Waitlisted");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->where("submissions_waitlist_final_status.version", $version)
                                ->orderBy("next_grade")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status']);
        }
        elseif($status == "Offered")
        {
            $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Offered")->where("second_choice_final_status", "<>", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Offered")->where("first_choice_final_status", "<>", "Waitlisted");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->where('submissions_waitlist_final_status.version', $version)
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'offer_slug']);
        }
        else
        {
            $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q)  use($status){
                                    $q->where("first_choice_final_status", $status)
                                      ->orWhere("second_choice_final_status", $status);
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->where("submissions_waitlist_final_status.version", $version)
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'offer_slug']);
        }
        $student_data = array();
        foreach($submissions as $key=>$value)
        {
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



    public function downloadFile($id)
    {
        $data = WaitlistEditCommunicationLog::where("id", $id)->first();
        if(!empty($data))
        {
            $file_path = 'resources/assets/admin/edit_communication/'.$data->file_name;

            if($data->type=="Email")
            {
                $headers = array(
                  'Content-Type: application/vnd.openxmlformats-officedocument',
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


    public function previewPDF($status)
    {
        return $this->generate_letter_now($this->dbStatusArr[$status], true);
    }

    public function previewEmail($status)
    {
        return $this->send_email_now($this->dbStatusArr[$status], true);
    }

    public function send_test_email(Request $request)
    {
        $req = $request->all();
        $email = $req['email'];
        $status = $req['status'];
        $district_id = 3;
        
        $cdata = WaitlistEditCommunication::where("status", $status)->first();
        
        $last_date_online_acceptance = $last_date_offline_acceptance = "";
        $rs = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("name", "last_date_waitlist_online_acceptance")->select("value")->first();
        $last_date_online_acceptance = getDateTimeFormat($rs->value);

        $rs = DistrictConfiguration::where("enrollment_id", Session::get("enrollment_id"))->where("name", "last_date_waitlist_offline_acceptance")->select("value")->first();
        $last_date_offline_acceptance = getDateTimeFormat($rs->value);

 $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.enrollment_id", Session::get("enrollment_id"))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();        



        if($status == "Waitlisted")
        {
            $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($status) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Waitlisted")->where("second_choice_final_status", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("first_choice_final_status", "Waitlisted")->where("second_choice_final_status", "<>", "Offered");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Waitlisted")->where("first_choice_final_status", "<>", "Offered");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->orderBy("next_grade")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status']);
        }
        elseif($status == "Offered and Waitlisted")
        {
             $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($status) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Offered")->where("second_choice_final_status", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Offered")->where("first_choice_final_status", "Waitlisted");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                                ->orderBy("next_grade")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status','offer_slug']);
        }
        elseif($status == "Offered")
        {
            $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) {
                                    $q->where(function ($q1) {
                                        $q1->where("first_choice_final_status", "Offered")->where("second_choice_final_status", "<>", "Waitlisted");
                                    })->orWhere(function ($q1) {
                                        $q1->where("second_choice_final_status", "Offered")->where("first_choice_final_status", "<>", "Waitlisted");
                                    });
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'offer_slug']);
        }
        else
        {
            $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q)  use($status){
                                    $q->where("first_choice_final_status", $status)
                                      ->orWhere("second_choice_final_status", $status);
                                })
                                ->where('district_id', $district_id)
                                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")
                ->get(['submissions.*', 'first_offered_rank', 'second_offered_rank', 'next_grade', 'race', 'first_choice_final_status', 'second_choice_final_status', 'offer_slug']);
        }
        $countMail = 0;
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

                    $tmp['choice_program_1_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
                    $tmp['choice_program_2_with_grade'] = ($value->second_choice_program_id > 0 ? getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'] : "");

                    $tmp['offer_program'] = getProgramName($value->first_choice_program_id);
                    $tmp['offer_program_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                    $tmp['accepted_program_name_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                    $tmp['parent_address'] = $value->address."<br>". $value->city.", ".$value->state." ".$value->zip;


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
                        $tmp['contract_link'] = url('/Offers/Contract/Fill/'.$value->offer_slug);
                    }
                    else
                    {
                        $tmp['offer_link'] = "";
                        $tmp['contract_link'] = "";
                    }
                    $tmp['online_offer_last_date'] = $last_date_online_acceptance;
                    $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;


                    $msg = find_replace_string($cdata->mail_body,$tmp);
                    $msg = str_replace("{","",$msg);
                    $msg = str_replace("}","",$msg);
                    $tmp['msg'] = $msg;

                    $msg = find_replace_string($cdata->mail_subject,$tmp);
                    $msg = str_replace("{","",$msg);
                    $msg = str_replace("}","",$msg);
                    $tmp['subject'] = $msg;
                    $tmp['module'] = "Edit Communication - Waitlist";
                    
                    $tmp['email'] = $email;
                    $student_data[] = array($value->id, $tmp['name'], $tmp['parent_name'], $tmp['parent_email'], $tmp['grade']);
                    //if($countMail == 0)
                    //{
                        sendMail($tmp, true);
                    //}
                    $countMail++;
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
                    $tmp['transcript_due_date'] = getDateTimeFormat($application_data1->transcript_due_date);
                    $tmp['application_url'] = url('/');
                    $tmp['signature'] = get_signature('email_signature');
                    $tmp['school_year'] = $application_data->school_year;
                    $tmp['enrollment_period'] = $tmp['school_year'];
                    $t1 = explode("-", $tmp['school_year']);
                    $tmp['next_school_year'] = ($t1[0] + 1)."-".($t1[1]+1);
                    $tmp['next_year'] = date("Y")+1;

                    $tmp['choice_program_1_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
                    $tmp['choice_program_2_with_grade'] = ($value->second_choice_program_id > 0 ? getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'] : "");
        
                    $tmp['offer_program'] = getProgramName($value->second_choice_program_id);
                    $tmp['offer_program_with_grade'] = getProgramName($value->second_choice_program_id). " - Grade ".$value->next_grade;
                    $tmp['accepted_program_name_with_grade'] = getProgramName($value->first_choice_program_id). " - Grade ".$value->next_grade;
                    $tmp['parent_address'] = $value->address."<br>". $value->city.", ".$value->state." ".$value->zip;


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
                        $tmp['contract_link'] = url('/Offers/Contract/Fill/'.$value->offer_slug);
                    }
                    else
                    {
                        $tmp['offer_link'] = "";
                        $tmp['contract_link'] = "";

                    }
                    $tmp['program_name_with_grade'] = getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'];
                    $tmp['online_offer_last_date'] = $last_date_online_acceptance;
                    $tmp['offline_offer_last_date'] = $last_date_offline_acceptance;



                    $msg = find_replace_string($cdata->mail_body,$tmp);
                    $msg = str_replace("{","",$msg);
                    $msg = str_replace("}","",$msg);
                    $tmp['msg'] = $msg;

                    $msg = find_replace_string($cdata->mail_subject,$tmp);
                    $msg = str_replace("{","",$msg);
                    $msg = str_replace("}","",$msg);
                    $tmp['subject'] = $msg;
                    $tmp['module'] = "Edit Communication - Waitlist";
                    
                    $tmp['email'] = $email;

                    $student_data[] = array($value->id, $tmp['name'], $tmp['parent_name'], $tmp['parent_email'], $tmp['grade']);
                    //if($countMail == 0)
                   // {
                        sendMail($tmp, true);
                    //}
                    
                    $countMail++;
                }

                
            }
            echo "done";
    }

}
