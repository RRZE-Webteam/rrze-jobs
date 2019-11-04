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
  return [
    'interamt' => [
      [
        'name'    => 'orgid',
        'label'   => __("orgID", 'rrze-jobs'),
        'desc'    => __('Enter the ID of your organization', 'rrze-jobs'),
        'type'    => 'text',
        'default' => '2217'
      ]
    ],
    'univis' => [
      [
        'name'    => 'orgid',
        'label'   => __("orgID", 'rrze-jobs'),
        'desc'    => __('Enter the ID of your organization', 'rrze-jobs'),
        'type'    => 'text',
        'default' => '420100'
      ]
    ],
  ];
}

/**
 * Gibt die API-URL zurück.
 * @return array
 */
function getURL(&$provider, $urltype) {
	$ret = [
		'interamt' => [
			'urllist' => 'https://www.interamt.de/koop/app/webservice_v2?partner=',
			'urlsingle' => 'https://www.interamt.de/koop/app/webservice_v2?id=' 
		],
		'univis' => [
			'urllist' => 'http://univis.uni-erlangen.de/prg?search=positions&department=',
			'urlsingle' => ''
		]
  ];
  
  return $ret[$provider][$urltype];
}


/**
 * Gibt die Zuordnung "Feld zu Schnittstellenfeld" zurück.
 * @return array
 * 'interamt' => Feld der Schnittstelle zu Interamt
 * 'univis'=>  Feld der Schnittstelle zu UnivIS
 * 'label => so wird das Feld in der Anwendung angezeigt
 */
function getMap( &$provider, $type ){
  // $map = [
  //   'node' => [
  //     'interamt' => 'Stellenangebote', 
  //     'univis'=> 'Position',
  //     'label' => 'Knotenpunkt' // Knotenpunkt im JSON ab dem Jobs aufgelistet werden
  //   ], 
  //   'job_id' => [
  //     'interamt' => 'Id', 
  //     'univis'=> '',
  //     'label' => 'Job ID'
  //   ], 
  //   'job_type' => [
  //     'interamt' => 'Kennung', 
  //     'univis'=> 'type2',
  //     'label' => 'Job Typ'
  //   ], 
  //   'job_employmenttype' => [
  //     'interamt' => 'Teilzeit', 
  //     'univis'=> 'type2',
  //     'label' => 'Vollzeit / Teilzeit'
  //     ],
  //   'job_title' => [
  //     'interamt' => 'StellenBezeichnung', 
  //     'univis'=> 'title',
	//     'label' => 'Stellenbezeichnung'
  //   ],
  //   'job_description' => [
  //     'interamt' => 'Beschreibung', 
  //     'univis'=> 'desc1',
  //     'label' => 'Beschreibung'
  //   ],
  //   'job_qualifications' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //     'label' => 'Qualifikationen'
  //   ],
  //   'job_education' => [
  //     'interamt' => '', 
  //     'univis'=> 'desc2',
  //     'label' => 'Ausbildung'
  //   ],
  //   'job_experience' => [
  //     'interamt' => '', 
  //     'univis'=> 'desc3',
  //     'label' => 'Berufserfahrung'
  //   ],
  //   'job_benefits' => [
  //     'interamt' => '', 
  //     'univis'=> 'desc4',
  //     'label' => 'Benefits'
  //   ],
  //   'job_category' => [
  //     'interamt' => 'FachrichtungCluster', //ggf. auch 'Fachrichtung' ?? 
  //     'univis'=> 'group',
  //     'label' => 'Berufsgruppe'
  //   ],
  //   'job_salary_from' => [
  //     'interamt' => array('Bezahlung', 'Entgelt'), // 'Bezahlung->{Entgelt}'
  //     'univis'=> 'vonbesold',
	//     'label' => 'Tarifebene von'
  //   ],
  //   'job_salary_to' => [
  //     'interamt' => '',
  //     'univis'=> 'bisbesold',
  //     'label' => 'Tarifebene bis'
  //   ],
  //   'job_unit' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //     'label' => 'Unit'
  //   ],
  //   'job_label' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //     'label' => 'Job Label'
  //   ],
  //   'job_workhours' => [
  //     'interamt' => 'WochenarbeitszeitArbeitnehmer', 
  //     'univis'=> 'wstunden',
	//     'label' => 'Wochenarbeitszeit'
  //   ],
  //   'job_start' => [
  //     'interamt' => 'DatumBesetzungZum', 
  //     'univis'=> 'start',
  //   	'label' => 'Besetzung zum'
  //   ],
  //   'job_end' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //     'label' => 'Job Ende'
  //   ],
  //   'job_limitation' => [
  //     'interamt' => 'BeschaeftigungDauer', 
  //     'univis'=> 'type1',
 	//     'label' => 'Befristung'
  //  ],
  //   'job_limitation_duration' => [      // Befristung Dauer
	//     'interamt' => 'BefristetFuer',  // Anzahl Monate !!!
	//     'univis'=> 'befristet',         // Enddatum !!!
	//     'label' => 'Dauer der Befristung'
  //   ],
  //   'application_start' => [
  //     'interamt' => 'datePosted', 
  //     'univis'=> '',
  //     'label' => 'Bewerbungsstart'
  //   ],
  //   'application_end' => [
  //     'interamt' => 'DatumBewerbungsfrist', 
  //     'univis'=> 'enddate',
  //     'label' => 'Bewerbungsschluss'
  //   ],
  //   'application_link' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //     'label' => 'Link zur Bewerbung'
  //   ],
  //   'employer_organization' => [
  //     'interamt' => 'StellenangebotBehoerde', 
  //     'univis' => '',   // orgunits/orgunit Verschachtelt strukturiert !
  //     'label' => 'Organisationseinheit',
  //   ],
  //   'employer_street' => [
  //     'interamt' => 'EinsatzortStrasse', 
  //     'univis'=> '',
	//     'label' => 'Straße'
  //   ],
  //   'employer_postalcode' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //     'label' => 'PLZ'
  //   ],
  //   'employer_city' => [
  //     'interamt' => 'EinsatzortOrt', 
  //     'univis'=> '',
  //     'label' => 'Ort'
  //   ],
  //   'employer_district' => [
  //     'interamt' => '', 
  //     'univis'=> '',
	// 	  'label' => 'Bezirk'
  //   ],
  //   'contact_title' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //     'label' => 'Ansprechpartner Titel'
  //   ],
  //   'contact_firstname' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //   	'label' => 'Ansprechpartner Vorname'
  //   ],
  //   'contact_lastname' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //     'label' => 'Ansprechpartner Nachname'
  //   ],
  //   'contact_tel' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //   	'label' => 'Ansprechpartner Telefonnummer'
  //   ],
  //   'contact_mobile' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //   	'label' => 'Ansprechpartner Mobilnummer'
  //   ],
  //   'contact_email' => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //   	'label' => 'Ansprechpartner E-Mail'
  //   ],
  //   'contact_link'  => [
  //     'interamt' => '', 
  //     'univis'=> '',
  //   	'label' => 'Ansprechpartner Link'
  //   ]
  // ];

  $map_single = [
    'job_id' => [
      'interamt' => 'Id', 
      'univis'=> '',
      'label' => 'Job ID'
    ], 
    'job_type' => [
      'interamt' => 'Kennung', 
      'univis'=> '',
      'label' => 'Job Typ'
    ], 
    'job_title' => [
      'interamt' => 'Stellenbezeichnung', 
      'univis'=> '',
	    'label' => 'Stellenbezeichnung'
    ],
    'employer_organization' => [
      'interamt' => 'StellenangebotBehoerde', 
      'univis'=> '',
      'label' => 'Organisationseinheit',
    ],
    'contact_link'  => [
      'interamt' => 'HomepageBehoerde', 
      'univis'=> '',
    	'label' => 'Ansprechpartner Link'
    ],
    'employer_street' => [
      'interamt' => array('Einsatzort', 'EinsatzortStrasse'), 
      'univis'=> '',
	    'label' => 'Straße'
    ],
    'employer_postalcode' => [
      'interamt' => array('Einsatzort', 'EinsatzortPLZ'), 
      'univis'=> '',
      'label' => 'PLZ'
    ],
    'employer_city' => [
      'interamt' => array('Einsatzort', 'EinsatzortOrt'), 
      'univis'=> '',
      'label' => 'Ort'
    ],
    'employer_district' => [
      'interamt' => 'BeschaeftigungBereichBundesland', 
      'univis'=> '',
		  'label' => 'Bezirk'
    ],
    'job_salary_from' => [
      'interamt' => 'TarifEbeneVon',
      'univis'=> '',
	    'label' => 'Tarifebene von'
    ],
    'job_salary_to' => [
      'interamt' => 'TarifEbeneBis',
      'univis'=> '',
      'label' => 'Tarifebene bis'
    ],
    'job_qualifications' => [
      'interamt' => 'Qualifikation', 
      'univis'=> '',
      'label' => 'Qualifikationen'
    ],
    'job_education' => [
      'interamt' => 'Ausbildung', 
      'univis'=> 'desc2',
      'label' => 'Ausbildung'
    ],
    'job_employmenttype' => [
      'interamt' => 'Teilzeit', 
      'univis'=> '',
      'label' => 'Vollzeit / Teilzeit'
      ],
      'job_workhours' => [
        'interamt' => 'WochenarbeitszeitArbeitnehmer', 
        'univis'=> '',
        'label' => 'Wochenarbeitszeit'
      ],
      'job_limitation' => [
        'interamt' => 'BeschaeftigungDauer', 
        'univis'=> '',
        'label' => 'Befristung'
     ],
     'job_limitation_duration' => [      // Befristung Dauer
	    'interamt' => 'BefristetFuer',  // Anzahl Monate !!!
      'univis'=> '',
	    'label' => 'Dauer der Befristung'
    ],
    'application_start' => [
      'interamt' => 'DatumOeffentlichAusschreiben', 
      'univis'=> '',
      'label' => 'Bewerbungsstart'
    ],
    'application_end' => [
      'interamt' => 'DatumBewerbungsfrist', 
      'univis'=> '',
      'label' => 'Bewerbungsschluss'
    ],
    'job_start' => [
      'interamt' => 'DatumBesetzungZum', 
      'univis'=> '',
    	'label' => 'Besetzung zum'
    ],
    'contact_title' => [
      'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerAnrede'),
      'univis'=> '',
      'label' => 'Ansprechpartner Titel'
    ],
    'contact_firstname' => [
      'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerVorname'),
      'univis'=> '',
    	'label' => 'Ansprechpartner Vorname'
    ],
    'contact_lastname' => [
      'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerNachname'), 
      'univis'=> '',
      'label' => 'Ansprechpartner Nachname'
    ],
    'contact_tel' => [
      'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerTelefon'),
      'univis'=> '',
    	'label' => 'Ansprechpartner Telefonnummer'
    ],
    'contact_mobile' => [
      'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerMobil'), 
      'univis'=> '',
    	'label' => 'Ansprechpartner Mobilnummer'
    ],
    'contact_email' => [
      'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerEMail'), 
      'univis'=> '',
    	'label' => 'Ansprechpartner E-Mail'
    ],
    'job_description' => [
      'interamt' => 'Beschreibung', 
      'univis'=> '',
      'label' => 'Beschreibung'
    ],
    'application_link' => [
      'interamt' => 'BewerbungUrl', 
      'univis'=> '',
      'label' => 'Link zur Bewerbung'
    ]
  ];


  $map_list = [
    'node' => [
      'interamt' => 'Stellenangebote', 
      'univis'=> 'Position',
      'label' => 'Knotenpunkt' // Knotenpunkt im JSON ab dem Jobs aufgelistet werden
    ], 
    'job_id' => [
      'interamt' => 'Id', 
      'univis'=> '',
      'label' => 'Job ID'
    ], 
    'job_type' => [
      'interamt' => '', 
      'univis'=> 'type2',
      'label' => 'Job Typ'
    ], 
    'employer_organization' => [
      'interamt' => 'Behoerde', 
      'univis'=> 'orgname',
      'label' => 'Organisationseinheit',
    ],
    'job_title' => [
      'interamt' => 'StellenBezeichnung', 
      'univis'=> 'title',
	    'label' => 'Stellenbezeichnung'
    ],
    'job_salary_from' => [
      'interamt' => array('Bezahlung', 'Entgelt'),
      'univis'=> 'vonbesold',
	    'label' => 'Tarifebene von'
    ],
    'job_salary_to' => [
      'interamt' => '',
      'univis'=> 'bisbesold',
	    'label' => 'Tarifebene von'
    ],
    'employer_postalcode' => [
      'interamt' => array('Ort', 'Plz'), 
      'univis'=> '',
      'label' => 'PLZ'
    ],
    'employer_city' => [
      'interamt' => array('Ort', 'Stadt'), 
      'univis'=> '',
      'label' => 'Ort'
    ],
    'job_limitation' => [
      'interamt' => '', 
      'univis'=> 'type1',
 	    'label' => 'Befristung'
   ],
  'application_start' => [
      'interamt' => array('Daten', 'Eingestellt'), 
      'univis'=> 'start',
      'label' => 'Bewerbungsstart'
    ],
    'job_description' => [
      'interamt' => '', 
      'univis'=> 'desc1',
      'label' => 'Beschreibung'
    ],
    'application_end' => [
      'interamt' => array('Daten', 'Bewerbungsfrist'), 
      'univis'=> 'enddate',
      'label' => 'Bewerbungsschluss'
    ],
    'job_category' => [
      'interamt' => '',
      'univis'=> 'group',
      'label' => 'Berufsgruppe'
    ],
    'job_experience' => [
      'interamt' => '', 
      'univis'=> 'desc2',
      'label' => 'Berufserfahrung'
    ],
    'job_benefits' => [
      'interamt' => '', 
      'univis'=> 'desc4',
      'label' => 'Benefits'
    ]
  ];


  $provider_map = array();
  $map = ( $type == 'list' ? $map_list : $map_single );
  foreach ($map as $key => $val) {
    $provider_map[$key] = $val[$provider]; 
  }
  
  return $provider_map;
}

