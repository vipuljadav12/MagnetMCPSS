<?php
    set_time_limit(0);
    ini_set('memory_limit','2048M');
    
    include("common_functions.php");
    include("dbClass.php");
    $objDB = new MySQLCN;
    
    
    $endpoint = "schools";
    $schools = fetch_inow_details($endpoint);
	echo "<pre>";
    print_r($schools);exit;
    foreach($schools as $key=>$school)
    {
        echo $school->Id."^".$school->Name."<BR>";
    }

    
?>