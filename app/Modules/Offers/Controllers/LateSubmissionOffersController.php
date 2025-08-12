<?php

namespace App\Modules\Offers\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Session;
use View;
use Config;
use Illuminate\Support\Facades\Crypt;
use App\Modules\Submissions\Models\{Submissions,SubmissionsFinalStatus,LateSubmissionFinalStatus,SubmissionContractsLog,SubmissionsStatusLog};
use App\Modules\LateSubmission\Models\LateSubmissionProcessLogs;
use App\Modules\DistrictConfiguration\Models\DistrictConfiguration;
use App\Modules\Application\Models\Application;
use App\Modules\EditCommunication\Models\EditCommunication;
use Auth;
use Mail;
use PDF;



class LateSubmissionOffersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
    }

    public function index()
    {
        echo $encryptedValue = Crypt::encryptString('Hello world.');
        //print_r($eligibilities);exit;
        //return view("Eligibility::index1",compact('eligibilities','eligibilityTemplates'));
    }

    public function adminOfferChoice($slug)
    {


        $submission = LateSubmissionFinalStatus::where("offer_slug", $slug)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.*", "submissions.*")->first();

        $date = DistrictConfiguration::where("enrollment_id", $submission->enrollment_id)->where("name", "last_date_late_submission_online_acceptance")->select("value")->first();

        $msg = "";
        $last_offline_date = date("Y-m-d H:i:s", strtotime($date->value));

        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.id', $submission->application_id)->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

        if($submission->last_date_online_acceptance != '')
        {
            $last_offline_date = date("Y-m-d H:i:s", strtotime($submission->last_date_online_acceptance));   
        }

        if(date("Y-m-d H:i:s") > $last_offline_date)
        {
            if($submission->submission_status == "Offered and Accepted" && $submission->contract_status != "Signed")
            {
                if(Auth::check())
                {
                    Session::put("contract_from_admin", "Y");   
                }
                return view("Offers::LateSubmission.admin_index", compact("slug"));
            }
            else
                return view("Offers::LateSubmission.timed_out", compact("submission", "application_data", "msg"));
        }
        if(Auth::check())
        {
            Session::put("contract_from_admin", "Y");   
        }


        return view("Offers::LateSubmission.admin_index", compact("slug"));
    }

    public function offerChoice($slug)
    {
        $submission = LateSubmissionFinalStatus::where("offer_slug", $slug)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.version as lversion", "late_submissions_final_status.*", "submissions.*")->first();

        $date = DistrictConfiguration::where("enrollment_id", $submission->enrollment_id)->where("name", "last_date_late_submission_online_acceptance")->select("value")->first();
        if(!empty($data))
            $last_online_date = date("Y-m-d H:i:s", strtotime($date->value));

        $date = DistrictConfiguration::where("enrollment_id", $submission->enrollment_id)->where("name", "last_date_late_submission_offline_acceptance")->select("value")->first();
        if(!empty($date))
            $last_offline_date = date("Y-m-d H:i:s", strtotime($date->value));

        $str = DistrictConfiguration::where("name", "late_submission_offer_accept_screen")->select("value")->first();
        $msg = $str->value;



        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.id', $submission->application_id)->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

        if($submission->last_date_online_acceptance != '')
        {
            $last_online_date = $last_offline_date = date("Y-m-d H:i:s", strtotime($submission->last_date_online_acceptance));
        }

        


        if((date("Y-m-d H:i:s") > $last_online_date && $submission->submission_status != "Offered and Accepted") || (date("Y-m-d H:i:s") > $last_online_date  && $submission->submission_status == "Offered and Accepted" &&  $submission->contract_status == "Signed"))
        {
            return view("Offers::LateSubmission.timed_out", compact("submission", "application_data", "msg"));
        }
        else
        {
            $tmp = generateShortCode($submission);
            $tmp['offer_link'] = url('/Offers/'.$slug);
            $tmp['online_offer_last_date'] = getDateTimeFormat($last_online_date);
            $tmp['offline_offer_last_date'] = getDateTimeFormat($last_offline_date);

            

            $second_program = "";
            $approve_program_id = 0;
            if(!empty($submission))
            {
                if($submission->first_choice_final_status == "Offered")
                {
                    $first_program = getProgramName($submission->first_waitlist_for);
                    $approve_program_id = $submission->first_waitlist_for;
                    if($submission->second_choice_final_status != "Pending" && $submission->second_choice_final_status != "Denied due to Ineligibility")
                    {
                        $second_program = getProgramName($submission->second_waitlist_for);
                    }
                }
                elseif($submission->second_choice_final_status == "Offered")
                {
                    $approve_program_id = $submission->second_waitlist_for;
                    $first_program = getProgramName($submission->second_waitlist_for);
                    if($submission->firt_choice_final_status != "Pending" && $submission->firt_choice_final_status != "Denied due to Ineligibility")
                    {
                        $second_program = getProgramName($submission->first_waitlist_for);
                    }
                }
                $tmp['program_name'] = $first_program;
                $tmp['program_name_with_grade'] = $first_program. " - Grade ".$tmp['next_grade'];
                $tmp['offer_program_with_grade'] = getProgramName($approve_program_id). " - Grade ".$tmp['next_grade'];


                if($submission->contract_status == "Signed")
                {
                    $str = DistrictConfiguration::where("name", "late_submission_offer_confirmation_screen")->select("value")->first();
                    $msg = $str->value;
                    $msg = find_replace_string($msg,$tmp);
                    return view("Offers::LateSubmission.confirm_screen", compact("submission", "application_data", "msg"));
                }
                if($submission->first_offer_status == "Declined" && $submission->second_offer_status == "Declined")
                {
                    $str = DistrictConfiguration::where("name", "late_submission_offer_declined_screen")->select("value")->first();
                    $msg = $str->value;
                    $msg = find_replace_string($msg,$tmp);

                    return view("Offers::LateSubmission.decline_screen", compact("submission", "application_data", "msg"));
                }
                if($submission->first_offer_status == "Declined & Waitlisted" || $submission->second_offer_status == "Declined & Waitlisted")
                {
                    $str = DistrictConfiguration::where("name", "late_submission_offer_waitlist_screen")->select("value")->first();
                    $msg = $str->value;
                    if($submission->first_offer_status == "Declined & Waitlisted")
                    {
                        $tmp['waitlist_program_with_grade'] = getProgramName($submission->second_program_id). " - Grade ".$tmp['next_grade'];
                    }
                    else
                    {
                        $tmp['waitlist_program_with_grade'] = getProgramName($submission->first_program_id). " - Grade ".$tmp['next_grade'];
                    }
                    $msg = find_replace_string($msg,$tmp);
                    return view("Offers::LateSubmission.wailist_screen", compact("submission", "application_data", "msg"));
                }   

                if($submission->contract_mode != "Pending")
                {
                    if($submission->contract_mode == "Offline")
                    {
                    	return redirect("/LateSubmission/Offers/Contract/Fill/".$slug);
//                        return view("Offers::LateSubmission.contract_later", compact("submission", "application_data", "msg"));
                    }
                    else
                        return redirect("/LateSubmission/Offers/Contract/Fill/".$slug);
                }

                if(($submission->first_offer_status != "Pending" || $submission->second_offer_status != "Pending") &&  ($submission->first_offer_status == "Accepted" || $submission->second_offer_status == "Accepted"))
                {
                    return redirect("/LateSubmission/Offers/Contract/Option/".$submission->offer_slug); 
                }
                $msg = find_replace_string($msg,$tmp);

                if($submission->first_offer_status == "Declined & Waitlisted")
                {
                    $first_program = $second_program;
                    $second_program = "";
                }

                if($submission->second_offer_status == "Declined & Waitlisted")
                {
                    $second_program = "";
                }
                $version = $submission->lversion;
                return view("Offers::LateSubmission.index",compact('submission','first_program', 'second_program', "last_online_date", "last_offline_date", "application_data", "approve_program_id", "msg", "version"));
            }
            else
            {
                echo "T";exit;
            }
        }
    }

    public function offerSave(Request $request)
    {
        $req = $request->all();        
        $rsenv = Submissions::where("id", $req['submission_id'])->select("enrollment_id")->first();

        $date = DistrictConfiguration::where("enrollment_id", $rsenv->enrollment_id)->where("name", "last_date_late_submission_online_acceptance")->select("value")->first();
        $last_online_date = date("Y-m-d H:i:s", strtotime($date->value));

        $date = DistrictConfiguration::where("enrollment_id", $rsenv->enrollment_id)->where("name", "last_date_late_submission_offline_acceptance")->select("value")->first();
        $last_offline_date = date("Y-m-d H:i:s", strtotime($date->value));


       // $req = $request->all();
        $version = $req['version'];

        if(Session::has("contract_from_admin"))
        {
            $rs = LateSubmissionFinalStatus::where("submission_id", $req['submission_id'])->where("late_submissions_final_status.version", $version)->update(["offer_status_by"=>Auth::user()->id]);
        }

        if(isset($request->accept_btn))
        {
            $program_id = $req['accept_btn'];
            $submission_id = $req['submission_id'];

            $submission = LateSubmissionFinalStatus::where("submission_id", $req['submission_id'])->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->where("late_submissions_final_status.version", $version)->select("late_submissions_final_status.version as lversion", "late_submissions_final_status.*", "submissions.*")->first();
            $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.id', $submission->application_id)->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();
         
            $rs = Submissions::where("id", $submission_id)->update(array("submission_status"=>"Offered and Accepted"));




            $data = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("late_submissions_final_status.version", $version)->first();
            if(!empty($data))
            {
                $redirectN = true;
                if($data->first_choice_final_status == "Offered" && $data->first_waitlist_for == $program_id)
                {
                    $rs = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("late_submissions_final_status.version", $version)->update(["first_offer_status"=>"Accepted", "first_offer_update_at"=>date("Y-m-d H:i:s"), "second_offer_status"=>"NoAction"]);

                    $program_name = getProgramName($submission->first_waitlist_for);
                    $program_id = $submission->first_waitlist_for;
                    $redirectN = false;

                }
                elseif($data->second_choice_final_status == "Offered" && $data->second_waitlist_for == $program_id)
                {
                    $rs = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("late_submissions_final_status.version", $version)->update(["second_offer_status"=>"Accepted", "second_offer_update_at"=>date("Y-m-d H:i:s"), "first_offer_status"=>"NoAction"]);
                    $program_name = getProgramName($submission->first_waitlist_for);
                    $program_id = $submission->first_waitlist_for;
                    $redirectN = false;
                }

                if($redirectN)
                {
                    return redirect()->back();
                }
                else
                {
                    $commentObj = array();
                    $commentObj['old_status'] = $submission->submission_status;
                    $commentObj['new_status'] = "Offered and Accepted";
                    if(Session::has("contract_from_admin"))
                    {
                        $commentObj['comment'] = "MCPSS Admin has Accepted the Offer for ".$program_name." - Grade ".$submission->next_grade;
                        $commentObj['updated_by'] = Auth::user()->id;
                    }
                    else
                    {
                        $commentObj['updated_by'] = 0;
                        $commentObj['comment'] = "Parent has Accepted the Offer for ".$program_name." - Grade ".$submission->next_grade;
                    }
                    $commentObj['submission_id'] = $submission->id;
                    SubmissionsStatusLog::create($commentObj);

                    $submission = LateSubmissionFinalStatus::where("submission_id", $req['submission_id'])->where("late_submissions_final_status.version", $version)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.*", "submissions.*")->first();

                    $this->sendOfferEmails($submission, $req, "offer_accepted");

                    return redirect("/LateSubmission/Offers/Contract/Option/".$submission->offer_slug);
                }
            }
        }
        elseif(isset($request->decline_btn))
        {
            $submission_id = $req['submission_id'];
            $rs = Submissions::where("id", $submission_id)->update(array("submission_status"=>"Offered and Declined"));

            $submission = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("late_submissions_final_status.version", $version)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.*", "submissions.*")->first();


            $tmp = generateShortCode($submission);
            $str = DistrictConfiguration::where("name", "late_submission_offer_declined_screen")->select("value")->first();

            $msg = $str->value;

            $commentObj = array();
            $commentObj['old_status'] = "Offered";
            $commentObj['new_status'] = "Offered and Declined";

            if($submission->first_choice_final_status == "Offered")
            {
                $program_name = getProgramName($submission->first_choice_program_id);
            }
            else
            {
                $program_name = getProgramName($submission->second_choice_program_id);
            }
            $tmp['program_name_with_grade'] = $program_name. " - Grade ".$submission->next_grade;
            $msg = find_replace_string($msg,$tmp);

            if(Session::has("contract_from_admin"))
            {
                $commentObj['comment'] = "MCPSS Admin has Declined the Offer for ".$program_name." - Grade ".$submission->next_grade;
                $commentObj['updated_by'] = Auth::user()->id;
            }
            else
            {
                $commentObj['updated_by'] = 0;
                $commentObj['comment'] = "Parent has Declined the Offer for ".$program_name." - Grade ".$submission->next_grade;
            }
            $commentObj['submission_id'] = $submission_id;
            SubmissionsStatusLog::create($commentObj);

            $this->sendOfferEmails($submission, $req, "offer_declined");

            $rs = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("late_submissions_final_status.version", $version)->update(["first_offer_status"=>"Declined", "first_offer_update_at"=>date("Y-m-d H:i:s"), "second_offer_status"=>"Declined", "second_offer_update_at"=>date("Y-m-d H:i:s")]);
            $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.id', $submission->application_id)->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();
            Session::forget("contract_from_admin");
            return view("Offers::LateSubmission.decline_screen", compact("submission", "application_data", "msg"));
        }
        elseif(isset($request->decline_waitlist))
        {
            $submission_id = $req['submission_id'];
            $program_id = $req['decline_waitlist'];


            $rs = Submissions::where("id", $submission_id)->update(["submission_status"=>"Declined / Waitlist for other"]);

            $submission = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("late_submissions_final_status.version", $version)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.*", "submissions.*")->first();
            $tmp = generateShortCode($submission);
            $tmp['offer_link'] = url('/Offers/'.$submission->offer_slug);
            $tmp['online_offer_last_date'] = getDateTimeFormat($last_online_date);
            $tmp['offline_offer_last_date'] = getDateTimeFormat($last_offline_date);
            $tmp['program_name'] = getProgramName($program_id);
            $tmp['program_name_with_grade'] = getProgramName($program_id). " - Grade ".$submission->next_grade;

            $str = DistrictConfiguration::where("name", "late_submission_offer_waitlist_screen")->select("value")->first();
            $msg = $str->value;
            

            if($submission->first_choice_final_status == "Offered" && $submission->first_waitlist_for == $program_id)
            {
                $rs = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("late_submissions_final_status.version", $version)->update(["first_offer_status"=>"Declined & Waitlisted", "first_offer_update_at"=>date("Y-m-d H:i:s"), "second_offer_status"=>"Waitlisted"]);
                $program_name = getProgramName($submission->second_waitlist_for);

            }
            elseif($submission->second_choice_final_status == "Offered" && $submission->second_waitlist_for == $program_id)
            {
                $rs = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("late_submissions_final_status.version", $version)->update(["second_offer_status"=>"Declined & Waitlisted", "second_offer_update_at"=>date("Y-m-d H:i:s"), "first_offer_status"=>"Waitlisted"]);
                $program_name = getProgramName($submission->second_waitlist_for);
            }

            if($submission->first_choice_final_status == "Offered")
                $second_program = getProgramName($submission->second_choice_program_id);
            else
                $second_program = getProgramName($submission->first_choice_program_id);

            $tmp['program_name'] = $second_program;
            $tmp['program_name_with_grade'] = $second_program . " - Grade ".$submission->next_grade;
            $tmp['waitlist_program_with_grade'] = $second_program . " - Grade ".$submission->next_grade;

            $msg = find_replace_string($msg,$tmp);



            $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.id', $submission->application_id)->where('application.district_id', Session::get('district_id'))->where("application.id", $submission->application_id)->select("application.*", "enrollments.school_year")->first();
            
            $commentObj = array();
            $commentObj['old_status'] = "Offered";
            $commentObj['new_status'] = "Declined / Waitlist for other";


            if(Session::has("contract_from_admin"))
            {
                $commentObj['comment'] = "MCPSS Admin has selected to be Waitlisted for ".$second_program . " - Grade ".$submission->next_grade;
                $commentObj['updated_by'] = Auth::user()->id;
            }
            else
            {
                $commentObj['updated_by'] = 0;
                $commentObj['comment'] = "Parent has selected to be Waitlisted for ". $second_program . " - Grade ".$submission->next_grade;
            }
            $commentObj['submission_id'] = $submission_id;
            SubmissionsStatusLog::create($commentObj);

             $submission = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("late_submissions_final_status.version", $version)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.*", "submissions.*")->first();

            $this->sendOfferEmails($submission, $req, "offer_waitlisted");


            Session::forget("contract_from_admin");
            return view("Offers::LateSubmission.wailist_screen", compact("submission", "application_data", "program_name", "msg"));
        }

    }

    public function contractOption($slug)
    {
        $submission = LateSubmissionFinalStatus::where("offer_slug", $slug)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.version as lversion", "late_submissions_final_status.*", "submissions.*", "submissions.enrollment_id as enrolid")->first();

        $date = DistrictConfiguration::where("enrollment_id", $submission->enrolid)->where("name", "last_date_late_submission_online_acceptance")->select("value")->first();
        $last_online_date = date("Y-m-d H:i:s", strtotime($date->value));

        $date = DistrictConfiguration::where("enrollment_id", $submission->enrolid)->where("name", "last_date_late_submission_offline_acceptance")->select("value")->first();
        $last_offline_date = date("Y-m-d H:i:s", strtotime($date->value));
       
        $str = DistrictConfiguration::where("name", "late_submission_contract_option_screen")->select("value")->first();
        $msg = $str->value;


        /*if(date("Y-m-d H:i:s") > $last_online_date)
        {
            echo "Time Out";
            exit;
        }
        else
        {*/
           // $submission = LateSubmissionFinalStatus::where("offer_slug", $slug)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.version as lversion", "late_submissions_final_status.*", "submissions.*")->first();
            $version = $submission->lversion;
            if($submission->contract_status == "Signed")
            {
                echo "You have already signed contract";
                exit;
            }
            else
            {
                $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.id', $submission->application_id)->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

                $program_id = 0;
                if($submission->first_offer_status == "Accepted")
                {
                    $program_id = $submission->first_waitlist_for;
                }
                elseif($submission->second_offer_status == "Accepted")
                {
                    $program_id = $submission->second_waitlist_for;
                }
                $first_program = getProgramName($program_id);

                $tmp = generateShortCode($submission);
                $tmp['offer_link'] = url('/Offers/'.$slug);
                $tmp['online_offer_last_date'] = getDateTimeFormat($last_online_date);
                $tmp['offline_offer_last_date'] = getDateTimeFormat($last_offline_date);
                $tmp['program_name'] = $first_program . " - Grade ".$tmp['next_grade'];
                $tmp['program_name_with_grade'] = $first_program. " - Grade ".$tmp['next_grade'];

                $msg = find_replace_string($msg,$tmp);

                return view("Offers::LateSubmission.contract_option",compact("submission","program_id", "application_data", "program_id", "msg", "version"));

            }
        //}
    }

    public function onlineContract($slug)
    {


        $submission = LateSubmissionFinalStatus::where("offer_slug", $slug)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.version as lversion", "late_submissions_final_status.*", "submissions.*")->first();
        $version = $submission->lversion;
        
        if(!empty($submission))
        {
            $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.id', $submission->application_id)->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

            $program_id = 0;
            if($submission->first_offer_status == "Accepted")
            {
                $program_name = getProgramName($submission->first_waitlist_for);
                $program_id = $submission->first_waitlist_for;
            }
            elseif($submission->second_offer_status == "Accepted")
            {
                $program_name = getProgramName($submission->second_waitlist_for);
                $program_id = $submission->second_waitlist_for;
            }
            if($submission->contract_status == "Signed")
            {
                $date = DistrictConfiguration::where("enrollment_id", $submission->enrollment_id)->where("name", "last_date_late_submission_online_acceptance")->select("value")->first();
                $last_online_date = date("Y-m-d H:i:s", strtotime($date->value));

                $date = DistrictConfiguration::where("enrollment_id", $submission->enrollment_id)->where("name", "last_date_late_submission_offline_acceptance")->select("value")->first();
                $last_offline_date = date("Y-m-d H:i:s", strtotime($date->value));

                $tmp = generateShortCode($submission);
                $tmp['offer_link'] = url('/Offers/'.$slug);
                $tmp['online_offer_last_date'] = getDateTimeFormat($last_online_date);
                $tmp['offline_offer_last_date'] = getDateTimeFormat($last_offline_date);
                $tmp['accepted_program_name_with_grade'] = $program_name . " - Grade ".$submission->next_grade;



                $str = DistrictConfiguration::where("name", "late_submission_offer_confirmation_screen")->select("value")->first();
                $msg = $str->value;
                $msg = find_replace_string($msg,$tmp);
                return view("Offers::LateSubmission.confirm_screen", compact("submission", "application_data", "msg"));
            }
            return view("Offers::LateSubmission.contract_text", compact("submission", "application_data", "program_name", "program_id", "version"));
        }
        else
        {
            echo "No Contract found";
        }
    }

    public function contractOptionStore(Request $request)
    {
        //return $request;
        $req = $request->all();
        
        $version = $req['version'];


        $submission = LateSubmissionFinalStatus::where("submission_id", $req['submission_id'])->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.*", "submissions.*")->first();
        $district_id = $submission->district_id;
        
        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.id', $submission->application_id)->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

        if(isset($request->online_contract_later))
        {
            /* Mail Code Here */
            $date = DistrictConfiguration::where("enrollment_id", $submission->enrollment_id)->where("name", "last_date_late_submission_online_acceptance")->select("value")->first();
            $last_online_date = date("Y-m-d H:i:s", strtotime($date->value));

            $date = DistrictConfiguration::where("enrollment_id", $submission->enrollment_id)->where("name", "last_date_late_submission_offline_acceptance")->select("value")->first();
            $last_offline_date = date("Y-m-d H:i:s", strtotime($date->value));


            $data = EditCommunication::where('district_id',$district_id)->where('status', "Contract Revisit Text")->first();
            $msg = $data->mail_body;
            $subject = $data->mail_subject;

            $tmp = generateShortCode($submission);
            $tmp['offer_link'] = url('/Offers/Contract/Fill/'.$submission->offer_slug);
            $tmp['online_offer_last_date'] = getDateTimeFormat($last_online_date);
            $tmp['offline_offer_last_date'] = getDateTimeFormat($last_offline_date);

            if($submission->first_offer_status == "Accepted")
            {
                $program_id = $submission->first_waitlist_for;
                $program_name = getProgramName($submission->first_waitlist_for) . " - Grade ".$submission->next_grade;
            }
            elseif($submission->second_offer_status == "Accepted")
            {
                $program_id = $submission->second_waitlist_for;
                $program_name = getProgramName($submission->second_waitlist_for);
            }
            $tmp['program_name'] = $program_name;
            $tmp['program_name_with_grade'] = $program_name. " - Grade ".$tmp['next_grade'];

            $msg = find_replace_string($msg,$tmp);

            $subject = find_replace_string($subject, $tmp);

            $rs = LateSubmissionFinalStatus::where("submission_id", $req['submission_id'])->where("version", $version)->update(array("contract_mode"=>"Offline"));

            $emailArr = array();
            $emailArr['email_text'] = $msg;
            $emailArr['subject'] = $subject;
            $emailArr['logo'] = getDistrictLogo();
            $emailArr['email'] = $submission->parent_email;
            try{
                Mail::send('emails.index', ['data' => $emailArr], function($message) use ($emailArr){
                        $message->to($emailArr['email']);
                        $message->subject($emailArr['subject']);
                    });
            }
            catch(\Exception $e){
                // Get error here
                //echo 'Message: ' .$e->getMessage();exit;
            }
            return view("Offers::LateSubmission.contract_later", compact("submission", "application_data", "program_name", "program_id"));


        }
        else
        {
            $program_id = 0;
            $rs = LateSubmissionFinalStatus::where("submission_id", $req['submission_id'])->where("version", $version)->update(array("contract_mode"=>"Online"));

            if($submission->first_offer_status == "Accepted")
            {
                $program_name = getProgramName($submission->first_waitlist_for);
                $program_id = $submission->first_waitlist_for;
            }
            elseif($submission->second_offer_status == "Accepted")
            {
                $program_name = getProgramName($submission->second_waitlist_for);
                $program_id = $submission->second_waitlist_for;
            }
            return view("Offers::LateSubmission.contract_text", compact("submission", "application_data", "program_name", "program_id", "version"));
        }
    }

    public function finalizeContract(Request $request)
    {
        $req = $request->all();
        $version = $req['version'];

        $submission = LateSubmissionFinalStatus::where("submission_id", $req['submission_id'])->where("late_submissions_final_status.version", $version)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->select("late_submissions_final_status.*", "submissions.*")->first();

        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.id', $submission->application_id)->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

        $rs = Submissions::where("id", $req['submission_id'])->update(["submission_status"=>"Offered and Accepted"]);

        if($submission->first_offer_status == "Accepted")
        {
            $program_name = getProgramName($submission->first_waitlist_for);
            $program_id = $submission->first_waitlist_for;
        }
        elseif($submission->second_offer_status == "Accepted")
        {
            $program_name = getProgramName($submission->second_waitlist_for);
            $program_id = $submission->second_waitlist_for;
        }

        $tmp = generateShortCode($submission);
        $data = array();
        $data['submission_id'] = $req['submission_id'];
        $data['contract_name'] = $req['contract_name'];
        $data['contract_status'] = 'Signed';
        $data['contract_signed_on'] = date("Y-m-d H:i:s");
        $rs = LateSubmissionFinalStatus::where("submission_id", $data['submission_id'])->where("version", $version)->update($data);
        $data['program_name'] = $program_name;
        $tmp['program_name_with_grade'] = $program_name. " - Grade ".$tmp['next_grade'];
        $tmp['accepted_program_name_with_grade'] = $program_name . " - Grade ".$submission->next_grade;



//        $rs = Submissions::where("id", $req['submission_id'])->update(array("submission_status"=>"Accepted"));

        $path = "resources/assets/admin/online_contract";
        $fileName = "Contract-".$submission->confirmation_no.".pdf";
        view()->share('data',$data);
        view()->share("submission", $submission);
        view()->share("application_data", $application_data);


        $pdf = PDF::loadView('Offers::LateSubmission.contract_sign',['data','application_data', 'submission']);
        $pdf->save($path . '/' . $fileName);

        $str = DistrictConfiguration::where("name", "late_submission_offer_confirmation_screen")->select("value")->first();
        $msg = $str->value;
        $msg = find_replace_string($msg,$tmp);

        $visitorData = getLocationInfoByIp($_SERVER['REMOTE_ADDR']);
        $data = array();
        $data['submission_id'] = $req['submission_id'];
        $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $data['city'] = (isset($visitorData['city']) ? $visitorData['city'] : "");
        $data['country'] = (isset($visitorData['country']) ? $visitorData['country'] : "");
        $rs = SubmissionContractsLog::create($data);


        Session::forget("contract_from_admin");

        $this->sendOfferEmails($submission, $req, "contract_signed");
        return view("Offers::LateSubmission.confirm_screen", compact("submission", "application_data", "msg"));
    }

    public function sendOfferEmails($submission, $req, $mailType)
    {
        Session::put("district_id", "3");
        $district_id = 3;//Session::get("district_id");
        $subject_str = $mailType."_mail_subject";
        $body_str = $mailType."_mail_body";

        $subject = DistrictConfiguration::where('district_id', $district_id)
            ->where('name', $subject_str)
            ->first();
        $body = DistrictConfiguration::where('district_id', $district_id)
            ->where('name', $body_str)
            ->first();

        if(!empty($body) && !empty($subject))
        {
            $district_id = Session::get("district_id");
            $date = DistrictConfiguration::where("enrollment_id", $submission->enrollment_id)->where("name", "last_date_late_submission_online_acceptance")->select("value")->first();
            $last_online_date = date("Y-m-d H:i:s", strtotime($date->value));

            $date = DistrictConfiguration::where("enrollment_id", $submission->enrollment_id)->where("name", "last_date_late_submission_offline_acceptance")->select("value")->first();
            $last_offline_date = date("Y-m-d H:i:s", strtotime($date->value));


            $msg = $body->value;
            $subject = $subject->value;



            $tmp = generateShortCode($submission);
            $tmp['offer_link'] = url('/Offers/Contract/Fill/'.$submission->offer_slug);
            $tmp['online_offer_last_date'] = getDateTimeFormat($last_online_date);
            $tmp['offline_offer_last_date'] = getDateTimeFormat($last_offline_date);

            if($submission->first_choice_final_status == "Offered")
            {
                $program_id = $submission->first_waitlist_for;
                $program_name = getProgramName($submission->first_waitlist_for);
            }
            elseif($submission->second_choice_final_status == "Offered")
            {
                $program_id = $submission->second_waitlist_for;
                $program_name = getProgramName($submission->second_waitlist_for);
            }
            $tmp['program_name_with_grade'] = $program_name . " - Grade ".$tmp['next_grade'];
            $tmp['offer_program_with_grade'] = $program_name . " - Grade ".$tmp['next_grade'];

            if($submission->first_choice_final_status == "Offered" && $submission->second_choice_final_status == "Waitlisted" && $submission->first_offer_status == "Declined & Waitlisted" && $mailType == "offer_waitlisted")
            {

                $program_id = $submission->second_waitlist_for;
                $program_name = getProgramName($submission->second_waitlist_for);
            }
            else if($submission->second_choice_final_status == "Offered" && $submission->first_choice_final_status == "Waitlisted" && $submission->second_offer_status == "Declined & Waitlisted"  && $mailType == "offer_waitlisted")
            {
                $program_id = $submission->first_waitlist_for;
                $program_name = getProgramName($submission->first_waitlist_for);
            }

            $tmp['program_name'] = $program_name;
            $tmp['waitlist_program_with_grade'] = $program_name . " - Grade ".$tmp['next_grade'];
            

            $msg = find_replace_string($msg,$tmp);

            $subject = find_replace_string($subject, $tmp);

            //$rs = LateSubmissionFinalStatus::where("submission_id", $req['submission_id'])->update(array("contract_mode"=>"Offline"));

            $emailArr = array();
            $emailArr['email_text'] = $msg;
            $emailArr['subject'] = $subject;
            $emailArr['logo'] = getDistrictLogo();
            $emailArr['email'] = $submission->parent_email;//"mcpssparent@gmail.com";

            try{
                Mail::send('emails.index', ['data' => $emailArr], function($message) use ($emailArr){
                        $message->to($emailArr['email']);
                        $message->subject($emailArr['subject']);
                    });
                $data['status'] = "Success";
            }
            catch(\Exception $e){
                // Get error here
                //echo 'Message: ' .$e->getMessage();exit;
                $data['status'] = $e->getMessage();
            }
            $data = [];
            $data['submission_id'] = $submission->id;
            $data['program_id'] = 0;
            $data['email_text'] = $data['email_body'] = $msg;
            $data['logo'] = getDistrictLogo() ?? '';;
            $data['email_to'] = $emailArr['email'];
            $data['email_subject'] = $emailArr['subject'] ?? '';
            $data['module'] = "Late Submission Offer";
            createEmailActivityLog($data);
        }
    }

    public function autoDecline()
    {

        $rs = LateSubmissionProcessLogs::where("last_date_online", "<", date("Y-m-d H:i:s"))->where('auto_decline_cron', 'N')->get();
        foreach($rs as $key=>$value)
        {
            $rs1 = LateSubmissionProcessLogs::where("id", $value->id)->update(array("auto_decline_cron" => "Y"));
            $submissions = Submissions::where('district_id', 3)->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->where("late_submissions_final_status.version", $value->version)->where('submission_status', 'Offered')->select('submissions.id', 'late_submissions_final_status.version', 'late_submissions_final_status.submission_id', 'submission_status')->get();
            foreach($submissions as $sk=>$sv)
            {
                $rs1 = Submissions::where("submission_status", "Offered")->where("id", $sv->submission_id)->update(array("submission_status"=>"Offered and Declined"));
                $rs1 = LateSubmissionFinalStatus::where("submission_id", $sv->submission_id)->where("version", $sv->version)->update(array('first_offer_status' => 'Auto Decline','second_offer_status' => 'Auto Decline'));
                $rs1 = SubmissionsStatusLog::create(array("submission_id"=>$sv->submission_id, "new_status"=>"Offered and Declined", "old_status"=>$sv->submission_status, "updated_by"=>0, "comment"=>'No response from user hence system has updated status to "Offered and Declined"'));

            }
        }
        echo "Done";exit;

    }


    public function manualFinalizeContract($submission_id)
    {
        //$req = $request->all();
        $submission = LateSubmissionFinalStatus::where("submission_id", $submission_id)->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->orderBy("late_submissions_final_status.version", "DESC")->select("late_submissions_final_status.*", "submissions.*")->first();

        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.id', $submission->application_id)->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

        $rs = Submissions::where("id", $submission_id)->update(["submission_status"=>"Offered and Accepted"]);
        $data = [];
        if($submission->first_offer_status == "Accepted")
        {
            $program_name = getProgramName($submission->first_waitlist_for);
            $program_id = $submission->first_waitlist_for;
        }
        elseif($submission->second_offer_status == "Accepted")
        {
            $program_name = getProgramName($submission->second_waitlist_for);
            $program_id = $submission->second_waitlist_for;
        }

        $data['program_name'] = $program_name;
        $data['contract_name'] = $submission->contract_name;
        $data['contract_signed_on'] = getDateFormat($submission->contract_signed_on);


//        $rs = Submissions::where("id", $req['submission_id'])->update(array("submission_status"=>"Accepted"));

        $path = "resources/assets/admin/online_contract";
        $fileName = "Contract-".$submission->confirmation_no.".pdf";
        view()->share('data',$data);
        view()->share("submission", $submission);
        view()->share("application_data", $application_data);


        $pdf = PDF::loadView('Offers::manual_contract_sign',['data','application_data', 'submission']);
        $pdf->save($path . '/' . $fileName);

        return $pdf->download($fileName);


        //echo "Done";
    }
}
