<?php
    set_time_limit(0);
	ini_set("error_reporting", "1");
    ini_set('memory_limit','2048M');
    
    include("common_functions.php");
    
    include("dbClass.php");
    $objDB = new MySQLCN;
    
    $locations = fetch_inow_details("disciplinaryLocations");
    $locations_hash = [];
    foreach( $locations as $location ){
        $locations_hash[ $location->Id ] = $location;
    }
    unset( $locations );


    /* Get Incidents */
    $incidents = fetch_inow_details("incidents");
    $incident_hash = [];
    foreach( $incidents as $incident ){
        $incident_hash[ $incident->Id ] = $incident;
    }
    unset( $incidents );
    $infractions = fetch_inow_details( 'infractions' );
    $infraction_hash = [];
    foreach( $infractions as $infraction ){
        $infraction_hash[ $infraction->Id ] = $infraction;
    }
    unset( $infractions );   


    $SQL = "SELECT stateID, student_id  FROM student_latest WHERE current_grade NOT IN ('PreK') ORDER BY id DESC";
    $rs = $objDB->select($SQL);
    

    if(count($rs) > 0)
    {
        for($im=0; $im < count($rs); $im++)
        {       

            $SQL = "SELECT stateID FROM student_cdi_details_new WHERE stateID = '".$rs[$im]['stateID']."'";
            $rsCDI = $objDB->select($SQL);
            if(count($rsCDI) > 0)
            {

            }
            else
            {
                $arr = $arr1 = array();
                $arr[] = $rs[$im]['student_id'];
                $arr1[] = $rs[$im]['stateID'];



     

                $infraction_codeArr = array();
                foreach($arr as $key=>$value)
                {

                    $endpoint = "students/".$value."/disciplinaryOccurrences";
                    $dispData = fetch_inow_details($endpoint);

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
                            
                            $delSQL = "DELETE FROM student_cdi_details_new WHERE stateID = '".$arr1[$key]."' AND student_id = '".$value."' AND infraction_name = '".$dataArr['infraction_name']."' AND infraction_code = '".$dataArr['infraction_code']."' AND datetime = '".$dataArr['datetime']."'";
                            $rsDel = $objDB->sql_query($delSQL);

                            $insertSql = "INSERT INTO student_cdi_details_new SET ";
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
                        
                        
                        
                        

                        


                    }

                    

                }

            }


            

            
            

        }
    }
    else
    {
        echo "No Submissions";
        exit;
    }






?>
