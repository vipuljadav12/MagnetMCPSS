<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
include('functions.php');
include_once('dbClass.php');
$type = 'student_storedgrades';
$url = 'https://mcpss.powerschool.com';
$clientID = '0636cab1-bd5a-4df6-8be3-79e050e5a57e';
$clientSecret = 'ebfb0ae9-84fc-4521-95af-24fb187b3b92';

$objDB = new MySQLCN; 


$accessToken = getAccessToken($url, $clientID, $clientSecret);
$accessTokenArray = json_decode($accessToken);

if (!empty($accessTokenArray)) {
    $accessTokenKey = $accessTokenArray->access_token;
    $accessTokenType = $accessTokenArray->token_type;
    $accessTokenExpiresIn = $accessTokenArray->expires_in;
    
    if (isset($accessTokenKey) && !empty($accessTokenKey)) {
        $SQL = "SELECT id, student_id FROM submissions WHERE student_id != '' AND grade_exists = 'N'";//" LIMIT 1";
        $rsMain = $objDB->select($SQL);

        if(count($rsMain) > 0)
        {
            for($i=0; $i < count($rsMain); $i++)
            {

            $SQL = "SELECT dcid FROM student WHERE stateID = '".$rsMain[$i]['student_id']."'";
            $rsS = $objDB->select($SQL);
            $powerSchoolRecords = getPowerSchoolRecords($type, $accessTokenKey, $url, array("submission_id"=>$rsMain[$i]['id'], "student_id"=>$rsS[0]['dcid']));

            $SQL = "UPDATE submissions SET  grade_exists = 'Y' WHERE id = '".$rsMain[$i]['id']."'";
            $rs = $objDB->sql_query($SQL);
            }

        }
        //}
    }
} else {
    echo "Invalid Token";
}
