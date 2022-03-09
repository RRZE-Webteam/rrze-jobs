<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;
use function RRZE\Jobs\Config\getShortcodeSettings;
use RRZE\Jobs\Job;

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

    private $limit;
    private $orderby;
    private $order;
    private $internal;
    private $fallback_apply = '';
    private $link_only;

    /**
     * Shortcode-Klasse wird instanziiert.
     */
    public function __construct($settings)
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $this->settings = getShortcodeSettings();
        $this->pluginname = $this->settings['block']['blockname'];
        $this->options = $settings->getOptions();
        $this->jobOutput = new Job();
        add_action('init', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueGutenberg']);
        add_action('init', [$this, 'initGutenberg']);
        add_action('admin_head', [$this, 'setMCEConfig']);
        add_filter('mce_external_plugins', [$this, 'addMCEButtons']);
        if (!is_plugin_active('fau-jobportal/fau-jobportal.php')) {
            add_shortcode('jobs', [$this, 'shortcodeOutput'], 10, 2);
        }
    }

    /**
     * Enqueue der Skripte.
     */
    public function enqueue_scripts()
    {
        wp_register_style('rrze-jobs-css', plugins_url('assets/css/rrze-jobs.css', plugin_basename(RRZE_PLUGIN_FILE)));
        if (file_exists(WP_PLUGIN_DIR . '/rrze-elements/assets/css/rrze-elements.min.css')) {
            wp_register_style('rrze-elements', plugins_url() . '/rrze-elements/assets/css/rrze-elements.min.css');
        }
    }

    private function getSalary(&$map)
    {
        $salary = '';
        if (isset($map['job_salary_to'])) {
            if (isset($map['job_salary_from']) && ($map['job_salary_from'] != $map['job_salary_to'])) {
                $salary = $map['job_salary_from'] . ' - ' . $map['job_salary_to'];
            } else {
                $salary = $map['job_salary_to'];
            }
        } elseif (isset($map['job_salary_from'])) {
            $salary = $map['job_salary_from'];
        }
        if (($this->provider == 'bite') && !empty($map['job_salary_type'])) {
            $salary = 'TV-L ' . $map['job_salary_type'] . $salary;
        }
        return $salary;
    }

    private function getEmploymentType(&$map)
    {
        $aEmploymentType = [
            'txt' => '',
            'schema' => '',
        ];

        switch ($this->provider) {
            case 'bite';
                $aTmp = explode(' ', $map['job_employmenttype']);
                $aEmploymentType['schema'] = strtoupper($aTmp[0]);
                if (!empty($aTmp[1])) {
                    $aEmploymentType['schema'] .= ' TEMPORARY';
                }
                if ($aEmploymentType['schema'] == 'FULL_TIME') {
                    $aEmploymentType['txt'] = 'Vollzeit';
                }
                break;
            case 'univis':
            case 'interamt':
                $aEmploymentType['txt'] = $map['job_employmenttype'];
                $aEmploymentType['schema'] = 'FULL_TIME';
                if ($aEmploymentType['txt'] != 'Vollzeit') {
                    $aEmploymentType['schema'] = 'PART_TIME';
                }
                if (!empty($map['job_limitation']) && $map['job_limitation'] != 'unbef') {
                    $aEmploymentType['schema'] .= ' TEMPORARY';
                }
                break;
        }
        return $aEmploymentType;
    }

    private function getLabelsField($field, $label, $map){


        if (empty($map[$field])) echo $field . '<br>';

    

        echo 'here<pre>';
        var_dump($map);
        exit;

        return (!empty($map[$field]) ? '<h4>' . strip_tags($this->options['rrze-jobs-labels_' . $label]) . '</h4><p>' . $map[$field] . '</p>' : '');
    }

    private function getDescription(&$map)
    {
        $description = '';
        $aFields = [
            'job_headline_task' => 'job_description',
            'job_headline_qualifications' => 'job_qualifications',
            'job_headline_qualifications_nth' => 'job_qualifications_nth',
            'job_headline_remarks' => 'job_benefits',
            'job_headline_qualifications' => 'application_link',
        ];

        switch ($this->provider) {
            case 'bite':
            case 'univis':
                $description =
                    (isset($map['job_description_introduction']) ? '<p>' . $map['job_description_introduction'] . '</p>' : '')
                    . ($this->provider == 'bite' && isset($map['job_description_introduction_added']) ? '<p>' . $map['job_description_introduction_added'] . '</p>' : '')
                    . (isset($map['job_title']) ? '<h3>' . $map['job_title'] . '</h3>' : '')
                    . array_walk($aFields, [$this, 'getLabelsField'], $map);
                break;
            case 'interamt':
                $description = isset($map['job_description']) ? $map['job_description'] : $map['job_title'];
                break;
                exit;
        }

        // echo '<pre>';
        // var_dump($map);
        // exit;

        return $description;
    }

    private function transform_date($mydate)
    {
        return date('Y-m-d', strtotime($mydate));
    }

    private function sortArrayByField($myArray, $fieldname, $order)
    {
        if (!empty($this->order)){
            usort($myArray, function ($a, $b) use ($fieldname, $order) {
                return ($this->order == 'ASC' ? strtolower($a[$fieldname]) <=> strtolower($b[$fieldname]) : strtolower($b[$fieldname]) <=> strtolower($a[$fieldname]));
            });
        }
        return $myArray;
    }

    private function setAtts(&$atts){
        $allAtts = [
            'limit',
            'internal',
            'orderby',
            'order',
            'fallback_apply',
            'link_only',
        ];

        foreach($allAtts as $val){
            $this->val = (!empty($atts[$val]) ? $atts[$val] : $this->settings[$val]['default']);
        }
    }

    public function shortcodeOutput($atts)
    {
        $output = '';
        $this->count = 0;

        // make variables for all(!) attributes
        $this->setAtts($atts);

        // provider <== attribute provider or GET-parameter provider or default from shortcode settings
        $this->provider = (!empty($atts['provider']) ? $atts['provider'] : (!empty($_GET['provider']) ? $_GET['provider'] : $this->settings['provider']['default']));

        // multi-provider given f.e. "bite, interamt" or "univis,interamt,bite    , unknownProvider"
        $aProvider = explode(',', $this->provider);
        array_walk($aProvider, function (&$val){
            $val = trim(strtolower(sanitize_text_field($val)));
        });

        foreach($aProvider as $this->provider){
            $this->provider = $this->provider;
    
            $this->map_template = $this->jobOutput->getMap($this->provider);

            // set jobid from attribute jobid or GET-parameter jobid
            $this->jobid = (!empty($atts['jobid']) ? sanitize_text_field($atts['jobid']) : (!empty($_GET['jobid']) ? sanitize_text_field($_GET['jobid']) : 0));

            // orgids => attribute orgids or attribute orgid or fetch from settings page
            $orgids = (!empty($atts['orgids']) ? sanitize_text_field($atts['orgids']) : (!empty($atts['orgid']) ? sanitize_text_field($atts['orgid']) : ''));
            if (!$orgids) {
                if (isset($this->options['rrze-jobs-access' . '_orgids_' . $this->provider])) {
                    $orgids = $this->options['rrze-jobs-access' . '_orgids_' . $this->provider];
                }
            }

            if (!empty($orgids)) {
                $this->aOrgIDs = explode(',', $orgids);
            }

            if ($this->provider != 'bite' && empty($this->aOrgIDs) && !$this->jobid) {
                return '<p>' . __('Please provide an organisation or job ID!', 'rrze-jobs') . '</p>';
            }

            if (($orgids || $this->provider == 'bite') && !$this->jobid) {
                $output .= $this->get_all_jobs();
            } else {
                $output = $this->get_single_job();
            }
        }

        wp_enqueue_style('rrze-elements');
        wp_enqueue_style('rrze-jobs-css');

        return $output;
    }

    private function get_sidebar(&$map, &$logo_url)
    {
        $sidebar = '';
        $application_button_link = '';
        $mailto = '';

        if (isset($map['application_email'])) {
            $application_button_link = $map['application_email'];
            $mailto = 'mailto:';
        } elseif (isset($map['application_link']) && strpos($map['application_link'], 'http') !== false) {
            preg_match('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $map['application_link'], $match);
            if (isset($match[0])) {
                $application_button_link = $match[0];
            }
        }

        if (!isset($map['employer_district']) || $map['employer_district'] == '') {
            $map['employer_district'] = RRZE_JOBS_ADDRESS_REGION;
        }

        if ($application_button_link != '') {
            $sidebar .= do_shortcode('<div>[button link="' . $mailto . $application_button_link . '" width="full"]' . $this->options['rrze-jobs-labels_sidebar_application_button'] . '[/button]</div>');
        }

        $sidebar .= '<div class="rrze-jobs-single-application"><dl>';
        if (isset($map['application_end'])) {
            $sidebar .= '<dt>' . __('Application deadline', 'rrze-jobs') . '</dt>'
            . '<dd itemprop="validThrough" content="' . $map['application_end'] . '">' . $map['application_end'] . '</dd>';
        }
        if (isset($map['job_type'])) {
            $sidebar .= '<dt>' . __('Reference', 'rrze-jobs') . '</dt>' . '<dd>' . $map['job_type'] . '</dd>';
        }
        if (isset($map['application_link']) && $this->options['rrze-jobs-labels_sidebar_show_application_link']) {
            $sidebar .= '<dt>' . $this->options['rrze-jobs-labels_sidebar_headline_application'] . '</dt>';
            $sidebar .= '<dd>' . $map['application_link'] . '</dd>';
        }
        $sidebar .= '</dl></div>';
        $sidebar .= '<div class="rrze-jobs-single-keyfacts"><dl>';
        $sidebar .= '<h3>' . __('Details', 'rrze-jobs') . '</h3>'
        . '<dt>' . __('Job title', 'rrze-jobs') . '</dt><dd itemprop="title">' . $map['job_title'] . '</dd>';
        if ((isset($map['job_start'])) && ($map['job_start'] != '')) {
            $sidebar .= '<dt>' . __('Job start', 'rrze-jobs') . '</dt><dd itemprop="jobStartDate">' . $map['job_start'] . '</dd>';
        } elseif ($this->provider == 'bite') {
            // BITE delivers no field named "job_start" if "nächstmöglichen Zeitpunkt" is meant
            $sidebar .= '<dt>' . __('Job start', 'rrze-jobs') . '</dt><dd itemprop="jobStartDate">' . __('nächstmöglichen Zeitpunkt', 'rrze_jobs') . '</dd>';
        }
        $sidebar .= '<dt>' . __('Deployment location', 'rrze-jobs') . '</dt>';
        if (isset($map['employer_organization'])) {
            $sidebar .= '<dd itemprop="hiringOrganization" itemscope itemtype="http://schema.org/Organization">' . $map['employer_organization'] . '<br />';
        }
        if (isset($map['employer_street'])) {
            $sidebar .= $map['employer_street'] . '<br />';
        }
        if (isset($map['employer_postalcode'])) {
            $sidebar .= $map['employer_postalcode'] . ' ';
        }
        if (!empty($map['employer_organization'])) {
            $sidebar .= '<meta itemprop="name" content="' . $map['employer_organization'] . '" />
            <meta itemprop="logo" content="' . $logo_url . '" />';
        }
        $sidebar .= '</dd>';
        $sidebar .= '<span itemprop="jobLocation" itemscope itemtype="http://schema.org/Place" >'
            . '<meta itemprop="logo" content="' . $logo_url . '" />'
            . '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';
        if (!empty($map['employer_organization'])) {
            $sidebar .= '<meta itemprop="name" content="' . $map['employer_organization'] . '" />';
        }
        $sidebar .= (isset($map['contact_street']) ? '<meta itemprop="streetAddress" content="' . $map['contact_street'] . '" />' : '');
        $sidebar .= (isset($map['contact_postalcode']) ? '<meta itemprop="postalCode" content="' . $map['contact_postalcode'] . '" />' : '');
        $sidebar .= (isset($map['contact_city']) ? '<meta itemprop="addressLocality" content="' . $map['contact_city'] . '" />' : '');
        $sidebar .= (isset($map['employer_district']) ? '<meta itemprop="addressRegion" content="' . $map['employer_district'] . '" />' : '');
        $sidebar .= (isset($map['contact_link']) ? '<meta itemprop="url" content="' . $map['contact_link'] . '" />' : '');
        $sidebar .= '</span></span>';

        $salary = $this->getSalary($map);
        $aEmploymenttype = $this->getEmploymentType($map);

        if ($salary != '') {
            $sidebar .= '<dt>' . __('Payment', 'rrze-jobs') . '</dt><dd itemprop="estimatedSalary">' . $salary . '</dd>';
        }
        if ($aEmploymenttype['txt'] != '') {
            $sidebar .= '<dt>' . __('Part-time / full-time', 'rrze-jobs') . '</dt><dd>' . $aEmploymenttype['txt'] . '</dd><meta itemprop="employmentType" content="' . $aEmploymenttype['schema'] . '" /></dd>';
        }

        if (isset($map['job_workhours'])) {
            if (is_string($map['job_workhours']) === false) {
                $map['job_workhours'] = floatval(str_replace(',', '.', $map['job_workhours'])) . ' h';
            }
            $sidebar .= '<dt>' . __('Weekly working hours', 'rrze-jobs') . '</dt><dd itemprop="workHours">' . $map['job_workhours'] . '</dd>';
        }

        if ((isset($map['job_limitation']) && $map['job_limitation'] == 'befristet') || (isset($map['job_limitation_duration']))) {
            $map['job_limitation_duration'] .= (is_numeric($map['job_limitation_duration']) ? ' ' . __('Months', 'rrze-jobs') : '');
            $sidebar .= '<dt>' . __('Limitation', 'rrze-jobs') . '</dt><dd>' . $map['job_limitation_duration'] . '</dd>';
            if (isset($map['job_limitation_reason'])) {
                switch ($map['job_limitation_reason']) {
                    case 'vertr':
                        $map['job_limitation_reason'] = 'Vertretung';
                        break;
                    case 'schwanger':
                        $map['job_limitation_reason'] = 'Mutterschutzvertretung';
                        break;
                    case 'eltern':
                        $map['job_limitation_reason'] = 'Mutterschutz- / Elternzeitvertretung';
                        break;
                    case 'krankh':
                        $map['job_limitation_reason'] = 'Krankheitsvertretung';
                        break;
                    case 'forsch':
                        $map['job_limitation_reason'] = 'befristetes Forschungsvorhaben';
                        break;
                    case 'zeitb':
                        $map['job_limitation_reason'] = 'Beamtenschaft auf Zeit';
                        break;
                }
                $sidebar .= '<dt>' . __('Reason for the limitation', 'rrze-jobs') . '</dt><dd>' . $map['job_limitation_reason'] . '</dd>';
            }
        }

        if ((isset($map['contact_lastname'])) && ($map['contact_lastname'] != '')) {
            $sidebar .= '<dt>' . __('Contact for further information', 'rrze-jobs') . '</dt>'
                . '<dd>' . (isset($map['contact_title']) ? $map['contact_title'] . ' ' : '') . (isset($map['contact_firstname']) ? $map['contact_firstname'] . ' ' : '') . (isset($map['contact_lastname']) ? $map['contact_lastname'] : '');
            if ((isset($map['contact_tel'])) && ($map['contact_tel'] != '')) {
                $sidebar .= '<br />' . __('Phone', 'rrze-jobs') . ': ' . $map['contact_tel'];
            }
            if ((isset($map['contact_mobile'])) && ($map['contact_mobile'] != '')) {
                $sidebar .= '<br />' . __('Mobile', 'rrze-jobs') . ': ' . $map['contact_mobile'];
            }
            if ((isset($map['contact_email'])) && ($map['contact_email'] != '')) {
                $sidebar .= '<br />' . __('E-Mail', 'rrze-jobs') . ': <a href="mailto:' . $map['contact_email'] . '">' . $map['contact_email'] . '</a>';
            }
            $sidebar .= '</dd>';
        }
        $sidebar .= '</dl>';

        $datePosted = (isset($map['application_start']) ? $this->transform_date($map['application_start']) : date('Y-m-d'));

        $sidebar .= '<div><meta itemprop="datePosted" content="' . $datePosted . '" />'
        . (isset($map['job_education']) ? '<meta itemprop="educationRequirements" content="' . strip_tags(htmlentities($map['job_education'])) . '" />' : '')
        . (isset($map['job_unit']) ? '<meta itemprop="employmentUnit" content="' . $map['job_unit'] . '" />' : '')
        . (isset($map['job_experience']) ? '<meta itemprop="experienceRequirements" content="' . strip_tags(htmlentities($map['job_experience'])) . '" />' : '')
        . (isset($map['job_benefits']) ? '<meta itemprop="jobBenefits" content="' . htmlentities($map['job_benefits']) . '" />' : '')
        . (isset($map['job_category']) ? '<meta itemprop="occupationalCategory" content="' . $map['job_category'] . '" />' : '')
        . '<meta itemprop="qualifications" content="' . (isset($map['job_qualifications']) ? $map['job_qualifications'] : '') . '" />'
        . '<meta itemprop="url" content="' . get_permalink() . '?jobid=' . $map['job_id'] . '" />'
            . '</div>';
        $sidebar .= '</div>';

        return $sidebar;
    }

    private static function checkDates(&$content)
    {
        if (!isset($content['channels']['channel0']['from']) || !isset($content['channels']['channel0']['to'])) {
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

    private static function isValid(&$content)
    {

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

    private function getResponse($sType, $sParam = null)
    {
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
            ];
        }

        $api_url = $this->jobOutput->getURL($this->provider, $sType) . $sParam;

        $content = wp_remote_get($api_url, $aGetArgs);
        $content = $content["body"];

        $content = json_decode($content, true);

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

    private function makeLink(&$item, $key)
    {
        $item = '<li class=".rrze-jobs-bite-li"><a class=".rrze-jobs-bite-link" href="' . $item . '">' . $key . '</a></li>';
    }

    private function get_all_jobs()
    {
        $output = '';
        $aResponseByAPI = [];
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = (has_custom_logo() ? wp_get_attachment_url($custom_logo_id) : RRZE_JOBS_LOGO);
        $intern_allowed = $this->jobOutput->isInternAllowed();
        $aMaps = [];

        // BITE
        if ($this->provider == 'bite') {
            if ($this->link_only) {
                // return just as a list of links to jobpostings
                // 1. get JobsIDs
                $aResponseByAPI = $this->getResponse('list');

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
                    if (isset($this->options['rrze-jobs-labels_no_jobs_message'])) {
                        return '<p>' . $this->options['rrze-jobs_no_jobs_message'] . '</a></p>';
                    }
                }
            } else {
                // 1. get JobsIDs
                $aResponseByAPIJobIDs = $this->getResponse('list');

                if (!$aResponseByAPIJobIDs['valid']) {
                    return $aResponseByAPIJobIDs['content'];
                }

                foreach ($aResponseByAPIJobIDs['content']['entries'] as $entry) {
                    // 2. get actual job
                    $aResponseByAPI = $this->getResponse('single', $entry['id']);
                    if (!$aResponseByAPI['valid']) {
                        // let's skip this entry, there might be valid ones
                        continue;
                    }
                    $aMaps[] = $this->jobOutput->fillMap($this->map_template, $aResponseByAPI['content']);

                    // $job_title = $aResponseByAPI['content']['title']; // does not deliver JOB-TITLE but title for the template
                    // $description = $aResponseByAPI['content']['content']['html'];

                    // $shortcode_item_inner .= '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
                    // $shortcode_item_inner .= do_shortcode('[three_columns_two]<div itemprop="description">' . $description  .'</div>[/three_columns_two]' . '[three_columns_one_last] SIDEBAR in Entwicklung [/three_columns_one_last][divider]');

                    // $shortcode_item_inner .= '</div>';
                    // $shortcode_items .= do_shortcode('[collapse title="' . $job_title . '" name="' . substr($this->provider,0,1) . $entry['id'] . '"]' . $shortcode_item_inner . '[/collapse]');
                }
                // return do_shortcode('[collapsibles expand-all-link="true"]' . $shortcode_items . '[/collapsibles]');;
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

                if ($this->provider == 'univis') {
                    if (!isset($aResponseByAPI['content']['Person'])) {
                        if (isset($this->options['rrze-jobs_no_jobs_message'])) {
                            return '<p>' . $this->options['rrze-jobs_no_jobs_message'] . '</a></p>';
                        } else {
                            return '<p>' . __('API does not return any data.', 'rrze-jobs') . '</a></p>';
                        }
                    }
                    $aPersons = $this->jobOutput->getUnivisPersons($aResponseByAPI['content']['Person']);
                }

                $aJobs = [];

                if ($this->provider == 'interamt') {
                    $node = 'Stellenangebote';
                } elseif ($this->provider = 'univis') {
                    $node = 'Position';
                }

                switch ($this->provider) {
                    case 'interamt':
                    case 'univis':
                        if (empty($aResponseByAPI['content'][$node])) {
                            continue 2; // continue the foreach loop
                        }
                        $aJobs = $aResponseByAPI['content'][$node];
                        break;
                    case 'bite':
                        $aJobs = $aResponseByAPI['content'];
                        break;
                }

                // Loop through jobs
                if (!empty($aJobs)) {
                    foreach ($aJobs as $jobData) {
                        if ($this->provider == 'interamt') {

                            // Interamt is different: 1. call returns IDs, 2. call fetches data for single job by ID
                            $aResponseByAPI = $this->getResponse('single', $jobData['Id']);

                            if (!$aResponseByAPI['valid']) {
                                return $aResponseByAPI['content'];
                            }

                            $singleJob = $this->jobOutput->fillMap($this->map_template, $aResponseByAPI['content']);
                        } else {
                            // UnivIS
                            $singleJob = $this->jobOutput->fillMap($this->map_template, $jobData);
                        }
                        if ($this->provider == 'univis') {

                            // add Person data to each job
                            $personKey = (!empty($jobData['acontact']) ? $jobData['acontact'] : (!empty($jobData['contact']) ? $jobData['contact'] : false));

                            if (!empty($jobData[$personKey]) && !empty($aPersons[$jobData[$personKey]])){
                                foreach ($aPersons[$jobData[$personKey]] as $key => $val) {
                                    $job[$key] = $val;
                                }
                            }
                        }
                
                        if (!empty($singleJob)){
                            $this->jobOutput->cleanData($this->provider, $singleJob, $intern_allowed);
                            $aMaps[] = $singleJob;
                        }
                    }
                }
            }
        }

        /*
         * Ausgabe für Public Displays
         *
         * $_GET['job] defines, which job in the list is to be shown, f.e. ?format=embedded&job=2 shows the second job, if there is one otherwise an image
         */

        if (isset($_GET['format']) && $_GET['format'] == 'embedded' && isset($_GET['job'])) {
            $jobnr = (int) $_GET['job'] - 1;

            usort($aMaps, function ($a, $b) {
                return strcmp($a['job_id'], $b['job_id']);
            });

            if ((count($aMaps) > 0) && (isset($aMaps[$jobnr]['job_id']))) {
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
                $shortcode_item_inner = '';
                // If parameter "limit" is reached stop output
                if (($this->limit > 0) && ($this->count >= $this->limit)) {
                    break 1;
                }

                $description = $this->getDescription($map);
                $description = str_replace('"', '', $description);
                $datePosted = (isset($map['application_start']) ? $this->transform_date($map['application_start']) : date('Y-m-d'));
                $shortcode_item_inner .= '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
                $shortcode_item_inner .= do_shortcode('[three_columns_two]<div itemprop="description">' . $description . '</div>[/three_columns_two]' . '[three_columns_one_last]' . $this->get_sidebar($map, $logo_url) . '[/three_columns_one_last][divider]');

                if (!empty($this->options['rrze-jobs-labels_job_notice'])) {
                    $shortcode_item_inner .= '<hr /><div>' . strip_tags($this->options['rrze-jobs-labels_job_notice'], '<p><a><br><br /><b><strong><i><em>') . '</div>';
                }

                $shortcode_item_inner .= '</div>';
                $shortcode_items .= do_shortcode('[collapse title="' . $map['job_title'] . '" name="' . substr($this->provider, 0, 1) . $map['job_id'] . '"]' . $shortcode_item_inner . '[/collapse]');
                $this->count++;
            }
        }

        if (isset($_GET['format']) && $_GET['format'] == 'embedded') {
            if (count($aMaps) > 0) {
                return $this->getPublicDisplayList($aMaps);
            } else {
                return '<img src="' . plugin_dir_url(__DIR__) . 'assets/img/jobs-rrze-517x120.png" class="default-image">';
            }
        }

        if ($this->count == 0) {
            if (isset($this->options['rrze-jobs-labels_no_jobs_message'])) {
                return '<p>' . $this->options['rrze-jobs-labels_no_jobs_message'] . '</a></p>';
            } else {
                return '<p>asdf' . __('API does not return any data.', 'rrze-jobs') . '</a></p>';
            }
        }

        return do_shortcode('[collapsibles expand-all-link="true"]' . $shortcode_items . '[/collapsibles]');
    }

    public function get_single_job()
    {
        $output = '';
        $aResponseByAPI = $this->getResponse('single', $this->jobid);
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = (has_custom_logo() ? wp_get_attachment_url($custom_logo_id) : '');

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

        // not job found => exit
        if (empty($job)) {
            return '<p>' . __('This job offer is not available', 'rrze-jobs') . '</p>';
        }


        $map = $this->jobOutput->fillMap($this->map_template, $job);

        $intern_allowed = $this->jobOutput->isInternAllowed();

        if ($this->provider == 'univis') {
            foreach ($aPersons as $key => $val) {
                $map[$key] = $val;
            }
        }

        // Skip internal job offers if necessary
        $job_intern = (isset($map['job_intern']) && $map['job_intern'] == 'ja' ? 1 : 0);

        // 'This job offer is not available'  => job is intern but internals jobs are not allowed OR application_end is given but is expired
        if ((!$intern_allowed && $job_intern) || (isset($map['application_end']) && ($map['application_end'] < date('Y-m-d')))) {
            return '<p>' . __('This job offer is not available', 'rrze-jobs') . '</p>';
        }

        // BK 2022-03-04 : why? $azubi is not used anywhere
        // $azubi = false;
        // if ((isset($map['job_title'])) && (strpos($map['job_title'], 'Auszubildende'))) {
        //     $azubi = true;
        // }
        
        $salary = $this->getSalary($map);
        $description = $this->getDescription($map);

        if (isset($map['job_employmenttype'])) {
            if ($map['job_employmenttype'] == 'voll') {
                $map['job_employmenttype'] = 'Vollzeit';
            } elseif ($map['job_employmenttype'] == 'teil') {
                $map['job_employmenttype'] = 'Teilzeit';
            }
        }
        if ($this->provider == 'interamt') {
            $start_application_string = strpos($map['job_description'], 'Bitte bewerben Sie sich');
            if ($start_application_string !== false && $start_application_string > 100) {
                $application_string = substr($map['job_description'], $start_application_string);
                $map['application_link'] = strip_tags(html_entity_decode($application_string), '<a><br><br /><b><strong><i><em>');
            }
        }
        $application_email = '';
        if (isset($map['application_link'])) {
            preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+[a-zA-Z]+/i", $map['application_link'], $matches);
            if (!empty($matches[0])) {
                $application_email = $matches[0][0];
                $map['application_email'] = $application_email;
            }
        }

        if ((isset($map['job_type'])) && ($map['job_type'] != 'keine')) {
            $kennung_old = $map['job_type'];
            switch (mb_substr($map['job_type'], -2)) {
                case '-I':
                    $map['job_type'] = preg_replace('/-I$/', '-W', $map['job_type']);
                    break;
                case '-U':
                    $map['job_type'] = preg_replace('/-U$/', '-W', $map['job_type']);
                    break;
                default:
                    $map['job_type'] = $map['job_type'] . '-W';
            }
            $map['job_type'] = str_replace(["[", "]"], ["&#91;", "&#93;"], $map['job_type']);
        } else {
            $map['job_type'] = null;
        }

        if (isset($map['application_end'])) {
            $date = date_create($map['application_end']);
            $date_deadline = date_format($date, 'd.m.Y');
        }

        // BK 2021-12-17: why does this exist? $map['job_description'] is not used
        // if ( isset( $kennung_old ) && isset( $map['job_type'] ) && isset( $map['job_description'] ) ) {
        //     $map['job_description'] = str_replace( $kennung_old, $map['job_type'], $map['job_description'] );
        // }

        $description = '<div itemprop="description" class="rrze-jobs-single-description">' . $description . '</div>';

        $output = '';
        $output .= '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';

        $output .= do_shortcode('[three_columns_two]' . $description . '[/three_columns_two]' . '[three_columns_one_last]' . $this->get_sidebar($map, $logo_url) . '[/three_columns_one_last][divider]');

        // display job_notice
        if (!isset($_GET['format']) && isset($this->options['rrze-jobs_job_notice']) && $this->options['rrze-jobs_job_notice'] != '') {
            $output .= '<hr /><div>' . strip_tags($this->options['rrze-jobs_job_notice'], '<p><a><br><br /><b><strong><i><em>') . '</div>';
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

        $jobs_page_url = get_permalink($this->options['rrze-jobs_jobs_page']);

        $intern_allowed = $this->jobOutput->isInternAllowed();

        foreach ($aMaps as $k => $map) {
            $job_intern = (isset($map['job_intern']) && $map['job_intern'] == 'ja' ? 1 : 0);

            if (!$intern_allowed && $job_intern) {
                return '<p>' . __('This job offer is not available', 'rrze-jobs') . '</p>';
            }

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
            if (isset($map['job_start'])) {
                $job_item .= '<span class="label">' . __('Job start', 'rrze-jobs') . ':</span> ' . $map['job_start'];
            }
            if (isset($map['application_end'])) {
                $job_item .= '<br /><span class="label">' . __('Application deadline', 'rrze-jobs') . ':</span> ' . date('d.m.Y', strtotime($map['application_end']));
            }
            if (isset($map['job_employmenttype'])) {
                $job_item .= '<br /><span class="label">' . __('Part-time / full-time', 'rrze-jobs') . ':</span> ' . $map['job_employmenttype'];
            }
            $salary = $this->getSalary($map);
            if ($salary != '') {
                $job_item .= '<br /><span class="label">' . __('Payment', 'rrze-jobs') . '</span>: ' . $salary;
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
