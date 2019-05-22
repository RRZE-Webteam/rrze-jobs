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
    }

    /**
     * Enqueue der Skripte.
     */
    public function enqueue_scripts()
    {
        wp_register_style('jobs-shortcode', plugins_url('assets/css/jobs-shortcode.min.css', plugin_basename(RRZE_PLUGIN_FILE)));
        //wp_register_script('jobs-shortcode', plugins_url('assets/js/jobs-shortcode.min.js', plugin_basename(RRZE_PLUGIN_FILE)));
        //wp_register_script('jobs-shortcode', plugins_url('assets/js/jobs-shortcode.js', plugin_basename(RRZE_PLUGIN_FILE)));
    }

    /**
     * Shortcode
     * @param  array   $atts
     * @param  string  $content
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
            return '<p>' .sprintf(__(' Please specify the job portal in the shortcode attribute %sprovider=""%s', 'rrze-jobs'),'<code>','</code>') . '</p>';

        if ($orgid == '' && $jobid == '')
            return '<p>' .__('Please provide an organisation or job ID!', 'rrze-jobs') . '</p>';

        if (strtolower($provider) == 'interamt') {
            if ($orgid != '') {
                if (!empty($_GET['jobid'])) {
                    $jobid = $_GET['jobid'];
                    $api_url_job = sprintf('https://www.interamt.de/koop/app/webservice_v2?id=%s', $jobid);
                    $json_job = file_get_contents($api_url_job);
                    $json_job = utf8_encode($json_job);
                    $obj_job = json_decode($json_job);
                    /*print "<pre>";
                    var_dump($obj_job);
                    print "</pre>";*/
                    $custom_logo_id = get_theme_mod( 'custom_logo' );
                    $logo_url = has_custom_logo() ?  wp_get_attachment_url($custom_logo_id) : '';

                    $output .= '<p></p><a href="' . get_permalink() . '" class="view-all"><i class="fa fa-arrow-left" aria-hidden="true"></i> ' . __('back to list','rrze-jobs') . '</a></p>';
                    $output .= '<div itemscope itemtype="https://schema.org/JobPosting" class="rrze-jobs-single">';
                    $output .= '<div itemprop="description">' . $obj_job->Beschreibung . '</div>';
                    $output .= '<meta itemprop="title" content="' . $obj_job->Stellenbezeichnung . '" />'
                            . '<meta itemprop="datePosted" content="' . $obj_job->DatumOeffentlichAusschreiben . '" />'
                            //. '<span itemprop="baseSalary" itemscope itemtype="http://schema.org/PriceSpecification" >'
                            //    . '<meta itemprop="price" content="' . $obj_job->TarifEbeneVon . ' bis ' . $obj_job->TarifEbeneBis . '" />'
                            //. '</span>'
                            . '<meta itemprop="employmentType" content="' . $obj_job->Teilzeit . '" />'
                            . '<meta itemprop="validThrough" content="' . $obj_job->DatumBewerbungsfrist . '" />'
                            . '<meta itemprop="workHours" content="' . $obj_job->WochenarbeitszeitArbeitnehmer . '" />'
                            . '<meta itemprop="hiringOrganization" content="' . $obj_job->StellenangebotBehoerde . '" />'
                            . '<meta itemprop="qualifications" content="' . $obj_job->Qualifikation . '" />'
                            . '<meta itemprop="url" content="' . get_permalink() . '?jobid=' . $obj_job->Id . '" />'
                            . '<span itemprop="jobLocation" itemscope itemtype="http://schema.org/Place" >'
                                . '<meta itemprop="name" content="' . $obj_job-> StellenangebotBehoerde. '" />'
                                . '<meta itemprop="logo" content="' . $logo_url . '" />'
                                . '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress" >'
                                    . '<meta itemprop="streetAddress" content="' . $obj_job->Einsatzort->EinsatzortStrasse . '" />'
                                    . '<meta itemprop="postalCode" content="' . $obj_job->Einsatzort->EinsatzortPLZ . '" />'
                                    . '<meta itemprop="addressLocality" content="' . $obj_job->Einsatzort->EinsatzortOrt . '" />'
                                    . '<meta itemprop="addressRegion" content="' . $obj_job->BeschaeftigungBereichBundesland . '" />'
                                . '</span>'
                                . '<meta itemprop="url" content="' . $obj_job->HomepageBehoerde . '" />'
                            . '</span>';
                    $output .= '</div>';
                    $output .= '<p></p><a href="' . get_permalink() . '" class="view-all"><i class="fa fa-arrow-left" aria-hidden="true"></i> ' . __('back to list','rrze-jobs') . '</a></p>';

                } else {
                    $api_url = sprintf('https://www.interamt.de/koop/app/webservice_v2?partner=%s', $orgid);
                    $json = file_get_contents($api_url);
                    $json = utf8_encode($json);
                    $obj = json_decode($json);
                    /*print "<pre>";
                    var_dump($obj);
                    print "</pre>";
                    */

                    $output .= '<ul>';
                    foreach ($obj->Stellenangebote as $job) {
                        $output .= '<li><a href="?jobid=' . $job->Id . '"">' . $job->StellenBezeichnung . '</a> (' . $job->Bezahlung->Entgelt . ')' . '</li>';
                    }
                    $output .= '</ul>';
                }
            }

            if ($orgid == '' && $jobid != '') {
                $api_url_job = sprintf('https://www.interamt.de/koop/app/webservice_v2?id=%s', $jobid);
                $json_job = file_get_contents($api_url_job);
                $json_job = utf8_encode($json_job);
                $obj_job = json_decode($json_job);
                /*print "<pre>";
                var_dump($obj_job);
                print "</pre>";*/
                $output .= '<div class=\'rrze-jobs-single\'>' . $obj_job->Beschreibung . '</div>';
            }
        }
        return $output;

        wp_enqueue_style('jobs-shortcode');
        //wp_enqueue_script('jobs-shortcode');

    }
}
