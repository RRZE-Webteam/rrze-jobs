<?php
/**
 * BITE 
 * 
 * Created on : 18.10.2022
 */

namespace RRZE\Jobs\Provider;

defined('ABSPATH') || exit;
use RRZE\Jobs\Provider;
use RRZE\Jobs\Cache;
use RRZE\Jobs\Helper;

class BITE extends Provider { 

    public function __construct() {
	 $this->api_url	    = 'https://api.b-ite.io/v1/jobpostings';
	 //   list: https://api.b-ite.io/v1/jobpostings
	 // single: https://api.b-ite.io/v1/jobpostings/
	 $this->url	    = 'https://www.b-ite.com/';
	 $this->name	    = "BITE";
	 $this->cachetime   =  2 * HOUR_IN_SECONDS;
	 $this->cachetime_list   = $this->cachetime;
	 $this->cachetime_single   =  4 * HOUR_IN_SECONDS;
	 $this->uriparameter = '';
	 $this->request_args = array(
		'timeout'     => 45,
		'redirection' => 5,
		'headers' => [
                    'Content-Type' => 'application/json',
                    'BAPI-Token' => '',  // API-KEY
                ],
		 'sslverify' => true
	     
	    );

	 // defines all required parameters for defined request method
	 $this->required_fields = array(
	     'get_list'	=> array( ),
	     'get_single'	=> array( 'id'	=> 'string' )
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
	
	if (isset($data['entries'])) {	    
	    // in a list request we get all jobs in the array Stellenangebote
	     foreach ($data['entries'] as $num => $job) {
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
	$known_fields = array("assignees", "hash", "emailTemplate", "jobSite", 
	    "subsidiary", "content", "seo");
	    // keynames we already used in schema values or which we dont need anyway
	
	
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

	 
	
	
	  // description
	  $desc = '';
	  if ((isset($jobdata['custom']['einleitung'])) && (!empty($jobdata['custom']['einleitung']))) {
	      $desc .= $jobdata['custom']['einleitung'];
	  }
	  if ((isset($jobdata['custom']['aufgaben'])) && (!empty($jobdata['custom']['aufgaben']))) {
	      $desc .= $jobdata['custom']['aufgaben'];
	  }
	  if ((isset($jobdata['custom']['stellenzusatz'])) && (!empty($jobdata['custom']['stellenzusatz']))) {
	      $desc .= $jobdata['custom']['stellenzusatz'];
	  }
	  
	  $res['description'] = $desc;
	  
	  
	   if ((isset($jobdata['custom']['profil'])) && (!empty($jobdata['custom']['profil']))) {	        
		$res['qualifications'] = '<p><strong>{{=const.title_qualifications_required}}:</strong></p>';
		$res['qualifications'] .= $jobdata['custom']['profil'];	     
	   }
	    if ((isset($jobdata['custom']['job_experience'])) && (!empty($jobdata['custom']['job_experience']))) {	        
		$res['qualifications'] .= '<p><strong>{{=const.title_qualifications_experience}}:</strong></p>';
		$res['qualifications'] .= $jobdata['custom']['job_experience'];	     
	   }
	   
	   if ((isset($jobdata['custom']['job_qualifications_nth'])) && (!empty($jobdata['custom']['job_qualifications_nth']))) {	        
		$res['qualifications'] .= '<p><strong>{{=const.title_qualifications_optional}}:</strong></p>';
		$res['qualifications'] .= $jobdata['custom']['job_qualifications_nth'];	     
	   }
	  

	   
	$res['disambiguatingDescription'] = '';
	if ((isset($jobdata['custom']['wir_bieten'])) && (!empty($jobdata['custom']['wir_bieten']))) {	        
	      $res['disambiguatingDescription'] = $jobdata['custom']['wir_bieten'];
	}
	  

	  
	  // title
	  if ((isset($jobdata['title'])) && (!empty($jobdata['title']))) {
	       $res['title'] = $jobdata['title'];
	  }
	 
	  
	   // identifier
	  $res['identifier'] = $jobdata['identification'];
	 
	 
	 // employmentType
	  $typeliste = array();
	  if ((isset($jobdata['seo'])) && (isset($jobdata['seo']['employmentType']))) {
	  //    $typeliste = $jobdata['seo']['employmentType'];
	      foreach ($jobdata['seo']['employmentType'] as $val) {
		  if (!empty($val)) {
		      $typeliste[] = strtoupper($val);
		  }
	      }
	  }
	 
	 $res['employmentType'] = $typeliste;

	    //  muss aber einen oder mehrere der folgenden
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
	    
	 
	  // Gruppe / Kategorie der Stelle  
	if ((isset($jobdata['custom']['zuordnung'])) && (!empty($jobdata['custom']['zuordnung']))) {
	    $res['occupationalCategory'] = $jobdata['custom']['zuordnung'];
	}
	
	
	
	
	  if (isset($jobdata['active']) && ($jobdata['active']==true)  && (isset($jobdata['channels'])) && (isset($jobdata['channels']['channel0']))) {
	       // datePosted
	       $res['datePosted'] = $jobdata['channels']['channel0']['from'];
	         // validThrough
	       $res['validThrough'] = $jobdata['channels']['channel0']['to'];
	       
	       if (isset($jobdata['channels']['channel0']['application'])) {
		   $res['applicationContact']['url'] = $jobdata['channels']['channel0']['application'];
	       }
	         if (isset($jobdata['channels']['channel0']['email'])) {
		   $res['applicationContact']['email'] = $jobdata['channels']['channel0']['email'];
		    if (isset($jobdata['custom']['ausschreibungskennziffer'])) {
			$res['applicationContact']['email_subject'] = $jobdata['custom']['ausschreibungskennziffer'];
		    }
		   
		   
	       }
	       $res['directApply'] = true;
	  }
	
	
	
	    
	    if (isset($jobdata['custom']['place_of_employment_street'])) {
		$res['jobLocation']['address']['streetAddress'] = $jobdata['custom']['place_of_employment_street'];
		 if (isset($jobdata['custom']['place_of_employment_house_number'])) {
		    $res['jobLocation']['address']['streetAddress'] .= ' '.$jobdata['custom']['place_of_employment_house_number'];
		 }
	    }
	    if (isset($jobdata['custom']['place_of_employment_postcode'])) {
		 $res['jobLocation']['address']['postalCode'] = $jobdata['custom']['place_of_employment_postcode'];
	    }
	   
	    if (isset($jobdata['custom']['place_of_employment_city'])) {
		 $res['jobLocation']['address']['addressLocality'] = $jobdata['custom']['place_of_employment_city'];
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
	    
	

	 // hiringOrganization   (type: Organzsation)
	    // aus orgunit und oder orgname generieren	    
	    // Der Inhalt (Ansprechperson) contact geht hier in contactpoint mit ein
	    if (isset($jobdata['custom']['hiringorganization'])) {
		if (isset($jobdata['custom']['hiringorganization']['title'])) {
		    $res['employmentUnit']['name'] = $jobdata['custom']['hiringorganization']['title'];
		    $res['hiringOrganization']['name'] = $jobdata['custom']['hiringorganization']['title'];
		}
		if (isset($jobdata['custom']['hiringorganization']['url'])) {
		    $res['employmentUnit']['url'] = $jobdata['custom']['hiringorganization']['url'];
		    $res['hiringOrganization']['url'] = $jobdata['custom']['hiringorganization']['url'];
		}
	    }
	    if (isset($jobdata['custom']['contact_email'])) {
		$res['employmentUnit']['email'] = $jobdata['custom']['contact_email'];
	    } 
	    if (isset($jobdata['custom']['contact_tel'])) {
		$res['employmentUnit']['telephone'] = $jobdata['custom']['contact_tel'];
	    } 

	    
	    
	    
	    $res['estimatedSalary'] = '';
	
	  if ((empty($res['estimatedSalary'])) && (isset($jobdata['custom']['festbetrag'])) && (!empty($jobdata['custom']['festbetrag']))) {
	       $res['estimatedSalary'] = $this->get_Salary_by_TVL($jobdata['custom']['festbetrag']);
	  }
	

	

	
	 // workHours
	  // wenn type4 gesetzt (Vormittags / nachmittags)
	    // ausserdem, wenn gesetzt: 
	    // 'nd':  Nachtdienst
	    // 'sd':  Schichtdienst
	    // 'bd':  Bereitsschaftsdienst
	    // + wstunden
	$res['workHours'] = '';	 
	
	
	if ((isset($jobdata['custom']['job_workhours'])) && (!empty($jobdata['custom']['job_workhours'])))  {
	     $res['workHours'] = $jobdata['custom']['job_workhours'].' '.__('hours per week','rrze-jobs');
	}
	if ((isset($jobdata['custom']['workhours'])) && (!empty($jobdata['custom']['workhours'])))  {
	    if (!empty($res['workHours'])) {
		$res['workHours'] .= ', ';
	    }
	    $res['workHours'] .= $jobdata['custom']['workhours'];
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
	     
	     if ((isset($response['content']['total']) && ((intval($response['content']['total']) > 0)))) {
		 
		 
		 if (isset($response['content']['entries'])) {
		    foreach ($response['content']['entries'] as $num => $pos) {
			$singleparams['get_single']['id'] = $pos['id']; 
			
			$singledata = $this->get_single($singleparams, false);
			
			if ($singledata['valid'] == true) {
			    $response['content']['entries'][$num] = $singledata['content'];
			}
			break;
		    }
		 }
		  
	     

	     
	   } elseif ((isset($response['content']['total']) && ((intval($response['content']['total']) == 0)))) {
		   $aRet = [
                    'valid' => false,
                    'error' => 'No entry',
		    'code'   => 404,
		    'params_given'   => $params,
		    'content'	=> ''
                ];
		return $aRet;
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
	      if ((is_array($response['content'])) && (isset($response['content']['code'])) && ($response['content']['code'] >= 400)) {
		    $aRet = [
                    'valid' => false,
		    'code'  => $response['content']['code'],
		    'error' => $response['content']['messsage'],
		    'params_given'   => $params,
                    'content' => ''
		];
		return $aRet;
	      } elseif ((is_array($response['content'])) && (!empty($response['content']))) {
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
		     $uri .= '/';
		 }
		 if ($uriname == 'id') {
		      $uri .= $urivalue;
		 } else {
		     $uri .= $uriname.'/'.$urivalue;
		 }
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
	 
	 if ((!isset($params['request-header']['headers']['BAPI-Token'])) || (empty($params['request-header']['headers']['BAPI-Token']))) {
	     return $this->request_args; 
	 }
	 
	 
	return true;
    }
    
    // update and returns the request args
    private function get_request_args($params) {
	if ((isset($params)) && (isset($params['request-header']))) {
	    // $params['request-header']['headers']['BAPI-Token']
	    foreach ($params['request-header'] as $name => $value) {
		if (is_array($value)) {
		    foreach ($value as $subname => $subval) {
			$this->request_args[$name][$subname] = $subval;
		    }
		} else {
		    
		    $this->request_args[$name] = $value;
		}
		
	    }
	}
	return $this->request_args;
    }
    
    
   
    // get the raw data from provider by a a method and parameters
    // https://api.b-ite.io/docs/#/jobpostings/get_jobpostings
    public function get_data($method = 'get_list', $params) { 
	$uri = $this->get_uri($method,$params);
	$url = $this->api_url;
	if ($uri) {
	    $url .= '/'.$uri;
	}
	
	$cache = new Cache();
	
	$cachetime = $this->cachetime;
	if ($method == 'get_list') {
	    $cachetime = $this->cachetime_list;
	} elseif ($method == 'get_single') {
	    $cachetime = $this->cachetime_single;
	}
	
	$cache->set_cachetime($cachetime);

	$id = '';
	if ((isset($params[$method]['id'])) && (!isset($params[$method]['apikey']))) {
	    $id = $params[$method]['id'];
	}  
	if (empty($id)) {
		if (isset($params['request-header']['headers']['BAPI-Token'])) {
	//	    $id = $params['request-header']['headers']['BAPI-Token'];
		}	    
	}

	
	$request_args = $this->get_request_args($params);
	
//	$cachedout = $cache->get_cached_job('BITE',$id,'',$method);
//	if ($cachedout) {
//	    return $cachedout;
//	}

    
	if ($method == 'get_list') {
		$filter = '{
		    "filter": {
			"standard": {  "active": true }
		    }
		  }';

		  $post_args = $request_args;
		  $post_args['body'] = $filter;
		  $url .= '/search';

		 $remote_get    =  wp_safe_remote_post($url, $post_args);
	} else {
		$remote_get    = wp_safe_remote_get( $url , $request_args);
	}	


	if ( is_wp_error( $remote_get ) ) {	 
		 $aRet = [
                    'valid' => false,
                    'content' => $remote_get->get_error_message()
                ];
		return $aRet;
         } else {
	     $content = json_decode($remote_get["body"], true);	     
	      if (isset($content['code']) && ($content['code'] >= 400)) {
		  $aRet = [
                    'valid' => false,
                    'error' => $content['message'],
		    'code'   => $content['code'],
		    'params_given'   => $params,
		    'content'	=> ''
                ];
		return $aRet;
	     }
	     
	     $aRet = [
		    'request'	=> $url,
                    'valid'	=> true,
                    'content'	=> $content
              ];
	          
	//     $cache->set_cached_job('BITE',$id,'',$method, $aRet);
	     
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
	 
	 if (isset($data['entries'])) {
	     // wird bei der Indexabfrage geliefert
	     foreach ($data['entries'] as $num => $job) {
		 if (is_array($job)) {
		    foreach ($job as $name => $value) {
			switch($name) {

			    case 'id':
				$value = $this->sanitize_bite_id($value);
				break;
			  

			}
			
			$data['entries'][$num][$name] = $value;
		    }

		    
		 }
	     }
	 } else {
		// Bei der Direkten Abfrage einer Stelle wird alles auf oberester Ebene geliefert
	       foreach ($data as $key => $value) {
		   if (!is_array($value)) {
			switch($key) {
			    case 'job_intern':
			    case 'befristung':
			    case'active':
				$value = $this->sanitize_bool($value);
				break;
				
			    case 'id':
				$value = $this->sanitize_bite_id($value);
				break;
			
			    case 'anr':	
				$value = $this->sanitize_type('number',$value);		
				break;
			    
			    case 'aufgaben':
			    case 'einleitung':
			    case 'stellenzusatz':
			    case 'profil':
			    case 'job_qualifications_nth':
			    case 'job_experience':
			    case 'wir_bieten':
			    case 'description':
			    case 'abschlusstext':
				$value = $this->sanitize_markdown_field($value);
				break;
			     case 'hiringorganization':
				$value = $this->sanitize_custom_org($value);
				break;
			
			    case 'workhours':
				$value = $this->sanitize_bite_arbeitszeit($value);
				break;
			    
			    case 'job_limitation_reason':
				$value = $this->sanitize_bite_limitation_reason($value);
				break;
			    
			    case 'zuordnung':
				$value = $this->sanitize_bite_group($value);
				break; 
			    case 'job_limitation_duration':
				$value = $this->sanitize_type('number',$value);		
				break; 
			
			    case 'jobstartdate':
				 $value = $this->sanitize_dates($value);	
				break;
			    
			    case 'employmenttype':
				 $value = $this->sanitize_bite_employmenttype($value);	
				break;
			    case 'beschaeftigungsumfang':
				 $value = $this->sanitize_bite_beschaeftigungsumfang($value);	
				break;	
			    
			    case 'identification':
			    case 'ausschreibungskennziffer':
				 $value = $this->sanitize_bite_kennziffer($value);	
				break;
				
			    case 'locale':
			    case 'jobposting_language':
				$value = $this->sanitize_lang($value);
				break;    
			   

			     default:
				     $value = sanitize_text_field($value);
			}
		   } else {
		       
		       switch($key) {
			   case 'location':
			   case 'custom': 
				$value = $this->sanitize_sourcedata($value);
				break;
			   case 'content':
			   case 'channels':
				break;
			    default:
				$value = $this->sanitize_sourcedata($value);    
			    
		       }
		   }
		   $data[$key] = $value;
	       }
	 }
	 
	 return $data;
	 
     } 
     
     
     
     // sanitize der Kennziffer
     private function sanitize_bite_kennziffer($key) {
	 $key = preg_replace('/[^A-Za-z0-9\-]+$/i', '', $key);
	 return $key;
     }
     
     
        // check for valid bite ids
     private function sanitize_bite_id($key) {
	 $key = preg_replace('/[^A-Za-z0-9\-]+$/i', '', $key);
	 return $key;
     }
     
     // employment type aus custom.employmenttype , nicht aus seo
     private function sanitize_bite_employmenttype($value) {
	 $options = array(
	     	 'unlimited'    => __('unlimited','rrze-jobs'),
		 'temporary'    => __('temporary', 'rrze-jobs')
	 );
	 if (!empty($value)) {
	     $value = trim(strtolower($value));
	     
	     
	     if (isset($options[$value])) {
		 return $options[$value];
	     } else {
		 // es kann sein, dass hier leider nicht die Keys übergeben wurden, sondern der lange String.
		  $value = sanitize_text_field($value);
		  return $value;
	     }
	 }
	 return '';
	 
     }
     
      // employment type aus custom.beschaeftigungsumfang , nicht aus seo
     private function sanitize_bite_beschaeftigungsumfang($value) {
	 $options = array(
		'01_vollzeit'    =>  __('Full time', 'rrze-jobs'),
		 'teil'    =>  __('Part time', 'rrze-jobs')
	 );
	 if (!empty($value)) {
	     $value = trim(strtolower($value));
	     
	     
	     if (isset($options[$value])) {
		 return $options[$value];
	     } else {
		 // es kann sein, dass hier leider nicht die Keys übergeben wurden, sondern der lange String.
		  $value = sanitize_text_field($value);
		  return $value;
	     }
	 }
	 return '';
	 
     }
     
     
        // ubersetze die Auswahlliste für die optionale Begründung befrister Stellen
     private function sanitize_bite_limitation_reason($value) {
	  $options = array(
		 'vertr' => __('Replacement', 'rrze-jobs'), // 'Vertretung',
		 'schwanger' => __('Maternity leave replacement', 'rrze-jobs'), // 'Mutterschutzvertretung', 
		 'eltern' => __('Maternity/parental leave replacement', 'rrze-jobs'), // 'Mutterschutz- / Elternzeitvertretung',
		 'forsch' => __('Limited research project', 'rrze-jobs'), // 'befristetes Forschungsvorhaben',
	         'projekt' => __('Project', 'rrze-jobs'), // Projekt
		 'krankh' => __('Sickness replacement', 'rrze-jobs'), // 'befristetes Krankheitsvertretung',
		 'zeitb'  => __('Temporary officials', 'rrze-jobs'), // 'Beamtenschaft auf Zeit'
	      
	 );
	  
	   
	 
	  
	 if (!empty($value)) {
	     $value = trim(strtolower($value));
	     
	     
	     if (isset($options[$value])) {
		 return $options[$value];
	     } else {
		 // es kann sein, dass hier leider nicht die Keys übergeben wurden, sondern der lange String.
		  $value = sanitize_text_field($value);
		  return $value;
	     }
	 }
	 return '';
     }
     
     
     // ubersetze die Auswahlliste für die ARbeitszeiten
     private function sanitize_bite_arbeitszeit($value) {
	 $options = array(
	     	 'nach_vereinbarung'    =>  __('By arrangement', 'rrze-jobs'),
		 'vormittags'    =>  __('Morning times', 'rrze-jobs'),
		 'nachmittags'    =>  __('Afternoon times', 'rrze-jobs')
	 );
	 if (!empty($value)) {
	     $value = trim(strtolower($value));
	     if (isset($options[$value])) {
		 return $options[$value];
	     }
	 }
	 return '';
	 	
     }
     
     
     // check for select value of group and translate into the desired long form
     private function sanitize_bite_group($group) {
	 $res = '';
	 if (empty($group)) {
	     return $res;
	 }
	 switch($group) {
	     case 'wiss':
		 $res = __('Research & Teaching', 'rrze-jobs');
		 break;
	     case 'verw':
	     case 'tech':
	     case 'pflege':
	     case 'arb':
	     case 'n-wiss':
		 $res = __('Technology & Administration', 'rrze-jobs');
		 break;
	     case 'azubi':
		 $res = __('Trainee', 'rrze-jobs');
		 break;
	     
	     case 'hiwi':
		 $res = __('Student assistants', 'rrze-jobs');
		 break;
	     
	     case 'aush':
	     case 'other':
		 $res = __('Other', 'rrze-jobs');
		  break;
	      
	     default:
		 $res = __('Other', 'rrze-jobs');
	 }
	 
	 return $res;
     }
     
     


}

  
