<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

if (!function_exists('createEmailActivityLog')) {
    function createEmailActivityLog($data = [])
    {
        $fields = ['submission_id', 'program_id', 'email_body', 'email_subject', 'email_to', 'module', 'status'];
        if (Auth::check())
            $create_data['user_id'] = Auth::user()->id;
        else
            $create_data['user_id'] = 0;
        $create_data['district_id'] = session('district_id');
        foreach ($fields as $key => $field) {
            if (isset($data[$field])) {
                $create_data[$field] = $data[$field];
            } else {
                $create_data[$field] = NULL;
            }
        }
        App\Modules\Submissions\Models\EmailActivityLog::create($create_data);
    }
}

if (!function_exists('checkPermission')) {
    function checkPermission($role_id, $slug)
    {
        $flag  = 0;
        $roles = new App\Modules\Role\Controllers\RoleController;
        $permission = $roles->getRolesPermssion($role_id, $slug)->count();
        if ($permission > 0 || in_array(Auth::user()->role_id, [1])) {
            $flag = 1;
        }
        return $flag;
    }
}

if (!function_exists('getDistrictLogo')) {
    function getDistrictLogo($logo_type = "")
    {
        if (Session::has('district_id')) {
            if (Session::get('district_id') > 0) {
                $rsStores = DB::table("district")->where("id", Session::get('district_id'))->get();
                if (!empty($rsStores)) {
                    if ($logo_type == "")
                        $logo = $rsStores[0]->district_logo;
                    else
                        $logo = $rsStores[0]->{$logo_type};
                    return url('/') . '/resources/filebrowser/' . $rsStores[0]->district_slug . "/logo/" . $logo; //url('/resources/filebrowser/').'/'.$rsStores[0]->display_name.'/Logo/'.$rsStores[0]->logo_file;
                }
            }
            //print_r($rsStores);
        }
        return url('/resources/assets/admin/images/login.png');
    }
}

if (!function_exists('get_district_combo')) {
    function get_district_combo()
    {
        $districtList = "";
        $action = app('request')->route()->getAction();
        $controller = class_basename($action['controller']);
        $tmp = explode("@", $controller);
        if ($tmp[0] == "DistrictController")
            return "";
        if (Session::get('super_admin') == "Y") {
            $rsStores = DB::table("district")->where("status", "Y")->get();
            if (!empty($rsStores)) {
                $districtList .= '<select class="form-control custom-select custom-select-sm w-120 theme-changer" onchange="changeDistrict(this.value)">';
                //$districtList .= '<option value="0">All</option>';
                foreach ($rsStores as $key => $value) {
                    $districtList .= '<option value="' . $value->id . '"' . (Session::get('district_id') == $value->id ? "selected" : "") . '>' . $value->short_name . '</option>';
                }
                $districtList .= '</select>';
            }
        }
        //        echo $districtList;exit;
        return $districtList;
    }
}

if (!function_exists('get_enrollment_combo')) {
    function get_enrollment_combo()
    {
        $district_id = Session::get('district_id');
        $enrollments = DB::table("enrollments")->where('district_id', $district_id)->where("status", "Y")->get();
        $enrollList = '';
        if (isset($enrollments) && !empty($enrollments->toArray())) {
            $enrollList .= '<select class="form-control custom-select custom-select-sm w-120 theme-changer" autocomplete="off" id="enrollment_main" onchange="changeEnrollment(this.value)">';
            //$districtList .= '<option value="0">All</option>';
            foreach ($enrollments as $key => $value) {
                $enrollList .= '<option value="' . $value->id . '"' . (Session::get('enrollment_id') == $value->id ? "selected" : "") . '>' . $value->school_year . '</option>';
            }
            $enrollList .= '</select>';
        }
        return $enrollList;
    }
}

if (!function_exists('theme_css')) {
    function theme_css()
    {
        $css = "<style>        ";
        $css .= "header {background-color: " . Session::get("theme_color") . " !important;}";
        $css .= ".logo-box {background-color: " . Session::get("theme_color") . " !important;}";
        $css .= "</style>";
        return $css;
    }
}

if (!function_exists('getTopLinks')) {
    function getTopLinks()
    {
        $topLinks = DB::table("landing_links")->where("district_id", Session::get("district_id"))->where('status', 'Y')->orderBy('sort_order')->get();
        $rsStores = DB::table("district")->where("id", Session::get('district_id'))->first();
        $class = "top_links_" . count($topLinks);
        $linkStr = '<div class="mt-20 d-flex flex-wrap justify-content-around top-links ' . $class . '">';
        foreach ($topLinks as $key => $value) {
            $linkStr .= '<div class="">';
            if ($value->link_filename != "") {
                $link = url('/resources/filebrowser/' . $rsStores->district_slug . '/documents/' . $value->link_filename);
                $linkStr .= "<a href='" . $link . "' title='" . $value->link_title . "' target='_blank'>" . $value->link_title . "</a>";
            } elseif ($value->link_url != "") {
                $linkStr .= "<a href='" . $value->link_url . "' title='" . $value->link_title . "' target='_blank'>" . $value->link_title . "</a>";
            } else {
                $linkStr .= "<a href='" . url('/showinfo/' . $value->link_id) . "' data-toggle='modal' data-target='#infopopup' class='ls-modal' title='" . $value->link_title . "'>" . $value->link_title . "</a>";
            }
            $linkStr .= '</div>';
        }
        $linkStr .= "</div>";
        return $linkStr;
    }
}

if (!function_exists('getConfig')) {
    function getConfig()
    {
        $conf   = DB::table('district_config')->where("district_id", Session::get("district_id"))->get();
        $return = [];
        foreach ($conf as $key => $value) {
            $return[$value->config_name] = $value->config_value;
        }
        return $return;
    }
}

if (!function_exists('getFormbyId')) {
    function getFormbyId($form_id)
    {
        $FrontControl = new \App\Http\Controllers\HomeController;
        $data  = $FrontControl->getFormbyId($form_id);
        return $data;
    }
}

if (!function_exists('getPreviewFieldByTypeandId')) {
    function getPreviewFieldByTypeandId($type_id, $form_field_id, $form_id = '')
    {
        $FrontControl = new \App\Http\Controllers\HomeController;
        $data  = $FrontControl->getPreviewFieldByTypeandId($type_id, $form_field_id, $form_id);
        return $data;
    }
}

if (!function_exists('getFieldByTypeandId')) {
    function getFieldByTypeandId($type_id, $form_field_id, $form_id = '')
    {
        $FrontControl = new \App\Http\Controllers\HomeController;
        $data  = $FrontControl->getFieldByTypeandId($type_id, $form_field_id, $form_id);
        return $data;
    }
}

if (!function_exists('getOnlyFieldProperty')) {
    function getOnlyFieldProperty($type_id, $form_field_id, $form_id = '')
    {
        $property = DB::table('form_content')->join('form_build', 'form_build.id', 'form_content.build_id')->where('build_id', $form_field_id)->select('form_content.*', 'form_build.type')->orderBy('sort_option')->get();
        $data = [];
        foreach ($property as $key => $value) {
            $data[$value->field_property] = $value->field_value;
            $data['type'] = $value->type;
        }
        //    print_r($data);
        return $data;
    }
}

if (!function_exists('get_form_pages')) {
    function get_form_pages($form_id)
    {
        $rsForm = DB::table("form")->where('id', $form_id)->first();
        return $rsForm->no_of_pages;
    }
}

if (!function_exists('getFormElementLabel')) {
    function getFormElementLabel($field_id)
    {
        $rsField = DB::table("form_content")->where('build_id', $field_id)->where('field_property', 'label')->first();
        if (!empty($rsField)) {
            return $rsField->field_value;
        } else
            return "";
    }
}

if (!function_exists('getFormElementType')) {
    function getFormElementType($field_id)
    {
        $rsField = DB::table("form_build")->where('id', $field_id)->first();
        if (!empty($rsField)) {
            return $rsField->type;
        } else
            return "";
    }
}

if (!function_exists('get_district_slug')) {
    function get_district_slug()
    {
        $rsDistrict = DB::table("district")->where("id", Session::get("district_id"))->first();
        return $rsDistrict->district_slug;
    }
}

if (!function_exists('fetch_student_field_id')) {
    function fetch_student_field_id($form_id, $field = 'student_id')
    {
        $rs_field = DB::table("form_content")->where('field_property', 'db_field')->where('field_value', $field)->join('form_build', 'form_build.id', 'form_content.build_id')->where('form_content.form_id', $form_id)->first();
        if (!empty($rs_field)) {
            return $rs_field->build_id;
        } else {
            return 0;
        }
    }
}

if (!function_exists('getProgramDropdown')) {
    function getProgramDropdown($application_id = 0)
    {
        if ($application_id > 0) {
            $rs_program_grade = DB::table("application_programs")->where("application_id", $application_id)->join("program", "program.id", "application_programs.program_id")->join("grade", "grade.id", "application_programs.grade_id")->select("program.name as program_name", "grade.name as grade_name", "application_programs.id", "application_programs.program_id")->get();
        } else {
            $rs_program_grade = DB::table("application_programs")->where("application_id", Session::get("application_id"))->join("program", "program.id", "application_programs.program_id")->join("grade", "grade.id", "application_programs.grade_id")->select("program.name as program_name", "grade.name as grade_name", "application_programs.id", "application_programs.program_id")->get();
        }
        return $rs_program_grade;
    }
}

if (!function_exists('getEnrolmentConfirmationStyle')) {
    function getEnrolmentConfirmationStyle($application_id)
    {
        $confirm_style = DB::table("enrollments")->join("application", "application.enrollment_id", "enrollments.id")->where("application.id", $application_id)->first();
        return $confirm_style->confirmation_style;
    }
}

if (!function_exists('checkDuplicateFields')) {
    function checkDuplicateFields($form_id)
    {
        $duplication_build = DB::table("form_content")->where('form_id', $form_id)->where('field_property', 'check_unique')->where('field_value', 'yes')->get();
        return $duplication_build;
    }
}

if (!function_exists('get_field_value')) {
    function get_field_value($build_id, $form_id, $field_property)
    {
        $field_value = DB::table("form_content")->where("build_id", $build_id)->where("form_id", $form_id)->where('field_property', $field_property)->first();
        if (!empty($field_value)) {
            return $field_value->field_value;
        } else {
            return "";
        }
    }
}

if (!function_exists('generate_lottery_number')) {
    function generate_lottery_number()
    {
        $lotteryNumbers = array(0, 0, 0, 0, 0, 0);
        $lottery_number = 1;
        $uniqueLottoFlag = true;
        $breakOutIfInfiniteLoopCounter = 0;
        while ($uniqueLottoFlag) {
            //create an array of 6 random numbers that range in value from 1 to 64
            for ($i = 0; $i < 6; $i++) {
                $lotteryNumbers[$i] = rand(1, 64);
            }
            //concatenate all lottery numbers into one lottery number
            $lottery_number = intval(implode($lotteryNumbers));
            //check to see if the lottery number has already been assigned during another submission
            $submission = DB::table("submissions")->where("lottery_number", $lottery_number)->first();
            //if not already assigned, break out of loop
            if (empty($submission)) {
                $uniqueLottoFlag = false;
            }
            //make sure we do not get in an infinite loop; report error if so
            $breakOutIfInfiniteLoopCounter++;
            if ($breakOutIfInfiniteLoopCounter == 500) {
                sleep(1);
            }
            if ($breakOutIfInfiniteLoopCounter == 1000) {
                sleep(1);
            }
            if ($breakOutIfInfiniteLoopCounter == 1250) {
                sleep(1);
            }
            if ($breakOutIfInfiniteLoopCounter == 1400) {
                sleep(1);
            }
            if ($breakOutIfInfiniteLoopCounter > 1500) {
                return 0;
                throw new \Exception("We are sorry, there was an error with your submission. Please visit Student Support Services for assistance, or try your submission again later.");
            }
        }
        return $lottery_number;
    }
}

if (!function_exists('getNextGradeField')) {
    function getNextGradeField($form_field_id)
    {
        $form = DB::table("form_build")->where('id', $form_field_id)->first();
        $rs_field = DB::table("form_content")->where('field_property', 'db_field')->where('field_value', 'next_grade')->where('form_id', $form->form_id)->first();
        if (!empty($rs_field)) {
            return $rs_field->build_id;
        } else {
            return 0;
        }
    }
}

if (!function_exists('getCurrentGradeField')) {
    function getCurrentGradeField($form_field_id)
    {
        $form = DB::table("form_build")->where('id', $form_field_id)->first();
        $rs_field = DB::table("form_content")->where('field_property', 'db_field')->where('field_value', 'current_grade')->where('form_id', $form->form_id)->first();
        if (!empty($rs_field)) {
            return $rs_field->build_id;
        } else {
            return 0;
        }
    }
}

if (!function_exists('getCurrentSchoolField')) {
    function getCurrentSchoolField($form_field_id)
    {
        $form = DB::table("form_build")->where('id', $form_field_id)->first();
        $rs_field = DB::table("form_content")->where('field_property', 'db_field')->where('field_value', 'current_school')->where('form_id', $form->form_id)->first();
        if (!empty($rs_field)) {
            return $rs_field->build_id;
        } else {
            return 0;
        }
    }
}

if (!function_exists('getStudentGradeData')) {
    function getStudentGradeData($state_id)
    {
        $grade_data = DB::table("studentgrade")->where('stateID', $state_id)->get();
        if (count($grade_data) > 0) {
            return $grade_data;
        } else {
            return array();
        }
    }
}

if (!function_exists('getStudentGradeDataYear')) {
    function getStudentGradeDataYear($state_id, $term_arr, $term_arr1, $subjects)
    {
        $sub = array();
        $configSubject = Config::get('variables.subjects');
        foreach ($subjects as $key => $value) {
            if (isset($configSubject[$value]))
                $sub[] = $configSubject[$value];
        }
        /* $grade_data = DB::table("studentgrade")->where('stateID', $state_id)->where(function ($query) use ($term_arr1, $term_arr) {
            $query->where('academicYear', $term_arr)
                  ->orWhere('academicYear', $term_arr1);
        })->whereIn('courseType', $sub)->orderBy('academicTerm')->get();*/
        $grade_data = DB::table("studentgrade")->where('stateID', $state_id)->where(function ($query) use ($term_arr1, $term_arr) {
            $query->where('academicYear', $term_arr)
                ->orWhere('academicYear', $term_arr1);
        })->whereIn('courseTypeID', array(11, 30, 35, 39, 18))->orderBy('academicTerm')->get();
        $tmp_data = $tmp_store = array();
        $type = "";
        foreach ($grade_data as $key => $value) {
            $tmpstr = $value->academicTerm . "-" . $value->courseTypeID;
            if (strstr(strtolower($value->academicTerm) . "-", "wk") || strstr(strtolower($value->academicTerm) . "-", "week")) {
                $type = "9W";
            } elseif (strstr(strtolower($value->academicTerm) . "-", "sem") || strstr(strtolower($value->academicTerm) . "-", "semester")) {
                $type = "SEM";
            } else {
                $type = "YE";
            }
            $type = "9W";
            if (!in_array($tmpstr, $tmp_store)) {
                $tmp_data[] = $value;
                //$tmp_store[] = $value->academicTerm."-".$value->courseTypeID;
            }
        }
        return array("type" => $type, "data" => $tmp_data);
    }
}

if (!function_exists('getStudentGradeDataYearLate')) {
    function getStudentGradeDataYearLate($state_id, $term_calc, $academic_years, $subjects)
    {
        $sub = array();
        $configSubject = Config::get('variables.subjects');
        foreach ($subjects as $key => $value) {
            if (isset($configSubject[$value]))
                $sub[] = $configSubject[$value];
        }
        /* $grade_data = DB::table("studentgrade")->where('stateID', $state_id)->where(function ($query) use ($term_arr1, $term_arr) {
            $query->where('academicYear', $term_arr)
                  ->orWhere('academicYear', $term_arr1);
        })->whereIn('courseType', $sub)->orderBy('academicTerm')->get();*/
        //print_r($term_calc);exit;
        $grade_data = DB::table("studentgrade")->where('stateID', $state_id)->whereIn("academicYear", $academic_years)->whereIn("GradeName", $term_calc)->whereIn('courseTypeID', array(11, 30, 35, 39, 18))->orderBy('academicYear')->get();
        $tmp_data = $tmp_store = array();
        $type = "";
        foreach ($grade_data as $key => $value) {
            $tmpstr = $value->academicTerm . "-" . $value->courseTypeID;
            if (strstr(strtolower($value->academicTerm) . "-", "wk") || strstr(strtolower($value->academicTerm) . "-", "week")) {
                $type = "9W";
            } elseif (strstr(strtolower($value->academicTerm) . "-", "sem") || strstr(strtolower($value->academicTerm) . "-", "semester")) {
                $type = "SEM";
            } else {
                $type = "YE";
            }
            $type = "9W";
            if (!in_array($tmpstr, $tmp_store)) {
                $tmp_data[] = $value;
                //$tmp_store[] = $value->academicTerm."-".$value->courseTypeID;
            }
        }
        return array("type" => $type, "data" => $tmp_data);
    }
}

if (!function_exists('getConsolidatedGradeWeekData')) {
    function getConsolidatedGradeWeekData($grade_data, $course_type_id, $conversion)
    {
        if ($conversion == "SEM") {
            $first_sem = $second_sem = $third_sem = $fourth_sem = 0;
            foreach ($grade_data as $key => $value) {
                if ($course_type_id == $value->courseTypeID) {
                    if (strtolower(str_replace(" ", "", $value->academicTerm)) == "1-9wk" || strtolower(str_replace(" ", "", $value->academicTerm)) == "1-9week") {
                        $first_sem = $value->numericGrade;
                    } elseif (strtolower(str_replace(" ", "", $value->academicTerm)) == "2-9wk" || strtolower(str_replace(" ", "", $value->academicTerm)) == "2-9week") {
                        $second_sem = $value->numericGrade;
                    } elseif (strtolower(str_replace(" ", "", $value->academicTerm)) == "3-9wk" || strtolower(str_replace(" ", "", $value->academicTerm)) == "3-9week") {
                        $third_sem = $value->numericGrade;
                    } elseif (strtolower(str_replace(" ", "", $value->academicTerm)) == "4-9wk" || strtolower(str_replace(" ", "", $value->academicTerm)) == "4-9week") {
                        $fourth_sem = $value->numericGrade;
                    }
                }
            }
            return array("Semester 1" => number_format(($first_sem + $second_sem) / 2, 0), "Semester 2" => number_format(($third_sem + $fourth_sem) / 2, 0));
        }
        if ($conversion == "YE") {
            $total = $count = 0;
            foreach ($grade_data as $key => $value) {
                if ($course_type_id == $value->courseTypeID) {
                    $total += $value->numericGrade;
                    $count++;
                }
            }
            return array("Yearly" => number_format($total / $count, 0));
        }
    }
}

/*if (!function_exists('getStudentConductData')) {
    function getStudentConductData($state_id){
        $conduct_data = DB::table("student_conduct_disciplinary")->where('stateID', $state_id)->first();
        //    print_r($conduct_data);exit;
        if(!empty($conduct_data) > 0)
        {
            return $conduct_data;
        }
        else
        {
            return array();
        }
    }
}*/

if (!function_exists('getStudentStandardData')) {
    function getStudentStandardData($state_id)
    {
        $standard_data = DB::table("student_standard_testing")->where('stateID', $state_id)->first();
        //    print_r($standard_data);exit;
        if (!empty($standard_data) > 0) {
            return $standard_data;
        } else {
            return array();
        }
    }
}

if (!function_exists('getAcademicYearGrade')) {
    function getAcademicYearGrade($grade_data)
    {
        $academic_year = array();
        foreach ($grade_data as $key => $value) {
            if (!in_array($value->academicYear, $academic_year)) {
                $academic_year[] = $value->academicYear;
            }
        }
        return $academic_year;
    }
}

if (!function_exists('getAcademicTerms')) {
    function getAcademicTerms($grade_data)
    {
        //    print_r()
        $academic_terms = array();
        foreach ($grade_data as $key => $value) {
            if (!in_array($value['academicTerm'], $academic_terms)) {
                $academic_terms[$value['academicTerm']] = $value['academicTerm'];
            }
        }
        //print_r($academic_terms);exit;
        return $academic_terms;
    }
}

if (!function_exists('getEligibilities')) {
    function getEligibilities($choice_id, $type = '')
    {
        $program = DB::table("application_programs")->where("id", $choice_id)->first();
        if (!empty($program)) {
            if ($type == '') {
                $eligibilities = DB::table("program_eligibility")->where("program_id", $program->program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibiility.override", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
            } else {
                $eligibilities = DB::table("program_eligibility")->where("program_id", $program->program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->where("eligibility_template.name", $type)->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibiility.override", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
            }
        } else {
            $eligibilities = array();
        }
        return $eligibilities;
    }
}

if (!function_exists('getEligibilitiesDynamic')) {
    function getEligibilitiesDynamic($choice_id, $type = '')
    {
        $program = DB::table("application_programs")->where("id", $choice_id)->first();
        if (!empty($program)) {
            if ($type == '') {
                $eligibilities = DB::table("program_eligibility")->where("program_id", $program->program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibiility.override", "eligibility_template.sort")->orderBy('eligibility_template.sort')->where("program_eligibility.application_id", $program->application_id)->get();
            } else {
                $eligibilities = DB::table("program_eligibility")->where("program_id", $program->program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->where("eligibility_template.name", $type)->where("program_eligibility.application_id", $program->application_id)->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibiility.override", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
            }
        } else {
            $eligibilities = array();
        }
        return $eligibilities;
    }
}

if (!function_exists('getEligibilitiesDynamicProcessing')) {
    function getEligibilitiesDynamicProcessing($choice_id, $type = '')
    {
        $program = DB::table("application_programs")->where("id", $choice_id)->first();
        if (!empty($program)) {
            if ($type == '') {
                $eligibilities = DB::table("program_eligibility")->where("program_id", $program->program_id)->where('program_eligibility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibiility.override", "eligibility_template.sort")->orderBy('eligibility_template.sort')->where("program_eligibility.application_id", $program->application_id)->get();
            } else {
                $eligibilities = DB::table("program_eligibility")->where("program_id", $program->program_id)->where('program_eligibility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->where("eligibility_template.name", $type)->where("program_eligibility.application_id", $program->application_id)->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibiility.override", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
            }
        } else {
            $eligibilities = array();
        }
        return $eligibilities;
    }
}

if (!function_exists('getEligibilitiesDynamic_bk')) {
    function getEligibilitiesDynamic_bk($choice_id, $type = '')
    {
        $program = DB::table("application_programs")->where("id", $choice_id)->first();
        if (!empty($program)) {
            if ($type == '') {
                $eligibilities = DB::table("program_eligibility")->where("program_id", $program->program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibiility.override", "eligibility_template.sort")->orderBy('eligibility_template.sort')->where("program_eligibility.application_id", $program->application_id)->get();
            } else {
                $eligibilities = DB::table("program_eligibility")->where("program_id", $program->program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->where("eligibility_template.name", $type)->where("program_eligibility.application_id", $program->application_id)->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibiility.override", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
            }
        } else {
            $eligibilities = array();
        }
        return $eligibilities;
    }
}

if (!function_exists('getEligibilitiesLateSubmission')) {
    function getEligibilitiesLateSubmission($choice_id, $type = '')
    {
        $program = DB::table("application_programs")->where("id", $choice_id)->first();
        if (!empty($program)) {
            //        echo $program->program_id;exit;
            if ($type == '') {
                $eligibilities = DB::table("program_eligibility_late_submission")->where("program_id", $program->program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility_late_submission.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->select("program_eligibility_late_submission.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibiility.override", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
            } else {
                $eligibilities = DB::table("program_eligibility_late_submission")->where("program_id", $program->program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility_late_submission.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->where("eligibility_template.name", $type)->select("program_eligibility_late_submission.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibiility.override", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
            }
        } else {
            $eligibilities = array();
        }
        return $eligibilities;
    }
}

if (!function_exists('getSubmissionEligibilities')) {
    function getSubmissionEligibilities($submission)
    {
        $choice_id = array();
        if ($submission->first_choice != '')
            $choice_id[] = $submission->first_choice;
        if ($submission->second_choice != '')
            $choice_id[] = $submission->second_choice;
        $program = DB::table("application_programs")->whereIn("id", $choice_id)->select("program_id")->get();
        $arr = array();
        foreach ($program as $value) {
            $arr[] = $value->program_id;
        }
        if (count($arr) > 0) {
            $eligibilities = \App\Modules\Program\Models\ProgramEligibility::join('eligibility_template', 'eligibility_template.id', 'program_eligibility.eligibility_type')->whereIn("program_id", $arr)->whereRaw("FIND_IN_SET('" . $submission->next_grade . "', grade_lavel_or_recommendation_by)")->where("application_id", $submission->application_id)->where('program_eligibility.status', 'Y')->get();
            $arr = array();
            foreach ($eligibilities as $value) {
                $arr[] = $value->name;
            }
            return $arr;
        } else {
            $eligibilities = array();
        }
        return $eligibilities;
    }
}

if (!function_exists('getLateSubmissionEligibilities')) {
    function getLateSubmissionEligibilities($submission)
    {
        $choice_id = array();
        if ($submission->first_choice != '')
            $choice_id[] = $submission->first_choice;
        if ($submission->second_choice != '')
            $choice_id[] = $submission->second_choice;
        $program = DB::table("application_programs")->whereIn("id", $choice_id)->select("program_id")->get();
        $arr = array();
        foreach ($program as $value) {
            $arr[] = $value->program_id;
        }
        if (count($arr) > 0) {
            $eligibilities = \App\Modules\Program\Models\ProgramEligibilityLateSubmission::join('eligibility_template', 'eligibility_template.id', 'program_eligibility_late_submission.eligibility_type')->whereIn("program_id", $arr)->whereRaw("FIND_IN_SET('" . $submission->next_grade . "', grade_lavel_or_recommendation_by)")->where('program_eligibility_late_submission.status', 'Y')->get();
            $arr = array();
            foreach ($eligibilities as $value) {
                $arr[] = $value->name;
            }
            return $arr;
        } else {
            $eligibilities = array();
        }
        return $eligibilities;
    }
}

if (!function_exists('getEligibilitiesByProgram')) {
    function getEligibilitiesByProgram($program_id, $type = '')
    {
        if ($type == '') {
            $eligibilities = DB::table("program_eligibility")->where("program_id", $program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
        } else {
            $eligibilities = DB::table("program_eligibility")->where("program_id", $program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->where("eligibility_template.name", $type)->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
        }
        return $eligibilities;
    }
}

if (!function_exists('getEligibilitiesByProgramDynamic')) {
    function getEligibilitiesByProgramDynamic($program_id, $application_ids, $type = '')
    {
        if ($type == '') {
            $eligibilities = DB::table("program_eligibility")->where("program_id", $program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->whereIn('program_eligibility.application_id', $application_ids)->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
        } else {
            $eligibilities = DB::table("program_eligibility")->where("program_id", $program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->whereIn('program_eligibility.application_id', $application_ids)->where("eligibility_template.name", $type)->select("program_eligibility.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
        }
        return $eligibilities;
    }
}

if (!function_exists('getEligibilitiesByProgramLateSubmission')) {
    function getEligibilitiesByProgramLateSubmission($program_id, $type = '')
    {
        if ($type == '') {
            $eligibilities = DB::table("program_eligibility_late_submission")->where("program_id", $program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility_late_submission.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->select("program_eligibility_late_submission.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
        } else {
            $eligibilities = DB::table("program_eligibility_late_submission")->where("program_id", $program_id)->where('eligibiility.status', 'Y')->join("eligibiility", "eligibiility.id", "program_eligibility_late_submission.assigned_eigibility_name")->join("eligibility_template", "eligibility_template.id", "eligibiility.template_id")->where("eligibility_template.name", $type)->select("program_eligibility_late_submission.*", "eligibiility.name as eligibility_name", "eligibility_template.name as eligibility_ype", "eligibility_template.sort")->orderBy('eligibility_template.sort')->get();
        }
        return $eligibilities;
    }
}

if (!function_exists('getEligibilityContent1')) {
    function getEligibilityContent1($eligibility_id)
    {
        $eligibiility_data = DB::table("eligibility_content")->where("eligibility_id", $eligibility_id)->first();
        return json_decode($eligibiility_data->content);
    }
}

if (!function_exists('getFieldSequence')) {
    function getFieldSequence($field_id)
    {
        $form = DB::table("form_build")->where("id", $field_id)->first();
        if (!empty($form)) {
            $fields = DB::table("form_build")->where("form_id", $form->form_id)->orderBy('sort', 'asc')->get();
            return $fields;
        } else
            return array();
    }
}

if (!function_exists('getStudentName')) {
    function getStudentName($student_id)
    {
        $student = DB::table("student")->where('stateID', $student_id)->first();
        if (!empty($student)) {
            return " - " . $student->first_name . " " . $student->last_name;
        } else
            return "";
    }
}

if (!function_exists('findSubmissionForm')) {
    function findSubmissionForm($application_id)
    {
        $form_data = DB::table("form")->join("application", "application.form_id", "form.id")->where("application.id", $application_id)->select("form.name")->first();
        if (!empty($form_data))
            return $form_data->name;
        else
            return "";
    }
}

if (!function_exists('findFormName')) {
    function findFormName($form_id)
    {
        $form_data = DB::table("form")->where("id", $form_id)->first();
        if (!empty($form_data))
            return $form_data->name;
        else
            return "";
    }
}

if (!function_exists('getApplicationDetailsById')) {
    function getApplicationDetailsById($application_id)
    {
        $enrollment_id = DB::table("application")->where("id", $application_id)->pluck("enrollment_id")->first();
        $birthday_cut_off = DB::table("enrollments")->where("id", $enrollment_id)->pluck("perk_birthday_cut_off")->first();
        return $birthday_cut_off;
        // SELECT * FROM enrollments WHERE STATUS = 'Y' AND id = 5
        return $enrollment_id;
        // SELECT * FROM APPLICATION WHERE ID=59
    }
}

if (!function_exists('getProgramName')) {
    function getProgramName($program_id)
    {
        $program = DB::table("program")->where("id", $program_id)->first();
        if (!empty($program))
            return $program->name;
        else
            return "";
    }
}

if (!function_exists('findGPA')) {
    function findGPA($score, $gpa_limit)
    {
        $gpa_grade = "0";
        $count = 4;
        foreach ($gpa_limit as $key => $value) {
            $tmp = explode("-", $value);
            if ($score >= $tmp[0] && $score <= $tmp[1]) {
                $gpa_grade = $count;
                break;
            }
            $count--;
        }
        return $gpa_grade;
    }
}

if (!function_exists('getSubmissionGradeDataDynamic')) {
    function getSubmissionGradeDataDynamic($submission_id, $term_calc, $academic_years)
    {
        // dd($term_calc);
        $year = explode("-", $academic_years[0]);
        $tmpYear = [];
        if (strlen($year[1]) == 2) {
            $tmpYear[] = $year[0] . "-" . $year[1];
            $tmpYear[] = $year[0] . "-20" . $year[1];
        } else {
            $tmpYear[] = $year[0] . "-" . $year[1];
            $tmpYear[] = $year[0] . "-" . ($year[1] - 2000);
        }
        $submission_data = DB::table("submission_grade")->where("submission_id", $submission_id)->whereIn('academicYear', $academic_years)->whereIn('courseTypeID', array(11, 30, 35, 39, 18))->whereIn('GradeName', $term_calc)->get();
        if (!empty($submission_data)) {
            $tmpdata = array();
            foreach ($submission_data as $key => $value) {
                $year = explode("-", $value->academicYear);
                $tmpYear = $year[1];
                if (strlen($year[1]) == 4) {
                    $tmpYear = $year[0] . "-" . $year[1];
                }
                $tmp = array();
                $tmp['submission_id'] = $value->submission_id;
                $tmp['academicYear'] = $tmpYear;
                $tmp['sAcademicYear'] = $tmpYear;
                $tmp['academicTerm'] = $value->GradeName;
                $tmp['GradeName'] = $value->GradeName;
                $tmp['courseType'] = $value->courseType;
                $tmp['courseTypeID'] = $value->courseTypeID;
                $tmp['courseName'] = $value->courseName;
                $tmp['sectionNumber'] = $value->sectionNumber;
                $tmp['numericGrade'] = $value->numericGrade;
                $tmpdata[] = $tmp;
            }
            return $tmpdata;
        } else {
            return array();
        }
    }
}

if (!function_exists('getSubmissionGradeData')) {
    function getSubmissionGradeData($submission_id)
    {
        $term1 = (date("Y") - 1) . "-" . (date("Y"));
        $term2 = (date("Y") - 1) . "-" . (date("y"));
        $term1 = "2019-2020";
        $submission_data = DB::table("submission_grade")->where("submission_id", $submission_id)->where(function ($query) use ($term1, $term2) {
            $query->where('academicYear', $term1)
                ->orWhere('academicYear', $term2);
        })->whereIn('courseTypeID', array(11, 30, 35, 39, 18))->get();
        if (!empty($submission_data)) {
            $tmpdata = array();
            foreach ($submission_data as $key => $value) {
                $tmp = array();
                $tmp['submission_id'] = $value->submission_id;
                $tmp['academicYear'] = $value->academicYear;
                $tmp['sAcademicYear'] = $value->academicYear;
                $tmp['academicTerm'] = $value->GradeName;
                $tmp['GradeName'] = $value->GradeName;
                $tmp['courseType'] = $value->courseType;
                $tmp['courseTypeID'] = $value->courseTypeID;
                $tmp['courseName'] = $value->courseName;
                $tmp['sectionNumber'] = $value->sectionNumber;
                $tmp['numericGrade'] = $value->numericGrade;
                $tmpdata[] = $tmp;
            }
            return $tmpdata;
        } else {
            return array();
        }
    }
}

if (!function_exists('getLateSubmissionGradeData')) {
    function getLateSubmissionGradeData($submission_id, $term_calc, $academic_years)
    {
        $term1 = (date("Y") - 1) . "-" . (date("Y"));
        $term2 = (date("Y") - 1) . "-" . (date("y"));
        $term1 = "2019-2020";
        $tmpdata = array();
        foreach ($academic_years as $avalue) {
            $term1 = $term2 = $avalue;
            foreach ($term_calc as $tvalue) {
                $submission_data = DB::table("submission_grade")->where("submission_id", $submission_id)->where(function ($query) use ($term1, $term2) {
                    $query->where('academicYear', $term1)
                        ->orWhere('academicYear', $term2);
                })->whereIn('courseTypeID', array(11, 30, 35, 39, 18))->where("GradeName", $tvalue)->get();
                if (!empty($submission_data)) {
                    //$tmpdata = array();
                    foreach ($submission_data as $key => $value) {
                        $tmp = array();
                        $tmp['submission_id'] = $value->submission_id;
                        $tmp['academicYear'] = $value->academicYear;
                        $tmp['sAcademicYear'] = $value->academicYear;
                        $tmp['academicTerm'] = $value->GradeName;
                        $tmp['GradeName'] = $value->GradeName;
                        $tmp['courseType'] = $value->courseType;
                        $tmp['courseTypeID'] = $value->courseTypeID;
                        $tmp['courseName'] = $value->courseName;
                        $tmp['sectionNumber'] = $value->sectionNumber;
                        $tmp['numericGrade'] = $value->numericGrade;
                        $tmpdata[] = $tmp;
                    }
                }
            }
        }
        return $tmpdata;
    }
}

if (!function_exists('getSubmissionGradeCalculationData')) {
    function getSubmissionGradeCalculationData($submission_id)
    {
        $submission_grade_data = DB::table("submission_academic_grade_calculation")->where("submission_id", $submission_id)->first();
        if (!empty($submission_grade_data) > 0) {
            return $submission_grade_data;
        } else {
            return array();
        }
    }
}

if (!function_exists('getViewEnableFields')) {
    function getViewEnableFields($form_id)
    {
        $form = DB::table("form_content")->where("form_id", $form_id)->where("field_property", "display_view")->where("field_value", "yes")->get();
        if (!empty($form)) {
            $field_ids = array();
            foreach ($form as $key => $value) {
                $field_ids[] = $value->build_id;
            }
            //print_r($field_ids);exit;
            $fields = DB::table("form_content")->whereIn("build_id", $field_ids)->where('form_id', $form_id)->where('field_property', 'label')->get();
            return $fields;
        } else
            return array();
    }
}

if (!function_exists('getViewEnable')) {
    function getViewEnable($build_id)
    {
        $form = DB::table("form_content")->where("build_id", $build_id)->where("field_property", "display_view")->where("field_value", "yes")->first();
        if (!empty($form))
            return true;
        else
            return false;
    }
}

if (!function_exists('getAcademicScoreDynamic')) {
    function getAcademicScoreDynamic($student_id, $courseType, $GradeName, $term, $submission_id = 0)
    {
        $year = explode("-", $term);
        $tmpYear = [];
        if (strlen($year[1]) == 2) {
            $tmpYear[] = $year[0] . "-" . $year[1];
            $tmpYear[] = $year[0] . "-20" . $year[1];
        } else {
            $tmpYear[] = $year[0] . "-" . $year[1];
            $tmpYear[] = $year[0] . "-" . ($year[1] - 2000);
        }
        $data = DB::table("submission_grade")->where("submission_id", $submission_id)->where("courseType", $courseType)->where("GradeName", $GradeName)->whereIn('academicYear', $tmpYear)->first();
        if (!empty($data)) {
            return $data->numericGrade;
        } else
            return 0;
    }
}

if (!function_exists('getAcademicScore')) {
    function getAcademicScore($student_id, $courseType, $GradeName, $term1, $term2, $submission_id = 0)
    {
        $data = DB::table("submission_grade")->where("submission_id", $submission_id)->where("courseType", $courseType)->where("GradeName", $GradeName)->where(function ($query) use ($term1, $term2) {
            $query->where('academicYear', $term1)
                ->orWhere('academicYear', $term2);
        })->first();
        if (empty($data) && $student_id != '') {
            $data = DB::table("studentgrade")->where("stateID", $student_id)->where("courseType", $courseType)->where("GradeName", $GradeName)->where(function ($query) use ($term1, $term2) {
                $query->where('academicYear', $term1)
                    ->orWhere('academicYear', $term2);
            })->first();
        }
        if (!empty($data)) {
            return $data->numericGrade;
        } else
            return 0;
    }
}

if (!function_exists('getSubmissionAcademicScore')) {
    function getSubmissionAcademicScore($submission_id, $courseType, $GradeName, $term1, $term2)
    {
        $data = DB::table("submission_grade")->where("submission_id", $submission_id)->where("courseType", $courseType)->where("GradeName", $GradeName)->where(function ($query) use ($term1, $term2) {
            $query->where('academicYear', $term1)
                ->orWhere('academicYear', $term2);
        })->first();
        if (!empty($data)) {
            return $data->numericGrade;
        } else {
            return 0;
            $student_id = DB::table("submissions")->where("id", $submission_id)->where("student_id", "<>", "")->select('student_id')->first();
            if (!empty($student_id)) {
                /*$data = DB::table("studentgrade")->where("stateID", $student_id->student_id)->get();
                foreach($data as $key=>$value)
                {
                    $grade_data = [
                        'submission_id' => $submission_id,
                        'academicYear' => $value->academicYear,
                        'academicTerm' => $value->academicTerm,
                        'courseTypeID' => $value->courseTypeID,
                        'courseName' => $value->courseName,
                        'numericGrade' => $value->numericGrade,
                        'sectionNumber' => $value->sectionNumber,
                        'courseType' => $value->courseType,
                        'stateID' => $value->stateID,
                        'GradeName' => $value->GradeName,
                        'sequence' => $value->sequence,
                        'courseFullName' => $value->courseFullName,
                        'fullsection_number' => $value->fullsection_number,
                    ];
                    DB::table("submission_grade")->insert($grade_data);
                }*/
                $data = DB::table("studentgrade")->where("stateID", $student_id->student_id)->where("courseType", $courseType)->where("GradeName", $GradeName)->where(function ($query) use ($term1, $term2) {
                    $query->where('academicYear', $term1)
                        ->orWhere('academicYear', $term2);
                })->first();
                if (!empty($data)) {
                    /*foreach($data as $key=>$value)
                    {
                        $grade_data = [
                            'submission_id' => $submission_id,
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
                        if($grade_data['academicYear'] != null)
                        DB::table("submission_grade")->insert($grade_data);
                    }*/
                    return $data->numericGrade;
                }
            }
            return 0;
        }
    }
}

if (!function_exists('getSubmissionAcademicScoreMissing')) {
    function getSubmissionAcademicScoreMissing($submission_id, $courseType, $GradeName, $term1, $term2)
    {
        $data = DB::table("submission_grade")->where("submission_id", $submission_id)->where("courseType", $courseType)->where("GradeName", $GradeName)->where(function ($query) use ($term1, $term2) {
            $query->where('academicYear', $term1)
                ->orWhere('academicYear', $term2);
        })->first();
        if (!empty($data)) {
            return $data->numericGrade;
        } else {
            $student_id = DB::table("submissions")->where("id", $submission_id)->where("student_id", "<>", "")->select('student_id')->first();
            if (!empty($student_id)) {
                $data = DB::table("studentgrade")->where("stateID", $student_id->student_id)->where("courseType", $courseType)->where("GradeName", $GradeName)->where(function ($query) use ($term1, $term2) {
                    $query->where('academicYear', $term1)
                        ->orWhere('academicYear', $term2);
                })->first();
                if (!empty($data)) {
                    foreach ($data as $key => $value) {
                        $grade_data = [
                            'submission_id' => $submission_id,
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
                        if ($grade_data['academicYear'] != null)
                            DB::table("submission_grade")->insert($grade_data);
                    }
                    return $data->numericGrade;
                }
            }
            return "NA";
        }
    }
}

if (!function_exists('getApplicationProgramName')) {
    function getApplicationProgramName($program_id)
    {
        $rs = DB::table("program")->join("application_programs", "application_programs.program_id", "program.id")->where("application_programs.id", $program_id)->select("name")->first();
        if (!empty($rs)) {
            return $rs->name;
        } else
            return "";
    }
}

if (!function_exists('getSetEligibilityData')) {
    function getSetEligibilityData($application_program_id, $type)
    {
        $rs = DB::table("program")->join("application_programs", "application_programs.program_id", "program.id")->where("application_programs.id", $application_program_id)->select("program.id")->first();
        if (!empty($rs)) {
            $program_id = $rs->id;
            $data = DB::table('seteligibility_extravalue')->where('program_id', $program_id)->where('eligibility_type', $type)->first();
            if (!empty($data))
                return json_decode($data->extra_values);
            else
                return array();
        } else {
            return array();
        }
    }
}

if (!function_exists('getSetEligibilityDataLS')) {
    function getSetEligibilityDataLS($application_program_id, $type)
    {
        $rs = DB::table("program")->join("application_programs", "application_programs.program_id", "program.id")->where("application_programs.id", $application_program_id)->select("program.id")->first();
        if (!empty($rs)) {
            $program_id = $rs->id;
            $data = DB::table('seteligibility_extravalue_late_submission')->where('program_id', $program_id)->where('eligibility_type', $type)->first();
            if (!empty($data))
                return json_decode($data->extra_values);
            else
                return array();
        } else {
            return array();
        }
    }
}

if (!function_exists('getSetEligibilityDataDynamic')) {
    function getSetEligibilityDataDynamic($application_program_id, $type)
    {
        $rs = DB::table("program")->join("application_programs", "application_programs.program_id", "program.id")->where("application_programs.id", $application_program_id)->select("program.id", "application_programs.application_id")->first();
        if (!empty($rs)) {
            $program_id = $rs->id;
            $data = DB::table('seteligibility_extravalue')->where('application_id', $rs->application_id)->where('program_id', $program_id)->where('eligibility_type', $type)->first();
            if (!empty($data))
                return json_decode($data->extra_values);
            else
                return array();
        } else {
            return array();
        }
    }
}

if (!function_exists('getSetEligibilityDataByProgramID')) {
    function getSetEligibilityDataByProgramID($program_id, $type)
    {
        $data = DB::table('seteligibility_extravalue')->where('program_id', $program_id)->where('eligibility_type', $type)->first();
        if (!empty($data))
            return json_decode($data->extra_values);
        else
            return array();
    }
}

if (!function_exists('getStudentConductData')) {
    function getStudentConductData($state_id, $submission_id = 0)
    {
        $conduct_data = DB::table("submission_conduct_discplinary_info")->where('submission_id', $submission_id)->first();
        if (empty($conduct_data)) {
            $conduct_data = DB::table("student_conduct_disciplinary")->where('stateID', $state_id)->first();
        }
        if (!empty($conduct_data) > 0) {
            return $conduct_data;
        } else {
            return array();
        }
    }
}

if (!function_exists('getDateTimeFormat')) {
    function getDateTimeFormat($date)
    {
        if ($date != '')
            return date('m/d/Y h:i:s A', strtotime($date));
        else {
            return date('m/d/Y');
        }
    }
}

if (!function_exists('getDateFormat')) {
    function getDateFormat($date)
    {
        return date('m/d/Y', strtotime($date));
    }
}

if (!function_exists('findArrayKey')) {
    function findArrayKey($arr, $value)
    {
        foreach ($arr as $key => $val) {
            if ($val == $value)
                return $key;
        }
        return "";
    }
}

if (!function_exists('getUserName')) {
    function getUserName($user_id)
    {
        $username = "";
        if (isset($user_id)) {
            $user = DB::table("users")->where("id", $user_id)->first();
            if (!empty($user)) {
                $username = ucwords($user->first_name . ' ' . $user->last_name);
            }
        }
        return $username;
    }
}

if (!function_exists('getAlertMsg')) {
    function getAlertMsg($msg_title)
    {
        $data = DB::table("common_alert_msg")->where("msg_title", $msg_title)->first();
        if (!empty($data))
            return $data->msg_txt;
        else
            return "";
    }
}

if (!function_exists('fetch_conduct_details')) {
    function fetch_conduct_details($student_id)
    {
        $data = DB::table("student_cdi_details")->where("stateID", $student_id)->orderByDesc('datetime')->get();
        if (!empty($data)) {
            return $data;
        } else {
            return array();
        }
    }
}

if (!function_exists('getMagnetSchool')) {
    function getMagnetSchool($program_id)
    {
        $data = DB::table("program")->where("id", $program_id)->select("magnet_school")->first();
        if (!empty($data)) {
            return $data->magnet_school;
        } else {
            return "";
        }
    }
}

if (!function_exists('getFieldLabel')) {
    function getFieldLabel($field)
    {
        $content = DB::table("form_content")->where("field_value", $field)->first(['build_id']);
        if (isset($content)) {
            return getContentValue($content->build_id, 'label');
        } else {
            return null;
        }
    }
}

if (!function_exists('sendMail')) {
    function sendMail($emailArr, $log = false)
    {
        $msg = $emailArr['msg'];
        $msg = str_replace("{student_name}", (isset($emailArr['student_first_name']) ? $emailArr['student_first_name'] . " " . $emailArr['student_last_name'] : (isset($emailArr['first_name']) ? $emailArr['first_name'] . " " . $emailArr['last_name'] : "")), $msg);
        // $msg = str_replace("{student_name}", (isset($emailArr['first_name']) ? $emailArr['first_name']." ".$emailArr['last_name'] : ""), $msg);
        $msg = str_replace("{parent_name}", (isset($emailArr['parent_name']) ? $emailArr['parent_name'] : ""), $msg);
        $msg = str_replace("{confirm_number}", (isset($emailArr['confirm_number']) ? $emailArr['confirm_number'] : ""), $msg);
        $msg = str_replace("{confirmation_no}", (isset($emailArr['confirmation_no']) ? $emailArr['confirmation_no'] : ""), $msg);
        $msg = str_replace("{parent_grade_cdi_upload_link}", url('/upload/grade/cdi'), $msg);
        $msg = str_replace("{transcript_due_date}", (isset($emailArr['transcript_due_date']) ? $emailArr['transcript_due_date'] : ""), $msg);
        $emailArr['email_text'] = $msg;
        $emailArr['logo'] = getDistrictLogo();
        $data = array();
        $data['submission_id'] = (isset($emailArr['id']) ? $emailArr['id'] : 0);
        $data['email_to'] = $emailArr['email'];
        $data['email_subject'] = $emailArr['subject'];
        $data['email_body'] = $msg;
        $data['logo'] = getDistrictLogo();
        $data['module'] = $emailArr['module'] ?? "Edit Communication";
        try {
            Mail::send('emails.index', ['data' => $emailArr], function ($message) use ($emailArr) {
                $message->to($emailArr['email']);
                $message->subject($emailArr['subject']);
            });
            $data['status'] = "success";
        } catch (Exception $e) {
            $data['status'] = "Error";
            Session::flash('error', 'Something went wrong.');
            return false;
        }
        if ($log) {
            createEmailActivityLog($data);
        }
        return true;
    }
}

if (!function_exists('getMaxGrade')) {
    function getMaxGrade($program_id)
    {
        $data = \App\Modules\Program\Models\Program::where("id", $program_id)->first();
        $grade_level = $data->grade_lavel;
        $grades = explode(",", $grade_level);
        $cgrade = $grades[count($grades) - 1];
        if ($cgrade == "PreK" || $cgrade == "K")
            return 0;
        else
            return $cgrade;
    }
}

if (!function_exists('getEnrollmentYear')) {
    function getEnrollmentYear($enrollment_id)
    {
        $enrollment = DB::table("enrollments")->where('id', $enrollment_id)->first();
        if (!empty($enrollment))
            return $enrollment->school_year;
        else
            return "";
    }
}

if (!function_exists('getApplicationName')) {
    function getApplicationName($application_id)
    {
        $application = DB::table("application")->where('id', $application_id)->first();
        if (!empty($application))
            return $application->application_name;
        else
            return "";
    }
}

if (!function_exists('get_signature')) {
    function get_signature($type)
    {
        $dist_config = App\Modules\DistrictConfiguration\Models\DistrictConfiguration::where('district_id', \Session('district_id'))
            ->where('name', $type)
            ->first();
        $signature = url('/resources/filebrowser/signature/common_signature.png');
        if (!empty($dist_config)) {
            $signature = $dist_config->value;
        }
        return str_replace("https://", "http://", $signature);
    }
}

if (!function_exists('changeTimezone')) {
    function changeTimezone()
    {
        $dist_config = App\Modules\District\Models\District::where('id', \Session('district_id'))
            ->select('district_timezone')
            ->first();
        file_put_contents("resources/filebrowser/Timezone.txt", ($dist_config->district_timezone ?? 'US/Central'));
    }
}

if (!function_exists('find_replace_string')) {
    function find_replace_string($str, $arr)
    {
        foreach ($arr as $key => $value) {
            //echo "{".$key."} - ".$value."<BR>";
            $str = str_replace("{" . $key . "}", $value, $str);
        }
        return $str;
    }
}

if (!function_exists('fetch_individual_custom_communication')) {
    function fetch_individual_custom_communication($id)
    {
        $data = DB::table("custom_communication_data")->where("submission_id", $id)->select('custom_communication_data.*')->orderByDesc('created_at')->get();
        $download_data = array();
        foreach ($data as $key => $value) {
            $tmp = array();
            $tmp['id'] = $value->id;
            $tmp['template_name'] = "";
            $tmp['program'] = "";
            $tmp['grade'] = "";
            $tmp['status'] = "";
            $tmp['generated_by'] = $value->generated_by;
            $tmp['total_count'] = 1;
            $tmp['file_name'] = $value->file_name;
            $tmp['created_at'] = getDateTimeFormat($value->created_at);
            $download_data[] = $tmp;
        }
        return $download_data;
    }
}

if (!function_exists('fetch_individual_email_log')) {
    function fetch_individual_email_log($id)
    {
        $data = DB::table("custom_communication_data")->where("submission_id", $id)->where('email_body', '<>', '')->select('custom_communication_data.*')->orderByDesc('created_at')->get();
        $download_data = array();
        foreach ($data as $key => $value) {
            $tmp = array();
            $tmp['id'] = $value->id;
            $tmp['template_name'] = "";
            $tmp['program'] = "";
            $tmp['grade'] = "";
            $tmp['status'] = "";
            $tmp['generated_by'] = $value->generated_by;
            $tmp['total_count'] = 1;
            $tmp['file_name'] = $value->file_name;
            $tmp['created_at'] = getDateTimeFormat($value->created_at);
            $tmp['email_body'] = $value->email_body;
            $tmp['email_subject'] = $value->email_subject;
            $tmp['email'] = $value->email;
            $download_data[] = $tmp;
        }
        return $download_data;
    }
}

if (!function_exists('getSubmissionStudentName')) {
    function getSubmissionStudentName($id)
    {
        $data = DB::table("submissions")->where("id", $id)->first();
        if (!empty($data)) {
            return $data->first_name . " " . $data->last_name;
        } else
            return "";
    }
}

if (!function_exists('generateShortCode')) {
    function generateShortCode($value)
    {
        $application_data1 = \App\Modules\Application\Models\Application::join("enrollments", "enrollments.id", "application.enrollment_id")->where("application.status", "Y")->where("application.id", $value->application_id)->select("application.*", "enrollments.school_year")->first();
        $tmp = array();
        $tmp['id'] = $value->id;
        $tmp['student_id'] = $value->student_id;
        $tmp['confirmation_no'] = $value->confirmation_no;
        $tmp['name'] = $value->first_name . " " . $value->last_name;
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
        $tmp['birth_date'] = getDateFormat($value->birthday);
        $tmp['student_name'] = $value->first_name . " " . $value->last_name;
        $tmp['parent_name'] = $value->parent_first_name . " " . $value->parent_last_name;
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
        $tmp['next_school_year'] = ($t1[0] + 1) . "-" . ($t1[1] + 1);
        $tmp['next_year'] = date("Y") + 1;
        return $tmp;
    }
}

if (!function_exists('getLocationInfoByIp')) {
    function getLocationInfoByIp($ip_address = '')
    {
        if (empty($ip_address)) {
            $client  = @$_SERVER['HTTP_CLIENT_IP'];
            $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
            $server  = @$_SERVER['SERVER_ADDR'];
            $remote  = @$_SERVER['REMOTE_ADDR'];
            if (!empty($client) && filter_var($client, FILTER_VALIDATE_IP)) {
                $ip = $client;
            } elseif (!empty($forward) && filter_var($forward, FILTER_VALIDATE_IP)) {
                $ip = $forward;
            } elseif (!empty($server) && filter_var($server, FILTER_VALIDATE_IP)) {
                $ip = $server;
            } else {
                $ip = $remote;
            }
        } else {
            $ip = $ip_address;
        }
        $ip_data = unserialize(file_get_contents("http://www.geoplugin.net/php.gp?ip=" . $ip));
        $result  = ['country' => '', 'city' => ''];
        if ($ip_data && $ip_data['geoplugin_countryCode'] != null) {
            $result['country'] = $ip_data['geoplugin_countryCode'];
            $result['city'] = $ip_data['geoplugin_city'];
        }
        return $result;
    }
}

if (!function_exists('getContratIPInfo')) {
    function getContratIPInfo($submission_id)
    {
        $data = DB::table("contract_logs")->where("submission_id", $submission_id)->first();
        $str = "";
        if (!empty($data)) {
            $str .= "<div>";
            $str .= "<strong>IP: </strong>" . $data->ip_address;
            if ($data->city != '')
                $str .= "<br><strong>City: </strong>" . $data->city;
            /*if($data->country != '')
                $str .= "<br><strong>Country: </strong>".$data->country;*/
            $str .= "</div>";
        }
        return $str;
    }
}

if (!function_exists('get_district_global_setting')) {
    function get_district_global_setting($type)
    {
        $dist_config = App\Modules\DistrictConfiguration\Models\DistrictConfiguration::where('name', $type)
            ->first();
        $config_value = '';
        if (!empty($dist_config)) {
            $config_value = $dist_config->value;
        }
        return $config_value;
    }
}

if (!function_exists('getGradeUploadDocs')) {
    function getGradeUploadDocs($submission_id, $type)
    {
        $docs = App\Modules\UploadGradeCDI\Models\GradeCdi::where('submission_id', $submission_id)->where('file_type', $type)->get(['file_name', 'created_at']);
        return $docs;
    }
}

if (!function_exists('getCDIUploadDocs')) {
    function getCDIUploadDocs($submission_id)
    {
        $docs = App\SubmissionDocuments::where('submission_id', $submission_id)->where('doc_cdi', '!=', '')->get();
        // dd($docs, $submission_id);
        if ($docs != '') {
            return $docs;
        }
        return '';
    }
}

if (!function_exists('getContractConfiguration')) {
    function getContractConfiguration()
    {
        $district_id = session('district_id');
        $enrollment_id = session('enrollment_id');
        $contract_config = App\Modules\Offers\Models\ContractConfiguration::where('district_id', $district_id)->where('enrollment_id', $enrollment_id)->first();
        if (isset($contract_config) && !empty($contract_config)) {
            return $contract_config;
        }
        return '';
    }
}

if (!function_exists('getAcademicYears')) {
    function getAcademicYears()
    {
        $academic_year_ary = [];
        $current_year = date('Y');
        for ($i = 0; $i < 5; $i++) {
            $tmp_year = $current_year . '-' . ($current_year + 1);
            array_push($academic_year_ary, $tmp_year);
            $current_year--;
        }
        return $academic_year_ary;
    }
}

if (!function_exists('getApplicationGrades')) {
    function getApplicationGrades()
    {
        $application_id = Session::get("application_id");
        $rs = \App\Modules\Application\Models\ApplicationProgram::join("grade", "grade.id", "application_programs.grade_id")->where("application_id", $application_id)->pluck("grade.name")->toArray();
        $data = array_unique($rs);
        sort($data);
        return $data;
    }
}

if (!function_exists('check_last_process')) {
    function check_last_process($submission_id)
    {
        $rsMain = \App\Modules\Submissions\Models\SubmissionsFinalStatus::where("submission_id", $submission_id)->first();
        $rsWait = \App\Modules\Submissions\Models\SubmissionsWaitlistFinalStatus::where("submission_id", $submission_id)->orderBy("id", "DESC")->first();
        $rsLate = \App\Modules\Submissions\Models\LateSubmissionFinalStatus::where("submission_id", $submission_id)->orderBy("id", "DESC")->first();
        if (!empty($rsLate)) {
            if (!empty($rsWait)) {
                if ($rsWait->updated_at > $rsLate->updated_at) {
                    return array("id" => $rsWait->id, "finalObj" => "waitlist", "version" => $rsWait->version);
                } else {
                    return array("id" => $rsLate->id, "finalObj" => "late_submission", "version" => $rsLate->version);
                }
            } else {
                if (!empty($rsMain)) {
                    if ($rsMain->updated_at > $rsLate->updated_at) {
                        return array("id" => $rsMain->id, "finalObj" => "regular", "version" => 0);
                    } else {
                        return array("id" => $rsLate->id, "finalObj" =>  "late_submission", "version" => $rsLate->version);
                    }
                } else {
                    return array("id" => $rsLate->id, "finalObj" =>  "late_submission", "version" => $rsLate->version);
                }
            }
        } else {
            if (!empty($rsWait) && !empty($rsMain)) {
                if ($rsWait->updated_at > $rsMain->updated_at) {
                    return array("id" => $rsWait->id, "finalObj" => "waitlist", "version" => $rsWait->version);
                } else {
                    return array("id" => $rsMain->id, "finalObj" => "regular", "version" => 0);
                }
            } elseif (!empty($rsWait)) {
                return array("id" => $rsWait->id, "finalObj" => "waitlist", "version" => $rsWait->version);
            } elseif (!empty($rsMain)) {
                return array("id" => $rsMain->id, "finalObj" => "regular", "version" => 0);
            }
        }
        return array("id" => 0, "finalObj" => "", "version" => 0);
    }
}