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
	    // in a list request we get all jobs in the array Stellenangebote
	     foreach ($data['Stellenangebote'] as $num => $job) {
		 if (is_array($job)) {	    
		    $newpositions['JobPosting'][$num] = $this->generate_schema_values($job);
		    $newpositions['JobPosting'][$num]['_provider-values'] = $this->add_remaining_non_schema_fields($job);
		    
		 }
	     }
	     $data = $newpositions;	     
	} else {
	    // in a single request we get all jobdata direct on frist level
	    if (is_array($data)) {	    
		    $newpositions['JobPosting'][0] = $this->generate_schema_values($data);
		    $newpositions['JobPosting'][0]['_provider-values'] = $this->add_remaining_non_schema_fields($data);
		     $data = $newpositions;	     
		 }
	    
	}
	return $data;
     }
     
     
     // go through the provider data stream and diff it from the fields
     // we already map to schema.
     // all fields that remain are new or was not mapped to schema and
     // may be used to other purpuses
     private function add_remaining_non_schema_fields($jobdata) {
	$known_fields = array("StellenBezeichnung", "Stellenbezeichnung", "Behoerde", "Id", "Bezahlung", "Plz", "Ort", 
	    "DatumBesetzungZum", "DatumOeffentlichAusschreiben", "TarifEbeneVon", "TarifEbeneBis", "DatumBewerbungsfrist",
	    "BesoldungGruppeBis", "BesoldungGruppeVon", "Studiengaenge", 
	    "BewerbungUrl", "Aufgabenbereiche", "WochenarbeitszeitBeamter", "WochenarbeitszeitArbeitnehmer", "StellenangebotBehoerde",
	    "BeschaeftigungBereichBundesland", "HomepageBehoerde", "Einsatzort", "BeschaeftigungDauer", "BefristetFuer", "Teilzeit", "ExtAnsprechpartner");
	$providerfield = array();
	
	foreach ($jobdata as $name => $value) {
	    if (!in_array($name, $known_fields)) {
		$providerfield[$name] = $value;
	    }
	}
	
	return $providerfield;
     }
     
      // some missing schema fields can be generated automatically 
     private function generate_schema_values($jobdata) {
	 // Paramas:
	 // $jobdata - one single jobarray
	 
	 
	 $res = array();
	 
	// the following schema fields are not set in univis data,
	// but they can be evaluated from others

	 
	
	 // datePosted
	  if (isset($jobdata['Daten']["Eingestellt"])) {
	       $res['datePosted'] = $jobdata['Daten']["Eingestellt"];
	  }
	  if (($jobdata['DatumOeffentlichAusschreiben']) && (!empty($jobdata['DatumOeffentlichAusschreiben']))) {
	       $res['datePosted'] = $jobdata['DatumOeffentlichAusschreiben'];
	  }
	  
	  // description
	  $res['description'] = $jobdata['Beschreibung'];
	  
	  // title
	  if ((isset($jobdata['StellenBezeichnung'])) && (!empty($jobdata['StellenBezeichnung']))) {
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
	  if ((isset($jobdata['DatumBewerbungsfrist'])) && (!empty($jobdata['DatumBewerbungsfrist']))) {
	      $res['validThrough'] = $jobdata['DatumBewerbungsfrist'];
	  }
	  
	  
	  
	  
	// 'jobStartDate'	=> 'start', 
	// jobImmediateStart

	    if (isset($jobdata['DatumBesetzungZum'])) {
		$res['jobStartDate'] = $jobdata['DatumBesetzungZum'];
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
	   if ((isset($jobdata['TarifEbeneVon'])) && (!empty($jobdata['TarifEbeneVon']))) {
	        if (isset($jobdata['TarifEbeneBis'])) {
		    $res['estimatedSalary'] = $this->get_Salary_by_TVL($jobdata['TarifEbeneVon'],$jobdata['TarifEbeneBis']);
		} else {
		    $res['estimatedSalary'] = $this->get_Salary_by_TVL($jobdata['TarifEbeneVon']);
		}
	   } elseif ((isset($jobdata['BesoldungGruppeVon'])) && (!empty($jobdata['BesoldungGruppeVon']))) {
	       if (isset($jobdata['BesoldungGruppeBis'])) {
		    $res['estimatedSalary'] = $this->get_Salary_by_TVL($jobdata['BesoldungGruppeVon'],$jobdata['BesoldungGruppeBis']);
		} else {
		    $res['estimatedSalary'] = $this->get_Salary_by_TVL($jobdata['BesoldungGruppeVon']);
		}
	   }	   
  
	  
	

	    $acontact = $this->get_interamt_application_contact($jobdata);
	    if (!empty($acontact['url'])) {
		$res['applicationContact']['url'] = $acontact['url'];
	    }
	    if (!empty($acontact['email'])) {
		$res['applicationContact']['email'] = $acontact['email'];
	    }
	    if (!empty($acontact['email_subject'])) {
		$res['applicationContact']['email_subject'] = $acontact['email_subject'];
	    }
	    if (isset($acontact['directApply'])) {
		$res['directApply'] = $acontact['directApply'];
	    }
	    
	    
	 
		
		
	    if (isset($jobdata['ExtAnsprechpartner'])) {
		$res['applicationContact']['contactType'] = __('Contact for application','rrze-jobs');
		if ((isset($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerNachname'])) && (!empty($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerNachname']))) {
		    
		    if ((isset($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerVorname'])) && (!empty($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerVorname']))) {
			$res['applicationContact']['name'] = $jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerVorname'].' '.$jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerNachname'];
		    } else {
			$res['applicationContact']['name'] = $jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerNachname'];
		    }
		    
		    $res['employmentUnit']['name'] = $res['applicationContact']['name'];
		}
		if ((isset($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerEMail'])) && (!empty($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerEMail']))) {
			
			$res['employmentUnit']['email'] = $jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerEMail'];
		}
		if ((isset($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerTelefax'])) && (!empty($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerTelefax']))) {
			$res['applicationContact']['faxNumber'] =  $this->sanitize_telefon($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerTelefax']);
			$res['employmentUnit']['faxNumber'] = $res['applicationContact']['faxNumber'];
		}
		if ((isset($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerTelefon'])) && (!empty($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerTelefon']))) {
			$res['applicationContact']['telephone'] =  $this->sanitize_telefon($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerTelefon']);
			$res['employmentUnit']['telephone'] = $res['applicationContact']['telephone'];
		}
		if ((isset($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerStrasse'])) && (!empty($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerStrasse']))) {
			$res['applicationContact']['street'] =  $jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerStrasse'];
			$res['employmentUnit']['street'] = $res['applicationContact']['street'];
		}
	    }
	    
	    
	    
	    
	 // hiringOrganization   (type: Organzsation)
	    // aus orgunit und oder orgname generieren	    
	    // Der Inhalt (Ansprechperson) contact geht hier in contactpoint mit ein
	 
	    
	    if ((isset($jobdata['Behoerde'])) && (!empty($jobdata['Behoerde']))) {
		$res['employmentUnit']['name'] = $jobdata['Behoerde'];
		$res['hiringOrganization']['name'] = $jobdata['Behoerde'];
	    } elseif ((isset($jobdata['StellenangebotBehoerde'])) && (!empty($jobdata['StellenangebotBehoerde']))) {
		$res['employmentUnit']['name'] = $jobdata['StellenangebotBehoerde'];
		$res['hiringOrganization']['name'] = $jobdata['StellenangebotBehoerde'];
	    }
	    if ((isset($jobdata['HomepageBehoerde'])) && (!empty($jobdata['HomepageBehoerde']))) {
		$res['employmentUnit']['url'] = $jobdata['HomepageBehoerde'];
		$res['hiringOrganization']['url'] = sanitize_url($jobdata['HomepageBehoerde']);
	    }
	    
	    
	   

	    
	    if (isset($jobdata['Plz'])) {
		$res['jobLocation']['address']['postalCode'] = $jobdata['Plz'];
	    } elseif (isset($jobdata['Einsatzort']['EinsatzortPLZ'])) {
		$res['jobLocation']['address']['postalCode'] = $jobdata['Einsatzort']['EinsatzortPLZ'];
	    }
	    if (isset($jobdata['Ort'])) {
		$res['jobLocation']['address']['addressLocality'] = $jobdata['Ort'];
	    } elseif (isset($jobdata['Einsatzort']['EinsatzortOrt'])) {
		$res['jobLocation']['address']['postalCode'] = $jobdata['Einsatzort']['EinsatzortOrt'];
	    }
	    
	    if ((isset($jobdata['BeschaeftigungBereichBundesland'])) && (!empty($jobdata['BeschaeftigungBereichBundesland']))) {
		$res['jobLocation']['address']['addressRegion'] = $jobdata['BeschaeftigungBereichBundesland'];
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
	 if (!empty($jobdata['Teilzeit'])) {
	     if ($jobdata['Teilzeit'] == 'Vollzeit') {
		  $res['text_workingtime'] = __('Full time', 'rrze-jobs');		
		 $typeliste[] = 'FULL_TIME';
	     } else {
		  $res['text_workingtime'] = __('Part time', 'rrze-jobs');
		 $typeliste[] = 'PART_TIME';
	     }
	 }
	 			 

	 
	 
	  if ((!empty($jobdata['BeschaeftigungDauer'])) || (isset($jobdata['BeschaeftigungDauer'])) ){
	     if (($jobdata['BeschaeftigungDauer'] == 'befristet') || (!empty($jobdata['BeschaeftigungDauer']))) {
		 $typeliste[] = 'TEMPORARY';
		 $res['text_befristet'] = __('Temporary employment','rrze-jobs');
	     }
	 } 
	if ((isset($jobdata['BefristetFuer'])) && (!empty($jobdata['BefristetFuer']))) {  
	      $res['text_befristet'] = __('Temporary employment until','rrze-jobs').' '.$jobdata['BefristetFuer']. " ".__('monthes','rrze-jobs');
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
	    
	
	 // workHours
	  // wenn type4 gesetzt (Vormittags / nachmittags)
	    // ausserdem, wenn gesetzt: 
	    // 'nd':  Nachtdienst
	    // 'sd':  Schichtdienst
	    // 'bd':  Bereitsschaftsdienst
	    // + wstunden
	$res['workHours'] = '';
	 
	if ((isset($jobdata['WochenarbeitszeitArbeitnehmer'])) && (!empty($jobdata['WochenarbeitszeitArbeitnehmer'])))  {
	      $res['workHours'] = $jobdata['WochenarbeitszeitArbeitnehmer'].' '.__('hours per week','rrze-jobs');
	} elseif ((isset($jobdata['WochenarbeitszeitBeamter'])) && (!empty($jobdata['WochenarbeitszeitBeamter']))) {
	     $res['workHours'] = $jobdata['WochenarbeitszeitBeamter'].' '.__('hours per week','rrze-jobs');
	}

	  
	 
	// Gruppe / Kategorie der Stelle  
	if ((isset($jobdata['Aufgabenbereiche'])) && (!empty($jobdata['Aufgabenbereiche']))) {
	    $res['occupationalCategory'] = $jobdata['Aufgabenbereiche'];
	}
	
	
	
	if ((isset($jobdata['Studiengaenge'])) && (is_array($jobdata['Studiengaenge']))) {
	    $qualification = '';
	    foreach ($jobdata['Studiengaenge'] as $num => $studium) {
		if (!empty($qualification)) {
		    $qualification .= ', ';
		}
		$qualification .= $studium['Studiengang'].' ('.$studium['AbschlussStudium'].')';
		
	    }
	    $res['qualifications'] = '<p>'.$qualification.'</p>';
	    
	}
	  
	if ((isset($jobdata['AnzahlStellen'])) && ($jobdata['AnzahlStellen'] > 0)) {
	      $res['totalJobOpenings'] = $jobdata['AnzahlStellen'];
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
			
			$singledata = $this->get_single($singleparams, false);
			
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
     
     
     public function get_single($params, $parse = true) {
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
	 
	 if ($response['valid'] === true) {
	     
	     if ((is_array($response['content'])) && (!empty($response['content']))) {
		 
		 
		$response['content'] = $this->sanitize_sourcedata($response['content']);  
		if ($parse) {
		    $response['content'] = $this->map_to_schema($response['content']);
		}
	     } else {
		  $aRet = [
                    'valid' => false,
		    'code'  => 404,
		     'error' => 'No entry',
		    'params_given'   => $params,
                    'content' => ''
		];
		return $aRet;
	     }
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
		 if (!empty($uri)) {
		     $uri .= '&';
		 }
		 $uri .= $uriname.'='.$urivalue;
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
	     // wird bei der Indexabfrage geliefert
	     foreach ($data['Stellenangebote'] as $num => $job) {
		 if (is_array($job)) {
		    foreach ($job as $name => $value) {
			switch($name) {

			    case 'StellenBezeichnung':
			    case 'Behoerde':
			    case 'Ort':
				 $value = sanitize_text_field($value);
				break;
			    case 'Id':
			    case 'AnzahlStellen':
				$value = $this->sanitize_type('number',$value);	
				break;
			    case 'url':
				$value = sanitize_url($value);	
				break;

			}
			
			$data['Stellenangebote'][$num][$name] = $value;
		    }

		    
		 }
	     }
	 } else {
		// Bei der Direkten Abfrage einer Stelle wird alles auf oberester Ebene geliefert
	       foreach ($data as $key => $value) {
		   if (!is_array($value)) {
			switch($key) {
			    case 'Kennung':
			    case 'Stellenbezeichnung':
			    case 'StellenangebotBehoerde':
			    case 'BeschaeftigungsBereichBundesland':
			    case 'Aufgabenbereiche':
			    case 'Fachrichtung':
			    case 'Teilzeit':
			    case 'TarifEbeneVon':
			     case 'TarifEbeneBis':
			     case 'BesoldungGruppeVon':
			     case 'BesoldungGruppeBis':   
				 $value = sanitize_text_field($value);
				 break;
			    case 'Beschreibung':
				 $value = $this->sanitize_html_field($value);
				 break;
			     case 'BewerbungUrl':
				 $value = sanitize_url($value);	
				 break;
			    case 'DatumLetzteAenderung':
			    case 'DatumOeffentlichAusschreiben':
			    case 'DatumBewerbungsfrist':
			    case 'DatumBesetzungZum':
				  $value = $this->sanitize_dates($value);	
				 break;
			    case 'Id':
			    case 'AnzahlStellen':	
				 $value = $this->sanitize_type('number',$value);	
				 break;

			     default:
				     $value = sanitize_text_field($value);
			}
		   }
		   $data[$key] = $value;
	       }
	 }
	 
	 return $data;
	 
     } 
     
     
     // Interamt kennt zwar eine Application-URl in dem Feld "BewerbungUrl",
     // allerdings ist dieser nur besetzt,w enn man den Bewerbungsprozess bei Interamt durchführt.
     // Bewerbungsinformationen werden daher in der Regel im Text der Ausschreibung ergänzt.
     // Diese Funktion soll versuchen, die URl bzw. die E-Mail zur Bewerbung zur ermitteln.
     // 
     // returns array with keys 'url' , 'email', 'directApply' and 'email_subject' 
  
     
     private function get_interamt_application_contact($jobdata) {

	 $res['url'] = '';
	 $res['email'] = '';
	 $res['directApply'] = false;
	 $res['email_subject'] = '';
	 
	 if ((isset($jobdata['BewerbungUrl'])) && (!empty($jobdata['BewerbungUrl']))) {
	     // nevertheless, look into the desired field...
	    if (filter_var($jobdata['BewerbungUrl'], FILTER_VALIDATE_URL) !== FALSE) {
		$res['url'] = $jobdata['BewerbungUrl'];
	    } elseif (is_email($jobdata['BewerbungUrl'])) {
		$res['email'] = $jobdata['BewerbungUrl'];
	    }
	 }
	 
	 if ((empty($res['url'])) && (empty($res['email']))) {
	     // try to negotiate it from the description
	     
	     // we splut the text into parts followed by the usual keywords and take the last one
	     // to look for an email or url.
	     $textparts = preg_split('/(Bewerbung|bewerben|apply|Application)\b/i', $jobdata['Beschreibung']);
	     $lastpart = $textparts[array_key_last($textparts)];
	     
	     $lookforurl = $this->get_interamt_application_url_by_text($lastpart);
	     if (!empty($lookforurl)) {
		 $res['url'] = $lookforurl;
	     }
	     // also use the text for geting the email subject, if we need it later 
	     $lookformail = $this->get_interamt_application_mail_by_text($lastpart);
	     if (!empty($lookformail)) {
		 $res['email'] = $lookformail;
	     }
	     
	     
	     
	    $res['email_subject'] = $this->get_application_subject_by_text($lastpart);
	 }
	 
	 
	 
	if ((isset($jobdata['Kennung'])) && (!empty($jobdata['Kennung']))) {
	    // Kennung enthält bei Interamt einen Betreff für Bewerbungen
	    // Dieser String kann bei Bewerbungen über E-Mail für den Mail-Subject verwendet werden. 
	    // this will overwrite the previous negotiated text
	      $res['email_subject'] = $jobdata['Kennung'];
	}


	
	if ((!empty($res['url'])) || (!empty($res['email']))) {
	   $res['directApply'] = true;  
	}
	
	// if every negotiation fails, we try to use the contact mail adress
	// as fallback for the email
	if ((empty($res['email'])) && (isset($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerEMail'])) && (!empty($jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerEMail']))) {
	    $res['email'] =  $jobdata['ExtAnsprechpartner']['ExtAnsprechpartnerEMail'];
	}  
	    
	return $res;
	 
	 
     }
     
     
     
          // searchs for an URL in the text and returns the first hit
     private function get_interamt_application_url_by_text($text) {
	$res = '';
	if (!empty($text)) { 
	    preg_match_all('/<a href="([a-z0-9\/:\-\.\?\+]+)">([^<>]+)<\/a>/i', $text, $output_array);
	 
	    if (!empty($output_array)) {
		if ((isset($output_array[1])) && (isset($output_array[1][0]))) {
		 
		    if (filter_var($output_array[1][0], FILTER_VALIDATE_URL) !== FALSE) {
			$res = $output_array[1][0];
		    }
		}
	    }
	}
	return $res; 
     }
      // searchs for an URL in the text and returns the first hit
     private function get_interamt_application_mail_by_text($text) {
	$res = '';
	if (!empty($text)) { 
	    preg_match_all('/<a href="mailto:([@a-z0-9\/:\-\.]+)">([^<>]+)<\/a>/i', $text, $output_array);
	 
	    if (!empty($output_array)) {
		if ((isset($output_array[1])) && (isset($output_array[1][0]))) {
		    if (is_email($output_array[1][0])) {
			$res = $output_array[1][0];
		    }
		}
	    }
	    
	    if (empty($res)) {
		// look in case the email is written as text in <strong> instead of <a>...
		 preg_match_all('/<strong>([a-z0-9\-\.]+@[a-z0-9\-\.]+)<\/strong>/i', $text, $output_array);
	 
		if (!empty($output_array)) {
		    if ((isset($output_array[1])) && (isset($output_array[1][0]))) {
			if (is_email($output_array[1][0])) {
			    $res = $output_array[1][0];
			}
		    }
		}
	    }
	}

	return $res; 
     }
     

}

  
