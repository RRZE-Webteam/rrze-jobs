<?php
/**
 * UnivIS 
 * 
 * Created on : 08.08.2022, 16:45:20
 */

namespace RRZE\Jobs\Provider;

defined('ABSPATH') || exit;
use RRZE\Jobs\Helper;
use RRZE\Jobs\Provider;
use RRZE\Jobs\Cache;

class UnivIS extends Provider { 

    public function __construct() {
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
		 'department'	=> 'string'
	     ),
	     'get_single'	=> array(
		 'id'	=> 'number'
	     )
	 );
	 
	 
	 // map to transform univis fieldnames and content to schema.org Notation
	 $this->map_to_schema = array(
	     
	     'JobPosting' => array(
		    'datePosted'    => 'creation',
		    'directApply'   => true,
			// im Text ist immer Mailadresse oder URL angegeben.
			// dies sollte noch automatisiert werden und wir müssen 
			// eine URL binden zur Applikation. 
			// die eigentliche URL müpsste in
			// applicationContact reindefiniert werden.
			'applicationContact',
			// muss aus textuellen inhalten oder oacontact generiert werdem
			//  		 https://schema.org/ContactPoint
		    'educationRequirements' => 'desc3',
		 // to be filled.
	     )
	     // other scheme maps may be added for other use cases
	     
	 );
	 
     } 
     
     public $methods = array(
	    "get_list", "get_single", "map_to_schema", "get_uri", "required_parameter"
	 );
     
     
     public function get_list($params) {	 
	 $check = $this->required_parameter("get_list",$params);
	 
	 if (is_array($check)) {    
	      $aRet = [
                    'valid' => false,
		    'error' => 'required_parameter',
		    'params_given'   => $params,
                    'content' => $check
              ];
	      return $aRet;
	 }
	 $response = $this->get_data("get_list", $params);
	 
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
		    'error' => 'required_parameter',
                    'content' => $check
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
	if (isset($params[$method]['department'])) {
	    $org = $params[$method]['department'];
	} 
	$id = '';
	if (isset($params[$method]['id'])) {
	    $id = $params[$method]['id'];
	} 
	$cachedout = $cache->get_cached_job('UnivIS',$org,$id,$method);
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
	     
	     $cache->set_cached_job('UnivIS',$org,$id,$method, $aRet);
	     
	     return $aRet;
	  }
	 
	
     }
     
     // map univis field names and entries to schema standard
     public function map_to_schema($data) {
	 return $data;
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
				$value = $this->sanitize_univis_group($value);
				break;
			    
			    case 'orgname':
			    case 'title':
				 $value = sanitize_text_field($value);
				break;
			    case 'lang':
				 $value = $this->sanitize_univis_lang($value);
				break;
			    case 'start':
				 $value = $this->sanitize_univis_jobstart($value);	
				break;
			    case 'enddate':
			    case 'creation':					
				 $value = $this->sanitize_univis_dates($value);	
				break;
			   case 'befristet':					
				 $value = $this->sanitize_univis_befristet($value);	
				break; 
			    case 'id':
				$value = $this->sanitize_type('number',$value);	
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
				$value = $this->sanitize_univis_typen('type1',$value);	
				break;
			    case 'type2':
				$value = $this->sanitize_univis_typen('type2',$value);	
				break;
			    case 'type3':
				$value = $this->sanitize_univis_typen('type3',$value);	
				break;
			    case 'type4':
				$value = $this->sanitize_univis_typen('type4',$value);	
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
				$value = $this->sanitize_univis_telefon($entry);
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
     private function sanitize_univis_telefon($phone_number ) {
	 
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

    
     // check for valid univis keys
     private function sanitize_univis_key($key) {
	 $key = preg_replace('/[^A-Za-z0-9\/\._]+$/i', '', $key);
	 return $key;
     }
     
     
      
     // check for given date fields, that may be corrupt...   
     private function sanitize_univis_dates($dateinput) {
	$res = '';
	if (!empty($dateinput)) {
	    if (preg_match("/\d{4}-\d{2}-\d{2}/", $dateinput, $parts)) {
                $dateinput = $parts[0];
            } elseif (preg_match("/(\d{2}).(\d{2}).(\d{4})/", $dateinput, $parts)) {
                $dateinput = $parts[3] . '-' . $parts[2] . '-' . $parts[1];
            } elseif (preg_match("/(\d{2}).(\d{2}).(\d{2})\s*$/", $dateinput, $parts)) {
		$dateinput = '20'.$parts[3] . '-' . $parts[2] . '-' . $parts[1];
	    }

	    $res = date('d.m.Y', strtotime($dateinput));
	}
	return $res;
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
	    $res = __('As soon as possible', 'rrze_jobs');   
	} else{
	       // Convert date 'job_start'
	    $res = date('d.m.Y', strtotime($res));
	}
	    
       
	return $res;

     }
     
     
     
     // check for the position language tag
     private function sanitize_univis_lang($lang) {
	 $res = 'de';
	 if (empty($lang)) {
	     return $res;
	 }
	 switch($lang) {
	     case 'de':
	     case 'en':
	     case 'es':
	     case 'fr':
	     case 'zh':
	     case 'ru':
		 $res = $lang;
		 break;
	     default:
		 $res = 'de';
	 }
	 
	 return $res;
     }
     
     
     // check for select value of group and translate into the desired long form
     private function sanitize_univis_group($group) {
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
     


   
     
     
     // Translate UnivIS Text Input in HTML
     private function sanitize_univis_textfeld($txt) {
        $subs = array(
            '/^\-+\s+(.*)?/mi' => '<ul><li>$1</li></ul>', // list
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
        $txt = nl2br($txt);
        $txt = make_clickable($txt);

        return $txt;
    }
    
    
}

  
