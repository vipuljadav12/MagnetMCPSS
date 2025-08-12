<?php
    set_time_limit(0);
    $sid = "706678";

    ini_set('memory_limit','2048M');
    include("common_functions.php");
    
    include("dbClass.php");
    $objDB = new MySQLCN;
    $endpoint = "schools";

    $endpoint = "students?firstName=Hayden";
    echo "<pre>";
//    $endpoint = "students/706678";
    print_r(fetch_inow_details($endpoint));


?>