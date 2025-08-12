<?php

namespace App\Modules\Reports\Controllers;

use App\Modules\School\Models\School;
use App\Modules\District\Models\District;
use App\Modules\DistrictConfiguration\Models\DistrictConfiguration;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\School\Models\Grade;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\Application\Models\ApplicationProgram;
use App\Modules\Application\Models\Application;
use App\Modules\Program\Models\Program;
use App\Modules\Submissions\Models\{Submissions, SubmissionGrade, SubmissionConductDisciplinaryInfo, SubmissionsFinalStatus, SubmissionsWaitlistFinalStatus, SubmissionsStatusUniqueLog, LateSubmissionFinalStatus};
use App\Modules\Waitlist\Models\{WaitlistProcessLogs, WaitlistAvailabilityLog, WaitlistAvailabilityProcessLog, WaitlistIndividualAvailability};
use App\Modules\LateSubmission\Models\{LateSubmissionProcessLogs, LateSubmissionAvailabilityLog, LateSubmissionAvailabilityProcessLog, LateSubmissionIndividualAvailability};
use Maatwebsite\Excel\Facades\Excel;
use App\Modules\Reports\Export\{SubmissionExport, MissingGradesExport, MissingCDIExport, GradeImport, CDIImport};
use App\Modules\Eligibility\Models\SubjectManagement;
use App\Modules\SetAvailability\Models\{Availability, WaitlistAvailability, LateSubmissionAvailability};
use Maatwebsite\Excel\HeadingRowImport;
use App\Traits\AuditTrail;
use App\Modules\ProcessSelection\Models\ProcessSelection;
use App\Modules\Configuration\Models\Configuration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ReportsController extends Controller
{
    use AuditTrail;

    public $eligibility_grade_pass = array();

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function waitlist_index($grade = 0)
    {
        $settings = DB::table("reports_hide_option")->first();
        $availabilityArray = array();
        $allProgram = Availability::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->get(['program_id']);
        foreach ($allProgram as $key => $value) {
            $avail_grade = Availability::where("district_id", Session::get("district_id"))->where("enrollment_id", Session::get("enrollment_id"))->where("program_id", $value->program_id)->get();
            foreach ($avail_grade as $gkey => $gvalue) {
                $availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats;
            }
        }

        /* Get Next Grade Unique for Tabbing */
        $grade_data = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where('next_grade', '<>', '')->orderBy('next_grade', 'DESC')->get(["next_grade"]);
        $gradeArr = array("K", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12");
        $fgradeTab = [];
        foreach ($grade_data as $key => $value) {
            $fgradeTab[] = $value->next_grade;
        }
        $gradeTab = [];
        foreach ($gradeArr as $key => $value) {
            if (in_array($value, $fgradeTab))
                $gradeTab[] = $value;
        }

        if ($grade == 0)
            $existGrade = $gradeTab[0];
        else
            $existGrade = $grade;

        $rs = WaitlistProcessLogs::where("last_date_online", ">", date("Y-m-d H:i:s"))->first();
        if (!empty($rs)) {
            $version = $rs->version;
        } else {
            $version = 0;
        }


        $firstData = Submissions::where("enrollment_id", Session::get("enrollment_id"))->distinct()->get(["first_choice"]);

        /* Get Subject and Acardemic Term like Q1.1 Q1.2 etc set for Academic Grade Calculation 
                For all unique First Choice and Second Choice
         */
        $subjects = $terms = array();
        $eligibilityArr = array();
        foreach ($firstData as $value) {
            if ($value->first_choice != "") {
                $eligibilityData = getEligibilitiesDynamic($value->first_choice, 'Academic Grade Calculation');

                if (count($eligibilityData) > 0) {
                    if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                        $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                        // echo $eligibilityData[0]->id;exit;
                        $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);



                        if (!empty($content)) {
                            if ($content->scoring->type == "DD") {
                                $tmp = array();

                                foreach ($content->subjects as $value) {
                                    if (!in_array($value, $subjects)) {
                                        $subjects[] = $value;
                                    }
                                }

                                foreach ($content->terms_calc as $value) {
                                    if (!in_array($value, $terms)) {
                                        $terms[] = $value;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $secondData = Submissions::where("enrollment_id", Session::get("enrollment_id"))->distinct()->get(["second_choice"]);
        foreach ($secondData as $value) {
            if ($value->second_choice != "") {
                $eligibilityData = getEligibilitiesDynamic($value->second_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);
                    if (!empty($content)) {
                        if ($content->scoring->type == "DD") {
                            $tmp = array();

                            foreach ($content->subjects as $value) {
                                if (!in_array($value, $subjects)) {
                                    $subjects[] = $value;
                                }
                            }

                            foreach ($content->terms_calc as $value) {
                                if (!in_array($value, $terms)) {
                                    $terms[] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }

        /* Get Set Eligibility Data Set for first choice program and second choice program
         */

        $setEligibilityData = array();
        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, array_keys($setEligibilityData))) {
                $data = getSetEligibilityData($value->first_choice, 3);
                foreach ($subjects as $svalue) {
                    foreach ($terms as $tvalue) {
                        if (isset($data->{$svalue . "-" . $tvalue})) {
                            $setEligibilityData[$value->first_choice][$svalue . "-" . $tvalue] = $data->{$svalue . "-" . $tvalue}[0];
                        }
                        /*                        else
                            $setEligibilityData[$value->first_choice][$svalue."-".$tvalue] = 50;*/
                    }
                }
            }
        }
        foreach ($secondData as $value) {
            if (!in_array($value->second_choice, array_keys($setEligibilityData))) {
                $data = getSetEligibilityData($value->second_choice, 3);
                foreach ($subjects as $svalue) {
                    foreach ($terms as $tvalue) {
                        if (isset($data->{$svalue . "-" . $tvalue})) {
                            $setEligibilityData[$value->second_choice][$svalue . "-" . $tvalue] = $data->{$svalue . "-" . $tvalue}[0];
                        }
                        /*   else
                            $setEligibilityData[$value->second_choice][$svalue."-".$tvalue] = 50;*/
                    }
                }
            }
        }


        /* Get CDI Set Eligibility Data Set for first choice program and second choice program
         */

        $setCDIEligibilityData = array();
        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityData($value->first_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->first_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->first_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->first_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->first_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->first_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->first_choice]['susp_days'] = $data->SuspDays[0];
                }
            }
        }
        foreach ($secondData as $value) {
            if (!in_array($value->second_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityData($value->second_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->second_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->second_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->second_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->second_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->second_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->second_choice]['susp_days'] = $data->SuspDays[0];
                }
            }
        }
        /* Get CDI Data */

        if ($version > 0) {
            $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
                ->where("submissions.enrollment_id", Session::get("enrollment_id"))
                ->where('next_grade', $existGrade)
                ->where('submission_status', '<>', 'Application Withdrawn')
                ->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->where("submissions_waitlist_final_status.version", $version)->select("submissions.*", "submissions_waitlist_final_status.first_offer_status", "submissions_waitlist_final_status.second_offer_status") // Pending Code here 
                //            ->limit(5)
                ->get();
        } else {
            $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
                ->where("submissions.enrollment_id", Session::get("enrollment_id"))
                ->where('next_grade', $existGrade)
                ->whereIn('submission_status', array("Waitlisted", "Declined / Waitlist for other"))
                ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->select("submissions.*", "submissions_final_status.first_offer_status", "submissions_final_status.second_offer_status")
                ->get();
        }

        $firstdata = $seconddata = array();
        $programGrades = array();
        foreach ($submissions as $key => $value) {
            $failed = false;
            if (!isset($programGrades[$value->first_choice_program_id])) {
                $availableGrades = array();
                $eligibilityData = getEligibilitiesByProgram($value->first_choice_program_id, 'Academic Grade Calculation');
                if (isset($eligibilityData[0])) {
                    $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                    $programGrades[$value->first_choice_program_id] = $availableGrades;
                }
            }
            $skip = false;
            if ($value->first_choice_program_id != 0 && !in_array($value->next_grade, $programGrades[$value->first_choice_program_id])) {
                $skip = true;
            }

            if ($value->second_choice_program_id != '' && $value->second_choice_program_id != '0') {
                if (!isset($programGrades[$value->second_choice_program_id])) {
                    $availableGrades = array();
                    $eligibilityData = getEligibilitiesByProgram($value->second_choice_program_id, 'Academic Grade Calculation');
                    if (isset($eligibilityData[0])) {
                        $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                        $programGrades[$value->second_choice_program_id] = $availableGrades;
                    }
                }
                if (!in_array($value->next_grade, $programGrades[$value->second_choice_program_id])) {
                    $skip = true;
                }
            }

            $score = $this->collectionStudentGradeReport($value, $subjects, $terms, $value->next_grade, $skip, $setEligibilityData);
            if (count($score) <= 0) {
                $failed = true;
                $score = array();
                foreach ($subjects as $svalue) {
                    foreach ($terms as $svalue1) {
                        $score[$svalue][$svalue1] = "";
                    }
                }
            }

            if ($skip) {
                $cdiArr = array();
                $cdiArr['b_info'] = "NA";
                $cdiArr['c_info'] = "NA";
                $cdiArr['d_info'] = "NA";
                $cdiArr['e_info'] = "NA";
                $cdiArr['susp'] = "NA";
                $cdiArr['susp_days'] = "NA";
            } else {
                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                if (!empty($cdi_data)) {
                    $cdiArr = array();
                    $cdiArr['b_info'] = $cdi_data->b_info;
                    $cdiArr['c_info'] = $cdi_data->c_info;
                    $cdiArr['d_info'] = $cdi_data->d_info;
                    $cdiArr['e_info'] = $cdi_data->e_info;
                    $cdiArr['susp'] = $cdi_data->susp;
                    $cdiArr['susp_days'] = $cdi_data->susp_days;
                } elseif ($value->cdi_override == "Y") {
                    $cdiArr = array();
                    $cdiArr['b_info'] = 0;
                    $cdiArr['c_info'] = 0;
                    $cdiArr['d_info'] = 0;
                    $cdiArr['e_info'] = 0;
                    $cdiArr['susp'] = 0;
                    $cdiArr['susp_days'] = 0;
                } else {
                    $failed = true;
                    $cdiArr = array();
                    $cdiArr['b_info'] = "";
                    $cdiArr['c_info'] = "";
                    $cdiArr['d_info'] = "";
                    $cdiArr['e_info'] = "";
                    $cdiArr['susp'] = "";
                    $cdiArr['susp_days'] = "";
                }
            }
            if ($value->first_choice != "" && $value->second_choice != "") {


                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice'] = $value->first_choice;
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['second_program'] = "";
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                }
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;
                if (!isset($this->eligibility_grade_pass[$value->id]['first'])) {
                    $tmp['grade_status'] = "NA";
                } else {
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                }
                //echo $value->id." - ".$value->first_offer_status . "  - ".$value->second_offer_status."<BR>";
                if ($value->first_offer_status != "Declined & Waitlisted") {
                    $firstdata[] = $tmp;
                }

                if (!isset($this->eligibility_grade_pass[$value->id]['second'])) {
                    $tmp['grade_status'] = "NA";
                } else {
                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                }
                $tmp['rank'] = $this->priorityCalculate($value, "second");

                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    if ($failed == true) {
                        $tmp['cdi_status'] = "NA";
                    } else
                        $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                }

                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_program'] = "";
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                if ($value->second_offer_status != "Declined & Waitlisted") {
                    $seconddata[] = $tmp;
                }
                //$seconddata[] = $tmp;

            } elseif ($value->first_choice != "") {
                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_program'] = "";
                $tmp['score'] = $score;
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['cdi'] = $cdiArr;
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else
                        if ($failed == true) {
                    $tmp['cdi_status'] = "NA";
                } else
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;
                if (!isset($this->eligibility_grade_pass[$value->id]['first'])) {
                    $tmp['grade_status'] = "NA";
                } else {
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                }
                if ($value->first_offer_status != "Declined & Waitlisted") {
                    $firstdata[] = $tmp;
                }
            } else {
                $tmp = $this->convertToArray($value);
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['first_program'] = "";
                $tmp['score'] = $score;
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['cdi'] = $cdiArr;
                $tmp['rank'] = $this->priorityCalculate($value, "second");
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;
                if (!isset($this->eligibility_grade_pass[$value->id]['second'])) {
                    $tmp['grade_status'] = "NA";
                } else {
                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                }
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    if ($failed == true) {
                        $tmp['cdi_status'] = "NA";
                    } else
                        $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                }

                if ($value->second_offer_status != "Declined & Waitlisted") {
                    $seconddata[] = $tmp;
                }
            }
        }
        //        exit;

        /*
        $fdata = array();
        $count = 0;
        foreach($firstdata as $key=>$value)
        {
            $score = $value['score'];
            $fdata[$count] = $value;
            $failcount = 0;
            foreach($score as $scrkey=>$scrvalue)
            {
                foreach($terms as $termkey=>$termvalue)
                {
                    if(!isset($score[$scrkey]))
                        $failcount++;
                    else
                    {
                       // echo $termkey."--".$value['first_choice']."--".$scrkey."-".$termvalue." -- ".$setEligibilityData[$value['first_choice']][$scrkey."-".$termvalue]."<BR>";
                        if(isset($setEligibilityData[$value['first_choice']][$scrkey."-".$termvalue]))
                        {
                            
                            if($score[$scrkey][$termvalue] >= $setEligibilityData[$value['first_choice']][$scrkey."-".$termvalue])
                              {

                              } 
                            else
                            {
                                 $failcount++;
                            }
                        }
                    }                    
                }
            }
            if($failcount > 0)
                $fdata[$count]['status'] = "Fail";
            else
                $fdata[$count]['status'] = "Pass";
            $count++;

        }*/

        /*echo "<pre>";
        print_r($firstdata);
        print_r($seconddata);
       exit;*/

        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }

        $tmpAvailability = $availabilityArray;
        $waitlistArr = $offeredRank = $firstOffered = array();

        foreach ($firstdata as $key => $value) {

            $rsT = SubmissionsFinalStatus::where("submission_id", $value['id'])->select("first_choice_final_status")->first();
            if (!empty($rsT))
                $status = $rsT->first_choice_final_status;
            else
                $status = "";
            if ($value['grade_status'] == "NA" || $value['cdi_status'] == "NA") {
                $firstdata[$key]['final_status'] = "<div class='alert1 alert-info'>Denied due to Incomplete Records</div>";
            } else {
                if ($status == "Offered")
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                elseif ($status == "Waitlisted")
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                elseif ($status == "Denied due to Ineligibility")
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Eligibility</div>";
            }
        }


        foreach ($seconddata as $key => $value) {

            $rsT = SubmissionsFinalStatus::where("submission_id", $value['id'])->select("second_choice_final_status")->first();
            if (!empty($rsT))
                $status = $rsT->second_choice_final_status;
            else
                $status = "";
            if ($value['grade_status'] == "NA" || $value['cdi_status'] == "NA") {
                $seconddata[$key]['final_status'] = "<div class='alert1 alert-info'>Denied due to Incomplete Records</div>";
            } else {
                if ($status == "Offered")
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                elseif ($status == "Waitlisted")
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                elseif ($status == "Denied due to Ineligibility")
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Eligibility</div>";
                //                else
                //                   echo $value['id']."<BR>";
            }
        }

        /*
echo "<pre>";
print_r($firstdata);
print_r($seconddata);

exit;*/

        if (str_contains(request()->url(), '/export')) {
            return $this->exportSubmissions($firstdata, $seconddata, $subjects, $terms);
        } else {
            return view("Reports::waitlist_index", compact("firstdata", "seconddata", "existGrade", "gradeTab", "subjects", "terms", "setEligibilityData", "setCDIEligibilityData", "settings"));
        }
    }


    public function late_submission_index($grade = 0)
    {
        $rs = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $version = $rs + 1;

        $settings = DB::table("reports_hide_option")->first();

        $grade_data = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->distinct()->where('next_grade', '<>', '')->orderBy('next_grade', 'DESC')->get(["next_grade"]);
        $gradeArr = array("K", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12");
        $fgradeTab = [];
        foreach ($grade_data as $key => $value) {
            $fgradeTab[] = $value->next_grade;
        }
        $gradeTab = [];
        foreach ($gradeArr as $key => $value) {
            if (in_array($value, $fgradeTab))
                $gradeTab[] = $value;
        }

        if ($grade == 0)
            $existGrade = $gradeTab[0];
        else
            $existGrade = $grade;


        $last_type = app('App\Modules\Waitlist\Controllers\WaitlistController')->check_last_process();
        if ($last_type == "regular") {
            $id = 0;
        } else {
            if ($last_type == "late_submission") {
                $rs = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first();
                $id = $rs->version;
            } else {
                $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first();
                $id = $rs->version;
            }
        }
        /*$rs = LateSubmissionProcessLogs::count();
        $version = $rs + 1;

        $rsWt = WaitlistProcessLogs::count();
        if($rsWt > 0)
        {
            $id = WaitlistProcessLogs::orderBy("created_at", "DESC")->first()->version;
        }
        else
        {
            $id = 0;
        }*/

        $availabilityArray = array();
        $parray = $garray = array();
        $allProgram = Availability::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->get(['program_id']);
        foreach ($allProgram as $key => $value) {
            $avail_grade = Availability::where("district_id", Session::get("district_id"))->where("enrollment_id", Session::get("enrollment_id"))->where("program_id", $value->program_id)->get();
            foreach ($avail_grade as $gkey => $gvalue) {
                $offer_count = $this->get_offered_count_programwise($value->program_id, $gvalue);


                $rs = WaitlistAvailability::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();
                if (!empty($rs)) {
                    $garray[] = $gvalue->grade;
                    $parray[] = $value->program_id;
                    $wt_count = $rs->withdrawn_seats;
                } else {
                    $wt_count = 0;
                }

                $rs = LateSubmissionAvailability::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();
                if (!empty($rs)) {
                    $garray[] = $gvalue->grade;
                    $parray[] = $value->program_id;
                    $lt_count = $rs->withdrawn_seats;
                } else {
                    $lt_count = 0;
                }
                $c[$value->program_id][$gvalue->grade] = $gvalue->available_seats + $wt_count + $lt_count - $offer_count;
            }
        }

        //LateSubmissionFinalStatus::where("version", $version)->delete();

        $academic_year = $eligibilityArr = $subjects = $terms =  $calc_type_arr = [];
        $calc_type = "DD";
        $firstData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->get(["first_choice"]);
        foreach ($firstData as $value) {
            if ($value->first_choice != "") {
                //echo "FC".$value->first_choice."<BR>";
                $eligibilityData = getEligibilitiesDynamic($value->first_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                        $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                        // echo $eligibilityData[0]->id;exit;
                        $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                        if (!empty($content)) {
                            if ($content->scoring->type == "GA" || $content->scoring->type == "DD" || $content->scoring->type == "CLSG") {
                                $calc_type = $content->scoring->type;
                                $calc_type_arr[$value->first_choice] = $calc_type;
                                $tmp = array();

                                if (isset($content->academic_year_calc)) {
                                    foreach ($content->academic_year_calc as $svalue) {
                                        if (!in_array($svalue, $academic_year)) {
                                            $academic_year[] = $svalue;
                                        }
                                    }
                                }

                                foreach ($content->subjects as $svalue) {
                                    if (!in_array($svalue, $subjects)) {
                                        $subjects[] = $svalue;
                                    }
                                }

                                foreach ($content->terms_calc as $svalue) {
                                    if (!in_array($svalue, $terms)) {
                                        $terms[] = $svalue;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $secondData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->get(["second_choice"]);
        foreach ($secondData as $value) {
            if ($value->second_choice != "") {
                $eligibilityData = getEligibilitiesDynamic($value->second_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                    if (!empty($content)) {
                        if ($content->scoring->type == "DD" || $content->scoring->type == "CLSG" || $content->scoring->type == "GA") {
                            $calc_type = $content->scoring->type;
                            $calc_type_arr[$value->second_choice] = $calc_type;
                            $tmp = array();

                            if (isset($content->academic_year_calc)) {
                                foreach ($content->academic_year_calc as $svalue) {
                                    if (!in_array($svalue, $academic_year)) {
                                        $academic_year[] = $svalue;
                                    }
                                }
                            }

                            foreach ($content->subjects as $svalue) {
                                if (!in_array($svalue, $subjects)) {
                                    $subjects[] = $svalue;
                                }
                            }

                            foreach ($content->terms_calc as $svalue) {
                                if (!in_array($svalue, $terms)) {
                                    $terms[] = $svalue;
                                }
                            }
                        }
                    }
                }
            }
        }

        /* Get Set Eligibility Data Set for first choice program and second choice program
         */

        $setEligibilityData = array();

        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, array_keys($setEligibilityData))) {

                $data = getSetEligibilityDataDynamic($value->first_choice, 3);

                foreach ($subjects as $svalue) {
                    if (isset($data->{$svalue})) {
                        $setEligibilityData[$value->first_choice][$svalue] = $data->{$svalue}[0];
                    } else {
                        $setEligibilityData[$value->first_choice][$svalue] = 70;
                    }
                }
            }
        }

        foreach ($secondData as $value) {
            if (!in_array($value->second_choice, array_keys($setEligibilityData))) {
                $data = getSetEligibilityDataDynamic($value->second_choice, 3);

                foreach ($subjects as $svalue) {
                    if (isset($data->{$svalue})) {
                        $setEligibilityData[$value->second_choice][$svalue] = $data->{$svalue}[0];
                    } else {
                        $setEligibilityData[$value->second_choice][$svalue] = 70;
                    }
                }
            }
        }
        /* Get CDI Set Eligibility Data Set for first choice program and second choice program
         */

        $setCDIEligibilityData = array();
        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityDataDynamic($value->first_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->first_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->first_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->first_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->first_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->first_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->first_choice]['susp_days'] = $data->SuspDays[0];
                }
            }
        }
        foreach ($secondData as $value) {
            if (!in_array($value->second_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityDataDynamic($value->second_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->second_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->second_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->second_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->second_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->second_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->second_choice]['susp_days'] = $data->SuspDays[0];
                }
            }
        }


        if ($id == 0) {
            $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue) {
                $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
            })->where('next_grade', $existGrade)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->select("submissions.*", "submissions_final_status.first_offer_status", "submissions_final_status.second_offer_status")
                ->get();
        } else {
            if ($last_type == "waitlist") {
                $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue) {
                    $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
                })->where("submissions_waitlist_final_status.version", $id)->where('next_grade', $existGrade)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->select("submissions.*", "submissions_waitlist_final_status.first_offer_status", "submissions_waitlist_final_status.second_offer_status")
                    ->get();
            } else {
                $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue) {
                    $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
                })->where("late_submissions_final_status.version", $id)->where('next_grade', $existGrade)->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->select("submissions.*", "late_submissions_final_status.first_offer_status", "late_submissions_final_status.second_offer_status")
                    ->get();
            }
        }



        $decWtArry = array();
        $firstdata = $seconddata = array();

        foreach ($submissions as $key => $value) {
            $failed = false;
            if (!isset($programGrades[$value->first_choice_program_id])) {
                $availableGrades = array();
                $application_ids = array($value->application_id);
                $eligibilityData = getEligibilitiesByProgramDynamic($value->first_choice_program_id, $application_ids, 'Academic Grade Calculation');

                if (isset($eligibilityData[0])) {
                    $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                    $programGrades[$value->first_choice_program_id] = $availableGrades;
                }
            }
            $skip = false;
            if ($value->first_choice_program_id != 0 && isset($programGrades[$value->first_choice_program_id]) && !in_array($value->next_grade, $programGrades[$value->first_choice_program_id])) {
                $skip = true;
            }

            if ($value->second_choice_program_id != '' && $value->second_choice_program_id != '0') {
                if (!isset($programGrades[$value->second_choice_program_id])) {
                    $availableGrades = array();
                    $application_ids = array($value->application_id);
                    $eligibilityData = getEligibilitiesByProgramDynamic($value->second_choice_program_id,  $application_ids, 'Academic Grade Calculation');
                    if (isset($eligibilityData[0])) {
                        $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                        $programGrades[$value->second_choice_program_id] = $availableGrades;
                    }
                }
                if (isset($programGrades[$value->second_choice_program_id]) && !in_array($value->next_grade, $programGrades[$value->second_choice_program_id])) {
                    $skip = true;
                }
            }

            if (isset($calc_type_arr[$value->first_choice]))
                $ctype = $calc_type_arr[$value->first_choice];
            elseif (isset($calc_type_arr[$value->second_choice]))
                $ctype = $calc_type_arr[$value->second_choice];
            else
                $ctype = "DD";

            $score = $this->collectionStudentGradeReportDynamic($value, $academic_year, $subjects, $terms, $value->next_grade, $skip, $setEligibilityData, $ctype);


            if (count($score) <= 0) {
                $failed = true;
                $score = array();
                foreach ($subjects as $svalue) {
                    foreach ($terms as $svalue1) {
                        $score[$svalue][$svalue1] = "";
                    }
                }
            }

            if ($skip) {
                $cdiArr = array();
                $cdiArr['b_info'] = "NA";
                $cdiArr['c_info'] = "NA";
                $cdiArr['d_info'] = "NA";
                $cdiArr['e_info'] = "NA";
                $cdiArr['susp'] = "NA";
                $cdiArr['susp_days'] = "NA";
            } else {
                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                if (!empty($cdi_data)) {
                    $cdiArr = array();
                    $cdiArr['b_info'] = $cdi_data->b_info;
                    $cdiArr['c_info'] = $cdi_data->c_info;
                    $cdiArr['d_info'] = $cdi_data->d_info;
                    $cdiArr['e_info'] = $cdi_data->e_info;
                    $cdiArr['susp'] = $cdi_data->susp;
                    $cdiArr['susp_days'] = $cdi_data->susp_days;
                } elseif ($value->cdi_override == "Y") {
                    $cdiArr = array();
                    $cdiArr['b_info'] = 0;
                    $cdiArr['c_info'] = 0;
                    $cdiArr['d_info'] = 0;
                    $cdiArr['e_info'] = 0;
                    $cdiArr['susp'] = 0;
                    $cdiArr['susp_days'] = 0;
                } else {
                    $failed = true;
                    $cdiArr = array();
                    $cdiArr['b_info'] = "";
                    $cdiArr['c_info'] = "";
                    $cdiArr['d_info'] = "";
                    $cdiArr['e_info'] = "";
                    $cdiArr['susp'] = "";
                    $cdiArr['susp_days'] = "";
                }
            }
            if ($value->first_choice != "" && $value->second_choice != "") {

                $tmp = $this->convertToArray($value);
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                }

                $choice = getApplicationProgramName($value->first_choice);
                $tmp['late_submission'] = "No";
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice'] = $value->first_choice;
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;

                $tmp['second_program'] = "";
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                    $tmp['grade_status'] = "Pass";
                } else {
                    $tmp['grade_status'] = "Fail";
                }
                if ($value->first_offer_status != "Declined & Waitlisted") {
                    $firstdata[] = $tmp;
                }
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                }


                $tmp['rank'] = $this->priorityCalculate($value, "second");
                if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                    $tmp['grade_status'] = "Pass";
                } else {
                    $tmp['grade_status'] = "Fail";
                }
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_program'] = "";
                if ($value->second_offer_status != "Declined & Waitlisted") {
                    $seconddata[] = $tmp;
                }
            } elseif ($value->first_choice != "") {
                $tmp = $this->convertToArray($value);
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['late_submission'] = "No";
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_program'] = "";
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                    $tmp['grade_status'] = "Pass";
                } else {
                    $tmp['grade_status'] = "Fail";
                }
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                }
                if ($value->first_offer_status != "Declined & Waitlisted") {
                    $firstdata[] = $tmp;
                }
            } else {
                $tmp = $this->convertToArray($value);
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['late_submission'] = "No";
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['first_program'] = "";
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;
                $tmp['rank'] = $this->priorityCalculate($value, "second");
                if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                    $tmp['grade_status'] = "Pass";
                } else {
                    $tmp['grade_status'] = "Fail";
                }
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                }
                if ($value->second_offer_status != "Declined & Waitlisted") {
                    $seconddata[] = $tmp;
                }
            }
        }

        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }

        if (count($firstdata) > 0) {
            $final_first_data = $firstdata;
        } else {
            $final_first_data = array();
        }
        if (count($seconddata) > 0) {
            $final_second_data = $seconddata;
        } else {
            $final_second_data = array();
        }





        /* Code for Late Submissions */
        $firstdata = $seconddata = array();

        // $subjects = $terms = array();
        $eligibilityArr = array();


        $calc_type = "DD";


        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where("late_submission", "Y")->where('next_grade', $existGrade)->select("submissions.*")->where("next_grade", $existGrade)->whereIn('submission_status', array('Active', 'Pending'))
            ->get();

        $firstdata = $seconddata = array();
        $programGrades = array();
        foreach ($submissions as $key => $value) {
            if (!isset($programGrades[$value->first_choice_program_id])) {
                $availableGrades = array();
                $application_ids = array($value->application_id);
                $eligibilityData = getEligibilitiesByProgramDynamic($value->first_choice_program_id, $application_ids, 'Academic Grade Calculation');
                if (isset($eligibilityData[0])) {
                    $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                    $programGrades[$value->first_choice_program_id] = $availableGrades;
                }
            }
            $skip = false;
            if ($value->first_choice_program_id != 0 && !in_array($value->next_grade, $programGrades[$value->first_choice_program_id])) {
                $skip = true;
            }

            if ($value->second_choice_program_id != '' && $value->second_choice_program_id != '0') {
                if (!isset($programGrades[$value->second_choice_program_id])) {
                    $availableGrades = array();
                    $application_ids = array($value->application_id);
                    $eligibilityData = getEligibilitiesByProgramDynamic($value->second_choice_program_id, $application_ids, 'Academic Grade Calculation');

                    if (isset($eligibilityData[0])) {
                        $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                        $programGrades[$value->second_choice_program_id] = $availableGrades;
                    }
                }
                if (!in_array($value->next_grade, $programGrades[$value->second_choice_program_id])) {
                    $skip = true;
                }
            }

            if (isset($calc_type_arr[$value->first_choice]))
                $ctype = $calc_type_arr[$value->first_choice];
            elseif (isset($calc_type_arr[$value->second_choice]))
                $ctype = $calc_type_arr[$value->second_choice];
            else
                $ctype = "DD";

            $score = $this->collectionStudentGradeReportDynamic($value, $academic_year, $subjects, $terms, $value->next_grade, $skip, $setEligibilityData, $ctype);

            if (count($score) <= 0) {
                $failed = true;
                $score = array();
                foreach ($subjects as $svalue) {
                    foreach ($terms as $svalue1) {
                        $score[$svalue][$svalue1] = "";
                    }
                }
            }

            if ($skip) {
                $cdiArr = array();
                $cdiArr['b_info'] = "NA";
                $cdiArr['c_info'] = "NA";
                $cdiArr['d_info'] = "NA";
                $cdiArr['e_info'] = "NA";
                $cdiArr['susp'] = "NA";
                $cdiArr['susp_days'] = "NA";
            } else {
                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                if (!empty($cdi_data)) {
                    $cdiArr = array();
                    $cdiArr['b_info'] = $cdi_data->b_info;
                    $cdiArr['c_info'] = $cdi_data->c_info;
                    $cdiArr['d_info'] = $cdi_data->d_info;
                    $cdiArr['e_info'] = $cdi_data->e_info;
                    $cdiArr['susp'] = $cdi_data->susp;
                    $cdiArr['susp_days'] = $cdi_data->susp_days;
                } elseif ($value->cdi_override == "Y") {
                    $cdiArr = array();
                    $cdiArr['b_info'] = 0;
                    $cdiArr['c_info'] = 0;
                    $cdiArr['d_info'] = 0;
                    $cdiArr['e_info'] = 0;
                    $cdiArr['susp'] = 0;
                    $cdiArr['susp_days'] = 0;
                } else {
                    $failed = true;
                    $cdiArr = array();
                    $cdiArr['b_info'] = "";
                    $cdiArr['c_info'] = "";
                    $cdiArr['d_info'] = "";
                    $cdiArr['e_info'] = "";
                    $cdiArr['susp'] = "";
                    $cdiArr['susp_days'] = "";
                }
            }
            if ($value->first_choice != "" && $value->second_choice != "") {

                $tmp = $this->convertToArray($value);
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                }

                $choice = getApplicationProgramName($value->first_choice);
                $tmp['late_submission'] = "Yes";
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice'] = $value->first_choice;
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;

                $tmp['second_program'] = "";
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                if (isset($this->eligibility_grade_pass[$value->id]['first']) && $this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                    $tmp['grade_status'] = "Pass";
                } else {
                    $tmp['grade_status'] = "Fail";
                }
                $firstdata[] = $tmp;
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                }


                $tmp['rank'] = $this->priorityCalculate($value, "second");
                if (isset($this->eligibility_grade_pass[$value->id]['second']) && $this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                    $tmp['grade_status'] = "Pass";
                } else {
                    $tmp['grade_status'] = "Fail";
                }
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_program'] = "";
                $seconddata[] = $tmp;
            } elseif ($value->first_choice != "") {
                $tmp = $this->convertToArray($value);
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['late_submission'] = "Yes";
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_program'] = "";
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                if (isset($this->eligibility_grade_pass[$value->id]['first']) && $this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                    $tmp['grade_status'] = "Pass";
                } else {
                    $tmp['grade_status'] = "Fail";
                }
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                }
                $firstdata[] = $tmp;
            } else {
                $tmp = $this->convertToArray($value);
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['late_submission'] = "Yes";
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['first_program'] = "";
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;
                $tmp['rank'] = $this->priorityCalculate($value, "second");
                if (isset($this->eligibility_grade_pass[$value->id]['second']) && $this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                    $tmp['grade_status'] = "Pass";
                } else {
                    $tmp['grade_status'] = "Fail";
                }
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                }
                $seconddata[] = $tmp;
            }
        }

        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }
        $final_first_data = array_merge($final_first_data, $firstdata);
        $final_second_data = array_merge($final_second_data, $seconddata);

        $firstdata = $final_first_data;
        $seconddata = $final_second_data;

        if (str_contains(request()->url(), '/export')) {
            return $this->exportSubmissions($firstdata, $seconddata, $subjects, $terms);
        } else {
            return view("Reports::late_submission_index", compact("firstdata", "seconddata", "existGrade", "gradeTab", "subjects", "terms", "setEligibilityData", "setCDIEligibilityData", "settings", "academic_year"));
        }
    }

    public function processingLogsReport()
    {
        $enrollment_id = Session::get('enrollment_id');
        $process_selecton = ProcessSelection::where('enrollment_id', $enrollment_id)->orderBy("created_at", "desc")->get();
        $versions_lists = WaitlistProcessLogs::where('enrollment_id', $enrollment_id)->orderBy("created_at", "desc")->get();
        $late_lists = LateSubmissionProcessLogs::where('enrollment_id', $enrollment_id)->orderBy("created_at", "desc")->get();
        $date = DistrictConfiguration::where("name", "last_date_online_acceptance")->where("enrollment_id", Session::get("enrollment_id"))->select("value")->first();
        $msg = "";
        $last_online_date = (isset($date) ? date("Y-m-d H:i:s", strtotime($date->value)) : "");

        return view("Reports::log_index", compact("versions_lists", "process_selecton", "late_lists", "last_online_date"));
    }

    public function processingRealLogsReport()
    {
        $process_selecton = ProcessSelection::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "desc")->first();
        $versions_lists = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "desc")->first();
        $late_lists = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "desc")->first();

        if (!empty($late_lists)) {
            return redirect(url('/admin/LateSubmission/SeatsStatus/Version/' . $late_lists->version));
        } elseif (!empty($versions_lists)) {
            return redirect(url('/admin/Waitlist/SeatsStatus/Version/' . $versions_lists->version));
        } else {
            return redirect(url('/admin/Reports/missing/' . $process_selecton->enrollment_id . '/seatstatus'));
        }
        //return view("Reports::real_log_index",compact("versions_lists", "process_selecton", "late_lists"));
    }

    public function generateWaitlistStatus()
    {
        $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $actial_version = $rs + 1;

        $last_type = app('App\Modules\Waitlist\Controllers\WaitlistController')->check_last_process();
        $table = "submissions_final_status";


        $version = 0;
        if ($last_type == "waitlist") {
            $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first();
            $version = $rs->version;
            $table = "submissions_waitlist_final_status";
        } elseif ($last_type == "late_submission") {
            $rs = LateSubmissionProcessLogs::orderBy("created_at", "DESC")->first();
            $version = $rs->version;
            $table = "late_submissions_final_status";
        } else {
            $version = 0;
            //$table_name = "first_choice_final_status";
        }


        $availabilityArray = array();
        $allProgram = Availability::distinct()->where("district_id", Session::get("district_id"))->where("enrollment_id", Session::get("enrollment_id"))->get(['program_id']);
        foreach ($allProgram as $key => $value) {
            $avail_grade = Availability::where("district_id", Session::get("district_id"))->where("program_id", $value->program_id)->get();
            foreach ($avail_grade as $gkey => $gvalue) {
                //echo $value->id . " - " .$gvalue->grade."<BR>";
                $offer_count = Submissions::where('district_id', Session::get("district_id"))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where("submission_status", "Offered and Accepted")->where("awarded_school", getProgramName($value->program_id))->where("next_grade", $gvalue->grade)->count();




                $rs = LateSubmissionAvailabilityLog::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->sum('withdrawn_seats');
                $lt_count = $rs;

                $rs = WaitlistAvailabilityLog::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->sum('withdrawn_seats');
                $wt_count = $rs;


                $rs = WaitlistAvailability::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();

                if (!empty($rs)) {
                    $availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats + $wt_count + $lt_count + $rs->withdrawn_seats - $offer_count;
                } else {
                    $availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats + $wt_count + $lt_count - $offer_count;
                }
            }
        }


        $rsD = SubmissionsWaitlistFinalStatus::where("version", $actial_version)->delete();
        /*$submissions=Submissions::
            where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue){
                                $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
                            })->join($table, $table.".submission_id", "submissions.id")->select("submissions.*", $table.".first_offer_status", $table.".second_offer_status")
            ->get();
            */
        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue) {
            $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
        })->select("submissions.*")
            ->get();

        $decWtArry = array();
        foreach ($submissions as $key => $value) {

            $d = WaitlistAvailabilityProcessLog::where("program_id", $value->first_choice_program_id)->where("grade", $value->next_grade)->orderBy("created_at", "DESC")->first();
            if (!empty($d)) {
                if ($d->type == "Late Submission") {
                    $offer_data = LateSubmissionFinalStatus::where("version", $d->version)->where("submission_id", $value->id)->first();
                } elseif ($d->type == "Waitlist") {
                    $offer_data = SubmissionsWaitlistFinalStatus::where("version", $d->version)->where("submission_id", $value->id)->first();
                } else {
                    $offer_data = SubmissionsFinalStatus::where("submission_id", $value->id)->first();
                }
            } else {
                $offer_data = $value;
            }
            if (empty($offer_data))
                $offer_data = $value;



            if ($value->second_choice != '') {


                $d = WaitlistAvailabilityProcessLog::where("program_id", $value->second_choice_program_id)->where("grade", $value->next_grade)->orderBy("created_at", "DESC")->first();
                if (!empty($d)) {
                    if ($d->type == "Late Submission") {
                        $offer_data_2 = LateSubmissionFinalStatus::where("version", $d->version)->where("submission_id", $value->id)->first();
                    } elseif ($d->type == "Waitlist") {
                        $offer_data_2 = SubmissionsWaitlistFinalStatus::where("version", $d->version)->where("submission_id", $value->id)->first();
                    } else {
                        $offer_data_2 = SubmissionsFinalStatus::where("submission_id", $value->id)->first();
                    }
                } else {
                    $offer_data_2 = $value;
                }
            } else {
                $offer_data_2 = $value;
            }


            if ($value->first_choice != "" && $value->second_choice != "") {

                if (empty($offer_data_2))
                    $offer_data_2 = $value;


                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice'] = $value->first_choice;
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['second_program'] = "";
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                if ($offer_data->first_offer_status != "Declined & Waitlisted") {
                    $firstdata[] = $tmp;
                }

                $tmp['rank'] = $this->priorityCalculate($value, "second");
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_program'] = "";
                if ($offer_data_2->second_offer_status != "Declined & Waitlisted") {
                    $seconddata[] = $tmp;
                }
            } elseif ($value->first_choice != "") {
                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_program'] = "";
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                if ($offer_data->first_offer_status != "Declined & Waitlisted") {
                    $firstdata[] = $tmp;
                }
            } else {
                if (empty($offer_data_2))
                    $offer_data_2 = $value;
                $tmp = $this->convertToArray($value);
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['first_program'] = "";
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['rank'] = $this->priorityCalculate($value, "second");
                if ($offer_data_2->second_offer_status != "Declined & Waitlisted") {
                    $seconddata[] = $tmp;
                }
            }
        }

        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {

            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }
        $tmpAvailability = $availabilityArray;
        $waitlistArr = $offeredRank = $firstOffered = array();
        foreach ($firstdata as $key => $value) {
            if (isset($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']])) {
                if ($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] > 0) {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                    $firstOffered[] = $value['id'];
                    if (isset($offeredRank[$value['first_choice_program_id']])) {
                        $offeredRank[$value['first_choice_program_id']] = $offeredRank[$value['first_choice_program_id']] + 1;
                    } else {
                        $offeredRank[$value['first_choice_program_id']] = 1;
                    }

                    $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] - 1;
                    do {
                        $code = mt_rand(100000, 999999);
                        $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                        $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                        $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                    } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                    $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Offered", "first_offered_rank" => $offeredRank[$value['first_choice_program_id']], "first_waitlist_for" => $value['first_choice_program_id'], 'offer_slug' => $code, "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")]);
                } else {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['first_choice_program_id']])) {
                        $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['first_choice_program_id']] = 1;
                    }

                    $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                if (isset($waitlistArr[$value['first_choice_program_id']])) {
                    $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                } else {
                    $waitlistArr[$value['first_choice_program_id']] = 1;
                }
                $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        foreach ($seconddata as $key => $value) {

            if (isset($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']])) {
                if ($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] > 0 && !in_array($value['id'], $firstOffered)) {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                    $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] - 1;

                    if (isset($offeredRank[$value['second_choice_program_id']])) {
                        $offeredRank[$value['second_choice_program_id']] = $offeredRank[$value['second_choice_program_id']] + 1;
                    } else {
                        $offeredRank[$value['second_choice_program_id']] = 1;
                    }
                    do {
                        $code = mt_rand(100000, 999999);
                        $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                        $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                        $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                    } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                    $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Offered", "second_offered_rank" => $offeredRank[$value['second_choice_program_id']], "second_waitlist_for" => $value['second_choice_program_id'], 'offer_slug' => $code, "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")]);
                } else {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['second_choice_program_id']])) {
                        $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['second_choice_program_id']] = 1;
                    }

                    $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                if (isset($waitlistArr[$value['second_choice_program_id']])) {
                    $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                } else {
                    $waitlistArr[$value['second_choice_program_id']] = 1;
                }
                $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $actial_version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        $rsUpdate = SubmissionsWaitlistFinalStatus::where("version", $actial_version)->where("enrollment_id", Session::get("enrollment_id"))->where("first_choice_final_status", "Offered")->where("second_choice_final_status", "Waitlisted")->get();
        foreach ($rsUpdate as $ukey => $uvalue) {
            $rs = SubmissionsWaitlistFinalStatus::where("version", $actial_version)->where("submission_id", $uvalue->submission_id)->where("first_choice_final_status", "Offered")->update(["second_choice_final_status" => "Pending"]);
        }
        echo "Done";
    }

    /* Late Submission Individual Submissions */
    public function generateLateSubmissionIndividualStatus()
    {
        $rs = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $version = $rs + 1;

        $from = "wait";

        if ($rs > 0) {
            $id = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first()->version;
            $from = "late";
        } else {

            $rsWt = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
            if ($rsWt > 0) {
                $id = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first()->version;
                $from = "wait";
            } else {
                $id = "regular";
            }
        }

        $availabilityArray = array();
        $parray = $garray = array();

        $rs = LateSubmissionAvailability::get();
        $tprogram_id = [];
        foreach ($rs as $k => $v) {
            $tprogram_id[] = $v->program_id . "-" . $v->grade;
        }
        $allProgram = Availability::distinct()->where("district_id", Session::get("district_id"))->where("enrollment_id", Session::get("enrollment_id"))->get(['program_id']);
        foreach ($allProgram as $key => $value) {

            $avail_grade = Availability::where("district_id", Session::get("district_id"))->where("program_id", $value->program_id)->get();
            foreach ($avail_grade as $gkey => $gvalue) {
                if (in_array($value->program_id . "-" . $gvalue->grade, $tprogram_id)) {
                    $offer_count = app('App\Modules\Waitlist\Controllers\WaitlistController')->get_offer_count($value->program_id, $gvalue->grade, Session::get("district_id"), 1);


                    $rs = WaitlistAvailability::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();
                    if (!empty($rs)) {
                        $garray[] = $gvalue->grade;
                        $parray[] = $value->program_id;
                        $wt_count = $rs->withdrawn_seats;
                        //$availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats + $rs->withdrawn_seats - $offer_count;
                    } else {
                        $wt_count = 0;
                        //$availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats - $offer_count;
                    }

                    $rs = LateSubmissionAvailability::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();
                    if (!empty($rs)) {
                        $garray[] = $gvalue->grade;
                        $parray[] = $value->program_id;
                        $lt_count = $rs->withdrawn_seats;
                    } else {
                        $lt_count = 0;
                    }
                    $availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats + $wt_count + $lt_count - $offer_count;
                }
            }
        }



        LateSubmissionFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->delete();

        if ($from  == "regular") {
            $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue) {
                $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
            })->where(function ($q) use ($parray) {
                $q->whereIn("first_choice_program_id", $parray)->orWhereIn("second_choice_program_id", $parray);
            })->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->select("submissions.*", "submissions_final_status.first_offer_status", "submissions_final_status.second_offer_status")
                ->get();
        } elseif ($from == "wait") {
            $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue) {
                $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
            })->where(function ($q) use ($parray) {
                $q->whereIn("first_choice_program_id", $parray)->orWhereIn("second_choice_program_id", $parray);
            })->where("submissions_waitlist_final_status.version", $id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->select("submissions.*", "submissions_waitlist_final_status.first_offer_status", "submissions_waitlist_final_status.second_offer_status")
                ->get();
        } elseif ($from == "late") {
            $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue) {
                $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
            })->where(function ($q) use ($parray) {
                $q->whereIn("first_choice_program_id", $parray)->orWhereIn("second_choice_program_id", $parray);
            })->where("late_submissions_final_status.version", $id)->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->select("submissions.*", "late_submissions_final_status.first_offer_status", "late_submissions_final_status.second_offer_status")
                ->get();
        }


        $decWtArry = array();


        foreach ($submissions as $key => $value) {
            if (in_array($value->next_grade, $garray)) {
                if ($value->first_choice != "" && $value->second_choice != "") {

                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['second_program'] = "";
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($value->first_offer_status != "Declined & Waitlisted") {
                        $firstdata[] = $tmp;
                    }

                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_program'] = "";
                    if ($value->second_offer_status != "Declined & Waitlisted") {
                        $seconddata[] = $tmp;
                    }
                } elseif ($value->first_choice != "") {
                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_program'] = "";
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($value->first_offer_status != "Declined & Waitlisted") {
                        $firstdata[] = $tmp;
                    }
                } else {
                    $tmp = $this->convertToArray($value);
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['first_program'] = "";
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    if ($value->second_offer_status != "Declined & Waitlisted") {
                        $seconddata[] = $tmp;
                    }
                }
            }
        }

        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }

        $application_ids = [];
        $rsAppData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("late_submission", "Y")->get(["application_id"]);
        foreach ($rsAppData as $rkey => $rvalue) {
            $application_ids[] = $rvalue->application_id;
        }

        $tmpAvailability = $availabilityArray;
        $waitlistArr = $offeredRank = $firstOffered = array();

        if (!empty($firstdata)) {
            foreach ($firstdata as $key => $value) {
                if (isset($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']])) {

                    if ($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] > 0 && in_array($value['first_choice_program_id'], $parray)) {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $firstOffered[] = $value['id'];
                        if (isset($offeredRank[$value['first_choice_program_id']])) {
                            $offeredRank[$value['first_choice_program_id']] = $offeredRank[$value['first_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['first_choice_program_id']] = 1;
                        }

                        $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] - 1;
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));
                        $rs = LateSubmissionFinalStatus::updateOrCreate(["enrollment_id" => Session::get("enrollment_id"), "submission_id" => $value['id'], "version" => $version], ["first_choice_final_status" => "Offered", "first_offered_rank" => $offeredRank[$value['first_choice_program_id']], "first_waitlist_for" => $value['first_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    } else {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['first_choice_program_id']])) {
                            $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['first_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "enrollment_id" => Session::get("enrollment_id"), "version" => $version], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['first_choice_program_id']])) {
                        $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['first_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            }
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {

                if (isset($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']])) {
                    if ($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] > 0 && !in_array($value['id'], $firstOffered)  && in_array($value['second_choice_program_id'], $parray)) {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] - 1;

                        if (isset($offeredRank[$value['second_choice_program_id']])) {
                            $offeredRank[$value['second_choice_program_id']] = $offeredRank[$value['second_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['second_choice_program_id']] = 1;
                        }
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Offered", "second_offered_rank" => $offeredRank[$value['second_choice_program_id']], "second_waitlist_for" => $value['second_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    } else {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['second_choice_program_id']])) {
                            $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['second_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['second_choice_program_id']])) {
                        $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['second_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            }
        }




        /* Code for Late Submissions */
        $firstdata = $seconddata = array();

        $subjects = $terms = array();
        $eligibilityArr = array();

        $firstData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("late_submission", "Y")->where(function ($q) use ($parray) {
            $q->whereIn("first_choice_program_id", $parray)->orWhereIn("second_choice_program_id", $parray);
        })->whereIn("next_grade", $garray)->whereIn('submission_status', array('Active'))->get(["first_choice", "second_choice"]);

        // $secondData = Submissions::distinct()->where("late_submission", "Y")->whereIn("second_choice_program_id", $parray)->whereIn("next_grade", $garray)->get(["first_choice"]);

        $calc_type = "DD";
        $academic_year = $calc_type_arr = [];

        foreach ($firstData as $value) {
            if ($value->first_choice != "") {
                //echo "FC".$value->first_choice."<BR>";
                $eligibilityData = getEligibilitiesDynamic($value->first_choice, 'Academic Grade Calculation');

                //dd($value->first_choice, $eligibilityData);
                if (count($eligibilityData) > 0) {
                    if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                        $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                        // echo $eligibilityData[0]->id;exit;
                        $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                        if (!empty($content)) {
                            if ($content->scoring->type == "GA" || $content->scoring->type == "DD" || $content->scoring->type == "CLSG") {
                                $calc_type = $content->scoring->type;
                                $calc_type_arr[$value->first_choice] = $calc_type;
                                $tmp = array();

                                foreach ($content->academic_year_calc as $svalue) {
                                    if (!in_array($svalue, $academic_year)) {
                                        $academic_year[] = $svalue;
                                    }
                                }

                                foreach ($content->subjects as $svalue) {
                                    if (!in_array($svalue, $subjects)) {
                                        $subjects[] = $svalue;
                                    }
                                }

                                foreach ($content->terms_calc as $svalue) {
                                    if (!in_array($svalue, $terms)) {
                                        $terms[] = $svalue;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($value->second_choice != "") {
                $eligibilityData = getEligibilitiesDynamic($value->second_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                    if (!empty($content)) {
                        if ($content->scoring->type == "DD" || $content->scoring->type == "CLSG" || $content->scoring->type == "GA") {
                            $calc_type = $content->scoring->type;
                            $calc_type_arr[$value->second_choice] = $calc_type;

                            $tmp = array();

                            foreach ($content->academic_year_calc as $svalue) {
                                if (!in_array($svalue, $academic_year)) {
                                    $academic_year[] = $svalue;
                                }
                            }

                            foreach ($content->subjects as $svalue) {
                                if (!in_array($svalue, $subjects)) {
                                    $subjects[] = $svalue;
                                }
                            }

                            foreach ($content->terms_calc as $svalue) {
                                if (!in_array($svalue, $terms)) {
                                    $terms[] = $svalue;
                                }
                            }
                        }
                    }
                }
            }
        }

        //exit;
        //print_r($parray);exit;

        /* Get Set Eligibility Data Set for first choice program and second choice program
         */

        $setEligibilityData = array();

        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, array_keys($setEligibilityData))) {

                $data = getSetEligibilityDataDynamic($value->first_choice, 3);

                foreach ($subjects as $svalue) {
                    if (isset($data->{$svalue})) {
                        $setEligibilityData[$value->first_choice][$svalue] = $data->{$svalue}[0];
                    } else {
                        $setEligibilityData[$value->first_choice][$svalue] = 70;
                    }
                }
            }


            if (!in_array($value->second_choice, array_keys($setEligibilityData))) {
                $data = getSetEligibilityDataDynamic($value->second_choice, 3);

                foreach ($subjects as $svalue) {
                    if (isset($data->{$svalue})) {
                        $setEligibilityData[$value->second_choice][$svalue] = $data->{$svalue}[0];
                    } else {
                        $setEligibilityData[$value->second_choice][$svalue] = 70;
                    }
                }
            }
        }

        /* Get CDI Set Eligibility Data Set for first choice program and second choice program
         */

        $setCDIEligibilityData = array();
        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityDataDynamic($value->first_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->first_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->first_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->first_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->first_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->first_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->first_choice]['susp_days'] = $data->SuspDays[0];
                } else {
                    $setCDIEligibilityData[$value->first_choice]['b_info'] = 5;
                    $setCDIEligibilityData[$value->first_choice]['c_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['d_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['e_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['susp'] = 2;
                    $setCDIEligibilityData[$value->first_choice]['susp_days'] = 4;
                }
            }

            if (!in_array($value->second_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityDataDynamic($value->second_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->second_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->second_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->second_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->second_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->second_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->second_choice]['susp_days'] = $data->SuspDays[0];
                } else {
                    $setCDIEligibilityData[$value->second_choice]['b_info'] = 5;
                    $setCDIEligibilityData[$value->second_choice]['c_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['d_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['e_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['susp'] = 2;
                    $setCDIEligibilityData[$value->second_choice]['susp_days'] = 4;
                }
            }
        }
        /* Get CDI Data */

        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
            ->where("submissions.enrollment_id", Session::get("enrollment_id"))
            ->where("late_submission", "Y")
            ->where(function ($q) use ($parray) {
                $q->whereIn("first_choice_program_id", $parray)->orWhereIn("second_choice_program_id", $parray);
            })
            ->whereIn("next_grade", $garray)
            ->whereIn('submission_status', array('Active', 'Pending'))
            // ->limit(5)
            ->get();

        $firstdata = $seconddata = array();
        $programGrades = array();

        foreach ($submissions as $key => $value) {
            if (!isset($programGrades[$value->first_choice_program_id])) {
                $availableGrades = array();
                $eligibilityData = getEligibilitiesByProgramDynamic($value->first_choice_program_id, $application_ids, 'Academic Grade Calculation');

                if (isset($eligibilityData[0])) {
                    $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                    $programGrades[$value->first_choice_program_id] = $availableGrades;
                } else {
                    $programGrades[$value->first_choice_program_id] = [];
                }
            }
            $skip = false;
            if ($value->first_choice_program_id != 0 && !in_array($value->next_grade, $programGrades[$value->first_choice_program_id])) {
                $skip = true;
            }

            if ($value->second_choice_program_id != '' && $value->second_choice_program_id != '0') {
                if (!isset($programGrades[$value->second_choice_program_id])) {
                    $availableGrades = array();
                    $eligibilityData = getEligibilitiesByProgramDynamic($value->second_choice_program_id, $application_ids, 'Academic Grade Calculation');
                    if (isset($eligibilityData[0])) {
                        $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                        $programGrades[$value->second_choice_program_id] = $availableGrades;
                    } else {
                        $programGrades[$value->second_choice_program_id] = [];
                    }
                }
                if (!in_array($value->next_grade, $programGrades[$value->second_choice_program_id])) {
                    $skip = true;
                }
            }
            //exit;
            if (isset($calc_type_arr[$value->first_choice]))
                $ctype = $calc_type_arr[$value->first_choice];
            elseif (isset($calc_type_arr[$value->second_choice]))
                $ctype = $calc_type_arr[$value->second_choice];
            else
                $ctype = "DD";

            $score = $this->collectionStudentGradeReportDynamic($value, $academic_year, $subjects, $terms, $value->next_grade, $skip, $setEligibilityData, $calc_type);


            if (count($score) > 0) {

                if ($skip) { //0.1
                    $cdiArr = array();
                    $cdiArr['b_info'] = "NA";
                    $cdiArr['c_info'] = "NA";
                    $cdiArr['d_info'] = "NA";
                    $cdiArr['e_info'] = "NA";
                    $cdiArr['susp'] = "NA";
                    $cdiArr['susp_days'] = "NA";
                } //0.1
                else { //0
                    $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                    if (!empty($cdi_data)) {
                        $cdiArr = array();
                        $cdiArr['b_info'] = $cdi_data->b_info;
                        $cdiArr['c_info'] = $cdi_data->c_info;
                        $cdiArr['d_info'] = $cdi_data->d_info;
                        $cdiArr['e_info'] = $cdi_data->e_info;
                        $cdiArr['susp'] = $cdi_data->susp;
                        $cdiArr['susp_days'] = $cdi_data->susp_days;
                    } elseif ($value->cdi_override == "Y") {
                        $cdiArr = array();
                        $cdiArr['b_info'] = 0;
                        $cdiArr['c_info'] = 0;
                        $cdiArr['d_info'] = 0;
                        $cdiArr['e_info'] = 0;
                        $cdiArr['susp'] = 0;
                        $cdiArr['susp_days'] = 0;
                    } else {
                        $incomplete_reason = "CDI";
                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value->id, "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied Due To Incomplete Records", "first_offered_rank" => 0, "first_waitlist_for" => $value['first_choice_program_id'], "second_choice_final_status" => "Denied Due To Incomplete Records", "second_offered_rank" => 0, "second_waitlist_for" => $value['second_choice_program_id'], 'incomplete_reason' => $incomplete_reason, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                        continue;
                    }
                } //0
                if ($value->first_choice != "" && $value->second_choice != "") { //1

                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['second_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['cdi'] = $cdiArr;
                    if ($value->cdi_override == "Y")
                        $tmp['cdi_status'] = "Pass";
                    else {
                        $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                    }
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $firstdata[] = $tmp;

                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    if ($value->cdi_override == "Y")
                        $tmp['cdi_status'] = "Pass";
                    else
                        $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['cdi'] = $cdiArr;
                    $seconddata[] = $tmp;
                } //1
                elseif ($value->first_choice != "") { //2
                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['cdi'] = $cdiArr;
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($value->cdi_override == "Y")
                        $tmp['cdi_status'] = "Pass";
                    else
                        $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $firstdata[] = $tmp;
                } //2
                else { //3
                    $tmp = $this->convertToArray($value);
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['first_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['cdi'] = $cdiArr;
                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                    $seconddata[] = $tmp;
                } //3
            } else { //4
                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                if (!empty($cdi_data)) {
                    $incomplete_reason = "Grade";
                } else {
                    $incomplete_reason = "Both";
                }
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value->id, "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied Due To Incomplete Records", "first_offered_rank" => 0, "first_waitlist_for" => $value['first_choice_program_id'], "second_choice_final_status" => "Denied Due To Incomplete Records", "second_offered_rank" => 0, "second_waitlist_for" => $value['second_choice_program_id'], 'incomplete_reason' => $incomplete_reason, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            } //4
        }
        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }


        //$tmpAvailability = $availabilityArray;
        $waitlistArr = $offeredRank = $firstOffered = array();
        foreach ($firstdata as $key => $value) {
            if ($value['grade_status'] == "Pass" && $value['cdi_status'] == "Pass") {
                if (isset($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']])) {

                    if ($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] > 0) {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $firstOffered[] = $value['id'];
                        if (isset($offeredRank[$value['first_choice_program_id']])) {
                            $offeredRank[$value['first_choice_program_id']] = $offeredRank[$value['first_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['first_choice_program_id']] = 1;
                        }

                        $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] - 1;
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Offered", "first_offered_rank" => $offeredRank[$value['first_choice_program_id']], "first_waitlist_for" => $value['first_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    } else {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['first_choice_program_id']])) {
                            $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['first_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['first_choice_program_id']])) {
                        $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['first_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $firstdata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Eligibility</div>";
                if ($value['cdi_status'] == "Fail" && $value['grade_status'] == "Fail") {
                    $first_choice_eligibility_reason = "Both";
                } elseif ($value['cdi_status'] == "Fail") {
                    $first_choice_eligibility_reason = "CDI";
                } else {
                    $first_choice_eligibility_reason = "Grade";
                }

                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied due to Ineligibility", "first_waitlist_for" => $value['first_choice_program_id'], "first_choice_eligibility_reason" => $first_choice_eligibility_reason, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        foreach ($seconddata as $key => $value) {
            if ($value['grade_status'] == "Pass" && $value['cdi_status'] == "Pass") {
                if (isset($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']])) {
                    if ($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] > 0 && !in_array($value['id'], $firstOffered)) {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] - 1;

                        if (isset($offeredRank[$value['second_choice_program_id']])) {
                            $offeredRank[$value['second_choice_program_id']] = $offeredRank[$value['second_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['second_choice_program_id']] = 1;
                        }
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Offered", "second_offered_rank" => $offeredRank[$value['second_choice_program_id']], "second_waitlist_for" => $value['second_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    } else {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['second_choice_program_id']])) {
                            $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['second_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['second_choice_program_id']])) {
                        $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['second_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $seconddata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Ineligibility</div>";
                if ($value['cdi_status'] == "Fail" && $value['grade_status'] == "Fail") {
                    $second_choice_eligibility_reason = "Both";
                } elseif ($value['cdi_status'] == "Fail") {
                    $second_choice_eligibility_reason = "CDI";
                } else {
                    $second_choice_eligibility_reason = "Grade";
                }

                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Denied due to Ineligibility", "second_waitlist_for" => $value['second_choice_program_id'], "second_choice_eligibility_reason" => $second_choice_eligibility_reason, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }
        echo "Done";
    }
    /* Late Submissions Individual Submissions Ends */
    public function generateWaitlistIndividualStatus()
    {
        $rs = WaitlistProcessLogs::count();
        $version = $rs + 1;

        $availabilityArray = array();
        $parray = $garray = array();
        $allProgram = Availability::distinct()->where("district_id", Session::get("district_id"))->where("enrollment_id", Session::get("enrollment_id"))->get(['program_id']);
        foreach ($allProgram as $key => $value) {
            $avail_grade = Availability::where("district_id", Session::get("district_id"))->where("program_id", $value->program_id)->get();
            foreach ($avail_grade as $gkey => $gvalue) {
                $offer_count = Submissions::where('district_id', Session::get("district_id"))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue) {
                    $q->where(function ($q1)  use ($value, $gvalue) {
                        $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $value->program_id)->where('next_grade', $gvalue->grade);
                    })->orWhere(function ($q1) use ($value, $gvalue) {
                        $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $value->program_id)->where('next_grade', $gvalue->grade);
                    });
                })->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->count();


                $rs = WaitlistAvailability::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();
                if (!empty($rs)) {
                    $garray[] = $gvalue->grade;
                    $parray[] = $value->program_id;
                    $availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats + $rs->withdrawn_seats - $offer_count;
                } else {
                    $availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats - $offer_count;
                }
            }
        }

        SubmissionsWaitlistFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->delete();
        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where('submissions.enrollment_id', Session::get('enrollment_id'))->where(function ($q) use ($value, $gvalue) {
            $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
        })->where(function ($q) use ($parray) {
            $q->whereIn("first_choice_program_id", $parray)->orWhereIn("second_choice_program_id", $parray);
        })->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->select("submissions.*", "submissions_final_status.first_offer_status", "submissions_final_status.second_offer_status")
            ->get();
        $decWtArry = array();


        foreach ($submissions as $key => $value) {
            if (in_array($value->next_grade, $garray)) {
                if ($value->first_choice != "" && $value->second_choice != "") {

                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['second_program'] = "";
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($value->first_offer_status != "Declined & Waitlisted") {
                        $firstdata[] = $tmp;
                    }

                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_program'] = "";
                    if ($value->second_offer_status != "Declined & Waitlisted") {
                        $seconddata[] = $tmp;
                    }
                } elseif ($value->first_choice != "") {
                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_program'] = "";
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($value->first_offer_status != "Declined & Waitlisted") {
                        $firstdata[] = $tmp;
                    }
                } else {
                    $tmp = $this->convertToArray($value);
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['first_program'] = "";
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    if ($value->second_offer_status != "Declined & Waitlisted") {
                        $seconddata[] = $tmp;
                    }
                }
            }
        }


        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }

        $tmpAvailability = $availabilityArray;
        $waitlistArr = $offeredRank = $firstOffered = array();
        foreach ($firstdata as $key => $value) {
            if (isset($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']])) {

                if ($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] > 0 && in_array($value['first_choice_program_id'], $parray)) {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                    $firstOffered[] = $value['id'];
                    if (isset($offeredRank[$value['first_choice_program_id']])) {
                        $offeredRank[$value['first_choice_program_id']] = $offeredRank[$value['first_choice_program_id']] + 1;
                    } else {
                        $offeredRank[$value['first_choice_program_id']] = 1;
                    }

                    $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] - 1;
                    do {
                        $code = mt_rand(100000, 999999);
                        $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                        $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                        $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                    } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));
                    $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Offered", "first_offered_rank" => $offeredRank[$value['first_choice_program_id']], "first_waitlist_for" => $value['first_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                } else {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['first_choice_program_id']])) {
                        $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['first_choice_program_id']] = 1;
                    }

                    $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                if (isset($waitlistArr[$value['first_choice_program_id']])) {
                    $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                } else {
                    $waitlistArr[$value['first_choice_program_id']] = 1;
                }
                $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        foreach ($seconddata as $key => $value) {

            if (isset($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']])) {
                if ($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] > 0 && !in_array($value['id'], $firstOffered)  && in_array($value['second_choice_program_id'], $parray)) {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                    $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] - 1;

                    if (isset($offeredRank[$value['second_choice_program_id']])) {
                        $offeredRank[$value['second_choice_program_id']] = $offeredRank[$value['second_choice_program_id']] + 1;
                    } else {
                        $offeredRank[$value['second_choice_program_id']] = 1;
                    }
                    do {
                        $code = mt_rand(100000, 999999);
                        $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                        $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                        $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                    } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                    $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Offered", "second_offered_rank" => $offeredRank[$value['second_choice_program_id']], "second_waitlist_for" => $value['second_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                } else {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['second_choice_program_id']])) {
                        $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['second_choice_program_id']] = 1;
                    }

                    $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                if (isset($waitlistArr[$value['second_choice_program_id']])) {
                    $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                } else {
                    $waitlistArr[$value['second_choice_program_id']] = 1;
                }
                $rs = SubmissionsWaitlistFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }
        echo "Done";
    }


    /* Code for Late Submissions Process Selection */
    public function late_submission_wailist_calculate()
    {
        $last_type = app('App\Modules\Waitlist\Controllers\WaitlistController')->check_last_process();
        if ($last_type == "regular") {
            $id = 0;
        } else {
            if ($last_type == "late_submission") {
                $rs = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first();
                $id = $rs->version;
            } else {
                $rs = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first();
                $id = $rs->version;
            }
        }


        $firstdata = $seconddata = array();
        if ($id == 0) {
            $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) {
                $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
            })->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->select("submissions.*", "submissions_final_status.first_offer_status", "submissions_final_status.second_offer_status", "submissions_final_status.first_choice_final_status", "submissions_final_status.second_choice_final_status")
                ->get();
        } else {
            if ($last_type == "waitlist") {
                $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) {
                    $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
                })->where("submissions_waitlist_final_status.version", $id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->select("submissions.*", "submissions_waitlist_final_status.first_offer_status", "submissions_waitlist_final_status.first_choice_final_status", "submissions_waitlist_final_status.second_choice_final_status", "submissions_waitlist_final_status.second_offer_status")
                    ->get();
            } else {
                $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) {
                    $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
                })->where("late_submissions_final_status.version", $id)->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->select("submissions.*", "late_submissions_final_status.first_offer_status", "late_submissions_final_status.first_choice_final_status", "late_submissions_final_status.second_choice_final_status", "late_submissions_final_status.second_offer_status")
                    ->get();
            }
        }


        $decWtArry = array();
        foreach ($submissions as $key => $value) {
            if ($value->first_choice != "" && $value->second_choice != "") {

                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice'] = $value->first_choice;
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['second_program'] = "";
                $tmp['org_rank'] = 1;
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                if ($value->first_offer_status != "Declined & Waitlisted" && $value->first_choice_final_status != "Pending") {
                    $firstdata[] = $tmp;
                }
                $tmp['org_rank'] = 1;
                $tmp['rank'] = $this->priorityCalculate($value, "second");
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_program'] = "";
                if ($value->second_offer_status != "Declined & Waitlisted" && $value->second_choice_final_status != "Pending") {
                    $seconddata[] = $tmp;
                }
            } elseif ($value->first_choice != "") {
                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_program'] = "";
                $tmp['org_rank'] = 1;
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                if ($value->first_offer_status != "Declined & Waitlisted" && $value->first_choice_final_status != "Pending") {
                    $firstdata[] = $tmp;
                }
            } else {
                $tmp = $this->convertToArray($value);
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['first_program'] = "";
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['org_rank'] = 1;
                $tmp['rank'] = $this->priorityCalculate($value, "second");
                if ($value->second_offer_status != "Declined & Waitlisted" && $value->second_choice_final_status != "Pending") {
                    $seconddata[] = $tmp;
                }
            }
        }

        return array("firstdata" => $firstdata, "seconddata" => $seconddata);
    }

    public function generateLateSubmissionStatus()
    {
        $rs = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $version = $rs + 1;

        $rsD = LateSubmissionFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->delete();

        $rsWt = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        if ($rsWt > 0) {
            $id = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first()->version;
        } else {
            $id = 0;
        }

        $availabilityArray = array();
        $allProgram = Availability::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->get(['program_id']);
        foreach ($allProgram as $key => $value) {
            $avail_grade = Availability::where("district_id", Session::get("district_id"))->where("program_id", $value->program_id)->get();
            foreach ($avail_grade as $gkey => $gvalue) {
                /*$offer_count = Submissions::where('district_id', Session::get("district_id"))->where(function ($q) use ($value, $gvalue){
                                $q->where(function ($q1)  use ($value, $gvalue){
                                    $q1->where('first_choice_final_status', 'Offered')->where('first_offer_status', 'Accepted')->where('first_choice_program_id', $value->program_id)->where('next_grade', $gvalue->grade);
                                })->orWhere(function ($q1) use ($value, $gvalue){
                                    $q1->where('second_choice_final_status', 'Offered')->where('second_offer_status', 'Accepted')->where('second_choice_program_id', $value->program_id)->where('next_grade', $gvalue->grade);
                                });
                            })->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->count();*/

                $offer_count = app('App\Modules\Waitlist\Controllers\WaitlistController')->get_offer_count($value->program_id, $gvalue->grade, Session::get("district_id"), 1);
                //echo $value->grade
                $rs = WaitlistAvailabilityLog::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();
                if (!empty($rs)) {
                    $wt_count = $rs->withdrawn_seats;
                } else {
                    $wt_count = 0;
                }

                $rs = LateSubmissionAvailability::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();
                if (!empty($rs)) {
                    $lt_count = $rs->withdrawn_seats;
                } else {
                    $lt_count = 0;
                }
                $availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats + $wt_count + $lt_count - $offer_count;
            }
        }

        /* Code to Process Earlier Waitlist before Processing Regular Submissions */

        $tstArray = $this->late_submission_wailist_calculate();
        $firstdata = $tstArray['firstdata'];
        $seconddata = $tstArray['seconddata'];



        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }

        $tmpAvailability = $availabilityArray;
        $waitlistArr = $offeredRank = $firstOffered = array();



        foreach ($firstdata as $key => $value) {
            if (isset($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']])) {

                if ($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] > 0) {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                    $firstOffered[] = $value['id'];
                    if (isset($offeredRank[$value['first_choice_program_id']])) {
                        $offeredRank[$value['first_choice_program_id']] = $offeredRank[$value['first_choice_program_id']] + 1;
                    } else {
                        $offeredRank[$value['first_choice_program_id']] = 1;
                    }

                    $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] - 1;
                    do {
                        $code = mt_rand(100000, 999999);
                        $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                        $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                        $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                    } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Offered", "first_offered_rank" => $offeredRank[$value['first_choice_program_id']], "first_waitlist_for" => $value['first_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);

                    $pname = getProgramName($value['first_choice_program_id']);
                    $rU = Submissions::where("id", $value['id'])->update(array("awarded_school" => $pname));
                } else {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['first_choice_program_id']])) {
                        $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['first_choice_program_id']] = 1;
                    }

                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                if (isset($waitlistArr[$value['first_choice_program_id']])) {
                    $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                } else {
                    $waitlistArr[$value['first_choice_program_id']] = 1;
                }
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        foreach ($seconddata as $key => $value) {

            if (isset($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']])) {
                if ($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] > 0 && !in_array($value['id'], $firstOffered)) {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                    $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] - 1;

                    if (isset($offeredRank[$value['second_choice_program_id']])) {
                        $offeredRank[$value['second_choice_program_id']] = $offeredRank[$value['second_choice_program_id']] + 1;
                    } else {
                        $offeredRank[$value['second_choice_program_id']] = 1;
                    }
                    do {
                        $code = mt_rand(100000, 999999);
                        $user_code1 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                        $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        $user_code3 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                    } while (!empty($user_code1) && !empty($user_code2) && !empty($user_code3));

                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Offered", "second_offered_rank" => $offeredRank[$value['second_choice_program_id']], "second_waitlist_for" => $value['second_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    $pname = getProgramName($value['second_choice_program_id']);
                    $rU = Submissions::where("id", $value['id'])->update(array("awarded_school" => $pname));
                } else {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['second_choice_program_id']])) {
                        $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['second_choice_program_id']] = 1;
                    }

                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                if (isset($waitlistArr[$value['second_choice_program_id']])) {
                    $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                } else {
                    $waitlistArr[$value['second_choice_program_id']] = 1;
                }
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }
        $firstdata = $seconddata = array();

        $subjects = $terms = array();
        $eligibilityArr = array();
        $application_ids = [];
        $rsAppData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("late_submission", "Y")->whereIn("submission_status", array('Active'))->get(["application_id"]); //->where("id", 8839)
        foreach ($rsAppData as $rkey => $rvalue) {
            $application_ids[] = $rvalue->application_id;
        }
        $firstData = Submissions::distinct()->whereIn("application_id", $application_ids)->where("late_submission", "Y")->get(["first_choice", "second_choice"]);
        $calc_type = "DD";
        $academic_year = $calc_type_arr = [];
        foreach ($firstData as $value) {
            if ($value->first_choice != "") {
                //echo "FC".$value->first_choice."<BR>";
                $eligibilityData = getEligibilitiesDynamic($value->first_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                        $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                        // echo $eligibilityData[0]->id;exit;
                        $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                        if (!empty($content)) {
                            if ($content->scoring->type == "GA" || $content->scoring->type == "DD" || $content->scoring->type == "CLSG") {
                                $calc_type = $content->scoring->type;
                                $calc_type_arr[$value->first_choice] = $calc_type;
                                $tmp = array();

                                foreach ($content->academic_year_calc as $svalue) {
                                    if (!in_array($svalue, $academic_year)) {
                                        $academic_year[] = $svalue;
                                    }
                                }

                                foreach ($content->subjects as $svalue) {
                                    if (!in_array($svalue, $subjects)) {
                                        $subjects[] = $svalue;
                                    }
                                }

                                foreach ($content->terms_calc as $svalue) {
                                    if (!in_array($svalue, $terms)) {
                                        $terms[] = $svalue;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $secondData = Submissions::distinct()->where("late_submission", "Y")->whereIn("application_id", $application_ids)->get(["second_choice"]);
        foreach ($secondData as $value) {
            if ($value->second_choice != "") {
                $eligibilityData = getEligibilitiesDynamic($value->second_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                    if (!empty($content)) {
                        if ($content->scoring->type == "DD" || $content->scoring->type == "CLSG" || $content->scoring->type == "GA") {
                            $calc_type = $content->scoring->type;
                            $calc_type_arr[$value->second_choice] = $calc_type;

                            $tmp = array();

                            foreach ($content->academic_year_calc as $svalue) {
                                if (!in_array($svalue, $academic_year)) {
                                    $academic_year[] = $svalue;
                                }
                            }

                            foreach ($content->subjects as $svalue) {
                                if (!in_array($svalue, $subjects)) {
                                    $subjects[] = $svalue;
                                }
                            }

                            foreach ($content->terms_calc as $svalue) {
                                if (!in_array($svalue, $terms)) {
                                    $terms[] = $svalue;
                                }
                            }
                        }
                    }
                }
            }
        }
        //exit;*/
        //        $subjects = array("re", "eng", "math", "sci", "ss");
        //        $terms = array("Q1 Grade", "Q2 Grade");
        //        $academic_year = array("2023-2024");
        //       $calc_type_arr = array("841" => "CLSG","844" => "CLSG","941" => "CLSG","847" => "CLSG","850" => "CLSG","843" => "CLSG","852" => "CLSG","840" => "CLSG","836" => "CLSG","849" => "CLSG","848" => "CLSG","845" => "CLSG","854" => "CLSG","835" => "CLSG","839" => "CLSG","842" => "CLSG","851" => "CLSG","855" => "CLSG","846" => "CLSG");

        echo "<pre>";
        print_r($calc_type_arr);
        print_r($subjects);
        print_r($terms);
        print_r($academic_year);
        exit;

        /* Get Set Eligibility Data Set for first choice program and second choice program
         */

        $setEligibilityData = array();
        /*foreach($firstData as $value)
        {
            if(!in_array($value->first_choice, array_keys($setEligibilityData)))
            {

                $data = getSetEligibilityDataDynamic($value->first_choice, 3);
                
                foreach($subjects as $svalue)
                {
                    $setEligibilityData[$value->first_choice][$svalue] = 70;   
                    // if(isset($data->{$svalue}))
                    // {
                    //     $setEligibilityData[$value->first_choice][$svalue] = $data->{$svalue}[0];
                    // }
                    // else
                    // {
                    //      $setEligibilityData[$value->first_choice][$svalue] = 70;   
                    // }
                }
            }

        }

        foreach($secondData as $value)
        {
            if(!in_array($value->second_choice, array_keys($setEligibilityData)))
            {
                $data = getSetEligibilityDataDynamic($value->second_choice, 3);

                foreach($subjects as $svalue)
                {
                    $setEligibilityData[$value->second_choice][$svalue] = 70;   
                    // if(isset($data->{$svalue}))
                    // {
                    //     $setEligibilityData[$value->second_choice][$svalue] = $data->{$svalue}[0];
                    // }
                    // else
                    // {
                    //      $setEligibilityData[$value->second_choice][$svalue] = 70;   
                    // }
                }
            }

        }*/

        /* Get CDI Set Eligibility Data Set for first choice program and second choice program
         */

        $setCDIEligibilityData = array();
        /*foreach($firstData as $value)
        {
            if(!in_array($value->first_choice, array_keys($setCDIEligibilityData)))
            {
                $setCDIEligibilityData[$value->first_choice]['b_info'] = 5;
                    $setCDIEligibilityData[$value->first_choice]['c_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['d_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['e_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['susp'] = 2;
                    $setCDIEligibilityData[$value->first_choice]['susp_days'] = 4;

                // $data = getSetEligibilityDataDynamic($value->first_choice, 8);
                // if(!empty($data))
                // {
                //     $setCDIEligibilityData[$value->first_choice]['b_info'] = $data->B[0];
                //     $setCDIEligibilityData[$value->first_choice]['c_info'] = $data->C[0];
                //     $setCDIEligibilityData[$value->first_choice]['d_info'] = $data->D[0];
                //     $setCDIEligibilityData[$value->first_choice]['e_info'] = $data->E[0];
                //     $setCDIEligibilityData[$value->first_choice]['susp'] = $data->Susp[0];
                //     $setCDIEligibilityData[$value->first_choice]['susp_days'] = $data->SuspDays[0];
                // }
                // else
                // {
                //     $setCDIEligibilityData[$value->first_choice]['b_info'] = 5;
                //     $setCDIEligibilityData[$value->first_choice]['c_info'] = 0;
                //     $setCDIEligibilityData[$value->first_choice]['d_info'] = 0;
                //     $setCDIEligibilityData[$value->first_choice]['e_info'] = 0;
                //     $setCDIEligibilityData[$value->first_choice]['susp'] = 2;
                //     $setCDIEligibilityData[$value->first_choice]['susp_days'] = 4;
                // }
            }
        }
        foreach($secondData as $value)
        {
            if(!in_array($value->second_choice, array_keys($setCDIEligibilityData)))
            {
                $setCDIEligibilityData[$value->second_choice]['b_info'] = 5;
                    $setCDIEligibilityData[$value->second_choice]['c_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['d_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['e_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['susp'] = 2;
                    $setCDIEligibilityData[$value->second_choice]['susp_days'] = 4;
                // $data = getSetEligibilityDataDynamic($value->second_choice, 8);
                // if(!empty($data))
                // {
                //     $setCDIEligibilityData[$value->second_choice]['b_info'] = $data->B[0];
                //     $setCDIEligibilityData[$value->second_choice]['c_info'] = $data->C[0];
                //     $setCDIEligibilityData[$value->second_choice]['d_info'] = $data->D[0];
                //     $setCDIEligibilityData[$value->second_choice]['e_info'] = $data->E[0];
                //     $setCDIEligibilityData[$value->second_choice]['susp'] = $data->Susp[0];
                //     $setCDIEligibilityData[$value->second_choice]['susp_days'] = $data->SuspDays[0];
                // }
                // else
                // {
                //     $setCDIEligibilityData[$value->second_choice]['b_info'] = 5;
                //     $setCDIEligibilityData[$value->second_choice]['c_info'] = 0;
                //     $setCDIEligibilityData[$value->second_choice]['d_info'] = 0;
                //     $setCDIEligibilityData[$value->second_choice]['e_info'] = 0;
                //     $setCDIEligibilityData[$value->second_choice]['susp'] = 2;
                //     $setCDIEligibilityData[$value->second_choice]['susp_days'] = 4;
                // }
            }
        }*/
        /*
echo "<pre>";
print_r($setEligibilityData);
print_r($setCDIEligibilityData);
exit;*/

        $setEligibilityData = ["841" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "941" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "847" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "850" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "843" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "840" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "852" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "848" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "854" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "835" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "839" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "844" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "842" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "836" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "851" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "855" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "849" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "846" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "845" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70]];


        $setCDIEligibilityData = ["841" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "941" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "847" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "850" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "843" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "840" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "852" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "848" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "854" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "835" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "839" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "844" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "842" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "836" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "851" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "855" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "849" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "846" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "845" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4]];
        /* Get CDI Data */
        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
            ->where("late_submission", "Y")
            ->where("enrollment_id", Session::get("enrollment_id"))
            ->whereIn('submission_status', array('Active', 'Pending'))
            //->where("id", 8839)
            // ->limit(5)
            ->get();


        $firstdata = $seconddata = array();
        $programGrades = array();
        foreach ($submissions as $key => $value) {
            if ($value->submission_status == "Pending") {
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value->id, "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied Due To Incomplete Records", "first_offered_rank" => 0, "first_waitlist_for" => $value['first_choice_program_id'], "second_choice_final_status" => "Denied Due To Incomplete Records", "second_offered_rank" => 0, "second_waitlist_for" => $value['second_choice_program_id'], 'incomplete_reason' => "Pending Status", "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                continue;
            }
            if (!isset($programGrades[$value->first_choice_program_id])) {
                $availableGrades = array();
                $eligibilityData = getEligibilitiesByProgramDynamic($value->first_choice_program_id, $application_ids, 'Academic Grade Calculation');

                if (isset($eligibilityData[0])) {
                    $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                    $programGrades[$value->first_choice_program_id] = $availableGrades;
                } else {
                    $programGrades[$value->first_choice_program_id] = [];
                }
            }
            $skip = false;
            if ($value->first_choice_program_id != 0 && !in_array($value->next_grade, $programGrades[$value->first_choice_program_id])) {
                $skip = true;
            }

            if ($value->second_choice_program_id != '' && $value->second_choice_program_id != '0') {
                if (!isset($programGrades[$value->second_choice_program_id])) {
                    $availableGrades = array();
                    $eligibilityData = getEligibilitiesByProgramDynamic($value->second_choice_program_id, $application_ids, 'Academic Grade Calculation');
                    if (isset($eligibilityData[0])) {
                        $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                        $programGrades[$value->second_choice_program_id] = $availableGrades;
                    } else {
                        $programGrades[$value->second_choice_program_id] = [];
                    }
                }
                if (!in_array($value->next_grade, $programGrades[$value->second_choice_program_id])) {
                    $skip = true;
                }
            }

            if ($value->next_grade == "PreK" || $value->next_grade == "K" || $value->next_grade == "1") {
                $skip = true;
            }

            // if($value->id == 8525)
            // {
            //     if($skip)
            //         echo "T";
            //     else
            //         echo "W";

            //     dd($programGrades);
            // }
            //exit;
            if (isset($calc_type_arr[$value->first_choice]))
                $ctype = $calc_type_arr[$value->first_choice];
            elseif (isset($calc_type_arr[$value->second_choice]))
                $ctype = $calc_type_arr[$value->second_choice];
            else
                $ctype = "DD";
            $calc_type = 'CLSG';
            $score = $this->collectionStudentGradeReportDynamic($value, $academic_year, $subjects, $terms, $value->next_grade, $skip, $setEligibilityData, $calc_type);


            if (count($score) > 0 || $skip) {

                if ($skip) { //0.1
                    $cdiArr = array();
                    $cdiArr['b_info'] = "NA";
                    $cdiArr['c_info'] = "NA";
                    $cdiArr['d_info'] = "NA";
                    $cdiArr['e_info'] = "NA";
                    $cdiArr['susp'] = "NA";
                    $cdiArr['susp_days'] = "NA";
                } //0.1
                else { //0
                    $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                    if (!empty($cdi_data)) {
                        $cdiArr = array();
                        $cdiArr['b_info'] = $cdi_data->b_info;
                        $cdiArr['c_info'] = $cdi_data->c_info;
                        $cdiArr['d_info'] = $cdi_data->d_info;
                        $cdiArr['e_info'] = $cdi_data->e_info;
                        $cdiArr['susp'] = $cdi_data->susp;
                        $cdiArr['susp_days'] = $cdi_data->susp_days;
                    } elseif ($value->cdi_override == "Y") {
                        $cdiArr = array();
                        $cdiArr['b_info'] = 0;
                        $cdiArr['c_info'] = 0;
                        $cdiArr['d_info'] = 0;
                        $cdiArr['e_info'] = 0;
                        $cdiArr['susp'] = 0;
                        $cdiArr['susp_days'] = 0;
                    } else {
                        $incomplete_reason = "CDI";
                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value->id, "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied Due To Incomplete Records", "first_offered_rank" => 0, "first_waitlist_for" => $value['first_choice_program_id'], "second_choice_final_status" => "Denied Due To Incomplete Records", "second_offered_rank" => 0, "second_waitlist_for" => $value['second_choice_program_id'], 'incomplete_reason' => $incomplete_reason, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                        continue;
                    }
                } //0
                if ($value->first_choice != "" && $value->second_choice != "") { //1

                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['second_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['cdi'] = $cdiArr;
                    if ($value->cdi_override == "Y" || $skip)
                        $tmp['cdi_status'] = "Pass";
                    else {
                        $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                    }
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass" || $skip) {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $firstdata[] = $tmp;

                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass" || $skip) {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    if ($value->cdi_override == "Y" || $skip)
                        $tmp['cdi_status'] = "Pass";
                    else
                        $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['cdi'] = $cdiArr;
                    $seconddata[] = $tmp;
                } //1
                elseif ($value->first_choice != "") { //2
                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['cdi'] = $cdiArr;
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($value->cdi_override == "Y" || $skip)
                        $tmp['cdi_status'] = "Pass";
                    else
                        $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass" || $skip) {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $firstdata[] = $tmp;
                } //2
                else { //3
                    $tmp = $this->convertToArray($value);
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['first_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['cdi'] = $cdiArr;
                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass" || $skip) {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                    $seconddata[] = $tmp;
                } //3
            } else { //4
                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                if (!empty($cdi_data)) {
                    $incomplete_reason = "Grade";
                } else {
                    $incomplete_reason = "Both";
                }
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value->id, "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied Due To Incomplete Records", "first_offered_rank" => 0, "first_waitlist_for" => $value['first_choice_program_id'], "second_choice_final_status" => "Denied Due To Incomplete Records", "second_offered_rank" => 0, "second_waitlist_for" => $value['second_choice_program_id'], 'incomplete_reason' => $incomplete_reason, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            } //4
        }

        //dd($firstdata, $seconddata);
        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }
        //$tmpAvailability = $availabilityArray;

        // 3177
        $waitlistArr = $offeredRank = $firstOffered = array();

        foreach ($firstdata as $key => $value) {
            if ($value['grade_status'] == "Pass" && $value['cdi_status'] == "Pass") {
                if (isset($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']])) {

                    if ($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] > 0) {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $firstOffered[] = $value['id'];
                        if (isset($offeredRank[$value['first_choice_program_id']])) {
                            $offeredRank[$value['first_choice_program_id']] = $offeredRank[$value['first_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['first_choice_program_id']] = 1;
                        }

                        $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] - 1;
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));
                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Offered", "first_offered_rank" => $offeredRank[$value['first_choice_program_id']], "first_waitlist_for" => $value['first_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                        $pname = getProgramName($value['first_choice_program_id']);
                        $rU = Submissions::where("id", $value['id'])->update(array("awarded_school" => $pname));
                    } else {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['first_choice_program_id']])) {
                            $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['first_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['first_choice_program_id']])) {
                        $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['first_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $firstdata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Eligibility</div>";
                if ($value['cdi_status'] == "Fail" && $value['grade_status'] == "Fail") {
                    $first_choice_eligibility_reason = "Both";
                } elseif ($value['cdi_status'] == "Fail") {
                    $first_choice_eligibility_reason = "CDI";
                } else {
                    $first_choice_eligibility_reason = "Grade";
                }

                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied due to Ineligibility", "first_waitlist_for" => $value['first_choice_program_id'], "first_choice_eligibility_reason" => $first_choice_eligibility_reason, "version" => $version, "second_choice_final_status" => "Denied due to Ineligibility", "second_waitlist_for" => $value['second_choice_program_id'], "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        foreach ($seconddata as $key => $value) {
            if ($value['grade_status'] == "Pass" && $value['cdi_status'] == "Pass") {
                if (isset($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']])) {
                    if ($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] > 0 && !in_array($value['id'], $firstOffered)) {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] - 1;

                        if (isset($offeredRank[$value['second_choice_program_id']])) {
                            $offeredRank[$value['second_choice_program_id']] = $offeredRank[$value['second_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['second_choice_program_id']] = 1;
                        }
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Offered", "second_offered_rank" => $offeredRank[$value['second_choice_program_id']], "second_waitlist_for" => $value['second_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                        $pname = getProgramName($value['second_choice_program_id']);
                        $rU = Submissions::where("id", $value['id'])->update(array("awarded_school" => $pname));
                    } else {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['second_choice_program_id']])) {
                            $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['second_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['second_choice_program_id']])) {
                        $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['second_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $seconddata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Ineligibility</div>";
                if ($value['cdi_status'] == "Fail" && $value['grade_status'] == "Fail") {
                    $second_choice_eligibility_reason = "Both";
                } elseif ($value['cdi_status'] == "Fail") {
                    $second_choice_eligibility_reason = "CDI";
                } else {
                    $second_choice_eligibility_reason = "Grade";
                }

                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Denied due to Ineligibility", "second_waitlist_for" => $value['second_choice_program_id'], "second_choice_eligibility_reason" => $second_choice_eligibility_reason, "version" => $version, "first_choice_final_status" => "Denied due to Ineligibility", "first_waitlist_for" => $value['first_choice_program_id'], "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        $rsUpdate = LateSubmissionFinalStatus::where("first_choice_final_status", "Offered")->where('version', $version)->where("enrollment_id", Session::get("enrollment_id"))->where("second_choice_final_status", "Waitlisted")->update(array("second_choice_final_status" => "Pending", "second_waitlist_for" => 0));
    }

    /* Code Ends for Late Submission Process Selection */
    public function generateStatus()
    {

        $availabilityArray = array();
        $allProgram = Availability::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->get(['program_id']);
        foreach ($allProgram as $key => $value) {
            $avail_grade = Availability::where("district_id", Session::get("district_id"))->where("enrollment_id", Session::get("enrollment_id"))->where("program_id", $value->program_id)->get();
            foreach ($avail_grade as $gkey => $gvalue) {
                $availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats;
            }
        }


        $firstData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->whereIn("submission_status", array('Active', 'Pending'))->where("late_submission", "N")->get(["first_choice", "second_choice"]);
        /* Get Subject and Acardemic Term like Q1.1 Q1.2 etc set for Academic Grade Calculation 
                For all unique First Choice and Second Choice
         */
        $subjects = $terms = array();
        $eligibilityArr = array();
        $calc_type = "DD";
        $academic_year = [];
        $cdi_not_required = [];

        foreach ($firstData as $value) {
            if ($value->first_choice != "") {

                $eligibilityData_cd = getEligibilitiesDynamicProcessing($value->first_choice, 'Conduct Disciplinary Info');
                //dd($eligibilityData_cd);
                if (count($eligibilityData_cd) <= 0) {
                    $cdi_not_required[] = $value->first_choice;
                }

                $eligibilityData = getEligibilitiesDynamic($value->first_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                        $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                        // echo $eligibilityData[0]->id;exit;
                        $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                        if (!empty($content)) {
                            if ($content->scoring->type == "GA" || $content->scoring->type == "DD" || $content->scoring->type == "CLSG") {
                                $calc_type = $content->scoring->type;
                                $tmp = array();
                                foreach ($content->academic_year_calc as $svalue) {
                                    if (!in_array($svalue, $academic_year)) {
                                        $academic_year[] = $svalue;
                                    }
                                }

                                foreach ($content->subjects as $svalue) {
                                    if (!in_array($svalue, $subjects)) {
                                        $subjects[] = $svalue;
                                    }
                                }

                                foreach ($content->terms_calc as $svalue) {
                                    if (!in_array($svalue, $terms)) {
                                        $terms[] = $svalue;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            //}

            //$secondData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->whereIn("submission_status", array('Active', 'Pending'))->where("late_submission", "N")->get(["second_choice"]);
            //foreach($secondData as $value)
            //{
            if ($value->second_choice != "") {
                $eligibilityData_cd = getEligibilitiesDynamicProcessing($value->second_choice, 'Conduct Disciplinary Info');
                if (count($eligibilityData_cd) <= 0) {
                    $cdi_not_required[] = $value->second_choice;
                }

                $eligibilityData = getEligibilitiesDynamic($value->second_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);
                    if (!empty($content)) {
                        if ($content->scoring->type == "DD" || $content->scoring->type == "CLSG" || $content->scoring->type == "GA") {
                            $calc_type = $content->scoring->type;

                            $tmp = array();

                            foreach ($content->academic_year_calc as $svalue) {
                                if (!in_array($svalue, $academic_year)) {
                                    $academic_year[] = $svalue;
                                }
                            }

                            foreach ($content->subjects as $svalue) {
                                if (!in_array($svalue, $subjects)) {
                                    $subjects[] = $svalue;
                                }
                            }

                            foreach ($content->terms_calc as $svalue) {
                                if (!in_array($svalue, $terms)) {
                                    $terms[] = $svalue;
                                }
                            }
                        }
                    }
                }
            }
        }
        //        dd($terms, $subjects, $cdi_not_required);

        /* Get Set Eligibility Data Set for first choice program and second choice program
         */

        $setEligibilityData = array();
        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, array_keys($setEligibilityData))) {
                $data = getSetEligibilityDataDynamic($value->first_choice, 3);
                foreach ($subjects as $svalue) {
                    if (isset($data->{$svalue})) {
                        $setEligibilityData[$value->first_choice][$svalue] = $data->{$svalue}[0];
                    } else {
                        $setEligibilityData[$value->first_choice][$svalue] = 70;
                    }
                }
            }

            if (!in_array($value->second_choice, array_keys($setEligibilityData))) {
                $data = getSetEligibilityDataDynamic($value->second_choice, 3);

                foreach ($subjects as $svalue) {
                    if (isset($data->{$svalue})) {
                        $setEligibilityData[$value->second_choice][$svalue] = $data->{$svalue}[0];
                    } else {
                        $setEligibilityData[$value->second_choice][$svalue] = 70;
                    }
                }
            }
        }


        /* Get CDI Set Eligibility Data Set for first choice program and second choice program
         */

        $setCDIEligibilityData = array();
        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, $cdi_not_required) && !in_array($value->first_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityData($value->first_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->first_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->first_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->first_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->first_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->first_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->first_choice]['susp_days'] = $data->SuspDays[0];
                } else {
                    $setCDIEligibilityData[$value->first_choice]['b_info'] = 5;
                    $setCDIEligibilityData[$value->first_choice]['c_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['d_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['e_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['susp'] = 2;
                    $setCDIEligibilityData[$value->first_choice]['susp_days'] = 4;
                }
            }
        }
        foreach ($secondData as $value) {
            if (!in_array($value->second_choice, $cdi_not_required) && !in_array($value->second_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityData($value->second_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->second_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->second_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->second_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->second_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->second_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->second_choice]['susp_days'] = $data->SuspDays[0];
                } else {
                    $setCDIEligibilityData[$value->second_choice]['b_info'] = 5;
                    $setCDIEligibilityData[$value->second_choice]['c_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['d_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['e_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['susp'] = 2;
                    $setCDIEligibilityData[$value->second_choice]['susp_days'] = 4;
                }
            }
        }

        /* Needs to coding check from here - need to verify late submission status prat - Also verify K grade code */
        /* Get CDI Data */
        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
            ->whereIn('submission_status', array("Active", "Pending"))
            ->where("enrollment_id", Session::get("enrollment_id"))
            ->where("late_submission", "Y")
            //->where("id", 3768 )
            //->limit(5)
            ->get();


        $application_ids = [];


        foreach ($submissions as $sk => $sv) {
            //echo $sv->id." - ".$sv->next_grade."<BR>";
            if (!in_array($sv->application_id, $application_ids)) {
                $application_ids[] = $sv->application_id;
            }
        }


        $firstdata = $seconddata = array();
        $programGrades = array();
        foreach ($submissions as $key => $value) {
            if (!isset($programGrades[$value->first_choice_program_id])) {
                $availableGrades = array();
                $eligibilityData = getEligibilitiesByProgramDynamic($value->first_choice_program_id, $application_ids, 'Academic Grade Calculation');
                if (isset($eligibilityData[0])) {
                    $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                    $programGrades[$value->first_choice_program_id] = $availableGrades;
                } else {
                    $programGrades[$value->first_choice_program_id] = [];
                }
            }
            $skip = false;
            if ($value->first_choice_program_id != 0 && !in_array($value->next_grade, $programGrades[$value->first_choice_program_id])) {
                $skip = true;
            }

            if ($value->second_choice_program_id != '' && $value->second_choice_program_id != '0') {
                if (!isset($programGrades[$value->second_choice_program_id])) {
                    $availableGrades = array();
                    $eligibilityData = getEligibilitiesByProgramDynamic($value->second_choice_program_id, $application_ids, 'Academic Grade Calculation');
                    if (isset($eligibilityData[0])) {
                        $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                        $programGrades[$value->second_choice_program_id] = $availableGrades;
                    } else {
                        $programGrades[$value->second_choice_program_id] = [];
                    }
                }
                if (!in_array($value->next_grade, $programGrades[$value->second_choice_program_id])) {
                    $skip = true;
                }
            }

            $score = $this->collectionStudentGradeReportDynamic($value, $academic_year, $subjects, $terms, $value->next_grade, $skip, $setEligibilityData, $calc_type);


            if (count($score) > 0 || $skip) {
                //$skip = true;
                if ($skip) {

                    $cdiArr = array();
                    $cdiArr['b_info'] = "NA";
                    $cdiArr['c_info'] = "NA";
                    $cdiArr['d_info'] = "NA";
                    $cdiArr['e_info'] = "NA";
                    $cdiArr['susp'] = "NA";
                    $cdiArr['susp_days'] = "NA";
                } else {
                    $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                    if (!empty($cdi_data)) {
                        $cdiArr = array();
                        $cdiArr['b_info'] = $cdi_data->b_info;
                        $cdiArr['c_info'] = $cdi_data->c_info;
                        $cdiArr['d_info'] = $cdi_data->d_info;
                        $cdiArr['e_info'] = $cdi_data->e_info;
                        $cdiArr['susp'] = $cdi_data->susp;
                        $cdiArr['susp_days'] = $cdi_data->susp_days;
                    } elseif ($value->cdi_override == "Y") {
                        $cdiArr = array();
                        $cdiArr['b_info'] = 0;
                        $cdiArr['c_info'] = 0;
                        $cdiArr['d_info'] = 0;
                        $cdiArr['e_info'] = 0;
                        $cdiArr['susp'] = 0;
                        $cdiArr['susp_days'] = 0;
                    } elseif (!in_array($value->first_choice, $cdi_not_required) && !in_array($value->second_choice, $cdi_not_required)) {
                        $incomplete_reason = "CDI";
                        $rs = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value->id, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied Due To Incomplete Records", "first_offered_rank" => 0, "first_waitlist_for" => $value['first_choice_program_id'], "second_choice_final_status" => "Denied Due To Incomplete Records", "second_offered_rank" => 0, "second_waitlist_for" => $value['second_choice_program_id'], 'incomplete_reason' => $incomplete_reason, "enrollment_id" => Session::get("enrollment_id")]);
                        continue;
                    }
                }

                if ($value->first_choice != "" && $value->second_choice != "") {

                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['second_program'] = "";
                    $tmp['score'] = $score;
                    if (in_array($value->first_choice, $cdi_not_required)) {
                        $cdiArr = array();
                        $cdiArr['b_info'] = "NA";
                        $cdiArr['c_info'] = "NA";
                        $cdiArr['d_info'] = "NA";
                        $cdiArr['e_info'] = "NA";
                        $cdiArr['susp'] = "NA";
                        $cdiArr['susp_days'] = "NA";
                    }
                    $tmp['cdi'] = $cdiArr;
                    if ($value->cdi_override == "Y")
                        $tmp['cdi_status'] = "Pass";
                    else {
                        if (in_array($value->first_choice, $cdi_not_required))
                            $tmp['cdi_status'] = "Pass";
                        else
                            $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                    }
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass" || $skip) {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $firstdata[] = $tmp;

                    if (in_array($value->second_choice, $cdi_not_required)) {
                        $cdiArr = array();
                        $cdiArr['b_info'] = "NA";
                        $cdiArr['c_info'] = "NA";
                        $cdiArr['d_info'] = "NA";
                        $cdiArr['e_info'] = "NA";
                        $cdiArr['susp'] = "NA";
                        $cdiArr['susp_days'] = "NA";
                        $tmp['cdi'] = $cdiArr;
                    }

                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass" || $skip) {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    if ($value->cdi_override == "Y")
                        $tmp['cdi_status'] = "Pass";
                    else {
                        if (in_array($value->second_choice, $cdi_not_required))
                            $tmp['cdi_status'] = "Pass";
                        else
                            $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                    }
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['cdi'] = $cdiArr;
                    $seconddata[] = $tmp;
                } elseif ($value->first_choice != "") {
                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;

                    if (in_array($value->first_choice, $cdi_not_required)) {
                        $cdiArr = array();
                        $cdiArr['b_info'] = "NA";
                        $cdiArr['c_info'] = "NA";
                        $cdiArr['d_info'] = "NA";
                        $cdiArr['e_info'] = "NA";
                        $cdiArr['susp'] = "NA";
                        $cdiArr['susp_days'] = "NA";
                    }
                    $tmp['cdi'] = $cdiArr;
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($value->cdi_override == "Y")
                        $tmp['cdi_status'] = "Pass";
                    else {
                        if (in_array($value->first_choice, $cdi_not_required))
                            $tmp['cdi_status'] = "Pass";
                        else
                            $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                    }
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass" || $skip) {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $firstdata[] = $tmp;
                } else {
                    $tmp = $this->convertToArray($value);
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['first_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;
                    if (in_array($value->second_choice, $cdi_not_required)) {
                        $cdiArr = array();
                        $cdiArr['b_info'] = "NA";
                        $cdiArr['c_info'] = "NA";
                        $cdiArr['d_info'] = "NA";
                        $cdiArr['e_info'] = "NA";
                        $cdiArr['susp'] = "NA";
                        $cdiArr['susp_days'] = "NA";
                    }
                    $tmp['cdi'] = $cdiArr;

                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass" || $skip) {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    if (in_array($value->second_choice, $cdi_not_required))
                        $tmp['cdi_status'] = "Pass";
                    else
                        $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                    $seconddata[] = $tmp;
                }
            } else {
                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                if (!empty($cdi_data)) {
                    $incomplete_reason = "Grade";
                } else {
                    $incomplete_reason = "Both";
                }
                $rs = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value->id, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied Due To Incomplete Records", "first_offered_rank" => 0, "first_waitlist_for" => $value['first_choice_program_id'], "second_choice_final_status" => "Denied Due To Incomplete Records", "second_offered_rank" => 0, "second_waitlist_for" => $value['second_choice_program_id'], 'incomplete_reason' => $incomplete_reason, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }
        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }

        $tmpAvailability = $availabilityArray;
        $waitlistArr = $offeredRank = $firstOffered = array();
        foreach ($firstdata as $key => $value) {
            if ($value['grade_status'] == "Pass" && $value['cdi_status'] == "Pass") {
                if (isset($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']])) {

                    if ($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] > 0) {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $firstOffered[] = $value['id'];
                        if (isset($offeredRank[$value['first_choice_program_id']])) {
                            $offeredRank[$value['first_choice_program_id']] = $offeredRank[$value['first_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['first_choice_program_id']] = 1;
                        }

                        $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] - 1;
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                        $rs = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value['id'], "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Offered", "first_offered_rank" => $offeredRank[$value['first_choice_program_id']], "first_waitlist_for" => $value['first_choice_program_id'], 'offer_slug' => $code, "enrollment_id" => Session::get("enrollment_id")]);
                        $pname = getProgramName($value['first_choice_program_id']);
                        $rU = Submissions::where("id", $value['id'])->update(array("awarded_school" => $pname));
                    } else {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['first_choice_program_id']])) {
                            $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['first_choice_program_id']] = 1;
                        }

                        $rs = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value['id'], "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['first_choice_program_id']])) {
                        $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['first_choice_program_id']] = 1;
                    }
                    $rs = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value['id'], "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $firstdata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Eligibility</div>";
                if ($value['cdi_status'] == "Fail" && $value['grade_status'] == "Fail") {
                    $first_choice_eligibility_reason = "Both";
                } elseif ($value['cdi_status'] == "Fail") {
                    $first_choice_eligibility_reason = "CDI";
                } else {
                    $first_choice_eligibility_reason = "Grade";
                }

                $rs = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value['id'], "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied due to Ineligibility", "first_waitlist_for" => $value['first_choice_program_id'], "first_choice_eligibility_reason" => $first_choice_eligibility_reason, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        foreach ($seconddata as $key => $value) {
            if ($value['grade_status'] == "Pass" && $value['cdi_status'] == "Pass") {
                if (isset($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']])) {
                    if ($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] > 0 && !in_array($value['id'], $firstOffered)) {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] - 1;

                        if (isset($offeredRank[$value['second_choice_program_id']])) {
                            $offeredRank[$value['second_choice_program_id']] = $offeredRank[$value['second_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['second_choice_program_id']] = 1;
                        }
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));


                        $rs = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value['id'], "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Offered", "second_offered_rank" => $offeredRank[$value['second_choice_program_id']], "second_waitlist_for" => $value['second_choice_program_id'], 'offer_slug' => $code, "enrollment_id" => Session::get("enrollment_id")]);
                        $pname = getProgramName($value['second_choice_program_id']);
                        $rU = Submissions::where("id", $value['id'])->update(array("awarded_school" => $pname));
                    } else {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['second_choice_program_id']])) {
                            $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['second_choice_program_id']] = 1;
                        }

                        $rs = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value['id'], "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['second_choice_program_id']])) {
                        $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['second_choice_program_id']] = 1;
                    }
                    $rs = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value['id'], "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $seconddata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Ineligibility</div>";
                if ($value['cdi_status'] == "Fail" && $value['grade_status'] == "Fail") {
                    $second_choice_eligibility_reason = "Both";
                } elseif ($value['cdi_status'] == "Fail") {
                    $second_choice_eligibility_reason = "CDI";
                } else {
                    $second_choice_eligibility_reason = "Grade";
                }

                $rs = SubmissionsFinalStatus::updateOrCreate(["submission_id" => $value['id'], "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Denied due to Ineligibility", "second_waitlist_for" => $value['second_choice_program_id'], "second_choice_eligibility_reason" => $second_choice_eligibility_reason, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        $rsUpdate = SubmissionsFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("first_choice_final_status", "Offered")->where("second_choice_final_status", "Waitlisted")->get();
        foreach ($rsUpdate as $ukey => $uvalue) {
            $rs = SubmissionsFinalStatus::where("submission_id", $uvalue->submission_id)->where("first_choice_final_status", "Offered")->update(["second_choice_final_status" => "Pending"]);
        }
    }

    public function index($grade = 0)
    {

        $settings = DB::table("reports_hide_option")->first();
        $availabilityArray = array();
        $allProgram = Availability::distinct()->where("district_id", Session::get("district_id"))->where("enrollment_id", Session::get("enrollment_id"))->get(['program_id']);
        foreach ($allProgram as $key => $value) {
            $avail_grade = Availability::where("district_id", Session::get("district_id"))->where("program_id", $value->program_id)->get();
            foreach ($avail_grade as $gkey => $gvalue) {
                $availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats;
            }
        }

        /* Get Next Grade Unique for Tabbing */
        $grade_data = Submissions::distinct()->where('enrollment_id', Session::get('enrollment_id'))->where('next_grade', '<>', '')->orderBy('next_grade', 'DESC')->get(["next_grade"]);
        $gradeArr = array("K", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12");
        $fgradeTab = [];
        foreach ($grade_data as $key => $value) {
            $fgradeTab[] = $value->next_grade;
        }
        $gradeTab = [];
        foreach ($gradeArr as $key => $value) {
            if (in_array($value, $fgradeTab))
                $gradeTab[] = $value;
        }

        if ($grade == 0)
            $existGrade = $gradeTab[0];
        else
            $existGrade = $grade;

        $firstData = Submissions::distinct()->where('enrollment_id', Session::get('enrollment_id'))->get(["first_choice"]);

        /* Get Subject and Acardemic Term like Q1.1 Q1.2 etc set for Academic Grade Calculation 
                For all unique First Choice and Second Choice
         */
        $subjects = $terms = array();
        $eligibilityArr = array();
        foreach ($firstData as $value) {
            if ($value->first_choice != "") {
                $eligibilityData = getEligibilities($value->first_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                        $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                        // echo $eligibilityData[0]->id;exit;
                        $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                        if (!empty($content)) {
                            if ($content->scoring->type == "DD") {
                                $tmp = array();

                                foreach ($content->subjects as $value) {
                                    if (!in_array($value, $subjects)) {
                                        $subjects[] = $value;
                                    }
                                }

                                foreach ($content->terms_calc as $value) {
                                    if (!in_array($value, $terms)) {
                                        $terms[] = $value;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $secondData = Submissions::distinct()->where('enrollment_id', Session::get('enrollment_id'))->get(["second_choice"]);
        foreach ($secondData as $value) {
            if ($value->second_choice != "") {
                $eligibilityData = getEligibilities($value->second_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);
                    if (!empty($content)) {
                        if ($content->scoring->type == "DD") {
                            $tmp = array();

                            foreach ($content->subjects as $value) {
                                if (!in_array($value, $subjects)) {
                                    $subjects[] = $value;
                                }
                            }

                            foreach ($content->terms_calc as $value) {
                                if (!in_array($value, $terms)) {
                                    $terms[] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }

        /* Get Set Eligibility Data Set for first choice program and second choice program
         */

        $setEligibilityData = array();
        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, array_keys($setEligibilityData))) {
                $data = getSetEligibilityData($value->first_choice, 3);
                foreach ($subjects as $svalue) {
                    foreach ($terms as $tvalue) {
                        if (isset($data->{$svalue . "-" . $tvalue})) {
                            $setEligibilityData[$value->first_choice][$svalue . "-" . $tvalue] = $data->{$svalue . "-" . $tvalue}[0];
                        }
                        /*else
                            $setEligibilityData[$value->first_choice][$svalue."-".$tvalue] = 50;*/
                    }
                }
            }
        }
        foreach ($secondData as $value) {
            if (!in_array($value->second_choice, array_keys($setEligibilityData))) {
                $data = getSetEligibilityData($value->second_choice, 3);
                foreach ($subjects as $svalue) {
                    foreach ($terms as $tvalue) {
                        if (isset($data->{$svalue . "-" . $tvalue})) {
                            $setEligibilityData[$value->second_choice][$svalue . "-" . $tvalue] = $data->{$svalue . "-" . $tvalue}[0];
                        }
                        /*   else
                            $setEligibilityData[$value->second_choice][$svalue."-".$tvalue] = 50;*/
                    }
                }
            }
        }


        /* Get CDI Set Eligibility Data Set for first choice program and second choice program
         */

        $setCDIEligibilityData = array();
        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityData($value->first_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->first_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->first_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->first_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->first_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->first_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->first_choice]['susp_days'] = $data->SuspDays[0];
                }
            }
        }
        foreach ($secondData as $value) {
            if (!in_array($value->second_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityData($value->second_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->second_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->second_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->second_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->second_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->second_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->second_choice]['susp_days'] = $data->SuspDays[0];
                }
            }
        }
        /* Get CDI Data */
        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
            ->where('submissions.enrollment_id', Session::get('enrollment_id'))
            ->where('next_grade', $existGrade)
            ->where('submission_status', array("Active", "Pending"))
            //->limit(5)
            ->get();


        $firstdata = $seconddata = array();
        $programGrades = array();
        foreach ($submissions as $key => $value) {
            $failed = false;
            if (!isset($programGrades[$value->first_choice_program_id])) {
                $availableGrades = array();
                $eligibilityData = getEligibilitiesByProgram($value->first_choice_program_id, 'Academic Grade Calculation');
                if (isset($eligibilityData[0])) {
                    $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                    $programGrades[$value->first_choice_program_id] = $availableGrades;
                }
            }
            $skip = false;
            if ($value->first_choice_program_id != 0 && !in_array($value->next_grade, $programGrades[$value->first_choice_program_id])) {
                $skip = true;
            }

            if ($value->second_choice_program_id != '' && $value->second_choice_program_id != '0') {
                if (!isset($programGrades[$value->second_choice_program_id])) {
                    $availableGrades = array();
                    $eligibilityData = getEligibilitiesByProgram($value->second_choice_program_id, 'Academic Grade Calculation');
                    if (isset($eligibilityData[0])) {
                        $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                        $programGrades[$value->second_choice_program_id] = $availableGrades;
                    }
                }
                if (!in_array($value->next_grade, $programGrades[$value->second_choice_program_id])) {
                    $skip = true;
                }
            }

            $score = $this->collectionStudentGradeReport($value, $subjects, $terms, $value->next_grade, $skip, $setEligibilityData);
            if (count($score) <= 0) {
                $failed = true;
                $score = array();
                foreach ($subjects as $svalue) {
                    foreach ($terms as $svalue1) {
                        $score[$svalue][$svalue1] = "";
                    }
                }
            }

            if ($skip) {
                $cdiArr = array();
                $cdiArr['b_info'] = "NA";
                $cdiArr['c_info'] = "NA";
                $cdiArr['d_info'] = "NA";
                $cdiArr['e_info'] = "NA";
                $cdiArr['susp'] = "NA";
                $cdiArr['susp_days'] = "NA";
            } else {
                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                if (!empty($cdi_data)) {
                    $cdiArr = array();
                    $cdiArr['b_info'] = $cdi_data->b_info;
                    $cdiArr['c_info'] = $cdi_data->c_info;
                    $cdiArr['d_info'] = $cdi_data->d_info;
                    $cdiArr['e_info'] = $cdi_data->e_info;
                    $cdiArr['susp'] = $cdi_data->susp;
                    $cdiArr['susp_days'] = $cdi_data->susp_days;
                } elseif ($value->cdi_override == "Y") {
                    $cdiArr = array();
                    $cdiArr['b_info'] = 0;
                    $cdiArr['c_info'] = 0;
                    $cdiArr['d_info'] = 0;
                    $cdiArr['e_info'] = 0;
                    $cdiArr['susp'] = 0;
                    $cdiArr['susp_days'] = 0;
                } else {
                    $failed = true;
                    $cdiArr = array();
                    $cdiArr['b_info'] = "";
                    $cdiArr['c_info'] = "";
                    $cdiArr['d_info'] = "";
                    $cdiArr['e_info'] = "";
                    $cdiArr['susp'] = "";
                    $cdiArr['susp_days'] = "";
                }
            }
            if ($value->first_choice != "" && $value->second_choice != "") {


                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice'] = $value->first_choice;
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['second_program'] = "";
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                }
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;
                if (!isset($this->eligibility_grade_pass[$value->id]['first'])) {
                    $tmp['grade_status'] = "NA";
                } else {
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                }
                $firstdata[] = $tmp;

                if (!isset($this->eligibility_grade_pass[$value->id]['second'])) {
                    $tmp['grade_status'] = "NA";
                } else {
                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                }
                $tmp['rank'] = $this->priorityCalculate($value, "second");

                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else {
                    if ($failed == true) {
                        $tmp['cdi_status'] = "NA";
                    } else
                        $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                }

                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_program'] = "";
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                $seconddata[] = $tmp;
            } elseif ($value->first_choice != "") {
                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_program'] = "";
                $tmp['score'] = $score;
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['cdi'] = $cdiArr;
                $tmp['rank'] = $this->priorityCalculate($value, "first");
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else
                        if ($failed == true) {
                    $tmp['cdi_status'] = "NA";
                } else
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;
                if (!isset($this->eligibility_grade_pass[$value->id]['first'])) {
                    $tmp['grade_status'] = "NA";
                } else {
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                }
                $firstdata[] = $tmp;
            } else {
                $tmp = $this->convertToArray($value);
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['first_program'] = "";
                $tmp['score'] = $score;
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['cdi'] = $cdiArr;
                $tmp['rank'] = $this->priorityCalculate($value, "second");
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;
                if (!isset($this->eligibility_grade_pass[$value->id]['second'])) {
                    $tmp['grade_status'] = "NA";
                } else {
                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                }
                if ($value->cdi_override == "Y")
                    $tmp['cdi_status'] = "Pass";
                else
                        if ($failed == true) {
                    $tmp['cdi_status'] = "NA";
                } else
                    $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);


                $seconddata[] = $tmp;
            }
        }

        /*
        $fdata = array();
        $count = 0;
        foreach($firstdata as $key=>$value)
        {
            $score = $value['score'];
            $fdata[$count] = $value;
            $failcount = 0;
            foreach($score as $scrkey=>$scrvalue)
            {
                foreach($terms as $termkey=>$termvalue)
                {
                    if(!isset($score[$scrkey]))
                        $failcount++;
                    else
                    {
                       // echo $termkey."--".$value['first_choice']."--".$scrkey."-".$termvalue." -- ".$setEligibilityData[$value['first_choice']][$scrkey."-".$termvalue]."<BR>";
                        if(isset($setEligibilityData[$value['first_choice']][$scrkey."-".$termvalue]))
                        {
                            
                            if($score[$scrkey][$termvalue] >= $setEligibilityData[$value['first_choice']][$scrkey."-".$termvalue])
                              {

                              } 
                            else
                            {
                                 $failcount++;
                            }
                        }
                    }                    
                }
            }
            if($failcount > 0)
                $fdata[$count]['status'] = "Fail";
            else
                $fdata[$count]['status'] = "Pass";
            $count++;

        }*/

        /*echo "<pre>";
        print_r($firstdata);
        print_r($seconddata);
       exit;*/

        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }

        $tmpAvailability = $availabilityArray;
        $waitlistArr = $offeredRank = $firstOffered = array();

        foreach ($firstdata as $key => $value) {

            $rsT = SubmissionsFinalStatus::where("submission_id", $value['id'])->select("first_choice_final_status")->first();
            if (!empty($rsT))
                $status = $rsT->first_choice_final_status;
            else
                $status = "";
            if ($value['grade_status'] == "NA" || $value['cdi_status'] == "NA") {
                $firstdata[$key]['final_status'] = "<div class='alert1 alert-info'>Denied due to Incomplete Records</div>";
            } else {
                if ($status == "Offered")
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                elseif ($status == "Waitlisted")
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                elseif ($status == "Denied due to Ineligibility")
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Eligibility</div>";
            }
        }


        foreach ($seconddata as $key => $value) {

            $rsT = SubmissionsFinalStatus::where("submission_id", $value['id'])->select("second_choice_final_status")->first();
            if (!empty($rsT))
                $status = $rsT->second_choice_final_status;
            else
                $status = "";
            if ($value['grade_status'] == "NA" || $value['cdi_status'] == "NA") {
                $seconddata[$key]['final_status'] = "<div class='alert1 alert-info'>Denied due to Incomplete Records</div>";
            } else {
                if ($status == "Offered")
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                elseif ($status == "Waitlisted")
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                elseif ($status == "Denied due to Ineligibility")
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Eligibility</div>";
                //                else
                //                   echo $value['id']."<BR>";
            }
        }

        /*
echo "<pre>";
print_r($firstdata);
print_r($seconddata);

exit;*/
        if (str_contains(request()->url(), '/export')) {
            return $this->exportSubmissions($firstdata, $seconddata, $subjects, $terms);
        } else {
            return view("Reports::index", compact("firstdata", "seconddata", "existGrade", "gradeTab", "subjects", "terms", "setEligibilityData", "setCDIEligibilityData", "settings"));
        }
    }

    public function missing_index()
    {
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        return view("Reports::missing_index", compact('enrollment'));
    }

    public function admin_index()
    {
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $selection = "";
        $enrollment_id = Session::get("enrollment_id");
        return view("Reports::admin_review_index", compact('enrollment', "selection", "enrollment_id"));
    }

    /* Missing Grade Report */
    public function missing($program_id = 0)
    {
        $application_program = ApplicationProgram::get();
        $aprogram = $programs = array();
        $firstdata = $seconddata = array();
        foreach ($application_program as $key => $value) {
            if (!in_array($value->program_id, $programs)) {
                $programs[] = $value->program_id;
            }
            if ($value->program_id == $program_id) {
                $aprogram[] = $value->id;
            }
        }
        $setEligibilityData = array();
        $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))
            ->where("enrollment_id", Session::get("enrollment_id"))
            ->where(function ($q) use ($aprogram) {
                $q->whereIn("first_choice", $aprogram)
                    ->orWhereIn("second_choice", $aprogram);
            })->get();
        $subjects = $terms = array();
        //print_r($submissions);exit;
        $eligibilityArr = array();
        foreach ($submissions as $value) {
            $eligibilityData = getEligibilitiesByProgram($program_id, 'Academic Grade Calculation');
            if (count($eligibilityData) > 0) {
                if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                    $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                    // echo $eligibilityData[0]->id;exit;
                    $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                    if (!empty($content)) {
                        if ($content->scoring->type == "DD") {
                            $tmp = array();

                            foreach ($content->subjects as $value) {
                                if (!in_array($value, $subjects)) {
                                    $subjects[] = $value;
                                }
                            }

                            foreach ($content->terms_calc as $value) {
                                if (!in_array($value, $terms)) {
                                    $terms[] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }




        /* Get CDI Data */


        foreach ($submissions as $key => $value) {

            $score = $this->collectionStudentGrade($value->id, $subjects, $terms, "missing", $value->next_grade);

            $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
            if (!empty($cdi_data)) {
                $cdiArr = array();
                $cdiArr['b_info'] = $cdi_data->b_info;
                $cdiArr['c_info'] = $cdi_data->c_info;
                $cdiArr['d_info'] = $cdi_data->d_info;
                $cdiArr['e_info'] = $cdi_data->e_info;
                $cdiArr['susp'] = $cdi_data->susp;
                $cdiArr['susp_days'] = $cdi_data->susp_days;
            } else {
                $cdiArr = array();

                $data = DB::table("student_conduct_disciplinary")->where("stateID", $value->student_id)->first();
                if (!empty($data)) {
                    $cdi_data = [
                        'submission_id' => $value->id,
                        'b_info' => $data->b_info,
                        'c_info' => $data->c_info,
                        'd_info' => $data->d_info,
                        'e_info' => $data->e_info,
                        'susp' => $data->susp,
                        'susp_days' => $data->susp_days
                    ];
                    DB::table("submission_conduct_discplinary_info")->insert($cdi_data);
                    $cdiArr = array();
                    $cdiArr['b_info'] = $data->b_info;
                    $cdiArr['c_info'] = $data->c_info;
                    $cdiArr['d_info'] = $data->d_info;
                    $cdiArr['e_info'] = $data->e_info;
                    $cdiArr['susp'] = $data->susp;
                    $cdiArr['susp_days'] = $data->susp_days;
                } else {
                    $cdiArr['b_info'] = $cdiArr['c_info'] = $cdiArr['d_info'] = $cdi_data['d_info'] = $cdiArr['e_info'] = $cdiArr['susp'] = $cdiArr['susp_days'] = '<i class="fas fa-exclamation-circle text-danger"></i>';
                }
            }
            $tmp = $this->convertToArray($value);
            $tmp['submission_id'] = $value->id;
            $tmp['first_program'] = getApplicationProgramName($value->first_choice);
            $tmp['second_program'] = getApplicationProgramName($value->second_choice);
            $tmp['score'] = $score;
            $tmp['first_choice'] = $value->first_choice;
            $tmp['second_choice'] = $value->second_choice;
            $tmp['cdi'] = $cdiArr;
            $firstdata[] = $tmp;
        }

        $seconddata = array();
        if (str_contains(request()->url(), '/export')) {
            return $this->exportSubmissions($firstdata, $seconddata, $subjects, $terms);
        } else {
            return view("Reports::missing", compact("firstdata", "seconddata", "subjects", "terms", "programs", "program_id"));
        }
    }

    /* Manual Override Grades Functionality */
    public function manualOverride($enrollment_id = 0)
    {
        $district_conf_value = (Configuration::where('config_name', 'needed_grade_conf')->first()->config_value ?? '');
        $district_conf = [];
        if ($district_conf_value != '') {
            $district_conf_value = json_decode($district_conf_value, true);
            $disctict_conf['academic_year'] = array_key_first($district_conf_value);
            $disctict_conf['academic_grade'] = $district_conf_value[$disctict_conf['academic_year']][0];
        } else {
            $disctict_conf['academic_year'] = $disctict_conf['academic_grade'] = '';
        }
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $selection = "manual_grade_check";
        return view("Reports::manual_override", compact('enrollment_id', 'enrollment', 'disctict_conf', 'selection'));
    }

    public function manualOverrideResponse($enrollment_id = 0)
    {
        $request = request()->all();
        $academic_year = ($request['academic_year'] ?? '');
        $academic_grade = ($request['academic_grade'] ?? '');
        $district_conf_value = '';
        if (($academic_year != '') && $academic_grade != '') {
            $district_conf_value = json_encode(array($academic_year => array($academic_grade)));
            $disctict_conf_key_data = [
                'district_id' => session('district_id'),
                'config_name' => 'needed_grade_conf',
            ];
            $disctict_conf_data = [
                'config_value' => $district_conf_value,
            ];
            $district_conf = Configuration::updateOrCreate($disctict_conf_key_data, $disctict_conf_data);
        } else {
            $district_conf_value = (Configuration::where('config_name', 'needed_grade_conf')->first()->config_value ?? '');
        }
        if ($district_conf_value == '') {
            return response()->json(array('success' => true, 'html' => 'config_not_set'));
        } else {
            $needed_grade = json_decode($district_conf_value, true);
        }

        set_time_limit(0);
        //        $enrollment_id = Session::get("enrollment_id");
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))->where("enrollment_id", $enrollment_id)->whereIn('submission_status', array('Active', 'Pending'))->get(); //->where('student_id', '<>', '') ->whereIn("submissions.id", SubmissionGrade::selectRaw("DISTINCT(submission_id)")

        $application_ids = [];
        foreach ($submissions as $sk => $sv) {
            if (!in_array($sv->application_id, $application_ids)) {
                $application_ids[] = $sv->application_id;
            }
        }



        $subjects = $terms = $availableGrades = $eligibilityArr =  array();
        foreach ($submissions as $value) {
            $eligibilityData = getEligibilitiesByProgramDynamic($value->first_choice_program_id, $application_ids, 'Academic Grades');
            if (count($eligibilityData) > 0) {
                $availableGrades = array_merge(explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by), $availableGrades);
                if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                    $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                    $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);
                    //dd($content);
                    if (!empty($content)) {
                        // $tmp = array();
                        foreach ($content->academic_year_calc as $ayc_val) {
                            if (isset($content->terms_calc->{$ayc_val})) {
                                if (!isset($terms[$ayc_val])) {
                                    $terms[$ayc_val] = [];
                                }
                                foreach ($content->terms_calc->{$ayc_val} as $tc_value) {
                                    if (!in_array($tc_value, $terms[$ayc_val])) {
                                        array_push($terms[$ayc_val], $tc_value);
                                    }
                                }
                            }
                        }
                        foreach ($content->subjects as $svalue) {
                            if (!in_array($svalue, $subjects)) {
                                $subjects[] = $svalue;
                            }
                        }
                    }
                }
            }
            // dd($subjects, $terms);
            if ($value->second_choice_program_id > 0) {
                $eligibilityData = getEligibilitiesByProgramDynamic($value->second_choice_program_id, $application_ids, 'Academic Grades');
                if (count($eligibilityData) > 0) {
                    $availableGrades = array_merge(explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by), $availableGrades);
                    if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                        $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;

                        $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                        if (!empty($content)) {
                            // $tmp = array();
                            foreach ($content->academic_year_calc as $ayc_val) {
                                if (isset($content->terms_calc->{$ayc_val})) {
                                    if (!isset($terms[$ayc_val])) {
                                        $terms[$ayc_val] = [];
                                    }
                                    foreach ($content->terms_calc->{$ayc_val} as $tc_value) {
                                        if (!in_array($tc_value, $terms[$ayc_val])) {
                                            array_push($terms[$ayc_val], $tc_value);
                                        }
                                    }
                                }
                            }
                            foreach ($content->subjects as $value) {
                                if (!in_array($value, $subjects)) {
                                    $subjects[] = $value;
                                }
                            }
                        }
                    }
                }
            }
            // dd($subjects, $terms);
        }
        /*
        Array
(
    [0] => re
    [1] => eng
    [2] => math
    [3] => sci
    [4] => ss
)

    $terms = array()
Array
(
    [2020-2021] => Array
        (
            [0] => F1 Grade
        )

)
        echo "<pre>";
        print_r($subjects);
        print_r($terms);
        exit;*/

        // $needed_grade = array("2021-2022"=>array("Q1 Grade"));

        // dd($needed_grade);

        //$terms = array_merge($terms, $needed_grade);
        $config_subjects = Config::get('variables.subjects');

        $final_data = [];
        foreach ($submissions as $key => $value) {
            $data = [];
            $data['student_id'] = $value->student_id;
            $data['id'] = $value->id;
            $data['current_grade'] = $value->current_grade;

            $data['next_grade'] = $value->next_grade;
            $data['submission_status'] = $value->submission_status;
            $data['first_name'] = $value->first_name;
            $data['last_name'] = $value->last_name;
            $data['grade_override'] = $value->grade_override;
            $data['first_program'] = getProgramName($value->first_choice_program_id);
            $data['second_program'] = getProgramName($value->second_choice_program_id);
            $data['current_school'] = $value->current_school;





            $grade_data = [];

            $fail = false;

            foreach ($terms as $tkey => $tvalue) {
                $yr_data = [];
                foreach ($tvalue as $tk => $tv) {

                    foreach ($subjects as $skey => $svalue) {
                        $rs = SubmissionGrade::where("submission_id", $value->id)->where("courseType", $config_subjects[$svalue])->where("academicYear", $tkey)->where("GradeName", $tv)->first();
                        if (!empty($rs) && $rs->numericGrade < 70) {
                            $fail = true;
                            $marks = $rs->numericGrade;
                            $yr_data[$svalue][$tv] = $marks;
                        }
                    }
                }
                if (!empty($yr_data))
                    $grade_data[$tkey] = $yr_data;
            }

            if ($fail) {
                $grade_data = [];
                foreach ($needed_grade as $tkey => $tvalue) {
                    $yr_data = [];
                    foreach ($tvalue as $tk => $tv) {

                        foreach ($subjects as $skey => $svalue) {
                            $rs = SubmissionGrade::where("submission_id", $value->id)->where("courseType", $config_subjects[$svalue])->where("academicYear", $tkey)->where("GradeName", $tv)->first();
                            if (!empty($rs)) {
                                $marks = $rs->numericGrade;
                                $yr_data[$svalue][$tv] = $marks;
                            }
                        }
                    }
                    if (!empty($yr_data))
                        $grade_data[$tkey] = $yr_data;
                }
            }


            /*foreach($subjects as $skey=>$svalue)
            {
                $subjects_grade = [];
                foreach($terms as $tkey=>$tvalue)
                {
                    $yr_data = [];
                    foreach($tvalue as $tk=>$tv)
                    {
                        $rs = SubmissionGrade::where("submission_id", $value->id)->where("courseType", $config_subjects[$svalue])->where("academicYear", $tkey)->where("GradeName", $tv)->first();
                        if(!empty($rs))
                        {
                            $marks = $rs->numericGrade;
                        }
                        else
                        {
                            $marks = "-";
                        }
                        $yr_data[$tv] = $marks;

                    }
                    $subjects_grade[$config_subjects[$svalue]] = $yr_data;
                }

                $grade_data[$skey] = $subjects_grade;
                
            }*/

            $data['grade'] = $grade_data;
            $data['score'] = $grade_data;
            if (!empty($grade_data))
                $final_data[] = $data;
        }

        $terms = array_merge($terms, $needed_grade);
        $terms = $needed_grade;
        //echo request()->url();exit;
        if (str_contains(request()->url(), '/export/manual_grade_check')) {
            return $this->exportMissingGrade($final_data, $subjects, $terms);
        } else {
            $returnHTML =  view("Reports::manual_override_response", compact("final_data", "subjects", "terms"))->render();
        }
        return response()->json(array('success' => true, 'html' => $returnHTML));
    }

    /* Denied due to Ineligibility Report */
    public function allCDIReport($enrollment_id)
    {
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $selection = "allcdi";
        return view("Reports::all_cdi", compact('enrollment_id', 'enrollment', 'selection'));
    }
    public function allCDIReportResponse($enrollment_id = 0, $submission_type = '', $late_submission = 0)
    {

        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $program_id = 0;


        $aprogram = $programs = array();
        $firstdata = $seconddata = array();
        $setEligibilityData = $availableGrades = array();
        $setEligibilityData = array();


        if (is_string($submission_type) && $submission_type == "Non-Current") {

            $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))->where("enrollment_id", $enrollment_id)->where('cdi_override', 'N')->whereIn('submission_status', array("Active", "Pending"))->whereNull('student_id')->get();
        } else if (is_string($submission_type) && $submission_type == "Current") {
            $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))->where("enrollment_id", $enrollment_id)->where('cdi_override', 'N')->whereIn('submission_status', array("Active", "Pending"))->whereNotNull('student_id')->get();
        } else {
            $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))->where("enrollment_id", $enrollment_id)->whereIn('submission_status', array("Active", "Pending"))->where('cdi_override', 'N')->get();
        }


        foreach ($submissions as $value) {
            $eligibilityData = getEligibilitiesByProgram($value->first_choice_program_id, 'Conduct Disciplinary Info');
            if (count($eligibilityData) > 0) {
                $availableGrades = array_merge(explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by), $availableGrades);
            }
            if ($value->second_choice_program_id > 0) {
                $eligibilityData = getEligibilitiesByProgram($value->second_choice_program_id, 'Conduct Disciplinary Info');
                if (count($eligibilityData) > 0) {
                    $availableGrades = array_merge(explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by), $availableGrades);
                }
            }
        }
        $subjects = $terms = array();

        /* Get CDI Data */


        foreach ($submissions as $key => $value) {
            if (in_array($value->next_grade, $availableGrades)) {
                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                if (empty($cdi_data)) {


                    $cdi_data = [
                        'submission_id' => $value->id,
                        'b_info' => 0,
                        'c_info' => 0,
                        'd_info' => 0,
                        'e_info' => 0,
                        'susp' => 0,
                        'susp_days' => 0
                    ];
                    DB::table("submission_conduct_discplinary_info")->insert($cdi_data);
                    $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                }

                $cdiArr = array();
                $cdiArr['b_info'] = $cdi_data->b_info;
                $cdiArr['c_info'] = $cdi_data->c_info;
                $cdiArr['d_info'] = $cdi_data->d_info;
                $cdiArr['e_info'] = $cdi_data->e_info;
                $cdiArr['susp'] = $cdi_data->susp;
                $cdiArr['susp_days'] = $cdi_data->susp_days;

                $tmp = $this->convertToArray($value);
                $tmp['submission_id'] = $value->id;
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['cdi'] = $cdiArr;
                $firstdata[] = $tmp;
            }
        }
        $seconddata = $programs = array();


        if (str_contains(request()->url(), '/export/allcdi')) {
            return $this->exportMissingCDI($firstdata, "All-CDI.xlsx");
        } else {
            $returnHTML =  view("Reports::all_cdi_response", compact("firstdata", "seconddata", "programs", "program_id", "enrollment_id", "enrollment"))->render();
            return response()->json(array('success' => true, 'html' => $returnHTML));
        }
    }

    /* Denied due to Ineligibility Report */
    public function missingDeniedEligibilityIndex($enrollment_id)
    {
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $applications = Application::where("district_id", Session::get('district_id'))->where("enrollment_id", Session::get("enrollment_id"))->get();
        return view("Reports::denied_due_to_ineligibility_index", compact('enrollment_id', 'enrollment', 'applications'));
    }

    public function missingDeniedEligibilityMain($enrollment_id, $application_id)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $selection = "denied_due_to_ineligibility";
        return view("Reports::denied_due_to_ineligibility", compact('enrollment_id', 'enrollment', 'application_id', 'selection'));
    }

    public function missingDeniedEligibilityMainResponse($enrollment_id, $application_id)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $application_ids = array($application_id);

        $firstData = Submissions::distinct()->whereIn('submission_status', array("Active", "Pending"))->whereIn("application_id", $application_ids)->where("late_submission", "Y")->get(["first_choice"]);
        $calc_type = "DD";
        $academic_year = $calc_type_arr = $eligibilityArr = $subjects = $terms = [];
        $cdi_not_required = [];
        foreach ($firstData as $value) {
            if ($value->first_choice != "") {
                $eligibilityData_cd = getEligibilitiesDynamicProcessing($value->first_choice, 'Conduct Disciplinary Info');
                if (count($eligibilityData_cd) <= 0) {
                    $cdi_not_required[] = $value->first_choice;
                }
                //echo "FC".$value->first_choice."<BR>";
                $eligibilityData = getEligibilitiesDynamic($value->first_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                        $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                        // echo $eligibilityData[0]->id;exit;
                        $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                        if (!empty($content)) {
                            if ($content->scoring->type == "GA" || $content->scoring->type == "DD" || $content->scoring->type == "CLSG") {
                                $calc_type = $content->scoring->type;
                                $calc_type_arr[$value->first_choice] = $calc_type;
                                $tmp = array();

                                foreach ($content->academic_year_calc as $svalue) {
                                    if (!in_array($svalue, $academic_year)) {
                                        $academic_year[] = $svalue;
                                    }
                                }

                                foreach ($content->subjects as $svalue) {
                                    if (!in_array($svalue, $subjects)) {
                                        $subjects[] = $svalue;
                                    }
                                }

                                foreach ($content->terms_calc as $svalue) {
                                    if (!in_array($svalue, $terms)) {
                                        $terms[] = $svalue;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $secondData = Submissions::distinct()->whereIn('submission_status', array("Active", "Pending"))->whereIn("application_id", $application_ids)->get(["second_choice"]);
        foreach ($secondData as $value) {
            if ($value->second_choice != "") {
                $eligibilityData_cd = getEligibilitiesDynamicProcessing($value->second_choice, 'Conduct Disciplinary Info');
                if (count($eligibilityData_cd) <= 0) {
                    $cdi_not_required[] = $value->second_choice;
                }
                $eligibilityData = getEligibilitiesDynamic($value->second_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                    if (!empty($content)) {
                        if ($content->scoring->type == "DD" || $content->scoring->type == "CLSG" || $content->scoring->type == "GA") {
                            $calc_type = $content->scoring->type;
                            $calc_type_arr[$value->second_choice] = $calc_type;

                            $tmp = array();

                            foreach ($content->academic_year_calc as $svalue) {
                                if (!in_array($svalue, $academic_year)) {
                                    $academic_year[] = $svalue;
                                }
                            }

                            foreach ($content->subjects as $svalue) {
                                if (!in_array($svalue, $subjects)) {
                                    $subjects[] = $svalue;
                                }
                            }

                            foreach ($content->terms_calc as $svalue) {
                                if (!in_array($svalue, $terms)) {
                                    $terms[] = $svalue;
                                }
                            }
                        }
                    }
                }
            }
        }

        //dd($terms, $subjects, $academic_year);



        /* Get Set Eligibility Data Set for first choice program and second choice program
         */

        $setEligibilityData = array();
        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, array_keys($setEligibilityData))) {
                $data = getSetEligibilityData($value->first_choice, 3);

                foreach ($subjects as $svalue) {
                    foreach ($terms as $tvalue) {
                        if (isset($data->{$svalue})) {
                            $setEligibilityData[$value->first_choice][$svalue] = $data->{$svalue}[0];
                        } else
                            $setEligibilityData[$value->first_choice][$svalue] = 70;
                    }
                }
            }
        }
        foreach ($secondData as $value) {
            if (!in_array($value->second_choice, array_keys($setEligibilityData))) {
                $data = getSetEligibilityData($value->second_choice, 3);
                foreach ($subjects as $svalue) {
                    foreach ($terms as $tvalue) {
                        if (isset($data->{$svalue})) {
                            $setEligibilityData[$value->second_choice][$svalue] = $data->{$svalue}[0];
                        } else
                            $setEligibilityData[$value->second_choice][$svalue] = 70;
                    }
                }
            }
        }


        /* Get CDI Set Eligibility Data Set for first choice program and second choice program
         */

        $setCDIEligibilityData = array();
        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityData($value->first_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->first_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->first_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->first_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->first_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->first_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->first_choice]['susp_days'] = $data->SuspDays[0];
                } else {
                    $setCDIEligibilityData[$value->first_choice]['b_info'] = 5;
                    $setCDIEligibilityData[$value->first_choice]['c_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['d_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['e_info'] = 0;
                    $setCDIEligibilityData[$value->first_choice]['susp'] = 2;
                    $setCDIEligibilityData[$value->first_choice]['susp_days'] = 4;
                }
            }
        }
        foreach ($secondData as $value) {
            if (!in_array($value->second_choice, array_keys($setCDIEligibilityData))) {
                $data = getSetEligibilityData($value->second_choice, 8);
                if (!empty($data)) {
                    $setCDIEligibilityData[$value->second_choice]['b_info'] = $data->B[0];
                    $setCDIEligibilityData[$value->second_choice]['c_info'] = $data->C[0];
                    $setCDIEligibilityData[$value->second_choice]['d_info'] = $data->D[0];
                    $setCDIEligibilityData[$value->second_choice]['e_info'] = $data->E[0];
                    $setCDIEligibilityData[$value->second_choice]['susp'] = $data->Susp[0];
                    $setCDIEligibilityData[$value->second_choice]['susp_days'] = $data->SuspDays[0];
                } else {
                    $setCDIEligibilityData[$value->second_choice]['b_info'] = 5;
                    $setCDIEligibilityData[$value->second_choice]['c_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['d_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['e_info'] = 0;
                    $setCDIEligibilityData[$value->second_choice]['susp'] = 2;
                    $setCDIEligibilityData[$value->second_choice]['susp_days'] = 4;
                }
            }
        }


        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
            ->whereIn('submission_status', array("Active"))
            ->where("enrollment_id", Session::get("enrollment_id"))
            //->where("id", 6284)
            ->where('submissions.application_id', $application_id)
            ->get();




        $firstdata = $seconddata = array();
        $programGrades = array();
        foreach ($submissions as $key => $value) {
            if (!isset($programGrades[$value->first_choice_program_id])) {
                $availableGrades = array();
                $eligibilityData = getEligibilitiesByProgramDynamic($value->first_choice_program_id, $application_ids, 'Academic Grade Calculation');
                if (isset($eligibilityData[0])) {
                    $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                    $programGrades[$value->first_choice_program_id] = $availableGrades;
                } else {
                    $programGrades[$value->first_choice_program_id] = [];
                }
            }
            $skip = false;
            if ($value->first_choice_program_id != 0 && !in_array($value->next_grade, $programGrades[$value->first_choice_program_id])) {
                $skip = true;
            }

            if ($value->second_choice_program_id != '' && $value->second_choice_program_id != '0') {
                if (!isset($programGrades[$value->second_choice_program_id])) {
                    $availableGrades = array();
                    $eligibilityData = getEligibilitiesByProgramDynamic($value->second_choice_program_id, $application_ids, 'Academic Grade Calculation');
                    if (isset($eligibilityData[0])) {
                        $availableGrades = explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by);
                        $programGrades[$value->second_choice_program_id] = $availableGrades;
                    } else {
                        $programGrades[$value->second_choice_program_id] = [];
                    }
                }
                if (!in_array($value->next_grade, $programGrades[$value->second_choice_program_id])) {
                    $skip = true;
                }
            }

            $score = $this->collectionStudentGradeReportDynamicForIneligible($value, $academic_year, $subjects, $terms, $value->next_grade, $skip, $setEligibilityData, $calc_type);


            $cdi_status = true;
            $skip = true;
            if ($skip) {
                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                if (!empty($cdi_data)) {
                    $cdiArr = array();
                    $cdiArr['b_info'] = $cdi_data->b_info;
                    $cdiArr['c_info'] = $cdi_data->c_info;
                    $cdiArr['d_info'] = $cdi_data->d_info;
                    $cdiArr['e_info'] = $cdi_data->e_info;
                    $cdiArr['susp'] = $cdi_data->susp;
                    $cdiArr['susp_days'] = $cdi_data->susp_days;
                } elseif ($value->cdi_override == "Y" || (in_array($value->first_choice, $cdi_not_required) || in_array($value->second_choice, $cdi_not_required))) {
                    $cdiArr = array();
                    $cdiArr['b_info'] = 0;
                    $cdiArr['c_info'] = 0;
                    $cdiArr['d_info'] = 0;
                    $cdiArr['e_info'] = 0;
                    $cdiArr['susp'] = 0;
                    $cdiArr['susp_days'] = 0;
                } else {
                    $cdiArr = array();
                    $cdiArr['b_info'] = "<span class='text-center'><i class='fas fa-exclamation-circle text-danger'></i></span>";
                    $cdiArr['c_info'] = "<span class='text-center'><i class='fas fa-exclamation-circle text-danger'></i></span>";
                    $cdiArr['d_info'] = "<span class='text-center'><i class='fas fa-exclamation-circle text-danger'></i></span>";
                    $cdiArr['e_info'] = "<span class='text-center'><i class='fas fa-exclamation-circle text-danger'></i></span>";
                    $cdiArr['susp'] = "<span class='text-center'><i class='fas fa-exclamation-circle text-danger'></i></span>";
                    $cdiArr['susp_days'] = "<span class='text-center'><i class='fas fa-exclamation-circle text-danger'></i></span>";
                    $cdi_status = false;
                }



                if ($value->first_choice != "" && $value->second_choice != "") {

                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['second_program'] = "";
                    $tmp['score'] = $score;

                    $tmp['cdi'] = $cdiArr;
                    if ($value->cdi_override == "Y")
                        $tmp['cdi_status'] = "Pass";
                    else {
                        if (in_array($value->first_choice, $cdi_not_required))
                            $tmp['cdi_status'] = "Pass";
                        elseif ($cdi_status)
                            $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                        else
                            $tmp['cdi_status'] = "Fail";
                    }
                    $tmp['rank'] = 0; //$this->priorityCalculate($value, "first");
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    //dd($tmp);
                    if ($tmp['cdi_status'] == "Fail" || $tmp['grade_status'] == "Fail")
                        $firstdata[] = $tmp;



                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    $tmp['rank'] = 0; //$this->priorityCalculate($value, "second");
                    if ($value->cdi_override == "Y")
                        $tmp['cdi_status'] = "Pass";
                    else {
                        if (in_array($value->second_choice, $cdi_not_required))
                            $tmp['cdi_status'] = "Pass";
                        elseif ($cdi_status)
                            $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                        else
                            $tmp['cdi_status'] = "Fail";
                    }
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['cdi'] = $cdiArr;

                    if ($tmp['cdi_status'] == "Fail" || $tmp['grade_status'] == "Fail")
                        $seconddata[] = $tmp;
                } elseif ($value->first_choice != "") {
                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;


                    $tmp['cdi'] = $cdiArr;
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($value->cdi_override == "Y")
                        $tmp['cdi_status'] = "Pass";
                    else {
                        if (in_array($value->first_choice, $cdi_not_required))
                            $tmp['cdi_status'] = "Pass";
                        elseif ($cdi_status)
                            $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
                        else
                            $tmp['cdi_status'] = "Fail";
                    }
                    if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }

                    //dd($tmp);
                    if ($tmp['cdi_status'] == "Fail" || $tmp['grade_status'] == "Fail")
                        $firstdata[] = $tmp;
                } else {
                    $tmp = $this->convertToArray($value);
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['first_program'] = "";
                    $tmp['score'] = $score;
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;

                    $tmp['cdi'] = $cdiArr;

                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass") {
                        $tmp['grade_status'] = "Pass";
                    } else {
                        $tmp['grade_status'] = "Fail";
                    }
                    if (in_array($value->second_choice, $cdi_not_required))
                        $tmp['cdi_status'] = "Pass";
                    else {
                        if (in_array($value->second_choice, $cdi_not_required))
                            $tmp['cdi_status'] = "Pass";
                        elseif ($cdi_status)
                            $tmp['cdi_status'] = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                        else
                            $tmp['cdi_status'] = "Fail";
                    }
                    if ($tmp['cdi_status'] == "Fail" || $tmp['grade_status'] == "Fail")
                        $seconddata[] = $tmp;
                }
            }
        }


        if (str_contains(request()->url(), '/export/missinggrade')) {
            return $this->exportMissingGrade($firstdata, $subjects, $terms);
        } else {

            $returnHTML =  view("Reports::denied_due_to_ineligibility_response", compact("academic_year", "firstdata", "seconddata", "subjects", "terms",  "enrollment_id"))->render();
            return response()->json(array('success' => true, 'html' => $returnHTML));
        }
    }
    /* Missing Grade Report */
    public function missingGradeMain($enrollment_id, $cgrade = 3, $late_submission = 0)
    {
        set_time_limit(0);
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $display_outcome = SubmissionsStatusUniqueLog::whereIn("submission_id", Submissions::where("enrollment_id", Session::get("enrollment_id"))->select("id")->get()->toArray())->count();

        $selection = "grade";
        return view("Reports::missing_grade", compact('enrollment_id', 'enrollment', 'display_outcome', 'late_submission', "selection", "cgrade"));
    }

    public function missingGrade($enrollment_id = 0, $cgrade = 3, $submission_type = '', $late_submission = 0)
    {
        set_time_limit(-1);
        //        ini_set('memory_limi', '4096M');
        $program_id = 0;
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $aprogram = $programs = array();
        $firstdata = $seconddata = array();

        /*$application_program = ApplicationProgram::join('application', 'application.id', 'application_programs.application_id')->where('application.district_id', Session::get('district_id'))->select('application_programs.id', 'application_programs.program_id')->get();
        
        
        foreach($application_program as $key=>$value)
        {
            if(!in_array($value->program_id, $programs))
            {
                $programs[] = $value->program_id;
            }
                $aprogram[] = $value->id;
        
        }*/

        $setEligibilityData = array();
        //$submission_type = 'Current';
        if (is_string($submission_type) && $submission_type == "Non-Current") {
            $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))->where("enrollment_id", $enrollment_id)->whereNull('student_id')->whereIn('submission_status', array('Pending'))->where('grade_override', 'N')->get();
        } else if (is_string($submission_type) && $submission_type == "Current") {
            $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))->where("enrollment_id", $enrollment_id)->whereNotNull('student_id')->whereIn('submission_status', array('Active'))->where('grade_override', 'N')->get();
        } else {
            $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))->whereIn('submission_status', array('Active', 'Pending'))->where('grade_override', 'N')->where("enrollment_id", $enrollment_id)->get();
        }

        $application_ids = [];
        foreach ($submissions as $sk => $sv) {
            if (!in_array($sv->application_id, $application_ids)) {
                $application_ids[] = $sv->application_id;
            }
        }

        $subjects = $terms = array();
        $eligibilityArr = array();

        $availableGrades = array();
        // $program_id = $programs[0];


        // foreach($submissions as $value)
        // {
        //     $eligibilityData = getEligibilitiesByProgramDynamic($value->first_choice_program_id, $application_ids, 'Academic Grades');
        //     if(count($eligibilityData) > 0)
        //     {
        //         $availableGrades = array_merge(explode(",",$eligibilityData[0]->grade_lavel_or_recommendation_by), $availableGrades); 
        //         if(!in_array($eligibilityData[0]->id, $eligibilityArr))
        //         {
        //             $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
        //             $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);
        //              //dd($content);
        //             if(!empty($content))
        //             {
        //                 // $tmp = array();
        //                 foreach($content->academic_year_calc as $ayc_val) {
        //                     if(isset($content->terms_calc->{$ayc_val})) {
        //                         if(!isset($terms[$ayc_val])) {
        //                             $terms[$ayc_val] = [];
        //                         }
        //                         foreach ($content->terms_calc->{$ayc_val} as $tc_value) {
        //                             if (!in_array($tc_value, $terms[$ayc_val])) {
        //                                 array_push($terms[$ayc_val], $tc_value);
        //                             }
        //                         }
        //                     }
        //                 }
        //                 foreach($content->subjects as $svalue)
        //                 {
        //                     if(!in_array($svalue, $subjects))
        //                     {
        //                         $subjects[] = $svalue;
        //                     }
        //                 }
        //             }                        
        //         }

        //     }

        //     if($value->second_choice_program_id > 0)
        //     {
        //         $eligibilityData = getEligibilitiesByProgramDynamic($value->second_choice_program_id, $application_ids, 'Academic Grades');
        //         if(count($eligibilityData) > 0)
        //         {
        //             $availableGrades = array_merge(explode(",",$eligibilityData[0]->grade_lavel_or_recommendation_by), $availableGrades); 
        //             if(!in_array($eligibilityData[0]->id, $eligibilityArr))
        //             {
        //                 $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;

        //                 $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

        //                 if(!empty($content))
        //                 {
        //                     // $tmp = array();
        //                     foreach($content->academic_year_calc as $ayc_val) {
        //                         if(isset($content->terms_calc->{$ayc_val})) {
        //                             if(!isset($terms[$ayc_val])) {
        //                                 $terms[$ayc_val] = [];
        //                             }
        //                             foreach ($content->terms_calc->{$ayc_val} as $tc_value) {
        //                                 if (!in_array($tc_value, $terms[$ayc_val])) {
        //                                     array_push($terms[$ayc_val], $tc_value);
        //                                 }
        //                             }
        //                         }
        //                     }
        //                     foreach($content->subjects as $value)
        //                     {
        //                         if(!in_array($value, $subjects))
        //                         {
        //                             $subjects[] = $value;
        //                         }
        //                     }
        //                 }                        
        //             }

        //         }

        //     }
        //     // dd($subjects, $terms);
        // }


        $availableGrades = array("2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12");
        $availableGrades = array();
        $availableGrades[] = $cgrade;
        $subjects = array("re", "eng", "math", "sci", "ss");
        $terms = array("2023-2024" => array("F1 Grade"));

        //$subjects = ["re", "eng", "math", "sci", "ss"];
        //$terms = ["2022-2023" => ["Q1 Grade", "Q2 Grade"]];

        //      dd($subjects, $terms);
        //print_r($terms);exit;

        /* Get CDI Data */
        foreach ($submissions as $key => $value) {
            if (in_array($value->next_grade, $availableGrades)) {
                $score = $this->collectionStudentGrade($value->id, $subjects, $terms, "missing", $value->next_grade);
                //dd($score);
                if (!empty($score)) {
                    $tmp = $this->convertToArray($value);
                    $tmp['score'] = $score;
                    $tmp['submission_id'] = $value->id;
                    $tmp['first_program'] = getProgramName($value->first_choice_program_id);
                    $tmp['second_program'] = getProgramName($value->second_choice_program_id);
                    $tmp['score'] = $score;
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;
                    $firstdata[] = $tmp;
                }
            }
        }
        //dd($firstdata);
        //          print_r($subjects);exit;
        $seconddata = $programs = array();
        if (str_contains(request()->url(), '/export/missinggrade')) {
            return $this->exportMissingGrade($firstdata, $subjects, $terms);
        } else {
            $display_outcome = SubmissionsStatusUniqueLog::count();
            $returnHTML =  view("Reports::missing_grade_response", compact("firstdata", "seconddata", "subjects", "terms", "programs", "program_id", "enrollment_id", "enrollment", "display_outcome", "late_submission"))->render();
            return response()->json(array('success' => true, 'html' => $returnHTML));
        }
    }

    /* Missing Grade Report */
    public function missingGradeCopy($program_id = 0)
    {

        $application_program = ApplicationProgram::get();
        $aprogram = $programs = array();
        $firstdata = $seconddata = array();
        foreach ($application_program as $key => $value) {
            if (!in_array($value->program_id, $programs) && $value->program_id != 7) {
                $programs[] = $value->program_id;
            }
            if ($value->program_id == $program_id) {
                $aprogram[] = $value->id;
            }
        }
        $setEligibilityData = array();
        $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))
            ->where(function ($q) use ($aprogram) {
                $q->whereIn("first_choice", $aprogram)
                    ->orWhereIn("second_choice", $aprogram);
            })->get();
        $subjects = $terms = array();
        //print_r($submissions);exit;
        $eligibilityArr = array();
        foreach ($submissions as $value) {
            $eligibilityData = getEligibilitiesByProgram($program_id, 'Academic Grade Calculation');
            if (count($eligibilityData) > 0) {
                if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                    $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                    // echo $eligibilityData[0]->id;exit;
                    $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                    if (!empty($content)) {
                        if ($content->scoring->type == "DD") {
                            $tmp = array();

                            foreach ($content->subjects as $value) {
                                if (!in_array($value, $subjects)) {
                                    $subjects[] = $value;
                                }
                            }

                            foreach ($content->terms_calc as $value) {
                                if (!in_array($value, $terms)) {
                                    $terms[] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }




        /* Get CDI Data */


        foreach ($submissions as $key => $value) {

            $score = $this->collectionStudentGrade($value->id, $subjects, $terms, "missing");

            if (!empty($score)) {
                $tmp = $this->convertToArray($value);
                $tmp['score'] = $score;
                $tmp['submission_id'] = $value->id;
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['score'] = $score;
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $firstdata[] = $tmp;
            }
        }

        $seconddata = array();
        if (str_contains(request()->url(), '/export')) {
            return $this->exportSubmissions($firstdata, $seconddata, $subjects, $terms);
        } else {
            return view("Reports::missing_grade", compact("firstdata", "seconddata", "subjects", "terms", "programs", "program_id", 'enrollment_id', 'enrollment'));
        }
    }

    public function mcpssSubmissions($enrollment_id = 0)
    {
        $display_outcome = SubmissionsStatusUniqueLog::whereIn("submission_id", Submissions::where("enrollment_id", Session::get("enrollment_id"))->select("id")->get()->toArray())->count();


        if ($enrollment_id == 0) {
            $enrollment_id = Session::get('enrollment_id');
        }

        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();

        //$firstdata = Submissions::where("submissions.district_id", Session::get("district_id"))->where("mcp_employee", "Yes")->join("application", "application.id","submissions.application_id")->select("submissions.*")->where("application.enrollment_id", $enrollment_id)->get();

        $firstdata = Submissions::join('application', 'application.id', 'submissions.application_id')
            ->join('enrollments', 'enrollments.id', 'application.enrollment_id')
            ->where('submissions.district_id', Session::get('district_id'))
            ->where('submissions.enrollment_id', $enrollment_id)
            ->whereIn('submission_status', array('Active', 'Pending'))
            ->where("mcp_employee", "Yes")
            ->select('submissions.*', 'enrollments.school_year')
            ->orderBy('created_at', 'desc')
            ->get();
        $selection = "mcpss";
        return view("Reports::mcpss_employee", compact("firstdata", "enrollment_id", "enrollment", "display_outcome", "selection"));
    }

    /* Missing CDI Report */
    public function missingCDIMain($enrollment_id, $late_submission = 0)
    {
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $display_outcome = SubmissionsStatusUniqueLog::whereIn("submission_id", Submissions::where("enrollment_id", Session::get("enrollment_id"))->select("id")->get()->toArray())->count();

        $selection = "cdi";
        return view("Reports::missing_cdi", compact('enrollment_id', 'enrollment', 'display_outcome', 'late_submission', 'selection'));
    }
    public function missingCDI($enrollment_id = 0, $submission_type = '', $late_submission = 0)
    {
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $program_id = 0;
        /*$application_program = ApplicationProgram::join('application', 'application.id', 'application_programs.application_id')->where('application.district_id', Session::get('district_id'))->select('application_programs.id', 'application_programs.program_id')->get();
        
        
        foreach($application_program as $key=>$value)
        {
            if(!in_array($value->program_id, $programs))
            {
                $programs[] = $value->program_id;
            }
                $aprogram[] = $value->id;
        
        }*/

        $aprogram = $programs = array();
        $firstdata = $seconddata = array();
        $setEligibilityData = $availableGrades = array();
        $setEligibilityData = array();

        if ($late_submission == 1)
            $late = "Y";
        else
            $late = "N";
        if (is_string($submission_type) && $submission_type == "Non-Current") {

            $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))->where("enrollment_id", $enrollment_id)->where('cdi_override', 'N')->whereIn('submission_status', array("Active", "Pending"))->whereNull('student_id')->get();
        } else if (is_string($submission_type) && $submission_type == "Current") {
            $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))->where("enrollment_id", $enrollment_id)->where('cdi_override', 'N')->whereIn('submission_status', array("Active", "Pending"))->whereNotNull('student_id')->get();
        } else {
            $submissions = Submissions::where('submissions.district_id', (Session::get('district_id') != 0 ? Session::get('district_id') : 3))->where("enrollment_id", $enrollment_id)->whereIn('submission_status', array("Active", "Pending"))->where('cdi_override', 'N')->get();
        }


        foreach ($submissions as $value) {
            $eligibilityData = getEligibilitiesByProgram($value->first_choice_program_id, 'Conduct Disciplinary Info');
            if (count($eligibilityData) > 0) {
                $availableGrades = array_merge(explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by), $availableGrades);
            }
            if ($value->second_choice_program_id > 0) {
                $eligibilityData = getEligibilitiesByProgram($value->second_choice_program_id, 'Conduct Disciplinary Info');
                if (count($eligibilityData) > 0) {
                    $availableGrades = array_merge(explode(",", $eligibilityData[0]->grade_lavel_or_recommendation_by), $availableGrades);
                }
            }
        }
        $subjects = $terms = array();

        /* Get CDI Data */


        foreach ($submissions as $key => $value) {
            if (in_array($value->next_grade, $availableGrades)) {
                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                if (empty($cdi_data)) {
                    $cdiArr = array();

                    $data = DB::table("student_conduct_disciplinary")->where("stateID", $value->student_id)->where('stateID', '<>', '')->first();
                    if (!empty($data)) {
                        $cdi_data = [
                            'submission_id' => $value->id,
                            'b_info' => $data->b_info,
                            'c_info' => $data->c_info,
                            'd_info' => $data->d_info,
                            'e_info' => $data->e_info,
                            'susp' => $data->susp,
                            'susp_days' => $data->susp_days
                        ];
                        DB::table("submission_conduct_discplinary_info")->insert($cdi_data);
                    } else {
                        $cdiArr['b_info'] = $cdiArr['c_info'] = $cdiArr['d_info'] = $cdi_data['d_info'] = $cdiArr['e_info'] = $cdiArr['susp'] = $cdiArr['susp_days'] = '<i class="fas fa-exclamation-circle text-danger"></i>';
                        $tmp = $this->convertToArray($value);
                        $tmp['submission_id'] = $value->id;
                        $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                        $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                        $tmp['first_choice'] = $value->first_choice;
                        $tmp['second_choice'] = $value->second_choice;
                        $tmp['cdi'] = $cdiArr;
                        $firstdata[] = $tmp;
                    }
                }
            }
        }
        $seconddata = $programs = array();


        if (str_contains(request()->url(), '/export/missingcdi')) {
            return $this->exportMissingCDI($firstdata);
        } else {
            $display_outcome = SubmissionsStatusUniqueLog::whereIn("submission_id", Submissions::where("enrollment_id", Session::get("enrollment_id"))->select("id")->get()->toArray())->count();

            $returnHTML =  view("Reports::missing_cdi_response", compact("firstdata", "seconddata", "subjects", "terms", "programs", "program_id", "enrollment_id", "enrollment", "display_outcome", "late_submission"))->render();
            return response()->json(array('success' => true, 'html' => $returnHTML));
        }
    }



    public function collectionStudentGrade($submission_id, $subjects, $terms, $type = "", $next_grade = 0)
    {

        $config_subjects = Config::get('variables.subjects');
        $score = array();
        $missing = false;

        $sub = Submissions::where("id", $submission_id)->first();
        // if($sub->late_submission == "Y")
        //     $yr = "2020-2021";
        // else
        //     $yr = "2019-2020";
        $gradeInfo = (object) array("application_id" => 89, "grade" => 7, "english" => 'Y', "reading" => 'N', "science" => 'Y', "social_studies" => 'Y', "math" => 'Y', "year" => '');
        //        $gradeInfo = SubjectManagement::where("grade", $next_grade)->where("application_id", $sub->application_id)->first();
        //print_r($gradeInfo);exit;
        foreach ($terms as $tyear => $tvalue) {
            $yr = $tyear;
            foreach ($subjects as $value) {
                foreach ($tvalue as $value1) {

                    $marks = getSubmissionAcademicScore($submission_id, $config_subjects[$value], $value1, $yr, $yr);
                    //		    dd($marks);
                    if ($type == "missing") {
                        if ($marks == 0) {
                            if (!empty($gradeInfo)) {
                                $grade_yrs = $yr ?? '';
                                $yrs_ary = explode(',', $grade_yrs);

                                $field = strtolower(str_replace(" ", "_", $config_subjects[$value]));
                                if ($gradeInfo->{$field} == "N") {
                                    $score[$yr][$value][$value1] = "NA";
                                } else if (!in_array($yr, $yrs_ary)) {
                                    $score[$yr][$value][$value1] = "NA";
                                } else {
                                    $score[$yr][$value][$value1] = '<i class="fas fa-exclamation-circle text-danger"></i>';
                                    $missing = true;
                                }
                            } else {
                                $score[$yr][$value][$value1] = '<i class="fas fa-exclamation-circle text-danger"></i>';
                                $missing = true;
                            }
                        } else
                            $score[$yr][$value][$value1] = $marks;
                    } else
                        $score[$yr][$value][$value1] = $marks;
                }
            }
        }
        //dd($score);
        if ($type == "missing") {
            if ($missing == true) {
                return $score;
            } else
                return array();
        } else
            return $score;
    }


    public function collectionStudentGradeReport($submission, $subjects, $terms, $next_grade = 0, $skip = false, $setEligibilityData)
    {
        $config_subjects = Config::get('variables.subjects');
        $score = array();
        $missing = false;
        /*
         if($next_grade == "K" || $next_grade == "PreK")
            $gd = "0";
        elseif($next_grade == "1")
            $gd = "PreK";
        elseif($next_grade == "2")
            $gd = "K";
        else
            $gd = $next_grade-2;
            */
        $gradeInfo = SubjectManagement::where("grade", $next_grade)->first();
        $import_academic_year = Config::get('variables.import_academic_year');
        $first_failed = $second_failed = 0;
        foreach ($subjects as $value) {
            foreach ($terms as $value1) {
                if ($submission->late_submission == "Y")
                    $import_academic_year = "2020-2021";
                else
                    $import_academic_year = "2019-2020";

                $marks = getSubmissionAcademicScoreMissing($submission->id, $config_subjects[$value], $value1, $import_academic_year, $import_academic_year);

                /* Here copy above function if condition  for NA */

                if ($marks == "NA") {
                    if ($skip || $submission->grade_override == "Y") {
                        $score[$value][$value1] = "NA";
                    } else {
                        if (!empty($gradeInfo)) {
                            $field = strtolower(str_replace(" ", "_", $config_subjects[$value]));
                            if ($gradeInfo->{$field} == "N") {
                                $score[$value][$value1] = "NA";
                            } else {
                                return array();
                            }
                        } else {
                            return array();
                        }
                    }
                } else {
                    if (isset($setEligibilityData[$submission->first_choice][$value . "-" . $value1])) {
                        if ($setEligibilityData[$submission->first_choice][$value . "-" . $value1] > $marks) {
                            $first_failed++;
                        }
                    }

                    if (isset($setEligibilityData[$submission->second_choice][$value . "-" . $value1])) {
                        if ($setEligibilityData[$submission->second_choice][$value . "-" . $value1] > $marks) {
                            $second_failed++;
                        }
                    }
                    $score[$value][$value1] = $marks;
                }
            }
        }

        if ($first_failed > 0 && $submission->grade_override == "N") {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Fail";
        } else {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Pass";
        }

        if ($second_failed > 0 && $submission->grade_override == "N") {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Fail";
        } else {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Pass";
        }


        return $score;
    }


    public function collectionStudentGradeReportLateSubmission($submission, $subjects, $terms, $next_grade = 0, $skip = false, $setEligibilityData)
    {
        $config_subjects = Config::get('variables.subjects');
        $score = array();
        $missing = false;
        /*
         if($next_grade == "K" || $next_grade == "PreK")
            $gd = "0";
        elseif($next_grade == "1")
            $gd = "PreK";
        elseif($next_grade == "2")
            $gd = "K";
        else
            $gd = $next_grade-2;
            */
        $gradeInfo = SubjectManagement::where("grade", $next_grade)->first();
        $import_academic_year = Config::get('variables.import_academic_year');
        $first_failed = $second_failed = 0;
        foreach ($subjects as $value) {
            $avgcnt = $avgmarks = 0;
            $str = "";
            $na = false;
            foreach ($terms as $value1) {
                $tt = explode("-", $value1);
                foreach ($tt as $tv) {
                    if ($tv != "Q4.4 Final Grade") {
                        $marks = getSubmissionAcademicScoreMissing($submission->id, $config_subjects[$value], $tv, "2020-2021", "2020-2021");
                        //echo $submission->id."-".$config_subjects[$value]." - ".$tv."-".$marks."<BR>";exit;
                        /* Here copy above function if condition  for NA */
                        $str .= $tv . "-";
                        if ($marks == "NA") {
                            if ($skip || $submission->grade_override == "Y") {
                                $score[$value][$tv] = "NA";
                                $na = true;
                            } else {
                                if (!empty($gradeInfo)) {
                                    $field = strtolower(str_replace(" ", "_", $config_subjects[$value]));
                                    if ($gradeInfo->{$field} == "N") {
                                        $score[$value][$tv] = "NA";
                                        $na = true;
                                    } else {
                                        return array();
                                    }
                                } else {
                                    return array();
                                }
                            }
                        } else {
                            $avgmarks += $marks;
                        }
                        $avgcnt++;
                    }
                }
            }

            if ($avgcnt > 0 && !$na) {
                $marks = number_format($avgmarks / $avgcnt, 2);
            } elseif (!$na) {
                $marks = 0;
            }
            $str = trim($str, "-");



            if (isset($setEligibilityData[$submission->first_choice][$value . "-" . str_replace(" Qtr Grade", "", $str)])) {
                if ($marks != "NA" && $marks > 0 && $setEligibilityData[$submission->first_choice][$value . "-" . str_replace(" Qtr Grade", "", $str)] > $marks) {
                    //echo $marks . " - ".($value."-".str_replace(" Qtr Grade", "", $str));exit;
                    $first_failed++;
                }
            }

            if (isset($setEligibilityData[$submission->second_choice][$value . "-" . str_replace(" Qtr Grade", "", $str)])) {
                if ($marks != "NA" && $marks > 0 && $setEligibilityData[$submission->second_choice][$value . "-" . str_replace(" Qtr Grade", "", $str)] > $marks) {
                    $second_failed++;
                }
            }


            $score[$value][$str] = $marks;
        }


        if ($first_failed > 0 && $submission->grade_override == "N") {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Fail";
        } else {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Pass";
        }

        if ($second_failed > 0 && $submission->grade_override == "N") {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Fail";
        } else {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Pass";
        }

        return $score;
    }


    public function convertToArray($value)
    {
        $tmp = array();
        $tmp['id'] = $value->id;
        $tmp['student_id'] = $value->student_id;
        $tmp['first_name'] = $value->first_name;
        $tmp['last_name'] = $value->last_name;
        $tmp['next_grade'] = $value->next_grade;
        $tmp['current_grade'] = $value->current_grade;
        $tmp['zoned_school'] = $value->zoned_school;
        $tmp['current_school'] = $value->current_school;
        $tmp['lottery_number'] = $value->lottery_number;
        $tmp['race'] = $value->race;
        $tmp['submission_status'] = $value->submission_status;
        $tmp['first_sibling'] = $value->first_sibling;
        $tmp['second_sibling'] = $value->second_sibling;
        return $tmp;
    }


    public function exportSubmissions($firstdata, $seconddata, $subjects, $terms)
    {
        $config_subjects = Config::get('variables.subjects');
        $data_ary = [];
        $heading = array(
            "Submission ID",
            "Submission Status",
            "Race",
            "State ID",
            "Last Name",
            "First Name",
            "Next Grade",
            "Current School",
            "Zoned School",
            "First Choice",
            "Second Choice",
            "Sibling ID",
            "Lottery Number"
        );
        foreach ($subjects as $sbjct) {
            foreach ($terms as $term) {
                $heading[] = $config_subjects[$sbjct] . " " . $term;
            }
        }
        $heading[] = "B Info";
        $heading[] = "C Info";
        $heading[] = "D Info";
        $heading[] = "E Info";
        $heading[] = "Susp";
        $heading[] = "#Days of Suspension";
        $heading[] = "Priority";
        $data_ary[] = $heading;

        foreach ($firstdata as $key => $value) {
            $tmp = array();
            $tmp[] = $value['id'];
            $tmp[] = $value['submission_status'];
            $tmp[] = $value['race'];
            $tmp[] = $value['student_id'];
            $tmp[] = $value['last_name'];
            $tmp[] = $value['first_name'];
            $tmp[] = $value['next_grade'];
            $tmp[] = $value['current_school'];
            $tmp[] = '';
            $tmp[] = $value['first_program'];
            $tmp[] = $value['second_program'];
            $tmp[] = $value['first_sibling'];
            $tmp[] = $value['lottery_number'];

            foreach ($value['score'] as $skey => $sbjct) {
                foreach ($terms as $term) {
                    if (isset($sbjct[$term])) {
                        $tmp[] = $sbjct[$term];
                    } else
                        $tmp[] = "";
                }
            }
            foreach ($value['cdi'] as $vkey => $vcdi) {
                $tmp[] = ($value['cdi'][$vkey] == 0 ? "0" : $value['cdi'][$vkey]);
            }
            if ($value['first_sibling'] != "" && $value['lottery_number'] != "")
                $tmp[] = 1;
            else
                $tmp[] = 2;
            $data_ary[] = $tmp;
        }

        foreach ($seconddata as $key => $value) {
            $tmp = array();
            $tmp[] = $value['id'];
            $tmp[] = $value['submission_status'];
            $tmp[] = $value['race'];
            $tmp[] = $value['student_id'];
            $tmp[] = $value['last_name'];
            $tmp[] = $value['first_name'];
            $tmp[] = $value['next_grade'];
            $tmp[] = $value['current_school'];
            $tmp[] = '';
            $tmp[] = $value['first_program'];
            $tmp[] = $value['second_program'];
            $tmp[] = $value['first_sibling'];
            $tmp[] = $value['lottery_number'];
            foreach ($value['score'] as $skey => $sbjct) {
                foreach ($terms as $term) {
                    if (isset($sbjct[$term])) {
                        $tmp[] = $sbjct[$term];
                    } else
                        $tmp[] = "";
                }
            }
            foreach ($value['cdi'] as $vkey => $vcdi) {
                $tmp[] = ($value['cdi'][$vkey] == 0 ? "0" : $value['cdi'][$vkey]);
            }
            if ($value['second_sibling'] != "" && $value['lottery_number'] != "")
                $tmp[] = 1;
            else
                $tmp[] = 2;

            $data_ary[] = $tmp;
        }

        ob_end_clean();
        ob_start();

        return Excel::download(new SubmissionExport(collect($data_ary)), 'Submissions.xlsx');
    }

    public function exportMissingGrade($firstdata, $subjects, $terms)
    {
        set_time_limit(0);
        $config_subjects = Config::get('variables.subjects');
        $data_ary = [];
        $heading = array(
            "Submission ID",
            "Submission Status",
            "State ID",
            "Last Name",
            "First Name",
            "Next Grade",
            "Current School",
            "First Choice",
            "Second Choice"
        );


        foreach ($subjects as $sbjct) {
            foreach ($terms as $term) {
                foreach ($term as $tv) {
                    $heading[] = $config_subjects[$sbjct] . " " . $tv;
                }

                //if(in_array($sbjct, array("re", "eng", "math", "sci", "ss")))
                //{

                //}
            }
        }
        $heading[] = "Current Grade";
        $data_ary[] = $heading;

        foreach ($firstdata as $key => $value) {
            // print_r($value['score']);exit;
            $gradeInfo = SubjectManagement::where("grade", $value['next_grade'])->first();

            $tmp = array();
            $tmp[] = $value['id'];
            $tmp[] = $value['submission_status'];
            $tmp[] = $value['student_id'];
            $tmp[] = $value['last_name'];
            $tmp[] = $value['first_name'];
            $tmp[] = $value['next_grade'];
            $tmp[] = $value['current_school'];
            $tmp[] = $value['first_program'];
            $tmp[] = $value['second_program'];

            //dd($value['score']);

            foreach ($value['score'] as $vkey => $sbjct) {
                foreach ($sbjct as $sk => $sv) {
                    foreach ($terms as $term1) {
                        foreach ($term1 as $tv) {
                            $term = $tv;
                            $field = strtolower(str_replace(" ", "_", $config_subjects[$sk]));

                            if (isset($sbjct[$sk][$term]) && is_numeric($sbjct[$sk][$term])) {
                                $tmp[] = $sbjct[$sk][$term];
                            } elseif ($gradeInfo->{$field} == "N") {
                                $tmp[] = "NA";
                            } else {
                                $tmp[] = "0";
                            }
                        }
                    }
                }
            }
            $tmp[] = $value['current_grade'];
            // dd($tmp);
            $data_ary[] = $tmp;
        }

        ob_end_clean();
        ob_start();
        if (str_contains(request()->url(), '/export/manual_grade_check'))
            return Excel::download(new MissingGradesExport(collect($data_ary)), 'GradeEligibility.xlsx');
        else
            return Excel::download(new MissingGradesExport(collect($data_ary)), 'MissingGrades.xlsx');
    }

    public function exportMissingCDI($firstdata, $filename = "MissingCDI.xlsx")
    {
        $data_ary = [];
        $heading = array(
            "Submission ID",
            "Submission Status",
            "State ID",
            "Last Name",
            "First Name",
            "Next Grade",
            "Current School",
            "First Choice",
            "Second Choice",
            "B Info",
            "C Info",
            "D Info",
            "E Info",
            "Susp",
            "#Days of Suspension",
            "Current Grade"
        );
        $data_ary[] = $heading;

        foreach ($firstdata as $key => $value) {
            $tmp = array();
            $tmp[] = $value['id'];
            $tmp[] = $value['submission_status'];
            $tmp[] = $value['student_id'];
            $tmp[] = $value['last_name'];
            $tmp[] = $value['first_name'];
            $tmp[] = $value['next_grade'];
            $tmp[] = $value['current_school'];
            $tmp[] = $value['first_program'];
            $tmp[] = $value['second_program'];
            foreach ($value['cdi'] as $skey => $sbjct) {
                if (isset($value['cdi'][$skey]) && is_numeric($value['cdi'][$skey])) {
                    $tmp[] = $value['cdi'][$skey];
                } else
                    $tmp[] = 0;
            }
            $tmp[] = $value['current_grade'];
            $data_ary[] = $tmp;
        }
        ob_end_clean();
        ob_start();

        return Excel::download(new MissingCDIExport(collect($data_ary)), $filename);
    }

    public function downloadTemplate(Request $request)
    {
        $filename = 'Submissions.xlsx';
        return Excel::download(new DownloadTemplate(), $filename);
    }

    public function importGrade(Request $request)
    {
        $rules = [
            // 'upload_csv'=>'required',
            'upload_csv' => 'required|mimes:xlsx',
        ];
        $message = [
            // 'upload_csv.required'=>'File is required',
            'upload_csv.required' => 'File is required',
            'upload_csv.mimes' => 'Invalid file format | File format must be xlsx.',
        ];

        $import = new GradeImport;
        $validator = Validator::make($request->all(), $rules, $message);
        if ($validator->fails()) {
            Session::flash('error', 'Please select proper file');
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            $file = $request->file('upload_csv');
            $headings = (new HeadingRowImport)->toArray($file);

            $headRow = array();
            $tmp = array();
            foreach ($headings[0][0] as $key => $value) {
                $value = str_replace("_", " ", $value);
                $value = str_replace(array_keys($import->termArr), array_values($import->termArr), $value);
                $tmp[] = ucwords($value);
            }
            $tmp[] = "Error";
            $headRow = $tmp;

            $import->import($file);

            $grade_array = array();
            $grade_array[] = $headRow;
            foreach ($import->invalidArr as $key => $data) {

                $tmp = $data;
                $tmp['Error'] = "Invalid Value";
                $grade_array[] = $tmp;
            }
            if (count($import->invalidArr) > 0) {
                return Excel::download(new MissingGradesExport(collect($grade_array)), 'GradeError.xlsx');
            }

            Session::flash('success', 'Grade Imported successfully');
        }
        return  redirect()->back();
    }

    public function importGradeGet($enrollment_id)
    {
        return view('Reports::import_missing_grade', compact("enrollment_id"));
    }

    public function importCDI(Request $request)
    {
        $rules = [
            // 'upload_csv'=>'required',
            'upload_csv' => 'required|mimes:xlsx',
        ];
        $message = [
            // 'upload_csv.required'=>'File is required',
            'upload_csv.required' => 'File is required',
            'upload_csv.mimes' => 'Invalid file format | File format must be xlsx.',
        ];

        $validator = Validator::make($request->all(), $rules, $message);
        if ($validator->fails()) {
            Session::flash('error', 'Please select proper file');
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            $file = $request->file('upload_csv');
            $import = new CDIImport;
            $import->import($file);

            $headings = (new HeadingRowImport)->toArray($file);

            $headRow = array();
            $tmp = array();
            foreach ($headings[0][0] as $key => $value) {
                if ($value != "") {
                    if ($value == "days_of_suspension")
                        $tmp[] = "#Days of Suspension";
                    else {
                        $value = str_replace("_", " ", $value);
                        $tmp[] = ucwords($value);
                    }
                }
            }
            $tmp[] = "Error";
            $headRow = $tmp;

            $cdi_array = array();
            $cdi_array[] = $headRow;
            foreach ($import->invalidArr as $key => $data) {
                $tmp = $data;
                $tmp['Error'] = "Invalid Value";
                $cdi_array[] = $tmp;
            }
            if (count($import->invalidArr) > 0) {
                return Excel::download(new MissingCDIExport(collect($cdi_array)), 'CDIError.xlsx');
            }
            Session::flash('success', 'CDI Imported successfully');
        }
        return  redirect()->back();
    }

    public function importCDIGet($enrollment_id)
    {
        return view('Reports::import_missing_cdi', compact("enrollment_id"));
    }

    public function saveGrade(Request $request, $id)
    {
        $arr = array("1 1" => "1.1", "1 2" => "1.2", "1 3" => "1.3", "1 4" => "1.4", "2 1" => "2.1", "2 2" => "2.2", "2 3" => "2.3", "2 4" => "2.4", "3 1" => "3.1", "3 2" => "3.2", "3 3" => "3.3", "3 4" => "3.4", "4 1" => "4.1", "4 2" => "4.2", "4 3" => "4.3", "4 4" => "4.4");
        $data = $request->all();
        $config_subjects = Config::get('variables.subjects');

        $courses = Config::get('variables.courseType');

        $submission_grade = SubmissionGrade::where("submission_id", $id)->join("submissions", "submissions.id", "submission_grade.submission_id")->join("application", "application.id", "submissions.application_id")->select("submission_grade.*", "submissions.application_id", "application.enrollment_id")->get();
        $current_grade = array();
        foreach ($submission_grade as $key => $value) {
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


        $new_grade = array();
        $grades_data = [];
        $rs = Submissions::where("id", $id)->first();
        // dd($data);
        foreach ($data as $key => $value) {
            // dd($data);
            if ($key != "_token") {
                // $key = str_replace("id_".$id."_", "", $key);
                // dd($id);
                $insert = array();
                $insert['submission_id'] = $id;
                $tmp = explode(",", $key);
                // dd($tmp[1]);
                $insert['courseType'] = $config_subjects[$tmp[1]];
                $insert['courseTypeID'] = findArrayKey($courses, $config_subjects[$tmp[1]]);
                $tmp1 = str_replace("_", " ", $tmp[2]);
                $insert['GradeName'] = str_replace(array_keys($arr), array_values($arr), $tmp1);
                $insert['courseFullName'] = $insert['courseType'];
                $insert['academicYear'] = $tmp[3];
                //     if($rs->late_submission == "Y")
                //         $insert['academicYear'] = "2020-2021";
                //     else
                // $insert['academicYear'] = "2019-2020";
                $insert['academicTerm'] = str_replace(array_keys($arr), array_values($arr), $tmp1);
                $insert['stateID'] = $rs->student_id;
                $insert['numericGrade'] = $value;
                $exist = SubmissionGrade::where("submission_id", $id)->where("courseType", $insert['courseType'])->where('academicYear', $insert['academicYear'])->where('academicTerm', $insert['academicTerm'])->first();
                if (isset($exist)) {
                    // dd('exist');
                    $upd = $exist->update($insert);
                } else {
                    // dd('create');
                    $ins = SubmissionGrade::create($insert);
                }

                $initSubmission = Submissions::where('submissions.id', $id)->join("application", "application.id", "submissions.application_id")->select("submissions.*", 'submissions.id as submission_id', "application.enrollment_id")->first();
                $insert['enrollment_id'] = $initSubmission->enrollment_id;
                $insert['application_id'] = $initSubmission->application_id;
                $new_grade[] = $insert;
            }
        }
        $this->modelGradeChanges($current_grade, $new_grade, "Submission Academic Grade Report");

        $failed = $this->checkMissingGrades($id);
        if ($failed == 0 && $rs->submission_status == "Pending") {
            $rs = SubmissionConductDisciplinaryInfo::where("submission_id", $id)->first();
            if (!empty($rs)) {
                Submissions::where("id", $id)->update(array("submission_status" => "Active"));
            }
        }
        echo "Succ";
    }

    public function checkMissingGrades($id)
    {
        $submission = Submissions::where("id", $id)->first();

        $subjects = $terms = $eligibilityArr = array();
        $eligibilityData = getEligibilitiesByProgram($submission->first_choice_program_id, 'Academic Grade Calculation');

        if (count($eligibilityData) > 0) {
            if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                // echo $eligibilityData[0]->id;exit;
                $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                if (!empty($content)) {
                    if ($content->scoring->type == "DD") {
                        $tmp = array();

                        foreach ($content->subjects as $svalue) {
                            if (!in_array($svalue, $subjects)) {
                                $subjects[] = $svalue;
                            }
                        }

                        foreach ($content->terms_calc as $tvalue) {
                            if (!in_array($tvalue, $terms)) {
                                $terms[] = $tvalue;
                            }
                        }
                    }
                }
            }
        }

        if ($submission->second_choice_program_id > 0) {
            $eligibilityData = getEligibilitiesByProgram($submission->second_choice_program_id, 'Academic Grade Calculation');
            if (count($eligibilityData) > 0) {
                if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                    $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                    // echo $eligibilityData[0]->id;exit;
                    $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                    if (!empty($content)) {
                        if ($content->scoring->type == "DD") {
                            $tmp = array();

                            foreach ($content->subjects as $value) {
                                if (!in_array($value, $subjects)) {
                                    $subjects[] = $value;
                                }
                            }

                            foreach ($content->terms_calc as $value) {
                                if (!in_array($value, $terms)) {
                                    $terms[] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }

        $config_subjects = Config::get('variables.subjects');
        $score = array();
        $missing = false;

        $gradeInfo = SubjectManagement::where("grade", $submission->next_grade)->first();
        $import_academic_year = Config::get('variables.import_academic_year');
        $first_failed = $second_failed = 0;
        $failed = 0;
        foreach ($subjects as $value) {
            foreach ($terms as $value1) {

                $marks = getSubmissionAcademicScoreMissing($submission->id, $config_subjects[$value], $value1, $import_academic_year, $import_academic_year);
                /* Here copy above function if condition  for NA */
                if ($marks == "NA") {
                    if (!empty($gradeInfo)) {
                        $field = strtolower(str_replace(" ", "_", $config_subjects[$value]));
                        echo $gradeInfo->{$field} . "-";
                        if ($gradeInfo->{$field} == "Y") {
                            echo " - " . $failed;
                            $failed++;
                        }
                    } else {
                        $failed++;
                    }
                }
            }
        }
        return $failed;
    }

    public function saveCDI(Request $request, $id)
    {
        $data = $request->all();

        $rs = Submissions::where("id", $id)->first();
        $insert = array();
        $insert['submission_id'] = $id;
        $insert['stateID'] = $rs->student_id;
        foreach ($data as $key => $value) {
            if ($key != "_token") {
                $key = str_replace("id_" . $id . "_", "", $key);
                $insert[$key] = $value;
            }
        }
        SubmissionConductDisciplinaryInfo::updateOrCreate(["submission_id" => $id], $insert);

        $app_data = SubmissionConductDisciplinaryInfo::where("submission_id", $id)->join("submissions", "submissions.id", "submission_conduct_discplinary_info.submission_id")->join("application", "application.id", "submissions.application_id")->select("submission_id", "b_info", "c_info", "d_info", "e_info", "susp", "susp_days",  "submissions.application_id", "application.enrollment_id", "submissions.submission_status")->first();

        //        print_r($app_data);exit;
        $this->modelCDICreate($app_data, "Submission - CDI");

        $failed = $this->checkMissingGrades($id);
        if ($failed == 0 && $app_data->submissions_final_status == "Pending") {
            Submissions::where("id", $id)->update(array("submission_status" => "Active"));
        }

        echo "Succ";
    }

    public function mcpssEmployeeVerification($submission_id, $status)
    {
        $data = Submissions::where('id', $submission_id)->first();
        if (isset($data)) {
            Submissions::where('id', $submission_id)->update(['mcpss_verification_status' => $status, 'mcpss_verification_status_by' => Auth::user()->id, 'mcpss_verification_status_at' => date("Y-m-d H:i:s")]);
        }
        Session::flash('success', 'Employee verification status changed successfully.');
        return redirect()->back();
    }

    public function mcpssEmployeeStatus($submission_id)
    {
        $data = Submissions::where('id', $submission_id)->first();
        if (isset($data)) {
            Submissions::where('id', $submission_id)->update(['magnet_program_employee' => 'Y', 'magnet_program_employee_by' => Auth::user()->id, 'magnet_program_employee_at' => date("Y-m-d H:i:s")]);
        }
        Session::flash('success', 'Employee status changed successfully.');
        return redirect()->back();
    }

    public function priorityCalculate($submission, $choice = "first")
    {
        $str = $choice . "_choice_program_id";
        $rank_counter = 0;
        if ($submission->{$str} != 0 && $submission->{$str} != '') {
            $priority_details = DB::table("priorities")->join("program", "program.priority", "priorities.id")->join("priority_details", "priority_details.priority_id", "priorities.id")->where("program.id", $submission->{$str})->select('priorities.*', 'priority_details.*', 'program.feeder_priorities', 'program.magnet_priorities')->get();

            foreach ($priority_details as $count => $priority) {

                $flag = false;
                if ($priority->sibling == 'Y') {
                    if (isset($submission->{$choice . '_sibling'}) && $submission->{$choice . '_sibling'} != '') {
                        $flag = true;
                    }
                    if ($flag == false) {
                        continue;
                    }
                }

                // Magnet Employee
                $flag = false;
                if ($priority->magnet_employee == 'Y') {
                    if (isset($submission->magnet_program_employee) && $submission->magnet_program_employee == 'Y') {
                        $flag = true;
                    }
                    if ($flag == false) {
                        continue;
                    }
                }

                // Feeder
                $flag = false;
                if ($priority->feeder == 'Y') {
                    if ($priority->feeder_priorities != '') {
                        $tmp = explode(",", $priority->feeder_priorities);

                        if (in_array($submission->current_school, $tmp)) {
                            $flag = true;
                        }
                        if ($flag == false) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }

                // Magnet School
                $flag = false;
                if ($priority->magnet_student == 'Y') {
                    if ($priority->magnet_priorities != '') {
                        $tmp = explode(",", $priority->magnet_priorities);
                        if (in_array($submission->current_school, $tmp)) {
                            $flag = true;
                        }
                        if ($flag == false) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
                return $count + 1;
            }

            return 404;
        }
        return 404;
    }

    public function settingUpdate($field, $val)
    {
        $rs = DB::table("reports_hide_option")->update([$field => $val]);
        echo "done";
    }

    public function checkCDIStatus($setCDIEligibilityData, $cdiData, $id)
    {
        if (isset($setCDIEligibilityData[$id]['b_info'])) {
            if ($cdiData['b_info'] == "NA") {
                return "Pass";
            } elseif ($cdiData['b_info'] > $setCDIEligibilityData[$id]['b_info'] || $cdiData['c_info'] > $setCDIEligibilityData[$id]['c_info'] || $cdiData['d_info'] > $setCDIEligibilityData[$id]['d_info'] || $cdiData['e_info'] > $setCDIEligibilityData[$id]['e_info'] || $cdiData['susp'] > $setCDIEligibilityData[$id]['susp'] || $cdiData['susp_days'] > $setCDIEligibilityData[$id]['susp_days']) {
                return "Fail";
            } else {
                return "Pass";
            }
        } else
            return "Pass";
    }


    public function offerStatus($enrollment_id = 0, $type = "", $version = 0)
    {
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();

        $versions_lists = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "desc")->get();
        $late_lists = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "desc")->get();

        if ($version == 0) {
            $data_ary = SubmissionsFinalStatus::join('submissions', 'submissions.id', 'submissions_final_status.submission_id')
                ->where('submissions.enrollment_id', $enrollment_id)
                ->where(function ($query) {
                    $query->where('first_choice_final_status', 'Offered');
                    $query->orWhere('second_choice_final_status', 'Offered');
                })
                ->get();
        } elseif ($type == "waitlist") {
            $data_ary = SubmissionsWaitlistFinalStatus::join('submissions', 'submissions.id', 'submissions_waitlist_final_status.submission_id')
                ->where('submissions.enrollment_id', $enrollment_id)
                ->where(function ($query) {
                    $query->where('first_choice_final_status', 'Offered');
                    $query->orWhere('second_choice_final_status', 'Offered');
                })->where("submissions_waitlist_final_status.version", $version)
                ->get();
        } elseif ($type == "latesubmission") {
            $data_ary = LateSubmissionFinalStatus::join('submissions', 'submissions.id', 'late_submissions_final_status.submission_id')
                ->where('submissions.enrollment_id', $enrollment_id)
                ->where(function ($query) {
                    $query->where('first_choice_final_status', 'Offered');
                    $query->orWhere('second_choice_final_status', 'Offered');
                })->where("late_submissions_final_status.version", $version)
                ->get();
        }
        // return $data_ary;



        return view("Reports::offer_status", compact("enrollment_id", "enrollment", "data_ary", "versions_lists", "version", "late_lists", "type"));
    }

    public function seatStatus($enrollment_id = 0)
    {
        $ids = array('"PreK"', '"K"', '"1"', '"2"', '"3"', '"4"', '"5"', '"6"', '"7"', '"8"', '"9"', '"10"', '"11"', '"12"');
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $district_id = Session::get("district_id");
        $submissions = Submissions::where('district_id', $district_id)->where("enrollment_id", Session::get("enrollment_id"))->orderByRaw('FIELD(next_grade,' . implode(",", $ids) . ')')
            ->get(['first_choice_program_id', 'second_choice_program_id', 'next_grade']);


        $choices = ['first_choice_program_id', 'second_choice_program_id'];
        $prgCount = array();;
        if (isset($submissions)) {
            foreach ($choices as $choice) {
                foreach ($submissions as $key => $value) {
                    if ($value->$choice != 0) {
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
        foreach ($programs as $key => $value) {
            foreach ($value as $ikey => $ivalue) {
                $tmp = array();
                $tmp['program_name'] = getProgramName($key) . " - Grade " . $ivalue;
                $rs = Availability::where("program_id", $key)->where("grade", $ivalue)->select("available_seats")->first();
                $tmp['total_seats'] = $rs->available_seats;
                $tmp['total_applicants'] = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where(function ($query) use ($key) {
                    $query->where('first_choice_program_id', $key);
                    $query->orWhere('second_choice_program_id', $key);
                })->where('next_grade', $ivalue)->get()->count();

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                    ->where("first_choice_program_id", $key)
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                    ->where("second_choice_program_id", $key)
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $tmp['offered'] = $rs1 + $rs2;


                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Denied due to Ineligibility")
                    ->where("first_choice_program_id", $key)
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Denied due to Ineligibility")
                    ->where("second_choice_program_id", $key)
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $tmp['noteligible'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Denied due To Incomplete Records")
                    ->where("first_choice_program_id", $key)
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Denied due To Incomplete Records")
                    ->where("second_choice_program_id", $key)
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $tmp['Incomplete'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                    ->where("first_choice_program_id", $key)
                    ->where("first_offer_status", 'Declined')
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                    ->where("second_choice_program_id", $key)
                    ->where("second_offer_status", 'Declined')
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $tmp['Decline'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                    ->where("first_choice_program_id", $key)
                    ->where("first_offer_status", 'Declined & Waitlisted')
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                    ->where("second_choice_program_id", $key)
                    ->where("second_offer_status", 'Declined & Waitlisted')
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $tmp['Waitlisted'] = $rs1 + $rs2;

                $rs1 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("first_choice_final_status", "Offered")
                    ->where("first_choice_program_id", $key)
                    ->where("first_offer_status", 'Accepted')
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $rs2 = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where("second_choice_final_status", "Offered")
                    ->where("second_choice_program_id", $key)
                    ->where("second_offer_status", 'Accepted')
                    ->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
                    ->where('next_grade', $ivalue)
                    ->get()->count();
                $tmp['Accepted'] = $rs1 + $rs2;

                $tmp['remaining'] = $tmp['total_seats'] - $tmp['Accepted'];
                $final_data[] = $tmp;
            }
        }

        //print_r($final_data);exit;
        return view("Reports::seats_status", compact("enrollment_id", "enrollment", "final_data"));
    }


    public function newreport()
    {

        /*$rs = Submissions::get();
        foreach($rs as $key=>$value)
        {
            $first_choice = $value->first_choice;
            $second_choice = $value->second_choice;

            $program = ApplicationProgram::where("id", $first_choice)->first();
            echo $value->id."^".$value->first_choice."^".$value->first_choice_program_id."^".$program->program_id;
            if($second_choice != '')
            {
                $program = ApplicationProgram::where("id", $second_choice)->first();
                echo "^".$value->second_choice."^".$value->second_choice_program_id."^".$program->program_id;
            }
            else
                echo "^".$value->second_choice."^^";
            echo "<BR>"; 


        }
        exit;*/

        /* Get Next Grade Unique for Tabbing */
        $grade_data = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where('next_grade', '<>', '')->orderBy('next_grade', 'DESC')->get(["next_grade"]);
        $gradeArr = array("K", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12");
        $fgradeTab = [];
        foreach ($grade_data as $key => $value) {
            $fgradeTab[] = $value->next_grade;
        }
        $gradeTab = [];
        foreach ($gradeArr as $key => $value) {
            if (in_array($value, $fgradeTab))
                $gradeTab[] = $value;
        }
        $firstData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->whereIn("submission_status", array("Waitlisted", "Declined / Waitlist for other"))->get(["first_choice"]);

        /* Get Subject and Acardemic Term like Q1.1 Q1.2 etc set for Academic Grade Calculation 
                For all unique First Choice and Second Choice
         */
        $subjects = $terms = array();
        $eligibilityArr = array();
        foreach ($firstData as $value) {
            if ($value->first_choice != "") {
                $eligibilityData = getEligibilities($value->first_choice, 'Academic Grade Calculation');
                if (count($eligibilityData) > 0) {
                    if (!in_array($eligibilityData[0]->id, $eligibilityArr)) {
                        $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
                        // echo $eligibilityData[0]->id;exit;
                        $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

                        if (!empty($content)) {
                            if ($content->scoring->type == "DD") {
                                $tmp = array();

                                foreach ($content->subjects as $value) {
                                    if (!in_array($value, $subjects)) {
                                        $subjects[] = $value;
                                    }
                                }

                                foreach ($content->terms_calc as $value) {
                                    if (!in_array($value, $terms)) {
                                        $terms[] = $value;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }


        /* Get Set Eligibility Data Set for first choice program and second choice program
         */


        /* Get CDI Data */
        $submissions = Submissions::where("enrollment_id", Session::get("enrollment_id"))->where('submissions.district_id', Session::get('district_id'))
            ->whereIn("submission_status", array("Waitlisted", "Declined / Waitlist for other"))
            //            ->where('submission_status', 'Denied due to Ineligibility')
            //            ->limit(5)
            ->get();
        //exit;//print_r($submissions);exit;

        $firstdata = $seconddata = array();
        $programGrades = array();
        foreach ($submissions as $key => $value) {


            $score = $this->collectionStudentGradeReport1($value, $subjects, $terms, $value->next_grade);
            if (count($score) > 0) {

                $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
                if (!empty($cdi_data)) {
                    $cdiArr = array();
                    $cdiArr['b_info'] = $cdi_data->b_info;
                    $cdiArr['c_info'] = $cdi_data->c_info;
                    $cdiArr['d_info'] = $cdi_data->d_info;
                    $cdiArr['e_info'] = $cdi_data->e_info;
                    $cdiArr['susp'] = $cdi_data->susp;
                    $cdiArr['susp_days'] = $cdi_data->susp_days;
                } else {
                    $cdiArr = array();
                    $cdiArr['b_info'] = "";
                    $cdiArr['c_info'] = "";
                    $cdiArr['d_info'] = "";
                    $cdiArr['e_info'] = "";
                    $cdiArr['susp'] = "";
                    $cdiArr['susp_days'] = "";
                }


                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice'] = $value->first_choice;
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['second_program'] = "";
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                $tmp['magnet_employee'] = $value->mcp_employee;
                $tmp['magnet_program_employee'] = $value->magnet_program_employee;
                if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass") {
                    $tmp['grade_status'] = "Pass";
                } else {
                    $tmp['grade_status'] = "Fail";
                }
                $firstdata[] = $tmp;
            }
        }
        $config_subjects = Config::get('variables.subjects');
        echo "Submission ID^Name^Submission Status^Next Grade^";

        foreach ($subjects as $sbjct) {
            foreach ($terms as $term) {
                echo $config_subjects[$sbjct] . " " . $term . "^";
            }
        }
        echo "B Info^C Info^D Info^E Info^Susp^# Days Susp^Status<br>";
        foreach ($firstdata as $key => $value) {
            echo $value['id'] . "^" . ($value['first_name'] . " " . $value['last_name']) . "^" . $value['submission_status'] . "^" . $value['next_grade'] . "^";

            foreach ($value['score'] as $skey => $sbjct) {
                foreach ($terms as $term) {

                    if (isset($sbjct[$term])) {
                        echo $sbjct[$term] . "^";
                    } else {
                        echo "^";
                    }
                }
            }

            foreach ($value['cdi'] as $vkey => $vcdi) {
                foreach ($terms as $term) {
                    echo $value['cdi'][$vkey] . "^";
                }
            }
            echo "<br>";
        }
    }


    public function getSubmissionAcademicScoreMissing($submission_id, $courseType, $GradeName, $term1, $term2)
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


    public function collectionStudentGradeReport1($submission, $subjects, $terms, $next_grade = 0)
    {

        $config_subjects = Config::get('variables.subjects');
        $score = array();
        $missing = false;
        $gradeInfo = SubjectManagement::where("grade", $next_grade)->first();

        $first_failed = $second_failed = 0;
        foreach ($subjects as $value) {
            foreach ($terms as $value1) {

                $marks = $this->getSubmissionAcademicScoreMissing($submission->id, $config_subjects[$value], $value1, (date("Y") - 1) . "-" . (date("Y")), (date("Y") - 1) . "-" . (date("y")));
                /* Here copy above function if condition  for NA */

                if ($marks == "NA") {
                    $score[$value][$value1] = "";
                } else {
                    $score[$value][$value1] = $marks;
                }
            }
        }

        if ($first_failed > 0 && $submission->grade_override == "N") {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Fail";
        } else {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Pass";
        }

        if ($second_failed > 0 && $submission->grade_override == "N") {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Fail";
        } else {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Pass";
        }
        return $score;
    }

    public function populationChange($enrollment_id)
    {
        // Processing
        $form_id = 1;
        $pid = $form_id;
        $from = "form";

        $display_outcome = SubmissionsStatusUniqueLog::count();

        // Population Changes
        $programs = [];
        $district_id = \Session('district_id');

        $ids = array('"PreK"', '"K"', '"1"', '"2"', '"3"', '"4"', '"5"', '"6"', '"7"', '"8"', '"9"', '"10"', '"11"', '"12"');
        $ids_ordered = implode(',', $ids);

        $rawOrder = DB::raw(sprintf('FIELD(submissions.next_grade, %s)', "'" . implode(',', $ids) . "'"));

        $submissions = Submissions::where("submissions.enrollment_id", Session::get("enrollment_id"))->where('district_id', $district_id)->where(function ($q) {
            $q->where(function ($q1) {
                $q1->where("first_choice_final_status", "Offered")->where('second_choice_final_status', '<>', 'Offered');
            })
                ->orWhere(function ($q1) {
                    $q1->where("second_choice_final_status", "Offered")->where('first_choice_final_status', '<>', 'Offered');
                });
        })
            ->where('district_id', $district_id)->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
            ->where("submissions.version", 0)
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
                $availability = Availability::where('program_id', $program_id)
                    ->where('grade', $grade)->first(['total_seats', 'available_seats']);
                $race_count = [];
                if (!empty($availability)) {
                    foreach ($choices as $choice) {

                        if ($choice == "first_choice_program_id") {
                            $submission_race_data = $submissions->where($choice, $program_id)->where('first_choice_final_status', "Offered")->where('second_choice_final_status', '<>', "Offered")->where("submissions.version", 0)
                                ->where('next_grade', $grade);
                        } else {
                            $submission_race_data = $submissions->where($choice, $program_id)->where('second_choice_final_status', "Offered")->where('first_choice_final_status', '<>', "Offered")->where("submissions.version", 0)
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
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        return view("Reports::population_change", compact('data_ary', 'race_ary', 'pid', 'from', "display_outcome", "enrollment", "enrollment_id"));
    }

    public function submissionResults($enrollment_id)
    {
        $form_id = 1;
        $pid = $form_id;
        $from = "form";
        $programs = [];
        $district_id = \Session('district_id');
        $display_outcome = SubmissionsStatusUniqueLog::whereIn("submission_id", Submissions::where("enrollment_id", Session::get("enrollment_id"))->select("id")->get()->toArray())->count();
        $submissions = Submissions::where('submissions.enrollment_id', Session::get("enrollment_id"))
            ->where('district_id', $district_id)
            ->where("form_id", $form_id)->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")
            ->where("submissions.version", 0)
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
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        return view("Reports::submissions_result", compact('final_data', 'pid', 'from', 'display_outcome', "enrollment", "enrollment_id"));
    }

    public function duplicate_student($enrollment_id = 0, $type = 0)
    {
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        if ($type == 0) {
            /*     $data = DB::table("submissions")->where("enrollment_id", $enrollment_id)->where("late_submission", "N")->where('enrollment_id',$enrollment_id)->select('first_name', 'last_name', 'parent_first_name', 'parent_last_name', DB::raw("count(last_name) as total_quantity"))->whereIn('submission_status', ['Active', 'Pending'])
                    ->groupBy('first_name', 'last_name', 'parent_first_name', 'parent_last_name')->havingRaw('count(last_name) > 1')
                    ->get();
            */
            $data = DB::select("SELECT first_name, last_name, parent_first_name, parent_last_name, count(last_name) as total_quantity from submissions where enrollment_id = " . $enrollment_id . " and late_submission = 'N' and submission_status in ('Active', 'Pending') group by first_name, last_name, parent_first_name, parent_last_name having total_quantity > 1");
        } else {
            /*   $data = DB::table("submissions")->where("enrollment_id", $enrollment_id)->where("late_submission", "Y")->where('enrollment_id',$enrollment_id)->select('first_name', 'last_name', 'parent_first_name', 'parent_last_name', DB::raw("count(last_name) as total_quantity"))->whereIn('submission_status', ['Active', 'Pending'])
                    ->groupBy('first_name', 'last_name', 'parent_first_name', 'parent_last_name')->havingRaw('count(last_name) > 1')
                    ->get();
              */
            $data = DB::select("SELECT first_name, last_name, parent_first_name, parent_last_name, count(last_name) as total_quantity from submissions where enrollment_id = " . $enrollment_id . " and late_submission = 'Y' and submission_status in ('Active', 'Pending') group by first_name, last_name, parent_first_name, parent_last_name having total_quantity > 1");
        }

        $dispData = [];
        foreach ($data as $key => $value) {
            $first_name = $value->first_name;
            $last_name = $value->last_name;
            $parent_first_name = $value->parent_first_name;
            $parent_last_name = $value->parent_last_name;
            if ($type == 0) {
                $submissions = Submissions::where("first_name", $first_name)->where("enrollment_id", $enrollment_id)->where("late_submission", "N")->where("last_name", $last_name)->where("parent_first_name", $parent_first_name)->where("parent_last_name", $parent_last_name)->where('enrollment_id', $enrollment_id)->get();
            } else {
                $submissions = Submissions::where("first_name", $first_name)->where("enrollment_id", $enrollment_id)->where("late_submission", "Y")->where("last_name", $last_name)->where("parent_first_name", $parent_first_name)->where("parent_last_name", $parent_last_name)->where('enrollment_id', $enrollment_id)->get();
            }

            $tmp = [];
            if (count($submissions) > 0) {
                foreach ($submissions as $sk => $sv) {
                    $tmp1 = array();
                    $tmp1['first_name'] = $sv->first_name;
                    $tmp1['last_name'] = $sv->last_name;
                    $tmp1['parent_first_name'] = $sv->parent_first_name;
                    $tmp1['parent_last_name'] = $sv->parent_last_name;
                    $tmp1['submission_id'] = $sv->id;
                    $tmp1['first_program'] = getProgramName($sv->first_choice_program_id);
                    $tmp1['second_program'] = getProgramName($sv->second_choice_program_id);
                    $tmp1['next_grade'] = $sv->next_grade;
                    $tmp1['current_school'] = $sv->current_school;
                    $tmp1['submission_status'] = $sv->submission_status;
                    $tmp1['created_at'] = getDateTimeFormat($sv->created_at);
                    $tmp1['student_id'] = $sv->student_id;
                    $tmp[] = $tmp1;
                }
            }
            if (count($tmp) > 0) {
                $dispData[] = $tmp;
            }
        }
        $selection = "duplicatestudent";
        return view("Reports::duplicate_student", compact('enrollment_id', 'enrollment', 'dispData', 'type', 'selection'));
    }

    public function gradeCdiUploadList($enrollment_id)
    {
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $gradecdilist = Submissions::whereRaw('id in (SELECT submission_id FROM grade_cdi_files)')->where('enrollment_id', $enrollment_id)->get();

        return view("Reports::grade_cdi_upload_list", compact('enrollment', 'enrollment_id', 'gradecdilist'));
    }

    public function gradeCdiUploadConfirmed($submission_id, $type)
    {
        $data = Submissions::where('id', $submission_id)->first();
        if (isset($data)) {
            if ($type == 'grade') {
                Submissions::where('id', $submission_id)->update(['grade_upload_confirmed' => 'Y', 'grade_upload_confirmed_by' => Auth::user()->id, 'grade_upload_confirmed_at' => date("Y-m-d H:i:s")]);
                Session::flash('success', 'Grades Confirmed successfully.');
            } else if ($type == 'cdi') {
                Submissions::where('id', $submission_id)->update(['cdi_upload_confirmed' => 'Y', 'cdi_upload_confirmed_by' => Auth::user()->id, 'cdi_upload_confirmed_at' => date("Y-m-d H:i:s")]);
                Session::flash('success', 'CDI Confirmed successfully.');
            }
        }
        return redirect()->back();
    }


    public function collectionStudentGradeReportDynamic($submission, $academic_year, $subjects, $terms, $next_grade = 0, $skip = false, $setEligibilityData, $calc_type)
    {
        $config_subjects = Config::get('variables.subjects');
        $score = array();
        $missing = false;

        $gradeInfo = SubjectManagement::where("grade", $next_grade)->where('application_id', $submission->application_id)->first();

        $first_failed = $second_failed = 0;
        $avgcnt = $avgmarks = 0;
        $subArr = $subTarr = [];

        foreach ($academic_year as $ykey => $yvalue) {
            $yr = $yvalue;
            foreach ($subjects as $value) {
                $cnt = $cntmarks = 0;
                foreach ($terms as $value1) {
                    //echo $yr . " - " . $config_subjects[$value] . " - " .$value1."<BR>";
                    $marks = getSubmissionAcademicScore($submission->id, $config_subjects[$value], $value1, $yr, $yr);
                    //echo " - " . $marks."<BR>";
                    if (isset($subArr[$value])) {
                        $tmp = $subArr[$value];
                        $tmp['score'] += $marks;
                        $tmp['count']++;
                    } else {
                        $tmp = [];
                        $tmp['score'] = $marks;
                        $tmp['count'] = 1;
                    }
                    $subArr[$value] = $tmp;
                    $subTarr[$yvalue][$value][$value1] = $marks;
                }
            }
        }
        //exit;

        // /3584

        //dd($submission->application_id, $next_grade, $gradeInfo, $subArr, $calc_type);       
        if ($calc_type == "DD" || $calc_type == "CLSG") {
            // here we need to check whether set eligibility array set by first choice or second choice and validate subject wise range
            //if(isset)
            $avgCnt = $avgTotal = 0;
            //dd($subArr);
            $tmpArr = [];
            foreach ($subArr as $key => $value) {
                $field = strtolower(str_replace(" ", "_", $config_subjects[$key]));

                if (isset($gradeInfo->{$field}) && $gradeInfo->{$field} != 'N') {
                    if ($value['count'] > 0)
                        $final_avg  = number_format($value['score'] / $value['count'], 2);
                    else
                        $final_avg = 0;
                    //       echo $final_avg."<br>";
                    //      echo  $setEligibilityData[$submission->second_choice][$key]."<BR><BR>-----<BR>";

                    if (isset($setEligibilityData[$submission->first_choice][$key]) && $final_avg < $setEligibilityData[$submission->first_choice][$key]) {
                        $first_failed++;
                    }
                    if (isset($setEligibilityData[$submission->second_choice][$key]) && $final_avg < $setEligibilityData[$submission->second_choice][$key]) {
                        $second_failed++;
                    }
                }
            }
        }

        if ($first_failed > 0 && $submission->grade_override == "N") {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Fail";
        } else {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Pass";
        }

        if ($second_failed > 0 && $submission->grade_override == "N") {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Fail";
        } else {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Pass";
        }

        return $subTarr;
    }

    public function collectionStudentGradeReportDynamicForIneligible($submission, $academic_year, $subjects, $terms, $next_grade = 0, $skip = false, $setEligibilityData, $calc_type)
    {
        $config_subjects = Config::get('variables.subjects');
        $score = array();
        $missing = false;

        $gradeInfo = SubjectManagement::where("grade", $next_grade)->where('application_id', $submission->application_id)->first();

        $first_failed = $second_failed = 0;
        $avgcnt = $avgmarks = 0;
        $subArr = $subTarr = [];

        foreach ($academic_year as $ykey => $yvalue) {
            $yr = $yvalue;
            foreach ($subjects as $value) {
                $cnt = $cntmarks = 0;
                foreach ($terms as $value1) {
                    //echo $yr . " - " . $config_subjects[$value] . " - " .$value1."<BR>";
                    $marks = getSubmissionAcademicScore($submission->id, $config_subjects[$value], $value1, $yr, $yr);
                    //echo " - " . $marks."<BR>";
                    if (isset($subArr[$value])) {
                        $tmp = $subArr[$value];
                        $tmp['score'] += $marks;
                        $tmp['count']++;
                    } else {
                        $tmp = [];
                        $tmp['score'] = $marks;
                        $tmp['count'] = 1;
                    }
                    $subArr[$value] = $tmp;
                    $subTarr[$yvalue][$value][$value1] = $marks;
                }
            }
        }
        //exit;

        // /3584


        if ($calc_type == "DD" || $calc_type == "CLSG") {
            // here we need to check whether set eligibility array set by first choice or second choice and validate subject wise range
            //if(isset)
            $avgCnt = $avgTotal = 0;
            //dd($subArr);
            $tmpArr = [];
            foreach ($subArr as $key => $value) {
                $tmp = $value;
                $failed = false;
                $field = strtolower(str_replace(" ", "_", $config_subjects[$key]));

                if ($value['count'] > 0)
                    $final_avg  = number_format($value['score'] / $value['count'], 2);
                else
                    $final_avg = 0;
                if (isset($gradeInfo->{$field}) && $gradeInfo->{$field} != 'N') {




                    if (isset($setEligibilityData[$submission->first_choice][$key]) && $final_avg < $setEligibilityData[$submission->first_choice][$key]) {
                        $first_failed++;
                        $failed = true;
                    }
                    if (isset($setEligibilityData[$submission->second_choice][$key]) && $final_avg < $setEligibilityData[$submission->second_choice][$key]) {
                        $second_failed++;
                        $failed = true;
                    }

                    if ($failed && $submission->grade_override == "N") {
                        $tmp['Final Average'] = "<span class='alert1 alert-danger'>" . $final_avg . "</span>";
                    } else {
                        $tmp['Final Average'] = "<span>" . $final_avg . "</span>";
                    }

                    // if($submission->id == 6295 && $key=="sci") {dd($submission, $final_avg,$first_failed);}


                    if ($failed && $submission->grade_override == "N") {
                        $tmp['Final Average'] = "<span class='alert1 alert-danger'>" . $final_avg . "</span>";
                    } else {
                        $tmp['Final Average'] = "<span>" . $final_avg . "</span>";
                    }
                } else {
                    $tmp['Final Average'] = "<span>" . $final_avg . "</span>";
                }
                $tmpArr[$key] = $tmp;
            }
            $subArr = $tmpArr;
        }

        // dd($subArr);
        if ($first_failed > 0 && $submission->grade_override == "N") {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Fail";
        } else {
            $this->eligibility_grade_pass[$submission->id]['first'] = "Pass";
        }

        if ($second_failed > 0 && $submission->grade_override == "N") {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Fail";
        } else {
            $this->eligibility_grade_pass[$submission->id]['second'] = "Pass";
        }

        $tmpArr = [];
        foreach ($subTarr as $key => $value) {
            foreach ($value as $kv => $kvv) {
                $tmp = $kvv;
                $tmp['Final Average'] = $subArr[$kv]['Final Average'];

                $tmpArr[$key][$kv] = $tmp;
            }
        }
        return $tmpArr;
    }


    public function get_offered_count_programwise($program_id, $grade)
    {
        /* From regular submissions Results */
        $count1 = SubmissionsFinalStatus::where("next_grade", $grade)->where(function ($q1) use ($program_id) {
            $q1->where(function ($q) use ($program_id) {
                $q->where("first_offer_status", "Accepted")->where("first_waitlist_for", $program_id);
            })->orWhere(function ($q) use ($program_id) {
                $q->where("second_offer_status", "Accepted")->where("second_waitlist_for", $program_id);
            });
        })->where("submission_status", "Offered and Accepted")->join("submissions", "submissions.id", "submissions_final_status.submission_id")->count();


        /* From regular submissions Results LateSubmissionFinalStatus,SubmissionsWaitlistFinalStatus*/
        $count2 = LateSubmissionFinalStatus::where("next_grade", $grade)->where(function ($q1) use ($program_id) {
            $q1->where(function ($q) use ($program_id) {
                $q->where("first_offer_status", "Accepted")->where("first_waitlist_for", $program_id);
            })->orWhere(function ($q) use ($program_id) {
                $q->where("second_offer_status", "Accepted")->where("second_waitlist_for", $program_id);
            });
        })->where("submission_status", "Offered and Accepted")->join("submissions", "submissions.id", "late_submissions_final_status.submission_id")->count();

        /* From regular submissions Results LateSubmissionFinalStatus,SubmissionsWaitlistFinalStatus*/
        $count3 = SubmissionsWaitlistFinalStatus::where("next_grade", $grade)->where(function ($q1) use ($program_id) {
            $q1->where(function ($q) use ($program_id) {
                $q->where("first_offer_status", "Accepted")->where("first_waitlist_for", $program_id);
            })->orWhere(function ($q) use ($program_id) {
                $q->where("second_offer_status", "Accepted")->where("second_waitlist_for", $program_id);
            });
        })->where("submission_status", "Offered and Accepted")->join("submissions", "submissions.id", "submissions_waitlist_final_status.submission_id")->count();

        return $count1 + $count2 + $count3;
    }

    /*All PowerSchool CDI Data*/
    public function allPowerSchoolCDIReport($enrollment_id)
    {
        $enrollment = Enrollment::where("district_id", Session::get('district_id'))->get();
        $selection = "allcdi";
        return view("Reports::all_powerschool_cdi", compact('enrollment_id', 'enrollment', 'selection'));
    }
    public function allPowerSchoolCDIReportResponse($enrollment_id = 0, $submission_type = '', $late_submission = 0)
    {
        $submissions = Submissions::where("enrollment_id", $enrollment_id)->pluck('id')->toArray();

        $data['cdi_data'] = \DB::table("student_cdi_info_powerschool")->whereIn("submission_id", $submissions)->get();
        //        $data['cdi_data'] = \DB::table("student_cdi_info_powerschool")->get();
        $returnHTML =  view("Reports::all_powerschool_cdi_response", compact("enrollment_id", "data"))->render();
        return response()->json(array('success' => true, 'html' => $returnHTML));
    }


    public function generateLateGradeStatus()
    {
        set_time_limit(0);
        $rsAppData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("late_submission", "Y")->whereIn("submission_status", array('Active'))->get(["application_id"]); //, 'Pending'
        foreach ($rsAppData as $rkey => $rvalue) {
            $application_ids[] = $rvalue->application_id;
        }
        $rsAppData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("late_submission", "Y")->whereIn("submission_status", array('Active'))->get(["application_id"]); //, 'Pending'
        foreach ($rsAppData as $rkey => $rvalue) {
            $application_ids[] = $rvalue->application_id;
        }
        $firstdata = $seconddata = array();

        $subjects = $terms = array();
        $eligibilityArr = array();
        $grade_not_required = [];
        $done_grade_arr = [];
        $academic_year = $calc_type_arr = $choice_ac_yr =  $choice_tm_arr = [];
        $calc_type = "CLSG";

        $subjects = array("re", "eng", "math", "sci", "ss");
        $terms = array("Q1 Grade", "Q2 Grade");
        $academic_year = array("2023-2024");
        $calc_type_arr = array("841" => "CLSG", "844" => "CLSG", "941" => "CLSG", "847" => "CLSG", "850" => "CLSG", "843" => "CLSG", "852" => "CLSG", "840" => "CLSG", "836" => "CLSG", "849" => "CLSG", "848" => "CLSG", "845" => "CLSG", "854" => "CLSG", "835" => "CLSG", "839" => "CLSG", "842" => "CLSG", "851" => "CLSG", "855" => "CLSG", "846" => "CLSG");

        //$setEligibilityData = ["841" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"941" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"847" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"850" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"843" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"840" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"852" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"848" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"854" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"835" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"839" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"844" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"842" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"836" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"851" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"855" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"849" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"846" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70],"845" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70]];


        //        $setCDIEligibilityData = ["841" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "941" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "847" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "850" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "843" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "840" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "852" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "848" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "854" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "835" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "839" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "844" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "842" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "836" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "851" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "855" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "849" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "846" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "845" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4]];

        $calc_type_arr = array(
            "868" => "CLSG",
            "861" => "CLSG",
            "858" => "CLSG",
            "866" => "CLSG",
            "869" => "CLSG",
            "860" => "CLSG",
            "859" => "CLSG",
            "864" => "CLSG",
            "862" => "CLSG",
            "865" => "CLSG",
            "870" => "CLSG",
            "942" => "CLSG",
            "863" => "CLSG",
            "867" => "CLSG",
            "872" => "CLSG",
        );

        $setCDIEligibilityData = array("868" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "861" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "858" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "866" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "869" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "860" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "859" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "864" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "862" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "865" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "870" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "942" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "863" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "867" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "872" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4]);


        $setEligibilityData = array("868" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "861" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "858" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "866" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "869" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "860" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "859" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "864" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "862" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "865" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "870" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "942" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "863" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "867" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "872" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70]);

        // $firstData = Submissions::distinct()->where("late_submission", "Y")->whereIn("application_id", $application_ids)->get(["first_choice", "second_choice"]);
        // foreach($firstData as $value)
        // {
        //     if($value->first_choice != "" && !in_array($value->second_choice, $grade_not_required) && !in_array($value->first_choice, $done_grade_arr))
        //     {
        //         //echo "FC".$value->first_choice."<BR>";
        //         $eligibilityData = getEligibilitiesDynamic($value->first_choice, 'Academic Grade Calculation');
        //         if(count($eligibilityData) > 0)
        //         {
        //             if(!in_array($eligibilityData[0]->id, $eligibilityArr))
        //             {
        //                 $done_grade_arr[] = $value->first_choice;
        //                 $eligibilityArr[] = $eligibilityData[0]->assigned_eigibility_name;
        //                // echo $eligibilityData[0]->id;exit;
        //                 $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);

        //                 if(!empty($content))
        //                 {
        //                     if($content->scoring->type == "GA" || $content->scoring->type=="DD" || $content->scoring->type=="CLSG")
        //                     {
        //                         $calc_type = $content->scoring->type;
        //                         $calc_type_arr[$value->first_choice] = $calc_type;
        //                         $tmp = array();

        //                         foreach($content->academic_year_calc as $svalue)
        //                                     {

        //                                         if(!in_array($svalue, $academic_year))
        //                                         {

        //                                             $academic_year[] = $svalue;


        //                                         }
        //                                         if(isset($choice_ac_yr[$value->first_choice]) && !in_array($svalue, array_values($choice_ac_yr[$value->first_choice])))
        //                                             {
        //                                                 $choice_ac_yr[$value->first_choice][] = $svalue;
        //                                             }
        //                                             elseif(!isset($choice_ac_yr[$value->first_choice]))
        //                                             {
        //                                                 $choice_ac_yr[$value->first_choice][] = $svalue;

        //                                             }

        //                                     }

        //                         foreach($content->subjects as $svalue)
        //                         {
        //                             if(!in_array($svalue, $subjects))
        //                             {
        //                                 $subjects[] = $svalue;
        //                             }
        //                         }

        //                         foreach($content->terms_calc as $svalue)
        //                         {
        //                             if(!in_array($svalue, $terms))
        //                             {
        //                                 $terms[] = $svalue;

        //                             }
        //                             if(isset($choice_tm_arr[$value->first_choice]) && !in_array($svalue, array_values($choice_tm_arr[$value->first_choice])))
        //                                     {
        //                                         $choice_tm_arr[$value->first_choice][] = $svalue;
        //                                     }
        //                                     elseif(!isset($choice_tm_arr[$value->first_choice]))
        //                                     {
        //                                         $choice_tm_arr[$value->first_choice][] = $svalue;

        //                                     }

        //                         }




        //                     }
        //                 }                        
        //             }

        //         }
        //         else
        //         {
        //             $grade_not_required[] = $value->first_choice;
        //         }
        //     }
        //     if($value->second_choice != "" && !in_array($value->second_choice, $grade_not_required) && !in_array($value->second_choice, $done_grade_arr))
        //     {
        //         $eligibilityData = getEligibilitiesDynamic($value->second_choice, 'Academic Grade Calculation');
        //         if(count($eligibilityData) > 0)
        //         {
        //             $content = getEligibilityContent1($eligibilityData[0]->assigned_eigibility_name);
        //             $done_grade_arr[] = $value->second_choice;
        //             if(!empty($content))
        //             {
        //                 if($content->scoring->type=="DD" || $content->scoring->type=="CLSG" || $content->scoring->type == "GA")
        //                 {
        //                     $calc_type = $content->scoring->type;
        //                     $calc_type_arr[$value->second_choice] = $calc_type;

        //                     $tmp = array();

        //                     foreach($content->academic_year_calc as $svalue)
        //                                     {
        //                                         if(!in_array($svalue, $academic_year))
        //                                         {
        //                                             $academic_year[] = $svalue;

        //                                         }
        //                                         if(isset($choice_ac_yr[$value->second_choice]) && !in_array($svalue, array_values($choice_ac_yr[$value->second_choice])))
        //                                             {
        //                                                 $choice_ac_yr[$value->second_choice][] = $svalue;
        //                                             }
        //                                             elseif(!isset($choice_ac_yr[$value->second_choice]))
        //                                             {
        //                                                 $choice_ac_yr[$value->second_choice][] = $svalue;
        //                                             }

        //                                     }

        //                         foreach($content->subjects as $svalue)
        //                         {
        //                             if(!in_array($svalue, $subjects))
        //                             {
        //                                 $subjects[] = $svalue;
        //                             }
        //                         }

        //                         foreach($content->terms_calc as $svalue)
        //                         {
        //                             if(!in_array($svalue, $terms))
        //                             {
        //                                 $terms[] = $svalue;

        //                             }
        //                             if(isset($choice_tm_arr[$value->second_choice]) && !in_array($svalue, array_values($choice_tm_arr[$value->second_choice])))
        //                                     {
        //                                         $choice_tm_arr[$value->second_choice][] = $svalue;
        //                                     }
        //                                     elseif(!isset($choice_tm_arr[$value->second_choice]))
        //                                     {
        //                                         $choice_tm_arr[$value->second_choice][] = $svalue;

        //                                     }

        //                         }
        //                 }
        //             }
        //         }
        //         else
        //         {
        //             $grade_not_required[] = $value->second_choice;
        //         }
        //     }
        // }

        // $setEligibilityData = array();

        // foreach($firstData as $value)
        // {
        //     if(!in_array($value->first_choice, array_keys($setEligibilityData)))
        //     {

        //         $data = getSetEligibilityDataDynamic($value->first_choice, 3);

        //         foreach($subjects as $svalue)
        //         {
        //             if(isset($data->{$svalue}))
        //             {
        //                 $setEligibilityData[$value->first_choice][$svalue] = $data->{$svalue}[0];
        //             }
        //             else
        //             {
        //                  $setEligibilityData[$value->first_choice][$svalue] = 70;   
        //             }
        //         }
        //     }

        //     if(!in_array($value->second_choice, array_keys($setEligibilityData)))
        //     {
        //         $data = getSetEligibilityDataDynamic($value->second_choice, 3);

        //         foreach($subjects as $svalue)
        //         {
        //             if(isset($data->{$svalue}))
        //             {
        //                 $setEligibilityData[$value->second_choice][$svalue] = $data->{$svalue}[0];
        //             }
        //             else
        //             {
        //                  $setEligibilityData[$value->second_choice][$svalue] = 70;   
        //             }
        //         }
        //     }

        // }



        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
            ->where("late_submission", "Y")
            ->where("enrollment_id", Session::get("enrollment_id"))
            ->whereIn('submission_status', array('Active'))
            //->where("id", 11389)
            // ->limit(5)
            ->get(['grade_override', 'id', 'first_choice', 'second_choice', 'application_id', 'next_grade']); // , 'Pending'
        $ctype = $calc_type;

        foreach ($submissions as $key => $value) {
            // dd($value->first_choice, $choice_tm_arr);
            $skip = false;
            /*    if(isset($calc_type_arr[$value->first_choice]))
                $ctype = $calc_type_arr[$value->first_choice];
            elseif(isset($calc_type_arr[$value->second_choice]))
                $ctype = $calc_type_arr[$value->second_choice];
            else
                $ctype = "DD";*/
            $score = $this->collectionStudentGradeReportDynamic($value, $academic_year, $subjects, $terms, $value->next_grade, $skip, $setEligibilityData, $calc_type);
            //dd($this->eligibility_grade_pass, $ctype, $score, $value->first_choice, $setEligibilityData);

            if (count($score) > 0 || $value->grade_override == 'Y') {
                if ($this->eligibility_grade_pass[$value->id]['first'] == "Pass" || $value->grade_override == 'Y' || in_array($value->first_choice, $grade_not_required)) {
                    $grade_status = "Pass";
                } else {
                    $grade_status = "Fail";
                }
                $rs = Submissions::where("id", $value->id)->update(['first_grade_status' => $grade_status]);

                if ($value->second_choice != '' && $value->second_choice != 0) {

                    if ($this->eligibility_grade_pass[$value->id]['second'] == "Pass" || $value->grade_override == 'Y' || in_array($value->second_choice, $grade_not_required)) {
                        $grade_status = "Pass";
                    } else {
                        $grade_status = "Fail";
                    }
                    $rs = Submissions::where("id", $value->id)->update(['second_grade_status' => $grade_status]);
                }
            } else {
                $rs = Submissions::where("id", $value->id)->update(['first_grade_status' => "Incomplete", 'second_grade_status' => "Incomplete"]);
            }
        }
        echo "Done";
        exit;
    }


    public function generateLateCDIStatus()
    {
        $rsAppData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("late_submission", "Y")->whereIn("submission_status", array('Active'))->get(["application_id"]); //, 'Pending'
        foreach ($rsAppData as $rkey => $rvalue) {
            $application_ids[] = $rvalue->application_id;
        }

        $firstData = Submissions::distinct()->whereIn("application_id", $application_ids)->where("late_submission", "Y")->get(["first_choice"]);

        $secondData = Submissions::distinct()->whereIn("application_id", $application_ids)->where("late_submission", "Y")->get(["second_choice"]);
        $setCDIEligibilityData = array();
        $cdi_not_required = [];
        foreach ($firstData as $value) {
            if (!in_array($value->first_choice, $cdi_not_required)) {
                $eligibilityData_cd = getEligibilitiesDynamicProcessing($value->first_choice, 'Conduct Disciplinary Info');
                if (count($eligibilityData_cd) <= 0) {
                    $cdi_not_required[] = $value->first_choice;
                }
                //         $data = getSetEligibilityDataDynamic($value->first_choice, 8);
                //         if(!empty($data))
                //         {
                //             $setCDIEligibilityData[$value->first_choice]['b_info'] = $data->B[0];
                //             $setCDIEligibilityData[$value->first_choice]['c_info'] = $data->C[0];
                //             $setCDIEligibilityData[$value->first_choice]['d_info'] = $data->D[0];
                //             $setCDIEligibilityData[$value->first_choice]['e_info'] = $data->E[0];
                //             $setCDIEligibilityData[$value->first_choice]['susp'] = $data->Susp[0];
                //             $setCDIEligibilityData[$value->first_choice]['susp_days'] = $data->SuspDays[0];
                //         }
                //         else
                //         {
                //             $setCDIEligibilityData[$value->first_choice]['b_info'] = 5;
                //             $setCDIEligibilityData[$value->first_choice]['c_info'] = 0;
                //             $setCDIEligibilityData[$value->first_choice]['d_info'] = 0;
                //             $setCDIEligibilityData[$value->first_choice]['e_info'] = 0;
                //             $setCDIEligibilityData[$value->first_choice]['susp'] = 2;
                //             $setCDIEligibilityData[$value->first_choice]['susp_days'] = 4;
                //         }
            }
        }
        foreach ($secondData as $value) {
            if (!in_array($value->second_choice, $cdi_not_required)) {
                $eligibilityData_cd = getEligibilitiesDynamicProcessing($value->second_choice, 'Conduct Disciplinary Info');
                if (count($eligibilityData_cd) <= 0) {
                    $cdi_not_required[] = $value->second_choice;
                }
                //         $data = getSetEligibilityDataDynamic($value->second_choice, 8);
                //         if(!empty($data))
                //         {
                //             $setCDIEligibilityData[$value->second_choice]['b_info'] = $data->B[0];
                //             $setCDIEligibilityData[$value->second_choice]['c_info'] = $data->C[0];
                //             $setCDIEligibilityData[$value->second_choice]['d_info'] = $data->D[0];
                //             $setCDIEligibilityData[$value->second_choice]['e_info'] = $data->E[0];
                //             $setCDIEligibilityData[$value->second_choice]['susp'] = $data->Susp[0];
                //             $setCDIEligibilityData[$value->second_choice]['susp_days'] = $data->SuspDays[0];
                //         }
                //         else
                //         {
                //             $setCDIEligibilityData[$value->second_choice]['b_info'] = 5;
                //             $setCDIEligibilityData[$value->second_choice]['c_info'] = 0;
                //             $setCDIEligibilityData[$value->second_choice]['d_info'] = 0;
                //             $setCDIEligibilityData[$value->second_choice]['e_info'] = 0;
                //             $setCDIEligibilityData[$value->second_choice]['susp'] = 2;
                //             $setCDIEligibilityData[$value->second_choice]['susp_days'] = 4;
                //         }
            }
        }



        $setEligibilityData = ["841" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "941" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "847" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "850" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "843" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "840" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "852" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "848" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "854" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "835" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "839" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "844" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "842" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "836" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "851" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "855" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "849" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "846" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70], "845" => ["re" => 70, "eng" => 70, "math" => 70, "sci" => 70, "ss" => 70]];


        //        $setCDIEligibilityData = ["841" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "941" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "847" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "850" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "843" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "840" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "852" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "848" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "854" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "835" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "839" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "844" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "842" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "836" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "851" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "855" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "849" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "846" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "845" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4]];
        $setCDIEligibilityData = array("868" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "861" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "858" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "866" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "869" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "860" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "859" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "864" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "862" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "865" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "870" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "942" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "863" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "867" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4], "872" => ["b_info" => 5, "c_info" => 0, "d_info" => 0, "e_info" => 0,  "susp" => 2, "susp_days" => 4]);



        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
            ->where("late_submission", "Y")
            ->where("enrollment_id", Session::get("enrollment_id"))
            ->whereIn('submission_status', array('Active'))
            // ->limit(5)
            ->get();

        foreach ($submissions as $key => $value) {
            if ($value->next_grade == 'K') {

                $rs = Submissions::where("id", $value->id)->update(['first_cdi_status' => 'Pass']);
                if ($value->second_choice != '') {
                    $rs = Submissions::where("id", $value->id)->update(['second_cdi_status' => 'Pass']);
                }
            }

            $cdi_data = DB::table("submission_conduct_discplinary_info")->where("submission_id", $value->id)->first();
            if (!empty($cdi_data)) {
                $cdiArr = array();
                $cdiArr['b_info'] = $cdi_data->b_info;
                $cdiArr['c_info'] = $cdi_data->c_info;
                $cdiArr['d_info'] = $cdi_data->d_info;
                $cdiArr['e_info'] = $cdi_data->e_info;
                $cdiArr['susp'] = $cdi_data->susp;
                $cdiArr['susp_days'] = $cdi_data->susp_days;
            } elseif ($value->cdi_override == "Y") {
                $cdiArr = array();
                $cdiArr['b_info'] = 0;
                $cdiArr['c_info'] = 0;
                $cdiArr['d_info'] = 0;
                $cdiArr['e_info'] = 0;
                $cdiArr['susp'] = 0;
                $cdiArr['susp_days'] = 0;
            } elseif (!in_array($value->first_choice, $cdi_not_required) && $value->next_grade !== 'K') {
                // $incomplete_reason = "CDI";
                // $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value->id, "version"=>$version, "enrollment_id"=>Session::get("enrollment_id")], ["first_choice_final_status"=>"Denied Due To Incomplete Records", "first_offered_rank"=> 0, "first_waitlist_for"=>$value['first_choice_program_id'], "second_choice_final_status"=>"Denied Due To Incomplete Records", "second_offered_rank"=>0, "second_waitlist_for"=>$value['second_choice_program_id'], 'incomplete_reason'=>$incomplete_reason, "version"=>$version, "enrollment_id"=>Session::get("enrollment_id")]);

                $rs = Submissions::where("id", $value->id)->update(['first_cdi_status' => "Incomplete", "second_cdi_status" => "Incomplete"]);

                continue;
            }

            if ($value->cdi_override == "Y" || in_array($value->first_choice, $cdi_not_required) || $value->next_grade == 'K')
                $cdi_status = "Pass";
            else {
                $cdi_status = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->first_choice);
            }
            $rs = Submissions::where("id", $value->id)->update(['first_cdi_status' => $cdi_status]);

            if ($value->second_choice != '' && $value->second_choice != 0) {
                if ($value->cdi_override == "Y" || in_array($value->second_choice, $cdi_not_required) || $value->next_grade == 'K')
                    $cdi_status = "Pass";
                else {
                    $cdi_status = $this->checkCDIStatus($setCDIEligibilityData, $cdiArr, $value->second_choice);
                }
                $rs = Submissions::where("id", $value->id)->update(['second_cdi_status' => $cdi_status]);
            }
        }
    }



    public function generateLatePriorityStatus()
    {
        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
            ->where("late_submission", "Y")
            ->where("enrollment_id", Session::get("enrollment_id"))
            ->whereIn('submission_status', array('Active'))
            // ->limit(5)
            ->get(); // , 'Pending'
        foreach ($submissions as $value) {
            $rank = $this->priorityCalculate($value, "first");
            $rs = Submissions::where("id", $value->id)->update(["first_rank" => $rank]);

            if ($value->second_choice != '' && $value->second_choice != '') {
                $rank = $this->priorityCalculate($value, "second");
                $rs = Submissions::where("id", $value->id)->update(["second_rank" => $rank]);
            }
        }
        echo "Done";
    }



    public function onlyLateProcessing()
    {
        $rs = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $version = $rs + 1;

        $rsD = LateSubmissionFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->delete();

        $rsWt = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        if ($rsWt > 0) {
            $id = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first()->version;
        } else {
            $id = 0;
        }

        $availabilityArray = array();
        $allProgram = Availability::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("district_id", Session::get("district_id"))->get(['program_id']);
        foreach ($allProgram as $key => $value) {
            $avail_grade = Availability::where("district_id", Session::get("district_id"))->where("program_id", $value->program_id)->get();
            foreach ($avail_grade as $gkey => $gvalue) {

                $offer_count = app('App\Modules\Waitlist\Controllers\WaitlistController')->get_offer_count($value->program_id, $gvalue->grade, Session::get("district_id"), 1);
                //echo $value->grade
                $rs = WaitlistAvailabilityLog::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();
                if (!empty($rs)) {
                    $wt_count = $rs->withdrawn_seats;
                } else {
                    $wt_count = 0;
                }

                $rs = LateSubmissionAvailability::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();
                if (!empty($rs)) {
                    $lt_count = $rs->withdrawn_seats;
                } else {
                    $lt_count = 0;
                }
                $availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats + $wt_count + $lt_count - $offer_count;
            }
        }

        /* Code to Process Earlier Waitlist before Processing Regular Submissions */

        $tstArray = $this->late_submission_wailist_calculate();
        $firstdata = $tstArray['firstdata'];
        $seconddata = $tstArray['seconddata'];



        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }

        $tmpAvailability = $availabilityArray;
        $waitlistArr = $offeredRank = $firstOffered = array();



        foreach ($firstdata as $key => $value) {
            if (isset($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']])) {

                if ($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] > 0) {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                    $firstOffered[] = $value['id'];
                    if (isset($offeredRank[$value['first_choice_program_id']])) {
                        $offeredRank[$value['first_choice_program_id']] = $offeredRank[$value['first_choice_program_id']] + 1;
                    } else {
                        $offeredRank[$value['first_choice_program_id']] = 1;
                    }

                    $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] - 1;
                    do {
                        $code = mt_rand(100000, 999999);
                        $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                        $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                        $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                    } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Offered", "first_offered_rank" => $offeredRank[$value['first_choice_program_id']], "first_waitlist_for" => $value['first_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);

                    $pname = getProgramName($value['first_choice_program_id']);
                    $rU = Submissions::where("id", $value['id'])->update(array("awarded_school" => $pname));
                } else {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['first_choice_program_id']])) {
                        $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['first_choice_program_id']] = 1;
                    }

                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                if (isset($waitlistArr[$value['first_choice_program_id']])) {
                    $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                } else {
                    $waitlistArr[$value['first_choice_program_id']] = 1;
                }
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        foreach ($seconddata as $key => $value) {

            if (isset($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']])) {
                if ($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] > 0 && !in_array($value['id'], $firstOffered)) {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                    $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] - 1;

                    if (isset($offeredRank[$value['second_choice_program_id']])) {
                        $offeredRank[$value['second_choice_program_id']] = $offeredRank[$value['second_choice_program_id']] + 1;
                    } else {
                        $offeredRank[$value['second_choice_program_id']] = 1;
                    }
                    do {
                        $code = mt_rand(100000, 999999);
                        $user_code1 = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                        $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        $user_code3 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                    } while (!empty($user_code1) && !empty($user_code2) && !empty($user_code3));

                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Offered", "second_offered_rank" => $offeredRank[$value['second_choice_program_id']], "second_waitlist_for" => $value['second_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    $pname = getProgramName($value['second_choice_program_id']);
                    $rU = Submissions::where("id", $value['id'])->update(array("awarded_school" => $pname));
                } else {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['second_choice_program_id']])) {
                        $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['second_choice_program_id']] = 1;
                    }

                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                if (isset($waitlistArr[$value['second_choice_program_id']])) {
                    $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                } else {
                    $waitlistArr[$value['second_choice_program_id']] = 1;
                }
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }
        $firstdata = $seconddata = array();

        $subjects = $terms = array();
        $eligibilityArr = array();
        $application_ids = [];
        $rsAppData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("late_submission", "Y")->whereIn("submission_status", array('Active', 'Pending'))->get(["application_id"]); //->where("id", 8839)
        foreach ($rsAppData as $rkey => $rvalue) {
            $application_ids[] = $rvalue->application_id;
        }
        $calc_type = "DD";
        $academic_year = $calc_type_arr = [];


        /* Get CDI Data */
        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
            ->where("late_submission", "Y")
            ->where("enrollment_id", Session::get("enrollment_id"))
            ->whereIn('submission_status', array('Active', 'Pending'))
            //->where("id", 8839)
            // ->limit(5)
            ->get();


        $firstdata = $seconddata = array();
        $programGrades = array();
        foreach ($submissions as $key => $value) {
            if ($value->submission_status == "Pending") {
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value->id, "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied Due To Incomplete Records", "first_offered_rank" => 0, "first_waitlist_for" => $value['first_choice_program_id'], "second_choice_final_status" => "Denied Due To Incomplete Records", "second_offered_rank" => 0, "second_waitlist_for" => $value['second_choice_program_id'], 'incomplete_reason' => "Pending Status", "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                continue;
            }
            if ($value->first_grade_status == "Pending" && $value->second_grade_status == "Pending") {
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value->id, "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied Due To Incomplete Records", "first_offered_rank" => 0, "first_waitlist_for" => $value['first_choice_program_id'], "second_choice_final_status" => "Denied Due To Incomplete Records", "second_offered_rank" => 0, "second_waitlist_for" => $value['second_choice_program_id'], 'incomplete_reason' => "Pending Status", "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                continue;
            }
            if ($value->first_grade_status == "Fail" || $value->second_grade_status == "Fail") {
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied due to Ineligibility", "first_waitlist_for" => $value['first_choice_program_id'], "first_choice_eligibility_reason" => "Grade", "version" => $version, "second_choice_final_status" => "Denied due to Ineligibility", "second_waitlist_for" => $value['second_choice_program_id'], "enrollment_id" => Session::get("enrollment_id")]);
                continue;
            }
            if ($value->first_cdi_status == "Fail" || $value->second_cdi_status == "Fail") {
                $data = [];
                $data['submission_id'] = $value->id;
                $data['version'] = $version;
                $data['enrollment_id'] = Session::get("enrollment_id");
                $data['first_choice_final_status'] = 'Denied due to Ineligibility';
                $data['first_choice_eligibility_reason'] = 'CDI';
                if ($value->second_choice != '') {
                    $data['second_choice_final_status'] = 'Denied due to Ineligibility';
                    $data['second_choice_eligibility_reason'] = 'CDI';
                }
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], $data);
                continue;
            }

            if ($value->first_choice != "" && $value->second_choice != "") { //1

                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice'] = $value->first_choice;
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['second_program'] = "";
                //$tmp['score'] = $score;
                //$tmp['cdi'] = $cdiArr;
                $tmp['cdi_status'] = $value->first_cdi_status;
                $tmp['rank'] = $value->first_rank;
                $tmp['grade_status'] = $value->first_grade_status;
                $firstdata[] = $tmp;

                $tmp['grade_status'] = $value->second_grade_status;
                $tmp['rank'] = $value->second_rank;
                $tmp['cdi_status'] = $value->second_cdi_status;
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_program'] = "";
                $seconddata[] = $tmp;
            } //1
            elseif ($value->first_choice != "") { //2
                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_program'] = "";
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['rank'] = $value->first_rank;
                $tmp['cdi_status'] = $value->first_cdi_status;
                $tmp['grade_status'] = $value->first_grade_status;
                $firstdata[] = $tmp;
            } //2
            else { //3
                $tmp = $this->convertToArray($value);
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['first_program'] = "";
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['rank'] = $value->second_rank;
                $tmp['grade_status'] = $value->second_grade_status;
                $tmp['cdi_status'] = $value->second_cdi_status;
                $seconddata[] = $tmp;
            } //3
        }

        //dd($firstdata, $seconddata);
        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }
        //$tmpAvailability = $availabilityArray;

        // 3177
        $waitlistArr = $offeredRank = $firstOffered = array();

        foreach ($firstdata as $key => $value) {
            if ($value['grade_status'] == "Pass" && $value['cdi_status'] == "Pass") {
                if (isset($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']])) {

                    if ($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] > 0) {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $firstOffered[] = $value['id'];
                        if (isset($offeredRank[$value['first_choice_program_id']])) {
                            $offeredRank[$value['first_choice_program_id']] = $offeredRank[$value['first_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['first_choice_program_id']] = 1;
                        }

                        $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] - 1;
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));
                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Offered", "first_offered_rank" => $offeredRank[$value['first_choice_program_id']], "first_waitlist_for" => $value['first_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                        $pname = getProgramName($value['first_choice_program_id']);
                        $rU = Submissions::where("id", $value['id'])->update(array("awarded_school" => $pname));
                    } else {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['first_choice_program_id']])) {
                            $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['first_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['first_choice_program_id']])) {
                        $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['first_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $firstdata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Eligibility</div>";
                if ($value['cdi_status'] == "Fail" && $value['grade_status'] == "Fail") {
                    $first_choice_eligibility_reason = "Both";
                } elseif ($value['cdi_status'] == "Fail") {
                    $first_choice_eligibility_reason = "CDI";
                } else {
                    $first_choice_eligibility_reason = "Grade";
                }

                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied due to Ineligibility", "first_waitlist_for" => $value['first_choice_program_id'], "first_choice_eligibility_reason" => $first_choice_eligibility_reason, "version" => $version, "second_choice_final_status" => "Denied due to Ineligibility", "second_waitlist_for" => $value['second_choice_program_id'], "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        foreach ($seconddata as $key => $value) {
            if ($value['grade_status'] == "Pass" && $value['cdi_status'] == "Pass") {
                if (isset($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']])) {
                    if ($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] > 0 && !in_array($value['id'], $firstOffered)) {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] - 1;

                        if (isset($offeredRank[$value['second_choice_program_id']])) {
                            $offeredRank[$value['second_choice_program_id']] = $offeredRank[$value['second_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['second_choice_program_id']] = 1;
                        }
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Offered", "second_offered_rank" => $offeredRank[$value['second_choice_program_id']], "second_waitlist_for" => $value['second_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                        $pname = getProgramName($value['second_choice_program_id']);
                        $rU = Submissions::where("id", $value['id'])->update(array("awarded_school" => $pname));
                    } else {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['second_choice_program_id']])) {
                            $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['second_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['second_choice_program_id']])) {
                        $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['second_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $seconddata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Ineligibility</div>";
                if ($value['cdi_status'] == "Fail" && $value['grade_status'] == "Fail") {
                    $second_choice_eligibility_reason = "Both";
                } elseif ($value['cdi_status'] == "Fail") {
                    $second_choice_eligibility_reason = "CDI";
                } else {
                    $second_choice_eligibility_reason = "Grade";
                }

                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Denied due to Ineligibility", "second_waitlist_for" => $value['second_choice_program_id'], "second_choice_eligibility_reason" => $second_choice_eligibility_reason, "version" => $version, "first_choice_final_status" => "Denied due to Ineligibility", "first_waitlist_for" => $value['first_choice_program_id'], "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        $rsUpdate = LateSubmissionFinalStatus::where("first_choice_final_status", "Offered")->where('version', $version)->where("enrollment_id", Session::get("enrollment_id"))->where("second_choice_final_status", "Waitlisted")->update(array("second_choice_final_status" => "Pending", "second_waitlist_for" => 0));
    }


    public function onlyLateProcessingIndividual()
    {
        $rs = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
        $version = $rs + 1;

        $from = "wait";

        if ($rs > 0) {
            $id = LateSubmissionProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first()->version;
            $from = "late";
        } else {

            $rsWt = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->count();
            if ($rsWt > 0) {
                $id = WaitlistProcessLogs::where("enrollment_id", Session::get("enrollment_id"))->orderBy("created_at", "DESC")->first()->version;
                $from = "wait";
            } else {
                $id = "regular";
            }
        }

        $availabilityArray = array();
        $parray = $garray = array();

        $rs = LateSubmissionAvailability::get();
        $tprogram_id = [];
        foreach ($rs as $k => $v) {
            $tprogram_id[] = $v->program_id . "-" . $v->grade;
        }
        $allProgram = Availability::distinct()->where("district_id", Session::get("district_id"))->where("enrollment_id", Session::get("enrollment_id"))->get(['program_id']);
        foreach ($allProgram as $key => $value) {

            $avail_grade = Availability::where("district_id", Session::get("district_id"))->where("program_id", $value->program_id)->get();
            foreach ($avail_grade as $gkey => $gvalue) {
                if (in_array($value->program_id . "-" . $gvalue->grade, $tprogram_id)) {
                    $offer_count = app('App\Modules\Waitlist\Controllers\WaitlistController')->get_offer_count($value->program_id, $gvalue->grade, Session::get("district_id"), 1);


                    $rs = WaitlistAvailability::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();
                    if (!empty($rs)) {
                        $garray[] = $gvalue->grade;
                        $parray[] = $value->program_id;
                        $wt_count = $rs->withdrawn_seats;
                        //$availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats + $rs->withdrawn_seats - $offer_count;
                    } else {
                        $wt_count = 0;
                        //$availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats - $offer_count;
                    }

                    $rs = LateSubmissionAvailability::where("program_id", $value->program_id)->where("grade", $gvalue->grade)->first();
                    if (!empty($rs)) {
                        $garray[] = $gvalue->grade;
                        $parray[] = $value->program_id;
                        $lt_count = $rs->withdrawn_seats;
                    } else {
                        $lt_count = 0;
                    }
                    $availabilityArray[$value->program_id][$gvalue->grade] = $gvalue->available_seats + $wt_count + $lt_count - $offer_count;
                }
            }
        }



        LateSubmissionFinalStatus::where("enrollment_id", Session::get("enrollment_id"))->where("version", $version)->delete();

        if ($from  == "regular") {
            $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue) {
                $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
            })->where(function ($q) use ($parray) {
                $q->whereIn("first_choice_program_id", $parray)->orWhereIn("second_choice_program_id", $parray);
            })->join("submissions_final_status", "submissions_final_status.submission_id", "submissions.id")->select("submissions.*", "submissions_final_status.first_offer_status", "submissions_final_status.second_offer_status")
                ->get();
        } elseif ($from == "wait") {
            $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue) {
                $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
            })->where(function ($q) use ($parray) {
                $q->whereIn("first_choice_program_id", $parray)->orWhereIn("second_choice_program_id", $parray);
            })->where("submissions_waitlist_final_status.version", $id)->join("submissions_waitlist_final_status", "submissions_waitlist_final_status.submission_id", "submissions.id")->select("submissions.*", "submissions_waitlist_final_status.first_offer_status", "submissions_waitlist_final_status.second_offer_status")
                ->get();
        } elseif ($from == "late") {
            $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))->where("submissions.enrollment_id", Session::get("enrollment_id"))->where(function ($q) use ($value, $gvalue) {
                $q->where("submission_status", "Waitlisted")->orWhere("submission_status", "Declined / Waitlist for other");
            })->where(function ($q) use ($parray) {
                $q->whereIn("first_choice_program_id", $parray)->orWhereIn("second_choice_program_id", $parray);
            })->where("late_submissions_final_status.version", $id)->join("late_submissions_final_status", "late_submissions_final_status.submission_id", "submissions.id")->select("submissions.*", "late_submissions_final_status.first_offer_status", "late_submissions_final_status.second_offer_status")
                ->get();
        }


        $decWtArry = array();


        foreach ($submissions as $key => $value) {
            if (in_array($value->next_grade, $garray)) {
                if ($value->first_choice != "" && $value->second_choice != "") {

                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['second_program'] = "";
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($value->first_offer_status != "Declined & Waitlisted") {
                        $firstdata[] = $tmp;
                    }

                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_program'] = "";
                    if ($value->second_offer_status != "Declined & Waitlisted") {
                        $seconddata[] = $tmp;
                    }
                } elseif ($value->first_choice != "") {
                    $tmp = $this->convertToArray($value);
                    $choice = getApplicationProgramName($value->first_choice);
                    $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['second_program'] = "";
                    $tmp['rank'] = $this->priorityCalculate($value, "first");
                    if ($value->first_offer_status != "Declined & Waitlisted") {
                        $firstdata[] = $tmp;
                    }
                } else {
                    $tmp = $this->convertToArray($value);
                    $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                    $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                    $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                    $tmp['first_program'] = "";
                    $tmp['first_choice'] = $value->first_choice;
                    $tmp['second_choice'] = $value->second_choice;
                    $tmp['rank'] = $this->priorityCalculate($value, "second");
                    if ($value->second_offer_status != "Declined & Waitlisted") {
                        $seconddata[] = $tmp;
                    }
                }
            }
        }

        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }

        $application_ids = [];
        $rsAppData = Submissions::distinct()->where("enrollment_id", Session::get("enrollment_id"))->where("late_submission", "Y")->get(["application_id"]);
        foreach ($rsAppData as $rkey => $rvalue) {
            $application_ids[] = $rvalue->application_id;
        }

        $tmpAvailability = $availabilityArray;
        $waitlistArr = $offeredRank = $firstOffered = array();

        if (!empty($firstdata)) {
            foreach ($firstdata as $key => $value) {
                if (isset($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']])) {

                    if ($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] > 0 && in_array($value['first_choice_program_id'], $parray)) {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $firstOffered[] = $value['id'];
                        if (isset($offeredRank[$value['first_choice_program_id']])) {
                            $offeredRank[$value['first_choice_program_id']] = $offeredRank[$value['first_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['first_choice_program_id']] = 1;
                        }

                        $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] - 1;
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));
                        $rs = LateSubmissionFinalStatus::updateOrCreate(["enrollment_id" => Session::get("enrollment_id"), "submission_id" => $value['id'], "version" => $version], ["first_choice_final_status" => "Offered", "first_offered_rank" => $offeredRank[$value['first_choice_program_id']], "first_waitlist_for" => $value['first_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    } else {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['first_choice_program_id']])) {
                            $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['first_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "enrollment_id" => Session::get("enrollment_id"), "version" => $version], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['first_choice_program_id']])) {
                        $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['first_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            }
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {

                if (isset($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']])) {
                    if ($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] > 0 && !in_array($value['id'], $firstOffered)  && in_array($value['second_choice_program_id'], $parray)) {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] - 1;

                        if (isset($offeredRank[$value['second_choice_program_id']])) {
                            $offeredRank[$value['second_choice_program_id']] = $offeredRank[$value['second_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['second_choice_program_id']] = 1;
                        }
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Offered", "second_offered_rank" => $offeredRank[$value['second_choice_program_id']], "second_waitlist_for" => $value['second_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    } else {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['second_choice_program_id']])) {
                            $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['second_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['second_choice_program_id']])) {
                        $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['second_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            }
        }




        /* Code for Late Submissions */
        $firstdata = $seconddata = array();

        $subjects = $terms = array();
        $eligibilityArr = array();
        /* Get CDI Data */

        $submissions = Submissions::where('submissions.district_id', Session::get('district_id'))
            ->where("submissions.enrollment_id", Session::get("enrollment_id"))
            ->where("late_submission", "Y")
            ->where(function ($q) use ($parray) {
                $q->whereIn("first_choice_program_id", $parray)->orWhereIn("second_choice_program_id", $parray);
            })
            ->whereIn("next_grade", $garray)
            ->whereIn('submission_status', array('Active', 'Pending'))
            // ->limit(5)
            ->get();

        $firstdata = $seconddata = array();
        $programGrades = array();

        foreach ($submissions as $key => $value) {

            if ($value->submission_status == "Pending") {
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value->id, "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied Due To Incomplete Records", "first_offered_rank" => 0, "first_waitlist_for" => $value['first_choice_program_id'], "second_choice_final_status" => "Denied Due To Incomplete Records", "second_offered_rank" => 0, "second_waitlist_for" => $value['second_choice_program_id'], 'incomplete_reason' => "Pending Status", "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                continue;
            }
            if ($value->first_grade_status == "Pending" && $value->second_grade_status == "Pending") {
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value->id, "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied Due To Incomplete Records", "first_offered_rank" => 0, "first_waitlist_for" => $value['first_choice_program_id'], "second_choice_final_status" => "Denied Due To Incomplete Records", "second_offered_rank" => 0, "second_waitlist_for" => $value['second_choice_program_id'], 'incomplete_reason' => "Pending Status", "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                continue;
            }
            if ($value->first_grade_status == "Fail" || $value->second_grade_status == "Fail") {
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied due to Ineligibility", "first_waitlist_for" => $value['first_choice_program_id'], "first_choice_eligibility_reason" => "Grade", "version" => $version, "second_choice_final_status" => "Denied due to Ineligibility", "second_waitlist_for" => $value['second_choice_program_id'], "enrollment_id" => Session::get("enrollment_id")]);
                continue;
            }
            if ($value->first_cdi_status == "Fail" || $value->second_cdi_status == "Fail") {
                $data = [];
                $data['submission_id'] = $value->id;
                $data['version'] = $version;
                $data['enrollment_id'] = Session::get("enrollment_id");
                $data['first_choice_final_status'] = 'Denied due to Ineligibility';
                $data['first_choice_eligibility_reason'] = 'CDI';
                if ($value->second_choice != '') {
                    $data['second_choice_final_status'] = 'Denied due to Ineligibility';
                    $data['second_choice_eligibility_reason'] = 'CDI';
                }
                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], $data);
                continue;
            }


            if ($value->first_choice != "" && $value->second_choice != "") { //1

                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice'] = $value->first_choice;
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['second_program'] = "";
                $tmp['score'] = $score;
                $tmp['cdi'] = $cdiArr;
                $tmp['cdi_status'] = $value->first_cdi_status;
                $tmp['rank'] = $value->first_rank;
                $tmp['grade_status'] = $value->first_grade_status;
                $firstdata[] = $tmp;

                $tmp['grade_status'] = $value->second_grade_status;
                $tmp['rank'] = $value->second_rank;
                $tmp['cdi_status'] = $value->second_cdi_status;
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_program'] = "";
                $seconddata[] = $tmp;
            } //1
            elseif ($value->first_choice != "") { //2
                $tmp = $this->convertToArray($value);
                $choice = getApplicationProgramName($value->first_choice);
                $tmp['first_program'] = getApplicationProgramName($value->first_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['second_program'] = "";
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['rank'] = $value->first_rank;
                $tmp['cdi_status'] = $value->first_cdi_status;
                $tmp['grade_status'] = $value->first_grade_status;
                $firstdata[] = $tmp;
            } //2
            else { //3
                $tmp = $this->convertToArray($value);
                $tmp['second_program'] = getApplicationProgramName($value->second_choice);
                $tmp['first_choice_program_id'] = $value->first_choice_program_id;
                $tmp['second_choice_program_id'] = $value->second_choice_program_id;
                $tmp['first_program'] = "";
                $tmp['first_choice'] = $value->first_choice;
                $tmp['second_choice'] = $value->second_choice;
                $tmp['rank'] = $value->second_rank;
                $tmp['grade_status'] = $value->second_grade_status;
                $tmp['cdi_status'] = $value->second_cdi_status;
                $seconddata[] = $tmp;
            }
        }
        if (!empty($firstdata)) {
            $f_siblings = $s_siblings = $f_lottery_numbers = $s_lottery_numbers = $f_status = $s_status = array();
            foreach ($firstdata as $key => $value) {
                $f_siblings['rank'][] = $value['rank'];
                $f_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($f_siblings['rank'], SORT_ASC, $f_lottery_numbers['lottery_number'], SORT_DESC, $firstdata);
        }

        if (!empty($seconddata)) {
            foreach ($seconddata as $key => $value) {
                $s_siblings['rank'][] = $value['rank'];
                $s_lottery_numbers['lottery_number'][] = $value['lottery_number'];
            }
            array_multisort($s_siblings['rank'], SORT_ASC, $s_lottery_numbers['lottery_number'], SORT_DESC, $seconddata);
        }


        //$tmpAvailability = $availabilityArray;
        $waitlistArr = $offeredRank = $firstOffered = array();
        foreach ($firstdata as $key => $value) {
            if ($value['grade_status'] == "Pass" && $value['cdi_status'] == "Pass") {
                if (isset($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']])) {

                    if ($tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] > 0) {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $firstOffered[] = $value['id'];
                        if (isset($offeredRank[$value['first_choice_program_id']])) {
                            $offeredRank[$value['first_choice_program_id']] = $offeredRank[$value['first_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['first_choice_program_id']] = 1;
                        }

                        $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['first_choice_program_id']][$value['next_grade']] - 1;
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Offered", "first_offered_rank" => $offeredRank[$value['first_choice_program_id']], "first_waitlist_for" => $value['first_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    } else {
                        $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['first_choice_program_id']])) {
                            $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['first_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $firstdata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['first_choice_program_id']])) {
                        $waitlistArr[$value['first_choice_program_id']] = $waitlistArr[$value['first_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['first_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Waitlisted", "first_waitlist_for" => $value['first_choice_program_id'], "first_waitlist_number" => $waitlistArr[$value['first_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $firstdata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Eligibility</div>";
                if ($value['cdi_status'] == "Fail" && $value['grade_status'] == "Fail") {
                    $first_choice_eligibility_reason = "Both";
                } elseif ($value['cdi_status'] == "Fail") {
                    $first_choice_eligibility_reason = "CDI";
                } else {
                    $first_choice_eligibility_reason = "Grade";
                }

                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["first_choice_final_status" => "Denied due to Ineligibility", "first_waitlist_for" => $value['first_choice_program_id'], "first_choice_eligibility_reason" => $first_choice_eligibility_reason, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }

        foreach ($seconddata as $key => $value) {
            if ($value['grade_status'] == "Pass" && $value['cdi_status'] == "Pass") {
                if (isset($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']])) {
                    if ($tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] > 0 && !in_array($value['id'], $firstOffered)) {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-success'>Offered</div>";
                        $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] = $tmpAvailability[$value['second_choice_program_id']][$value['next_grade']] - 1;

                        if (isset($offeredRank[$value['second_choice_program_id']])) {
                            $offeredRank[$value['second_choice_program_id']] = $offeredRank[$value['second_choice_program_id']] + 1;
                        } else {
                            $offeredRank[$value['second_choice_program_id']] = 1;
                        }
                        do {
                            $code = mt_rand(100000, 999999);
                            $user_code = LateSubmissionFinalStatus::where('offer_slug', $code)->first();
                            $user_code1 = SubmissionsWaitlistFinalStatus::where('offer_slug', $code)->first();
                            $user_code2 = SubmissionsFinalStatus::where('offer_slug', $code)->first();
                        } while (!empty($user_code) && !empty($user_code1) && !empty($user_code2));

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Offered", "second_offered_rank" => $offeredRank[$value['second_choice_program_id']], "second_waitlist_for" => $value['second_choice_program_id'], 'offer_slug' => $code, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    } else {
                        $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                        if (isset($waitlistArr[$value['second_choice_program_id']])) {
                            $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                        } else {
                            $waitlistArr[$value['second_choice_program_id']] = 1;
                        }

                        $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                    }
                } else {
                    $seconddata[$key]['final_status'] = "<div class='alert1 alert-warning'>Wait Listed</div>";
                    if (isset($waitlistArr[$value['second_choice_program_id']])) {
                        $waitlistArr[$value['second_choice_program_id']] = $waitlistArr[$value['second_choice_program_id']] + 1;
                    } else {
                        $waitlistArr[$value['second_choice_program_id']] = 1;
                    }
                    $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Waitlisted", "second_waitlist_for" => $value['second_choice_program_id'], "second_waitlist_number" => $waitlistArr[$value['second_choice_program_id']], "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
                }
            } else {
                $seconddata[$key]['final_status'] = "<div class='alert1 alert-danger'>Declined due to Ineligibility</div>";
                if ($value['cdi_status'] == "Fail" && $value['grade_status'] == "Fail") {
                    $second_choice_eligibility_reason = "Both";
                } elseif ($value['cdi_status'] == "Fail") {
                    $second_choice_eligibility_reason = "CDI";
                } else {
                    $second_choice_eligibility_reason = "Grade";
                }

                $rs = LateSubmissionFinalStatus::updateOrCreate(["submission_id" => $value['id'], "version" => $version, "enrollment_id" => Session::get("enrollment_id")], ["second_choice_final_status" => "Denied due to Ineligibility", "second_waitlist_for" => $value['second_choice_program_id'], "second_choice_eligibility_reason" => $second_choice_eligibility_reason, "version" => $version, "enrollment_id" => Session::get("enrollment_id")]);
            }
        }
        echo "Done";
    }
}
