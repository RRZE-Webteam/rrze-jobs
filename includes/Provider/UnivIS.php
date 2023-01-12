<?php
/**
 * UnivIS 
 * 
 * Created on : 08.08.2022, 16:45:20
 */

namespace RRZE\Jobs\Provider;

defined('ABSPATH') || exit;
use RRZE\Jobs\Provider;
use RRZE\Jobs\Cache;
use RRZE\Jobs\Helper;

class UnivIS extends Provider { 

     public function __construct($use_cache = true) {
        $this->use_cache = $use_cache;
	 $this->api_url	    = 'https://univis.uni-erlangen.de/prg';
	 $this->url	    = 'https://univis.uni-erlangen.de/';
	 $this->name	    = "UnivIS";
	 $this->cachetime   =  3 * HOUR_IN_SECONDS;
	 $this->uriparameter = 'search=positions&closed=0&show=json';
	 $this->request_args = array(
		'timeout'     => 45,
		'redirection' => 5,
	    );
	 
	 // defines all required parameters for defined request method
	 $this->required_fields = array(
	     'get_list'	=> array(
		 'department'	=> 'uriparam'
	     ),
	     'get_single'	=> array(
		 'id'	=> 'number'
	     ),
	     'get_group' => array(
                'group' => 'uriparam',
            )
	 );
	 
	 

	 
     } 
     
     // which methods do we serve 
     public $methods = array(
	    "get_group", "get_list", "get_single", "map_to_schema", "get_uri", "required_parameter"
	 );
     
          
     // map univis field names and entries to schema standard
     public function map_to_schema($data) {
	$newpositions = array();
	 // First we make a simple Mapping by an array with fields, that match 
	 // as they are
	
	if (isset($data['Position'])) {	    
	     foreach ($data['Position'] as $num => $job) {
		 if (is_array($job)) {	    
		    $newpositions['JobPosting'][$num] = $this->generate_schema_values($job, $data);
		    $newpositions['JobPosting'][$num]['_provider-values'] = $this->add_remaining_non_schema_fields($job);
		    $newpositions['JobPosting'][$num]['_provider-values']['provider'] = $this->name;
		    
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
	$known_fields = array(
		    'creation',		   
		    'title',
		    'id', 'key',
		    'enddate', 'start',
		    'desc1', 'desc2', 'desc3', 'desc4', 'desc6',
		    'orig_type1', 'orig_type2', 'orig_type3', 'orig_type4',
		    'acontact', 'contact',
		    'vonbesold', 'bisbesold',
		    'nd', 'sd', 'bd', 'wstunden',
		    'orgname', 'orgunit', 'orgunit_en',
		    'group', 'orig_occupationalCategory'
	    );
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
	  $res['datePosted'] = $jobdata['creation'];
	  
	  // description
	  $res['description'] = $jobdata['desc1'];
	  
	  // title
	  $res['title'] = $jobdata['title'];
	  
	   // identifier
	  $res['identifier'] = $jobdata['id'];
	 
	   // job_id (needed by FAU-Jobportal)
	    $res['job_id'] = $jobdata['id'];
	
	   // intern (needed by FAU-Jobportal)
	   $res['intern'] = (!empty($jobdata['intern']) ? true : false);
	
	  // validThrough
	  $res['validThrough'] = $jobdata['enddate'];
	  
  
	  
	// qualifications
	    // contains out of template (headlines) + content from  
	    // desc2 (Notwendige Qualifikation:)
	    // desc3 ( Wünschenswerte Qualifikation:)
	 $res['qualifications'] = '';
	 if ((isset($jobdata['desc2'])) && (!empty($jobdata['desc2']))) {
	     
	     // 
	     $res['qualifications'] = '<p><strong>{{=const.title_qualifications_required}}:</strong></p>';
	    //  $res['qualifications'] = '<p><strong>'.__("Your profile (necessary)", 'rrze-jobs').':</strong></p>';
	     $res['qualifications'] .= $jobdata['desc2'];
	 }
	 if (!empty($jobdata['desc3'])) {

	     // {{=const.title_qualifications}}
	     $res['qualifications'] .= '<p><strong>{{=const.title_qualifications_optional}}:</strong></p>';
	    // $res['qualifications'] .= '<p><strong>'.__("Your profile (desired)", 'rrze-jobs').':</strong></p>';
	    $res['qualifications'] .= $jobdata['desc3'];
	 }
	 

	 // applicationContact (type ContactPoint)
	 // 
	    // aus acontact oder aus Texteingabe desc6
	    // acontact = Ansprechpartner
	    //	   Der "contact" hingehgemn (Ansprechpartner) ist contactpoint in hiringOrganisatzion
	    // desc6 = Text für Bewerbungen
	    // applicationContact.url wenn url zur bewerbung vorhanden

		
		
		$persondata = $this->get_univis_person_contactpoint_by_key($jobdata['acontact'], $data);	
		if (!empty($persondata)) {
		    
			$res['applicationContact']['contactType'] = __('Contact for application','rrze-jobs');
			if (isset($persondata['email'])) {
			    $res['applicationContact']['email'] = $persondata['email'];
			}
			if (isset($persondata['name'])) {
			    $res['applicationContact']['name'] = $persondata['name'];
			}
			if (isset($persondata['faxNumber'])) {
			    $res['applicationContact']['faxNumber'] = $persondata['faxNumber'];
			}
			if (isset($persondata['telephone'])) {
			    $res['applicationContact']['telephone'] = $persondata['telephone'];
			}
			
			$res['directApply'] = true;
		     // directApply => true/false
		    // wenn applicationContact oder desc6 gegeben ist 
		}
	//	$res['acontact'] = $persondata;
		

	
	    if ((isset($jobdata['desc6'])) && (!empty($jobdata['desc6']))) {
	       $res['applicationContact']['description'] = $jobdata['desc6'];
	       
	       $url = $this->get_univis_application_url_by_text($jobdata['desc6']);	     
	       if ($url) {
		    $res['applicationContact']['url'] = $url;
	       }	    
	       
	       $mail = $this->get_univis_application_mail_by_text($jobdata['desc6']);
	       if ($mail) {
		    $res['applicationContact']['email'] = $mail;	    
		    $res['applicationContact']['email_subject'] = $this->get_application_subject_by_text($jobdata['desc6']);
		    
	       }
	       $res['applicationContact']['contactType'] = __('Contact for application','rrze-jobs');
	       $res['directApply'] = true;
	       	 // directApply => true/false
		 // wenn applicationContact oder desc6 gegeben ist 
	    }
	    
 
 
	 // estimatedSalary  (type: MonetaryAmount)
	    // aus vonbesold und bisbesold   generieren
	    if (isset($jobdata['vonbesold']) || isset($jobdata['bisbesold'])) {
		$von = $bis = '';
		if (!empty($jobdata['vonbesold'])) {
		    $von = $jobdata['vonbesold'];
		}
		if (!empty($jobdata['bisbesold'])) {
		    $bis = $jobdata['bisbesold'];
		}
		$res['estimatedSalary'] = $this->get_Salary_by_TVL($von, $bis);
	    }
	   	    
	    
	    
	 // hiringOrganization   (type: Organzsation)
	    // aus orgunit und oder orgname generieren	    
	    // Der Inhalt (Ansprechperson) contact geht hier in contactpoint mit ein
	 
	    $res['employmentUnit']['name'] = $jobdata['orgname'];
	    $res['hiringOrganization']['name'] = $jobdata['orgname'];
	    
	    if (isset($jobdata['orgunit'])) {
		// Wir haben ein Orgabaum. Daher nehme nicht den letzten EIntrag aus
		// dem Orgabaum als Name, sondern ergänze mit dem davor, wenn vorhanden
		// Da aus UnivIS die Werte leider in 2 Sprachen kommen, 
		// gebe hier entsprechend der Website-Sprache zurück.
		
		 $orgarray = $jobdata['orgunit'];
		 $charset = get_bloginfo('language');
		 if (strpos($charset,'de') !== false) {
		     $orgarray = $jobdata['orgunit'];
		 } elseif (isset($jobdata['orgunit_en'])) {
		    $orgarray = $jobdata['orgunit_en'];
		 }
		 $thisname = '';
		 $count = count($orgarray);
		 if (trim($orgarray[$count - 1]) !== trim($jobdata['orgname'])) {
		     $thisname = $orgarray[$count - 1];
		 } 
		 if (isset($orgarray[$count - 2])) {
		     if (!empty($thisname)) {
			 $thisname = $orgarray[$count - 2].', '.$thisname; 
		     } else {
			 $thisname = $orgarray[$count - 2];
		     }
		    
		 }
		 if (!empty($thisname)) {
		     $res['hiringOrganization']['name'] = $thisname;
		 }
		 
	    }
	    
	    
	    if ((!isset($jobdata['contact'])) && (isset($jobdata['acontact']))) {
		// Wenn es keinen Ansprechpartner gibt als Eintrag, aber einen 
		// Ansprechpartner für Bewerbungen, dann nehme den bei den 
		// Bewerbungen auch als Ansprechpartner für Fragen
		$jobdata['contact'] = $jobdata['acontact'];
	    }

	    $contactpersondata = $this->get_univis_person_contactpoint_by_key($jobdata['contact'], $data);	
	    if (!empty($contactpersondata)) {
		if (isset($contactpersondata['email'])) {
		    $res['employmentUnit']['email'] = $contactpersondata['email'];		
		}
		if (isset($contactpersondata['faxNumber'])) {
		    $res['employmentUnit']['faxNumber'] = $contactpersondata['faxNumber'];
		}
		if (isset($contactpersondata['telephone'])) {
		     $res['employmentUnit']['telephone'] = $contactpersondata['telephone'];
		}
		if (isset($contactpersondata['workLocation']['address'])) {
		    $res['employmentUnit']['address'] = $contactpersondata['workLocation']['address'];
		}  
		if ((!isset($res['applicationContact']['name'])) || (empty($res['applicationContact']['name']))) {
		    $res['applicationContact']['name'] = $contactpersondata['name'];
		}

	    }
	//    $res['contact'] = $contactpersondata;
	    
	    
	    if (!empty($contactpersondata['workLocation']['address'])) {
		$res['jobLocation']['address'] = $contactpersondata['workLocation']['address'];
	    } elseif (!empty($persondata['workLocation']['address'])) {
		$res['jobLocation']['address'] = $persondata['workLocation']['address'];
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
		 $res['text_workingtime'] = __('Full time', 'rrze-jobs');			 
	     } else {
		 $res['text_workingtime'] = __('Part time', 'rrze-jobs');
		 $typeliste[] = 'PART_TIME';
	     }
	 }
	  if ((!empty($jobdata['type1'])) || (isset($jobdata['befristet'])) ){
	     if (($jobdata['orig_type1'] == 'bef') || (!empty($jobdata['befristet']))) {
		 $typeliste[] = 'TEMPORARY';		 
		 $res['text_befristet'] = __('Temporary employment','rrze-jobs');
	     } elseif ($jobdata['orig_type1'] == 'unbef') {
		 $res['text_befristet'] = __('Permanent employment','rrze-jobs');
	     }
	 }
	 
	if (!empty($jobdata['orig_group'])) {
	     if ($jobdata['orig_group'] == 'azubi') {
		 $typeliste[] = 'INTERN';
	     }
	     
	     $res['orig_occupationalCategory'] = $jobdata['orig_group'];
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
	      $res['workHours'] .= __('Shift work','rrze-jobs');
	      // In Absprache mit Ch. Schmidt vom 2.11. soll es nur noch
	      // Schichtdienst/Shift work und On Call Duty geben
	//      $res['workHours'] .= __('Night duty','rrze-jobs');
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
	  

	if ((isset($jobdata['desc4'])) && (!empty($jobdata['desc4']))) {
	     if (!empty($res['disambiguatingDescription'])) {
		  $res['disambiguatingDescription'] = '<p>'.$res['disambiguatingDescription']."</p>\n";
	      }
	      $res['disambiguatingDescription'] .= '<p>'.$jobdata['desc4'].'</p>';
	    
	}  
	
	if (!empty($jobdata['desc5'])) {
	    // Die Einleitung geht in ein die employerOverview
	    // https://schema.org/employerOverview

            $res['employerOverview']  = $jobdata['desc5'];

	}  

	return $res;
    }
     
     // get a person by its key and return the contactdata fields
    private function get_univis_person_contactpoint_by_key($key, $data) {
	 
	 $res = array();
	 
	 if (isset($data['Person'])) {
	       foreach ($data['Person'] as $num => $person) {
		 if (is_array($person)) {
		     
		     if ($person['key'] == $key) {
			if (!empty($person['location'][0]['email'])) {
			    $res['email'] = $person['location'][0]['email'];
			}
			if (!empty($person['location'][0]['tel'])) {
			    $res['telephone'] = $person['location'][0]['tel'];
			}
			if (isset($person['location'][0]['fax'])) {
			    $res['faxNumber'] = $person['location'][0]['fax'];
			}
			if (!empty($person['lastname'])) {
			    $res['familyName'] = $person['lastname'];
			}
			if (!empty($person['firstname'])) {
			    $res['givenName'] = $person['firstname'];
			}
			if (!empty($person['orgname'])) {
			    $res['worksFor'] = $person['orgname'];
			}
			if (isset ($person['gender']))
			    $res['gender'] = $person['gender'];
			
			if (!empty($person['id'])) {
			    $res['identifier'] = $person['id'];
			}
			$res['name'] = '';
			if  (!empty($res['title'])) {
			    $res['honorificPrefix'] = $person['title'];
			    $res['name'] = $person['title'];
			    $res['name'] .= ' ';
			} 
			$res['name'] .= $person['firstname']. ' '.$person['lastname'];
			if (!empty($person['orgname'])) {
			    $res['workLocation']['name'] =  $person['orgname'];
			}
			if (!empty($person['location'][0]['street'])) {
			    $res['workLocation']['address']['streetAddress'] = $person['location'][0]['street'];
			}
			if (!empty($person['location'][0]['ort'])) {
			    $res['workLocation']['address']['addressLocality'] = preg_replace('/([0-9\s]+)/i', '', $person['location'][0]['ort']);
			    $res['workLocation']['address']['postalCode'] = preg_replace('/([^0-9]+)/i', '', $person['location'][0]['ort']);
			}
			
			
			// $res['orig'] =  $person;
		     }
		   
		 }
	       }
	 }
	 return $res;
     }
     
     // searchs for an URL in the text and returns the first hit
     private function get_univis_application_url_by_text($text) {
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
     private function get_univis_application_mail_by_text($text) {
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
	}
	return $res; 
     }
     
     // make request for a positions list and return it as array
    public function get_group($params) {
        $check = $this->required_parameter("get_group", $params);

        if (is_array($check)) {
            $aRet = [
                'valid' => false,
                'code' => 405,
                'error' => $check,
                'params_given' => $params,
                'content' => '',
            ];

            return $aRet;
        }

        $aGroups = (!empty($params['get_group']['group']) ? explode(',', $params['get_group']['group']) : []);

        foreach ($aGroups as $group) {
            $params['get_group']['group'] = $group;
            $responseGroup = $this->get_data($params, "get_group");

            if ($responseGroup['valid'] == true) {
                $responseGroup['content'] = $this->sanitize_sourcedata($responseGroup['content']);
                $responseGroup['content'] = $this->map_to_schema($responseGroup['content']);

                if (!empty($response['content'])) {
                    $response['content']['JobPosting'] = array_merge($response['content']['JobPosting'], $responseGroup['content']['JobPosting']);
                }
            }

            if (empty($response)) {
                $response = $responseGroup;
            }
        }

        return $response;
    }
   
     
     
     // make request for a positions list and return it as array
     public function get_list($params) {	 
	 $check = $this->required_parameter("get_list",$params);
	 
	 if (is_array($check)) {    
	      $aRet = [
                    'valid' => false,
		    'code'  => 405,
		    'error' => $check,
		    'params_given'   => $params,
                    'content' => ''
              ];
	      return $aRet;
	 }
	 $response = $this->get_data($params, "get_list");
	 
	 if ($response['valid'] == true) {
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
	 

	 $response = $this->get_data($params, "get_single");
	 
	 if ($response['valid'] == true) {
	     $response['content'] = $this->sanitize_sourcedata($response['content']);  
     	     $response['content'] = $this->map_to_schema($response['content']);
	 }

	 return $response;
     }
     
     

     
     // Generate URI for request
     public function get_uri($params, $method = 'get_list') {
	 $uri = $this->uriparameter;
	 
	 foreach ($params[$method] as $name => $value) {
	     $type = 'uriparam';
	     if (isset($this->required_fields[$method][$name])) {
		$type =  $this->required_fields[$method][$name];
	     } 
	     
	     $urivalue = $this->sanitize_type($value, $type);
	     $uriname = $this->sanitize_type($name, 'key');

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
		    $urivalue = $this->sanitize_type($value, $type);
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
    public function get_data($params, $method = 'get_list') {
	$uri = $this->get_uri($params, $method);
	$url = $this->api_url.'?'.$uri;
	
	
	$cache = new Cache();
	$cache->set_cachetime($this->cachetime);
	$org = '';
	if (isset($params[$method]['department'])) {
	    $org = preg_replace('/[^0-9,]/', '', $params[$method]['department']);
	} 
	$id = '';
	if (isset($params[$method]['id'])) {
	    $id = $params[$method]['id'];
	} elseif ($method== "get_group") {
	    $id =  $params['get_group']['group'];
	}
	

	if ($this->use_cache) {
            $cachedout = $cache->get_cached_job('UnivIS', $org, $id, $method);
            if ($cachedout) {
                return $cachedout;
            }
        }
	$remote_get    = wp_safe_remote_get( $url , $this->request_args);
	
	if ( is_wp_error( $remote_get ) ) {	
		 $aRet = [
                    'valid' => false,
                    'error' => $remote_get->get_error_message(),
		    'code'    => $remote_get->get_error_code(),
		    'params_given'   => $params,
		    'content'	=> ''
                ];
		return $aRet;
         } else {
	     $content = json_decode($remote_get["body"], true);
	     
	     if (!is_array($content) && strpos($content, 'keine passenden ') === false) {
		  $aRet = [
                    'valid' => false,
                    'error' => 'No entry',
		    'code'   => 404,
		    'params_given'   => $params,
		    'content'	=> ''
                ];
		return $aRet;
	     }
	     
	     $aRet = [
		    'request'	=> $url,
                    'valid'	=> true,
		    'code'   => 200,
		    'params_given'   => $params,
                    'content'	=> $content,
              ];
	     
	     $cache->set_cached_job('UnivIS',$org,$id,$method, $aRet);
	     
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
	 if (isset($data['Position'])) {
	     foreach ($data['Position'] as $num => $job) {
		 if (is_array($job)) {
		    foreach ($job as $name => $value) {
			switch($name) {
			    case 'desc1':
			    case 'desc2':
			    case 'desc3':
			    case 'desc4':
			    case 'desc5':
			    case 'desc6':
				$value = $this->sanitize_univis_textfeld($value);
				break;
			    
			    case 'group':
				$data['Position'][$num]['orig_group'] = $value;
				$value = $this->sanitize_occupationalCategory($value);
				
				break;
			    
			    case 'orgname':
			    case 'title':
				 $value = sanitize_text_field($value);
				break;
			    case 'lang':
				 $value = $this->sanitize_lang($value);
				break;
			    case 'start':
				 $value = $this->sanitize_univis_jobstart($value);	
				break;
			    case 'enddate':
			    case 'creation':					
				 $value = $this->sanitize_dates($value);	
				break;
			   case 'befristet':					
				 $value = $this->sanitize_univis_befristet($value);	
				break; 
			    case 'wstunden':
				$value = $this->sanitize_type($value, 'float');	
				break;
			    case 'id':
				$value = $this->sanitize_type($value, 'number');	
				break;
			    case 'key':
			    case 'acontact':
			    case 'contact':	
				$value = $this->sanitize_univis_key($value);	
				break;
			    case 'url':
				$value = sanitize_url($value);	
				break;
			    case 'type1':
				$data['Position'][$num]['orig_type1'] = $value;
				$value = $this->sanitize_univis_typen($value, 'type1');	
				break;
			    case 'type2':
				$data['Position'][$num]['orig_type2'] = $value;
				$value = $this->sanitize_univis_typen($value, 'type2');	
				break;
			    case 'type3':
				$data['Position'][$num]['orig_type3'] = $value;
				$value = $this->sanitize_univis_typen($value, 'type3');	
				break;
			    case 'type4':
				$data['Position'][$num]['orig_type4'] = $value;
				$value = $this->sanitize_univis_typen($value, 'type4');	
				break;
				
			    case 'orgunit':
			    case 'orgunit_en':
				$value = $this->sanitize_univis_orgunits($value);
				break;
				
			    case 'intern':  // Stellenangebot intern
			    case 'nd':   // Nachtdienst
			    case 'sd':   // Schichtdienst
			    case 'bd':   // Bereitsschaftsdienst
				$value = $this->sanitize_univis_boolean($value);
				break;
				
			    case 'vonbesold':
			    case 'bisbesold':
				$value = $this->sanitize_tvl($value);
				break;
				
			    default:
				$value = sanitize_text_field($value);
			}
			
			$data['Position'][$num][$name] = $value;
		    }

		    
		 }
	     }
	 }
	 if (isset($data['Person'])) {
	      foreach ($data['Person'] as $num => $person) {
		 if (is_array($person)) {
		    foreach ($person as $name => $value) {
			switch($name) {
			    case 'lehr':  // Lehrperson
			    case 'pub_visible':   // Publikationsliste und Vorträge anzeigen
			    case 'visible':   // (Druck und Internet)
			    case 'restrict':   // Öffentliche Anzeige personenbezogener Daten
				$value = $this->sanitize_univis_boolean($value);
				break;
				
			    case 'orgunit':
			    case 'orgunit_en':
				$value = $this->sanitize_univis_orgunits($value);
				break;
				
			    case 'location':
				$value = $this->sanitize_univis_location($value);
				break;
			    
			     case 'officehour':
				$value = $this->sanitize_univis_officehours($value);
				break;
			      case 'gender':
				$value = $this->sanitize_univis_gender($value);
				break;
			    
			    case 'orgname':
			    case 'work':	
			    case 'title':
			    case 'atitle':
			    case 'lastname':
			    case 'firstname':
			    
				 $value = sanitize_text_field($value);
				break;
			    
			    case 'id':
			    case 'idm_id':
			    case 'key':	
				$value = $this->sanitize_univis_key($value);
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
     
     private function sanitize_univis_gender($gender) {
	 if (!empty($gender)) {
	     if ($gender === 'f') {
		 return 'female';
	     } elseif ($gender === 'm') {
		 return 'male';
	     }
	 }
     }
     
     // sanitize univis officehours
    private function sanitize_univis_officehours($value) {
	 if (is_array($value)) {
	     $res = array();
	     foreach ($value as $name => $entry) {
		 if (is_array($entry)) {
		     // Subarray, es gibt mehr als eine location
		     $res[$name] = $this->sanitize_univis_officehours($entry);
		 } else {
		     
		    $repeat = isset($value['repeat'] ) ? $value['repeat'] : 0;
		    $repeat_submode = isset( $value['repeat_submode'] ) ? sanitize_text_field($value['repeat_submode']) : 0;
		    $starttime = isset( $value['starttime'] ) ? sanitize_text_field($value['starttime']) : 0;
		    $endtime = isset( $value['endtime'] ) ? sanitize_text_field($value['endtime']) : 0;
		    $office = isset( $value['office'] ) ? sanitize_text_field($value['office']) : '';
		    $comment = isset($value['comment'] ) ? sanitize_text_field($value['comment']) : '';
                    
		    $res['repeat'] = $repeat;
		    $res['starttime'] = $starttime;
		    $res['endtime'] = $endtime;
		    $res['office'] = $office;
		    $res['comment'] = $comment;
		    $res['text'] =  $this->univis_officehours_repeat($repeat, $repeat_submode, $starttime, $endtime, $office, $comment);                    		   
		    
		    // we need the original entries to add schema.org Schedule Notation later
		     
		 }
		 
	     }
	     return $res;
	 } else {
	     $value = sanitize_text_field($value);
	     return $value;
	 }
     }

     
      //public static function officehours_repeat( $officehours ) {
    private function univis_officehours_repeat( $repeat, $repeat_submode, $starttime, $endtime, $office, $comment ) {
        $date = array();
	$days_short = array(
                    1 => __('Monday', 'rrze-jobs'),
                    2 => __('Tuesday', 'rrze-jobs'),
                    3 => __('Wednesday', 'rrze-jobs'),
                    4 => __('Thursday', 'rrze-jobs'),
                    5 => __('Friday', 'rrze-jobs'),
                    6 => __('Saturday', 'rrze-jobs'),
                    7 => __('Sunday', 'rrze-jobs')
                );


        if (($repeat ) && ($repeat !== '-') ) {
            $repeat_submodes = explode( ' ', $repeat );
	    if ((isset($repeat_submodes[0])) && (strpos($repeat_submodes[0],'d')!==false) && (strpos($repeat_submodes[0],'d')==0)) {
		// taegliche wiederholung
		$daily_repeat = substr($repeat_submodes[0],1);
		if ($daily_repeat == '1') {
		    $res =  __('Daily', 'rrze-jobs');
		} else {
		    $res = __('Each','rrze-jobs').' '.$daily_repeat.'. '.__('day','rrze-jobs');
		}
		
	    } elseif ((isset($repeat_submodes[0])) && (strpos($repeat_submodes[0],'w')!==false) && (strpos($repeat_submodes[0],'w')==0)) {
		// woechentliche Wiederholung    
		$weekly_repeat = substr($repeat_submodes[0],1);
		
		if ($weekly_repeat == '1') {
		    $res =  __('Each', 'rrze-jobs').' '.__('week','rrze-jobs');
		} else {
		    $res = __('Each','rrze-jobs').' '.$weekly_repeat.'. '.__('week','rrze-jobs');
		}
		if (isset($repeat_submodes[1])) {
		   if (strpos($repeat_submodes[1],',') !== false) {
		       // mehr als ein Tag in der Woche
		       $daylist = explode(',',$repeat_submodes[1]);
		       foreach($daylist as $thisday) {
			    $daynum = intval($thisday);
			    $res .= ", ".$days_short[$daynum];
		       }

		   } else {
		       $daynum = intval($repeat_submodes[1]);
		       $res .= ", ".$days_short[$daynum];
		   }
		}
		
		
	    } elseif ((isset($repeat_submodes[0])) && (strpos($repeat_submodes[0],'m')!==false) && (strpos($repeat_submodes[0],'m')==0)) {
		// monatliche Wiederholung
		$monthly_repeat = substr($repeat_submodes[0],1);
		
		if ($monthly_repeat == '1') {
		    $res =  __('Each','rrze-jobs').' '.__('month','rrze-jobs');
		} else {
		    $res =   __('Each','rrze-jobs').' '.$monthly_repeat.'. '.__('month','rrze-jobs');
		}
		if ((isset($repeat_submodes[1])) && (strpos($repeat_submodes[1],'w')!==false) && (strpos($repeat_submodes[1],'w')==0)) {
		  // woechentliche Wiederholung    
		    $weekly_repeat = substr($repeat_submodes[1],1);
		    if ($weekly_repeat == '1') {
			$res .=  ', '. __('First','rrze-jobs').' '.__('week','rrze-jobs');
		    } else {
			$res .=  ', '.  $weekly_repeat.'. '.__('week','rrze-jobs');
		    }

	
		    if (isset($repeat_submodes[2])) {
		       if (strpos($repeat_submodes[2],',') !== false) {
			   // mehr als ein Tag in der Woche
			   $daylist = explode($repeat_submodes[2],',');
			   foreach($daylist as $thisday) {
				$daynum = intval($thisday);
				$res .= ", ".$days_short[$daynum];
			   }
		       } else {
			   $daynum = intval($repeat_submodes[2]);
			   $res .= ", ".$days_short[$daynum];
		       }
		    }
		} elseif ((isset($repeat_submodes[1])) && (strpos($repeat_submodes[1],'d')!==false) && (strpos($repeat_submodes[1],'d')==0)) {
		      $daynum = substr($repeat_submodes[1],1);
		     $res .= ", ". __('each','rrze-jobs').' '.$daynum.'. '.__('day','rrze-jobs');
		}
		
		
	    }	
	    array_push($date, $res);

           
        }
        if ( $starttime ) {
	    $time = $starttime;
	    $charset = get_bloginfo('language');
	    if (strpos($charset,'de') !== false) {
		$time = $this->convert_time_24hours( $starttime );
		if ( $endtime ) {
		    $time .= ' - ' . $this->convert_time_24hours( $endtime );
		}
		
		$time .= ' Uhr';
		
	    } else {
		if ( $endtime ) {
		    $time .= ' - ' .$endtime;
		}
	    }
	    
            array_push($date, $time);
        }

        if ( $office ) {
            array_push($date, $office);            
        }
        
        if( $comment !== 0 ) {
            array_push($date, $comment);
        }

        $officehours = implode( ', ', $date );
        
        return $officehours;
    }
    
    

   
     
    // sanitize univis location
    private function sanitize_univis_location($value) {
	 if (is_array($value)) {
	     $res = array();
	     foreach ($value as $name => $entry) {
		 if (is_array($entry)) {
		     // Subarray, es gibt mehr als eine location
		     $res[$name] = $this->sanitize_univis_location($entry);
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
				$value = $this->sanitize_telefon($entry);
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

     
     
     
    // sanitize univis boolean thingis
    private function sanitize_univis_boolean($value) {
	if ((!isset($value)) || (empty($value))) {
	     return false;
	}
	$val = strtolower($value);
	if (strpos($val, 'ja') !== false 
		|| strpos($val, 'yes') !== false 
		|| strpos($val, '1') !== false ) {
		return true;

	}
	return false;
     }
     
     // sanitize orgunits
     private function sanitize_univis_orgunits($value) {
	 if (is_array($value)) {
	     $res = array();
	     foreach ($value as $entry) {
		 $res[] = sanitize_text_field($entry);
	     }
	     return $res;
	 } else {
	     $value = sanitize_text_field($value);
	     return $value;
	 }
     }
     // check for select fields from univis
     private function sanitize_univis_typen($value, $type = 'type1') {
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

    
     // check for valid univis keys
     private function sanitize_univis_key($key) {
	 $key = preg_replace('/[^A-Za-z0-9\/\._]+$/i', '', $key);
	 return $key;
     }
     
     
      
        
     
      // check and translate creative input in befristet date input
     private function sanitize_univis_befristet($start) {
	 $res = '';
	 // Set 'job_start_sort'
        if (!empty($start)) {
            // field might contain a date - if it contains a date, it must be in english format
            if (preg_match("/\d{4}-\d{2}-\d{2}/", $start, $parts)) {
                $res = $parts[0];
		$res = date('d.m.Y', strtotime($res));
            } elseif (preg_match("/(\d{2}).(\d{2}).(\d{4})/", $start, $parts)) {
		$res = $parts[3] . '-' . $parts[2] . '-' . $parts[1];
		$res = date('d.m.Y', strtotime($res));
	    } elseif (preg_match("/(\d{2}).(\d{2}).(\d{2})\s*$/", $start, $parts)) {
		$res = '20'.$parts[3] . '-' . $parts[2] . '-' . $parts[1];
		$res = date('d.m.Y', strtotime($res));
	    	
            } else {
		$res = sanitize_text_field($start);
	    }
	}    

	    
       
	return $res;

     }
     
     // check and translate creative input in job start date field
     private function sanitize_univis_jobstart($start) {
	 $res = '0';
	 // Set 'job_start_sort'
        if (!empty($start)) {
            // field might contain a date - if it contains a date, it must be in english format
            if (preg_match("/\d{4}-\d{2}-\d{2}/", $start, $parts)) {
                $res = $parts[0];
            } elseif (preg_match("/(\d{2}).(\d{2}).(\d{4})/", $start, $parts)) {
                $res = $parts[3] . '-' . $parts[2] . '-' . $parts[1];
	    } elseif (preg_match("/(\d{2}).(\d{2}).(\d{2})\s*$/", $start, $parts)) {
		$res = '20'.$parts[3] . '-' . $parts[2] . '-' . $parts[1];
	    	
            } else {
		// field contains only a string - check if it is ASAP
		$val = strtolower($start);
		if (strpos($val, 'sofort') !== false 
			|| strpos($val, 'bald') !== false 
			|| strpos($val, 'glich') !== false 
			|| strpos($val, 'asap') !== false 
			|| strpos($val, 'a.s.a.p.') !== false) {
			// sofort, ab sofort, baldmöglich, baldmöglichst, zum nächstmöglichen Zeitpunkt, nächstmöglich, frühst möglich, frühestmöglich, asap, a.s.a.p.
			$res = '0';
		} else {
		    $res = $start;
		}
	    }
	}    
	if ($res == '0'){
	    $res = "-1";   
	} else{
	       // Convert date 'job_start'
	    $res = date('d.m.Y', strtotime($res));
	}
	    
       
	return $res;

     }
     
     
     
    
          
     
     // Translate UnivIS Text Input in HTML
     private function sanitize_univis_textfeld($txt) {
        $subs = array(
            '/^\-+\s+(.*)?/mi' => '<ul><li>$1</li></ul>', // list
	    '/[\n\r\s]+<\/li>/mi' => '</li>', // remove breaks at end of listelements
            '/(<\/ul>\n(.*)<ul>*)+/' => '', // list
            '/\*{2}/m' => '/\*/', // **
            '/_{2}/m' => '/_/', // __
            '/\|(.*)\|/m' => '<i>$1</i>', // |itallic|
            '/_(.*)_/m' => '<sub>$1</sub>', // H_2_O
            '/\^(.*)\^/m' => '<sup>$1</sup>', // pi^2^
            '/\[([^\]]*)\]\s{0,1}((http|https|ftp|ftps):\/\/\S*)/mi' => '<a href="$2">$1</a>', // [link text] http...
            '/\[([^\]]*)\]\s{0,1}(mailto:)([^")\s<>]+)/mi' => '<a href="mailto:$3">$1</a>', // find [link text] mailto:email@address.tld but not <a href="mailto:email@address.tld">mailto:email@address.tld</a>
            '/\*([^\*]*)\*/m' => '<strong>$1</strong>', // *bold*
        );

        $txt = preg_replace(array_keys($subs), array_values($subs), $txt);
        $txt = make_clickable($txt);
	
	$txt = $this->sanitize_html_field($txt);
	
	
	$txt = wpautop($txt, false);
	$txt = trim($txt);

        return $txt;
    }
    
    
}

  
