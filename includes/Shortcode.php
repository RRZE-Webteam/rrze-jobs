<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;
use function RRZE\Jobs\Config\getMap;
use function RRZE\Jobs\Config\getURL;
use function RRZE\Jobs\Config\getFields;
use function RRZE\Jobs\Config\fillMap;

class Shortcode {
    private $groups = array(
        'wiss' => 'Wissenschaftliche Stellen (Wissenschaftlicher Dienst und Ärzte)',
        // 'Nichtwissenschaftliche Stellen (Verwaltungsdienst, Technischer DIenst, Pflege- und Funktionsdienst, Arbeitnehmer/innnen)',
        'tech' => 'Technischer DIenst',
        'verw' => 'Verwaltungsdienst',
        'arb' => 'Arbeitnehmer/innnen',
        'azubi' => 'Auszubildende',
        'hiwi' => 'Studentische Hilfskräfte',
        'other' => '?'
    ); // $job->group

    private $provider = '';


    /**
     * Shortcode-Klasse wird instanziiert.
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('jobs', [$this, 'jobsHandler'], 10, 2);
        add_action('wp_ajax_nopriv_rrze_jobs_ajax_function', [$this, 'rrze_jobs_ajax_function']);
        add_action('wp_ajax_rrze_jobs_ajax_function', [$this, 'rrze_jobs_ajax_function']);
        add_action( 'init',  [$this, 'jobs_block_init'] );
    }

    /**
     * Enqueue der Skripte.
     */
    public function enqueue_scripts() {
        wp_register_style('jobs-shortcode', plugins_url('assets/css/jobs-shortcode.css', plugin_basename(RRZE_PLUGIN_FILE)));
        wp_register_script('jobs-shortcode', plugins_url('assets/js/jobs-shortcode.js', plugin_basename(RRZE_PLUGIN_FILE)));
        wp_localize_script( 'jobs-shortcode', 'jobs_sc', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' )
            )
        );
        if (file_exists(WP_PLUGIN_DIR.'/rrze-elements/assets/css/rrze-elements.min.css')) {
	        wp_register_style( 'rrze-elements', plugins_url() . '/rrze-elements/assets/css/rrze-elements.min.css' );
        }
    }

    private function get_providers() {
        $providers = array();
        $options = get_option('rrze-jobs');
        if (!empty( $options )) {
            foreach ( $options as $key => $value ) {
                $parts = explode('_', $key);
                $providers[$parts[0]][$parts[1]] = $value;
            }
          }
        return $providers;
    }

    public function jobsHandler( $atts ) {
        $atts = shortcode_atts([
            'provider' => '',
            'department' => '',
            'jobtype' => '',
            'jobid' => '',
            'orderby' => '',
            'sort' => ''
        ], $atts, 'jobs');

        $provider = strtolower(sanitize_text_field($atts['provider']));
        $isInteramt = ( ( $provider == 'interamt' ) || ( $provider == 'all' ) ? TRUE : FALSE );
        $isUnivis = ( ( $provider == 'univis' ) || ( $provider == 'all' ) ? TRUE : FALSE );
        $output = '';

        if ( $isInteramt ) {
            $atts['provider'] = 'interamt';
            $this->provider = $atts['provider']; 
            $output = $this->jobs_shortcode( $atts );
        } 
        if ( $isUnivis ) {
            $atts['provider'] = 'univis';
            $this->provider = $atts['provider']; 
            $output .= $this->jobs_shortcode( $atts );
        } 
        if ( !$isInteramt && !$isUnivis ){
            return '<p>' . __('Please specify the correct job portal in the shortcode attribute <code>provider=""</code>.', 'rrze-jobs') . '</p>';
        }

        return $output;
    }
    

    public function jobs_shortcode( $atts ) {
        $providers = $this->get_providers();
        $orgids = explode( ',', $providers[$this->provider]['orgid'] );
        $jobid = sanitize_text_field( $atts['jobid'] );

        if ( $orgids[0] == '' && $jobid == '' ) {
            return '<p>' . __('Please provide an organisation or job ID!', 'rrze-jobs') . '</p>';
        }
        $output = '';
	    if ( !empty( $_GET['jobid'] ) ) {
		    $jobid = $_GET['jobid'];
		    $output .= '<p class="rrze-jobs-closelink-container"><a href="' . get_permalink() . '" class="view-all"><i class="fa fa-close" aria-hidden="true"></i> schließen</a></p>';
        }
        if ( $jobid != '' ) {
            $output .= $this->get_single_job( $this->provider, $jobid );
        }elseif ( $orgids[0] != '' ) {
            $output = '';
            foreach ( $orgids as $orgid ){
                $output .= $this->get_job_list( getURL($this->provider, 'urllist') . trim( $orgid ) );
            }
        }

        wp_enqueue_style('rrze-elements');
        wp_enqueue_style('jobs-shortcode');
        wp_enqueue_script('jobs-shortcode');

        return $output;
    }

    public function rrze_jobs_ajax_function() {
        $jobid = sanitize_text_field( $_POST['jobid'] );
        $parts = explode('_', $jobid);
        $responseData = $this->get_single_job( $parts[0], $parts[1] );
        echo json_encode($responseData);
        wp_die();
    }

    // public function store_job( &$provider, &$job ) {
    //     $post_id = wp_insert_post(
    //         array(
    //             'post_title'		=>	htmlentities( $job->StellenBezeichnung ) ,
    //             'post_name'		=>	$provider . '-' . $job->Id,
    //             'post_type'		=>	'post', // custom post type anlegen
    //             'post_author'	=>	1, // author -> admin?
    //             'comment_status'	=>	'closed',
    //             'ping_status'		=>	'closed',
    //             'post_status'		=>	'publish'
    //         )
    //     );

    //     $map = getMap( $provider );

    //     foreach( $map as $key => $val ) {
    //         $map[$key] = $job->$val;
    //         $val = ( $job->$val ? htmlentities( $job->$val ) : '');
    //         // update_post_meta( $post_id, $key, $val );
    //     }
    // }

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


    private function get_job_list( $api_url ) {
        $json = file_get_contents($api_url);
        if (!$json) {
            return '<p>' . __('Cannot connect to API at the moment. Link is ', 'rrze-jobs') . '<a href="' . $api_url . '" target="_blank">' . $api_url . '</a></p>';
        }
        $json = utf8_encode($json);
        $obj = json_decode($json);

        $output = '';
        $output .= '<ul class=\'rrze-jobs-list\'>';
    
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_meta = has_custom_logo() ? '<meta itemprop="image" content="' . wp_get_attachment_url($custom_logo_id) . '" />' : '';
    
        $map_template = getMap( $this->provider, 'list' );
        $node = $map_template['node'];
        unset( $map_template['node'] );
    
        if ( is_null( $obj ) ){
            return '<p>' . __('API does not return any data. Link is ', 'rrze-jobs') . '<a href="' . $api_url . '" target="_blank">' . $api_url . '</a></p>';
        } else {
            foreach ($obj->$node as $job) {
                $map = fillMap( $map_template, $job );
                $salary = $this->getSalary( $map );

                $output .= '<li itemscope itemtype="https://schema.org/JobPosting"><a href="?provider=' . $this->provider . '&jobid=' . $map['job_id']  . '" data-jobid="' . $this->provider . '_' . ( isset( $map['job_id'] ) ? $map['job_id'] : 'fehlt noch für univis' ) . '" class="joblink">'
                    .'<span itemprop="title">' . $map['job_title'] . ( $salary != '' ? ' (' . $salary . ')' : '' ) . '</span></a>';
                    $output .= $logo_meta 
                    .(isset($map['application_start']) ? '<meta itemprop="datePosted" content="' . $this->transform_date( $map['application_start'] ) . '" />': '')
                    .(isset($map['job_education']) ? '<meta itemprop="educationRequirements" content="' . $map['job_education'] . '" />': '')  
                    .(isset($map['job_type']) ? '<meta itemprop="employmentType" content="' . ( $map['job_type'] == 'teil' ? 'Teilzeit' : 'Vollzeit' ) . '" />': '') 
                    .(isset($map['job_unit']) ? '<meta itemprop="employmentUnit" content="' .$map['job_unit'] . '" />':'')
                    .'<meta itemprop="estimatedSalary" content="' . $salary . '" />'
                    .(isset($map['job_experience']) ? '<meta itemprop="experienceRequirements" content="' . $map['job_experience'] . '" />': '')
                    .(isset($map['employer_organization']) ? '<meta itemprop="hiringOrganization" content="' . $map['employer_organization'] . '" />': '')
                    .(isset($map['job_benefits']) ? '<meta itemprop="jobBenefits" content="' . $map['job_benefits'] . '" />': '')
                    .(isset($map['job_start']) ? '<meta itemprop="jobStartDate" content="' . $this->transform_date( $map['job_start'] ) . '" />' : '')
                    .(isset($map['job_category']) ? '<meta itemprop="occupationalCategory" content="' . $map['job_category'] . '" />': '')
                    .(isset($map['job_qualifications']) ? '<meta itemprop="qualifications" content="' . $map['job_qualifications'] . '" />': '')
                    // skills
                    .(isset($map['job_title']) ? '<meta itemprop="title" content="' . $map['job_title'] . '" />': '')
                    .(isset($map['application_end']) ? '<meta itemprop="validThrough" content="' . $map['application_end'] . '" />': '') 
                    .(isset($map['job_workhours']) ? '<meta itemprop="workHours" content="' . $map['job_workhours'] . '" />': '') 
                    .(isset($map['application_end']) ? '<meta itemprop="datePosted" content="' . $this->transform_date( $map['application_end'] ) . '" />': '')
                    .(isset($map['job_description']) ? '<meta itemprop="description" content="' . $map['job_description'] . '" />': '')
                    . '<span itemprop="jobLocation" itemscope itemtype="http://schema.org/Place" >'
                    . '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress" >'
                    .(isset($map['employer_postalcode']) ? '<meta itemprop="postalCode" content="' . $map['employer_postalcode'] . '" />': '')
                    .(isset($map['employer_city']) ? '<meta itemprop="addressLocality" content="' . $map['employer_city'] . '" />': '')
                    . '</span></li>';
            }
        }
    
        return $output;
    }
    

    public function get_single_job( $provider, $jobid ) {
        $api_url = getURL($provider, 'urlsingle') . $jobid;
        $json_job = file_get_contents($api_url);
        $json_job = utf8_encode($json_job);
        $job = json_decode($json_job);

        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = ( has_custom_logo() ? wp_get_attachment_url($custom_logo_id) : '' );

        $map_template = getMap( $provider, 'single' );
        $map = fillMap( $map_template, $job );

        $azubi = false;
        if ( ( isset( $map['job_title'] ) ) && ( strpos( $map['job_title'], 'Auszubildende' ) ) ) {
            $azubi = true;
        }
        $salary = $this->getSalary( $map );
        $application_email = 'RRZE-Bewerbungseingang@fau.de';
        
        if ( ( isset( $map['job_type'] ) ) && ( $map['job_type'] != 'keine' ) ) {
            $kennung_old = $map['job_type'];
            switch ( mb_substr( $map['job_type'], - 2 ) ) {
                case '-I':
                    $map['job_type'] = preg_replace( '/-I$/', '-W', $map['job_type'] );
                    break;
                case '-U':
                    $map['job_type'] = preg_replace( '/-U$/', '-W', $map['job_type'] );
                    break;
                default:
                    $map['job_type'] = $map['job_type'] . '-W';
            }
            $map['job_type'] = str_replace( [ "[", "]" ], [ "&#91;", "&#93;" ], $map['job_type'] );
        } else {
            $map['job_type'] = NULL;
        }
        if ( $map['job_type'] ) {
            $application_subject = $map['job_type'] . ' z.H. '
                                . ( isset( $map['contact_title'] ) ? $map['contact_title'] : '' ) . ' ' 
                                . ( isset( $map['contact_firstname'] ) ? $map['contact_firstname'] : '' ) . ' ' 
                                . ( isset( $map['contact_lastname'] ) ? $map['contact_lastname'] : '' ); 
        } else {
            $application_subject = '';
        }
        $application_mailto = 'mailto:' . $application_email . '?subject=' . $application_subject;
        if ( isset( $map['application_end'] ) ){
            $date = date_create( $map['application_end'] );
            $date_deadline = date_format($date, 'd.m.Y');
        }
        if ( isset( $kennung_old ) && isset( $map['job_type'] ) && isset( $map['job_description'] ) ) {
            $map['job_description'] = str_replace( $kennung_old, $map['job_type'], $map['job_description'] );
        }
        if ( isset( $map['job_description'] ) ) {
            $description = '<div itemprop="description" class="rrze-jobs-single-description">' . $map['job_description'] . '</div>'; 
        }
        $sidebar = '';
        if ($azubi) {
            $sidebar .= do_shortcode( '<div>[button link="https://azb.rrze.fau.de/" width="full"]Jetzt bewerben![/button]</div>' );
        } else {
            $sidebar .= do_shortcode( '<div>[button link="' . $application_mailto . '" width="full"]Jetzt bewerben![/button]</div>' );
        }
        $sidebar .= '<div class="rrze-jobs-single-application"><dl>';
        if ( isset( $map['application_end']) ) {
            $sidebar .= '<dt>' . __('Bewerbungsschluss', 'rrze-jobs') . '</dt>'
            . '<dd itemprop="validThrough" content="' . $map['application_end'] . '">' . $date_deadline . '</dd>';
        }
        if ( isset( $map['job_type'] ) ) { 
            $sidebar .= '<dt>' . __( 'Referenz', 'rrze-jobs' ) . '</dt>' . '<dd>' . $map['job_type'] . '</dd>';
        }
        $sidebar .= '<dt>' . __( 'Bewerbung', 'rrze-jobs' ) . '</dt>';
        if ($azubi) {
            $sidebar .= '<dd>Online über unser <a href="https://azb.rrze.fau.de/">Azubi-Bewerbungsportal</a>';
        } else {
            $sidebar .= '<dd>Bitte bewerben Sie sich ausschließlich per E-Mail an <a href="' . $application_mailto . '">' . $application_email . '.</a>';
        }
        if ( isset( $map['job_type'] ) ) {
            $sidebar .= ', <br/>Betreff: ' . $application_subject;
        }
        $sidebar .= '</dd>' . '</dl></div>';

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
        $sidebar .= '<hr />';

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
        $sidebar .= '<hr />';

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

        $output = '';
        $output .= '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
        $output .= do_shortcode('[three_columns_two]' . $description .'[/three_columns_two]' . '[three_columns_one_last]' . $sidebar . '[/three_columns_one_last][divider]');
        $output .= '</div>';

        return $output;
    }


    public function get_single_job_OLD( $provider, $jobid ) {
        $api_url = getURL($provider, 'urlsingle') . $jobid;
        if ($provider != 'univis') {
            $json_job = file_get_contents($api_url);
            $json_job = utf8_encode($json_job);
            $obj_job = json_decode($json_job);
        }

        switch ($provider) {
            case 'interamt': 
            // $this->store_job( $provider, $obj_job );
                $custom_logo_id = get_theme_mod('custom_logo');
                $logo_url = ( has_custom_logo() ? wp_get_attachment_url($custom_logo_id) : '' );
                $azubi = (strpos($obj_job->Stellenbezeichnung, 'Auszubildende') !== false) ? true : false;
                if ($obj_job->TarifEbeneVon == $obj_job->TarifEbeneBis) {
                    $salary = ($obj_job->TarifEbeneVon != '') ? $obj_job->TarifEbeneVon : $obj_job->TarifEbeneBis;
                } else {
                    $salary = $obj_job->TarifEbeneVon . ' – ' . $obj_job->TarifEbeneBis;
                }
                $application_email = 'RRZE-Bewerbungseingang@fau.de';
                if ($obj_job->Kennung != 'keine') {
                    $kennung_old = $obj_job->Kennung;
                    switch ( mb_substr( $obj_job->Kennung, - 2 ) ) {
                        case '-I':
                            $obj_job->Kennung = preg_replace( '/-I$/', '-W', $obj_job->Kennung );
                            break;
                        case '-U':
                            $obj_job->Kennung = preg_replace( '/-U$/', '-W', $obj_job->Kennung );
                            break;
                        default:
                            $obj_job->Kennung = $obj_job->Kennung . '-W';
                    }
                    $obj_job->Kennung = str_replace( [ "[", "]" ], [ "&#91;", "&#93;" ], $obj_job->Kennung );
                } else {
                    $obj_job->Kennung = NULL;
                }
                if ($obj_job->Kennung) {
                    $application_subject = $obj_job->Kennung . ' z.H. '
                                        . $obj_job->ExtAnsprechpartner->ExtAnsprechpartnerAnrede . ' '
                                        . $obj_job->ExtAnsprechpartner->ExtAnsprechpartnerVorname . ' '
                                        . $obj_job->ExtAnsprechpartner->ExtAnsprechpartnerNachname;
                } else {
                    $application_subject = '';
                }
                $application_mailto = 'mailto:' . $application_email . '?subject=' . $application_subject;
                $date = date_create($obj_job->DatumBewerbungsfrist);
                $date_deadline = date_format($date, 'd.m.Y');
                if (isset($kennung_old)) {
                    $obj_job->Beschreibung = str_replace( $kennung_old, $obj_job->Kennung, $obj_job->Beschreibung );
                }
                $description = '<div itemprop="description" class="rrze-jobs-single-description">' . $obj_job->Beschreibung . '</div>';
                $sidebar = '';
                if ($azubi) {
                    $sidebar .= do_shortcode( '<div>[button link="https://azb.rrze.fau.de/" width="full"]Jetzt bewerben![/button]</div>' );
                } else {
                    $sidebar .= do_shortcode( '<div>[button link="' . $application_mailto . '" width="full"]Jetzt bewerben![/button]</div>' );
                }

                $sidebar .= '<div class="rrze-jobs-single-application"><dl>'
                    . '<dt>' . __('Bewerbungsschluss', 'rrze-jobs') . '</dt>'
                    . '<dd itemprop="validThrough" content="' . $obj_job->DatumBewerbungsfrist . '">' . $date_deadline . '</dd>';
                if ($obj_job->Kennung) {
                    $sidebar .= '<dt>' . __( 'Referenz', 'rrze-jobs' ) . '</dt>'
                            . '<dd>' . $obj_job->Kennung . '</dd>';
                }
                $sidebar .= '<dt>' . __( 'Bewerbung', 'rrze-jobs' ) . '</dt>';
                if ($azubi) {
                    $sidebar .= '<dd>Online über unser <a href="https://azb.rrze.fau.de/">Azubi-Bewerbungsportal</a>';
                } else {
                    $sidebar .= '<dd>Bitte bewerben Sie sich ausschließlich per E-Mail an <a href="' . $application_mailto . '">' . $application_email . '.</a>';
                }
                if ($obj_job->Kennung) {
                    $sidebar .= ', <br/>Betreff: ' . $application_subject;
                }
                $sidebar .= '</dd>'
                            . '</dl></div>';

                $sidebar .= '<div class="rrze-jobs-single-keyfacts"><dl>';
                $sidebar .= '<h3>' . __('Details','rrze-jobs') . '</h3>'
                    . '<dt>'.__('Stellenbezeichnung','rrze-jobs') . '</dt><dd itemprop="title">' . $obj_job->Stellenbezeichnung . '</dd>';
                if ($obj_job->DatumBesetzungZum != '') {
                    $sidebar .= '<dt>'.__('Besetzung zum','rrze-jobs') . '</dt><dd>' . $obj_job->DatumBesetzungZum . '</dd>';
                }
                if (!empty($obj_job->Einsatzort)) {
                    $sidebar .= '<dt>'.__('Einsatzort','rrze-jobs'). '</dt>'
                        . '<dd itemprop="hiringOrganization">' . $obj_job->StellenangebotBehoerde . '<br />'
                                . $obj_job->Einsatzort->EinsatzortStrasse . '<br />'
                                . $obj_job->Einsatzort->EinsatzortPLZ . ' ' . $obj_job->Einsatzort->EinsatzortOrt
                        . '<span itemprop="jobLocation" itemscope itemtype="http://schema.org/Place" >'
                            . '<meta itemprop="logo" content="' . $logo_url . '" />'
                            . '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">'
                                . '<meta itemprop="name" content="' . $obj_job->StellenangebotBehoerde . '" />'
                                . '<meta itemprop="streetAddress" content="' . $obj_job->Einsatzort->EinsatzortStrasse . '" />'
                                . '<meta itemprop="postalCode" content="' . $obj_job->Einsatzort->EinsatzortPLZ . '" />'
                                . '<meta itemprop="addressLocality" content="' . $obj_job->Einsatzort->EinsatzortOrt . '" />'
                                . '<meta itemprop="addressRegion" content="' . $obj_job->BeschaeftigungBereichBundesland . '" />'
                                . '<meta itemprop="url" content="' . $obj_job->HomepageBehoerde . '" />'
                            .'</span></span>'
                        . '</dd>';
                }
                $sidebar .= '<hr />';

                if ($salary != '') {
                    $sidebar .= '<dt>'.__('Entgelt','rrze-jobs') . '</dt><dd>' . $salary . '</dd>';
                }
                if ($obj_job->Teilzeit != '') {
                    $sidebar .= '<dt>'.__('Teilzeit / Vollzeit','rrze-jobs') . '</dt><dd itemprop="employmentType">' . $obj_job->Teilzeit . '</dd>';
                }
                if ($obj_job->WochenarbeitszeitArbeitnehmer != '') {
                    $sidebar .= '<dt>'.__('Wochenarbeitszeit','rrze-jobs') . '</dt><dd itemprop="workHours">' . number_format($obj_job->WochenarbeitszeitArbeitnehmer, 1, ',', '.') . ' h</dd>';
                }
                if ($obj_job->BeschaeftigungDauer == 'befristet') {
                    $sidebar .= '<dt>'.__('Befristung (Monate)','rrze-jobs') . '</dt><dd>' . $obj_job->BefristetFuer . '</dd>';
                }
                $sidebar .= '<hr />';

                if (!empty($obj_job->ExtAnsprechpartner)) {
                    $sidebar .= '<dt>'.__('Ansprechpartner für weitere Informationen','rrze-jobs') . '</dt>'
                        . '<dd>' . $obj_job->ExtAnsprechpartner->ExtAnsprechpartnerAnrede . ' ' . $obj_job->ExtAnsprechpartner->ExtAnsprechpartnerVorname . ' ' . $obj_job->ExtAnsprechpartner->ExtAnsprechpartnerNachname;
                    if ($obj_job->ExtAnsprechpartner->ExtAnsprechpartnerTelefon != '') {
                        $sidebar.= '<br />' . __('Telefon', 'rrze-jobs') . ': ' . $obj_job->ExtAnsprechpartner->ExtAnsprechpartnerTelefon;
                    }
                    if ($obj_job->ExtAnsprechpartner->ExtAnsprechpartnerMobil != '') {
                        $sidebar.= '<br />' . __('Mobil', 'rrze-jobs') . ': ' . $obj_job->ExtAnsprechpartner->ExtAnsprechpartnerMobil;
                    }
                    if ($obj_job->ExtAnsprechpartner->ExtAnsprechpartnerEMail != '') {
                        $sidebar.= '<br />' . __('E-Mail', 'rrze-jobs') . ': <a href="mailto:' . $obj_job->ExtAnsprechpartner->ExtAnsprechpartnerEMail . '">' . $obj_job->ExtAnsprechpartner->ExtAnsprechpartnerEMail . '</a>';
                    }
                    $sidebar .= '</dd>';
                }
                $sidebar .= '</dl>';

                $sidebar .= '<div><meta itemprop="datePosted" content="' . $obj_job->DatumOeffentlichAusschreiben . '" />'
                    . '<meta itemprop="qualifications" content="' . $obj_job->Qualifikation . '" />'
                    . '<meta itemprop="url" content="' . get_permalink() . '?jobid=' . $obj_job->Id . '" />'
                    . '</div>';
                $sidebar .= '</div>';

                $output = '';
                $output .= '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
                $output .= do_shortcode('[three_columns_two]' . $description .'[/three_columns_two]' . '[three_columns_one_last]' . $sidebar . '[/three_columns_one_last][divider]');
                $output .= '</div>';
                break;
            case 'univis':
                $description = 'Die UnivIS liefert noch keine ID';
                $sidebar = '';
                $output = '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
                $output .= $description;
                $output .= '</div>';
            break;
        }

        return $output;
    }

    private function transform_date($date) {
        $date_parts = explode('.', $date);
        if ( empty($date_parts) || count( $date_parts ) != 3 ) {
            return '';
        } else {
            return $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
        }
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
            'render_callback'  => array($this, 'jobsHandler'),
            'attributes'         =>   [
                "provider" => [
                    'default' => ''
                ],
                "department" => [
                    'default' => ''
                ],
                "jobtype" => [
                    'default' => ''
                ],
                "jobid" => [
                    'default' => ''
                ],
                "orderby" => [
                    'default' => ''
                ],
                "sort" => [
                    'default' => ''
                ]
            ]
        ) );
    }
}