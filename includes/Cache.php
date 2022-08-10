<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;
use function RRZE\Jobs\Config\getConstants;

use RRZE\Jobs\Job;

/**
 * Methide zur Zwischenspeicherung von Ergebnissen
 */
class Cache  {
    protected $pluginFile;
    private $settings = '';
    
    public function __construct() {
	$this->constants = getConstants();
    }

    public function onLoaded() {
	return true;
    }
    
    public function set_cachetime($time) {
	if ((isset($time)) && (intval($time) > 0)) {
	    $this->constants['Transient_Seconds'] = $time;
	}
	return $this->constants['Transient_Seconds'];
    }
    
    public function get_cached_job($provider = '', $provider_orgid = '', $jobid = '', $format = 'default') {
	$prefix = $this->constants['Transient_Prefix'];
	if (empty($provider)) {
	    $provider = 'noprovider';
	} else {
	    $provider = preg_replace('/[^a-z0-9]+/i', '', $provider);
	}
	
	if (empty($provider_orgid)) {
	    $provider_orgid = 'noorg';
	} else {
	    $provider_orgid = preg_replace('/[^a-z0-9]+/i', '', $provider_orgid);
	}
	if (empty($jobid)) {
	    $jobid = 'noid';
	} else {
	    $jobid = preg_replace('/[^a-z0-9]+/i', '', $jobid);
	}
	$transient_name = $prefix.'_'.$provider.'_'.$provider_orgid.'_'.$jobid;
	if ($format !== 'default') {
	    $format = preg_replace('/[^a-z0-9]+/i', '', $format);
	    
	    $transient_name .= '_'.$format;
	}
	$value = get_transient( $transient_name );
	
	if ( false === $value ) {
	    return false;
	} else {
	    return $value;
	}
    }
    public function set_cached_job($provider = '', $provider_orgid = '', $jobid = '', $format = 'default', $content = '') {	
	if (empty($content)) {
	    return false;
	}
	
	$prefix = $this->constants['Transient_Prefix'];
	if (empty($provider)) {
	    $provider = 'noprovider';
	} else {
	    $provider = preg_replace('/[^a-z0-9]+/i', '', $provider);
	}
	
	if (empty($provider_orgid)) {
	    $provider_orgid = 'noorg';
	} else {
	    $provider_orgid = preg_replace('/[^a-z0-9]+/i', '', $provider_orgid);
	}
	if (empty($jobid)) {
	    $jobid = 'noid';
	} else {
	    $jobid = preg_replace('/[^a-z0-9]+/i', '', $jobid);
	}
	$transient_name = $prefix.'_'.$provider.'_'.$provider_orgid.'_'.$jobid;
	if ($format !== 'default') {
	    $format = preg_replace('/[^a-z0-9]+/i', '', $format);
	    $transient_name .= '_'.$format;
	}
	$cachetime = $this->constants['Transient_Seconds'];
	
	set_transient( $transient_name, $content, $cachetime);
	return true;
    }
}
