<?php

namespace RRZE\Jobs\Config;


defined('ABSPATH') || exit;



/**
 * Gibt der Name der Option zurück.
 * @return array [description]
 */
function getOptionName() {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if ( is_plugin_active('fau-jobportal/fau-jobportal.php') ){
		return 'fau-jobportal';
	} else {
		return 'rrze-jobs';
	}
}


/**
 * Prüft, ob interne Jobs synchronisiert bzw angezeigt werden dürfen
 * @return boolean
 */
function isInternAllowed() {
	$ret = FALSE;
	$allowedHosts = array(
		'uni-erlangen.de'
	);
	$remoteAdr = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );
	if ( $remoteAdr == 'localhost' ){
		$ret = TRUE;
	} else {
		$parts = explode( '.', $remoteAdr );
		$cnt = count( $parts );
		if ($cnt >= 3){
			$domain = $parts[$cnt-2] . '.' . $parts[$cnt-1];
			$ret = in_array( $domain, $allowedHosts );
		}
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
			'id'    => 'basic',
			'title' => __('General Settings', 'rrze-jobs')
		]
	];
}

/**
 * Gibt die Einstellungen der Optionsfelder zurück.
 * @return array [description]
 */
function getFields() {
	return [
		'basic' => [
			[
				'name'    => 'interamt_orgid',
				'label'   => __("orgIDs Interamt", 'rrze-jobs'),
				'desc'    => __('Enter the ID(s) of your organization(s)', 'rrze-jobs'),
				'type'    => 'text',
				'default' => ''
			],
			[
				'name'    => 'univis_orgid',
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
				'default' => '<p>Für alle Stellenausschreibungen gilt: Die Friedrich-Alexander-Universität fördert die berufliche Gleichstellung der Frauen. Frauen werden deshalb ausdrücklich aufgefordert, sich zu bewerben.</p>
<p>Schwerbehinderte im Sinne des Schwerbehindertengesetzes werden bei gleicher fachlicher Qualifikation und persönlicher Eignung bevorzugt berücksichtigt, wenn die ausgeschriebene Stelle sich für Schwerbehinderte eignet. Details dazu finden Sie in der jeweiligen Ausschreibung unter dem Punkt "Bemerkungen".</p>
<p>Bei Wunsch der Bewerberin, des Bewerbers, kann die Gleichstellungsbeauftragte zum Bewerbungsgespräch hinzugezogen werden, ohne dass der Bewerberin, dem Bewerber dadurch Nachteile entstehen.</p>
<p>Ausgeschriebene Stellen sind grundsätzlich teilzeitfähig, es sei denn, im Ausschreibungstext erfolgt ein anderweitiger Hinweis.</p>'
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
			'urllist' => 'https://www.interamt.de/koop/app/webservice_v2?partner=',
			'urlsingle' => 'https://www.interamt.de/koop/app/webservice_v2?id='
		],
		'univis' => [
			'urllist' => 'https://univis.uni-erlangen.de/prg?search=positions&show=json&closed=1&department=',
			// 'urllist' => 'http://univis.uni-erlangen.de/prg?search=positions&show=json&closed=0&department=', // liefert eine andere Datenstruktur als mit closed=1
			'urlsingle' => 'https://univis.uni-erlangen.de/prg?search=positions&closed=1&show=json&id='
			// 'urlsingle' => 'http://univis.uni-erlangen.de/prg?search=positions&closed=0&show=json&id=' // liefert eine andere Datenstruktur als mit closed=1
		]
	];

	return $ret[$provider][$urltype];
}


/**
 * Füllt die Map mit Werten aus der Schnittstelle
 * @return array
 */
function  fillMap( &$map, &$job ) {
	$map_ret = array();
	foreach ($map as $k => $val){
		if ( is_array($val) ) {
			switch ( count( $val ) ) {
				case 2:
					if ( isset( $job->{$val[0]}->{$val[1]} ) ){
						$map_ret[$k] =  htmlentities( $job->{$val[0]}->{$val[1]} );
					}
					break;
				case 3:
					if ( isset( $job->{$val[0]}->{$val[1]}->{$val[2]} ) ){
						if (is_array($job->{$val[0]}->{$val[1]}->{$val[2]})) {
							$map_ret[ $k ] = htmlentities( implode(PHP_EOL, $job->{$val[0]}->{$val[1]}->{$val[2]} ));
						} else {
							$map_ret[ $k ] = htmlentities( $job->{$val[0]}->{$val[1]}->{$val[2]} );
						}
					}
					break;
				case 4:
					if ( isset( $job->{$val[0]}->{$val[1]}->{$val[2]}->{$val[3]} ) ){
						$map_ret[$k] =  htmlentities( $job->{$val[0]}->{$val[1]}->{$val[2]}->{$val[3]} );
					}
					break;
			}
		}elseif ( isset( $job->{$val} ) ) {
			$map_ret[$k] =  $job->{$val};
		}
	}
	return $map_ret;
}


/**
 * Gibt die Zuordnung "Feld zu Schnittstellenfeld" zurück.
 * @return array
 * 'interamt' => Feld der Schnittstelle zu Interamt
 * 'univis'=>  Feld der Schnittstelle zu UnivIS
 * 'label => so wird das Feld in der Anwendung angezeigt
 */
function getMap( $provider, $type ){
	$map_single = [
		'job_id' => [
			'interamt' => 'Id',
			'univis'=> array('Position', 'id'),
			'label' => 'Job ID'
		],
		'job_intern' => [
			'interamt' => '',
			'univis'=> array('Position', 'intern'),
			'label' => 'Intern'
		],
		'job_type' => [
			'interamt' => 'Kennung',
			'univis'=> '',
			'label' => 'Job Typ'
		],
		'job_title' => [
			'interamt' => 'Stellenbezeichnung',
			'univis'=> array('Position', 'title'),
			'label' => 'Stellenbezeichnung'
		],
		'employer_organization' => [
			'interamt' => 'StellenangebotBehoerde',
			'univis'=> array('Position', 'orgunits', 'orgunit'),
			'label' => 'Organisationseinheit'
		],
		'contact_link'  => [
			'interamt' => 'HomepageBehoerde',
			'univis'=> array('Person', 'locations', 'location', 'url'),
			'label' => 'Ansprechpartner Link'
		],
		'employer_street' => [
			'interamt' => array('Einsatzort', 'EinsatzortStrasse'),
			'univis'=> array('Person', 'locations', 'location', 'street'),
			'label' => 'Straße'
		],
		'employer_postalcode' => [
			'interamt' => array('Einsatzort', 'EinsatzortPLZ'),
			'univis'=> '',
			'label' => 'PLZ'
		],
		'employer_city' => [
			'interamt' => array('Einsatzort', 'EinsatzortOrt'),
			'univis'=> array('Person', 'locations', 'location', 'ort'),
			'label' => 'Ort'
		],
		'employer_district' => [
			'interamt' => 'BeschaeftigungBereichBundesland',
			'univis'=> '',// existiert nicht
			'label' => 'Bezirk'
		],
		'job_salary_from' => [
			'interamt' => 'TarifEbeneVon',
			'univis'=> array('Position', 'vonbesold'),
			'label' => 'Tarifebene von'
		],
		'job_salary_to' => [
			'interamt' => 'TarifEbeneBis',
			'univis'=> array('Position', 'bisbesold'),
			'label' => 'Tarifebene bis'
		],
		'job_qualifications' => [
			'interamt' => 'Qualifikation',
			'univis'=> array('Position', 'desc2'),
			'label' => 'Qualifikationen'
		],
		'job_qualifications_nth' => [
			'interamt' => '',
			'univis'=>  array('Position', 'desc3'),
			'label' => 'Wünschenswerte Qualifikationen'
		],
		'job_education' => [
			'interamt' => 'Ausbildung',
			'univis'=> '',// Fehlt noch
			'label' => 'Ausbildung'
		],
		'job_employmenttype' => [
			'interamt' => 'Teilzeit',
			'univis'=> array('Position', 'type2'),
			'label' => 'Vollzeit / Teilzeit'
		],
		'job_workhours' => [
			'interamt' => 'WochenarbeitszeitArbeitnehmer',
			'univis'=> '',// Fehlt noch
			'label' => 'Wochenarbeitszeit'
		],
		'job_limitation' => [
			'interamt' => 'BeschaeftigungDauer',
			'univis'=> '',// Fehlt noch
			'label' => 'Befristung'
		],
		'job_limitation_duration' => [      // Befristung Dauer
			'interamt' => 'BefristetFuer',  // Anzahl Monate !!!
			'univis'=> array('Position', 'befristet'),
			'label' => 'Dauer der Befristung'
		],
		'application_start' => [
			'interamt' => 'DatumOeffentlichAusschreiben',
			'univis'=> '',// Fehlt noch
			'label' => 'Bewerbungsstart'
		],
		'application_end' => [
			'interamt' => 'DatumBewerbungsfrist',
			'univis'=> array('Position', 'enddate'),
			'label' => 'Bewerbungsschluss'
		],
		'job_start' => [
			'interamt' => 'DatumBesetzungZum',
			'univis'=> array('Position', 'start'),
			'label' => 'Besetzung zum'
		],
		'contact_title' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerAnrede'),
			'univis'=> array('Person', 'title'),
			'label' => 'Ansprechpartner Titel'
		],
		'contact_firstname' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerVorname'),
			'univis'=> array('Person', 'firstname'),
			'label' => 'Ansprechpartner Vorname'
		],
		'contact_lastname' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerNachname'),
			'univis'=> array('Person', 'lastname'),
			'label' => 'Ansprechpartner Nachname'
		],
		'contact_tel' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerTelefon'),
			'univis'=> array('Person', 'locations', 'location', 'tel'),
			'label' => 'Ansprechpartner Telefonnummer'
		],
		'contact_mobile' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerMobil'),
			'univis'=> '', // existiert nicht
			'label' => 'Ansprechpartner Mobilnummer'
		],
		'contact_email' => [
			'interamt' => array('ExtAnsprechpartner', 'ExtAnsprechpartnerEMail'),
			'univis'=> array('Person', 'locations', 'location', 'email'),
			'label' => 'Ansprechpartner E-Mail'
		],
		'contact_street' => [
			'interamt' => array('Einsatzort', 'EinsatzortStrasse'),
			'univis'=> array('Person', 'locations', 'location', 'street'),
			'label' => 'Straße'
		],
		'contact_postalcode' => [
			'interamt' => array('Einsatzort', 'EinsatzortPLZ'),
			'univis'=> array('Person', 'locations', 'location', 'ort'),
			'label' => 'PLZ'
		],
		'contact_city' => [
			'interamt' => array('Einsatzort', 'EinsatzortOrt'),
			'univis'=> array('Person', 'locations', 'location', 'ort'),
			'label' => 'Ort'
		],
		'job_description' => [
			'interamt' => 'Beschreibung',
			'univis'=> array('Position', 'desc1'),
			'label' => 'Beschreibung'
		],
		'job_description_introduction' => [
			'interamt' => '',
			'univis'=> array('Position', 'desc5'),
			'label' => 'Beschreibung - Einleitung'
		],
		'job_experience' => [
			'interamt' => '',
			'univis'=>  array('Position', 'desc2'),
			'label' => 'Berufserfahrung'
		],
		'job_benefits' => [
			'interamt' => '',
			'univis'=>  array('Position', 'desc4'),
			'label' => 'Benefits'
		],
		'application_link' => [
			'interamt' => 'BewerbungUrl',
			'univis'=> array('Position', 'desc6'),
			'label' => 'Link zur Bewerbung'
		],
		'job_category' => [
			'interamt' => '',
			'univis'=> array('Position', 'group'),
			'label' => 'Berufsgruppe'
		],
	];


	$map_list = [
		'node' => [
			'interamt' => 'Stellenangebote',
			'univis'=> 'Position',
			'label' => 'Knotenpunkt' // Knotenpunkt im JSON ab dem Jobs aufgelistet werden
		],
		'job_id' => [
			'interamt' => 'Id',
			'univis'=> 'id',
			'label' => 'Job ID'
		],
		'job_intern' => [
			'interamt' => '',
			'univis'=> 'intern',
			'label' => 'Intern'
		],
		'job_employmenttype' => [
			'interamt' => '',
			'univis'=> 'type2',
			'label' => 'Vollzeit / Teilzeit'
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

