<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;
use function RRZE\Jobs\Config\getShortcodeSettings;
use RRZE\Jobs\Job;

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
    public function __construct($settings) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $this->settings = getShortcodeSettings();
        $this->pluginname = $this->settings['block']['blockname'];
        $this->options = $settings->getOptions();
        $this->jobOutput = new Job();
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
        if (file_exists(WP_PLUGIN_DIR . '/rrze-elements/assets/css/rrze-elements.min.css')) {
            wp_register_style('rrze-elements', plugins_url() . '/rrze-elements/assets/css/rrze-elements.min.css');
        }
    }

    private function getDescription(&$map) {
        $description = '';
        $aFields = [
            'job_headline_task' => 'job_description',
            'job_headline_qualifications' => 'job_qualifications',
            'job_headline_qualifications_nth' => 'job_qualifications_nth',
            'job_headline_remarks' => 'job_benefits',
        ];

        switch ($this->provider) {
            case 'bite':
            case 'univis':
                foreach ($aFields as $label => $field) {
                    $description .= (!empty($map[$field]) ? '<h4>' . strip_tags($this->options['rrze-jobs-labels_' . $label]) . '</h4><p>' . $map[$field] . '</p>' : '');
                }

                $description =
                    (!empty($map['job_description_introduction']) ? '<p>' . $map['job_description_introduction'] . '</p>' : '')
                    . ($this->provider == 'bite' && !empty($map['job_description_introduction_added']) ? '<p>' . $map['job_description_introduction_added'] . '</p>' : '')
                    . (!empty($map['job_title']) ? '<h3>' . $map['job_title'] . '</h3>' : '')
                    . $description;
                break;
            case 'interamt':
                $description = !empty($map['job_description']) ? $map['job_description'] : $map['job_title'];
                break;
        }

        $description = str_replace('"', '', $description);

        return $description;
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
        $output = '';
        $this->count = 0;

        $this->setAtts($atts);

        // provider <== attribute or GET-parameter or config
        $this->provider = (!empty($atts['provider']) ? $atts['provider'] : (!empty($_GET['provider']) ? $_GET['provider'] : $this->options['provider']));

        // multi-provider given f.e. "bite, interamt" or "univis,interamt,bite    , unknownProvider"
        $aProvider = explode(',', $this->provider);
        array_walk($aProvider, function (&$val) {
            $val = trim(strtolower(sanitize_text_field($val)));
        });

        foreach ($aProvider as $provider) {
            $this->provider = $provider;
            $this->map_template = $this->jobOutput->getMap($provider);

            // set jobid from attribute jobid or GET-parameter jobid
            $this->jobid = (!empty($atts['jobid']) ? sanitize_text_field($atts['jobid']) : (!empty($_GET['jobid']) ? sanitize_text_field($_GET['jobid']) : 0));

            // orgids => attribute orgids or attribute orgid or fetch from settings page
            $orgids = (!empty($atts['orgids']) ? sanitize_text_field($atts['orgids']) : (!empty($atts['orgid']) ? sanitize_text_field($atts['orgid']) : ''));
	    
	    
            if (!$orgids) {
                if (!empty($this->options['rrze-jobs-access_orgids_' . $provider])) {
                    $orgids = $this->options['rrze-jobs-access_orgids_' .$provider];
                }
            }

            if (!empty($orgids)) {
                $this->aOrgIDs = explode(',', $orgids);
            }

            if ($provider != 'bite' && empty($this->aOrgIDs) && !$this->jobid) {
                return '<p>' . __('Please provide an organisation or job ID!', 'rrze-jobs') . '</p>';
            }

            if (($orgids || $provider == 'bite') && !$this->jobid) {
                $output .= $this->get_all_jobs();
            } else {
                $output = $this->get_single_job();
            }
        }

        wp_enqueue_style('rrze-elements');
        wp_enqueue_style('rrze-jobs-css');

        return $output;
    }

    private function get_sidebar(&$map)  {
        $sidebar = '';
        $application_button_link = '';
        $mailto = '';

        if (!empty($map['application_email'])) {
            $application_button_link = $map['application_email'];
            $mailto = 'mailto:';
        } elseif (!empty($map['application_link']) && strpos($map['application_link'], 'http') !== false) {
            preg_match('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $map['application_link'], $match);
            if (empty($match[0])) {
                $application_button_link = $match[0];
            }
        }

        if (empty($map['employer_district'])) {
            $map['employer_district'] = RRZE_JOBS_ADDRESS_REGION;
        }

        if ($application_button_link != '') {
            $sidebar .= do_shortcode('<div>[button link="' . $mailto . $application_button_link . '" width="full"]' . $this->options['rrze-jobs-labels_sidebar_application_button'] . '[/button]</div>');
        }

        $sidebar .= '<div class="rrze-jobs-single-application"><dl>';
        if (!empty($map['application_end'])) {
            $sidebar .= '<dt>' . __('Application deadline', 'rrze-jobs') . '</dt>'
                . '<dd itemprop="validThrough" content="' . $map['application_end'] . '">' . $map['application_end'] . '</dd>';
        }
        if (!empty($map['job_type'])) {
            $sidebar .= '<dt>' . __('Reference', 'rrze-jobs') . '</dt>' . '<dd>' . $map['job_type'] . '</dd>';
        }

        if (!empty($map['application_link']) && $this->options['rrze-jobs-labels_sidebar_show_application_link'] == 'on') {
            $sidebar .= '<dt>' . $this->options['rrze-jobs-labels_sidebar_headline_application'] . '</dt>';
            $sidebar .= '<dd>' . $map['application_link'] . '</dd>';
        }
        $sidebar .= '</dl></div>';
        $sidebar .= '<div class="rrze-jobs-single-keyfacts"><dl>';
        $sidebar .= '<h3>' . __('Details', 'rrze-jobs') . '</h3>'
        . '<dt>' . __('Job title', 'rrze-jobs') . '</dt><dd itemprop="title">' . $map['job_title'] . '</dd>';
        if (!empty($map['job_start'])) {
            $sidebar .= '<dt>' . __('Job start', 'rrze-jobs') . '</dt><dd itemprop="jobStartDate">' . $map['job_start'] . '</dd>';
        } elseif ($this->provider == 'bite') {
            // BITE delivers no field named "job_start" if "n??chstm??glichen Zeitpunkt" is meant
            $sidebar .= '<dt>' . __('Job start', 'rrze-jobs') . '</dt><dd itemprop="jobStartDate">' . __('n??chstm??glichen Zeitpunkt', 'rrze_jobs') . '</dd>';
        }
        $sidebar .= '<dt>' . __('Deployment location', 'rrze-jobs') . '</dt>';
        if (!empty($map['employer_organization'])) {
            $sidebar .= '<dd itemprop="hiringOrganization" itemscope itemtype="http://schema.org/Organization">' . $map['employer_organization'] . '<br />';
        }
        if (!empty($map['employer_street'])) {
            $sidebar .= $map['employer_street'] . '<br />';
        }
        if (!empty($map['employer_postalcode'])) {
            $sidebar .= $map['employer_postalcode'] . ' ';
        }
        if (!empty($map['employer_organization'])) {
            $sidebar .= '<meta itemprop="name" content="' . $map['employer_organization'] . '" />
            <meta itemprop="logo" content="' . $this->logo_url . '" />';
        }
        $sidebar .= '</dd>';
        $sidebar .= '<span itemprop="jobLocation" itemscope itemtype="http://schema.org/Place" >'
        . '<meta itemprop="logo" content="' . $this->logo_url . '" />'
            . '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';
        if (!empty($map['employer_organization'])) {
            $sidebar .= '<meta itemprop="name" content="' . $map['employer_organization'] . '" />';
        }
        $sidebar .= (!empty($map['contact_street']) ? '<meta itemprop="streetAddress" content="' . $map['contact_street'] . '" />' : '');
        $sidebar .= (!empty($map['contact_postalcode']) ? '<meta itemprop="postalCode" content="' . $map['contact_postalcode'] . '" />' : '');
        $sidebar .= (!empty($map['contact_city']) ? '<meta itemprop="addressLocality" content="' . $map['contact_city'] . '" />' : '');
        $sidebar .= (!empty($map['employer_district']) ? '<meta itemprop="addressRegion" content="' . $map['employer_district'] . '" />' : '');
        $sidebar .= (!empty($map['contact_link']) ? '<meta itemprop="url" content="' . $map['contact_link'] . '" />' : '');
        $sidebar .= '</span></span>';

        if (!empty($map['job_salary'])) {
            $sidebar .= '<dt>' . __('Payment', 'rrze-jobs') . '</dt><dd itemprop="estimatedSalary">' . $map['job_salary'] . '</dd>';
        }
        if (!empty($map['job_employmenttype_txt'])) {
            $sidebar .= '<dt>' . __('Part-time / full-time', 'rrze-jobs') . '</dt><dd>' . $map['job_employmenttype_txt'] . '</dd><meta itemprop="employmentType" content="' . $map['job_employmenttype_schema'] . '" /></dd>';
        }

        if (!empty($map['job_workhours'])) {
            if (is_string($map['job_workhours']) === false) {
                $map['job_workhours'] = floatval(str_replace(',', '.', $map['job_workhours'])) . ' h';
            }
            $sidebar .= '<dt>' . __('Weekly working hours', 'rrze-jobs') . '</dt><dd itemprop="workHours">' . $map['job_workhours'] . '</dd>';
        }

        if ((!empty($map['job_limitation']) && $map['job_limitation'] == 'befristet') || (!empty($map['job_limitation_duration']))) {
            $map['job_limitation_duration'] .= (is_numeric($map['job_limitation_duration']) ? ' ' . __('Months', 'rrze-jobs') : '');
            $sidebar .= '<dt>' . __('Limitation', 'rrze-jobs') . '</dt><dd>' . $map['job_limitation_duration'] . '</dd>';
            $sidebar .= (!empty($map['job_limitation_reason']) ? '<dt>' . __('Reason for the limitation', 'rrze-jobs') . '</dt><dd>' . $map['job_limitation_reason'] . '</dd>' : '');
        }

        if (!empty($map['contact_lastname'])) {
            $sidebar .= '<dt>' . __('Contact for further information', 'rrze-jobs') . '</dt>'
                . '<dd>' . (!empty($map['contact_title']) ? $map['contact_title'] . ' ' : '') . (!empty($map['contact_firstname']) ? $map['contact_firstname'] . ' ' : '') . (!empty($map['contact_lastname']) ? $map['contact_lastname'] : '');
            if ((!empty($map['contact_tel']))) {
                $sidebar .= '<br />' . __('Phone', 'rrze-jobs') . ': ' . $map['contact_tel'];
            }
            if (!empty($map['contact_mobile'])) {
                $sidebar .= '<br />' . __('Mobile', 'rrze-jobs') . ': ' . $map['contact_mobile'];
            }
            if (!empty($map['contact_email'])) {
                $sidebar .= '<br />' . __('E-Mail', 'rrze-jobs') . ': <a href="mailto:' . $map['contact_email'] . '">' . $map['contact_email'] . '</a>';
            }
            $sidebar .= '</dd>';
        }
        $sidebar .= '</dl>';

        $sidebar .= '<div><meta itemprop="datePosted" content="' . $map['job_date_posted'] . '" />'
        . (!empty($map['job_education']) ? '<meta itemprop="educationRequirements" content="' . wp_strip_all_tags($map['job_education']) . '" />' : '')
        . (!empty($map['job_unit']) ? '<meta itemprop="employmentUnit" content="' . wp_strip_all_tags($map['job_unit']) . '" />' : '')
        . (!empty($map['job_experience']) ? '<meta itemprop="experienceRequirements" content="' . wp_strip_all_tags($map['job_experience']) . '" />' : '')
        . (!empty($map['job_benefits']) ? '<meta itemprop="jobBenefits" content="' . wp_strip_all_tags($map['job_benefits']) . '" />' : '')
        . (!empty($map['job_category']) ? '<meta itemprop="occupationalCategory" content="' . wp_strip_all_tags($map['job_category']) . '" />' : '')
        . (!empty($map['job_qualifications']) ? '<meta itemprop="qualifications" content="' . wp_strip_all_tags($map['job_qualifications']) . '" />' : '')
        . '<meta itemprop="url" content="' . get_permalink() . '?jobid=' . $map['job_id'] . '" />'
            . '</div>';
        $sidebar .= '</div>';

        return $sidebar;
    }

    private static function checkDates(&$content)  {
        if (empty($content['channels']['channel0']['from']) || empty($content['channels']['channel0']['to'])) {
            return true;
        }
        $now = date("Y-m-d H:i:s");
        $from = date("Y-m-d H:i:s", strtotime($content['channels']['channel0']['from'])); // 2022-01-19T23:01:00+00:00
        $to = date("Y-m-d H:i:s", strtotime($content['channels']['channel0']['to'])); // 2022-01-21T23:01:00+00:00

        if (($from <= $now) && ($to >= $now)) {
            return true;
        } else {
            return false;
        }
    }

    private static function isValid(&$content) {

        if (isset($content['active'])) {
            if ($content['active']) {
                return self::checkDates($content);
            } else {
                return false;
            }
        } else {
            return self::checkDates($content);
        }
    }

    private function getResponse($sType, $sParam = null) {
        $aRet = [
            'valid' => false,
            'content' => '',
        ];

        $aGetArgs = [];

        if (($this->provider == 'bite') && (!empty($this->options['rrze-jobs-access_apiKey']))) {
            $aGetArgs = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'BAPI-Token' => $this->options['rrze-jobs-access_apiKey'],
                ],
		'sslverify' => true
            ];
        }

        $api_url = $this->jobOutput->getURL($this->provider, $sType) . $sParam;	
	$remote_get    = wp_safe_remote_get( $api_url, $aGetArgs);
	
	if ( is_wp_error( $remote_get ) ) {	
		 $aRet = [
                    'valid' => false,
                    'content' => '<p>' . __('Error', 'rrze_jobs') . ' ' . $remote_get->get_error_message() . '</p>',
                ];
		return $aRet;
         } else {
		$content = json_decode($remote_get["body"], true);
	  }
	

      

        if ($this->provider == 'bite') {
            if (!empty($content['code'])) {
                $aRet = [
                    'valid' => false,
                    'content' => '<p>' . __('Error', 'rrze_jobs') . ' ' . $content['code'] . ' : ' . $content['type'] . ' - ' . $content['message'] . '</p>',
                ];
            } elseif (self::isValid($content)) {
                $aRet = [
                    'valid' => true,
                    'content' => $content,
                ];
            } else {
                $aRet = [
                    'valid' => false,
                    'content' => '<p>' . __('This job offer is not available', 'rrze-jobs') . '</p>',
                ];
            }
        } else {
            if (!$content) {
                $aRet = [
                    'valid' => false,
                    'content' => '<p>' . ($sType == 'single' ? __('This job offer is not available', 'rrze-jobs') : __('Cannot connect to API at the moment.', 'rrze-jobs')) . '</p>',
                ];
            } else {
                $aRet = [
                    'valid' => true,
                    'content' => $content,
                ];
            }
        }

        return $aRet;
    }

    private function makeLink(&$item, $key)  {
        $item = '<li class=".rrze-jobs-bite-li"><a class=".rrze-jobs-bite-link" href="' . $item . '">' . $key . '</a></li>';
    }

    private function get_all_jobs($debug = false)  {
        $output = '';
        $aResponseByAPI = [];
        $aPersons = [];
        $aMaps = [];

        // BITE
        if ($this->provider == 'bite') {
            if ($this->link_only) {
                // return just as a list of links to jobpostings
                // 1. get JobsIDs
                $aResponseByAPI = $this->getResponse('list');
	//	echo "LISTOUT: ". Helper::get_html_var_dump($aResponseByAPI);
		
                if (!$aResponseByAPI['valid']) {
                    return $aResponseByAPI['content'];
                }

                $aLink = [];

                foreach ($aResponseByAPI['content']['entries'] as $entry) {
                    $aResponseByAPI = $this->getResponse('single', $entry['id']);
                    if (!$aResponseByAPI['valid']) {
                        // let's skip this entry, there might be valid ones
                        continue;
                    }

                    $job_title = $aResponseByAPI['content']['title'];
                    $link = $aResponseByAPI['content']['channels']['channel0']['route']['posting'];
                    $aLink[$job_title] = $link;
                }

                if (!empty($aLink)) {
                    array_walk($aLink, [$this, 'makeLink']);
                    return '<ul class=".rrze-jobs-bite-ul">' . implode('', $aLink) . '</ul>';
                } else {
                    if (!empty($this->options['rrze-jobs-labels_no_jobs_message'])) {
                        return '<p>' . $this->options['rrze-jobs-labels_no_jobs_message'] . '</a></p>';
                    }
                }
            } else {
                // 1. get JobsIDs
                $aResponseByAPIJobIDs = $this->getResponse('list');
	//    echo "LISTOUT: ". Helper::get_html_var_dump($aResponseByAPIJobIDs);
                if (!$aResponseByAPIJobIDs['valid']) {
                    return $aResponseByAPIJobIDs['content'];
                }

                foreach ($aResponseByAPIJobIDs['content']['entries'] as $entry) {
                    // 2. get actual job
                    $aResponseByAPI = $this->getResponse('single', $entry['id']);
	//	    echo "SINGLEOUT: ". Helper::get_html_var_dump($aResponseByAPI);
                    if (!$aResponseByAPI['valid']) {
                        // let's skip this entry, there might be valid ones
                        continue;
                    }

                    $aJob = $this->jobOutput->fillMap($this->provider, $this->map_template, $aResponseByAPI['content'], $aPersons, $this->internal, $this->options);
                    if ($aJob['valid']) {
                        $aMaps[] = $aJob['data'];
                    }
                }
            }
        } else {
            // Interamt + UnivIS
            foreach ($this->aOrgIDs as $orgid) {
                $orgid = trim($orgid);

                // Check if orgid is an integer and ignore if not (we don't output a message because there might be more than one orgid) - fun-fact: Interamt delivers their complete database entries if orgid contains characters
                if (strval($orgid) !== strval(intval($orgid))) {
                    continue;
                }

                $aResponseByAPI = $this->getResponse('list', $orgid);

                if (!$aResponseByAPI['valid']) {
                    return $aResponseByAPI['content'];
                }

                if ($this->provider == 'interamt') {
                    $node = 'Stellenangebote';
                } elseif ($this->provider = 'univis') {
                    $node = 'Position';
                    if (!empty($aResponseByAPI['content']['Person'])) {
                        $aPersons = $this->jobOutput->getUnivisPersons($aResponseByAPI['content']['Person']);
                    }
                }

                switch ($this->provider) {
                    case 'interamt':
                    case 'univis':
                        if (empty($aResponseByAPI['content'][$node])) {
                            continue 2; // continue the foreach loop
                        }
                        $aRawData = $aResponseByAPI['content'][$node];
                        break;
                    case 'bite':
                        $aRawData = $aResponseByAPI['content'];
                        break;
                }

                // Loop through jobs
                if (!empty($aRawData)) {
                    foreach ($aRawData as $aSingleRawData) {
                        if ($this->provider == 'interamt') {
                            // Interamt is different: 1. call returns IDs, 2. call fetches data for single job by ID
                            $aResponseByAPI = $this->getResponse('single', $aSingleRawData['Id']);
                            if ($aResponseByAPI['valid']) {
                                $aJobRawData = $aResponseByAPI['content'];
                            }
                        } else {
                            // UnivIS
                            $aJobRawData = $aSingleRawData;
                        }

                        $aJob = $this->jobOutput->fillMap($this->provider, $this->map_template, $aJobRawData, $aPersons, $this->internal, $this->options);

                        if ($aJob['valid']) {
                            $aMaps[] = $aJob['data'];
                        }
                    }
                }
            }
        }

        /*
         * Ausgabe f??r Public Displays
         *
         * $_GET['job] defines, which job in the list is to be shown, f.e. ?format=embedded&job=2 shows the second job, if there is one otherwise an image
         */

        if (!empty($_GET['format']) && ($_GET['format'] == 'embedded') && !empty($_GET['job'])) {
            $jobnr = (int) $_GET['job'] - 1;

            usort($aMaps, function ($a, $b) {
                return strcmp($a['job_id'], $b['job_id']);
            });

            if ((count($aMaps) > 0) && (!empty($aMaps[$jobnr]['job_id']))) {
                $this->jobid = $aMaps[$jobnr]['job_id'];
                return $this->get_single_job();
            } else {
                return '<img src="' . plugin_dir_url(__DIR__) . 'assets/img/jobs-rrze-517x120.png" class="default-image">';
            }
            return;
        }

        /*
         * Normale Ausgabe
         */
        if (count($aMaps) > 0) {
            // check if orderby contains a valid fieldname
            if (!empty($this->orderby) && !array_key_exists($this->orderby, $this->map_template)) {
                $correct_vals = implode(', ', array_keys($this->map_template));
                return '<p>' . __('Parameter "orderby" is not correct. Please use one of the following values: ', 'rrze-jobs') . $correct_vals;
            }

            $aMaps = $this->sortArrayByField($aMaps, $this->orderby, $this->order);

            $shortcode_items = '';
            foreach ($aMaps as $map) {
                // If parameter "limit" is reached stop output
                if (($this->limit > 0) && ($this->count >= $this->limit)) {
                    break 1;
                }

                $shortcode_item_inner = '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
                $shortcode_item_inner .= do_shortcode('[three_columns_two]<div itemprop="description">' . $this->getDescription($map) . '</div>[/three_columns_two]' . '[three_columns_one_last]' . $this->get_sidebar($map) . '[/three_columns_one_last][divider]');

                if (!empty($this->options['rrze-jobs-labels_job_notice'])) {
                    $shortcode_item_inner .= '<hr /><div>' . strip_tags($this->options['rrze-jobs-labels_job_notice'], '<p><a><br><br /><b><strong><i><em>') . '</div>';
                }

                $shortcode_item_inner .= '</div>';
                $shortcode_items .= do_shortcode('[collapse title="' . $map['job_title'] . '" name="' . substr($this->provider, 0, 1) . $map['job_id'] . '"]' . $shortcode_item_inner . '[/collapse]');
                $this->count++;
            }
        }

        if (!empty($_GET['format']) && $_GET['format'] == 'embedded') {
            if (count($aMaps) > 0) {
                return $this->getPublicDisplayList($aMaps);
            } else {
                return '<img src="' . plugin_dir_url(__DIR__) . 'assets/img/jobs-rrze-517x120.png" class="default-image">';
            }
        }

        if ($this->count == 0) {
            if (!empty($this->options['rrze-jobs-labels_no_jobs_message'])) {
                return '<p>' . $this->options['rrze-jobs-labels_no_jobs_message'] . '</a></p>';
            } else {
                return '<p>' . __('API does not return any data.', 'rrze-jobs') . '</a></p>';
            }
        }

        return do_shortcode('[collapsibles expand-all-link="true"]' . $shortcode_items . '[/collapsibles]');
    }

    public function get_single_job()  {
        $output = '';
        $aPersons = [];
        $aResponseByAPI = $this->getResponse('single', $this->jobid);

        if (!$aResponseByAPI['valid']) {
            return $aResponseByAPI['content'];
        }

        switch ($this->provider) {
            case 'bite':
            case 'interamt':
                $job = $aResponseByAPI['content'];
                break;
            case 'univis':
                $job = $aResponseByAPI['content']['Position'][0];
                $aPersons = $this->jobOutput->getUnivisPersons($aResponseByAPI['content']['Person']);
                $aPersons = $aPersons[$job['acontact']];
                break;
        }

        // job not found => exit
        if (empty($job)) {
            return '<p>' . __('This job offer is not available', 'rrze-jobs') . '</p>';
        }

        $map = $this->jobOutput->fillMap($this->provider, $this->map_template, $job, $aPersons, $this->internal, $this->options);

        if (empty($map) || !$map['valid']) {
            return '<p>' . __('This job offer is not available', 'rrze-jobs') . '</p>';
        }else{
            $map = $map['data'];
        }

        if ($this->provider == 'univis') {
            foreach ($aPersons as $key => $val) {
                $map[$key] = $val;
            }
        }

        $description = $this->getDescription($map);
        $description = '<div itemprop="description" class="rrze-jobs-single-description">' . $description . '</div>';

        $output = '';
        $output .= '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
        $output .= do_shortcode('[three_columns_two]' . $description . '[/three_columns_two]' . '[three_columns_one_last]' . $this->get_sidebar($map) . '[/three_columns_one_last][divider]');

        // display job_notice
        if (empty($_GET['format']) && !empty($this->options['rrze-jobs-labels_job_notice'])) {
            $output .= '<hr /><div>' . strip_tags($this->options['rrze-jobs-labels_job_notice'], '<p><a><br><br /><b><strong><i><em>') . '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    /*
     * getPublicDisplayList
     *      returns '' if there is no job or more than 3
     *      2 jobs in 2 column HTML
     *      3 jobs in 3 column HTML
     */
    private function getPublicDisplayList($aMaps = [])
    {
        $output = '';
        $last = '';

        $jobs_page_url = get_permalink($this->options['rrze-jobs-labels_jobs_page']);

        foreach ($aMaps as $k => $map) {

            // if there are more than 3 jobs return ''
            if ($k > 2) {
                return $output;
            }
            $result = [];
            preg_match('/<ul\>(.*?)<\/ul\>/m', $map["job_description"], $result);
            if (!empty($result)) {
                $teaser = '<h3>' . __('Tasks', 'rrze-jobs') . ':</h3><ul class="job-tasks">' . $result[1] . '</ul>';
            } else {
                preg_match('/<p\>(.*?)<\/p\>/m', $map["job_description"], $result);
                if (!empty($result)) {
                    $teaser = '<h3>' . __('Tasks', 'rrze-jobs') . ':</h3><p class="job-tasks">' . $result[1] . '</p>';
                } else {
                    $teaser = $map["job_description"];
                }
            }

            $job_item = "<h2>" . $map['job_title'] . "</h2>";
            $job_item .= $teaser;
            $job_item .= '<p>';
            if (!empty($map['job_start'])) {
                $job_item .= '<span class="label">' . __('Job start', 'rrze-jobs') . ':</span> ' . $map['job_start'];
            }
            if (!empty($map['application_end'])) {
                $job_item .= '<br /><span class="label">' . __('Application deadline', 'rrze-jobs') . ':</span> ' . $map['application_end'];
            }
            if (!empty($map['job_employmenttype_txt'])) {
                $job_item .= '<br /><span class="label">' . __('Part-time / full-time', 'rrze-jobs') . ':</span> ' . $map['job_employmenttype_txt'];
            }
            if (!empty($map['job_salary'])) {
                $job_item .= '<br /><span class="label">' . __('Payment', 'rrze-jobs') . '</span>: ' . $map['job_salary'];
            }

            $job_item .= '<p><img src="' . plugin_dir_url(__FILE__) . 'qrcode.php?url=' . $jobs_page_url . '&collapse=' . substr($this->provider, 0, 1) . $map['job_id'] . '"></p>';

            if ($k == (count($aMaps) - 1) || $k > 1) {
                $last = '_last';
            }
            switch (count($aMaps)) {
                case 1:
                    $output .= $job_item;
                    break;
                case 2:
                    $output .= do_shortcode('[two_columns_one' . $last . ']' . $job_item . '[/two_columns_one' . $last . ']');
                    break;
                default:
                    $output .= do_shortcode('[three_columns_one' . $last . ']' . $job_item . '[/three_columns_one' . $last . ']');
                    break;
            }
        }

        return $output;
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
