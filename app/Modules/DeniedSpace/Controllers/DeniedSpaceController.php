<?php

namespace App\Modules\DeniedSpace\Controllers;

use App\Modules\DeniedSpace\Models\{DeniedSpace, DeniedSpaceSubmission};
use Illuminate\Http\Request;
use App\Modules\EditCommunication\Models\EditCommunicationLog;
use App\Modules\Enrollment\Models\Enrollment;
use App\Http\Controllers\Controller;
use App\Modules\Application\Models\Application;
use App\Modules\Form\Models\Form;
use App\Modules\Submissions\Models\{Submissions, SubmissionGrade, SubmissionConductDisciplinaryInfo, SubmissionsFinalStatus, SubmissionsWaitlistFinalStatus, SubmissionsStatusLog, SubmissionsWaitlistStatusUniqueLog, LateSubmissionFinalStatus};
use App\Modules\Waitlist\Models\{WaitlistProcessLogs};
use App\Modules\LateSubmission\Models\{LateSubmissionProcessLogs};
use Maatwebsite\Excel\Facades\Excel;
use App\Modules\CustomCommunication\Export\{CustomCommunicationEmails};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class DeniedSpaceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct() {}

    public function index($form_id = 1)
    {
        $data = DeniedSpace::where("enrollment_id", Session::get("enrollment_id"))->where("form_id", $form_id)->first();
        return view('DeniedSpace::index', compact("form_id", "data"));
    }

    public function communication($form_id = 1)
    {
        $data = DeniedSpace::where("enrollment_id", Session::get("enrollment_id"))->where("form_id", $form_id)->first();
        return view('DeniedSpace::communication', compact("form_id", "data"));
    }

    public function form_index()
    {
        $forms = Form::where("district_id", Session::get("district_id"))->where("status", "y")->get();
        return view('DeniedSpace::form_index', compact("forms"));
    }

    public function cron_method()
    {
        $rs = Enrollment::where("begning_date", "<", date("Y-m-d"))->where("district_id", 3)->where("ending_date", ">", date("Y-m-d"))->first();
        if (empty($rs)) {
            $rs = Enrollment::where("begning_date", "<", date("Y-m-d"))->where("district_id", 3)->where("ending_date", "<", date("Y-m-d"))->orderBy("id", "DESC")->first();
            if (empty($rs)) {
                echo "Done";
                exit;
            } else {
                $enrollment_id = $rs->id;
            }
        } else {
            $enrollment_id = $rs->id;
        }
        $rsdata = DeniedSpace::where("enrollment_id", $enrollment_id)->where("waitlist_end_date", "<=", date("Y-m-d"))->where('cron_executed', 'N')->get();
        foreach ($rsdata as $key => $value) {
            $form_id = $value->form_id;
            $last_type = "";
            $version = 0;
            $submission = Submissions::where("form_id", $form_id)->whereIn("submission_status", array("Waitlisted", "Declined / Waitlist for other"))->where("enrollment_id", $enrollment_id)->get();

            $count = LateSubmissionProcessLogs::where("form_id", $form_id)->where("enrollment_id", $enrollment_id)->count();
            $count1 = WaitlistProcessLogs::where("form_id", $form_id)->where("enrollment_id", $enrollment_id)->count();


            if ($count > 0 && $count1 > 0) {
                $rs = LateSubmissionProcessLogs::where("form_id", $form_id)->where("enrollment_id", $enrollment_id)->orderBy("created_at", "desc")->first();
                $rs1 = WaitlistProcessLogs::where("form_id", $form_id)->where("enrollment_id", $enrollment_id)->orderBy("created_at", "desc")->first();
                if ($rs1->create_at > $rs->created_at) {
                    $version = $rs1->version;
                    $last_type = "waitlist";
                } else {
                    $version = $rs->version;
                    $last_type = "late_submission";
                }
            } elseif ($count1 > 0) {
                $rs1 = WaitlistProcessLogs::where("form_id", $form_id)->where("enrollment_id", $enrollment_id)->orderBy("created_at", "desc")->first();
                $version = $rs1->version;
                $last_type = "waitlist";
            } elseif ($count > 0) {
                $rs1 = LateSubmissionProcessLogs::where("form_id", $form_id)->where("enrollment_id", $enrollment_id)->orderBy("created_at", "desc")->first();
                $version = $rs1->version;
                $last_type = "late_submission";
            } else {
                $last_type = "regular";
            }


            foreach ($submission as $skey => $svalue) {
                $submission_id = $svalue->id;
                $data = $data1 = [];
                if ($last_type == "regular") {
                    $table = "submissions_final_status";

                    $data = SubmissionsFinalStatus::where("submission_id", $submission_id)->where("first_offer_status", "Waitlisted")->first();
                    if (empty($data)) {
                        $data = SubmissionsFinalStatus::where("submission_id", $submission_id)->where("first_choice_final_status", "Waitlisted")->where("first_offer_status", "Pending")->first();
                    }
                    if (!empty($data)) {
                        $tmp = [];
                        $tmp['form_id'] = $form_id;
                        $tmp['enrollment_id'] = Session::get("enrollment_id");
                        $tmp['submission_id'] = $submission_id;
                        $tmp['choice'] = "first";
                        $tmp['program_id'] = $data->first_waitlist_for;
                        $rsT = DeniedSpaceSubmission::create($tmp);

                        $rsupdate = SubmissionsFinalStatus::where("id", $data->id)->update(array("first_offer_status" => "Denied due to Space"));
                    }

                    $data1 = SubmissionsFinalStatus::where("submission_id", $submission_id)->where("second_offer_status", "Waitlisted")->first();
                    if (empty($data1)) {
                        $data1 = SubmissionsFinalStatus::where("submission_id", $submission_id)->where("second_choice_final_status", "Waitlisted")->where("second_offer_status", "Pending")->first();
                    }
                    if (!empty($data1)) {
                        $tmp = [];
                        $tmp['form_id'] = $form_id;
                        $tmp['enrollment_id'] = $enrollment_id;
                        $tmp['submission_id'] = $submission_id;
                        $tmp['choice'] = "second";
                        $tmp['program_id'] = $data1->second_waitlist_for;
                        $rsT = DeniedSpaceSubmission::create($tmp);

                        $data1 = SubmissionsFinalStatus::where("id", $data1->id)->update(array("second_offer_status" => "Denied due to Space"));
                    }
                } elseif ($last_type == "waitlist") {
                    $table = "submissions_waitlist_final_status";
                    $data = SubmissionsWaitlistFinalStatus::where("submission_id", $submission_id)->where("version", $version)->where("first_offer_status", "Waitlisted")->first();
                    if (empty($data)) {
                        $data = SubmissionsWaitlistFinalStatus::where("submission_id", $submission_id)->where("version", $version)->where("first_choice_final_status", "Waitlisted")->where("first_offer_status", "Pending")->first();
                    }
                    if (!empty($data)) {
                        $tmp = [];
                        $tmp['form_id'] = $form_id;
                        $tmp['enrollment_id'] = $enrollment_id;
                        $tmp['submission_id'] = $submission_id;
                        $tmp['choice'] = "first";
                        $tmp['program_id'] = $data->first_waitlist_for;
                        $rsT = DeniedSpaceSubmission::create($tmp);

                        $rsupdate = SubmissionsWaitlistFinalStatus::where("id", $data->id)->update(array("first_offer_status" => "Denied due to Space"));
                    }

                    $data1 = SubmissionsWaitlistFinalStatus::where("submission_id", $submission_id)->where("version", $version)->where("second_offer_status", "Waitlisted")->first();
                    if (empty($data1)) {
                        $data1 = SubmissionsWaitlistFinalStatus::where("submission_id", $submission_id)->where("version", $version)->where("second_choice_final_status", "Waitlisted")->where("second_offer_status", "Pending")->first();
                    }
                    if (!empty($data1)) {
                        $tmp = [];
                        $tmp['form_id'] = $form_id;
                        $tmp['enrollment_id'] = $enrollment_id;
                        $tmp['submission_id'] = $submission_id;
                        $tmp['choice'] = "second";
                        $tmp['program_id'] = $data1->second_waitlist_for;
                        $rsT = DeniedSpaceSubmission::create($tmp);

                        $rsupdate = SubmissionsWaitlistFinalStatus::where("id", $data1->id)->update(array("second_offer_status" => "Denied due to Space"));
                    }
                } elseif ($last_type == "late_submission") {
                    $table = "late_submissions_final_status";
                    $data = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("version", $version)->where("first_offer_status", "Waitlisted")->first();
                    if (empty($data)) {
                        $data = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("version", $version)->where("first_choice_final_status", "Waitlisted")->where("first_offer_status", "Pending")->first();
                    }

                    if (!empty($data)) {
                        $tmp = [];
                        $tmp['form_id'] = $form_id;
                        $tmp['enrollment_id'] = $enrollment_id;
                        $tmp['submission_id'] = $submission_id;
                        $tmp['choice'] = "first";
                        $tmp['program_id'] = $data->first_waitlist_for;
                        $rsT = DeniedSpaceSubmission::create($tmp);

                        $rsupdate = LateSubmissionFinalStatus::where("id", $data->id)->update(array("first_offer_status" => "Denied due to Space"));
                    }

                    $data1 = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("version", $version)->where("second_offer_status", "Waitlisted")->first();
                    if (empty($data1)) {
                        $data1 = LateSubmissionFinalStatus::where("submission_id", $submission_id)->where("version", $version)->where("second_choice_final_status", "Waitlisted")->where("second_offer_status", "Pending")->first();
                    }
                    if (!empty($data1)) {
                        $tmp = [];
                        $tmp['form_id'] = $form_id;
                        $tmp['enrollment_id'] = $enrollment_id;
                        $tmp['submission_id'] = $submission_id;
                        $tmp['choice'] = "second";
                        $tmp['program_id'] = $data1->second_waitlist_for;

                        $rsT = DeniedSpaceSubmission::create($tmp);

                        $rsupdate = LateSubmissionFinalStatus::where("id", $data1->id)->update(array("second_offer_status" => "Denied due to Space"));
                    }
                }




                $rs = SubmissionsStatusLog::create(array("submission_id" => $submission_id, "new_status" => "Denied due to Space", "old_status" => $svalue->submission_status, "updated_by" => 0, "comment" => "Expire Waitlist Cron"));

                $rsupdate = Submissions::where("id", $submission_id)->update(array("submission_status" => "Denied due to Space"));
            }
            $rsUp = DeniedSpace::where("id", $value->id)->update(array("cron_executed" => "Y"));
        }
    }

    public function store_dates(Request $request, $form_id)
    {
        $data = [];
        $data['enrollment_id'] = Session::get("enrollment_id");
        $data['district_id'] = Session::get("district_id");
        $data['form_id'] = $form_id;
        $data['waitlist_end_date'] = date('Y-m-d H:i:s', strtotime($request->expire_waitlist_date));

        $rs = DeniedSpace::updateOrCreate(["form_id" => $form_id, "enrollment_id" => Session::get("enrollment_id")], $data);

        if (isset($request->expire_now)) {
            $this->cron_method();
        }
        Session::flash("success", "Waitlist end date save successfully");
        return redirect('admin/DeniedSpace/form/date/' . $form_id);
    }

    public function store_letters(Request $request, $form_id)
    {
        $data = [];
        $data['letter_body'] = $request->letter_body;
        $data['enrollment_id'] = Session::get("enrollment_id");
        $data['district_id'] = Session::get("district_id");;
        $data['form_id'] = $form_id;
        $rs = DeniedSpace::updateOrCreate(["form_id" => $form_id, "enrollment_id" => Session::get("enrollment_id")], $data);

        if (isset($request->generate_letter_now)) {
            return $this->generate_letter_now($form_id);
        }
        Session::flash("success", "Letter content saved successfully");
        return redirect('admin/DeniedSpace/form/communication/' . $form_id);
    }


    public function generate_letter_now($form_id, $preview = false)
    {
        set_time_limit(0);
        $district_id = Session::get("district_id");
        $cdata = DeniedSpace::where("form_id", $form_id)->where("enrollment_id", Session::get("enrollment_id"))->first();

        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

        $submissions = Submissions::where("form_id", $form_id)->where('district_id', $district_id)->where("submission_status", "Denied due to Space")->get();

        $student_data = array();
        foreach ($submissions as $key => $value) {
            $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();

            $submission_data = DeniedSpaceSubmission::where("submission_id", $value->id)->get();
            $first_program_id = $second_program_id = 0;
            $first_program = $second_program = "";
            foreach ($submission_data as $sk => $sv) {
                if ($sv->choice == "first")
                    $first_program = getProgramName($sv->program_id);
                elseif ($sv->choice == "second")
                    $second_program = getProgramName($sv->program_id);
            }

            $generated = false;
            $tmp = array();
            $tmp['id'] = $value->id;
            $tmp['student_id'] = $value->student_id;
            $tmp['confirmation_no'] = $value->confirmation_no;
            $tmp['name'] = $value->first_name . " " . $value->last_name;
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
            $tmp['student_name'] = $value->first_name . " " . $value->last_name;
            $tmp['parent_name'] = $value->parent_first_name . " " . $value->parent_last_name;
            $tmp['parent_email'] = $value->parent_email;
            $tmp['student_id'] = $value->student_id;
            $tmp['submission_date'] = getDateTimeFormat($value->created_at);
            $tmp['transcript_due_date'] = getDateTimeFormat($application_data1->transcript_due_date);
            $tmp['application_url'] = url('/');
            $tmp['signature'] = get_signature('letter_signature');

            $tmp['program_name'] = getProgramName($value->first_choice_program_id);
            $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
            $tmp['choice_program_1_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
            $tmp['choice_program_2_with_grade'] = ($value->second_choice_program_id > 0 ? getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'] : "");
            $tmp['school_year'] = $application_data1->school_year;
            $tmp['enrollment_period'] = $tmp['school_year'];
            $t1 = explode("-", $tmp['school_year']);
            $tmp['next_school_year'] = ($t1[0] + 1) . "-" . ($t1[1] + 1);
            $tmp['next_year'] = date("Y") + 1;

            $tmp['offer_program'] = "";
            $tmp['offer_program_with_grade'] = "";
            $tmp['accepted_program_name_with_grade'] = "";

            $tmp['waitlist_program_1'] = $first_program;
            if ($first_program != "")
                $tmp['waitlist_program_1_with_grade'] = $first_program . " - Grade " . $value->next_grade;
            else
                $tmp['waitlist_program_1_with_grade'] = "";

            $tmp['waitlist_program_2'] = $second_program;
            if ($second_program != "")
                $tmp['waitlist_program_2_with_grade'] = $second_program . " - Grade " . $value->next_grade;
            else
                $tmp['waitlist_program_2_with_grade'] = "";

            $tmp['offer_link'] = "";

            $msg = find_replace_string($cdata->letter_body, $tmp);
            $msg = str_replace("{", "", $msg);
            $msg = str_replace("}", "", $msg);
            $tmp['letter_body'] = $msg;
            $student_data[] = $tmp;
        }
        if ($preview == true) {
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
        view()->share('student_data', $student_data);
        view()->share("application_data", $application_data);

        $fileName =  "EditCustomCommunication-" . strtotime(date("Y-m-d H:i:s")) . '.pdf';
        $path = "resources/assets/admin/edit_communication";
        if ($preview) {
            $pdf = Pdf::loadView('CustomCommunication::letterview', ['student_data', 'application_data']);
            $fileName = "preview.pdf";
            $pdf->save($path . '/' . $fileName);
            return response()->file($path . "/" . $fileName);
        } else {
            $pdf = Pdf::loadView('CustomCommunication::letterview', ['student_data', 'application_data']);
            $pdf->save($path . '/' . $fileName);

            $data = array();
            $data['district_id'] = Session::get("district_id");
            $data['communication_type'] = "Letter";
            $data['status'] = "Denied due to Space";
            $data['file_name'] = $fileName;
            $data['total_count'] = count($student_data);
            $data['generated_by'] = Auth::user()->id;
            EditCommunicationLog::create($data);
            return $pdf->download($fileName);
        }
    }



    public function send_email_now($form_id, $preview = false)
    {
        set_time_limit(0);
        $district_id = Session::get("district_id");
        $cdata = DeniedSpace::where("form_id", $form_id)->where("enrollment_id", Session::get("enrollment_id"))->first();

        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

        $submissions = Submissions::where("form_id", $form_id)->where('district_id', $district_id)->where("submission_status", "Denied due to Space")->get();

        $student_data = array();
        $countMail = 0;
        if ($preview == true) {
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


            $msg = find_replace_string($cdata->mail_body, $tmp);
            $msg = str_replace("{", "", $msg);
            $msg = str_replace("}", "", $msg);
            $msg = $cdata->mail_body;
            $tmp['email_text'] = $msg;
            $tmp['logo'] = getDistrictLogo();


            $msg = find_replace_string($cdata->mail_subject, $tmp);
            $msg = str_replace("{", "", $msg);
            $msg = str_replace("}", "", $msg);
            $tmp['subject'] = $msg;
            $data = $tmp;
            $type = "regular";
            $status = "Denied due to Space";
            return view("emails.preview_denied_waitlist_index", compact('data', 'status', 'form_id'));
        } else {
            foreach ($submissions as $key => $value) {
                $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();

                $submission_data = DeniedSpaceSubmission::where("submission_id", $value->id)->get();
                $first_program_id = $second_program_id = 0;
                $first_program = $second_program = "";
                foreach ($submission_data as $sk => $sv) {
                    if ($sv->choice == "first")
                        $first_program = getProgramName($sv->program_id);
                    elseif ($sv->choice == "second")
                        $second_program = getProgramName($sv->program_id);
                }

                $generated = false;
                $tmp = array();
                $tmp['id'] = $value->id;
                $tmp['student_id'] = $value->student_id;
                $tmp['confirmation_no'] = $value->confirmation_no;
                $tmp['name'] = $value->first_name . " " . $value->last_name;
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
                $tmp['student_name'] = $value->first_name . " " . $value->last_name;
                $tmp['parent_name'] = $value->parent_first_name . " " . $value->parent_last_name;
                $tmp['parent_email'] = $value->parent_email;
                $tmp['student_id'] = $value->student_id;
                $tmp['submission_date'] = getDateTimeFormat($value->created_at);
                $tmp['transcript_due_date'] = getDateTimeFormat($application_data1->transcript_due_date);
                $tmp['application_url'] = url('/');
                $tmp['signature'] = get_signature('letter_signature');

                $tmp['program_name'] = getProgramName($value->first_choice_program_id);
                $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
                $tmp['choice_program_1_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
                $tmp['choice_program_2_with_grade'] = ($value->second_choice_program_id > 0 ? getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'] : "");
                $tmp['school_year'] = $application_data1->school_year;
                $tmp['enrollment_period'] = $tmp['school_year'];
                $t1 = explode("-", $tmp['school_year']);
                $tmp['next_school_year'] = ($t1[0] + 1) . "-" . ($t1[1] + 1);
                $tmp['next_year'] = date("Y") + 1;

                $tmp['offer_program'] = "";
                $tmp['offer_program_with_grade'] = "";
                $tmp['accepted_program_name_with_grade'] = "";

                $tmp['waitlist_program_1'] = $first_program;
                if ($first_program != "")
                    $tmp['waitlist_program_1_with_grade'] = $first_program . " - Grade " . $value->next_grade;
                else
                    $tmp['waitlist_program_1_with_grade'] = "";

                $tmp['waitlist_program_2'] = $second_program;
                if ($second_program != "")
                    $tmp['waitlist_program_2_with_grade'] = $second_program . " - Grade " . $value->next_grade;
                else
                    $tmp['waitlist_program_2_with_grade'] = "";

                $tmp['offer_link'] = "";

                $msg = find_replace_string($cdata->mail_body, $tmp);
                $msg = str_replace("{", "", $msg);
                $msg = str_replace("}", "", $msg);
                $tmp['msg'] = $msg;

                $msg = find_replace_string($cdata->mail_subject, $tmp);
                $msg = str_replace("{", "", $msg);
                $msg = str_replace("}", "", $msg);
                $tmp['subject'] = $msg;

                $tmp['email'] = $value->parent_email;
                $student_data[] = array($value->id, $tmp['name'], $tmp['parent_name'], $tmp['parent_email'], $tmp['grade']);
                if (config('variables.environment') == 'production')
                    $countMail = 0;
                if ($countMail == 0) {
                    sendMail($tmp);
                }
                $countMail++;
            }
            ob_end_clean();
            ob_start();
            $fileName =  "EditCustomCommunication-" . strtotime(date("Y-m-d H:i:s")) . ".xlsx";
            $data = array();
            $data['district_id'] = Session::get("district_id");
            $data['communication_type'] = "Email";
            $data['mail_subject'] = $cdata->mail_subject;
            $data['mail_body'] = $cdata->mail_body;
            $data['status'] = "Denied due to Space";
            $data['file_name'] = $fileName;
            $data['total_count'] = count($student_data);
            $data['generated_by'] = Auth::user()->id;
            EditCommunicationLog::create($data);

            Excel::store(new CustomCommunicationEmails(collect($student_data)), $fileName, 'edit_communication');
        }
    }

    public function store_emails(Request $request, $form_id)
    {
        $data = [];
        $data['mail_body'] = $request->mail_body;
        $data['mail_subject'] = $request->mail_subject;
        $data['enrollment_id'] = Session::get("enrollment_id");
        $data['district_id'] = Session::get("district_id");;
        $data['form_id'] = $form_id;
        $rs = DeniedSpace::updateOrCreate(["form_id" => $form_id, "enrollment_id" => Session::get("enrollment_id")], $data);

        if (isset($request->send_email_now)) {
            $this->send_email_now($form_id);
            Session::flash("success", "Custom Communication emails sent successfully.");
        }
        Session::flash("success", "Email content saved successfully");
        return redirect('admin/DeniedSpace/form/communication/' . $form_id);
    }

    public function preview_letter($form_id = 1)
    {
        return $this->generate_letter_now($form_id, true);
    }

    public function preview_email($form_id = 1)
    {
        return $this->send_email_now($form_id, true);
    }

    public function sendTestMail(Request $request)
    {
        $req = $request->all();
        $email = $req['email'];
        $form_id = $req['form_id'];
        $district_id = 3;

        $district_id = Session::get("district_id");
        $cdata = DeniedSpace::where("form_id", $form_id)->where("enrollment_id", Session::get("enrollment_id"))->first();

        $application_data = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->select("application.*", "enrollments.school_year")->first();

        $value = Submissions::where("form_id", $form_id)->where('district_id', $district_id)->where("submission_status", "Denied due to Space")->first();
        $application_data1 = Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where('application.district_id', Session::get('district_id'))->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();

        $submission_data = DeniedSpaceSubmission::where("submission_id", $value->id)->get();
        $first_program_id = $second_program_id = 0;
        $first_program = $second_program = "";
        foreach ($submission_data as $sk => $sv) {
            if ($sv->choice == "first")
                $first_program = getProgramName($sv->program_id);
            elseif ($sv->choice == "second")
                $second_program = getProgramName($sv->program_id);
        }

        $generated = false;
        $tmp = array();
        $tmp['id'] = $value->id;
        $tmp['student_id'] = $value->student_id;
        $tmp['confirmation_no'] = $value->confirmation_no;
        $tmp['name'] = $value->first_name . " " . $value->last_name;
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
        $tmp['student_name'] = $value->first_name . " " . $value->last_name;
        $tmp['parent_name'] = $value->parent_first_name . " " . $value->parent_last_name;
        $tmp['parent_email'] = $value->parent_email;
        $tmp['student_id'] = $value->student_id;
        $tmp['submission_date'] = getDateTimeFormat($value->created_at);
        $tmp['transcript_due_date'] = getDateTimeFormat($application_data1->transcript_due_date);
        $tmp['application_url'] = url('/');
        $tmp['signature'] = get_signature('letter_signature');

        $tmp['program_name'] = getProgramName($value->first_choice_program_id);
        $tmp['program_name_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
        $tmp['choice_program_1_with_grade'] = getProgramName($value->first_choice_program_id) . " - Grade " . $tmp['next_grade'];
        $tmp['choice_program_2_with_grade'] = ($value->second_choice_program_id > 0 ? getProgramName($value->second_choice_program_id) . " - Grade " . $tmp['next_grade'] : "");
        $tmp['school_year'] = $application_data1->school_year;
        $tmp['enrollment_period'] = $tmp['school_year'];
        $t1 = explode("-", $tmp['school_year']);
        $tmp['next_school_year'] = ($t1[0] + 1) . "-" . ($t1[1] + 1);
        $tmp['next_year'] = date("Y") + 1;

        $tmp['offer_program'] = "";
        $tmp['offer_program_with_grade'] = "";
        $tmp['accepted_program_name_with_grade'] = "";

        $tmp['waitlist_program_1'] = $first_program;
        if ($first_program != "")
            $tmp['waitlist_program_1_with_grade'] = $first_program . " - Grade " . $value->next_grade;
        else
            $tmp['waitlist_program_1_with_grade'] = "";

        $tmp['waitlist_program_2'] = $second_program;
        if ($second_program != "")
            $tmp['waitlist_program_2_with_grade'] = $second_program . " - Grade " . $value->next_grade;
        else
            $tmp['waitlist_program_2_with_grade'] = "";

        $tmp['offer_link'] = "";

        $msg = find_replace_string($cdata->mail_body, $tmp);
        $msg = str_replace("{", "", $msg);
        $msg = str_replace("}", "", $msg);
        $tmp['msg'] = $msg;

        $msg = find_replace_string($cdata->mail_subject, $tmp);
        $msg = str_replace("{", "", $msg);
        $msg = str_replace("}", "", $msg);
        $tmp['subject'] = $msg;

        $tmp['email'] = $value->parent_email;
        sendMail($tmp);

        echo "done";
    }


    public function fetchEmails(Request $request)
    {
        $form_id = $request->form_id;
        $district_id = Session::get("district_id");
        $submissions = Submissions::where("form_id", $form_id)->where('district_id', $district_id)->where("submission_status", "Denied due to Space")->get();

        $student_data = array();
        foreach ($submissions as $key => $value) {
            $tmp = array();
            $tmp['id'] = $value->id;
            $tmp['student_name'] = $value->first_name . " " . $value->last_name;
            $tmp['grade'] = $value->next_grade;
            $tmp['parent_name'] = $value->parent_first_name . " " . $value->parent_last_name;
            $tmp['parent_email'] = $value->parent_email;
            $student_data[] = $tmp;
        }
        return json_encode($student_data);
    }
}
