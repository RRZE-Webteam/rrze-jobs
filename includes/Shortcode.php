<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;
use function RRZE\Jobs\Config\getMap;
use function RRZE\Jobs\Config\getURL;
use function RRZE\Jobs\Config\getFields;
use function RRZE\Jobs\Config\fillMap;
use function RRZE\Jobs\Config\getOptionName;
use function RRZE\Jobs\Config\isInternAllowed;

class Shortcode {
    private $provider = '';

    private $count = 0;


    /**
     * Shortcode-Klasse wird instanziiert.
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_nopriv_rrze_jobs_ajax_function', [$this, 'rrze_jobs_ajax_function']);
        add_action('wp_ajax_rrze_jobs_ajax_function', [$this, 'rrze_jobs_ajax_function']);
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
        wp_register_script('jobs-shortcode', plugins_url('assets/js/jobs-shortcode.js', plugin_basename(RRZE_PLUGIN_FILE)));
        wp_localize_script( 'jobs-shortcode', 'jobs_sc', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' )
            )
        );
        if (file_exists(WP_PLUGIN_DIR.'/rrze-elements/assets/css/rrze-elements.min.css')) {
	        wp_register_style( 'rrze-elements', plugins_url() . '/rrze-elements/assets/css/rrze-elements.min.css' );
        }
    }

    private function getProviders() {
        $providers = array();
        $options = get_option( getOptionName() );

        if (!empty( $options )) {
            foreach ( $options as $key => $value ) {
                $parts = explode('_', $key);
                if ( count( $parts ) == 3 ) {
                    $providers[$parts[1]][$parts[2]] = $value;
                }
            }
          }
        return $providers;
    }

    private function formatUnivIS( $txt ){
        $search = array(
            // '/\-+\s+(.*)?/mi', 
            '/\-+\s+(?!und)(.*)/mi', // exclude "- und"
            '/(\<\/ul\>\n(.*)\<ul\>*)+/',
            '/(\<br \/\>*)/mi',
            '/\*(.*)\*/',
            '/\|(.*)\|/',
        );
        $replace = array(
            '<ul><li>$1</li></ul>',
            '',
            '',
            '<strong>$1</strong>',
            '<em>$1</em>',
        );
        
        return preg_replace($search, $replace, $txt);
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

        $provider = strtolower( sanitize_text_field( $atts['provider'] ) );
        $output = '';

        if ( isset( $provider ) && ( $provider != '' ) ){
            $this->provider = $atts['provider']; 
            return $this->jobs_shortcode( $atts );
        }else{
            return '<p>' . __('Please specify the correct job portal in the shortcode attribute <code>provider=""</code>.', 'rrze-jobs') . '</p>';
        }
    }
    

    public function jobs_shortcode( $atts ) {
        $this->count = 0;
        $providers = $this->getProviders();

        if ( isset( $atts['orgid'] ) && $atts['orgid'] != '' ) {
            $atts['orgids'] = $atts['orgid'];
        }

        if ( isset($atts['orgids']) && $atts['orgids'] != '' ) {
            $orgids = sanitize_text_field( $atts['orgids'] );
        } else {
            $orgids = $providers[$this->provider]['orgid'];
        }
        $jobid = sanitize_text_field( $atts['jobid'] );

        if ( $orgids == '' && $jobid == '' ) {
            return '<p>' . __('Please provide an organisation or job ID!', 'rrze-jobs') . '</p>';
        }
        $output = '';

        if ( isset( $_GET['jobid']) && $_GET['provider'] == $atts['provider'] ) {
		    $jobid = $_GET['jobid'];
		    $output .= '<p class="rrze-jobs-closelink-container"><a href="' . get_permalink() . '" class="view-all"><i class="fa fa-close" aria-hidden="true"></i> schließen</a></p>';
        }
        if ( $jobid != '' ) {
            $output .= $this->get_single_job( $this->provider, $jobid );
        } else {
            // $output = 'here we are';
            // var_dump($atts);
            $output = $this->get_job_list( getURL($this->provider, 'urllist'), $orgids, $atts['limit'], $atts['orderby'], $atts['order'], $atts['internal'] , $atts['fallback_apply'] );
        }

        wp_enqueue_style('rrze-elements');
        wp_enqueue_style('jobs-shortcode');
        wp_enqueue_script('jobs-shortcode');

        return $output;
    }

    public function rrze_jobs_ajax_function() {
        $provider = sanitize_text_field( $_POST['provider'] );
        $jobid = sanitize_text_field( $_POST['jobid'] );
        $fallback_apply = sanitize_text_field( $_POST['fallback_apply'] );
        $responseData = $this->get_single_job( $provider, $jobid, $fallback_apply );
        //error_log( 'in rrze_jobs_ajax_function ' . $responseData);
        echo json_encode( $responseData );
        wp_die();
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

	private function getDescription( &$map, $provider = ''){
    	$description = '';
    	switch ($provider) {
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
    	if (\DateTime::createFromFormat( 'Y-m-d H:i:s', $mydate ) !== FALSE) {
            return (new \DateTime( $mydate ))->format('Y-m-d');
        } elseif (\DateTime::createFromFormat( 'd.m.Y', $mydate ) !== FALSE) {
	        return (new \DateTime( $mydate ))->format('Y-m-d');
        } elseif ($mydate == 'nächstmöglicher Zeitpunkt') {
            return date('Y-m-d');
        } else {
    		return '';
	    }
    }

    private function sortArrayByField( $myArray, $fieldname, $order ){
        usort( $myArray, function ( $a, $b ) use ($fieldname, $order) {
            return ( $order == 'ASC' ? strtolower( $a[$fieldname] ) <=> strtolower( $b[$fieldname] ) : strtolower( $b[$fieldname] ) <=> strtolower( $a[$fieldname] ) );
        });
        return $myArray;
    }

    private function get_job_list( $api_url, $orgids, $limit, $orderby, $order, $internal, $fallback_apply ) {
        $output = '';
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_meta = has_custom_logo() ? '<meta itemprop="image" content="' . wp_get_attachment_url($custom_logo_id) . '" />' : '';

        $maps = array();

        $orgids = explode( ',', $orgids );

        foreach ( $orgids as $orgid ){
            $orgid = trim( $orgid );

            // Check if orgid is an integer and ignore if not (we don't output a message because there might be more than one orgid) - fun-fact: UnivIS delivers their complete database entries if orgid contains characters
            if ( strval($orgid) !== strval(intval($orgid)) ) {
                continue;
            }

            $myurl = $api_url . trim( $orgid );
            $json = file_get_contents( $myurl );

            if ( !$json ) {
                return '<p>' . __('Cannot connect to API at the moment. Link is ', 'rrze-jobs') . '<a href="' . $myurl . '" target="_blank">' . $myurl . '</a></p>';
            }
            $json = utf8_encode($json);
            $obj = json_decode($json);

            $map_template = getMap( $this->provider, 'list' );
	        $node = $map_template['node'];
	        unset( $map_template['node'] );
        
            if ( !is_null( $obj ) && isset( $obj->$node )){
                foreach ($obj->$node as $job) {
                    $maps[] = fillMap( $map_template, $job );
                }
            }
        }

        if ( count( $maps ) > 0 ){
            // check if $orderby is a field we know
            if ( !array_key_exists( $orderby, $map_template) ){
                $correct_vals = implode(', ', array_keys( $map_template ) );
                return '<p>' . __( 'Parameter "orderby" is not correct. Please use one of the following values: ', 'rrze-jobs') . $correct_vals;
            }

            $maps = $this->sortArrayByField( $maps, $orderby, $order );
            $intern_allowed = isInternAllowed();

	        $output .= '<ul class=\'rrze-jobs-list\'>';
            foreach ($maps as $map) {

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

	            $output .= '<li itemscope itemtype="https://schema.org/JobPosting"><a href="?provider=' . $this->provider . '&jobid=' . $map['job_id'] . '" data-provider="' . $this->provider . '" data-jobid= "' . $map['job_id'] . '" data-fallback_apply="' . $fallback_apply . '" class="joblink">'
                    .'<span itemprop="title">' . $map['job_title'] . ( $salary != '' ? ' (' . $salary . ')' : '' ) . '</span></a>';
                    $output .= $logo_meta 
                    .(isset($map['application_start']) ? '<meta itemprop="datePosted" content="' . $this->transform_date( $map['application_start'] ) . '" />': '')
                    .(isset($map['job_education']) ? '<meta itemprop="educationRequirements" content="' . strip_tags(htmlentities( $map['job_education'])) . '" />': '')
                    .(isset($map['job_type']) ? '<meta itemprop="employmentType" content="' . ( $map['job_type'] == 'teil' ? 'Teilzeit' : 'Vollzeit' ) . '" />': '') 
                    .(isset($map['job_unit']) ? '<meta itemprop="employmentUnit" content="' .$map['job_unit'] . '" />':'')
                    .'<meta itemprop="estimatedSalary" content="' . $salary . '" />'
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
                    . '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">'
                    .(isset($map['employer_postalcode']) ? '<meta itemprop="postalCode" content="' . $map['employer_postalcode'] . '" />': '')
                    .(isset($map['employer_city']) ? '<meta itemprop="addressLocality" content="' . $map['employer_city'] . '" />': '')
                    . '</span></span></li>';
                $this->count++;
            }
	        $output .= '</ul>';
        } 

        if ( $this->count == 0 ) {
            return '<p>' . __('API does not return any data.', 'rrze-jobs') . '</a></p>';
        }
    
        return $output;
    }
    

    public function get_single_job( $provider, $jobid, $fallback_apply = '' ) {
	    $output = '';
    	$api_url = getURL($provider, 'urlsingle') . $jobid;
        $json_job = file_get_contents($api_url);
        $json_job = utf8_encode($json_job);
        $job = json_decode($json_job);

        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = ( has_custom_logo() ? wp_get_attachment_url($custom_logo_id) : '' );

        $map_template = getMap( $provider, 'single' );
        $map = fillMap( $map_template, $job );

        $intern_allowed = isInternAllowed();

        // Skip internal job offers if necessary
        if ( !$intern_allowed && isset( $map['job_intern'] ) ) {
            return '<p>' . __('This job offer is not available', 'rrze-jobs') . '</p>';
        }

        if ( ( isset( $map['application_end'] ) )  && ( $map['application_end'] >= date('Y-m-d') ) ) {
            $azubi = false;
            if ( ( isset( $map['job_title'] ) ) && ( strpos( $map['job_title'], 'Auszubildende' ) ) ) {
                $azubi = true;
            }
            $salary = $this->getSalary( $map );
            $description = $this->getDescription($map, $provider);

            if (isset($map['job_employmenttype'])) {
		        if ($map['job_employmenttype'] == 'voll') {
			        $map['job_employmenttype'] = 'Vollzeit';
		        } elseif ($map['job_employmenttype'] == 'teil') {
			        $map['job_employmenttype'] = 'Teilzeit';
		        }
	        }
	        if ($provider == 'interamt') {
                $start_application_string = (strpos($map['job_description'], 'Bitte bewerben Sie sich') || strpos($map['job_description'], 'Senden Sie Ihre Bewerbung'));
                if ($start_application_string !== false) {
			        $application_string = substr($map['job_description'], $start_application_string);
			        $map['application_link'] = strip_tags(html_entity_decode($application_string), '<a><br><br /><b><strong><i><em>');
		        }
	        }
	        $application_email = '';
	        if (isset($map['application_link'])) {
		        preg_match_all( "/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+[a-zA-Z]+/i", $map['application_link'], $matches );
		        if (!empty( $matches[0] )) {
			        $application_email = $matches[0][0];
			        $map['application_email'] = $application_email;
		        }
	        }

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

            if ( isset( $map['application_end'] ) ){
                $date = date_create( $map['application_end'] );
                $date_deadline = date_format($date, 'd.m.Y');
            }
            if ( isset( $kennung_old ) && isset( $map['job_type'] ) && isset( $map['job_description'] ) ) {
                $map['job_description'] = str_replace( $kennung_old, $map['job_type'], $map['job_description'] );
            }
            $description = '<div itemprop="description" class="rrze-jobs-single-description">' . $description . '</div>';

            // set application_email or application_link or fallback_link
            $sidebar = '';
            if ( $application_email != '' ) {
                $application_link = $application_email;
            } elseif ( isset( $map['application_link'] ) && $map['application_link'] != '' ) {
                $application_link = $map['application_link'];
            } elseif ( $fallback_apply != '' ) {
                $application_link = $fallback_apply;
                if ( strpos( $fallback_apply, '@' ) > 0 ){
                    $application_link = $fallback_apply;
                }
            }
            $mailto = strpos($application_link, '@') !== false ? 'mailto:' : '';
            $sidebar .= do_shortcode( '<div>[button link="' . $mailto . $application_link . '" width="full"]Jetzt bewerben![/button]</div>' );

            $sidebar .= '<div class="rrze-jobs-single-application"><dl>';
            if ( isset( $map['application_end']) ) {
                $sidebar .= '<dt>' . __('Bewerbungsschluss', 'rrze-jobs') . '</dt>'
                . '<dd itemprop="validThrough" content="' . $map['application_end'] . '">' . $date_deadline . '</dd>';
            }
            if ( isset( $map['job_type'] ) ) { 
                $sidebar .= '<dt>' . __( 'Referenz', 'rrze-jobs' ) . '</dt>' . '<dd>' . $map['job_type'] . '</dd>';
            }

            $sidebar .= '<dt>' . __( 'Bewerbung', 'rrze-jobs' ) . '</dt>';
            $sidebar .= '<dd>' . make_clickable($application_link) . '</dd></div>';
            $sidebar .= '<div class="rrze-jobs-single-keyfacts"><dl>';
            $sidebar .= '<h3>' . __('Details','rrze-jobs') . '</h3>'
                . '<dt>'.__('Stellenbezeichnung','rrze-jobs') . '</dt><dd itemprop="title">' . $map['job_title'] . '</dd>';
            if ( ( isset( $map['job_start']) ) && ( $map['job_start'] != '' ) ) {
                $sidebar .= '<dt>'. __('Besetzung zum','rrze-jobs') . '</dt><dd>' . $map['job_start'] . '</dd>'; 
            }
            if ( ( isset( $map['employer_city'] ) ) && ( !empty( $map['employer_city'] ) ) ) {
                $sidebar .= '<dt>'.__('Einsatzort','rrze-jobs'). '</dt>';
                if ( isset( $map['employer_organization']) ) {
                    $sidebar .= '<dd itemprop="hiringOrganization" itemscope itemtype="http://schema.org/Organization"><span itemprop="name">' . $map['employer_organization'] . '</span><meta itemprop="logo" content="' . RRZE_JOBS_LOGO . '" /><br />';
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
                $sidebar .= ( isset( $map['employer_city'] ) ? '<meta itemprop="addressRegion" content="' . RRZE_JOBS_ADDRESS_REGION . '" />' : '' );
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

            $output = '';
            $output .= '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
	        $output .= do_shortcode('[three_columns_two]' . ($provider == 'univis' ? $this->formatUnivIS( $description ) : $description ) .'[/three_columns_two]' . '[three_columns_one_last]' . $sidebar . '[/three_columns_one_last][divider]');
	        $options = get_option(getOptionName());
	        if (isset($options['rrze-jobs_job_notice']) && $options['rrze-jobs_job_notice'] != '') {
		        $output .= '<hr /><div>' . strip_tags( $options['rrze-jobs_job_notice'], '<p><a><br><br /><b><strong><i><em>' ) . '</div>';
	        }
	        $output .= '</div>';
        }
        return $output;
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