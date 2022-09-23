<?php
/**
 * Interamt 
 * 
 * Created on : 14.09.2022
 */

namespace RRZE\Jobs\Provider;

defined('ABSPATH') || exit;
use RRZE\Jobs\Provider;
use RRZE\Jobs\Cache;

class Interamt extends Provider { 

    public function __construct() {
	 $this->api_url	    = 'https://interamt.de/koop/app/webservice_v2';
	 $this->url	    = 'https://interamt.de/';
	 $this->name	    = "Interamt";
	 $this->cachetime   =  3 * HOUR_IN_SECONDS;
	 $this->uriparameter = '';
	 $this->request_args = array(
		'timeout'     => 45,
		'redirection' => 5,
	    );
	 
	 // defines all required parameters for defined request method
	 $this->required_fields = array(
	     'get_list'	=> array(
		 'partner'	=> 'number'
	     ),
	     'get_single'	=> array(
		 'id'	=> 'number'
	     )
	 );
	 
	 

	 
     } 
     
     // which methods do we serve 
     public $methods = array(
	    "get_list", "get_single", "map_to_schema", "get_uri", "required_parameter"
	 );
     
          
     // map univis field names and entries to schema standard
     public function map_to_schema($data) {
	$newpositions = array();
	 // First we make a simple Mapping by an array with fields, that match 
	 // as they are
	
	if (isset($data['Stellenangebote'])) {	    
	     foreach ($data['Stellenangebote'] as $num => $job) {
		 if (is_array($job)) {	    
		    $newpositions['JobPosting'][$num] = $this->generate_schema_values($job, $data);
		    $newpositions['JobPosting'][$num]['_provider-values'] = $this->add_remaining_non_schema_fields($job);
		    
		 }
	     }
	     $data = $newpositions;	     
	}
	return $data;
     }
     
     
     // go through the provider data stream and diff it from the fields
     // we already map to schema.
     // all fields that remain are new or was not mapped to schema and
     // may be used to other purpuses
     private function add_remaining_non_schema_fields($jobdata) {
	$known_fields = array("StellenBezeichnung", "Behoerde", "Id", "Bezahlung", "Plz", "Ort");
	$providerfield = array();
	
	foreach ($jobdata as $name => $value) {
	    if (!in_array($name, $known_fields)) {
		$providerfield[$name] = $value;
	    }
	}
	
	return $providerfield;
     }
     
      // some missing schema fields can be generated automatically 
     private function generate_schema_values($jobdata, $data) {
	 // Paramas:
	 // $jobdata - one single jobarray
	 // $data - all univis data. includes Persondata we need for contacts
	 
	 
	 $res = array();
	 
	// the following schema fields are not set in univis data,
	// but they can be evaluated from others

	 
	
	 // datePosted
	  if (isset($jobdata['Daten']["Eingestellt"])) {
	       $res['datePosted'] = $jobdata['Daten']["Eingestellt"];
	  }
	  
	  // description
	  $res['description'] = $jobdata['Beschreibung'];
	  
	  // title
	  if (isset($jobdata['StellenBezeichnung'])) {
	       $res['title'] = $jobdata['StellenBezeichnung'];
	  } elseif (isset($jobdata['Stellenbezeichnung'])) {
	       $res['title'] = $jobdata['Stellenbezeichnung'];
	  }
	 
	  
	   // identifier
	  $res['identifier'] = $jobdata['Id'];
	 
	  // validThrough
	  if (isset($jobdata['Daten']["Bewerbungsfrist"])) {
	       $res['validThrough'] = $jobdata['Daten']["Bewerbungsfrist"];
	  }
	  
	
	  
	  
  
	  
	// qualifications
	    // contains out of template (headlines) + content from  
	    // desc2 (Notwendige Qualifikation:)
	    // desc3 ( Wünschenswerte Qualifikation:)
	 
	 if ((isset($jobdata['Qualifikation'])) && (!empty($jobdata['Qualifikation']))) {
	     $res['qualifications'] = '<strong>'.__('Notwendige Qualifikation','rrze-jobs').':</strong><br>';
	     $res['qualifications'] .= $jobdata['Qualifikation'];
	 }

	 

	 // applicationContact (type ContactPoint)
	 // 
	    // aus acontact oder aus Texteingabe desc6
	    // acontact = Ansprechpartner
	    //	   Der "contact" hingehgemn (Ansprechpartner) ist contactpoint in hiringOrganisatzion
	    // desc6 = Text für Bewerbungen
	    // applicationContact.url wenn url zur bewerbung vorhanden

		
		
		$persondata = array();
		if (!empty($persondata)) {
		    
			$res['applicationContact']['contactType'] = __('Contact for application','rrze-jobs');
			
			$res['applicationContact']['email'] = $persondata['email'];
			$res['applicationContact']['name'] = $persondata['name'];
			$res['applicationContact']['faxNumber'] = $persondata['faxNumber'];
			$res['applicationContact']['telephone'] = $persondata['telephone'];
			
			$res['directApply'] = true;
		     // directApply => true/false
		    // wenn applicationContact oder desc6 gegeben ist 
		}
	//	$res['acontact'] = $persondata;
		

	
	    if ((isset($jobdata['desc6'])) && (!empty($jobdata['desc6']))) {
	       $res['applicationContact']['description'] = $jobdata['desc6'];
	       
	       $url = $jobdata['desc6'];	     
	       if ($url) {
		    $res['applicationContact']['url'] = $url;
	       }	    
	       
	       $mail = $jobdata['desc6'];
	       if ($mail) {
		    $res['applicationContact']['email'] = $mail;
	       }
	       $res['applicationContact']['contactType'] = __('Contact for application','rrze-jobs');
	       $res['directApply'] = true;
	       	 // directApply => true/false
		 // wenn applicationContact oder desc6 gegeben ist 
	    }
	    
 
 
	 // estimatedSalary  (type: MonetaryAmount)
	    // aus vonbesold und bisbesold   generieren
	    if (isset($jobdata['Bezahlung']))  {
		if ((isset($jobdata['Bezahlung']['Besoldung'])) && (!empty($jobdata['Bezahlung']['Besoldung']))) {
		    $res['estimatedSalary'] = $this->get_Salary_by_TVL(jobdata['Bezahlung']['Besoldung']);
		}
		if ((isset($jobdata['Bezahlung']['Entgelt'])) && (!empty($jobdata['Bezahlung']['Entgelt']))) {
		    $res['estimatedSalary'] = $this->get_Salary_by_TVL($jobdata['Bezahlung']['Entgelt']);
		}
		
		
	    }
	   	    
	    
	    
	 // hiringOrganization   (type: Organzsation)
	    // aus orgunit und oder orgname generieren	    
	    // Der Inhalt (Ansprechperson) contact geht hier in contactpoint mit ein
	 
	    $res['employmentUnit']['name'] = $jobdata['Behoerde'];
	    $res['hiringOrganization']['name'] = $jobdata['Behoerde'];
	    
	   
	    
	    
	
	    $contactpersondata = array();
	    if (!empty($contactpersondata)) {
		    $res['employmentUnit']['email'] = $contactpersondata['email'];		
		    $res['employmentUnit']['faxNumber'] = $contactpersondata['faxNumber'];
		    $res['employmentUnit']['telephone'] = $contactpersondata['telephone'];
		    $res['employmentUnit']['address'] = $contactpersondata['workLocation']['address'];
		    
		    if ((!isset($res['applicationContact']['name'])) || (empty($res['applicationContact']['name']))) {
			$res['applicationContact']['name'] = $contactpersondata['name'];
		    }

	    }
	//    $res['contact'] = $contactpersondata;
	    
	    
	  
	    
	    if (isset($jobdata['Plz'])) {
		$res['jobLocation']['address']['postalCode'] = $jobdata['Plz'];
	    }
	    if (isset($jobdata['Ort'])) {
		$res['jobLocation']['address']['addressLocality'] = $jobdata['Ort'];
	    }
	    
	    
	    
	    if (!isset($res['jobLocation']['address']['addressRegion'])) {
		$res['jobLocation']['address']['addressRegion'] = __('Bavaria','rrze-jobs');
	    }
	      if (!isset($res['jobLocation']['address']['addressCountry'])) {
		$res['jobLocation']['address']['addressCountry'] = 'DE';
	    }
	    
	    // jobLocation (type: Place)
	    // Achtung: Für Google muss die Property addressCountry (DE) enthalten sein. 
		/* "jobLocation": {
			"@type": "Place",
			"address": {
			  "@type": "PostalAddress",
			  "streetAddress": "555 Clancy St",   aus acontact.location.street
			  "addressLocality": "Detroit",	 aus acontact.location.ort ohne plz
			  "addressRegion": "MI",	defaults Bayern
			  "postalCode": "48201",     aus acontact.location.ort nur plz
			  "addressCountry": "US"    defaults auf DE
			}
		      }
		 */
	    
	
	 
	 // employmentType
	  $typeliste = array();
	 if (!empty($jobdata['type2'])) {
	     if ($jobdata['orig_type2'] == 'voll') {
		 $typeliste[] = 'FULL_TIME';
	     } else {
		 $typeliste[] = 'PART_TIME';
	     }
	 }
	  if ((!empty($jobdata['type1'])) || (isset($jobdata['befristet'])) ){
	     if (($jobdata['orig_type1'] == 'bef') || (!empty($jobdata['befristet']))) {
		 $typeliste[] = 'TEMPORARY';
	     }
	 }
	 
	if (!empty($jobdata['orig_group'])) {
	     if ($jobdata['orig_group'] == 'azubi') {
		 $typeliste[] = 'INTERN';
	     }
	 }

	 
	 $res['employmentType'] = $typeliste;

	 
	    // aus type2 bestimmt, muss aber einen oder mehrere der folgenden
	    // Werte enthalten:
	    /*
		FULL_TIME
		PART_TIME
		CONTRACTOR
		TEMPORARY
		INTERN
		VOLUNTEER
		PER_DIEM
		OTHER
	    */
	    // Beispiel: "employmentType": ["FULL_TIME", "CONTRACTOR"]
	    
	// 'jobStartDate'	=> 'start', 
	// jobImmediateStart
	    // Wenn in 'start' kein Datum gesetzt wurde sondern ein String
	 if ($jobdata['start'] == "-1") {
	     $res['jobImmediateStart'] = true;
	     $res['jobStartDate'] = __('As soon as possible','rrze-jobs');
	 }  else {
	      $res['jobStartDate'] = $jobdata['start'];
	 }
	 
	 
	 // workHours
	  // wenn type4 gesetzt (Vormittags / nachmittags)
	    // ausserdem, wenn gesetzt: 
	    // 'nd':  Nachtdienst
	    // 'sd':  Schichtdienst
	    // 'bd':  Bereitsschaftsdienst
	    // + wstunden
	$res['workHours'] = '';
	 
	if (isset($jobdata['wstunden']))  {
	      $res['workHours'] = $jobdata['wstunden'].' '.__('hours per week','rrze-jobs');
	}
	if (!empty($jobdata['type4'] )) {
	       if (!empty($res['workHours'])) {
		  $res['workHours'] .= ', ';
	      }
	      $res['workHours'] = $jobdata['type4'];
	 }
	  
	if ((isset($jobdata['nd'])) && ($jobdata['nd']===true)) {
	      if (!empty($res['workHours'])) {
		  $res['workHours'] .= ', ';
	      }
	      $res['workHours'] .= __('Night duty','rrze-jobs');
	}
	if ((isset($jobdata['sd'])) && ($jobdata['sd']===true)) {
	      if (!empty($res['workHours'])) {
		  $res['workHours'] .= ', ';
	      }
	      $res['workHours'] .= __('Shift work','rrze-jobs');
	}
	if ((isset($jobdata['bd'])) && ($jobdata['bd']===true)) {
	      if (!empty($res['workHours'])) {
		  $res['workHours'] .= ', ';
	      }
	      $res['workHours'] .= __('On-call duty','rrze-jobs');
	}
	  
	 
	// Gruppe / Kategorie der Stelle  
	if ((isset($jobdata['group'])) && (!empty($jobdata['group']))) {
	    $res['occupationalCategory'] = $jobdata['group'];
	}
	
	  
	  // Es gibt kein spezielles Feld in JobPosting mit dem 
	  // so etwas wie Schwangerschaftsvertreung, Krankheitsvertrung oä 
	  // angegeben wird, sieht man in Teiln von employmentType ab. 
	  // Daher nehme hierfür das Feld mit der Zusatzbeschreibung
	  // disambiguatingDescription
	$res['disambiguatingDescription'] = '';
	if ((isset($jobdata['type3'])) && (!empty($jobdata['type3']))) {
	      $res['disambiguatingDescription'] = $jobdata['type3'];
	}
	  
	   // Es gibt kein spezielles Feld in JobPosting in dem ich die 
	   // Befristungsdauer angeben kann, daher ergänze ich auch diese in 
	  // der Zusatzbeschreibung
	if ((isset($jobdata['befristet'])) && (!empty($jobdata['befristet']))) {
	      if (!empty($res['disambiguatingDescription'])) {
		  $res['disambiguatingDescription'] .= ", ";
	      }
	      $res['disambiguatingDescription'] .= __('Temporary employment until','rrze-jobs').' '.$jobdata['befristet'];
	}
	if ((isset($jobdata['desc4'])) && (!empty($jobdata['desc4']))) {
	     if (!empty($res['disambiguatingDescription'])) {
		  $res['disambiguatingDescription'] = '<p>'.$res['disambiguatingDescription']."</p>\n";
	      }
	      $res['disambiguatingDescription'] .= '<p>'.$jobdata['desc4'].'</p>';
	    
	}  
	  
	return $res;
    }
     
    
     
     // make request for a positions list and return it as array
     public function get_list($params) {	 
	 $check = $this->required_parameter("get_list",$params);
	 
	 if (is_array($check)) {    
	      $aRet = [
                    'valid' => false,
		    'code'    => 405,
		    'error' => $check,
		    'params_given'   => $params,
                    'content' => ''
              ];
	      return $aRet;
	 }
	 $response = $this->get_data("get_list", $params);
	 
	 if ($response['valid'] == true) {
	     
	     // After i got the list from interamt, i have to get the detail 
	     // data from each single job in an additional request, cause the
	     // list doesnt contains the details
	     
	     
	     $singleparams = $params;
	     
	     if ((isset($response['content']['Anzahltreffer']) && ((intval($response['content']['Anzahltreffer']) > 0)))) {
		 
		 
		 if (isset($response['content']['Stellenangebote'])) {
		    foreach ($response['content']['Stellenangebote'] as $num => $pos) {
			$singleparams['get_single']['id'] = $pos['Id']; 
			
			$singledata = $this->get_single($singleparams);
			
			if ($singledata['valid'] == true) {
			    $response['content']['Stellenangebote'][$num] = $singledata['content'];
			}
			
		    }
		 }
		  
	     
	     }
	     
	   
	     
	     
	     
	     $response['content'] = $this->sanitize_sourcedata($response['content']);   
	     $response['content'] = $this->map_to_schema($response['content']);
	     
	 }

	 return $response;
	 
     }
     
     
     public function get_single($params) {
	$check = $this->required_parameter("get_single",$params);
	 
	 if (is_array($check) && count($check) > 0) {    
	      $aRet = [
                    'valid' => false,
		    'code'  => 405,
		    'error' => $check,
		    'params_given'   => $params,
                    'content' => ''
              ];
	      return $aRet;
	 }
	 

	 $response = $this->get_data("get_single", $params);
	 
	 if ($response['valid'] == true) {
	     $response['content'] = $this->sanitize_sourcedata($response['content']);  
     	     $response['content'] = $this->map_to_schema($response['content']);
	 }

	 return $response;
     }
     
     

     
     // Generate URI for request
     public function get_uri($method = 'get_list', $params) {
	 $uri = $this->uriparameter;
	 
	 foreach ($params[$method] as $name => $value) {
	     $type = 'string';
	     if (isset($this->required_fields[$method][$name])) {
		$type =  $this->required_fields[$method][$name];
	     } 
	     $urivalue = $this->sanitize_type($type, $value);
	     $uriname = $this->sanitize_type('key', $name);
	     
	     if ((!empty($uriname)) && (!empty($urivalue))) {
		 $uri .= '&'.$uriname.'='.$urivalue;
	     }
	 }
	 
	 return $uri;
     }
     
     
    // Methode prüft, ob für einen Request mit einer definiertem Methode alle 
    // notwendigen Parameter vorhanden sind
    // Returns true if all required parameters are set.
    // Otherwise it returns an array with the missing parameters
    public function required_parameter($method = 'get_list', $params = array()) {
	$found = array();
	
	if (!isset($params[$method])) {
	    // No params for method
	} else {
	    foreach ($params[$method] as $name => $value) {
		if (isset($this->required_fields[$method][$name])) {
		    $type =  $this->required_fields[$method][$name];
		    $urivalue = $this->sanitize_type($type, $value);
		     if (!empty($urivalue)) {
			 $found[$name] = $urivalue;		
		     }
		}
	    }
	}
	// Now check, if all required fields are there
	 $diff = array_diff_key($this->required_fields[$method], $found);

	 if (count($diff)>0) {
	     return $diff;
	 }
	 
	 
	return true;
    }
    
    // get the raw data from provider by a a method and parameters
    public function get_data($method = 'get_list', $params) { 
	$uri = $this->get_uri($method,$params);
	$url = $this->api_url.'?'.$uri;
	
	
	$cache = new Cache();
	$cache->set_cachetime($this->cachetime);
	$org = '';
	if (isset($params[$method]['partner'])) {
	    $org = $params[$method]['partner'];
	} 
	$id = '';
	if (isset($params[$method]['id'])) {
	    $id = $params[$method]['id'];
	} 
	$cachedout = $cache->get_cached_job('Interamt',$org,$id,$method);
	if ($cachedout) {
	    return $cachedout;
	}
	$remote_get    = wp_safe_remote_get( $url , $this->request_args);
	
	if ( is_wp_error( $remote_get ) ) {	
		 $aRet = [
                    'valid' => false,
                    'content' => $remote_get->get_error_message()
                ];
		return $aRet;
         } else {
	     $content = json_decode($remote_get["body"], true);
	     
	     $aRet = [
		    'request'	=> $url,
                    'valid'	=> true,
                    'content'	=> $content,
              ];
	     
	     $cache->set_cached_job('Interamt',$org,$id,$method, $aRet);
	     
	     return $aRet;
	  }
	 
	
     }

     
     // Some data source use own formats in text fields (like markdown or 
     // univis text format) or source defined selectors or values 
     // which has to be translatet in a general form (HTML). 
     public function sanitize_sourcedata($data) {
	 if (empty($data)) {
	     return false;
	 }
	 if (isset($data['Stellenangebote'])) {
	     foreach ($data['Stellenangebote'] as $num => $job) {
		 if (is_array($job)) {
		    foreach ($job as $name => $value) {
			switch($name) {

			    case 'title':
				 $value = sanitize_text_field($value);
				break;

			    case 'wstunden':
				$value = $this->sanitize_type('float',$value);	
				break;
			    case 'id':
				$value = $this->sanitize_type('number',$value);	
				break;

				break;
			    case 'url':
				$value = sanitize_url($value);	
				break;

				
			    default:
				$value = sanitize_text_field($value);
			}
			
			$data['Position'][$num][$name] = $value;
		    }

		    
		 }
	     }
	 }
	 if (isset($data['Stellenangebote'])) {
	      foreach ($data['Stellenangebote'] as $num => $person) {
		 if (is_array($person)) {
		    foreach ($person as $name => $value) {
			switch($name) {
			    case 'location':
				$value = $this->sanitize_interamt_location($value);
				break;

			    case 'orgname':
			    case 'work':	
			    case 'title':
			    case 'atitle':
			    case 'lastname':
			    case 'firstname':
			    
				 $value = sanitize_text_field($value);
				break;
			    
			    case 'Id':
				$value = sanitize_text_field($value);
				break;
			    
			    default:
				$value = sanitize_text_field($value);
			}
			$data['Person'][$num][$name] = $value;
			
		    }
		 }
	      }
	 }
	 return $data;
	 
     } 
     
         

     
    // sanitize univis location
    private function sanitize_interamt_location($value) {
	 if (is_array($value)) {
	     $res = array();
	     foreach ($value as $name => $entry) {
		 if (is_array($entry)) {
		     // Subarray, es gibt mehr als eine location
		     $res[$name] = $this->sanitize_interamt_location($entry);
		 } else {
		     switch($name) {
			    case 'street':
			    case 'office':	
			    case 'ort':
			    case 'pgp':
				$value = sanitize_text_field($entry);
				break;
			    case 'tel':
			    case 'fax':
			    case 'mobile':				
				$value = $this->sanitize_interamt_telefon($entry);
				break;
				
			    case 'url':
				$value = sanitize_url($entry);	
				break;
			    case 'email':
				$value = sanitize_email($entry);	
				break;				    
			  default:
				$value = sanitize_text_field($entry);
		     }
		     $res[$name] = $value;
		 }
		 
	     }
	     return $res;
	 } else {
	     $value = sanitize_text_field($value);
	     return $value;
	 }
     }

     // try to sanitize and repair the telephone number 
     private function sanitize_interamt_telefon($phone_number ) {
	 
	$phone_number = trim($phone_number);
	
        if( ( strpos( $phone_number, '+49 9131 85-' ) !== 0 ) && ( strpos( $phone_number, '+49 911 5302-' ) !== 0 ) ) {
            if( !preg_match( '/\+49 [1-9][0-9]{1,4} [1-9][0-9]+/', $phone_number ) ) {
                $phone_data = preg_replace( '/\D/', '', $phone_number );
                $vorwahl_erl = '+49 9131 85-';
                $vorwahl_nbg = '+49 911 5302-';

		switch( strlen( $phone_data ) ) {
		    case '3':
			$phone_number = $vorwahl_nbg . $phone_data;
			break;
		    case '5':
			if( strpos( $phone_data, '06' ) === 0 ) {
			    $phone_number = $vorwahl_nbg . substr( $phone_data, -3 );
			    break;
			}                                 
			$phone_number = $vorwahl_erl . $phone_data;
			break;
		    case '7':
			if( strpos( $phone_data, '85' ) === 0 || strpos( $phone_data, '06' ) === 0 )  {
			    $phone_number = $vorwahl_erl . substr( $phone_data, -5 );
			    break;
			}
			if( strpos( $phone_data, '5302' ) === 0 ) {
			    $phone_number = $vorwahl_nbg . substr( $phone_data, -3 );
			    break;
			} 
		    default:
			if( strpos( $phone_data, '9115302' ) !== FALSE ) {
			    $durchwahl = explode( '9115302', $phone_data );
			    if( strlen( $durchwahl[1] ) ===  3 ) {
				$phone_number = $vorwahl_nbg . substr( $phone_data, -3 );
			    }
			    break;
			}  
			if( strpos( $phone_data, '913185' ) !== FALSE )  {
			    $durchwahl = explode( '913185', $phone_data );
			    if( strlen( $durchwahl[1] ) ===  5 ) {
				$phone_number = $vorwahl_erl . substr( $phone_data, -5 );
			    }
			    break;
			}
			if( strpos( $phone_data, '09131' ) === 0 || strpos( $phone_data, '499131' ) === 0 ) {
			    $durchwahl = explode( '9131', $phone_data );
			    $phone_number = "+49 9131 " . $durchwahl[1];
			    break;
			}
			if( strpos( $phone_data, '0911' ) === 0 || strpos( $phone_data, '49911' ) === 0 ) {
			    $durchwahl = explode( '911', $phone_data );
			    $phone_number = "+49 911 " . $durchwahl[1];
			    break;
			}

		}
                
        
            }
        }
        return $phone_number;
    }
     
     
     
   
     // check for select fields from univis
     private function sanitize_univis_typen($type = 'type1', $value) {
	 $validselectors = [
	     "type1"	=> [
		 'unbef'    => __('unlimited','rrze-jobs'),
		 'bef'    => __('temporary', 'rrze-jobs')
		 
	     ],
	      "type2"	=> [
		 'voll'    =>  __('Full time', 'rrze-jobs'),
		 'teil'    =>  __('Part time', 'rrze-jobs')
	     ],
	     "type3"	=> [
		 'vertr' => __('Replacement', 'rrze-jobs'), // 'Vertretung',
		 'schwanger' => __('Maternity leave replacement', 'rrze-jobs'), // 'Mutterschutzvertretung', 
		 'eltern' => __('Maternity/parental leave replacement', 'rrze-jobs'), // 'Mutterschutz- / Elternzeitvertretung',
		 'forsch' => __('Limited research project', 'rrze-jobs'), // 'befristetes Forschungsvorhaben',
		 'krankh' => __('Sickness replacement', 'rrze-jobs'), // 'befristetes Krankheitsvertretung',
		 'zeitb'  => __('Temporary officials', 'rrze-jobs'), // 'Beamtenschaft auf Zeit'
	     ],
	      "type4"	=> [
		 'nachv'    =>  __('By arrangement', 'rrze-jobs'),
		 'vorm'    =>  __('Morning times', 'rrze-jobs'),
		 'nachm'    =>  __('Afternoon times', 'rrze-jobs')
	     ],
	 ];
	 if (isset($validselectors[$type])) {
	     if (isset($validselectors[$type][$value])) {
		 return $validselectors[$type][$value];
	     }
	 }
	 return sanitize_text_field($value);

     }


     

     
     
     
     
    
     
     
     


   
     
     
     
    
    
}

  
