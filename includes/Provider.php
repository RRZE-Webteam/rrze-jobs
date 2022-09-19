<?php

/**
 * Positions 
 * 
 * Created on : 03.08.2022, 13:58:52
 */


namespace RRZE\Jobs;

defined('ABSPATH') || exit;


class Provider {
    var $name;
    var $version;
    var $url; 

    public $systems = [
        "UnivIS"
    ];
    private $common_methods = ["get_list", "get_single"];
    
    
    
    public function __construct() {
         $this->positions = array();
	 $this->lastcheck = '';
	 $this->params = array();
     } 
     
     public function is_valid_provider($provider) {
	 if ((!isset($provider)) || (empty($provider))) {
	     return false;
	 }
	 $found = false;
	 foreach ($this->systems as $system_name) {
	     if ((strtolower(trim($provider))) == strtolower(trim($system_name))) {
		 $found = $system_name;
		 break;
	     }
	 }
	 return $found;	 
     }
     
     public function set_provider_params($provider, $params) {
	$provider = is_valid_provider($provider);
	if ($provider===false) {
	     return false;
	}
	 
	if (!empty($provider)) {
	       if (is_array($params)){
		    $this->params[$provider] = $params;
		}
	}
	
	return;
    } 
     
    public function set_params($params) {
	 if (is_array($params)){
	     $this->params = $params;
	 }
	 return;
    }
    
    
    // merge all positions from different provider, if there are more as one
    public function merge_positions() {
	if ((!isset($this->positions)) || (empty($this->positions))) {
	    return false;
	}
	
	$res = array();
	$positionlist = array();
	
	foreach ($this->positions as $providername => $provider) {
	    $res['request'][$providername]['request'] =  $provider['request'];
	    $res['status'][$providername]['valid'] = $provider['valid'];
	    if ($provider['valid'] === true) {
		$truecounter++;
		if ((is_array($provider['content'])) && (isset($provider['content']['JobPosting']))) {
		    
		    foreach ($provider['content']['JobPosting'] as $num => $posdata) {
			  $res['positions'][] = $posdata;
		    }
		}   
	    }
	}
	if ($truecounter == count($this->positions)) {
	    $res['valid'] = true;
	} else {
	    $res['valid'] = false;
	}  
	if (count($this->positions) > 1) {
	    // check for duplicate posts
	    
	    // a job is duplicate if all the folowing fields together are 
	    // identical in content:
	    $matching_fields = array('title', 'validThrough', 'employmentUnit.name', 
		'jobStartDate', 'applicationContact.url', 'applicationContact.email', 
		'baseSalary.value.value');
	    
	    // todo later:
	    // make a checksum of the content of description (after remove all
	    // html and other markup and look if this matches too
	    
	    $dup = array();
	    foreach ($res['positions'] as $num => $pos) {		
		foreach ($res['positions'] as $num2 => $pos2) {
		    if ($num == $num2)  {
			continue;
		    }
		    if ($num > $num2) {
			// we dont need to look backwards, cause we did them in 
			// a previous round already
			continue;
		    }
		    
		    $matchnum = 0;
		    foreach ($matching_fields as $field) {
			if (strpos($field,".") !== false) {
			    $fieldnames = explode(".",$field);
			    
			   
			    if (isset($fieldnames[2])) {
				if ($pos[$fieldnames[0]][$fieldnames[1]][$fieldnames[3]]  == $pos2[$fieldnames[0]][$fieldnames[1]][$fieldnames[3]]) {
				    $matchnum++;
				}
			    } elseif (isset($fieldnames[1])) {
				if ($pos[$fieldnames[0]][$fieldnames[1]]  == $pos2[$fieldnames[0]][$fieldnames[1]]) {
				    $matchnum++;
				}
			    }
			    
			    
			} elseif ($pos[$field]  == $pos2[$field]) {
			    $matchnum++;
			
			}
		    }
		    if ($matchnum == count($matching_fields)) {
			//ok, we found a duplicate entry.
			// mark it in a dup-list
			
			$dup[$num2] = true;
		    }
		}
	    }
	    if (count($dup)>0) {
		// there is one or more duplicate entries
		$newposlist = array();
		foreach ($res['positions'] as $num => $pos) {
		    if ((!isset($dup[$num])) || ($dup[$num] !== true)) {
			$newposlist[] = $pos;
		    }
		}
		$res['positions'] = $newposlist;
	    }
	}
	
	return $res;
    }
    
    
    public function get_positions($provider = '', $query = 'get_list') {
        // Ask all providers        

        foreach ($this->systems as $system_name) {
            $system_class = 'RRZE\\Jobs\\Provider\\' . $system_name;
	    $system = new $system_class();
	    
	   
	    if (!empty($provider)) {
		if ($system->name !== $provider) {
		    break;
		}
	    }

	    if (method_exists($system, $query)) {
		$params = array();
	
		if (isset($this->params[$system->name])) {
		    $params = $this->params[$system->name];
		}
		
		$positions = $system->$query($params);

		if ($positions) {
		    $this->positions[$system->name] = $positions;	    
		    $this->lastcheck =  time();
		}
	    }

            return $this->positions;
        }

        return false;

    }
     // Sanitize Requests values
     public function sanitize_type($type = 'string', $value) {
	 switch ($type) {
	     case 'string':
		  $value = sanitize_text_field($value);
		 break;
	     case 'key':
		  $value = sanitize_key($value);
		 break;
	     case 'url':
		  $value = sanitize_url($value);
		 break;
	     case 'number':
	     case 'int':
		 $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
		 break;
	      case 'float':
		 $value = preg_replace('/[^0-9,\.]+/i', '', $value);
		 break;
	     default:
		  $value = sanitize_text_field($value);
	     
	     
	 }
	 return $value;
     }

     /*
     * convert timestring to 24 hour format
     */
    public function convert_time_24hours($time) {
        if ( strpos( $time, 'PM' ) ) {
            $modtime = explode( ':', rtrim( $time, ' PM' ) );
            if ( $modtime[0] != 12 ) {
                $modtime[0] = $modtime[0] + 12;
            }                
            $time = implode( ':', $modtime );
        } elseif ( strpos( $time, 'AM' ) ) {
            $time = str_replace( '12:', '00:', $time);
            $time = rtrim( $time, ' AM');            
	}

        return $time;
    }
    
    /*
     * Try to make the Salary Schema MonetaryAmount by the textual strings of TV-L 
     * 
     * Example: https://schema.org/MonetaryAmount

		"baseSalary": {
		    "@type": "MonetaryAmount",
		    "currency": "EUR",
		    "value": {
		      "@type": "Text",
		      "value": "TV-L E1 - TV-L E2",
		      "unitText": "MONTH"
		    }
		  }

     */
    public function get_Salary_by_TVL($vonbesold, $bisbesold) {
	
	$res = array();
	$value = '';
	
	 if (!empty($bisbesold)) {
            if (!empty($vonbesold) && ($vonbesold != $bisbesold)) {
               $value = $vonbesold . ' - ' . $bisbesold;
            } else {
               $value = $bisbesold;
            }
        } elseif (!empty($vonbesold)) {
           $value = $vonbesold;
        }
	
	$res["currency"] =  "EUR";
	$res["stringvalue"] =  $value;
	$res["value"]["@type"] =  "Text";
	$res["value"]["value"] =  $value;
	$res["value"]["unitText"] =  "MONTH";
	
	return $res;

    }
    
    public function get_empoymentType_as_string($type) {
	if (is_array($type)) {
	    $res = '';
	    foreach ($type as $i) {
		if (!empty($res)) {
		    $res .= ', '.strval($i);
		} else {		
		    $res = strval($i);
		}
	    }
	    return $res;
	} else {
	    return strval($type);
	}
    }
	    
	   
}