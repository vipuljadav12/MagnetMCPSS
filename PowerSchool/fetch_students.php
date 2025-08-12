<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
include('functions.php');
include_once('dbClass.php');
$type = 'students';
$url = 'https://mcpss.powerschool.com';
$clientID = 'c8c7d902-81dc-41fb-8f92-5b26bcf18b19';
$clientSecret = '82a62983-c22c-419a-b7b9-4b68b92c2883';

$objDB = new MySQLCN; 


$accessToken = getAccessToken($url, $clientID, $clientSecret);
$accessTokenArray = json_decode($accessToken);

if (!empty($accessTokenArray)) {
    $accessTokenKey = $accessTokenArray->access_token;
    $accessTokenType = $accessTokenArray->token_type;
    $accessTokenExpiresIn = $accessTokenArray->expires_in;
    
    if (isset($accessTokenKey) && !empty($accessTokenKey)) {
            $powerSchoolRecords = getPowerSchoolRecords($type, $accessTokenKey, $url, array());

    }
} else {
    echo "Invalid Token";
}
?>