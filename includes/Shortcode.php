<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;

use function RRZE\Jobs\Config\getShortcodeSettings;
use RRZE\Jobs\Template;

// use RRZE\Jobs\Provider;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

class Shortcode {
    private $provider = '';
    private $map_template = [];
    private $jobid = 0;
    private $aOrgIDs = [];
    private $count = 0;
    private $settings;
    private $pluginname = '';
    static $options = [];
    private $jobOutput = '';
    private $logo_url;

    /*
     * Attributes
     */
    private $limit;
    private $orderby;
    private $order;
    private $internal = 'exclude';
    private $fallback_apply = '';
    private $default_subject = '';
    private $link_only;
    private $category;
    private $fauorg;
    

    
    /**
     * Der vollständige Pfad- und Dateiname der Plugin-Datei.
     * @var string
     */
    protected $pluginFile;

    /**
     * Shortcode-Klasse wird instanziiert.
     */
    public function __construct($pluginFile, $settings) {
        $this->pluginFile = $pluginFile;

        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $this->settings = getShortcodeSettings();
        $this->pluginname = $this->settings['block']['blockname'];
        self::$options = $settings->getOptions();
        $this->logo_url = (has_custom_logo() ? wp_get_attachment_url(get_theme_mod('custom_logo')) : RRZE_JOBS_LOGO);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_head', [$this, 'setMCEConfig']);
        add_filter('mce_external_plugins', [$this, 'addMCEButtons']);


        add_shortcode('jobs', [$this, 'shortcodeOutput']);
        add_shortcode('rrze-jobs', [$this, 'shortcodeOutput']);

    }

    /**
     * Enqueue der Skripte.
     */
    public function enqueue_scripts()  {
        wp_register_style('rrze-jobs-css', plugins_url('assets/css/rrze-jobs.css', plugin_basename(RRZE_PLUGIN_FILE)), [], RRZE_JOBS_PLUGIN_VERSION);
        if (file_exists(WP_PLUGIN_DIR . '/rrze-elements/assets/css/rrze-elements.css')) {
            wp_register_style('rrze-elements', plugins_url() . '/rrze-elements/assets/css/rrze-elements.css');
        }
    }

    private function sortArrayByField($myArray) {
        $this->orderby = self::backwardCompatibility($this->orderby);

        usort($myArray, function ($a, $b) {
            if ($this->orderby == 'title'){
                // we have DESC per default because dates are sorted this way => if no orderby is given, default is title and would be sorted descening which does not make any sense
                return strtolower($a[$this->orderby]) <=> strtolower($b[$this->orderby]);
            }else{
                return ($this->order == 'ASC' ? $a[$this->orderby] <=> $b[$this->orderby] : $b[$this->orderby] <=> $a[$this->orderby]);
            }
        });

        return $myArray;
    }

    private function setAtts($atts) {
        $aAtts = [
            'limit',
            'orderby',
            'order',
            'fallback_apply',
            'default_subject',
            'link_only',
            'category',
            'fauorg',
        ];

        foreach ($aAtts as $att) {
            $this->$att = (!empty($atts[$att]) ? $atts[$att] : $this->settings[$att]['default']);
        }
    }


    private function backwardCompatibility($val) {
        $ret = $val;

        switch($val){
            case 'job_title':
                $ret = 'title';
                break;
            case 'application_end':
                $ret = 'validThrough';
                break;
            case 'job_start':
                $ret = 'jobStartDateSort';
                break;
        }

        return $ret;
    }

    public function shortcodeOutput($atts)  {


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
        $search_provider = (!empty($atts['provider']) ? sanitize_text_field($atts['provider']) : '');

        // optional search for jobs with the given category in occupationalCategory
        $search_category = (!empty($atts['category']) ? sanitize_text_field($atts['category']) : '');
        $search_category = strtolower($search_category);

        // optional search for FAU.ORG Number (BITE only)
        $search_fauorg = (!empty($atts['fauorg']) ? sanitize_text_field($atts['fauorg']) : '');

        $link_only = (!empty($atts['link_only']) ? true : false);

        //    $output_format = (!empty($atts['format']) ? sanitize_text_field($atts['format']) : (!empty($_GET['format']) ? sanitize_text_field($_GET['format']) : 'default'));

        $fallback_apply = (!empty($atts['fallback_apply']) ? sanitize_text_field($atts['fallback_apply']) : '');
        $default_subject = (!empty($atts['default_subject']) ? sanitize_text_field($atts['default_subject']) : '');
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
        $debugmsg = '';
  //      $debugmsg .= Debug::get_notice("ATTS:<br>".Debug::get_html_var_dump($atts).'<br>Search Provider: '.$search_provider);
        
        $aSearchProvider = $positions->systems;

        if (!empty($search_provider)) {
            $aSearchProvider = array_map(function ($val) use ($positions) {
                foreach ($positions->systems as $system_name) {
                    if (strtolower(trim($val)) === strtolower(trim($system_name))) {
                        return $system_name;
                    }
                }
            }, explode(',', $search_provider));

            $aProvider = explode(',', $search_provider);

            foreach ($aProvider as $provider) {
                $provider = trim(strtolower($provider));

                $validprovider = $positions->is_valid_provider($provider);
                if ($validprovider === false) {
                    $ret['status'][$provider]['code'] = 400;
                    $ret['status'][$provider]['error'] = __('Invalid provider', 'rrze-jobs') . ' ' . $provider;
                    $ret['status'][$provider]['valid'] = false;
                    $ret['valid'] = false;

                    return self::get_errormsg($ret);
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

        if (!empty(self::$options['rrze-jobs-access_orgids_univis'])) {
            $params['UnivIS']['get_list']['department'] = self::$options['rrze-jobs-access_orgids_univis'];
        }
        if (!empty(self::$options['rrze-jobs-access_orgids_interamt'])) {
            $params['Interamt']['get_list']['partner'] = self::$options['rrze-jobs-access_orgids_interamt'];
        }

        if (!empty(Helper::getBiteApiKey())) {
            $params['BITE']['request-header']['headers']['BAPI-Token'] = Helper::getBiteApiKey();
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

        $debugmsg .= Debug::get_notice("Params:<br>".Debug::get_html_var_dump($params));
        $positions->set_params($params);

        foreach ($aSearchProvider as $provider) {
            $positions->get_positions($provider, $query);
        }

        $newdata = $positions->merge_positions();

        $debugmsg .= Debug::get_notice("Data:<br>".Debug::get_html_var_dump($newdata));

        if ($newdata['valid'] === false) {
            $res = $debugmsg . self::get_errormsg($newdata);
            return $res;
        }

        $parserdata = array();
        $strings = $this->get_labels();

        if ($query == 'get_single') {
            // single job
            $content = '';

            if ($newdata['valid'] === true) {
                $template = plugin()->getPath() . 'Templates/Shortcodes/single-job-bite.html';
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
                        return self::get_errormsg($ret);
                    } else {
                        $data['const'] = $strings;
                        $data['const']['internal_message'] = __('This is an internal job offer, accessible from FAU network only.', 'rrze-jobs');

                        // convert output to German format BUT NOT the one from $positions->merge_positions() because FAU-Jobportal needs Y-m-d (in fact WordPress needs this to sort by meta_value)
                        if ($data['jobStartDate'] != 'So bald wie möglich.') {
                            $data['jobStartDate'] = date('d.m.Y', strtotime($data['jobStartDate']));
                        }

                        $data['datePosted'] = date('Y-m-d', strtotime($data['datePosted']));
                        $data['releaseDate'] = date('d.m.Y', strtotime($data['datePosted']));
                        $data['validThrough_DE'] = date('d.m.Y', strtotime($data['validThrough']));

                        $data['employmentType'] = $positions->get_empoymentType_as_string($data['employmentType']);
                        $data['applicationContact']['url'] = $positions->get_apply_url($data, $fallback_apply, $default_subject);

                        $data = self::ParseDataVars($data);
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
                    return self::get_errormsg($newdata);
                }
            } else {
                $errortext = "Empty content from template by asking for job id: " . $jobid;
                return self::get_errormsg($newdata, $errortext, 'Error: No content');
            }
        } else {
            // list

            if (($newdata['valid'] === true) && (!empty($newdata['positions']))) {
                $parserdata['joblist'] = '';

                $parserdata['num'] = count($newdata['positions']);
                $listjobs = 0;

                $newdata['positions'] = self::sortArrayByField($newdata['positions']);

                foreach ($newdata['positions'] as $num => $data) {
                    $hidethis = $this->hideinternal($data);
                    if ($hidethis) {
                        // Ignore/hide this position in display
                        // Also do not give an error message like at single display

                    } else {
                        if ((!empty($search_category)) && (!empty($data['orig_occupationalCategory']))) {
                            $jobcategory = $positions->map_occupationalCategory($data['orig_occupationalCategory']);
                            if ($search_category !== $jobcategory) {
                                continue;
                            }
                        }

                        if (!empty($search_fauorg)) {
                            if (empty($data['hiringOrganization']['fauorg'])) {
                                continue;
                            }
                            $fauorg = $data['hiringOrganization']['fauorg'];
                            if ($search_fauorg !== $fauorg) {
                                continue;
                            }
                        }

                        $data['const'] = $strings;
                        $data['const']['internal_message'] = __('This is an internal job offer, accessible from FAU network only.', 'rrze-jobs');

                        if ((isset(self::$options['rrze-jobs-labels_job_usedefaulttext_jobnotice'])) && (self::$options['rrze-jobs-labels_job_usedefaulttext_jobnotice']) && (!empty($data['text_jobnotice']))) {
                            $data['const']['text_jobnotice'] = $data['text_jobnotice'];
                        }
                        // convert output to German format BUT NOT the one from $positions->merge_positions() because FAU-Jobportal needs Y-m-d (in fact WordPress needs this to sort by meta_value)
                        if (empty($data['jobStartDate'])) {
                            $data['jobStartDate'] = 'So bald wie möglich.';
                        } elseif ($data['jobStartDate'] != 'So bald wie möglich.') {
                            $data['jobStartDate'] = date('d.m.Y', strtotime($data['jobStartDate']));
                        }

                        $data['title'] = htmlentities($data['title']);
                        $data['datePosted'] = date('Y-m-d', strtotime($data['datePosted']));
                        $data['releaseDate'] = date('d.m.Y', strtotime($data['datePosted']));
                        $data['validThrough_DE'] = date('d.m.Y', strtotime($data['validThrough']));

                        $data['employmentType'] = $positions->get_empoymentType_as_string($data['employmentType']);
                        $data['applicationContact']['url'] = $positions->get_apply_url($data, $fallback_apply, $default_subject);

                        $data = self::ParseDataVars($data);

                        $templateSub = ((isset($data['provider']) && $data['provider'] == 'BITE') ? '-bite' : '');
                        $template = plugin()->getPath() . 'Templates/Shortcodes/joblist-single'.$templateSub.'.html';

                        if ($link_only) {
                            $template = plugin()->getPath() . 'Templates/Shortcodes/joblist-single-linkonly.html';
                        }

                        $parserdata['joblist'] .= Template::getContent($template, $data);

                        $listjobs++;

                        if (!empty($this->limit) && ($this->limit <= $listjobs)){
                            break;
                        }
                    }
                }

                if ($listjobs > 0) {
                    $template = plugin()->getPath() . 'Templates/Shortcodes/joblist.html';
                    if ($link_only) {
                        $template = plugin()->getPath() . 'Templates/Shortcodes/joblist-linkonly.html';
                    }
                } else {
                    $ret['status'][0]['code'] = 404;
                    $ret['status'][0]['valid'] = false;
                    return self::get_errormsg($ret);
                }
            } else {
                $ret['status'][0]['code'] = 404;
                $ret['status'][0]['valid'] = false;
                return self::get_errormsg($ret);
            }
            if (!is_readable($template)) {
                $errortext .= "Templatefile $template not readable!!";
                return self::get_errormsg($parserdata, $errortext, 'Template Error');
            }

            $parserdata['const'] = $strings;
            $content = Template::getContent($template, $parserdata);
            $content = do_shortcode($content);
            if (!empty($content)) {
                wp_enqueue_style('rrze-elements');
                wp_enqueue_style('rrze-jobs-css');
     
                return $debugmsg . $content;
            } else {
                $errortext .= "Empty content from template $template";
                return self::get_errormsg($parserdata, $errortext, 'Output Error');
            }
        }

        $errortext = "Unknown shortcode handling";
        return self::get_errormsg($parserdata, $errortext);
    }

    private static function isIPinRange($fromIP, $toIP, $myIP)  {
        $min = ip2long($fromIP);
        $max = ip2long($toIP);
        $needle = ip2long($myIP);

        return (($needle >= $min) and ($needle <= $max));
    }

    public static function isInternAllowed()  {
        $remoteIP = $_SERVER['REMOTE_ADDR'];
        $remoteAdr = gethostbyaddr($remoteIP);

        // TODO: Move this into constant arrays instead of hard codet values
        // IP-Range der Public Displays (dürfen interne Jobs nicht anzeigen): 10.26.24.0/24 und 10.26.25.0/24
        if (self::isIPinRange('10.26.24.0', '10.26.24.24', $remoteIP) || self::isIPinRange('10.26.25.0', '10.26.25.24', $remoteIP)) {
            return false;
        }

        // if user is loggend in and setting "show internal for admins" is chosen
        if (is_user_logged_in() && (self::$options['rrze-jobs-misc_hide_internal_jobs_notforadmins'] == true)) {
            return true;
        }

        // if user surfs within our network (hosts are defined in settings)
        if (!empty(self::$options['rrze-jobs-misc_hide_internal_jobs_required_hosts'])) {
            $required_hosts = trim(self::$options['rrze-jobs-misc_hide_internal_jobs_required_hosts']);
            $aAllowedHosts = preg_split("/[\s,\n]+/", $required_hosts);
            $ret = false;
            foreach ($aAllowedHosts as $host) {
                if ((strpos($remoteAdr, $host) !== false)) {
                    $ret = true;
                    break;
                }
            }

            return $ret;
        }

        return false;
    }

    // check if the position is internal and has to be ignored by current user host
    /* IP-Range der Public Displays (dürfen interne Jobs nicht anzeigen): 10.26.24.0/24 und 10.26.25.0/24
     */
    private function hideinternal($data)
    {
        if ((isset($data['_provider-values']['intern'])) && ($data['_provider-values']['intern'] === true)
            || (isset($data['intern']) && $data['intern'] == '1')) {

            return !self::isInternAllowed();

        }
        return false;
    }
    /*
     * Erroroutput for Shortcode calls
     */

    public static function get_errormsg($parserdata, $text = '', $title = '')
    {

        $parserdata['errormsg'] = $parserdata['errorcode'] = $parserdata['errortitle'] = '';

        foreach ($parserdata['status'] as $provider) {

            $errortextfield = 'rrze-jobs-labels_job_errortext_' . $provider['code'];
            if (isset(self::$options[$errortextfield])) {
                $errormsg = self::$options[$errortextfield];
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
        //  if (!empty(self::$options['rrze-jobs-labels_job_errortext_display']))  {
        if ((empty($parserdata['errormsg'])) || (empty(self::$options['rrze-jobs-labels_job_errortext_display']))) {
            $content = "<!--  ";
            $content .= " Code: " . $parserdata['errorcode'] . "; Msg: " . $parserdata['errormsg'];
            $content .= " -->";

            return $content;
        }

        $template = plugin()->getPath() . 'Templates/Shortcodes/error.html';
        $content = Template::getContent($template, $parserdata);
        $content = do_shortcode($content);
        //$content .= "FUUUU";
        if (!empty($content)) {
            wp_enqueue_style('rrze-elements');
            wp_enqueue_style('rrze-jobs-css');
            return $content;
        } else {
            $content = "<!-- Error on creating errormessage for shortcode call -->";
            return $content;
        }
    }

    // replace Parse Variables in values itself outside the parser
    public static function ParseDataVars($data)
    {
        $lang = isset($data["_provider-values"]["lang"]) && $data["_provider-values"]["lang"] == 'en' ? 'en' : 'de';
        $searchfields = $data['const'];
        $replacefields = array("description", "qualifications", "disambiguatingDescription", "text_jobnotice");
        foreach ($replacefields as $r) {
            if (isset($data[$r])) {
                foreach ($searchfields as $name => $value) {
                    if ($lang == 'en') {
                        $value = $searchfields[$name.'_en'] ?? $value;
                    }
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
    public static function get_labels()
    {
        if (empty(self::$options)) {
            $settings = new Settings(WP_PLUGIN_DIR . '/rrze-jobs/rrze-jobs.php');
            $settings->onLoaded();
            $options = $settings->getOptions();
        } else {
            $options = self::$options;
        }
        $res = array();
        foreach ($options as $name => $value) {
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
                'name': <?php echo wp_json_encode($this->pluginname); ?>,
                'title': <?php echo wp_json_encode($this->settings['block']['title']); ?>,
                'icon': <?php echo wp_json_encode($this->settings['block']['tinymce_icon']); ?>,
                'shortcode': <?php echo wp_json_encode($shortcode); ?>,
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