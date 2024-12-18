<?php

namespace RRZE\Jobs\Config;

defined('ABSPATH') || exit;
define('RRZE_JOBS_LOGO', plugins_url('assets/img/fau.gif', __DIR__));
define('RRZE_JOBS_ADDRESS_REGION', 'Bayern');
define('BESOLDUNG_TXT', '(BayBesG)');

use RRZE\Jobs\Job;

function getShortcodeSettings() {
    return [
        'block' => [
            'blocktype' => 'rrze-jobs/jobs',
            'blockname' => 'jobs',
            'title' => 'RRZE Jobs',
            'category' => 'widgets',
            'icon' => 'admin-users',
            'tinymce_icon' => 'sharpen',
        ],
        'provider' => [
            'field_type' => 'select',
            'values' => [
                'bite' => __('BITE', 'rrze-jobs'),
                'interamt' => __('Interamt', 'rrze-jobs'),
                'univis' => __('UnivIS', 'rrze-jobs'),
            ],
            'default' => 'univis',
            'label' => __('Provider', 'rrze-jobs'),
            'type' => 'string',
        ],
        'orgids' => [
            'field_type' => 'text',
            'default' => '',
            'label' => __('OrgID(s)', 'rrze-jobs'),
            'type' => 'string',
        ],
        'jobid' => [
            'field_type' => 'text',
            'default' => 0,
            'label' => __('Job ID (0 = all)', 'rrze-jobs'),
            'type' => 'number',
        ],
        'limit' => [
            'field_type' => 'text',
            'values' => '',
            'default' => 0,
            'label' => __('Number of job offers', 'rrze-jobs'),
            'type' => 'number',
        ],
        'orderby' => [
            'field_type' => 'select',
            'values' => [
                'title' => __('Job title', 'rrze-jobs'), // in Doku: job_title
                // 'application_start' => __('Application start', 'rrze-jobs'),
                'validThrough' => __('Application end', 'rrze-jobs'), // in Doku: application_end
                'jobStartDateSort' => __('Job start', 'rrze-jobs'), // in Doku: job_start  - ATTENTION: we need jobStartDateSort to sort in sortArrayByField()
            ],
            'default' => 'title',
            'label' => __('Order by', 'rrze-jobs'),
            'type' => 'string',
        ],
        'order' => [
            'field_type' => 'radio',
            'values' => [
                'ASC' => __('Ascending', 'rrze-jobs'),
                'DESC' => __('Descending', 'rrze-jobs'),
            ],
            'default' => 'DESC',
            'label' => __('Order', 'rrze-jobs'),
            'type' => 'string',
        ],
        'fallback_apply' => [
            'field_type' => 'text',
            'values' => '',
            'default' => '',
            'label' => __('Default application link', 'rrze-jobs'),
            'type' => 'string',
        ],
        'default_subject' => [
            'field_type' => 'text',
            'values' => '',
            'default' => '',
            'label' => __('Default application email subject', 'rrze-jobs'),
            'type' => 'string',
        ],
        'link_only' => [
            'field_type' => 'toggle',
            'label' => __('Show only links to BITE', 'rrze-jobs'),
            'type' => 'boolean',
            'default' => FALSE,
            'checked' => FALSE,
        ],
        'category' => [
            // filter for occupationalCategory
            'field_type' => 'select',
            'values' => [
                'wiss' => __('Research & Teaching', 'rrze-jobs'),
                'n-wiss' => __('Technology & Administration', 'rrze-jobs'),
                'azubi' => __('Trainee', 'rrze-jobs'),
                'hiwi' => __('Student assistants', 'rrze-jobs'),
                'prof' => __('Professorships', 'rrze-jobs'),
                'other' => __('Other', 'rrze-jobs'),
                '' => __('No filter', 'rrze-jobs'),
            ],
            'default' => '',
            'label' => __('Filter by occupationalCategory', 'rrze-jobs'),
            'type' => 'string',
        ],
        'fauorg' => [
            'field_type' => 'text',
            'default' => '',
            'label' => __('FAU.ORG Number', 'rrze-jobs'),
            'type' => 'number',
        ],


    ];
}

/**
 * Gibt die Einstellungen des Menus zurück.
 *
 * @return array [description]
 */
function getMenuSettings() {
    return [
        'page_title' => __('Jobs', 'rrze-jobs'),
        'menu_title' => __('RRZE Jobs', 'rrze-jobs'),
        'capability' => 'manage_options',
        'menu_slug' => 'rrze-jobs',
        'title' => __('Jobs Settings', 'rrze-jobs'),
    ];
}

/**
 * Gibt die Einstellungen der Optionsbereiche zurück.
 *
 * @return array [description]
 */
function getSections() {
    return [
        [
            'id' => 'rrze-jobs-access',
            'title' => __('Accesses', 'rrze-jobs'),
        ],
        [
            'id' => 'rrze-jobs-labels',
            'title' => __('Layout', 'rrze-jobs'),
            'desc' => __('Here you can set the headings and captions for each section in the job posting.', 'rrze-jobs'),
        ],

        [
            'id' => 'rrze-jobs-misc',
            'title' => __('Misc', 'rrze-jobs'),
        ],
    ];
}

/**
 * Gibt die Einstellungen der Optionsfelder zurück.
 *
 * @return array [description]
 */

/*
 * TODO:  nachhaltigere Variablennamen umsetzen und dabei Abwärtskompatibilitaet zu bestehenden Installationen herstellen
 * Sinnvoll ist eher Richtung:
 *       univis_orgid
 *       interamt_partnerid
 *       bite_apikey
 *
 * Dabei auch prüfen, ob man wirlich mehr als eine Id braucht und ob wir hier dies eben auf einen einzigen Zugang beschränken.
 * Wer andere provider will, kann das über den Shortcode Parameter setzen. Hier sollte der eine (1) Default oder Fallback stehen.
 * -WW, 16.09.2022
 */
function getFields() {
    $aFields = [
        'rrze-jobs-access' => [
            [
                'name' => 'orgids_interamt',
                'label' => __("Interamt Partner Id", 'rrze-jobs'),
                'desc' => __('Enter the Partner-ID of the Interamt-Service', 'rrze-jobs'),
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'orgids_univis',
                'label' => __("UnivIS OrgId", 'rrze-jobs'),
                'desc' => __('Enter the Id of your organization in UnivIS', 'rrze-jobs'),
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'bite_apikey',
                'label' => __("BITE API Key", 'rrze-jobs'),
                'desc' => __('Enter the API Key for BITE', 'rrze-jobs'),
                'type' => 'text',
                'default' => '',
            ],
        ],
        'rrze-jobs-labels' => [
            [
                'name' => 'hr1',
                'label' => __('Job offer', 'rrze-jobs'),
                'type' => 'line',
            ],
            [
                'name' => 'job_headline_title',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Title", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Title", 'rrze-jobs'),
                'default_en' => "Title"
            ],
            [
                'name' => 'job_headline_keyfacts',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __('Details', 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __('Details', 'rrze-jobs'),
                'default_en' => "Details"
            ],
            [
                'name' => 'job_headline_workplace',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __('Your Workplace', 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __('Your Workplace', 'rrze-jobs'),
                'default_en' => "Your Workplace"
            ],
            [
                'name' => 'job_headline_description',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __('Description', 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __('Description', 'rrze-jobs'),
                'default_en' => "Description"
            ],
            [
                'name' => 'job_headline_qualifications',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __('Qualifications', 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __('Qualifications', 'rrze-jobs'),
                'default_en' => "Qualifications"
            ],
            [
                'name' => 'job_headline_qualifications_required',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Necessary qualifications", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Necessary qualifications", 'rrze-jobs'),
                'default_en' => "Necessary qualifications"
            ],
            [
                'name' => 'job_headline_qualifications_experience',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Optional experience", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Optional experiences", 'rrze-jobs'),
                'default_en' => "Optional experiences"
            ],
            [
                'name' => 'job_headline_qualifications_optional',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Desirable qualifications", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Desirable qualifications", 'rrze-jobs'),
                'default_en' => "Desirable qualifications"
            ],
            [
                'name' => 'job_headline_disambiguatingDescription',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Supplementary description", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Supplementary description", 'rrze-jobs'),
                'default_en' => "Supplementary description"
            ],
            [
                'name' => 'job_headline_jobBenefits',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Job Benefits", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Job Benefits", 'rrze-jobs'),
                'default_en' => "Job Benefits"
            ],
            [
                'name' => 'job_headline_jobStartDate',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Job start date", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Job start date", 'rrze-jobs'),
                'default_en' => "Job start date"
            ],
            [
                'name' => 'job_headline_validThrough',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __('Application deadline', 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __('Application deadline', 'rrze-jobs'),
                'default_en' => "Application deadline"
            ],
            [
                'name' => 'job_headline_Location',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Location", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Location", 'rrze-jobs'),
                'default_en' => "Location"
            ],
            [
                'name' => 'job_headline_payment',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Payment", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Payment", 'rrze-jobs'),
                'default_en' => "Payment"
            ],
            [
                'name' => 'job_headline_befristet',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Limitation", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Limitation", 'rrze-jobs'),
                'default_en' => "Limitation"
            ],
            [
                'name' => 'job_headline_contact',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Contact", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Contact", 'rrze-jobs'),
                'default_en' => "Contact"
            ],
            [
                'name' => 'job_headline_workingtime',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Working time", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Working time", 'rrze-jobs'),
                'default_en' => "Working time"
            ],
            [
                'name' => 'job_headline_workHours',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __('Weekly working hours', 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __('Weekly working hours', 'rrze-jobs'),
                'default_en' => "Weekly working hours"
            ],
            [
                'name' => 'job_headline_application_button',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __('Apply now', 'rrze-jobs'),
                // 'desc' => __('Label for the button to apply to', 'rrze-jobs'),
                'type' => 'textTranslation',
                'default' => __('Apply now', 'rrze-jobs'),
                'default_en' => "Apply now"
            ],
            [
                'name' => 'job_headline_application',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Application", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Application", 'rrze-jobs'),
                'default_en' => "Application"
            ],
            [
                'name' => 'hr2',
                'label' => __('Static Textentries below each job offer', 'rrze-jobs'),
                'type' => 'line',
            ],
            [
                'name' => 'job_headline_jobnotice',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Notice", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Notice", 'rrze-jobs'),
                'default_en' => "Notice"
            ],
            [
                'name' => 'job_headline_releasedate',
                'label' => __('Label for', 'rrze-jobs') . ' ' . __("Release date", 'rrze-jobs'),
                'desc' => '',
                'type' => 'textTranslation',
                'default' => __("Release date", 'rrze-jobs'),
                'default_en' => "Release date"
            ],
            [
                'name' => 'job_defaulttext_jobnotice',
                'label' => __('Output for', 'rrze-jobs') . ' ' . __("Notice", 'rrze-jobs'),
                'type' => 'textareaTranslation',
                'size' => 'large',
                'default' => '<p>Für alle Stellenausschreibungen gilt: Die Friedrich-Alexander-Universität fördert die berufliche Gleichstellung der Frauen. Frauen werden deshalb ausdrücklich aufgefordert, sich zu bewerben.</p>
<p>Schwerbehinderte im Sinne des Schwerbehindertengesetzes werden bei gleicher fachlicher Qualifikation und persönlicher Eignung bevorzugt berücksichtigt, wenn die ausgeschriebene Stelle sich für Schwerbehinderte eignet. Details dazu finden Sie in der jeweiligen Ausschreibung unter dem Punkt "Bemerkungen".</p>
<p>Bei Wunsch der Bewerberin, des Bewerbers, kann die Gleichstellungsbeauftragte zum Bewerbungsgespräch hinzugezogen werden, ohne dass der Bewerberin, dem Bewerber dadurch Nachteile entstehen.</p>
<p>Ausgeschriebene Stellen sind grundsätzlich teilzeitfähig, es sei denn, im Ausschreibungstext erfolgt ein anderweitiger Hinweis.</p>',
                'default_en' => ""
            ],
            [
                'name' => 'job_usedefaulttext_jobnotice',
                'label' => __('Job Notices from job provider', 'rrze-jobs'),
                'desc' => __('Use the text from the provider and not the above text for additional notices. (Currently from BITE only).', 'rrze-jobs'),
                'type' => 'radio',
                'default' => FALSE,
                'options' => [
                    TRUE => __('Use text from provider if given', 'rrze-jobs'),
                    FALSE => __('Use above text', 'rrze-jobs'),
                ],
            ],
            [
                'name' => 'job_errortext_display',
                'label' => __('Errormessages', 'rrze-jobs'),
                'desc' => __('In case of errors or in case that no job was found, you can switch off the errormessages.', 'rrze-jobs'),
                'type' => 'radio',
                'default' => TRUE,
                'options' => [
                    TRUE => __('Show Errors and not found messages', 'rrze-jobs'),
                    FALSE => __('Hide errors and not found messages', 'rrze-jobs'),
                ],
            ],
            [
                'name' => 'job_errortext_400',
                'label' => __("Invalid provider", 'rrze-jobs'),
                'desc' => __('This message will be displayed if the given provider in the shortcode is invalid.', 'rrze-jobs'),
                'type' => 'textarea',
                'default' => __('Invalid provider.', 'rrze-jobs'),
            ],
            [
                'name' => 'job_errortext_403',
                'label' => __("Internal Position", 'rrze-jobs'),
                'desc' => __('This message will be displayed, if someone tries to get an internal positions without the needed authorization', 'rrze-jobs'),
                'type' => 'textarea',
                'default' => __('Internal position, avaible for members only', 'rrze-jobs'),
            ],
            [
                'name' => 'job_errortext_404',
                'label' => __("No Jobs Message", 'rrze-jobs'),
                'desc' => __('This message will be displayed if the API does not return any data.', 'rrze-jobs'),
                'type' => 'textarea',
                'default' => __('No open job positions found.', 'rrze-jobs'),
            ],
            [
                'name' => 'job_errortext_405',
                'label' => __("Invalid parameters or method", 'rrze-jobs'),
                'desc' => __('This message will be displayed if shortcode uses wrong parameters.', 'rrze-jobs'),
                'type' => 'textarea',
                'default' => __('Invalid parameters or method.', 'rrze-jobs'),
            ],
            [
                'name' => 'job_errortext_406',
                'label' => __("Missing Provider Identifier", 'rrze-jobs'),
                'desc' => __('This message will be displayed if a provider is adressed with a shortcode, but cannot be used cause of a missing id or key.', 'rrze-jobs'),
                'type' => 'textarea',
                'default' => __('Missing Provider Identifier', 'rrze-jobs'),
            ],
        ],
        'rrze-jobs-misc' => [
            [
                'name' => 'jobs_page',
                'label' => __('Jobs Page', 'rrze-jobs'),
                'desc' => __('QR on Public Displays link to this target.', 'rrze-jobs'),
                'type' => 'selectPage',
                'default' => '',
            ],
            /*    [
                        'name' => 'hide_internal_jobs',
                        'label' => __('Hide internal jobs', 'rrze-jobs'),
                        'desc' => __('Hide internal jobs, display them only if the website was displayed from an allowed host', 'rrze-jobs'),
                        'type' => 'radio',
                        'default' => true,
                'options'   => array(
                    true  => __('Hide', 'rrze-jobs'),
                    false  => __('Show', 'rrze-jobs'),
                )
                    ],
             */
            [

                'name' => 'hide_internal_jobs_notforadmins',
                'label' => __('Display internal jobs as admins', 'rrze-jobs'),
                'type' => 'radio',
                'default' => TRUE,
                'options' => [
                    TRUE => __('Internal jobs will always be visible for website admins', 'rrze-jobs'),
                    FALSE => __('Treat admins like normal website users', 'rrze-jobs'),
                ],
            ],
            [
                'name' => 'hide_internal_jobs_required_hosts',
                'label' => __('Required hosts for internal jobs', 'rrze-jobs'),
                'desc' => __('Internal job positions will be displayed only on hosts from the given hostnames', 'rrze-jobs'),
                'type' => 'textarea',
                'default' => 'uni-erlangen.de, fau.de',

            ],


        ],
    ];

    return $aFields;
    // add fields defined in map (Job.php)
    //  $jobOutput = new Job();
    //   $map_template = $jobOutput->getMap('bite', true);
    /*
    $aHideFields = [
        'job_id',
        'job_intern',
        'employer_street_nr',
    ];

    foreach ($map_template as $sField => $aDetails) {
        if (!in_array($sField, $aHideFields)) {
            $aFields['rrze-jobs-fields'][] = [
                'name' => $sField,
                'label' => $aDetails['label'],
                'desc' => (!empty($aDetails['desc']) ? $aDetails['desc'] : ''),
                'type' => (!empty($aDetails['type']) ? $aDetails['type'] : 'text'),
                'options' => (!empty($aDetails['options']) ? $aDetails['options'] : ''), 
                'default' => (!empty($aDetails['default']) ? $aDetails['default'] : ''), 
                'min' => (!empty($aDetails['min']) ? $aDetails['min'] : ''), 
                'max' => (!empty($aDetails['max']) ? $aDetails['max'] : ''), 
            ];
        }
    }
  
*/
}

/**
 * Gibt die Einstellungen der Inhaltshilfe zurück.
 *
 * @return array [description]
 */
function getHelpTab() {
    return [
        [
            'id' => 'rrze-jobs',
            'content' => [
                '<p>' . __('Find instructions at ', 'rrze-jobs') . '<a href="https://www.wordpress.rrze.fau.de/plugins/fau-und-rrze-plugins/jobs" target="_blank">https://www.wordpress.rrze.fau.de/plugins/fau-und-rrze-plugins/jobs</a></p>',
            ],
            'title' => __('Overview', 'rrze-jobs'),
            'sidebar' => sprintf('<p><strong>%1$s:</strong></p><p><a href="https://blogs.fau.de/webworking">RRZE Webworking</a></p><p><a href="https://github.com/RRZE Webteam">%2$s</a></p>', __('For more information', 'rrze-jobs'), __('RRZE Webteam on Github', 'rrze-jobs')),
        ],
    ];
}

/**
 * Fixe und nicht aenderbare Plugin-Optionen
 *
 * @return array
 */
function getConstants() {
    $options = [
        'Transient_Prefix' => 'rrze_jobs',
        'Transient_Seconds' => 6 * HOUR_IN_SECONDS,
        'fauthemes' => [
            'FAU-Einrichtungen',
            'FAU-Einrichtungen-Beta',
            'FAU-Philfak',
            'FAU-Natfak',
            'FAU-RWFak',
            'FAU-Medfak',
            'FAU-Techfak',
            'FAU-Jobs',
        ],
    ];

    return $options; // Standard-Array für zukünftige Optionen
}