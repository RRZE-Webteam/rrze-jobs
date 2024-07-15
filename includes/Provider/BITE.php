<?php
/**
 * BITE
 *
 * Created on : 18.10.2022
 */

namespace RRZE\Jobs\Provider;

defined('ABSPATH') || exit;
use RRZE\Jobs\Cache;
use RRZE\Jobs\Helper;
use RRZE\Jobs\Provider;

class BITE extends Provider {

    protected $api_url;
    protected $cachetime;
    protected $cachetime_list;
    protected $cachetime_single;
    protected $uriparameter;
    protected $request_args;
    protected $required_fields;


    public function __construct($use_cache = true) {
        $this->use_cache = $use_cache;

        $this->api_url = 'https://api.b-ite.io/v1/jobpostings';
        // list: https://api.b-ite.io/v1/jobpostings
        // single: https://api.b-ite.io/v1/jobpostings/
        $this->url = 'https://www.b-ite.com/';
        $this->name = "BITE";
        $this->cachetime = 1 * HOUR_IN_SECONDS;
        $this->cachetime_list = $this->cachetime;
        $this->cachetime_single = 2 * HOUR_IN_SECONDS;
        $this->uriparameter = '';
	
        $this->request_args = array(
            'timeout' => 45,
            'redirection' => 5,
            'headers' => [
                'Content-Type' => 'application/json',
                'BAPI-Token' => '', // API-KEY
            ],
            'sslverify' => true,

        );

        // defines all required parameters for defined request method
        $this->required_fields = array(
            'get_group' => array(),
            'get_list' => array(),
            'get_single' => array('id' => 'string'),
        );

    }

    // which methods do we serve
    public $methods = array(
        "get_group", "get_list", "get_single", "map_to_schema", "get_uri", "required_parameter",
    );

    // map univis field names and entries to schema standard
    public function map_to_schema($data) {
        $newpositions = array();
        // First we make a simple Mapping by an array with fields, that match
        // as they are

        if (isset($data['entries'])) {
            // in a list request we get all jobs in the array Stellenangebote
            foreach ($data['entries'] as $num => $job) {
                if (is_array($job)) {
                    $newpositions['JobPosting'][$num] = $this->generate_schema_values($job);
                    $newpositions['JobPosting'][$num]['_provider-values'] = $this->add_remaining_non_schema_fields($job);
                    $newpositions['JobPosting'][$num]['_provider-values']['provider'] = $this->name;
                    $newpositions['JobPosting'][$num]['provider'] = $this->name;
                    $newpositions['JobPosting'][$num]['hash'] = $job["hash"];
                }
            }
            $data = $newpositions;
        } else {
            // in a single request we get all jobdata direct on frist level
            if (is_array($data)) {
                $newpositions['JobPosting'][0] = $this->generate_schema_values($data);
                $newpositions['JobPosting'][0]['_provider-values'] = $this->add_remaining_non_schema_fields($data);
                $newpositions['JobPosting'][0]['_provider-values']['provider'] = $this->name;
                $newpositions['JobPosting'][0]['provider'] = $this->name;
                $newpositions['JobPosting'][0]['hash'] = $data["hash"];
                $data = $newpositions;
            }

        }
        return $data;
    }

    // go through the provider data stream and diff it from the fields
    // we already map to schema.
    // all fields that remain are new or was not mapped to schema and
    // may be used to other purpuses
    private function add_remaining_non_schema_fields($jobdata) {
        $known_fields = array("assignees", "hash", "emailTemplate", "jobSite",
            "subsidiary", "content", "seo", "title", "location", "custom",
            "description", "locale", "identification", "keywords");

        $known_custom_fields = array("einleitung", "03_aufgaben_profil", "interessiert",
            "sie_haben_fragen", "einleitungstext", "beschreibung_beschaeftigungsstelle", "aufgaben", "stellenzusatz", "profil",
            "job_experience", "job_qualifications_nth", "angebot", "wir_bieten",
            "ausschreibungskennziffer", "beschaeftigungsumfang", "zuordnung",
            "orig_occupationalCategory", "hiringorganization", "place_of_employment_street",
            "place_of_employment_house_number", "place_of_employment_postcode", "place_of_employment_city",
            "contact_email", "contact_tel", "contact_name", "06c_schluss", "entgelt_ar",
            "job_limitation_duration", "abschlusstext", "06_schluss",
            "estimatedsalary", "framework", "jobstartdate", "jobstartdate2", "job_workhours", "workHours", "befristung",
            "bite_pa_data", "befristung_wrapper", "job_limitation_duration_wrapper",
            "angebot_wrapper", "stellenzusatz_wrapper", "e-mail_signatur_persoenlich",
            "e-mail_signatur_neutral", "workhours_wrapper", "job_qualifications_nth_wrapper", "status");

        // keynames we already used in schema values or which we dont need anyway

        $providerfield = array();

        foreach ($jobdata as $name => $value) {
            if (($name != 'custom') && (!in_array($name, $known_fields))) {
                $providerfield[$name] = $value;
            } elseif ($name == 'custom') {
                foreach ($value as $subname => $subval) {
                    if (!in_array($subname, $known_custom_fields)) {
                        $providerfield[$subname] = $subval;
                    }
                }
            }
        }

        return $providerfield;
    }

    // some missing schema fields can be generated automatically
    private function generate_schema_values($jobdata) {
        // Paramas:
        // $jobdata - one single jobarray

        $res = array();

	
        // Cause several customer might use different custom fields but also
        // same fields but in different way, I insert here a switch.
        // default ist the custom settings of the FAU.

        $customer = $this->get_customer($jobdata);

            // the following schema fields are not set in univis data,
            // but they can be evaluated from others

      
        if ($customer == 'utn') {
            if (!empty($jobdata['custom']['einleitung'])) {
                $res['employerOverview']  = $jobdata['custom']['einleitung'].' '. $jobdata['title'];
            }
             // description
            if  (!empty($jobdata['custom']['03_aufgaben_profil'])) {
               $res['description'] = $jobdata['custom']['03_aufgaben_profil'];
            }
            // disambiguatingDescription
            $res['disambiguatingDescription'] = '';
            if (!empty($jobdata['custom']['interessiert'])) {
                $res['disambiguatingDescription'] = $jobdata['custom']['interessiert'];
            }

            if (!empty($jobdata['custom']['sie_haben_fragen'])) {
               $res['disambiguatingDescription'] .= "\n\n".$jobdata['custom']['sie_haben_fragen'];
            }
            if (!empty($jobdata['custom']['06_schluss'])) {
               $res['text_jobnotice'] = $jobdata['custom']['06_schluss'];
            }

        } else {
            // Die Einleitung geht in ein die employerOverview
            // https://schema.org/employerOverview
            if (!empty($jobdata['custom']['einleitungstext'])) {
                $res['employerOverview']  = $jobdata['custom']['einleitungstext'];
            }
            // neu 02/2024
            if (!empty($jobdata['custom']['beschreibung_beschaeftigungsstelle'])) {
                $res['employerWorkplace'] = $jobdata['custom']['beschreibung_beschaeftigungsstelle'];
            }
            // description
            if (!empty($jobdata['custom']['aufgaben'])) {
               $res['description'] = $jobdata['custom']['aufgaben'];
            }
            // disambiguatingDescription
            if (!empty($jobdata['custom']['stellenzusatz'])) {
                $res['disambiguatingDescription'] = $jobdata['custom']['stellenzusatz'];
            }

            $res['qualifications'] = '';
            if (!empty($jobdata['custom']['profil'])) {
                if (!empty($jobdata['custom']['job_experience']) || !empty($jobdata['custom']['job_qualifications_nth'])){
                    $res['qualifications'] = '<p><strong>{{=const.title_qualifications_required}}:</strong></p>';
                } else {
                    $res['qualifications'] = '';
                }
                $res['qualifications'] .= $jobdata['custom']['profil'];
            }
            if (!empty($jobdata['custom']['job_experience'])) {
                if (!empty($jobdata['custom']['profil']) || !empty($jobdata['custom']['job_qualifications_nth'])){
                    $res['qualifications'] .= '<p><strong>{{=const.title_qualifications_experience}}:</strong></p>';
                }
                $res['qualifications'] .= $jobdata['custom']['job_experience'];
            }

            if (!empty($jobdata['custom']['job_qualifications_nth'])) {
                if (!empty($jobdata['custom']['profil']) || !empty($jobdata['custom']['job_experience'])){
                    $res['qualifications'] .= '<p><strong>{{=const.title_qualifications_optional}}:</strong></p>';
                }
                $res['qualifications'] .= $jobdata['custom']['job_qualifications_nth'];
            }

            if (!empty($jobdata['custom']['abschlusstext'])) {
               $res['text_jobnotice'] = $jobdata['custom']['abschlusstext'];
            }
        }


        // jobBenefits
        if (!empty($jobdata['custom']['angebot'])) {
            $res['jobBenefits'] = $jobdata['custom']['angebot'];
        }
        if (!empty($jobdata['custom']['wir_bieten'])) {
            $res['jobBenefits'] = isset($res['jobBenefits']) ? $res['jobBenefits'] . $jobdata['custom']['wir_bieten'] : $jobdata['custom']['wir_bieten'];
        }

        
        // intern (needed by FAU-Jobportal)
        //   $res['intern'] = ((!empty($data['_provider-values']['intern']) && $data['_provider-values']['intern'] === true) ? true : false);
       /*if (!empty($jobdata['custom']['intern'])) {
           $res['intern'] = ((!empty($jobdata['custom']['intern']) && $jobdata['custom']['intern'] === true) ? true : false);
       } elseif (!empty($jobdata['custom']['job_intern'])) {
           $res['intern'] = ((!empty($jobdata['custom']['job_intern']) && $jobdata['custom']['job_intern'] === true) ? true : false);
       } else {
           $res['intern'] = false;
       }*/
        if (isset($jobdata['channels']['channel1']['to']) && strtotime($jobdata['channels']['channel1']['to']) > time()) {
            $res['intern'] = true;
        } else {
            $res['intern'] = false;
        }

        // title
        if (!empty($jobdata['title'])) {
            $res['title'] = $jobdata['title'];
            
            // Wenn der Jobtitel den STRING *TEST* enthält, wird die Stelle 
            // als INTERN markiert.
            if (preg_match('/\*test\*/i', $res['title'])) {
                 $res['intern'] = true;
            }
        }

        // identifier
        $res['identifier'] = $jobdata['id'];
        $res['id'] = $jobdata['id'];

        // job_id (needed by FAU-Jobportal)
        $res['job_id'] = $jobdata['id'];

      
       

        if (!empty($jobdata['ausschreibungskennziffer'])) {
            $res['identifier'] = $jobdata['ausschreibungskennziffer'];
        } elseif (!empty($jobdata['identification'])) {
            $res['identifier'] = $jobdata['identification'];
        }

        // employmentType
        $typeliste = array();
        $beschaeftigungsumfang = '';
        if ((isset($jobdata['seo'])) && (isset($jobdata['seo']['employmentType']))) {
            //    $typeliste = $jobdata['seo']['employmentType'];
            foreach ($jobdata['seo']['employmentType'] as $val) {
                if (!empty($val)) {
                    $val = strtoupper($val);
                    $typeliste[] = $val;

                    if ($val == 'FULL_TIME') {
                        if (!empty($beschaeftigungsumfang)) {
                            $beschaeftigungsumfang = ', ';
                        }
                        $beschaeftigungsumfang = __('Full time', 'rrze-jobs');
                    }
                    if ($val == 'PART_TIME') {
                        if (!empty($beschaeftigungsumfang)) {
                            $beschaeftigungsumfang = ', ';
                        }
                        $beschaeftigungsumfang = __('Part time', 'rrze-jobs');
                    }
                    if ($val == 'TEMPORARY') {
                        $res['text_befristet'] = __('Temporary employment', 'rrze-jobs');
                    }

                }
            }
        }
        if (!empty($beschaeftigungsumfang)) {
            $res['text_workingtime'] = $beschaeftigungsumfang;
        }

        if ((isset($jobdata['custom']['beschaeftigungsumfang'])) && (!empty($jobdata['custom']['beschaeftigungsumfang']))) {
            $res['text_workingtime'] = $jobdata['custom']['beschaeftigungsumfang'];
        }

        //  muss aber einen oder mehrere der folgenden
        // Werte enthalten:
        /*
        FULL_TIME
        PART_TIME
        CONTRACTOR
        TEMPORARY
        INTERN
        VOLUNTEER
        PER_DIEM
        OTHER
         */
        // Beispiel: "employmentType": ["FULL_TIME", "CONTRACTOR"]

        // Gruppe / Kategorie der Stelle
        if (!empty($jobdata['custom']['zuordnung'])) {
            $res['occupationalCategory'] = $jobdata['custom']['zuordnung'];
        }
        if (!empty($jobdata['custom']['orig_occupationalCategory'])) {
            $res['orig_occupationalCategory'] = $jobdata['custom']['orig_occupationalCategory'];
        }
	

        if (isset($jobdata['channels']) && (isset($jobdata['channels']['channel0']) || isset($jobdata['channels']['channel1']))) {
            // channel0 = Öffentliche Ausschreibungen
            // channel1 = Interne Ausschreibungen
            // Info vom 15.04.2024: channel0 darf nicht leer sein, daher wird bei internen Ausschreibungen in channel0 ein Datum in der Vergangenheit angegeben.
            // Notice: We validate the dates here, cause it might be possible, that we need the original
            // time on other functions. So we dont want to remove it in the sanitize-function
            if (isset($jobdata['channels']['channel1']) && strtotime($jobdata['channels']['channel1']['from']) > strtotime($jobdata['channels']['channel0']['from'])) {
                $res['datePosted'] = $this->sanitize_dates($jobdata['channels']['channel1']['from']);
                $res['validThrough'] = $this->sanitize_dates($jobdata['channels']['channel1']['to']);
                if (isset($jobdata['channels']['channel1']['route']['application'])) {
                    $res['applicationContact']['url'] = $jobdata['channels']['channel1']['route']['application'];
                }
                if (isset($jobdata['channels']['channel1']['route']['email'])) {
                    $res['applicationContact']['email'] = $jobdata['channels']['channel1']['route']['email'];
                    if (isset($jobdata['custom']['ausschreibungskennziffer'])) {
                        $res['applicationContact']['email_subject'] = $jobdata['custom']['ausschreibungskennziffer'];
                    }
                }
                if (isset($jobdata['channels']['channel1']['route']['posting'])) {
                    $res['url'] = $jobdata['channels']['channel1']['route']['posting'];
                    $res['sameAs'] = $jobdata['channels']['channel1']['route']['posting'];
                }
            } else {
                $res['datePosted'] = $this->sanitize_dates($jobdata['channels']['channel0']['from']);
                $res['validThrough'] = $this->sanitize_dates($jobdata['channels']['channel0']['to']);
                if (isset($jobdata['channels']['channel0']['route']['application'])) {
                    $res['applicationContact']['url'] = $jobdata['channels']['channel0']['route']['application'];
                }
                if (isset($jobdata['channels']['channel0']['route']['email'])) {
                    $res['applicationContact']['email'] = $jobdata['channels']['channel0']['route']['email'];
                    if (isset($jobdata['custom']['ausschreibungskennziffer'])) {
                        $res['applicationContact']['email_subject'] = $jobdata['custom']['ausschreibungskennziffer'];
                    }
                }
                if (isset($jobdata['channels']['channel0']['route']['posting'])) {
                    $res['url'] = $jobdata['channels']['channel0']['route']['posting'];
                    $res['sameAs'] = $jobdata['channels']['channel0']['route']['posting'];
                }
            }

            $res['directApply'] = true;
        }

        if (isset($jobdata['custom']['place_of_employment_street'])) {
            $res['jobLocation']['address']['streetAddress'] = $jobdata['custom']['place_of_employment_street'];
            if (isset($jobdata['custom']['place_of_employment_house_number'])) {
                $res['jobLocation']['address']['streetAddress'] .= ' ' . $jobdata['custom']['place_of_employment_house_number'];
            }
        }
        if (isset($jobdata['custom']['place_of_employment_postcode'])) {
            $res['jobLocation']['address']['postalCode'] = $jobdata['custom']['place_of_employment_postcode'];
        }

        if (isset($jobdata['custom']['place_of_employment_city'])) {
            $res['jobLocation']['address']['addressLocality'] = $jobdata['custom']['place_of_employment_city'];
        }

        if (!isset($res['jobLocation']['address']['addressRegion'])) {
            $res['jobLocation']['address']['addressRegion'] = __('Bavaria', 'rrze-jobs');
        }
        if (!isset($res['jobLocation']['address']['addressCountry'])) {
            $res['jobLocation']['address']['addressCountry'] = 'DE';
        }

        // fallback to defaults in standard array location

        if (empty($res['jobLocation']['address']['streetAddress']) && (isset($jobdata['location']['street']))) {
            $res['jobLocation']['address']['streetAddress'] = $jobdata['location']['street'];
            if (isset($jobdata['location']['houseNumber'])) {
                $res['jobLocation']['address']['streetAddress'] .= ' ' . $jobdata['location']['houseNumber'];
            }

        }
        if (empty($res['jobLocation']['address']['postalCode']) && (isset($jobdata['location']['postCode']))) {
            $res['jobLocation']['address']['postalCode'] = $jobdata['location']['postCode'];
        }
        if (empty($res['jobLocation']['address']['addressLocality']) && (isset($jobdata['location']['city']))) {
            $res['jobLocation']['address']['addressLocality'] = $jobdata['location']['city'];
        }
        if (empty($res['jobLocation']['address']['addressCountry']) && (isset($jobdata['location']['country']))) {
            $res['jobLocation']['address']['addressCountry'] = $jobdata['location']['country'];
        }

        // jobLocation (type: Place)
        // Achtung: Für Google muss die Property addressCountry (DE) enthalten sein.
        /* "jobLocation": {
        "@type": "Place",
        "address": {
        "@type": "PostalAddress",
        "streetAddress": "555 Clancy St",   aus acontact.location.street
        "addressLocality": "Detroit",     aus acontact.location.ort ohne plz
        "addressRegion": "MI",    defaults Bayern
        "postalCode": "48201",     aus acontact.location.ort nur plz
        "addressCountry": "US"    defaults auf DE
        }
        }
         */

        // hiringOrganization   (type: Organzsation)
        // aus orgunit und oder orgname generieren
        // Der Inhalt (Ansprechperson) contact geht hier in contactpoint mit ein
        if (isset($jobdata['custom']['hiringorganization'])) {
            if (is_string($jobdata['custom']['hiringorganization'])) {
                $res['hiringOrganization']['name'] = $jobdata['custom']['hiringorganization'];
                $res['employmentUnit']['name'] = $jobdata['custom']['hiringorganization'];
            } else {
                if (isset($jobdata['custom']['hiringorganization']['title'])) {
                    $res['employmentUnit']['name'] = $jobdata['custom']['hiringorganization']['title'];
                    $res['hiringOrganization']['name'] = $jobdata['custom']['hiringorganization']['title'];
                }
                if (isset($jobdata['custom']['hiringorganization']['url'])) {
                    $res['employmentUnit']['url'] = $jobdata['custom']['hiringorganization']['url'];
                    $res['hiringOrganization']['url'] = $jobdata['custom']['hiringorganization']['url'];
                }
                if (isset($jobdata['custom']['hiringorganization']['orgnum'])) {
                    $res['hiringOrganization']['fauorg'] = $jobdata['custom']['hiringorganization']['orgnum'];
                }
            }

        }
        if (isset($jobdata['custom']['contact_email'])) {
            $res['employmentUnit']['email'] = $jobdata['custom']['contact_email'];
        }
        if (isset($jobdata['custom']['contact_tel'])) {
            $res['employmentUnit']['telephone'] = $jobdata['custom']['contact_tel'];
        }
        if (isset($jobdata['custom']['contact_name'])) {
            $res['employmentUnit']['name'] = $jobdata['custom']['contact_name'];
        }

        if (isset($jobdata['custom']['06c_schluss'])) {
            // Kontaktstring aus der UTN
            $res['applicationContact']['description'] = $jobdata['custom']['06c_schluss'];
        }

        $res['estimatedSalary'] = '';

        if ((isset($jobdata['custom']['estimatedsalary'])) && (!empty($jobdata['custom']['estimatedsalary']))) {
            if (is_array($jobdata['custom']['estimatedsalary'])) {
                $salary = $to = $from = '';
                if (count($jobdata['custom']['estimatedsalary']) > 1) {
                    $sortarray = $jobdata['custom']['estimatedsalary'];
                    natsort($sortarray);
                    $from = array_shift($sortarray);
                    $to = end($sortarray);

                } else {
                    $from = array_shift($jobdata['custom']['estimatedsalary']);
                }

                $from = $this->sanitize_tvl($from);
                $to = $this->sanitize_tvl($to);

                $res['estimatedSalary'] = $this->get_Salary_by_TVL($from, $to);

            } else {
                $res['estimatedSalary'] = $this->get_Salary_by_TVL($jobdata['custom']['estimatedsalary']);
            }
        }

        if ((isset($jobdata['custom']['entgelt_ar'])) && (!empty($jobdata['custom']['entgelt_ar']))) {
            if (is_array($jobdata['custom']['entgelt_ar'])) {
                $salary = $to = $from = '';
                if (count($jobdata['custom']['entgelt_ar']) > 1) {
                    $sortarray = $jobdata['custom']['entgelt_ar'];
                    natsort($sortarray);
                    $from = array_shift($sortarray);
                    $to = end($sortarray);

                } else {
                    $from = array_shift($jobdata['custom']['entgelt_ar']);
                }

                $from = $this->sanitize_besoldung($from);
                $to = $this->sanitize_besoldung($to);

                if (empty($res['estimatedSalary'])) {
                    $res['estimatedSalary'] = $this->get_Salary_by_TVL($from, $to);
                } else {
                    $res['estimatedSalary']["value"]["value"] .= ', ' . $from;
                    $res['estimatedSalary']["stringvalue"] .= ', ' . $from;
                    if (!empty($to)) {
                        $res['estimatedSalary']["value"]["value"] .= ' - ' . $to;
                        $res['estimatedSalary']["stringvalue"] .= ' &mdash; ' . $to;
                    }

                }

            } else {
                if (empty($res['estimatedSalary'])) {
                    $res['estimatedSalary'] = $this->get_Salary_by_TVL($jobdata['custom']['entgelt_ar']);
                } else {
                    $res['estimatedSalary']["value"]["value"] .= ', ' . $jobdata['custom']['entgelt_ar'];
                    $res['estimatedSalary']["stringvalue"] = $res['estimatedSalary']["value"]["value"];
                }
            }
        }

        if ((empty($res['estimatedSalary'])) && (isset($jobdata['custom']['festbetrag'])) && (!empty($jobdata['custom']['festbetrag']))) {
            $res['estimatedSalary'] = $this->get_Salary_by_TVL($jobdata['custom']['festbetrag']);
        }

        // workHours
        // wenn job_workhours gesetzt  (=Wann wird gearbeitet und optional wieviel Stunden, wenn Zahlenwert)
        // zzgl. workhours (Vormittags / nachmittags), wenn gesetzt, kann ggf. zätzlich zur STundenzahl als job_workhours gesetzt sein
        // ausserdem, wenn framework gesetzt:
        //  Nachtdienst
        //  Schichtdienst
        //   Bereitsschaftsdienst

        $res['workHours'] = '';

        if ((isset($jobdata['custom']['job_workhours'])) && (!empty($jobdata['custom']['job_workhours']))) {

            if (preg_match_all('/^[0-9,\.]+$/i', $jobdata['custom']['job_workhours'], $output_array)) {
                $res['workHours'] = $jobdata['custom']['job_workhours'] . ' ' . __('hours per week', 'rrze-jobs');
            } else {
                $res['workHours'] = $jobdata['custom']['job_workhours'];
            }

        }
        if ((isset($jobdata['custom']['workhours'])) && (!empty($jobdata['custom']['workhours']))) {
            if (!empty($res['workHours'])) {
                $res['workHours'] .= ', ';
            }
            $res['workHours'] .= $jobdata['custom']['workhours'];
        }

        if ((isset($jobdata['custom']['framework'])) && (!empty($jobdata['custom']['framework']))) {
            $workspec = '';
            foreach ($jobdata['custom']['framework'] as $worktyp) {
                switch ($worktyp) {
                    case 'bereitschaftsdienst':
                    case 'rufbereitschaft':
                        $workspec .= ', ' . __('on-call duty', 'rrze-jobs');
                        break;
                    case 'schichtdienst':
                        $workspec .= ', ' . __('shift work', 'rrze-jobs');
                        break;
                    case 'nachtdienst':
                        $workspec .= ', ' . __('night duty', 'rrze-jobs');
                        break;
                    case 'wechselschicht':
                        $workspec .= ', ' . __('rotating shift', 'rrze-jobs');
                        break;
                }
            }
            if (!empty($workspec)) {
                $res['workHours'] .= $workspec;
            }
        }

        if (!empty($jobdata['custom']['jobstartdate'])) {

            if ($jobdata['custom']['jobstartdate'] == "-1") {
                $res['jobImmediateStart'] = true;
                $res['jobStartDate'] = __('As soon as possible', 'rrze-jobs');
                $res['jobStartDateSort'] = date('Ymd', strtotime('1. Januar 1970')); // we need this to sort in sortArrayByField()

            } else {
                $res['jobStartDate'] = $jobdata['custom']['jobstartdate'];
                $res['jobStartDateSort'] = date('Ymd', strtotime($res['jobStartDate'])); // we need this to sort in sortArrayByField()
            }
        } elseif (!empty($jobdata['custom']['jobstartdate2'])) {

            if ($jobdata['custom']['jobstartdate2'] == "-1") {
                $res['jobImmediateStart'] = true;
                $res['jobStartDate'] = __('As soon as possible', 'rrze-jobs');
                $res['jobStartDateSort'] = date('Ymd', strtotime('1. Januar 1970')); // we need this to sort in sortArrayByField()

            } else {
                $res['jobStartDate'] = $jobdata['custom']['jobstartdate2'];
                $res['jobStartDateSort'] = date('Ymd', strtotime($res['jobStartDate'])); // we need this to sort in sortArrayByField()
            }
        }

        if (isset($jobdata['custom']['befristung']) && ($jobdata['custom']['befristung'] == true)) {
            if ((!empty($jobdata['custom']['job_limitation_duration'])) || (isset($jobdata['custom']['job_limitation_duration']))) {
                $res['text_befristet'] = __('Temporary employment', 'rrze-jobs') . ': ' . $jobdata['custom']['job_limitation_duration'] . " " . __('months', 'rrze-jobs');
            }
            $typeliste[] = 'TEMPORARY';
        }

        $res['employmentType'] = $typeliste;

        if (isset($jobdata['keywords'])) {
            $res['text_keywords'] = $this->get_array_as_string($jobdata['keywords']);
        }

        return $res;
    }

    
    // get the customer for the jobdata
    private function get_customer($data) {
	
        // cause there is no useable identifier (['custom']['hiringorganization']) wont do it, 
        // cause at FAU we use it for the orgunits and at UTN fpr the whole org)
        // i create s switch by the known custom fields

        $customerer_fields = array(
          'fau'	    => ["zuordnung", "aufgaben", "einleitungstext", "beschreibung_beschaeftigungsstelle", "angebot", "profil", "stellenzusatz"],
          'utn'	    => ["einleitung", "01_introduction", "02_addition","03_aufgaben_profil", "03_qualifications", "04_Interested", "05_questions", "sie_haben_fragen", "interessiert", "06_schluss"]
        );


        $found = '';
        foreach ($customerer_fields as $customer => $fields) {
            $cnt_c = 0;
            foreach ($fields as $name) {
                if (isset($data['custom'][$name])) {
                    $cnt_c++;
                }
            }
            if ($cnt_c == count($fields)) {
                $found = $customer;
                break;
            }

        }
        return $found;
	
	
	
    }
    
    // needed for FAU-Jobporal
    public function get_group($params) {
        return $this->get_list($params);
    }

    // make request for a positions list and return it as array
    public function get_list($params)
    {
        $check = $this->required_parameter("get_list", $params);

        if (is_array($check)) {
            $aRet = [
                'valid' => false,
                'code' => 405,
                'error' => $check,
                'params_given' => $params,
                'content' => '',
            ];
            return $aRet;
        }
        $response = $this->get_data($params, "get_list");

        if ($response['valid'] == true) {

            // After i got the list from interamt, i have to get the detail
            // data from each single job in an additional request, cause the
            // list doesnt contains the details

            $singleparams = $params;

            if ((isset($response['content']['total']) && ((intval($response['content']['total']) > 0)))) {

                $entries = array();
                if (isset($response['content']['entries'])) {
                    foreach ($response['content']['entries'] as $num => $pos) {
                        $singleparams['get_single']['id'] = $pos['id'];

                        $singledata = $this->get_single($singleparams, false);
                        if ($singledata['valid'] == true) {
                            //    $response['content']['entries'][$num] = $singledata['content'];
                            $entries[] = $singledata['content'];
                        }

                    }
                }
                $response['content']['entries'] = $entries;
                $response['content']['public'] = count($entries);

            } elseif ((isset($response['content']['total']) && ((intval($response['content']['total']) == 0)))) {
                $aRet = [
                    'valid' => false,
                    'error' => 'No entry',
                    'code' => 404,
                    'params_given' => $params,
                    'content' => '',
                ];
                return $aRet;
            }

            $response['content'] = $this->sanitize_sourcedata($response['content']);
	    
            $response['content'] = $this->map_to_schema($response['content']);
        }
	
        return $response;

    }

    public function get_single($params, $parse = true) {
        $check = $this->required_parameter("get_single", $params);

        if (is_array($check) && count($check) > 0) {
            $aRet = [
                'valid' => false,
                'code' => 405,
                'error' => $check,
                'params_given' => $params,
                'content' => '',
            ];
            return $aRet;
        }

        $response = $this->get_data($params, "get_single");

        if ($response['valid'] === true) {
            if ((is_array($response['content'])) && (isset($response['content']['code'])) && ($response['content']['code'] >= 400)) {
                $aRet = [
                    'valid' => false,
                    'code' => $response['content']['code'],
                    'error' => $response['content']['messsage'],
                    'params_given' => $params,
                    'content' => '',
                ];
                return $aRet;
            } elseif ((is_array($response['content'])) && (!empty($response['content']))) {
                $response['content'] = $this->sanitize_sourcedata($response['content']);
                if ((!isset($response['content']['active'])) || ($response['content']['active'] == false)) {
                    $aRet = [
                        'valid' => false,
                        'code' => 401,
                        'error' => 'Entry not active',
                        'params_given' => $params,
                        'content' => '',
                    ];
                    return $aRet;
                }

                if ((isset($response['content']['channels'])) && (isset($response['channels']['channel0']) || isset($response['channels']['channel1']))) {
                    if (isset($response['channels']['channel1']) && strtotime($response['channels']['channel1']['from']) > strtotime($response['channels']['channel0']['from'])) {
                        $public = $this->is_public_by_dates($response['content']['channels']['channel1']['from'], $response['content']['channels']['channel1']['to']);
                    } else {
                        $public = $this->is_public_by_dates($response['content']['channels']['channel0']['from'], $response['content']['channels']['channel0']['to']);
                    }
                    if ($public == false) {
                        $aRet = [
                            'valid' => false,
                            'code' => 401,
                            'error' => 'Entry not active',
                            'params_given' => $params,
                            'content' => '',
                        ];
                        return $aRet;
                    }
                }
                if ((isset($response['content']['custom'])) && (isset($response['content']['custom']['status']))) {
                    $public = $this->is_public_by_custom_status($response['content']['custom']['status']);
                    if ($public == false) {
                        $aRet = [
                            'valid' => false,
                            'code' => 404,
                            'error' => 'Entry not public',
                            'params_given' => $params,
                            'content' => '',
                        ];
                        return $aRet;

                    }
                }
                
                if ($parse) {
                    $response['content'] = $this->map_to_schema($response['content']);
                }
            } else {
                $aRet = [
                    'valid' => false,
                    'code' => 404,
                    'error' => 'No entry',
                    'params_given' => $params,
                    'content' => '',
                ];
                return $aRet;
            }
        }

        return $response;
    }

    // Generate URI for request
    public function get_uri($params, $method = 'get_list')
    {
        $uri = $this->uriparameter;

        if (isset($params[$method])) {
            foreach ($params[$method] as $name => $value) {
                $type = 'string';
                if (isset($this->required_fields[$method][$name])) {
                    $type = $this->required_fields[$method][$name];
                }
                $urivalue = $this->sanitize_type($value, $type);
                $uriname = $this->sanitize_type($name, 'key');

                if ((!empty($uriname)) && (!empty($urivalue))) {
                    if (!empty($uri)) {
                        $uri .= '/';
                    }
                    if ($uriname == 'id') {
                        $uri .= $urivalue;
                    } else {
                        $uri .= $uriname . '/' . $urivalue;
                    }
                }
            }
        }
        return $uri;
    }

    // Methode prüft, ob für einen Request mit einer definiertem Methode alle
    // notwendigen Parameter vorhanden sind
    // Returns true if all required parameters are set.
    // Otherwise it returns an array with the missing parameters
    public function required_parameter($method = 'get_list', $params = array())
    {
        $found = array();

        if (!isset($params[$method])) {
            // No params for method
        } else {
            foreach ($params[$method] as $name => $value) {
                if (isset($this->required_fields[$method][$name])) {
                    $type = $this->required_fields[$method][$name];
                    $urivalue = $this->sanitize_type($value, $type);
                    if (!empty($urivalue)) {
                        $found[$name] = $urivalue;
                    }
                }
            }
        }
        // Now check, if all required fields are there
        $diff = array_diff_key($this->required_fields[$method], $found);

        if (count($diff) > 0) {
            return $diff;
        }

        if ((!isset($params['request-header']['headers']['BAPI-Token'])) || (empty($params['request-header']['headers']['BAPI-Token']))) {
            return $this->request_args;
        }

        return true;
    }

    // update and returns the request args
    private function get_request_args($params)
    {
        if ((isset($params)) && (isset($params['request-header']))) {
            // $params['request-header']['headers']['BAPI-Token']
            foreach ($params['request-header'] as $name => $value) {
                if (is_array($value)) {
                    foreach ($value as $subname => $subval) {
                        $this->request_args[$name][$subname] = $subval;
                    }
                } else {

                    $this->request_args[$name] = $value;
                }

            }
        }
        return $this->request_args;
    }

    // get the raw data from provider by a a method and parameters
    // https://api.b-ite.io/docs/#/jobpostings/get_jobpostings
    public function get_data($params, $method = 'get_list')  {
        $uri = $this->get_uri($params, $method);
        $url = $this->api_url;
        if ($uri) {
            $url .= '/' . $uri;
        }

        $cache = new Cache();

        $cachetime = $this->cachetime;
        if ($method == 'get_list') {
            $cachetime = $this->cachetime_list;
        } elseif ($method == 'get_single') {
            $cachetime = $this->cachetime_single;
        }

        $cache->set_cachetime($cachetime);

        $id = '';
        if ((isset($params[$method]['id'])) && (!isset($params[$method]['apikey']))) {
            $id = $params[$method]['id'];
        }
        if (empty($id)) {
            if (isset($params['request-header']['headers']['BAPI-Token'])) {
                $id = $params['request-header']['headers']['BAPI-Token'];
            }
        }

        $request_args = $this->get_request_args($params);
       
        if ($this->use_cache) {
            $cachedout = $cache->get_cached_job('BITE', $id, '', $method);
            if ($cachedout) {		
                return $cachedout;
	        }
	    }

        if ($method == 'get_list') {
            $filter = '{
		    "filter": {
			"standard": {  "active": true }
		    }
		  }';

            $post_args = $request_args;
            $post_args['body'] = $filter;
            $url .= '/search';

            $remote_get = wp_safe_remote_post($url, $post_args);
        } else {

            $remote_get = wp_safe_remote_get($url, $request_args);

        }

        if (is_wp_error($remote_get)) {
            $aRet = [
                'valid' => false,
                'content' => $remote_get->get_error_message(),
            ];
            return $aRet;
        } else {
            $content = json_decode($remote_get["body"], true);
            if (isset($content['code']) && ($content['code'] >= 400)) {
                $aRet = [
                    'valid' => false,
                    'error' => $content['message'],
                    'code' => $content['code'],
                    'params_given' => $params,
                    'content' => '',
                ];
                return $aRet;
            }

            if ((isset($content['channels'])) && (isset($content['channels']['channel0']) || isset($content['channels']['channel1']))) {
                if (isset($content['channels']['channel1']) && strtotime($content['channels']['channel1']['from']) > strtotime($content['channels']['channel0']['from'])) {
                    $public = $this->is_public_by_dates($content['channels']['channel1']['from'], $content['channels']['channel1']['to']);
                } else {
                    $public = $this->is_public_by_dates($content['channels']['channel0']['from'], $content['channels']['channel0']['to']);
                }
                if ($public == false) {
                    $aRet = [
                        'valid' => false,
                        'code' => 401,
                        'error' => 'Entry not active',
                        'params_given' => $params,
                        'content' => '',
                    ];
                    $cache->set_cached_job('BITE', $id, '', $method, $aRet);
                    // Notice: I will cache this too, cause even if the data is skipped, 
                    // we dont want to get it loaded every time if we get new data from the stream
                    return $aRet;

                }
            }
            if ((isset($content['custom'])) && (isset($content['custom']['status']))) {
                $public = $this->is_public_by_custom_status($content['custom']['status']);
                if ($public == false) {
                    $aRet = [
                        'valid' => false,
                        'code' => 404,
                        'error' => 'Entry not public',
                        'params_given' => $params,
                        'content' => '',
                    ];
                    $cache->set_cached_job('BITE', $id, '', $method, $aRet);
                    // Notice: I will cache this too, cause even if the data is skipped, 
                    // we dont want to get it loaded every time if we get new data from the stream
                    return $aRet;

                }
            }
           
            
            

            $aRet = [
                'request' => $url,
                'valid' => true,
                'content' => $content,
            ];

            $cache->set_cached_job('BITE', $id, '', $method, $aRet);
            return $aRet;
        }

    }
    
    
    /*
     Prüft ob es bei der BITE Installation einen custom value zur Freigabe 
     gibt und ob der Beitrag danach öffentlich sein darf. 
     Neu aus Anforderung P ab 20231207, WW
    
    
     Es wird das custom-feld status verwendet.
     Dieses hat folgende Werte:
     
        0 Entwurf
        1 Prüfung
        2 Korrektur
        3 Ausschreibung
        4 Ausschreibung abgelaufen
        5 Vorstellungsgespräche
        6 Auswahlentscheidung
        7 Auswahlbegründung ausstehend
        8 Archiviert
    */
    
    private function is_public_by_custom_status($statusval) {
        
        return true;
        
        // Nach Besprechung am 11.12.2023 werden erst noch 
        // Dinge geklärt, bevor wir das aktivieren.
        // Daher erstmal hier nur "true" als Antwort.
        // - WW
        
        if (isset($statusval)) {
            $statusval = intval($statusval);
            
            if ($statusval  == 3) {
                return true;
            }
            if (($statusval  >= 0) && ($statusval < 8)) {
                return false;
            }
            // falls mal Sonderwerte für <0 oder >8 eingeführt werden,
            // beschränke ich die Ablehnung auf diese.
        }
        // Wenn kein Wert gesetzt ist, dann filter den Beitrag auch nicht weg
        return true;
    }
    
    
    // prüft ob der Eintrag lt. Channel-Eintrag öffentlich anzeigbar ist
    private function is_public_by_dates($fromdate, $todate)  {
        if (strtotime($todate) > time()) {
            // we did not reach end date
            if (strtotime($fromdate) < time()) {
                # publication begin is in the past
                return true;
            }
        }
        return false;
    }

    // Some data source use own formats in text fields (like markdown or
    // univis text format) or source defined selectors or values
    // which has to be translatet in a general form (HTML).
    public function sanitize_sourcedata($data) {
        if (empty($data)) {
            return false;
        }

        if (isset($data['entries'])) {
            // wird bei der Indexabfrage geliefert
            foreach ($data['entries'] as $num => $job) {
                if (is_array($job)) {
                    foreach ($job as $name => $value) {
                        switch ($name) {

                            case 'id':
                                $value = $this->sanitize_bite_id($value);
                                break;

                        }

                        $data['entries'][$num][$name] = $value;
                    }

                }
            }
        } else {
            // Bei der Direkten Abfrage einer Stelle wird alles auf oberster Ebene geliefert
            foreach ($data as $key => $value) {
                if (!is_array($value)) {
                    switch ($key) {
                        case 'job_intern':
                        case 'befristung':
                        case 'active':
                            $value = $this->sanitize_bool($value);
                            break;
                        case 'id':
                            $value = $this->sanitize_bite_id($value);
                            break;
                        case 'anr':
                            $value = $this->sanitize_type($value, 'number');
                            break;
                        case 'angebot':
                        case 'aufgaben':
                        case 'einleitungstext':
                        case 'beschreibung_beschaeftigungsstelle':
                        case 'einleitung':
                        case 'stellenzusatz':
                        case 'profil':
                        case 'job_qualifications_nth':
                        case 'job_experience':
                        case 'wir_bieten':
                        case 'description':
                        case '06b_schluss':
                        case '06c_schluss':
                        case '06_schluss':
                        case 'abschlusstext':
                        case 'interessiert':
                        case 'sie_haben_fragen':
                        case '03_aufgaben_profil':
                        case '03_qualifications':
                        case '01_introduction':
                        case '05_questions':
                            $value = $this->sanitize_markdown_field($value);
                            $value = $this->deleteDefaults($value);
                            break;
                        case 'hiringorganization':
                            if ($data['hiringorganization'] == 'Technische Universität Nürnberg') {
                                $value = sanitize_text_field($data['hiringorganization']);
                            } else {
                                $value = $this->sanitize_custom_org($value);
                            }
                            break;

                        case 'workhours':
                            $value = $this->sanitize_bite_arbeitszeit($value);
                            break;

                        case 'job_limitation_reason':
                            $value = $this->sanitize_bite_limitation_reason($value);
                            break;

                        case 'zuordnung':
                            $data['orig_occupationalCategory'] = $value;
                            $value = $this->sanitize_occupationalCategory($value);
			    
                            break;
                        case 'job_limitation_duration':
                            $value = $this->sanitize_type($value, 'number');
                            break;
                        case '02_addition':
                        case 'jobstartdate':
                            $value = $this->sanitize_bite_jobstartdate($value);
                            break;

                        case 'employmenttype':
                            $value = $this->sanitize_bite_employmenttype($value);
                            break;
                        case 'beschaeftigungsumfang':
                            $value = $this->sanitize_bite_beschaeftigungsumfang($value);
                            break;

                        case 'identifier':
                        case 'identification':
                        case 'ausschreibungskennziffer':
                            $value = $this->sanitize_bite_kennziffer($value);
                            break;

                        case 'locale':
                        case 'jobposting_language':
                            $value = $this->sanitize_lang($value);
                            break;
                        case 'status':
                            $value = $this->sanitize_type($value, 'number');
                            break;
                            //     default:
                            //         $value = sanitize_text_field($value);
                    }
                } else {

                    switch ($key) {
                        case 'location':
                        case 'custom':
                            $value = $this->sanitize_sourcedata($value);
                            break;
                        case 'content':
                        case 'channels':
                        case 'seo':
                        case 'estimatedsalary':
                        case 'entgelt_ar':
                        case 'keywords':
                        case 'framework':
                        case 'bite_pa_data':

                            break;
                        default:
                            $value = $this->sanitize_sourcedata($value);

                    }
                }
                $data[$key] = $value;
            }
        }

        return $data;

    }

    private function deleteDefaults($value)
    {
        $aDelete = [
            'Hier steht dann die Beschreibung',
            'Hier steht dann die Einleitung',
            'Hier Infos über erforderliche Qualifikationen',
            'Hier Infos über wünschenswerte Qualifikationen',
            'Hier Infos über w&uuml;nschenswerte Qualifikationen',
            'Hier gibts ergänzende Bemerkungen',
            'Hier gibts erg&auml;nzende Bemerkungen',
            'Berufserfahrung ist wichtig....',
        ];

        $value = str_replace($aDelete, '', $value);

        // return '' if $value contains a paragraph only
        $check = str_replace('<p></p>', '', trim($value));
        if (empty($check)) {
            return $check;
        }

        return $value;
    }

    // sanitize start date
    // normally we would use a sanitizing for a date.
    // But due to wishes of customers, they want to insert also
    // text like "schnellstmöglich" or so...

    private function sanitize_bite_jobstartdate($date)
    {
        if (preg_match("/(\d{4}-\d{2}-\d{2})T([0-9:]+)/", $date, $parts)) {
            // ok, it looks like a normal date format. Therefor we use the default sanitized

            return $this->sanitize_dates($date);
        }
        if (preg_match_all('/(sofort|as soon|asap|bald|glich|a.s.a.p)/i', $date, $output_array)) {
            return "-1";
        }
        //ok, maybe its just text, so we sanitize it as text
        return sanitize_text_field($date);

    }

    // sanitize der Kennziffer
    private function sanitize_bite_kennziffer($key)
    {
        $key = preg_replace('/[^a-z0-9\-]+/i', '', $key);
        return $key;
    }

    // check for valid bite ids
    private function sanitize_bite_id($key)
    {
        $key = preg_replace('/[^A-Za-z0-9\-]+/i', '', $key);
        return $key;
    }

    // employment type aus custom.employmenttype , nicht aus seo
    private function sanitize_bite_employmenttype($value)
    {
        $options = array(
            'unlimited' => __('unlimited', 'rrze-jobs'),
            'temporary' => __('temporary', 'rrze-jobs'),
        );
        if (!empty($value)) {
            $value = trim(strtolower($value));

            if (isset($options[$value])) {
                return $options[$value];
            } else {
                // es kann sein, dass hier leider nicht die Keys übergeben wurden, sondern der lange String.
                $value = sanitize_text_field($value);
                return $value;
            }
        }
        return '';

    }

    // employment type aus custom.beschaeftigungsumfang , nicht aus seo
    private function sanitize_bite_beschaeftigungsumfang($value)
    {
        $options = array(
            '01_vollzeit' => __('Full time', 'rrze-jobs'),
            '02_teilzeit' => __('Part time', 'rrze-jobs'),
            '03_voll_teilzeit' => __('Full or part time', 'rrze-jobs'),
        );
        if (!empty($value)) {
            $value = trim(strtolower($value));

            if (isset($options[$value])) {
                return $options[$value];
            } else {
                // es kann sein, dass hier leider nicht die Keys übergeben wurden, sondern der lange String.
                $value = sanitize_text_field($value);
                return $value;
            }
        }
        return '';

    }

    // ubersetze die Auswahlliste für die optionale Begründung befrister Stellen
    private function sanitize_bite_limitation_reason($value)
    {
        $options = array(
            'vertr' => __('Replacement', 'rrze-jobs'), // 'Vertretung',
            'schwanger' => __('Maternity leave replacement', 'rrze-jobs'), // 'Mutterschutzvertretung',
            'eltern' => __('Maternity/parental leave replacement', 'rrze-jobs'), // 'Mutterschutz- / Elternzeitvertretung',
            'forsch' => __('Limited research project', 'rrze-jobs'), // 'befristetes Forschungsvorhaben',
            'projekt' => __('Project', 'rrze-jobs'), // Projekt
            'krankh' => __('Sickness replacement', 'rrze-jobs'), // 'befristetes Krankheitsvertretung',
            'zeitb' => __('Temporary officials', 'rrze-jobs'), // 'Beamtenschaft auf Zeit'

        );

        if (!empty($value)) {
            $value = trim(strtolower($value));

            if (isset($options[$value])) {
                return $options[$value];
            } else {
                // es kann sein, dass hier leider nicht die Keys übergeben wurden, sondern der lange String.
                $value = sanitize_text_field($value);
                return $value;
            }
        }
        return '';
    }

    // ubersetze die Auswahlliste für die ARbeitszeiten
    private function sanitize_bite_arbeitszeit($value)
    {
        $options = array(
            'nach_vereinbarung' => __('By arrangement', 'rrze-jobs'),
            'vormittags' => __('Morning times', 'rrze-jobs'),
            'nachmittags' => __('Afternoon times', 'rrze-jobs'),
        );
        if (!empty($value)) {
            $keyvalue = trim(strtolower($value));
            if (isset($options[$keyvalue])) {
                return $options[$keyvalue];
            }

            // ok, maybe another manual input text, so we just sanitize it as text
            $value = sanitize_text_field($value);
            return $value;
        }
        return '';

    }

   

}
