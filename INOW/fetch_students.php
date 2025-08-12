<?php
    set_time_limit(0);
    ini_set('memory_limit','2048M');
    
    include("common_functions.php");
    include("dbClass.php");
    $objDB = new MySQLCN;
    
    /* Fetch Grades Level */
    $grade_level_hash = array();
    $grade_levels = fetch_inow_details( 'gradelevels' );
    
    foreach( $grade_levels as $grade_level ){
        $grade_level_hash[ $grade_level->Id ] = intval( $grade_level->Name );
    }

    /* Fetch Ethnicities of district */
    $ethnicities = fetch_inow_details( 'ethnicities' );

    $race_hash = array();
    foreach( $ethnicities as $ethnicity ){
        $race_hash[ $ethnicity->Id ] = $ethnicity->Name;
    }

    // Get all student ethnicities
    $student_race_hash = array();
    $ethnicities = fetch_inow_details( 'persons/ethnicities' );
    foreach( $ethnicities as $ethnicity ){
        if( $ethnicity->IsPrimary ) {
            $student_race_hash[$ethnicity->PersonId] = $race_hash[ $ethnicity->EthnicityId ];
        }
    }

    /* Fetch Gender of district */
    $gender_hash = array();
    $genders = fetch_inow_details( 'genders' );
    foreach( $genders as $gender ){
        $gender_hash[ $gender->Id ] = $gender->Name;
    }

    /* Fetch Schools */

    $endpoint = "schools";
    $schools = fetch_inow_details($endpoint);
	$schools = array('83' => 'Robbins Elementary','142' => 'SARALAND ELEMENTARY SCHOOL','81' => 'Satsuma High School','82' => 'Scarborough Middle School','91' => 'Semmes Elementary School','92' => 'Semmes Middle School','143' => 'SHAW HIGH SCHOOL','90' => 'Shepard Elementary','85' => 'Sonny Callahan School For Deaf & Blind','87' => 'SPAN','102' => 'Special Services School','93' => 'Spencer Elementary','98' => 'Spencer-Westlawn Elementary School','94' => 'St Elmo Elementary','95' => 'Tanner Williams Elementary School','180' => 'Taylor-White Elementary School','103' => 'The Pathway','96' => 'Theodore High School','147' => 'TL Faulkner Sch','88' => 'Turner Elementary School','174' => 'Unassigned School','97' => 'Vigor High School','24' => 'W. H. Council Traditional School','86' => 'Washington Middle School','72' => 'West Mobile Academy','144' => 'WHISTLER ELEMENTARY SCHOOL','99' => 'Whitley Elementary School','89' => 'Will Elementary School','100' => 'Williamson High School','101' => 'Wilmer Elementary School','145' => 'WOODCOCK ELEMENTARY');
    foreach($schools as $key=>$school)
    {
        $schoolId = $key;//$school->Id;

            $endpoint = "schools/".$schoolId."/acadsessions";
            $sessions = fetch_inow_details($endpoint);
	
            $year = date("Y");
            foreach($sessions as $session)
            {
                
                    $endpoint = $session->Id."/students";
					
                    $students = fetch_inow_details($endpoint);
					//print_r($students);exit;
					if(!isset($students->Message))
					{
						foreach ($students as $student) {
							
							$SQL = "SELECT student_id FROM student WHERE student_id = '".$student->Id."'";
							//echo $SQL;exit;
							$rsp = $objDB->select($SQL);
							
							if(count($rsp) >= 0)
							{
							
								$data = array();
								$data['stateID'] = $student->StateIdNumber;
								$data['student_id'] = $student->Id;
								$data['first_name'] = $student->FirstName;
								$data['last_name'] = $student->LastName;
								$data['race'] = isset($student_race_hash[$student->Id]) ? $student_race_hash[$student->Id] : '';
								$data['gender'] = isset($gender_hash[$student->GenderId]) ? $gender_hash[$student->GenderId] : '';
								$data['birthday'] = $student->DateOfBirth;
								$data['current_school'] = $school;//$school->Name;
								$data['current_grade'] = (isset($grade_level_hash[$student->GradeLevelId]) ? $grade_level_hash[$student->GradeLevelId] : '');
								
								$current_grade = $data['current_grade'];

                                if($current_grade == 97)
                                {
                                    $data['current_grade'] = "PreK";
                                }
                                elseif($current_grade == 98)
                                {
                                    $data['current_grade'] = "PreK";
                                }
                                elseif($current_grade == 99)
                                {
                                    $data['current_grade'] = "PreK";
                                }
                                elseif($current_grade == 0)
                                {
                                    $data['current_grade'] = "K";
                                }
                                else
                                    $data['current_grade'] = $current_grade;


								$endpoint = "persons/".$student->Id."/addresses";
								$addr = fetch_inow_details($endpoint);
								$state = "";
								if(isset($addr[0]))
								{
									$state_id = $addr[0]->StateId;
									$endpoint = "states/".$state_id;
									$stateinfo = fetch_inow_details($endpoint);
									$state = $stateinfo->Description;
								}
								$data['address'] = (isset($addr[0]) ? $addr[0]->AddressLine1 : '');
								$data['city'] = (isset($addr[0]) ? $addr[0]->City : '');
								$data['state'] = $state;
								$data['zip'] = (isset($addr[0]) ? $addr[0]->PostalCode : '');

								$endpoint = "persons/".$student->Id."/emailaddresses";
								$emails = fetch_inow_details($endpoint);
								
								if(isset($emails[0]))
									$data['email'] =  $emails[0]->EmailAddress;
								else
									$data['email'] =  "";
			
								$endpoint = "persons/".$student->Id."/telephonenumbers";
								$tel = fetch_inow_details($endpoint);
								//print_r($data);exit;
								if(isset($tel[0]))
									$data['phone'] = $tel[0]->TelephoneNumber;
								else
									$data['phone'] = '';



								$SQL = "INSERT INTO student SET ";
								foreach($data as $mkey=>$mvalue)
								{
									$SQL .= $mkey."='".addslashes($mvalue)."',";
								}
								$SQL = trim($SQL,",");
								$rs = $objDB->insert($SQL);
							}

							# code...
						}
					}
            }
        
    }

    
?>