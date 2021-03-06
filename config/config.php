<?php

namespace RRZE\Jobs\Config;

defined('ABSPATH') || exit;
define('RRZE_JOBS_LOGO', plugins_url('assets/img/fau.gif', __DIR__));
define('RRZE_JOBS_ADDRESS_REGION', 'Bayern');
use RRZE\Jobs\Job;

function getShortcodeSettings()
{
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
        'internal' => [
            'field_type' => 'select',
            'values' => [
                'exclude' => __('exclude internal job offers', 'rrze-jobs'),
                'include' => __('include internal job offers', 'rrze-jobs'),
                'only' => __('only internal job offers', 'rrze-jobs'),
            ],
            'default' => 'exclude',
            'label' => __('Internal job offers', 'rrze-jobs'),
            'type' => 'string',
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
                'job_title' => __('Job title', 'rrze-jobs'),
                'application_start' => __('Application start', 'rrze-jobs'),
                'application_end' => __('Application end', 'rrze-jobs'),
                'job_start' => __('Job start', 'rrze-jobs'),
            ],
            'default' => 'job_title',
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
        'link_only' => [
            'field_type' => 'toggle',
            'label' => __('Show only links to BITE', 'rrze-univis'),
            'type' => 'boolean',
            'default' => false,
            'checked' => false,
        ],
    ];
}

/**
 * Gibt die Einstellungen des Menus zur??ck.
 * @return array [description]
 */
function getMenuSettings()
{
    return [
        'page_title' => __('Jobs', 'rrze-jobs'),
        'menu_title' => __('RRZE Jobs', 'rrze-jobs'),
        'capability' => 'manage_options',
        'menu_slug' => 'rrze-jobs',
        'title' => __('Jobs Settings', 'rrze-jobs'),
    ];
}

/**
 * Gibt die Einstellungen der Optionsbereiche zur??ck.
 * @return array [description]
 */
function getSections()
{
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
            'id' => 'rrze-jobs-fields',
            'title' => __('Data fields', 'rrze-jobs'),
            'desc' => __('These fields are supplied by the interfaces (Interamt, UnivIS, BITE).<br />You can set a default value for each field that is output in the job offer.<br />Leave the <strong>field empty</strong> , so that the value obtained from the interface can be used.', 'rrze-jobs'),
        ],
    ];
}

/**
 * Gibt die Einstellungen der Optionsfelder zur??ck.
 * @return array [description]
 */
function getFields()
{
    $aFields = [
        'rrze-jobs-access' => [
            [
                'name' => 'orgids_interamt',
                'label' => __("orgIDs Interamt", 'rrze-jobs'),
                'desc' => __('Enter the IDs of your organizations comma separated', 'rrze-jobs'),
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'orgids_univis',
                'label' => __("orgIDs UnivIS", 'rrze-jobs'),
                'desc' => __('Enter the IDs of your organizations comma separated', 'rrze-jobs'),
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'apiKey',
                'label' => __("API Key", 'rrze-jobs'),
                'desc' => __('Enter the apiKey for BITE', 'rrze-jobs'),
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
                'name' => 'job_headline_task',
                'label' => __("Your tasks", 'rrze-jobs'),
                'desc' => '',
                'type' => 'text',
                'default' => 'Das Aufgabengebiet umfasst u.a.:',
            ],
            [
                'name' => 'job_headline_qualifications',
                'label' => __("Your profile (necessary)", 'rrze-jobs'),
                'desc' => '',
                'type' => 'text',
                'default' => 'Notwendige Qualifikation',
            ],
            [
                'name' => 'job_headline_qualifications_nth',
                'label' => __("Your profile (desired)", 'rrze-jobs'),
                'desc' => '',
                'type' => 'text',
                'default' => 'W??nschenswerte Qualifikation',
            ],
            [
                'name' => 'job_headline_remarks',
                'label' => __("We offer", 'rrze-jobs'),
                'desc' => '',
                'type' => 'text',
                'default' => 'Bemerkungen',
            ],
            [
                'name' => 'job_headline_application',
                'label' => __("Application", 'rrze-jobs'),
                'desc' => '',
                'type' => 'text',
                'default' => 'Bewerbung',
            ],
            [
                'name' => 'job_notice',
                'label' => __("Notice", 'rrze-jobs'),
                'desc' => __('This notice will be dispayed at the bottom of each job offer.', 'rrze-jobs'),
                'type' => 'textarea',
                'size' => 'large',
                'default' => '<p>F??r alle Stellenausschreibungen gilt: Die Friedrich-Alexander-Universit??t f??rdert die berufliche Gleichstellung der Frauen. Frauen werden deshalb ausdr??cklich aufgefordert, sich zu bewerben.</p>
<p>Schwerbehinderte im Sinne des Schwerbehindertengesetzes werden bei gleicher fachlicher Qualifikation und pers??nlicher Eignung bevorzugt ber??cksichtigt, wenn die ausgeschriebene Stelle sich f??r Schwerbehinderte eignet. Details dazu finden Sie in der jeweiligen Ausschreibung unter dem Punkt "Bemerkungen".</p>
<p>Bei Wunsch der Bewerberin, des Bewerbers, kann die Gleichstellungsbeauftragte zum Bewerbungsgespr??ch hinzugezogen werden, ohne dass der Bewerberin, dem Bewerber dadurch Nachteile entstehen.</p>
<p>Ausgeschriebene Stellen sind grunds??tzlich teilzeitf??hig, es sei denn, im Ausschreibungstext erfolgt ein anderweitiger Hinweis.</p>',
            ],
            [
                'name' => 'hr2',
                'label' => __('Sidebar', 'rrze-jobs'),
                'type' => 'line',
            ],
            [
                'name' => 'sidebar_application_button',
                'label' => __('"Apply to" button', 'rrze-jobs'),
                // 'desc' => __('Label for the button to apply to', 'rrze-jobs'),
                'type' => 'text',
                'default' => 'Jetzt bewerben!',
            ],
            [
                'name' => 'sidebar_headline_application',
                'label' => __("Application", 'rrze-jobs'),
                'desc' => __('Title of "Your application"', 'rrze-jobs'),
                'type' => 'text',
                'default' => 'Bewerbung',
            ],
            [
                'name' => 'sidebar_show_application_link',
                'label' => __("Show application link", 'rrze-jobs'),
                'desc' => __('Display both, a link and a button to apply to', 'rrze-jobs'),
                'type' => 'checkbox',
                'default' => true,
            ],
            [
                'name' => 'hr3',
                'label' => __('Miscellaneous', 'rrze-jobs'),
                'type' => 'line',
            ],
            [
                'name' => 'jobs_page',
                'label' => __('Jobs Page', 'rrze-jobs'),
                'desc' => __('QR on Public Displays link to this target.', 'rrze-jobs'),
                'type' => 'selectPage',
                'default' => '',
            ],
            [
                'name' => 'no_jobs_message',
                'label' => __("No Jobs Message", 'rrze-jobs'),
                'desc' => __('This message will be displayed if the API does not return any data.', 'rrze-jobs'),
                'type' => 'textarea',
                'size' => 'large',
                'default' => 'Keine Stellenanzeigen gefunden.',
            ],
        ],
    ];

    // add fields defined in map (Job.php)
    $jobOutput = new Job();
    $map_template = $jobOutput->getMap('bite', true);
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
    return $aFields;

}

/**
 * Gibt die Einstellungen der Inhaltshilfe zur??ck.
 * @return array [description]
 */
function getHelpTab()
{
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
 * @return array 
 */
function getConstants() {
        $options = array(
	    
	    'fauthemes' => [
		'FAU-Einrichtungen', 
		'FAU-Philfak',
		'FAU-Natfak', 
		'FAU-RWFak', 
		'FAU-Medfak', 
		'FAU-Techfak',
		'FAU-Jobs'
		],

        );               
        return $options; // Standard-Array f??r zuk??nftige Optionen
    }