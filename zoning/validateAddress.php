<?php
	
	class customZoneAPI {

		private $end_points = [
	        'schools' => "https://maps.huntsvilleal.gov/ArcGIS/rest/services/Layers/Addresses/MapServer/1/query?returnCountOnly=false&returnIdsOnly=false&returnGeometry=false&outFields=elem_sch_distr%2Cmid_sch_distr%2Chigh_sch_distr%2Caddress_full&f=json&where=address_full+LIKE+",
	        'possible_addresses' => "https://maps.huntsvilleal.gov/arcgis/rest/services/Locators/CompositeLocator/GeocodeServer/findAddressCandidates?Street=&category=&outFields=*&maxLocations=5&outSR=&searchExtent=&location=&distance=&magicKey=&f=json&SingleLine=",
	    ];

	    private $useAPI = true;

	    /**
	     * ZoningAPIService constructor.
	     */
	    function __construct() {
	    }

	    /* Check Address Function */
	    public function checkAddress($lookupAddress, $zip)
		{
			$lookupResponse = $this->getZonedSchool( $lookupAddress , $zip );

			if( $lookupResponse == false ) {
				$addressArray = explode( ' ' , $lookupAddress );
		        $secondTryAddressLookup = implode( ' ' , array_slice( $addressArray , 0 , 2 ) );
		        $lookupResponse2 = $this->getZonedSchool( $secondTryAddressLookup , $zip );

		        if( $lookupResponse2 == false ) {
		        	$thirdTryAddressLookup = implode( ' ' , array_slice( $addressArray , 0 , 2 ) ) . '%' . implode( ' ' , array_slice( $addressArray , -2 , 2 ) );
		     		$lookupResponse3 = $this->getZonedSchool( $thirdTryAddressLookup , $zip );
		     		if( $lookupResponse3 == false ) {
						return false;
					} else {
						return $lookupResponse3;
					}
		        }
		        else
		        {
		        	return $lookupResponse2;
		        }

			}
			return $lookupResponse;

		}

		/* Get Suggestion Function */
		public function getSuggestions($address, $zip)
		{
			$addressParts = explode( ' ', trim( $address ) );
			$countParts = count($addressParts);
			$results = null;
	        for( $useParts = $countParts; $useParts > 0; $useParts-- ){
	            $searchAddress = implode( ' ', array_slice( $addressParts, 0, $useParts ) );
	            if( $this->useAPI ) {
	                $suggestions = $this->getAddressCandidates( $searchAddress , $zip, 5 );
	            } else {
	                //$suggestions = $this->getAddressFromDatabase( $searchAddress, $zip, $maxSuggestions );
	            }

	            if($suggestions) {
	                return ( is_array($suggestions) ) ? $suggestions : [$suggestions];
	            }
	        }
	        return [];

		}

		/* Get Zoned School based on Address */
		public function getZonedSchool($address, $zip = '')
	 	{
	 		$url = "https://maps.huntsvilleal.gov/ArcGIS/rest/services/Layers/Addresses/MapServer/1/query?returnCountOnly=false&returnIdsOnly=false&returnGeometry=false&outFields=elem_sch_distr%2Cmid_sch_distr%2Chigh_sch_distr%2Caddress_full&f=json&where=address_full+LIKE+";

	 		$end_point = $url . urlencode( "'" . $address . "%'" );
			$response = getResponse( $end_point );

			
			if( count( $response->features ) == 0 ){
				$address = $this->prepareAddress( $address );
		        $end_point = $url . urlencode( "'" . $address . "%'" );
		        $response = $this->getResponse( $end_point );

			}
			if( count( $response->features ) == 0 ){
	            return false;
	        }

			

	        $matching_index = 0;
	        if( count( $response->features ) > 1 ){

	            $matching_index = -1;
	            $multiple_matches = false;
	            foreach( $response->features as $index => $feature ){
	                	echo strtoupper( $address ) . " == ".strtoupper( $feature->attributes->address_full )."<BR>"; 

	                if( strtoupper( $address ) == strtoupper( $feature->attributes->address_full ) ){
	                    $multiple_matches = ($matching_index > -1);
	                    $matching_index = $index;
	                }
	            }

	            if( $multiple_matches ){
	                return false;
	            }
	        }

	        $addressBound = [];
	        $addressBound['ES'] = ( isset( $response->features[$matching_index]->attributes->elem_sch_distr )  ? $response->features[$matching_index]->attributes->elem_sch_distr : '');
	        $addressBound['MS'] =  ( isset( $response->features[$matching_index]->attributes->mid_sch_distr  )  ? $response->features[$matching_index]->attributes->mid_sch_distr  : '' );
	        $addressBound['HS'] = ( isset( $response->features[$matching_index]->attributes->high_sch_distr )  ? $response->features[$matching_index]->attributes->high_sch_distr : '' );
	        return $addressBound;
	 	}

		public function prepareAddress( $address ){
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

		public function getResponse( $end_point ){

	        $curl = curl_init($end_point);

	        curl_setopt( $curl , CURLOPT_URL , $end_point );
	        curl_setopt( $curl , CURLOPT_SSL_VERIFYPEER , false );
	        curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true );
	        curl_setopt( $curl , CURLOPT_HEADER , false );
	        curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:13.0) Gecko/20100101 Firefox/13.0.1');    // mPDF 5.7.4
	        $data = curl_exec($curl);
	        curl_close($curl);

	        if (!$data) {
	            return [];
	        }
	        $decoded_data = json_decode($data);

	        if (json_last_error() != JSON_ERROR_NONE) {

	            writeln('JSON error: ' . json_last_error());
	            return [];
	        }

	        return $decoded_data;
	    }

	    public function getAddressCandidates( $address, $zip = null, $maxAddresses = null ){

	        // Get possible addresses from API
	        $response = $this->getResponse( $this->end_points['possible_addresses'] . urlencode( $this->prepareAddress( $address ) ) );


echo "<pre>";
print_r($response);exit;
	        if( !$response->candidates ){
	            return false;
	        }

	        $possible_addresses = [];
	        $scoredList = [];
	        $addressBound = [];
	        //Build list of addresses with scores
	        foreach( $response->candidates as $candidate ){
	            $addressBound[] = $candidate->address;
	            $scoredList[] = [
	                'score' => $candidate->score,
	                'addressBound' => $addressBound
	            ];
	        }

	        //Sort scored list by score descending
	        usort($scoredList, function($a, $b) {
	            if ($a['score'] == $b['score']) {
	                return 0;
	            }
	            return ($a['score'] > $b['score']) ? -1 : 1;
	        });

	        //Remove duplicate addresses
	        foreach( $scoredList as $index => $scoredAddress ){

	            if( !in_array( $scoredAddress['addressBound'], $possible_addresses ) ) {
	                $possible_addresses[] = $scoredAddress['addressBound'];
	            } else {
	                unset( $scoredList[$index] );
	            }
	        }

	        $returnAddresses = [];
	        foreach( $scoredList as $address){
	            $returnAddresses[] = $address['addressBound'];
	        }

	        return $returnAddresses;
	    }

	}

	$lookupAddress = "14024 Glenview Dr SW";
	$zip = preg_split( '/-/' , trim( "35803-2523" ) , 2 );
	$zip = trim( $zip[0] );
    if( strlen( $zip) > 5 ) {
        $zip = substr( $zip , 0 , 5 );
    }

	$address = $lookupAddress;

	$customAPI = new customZoneAPI;
	print_r($customAPI->getSuggestions($address, $zip));


//        return $addressBound;
	
 	
