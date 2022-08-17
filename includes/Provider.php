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
     

     public function set_provider_params($provider, $params) {
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
	$res["@type"] =  "MonetaryAmount";
	$res["currency"] =  "EUR";
	$res["value"]["@type"] =  "Text";
	$res["value"]["value"] =  $value;
	$res["value"]["unitText"] =  "MONTH";
	
	return $res;

    }
	    
	   
}