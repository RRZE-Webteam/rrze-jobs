<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;
use function RRZE\Jobs\Config\getShortcodeSettings;
use function RRZE\Jobs\Config\getMap;
use function RRZE\Jobs\Config\getURL;
use function RRZE\Jobs\Config\fillMap;
use function RRZE\Jobs\Config\getPersons;
use function RRZE\Jobs\Config\formatUnivIS;
use function RRZE\Jobs\Config\isInternAllowed;


class Shortcode {
    private $provider = '';
    private $count = 0;
    private $settings = '';


    /**
     * Shortcode-Klasse wird instanziiert.
     */
    public function __construct() {
        $this->settings = getShortcodeSettings();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action( 'init',  [$this, 'gutenberg_init'] );
        if ( !is_plugin_active('fau-jobportal/fau-jobportal.php') ) {
            add_shortcode( 'jobs', [ $this, 'shortcodeOutput' ], 10, 2 );
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
            } else {
                $salary = $map['job_salary_to'];
            }
        } elseif ( isset( $map['job_salary_from'] ) ) {
            $salary = $map['job_salary_from'];
        }
        return $salary;
    }

    private function getDescription( &$map ){
        $description = '';
        switch ($this->provider) {
            case 'univis';
                $description =
                    (isset($map['job_description_introduction']) ? '<p>'.formatUnivIS($map['job_description_introduction']).'</p>' : '')
                    . (isset($map['job_title']) ? '<h3>'.$map['job_title'].'</h3>' : '')
                    . (isset($map['job_description']) ? '<h4>Das Aufgabengebiet umfasst u.a.:</h4><p>'.formatUnivIS($map['job_description']).'</p>' : '')
                    . (isset($map['job_qualifications']) ? '<h4>Notwendige Qualifikation</h4><p>'.formatUnivIS($map['job_qualifications']).'</p>' : '')
                    . (isset($map['job_qualifications_nth']) ? '<h4>Wünschenswerte Qualifikation</h4><p>'.formatUnivIS($map['job_qualifications_nth']).'</p>' : '')
                    . (isset($map['job_benefits']) ? '<h4>Bemerkungen</h4><p>'.formatUnivIS($map['job_benefits']).'</p>' : '')
                    . (isset($map['application_link']) ? '<h4>Bewerbung</h4><p>'.formatUnivIS($map['application_link']).'</p>' : '');
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

    public function shortcodeOutput( $atts ) {
        $this->count = 0;
        $this->providers = $this->getProviders();
        $this->provider = ( isset( $atts['provider'] ) ? $atts['provider'] : $this->settings['provider']['default'] );
        $orgids = 0;
        $jobid = 0;

        if ( isset( $_GET['provider']) && isset($_GET['jobid'])) {
            $jobid = sanitize_text_field( $_GET[ 'jobid' ] );
            $this->provider = ( isset( $_GET['provider'] ) ? sanitize_text_field($_GET['provider']) : $this->settings['provider']['default'] );
        } else {
            if ( isset( $atts[ 'jobid' ] ) ) {
                $jobid = sanitize_text_field( $atts[ 'jobid' ] );
            } else {
                // 1. shortcode param orgids
                if ( isset( $atts[ 'orgids' ] ) && $atts[ 'orgids' ] != '' ) {
                    $orgids = sanitize_text_field( $atts[ 'orgids' ] );
                } elseif ( isset( $atts[ 'orgid' ] ) && $atts[ 'orgid' ] != '' ) {
                    // 2. shortcode param orgid
                    $orgids = sanitize_text_field( $atts[ 'orgid' ] );
                } else {
                    // 3. plugin settings page orgids_<provider>
                    $options = get_option( RRZE_JOBS_TEXTDOMAIN );
                    if ( isset( $options[ RRZE_JOBS_TEXTDOMAIN . '_orgids_' . $this->provider ] ) ) {
                        $orgids = $options[ RRZE_JOBS_TEXTDOMAIN . '_orgids_' . $this->provider ];
                    }
                }
            }
        }

        if ( !$orgids && !$jobid) {
            return '<p>' . __('Please provide an organisation or job ID!', 'rrze-jobs') . '</p>';
        }

        $limit = ( isset( $atts['limit'] ) ? $atts['limit'] : $this->settings['limit']['default']);
        $internal = ( isset( $atts['internal'] ) ? $atts['internal'] : $this->settings['internal']['default']);
        $orderby = ( isset( $atts['orderby'] ) ? $atts['orderby'] : $this->settings['orderby']['default']);
        $order = ( isset( $atts['order'] ) ? $atts['order'] : $this->settings['order']['default']);
        $fallback_apply = ( isset( $atts['fallback_apply'] ) ? $atts['fallback_apply'] : $this->settings['fallback_apply']['default']);

        if ( $orgids ){
            $output = $this->get_jobs( $jobid, $orgids, $limit, $orderby, $order, $internal, $fallback_apply );
        } else {
            $output = $this->get_single_job( $jobid, $internal, $fallback_apply );
        }

        wp_enqueue_style('rrze-elements');
        wp_enqueue_style('jobs-shortcode');
        wp_enqueue_script('jobs-shortcode');

        return $output;
    }


    private function get_sidebar( &$map, &$logo_url ){
        $sidebar = '';
        $application_link = '';

        if ( isset($map['application_email']) ) {
            $application_link = $map['application_email'];
            $mailto = 'mailto:';
        } elseif (isset($map['application_link']) && strpos($map['application_link'], 'http') !== false) {
            $application_link = $map['application_link'];
            $mailto = '';
        }

        if (!isset( $map['employer_district'] ) || $map['employer_district'] == '') {
            $map['employer_district'] = RRZE_JOBS_ADDRESS_REGION;
        }

        if ($application_link != '') {
            $sidebar .= do_shortcode( '<div>[button link="' . $mailto . $application_link . '" width="full"]Jetzt bewerben![/button]</div>' );
        }

        $sidebar .= '<div class="rrze-jobs-single-application"><dl>';
        if ( isset( $map['application_end']) ) {
            $sidebar .= '<dt>' . __('Application deadline', 'rrze-jobs') . '</dt>'
                . '<dd itemprop="validThrough" content="' . $map['application_end'] . '">' . date('d.m.Y', strtotime($map['application_end'])) . '</dd>';
        }
        if ( isset( $map['job_type'] ) ) {
            $sidebar .= '<dt>' . __( 'Reference', 'rrze-jobs' ) . '</dt>' . '<dd>' . $map['job_type'] . '</dd>';
        }
        if (isset($map['application_link'])) {
            $sidebar .= '<dt>' . __( 'Application', 'rrze-jobs' ) . '</dt>';
            $sidebar .= '<dd>' . make_clickable($map['application_link']) . '</dd>';
        }
        $sidebar .= '</dl></div>';
        $sidebar .= '<div class="rrze-jobs-single-keyfacts"><dl>';
        $sidebar .= '<h3>' . __('Details', 'rrze-jobs') . '</h3>'
            . '<dt>'.__('Job title', 'rrze-jobs') . '</dt><dd itemprop="title">' . $map['job_title'] . '</dd>';
        if ( ( isset( $map['job_start']) ) && ( $map['job_start'] != '' ) ) {
            $sidebar .= '<dt>'. __('Job start', 'rrze-jobs') . '</dt><dd itemprop="jobStartDate">' . date('d.m.Y', strtotime($map['job_start'])) . '</dd>';
        }
        $sidebar .= '<dt>'.__('Deployment location', 'rrze-jobs'). '</dt>';
        if ( isset( $map['employer_organization']) ) {
            $sidebar .= '<dd itemprop="hiringOrganization" itemscope itemtype="http://schema.org/Organization">' . $map['employer_organization'] . '<br />';
        }
        if ( isset( $map['employer_street']) ) {
            $sidebar .= $map['employer_street'] . '<br />';
        }
        if ( isset( $map['employer_postalcode']) ) {
            $sidebar .=  $map['employer_postalcode'] . ' ';
        }
        $sidebar .= '<meta itemprop="name" content="' . $map['employer_organization'] . '" />
            <meta itemprop="logo" content="' . $logo_url . '" />';
        $sidebar .= '</dd>';
        $sidebar .=  '<span itemprop="jobLocation" itemscope itemtype="http://schema.org/Place" >'
            . '<meta itemprop="logo" content="' . $logo_url . '" />'
            . '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">'
            . '<meta itemprop="name" content="' . $map['employer_organization'] . '" />';
        $sidebar .= ( isset( $map['contact_street'] ) ? '<meta itemprop="streetAddress" content="' .  $map['contact_street'] . '" />' : '' );
        $sidebar .= ( isset( $map['contact_postalcode'] ) ? '<meta itemprop="postalCode" content="' . $map['contact_postalcode'] . '" />' : '' );
        $sidebar .= ( isset( $map['contact_city'] ) ? '<meta itemprop="addressLocality" content="' . $map['contact_city'] . '" />' : '' );
        $sidebar .= ( isset( $map['employer_district'] ) ? '<meta itemprop="addressRegion" content="' . $map['employer_district'] . '" />' : '' );
        $sidebar .= ( isset( $map['contact_link'] ) ? '<meta itemprop="url" content="' . $map['contact_link'] . '" />' : '' );
        $sidebar .= '</span></span>';

        $salary = $this->getSalary( $map );

        if ( $salary != '' ) {
            $sidebar .= '<dt>'.__('Payment','rrze-jobs') . '</dt><dd itemprop="estimatedSalary">' . $salary . '</dd>';
        }
        if ( isset( $map['job_employmenttype'] ) ) {
            $sidebar .= '<dt>'.__('Part-time / full-time','rrze-jobs') . '</dt><dd itemprop="employmentType">' . $map['job_employmenttype'] . '</dd>';
        }
        if ( isset( $map['job_workhours'] ) ) {
            $map['job_workhours'] = floatval( str_replace( ',', '.', $map['job_workhours'] ) );
            if (substr(get_locale(), 0, 2) == 'de') {
                $map['job_workhours'] = number_format($map['job_workhours'], 1,',', '.');
            }
            $sidebar .= '<dt>'.__('Weekly working hours','rrze-jobs') . '</dt><dd itemprop="workHours">' . $map['job_workhours'] . ' h</dd>';
        }

        if ( ( isset( $map['job_limitation'] ) && $map['job_limitation'] == 'befristet' )  || ( isset( $map['job_limitation_duration'] ) ) ) {
            $map['job_limitation_duration'] .= ( is_numeric( $map['job_limitation_duration'] ) ? ' ' .  __('Months', 'rrze-jobs' ) : '' );
            $sidebar .= '<dt>' . __( 'Limitation', 'rrze-jobs' ) . '</dt><dd>' . $map['job_limitation_duration'] . '</dd>';
            if ( isset( $map['job_limitation_reason'] ) ) {
                switch ( $map['job_limitation_reason'] ){
                    case 'vertr':
                        $map['job_limitation_reason'] = 'Vertretung';
                        break;
                    case 'schwanger':
                        $map['job_limitation_reason'] = 'Mutterschutzvertretung';
                        break;
                    case 'eltern':
                        $map['job_limitation_reason'] = 'Mutterschutz- / Elternzeitvertretung';
                        break;
                    case 'krankh':
                        $map['job_limitation_reason'] = 'Krankheitsvertretung';
                        break;
                    case 'forsch':
                        $map['job_limitation_reason'] = 'befristetes Forschungsvorhaben';
                        break;
                    case 'zeitb':
                        $map['job_limitation_reason'] = 'Beamtenschaft auf Zeit';
                        break;
                }
                $sidebar .= '<dt>' . __( 'Reason for the limitation', 'rrze-jobs' ) . '</dt><dd>' . $map['job_limitation_reason'] . '</dd>';
            }
        }

        if ( ( isset( $map['contact_lastname'] ) ) && ( $map['contact_lastname'] != '' ) ) {
            $sidebar .= '<dt>'.__('Contact for further information','rrze-jobs') . '</dt>'
                . '<dd>' . ( isset( $map['contact_title'] ) ? $map['contact_title'] . ' ' : '' ) . ( isset( $map['contact_firstname'] ) ? $map['contact_firstname'] . ' ' : '' ) . ( isset( $map['contact_lastname'] ) ? $map['contact_lastname'] : '' );
            if ( ( isset( $map['contact_tel'] ) ) && ( $map['contact_tel'] != '' ) ) {
                $sidebar.= '<br />' . __('Phone', 'rrze-jobs') . ': ' . $map['contact_tel'];
            }
            if ( ( isset( $map['contact_mobile'] ) ) && ( $map['contact_mobile'] != '' ) ) {
                $sidebar.= '<br />' . __('Mobile', 'rrze-jobs') . ': ' . $map['contact_mobile'];
            }
            if ( ( isset( $map['contact_email'] ) ) && ( $map['contact_email'] != '' ) ) {
                $sidebar.= '<br />' . __('E-Mail', 'rrze-jobs') . ': <a href="mailto:' . $map['contact_email'] . '">' . $map['contact_email'] . '</a>';
            }
            $sidebar .= '</dd>';
        }
        $sidebar .= '</dl>';

        $datePosted = ( isset($map['application_start']) ? $this->transform_date( $map['application_start'] ) : date('Y-m-d') );

        $sidebar .= '<div><meta itemprop="datePosted" content="' . $datePosted . '" />'
            . (isset($map['job_education']) ? '<meta itemprop="educationRequirements" content="' . strip_tags(htmlentities( $map['job_education'])) . '" />': '')
            . (isset($map['job_unit']) ? '<meta itemprop="employmentUnit" content="' . $map['job_unit'] . '" />':'')
            . (isset($map['job_experience']) ? '<meta itemprop="experienceRequirements" content="' . strip_tags(htmlentities( $map['job_experience'] )) . '" />': '')
            . (isset($map['job_benefits']) ? '<meta itemprop="jobBenefits" content="' . htmlentities( $map['job_benefits']) . '" />': '')
            . (isset($map['job_category']) ? '<meta itemprop="occupationalCategory" content="' . $map['job_category'] . '" />': '')
            . '<meta itemprop="qualifications" content="' . ( isset ($map['job_qualifications'] ) ? $map['job_qualifications'] : '' ) . '" />'
            . '<meta itemprop="url" content="' . get_permalink() . '?jobid=' . $map['job_id']. '" />'
            . '</div>';
        $sidebar .= '</div>';

        return $sidebar;
    }

    private function get_jobs( $jobid = '', $orgids = '', $limit, $orderby, $order, $internal, $fallback_apply ) {
        $options = get_option( RRZE_JOBS_TEXTDOMAIN );
        $output = '';
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = ( has_custom_logo() ? wp_get_attachment_url($custom_logo_id) : RRZE_JOBS_LOGO );

        $orgids = explode( ',', $orgids );
        $maps = [];

        foreach ( $orgids as $orgid ){
            $orgid = trim( $orgid );

            // Check if orgid is an integer and ignore if not (we don't output a message because there might be more than one orgid) - fun-fact: Interamt delivers their complete database entries if orgid contains characters
            if ( strval($orgid) !== strval(intval($orgid)) ) {
                continue;
            }
            $api_url = getURL( $this->provider, 'list') . trim( $orgid );
            $data = file_get_contents( $api_url );

            if ( !$data ) {
                return '<p>' . __('Cannot connect to API at the moment.', 'rrze-jobs') . '</p>';
            }
            $data = json_decode( utf8_encode( $data ), true);

            if ( $this->provider == 'univis' ){
                $persons = $data['Person'];
                $persons = getPersons( $persons );
            }

            $node = ( $this->provider == 'interamt' ? 'Stellenangebote' : 'Position' );
            if ( !isset( $data[$node] ) ){
                continue;
            }

            $jobs = $data[$node];

            // Loop through jobs
            if ( isset( $jobs ) ) {
                if ( $this->provider == 'univis' && isset( $data[$node]['id'] ) ) {
                    $jobs = array( $data[$node] );
                }

                foreach ( $jobs as $jobData ) {
                    $map = getMap( $this->provider );
                    if ( $this->provider == 'interamt' ){
                        $date = date_create_from_format('d.m.Y', $jobData['Daten']['Bewerbungsfrist']);
                        if ( date_format($date, 'Y-m-d') < date('Y-m-d') ){
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
                        if ( isset( $job['job_employmenttype'] ) ) {
                            $job['job_employmenttype'] = ucfirst( $job['job_employmenttype'] ) . 'zeit';
                        }

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

        /*
         * Ausgabe für Public Displays
         */
        if (isset($_GET['format']) && $_GET['format'] == 'embedded' && isset($_GET['job'])) {
            $jobnr = (int)$_GET[ 'job' ] - 1;
            usort( $maps, function ( $a, $b ) {
                return strcmp( $a[ 'job_id' ], $b[ 'job_id' ] );
            } );
            if ( ( count( $maps ) > 0 ) && ( isset( $maps[ $jobnr ][ 'job_id' ] ) ) ) {
                return $this->get_single_job( $maps[ $jobnr ][ 'job_id' ] );
            } else {
                return "nix!";
            }
            return;
        }

        /*
         * Normale Ausgabe
         */
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

                $description = $this->getDescription($map);
                $description = str_replace('"', '', $description);
                $datePosted = ( isset($map['application_start']) ? $this->transform_date( $map['application_start'] ) : date('Y-m-d') );
                $shortcode_item_inner .= '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
                $shortcode_item_inner .= do_shortcode('[three_columns_two]<div itemprop="description">' . $description  .'</div>[/three_columns_two]' . '[three_columns_one_last]' . $this->get_sidebar( $map, $logo_url ) . '[/three_columns_one_last][divider]');

                if (isset($options['rrze-jobs_job_notice']) && $options['rrze-jobs_job_notice'] != '') {
                    $shortcode_item_inner .= '<hr /><div>' . strip_tags( $options['rrze-jobs_job_notice'], '<p><a><br><br /><b><strong><i><em>' ) . '</div>';
                }

                $shortcode_item_inner .= '</div>';
                $shortcode_items .= do_shortcode('[collapse title="' . $map['job_title'] . '" name="' . substr($this->provider,0,1) . $map['job_id'] . '"]' . $shortcode_item_inner . '[/collapse]');
                $this->count++;
            }

        }

        if (isset($_GET['format']) && $_GET['format'] == 'embedded') {
            if ( count( $maps ) > 0 ) {
                return $this->getPublicDisplayList( $maps );
            } else {
                return "nix!";
            }
        }

        if ( $this->count == 0 ) {
            if (isset($options['rrze-jobs_no_jobs_message'])) {
                return '<p>' . $options['rrze-jobs_no_jobs_message'] . '</a></p>';
            } else {
                return '<p>' . __('API does not return any data.', 'rrze-jobs') . '</a></p>';
            }
        }

        return do_shortcode('[collapsibles expand-all-link="true"]' . $shortcode_items . '[/collapsibles]');;
    }


    public function get_single_job( $jobid, $fallback_apply = '' ) {
        $output = '';
        $api_url = getURL( $this->provider, 'single') . $jobid;
        $data = file_get_contents($api_url);
        $data = json_decode( utf8_encode( $data ), true);

        if ( $this->provider == 'univis' ){
            $persons = $data['Person'];
            $persons = getPersons( $persons );
        }

        if ( $this->provider == 'univis' ){
            $job = $data['Position'];
        } else {
            $job = $data;
        }

        if ( !isset( $job ) || empty( $job ) ){
            return '<p>' . __('This job offer is not available', 'rrze-jobs') . '</p>';
        }

        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = ( has_custom_logo() ? wp_get_attachment_url($custom_logo_id) : '' );

        $map_template = getMap( $this->provider );
        $map = fillMap( $map_template, $job );
        $intern_allowed = isInternAllowed();

        if ( $this->provider == 'univis' ){
            $person = $persons[$job['acontact']['UnivISRef']['key']];
            foreach ( $person as $key => $val ){
                $map[$key] = $val;
            }
        }

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
            $description = $this->getDescription( $map, $this->provider );

            if ( isset($map['job_employmenttype'] ) ) {
                if ($map['job_employmenttype'] == 'voll') {
                    $map['job_employmenttype'] = 'Vollzeit';
                } elseif ($map['job_employmenttype'] == 'teil') {
                    $map['job_employmenttype'] = 'Teilzeit';
                }
            }
            if ( $this->provider == 'interamt' ) {
                $start_application_string = strpos($map['job_description'], 'Bitte bewerben Sie sich');
                if ($start_application_string !== false && $start_application_string > 100) {
                    $application_string = substr($map['job_description'], $start_application_string);
                    $map['application_link'] = strip_tags(html_entity_decode($application_string), '<a><br><br /><b><strong><i><em>');
                }
            }
            $application_email = '';
            if ( isset($map['application_link'] ) ) {
                preg_match_all( "/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+[a-zA-Z]+/i", $map['application_link'], $matches );
                if (!empty( $matches[0] ) ) {
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

            $output = '';
            $output .= '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
            $output .= do_shortcode('[three_columns_two]' . ($this->provider == 'univis' ? formatUnivIS( $description ) : $description ) .'[/three_columns_two]' . '[three_columns_one_last]' . $this->get_sidebar( $map, $logo_url ) . '[/three_columns_one_last][divider]');
            $options = get_option( 'rrze-jobs' );
            if (!isset($_GET['format']) && isset($options['rrze-jobs_job_notice']) && $options['rrze-jobs_job_notice'] != '') {
                $output .= '<hr /><div>' . strip_tags( $options['rrze-jobs_job_notice'], '<p><a><br><br /><b><strong><i><em>' ) . '</div>';
            }
            $output .= '</div>';
        }

        return $output;
    }

    private function getPublicDisplayList($maps = []) {
        $output = '';
        $last = '';
        $options = get_option( 'rrze-jobs' );
        $jobs_page_url = get_permalink($options['rrze-jobs_jobs_page']);

        foreach ($maps as $k =>$map) {
            if ($k > 2) {
                return $output;
            }
            $result = [];
            preg_match('/<ul\>(.*?)<\/ul\>/m', $map["job_description"], $result);
            if (!empty($result)) {
                $teaser = '<h3>' . __( 'Tasks', 'rrze-jobs' ) . ':</h3><ul class="job-tasks">' . $result[ 1 ] . '</ul>';
            } else {
                preg_match('/<p\>(.*?)<\/p\>/m', $map["job_description"], $result);
                if (!empty($result)) {
                    $teaser = '<h3>' . __( 'Tasks', 'rrze-jobs' ) . ':</h3><p class="job-tasks">' . $result[ 1 ] . '</p>';
                } else {
                    $teaser = $map["job_description"];
                }
            }

            $job_item = "<h2>" . $map['job_title'] . "</h2>";
            $job_item .= $teaser;
            $job_item .= '<p>';
            if ( isset( $map['job_start']) ) {
                $job_item .= '<span class="label">' . __('Job start', 'rrze-jobs') . ':</span> ' . $map['job_start'];
            }
            if ( isset( $map['application_end']) ) {
                $job_item .= '<br /><span class="label">' . __('Application deadline', 'rrze-jobs') . ':</span> ' . date('d.m.Y', strtotime($map['application_end']));
            }
            if ( isset( $map['job_employmenttype']) ) {
                $job_item .= '<br /><span class="label">' . __('Part-time / full-time', 'rrze-jobs') . ':</span> ' . $map['job_employmenttype'];
            }
            $salary = $this->getSalary( $map );
            if ( $salary != '' ) {
                $job_item .= '<br /><span class="label">'.__('Payment','rrze-jobs') . '</span>: ' . $salary;
            }

            //$job_item .= '<p><a href="' . $jobs_page_url . '#' . substr($this->provider,0,1) . $map['job_id'] . '">Link</a>';

            $job_item .= '<p><img src="'. plugin_dir_url(__FILE__ ) .'qrcode.php?url=' . $jobs_page_url . '&collapse=' . substr($this->provider,0,1) . $map['job_id'] . '"></p>';

            if ($k == (count($maps)-1) || $k > 1) {
                $last = '_last';
            }
            switch (count($maps)) {
                case 1:
                    $output .= $job_item;
                    break;
                case 2:
                    $output .= do_shortcode('[two_columns_one'.$last.']' . $job_item . '[/two_columns_one'.$last.']');
                    break;
                default:
                    $output .= do_shortcode('[three_columns_one'.$last.']' . $job_item . '[/three_columns_one'.$last.']');
                    break;
            }
        }
        return $output;
    }


    public function gutenberg_init() {
        // Skip block registration if Gutenberg is not enabled/merged.
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
        $js = '../assets/js/gutenberg.js';

        wp_register_script(
            'rrze-jobs-editor',
            plugins_url( $js, __FILE__ ),
            array(
                'wp-blocks',
                'wp-i18n',
                'wp-element',
                'wp-components',
                'wp-editor'
            ),
            filemtime( dirname( __FILE__ ) . '/' . $js )
        );

        wp_localize_script( RRZE_JOBS_TEXTDOMAIN . '-editor', 'phpConfig', $this->settings );


        register_block_type( $this->settings['block']['type'], array(
                'editor_script' => 'rrze-jobs-editor',
                'render_callback' => [$this, 'shortcodeOutput'],
                'attributes' => $this->settings
            )
        );
    }
}