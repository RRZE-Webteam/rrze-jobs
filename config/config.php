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
        'title' => __('Interamt', 'rrze-jobs')
      ],
      [
        'id'    => 'univis',
        'title' => __('UnivIS', 'rrze-jobs')
      ]
    ];
}

/**
 * Gibt die Einstellungen der Optionsfelder zurück.
 * @return array [description]
 */
function getFields() {
  
  return [
    'interamt' => [
      [
        'name'    => 'orgid',
        'label'   => __("orgID", 'rrze-jobs'),
        'desc'    => __('Enter the ID of your organization', 'rrze-jobs'),
        'type'    => 'text',
        'default' => '2217'
      ],
      /*[
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
      ]*/
    ],
    'univis' => [
      [
        'name'    => 'orgid',
        'label'   => __("orgID", 'rrze-jobs'),
        'desc'    => __('Enter the ID of your organization', 'rrze-jobs'),
        'type'    => 'text',
        'default' => '420100'
      ],
	    /*[
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
		]*/
    ],
  ];
}

/**
 * Gibt die API-URLs zurück.
 * @return array
 */
function getURLs() {
	return [
		'interamt' => [
			'urllist' => 'https://www.interamt.de/koop/app/webservice_v2?partner=',
			'urlsingle' => 'https://www.interamt.de/koop/app/webservice_v2?id='
		],
		'univis' => [
			'urllist' => 'http://univis.uni-erlangen.de/prg?search=positions&department=',
			'urlsingle' => ''
		]
	];
}

function getMap(&$provider){
  $map = [
    // 'job_identifier' => [
	// 'interamt' => 'Kennung',
    //  'univis'=> '',            // nicht vorhanden
	//  'label' => 'Referenz'
    // ],
    // 'job_employmenttype' => [
    //   'interamt' => 'Teilzeit',
    //   'univis'=> 'type2',
	//   'label' => 'Vollzeit / Teilzeit'
    // ],
    // 'job_title' => [
    //  'interamt' => 'StellenBezeichnung',
    //  'univis'=> 'title',
	//  'label' => 'Stellenbezeichnung'
    // ],
    // 'job_description' => [
    //  'interamt' => 'Beschreibung',
    //  'univis'=> 'desc1',
	//  'label' => 'Beschreibung'
    // ],
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
	//   'interamt' => 'FachrichtungCluster', //ggf. auch 'Fachrichtung' ??
    //   'univis'=> 'group',
	//   'label' => 'Berufsgruppe'
    // ],
    // 'job_salary_from' => [
    //   'interamt' => 'TarifEbeneVon',
    //   'univis'=> 'vonbesold',
	//   'label' => 'Tarifebene von'
    // ],
    // 'job_salary_to' => [
    //  'interamt' => 'TarifEbeneBis',
    //  'univis'=> 'bisbesold',
    //  'label' => 'Tarifebene bis'
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
    //  'interamt' => 'WochenarbeitszeitArbeitnehmer',
    //  'univis'=> 'wstunden',
	//  'label' => 'Wochenarbeitszeit'
    // ],
    // 'job_start' => [
    //  'interamt' => 'DatumBesetzungZum',
    //  'univis'=> 'start',
	//  'label' => 'Besetzung zum'
    // ],
    // 'job_end' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'job_limitation' => [       // Befristung ja/nein
    //   'interamt' => 'BeschaeftigungDauer',
    //   'univis'=> 'type1',
	//   'label' => 'Befristung'
    // ],
    //'job_limitation_duration' => [      // Befristung Dauer
	//    'interamt' => 'BefristetFuer',  // Anzahl Monate !!!
	//    'univis'=> 'befristet',         // Enddatum !!!
	//    'label' => 'Dauer der Befristung'
    // ],
    // 'application_start' => [
    //   'interamt' => 'datePosted', 
    //   'univis'=> ''
    // ],
    // 'application_end' => [
    //   'interamt' => 'DatumBewerbungsfrist',
    //   'univis' => 'enddate',
    //   'label' => 'Bewerbungsschluss'
    // ],
    // 'application_link' => [
    //   'interamt' => '', 
    //   'univis'=> ''
    // ],
    // 'employer_organization' => [
    //  'interamt' => 'StellenangebotBehoerde',
    //  'univis' => 'orgunits/orgunit',   // Verschachtelt strukturiert !!!
	//  'label' => 'Organisationseinheit'
    // ],
    // 'employer_street' => [
    //   'interamt' => 'EinsatzortStrasse',
    //  'univis'=> '',
	//  'label' => 'Straße'
    // ],
    // 'employer_postalcode' => [
    //   'interamt' => 'EinsatzortPLZ',
    //   'univis'=> '',
    //   'label' => 'PLZ'
    // ],
    // 'employer_city' => [
    //   'interamt' => 'EinsatzortOrt',
    //   'univis'=> '',
    //   'label' => 'Ort'
    // ],
    // 'employer_district' => [
    //   'interamt' => '', 
    //   'univis'=> '',
	//	  'label' => ''
    // ],
    // 'contact_title' => [
    //   'interamt' => '', 
    //   'univis'=> '',
	//	  'label' => ''
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

