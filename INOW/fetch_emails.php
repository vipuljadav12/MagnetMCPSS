<?php
    set_time_limit(0);
    ini_set('memory_limit','2048M');
    
    include("common_functions.php");
    include("dbClass.php");
    $objDB = new MySQLCN;
	
	$endpoint = "persons/99813/emailaddresses";
	$emails = fetch_inow_details($endpoint);
    print_r($emails);exit;

    
?>