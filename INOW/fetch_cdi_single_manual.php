`
<?php
    set_time_limit(0);
	ini_set("error_reporting", "1");
    ini_set('memory_limit','2048M');
    
    include("common_functions.php");
    
    
    include("dbClass.php");
    $objDB = new MySQLCN;
    
    $submission_id  = $_REQUEST['id'];

    $SQL = "SELECT student_id, application_id FROM submissions WHERE id = '".$submission_id."'";
    $rs = $objDB->select($SQL);

    if(count($rs) > 0)
    {
        if($rs[0]['student_id'] == '')
        {
            echo "No student";
            exit;
        }
        else
        {
            $stateID = $rs[0]['student_id'];
            $application_id = $rs[0]['application_id'];
            
            $SQL = "SELECT stateID, student_id FROM student_latest WHERE stateID = '".$stateID."'";
            $rs = $objDB->select($SQL);

            if(count($rs) <= 0)
            {
                $SQL = "SELECT stateID, student_id FROM `student_latest-old` WHERE stateID = '".$stateID."'";
                $rs = $objDB->select($SQL);
            }
            
            $arr = $arr1 = array();
            for($i=0; $i < count($rs); $i++)
            {
                $arr[] = $rs[$i]['student_id'];
                $arr1[] = $rs[$i]['stateID'];
            }


            $SQL = "SELECT cdi_starting_date, cdi_ending_date FROM application WHERE id = '".$application_id."'";
            $rsApp = $objDB->select($SQL);
            if(count($rsApp) > 0)
            {
                $start_date = $rsApp[0]['cdi_starting_date'];
                $end_date = $rsApp[0]['cdi_ending_date'];
            }
            else
            {
                $start_date = "2020-01-01";
                $end_date = "2020-12-31";
            }

            $locations = fetch_inow_details("disciplinaryLocations");
            $locations_hash = [];
            foreach( $locations as $location ){
                $locations_hash[ $location->Id ] = $location;
            }
            unset( $locations );

            /* Get Incidents */
            /*$incidents = fetch_inow_details("incidents");
            $incident_hash = [];
            foreach( $incidents as $incident ){
                $incident_hash[ $incident->Id ] = $incident;
            }
            unset( $incidents );*/

            $infractions = fetch_inow_details( 'infractions' );
            $infraction_hash = [];
            foreach( $infractions as $infraction ){
                $infraction_hash[ $infraction->Id ] = $infraction;
            }
            unset( $infractions );  
            

            $infraction_codeArr = array();
            foreach($arr as $key=>$value)
            {

                $endpoint = "students/".$value."/disciplinaryOccurrences";
                $dispData = fetch_inow_details($endpoint);
                echo "<pre>";
                print_r($dispData);
                exit;
                if(count($dispData) > 0)
                {
                    foreach($dispData as $key1=>$value1)
                    {
                        $dataArr = array();
                        $aca_session_id = $value1->AcadSessionId;
                        
                        $endpoint = "students/".$value."/acadSessions/".$aca_session_id;
                        $data = fetch_inow_details($endpoint);
                        
                        $grade_level_id = $data->GradeLevelId;
                        $endpoint = "gradelevels/".$grade_level_id;
                        $data = fetch_inow_details($endpoint);
                        $dataArr['grade'] = $data->Description;

                        if($locations_hash[$value1->DisciplinaryLocationId])
                            $studentArr['location'] = $locations_hash[$value1->DisciplinaryLocationId];
                        else
                            $studentArr['location'] = 0;
                        $dataArr['suspend_days'] = "0";
                        $dataArr['datetime'] = $value1->DateTime;
                        $dataArr['startdate'] = "";
                        $dataArr['enddate'] = "";

                        if(isset($value1->Dispositions[0]))
                        {
                            $dispositionId = $value1->Dispositions[0]->DispositionId;
                            $endpoint = "dispositions/".$dispositionId;
                            $data = fetch_inow_details($endpoint);


                            $dataArr['disposition'] = $data->Description;
                            $dataArr['disposition_type'] = $data->Name;
                            
                            
                            
                            $dataArr['note'] = $value1->Dispositions[0]->Note;

                            if($data->Name=="Suspended/Out of School")
                            {
                                if(isset($value1->Dispositions[0]->StartDateTime) && isset($value1->Dispositions[0]->EndDateTime))
                                {
                                    if($value1->Dispositions[0]->EndDateTime != "" && $value1->Dispositions[0]->StartDateTime != "")
                                    {
                                        $dataArr['suspend_days'] = daydiff($value1->Dispositions[0]->StartDateTime, $value1->Dispositions[0]->EndDateTime);
                                        $dataArr['startdate'] = $value1->Dispositions[0]->StartDateTime;
                                        $dataArr['enddate'] = $value1->Dispositions[0]->EndDateTime;
                                    }
                                }
                            }
                        }
                        else{
                            $dataArr['note'] = "";
                            $dataArr['disposition'] = "";
                            $dataArr['disposition_type'] = "";
                            //$dataArr['startdate'] = "";
                            //  $dataArr['enddate'] = "";
                        }
                        //print_r($value1);exit;
                        if(isset($value1->DisciplinaryActions[0]))
                        {
                            $dispActionId = $value1->DisciplinaryActions[0]->DisciplinaryActionId;
                            $endpoint = "disciplinaryActions/".$dispActionId;
                            $data = fetch_inow_details($endpoint);
                            $dataArr['actioncode'] = $data->Code;
                            $dataArr['actionname'] = $data->Name;
                        }
                        else
                        {
                            $dataArr['actioncode'] = "";
                            $dataArr['actionname'] = "";

                        }
                        
                        if(isset($value1->Infractions))
                        {
                            $infractionid = $value1->Infractions[0]->InfractionId;
                            if(isset($infraction_hash[$infractionid]))
                            {
                                $dataArr['infraction_code'] = $infraction_hash[$infractionid]->Code;
                                $dataArr['infraction_name'] = $infraction_hash[$infractionid]->Name;

                            }
                            else
                            {
                                $dataArr['infraction_code'] = "";
                                $dataArr['infraction_name'] = "";
                                
                            }
                        }
                        else{
                            $dataArr['infraction_code'] = "";
                                $dataArr['infraction_name'] = "";
                        }

                        if($value1->IncidentId != '')
                        {
                            $endpoint = "incidents/".$value1->IncidentId;
                            $data = fetch_inow_details($endpoint);
                            if(!empty($data))
                            {
                                $dataArr['incident_number'] = $data->IncidentNumber;
                            }
                            else
                            {
                                $dataArr['incident_number'] = "";
                            }
                        }
                        else
                        {
                            $dataArr['incident_number'] = "";
                        }
                        $dataArr['student_id'] = $value;
                        $dataArr['stateID'] = $arr1[$key];
                        
                        $delSQL = "DELETE FROM student_cdi_details WHERE stateID = '".$arr1[$key]."' AND student_id = '".$value."' AND infraction_name = '".$dataArr['infraction_name']."' AND infraction_code = '".$dataArr['infraction_code']."' AND datetime = '".$dataArr['datetime']."'";
                        $rsDel = $objDB->sql_query($delSQL);

                        $insertSql = "INSERT INTO student_cdi_details SET ";
                        $exclude = array("note","actioncode");
                        foreach($dataArr as $skey=>$svalue)
                        {
                            if(!in_array($skey, $exclude))
                            {
                                $insertSql .= $skey." = '".$svalue."',";
                            }
                        }
                        $insertSql = trim($insertSql,",");
                        //echo $insertSql;exit;

                        $res = $objDB->insert($insertSql);

                    }
                    
                    
                    
                    $stateID = $arr1[$key];

                    $SQL = "SELECT * FROM student_cdi_details WHERE student_id = '".$value."' AND date(datetime) >= '".$start_date."' AND date(datetime) <= '".$end_date."'";//" AND ".date("Y-m-d", mktime(0,0,0, 9, 1, (date("Y")-1))) ."' AND (year(datetime) = '".date("Y")."' OR year(datetime) = '".(date("Y")-1)."')";
                    //echo $SQL;exit;
                    $rs1 = $objDB->select($SQL);
                    $BInfo = $CInfo = $DInfo = $EInfo = $Susp = $Days = 0;
                    for($j=0; $j < count($rs1); $j++)
                    {
                        if($rs1[$j]['disposition_type'] == "Suspended/Out of School")
                        {
                            $Days += $rs1[$j]['suspend_days'];
                            $Susp++;
                        }
                        if(substr($rs1[$j]['infraction_name'], 0, 1)=="B" || substr($rs1[$j]['infraction_name'], 0, 3)=="S-B")
                        {
                            $BInfo++;
                        }
                        if(substr($rs1[$j]['infraction_name'], 0, 1)=="C" || substr($rs1[$j]['infraction_name'], 0, 3)=="S-C")
                        {
                            $CInfo++;
                        }
                        if(substr($rs1[$j]['infraction_name'], 0, 1)=="D" || substr($rs1[$j]['infraction_name'], 0, 3)=="S-D")
                        {
                            $CInfo++;
                        }
                    }
                    $data = array();
                    $data['student_id'] = $value;
                    $data['stateID'] = $arr1[$key];
                    $data['b_info'] = $BInfo;
                    $data['c_info'] = $CInfo;
                    $data['d_info'] = $DInfo;
                    $data['e_info'] = $EInfo;
                    $data['susp'] = $Susp;
                    $data['susp_days'] = $Days;
                    
                    $SQL = "DELETE FROM submission_conduct_discplinary_info WHERE submission_id = '".$submission_id."'";
                    $rs_cdi = $objDB->sql_query($SQL);
                    

                    $SQL = "INSERT INTO student_conduct_disciplinary SET ";
                    $SQL1 = "INSERT INTO submission_conduct_discplinary_info SET submission_id = '".$submission_id."', ";
                    foreach($data as $dkey=>$dvalue)
                    {
                        $SQL .= $dkey." = '".$dvalue."',";
                        $SQL1 .= $dkey." = '".$dvalue."',";
                    }
                    $SQL = trim($SQL, ",");
                    $SQL1 = trim($SQL1, ",");

                    $rs = $objDB->insert($SQL);
                    $rs = $objDB->insert($SQL1);


                }
                else
                {
                    $SQL = "INSERT INTO student_conduct_disciplinary SET stateID='".$arr1[$key]."', student_id = '".$arr[$key]."', b_info = 0, c_info = 0, d_info = 0, e_info = 0, susp = 0, susp_days = 0";
                    $rsins = $objDB->sql_query($SQL);

                    $SQL = "INSERT INTO submission_conduct_discplinary_info SET submission_id = '".$submission_id."', stateID='".$arr1[$key]."', student_id = '".$arr[$key]."', b_info = 0, c_info = 0, d_info = 0, e_info = 0, susp = 0, susp_days = 0";
                    $rsins = $objDB->sql_query($SQL);

                }

                

            }
            
            echo "Student CDI fetched successfully";
            exit;          



        }
    }
    else
    {
        echo "No Submissions";
        exit;
    }






?>
