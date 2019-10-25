<?php

namespace RRZE\Jobs\Config;


defined('ABSPATH') || exit;



/**
 * Gibt der Name der Option zurück.
 * @return array [description]
 */
function getOptionName() {
    return 'rrze-jobs';
}

/**
 * Gibt die Einstellungen des Menus zurück.
 * @return array [description]
 */
function getMenuSettings() {
    return [
       'page_title'    => __('Jobs', 'rrze-jobs'),
        'menu_title'    => __('RRZE Jobs', 'rrze-jobs'),
        'capability'    => 'manage_options',
        'menu_slug'     => 'rrze-jobs',
        'title'         => __('Jobs Settings', 'rrze-jobs'),
    ];
}

/**
 * Gibt die Einstellungen der Inhaltshilfe zurück.
 * @return array [description]
 */
function getHelpTab() {
  return [
      [
          'id'        => 'rrze-jobs',
          'content'   => ['<p>' .
                    sprintf( __( 'This plugin will automatically add an icon or a preview image next to links of the activated file types. If you like, you can also let the plugin add the file size of the linked file to the page.', 'rrze-jobs' ), 'http://wordpress.org/plugins/mimetypes-link-icons/" target="_blank" class="ext-link' ) . '</p>
                    <p>' . esc_html__( 'On this settings page you can choose to show an icon or a preview image will be shown and specify the icon size, icon type (white matte gif or transparent png) and the icon alignment. Click on tab "File Types Settings" to select the file types for which this plugin will be enabled. "Additional Settings" allow you to specify exceptions, format the file size and set caching options.', 'rrze-jobs' ) . '</p>'
          ],
          'title'     => __('Overview', 'jobs'),
          'sidebar'   => sprintf('<p><strong>%1$s:</strong></p><p><a href="https://blogs.fau.de/webworking">RRZE Webworking</a></p><p><a href="https://github.com/RRZE Webteam">%2$s</a></p>', __('For more information', 'rrze-jobs'), __('RRZE Webteam on Github', 'rrze-jobs'))
      ]
  ];
}


/**
 * Gibt die Einstellungen der Optionsbereiche zurück.
 * @return array [description]
 */
function getSections() {
    return [
      [
        'id'    => 'interamt',
        'title' => __('interamt', 'rrze-jobs')
      ],
      [
        'id'    => 'univis',
        'title' => __('univIS', 'rrze-jobs')
      ]
    ];
}

/**
 * Gibt die Einstellungen der Optionsfelder zurück.
 * @return array [description]
 */
function getFields() {
  
  $ret = [
    'interamt' => [
      [
        'name'    => 'orgid',
        'label'   => __("orgID", 'rrze-jobs'),
        'desc'    => __('Enter the ID of your organization', 'rrze-jobs'),
        'type'    => 'text',
        'default' => '2217'
      ],
      [
        'name'    => 'urllist',
        'label'   => __('URL to listings', 'rrze-jobs'),
        'desc'    => __("Enter the link to all the job's listings", 'rrze-jobs'),
        'type'    => 'text',
        'default' => 'https://www.interamt.de/koop/app/webservice_v2?partner='
      ],
      [
        'name'    => 'urlsingle',
        'label'   => __("URL to details", 'rrze-jobs'),
        'desc'    => __("Enter the link to the detailed job's listing", 'rrze-jobs'),
        'type'    => 'text',
        'default' => 'https://www.interamt.de/koop/app/webservice_v2?id='
      ]
    ],
    'univis' => [
      [
        'name'    => 'orgid',
        'label'   => __("orgID", 'rrze-jobs'),
        'desc'    => __('Enter the ID of your organization', 'rrze-jobs'),
        'type'    => 'text',
        'default' => '420100'
      ],
      [
        'name'    => 'urllist',
        'label'   => __('URL to listings', 'rrze-jobs'),
        'desc'    => __("Enter the link to all the job's listings", 'rrze-jobs'),
        'type'    => 'text',
        'default' => 'http://univis.uni-erlangen.de/prg?search=positions&department='
      ],
      [
        'name'    => 'urlsingle',
        'label'   => __("URL to details", 'rrze-jobs'),
        'desc'    => __("Enter the link to the detailed job's listing", 'rrze-jobs'),
        'type'    => 'text',
        'default' => 'this one is to be defined'
      ]
    ],
  ];
  
  return $ret;
}

function getMap(&$provider){
  $map = [
    'job_type' => [
      'interamt' => 'Kennung', 
      'univis'=> 'type2'
    ], 
    // 'job_employmenttype' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    'job_title' => [
      'interamt' => 'StellenBezeichnung', 
      'univis'=> 'title'
    ],
    'job_description' => [
      'interamt' => 'Beschreibung', 
      'univis'=> 'desc1'
    ],
    // 'job_qualifications' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'job_education' => [
    //   'interamt' => '', 
    //   'univis'=> 'desc2'
    // ],
    // 'job_experience' => [
    //   'interamt' => '', 
    //   'univis'=> 'desc3'
    // ],
    // 'job_benefits' => [
    //   'interamt' => '', 
    //   'univis'=> 'desc4'
    // ],
    // 'job_category' => [
    //   'interamt' => '', 
    //   'univis'=> 'group'
    // ],
    // 'job_salary' => [
    //   'interamt' => '', 
    //   'univis'=> 'bisbesold'
    // ],
    // 'job_unit' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'job_label' =>  => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'job_workhours' => [
    //   'interamt' => '', 
    //   'univis'=> 'wstunden'
    // ],
    // 'job_start' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'job_end' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'job_limitation' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'application_start' => [
    //   'interamt' => 'datePosted', 
    //   'univis'=> ''
    // ],
    // 'application_end' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'application_link' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    'employer_organization' => [
      'interamt' => 'Behoerde', 
      'univis'=> 'StellenangebotBehoerde'
    ],
    // 'employer_street' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'employer_postalcode' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'employer_city' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'employer_district' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'contact_title' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'contact_firstname' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'contact_lastname' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'contact_tel' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'contact_mobile' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'contact_email' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'contact_link'  => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ]
  ];

  $provider_map = array();
  // $i=1;
  foreach ($map as $key => $val) {
    // echo "<script>console.log('" . $i . " " . $key . " = " . $val[$provider] . "');</script>";
    // $i++;
    $provider_map[$key] = $val[$provider]; 
  }
  
  return $provider_map;
}

