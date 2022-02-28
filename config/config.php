<?php

namespace RRZE\Jobs\Config;

defined('ABSPATH') || exit;
define('RRZE_JOBS_LOGO', plugins_url('assets/img/fau.gif', __DIR__));
define('RRZE_JOBS_ADDRESS_REGION', 'Bayern');

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
            'label' => __( 'Show only links to BITE', 'rrze-univis' ),
            'type' => 'boolean',
            'default' => FALSE,
            'checked'   => FALSE
        ],
    ];
}


/**
 * Gibt die Einstellungen des Menus zurück.
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
 * Gibt die Einstellungen der Optionsbereiche zurück.
 * @return array [description]
 */
function getSections()
{
    return [
        [
            'id' => 'rrze-jobs',
            'title' => __('Settings', 'rrze-jobs'),
        ],
    ];
}

/**
 * Gibt die Einstellungen der Optionsfelder zurück.
 * @return array [description]
 */
function getFields()
{
    return [
        'rrze-jobs' => [
            [
                'name' => 'orgids_interamt',
                'label' => __("orgIDs Interamt", 'rrze-jobs'),
                'desc' => __('Enter the ID(s) of your organization(s)', 'rrze-jobs'),
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'orgids_univis',
                'label' => __("orgIDs UnivIS", 'rrze-jobs'),
                'desc' => __('Enter the ID(s) of your organization(s)', 'rrze-jobs'),
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'apiKey',
                'label' => __("apiKey", 'rrze-jobs'),
                'desc' => __('Enter the apiKey for BITE', 'rrze-jobs'),
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'job_headline_task',
                'label' => __("Ihre Aufgaben", 'rrze-jobs'),
                'desc' => __('Title of "Your assignments"', 'rrze-jobs'),
                'type' => 'text',
                'default' => 'Das Aufgabengebiet umfasst u.a.:',
            ],
            [
                'name' => 'job_headline_qualifications',
                'label' => __("Ihr Profil", 'rrze-jobs'),
                'desc' => __('Title of "Your profile"', 'rrze-jobs'),
                'type' => 'text',
                'default' => 'Notwendige Qualifikation',
            ],
            [
                'name' => 'job_headline_remarks',
                'label' => __("Wir bieten", 'rrze-jobs'),
                'desc' => __('Title of "We offer"', 'rrze-jobs'),
                'type' => 'text',
                'default' => 'Bemerkungen',
            ],
            [
                'name' => 'job_headline_application',
                'label' => __("Bewerbung", 'rrze-jobs'),
                'desc' => __('Title of "Your application"', 'rrze-jobs'),
                'type' => 'text',
                'default' => 'Bewerbung',
            ],
            [
                'name' => 'job_notice',
                'label' => __("Notice", 'rrze-jobs'),
                'desc' => __('This notice will be dispayed below each job offer.', 'rrze-jobs'),
                'type' => 'textarea',
                'size' => 'large',
                'default' => '<p>Für alle Stellenausschreibungen gilt: Die Friedrich-Alexander-Universität fördert die berufliche Gleichstellung der Frauen. Frauen werden deshalb ausdrücklich aufgefordert, sich zu bewerben.</p>
<p>Schwerbehinderte im Sinne des Schwerbehindertengesetzes werden bei gleicher fachlicher Qualifikation und persönlicher Eignung bevorzugt berücksichtigt, wenn die ausgeschriebene Stelle sich für Schwerbehinderte eignet. Details dazu finden Sie in der jeweiligen Ausschreibung unter dem Punkt "Bemerkungen".</p>
<p>Bei Wunsch der Bewerberin, des Bewerbers, kann die Gleichstellungsbeauftragte zum Bewerbungsgespräch hinzugezogen werden, ohne dass der Bewerberin, dem Bewerber dadurch Nachteile entstehen.</p>
<p>Ausgeschriebene Stellen sind grundsätzlich teilzeitfähig, es sei denn, im Ausschreibungstext erfolgt ein anderweitiger Hinweis.</p>',
            ],
            [
                'name' => 'no_jobs_message',
                'label' => __("No Jobs Message", 'rrze-jobs'),
                'desc' => __('This message will be displayed if the API does not return any data.', 'rrze-jobs'),
                'type' => 'textarea',
                'default' => __('No job offers found.', 'rrze-jobs'),
            ],
            [
                'name' => 'jobs_page',
                'label' => __('Jobs Page', 'rrze-jobs'),
                'desc' => __('Link target, used on Public Displays only.', 'rrze-jobs'),
                'type' => 'selectPage',
                'default' => '',
            ],
        ],
    ];
}

/**
 * Gibt die Einstellungen der Inhaltshilfe zurück.
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




