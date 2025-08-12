<?php
    ini_set("error_reporting","1");
    set_time_limit(0);
    ini_set('memory_limit','2048M');
    
    include("common_functions.php");     
    include("dbClass.php");
    $objDB = new MySQLCN;

    
    $SQL = "SELECT student_id, application_id, id FROM submissions WHERE application_id = 61 AND student_id != '' AND grade_find = 'N' LIMIT 1";
    $rsdata = $objDB->select($SQL);
    if(count($rsdata) < 0)
    {
        echo "Done";
        exit;
    }

    $submission_id = $rsdata[0]['id'];

   

      
    $stateID = $rsdata[0]['student_id'];

        $courseType = array("47"=>"","62"=>"AAS Elective","57"=>"AAS English Requirement","58"=>"AAS Math Requirement","50"=>"Special Education AAS Course","59"=>"AAS Science Requirement","60"=>"AAS Social Studies Requirement","48"=>"FineArt/CareerTech/ForeignLang","1"=>"Agriscience","51"=>"Homeroom Courses and Schedule Fillers","40"=>"BASIC COURSEBAS","46"=>"Behavioral Skills","41"=>"Bus Edu Tech Elec BT","42"=>"Bus/Mktg Edu BTE","3"=>"Bus/Mktg Educ","5"=>"Career Technologies","6"=>"Computer Requirement","4"=>"Cooperative Ed","44"=>"Day Trades Elec DT","7"=>"Driver Ed Elective","10"=>"Elective","8"=>"English Certificate","9"=>"English Elective","11"=>"English","20"=>"Essential Career Tech","53"=>"Essential English Requirement","33"=>"Essential Math Elective","54"=>"Essential Math Requirement","66"=>"Essential Pathway Elective","36"=>"Essential Science Elective","55"=>"Essential Science Requirement","56"=>"Essential Social Studies Requirement","12"=>"Fam/cons/sci Elect.","2"=>"Fine Arts","16"=>"Foreign Language","13"=>"Health Required","14"=>"Health Science Educ.","15"=>"JROTC","43"=>"Keyboarding Required Key","17"=>"Math Certificate","19"=>"Math Elective","18"=>"Math","38"=>"NA","22"=>"Occ Dip Career Prep","21"=>"Occ Dip English Req.","23"=>"Occ Dip Mathematics","24"=>"Occ Dip Science","25"=>"Occ.Dip.Coop.Tech","45"=>"Phys Ed. Elective","26"=>"Physical Ed Required","39"=>"Reading","27"=>"Science Certificate","29"=>"Science Elective","30"=>"Science","28"=>"Soc.Studies Cert.","34"=>"Soc.Studies Elective","35"=>"Social Studies","63"=>"Spec Ed Elective","31"=>"Spec Ed Eng Elective","52"=>"Special Education Gifted Course","32"=>"Spec Ed Hy Elective","37"=>"Technical Education");
    
        $acad_sessions = fetch_inow_details( 'acadsessions' );
 
        $sess_arr = $session_year = array();
        foreach($acad_sessions as $acad_session){
                if(in_array($acad_session->Name, array("2020-2021")))
                {
                    $sess_arr[] = $acad_session->Id;
                    $session_year[$acad_session->Id] = $acad_session->Name; 
                }
            }

        $sections = $graded_items_hash = $grading_periods_hash = array();

        $SQL = "SELECT stateID, student_id FROM student WHERE stateID = '".$stateID."'";
        $rs = $objDB->select($SQL);

        $arr = $arr1 = array();
         for($i=0; $i < count($rs); $i++)
         {
            $arr[] = $rs[$i]['student_id'];
            $arr1[] = $rs[$i]['stateID'];
        }

        $teacherArr = $gradeItemIdArr = $gradePeriodArr = $sectionArr = array();
        foreach($arr as $ak=>$av)
        {
            $endpoint = "students/".$av."/acadSessions";
            $academic_sessions = fetch_inow_details($endpoint);
            $acd = $academic_sessions;
            
            foreach($acd as $key=>$value)
            {
                if(in_array($value->AcadSessionId, $sess_arr))
                {
                        $endpoint = $value->AcadSessionId."/students/".$av."/grades";
                        $grade_data = fetch_inow_details($endpoint);

                        foreach($grade_data as $gkey=>$gvalue)
                        {
                            $data = array();
                            $ins = array();
                            $data['stateID'] = $arr1[$ak]; //0
                            $data['academic_session'] = $value->AcadSessionId;
                            $data['NumericGrade'] = $gvalue->NumericGrade;

                            $sectionId = $gvalue->SectionId;
                            if(isset($sectionArr[$sectionId]))
                            {
                                $tmp = $sectionArr[$sectionId];
                                $data['CourseId'] = $tmp['CourseId'];
                                $data['CourseNumber'] = $tmp['CourseNumber'];
                                $data['CourseTypeId'] = $tmp['CourseTypeId'];
                                $data['FullName'] = $tmp['FullName'];
                                $data['FullSectionNumber'] = $tmp['FullSectionNumber'];
                                $data['MaxGradeLevelId'] = $tmp['MaxGradeLevelId'];
                                $data['StateCourseNumber'] = $tmp['StateCourseNumber'];
                                $data['ShortName'] = $tmp['ShortName'];
                            }
                            else
                            {
                                $endpoint = $value->AcadSessionId."/sections/".$sectionId;
                                $section_data = fetch_inow_details($endpoint);
                                $data['CourseId'] = addslashes($section_data->CourseId);
                                $data['CourseNumber'] = addslashes($section_data->CourseNumber);
                                $data['CourseTypeId'] = addslashes($section_data->CourseTypeId);
                                $data['FullName'] = addslashes($section_data->FullName);
                                $data['FullSectionNumber'] = addslashes($section_data->FullSectionNumber);
                                $data['MaxGradeLevelId'] = addslashes($section_data->MaxGradeLevelId);
                                $data['StateCourseNumber'] = addslashes($section_data->StateCourseNumber);
                                $data['ShortName'] = addslashes($section_data->ShortName);
                            
                                $tmp = array();
                                $tmp['CourseId'] = addslashes($section_data->CourseId);
                                $tmp['CourseNumber'] = addslashes($section_data->CourseNumber);
                                $tmp['CourseTypeId'] = addslashes($section_data->CourseTypeId);
                                $tmp['FullName'] = addslashes($section_data->FullName);
                                $tmp['FullSectionNumber'] = addslashes($section_data->FullSectionNumber);
                                $tmp['MaxGradeLevelId'] = addslashes($section_data->MaxGradeLevelId);
                                $tmp['StateCourseNumber'] = addslashes($section_data->StateCourseNumber);
                                $tmp['ShortName'] = addslashes($section_data->ShortName);
                                $sectionArr[$sectionId] = $tmp;
                            }

                            $teacher_id = $section_data->PrimaryTeacherId;
                            if(isset($teacherArr[$teacher_id]))
                            {
                                $data['TeacherName']  = $teacherArr[$teacher_id];
                            }
                            else
                            {
                                $endpoint = "persons/".$teacher_id;
                                $teacher_data = fetch_inow_details($endpoint);
                                $data['TeacherName'] = addslashes($teacher_data->DisplayName);
                                $teacherArr[$teacher_id] = $data['TeacherName'];
                            }

                            $grade_item_id = $gvalue->GradedItemId;
                            if(isset($gradeItemIdArr[$grade_item_id]))
                            {
                                $tmp = $gradeItemIdArr[$grade_item_id];
                                $data['GradeDescription'] = $tmp['GradeDescription'];
                                $data['GradeName'] = $tmp['GradeName'];
                                $data['Sequence'] = $tmp['Sequence'];
                                $grade_period_id = $tmp['GradePeriodId'];

                            }
                            else
                            {
                                $endpoint = $value->AcadSessionId."/gradedItems/".$grade_item_id;
                                $grade_item_data = fetch_inow_details($endpoint);


                                $data['GradeDescription'] = addslashes($grade_item_data->Description);
                                $data['GradeName'] = addslashes($grade_item_data->Name);
                                $data['Sequence'] = addslashes($grade_item_data->Sequence);

                                $tmp = array();
                                $tmp['GradeDescription'] = $data['GradeDescription'];
                                $tmp['GradeName'] = $data['GradeName'];
                                $tmp['Sequence'] = $data['Sequence'];
                                $tmp['GradePeriodId'] = $grade_item_data->GradingPeriodId;
                                $gradeItemIdArr[$grade_item_id] = $tmp;
                                $grade_period_id = $grade_item_data->GradingPeriodId;
             
                            }                

                           
                            if(isset($gradePeriodArr[$grade_period_id]))
                            {
                                $tmp = $gradePeriodArr[$grade_period_id];
                                $data['GradeTerm'] = $tmp['GradeTerm'];
                                $data['academicYear'] = $tmp['academicYear'];
                                $data['GradePeriodName'] = $tmp['GradePeriodName'];
                                $data['academicTerm'] = $tmp['academicTerm'];
                                $data['SchoolAnnouncement'] = $tmp['SchoolAnnouncement'];
                            }
                            else
                            {
                                $endpoint = $value->AcadSessionId."/gradingPeriods/".$grade_period_id;
                                $grade_period_data = fetch_inow_details($endpoint);

                                $data['GradeTerm'] = addslashes($grade_period_data->Description);
                                $tmp = explode(" ", $data['GradeTerm']);
                                $data['academicYear'] = $tmp[0]; //1
                                $data['GradePeriodName'] = addslashes($grade_period_data->Name);
                                $data['academicTerm'] = $data['GradePeriodName'];
                                $data['SchoolAnnouncement'] = addslashes($grade_period_data->SchoolAnnouncement);


                                $tmp = array();
                                $tmp['GradeTerm'] = $data['GradeTerm'];
                                $tmp['academicYear'] = $data['academicYear'];
                                $tmp['GradePeriodName'] = $data['GradePeriodName'];
                                $tmp['academicTerm'] = $data['academicTerm'];
                                $tmp['SchoolAnnouncement'] = $data['SchoolAnnouncement'];
                                $gradePeriodArr[$grade_period_id] = $tmp;
             
                            } 
                            $tmp['academicYear'] = $session_year[$value->AcadSessionId];

                            


                            $ins['stateID'] = $data['stateID'];
                            $ins['academicYear'] = $session_year[$value->AcadSessionId];
                            $ins['academicTerm'] = $data['academicTerm'];
                            $ins['GradeName'] = $data['GradeName'];
                            $ins['courseTypeID'] = $data['CourseTypeId'];
                            $ins['sequence'] = $data['Sequence'];
                            $ins['courseType'] = (isset($courseType[$data['CourseTypeId']]) ? $courseType[$data['CourseTypeId']] : 0);
                            $ins['courseFullName'] = $data['FullName'];
                            $ins['courseName'] = $data['ShortName'];
                            $ins['sectionNumber'] = $data['StateCourseNumber'];
                            $ins['fullsection_number'] = $data['FullSectionNumber'];
                            $ins['numericGrade'] = $data['NumericGrade'];


                            $grade_data = [
                                'submission_id' => $submission_id,
                                'stateID' => $stateID,
                                'academicYear' => $ins['academicYear'] ?? null,
                                'academicTerm' => $ins['academicTerm'] ?? null,
                                'courseTypeID' => $ins['courseTypeID'] ?? null,
                                'courseName' => $ins['courseName'] ?? null,
                                'numericGrade' => $ins['numericGrade'] ?? null,
                                'sectionNumber' => $ins['sectionNumber'] ?? null,
                                'courseType' => $ins['courseType'] ?? null,
                                'stateID' => $ins['stateID'] ?? null,
                                'GradeName' => $ins['GradeName'] ?? null,
                                'sequence' => $ins['sequence'] ?? null,
                                'courseFullName' => $ins['courseFullName'] ?? null,
                                'fullsection_number' => $ins['fullsection_number'] ?? null,
                            ];

                        $SQL = "DELETE FROM submission_grade WHERE submission_id = '".$submission_id."' AND stateID = '".$ins['stateID']."' AND  academicYear='".$ins['academicYear']."' AND GradeName = '".$ins['GradeName']."' AND courseType = '".$ins['courseType']."'";
                        $rsDel = $objDB->sql_query($SQL);



                            $SQL = "INSERT INTO submission_grade SET ";
                            foreach($grade_data as $mkey=>$mvalue)
                            {
                                $SQL .= $mkey."='".$mvalue."',";
                            }
                            $SQL = trim($SQL,",");
                            $rs = $objDB->insert($SQL);

                            

                        }
                }

            }

            $SQL = "UPDATE submissions SET grade_find = 'Y' WHERE id = '".$submission_id."'";
            echo $SQL."<BR>";
            $rs = $objDB->sql_query($SQL);    
        }
        echo "Student Grade fetched successfully.";
?>