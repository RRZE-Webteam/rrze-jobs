<?php

namespace RRZE\Jobs\Config;


defined('ABSPATH') || exit;
define( 'RRZE_JOBS_LOGO', plugins_url( 'assets/img/fau.gif', __DIR__ ) );
define( 'RRZE_JOBS_ADDRESS_REGION', 'Bayern' );


function getShortcodeSettings(){
	return [
		'block' => [
			'blocktype' => 'rrze-jobs/jobs', 
			'blockname' => 'jobs',
			'title' => 'RRZE Jobs',
			'category' => 'widgets',
			'icon' => 'admin-users',
			'show_block' => 'content', // 'right' or 'content' 
			'message' => __( 'Find the settings on the right side', 'rrze-jobs' )
		],
		'provider' => [
			'field_type' => 'select',
			'values' => [
				'interamt' => __( 'Interamt', 'rrze-jobs' ),
				'univis' => __( 'UnivIS', 'rrze-jobs' )
			],
			'default' => 'univis',
			'label' => __( 'Provider', 'rrze-jobs' ),
			'type' => 'string'
		],
		'orgids' => [
			'field_type' => 'text',
			'default' => '',
			'label' => __( 'OrgID(s)', 'rrze-jobs' ),
			'type' => 'string'
		],
		'jobid' => [
			'field_type' => 'text',
			'default' => NULL,
			'label' => __( 'Job ID', 'rrze-jobs' ),
			'type' => 'number'
		],
		'internal' => [
			'field_type' => 'select',
			'values' => [
				'exclude' => __( 'exclude internal job offers', 'rrze-jobs' ),
				'include' => __( 'include internal job offers', 'rrze-jobs'),
				'only' => __( 'only internal job offers', 'rrze-jobs' )
			],
			'default' => 'exclude',
			'label' => __( 'Internal job offers', 'rrze-jobs' ),
			'type' => 'string'
		],
		'limit' => [
			'field_type' => 'text',
			'values' => '',
			'default' => 0,
			'label' => __( 'Number of job offers', 'rrze-jobs' ),
			'type' => 'number'
		],
		'orderby' => [
			'field_type' => 'select',
			'values' => [
				'job_title' => __( 'Job title', 'rrze-jobs' ),
				'application_start' => __( 'Application start', 'rrze-jobs' ),
				'application_end' => __( 'Application end', 'rrze-jobs' ),
				'job_start' => __( 'Job start', 'rrze-jobs' )
			],
			'default' => 'job_title',
			'label' => __( 'Order by', 'rrze-jobs' ),
			'type' => 'string'
		],
		'order' => [
			'field_type' => 'radio',
			'values' => [
				'ASC' => __( 'Ascending', 'rrze-jobs' ),
				'DESC' => __( 'Descending', 'rrze-jobs' )
			],
			'selected' => 'DESC',
			'default' => 'DESC',
			'label' => __( 'Order', 'rrze-jobs' ),
			'type' => 'string'
		],
		'fallback_apply' => [
			'field_type' => 'text',
			'values' => '',
			'default' => '',
			'label' => __( 'Default application link', 'rrze-jobs' ),
			'type' => 'string'
			]                    
		];
}


/**
 * Prüft, ob interne Jobs synchronisiert bzw angezeigt werden dürfen
 * @return boolean
 */
function isInternAllowed() {
	$ret = FALSE;
	$allowedHost = 'uni-erlangen.de';
	$remoteAdr = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );

	if ( ( strpos( $remoteAdr, $allowedHost ) !== FALSE ) || ( is_admin() ) ) {
		$ret = TRUE;
	}
	return $ret;
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
 * Gibt die Einstellungen der Optionsbereiche zurück.
 * @return array [description]
 */
function getSections() {
	return [
		[
			'id'    => 'rrze-jobs',
			'title' => __('Settings', 'rrze-jobs')
		]
	];
}

/**
 * Gibt die Einstellungen der Optionsfelder zurück.
 * @return array [description]
 */
function getFields() {
	return [
		'rrze-jobs' => [
			[
				'name'    => 'orgids_interamt',
				'label'   => __("orgIDs Interamt", 'rrze-jobs'),
				'desc'    => __('Enter the ID(s) of your organization(s)', 'rrze-jobs'),
				'type'    => 'text',
				'default' => '2217'
			],
			[
				'name'    => 'orgids_univis',
				'label'   => __("orgIDs UnivIS", 'rrze-jobs'),
				'desc'    => __('Enter the ID(s) of your organization(s)', 'rrze-jobs'),
				'type'    => 'text',
				'default' => ''
			],
			[
				'name'    => 'job_notice',
				'label'   => __("Notice", 'rrze-jobs'),
				'desc'    => __('This notice will be dispayed below each job offer.', 'rrze-jobs'),
				'type'    => 'textarea',
				'size'    => 'large',
				'default' => '<p>TESTFür alle Stellenausschreibungen gilt: Die Friedrich-Alexander-Universität fördert die berufliche Gleichstellung der Frauen. Frauen werden deshalb ausdrücklich aufgefordert, sich zu bewerben.</p>
<p>Schwerbehinderte im Sinne des Schwerbehindertengesetzes werden bei gleicher fachlicher Qualifikation und persönlicher Eignung bevorzugt berücksichtigt, wenn die ausgeschriebene Stelle sich für Schwerbehinderte eignet. Details dazu finden Sie in der jeweiligen Ausschreibung unter dem Punkt "Bemerkungen".</p>
<p>Bei Wunsch der Bewerberin, des Bewerbers, kann die Gleichstellungsbeauftragte zum Bewerbungsgespräch hinzugezogen werden, ohne dass der Bewerberin, dem Bewerber dadurch Nachteile entstehen.</p>
<p>Ausgeschriebene Stellen sind grundsätzlich teilzeitfähig, es sei denn, im Ausschreibungstext erfolgt ein anderweitiger Hinweis.</p>'
			],
            [
                'name'    => 'no_jobs_message',
                'label'   => __("No Jobs Message", 'rrze-jobs'),
                'desc'    => __('This message will be displayed if the API does not return any data.', 'rrze-jobs'),
                'type'    => 'textarea',
                'default' => __('No job offers found.', 'rrze-jobs')
            ],
            [
                'name'    => 'jobs_page',
                'label'   => __('Jobs Page', 'rrze-jobs'),
                'desc'    => __('Link target, used on Public Displays only.', 'rrze-jobs'),
                'type'    => 'selectPage',
                'default' => ''
            ]
		]
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
			'content' => [
				'<p>' . __('Find instructions at ', 'rrze-jobs') .  '<a href="https://www.wordpress.rrze.fau.de/plugins/fau-und-rrze-plugins/jobs" target="_blank">https://www.wordpress.rrze.fau.de/plugins/fau-und-rrze-plugins/jobs</a></p>'
			],
			'title'     => __('Overview', 'rrze-jobs'),
			'sidebar'   => sprintf('<p><strong>%1$s:</strong></p><p><a href="https://blogs.fau.de/webworking">RRZE Webworking</a></p><p><a href="https://github.com/RRZE Webteam">%2$s</a></p>', __('For more information', 'rrze-jobs'), __('RRZE Webteam on Github', 'rrze-jobs'))
		]
	];
}



/**
 * Gibt die API-URL zurück.
 * @return array
 */
function getURL(&$provider, $urltype) {
	$ret = [
		'interamt' => [
			'list' => 'https://www.interamt.de/koop/app/webservice_v2?partner=',
			'single' => 'https://www.interamt.de/koop/app/webservice_v2?id='
		],
		'univis' => [
			'list' => 'https://univis.uni-erlangen.de/prg?search=positions&show=json&closed=1&department=',
			'single' => 'https://univis.uni-erlangen.de/prg?search=positions&closed=1&show=json&id='
		]
	];

	return $ret[$provider][$urltype];
}


/**
 * Füllt die Map mit Werten aus der Schnittstelle
 * @return array
 */
function fillMap( &$map, &$job ) {
	$map_ret = array();
	
	  foreach ( $map as $k => $val ){
		  if ( is_array( $val ) ) {
			  switch ( count( $val ) ) {
				  case 2:
					  if ( isset( $job[$val[0]][$val[1]] ) ){
						  $map_ret[$k] =  htmlentities( $job[$val[0]][$val[1]] );
					  }
					  break;
				  case 3:
					  if ( isset( $job[$val[0]][$val[1]][$val[2]] ) ){
						  if ( is_array( $job[$val[0]][$val[1]][$val[2]] ) ) {
							  $map_ret[ $k ] = htmlentities( implode( PHP_EOL, $job[$val[0]][$val[1]][$val[2]] ) );
						  } else {
							  $map_ret[ $k ] = htmlentities( $job[$val[0]][$val[1]][$val[2]] );
						  }
					  }
					  break;
				  case 4:
					  if ( isset( $job[$val[0]][$val[1]][$val[2]][$val[3]] ) ){
						  $map_ret[$k] =  htmlentities( $job[$val[0]][$val[1]][$val[2]][$val[3]] );
					  }
					  break;
			  }
		  }elseif ( isset( $job[$val] ) ) {
			  $map_ret[$k] =  $job[$val];
		  }
	  }
	  return $map_ret;
  }
  

  function getPersons( $p ) {
	// if there is only one entry UnivIS returns 'key' as another field instead of the value as key
	if ( isset( $p['key'] )) {
		$tmp = array();
		$tmp[$p['key']] = $p;
		$p = $tmp;
	}

	if ( !isset($p) ){
		return;
	}

  $keys = array_keys( $p );
  $persons = array();
 
  // Reason for "if field <-> elseif field[0]" : sometimes (I didn't figure out when) entries in locations are surrounded by brackets [], sometimes they are missing. 
  foreach ( $keys as $key ){
    if ( isset( $p[$key]['title'] ) ){
      $persons[$key]['contact_title'] = $p[$key]['title'];
    } elseif ( isset( $p[$key]['atitle'] ) ){
		$persons[$key]['contact_title'] = $p[$key]['atitle'];
	}
    if ( isset( $p[$key]['firstname'] ) ){
      $persons[$key]['contact_firstname'] = $p[$key]['firstname'];
    }
    if ( isset( $p[$key]['lastname'] ) ){
      $persons[$key]['contact_lastname'] = $p[$key]['lastname'];
    }
    if ( isset( $p[$key]['locations']['location']['tel'] ) ){
		$persons[$key]['contact_tel'] = $p[$key]['locations']['location']['tel'];
	} elseif ( isset( $p[$key]['locations']['location'][0]['tel'] ) ){
		$persons[$key]['contact_tel'] = $p[$key]['locations']['location'][0]['tel'];
	}
	if ( isset( $p[$key]['locations']['location']['email'] ) ){
    	$persons[$key]['contact_email'] = $p[$key]['locations']['location']['email'];
	} elseif ( isset( $p[$key]['locations']['location'][0]['email'] ) ){
		$persons[$key]['contact_email'] = $p[$key]['locations']['location'][0]['email'];
	}
  	if ( isset( $p[$key]['locations']['location']['street'] ) ){
		$persons[$key]['contact_street'] = $p[$key]['locations']['location']['street'];
	} elseif ( isset( $p[$key]['locations']['location'][0]['street'] ) ){
      	$persons[$key]['contact_street'] = $p[$key]['locations']['location'][0]['street'];
    }
    if ( isset( $p[$key]['locations']['location']['url'] ) ){
		$persons[$key]['contact_link'] = $p[$key]['locations']['location']['url'];
	} elseif ( isset( $p[$key]['locations']['location'][0]['url'] ) ){
    	$persons[$key]['contact_link'] = $p[$key]['locations']['location'][0]['url'];
    }
    if ( isset( $p[$key]['locations']['location']['ort']) ){
      $parts = explode( ' ', $p[$key]['locations']['location']['ort'] ); 
      if ( sizeof( $parts) == 2 ){
    	$persons[$key]['contact_postalcode'] = $parts[0];
        $persons[$key]['contact_city'] = $parts[1];
      } else {
        $persons[$key]['contact_city'] = $p[$key]['locations']['location']['ort'];
      }
    } elseif ( isset( $p[$key]['locations']['location'][0]['ort']) ){
		$parts = explode( ' ', $p[$key]['locations']['location'][0]['ort'] ); 
		if ( sizeof( $parts) == 2 ){
			$persons[$key]['contact_postalcode'] = $parts[0];
		  	$persons[$key]['contact_city'] = $parts[1];
		} else {
		  	$persons[$key]['contact_city'] = $p[$key]['locations']['location'][0]['ort'];
		}
	}
  }

  return $persons;
}


function getMap( $provider ){
	$map = [
		'job_id' => [
			'interamt' => 'Id',
			'univis'=> 'id',
			'label' => 'Job ID'
		],
		'application_start' => [
			'interamt' => 'DatumOeffentlichAusschreiben',
			'univis'=> '',  // fehlt
			'label' => 'Bewerbungsstart'
		],
		'application_end' => [
			'interamt' => 'DatumBewerbungsfrist',
			'univis'=> 'enddate',
			'label' => 'Bewerbungsschluss'
		],
		'application_link' => [
			'interamt' => 'BewerbungUrl',
			'univis'=> 'desc6',
			'label' => 'Link zur Bewerbung'
		],
		'job_intern' => [
			'interamt' => '', // fehlt
			'univis'=> 'intern',
			'label' => 'Intern'
		],
		'job_type' => [
			'interamt' => 'Kennung',
			'univis'=> '', // fehlt
			'label' => 'Kennung'
		],
		'job_title' => [
			'interamt' => 'Stellenbezeichnung',
			'univis'=> 'title',
			'label' => 'Stellenbezeichnung'
		],
		'job_start' => [
			'interamt' => 'DatumBesetzungZum',
			'univis'=> 'start',
			'label' => 'Besetzung zum'
		],
		'job_limitation' => [
			'interamt' => 'BeschaeftigungDauer',
			'univis'=> 'type1',
			'label' => 'Befristung'
		],
		'job_limitation_duration' => [      // Befristung Dauer
			'interamt' => 'BefristetFuer',  // Anzahl Monate !!!
			'univis'=> 'befristet', 
			'label' => 'Dauer der Befristung'
		],
		'job_limitation_reason' => [ 
			'interamt' => '', 
			'univis'=> 'type3', 
			'label' => 'Grund der Befristung'
		],
		'job_salary_from' => [
			'interamt' => 'TarifEbeneVon',
			'univis'=> 'vonbesold',
			'label' => 'Tarifebene von'
		],
		'job_salary_to' => [
			'interamt' => 'TarifEbeneBis',
			'univis'=> 'bisbesold',
			'label' => 'Tarifebene bis'
		],
		'job_qualifications' => [
			'interamt' => 'Qualifikation',
			'univis'=> 'desc2',
			'label' => 'Qualifikationen'
		],
		'job_qualifications_nth' => [
			'interamt' => '', // fehlt
			'univis'=>  'desc3',
			'label' => 'Wünschenswerte Qualifikationen'
		],
		'job_employmenttype' => [
			'interamt' => 'Teilzeit',
			'univis'=> 'type2',
			'label' => 'Vollzeit / Teilzeit'
		],
		'job_workhours' => [
			'interamt' => 'WochenarbeitszeitArbeitnehmer',
			'univis'=> 'wstunden',
			'label' => 'Wochenarbeitszeit'
		],
		'job_category' => [
			'interamt' => 'FachrichtungCluster',
			'univis'=> 'group',
			'label' => 'Berufsgruppe'
		],
		'job_description' => [
			'interamt' => 'Beschreibung',
			'univis'=> 'desc1',
			'label' => 'Beschreibung'
		],
		'job_description_introduction' => [
			'interamt' => '', // fehlt
			'univis'=> 'desc5',
			'label' => 'Beschreibung - Einleitung'
		],
		'job_experience' => [
			'interamt' => '', // fehlt
			'univis'=> 'desc2',
			'label' => 'Berufserfahrung'
		],
		'job_benefits' => [
			'interamt' => '', // fehlt
			'univis'=> 'desc4',
			'label' => 'Benefits'
		],
		'employer_organization' => [
			'interamt' => 'StellenangebotBehoerde',
			'univis'=> 'orgname',
			'label' => 'Organisationseinheit',
		],
		'employer_street' => [
			'interamt' => array('Einsatzort', 'EinsatzortStrasse'),
			'univis'=> array('Person', 'locations', 'location', 'street'),
			'label' => 'Straße'
		],
		'employer_postalcode' => [
			'interamt' =>  array('Einsatzort', 'EinsatzortPLZ'),
			'univis'=> '', // fehlt
			'label' => 'PLZ'
		],
		'employer_city' => [
			'interamt' => array('Einsatzort', 'EinsatzortOrt'),
			'univis'=> array('Person', 'locations', 'location', 'ort'),
			'label' => 'Ort'
		],
		'employer_district' => [
			'interamt' => 'BeschaeftigungBereichBundesland',
			'univis'=> '', // fehlt
			'label' => 'Bezirk'
		],
		'contact_link'  => [
			'interamt' => 'HomepageBehoerde',
			'univis'=> '', // see fillPersons()
			'label' => 'Ansprechpartner Link'
		],
		'contact_title' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerAnrede'),
			'univis'=> '', // see fillPersons()
			'label' => 'Ansprechpartner Titel'
		],
		'contact_firstname' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerVorname'),
			'univis'=> '', // see fillPersons()
			'label' => 'Ansprechpartner Vorname'
		],
		'contact_lastname' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerNachname'),
			'univis'=> '', // see fillPersons()
			'label' => 'Ansprechpartner Nachname'
		],
		'contact_tel' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerTelefon'),
			'univis'=> '', // see fillPersons()
			'label' => 'Ansprechpartner Telefonnummer'
		],
		'contact_mobile' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerMobil'),
			'univis'=> '', // fehlt
			'label' => 'Ansprechpartner Mobilnummer'
		],
		'contact_email' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerEMail'),
			'univis'=> '', // see fillPersons()
			'label' => 'Ansprechpartner E-Mail'
		],
		'contact_street' => [
			'interamt' => array('Einsatzort', 'EinsatzortStrasse'),
			'univis'=> '', // see fillPersons()
			'label' => 'Straße'
		],
		'contact_postalcode' => [
			'interamt' => array('Einsatzort', 'EinsatzortPLZ'),
			'univis'=> '', // see fillPersons()
			'label' => 'PLZ'
		],
		'contact_city' => [
			'interamt' => array('Einsatzort', 'EinsatzortOrt'),
			'univis'=> '', // see fillPersons()
			'label' => 'Ort'
		]
  ];
  
  $provider_map = array();
	foreach ($map as $key => $val) {
		$provider_map[$key] = $val[$provider];
	}

	return $provider_map;
}

function formatUnivIS( $txt ){
	$subs = array(
		'/^\-+\s+(.*)?/mi' => '<ul><li>$1</li></ul>',  // list 
		'/(<\/ul>\n(.*)<ul>*)+/' => '',  // list 
		// '/(<br \/>*)/mi' => '',  // <br />
		'/\*{2}/m' => '/\*/', // **
		'/_{2}/m' => '/_/', // __
		'/\|(.*)\|/m' => '<i>$1</i>',  // |itallic|
		'/_(.*)_/m' => '<sub>$1</sub>',  // H_2_O
		'/\^(.*)\^/m' => '<sup>$1</sup>',  // pi^2^
		'/\[(.*)\]\s?(<a.*>).*(<\/a>)/mi' => '$2$1$3', // [link text] <a ...>link</a>
		'/([^">]+)(mailto:)([^"\s>]+)/mi' => '$1<a href="mailto:$3">$3</a>', // find mailto:email@address.tld but not <a href="mailto:email@address.tld">mailto:email@address.tld</a>
		'/\*(.*)\*/m' => '<strong>$1</strong>', // *bold*
	);
	
	// return nl2br( preg_replace( array_keys( $subs ), array_values( $subs ), $txt ) );
	$txt = make_clickable( $txt );
	$txt = nl2br( $txt );
	return preg_replace( array_keys( $subs ), array_values( $subs ), $txt );
}

