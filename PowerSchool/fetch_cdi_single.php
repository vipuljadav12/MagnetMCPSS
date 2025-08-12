<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
include('functions.php');
include_once('dbClass.php');

$objDB = new MySQLCN; 

/*echo base64_decode("V3JvbmcgUXVlcnkgOiBJTlNFUlQgSU5UTyBzdHVkZW50X2NkaV9kZXRhaWxzIFNFVCBpbmZyYWN0aW9uX25hbWUgPSAnUy1CMDItQWN0IE9mIFBoeXNpY2FsIEFnZ3Jlc3Npb24gICBEaWFsLCBEYW5pZWwgRGVcJ01hcmVcJycsaW5jaWRlbnRfZGVzYyA9ICdEdXJpbmcgY29tcHV0ZXIgbGFiIERhbmllbCBiZWdhbiBoaXR0aW5nIGFuZCBraWNraW5nIHRoZSBtYWpvcml0eSBvZiB0aGUgc3R1ZGVudHMgaW4gY2xhc3MuIEhlIHdhcyBiZWluZyBydWRlIGFuZCBkaXNyZXNwZWN0ZnVsIHRvIHRoZSB0ZWFjaGVycyBpbiB0aGUgbGFiLCBNcy4gVGF0ZSwgSGFtcHRvbiwgYW5kIE1zLiBNY0NhbGwuIEhlIHRoZW4gd2Fsa2VkIG91dCBvZiB0aGUgbGFiIHdpdGhvdXQgcGVybWlzc2lvbi4nLGRhdGV0aW1lID0gJzIwMTctMDItMjIgMTc6Mjc6MDAuMCcsZGlzcG9zaXRpb24gPSAnRGlzY2lwbGluZScsaW5mcmFjdGlvbl9jb2RlID0gJycsYWN0aW9uX25hbWUgPSAnRGlzY2lwbGluZScsc3RhdGVJRCA9ICcxOTY4MjU0MzY1JyxzdHVkZW50X2lkID0gJzYwMzM0JyxncmFkZSA9ICc4Jzxicj5Vbmtub3duIGNvbHVtbiAnYWN0aW9uX25hbWUnIGluICdmaWVsZCBsaXN0Jw==");
exit;*/

        $SQL = "SELECT id, student_id, next_grade FROM submissions WHERE id = ".$_REQUEST['id'];
        $rs = $objDB->select($SQL);

        for($i=0; $i < count($rs); $i++)
        {

            $SQL = "SELECT dcid FROM student WHERE stateID = '".$rs[$i]['student_id']."'";
            $rsS = $objDB->select($SQL);


            $SQL = "SELECT `ps_incident_person_role`.`id` AS S_No, `ps_incident_person_role`.`incident_id` AS Incident_ID, `ps_incident`.`incident_title` AS Incident_Title, `ps_incident`.`incident_detail_desc` AS Incident_Detail_Desc, `ps_incident`.`incident_ts` AS Incident_TS, `ps_incident`.`location_details` AS Location_Details, `ps_incident_detail`.`incident_detail_id` AS Incident_Detail_ID, `ps_incident_detail`.`lookup_code_desc` AS Lookup_Code_Desc, `ps_incident_detail`.`lu_code_id` AS LU_Code_ID, `ps_incident_lu_code`.`code_type` AS Code_Type, `ps_incident_lu_code`.`incident_category` AS Incident_Category, `ps_incident_lu_code`.`state_aggregate_rpt_code` AS State_Aggregate_Rpt_Code, `ps_incident_detail`.`lu_sub_code_id` AS LU_Sub_Code_ID, `ps_incident_lu_sub_code`.`comment_enable_state` AS Comment_Enable_State, `ps_incident_lu_sub_code`.`is_police_reportable_flg` AS Is_Police_Reportable_Flg, `ps_incident_lu_sub_code`.`is_state_reportable_flg` AS Is_State_Reportable_Flg, `ps_incident_lu_sub_code`.`long_desc` AS Long_Desc, `ps_incident_lu_sub_code`.`restricted` AS Restricted, `ps_incident_lu_sub_code`.`short_desc` AS Short_Desc, `ps_incident_lu_sub_code`.`state_detail_report_code` AS State_Detail_Report_Code, `ps_incident_lu_sub_code`.`sub_category` AS Sub_Category, `ps_incident_action`.`action_actual_resolved_dt` AS Action_Actual_Resolved_Dt, `ps_incident_action`.`action_change_reason` AS Action_Change_Reason, `ps_incident_action`.`action_plan_begin_dt` AS Action_Plan_Begin_Dt, `ps_incident_action`.`action_plan_end_dt` AS Action_Plan_End_Dt, `ps_incident_action`.`action_resolved_desc` AS Action_Resolved_Desc, `ps_incident_person_role`.`studentid` AS StudentID
					FROM `ps_incident_person_role`
					LEFT JOIN `ps_incident` ON `ps_incident`.`incident_id` = `ps_incident_person_role`.`incident_id`
					LEFT JOIN `ps_incident_detail` ON `ps_incident_detail`.`incident_id` = `ps_incident`.`incident_id`
					LEFT JOIN `ps_incident_lu_code` ON `ps_incident_lu_code`.`lu_code_id` = `ps_incident_detail`.`lu_code_id`
					LEFT JOIN `ps_incident_lu_sub_code` ON `ps_incident_lu_sub_code`.`lu_sub_code_id` = `ps_incident_detail`.`lu_sub_code_id`
					LEFT JOIN `ps_incident_action` ON `ps_incident_action`.`incident_id` = `ps_incident`.`incident_id`
					
					WHERE `ps_incident_person_role`.`studentid` = '".$rsS[0]['dcid']."'";

			$rsIncident = $objDB->sql_query($SQL);

			if(count($rsIncident) > 0)
			{
				$SQL = "DELETE FROM student_cdi_details WHERE stateID = '".$rs[$i]['student_id']."'";
				//$rsDel = $objDB->sql_query($SQL);

			}

			$ks = array_keys($rsIncident[0]);
			$abc = [];
			foreach($ks as $ks1=>$v1)
			{
				if(!is_numeric($v1))
					echo $v1."^";
			}
			echo "<BR>";
			foreach($rsIncident as $k=>$v)
			{
				foreach($v as $k1=>$v1)
				{
					if(!is_numeric($k1))
						echo $v1."^";
					
				}
//				echo $v[."^";
/*				$dataArr = [];
				$dataArr['infraction_name'] = $v['Incident_Title'];
				$dataArr['incident_desc'] = $v['Incident_Detail_Desc'];
				$dataArr['datetime']  = $v['Incident_TS'];
				$dataArr['disposition'] = $v['Incident_Category'];
				$dataArr['disposition'] = $v['Incident_Category'];
				$dataArr['infraction_code'] = $v['State_Detail_Report_Code'];
				$dataArr['actionname'] = $v['Incident_Category'];
				if($v['Incident_Category'] == "Suspended/In School" || $v['Incident_Category'] == "Suspended/Out of School")
	            {
	                if($v['Action_Plan_Begin_Dt'] != '' && $v['Action_Plan_Begin_Dt'] != '')
	                {
	                        $dataArr['suspend_days'] = daydiff($v['Action_Plan_Begin_Dt'], $v['Action_Plan_End_Dt']);
	                        $dataArr['startdate'] = $v['Action_Plan_Begin_Dt'];
	                        $dataArr['enddate'] = $v['Action_Plan_End_Dt'];
	                }
	            }
	            $dataArr['stateID'] = $rs[$i]['student_id'];
				$dataArr['student_id'] = $rsS[0]['dcid'];
				$dataArr['grade'] = $rs[$i]['next_grade'];


				$SQL = "INSERT INTO student_cdi_details SET ";
				foreach($dataArr as $dk=>$dv)
				{
					$SQL .= $dk." = '".addslashes($dv)."',";
				}
				$SQL = trim($SQL, ",");
                $rsIns = $objDB->sql_query($SQL);*/
                echo "<BR>";
			}
			
        }
        exit;
        echo "Done";
    
