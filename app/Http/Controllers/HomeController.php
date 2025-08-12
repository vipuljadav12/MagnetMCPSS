<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\District\Models\District;
use App\Modules\Form\Models\Form;
use App\Modules\Form\Models\FormBuild;
use App\Modules\Form\Models\FormFields;
use App\Modules\Form\Models\FormContent;
use App\Modules\Application\Models\{Application, ApplicationConfiguration, ApplicationProgram};
use App\Modules\Submissions\Models\{Submissions, SubmissionSteps};
use App\Modules\ZonedSchool\Models\NoZonedSchool;
use App\ZoneAPI;
use App\IncorrectStudent;
use App\Modules\School\Models\School;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\Program\Models\{Program, ProgramEligibility};
use App\SubmissionRaw;
use App\SubmissionDocuments;
use Illuminate\Database\Eloquent\Model;
use App\Traits\AuditTrail;
use App\IncorrectStudentBDay;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    use AuditTrail;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($from = "")
    {
        Session::put("district_id", 3);
        Session::forget("step_session");
        if ($from == "")
            Session::forget("from_admin");

        if (Session::has("from_admin")) {
            $application_data = Application::where('admin_starting_date', '<=', date('Y-m-d H:i:s'))->where('admin_ending_date', '>=', date('Y-m-d H:i:s'))->where('district_id', Session::get('district_id'))->where("status", "Y")->first();
        } else {
            $application_data = Application::where('starting_date', '<=', date('Y-m-d H:i:s'))->where('ending_date', '>=', date('Y-m-d H:i:s'))->where('district_id', Session::get('district_id'))->where("status", "Y")->first();
        }

        if (Session::has("district_id") && Session::get("district_id") != 0) {
            $district = District::where("id", Session::get("district_id"))->first();
            if ($district->mcpss_zone_api == "N") {
                Session::put("mcpss_zone_api", "N");
            } else {
                Session::forget("mcpss_zone_api");
            }
            if ($district->zone_api == "N") {
                Session::put("zone_api", "N");
            } else {
                Session::forget("zone_api");
            }

            if (Session::has("from_admin") && $district->admin_mcpss_zone_api == "N") {
                Session::put("zone_api", "N");
                Session::put("mcpss_zone_api", "N");
            }

            Session::put("theme_color", $district->theme_color);


            if (!empty($application_data)) {
                $district = District::where("id", Session::get("district_id"))->first();
                Session::put("enrollment_id", $application_data->enrollment_id);
                return view('layouts.front.index', compact('district', 'application_data'));
            } else {
                $application_data = Application::where('district_id', Session::get('district_id'))->where("status", "Y")->first();
                if (!empty($application_data)) {
                    if (strtotime($application_data->ending_date) > strtotime(date("Y-m-d H:i:s")))
                        $msg_type = "before_application_open_text";
                    else
                        $msg_type = "after_application_open_text";
                } else {
                    $msg_type = "after_application_open_text";
                }
                return view('layouts.errors.msgs', compact("district", "msg_type", "application_data"));
            }
        } else {
            /* Session::put("district_id", 3);
            $district = District::where("id", 3)->first();
            Session::put("theme_color", $district->theme_color);
            if(empty($application_data))
                    $application_data = Application::where('district_id', Session::get("district_id"))->where("status", "Y")->first();
           return view('layouts.front.index',compact('district',"application_data"));*/
            return redirect('/login');
        }
    }


    public function indexStage($from = "")
    {
        Session::forget("step_session");
        if ($from == "")
            Session::forget("from_admin");
        $application_data = Application::where('district_id', Session::get('district_id'))->where("status", "Y")->first();
        if (Session::has("district_id") && Session::get("district_id") != 0) {
            $district = District::where("id", Session::get("district_id"))->first();

            if ($district->mcpss_zone_api == "N") {
                Session::put("mcpss_zone_api", "N");
            } else {
                Session::forget("mcpss_zone_api");
            }
            if ($district->zone_api == "N") {
                Session::put("zone_api", "N");
            } else {
                Session::forget("zone_api");
            }
            Session::put("theme_color", $district->theme_color);


            if (!empty($application_data)) {
                $district = District::where("id", Session::get("district_id"))->first();
                return view('layouts.front.index', compact('district', 'application_data'));
            } else {
                $application_data = Application::where('district_id', Session::get('district_id'))->where("status", "Y")->first();
                if (!empty($application_data)) {
                    $msg_type = "before_application_open_text";
                } else {
                    $msg_type = "after_application_open_text";
                }
                return view('layouts.errors.msgs', compact("district", "msg_type", "application_data"));
            }
        } else {
            Session::put("district_id", 3);
            $district = District::where("id", 3)->first();
            Session::put("theme_color", $district->theme_color);
            if (empty($application_data))
                $application_data = Application::where('district_id', Session::get("district_id"))->where("status", "Y")->first();
            return view('layouts.front.index', compact('district', "application_data"));
            return redirect('/login');
        }
    }

    public function phoneSubmission()
    {
        if (Auth::check()) {
            Session::put("from_admin", "Y");
        }
        return $this->index("Admin");
    }

    public function showProcessForm(Request $request)
    {
        Session::forget("form_data");
        $req = $request->all();

        if (!Session::has("step_session")) {
            Session::put("step_session", strtotime(date("Y-m-d H:i:s")));
        }
        $rsp = SubmissionSteps::updateOrCreate(["session_id" => Session::get("step_session")], ['step_no' => 1]);

        // return  $req;
        $page_id = $req['page_id'];
        $form_id = $req['form_id'];
        $application_id = $req['application_id'];
        Session::put("application_id", $application_id);
        $application_data = Application::where('id', $application_id)->first();

        $district = District::where("id", Session::get("district_id"))->first();
        if ($req['student_status'] == "exist") {
            $data = $this->getFormbyId($form_id, $page_id, 'exist');

            $form_type = "exist";
            return view("layouts.front.form_display", compact("district", "data", "page_id", "form_id", "form_type", "application_id", "application_data"));
        } else {
            $data = $this->getFormbyId($form_id, $page_id, 'new');
            //return $data;
            $form_type = "new";
            return view('layouts.front.form_display', compact("district", "data", "page_id", "form_id", "form_type", "application_id", "application_data"));
        }
    }

    public function previewForm($page_id, $form_id)
    {
        $district = District::where("id", Session::get("district_id"))->first();
        $data = $this->getFormbyId($form_id, $page_id);
        return view('layouts.front.preview_form', compact("district", "data", "page_id", "form_id"));
    }

    public function getFormbyId($form_id, $page_id = 1, $type = 'new')
    {
        $data = FormBuild::where('form_build.form_id', $form_id)->where('page_id', $page_id)->whereIn('form_build.id', function ($query) use ($type) {
            $query->select('build_id')->from('form_content')->where('field_property', 'show_in_exist')->where(function ($query) use ($type) {
                return $query->where('field_value', $type)->orWhere('field_value', 'both');
            });
        })->orderBy('sort', 'ASC')->get();
        return $data;
    }

    public function showNextStep(Request $request)
    {
        if (!Session::has("step_session")) {
            Session::put("step_session", strtotime(date("Y-m-d H:i:s")));
        }
        $district = District::where("id", Session::get("district_id"))->first();

        $req = $request->all();

        if (isset($req['submit']) && $req['submit'] == "Prev Step") {
            $req['page_id'] = $req['page_id'] - 1;
        }


        $form_id = $req['form_id'];
        $no_of_pages = $req['no_of_pages'];
        $page_id = $req['page_id'];
        $application_id = Session::get('application_id');
        $application_data = Application::where('id', $application_id)->first();
        $form_type = $req['form_type'];
        // /Session::push("form_type", $form_type);
        $tmpArray = array();
        if ($req['form_type'] != "exist") {
            $next_grade_id = fetch_student_field_id($form_id, "next_grade");
            $grade_id = 0;
            if (isset($req['formdata'][$next_grade_id])) {
                $grade_id = $req['formdata'][$next_grade_id];
            } elseif (Session::has("form_data")) {
                $fdata = Session::get("form_data")[0]['formdata'];
                if (isset($fdata[$next_grade_id])) {
                    $grade_id = $fdata[$next_grade_id];
                }
            }
            if ($grade_id != 0) {
                $rs_grade = DB::table("grade")->where("name", $grade_id)->first();
                $rs_application = ApplicationProgram::where("grade_id", $rs_grade->id)->where("application_id", $application_id)->first();
                if (empty($rs_application)) {
                    $this->destroySessions();
                    return redirect("/msgs/nograde");
                }
            }
        } else {
            $student_field_id = fetch_student_field_id($form_id);
            $student_grade_id = fetch_student_field_id($form_id, 'current_grade');
            $next_grade_required = true;
            if (isset($req['formdata'][$student_grade_id])) {
                $next_grade_required = false;
            }

            // dd($student_field_id);

            if ($student_field_id == 0) {
                $this->destroySessions();
                return redirect(url('/msgs/nostudent'));
            } else {
                if (isset($req['formdata'][$student_field_id])) {
                    $student_birthday_id = fetch_student_field_id($form_id, "birthday");
                    if ($student_birthday_id  == 0) {
                        return redirect(url('/msgs/nostudent'));
                    }
                    //      echo $req['formdata'][$student_birthday_id];exit;
                    $student_data = DB::table("student")->where("stateID", $req['formdata'][$student_field_id])->where('birthday', $req['formdata'][$student_birthday_id])->first();
                    if (empty($student_data)) {
                        $bd = IncorrectStudentBDay::create(["student_id" => $req['formdata'][$student_field_id], "birthday" => $req['formdata'][$student_birthday_id]]);

                        return redirect(url('/msgs/nostudent'));
                    } else {
                        $db_fields = FormContent::where('form_id', $form_id)->where('field_property', 'db_field')->get();
                        //print_r($db_fields);
                        $dataArray = array();
                        $dataArray['formdata'] = array();
                        if (Session::has("form_data")) {
                            if (isset(Session::get("form_data")[0])) {
                                $dataArray =  Session::get("form_data")[0];
                            }
                            Session::forget("form_data");
                        }


                        $formdata = $dataArray['formdata'];
                        $current_grade = 404;
                        $current_school = "";
                        foreach ($db_fields as $key => $value) {
                            if (isset($student_data->{$value->field_value})) {
                                if ($next_grade_required && $value->field_value == "current_grade") {
                                    $current_grade = $student_data->{$value->field_value};

                                    $formdata[$value->build_id] = $current_grade;
                                    if ($current_grade == "ASK-97" || $current_grade == "ASK-98") {
                                        $this->destroySessions();
                                        return redirect("/msgs/nograde");
                                    }
                                } elseif ($value->field_value == "current_school") {
                                    $current_school = $student_data->{$value->field_value};
                                    $formdata[$value->build_id] = $current_school;
                                } else {
                                    if ($value->field_value == "student_id")
                                        $formdata[$value->build_id] = $student_data->stateID;
                                    else if ($value->field_value != "parent_first_name" && $value->field_value != "parent_last_name")
                                        $formdata[$value->build_id] = $student_data->{$value->field_value};
                                }
                                if ($value->field_value != "parent_first_name" && $value->field_value != "parent_last_name") {
                                    if ($value->field_value == "current_grade" && $next_grade_required)
                                        $tmpArray[] = $value->build_id;
                                    elseif ($value->field_value != "current_grade")
                                        $tmpArray[] = $value->build_id;
                                }
                            }
                        }

                        //    print_r($formdata);exit;

                        if ($current_grade != 404) {
                            if ($next_grade_required) {
                                if ($current_grade == "PreK") {
                                    $next_grade = "K";
                                } elseif ($current_grade == "K") {
                                    $next_grade = "1";
                                } else {
                                    $next_grade = $current_grade + 1;
                                }
                                $rs_next = FormContent::where('field_property', 'db_field')->where('field_value', 'next_grade')->where('form_id', $form_id)->first();
                                if (!empty($rs_next)) {
                                    // if($next_grade == 0)
                                    //     $formdata[$rs_next->build_id] = "1";
                                    // else
                                    $formdata[$rs_next->build_id] = $next_grade;
                                }
                            }
                        }
                        $dataArray['formdata'] = $formdata;
                        Session::push("form_data", $dataArray);
                    }
                }
            }
        }
        $application_data = Application::where("id", $application_id)->first();


        if (Session::has("form_data")) {

            $dataArray =  Session::get("form_data")[0];
            Session::forget("form_data");
        } else
            $dataArray = array();

        if (isset($dataArray['formdata'])) {
            $formdata = $dataArray['formdata'];
        } else
            $formdata = array();


        foreach ($req as $key => $value) {
            if (!in_array($key, array("_token", "no_of_pages")) && !in_array($key, $tmpArray)) {
                $dataArray[$key] = $value;
            }
        }

        $newformdata = $dataArray['formdata'];
        $tmpformdata = array();

        foreach ($formdata as $key => $value) {
            $tmpformdata[$key] = $value;
        }
        foreach ($newformdata as $key => $value) {
            if (!in_array($key, $tmpArray))
                $tmpformdata[$key] = $value;
        }

        $dataArray['formdata'] = $tmpformdata;
        $get_mcp_id = fetch_student_field_id($form_id, "mcp_employee");
        Session::push("form_data", $dataArray);

        $dupicate_submission = $this->find_duplicate_submission($form_id, $req['form_type']);
        if (count($dupicate_submission) > 0) {

            $this->destroySessions();
            if ($dupicate_submission[0] == "processed") {
                $msg_type = "duplicate_processed_application_msg";
                return redirect(url('/msgs/processed'));
            } else {
                $msg_type = "duplicate_application_msg";
                return redirect(url('/msgs/duplicate'));
            }

            //$msg_type="duplicate_application_msg";
            //return view('layouts.errors.msgs',compact("district","msg_type","application_data"));  
        }

        $stopNext = false;
        if ($req['form_type'] != "exist") {
            if (Session::has("from_admin") && $district->admin_mcpss_zone_api == "N") {
                session()->forget('zonemsg');
            } else {
                if ($district->zone_api == "Yes") // && Session::has("from_admin"))
                {
                    if (isset($dataArray['formdata'][$get_mcp_id]) && $district->mcpss_zone_api == "No" && $dataArray['formdata'][$get_mcp_id] == "Yes") {
                        session()->forget('zonemsg');
                    } else {
                        if (!$this->getZonedSchool($form_id) && $req['form_type'] != "exist") {
                            $this->destroySessions();
                            return redirect(url('/msgs/nozone'));
                            session()->flash('zonemsg', getAlertMsg('zone_address_not_available'));
                            $stopNext = true;
                        } else {
                            session()->forget('zonemsg');
                        }
                    }
                } else {
                    session()->forget('zonemsg');
                }
            }
        } else {
            if ($district->zone_api_existing == "Yes") {
                if (!$this->getZonedSchool($form_id)) {
                    session()->flash('zonemsg', getAlertMsg('zone_address_not_available'));
                    $stopNext = true;
                } else {
                    session()->forget('zonemsg');
                }
            }
        }
        if ($page_id < $no_of_pages) {
            // Session::forget("form_data");
            if (isset($req['submit']) && $req['submit'] != "Prev Step" && !$stopNext) {
                $page_id = $page_id + 1;
            }
            $rsp = SubmissionSteps::updateOrCreate(["session_id" => Session::get("step_session")], ['step_no' => $page_id]);

            $data = $this->getFormbyId($form_id, $page_id, $req['form_type']);
            if (count($data) > 0) {
                $sessionarray = $tmpformdata;
                return view('layouts.front.form_display', compact("district", "data", "page_id", "form_type", "form_id", "application_id", "sessionarray", "application_data"));
            } else {
                // return Session::all();
                return $this->submit_form($form_id, $form_type);
            }
        } else {
            /* Here for save code will be done */
            return $this->submit_form($form_id, $form_type);
        }
    }


    public function submit_form($form_id, $form_type)
    {
        $dupicate_submission = array(); //$this->find_duplicate_submission($form_id);
        $district = District::where("id", Session::get("district_id"))->first();
        if (count($dupicate_submission) > 0) {
            $this->destroySessions();
            $msg_type = "duplicate_application_msg";
            return redirect(url('/msgs/duplicate'));
        } else {
            $confirmation_no = $this->saveFrontForm($form_type);


            $submission_data = Submissions::where('confirmation_no', $confirmation_no)->first();

            $field_id = fetch_student_field_id($form_id, "next_grade");
            $next_grade = Session::get("form_data")[0]["formdata"][$field_id];

            $field_id = fetch_student_field_id($form_id, "current_grade");
            $current_grade = Session::get("form_data")[0]["formdata"][$field_id];


            //$field_id = fetch_student_field_id($form_id, "next_grade"); $data = Session::get("form_data")[0]["formdata"][$field_id];

            $emailArr = array();
            $emailArr['application_id'] = Session::get("application_id");

            $field_id = fetch_student_field_id($form_id, "first_name");
            $first_name = Session::get("form_data")[0]["formdata"][$field_id];

            $field_id = fetch_student_field_id($form_id, "last_name");
            $last_name = Session::get("form_data")[0]["formdata"][$field_id];

            $field_id = fetch_student_field_id($form_id, "parent_first_name");
            $parent_first_name = Session::get("form_data")[0]["formdata"][$field_id];

            $field_id = fetch_student_field_id($form_id, "parent_last_name");
            $parent_last_name = Session::get("form_data")[0]["formdata"][$field_id];

            $field_id = fetch_student_field_id($form_id, "parent_email");
            $parent_email = Session::get("form_data")[0]["formdata"][$field_id];

            $emailArr['first_name'] = $first_name;
            $emailArr['last_name'] = $last_name;
            $emailArr['parent_first_name'] = $parent_first_name;
            $emailArr['parent_last_name'] = $parent_last_name;
            $emailArr['email'] = $parent_email;
            $emailArr['confirm_number'] = $confirmation_no;
            $emailArr['signature'] = get_signature('email_signature');
            $emailArr['student_id'] = $submission_data->student_id;
            $emailArr['next_grade'] = $next_grade;
            $emailArr['current_grade'] = $current_grade;
            $emailArr['submission_date'] = getDateTimeFormat($submission_data->created_at);
            $emailArr['current_school'] = $submission_data->current_school;
            $emailArr['zoned_school'] = $submission_data->current_school;
            $emailArr['parent_name'] = $parent_first_name . " " . $parent_last_name;
            $emailArr['student_name'] = $first_name . " " . $last_name;




            $msg_data = ApplicationConfiguration::where("application_id", $emailArr['application_id'])->first();
            $application_data = Application::where("id", $emailArr['application_id'])->first();
            $emailArr['transcript_due_date'] = getDateTimeFormat($application_data->transcript_due_date);

            if ($form_type == "exist" || $submission_data->submission_status == "Active") {
                //here i'll write code for active email
                $student_type = "active";
                $emailArr['type'] = "active_email";
                $emailArr['msg'] = $msg_data->active_email;
                $confirm_msg = $msg_data->active_screen;

                //$this->sentSuccessEmail("active_email");
                $msg_type = "exists_success_application_msg";
                $emailArr['email'] =    $parent_email; //'mcpssparent@gmail.com';
                $subject = $msg_data->active_email_subject;
                $confirm_title = $msg_data->active_screen_title;
                $confirm_subject = $msg_data->active_screen_subject;
            } else {
                //here i'll write pending email code
                $emailArr['type'] = "pending_email";
                $student_type = "pending";
                //$this->sentSuccessEmail("pending_email");                
                $msg_type = "new_success_application_msg";
                $emailArr['email'] = $parent_email;
                $emailArr['msg'] = $msg_data->pending_email;
                $confirm_msg = $msg_data->pending_screen;
                $subject = $msg_data->pending_email_subject;
                $confirm_title = $msg_data->pending_screen_title;
                $confirm_subject = $msg_data->pending_screen_subject;
            }

            $subject = find_replace_string($subject, $emailArr);
            $subject = str_replace("{", "", $subject);
            $subject = str_replace("}", "", $subject);

            $subject = str_replace("{student_name}", $emailArr['first_name'] . " " . $emailArr['last_name'], $subject);
            $subject = str_replace("{parent_name}", $emailArr['parent_first_name'] . " " . $emailArr['parent_last_name'], $subject);
            $subject = str_replace("{confirm_number}", $emailArr['confirm_number'], $subject);
            $subject = str_replace("{confirmation_no}", $emailArr['confirm_number'], $subject);
            $emailArr['subject'] = $subject;


            $confirm_subject = find_replace_string($confirm_subject, $emailArr);
            $confirm_subject = str_replace("{", "", $confirm_subject);
            $confirm_subject = str_replace("}", "", $confirm_subject);
            $confirm_subject = str_replace("{student_name}", $emailArr['first_name'] . " " . $emailArr['last_name'], $confirm_subject);
            $confirm_subject = str_replace("{parent_name}", $emailArr['parent_first_name'] . " " . $emailArr['parent_last_name'], $confirm_subject);
            $confirm_subject = str_replace("{confirm_number}", $emailArr['confirm_number'], $confirm_subject);
            $confirm_subject = str_replace("{confirmation_no}", $emailArr['confirm_number'], $confirm_subject);

            $confirm_title = find_replace_string($confirm_title, $emailArr);
            $confirm_title = str_replace("{", "", $confirm_title);
            $confirm_title = str_replace("}", "", $confirm_title);
            $confirm_title = str_replace("{student_name}", $emailArr['first_name'] . " " . $emailArr['last_name'], $confirm_title);
            $confirm_title = str_replace("{parent_name}", $emailArr['parent_first_name'] . " " . $emailArr['parent_last_name'], $confirm_title);
            $confirm_title = str_replace("{confirm_number}", $emailArr['confirm_number'], $confirm_title);
            $confirm_title = str_replace("{confirmation_no}", $emailArr['confirm_number'], $confirm_title);

            $this->sentSuccessEmail($emailArr);
            $rsp = SubmissionSteps::updateOrCreate(["session_id" => Session::get("step_session")], ['step_no' => 4]);
            $this->destroySessions();



            $confirm_msg = str_replace("{student_name}", $emailArr['first_name'] . " " . $emailArr['last_name'], $confirm_msg);
            $confirm_msg = str_replace("{parent_name}", $emailArr['parent_first_name'] . " " . $emailArr['parent_last_name'], $confirm_msg);
            $confirm_msg = str_replace("{confirm_number}", $emailArr['confirm_number'], $confirm_msg);
            $confirm_msg = str_replace("{confirmation_no}", $emailArr['confirm_number'], $confirm_msg);
            $confirm_msg = str_replace("{transcript_due_date}", getDateTimeFormat($application_data->transcript_due_date), $confirm_msg);
            return view('layouts.errors.confirm_screen', compact("district", "msg_type", "confirmation_no", "confirm_msg", "confirm_subject", "confirm_title", "student_type", "application_data"));

            // return view('layouts.errors.success_application',compact("district","confirmation_no"));

        }
    }

    public function getZonedSchool($form_id)
    {
        if (Session::has("form_data")) {
            $dataArray =  Session::get("form_data")[0];
        }
        $formdata = $dataArray['formdata'];
        $db_fields = FormContent::where('form_id', $form_id)->where('field_property', 'db_field')->get();

        $address = $city = $state = $zip = $zoned_school = "";
        $zoned_field_id = $next_grade = 0;
        foreach ($db_fields as $key => $value) {
            if ($value->field_value == "zoned_school") {
                $zoned_field_id = $value->build_id;
            } elseif (in_array($value->field_value, array("address", "city", "zip", "state", "next_grade"))) {
                if (isset($formdata[$value->build_id])) {
                    ${$value->field_value} = $formdata[$value->build_id];
                }
            }
        }

        if ($address != "") {
            $tmp = explode("-", $zip);
            $zip = $tmp[0];
            $insert = array();
            $insert['street_address'] = $address;
            $insert['city'] = $city;
            $insert['zip'] = $zip;
            $tmpaddress = strtolower(str_replace(" ", "", $address));
            $tmpaddress = str_replace(",", "", $tmpaddress);
            $withoutspace = explode(" ", strtolower($address));

            $arr = array();
            $tmpstr = "";
            for ($i = 0; $i < count($withoutspace); $i++) {
                $tmpstr .= $withoutspace[$i];
                if ($i + 1 < count($withoutspace)) {
                    $str = $withoutspace[$i] . "" . $withoutspace[$i + 1];
                    $arr[] = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $str));
                    $arr[] = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $tmpstr));
                } else {
                    $str = $withoutspace[$i];
                    $arr[] = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $str));
                    $arr[] = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $tmpstr));
                }
                if ($i > 0) {
                    $str = $withoutspace[$i - 1] . "" . $withoutspace[$i] . (isset($withoutspace[$i + 1]) ? $withoutspace[$i + 1] : "");
                    $arr[] = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $str));
                }
            }

            $zoneData = ZoneAPI::where("zip", $zip)->where(DB::raw("LOWER(replace(city, ' ',''))"), strtolower(str_replace(" ", "", $city)));
            $zoneData->where(function ($q) use ($arr, $withoutspace) {
                $q->whereIn(DB::raw("LOWER(replace(street_name,' ', ''))"), $withoutspace)
                    ->orWhereIn(DB::raw("LOWER(replace(street_name,' ', ''))"), $arr);
            });

            $streetMatch = clone ($zoneData); //->get();
            $streetPlusBldg = $zoneData->where(function ($q) use ($arr, $withoutspace, $tmpaddress) {
                $q->where(DB::raw("LOWER(replace(concat(bldg_num, street_name, street_type),' ', ''))"), "LIKE", "%" . $tmpaddress . "%")
                    ->orWhere(DB::raw("LOWER(replace(concat(bldg_num, street_name, street_type, suffix_dir),' ', ''))"), "LIKE", "%" . $tmpaddress . "%")
                    ->orWhere(DB::raw("LOWER(replace(concat(bldg_num, street_name, street_type, suffix_dir, unit_info),' ', ''))"), "LIKE", "%" . $tmpaddress . "%")
                    ->orwhere(DB::raw("LOWER(replace(concat(bldg_num, street_name, street_type),' ', ''))"), $tmpaddress)
                    ->orWhere(DB::raw("LOWER(replace(concat(bldg_num, street_name, street_type, suffix_dir),' ', ''))"), $tmpaddress)
                    ->orWhere(DB::raw("LOWER(replace(concat(bldg_num, street_name, street_type, suffix_dir, unit_info),' ', ''))"), $tmpaddress);
            })->first();
            /* $streetPlusBldg = $zoneData->where(function($q) use ($arr, $withoutspace) {
                $q->whereIn(DB::raw("LOWER(replace(concat(bldg_num, street_name),' ', ''))"), $withoutspace)
                    ->orWhereIn(DB::raw("LOWER(replace(concat(bldg_num, street_name),' ', ''))"), $arr)
                    ->orWhereIn(DB::raw("LOWER(replace(concat(street_name, bldg_num),' ', ''))"), $withoutspace)
                    ->orWhereIn(DB::raw("LOWER(replace(concat(street_name, bldg_num),' ', ''))"), $arr)
                    ->orWhereIn(DB::raw("LOWER(replace(concat(bldg_num, prefix_dir, street_name),' ', ''))"), $arr)
                    ->orWhereIn(DB::raw("LOWER(replace(concat(bldg_num, prefix_type, street_name),' ', ''))"), $arr);
            })->first();*/
            if (!empty($streetPlusBldg)) {
                $elementary_school = $streetPlusBldg->elementary_school;
                preg_match('#\((.*?)\)#', $elementary_school, $pmatch);
                if (isset($pmatch[1])) {
                    $sgrade = str_replace("grades", "", str_replace(" ", "", $pmatch[1]));
                    $tmp = explode("-", $sgrade);
                    if ($next_grade <= $tmp[1] || $next_grade == "PreK" || $next_grade == "K") {
                        $zoned_school = $elementary_school;
                    }
                }

                $intermediate_school = $streetPlusBldg->intermediate_school;
                preg_match('#\((.*?)\)#', $intermediate_school, $imatch);
                if (isset($imatch[1]) && $zoned_school == '') {
                    $sgrade = str_replace("grades", "", str_replace(" ", "", $imatch[1]));
                    $tmp = explode("-", $sgrade);
                    if ($next_grade <= $tmp[1]) {
                        $zoned_school = $intermediate_school;
                    }
                }

                $middle_school = $streetPlusBldg->middle_school;
                preg_match('#\((.*?)\)#', $middle_school, $mmatch);
                if (isset($mmatch[1]) && $zoned_school == '') {
                    $sgrade = str_replace("grades", "", str_replace(" ", "", $mmatch[1]));
                    $tmp = explode("-", $sgrade);
                    if ($next_grade <= $tmp[1]) {
                        $zoned_school = $middle_school;
                    }
                }

                $high_school = $streetPlusBldg->high_school;
                preg_match('#\((.*?)\)#', $high_school, $hmatch);
                if (isset($hmatch[1]) && $zoned_school == '') {
                    $sgrade = str_replace("grades", "", str_replace(" ", "", $hmatch[1]));
                    $tmp = explode("-", $sgrade);
                    if ($next_grade <= $tmp[1]) {
                        $zoned_school = $high_school;
                    }
                }
            } else {
                $result = $streetMatch->get();
                if (count($result) > 5) {
                    $nz = NoZonedSchool::create($insert);
                    return false;
                }
                $count = 0;
                foreach ($result as $key => $value) {
                    if ($count == 0) {
                        $elementary_school = $value->elementary_school;
                        preg_match('#\((.*?)\)#', $elementary_school, $pmatch);
                        if (isset($pmatch[1])) {
                            $sgrade = str_replace("grades", "", str_replace(" ", "", $pmatch[1]));
                            $tmp = explode("-", $sgrade);
                            if ($next_grade <= $tmp[1] || $next_grade == "PreK" || $next_grade == "K") {
                                $zoned_school = $elementary_school;
                            }
                        }

                        $intermediate_school = $value->intermediate_school;
                        preg_match('#\((.*?)\)#', $intermediate_school, $imatch);
                        if (isset($imatch[1]) && $zoned_school == '') {
                            $sgrade = str_replace("grades", "", str_replace(" ", "", $imatch[1]));
                            $tmp = explode("-", $sgrade);
                            if ($next_grade <= $tmp[1]) {
                                $zoned_school = $intermediate_school;
                            }
                        }

                        $middle_school = $value->middle_school;
                        preg_match('#\((.*?)\)#', $middle_school, $mmatch);
                        if (isset($mmatch[1]) && $zoned_school == '') {
                            $sgrade = str_replace("grades", "", str_replace(" ", "", $mmatch[1]));
                            $tmp = explode("-", $sgrade);
                            if ($next_grade <= $tmp[1]) {
                                $zoned_school = $middle_school;
                            }
                        }

                        $high_school = $value->high_school;
                        preg_match('#\((.*?)\)#', $high_school, $hmatch);
                        if (isset($hmatch[1]) && $zoned_school == '') {
                            $sgrade = str_replace("grades", "", str_replace(" ", "", $hmatch[1]));
                            $tmp = explode("-", $sgrade);
                            if ($next_grade <= $tmp[1]) {
                                $zoned_school = $high_school;
                            }
                        }
                        break;
                    }
                    $count++;
                }
            }
            if ($zoned_school != '') {
                $formdata[$zoned_field_id] = $zoned_school;
                Session::forget("form_data");
                $dataArray['formdata'] = $formdata;
                Session::push("form_data", $dataArray);
                return true;
            } else {
                $nz = NoZonedSchool::create($insert);
                return false;
            }
        }
        return true;
    }


    private function sentSuccessEmail($emailArr)
    {
        $msg_data = ApplicationConfiguration::where("application_id", $emailArr['application_id'])->first();
        $msg = $emailArr['msg'];

        $msg = find_replace_string($msg, $emailArr);


        $msg = str_replace("{student_name}", $emailArr['first_name'] . " " . $emailArr['last_name'], $msg);
        $msg = str_replace("{parent_name}", $emailArr['parent_first_name'] . " " . $emailArr['parent_last_name'], $msg);
        $msg = str_replace("{confirm_number}", $emailArr['confirm_number'], $msg);
        $msg = str_replace("{confirmation_number}", $emailArr['confirm_number'], $msg);
        $msg = str_replace("{confirmation_no}", $emailArr['confirm_number'], $msg);
        $msg = str_replace("{transcript_due_date}", $emailArr['transcript_due_date'], $msg);
        $parent_upload_link = url('/') . "/upload/" . $emailArr['application_id'] . "/grade/cdi";
        $msg = str_replace("{parent_grade_cdi_upload_link}", $parent_upload_link, $msg);
        $msg = str_replace("{", "", $msg);
        $msg = str_replace("}", "", $msg);


        $emailArr['email_text'] = $msg;
        $emailArr['logo'] = getDistrictLogo();

        $data = array();
        $submission_data = Submissions::where('confirmation_no', $emailArr['confirm_number'])->first();

        $data['submission_id'] = $submission_data->id;
        $data['email_to'] = $emailArr['email'];
        $data['email_subject'] = $emailArr['subject'];
        $data['email_body'] = $emailArr['email_text'];
        $data['logo'] = $emailArr['logo'];
        $data['module'] = "Direct Submission Confirmation";

        try {
            Mail::send('emails.index', ['data' => $emailArr], function ($message) use ($emailArr) {
                $message->to($emailArr['email']);
                $message->subject($emailArr['subject']);
            });
            $data['status'] = "Success";

            createEmailActivityLog($data);
        } catch (\Exception $e) {
            // Get error here
            $data['status'] = $e->getMessage();
            createEmailActivityLog($data);
        }
    }

    public function find_duplicate_submission($form_id, $type = "new")
    {
        $duplicate_array = checkDuplicateFields($form_id);


        $condition = array();
        $formdata = Session::get("form_data")[0];

        foreach ($duplicate_array as $key => $value) {
            $db_field = get_field_value($value->build_id, $value->form_id, "db_field");

            if ($db_field != "" && isset($formdata['formdata'][$value->build_id])) {
                if ($type == "exist") {
                    if ($db_field == "birthday" || $db_field == "student_id")
                        $condition[$db_field] = $formdata['formdata'][$value->build_id];
                } else
                    $condition[$db_field] = $formdata['formdata'][$value->build_id];
            }
        }
        if (!empty($condition)) {
            $query = Submissions::where('district_id', Session::get('district_id'))->where("enrollment_id", Session::get("enrollment_id"));
            foreach ($condition as $key => $value) {
                $query->where($key, $value);
            }
            $dt = $query->first();

            if (!empty($dt)) {
                if (in_array($dt->submission_status, array("Offered and Declined", "Denied due to Ineligibility", "Denied due to Incomplete Records", "Application Withdrawn")))
                    return array();
                else if (in_array($dt->submission_status, array("Offered and Accepted", "Waitlisted"))) {
                    return array("processed");
                } else {
                    return array("1", "2");
                }
            } else
                return array();
        } else
            return array();
    }

    public function saveFrontForm($form_type = '')
    {
        // $form_data = isset($form_data) ? $form_data : Session::get("formdata");
        // dd("dd");
        $form_data = Session::get("form_data")[0];
        // dd($form_data);
        $insert = array(
            "application_id" => $form_data["application_id"],
            "form_id" => $form_data["form_id"],
            "enrollment_id" => Session::get("enrollment_id")
        );
        $insert['first_choice'] = isset($form_data['first_choice']) ? $form_data['first_choice'] : null;
        $second_choice = isset($form_data['second_choice']) ? $form_data['second_choice'] : null;
        if ($insert['first_choice'] != $second_choice) {
            $insert['second_choice'] = isset($form_data['second_choice']) ? $form_data['second_choice'] : null;
            $insert['second_sibling'] = isset($form_data['second_sibling']) ? $form_data['second_sibling'] : null;
        } else {
            $insert['second_choice'] = null;
            $insert['second_sibling'] = null;
        }

        //$insert['second_choice'] = isset($form_data['second_choice']) ? $form_data['second_choice'] : null;
        //$insert['second_sibling'] = isset($form_data['second_sibling']) ? $form_data['second_sibling'] : null;
        $insert['first_sibling'] = isset($form_data['first_sibling']) ? $form_data['first_sibling'] : null;


        foreach ($form_data['formdata'] as $f => $input) {
            $db_field = FormContent::where('build_id', $f)->where('field_property', "db_field")->first();
            if (isset($db_field->field_value)) {
                $insert[$db_field->field_value] = $input;
            }
        }
        //        print_r($insert);exit;
        $insert['late_submission'] = "N";
        $rsApp = Application::where("id", $insert['application_id'])->first();
        if (!empty($rsApp)) {
            if ($rsApp->submission_type != "Regular") {
                $insert['late_submission'] = "Y";
            }
        }
        $insert['district_id'] = Session::get('district_id');
        $insert['lottery_number'] = generate_lottery_number();

        if ($form_type == "new") {
            $insert['submission_status'] = 'Pending';
        }
        $insert['grade_exists'] = 'N';
        $insert['cdi_exists'] = 'N';

        $active = false;
        if ($insert['first_choice'] != "") {
            $program_data = ApplicationProgram::where("id", $insert['first_choice'])->select('program_id')->first();
            if (!empty($program_data)) {
                $insert['first_choice_program_id'] = $program_data->program_id;
                $eligibilities = ProgramEligibility::where("program_id", $program_data->program_id)->where("application_id", Session::get("application_id"))->whereRaw("FIND_IN_SET('" . $insert['next_grade'] . "', grade_lavel_or_recommendation_by)")->where('status', 'Y')->first();
                if (empty($eligibilities))
                    $active = true;
            }
        }
        if ($insert['second_choice'] != "") {
            $program_data = ApplicationProgram::where("id", $insert['second_choice'])->select('program_id')->first();
            if (!empty($program_data)) {
                $insert['second_choice_program_id'] = $program_data->program_id;
                $eligibilities = ProgramEligibility::where("program_id", $program_data->program_id)->whereRaw("FIND_IN_SET('" . $insert['next_grade'] . "', grade_lavel_or_recommendation_by)")->where("application_id", Session::get("application_id"))->where('status', 'Y')->first();
                if (empty($eligibilities) && !$active)
                    $active = true;
            }
        }
        if ($active == true) {
            $insert['submission_status'] = "Active";
        }
        if (Session::has("from_admin")) {
            if (Auth::check()) {
                $insert['submitted_by'] = Auth::user()->id;
            }
        }
        $result = Submissions::create($insert);
        $last_id = DB::getPdo()->lastInsertId();




        $sub_data =  Submissions::where('submissions.id', $last_id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", "application.enrollment_id")->first();
        if (Session::has("from_admin")) {
            $this->modelCreate($sub_data, "Submission By Admin");
        }

        $form_data['formdata']['first_choice'] = isset($form_data['first_choice']) ? $form_data['first_choice'] : null;
        $form_data['formdata']['second_choice'] = isset($form_data['second_choice']) ? $form_data['second_choice'] : null;
        $form_data['formdata']['second_sibling'] = isset($form_data['second_sibling']) ? $form_data['second_sibling'] : null;
        $form_data['formdata']['first_sibling'] = isset($form_data['first_sibling']) ? $form_data['first_sibling'] : null;

        $form_data['formdata'] = json_encode($form_data['formdata']);
        $resultRaw = SubmissionRaw::create($form_data);
        $confirmation_no = "MAGNET-" . getEnrolmentConfirmationStyle($form_data['application_id']) . "-" . str_pad($last_id, 4, "0", STR_PAD_LEFT);
        // dd($confirmation_no);
        $result->confirmation_no = $confirmation_no;
        $result->save();
        // dd($result);

        return $confirmation_no;
    }

    public function getPreviewFieldByTypeandId($type, $form_build_id, $form_id = '')
    {

        $data = $this->getPropertyInArr($form_build_id);
        $return  = '';
        if (isset($type)) {
            //$type = DB::table('field_type')->where('field_type_id',$type_id)->pluck('name');
            $function = 'PreviewField' . ucfirst($type);
            //            echo $function;
            try {
                $return  = $this->$function($data, $form_build_id, $form_id);
            } catch (\Exception $e) {
                $return  = '';
            }
        }
        return $return;
    }

    public function getFieldByTypeandId($type, $form_build_id, $form_id = '')
    {

        $data = $this->getPropertyInArr($form_build_id);
        $return  = '';
        if (isset($type)) {
            //$type = DB::table('field_type')->where('field_type_id',$type_id)->pluck('name');
            $function = 'Field' . ucfirst($type);
            //            echo $function;
            try {
                $return  = $this->$function($data, $form_build_id);
            } catch (\Exception $e) {
                $return  = '';
            }
        }
        return $return;
    }
    public function getPropertyInArr($form_build_id)
    {
        $property = FormContent::where('build_id', $form_build_id)->orderBy('sort_option')->get();
        $data = [];
        foreach ($property as $key => $value) {
            $data[$value->field_property] = $value->field_value;
        }
        return $data;
    }

    /* Preview Form Field */
    public function PreviewFieldTermscheck($data, $form_field_id, $form_id)
    {
        return view('layouts.front.Field.preview.Termscheck', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }


    public function PreviewFieldView($data, $form_field_id, $form_id)
    {
        return view('layouts.front.Field.preview.View', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    public function PreviewFieldTextbox($data, $form_field_id, $form_id)
    {

        return view('layouts.front.Field.preview.Textbox', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    public function PreviewFieldTextarea($data, $form_field_id, $form_id)
    {

        return view('layouts.front.Field.preview.Textarea', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    public function PreviewFieldDate($data, $form_field_id, $form_id)
    {

        return view('layouts.front.Field.preview.Date', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    public function PreviewFieldText($data, $form_field_id, $form_id)
    {

        return view('layouts.front.Field.preview.Textbox', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    public function PreviewFieldProgram_choice($data, $form_field_id, $form_id)
    {
        // return "--here--";
        return view('layouts.front.Field.preview.Program_choice', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    public function PreviewFieldAddress($data, $form_field_id, $form_id)
    {

        return view('layouts.front.Field.preview.Address', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    public function PreviewFieldEmail($data, $form_field_id, $form_id)
    {

        return view('layouts.front.Field.preview.Email', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    public function PreviewFieldRadio($data, $form_field_id, $form_id)
    {
        return view('layouts.front.Field.preview.Radio', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    public function PreviewFieldCheckBox($data, $form_field_id, $form_id)
    {

        return view('layouts.front.Field.preview.CheckBox', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    public function PreviewFieldCkEdittor($data, $form_field_id, $form_id = 0)
    {

        return view('layouts.front.Field.preview.CkEdittor', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    public function PreviewFieldSelect($data, $form_field_id, $form_id)
    {
        return view('layouts.front.Field.preview.Select', ['data' => $data, 'field_id' => $form_field_id, 'form_id' => $form_id]);
    }

    /* Normal Form Field */

    public function FieldTermscheck($data, $form_field_id)
    {
        return view('layouts.front.Field.Termscheck', ['data' => $data, 'field_id' => $form_field_id]);
    }


    public function FieldView($data, $form_field_id)
    {
        return view('layouts.front.Field.View', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function FieldTextbox($data, $form_field_id)
    {

        return view('layouts.front.Field.Textbox', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function FieldTextarea($data, $form_field_id)
    {

        return view('layouts.front.Field.Textarea', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function FieldDate($data, $form_field_id)
    {

        return view('layouts.front.Field.Date', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function FieldText($data, $form_field_id)
    {

        return view('layouts.front.Field.Textbox', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function FieldProgram_choice($data, $form_field_id)
    {
        // return "--here--";
        return view('layouts.front.Field.Program_choice', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function FieldAddress($data, $form_field_id)
    {

        return view('layouts.front.Field.Address', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function FieldEmail($data, $form_field_id)
    {

        return view('layouts.front.Field.Email', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function FieldRadio($data, $form_field_id)
    {
        return view('layouts.front.Field.Radio', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function FieldCheckBox($data, $form_field_id)
    {

        return view('layouts.front.Field.CheckBox', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function FieldCkEdittor($data, $form_field_id)
    {

        return view('layouts.front.Field.CkEdittor', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function FieldSelect($data, $form_field_id)
    {
        return view('layouts.front.Field.Select', ['data' => $data, 'field_id' => $form_field_id]);
    }

    public function checkSibling($state_id, $program_id = 0)
    {
        if (is_numeric($state_id)) {
            if ($program_id != 0) {
                $data = ApplicationProgram::where("application_programs.id", $program_id)->join("grade", "grade.id", "application_programs.grade_id")->join("program", "program.id", "application_programs.program_id")->select('program.id AS program', 'grade.name AS grade')->first();
                $program = $data->program;
                $grade = $data->grade;
                $program_data = Program::where("id", $program)->first();
                if ($program_data->silbling_check == 'Y') {
                    $student = DB::table("student")->where('stateID', $state_id)->where('current_school', $program_data->magnet_school)->first();
                    if (isset($student->id)) {
                        return $student->first_name . " " . $student->last_name;
                    }
                } else {
                    $student = DB::table("student")->where('stateID', $state_id)->first();
                    if (isset($student->id)) {
                        return $student->first_name . " " . $student->last_name;
                    }
                }
            } else {
                $student = DB::table("student")->where('stateID', $state_id)->first();
                if (isset($student->id)) {
                    return $student->first_name . " " . $student->last_name;
                }
            }
        }
        return "";
    }

    public function checkStudent($state_id, $grade = "")
    {
        if (!is_numeric($state_id))
            return "";
        $student = DB::table("student")->where('stateID', $state_id)->first();

        if (!empty($student)) {
            if (isset($student->id)) {
                if ($grade == "")
                    $grade = $student->current_grade;
                if ($grade == "ASK-97") {
                    return "Higher"; //$next_grade = "K";
                } elseif ($grade == "PreK") {
                    $next_grade = "K";
                } elseif ($grade == "K") {
                    $next_grade = "1";
                } else {
                    $next_grade = $grade + 1;
                }
                //if($next_grade == "K")
                //   return "NoMagnet";
                $rsG = DB::table("application_programs")->join("grade", "grade.id", "application_programs.grade_id")->where("grade.name", $next_grade)->where("application_programs.application_id", Session::get("application_id"))->first();
                if (empty($rsG)) {
                    // return "Higher";
                }
                //if($student->current_grade == 5 || $student->current_grade > 7)
                // return "NoMagnet";
                $schools = DB::table("school")->where("name", $student->current_school)->orWhere("sis_name", $student->current_school)->get();
                $schoolArr = [];
                $schoolArr[] = $student->current_school;
                if (count($schools) > 0) {

                    foreach ($schools as $sk => $sv) {
                        $schoolArr[] = $sv->name;
                        $schoolArr[] = $sv->sis_name;
                    }
                }

                if (Session::has("form_id")) {
                    $fdata = Program::where("parent_submission_form", Session::get("form_id"))->first();
                    if (!empty($fdata)) {
                        if ($fdata->existing_magnet_program_alert == "N") {
                            return "NoMagnet";
                        }
                    }
                }

                $data = Program::where("district_id", Session::get("district_id"))->whereIn("magnet_school", $schoolArr)->first();
                if (!empty($data)) {
                    $grade_level = $data->exclude_grade_lavel;
                    $cgrade = explode(",", $grade_level);
                    //$cgrade = $grades[count($grades)-1];

                    if (in_array($student->current_grade, $cgrade))
                        return "NoMagnet";

                    if (!empty($data)) {
                        if ($data->existing_magnet_program_alert == 'Y')
                            return "Magnet";
                        else
                            return "NoMagnet";
                    } else {
                        return "NoMagnet";
                    }
                } else
                    return "NoMagnet";
                /*$school = School::where("name", $student->current_school)->first();
                if(!empty($school))
                {
                    if($school->magnet == "Yes")
                        return "Magnet";
                    else
                        return "NoMagnet";
                }
                else
                {
                        return "NoMagnet";
                }*/
            } else
                return "";
        } else {
            return "";
        }
        return "";
    }

    public function customError()
    {
        // dd('herew');
        // abort(404);
        // return view("error");

        throw new \Exception("TO Check Custom Errors message", 1);
    }

    public function getDOB($grade, $application_id)
    {
        $rs = Enrollment::join('application', 'application.enrollment_id', 'enrollments.id')->where("application.id", $application_id)->first();
        if (!empty($rs)) {
            if ($grade == "PreK") {
                $date = $rs->perk_birthday_cut_off;
            } else if ($grade == "K") {
                $date = $rs->kindergarten_birthday_cut_off;
            } else if ($grade == "1") {
                $date = $rs->first_grade_birthday_cut_off;
            } else {
                $date = $rs->perk_birthday_cut_off;
            }
        } else
            $date = date("Y-m-d");
        return $date;
    }

    public function msgDisp($msg_type)
    {
        $district = District::where("id", Session::get("district_id"))->first();
        $application_data = Application::where('district_id', Session::get('district_id'))->where("status", "Y")->first();
        if ($msg_type == "nozone")
            $msg_type = "no_zone_address_found";
        elseif ($msg_type == "duplicate")
            $msg_type = "duplicate_application_msg";
        elseif ($msg_type == "processed")
            $msg_type = "duplicate_processed_application_msg";
        elseif ($msg_type == "nostudent")
            $msg_type = "no_student_msg";
        elseif ($msg_type == "incorrectinfo")
            $msg_type = "incorrect_student_info";
        elseif ($msg_type == "nograde")
            $msg_type = "no_grade_info";

        return view('layouts.errors.msgs', compact("district", "msg_type", "application_data"));
    }

    public function checkSiblingEnabled($program_id)
    {
        $data = ApplicationProgram::where("application_programs.id", $program_id)->join("program", "program.id", "application_programs.program_id")->select('program.sibling_enabled')->first();
        if (!empty($data)) {
            return $data->sibling_enabled;
        } else
            return "N";
    }


    public function printApplicationMsg($confirmation_no)
    {
        $submission = Submissions::where("confirmation_no", $confirmation_no)->first();

        if (!empty($submission)) {
            $application_data = Application::where("id", $submission->application_id)->first();

            $msg_data = ApplicationConfiguration::where("application_id", $submission->application_id)->first();
            $emailAdd = array();
            $emailArr['first_name'] = $submission->first_name;
            $emailArr['last_name'] = $submission->last_name;
            $emailArr['parent_first_name'] = $submission->parent_first_name;
            $emailArr['parent_last_name'] = $submission->parent_last_name;
            $emailArr['email'] = $submission->parent_email;
            $emailArr['confirm_number'] = $submission->confirmation_no;
            $emailArr['signature'] = get_signature('email_signature');
            $emailArr['student_id'] = $submission->student_id;
            $emailArr['next_grade'] = $submission->next_grade;
            $emailArr['current_grade'] = $submission->current_grade;
            $emailArr['submission_date'] = getDateTimeFormat($submission->created_at);
            $emailArr['current_school'] = $submission->current_school;
            $emailArr['zoned_school'] = $submission->current_school;
            $emailArr['parent_name'] = $submission->parent_first_name . " " . $submission->parent_last_name;
            $emailArr['student_name'] = $submission->first_name . " " . $submission->last_name;
            $emailArr['transcript_due_date'] = getDateTimeFormat($application_data->transcript_due_date);



            if ($submission->submission_status == "Active") {
                $confirm_msg = $msg_data->active_screen;
                $confirm_title = $msg_data->active_screen_title;
                $confirm_subject = $msg_data->active_screen_subject;
            } else {
                $confirm_msg = $msg_data->pending_screen;
                $confirm_title = $msg_data->pending_screen_title;
                $confirm_subject = $msg_data->pending_screen_subject;
            }

            $confirm_subject = find_replace_string($confirm_subject, $emailArr);
            $confirm_subject = str_replace("{", "", $confirm_subject);
            $confirm_subject = str_replace("}", "", $confirm_subject);

            $confirm_title = find_replace_string($confirm_title, $emailArr);
            $confirm_title = str_replace("{", "", $confirm_title);
            $confirm_title = str_replace("}", "", $confirm_title);

            $confirm_msg = find_replace_string($confirm_msg, $emailArr);
            $confirm_msg = str_replace("{", "", $confirm_msg);
            $confirm_msg = str_replace("}", "", $confirm_msg);

            view()->share('confirm_title', $confirm_title);
            view()->share('confirm_msg', $confirm_msg);
            view()->share('confirm_subject', $confirm_subject);
            view()->share('application_data', $application_data);
            $pdf = Pdf::loadView('layouts.errors.print_application', compact('confirm_title', 'confirm_msg', 'confirm_subject', 'application_data'));
            return $pdf->download('PrintApplication-' . $confirmation_no . ".pdf");
        }
    }

    public function incorrectInfo($student_id)
    {
        $info = array();
        $info['student_id'] = $student_id;
        $info['status'] = "Pending";
        IncorrectStudent::updateOrCreate(['student_id' => $student_id], $info);
        $this->destroySessions();
        return redirect('/msgs/incorrectinfo');
    }


    public function destroySessions()
    {
        Session::forget("enrollment_id");
        Session::forget("application_id");
        Session::forget("step_session");
        Session::forget("form_data");
        Session::forget("page_id");
        Session::forget("mcpss_zone_api");
        Session::forget("zone_api");
    }

    public function documentUploadIndex(Request $request)
    {
        $submission_id = ($request->submission_id) ? $request->submission_id : '';
        $stu_dob = ($request->stu_dob) ? date('Y-m-d', strtotime($request->stu_dob)) : '';

        $data = Submissions::where('id', $submission_id)->where('birthday', $stu_dob)->first();
        return view('layouts.front.document_upload_index', compact('data', 'submission_id', 'stu_dob'));
    }

    public function storeDocumentUpload(Request $request)
    {
        if (isset($request->grades_upload) && !empty($request->grades_upload)) {
            foreach ($request->grades_upload as $key => $value) {
                if (isset($value)) {
                    $doc = $value;
                    $ext = $doc->getClientOriginalExtension();
                    if ($ext == 'pdf') {
                        $name =  pathinfo($doc->getClientOriginalName(), PATHINFO_FILENAME);
                        $doc_path = base_path() . '/resources/assets/admin/grade_cdi_uploads';
                        $doc_name = $name . '_' . time() . '.' . $ext;
                        $doc->move($doc_path, $doc_name);

                        $doc_info = [
                            'submission_id' => $request->submission_id,
                            'doc_grade' => $doc_name ?? '',
                        ];
                        SubmissionDocuments::create($doc_info);
                    }
                }
            }
        }

        if (isset($request->cdi_upload) && !empty($request->cdi_upload)) {
            foreach ($request->cdi_upload as $key => $value) {
                if (isset($value)) {
                    $doc = $value;
                    $ext = $doc->getClientOriginalExtension();
                    if ($ext == 'pdf') {
                        $name =  pathinfo($doc->getClientOriginalName(), PATHINFO_FILENAME);
                        $doc_path = base_path() . '/resources/assets/admin/grade_cdi_uploads';
                        $doc_name = $name . '_' . time() . '.' . $ext;
                        $doc->move($doc_path, $doc_name);

                        $doc_info = [
                            'submission_id' => $request->submission_id,
                            'doc_cdi' => $doc_name ?? '',
                        ];
                        SubmissionDocuments::create($doc_info);
                    }
                }
            }
        }

        Session::flash('success', 'Document Uploaded successfully.');
        return redirect('Document?submission_id=' . $request->submission_id . '&stu_dob=' . $request->stu_dob);
        // dd($request->all());
    }
}
