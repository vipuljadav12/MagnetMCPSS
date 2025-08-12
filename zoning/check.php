<?php
	$updatedData = [];
	$val = $_GET['addr'];
	$zip_code = $_GET['zip'];

	//$zip_code = "35803";

	echo "Below are suggestions<br><br>";
	echo generateAddress($val, $zip_code);

	function generateAddress($val, $zip_code)
	{
		$address = str_ireplace('Huntsville, AL', '', $val);
		$address = str_ireplace('Northport, AL', '', $address);
		$address = str_ireplace('Cottondale, AL', '', $address);
		$address_basic = trim($address);
		$abbrevs = [
			'/ GDNS\b/i' => ' Gardens',
			'/ HTS\b/i' => ' heights',
			'/ SQ\b/i' => ' square',
			'/ VLG\b/i' => ' village',
			'/ GRV\b/i' => ' grove',
			'/ RDG\b/i' => ' ridge',
			'/ HWY\b/i' => ' hw',
			'/ HLS\b/i' => ' hills',
			'/ TER\b/i' => ' terrace'
		];

		foreach($abbrevs as $k => $v)
		{
			$address_basic = preg_replace($k, $v, $address_basic);
		}

		$address_basic = strtoupper($address_basic);
		
		$pattern = '/ apt [a-z0-9 ]+/i';
		$pattern2 = '/ lot [a-z0-9 ]+/i';
		$address_basic = preg_replace($pattern, '', $address_basic);
		$address_basic = preg_replace($pattern2, '', $address_basic);
		$address_basic = str_replace(' ', '+', $address_basic);
		$address_basic = preg_replace('/\s+/', ' ', $address_basic);
		$address_basic = preg_replace( '/^(\d+)[a-zA-Z]/', '$1', $address_basic );
		//$fake_address = $address_basic.' | '.$zip_code;
		$nextGrade = 6;
		//$zip_code = "35803";
		$fake_address = $address_basic.' | '.$zip_code;

		if ($address_basic)
		{
			$url =  "https://maps.huntsvilleal.gov/arcgis/rest/services/Locators/CompositeLocator/GeocodeServer/findAddressCandidates?Street=&category=&outFields=*&maxLocations=5&outSR=&searchExtent=&location=&distance=&magicKey=&f=json&SingleLine=".$address_basic;

			$content = file_get_contents($url);
			$json = json_decode($content, true);
			if( count( $json['candidates'] ) == 0 ){

				$address_basic = preg_replace('/\+\w+$/', '', $address_basic);

				$url = "https://maps.huntsvilleal.gov/arcgis/rest/services/Locators/CompositeLocator/GeocodeServer/findAddressCandidates?Street=&category=&outFields=*&maxLocations=5&outSR=&searchExtent=&location=&distance=&magicKey=&f=json&SingleLine="
	            			.$address_basic;

		 	   	$content = file_get_contents($url);
		 	   	$json2 = json_decode($content, true);
		 	   	$json = $json2;
	 	   }
		 	if( count( $json['candidates'] ) == 1 ){

		 		return $json['candidates'][0]['address'];
		 	} 
		 	else if( count( $json['candidates'] ) > 1)
		 	{
		 		$data = $json['candidates'];
		 		$address = [];
		 		$final_address = "";
		 		foreach($data as $key=>$value)
		 		{
		 			if($value['score'] == 100)
		 			{
		 				$final_address = $value['address'];
		 			}
		 			if(!in_array($value['address'], $address))
		 			{
		 				$address[] = $value['address'];
		 			}
		 		}
/*		 		if($final_address != "")
		 		{
		 			return $final_address;
		 		}
		 		else
		 		{
*/		 			$str = "<select>";
		 			foreach($address as $value)
		 			{
		 				$str .= "<option>".$value."</option>";
		 			}
		 			$str .= "</select>";
		 			return $str;
//		 		}
			}		
		}
	}

	function prepareAddress( $address ){
        //HSV City System only used Unit, it changes Apt and Suite over to Unit.
        //We need to do the same. PREG_REPLACE Replaces either words with Unit.
        $address = trim( $address );
        $address = preg_replace( '/(\bSuite\b)|(\bLot\b)|(\bApt\b)/i' , 'Unit' , $address );
        $address = preg_replace( "/(\.)|(,)|(')|(#)/" , '' , $address );
        $address = preg_replace( '/(\bDrive\b)/i' , 'DR' , $address );
        $address = preg_replace( '/(\bCr\b)/i' , 'CIR' , $address );
        //$address = preg_replace( '/(\bmc)/i' , 'Mc ' , $address );
        $address = preg_replace( '/(\bBlvd\b)/i' , 'BLV' , $address );
        $address = preg_replace( '/(\bAvenue\b)/i' , 'AVE' , $address );
        $addressArray = explode( ' ' , $address );

        //Does the index:1 contain an number street. Example: 8th Street.
        if( isset( $addressArray[1] ) && preg_match( '/\d+/' , $addressArray[1] , $matches ) !== false ) {
            //Index:1 contains an number. Need to replace.
            //Add in switch statement to handle converting 1st - 17th to First - Seventeenth
            switch( strtoupper( $addressArray [1] ) ) {
                case '1ST':
                    $addressArray[1] = 'FIRST';
                    break;
                case '2ND':
                    $addressArray[1] = 'SECOND';
                    break;
                case '3RD':
                    $addressArray[1] = 'THIRD';
                    break;
                case '4TH':
                    $addressArray[1] = 'FOURTH';
                    break;
                case '5TH':
                    $addressArray[1] = 'FIFTH';
                    break;
                case '6TH':
                    $addressArray[1] = 'SIXTH';
                    break;
                case '7TH':
                    $addressArray[1] = 'SEVENTH';
                    break;
                case '8TH':
                    $addressArray[1] = 'EIGHTH';
                    break;
                case '9TH':
                    $addressArray[1] = 'NINTH';
                    break;
                case '10TH':
                    $addressArray[1] = 'TENTH';
                    break;
                case '11TH':
                    $addressArray[1] = 'ELEVENTH';
                    break;
                case '12TH':
                    $addressArray[1] = 'TWELFTH';
                    break;
                case '13TH':
                    $addressArray[1] = 'THIRTEENTH';
                    break;
                case '14TH':
                    $addressArray[1] = 'FOURTEENTH';
                    break;
                case '15TH':
                    $addressArray[1] = 'FIFTEENTH';
                    break;
                case '17TH':
                    $addressArray[1] = 'SEVENTEENTH';
                    break;
                default:
                    break;
            }
        }
        return implode( ' ' , $addressArray );
    }
?>