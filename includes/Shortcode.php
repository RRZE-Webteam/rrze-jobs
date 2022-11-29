<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;

use function RRZE\Jobs\Config\getShortcodeSettings;


use RRZE\Jobs\Cache;
use RRZE\Jobs\Template;
// use RRZE\Jobs\Provider;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

class Shortcode
{
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
    public function __construct($pluginFile, $settings)
    {
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
    public function enqueue_scripts()
    {
        wp_register_style('rrze-jobs-css', plugins_url('assets/css/rrze-jobs.css', plugin_basename(RRZE_PLUGIN_FILE)));
        if (file_exists(WP_PLUGIN_DIR . '/rrze-elements/assets/css/rrze-elements.css')) {
            wp_register_style('rrze-elements', plugins_url() . '/rrze-elements/assets/css/rrze-elements.css');
        }
    }



    private function sortArrayByField($myArray, $fieldname, $order)
    {
        if (!empty($this->order)) {
            usort($myArray, function ($a, $b) use ($fieldname, $order) {
                return ($this->order == 'ASC' ? strtolower($a[$fieldname]) <=> strtolower($b[$fieldname]) : strtolower($b[$fieldname]) <=> strtolower($a[$fieldname]));
            });
        }
        return $myArray;
    }

    private function setAtts($atts)
    {
        $aAtts = [
            'limit',
            'orderby',
            'order',
            'fallback_apply',
            'link_only',

        ];

        foreach ($aAtts as $att) {
            $this->$att = (!empty($atts[$att]) ? $atts[$att] : $this->settings[$att]['default']);
        }
    }

    public function shortcodeOutput($atts)
    {

        $this->count = 0;
        $this->setAtts($atts);

        // set jobid from attribute jobid or GET-parameter jobid
        $jobid = (!empty($atts['jobid']) ? sanitize_text_field($atts['jobid']) : (!empty($_GET['jobid']) ? sanitize_text_field($_GET['jobid']) : ''));

        // orgids => attribute orgids or attribute orgid or fetch from settings page
        $orgids = (!empty($atts['orgids']) ? sanitize_text_field($atts['orgids']) : (!empty($atts['orgid']) ? sanitize_text_field($atts['orgid']) : ''));

        // parameter bite-apikey  for setting an api key by pars
        $bite_apikey = (!empty($atts['bite-apikey']) ? sanitize_text_field($atts['bite-apikey']) : '');

        // parameter univis-orgid  for setting the univis org id
        $univis_orgid = (!empty($atts['univis-orgid']) ? sanitize_text_field($atts['univis-orgid']) : '');

        // parameter interamt-id  for setting the univis org id
        $interamt_id = (!empty($atts['interamt-id']) ? sanitize_text_field($atts['interamt-id']) : '');


        // filter provider
        $search_provider = (!empty($atts['provider']) ? sanitize_text_field($atts['provider']) : (!empty($atts['provider']) ? sanitize_text_field($atts['provider']) : ''));

        $link_only = (!empty($atts['link_only']) ? true : false);

        //	$output_format = (!empty($atts['format']) ? sanitize_text_field($atts['format']) : (!empty($_GET['format']) ? sanitize_text_field($_GET['format']) : 'default'));

        $fallback_apply = (!empty($atts['fallback_apply']) ? sanitize_text_field($atts['fallback_apply']) : '');
        // TODO: check if Mailadress or URL
        // If Mail, check if there is a subject we can add, depending of job

        $positions = new Provider();

        /*
	 * TODO: QR-Code new.
	 *  Please use the JS solution like we are using in FAU Einrichtungen theme
	 *  and remove the big library here..
	 * 
	 * 
	if (($output_format == 'embedded') && !empty($_GET['job'])) {
	   $format = 'embedded';
	   $jobnr = (int) $_GET['job'] - 1;
	   $format .= '-'.$jobnr;
	} else {
	   $format = 'default';
	}
	 *   Disabled yet
	 */




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
                    $ret['status'][$provider]['code'] = 400;
                    $ret['status'][$provider]['error'] = __('Invalid provider', 'rrze-jobs') . ' ' . $provider;
                    $ret['status'][$provider]['valid'] = false;
                    $ret['valid'] = false;

                    return $this->get_errormsg($ret);
                }
            }
        }


        $query = 'get_list';
        $params = array();
        if ($jobid) {
            $params['UnivIS']['get_single']['id'] = $jobid;
            $params['Interamt']['get_single']['id'] = $jobid;
            $params['BITE']['get_single']['id'] = $jobid;
            $query = 'get_single';
        }

        if (!empty($this->options['rrze-jobs-access_orgids_univis'])) {
            $params['UnivIS']['get_list']['department'] =  $this->options['rrze-jobs-access_orgids_univis'];
        }
        if (!empty($this->options['rrze-jobs-access_orgids_interamt'])) {
            $params['Interamt']['get_list']['partner'] =  $this->options['rrze-jobs-access_orgids_interamt'];
        }
        if (!empty($this->options['rrze-jobs-access_bite_apikey'])) {
            $params['BITE']['request-header']['headers']['BAPI-Token'] =  $this->options['rrze-jobs-access_bite_apikey'];
        }

        // In case the org id was given as a parameter, overwrite the default from backend
        // orgid will work on all identifier, anyway which provider

        if (!empty($orgids)) {
            $params['UnivIS']['get_list']['department'] = $orgids;
            $params['Interamt']['get_list']['partner'] = $orgids;
            $params['BITE']['request-header']['headers']['BAPI-Token'] = $orgids;
        }

        // special keys and ids für provider, in case they are not the same
        if (!empty($bite_apikey)) {
            $params['BITE']['request-header']['headers']['BAPI-Token'] = $bite_apikey;
        }
        if (!empty($univis_orgid)) {
            $params['UnivIS']['get_list']['department'] = $univis_orgid;
            $params['UnivIS']['get_single']['department'] = $univis_orgid;
        }
        if (!empty($interamt_id)) {
            $params['Interamt']['get_list']['partner'] = $interamt_id;
            $params['Interamt']['get_single']['partner'] = $interamt_id;
        }



        $positions->set_params($params);
        $positions->get_positions($search_provider, $query);
        $newdata = $positions->merge_positions();


        if ($newdata['valid'] === false) {
            return $this->get_errormsg($newdata);
        }


        $parserdata = array();
        $strings = $this->get_labels();


        if ($query == 'get_single') {
            // single job
            $content = '';


            if ($newdata['valid'] === true) {
                $template = plugin()->getPath() . 'Templates/Shortcodes/single-job.html';
                if ($link_only) {
                    $template = plugin()->getPath() . 'Templates/Shortcodes/single-job-linkonly.html';
                }

                $data['const'] = $strings;
                $parserdata['num'] = 1;
                $content = '';

                foreach ($newdata['positions'] as $num => $data) {
                    $hidethis = $this->hideinternal($data);
                    if ($hidethis) {
                        // Ignore/hide this position in display
                        $ret['status'][$num]['code'] = 403;
                        $ret['status'][$num]['valid'] = false;
                        return $this->get_errormsg($ret);
                    } else {

                        $data['const'] = $strings;
                        $data['employmentType'] = $positions->get_empoymentType_as_string($data['employmentType']);
                        $data['applicationContact']['url'] = $positions->get_apply_url($data, $fallback_apply);

                        $data = $this->ParseDataVars($data);
                        $content .= Template::getContent($template, $data);
                    }
                }



                $content = do_shortcode($content);

                if (!empty($content)) {
                    wp_enqueue_style('rrze-elements');
                    wp_enqueue_style('rrze-jobs-css');

                    return $content;
                } else {
                    $errortext = "Empty content from template by asking for job id: " . $jobid;
                    return $this->get_errormsg($newdata);
                }
            } else {
                $errortext = "Empty content from template by asking for job id: " . $jobid;
                return $this->get_errormsg($newdata, $errortext, 'Error: No content');
            }
        } else {
            // list


            if (($newdata['valid'] === true) && (!empty($newdata['positions']))) {
                $parserdata['joblist'] = '';

                $parserdata['num'] = count($newdata['positions']);
                $template = plugin()->getPath() . 'Templates/Shortcodes/joblist-single.html';
                if ($link_only) {

                    $template = plugin()->getPath() . 'Templates/Shortcodes/joblist-single-linkonly.html';
                }


                foreach ($newdata['positions'] as $num => $data) {
                    $hidethis = $this->hideinternal($data);

                    //    echo Helper::get_html_var_dump($data);

                    if ($hidethis) {
                        // Ignore/hide this position in display
                        // Also do not give an error message like at single display

                    } else {
                        $data['const'] = $strings;
                        $data['employmentType'] = $positions->get_empoymentType_as_string($data['employmentType']);
                        $data['applicationContact']['url'] = $positions->get_apply_url($data, $fallback_apply);


                        $data = $this->ParseDataVars($data);
                        $parserdata['joblist'] .= Template::getContent($template, $data);
                    }
                }

                $template = plugin()->getPath() . 'Templates/Shortcodes/joblist.html';
                if ($link_only) {
                    $template = plugin()->getPath() . 'Templates/Shortcodes/joblist-linkonly.html';
                }
            } else {
                $parserdata['errormsg'] = __('No jobs found.', 'rrze-jobs');
                $parserdata['errortitle'] = __('Error', 'rrze-jobs');
                $template = plugin()->getPath() . 'Templates/Shortcodes/joblist-error.html';
            }
            if (!is_readable($template)) {
                $errortext .=  "Templatefile $template not readable!!";
                return $this->get_errormsg($parserdata, $errortext, 'Template Error');
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
                return $this->get_errormsg($parserdata, $errortext, 'Output Error');
            }
        }

        $errortext =  "Unknown shortcode handling";
        //	$errortext .=  Helper::get_html_var_dump($parserdata);
        return $this->get_errormsg($parserdata, $errortext);
    }



    private static function isIPinRange($fromIP, $toIP, $myIP)
    {
        $min = ip2long($fromIP);
        $max = ip2long($toIP);
        $needle = ip2long($myIP);

        return (($needle >= $min) and ($needle <= $max));
    }


    // check if the position is internal and has to be ignored by current user host
    /* IP-Range der Public Displays (dürfen interne Jobs nicht anzeigen): 10.26.24.0/24 und 10.26.25.0/24
     */
    private function hideinternal($data)
    {
        if ((isset($data['_provider-values']['intern'])) && ($data['_provider-values']['intern'] === true)) {
            if ((isset($this->options['rrze-jobs-misc_hide_internal_jobs'])) && ($this->options['rrze-jobs-misc_hide_internal_jobs'] === false)) {
                return false;
            }

            $remoteIP = $_SERVER['REMOTE_ADDR'];
            $remoteAdr = gethostbyaddr($remoteIP);

            // TODO: Move this into constant arrays instead of hard codet values
            if ($this->isIPinRange('10.26.24.0', '10.26.24.24', $remoteIP) || $this->isIPinRange('10.26.25.0', '10.26.25.24', $remoteIP)) {
                return true;
            }

            if (is_user_logged_in()) {
                if ($this->options['rrze-jobs-misc_hide_internal_jobs_notforadmins'] == true) {
                    return false;
                }
            }



            if ((isset($this->options['rrze-jobs-misc_hide_internal_jobs_required_hosts'])) && (!empty($this->options['rrze-jobs-misc_hide_internal_jobs_required_hosts']))) {

                $required_hosts = trim($this->options['rrze-jobs-misc_hide_internal_jobs_required_hosts']);
                $hosts = preg_split("/[\s,\n]+/", $required_hosts);
                $ret = true;
                foreach ($hosts as $h) {
                    if ((strpos($remoteAdr, $h) !== false)) {
                        $ret = false;
                        break;
                    }
                }

                return $ret;
            }

            return true;
        }
        return false;
    }
    /*
     * Erroroutput for Shortcode calls
     */

    private function get_errormsg($parserdata, $text = '', $title = '')
    {


        $parserdata['errormsg'] = $parserdata['errorcode'] =  $parserdata['errortitle'] = '';

        foreach ($parserdata['status'] as $provider) {

            $errortextfield = 'rrze-jobs-labels_job_errortext_' . $provider['code'];
            if (isset($this->options[$errortextfield])) {
                $errormsg = $this->options[$errortextfield];
            } else {
                $errormsg = $provider['error'];
            }
            if (!empty($parserdata['errormsg'])) {
                $parserdata['errormsg'] .= ', ';
            }
            $parserdata['errormsg'] .= $errormsg;

            if (!empty($parserdata['errorcode'])) {
                $parserdata['errorcode'] .= ', ';
            }
            $parserdata['errorcode'] .= $provider['code'];


            if (!empty($parserdata['errortitle'])) {
                $parserdata['errortitle'] .= ', ';
            }
            $parserdata['errortitle'] .= __('Error', 'rrze-jobs') . ' ' . $provider['code'];
        }

        if (!empty($text)) {
            $parserdata['errormsg'] = $text;
        }

        if (!empty($title)) {
            $parserdata['errortitle'] = $title;
        }


        if ((empty($parserdata['errormsg']))  || ((isset($this->options['rrze-jobs-labels_job_errortext_display'])) && ($this->options['rrze-jobs-labels_job_errortext_display'] == false))) {
            $content =  "<!--  ";
            $content .= " Code: " . $parserdata['errorcode'] . "; Msg: " . $parserdata['errormsg'];
            $content .= " -->";

            return $content;
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
            return $content;
        }
    }

    // replace Parse Variables in values itself outside the parser
    private function ParseDataVars($data)
    {
        $searchfields = $data['const'];
        $replacefields = array("description", "qualifications", "disambiguatingDescription", "text_jobnotice");
        foreach ($replacefields as $r) {
            if (isset($data[$r])) {
                foreach ($searchfields as $name => $value) {
                    $pos = strpos($name, 'title_');
                    if (($pos !== false) && ($pos == 0)) {
                        $searchval = '/{{=const.' . $name . '}}/i';
                        $data[$r] = preg_replace($searchval, $value, $data[$r]);
                    }
                }
            }
        }
        return $data;
    }
    // get the Labels from the options 
    private function get_labels()
    {
        $res = array();
        foreach ($this->options as $name => $value) {
            $pos = strpos($name, 'rrze-jobs-labels_job_headline_');
            if (($pos !== false) && ($pos == 0)) {
                preg_match('/rrze-jobs-labels_job_headline_([a-z0-9\-_]*)/i', $name, $output_array);
                if (!empty($output_array)) {
                    $keyname = 'title_' . $output_array[1];
                    $res[$keyname] = $value;
                }
            }
            $pos = strpos($name, 'rrze-jobs-labels_job_defaulttext_');
            if (($pos !== false) && ($pos == 0)) {
                preg_match('/rrze-jobs-labels_job_defaulttext_([a-z0-9\-_]*)/i', $name, $output_array);
                if (!empty($output_array)) {
                    $keyname = 'text_' . $output_array[1];
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
        register_block_type(
            $this->settings['block']['blocktype'],
            array(
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
