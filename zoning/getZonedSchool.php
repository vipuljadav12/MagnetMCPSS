<?php
	$updatedData = [];
	$val = "14024 Glenview Dr SW";
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
	$zip_code = "35803";
	$fake_address = $address_basic.' | '.$zip_code;

	if ($address_basic)
	{
		$url =  "https://maps.huntsvilleal.gov/arcgis/rest/services/Locators/CompositeLocator/GeocodeServer/findAddressCandidates?Street=&category=&outFields=*&maxLocations=5&outSR=&searchExtent=&location=&distance=&magicKey=&f=json&SingleLine=".$address_basic;
		$content = file_get_contents($url);
		$json = json_decode($content, true);

		if( count( $json['candidates'] ) == 0 ){

					$address_basic = preg_replace('/\+\w+$/', '', $address_basic );
					$url = "https://maps.huntsvilleal.gov/arcgis/rest/services/Locators/CompositeLocator/GeocodeServer/findAddressCandidates?Street=&category=&outFields=*&maxLocations=5&outSR=&searchExtent=&location=&distance=&magicKey=&f=json&SingleLine="
            			.$address_basic;

			 	   	$content = file_get_contents($url);
			 	   	$json2 = json_decode($content, true);

				 	if( count( $json2['candidates'] ) == 1 ){
				 		$json = $json2;
				 	} else if( count( $json2['candidates'] ) > 1 ){
				 		$data = $json2['candidates'];
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
				 		if($final_address != "")
				 		{
				 			return $final_address;
				 			$url = "https://maps.huntsvilleal.gov/ArcGIS/rest/services/Layers/Addresses/MapServer/1/query?returnCountOnly=false&returnIdsOnly=false&returnGeometry=false&outFields=elem_sch_distr%2Cmid_sch_distr%2Chigh_sch_distr%2Caddress_full&f=json&where=address_full="
			        	.urlencode( "'".$json['candidates'][0]['address']."'" );
			        		$content = file_get_contents($url);
					$json = json_decode($content, true);



					$updatedData[12] = ( $json['features'][0]['attributes']['elem_sch_distr'] )
						? ucwords( strtolower($json['features'][0]['attributes']['elem_sch_distr']) )
						: 'N/A';
					$updatedData[13] = ( $json['features'][0]['attributes']['ES_choice'] )
						? ucwords( strtolower($json['features'][0]['attributes']['ES_choice']) )
						: '';

					$updatedData[14] = ($json['features'][0]['attributes']['mid_sch_distr'])
						? ucwords(strtolower($json['features'][0]['attributes']['mid_sch_distr']))
						: 'N/A';

					$updatedData[15] = ( $json['features'][0]['attributes']['MS_choice'] )
						? ucwords( strtolower($json['features'][0]['attributes']['MS_choice']) )
						: '';

					$updatedData[16] = ($json['features'][0]['attributes']['high_sch_distr'])
						? ucwords(strtolower($json['features'][0]['attributes']['high_sch_distr']))
						: 'N/A';
					$updatedData[17] = ( $json['features'][0]['attributes']['HS_choice'] )
						? ucwords( strtolower($json['features'][0]['attributes']['HS_choice']) )
						: '';
				 		}
				 	}
				}

				if( isset( $json['candidates'][0]['location']['x'] ) ){

					// $geometry = '{"x" : '. $json['candidates'][0]['location']['x']
			  //           .', "y" : '.$json['candidates'][0]['location']['y']
			  //           .', "spatialReference" : {"wkid" : '.$json['spatialReference']['wkid'].'}}';

					$url = "https://maps.huntsvilleal.gov/ArcGIS/rest/services/Layers/Addresses/MapServer/1/query?returnCountOnly=false&returnIdsOnly=false&returnGeometry=false&outFields=elem_sch_distr%2Cmid_sch_distr%2Chigh_sch_distr%2Caddress_full&f=json&where=address_full="
			        	.urlencode( "'".$json['candidates'][0]['address']."'" );

					$content = file_get_contents($url);
					$json = json_decode($content, true);



					$updatedData[12] = ( $json['features'][0]['attributes']['elem_sch_distr'] )
						? ucwords( strtolower($json['features'][0]['attributes']['elem_sch_distr']) )
						: 'N/A';
					$updatedData[13] = ( $json['features'][0]['attributes']['ES_choice'] )
						? ucwords( strtolower($json['features'][0]['attributes']['ES_choice']) )
						: '';

					$updatedData[14] = ($json['features'][0]['attributes']['mid_sch_distr'])
						? ucwords(strtolower($json['features'][0]['attributes']['mid_sch_distr']))
						: 'N/A';

					$updatedData[15] = ( $json['features'][0]['attributes']['MS_choice'] )
						? ucwords( strtolower($json['features'][0]['attributes']['MS_choice']) )
						: '';

					$updatedData[16] = ($json['features'][0]['attributes']['high_sch_distr'])
						? ucwords(strtolower($json['features'][0]['attributes']['high_sch_distr']))
						: 'N/A';
					$updatedData[17] = ( $json['features'][0]['attributes']['HS_choice'] )
						? ucwords( strtolower($json['features'][0]['attributes']['HS_choice']) )
						: '';
				}
	}
	$elem = [1, 2, 3, 4, 5];
		$mid = [6,7,8];
		$high = [9,10,11,12];
		if (in_array($nextGrade, $elem))
		{
			$nextSchool = $updatedData[12];
		} else if (in_array($nextGrade, $mid)) {
			$nextSchool = $updatedData[14];
		} else if (in_array($nextGrade, $high)) {
			$nextSchool = $updatedData[16];
		}
		echo $nextSchool;


	function getNextSchool($address, $next_grade)
	{
		$url = "https://maps.huntsvilleal.gov/ArcGIS/rest/services/Layers/Addresses/MapServer/1/query?returnCountOnly=false&returnIdsOnly=false&returnGeometry=false&outFields=elem_sch_distr%2Cmid_sch_distr%2Chigh_sch_distr%2Caddress_full&f=json&where=address_full="
			        	.urlencode( "'".$address."'" );

		$content = file_get_contents($url);
		$json = json_decode($content, true);

		$updatedData = [];
		$updatedData[12] = ( $json['features'][0]['attributes']['elem_sch_distr'] )
						? ucwords( strtolower($json['features'][0]['attributes']['elem_sch_distr']) )
						: 'N/A';
		$updatedData[13] = ( $json['features'][0]['attributes']['ES_choice'] )
			? ucwords( strtolower($json['features'][0]['attributes']['ES_choice']) )
			: '';

		$updatedData[14] = ($json['features'][0]['attributes']['mid_sch_distr'])
			? ucwords(strtolower($json['features'][0]['attributes']['mid_sch_distr']))
			: 'N/A';

		$updatedData[15] = ( $json['features'][0]['attributes']['MS_choice'] )
			? ucwords( strtolower($json['features'][0]['attributes']['MS_choice']) )
			: '';

		$updatedData[16] = ($json['features'][0]['attributes']['high_sch_distr'])
			? ucwords(strtolower($json['features'][0]['attributes']['high_sch_distr']))
			: 'N/A';
		$updatedData[17] = ( $json['features'][0]['attributes']['HS_choice'] )
			? ucwords( strtolower($json['features'][0]['attributes']['HS_choice']) )
			: '';

		$elem = [1, 2, 3, 4, 5];
		$mid = [6,7,8];
		$high = [9,10,11,12];
		if (in_array($nextGrade, $elem))
		{
			$nextSchool = $updatedData[12];
		} else if (in_array($nextGrade, $mid)) {
			$nextSchool = $updatedData[14];
		} else if (in_array($nextGrade, $high)) {
			$nextSchool = $updatedData[16];
		}
		return $nextSchool;
	}
	echo $nextSchool;