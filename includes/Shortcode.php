<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;
use function RRZE\Jobs\Config\getMap;
use function RRZE\Jobs\Config\getURL;
use function RRZE\Jobs\Config\getFields;
use function RRZE\Jobs\Config\fillMap;
use function RRZE\Jobs\Config\getPersons;
use function RRZE\Jobs\Config\formatUnivIS;
use function RRZE\Jobs\Config\isInternAllowed;


class Shortcode {
    private $provider = '';
    private $count = 0;


    /**
     * Shortcode-Klasse wird instanziiert.
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action( 'init',  [$this, 'jobs_block_init'] );
	    if ( !is_plugin_active('fau-jobportal/fau-jobportal.php') ) {
		    add_shortcode( 'jobs', [ $this, 'jobsHandler' ], 10, 2 );
	    }
    }

    /**
     * Enqueue der Skripte.
     */
    public function enqueue_scripts() {
        wp_register_style('jobs-shortcode', plugins_url('assets/css/jobs-shortcode.css', plugin_basename(RRZE_PLUGIN_FILE)));

        if (file_exists(WP_PLUGIN_DIR.'/rrze-elements/assets/css/rrze-elements.min.css')) {
	        wp_register_style( 'rrze-elements', plugins_url() . '/rrze-elements/assets/css/rrze-elements.min.css' );
        }
    }

    private function getProviders() {
        $this->providers = array();
        $options = get_option( RRZE_JOBS_TEXTDOMAIN );

        if (!empty( $options )) {
            foreach ( $options as $key => $value ) {
                $parts = explode('_', $key);
                if ( count( $parts ) == 3 ) {
                    $this->providers[$parts[1]][$parts[2]] = $value;
                }
            }
          }
        return $this->providers;
    }

    private function getSalary( &$map ){
        $salary = '';
        if ( isset( $map['job_salary_to'] ) ) {
            if ( isset( $map['job_salary_from'] ) && ( $map['job_salary_from'] != $map['job_salary_to'] ) ){
                $salary = $map['job_salary_from'] . ' - ' . $map['job_salary_to'];
            }
        }elseif ( isset( $map['job_salary_from'] ) ) {
            $salary = $map['job_salary_from'];
        }
        return $salary;
    }

	private function getDescription( &$map ){
    	$description = '';
    	switch ($this->provider) {
    		case 'univis';
			    $description =
				    (isset($map['job_description_introduction']) ? '<p>'.nl2br($map['job_description_introduction']).'</p>' : '')
				    . (isset($map['job_title']) ? '<h3>'.$map['job_title'].'</h3>' : '')
				    . (isset($map['job_description']) ? '<h4>Das Aufgabengebiet umfasst u. a.:</h4><p>'.nl2br($map['job_description']).'</p>' : '')
				    . (isset($map['job_qualifications']) ? '<h4>Notwendige Qualifikation:</h4><p>'.nl2br($map['job_qualifications']).'</p>' : '')
				    . (isset($map['job_qualifications_nth']) ? '<h4>Wünschenswerte Qualifikation:</h4><p>'.nl2br($map['job_qualifications_nth']).'</p>' : '')
				    . (isset($map['job_benefits']) ? '<h4>Bemerkungen:</h4><p>'.nl2br($map['job_benefits']).'</p>' : '')
				    . (isset($map['application_link']) ? '<p>'.$map['application_link'].'</p>' : '');
    		    break;
		    case 'interamt':
			    $description = isset($map['job_description']) ? $map['job_description'] : $map['job_title'];
		    	break;
	    }
	    return $description;
	}

    private function transform_date( $mydate ) {
        return date( 'Y-m-d', strtotime( $mydate ) );
    }

    private function sortArrayByField( $myArray, $fieldname, $order ){
        usort( $myArray, function ( $a, $b ) use ($fieldname, $order) {
            return ( $order == 'ASC' ? strtolower( $a[$fieldname] ) <=> strtolower( $b[$fieldname] ) : strtolower( $b[$fieldname] ) <=> strtolower( $a[$fieldname] ) );
        });
        return $myArray;
    }

    public function jobsHandler( $atts ) {
        $atts = shortcode_atts([
            'provider' => '',
            'orgid' => '',
            'orgids' => '',
            'jobid' => '',
            'internal' => 'exclude',
            'limit' => '',
            'orderby' => 'job_title',
            'order' => 'ASC',
            'fallback_apply' => ''
        ], $atts, 'jobs');

        $this->provider = strtolower( sanitize_text_field( $atts['provider'] ) );
        $output = '';

        if ( isset( $this->provider ) && ( $this->provider != '' ) ){
            $this->provider = $atts['provider']; 
            return $this->jobs_shortcode( $atts );
        }else{
            return '<p>' . __('Please specify the correct job portal in the shortcode attribute <code>provider=""</code>.', 'rrze-jobs') . '</p>';
        }
    }
    

    public function jobs_shortcode( $atts ) {
        $this->count = 0;
        $this->providers = $this->getProviders();

        if ( isset( $atts['orgid'] ) && $atts['orgid'] != '' ) {
            $atts['orgids'] = $atts['orgid'];
        }

        if ( isset($atts['orgids']) && $atts['orgids'] != '' ) {
            $orgids = sanitize_text_field( $atts['orgids'] );
        } else {
            $orgids = $this->providers[$this->provider]['orgid'];
        }
        $jobid = sanitize_text_field( $atts['jobid'] );

        if ( $orgids == '' && $jobid == '' ) {
            return '<p>' . __('Please provide an organisation or job ID!', 'rrze-jobs') . '</p>';
        }

        $output = $this->get_jobs( $jobid, $orgids, $atts['limit'], $atts['orderby'], $atts['order'], $atts['internal'] , $atts['fallback_apply'] );

        wp_enqueue_style('rrze-elements');
        wp_enqueue_style('jobs-shortcode');
        wp_enqueue_script('jobs-shortcode');

        return $output;
    }


    private function get_jobs( $jobid = '', $orgids = '', $limit, $orderby, $order, $internal, $fallback_apply ) {
        $output = '';
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = ( has_custom_logo() ? wp_get_attachment_url($custom_logo_id) : '' );

        $maps = array();

        $orgids = explode( ',', $orgids );
        

        foreach ( $orgids as $orgid ){
            $orgid = trim( $orgid );

            // Check if orgid is an integer and ignore if not (we don't output a message because there might be more than one orgid) - fun-fact: UnivIS delivers their complete database entries if orgid contains characters
            if ( strval($orgid) !== strval(intval($orgid)) ) {
                continue;
            }
            $api_url = getURL( $this->provider, 'list') . trim( $orgid );
            $data = file_get_contents( $api_url );

            if ( !$data ) {
                return '<p>' . __('Cannot connect to API at the moment. Link is ', 'rrze-jobs') . '<a href="' . $myurl . '" target="_blank">' . $myurl . '</a></p>';
            }
            $data = json_decode( utf8_encode( $data ), true);

            if ( $this->provider == 'univis' ){
                $persons = $data['Person'];
                $persons = getPersons( $persons );
            }

            $node = ( $this->provider == 'interamt' ? 'Stellenangebote' : 'Position' );
            $jobs = $data[$node];

            // Loop through jobs
            if ( isset( $jobs ) ) {
                if ( $this->provider == 'univis' && isset( $data[$node]['id'] ) ) {
                    $jobs = array( $data[$node] );
                }

                foreach ( $jobs as $jobData ) {
                    $map = getMap( $this->provider );
                    if ( $this->provider == 'interamt' ){
                        if ( $jobData['Daten']['Bewerbungsfrist'] < date('d.m.Y') ){
                            continue 2;
                        }
                        $id = $jobData['Id'];
                        $urlSingle = getURL( $this->provider, 'single') . $id;
                        $data = file_get_contents( $urlSingle );

                        if ( !$data ) {
                            return '<p>Die Schnittstelle ist momentan nicht erreichbar.</p>';
                        }

                        $data = json_decode( utf8_encode( $data ), true );
                        $job = fillMap( $map, $data );
                    } else {
                        $job = fillMap( $map, $jobData );
                    }

                    // Skip if outdated
                    if ( $job['application_end'] < date('Y-m-d' ) ) {
                        continue;
                    }

                    // Convert dates
                    $fields = ['job_start', 'application_start', 'application_end'];
                    foreach ( $fields as $field ) {
                        if ( isset( $job[$field] ) ) {
                            $enDate = date( 'Y-m-d', strtotime( $job[$field] ) );
                            if ( $enDate == '1970-01-01' ){
                                $enDate = $job[$field];
                            }
                            $job[$field] = $enDate;
                        }
                    }

                    if ( isset( $job['job_start'] ) ){
                        // field might contain a date - if it contains a date, it must be in english format
                        if ( preg_match( "/\d{4}-\d{2}-\d{2}/", $job['job_start'], $parts ) ) {
                            $job['job_start_sort'] = $parts[0];
                        } elseif ( preg_match( "/(\d{2}).(\d{2}).(\d{4})/", $job['job_start'], $parts ) ) {
                            $job['job_start_sort'] = $parts[3] . '-' . $parts[2] . '-' . $parts[1];
                         } else {
                            // field contains only a string - check if it is ASAP
                            $val = strtolower( $job['job_start'] );
                            if ( strpos( $val, 'sofort' ) !== FALSE || strpos( $val, 'bald' ) !== FALSE || strpos( $val, 'glich' ) !== FALSE  || strpos( $val, 'asap' ) !== FALSE  || strpos( $val, 'a.s.a.p.' ) !== FALSE  ) { 
                                // sofort, ab sofort, baldmöglich, baldmöglichst, zum nächstmöglichen Zeitpunkt, nächstmöglich, frühst möglich, frühestmöglich, asap, a.s.a.p.
                                $job['job_start_sort'] = '0'; 
                            } else {
                                $job['job_start_sort'] = $job[$field];
                            }
                        }
                    } 

                    if ( $this->provider == 'univis') {
                        if ( isset( $job['job_limitation_reason'] ) ) {
                            switch ( $job['job_limitation_reason'] ){
                                case 'vertr':
                                    $job['job_limitation_reason'] = 'Vertretung';
                                    break;
                                case 'schwanger':
                                    $job['job_limitation_reason'] = 'Mutterschutzvertretung';
                                    break;
                                case 'eltern':
                                    $job['job_limitation_reason'] = 'Mutterschutz- / Elternzeitvertretung';
                                    break;
                                case 'krankh':
                                    $job['job_limitation_reason'] = 'Krankheitsvertretung';
                                    break;
                                case 'forsch':
                                    $job['job_limitation_reason'] = 'befristetes Forschungsvorhaben';
                                    break;
                                case 'zeitb':
                                    $job['job_limitation_reason'] = 'Beamtenschaft auf Zeit';
                                    break;
                            }
                        }

                        $job['job_description'] =
                            ( isset( $job['job_description_introduction'] ) ? '<p>' . formatUnivIS( $job['job_description_introduction'] ) .'</p>' : '' )
                            . ( isset( $job['job_title'] ) ? '<p class="job-title">'.$job['job_title'].'</p>' : '' )
                            . ( isset( $job['job_description'] ) ? '<h2>Das Aufgabengebiet umfasst u. a.</h2><p>' . formatUnivIS( $job['job_description'] ) . '</p>' : '')
                            . ( isset( $job['job_qualifications'] ) ? '<h2>Notwendige Qualifikation</h2><p>'. formatUnivIS( $job['job_qualifications'] ) . '</p>' : '' )
                            . (isset( $job['job_qualifications_nth'] ) ? '<h2>Wünschenswerte Qualifikation</h2><p>' . formatUnivIS( $job['job_qualifications_nth'] ) . '</p>' : '')
                            . ( isset( $job['job_benefits'] ) ? '<h2>Bemerkungen</h2><p>'. formatUnivIS( $job['job_benefits'] ) . '</p>' : '')
                            . ( isset( $job['application_link'] ) ? '<p>' . formatUnivIS( $job['application_link'] ) . '</p>' : '' );

                        if ( isset( $job['job_employmenttype'] ) ) {
                            $job['job_employmenttype'] = ucfirst( $job['job_employmenttype'] ) . 'zeit';
                        }

                        // $job['job_category'] = $URLs[$url];
                        // $job['job_category_grouped'] = $URLs[$url];

                        $person = $persons[$jobData['acontact']['UnivISRef']['key']];
                        foreach ( $person as $key => $val ){
                            $job[$key] = $val;
                        }
                    } elseif ( $this->provider == 'interamt' ) {
                        if ( isset( $job['job_category'] ) ) {
                            if ( $job['job_category'] == 'Bildung und Wissenschaft' ) {
                                $job['job_category_grouped'] = 'wiss';
                            } else {
                                $job['job_category_grouped'] = 'n-wiss';
                            }
                        } 
                        
                        if ( strpos($job['job_title'], 'Auszubildende' ) !== FALSE) {
                            $job['job_category_grouped'] = 'azubi';
                        } elseif ( strpos( $job['job_title'], 'Wissenschaftliche Hilfs' ) !== FALSE) {
                            $job['job_category_grouped'] = 'hiwi';
                        }

                        $start_application_string = strpos( $job['job_description'], 'Bitte bewerben Sie sich' );
                        if ( $start_application_string === FALSE ){
                            $start_application_string = strpos( $job['job_description'], 'Senden Sie Ihre Bewerbung' );
                        }
                        if ( $start_application_string !== FALSE && $start_application_string > 100 ) {
                            $application_string = substr( $job['job_description'], $start_application_string );
                            $job['application_link'] = strip_tags( html_entity_decode( $application_string ), '<a><br><br /><b><strong><i><em>');
                        }
                    }

                    if ( isset( $job['application_link'] ) && $job['application_link'] != '' ) {
                        $job['application_link'] = formatUnivIS( $job['application_link'] );
                    }

                    if ( isset( $job['employer_organization'] ) ) {
                        $job['employer_organization'] = nl2br( str_replace( 'Zentrale wissenschaftliche Einrichtungen der FAU' . PHP_EOL, '', $job['employer_organization'] ) );
                    }
                    if ( isset( $job['application_link'] ) ) {
                        preg_match_all( "/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+[a-zA-Z]+/i", $job['application_link'], $matches );
                        if ( !empty( $matches[0] ) ) {
                            $application_email = $matches[0][0];
                            $job['application_email'] = $application_email;
                        }
                    }
                    if ( isset( $job['job_salary_from'] ) ) {
                        $job['job_salary_search'] = ( int ) filter_var( $job['job_salary_from'], FILTER_SANITIZE_NUMBER_INT );
                    } elseif ( isset( $job['job_salary_to'] ) ) {
                        $job['job_salary_search'] = ( int ) filter_var( $job['job_salary_to'], FILTER_SANITIZE_NUMBER_INT );
                    }
                    if ( !isset( $job['job_salary_search'] ) || $job['job_salary_search'] == 0 ){
                        $job['job_salary_search'] = 99;
                    }
                    $job['job_salary_search'] = abs( $job['job_salary_search'] );

                    if ( isset( $job['job_limitation'] ) || isset( $job['job_limitation_duration'] ) ) {
                        $job['job_limitation_boolean'] = 1;
                    } else {
                        $job['job_limitation_boolean'] = 0;
                    }
                    $maps[] = $job;
                }
            }
        }


        if ( count( $maps ) > 0 ){
            // check if $orderby is a field we know
            if ( !array_key_exists( $orderby, $map) ){
                $correct_vals = implode(', ', array_keys( $map ) );
                return '<p>' . __( 'Parameter "orderby" is not correct. Please use one of the following values: ', 'rrze-jobs') . $correct_vals;
            }

            $maps = $this->sortArrayByField( $maps, $orderby, $order );
            $intern_allowed = isInternAllowed();

            $shortcode_items = '';
            foreach ($maps as $map) {
                $shortcode_item_inner = '';
                // If parameter "limit" is reached stop output
                if ( ( $limit > 0 ) && ( $this->count >= $limit ) )  {
                    break 1;
                }

                // Skip internal job offers if necessary
                switch ( $internal ){
                    case 'only' :  
                        if ( ( !$intern_allowed ) || ( $intern_allowed && !isset( $map['job_intern'] ) ) ){
                            continue 2;
                        }
                        break;
                    case 'exclude' :  
                        if ( isset( $map['job_intern'] ) ){
                            continue 2;
                        }
                        break;
                    case 'include' :  
                        if ( !$intern_allowed && isset( $map['job_intern'] ) ) {
                            continue 2;
                        }
                        break;
                    default: 
						continue 2;
						break;
                }

                // Skip if outdated
                if ( isset( $map['application_end'] ) && (bool)strtotime( $map['application_end'] ) === true) {
                    $map['application_end'] = (new \DateTime( $map['application_end'] ) )->format('Y-m-d');
                }
                if ($map['application_end'] < date('Y-m-d')) {
                    continue;
                }

                $salary = $this->getSalary( $map );
	            $description = $this->getDescription($map, $this->provider);
                $description = str_replace('"', '', $description);
                $datePosted = ( isset($map['application_start']) ? $this->transform_date( $map['application_start'] ) : date('Y-m-d') ); 

	            /*$shortcode_item_inner .= '<li itemscope itemtype="https://schema.org/JobPosting"><a href="?provider=' . $this->provider . '&jobid=' . $map['job_id'] . '" data-provider="' . $this->provider . '" data-jobid= "' . $map['job_id'] . '" data-fallback_apply="' . $fallback_apply . '" class="joblink">'
                    .'<span itemprop="title">' . $map['job_title'] . ( $salary != '' ? ' (' . $salary . ')' : '' ) . '</span></a>';
                $shortcode_item_inner .= '<meta itemprop="datePosted" content="' . $datePosted . '" />'
                    .(isset($map['job_education']) ? '<meta itemprop="educationRequirements" content="' . strip_tags(htmlentities( $map['job_education'])) . '" />': '')
                    .(isset($map['job_type']) ? '<meta itemprop="employmentType" content="' . ( $map['job_type'] == 'teil' ? 'Teilzeit' : 'Vollzeit' ) . '" />': '') 
                    .(isset($map['job_unit']) ? '<meta itemprop="employmentUnit" content="' . $map['job_unit'] . '" />':'')
                    .'<meta itemprop="estimatedSalary" content="' . $salary . '" />'
                    .(isset($map['job_employmenttype']) ? '<meta itemprop="employmentType" content="' . $map['job_employmenttype'] . '" />':'')
                    .(isset($map['job_experience']) ? '<meta itemprop="experienceRequirements" content="' . strip_tags(htmlentities( $map['job_experience'] )) . '" />': '')
                    .(isset($map['employer_organization']) ? '<span itemprop="hiringOrganization" itemscope itemtype="http://schema.org/Organization"><meta itemprop="name" content="' . $map['employer_organization'] . '" /><meta itemprop="logo" content="' . RRZE_JOBS_LOGO . '" /></span>': '')
                    .(isset($map['job_benefits']) ? '<meta itemprop="jobBenefits" content="' . htmlentities( $map['job_benefits']) . '" />': '')
                    .(isset($map['job_start']) ? '<meta itemprop="jobStartDate" content="' . $this->transform_date( $map['job_start'] ) . '" />' : '')
                    .(isset($map['job_category']) ? '<meta itemprop="occupationalCategory" content="' . $map['job_category'] . '" />': '')
                    .(isset($map['job_qualifications']) ? '<meta itemprop="qualifications" content="' . strip_tags(htmlentities( $map['job_qualifications']) ). '" />': '')
                    // skills
                    .(isset($map['job_title']) ? '<meta itemprop="title" content="' . $map['job_title'] . '" />': '')
                    .(isset($map['application_end']) ? '<meta itemprop="validThrough" content="' . $map['application_end'] . '" />': '') 
                    .(isset($map['job_workhours']) ? '<meta itemprop="workHours" content="' . $map['job_workhours'] . '" />': '') 
                    . '<meta itemprop="description" content="' . strip_tags(htmlentities( $description)) . '" />'
                    . '<span itemprop="jobLocation" itemscope itemtype="http://schema.org/Place">'
                    . '<meta itemprop="logo" content="' . $logo_url . '" />'
                    . '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">'
                    . '<meta itemprop="name" content="' . $map['employer_organization'] . '" />';
                $shortcode_item_inner .= ( isset( $map['contact_street'] ) ? '<meta itemprop="streetAddress" content="' .  $map['contact_street'] . '" />' : '' );
                $shortcode_item_inner .= ( isset( $map['contact_postalcode'] ) ? '<meta itemprop="postalCode" content="' . $map['contact_postalcode'] . '" />' : '' );
                $shortcode_item_inner .= ( isset( $map['contact_city'] ) ? '<meta itemprop="addressLocality" content="' . $map['contact_city'] . '" />' : '' );
                $shortcode_item_inner .= ( isset( $map['contact_city'] ) ? '<meta itemprop="addressRegion" content="' . RRZE_JOBS_ADDRESS_REGION . '" />' : '' );
                $shortcode_item_inner .= ( isset( $map['contact_link'] ) ? '<meta itemprop="url" content="' . $map['contact_link'] . '" />' : '' );
                $shortcode_item_inner .= '</span></span></li>';*/

                $this->count++;



                $sidebar = '';
                $sidebar .= do_shortcode( '<div>[button link="' . $application_link . '" width="full"]Jetzt bewerben![/button]</div>' );

                $sidebar .= '<div class="rrze-jobs-single-application"><dl>';
                if ( isset( $map['application_end']) ) {
                    $sidebar .= '<dt>' . __('Bewerbungsschluss', 'rrze-jobs') . '</dt>'
                        . '<dd itemprop="validThrough" content="' . $map['application_end'] . '">' . $date_deadline . '</dd>';
                }
                if ( isset( $map['job_type'] ) ) {
                    $sidebar .= '<dt>' . __( 'Referenz', 'rrze-jobs' ) . '</dt>' . '<dd>' . $map['job_type'] . '</dd>';
                }

                $sidebar .= '<dt>' . __( 'Bewerbung', 'rrze-jobs' ) . '</dt>';
                $sidebar .= '<dd>' . $application_link . '</dd></div>';
                $sidebar .= '<div class="rrze-jobs-single-keyfacts"><dl>';
                $sidebar .= '<h3>' . __('Details','rrze-jobs') . '</h3>'
                    . '<dt>'.__('Stellenbezeichnung','rrze-jobs') . '</dt><dd itemprop="title">' . $map['job_title'] . '</dd>';
                if ( ( isset( $map['job_start']) ) && ( $map['job_start'] != '' ) ) {
                    $sidebar .= '<dt>'. __('Besetzung zum','rrze-jobs') . '</dt><dd>' . $map['job_start'] . '</dd>';
                }
                if ( ( isset( $map['employer_city'] ) ) && ( !empty( $map['employer_city'] ) ) ) {
                    $sidebar .= '<dt>'.__('Einsatzort','rrze-jobs'). '</dt>';
                    if ( isset( $map['employer_organization']) ) {
                        $sidebar .= '<dd itemprop="hiringOrganization">' . $map['employer_organization'] . '<br />';
                    }
                    if ( isset( $map['employer_street']) ) {
                        $sidebar .= $map['employer_street'] . '<br />';
                    }
                    if ( isset( $map['employer_postalcode']) ) {
                        $sidebar .=  $map['employer_postalcode'] . ' ';
                    }
                    $sidebar .=  '<span itemprop="jobLocation" itemscope itemtype="http://schema.org/Place" >'
                        . '<meta itemprop="logo" content="' . $logo_url . '" />'
                        . '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">'
                        . '<meta itemprop="name" content="' . $map['employer_organization'] . '" />';
                    $sidebar .= ( isset( $map['employer_street'] ) ? '<meta itemprop="streetAddress" content="' .  $map['employer_street'] . '" />' : '' );
                    $sidebar .= ( isset( $map['employer_postalcode'] ) ? '<meta itemprop="postalCode" content="' . $map['employer_postalcode'] . '" />' : '' );
                    $sidebar .= ( isset( $map['employer_city'] ) ? '<meta itemprop="addressLocality" content="' . $map['employer_city'] . '" />' : '' );
                    $sidebar .= ( isset( $map['employer_district'] ) ? '<meta itemprop="addressRegion" content="' . $map['employer_district'] . '" />' : '' );
                    $sidebar .= ( isset( $map['contact_link'] ) ? '<meta itemprop="url" content="' . $map['contact_link'] . '" />' : '' );
                    $sidebar .= '</span></span></dd>';
                }

                if ( $salary != '' ) {
                    $sidebar .= '<dt>'.__('Entgelt','rrze-jobs') . '</dt><dd>' . $salary . '</dd>';
                }
                if ( isset( $map['job_employmenttype'] ) ) {
                    $sidebar .= '<dt>'.__('Teilzeit / Vollzeit','rrze-jobs') . '</dt><dd itemprop="employmentType">' . $map['job_employmenttype'] . '</dd>';
                }
                if ( isset( $map['job_workhours'] ) ) {
                    $sidebar .= '<dt>'.__('Wochenarbeitszeit','rrze-jobs') . '</dt><dd itemprop="workHours">' . number_format( $map['job_workhours'], 1, ',', '.') . ' h</dd>';
                }
                if ( ( isset( $map['job_limitation'] ) ) && ( $map['job_limitation'] == 'befristet' ) ) {
                    $sidebar .= '<dt>'.__('Befristung (Monate)','rrze-jobs') . '</dt><dd>' . $map['job_limitation_duration'] . '</dd>';
                }

                if ( ( isset( $map['contact_lastname'] ) ) && ( $map['contact_lastname'] != '' ) ) {
                    $sidebar .= '<dt>'.__('Ansprechpartner für weitere Informationen','rrze-jobs') . '</dt>'
                        . '<dd>' . ( isset( $map['contact_title'] ) ? $map['contact_title'] . ' ' : '' ) . ( isset( $map['contact_firstname'] ) ? $map['contact_firstname'] . ' ' : '' ) . ( isset( $map['contact_lastname'] ) ? $map['contact_lastname'] : '' );
                    if ( ( isset( $map['contact_tel'] ) ) && ( $map['contact_tel'] != '' ) ) {
                        $sidebar.= '<br />' . __('Telefon', 'rrze-jobs') . ': ' . $map['contact_tel'];
                    }
                    if ( ( isset( $map['contact_mobile'] ) ) && ( $map['contact_mobile'] != '' ) ) {
                        $sidebar.= '<br />' . __('Mobil', 'rrze-jobs') . ': ' . $map['contact_mobile'];
                    }
                    if ( ( isset( $map['contact_email'] ) ) && ( $map['contact_email'] != '' ) ) {
                        $sidebar.= '<br />' . __('E-Mail', 'rrze-jobs') . ': <a href="mailto:' . $map['contact_email'] . '">' . $map['contact_email'] . '</a>';
                    }
                    $sidebar .= '</dd>';
                }
                $sidebar .= '</dl>';

                $sidebar .= '<div><meta itemprop="datePosted" content="' . ( isset( $map['application_start'] ) ? $map['application_start'] : '' ) . '" />'
                    . '<meta itemprop="qualifications" content="' . ( isset ($map['job_qualifications'] ) ? $map['job_qualifications'] : '' ) . '" />'
                    . '<meta itemprop="url" content="' . get_permalink() . '?jobid=' . $map['job_id']. '" />'
                    . '</div>';
                $sidebar .= '</div>';






                $shortcode_item_inner .= '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
                $shortcode_item_inner .= do_shortcode('[three_columns_two]' . ($this->provider == 'univis' ? $this->formatUnivIS( $description ) : $description ) .'[/three_columns_two]' . '[three_columns_one_last]' . $sidebar . '[/three_columns_one_last][divider]');
                $options = get_option(RRZE_JOBS_TEXTDOMAIN);
                if (isset($options['rrze-jobs_job_notice']) && $options['rrze-jobs_job_notice'] != '') {
                    $shortcode_item_inner .= '<hr /><div>' . strip_tags( $options['rrze-jobs_job_notice'], '<p><a><br><br /><b><strong><i><em>' ) . '</div>';
                }
                $shortcode_item_inner .= '</div>';


                $shortcode_items .= do_shortcode('[collapse title="' . $map['job_title'] . '"]' . $shortcode_item_inner . '[/collapse]');
            }

        }

        if ( $this->count == 0 ) {
            return '<p>' . __('API does not return any data.', 'rrze-jobs') . '</a></p>';
        }
    
        return do_shortcode('[collapsibles expand-all-link="true"]' . $shortcode_items . '[/collapsibles]');;
    }


    public function jobs_block_init() {
        // Skip block registration if Gutenberg is not enabled/merged.
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
        $dir = dirname( __FILE__ );
        $index_js = '../assets/js/jobs-block.js';
        
        wp_register_script(
            'jobsEditor',
            plugins_url( $index_js, __FILE__ ),
            array(
                'wp-blocks',
                'wp-i18n',
                'wp-element',
                'wp-components',
                'wp-editor'
            ),
            filemtime( "$dir/$index_js" )
        );

        register_block_type( 'rrze-jobs/jobs', array(
            'editor_script'  => 'jobsEditor',
            'render_callback'  => [$this, 'jobsHandler'],
            'attributes'         =>   [
                "provider" => [
                    'default' => 'univis',
                    'type' => 'string'
                ],
                "orgids" => [
                    'default' => '',
                    'type' => 'string'
                ],
                "jobid" => [
                    'default' => '',
                    'type' => 'string'
                ],
                "internal" => [
                    'default' => 'exclude',
                    'type' => 'string'
                ],
                "limit" => [
                    'default' => '',
                    'type' => 'integer'
                ],
                "orderby" => [
                    'default' => 'job_title',
                    'type' => 'string'
                ],
                "order" => [
                    'default' => 'DESC',
                    'type' => 'string'
                ],
                "fallback_apply" => [
                    'default' => '',
                    'type' => 'string'
                    ]                    
                ]
            ) 
        );
    }
}