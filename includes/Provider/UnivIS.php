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
         $this->classname   = 'univis';
	 $this->api_url	    = 'https://univis.uni-erlangen.de/prg';
	 $this->url	    = 'https://univis.uni-erlangen.de/';
	 $this->name	    = "UnivIS";
	 $this->cachetime   =  3 * HOUR_IN_SECONDS;
	 $this->uriparameter = 'search=positions&closed=0&show=json';
	 $this->request_args = array(
		'timeout'     => 45,
		'redirection' => 5,
	    );
	 
	 $this->required_fields = array(
	     'get_list'	=> array(
		 'department'	=> 'string'
	     ),
	     'get_single'	=> array(
		 'id'	=> 'number'
	     )
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
				
			    default:
				$value = sanitize_text_field($value);
			}
			
			$data['Position'][$num][$name] = $value;
		    }
		 }
	     }
	 }
	 return $data;
	 
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
	 $key = preg_replace('/[^A-Za-z0-9\/\._]+/i', '', $key);
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

  
