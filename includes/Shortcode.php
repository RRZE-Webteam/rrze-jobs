<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;

class Shortcode
{
    /**
     * Shortcode-Klasse wird instanziiert.
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('jobs', [$this, 'jobs_shortcode'], 10, 2);
        add_action('wp_ajax_nopriv_rrze_jobs_ajax_function', [$this, 'rrze_jobs_ajax_function']);
        add_action('wp_ajax_rrze_jobs_ajax_function', [$this, 'rrze_jobs_ajax_function']);
    }

    /**
     * Enqueue der Skripte.
     */
    public function enqueue_scripts()
    {
        wp_register_style('jobs-shortcode', plugins_url('assets/css/jobs-shortcode.min.css', plugin_basename(RRZE_PLUGIN_FILE)));
        wp_register_script('jobs-shortcode', plugins_url('assets/js/jobs-shortcode.min.js', plugin_basename(RRZE_PLUGIN_FILE)));
        //wp_register_script('jobs-shortcode', plugins_url('assets/js/jobs-shortcode.js', plugin_basename(RRZE_PLUGIN_FILE)));
        wp_localize_script( 'jobs-shortcode', 'jobs_sc', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' )
            )
        );
        if (file_exists(WP_PLUGIN_DIR.'/rrze-elements/assets/css/rrze-elements.min.css')) {
	        wp_register_style( 'rrze-elements', plugins_url() . '/rrze-elements/assets/css/rrze-elements.min.css' );
        }
    }

    /**
     * Shortcode
     * @param array $atts
     * @param string $content
     * @return string
     */
    public function jobs_shortcode($atts, $content = '')
    {
        $shortcode_atts = shortcode_atts([
            'provider' => '',
            'orgid' => '',
            'jobid' => ''
        ], $atts);

        $provider = sanitize_text_field($shortcode_atts['provider']);
        $orgid = sanitize_text_field($shortcode_atts['orgid']);
        $jobid = sanitize_text_field($shortcode_atts['jobid']);
        $output = '';

        if ($provider == '')
            return '<p>' . sprintf(__(' Please specify the job portal in the shortcode attribute %sprovider=""%s', 'rrze-jobs'), '<code>', '</code>') . '</p>';

        if ($orgid == '' && $jobid == '')
            return '<p>' . __('Please provide an organisation or job ID!', 'rrze-jobs') . '</p>';

        if (strtolower($provider) == 'interamt') {
            if ($orgid != '') {
	            $output = '';
                $output .= $this->get_job_list($orgid);
            }
	        if (!empty($_GET['jobid'])) {
		        $jobid = $_GET['jobid'];
		        $output .= '<p class="rrze-jobs-closelink-container"><a href="' . get_permalink() . '" class="view-all"><i class="fa fa-close" aria-hidden="true"></i> schließen</a></p>';
            }
            if ($jobid != '') {
                $output .= $this->get_single_job($jobid);
            }
        }

        wp_enqueue_style('rrze-elements');
        wp_enqueue_style('jobs-shortcode');
        wp_enqueue_script('jobs-shortcode');

        return $output;
    }

    public function rrze_jobs_ajax_function() {
        $responseData = $this->get_single_job($_POST['jobid']);
        echo json_encode($responseData);
        wp_die();
    }

    private function get_job_list ($orgid) {
        $api_url = sprintf('https://www.interamt.de/koop/app/webservice_v2?partner=%s', $orgid);
        $json = file_get_contents($api_url);
        if (!$json)
        	return '<p>Die Schnittstelle ist momentan nicht erreichbar.</p>';
        $json = utf8_encode($json);
        $obj = json_decode($json);
	    $custom_logo_id = get_theme_mod('custom_logo');
	    $logo_url = has_custom_logo() ? wp_get_attachment_url($custom_logo_id) : '';

        $output = '';
        $output .= '<ul class=\'rrze-jobs-list\'>';
        foreach ($obj->Stellenangebote as $job) {
        	$output .= '<li itemscope itemtype="https://schema.org/JobPosting"><a href="?jobid=' . $job->Id . '" data-jobid="' . $job->Id . '" class="joblink"><span itemprop="title">' . $job->StellenBezeichnung . '</span></a>';
            if (isset($job->Bezahlung->Entgelt) && $job->Bezahlung->Entgelt != '') {
	            $output .= ' (' . $job->Bezahlung->Entgelt . ')';
            }
	        $output .= '<meta itemprop="hiringOrganization" content="' . $job->Behoerde . '" />'
                . '<meta itemprop="datePosted" content="' . $this->transform_date($job->Daten->Eingestellt) . '" />'
                . '<meta itemprop="description" content="' . $job->StellenBezeichnung . '" />'
                . '<meta itemprop="validThrough" content="' . $this->transform_date($job->Daten->Bewerbungsfrist) . '" />'
                . '<span itemprop="jobLocation" itemscope itemtype="http://schema.org/Place" >'
                . '<meta itemprop="name" content="' . $job->Behoerde . '" />'
                . '<meta itemprop="logo" content="' . $logo_url . '" />'
                . '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress" >'
                . '<meta itemprop="postalCode" content="' . $job->Ort->Plz . '" />'
                . '<meta itemprop="addressLocality" content="' . $job->Ort->Stadt . '" />'
                . '</span>'
                . '</li>';
        }
        $output .= '</ul>';
        return $output;
    }

    private function get_single_job($jobid) {
        $api_url_job = sprintf('https://www.interamt.de/koop/app/webservice_v2?id=%s', $jobid);
        $json_job = file_get_contents($api_url_job);
        $json_job = utf8_encode($json_job);
        $obj_job = json_decode($json_job);
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = has_custom_logo() ? wp_get_attachment_url($custom_logo_id) : '';
        $azubi = (strpos($obj_job->Stellenbezeichnung, 'Auszubildende') !== false) ? true : false;
        var_dump($azubi);
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
        /*if ($obj_job->AnzahlStellen != '') {
            $sidebar .= '<dt>'.__('Anzahl Stellen','rrze-jobs') . '</dt><dd>' . $obj_job->AnzahlStellen . '</dd>';
        }*/
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
            //. '<meta itemprop="hiringOrganization" content="' . $obj_job->StellenangebotBehoerde . '" />'
            . '<meta itemprop="qualifications" content="' . $obj_job->Qualifikation . '" />'
            . '<meta itemprop="url" content="' . get_permalink() . '?jobid=' . $obj_job->Id . '" />'
            . '</div>';
        $sidebar .= '</div>';

        $output = '';
        $output .= '<div class="rrze-jobs-single" itemscope itemtype="https://schema.org/JobPosting">';
        $output .= do_shortcode('[three_columns_two]' . $description .'[/three_columns_two]' . '[three_columns_one_last]' . $sidebar . '[/three_columns_one_last][divider]');
        $output .= '</div>';

        return $output;
    }

    private function transform_date($date) {
        $date_parts = explode('.', $date);
        if (empty($date_parts)) {
            return '';
        } else {
            return $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
        }
    }
}