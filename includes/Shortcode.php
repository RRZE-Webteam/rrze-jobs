<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;
use function RRZE\Jobs\Config\getShortcodeSettings;

use RRZE\Jobs\Job;
use RRZE\Jobs\Cache;
use RRZE\Jobs\Template;
// use RRZE\Jobs\Provider;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

class Shortcode {
    private $provider = '';
    private $map_template = [];
    private $jobid = 0;
    private $aOrgIDs = [];
    private $count = 0;
    private $settings = '';
    private $pluginname = '';
    private $options = [];
    private $jobOutput = '';
    private $logo_url;

    private $limit;
    private $orderby;
    private $order;
    private $internal = 'exclude';
    private $fallback_apply = '';
    private $link_only;

    /**
     * Shortcode-Klasse wird instanziiert.
     */
    public function __construct($pluginFile,$settings) {
	 $this->pluginFile = $pluginFile;
	 
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $this->settings = getShortcodeSettings();
        $this->pluginname = $this->settings['block']['blockname'];
        $this->options = $settings->getOptions();
        $this->logo_url = (has_custom_logo() ? wp_get_attachment_url(get_theme_mod('custom_logo')) : RRZE_JOBS_LOGO);
        add_action('init', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueGutenberg']);
        add_action('init', [$this, 'initGutenberg']);
        add_action('admin_head', [$this, 'setMCEConfig']);
        add_filter('mce_external_plugins', [$this, 'addMCEButtons']);
	
        if (!is_plugin_active('fau-jobportal/fau-jobportal.php')) {
            add_shortcode('jobs', [$this, 'shortcodeOutput']);
	    add_shortcode('rrze-jobs', [$this, 'shortcodeOutput']);
        }
    }

    /**
     * Enqueue der Skripte.
     */
    public function enqueue_scripts() {
        wp_register_style('rrze-jobs-css', plugins_url('assets/css/rrze-jobs.css', plugin_basename(RRZE_PLUGIN_FILE)));
        if (file_exists(WP_PLUGIN_DIR . '/rrze-elements/assets/css/rrze-elements.css')) {
            wp_register_style('rrze-elements', plugins_url() . '/rrze-elements/assets/css/rrze-elements.css');
        }
    }

    

    private function sortArrayByField($myArray, $fieldname, $order)  {
        if (!empty($this->order)) {
            usort($myArray, function ($a, $b) use ($fieldname, $order) {
                return ($this->order == 'ASC' ? strtolower($a[$fieldname]) <=> strtolower($b[$fieldname]) : strtolower($b[$fieldname]) <=> strtolower($a[$fieldname]));
            });
        }
        return $myArray;
    }

    private function setAtts($atts)  {
        $aAtts = [
            'limit',
            'orderby',
            'order',
            'internal',
            'fallback_apply',
            'link_only',
        ];

        foreach ($aAtts as $att) {
            $this->$att = (!empty($atts[$att]) ? $atts[$att] : $this->settings[$att]['default']);
        }
    }

    public function shortcodeOutput($atts)  {
       
        $this->count = 0;
        $this->setAtts($atts);

	// set jobid from attribute jobid or GET-parameter jobid
        $jobid = (!empty($atts['jobid']) ? sanitize_text_field($atts['jobid']) : (!empty($_GET['jobid']) ? sanitize_text_field($_GET['jobid']) : ''));

        // orgids => attribute orgids or attribute orgid or fetch from settings page
        $orgids = (!empty($atts['orgids']) ? sanitize_text_field($atts['orgids']) : (!empty($atts['orgid']) ? sanitize_text_field($atts['orgid']) : ''));
	
	
	$search_provider = (!empty($atts['provider']) ? sanitize_text_field($atts['provider']) : (!empty($atts['provider']) ? sanitize_text_field($atts['provider']) : ''));
	
	$output_format = (!empty($atts['format']) ? sanitize_text_field($atts['format']) : (!empty($_GET['format']) ? sanitize_text_field($_GET['format']) : 'default'));
	
	
	$positions = new Provider();

	
	if (($output_format == 'embedded') && !empty($_GET['job'])) {
	   $format = 'embedded';
	   $jobnr = (int) $_GET['job'] - 1;
	   $format .= '-'.$jobnr;
	} else {
	   $format = 'default';
	}
	if (!empty($search_provider)) {
	    $aProvider = explode(',', $search_provider);
	    $filterprovider = '';
	    foreach ($aProvider as $provider) {
		$input = $provider;
		$provider = trim(strtolower(sanitize_text_field($provider)));

		$validprovider = $positions->is_valid_provider($provider);
		if ($validprovider !== false) {
		    $search_provider = $validprovider;
		    break;
		} else {
		    $errortext = "No valid provider \"$provider\" (Input: \"$input\")";
		    return $this->get_errormsg('Error: Bad provider', $errortext);
		}   
	    }
	}
	
     	
	$query = 'get_list';
	$params = array();
	if ($jobid) {
	    $params['UnivIS']['get_list']['id'] = $jobid;
	    $params['Interamt']['get_list']['Id'] =$jobid;
	    $query = 'get_single';	    
	}
	
	if (!empty($this->options['rrze-jobs-access_orgids_univis'])) {
		$params['UnivIS']['get_list']['department'] =  $this->options['rrze-jobs-access_orgids_univis'];
        }
	if (!empty($this->options['rrze-jobs-access_orgids_interamt'])) {
		$params['Interamt']['get_list']['partner'] =  $this->options['rrze-jobs-access_orgids_interamt'];
        } 
	if (!empty($this->options['rrze-jobs-access_bite_apikey'])) {
		$params['BITE']['get_list']['apikey'] =  $this->options['rrze-jobs-access_bite_apikey'];
		$params['BITE']['get_single']['apikey'] =  $this->options['rrze-jobs-access_bite_apikey'];
        } 
	
	 // In case the org id was given as a parameter, overwrite the default from backend
	if (!empty($orgids)) {
	    $params['UnivIS']['get_list']['department'] = $orgids;
	    $params['Interamt']['get_list']['partner'] = $orgids;   
	}
	

	$positions->set_params($params);
	// echo "get positions with $search_provider and $query with orgid $orgids, jobid: $jobid<br>";
	// echo "options: ". Helper::get_html_var_dump($this->options); 
		
		
	$positions->get_positions($search_provider, $query);
	$newdata = $positions->merge_positions();
	
	
	
	if ($newdata['valid']===false) {
	   $errortext = "No valid jobdata found. Provider $search_provider with id $orgids, jobid: $jobid";
	   return $this->get_errormsg('Error', $errortext, $newdata);	
	}
	
	
	$parserdata = array();
	// Output Strings - later to be changed with settings
	$strings = $this->get_labels();
	
	// echo Helper::get_html_var_dump($this->options);
	
	if ($query == 'get_single') {
	    // single job
	    
	    echo "look for ".$jobid;
	     echo  Helper::get_html_var_dump($newdata);
	    

	    if ($newdata['valid']===true) {
		$template = plugin()->getPath() . 'Templates/Shortcodes/single-job.html';
		$data['const'] = $strings;
		$content = '';
		$parserdata['num'] = 1;


		foreach ($newdata['positions'] as $num => $data) {
			$data['const'] = $strings;
			$data['employmentType'] = $positions->get_empoymentType_as_string($data['employmentType']);
			$data = $this->ParseDataVars($data);
			$content .= Template::getContent($template, $data);
		}



		$content = do_shortcode($content);

		if (!empty($content)) {
		    wp_enqueue_style('rrze-elements');
		    wp_enqueue_style('rrze-jobs-css');
		    
		    return $content;
		} else {
		    $errortext = "Empty content from template by asking for job id: ".$jobid;
		    return $this->get_errormsg('Error: No content', $errortext, $newdata);	
		}
	    } else {
		
		  $errortext = "Empty content from template by asking for job id: ".$jobid;
		  $errortext .= Helper::get_html_var_dump($newdata);
		  
		 return $this->get_errormsg('Error: No content', $errortext, $newdata);	
		
	    }
	    
	    
	} else {
	    // list
    
	    
	    if ($newdata['valid']===true) {
		$parserdata['joblist'] = '';
		
		$parserdata['num'] = count($newdata['valid']);
		foreach ($newdata['positions'] as $num => $data) {
		    $template = plugin()->getPath() . 'Templates/Shortcodes/joblist-single.html';
		    $data['const'] = $strings;
		    $data['employmentType'] = $positions->get_empoymentType_as_string($data['employmentType']);
		    
		    $data = $this->ParseDataVars($data);
		    
		    
		    $parserdata['joblist'] .= Template::getContent($template, $data);
		}
		
		$template = plugin()->getPath() . 'Templates/Shortcodes/joblist.html';
	    } else {
		$parserdata['errormsg'] = __('No jobs found.','rrze-jobs');
		$parserdata['errormsg'] .= Helper::get_html_var_dump($newdata);
		$parserdata['errortitle'] = __('Error','rrze-jobs');
		$template = plugin()->getPath() . 'Templates/Shortcodes/joblist-error.html';
	    }
	    if (!is_readable($template)) {
		$errortext .=  "Templatefile $template not readable!!";    
		return $this->get_errormsg('Template Error', $errortext, $parserdata);
	    }

	    $parserdata['const'] = $strings;
	    $content = Template::getContent($template, $parserdata);
	    $content = do_shortcode($content);
	    if (!empty($content)) {
		wp_enqueue_style('rrze-elements');
		wp_enqueue_style('rrze-jobs-css');
		
		return $content;
		
	    } else {
		$errortext .=  "Empty content from template $template";    
		return $this->get_errormsg('Output Error', $errortext, $parserdata);
	    
		
	    }
	}
	
	
	// echo Helper::get_html_var_dump($newdata);
	//	echo Helper::get_html_var_dump($parserdata);
	
        
	$errortext =  "Unknown shortcode handling";    
	$errortext .=  Helper::get_html_var_dump($parserdata);
	return $this->get_errormsg('Error', $errortext, $parserdata);

    }
    
    
    
    /*
     * Erroroutput for Shortcode calls
     */

    private function get_errormsg($title, $text, $parserdata = array()) {	
	if ((!isset($text)) || (empty($text))) {
	    $parserdata['errormsg'] = __('No jobs found.','rrze-jobs');
	} else {
	     $parserdata['errormsg'] = $text;
	}
	
	if ((!isset($title)) || (empty($title))) {
	    $parserdata['errortitle'] = __('Error','rrze-jobs');
	} else {
	    $parserdata['errortitle'] = $title;
	}
	
	
	$template = plugin()->getPath() . 'Templates/Shortcodes/error.html';
	$content = Template::getContent($template, $parserdata);	
	$content = do_shortcode($content);

	if (!empty($content)) {
	    wp_enqueue_style('rrze-elements');
	    wp_enqueue_style('rrze-jobs-css');
	    return $content;
	} else {
	    $content =  "<!-- Error on creating errormessage for shortcode call -->";    
	    $content .= Helper::get_html_var_dump($parserdata);
	    return $content;
	    
	}
    }

    // replace Parse Variables in values itself outside the parser
    private function ParseDataVars($data) {
	$searchfields = $data['const'];
	$replacefields = array("description", "qualifications", "disambiguatingDescription", "text_jobnotice" );
	foreach ($replacefields as $r) {
	    if (isset($data[$r])) {
		foreach ($searchfields as $name => $value) {
		     $pos = strpos($name, 'title_');
		    if (($pos !== false) && ($pos==0)) {
			$searchval = '/{{=const.'.$name.'}}/i';
			$data[$r] = preg_replace($searchval, $value, $data[$r]);
		    }
		}
	    }
	}
	return $data;
	
    }
    // get the Labels from the options 
    private function get_labels() {
	
	$res = array();
	foreach ($this->options as $name => $value) {
	    $pos = strpos($name, 'rrze-jobs-labels_job_headline_');
	    if (($pos !== false) && ($pos==0)) {
		preg_match('/rrze-jobs-labels_job_headline_([a-z0-9\-_]*)/i', $name, $output_array);
		if (!empty($output_array)) {
		    $keyname = 'title_'.$output_array[1];
		    $res[$keyname] = $value;
		}
	    }
	   
	}
	return $res;
	
	
    }
   



    public function isGutenberg()
    {
        $postID = get_the_ID();
        if ($postID && !use_block_editor_for_post($postID)) {
            return false;
        }

        return true;
    }

    public function initGutenberg()
    {
        if (!$this->isGutenberg()) {
            return;
        }

        // register js-script to inject php config to call gutenberg lib
        $editor_script = $this->settings['block']['blockname'] . '-block';
        $js = '../assets/js/' . $editor_script . '.js';

        wp_register_script(
            $editor_script,
            plugins_url($js, __FILE__),
            array(
                'RRZE-Gutenberg',
            ),
            null
        );
        wp_localize_script($editor_script, $this->settings['block']['blockname'] . 'Config', $this->settings);

        // register block
        register_block_type($this->settings['block']['blocktype'], array(
            'editor_script' => $editor_script,
            'render_callback' => [$this, 'shortcodeOutput'],
            'attributes' => $this->settings,
        )
        );
    }

    public function enqueueGutenberg()
    {
        if (!$this->isGutenberg()) {
            return;
        }

        // include gutenberg lib
        wp_enqueue_script(
            'RRZE-Gutenberg',
            plugins_url('../assets/js/gutenberg.js', __FILE__),
            array(
                'wp-blocks',
                'wp-i18n',
                'wp-element',
                'wp-components',
                'wp-editor',
            ),
            null
        );
    }

    public function setMCEConfig()
    {
        $shortcode = '';
        foreach ($this->settings as $att => $details) {
            if ($att != 'block') {
                $shortcode .= ' ' . $att . '=""';
            }
        }
        $shortcode = '[' . $this->pluginname . ' ' . $shortcode . ']';
        ?>
        <script type='text/javascript'>
            tmp = [{
                'name': <?php echo json_encode($this->pluginname); ?>,
                'title': <?php echo json_encode($this->settings['block']['title']); ?>,
                'icon': <?php echo json_encode($this->settings['block']['tinymce_icon']); ?>,
                'shortcode': <?php echo json_encode($shortcode); ?>,
            }];
            phpvar = (typeof phpvar === 'undefined' ? tmp : phpvar.concat(tmp));
        </script>
        <?php
}

    public function addMCEButtons($pluginArray)
    {
        if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
            $pluginArray['rrze_shortcode'] = plugins_url('../assets/js/tinymce-shortcodes.js', plugin_basename(__FILE__));
        }
        return $pluginArray;
    }
}

