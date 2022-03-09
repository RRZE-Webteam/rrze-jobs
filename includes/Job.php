<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;

class Job
{

    /* * * * * * * * * * * * * * *
     *
     * CONFIG STARTS HERE
     *
     * * * * * * * * * * * * * * */

    /**
     * Gibt die API-URL zurück.
     * @return array
     */
    public function getURL(&$provider, $urltype)
    {
        $ret = [
            'bite' => [
                'list' => 'https://api.b-ite.io/v1/jobpostings', // provides list of IDs
                'single' => 'https://api.b-ite.io/v1/jobpostings/', // add jobID
            ],
            'interamt' => [
                'list' => 'https://www.interamt.de/koop/app/webservice_v2?partner=',
                'single' => 'https://www.interamt.de/koop/app/webservice_v2?id=',
            ],
            'univis' => [
                'list' => 'https://univis.uni-erlangen.de/prg?search=positions&show=json&closed=1&department=',
                'single' => 'https://univis.uni-erlangen.de/prg?search=positions&closed=1&show=json&id=',
            ],
        ];

        return $ret[$provider][$urltype];
    }

    /**
     * liefert das Mapping zur aufgerufenen API anhand provider
     * @return array
     */

    public function getMap($provider, $bStructure = false)
    {
        $map = [
            'job_id' => [
                'bite' => 'id',
                'interamt' => 'Id',
                'univis' => 'id',
                'label' => 'Job ID',
            ],
            'application_start' => [
                'bite' => ['channels', 'channel0', 'from'],
                'interamt' => 'DatumOeffentlichAusschreiben',
                'univis' => '', // fehlt
                'label' => 'Bewerbungsstart',
                'desc' =>  'nicht bei UnivIS',
            ],
            'application_end' => [
                'bite' => ['channels', 'channel0', 'to'],
                'interamt' => 'DatumBewerbungsfrist',
                'univis' => 'enddate',
                'label' => 'Bewerbungsschluss',
            ],
            'application_link' => [
                'bite' => ['channels', 'channel0', 'route', 'application'],
                'interamt' => 'BewerbungUrl',
                'univis' => 'desc6',
                'label' => 'Link zur Bewerbung',
            ],
            'job_intern' => [
                'bite' => '', // fehlt, es gibt bei BITE nur öffentlich zugängliche Stellen
                'interamt' => '', // fehlt
                'univis' => 'intern',
                'label' => 'Intern',
                'desc' =>  'nur bei UnivIS',
            ],
            'job_type' => [
                'bite' => ['custom', 'ausschreibungskennziffer'],
                'interamt' => 'Kennung',
                'univis' => '', // fehlt
                'label' => 'Kennung',
                'desc' =>  'nicht bei UnivIS',
            ],
            'job_title' => [
                'bite' => 'title',
                'interamt' => 'Stellenbezeichnung',
                'univis' => 'title',
                'label' => 'Stellenbezeichnung',
            ],
            'job_start' => [
                'bite' => ['custom', 'jobstartdate'], // wenn es nicht exisitert: "nächstmöglichen Zeitpunkt"
                'interamt' => 'DatumBesetzungZum',
                'univis' => 'start',
                'label' => 'Besetzung zum',
                'desc' =>  'leer = "zum nächstmöglichen Zeitpunkt"',
            ],
            'job_limitation' => [
                'bite' => ['seo', 'employmentType', 0], // (full_time OR part_time) AND (temporary OR '') => if string contains "temporary" => befristet, else unbefristet
                'interamt' => 'BeschaeftigungDauer',
                'univis' => 'type1',
                'label' => 'Befristung',
            ],
            'job_limitation_duration' => [ // Befristung Dauer
                'bite' => ['custom', 'job_limitation_duration'],
                'interamt' => 'BefristetFuer', // Anzahl Monate !!!
                'univis' => 'befristet',
                'label' => 'Dauer der Befristung',
                'desc' =>  'in Monaten',
            ],
            'job_limitation_reason' => [
                'bite' => ['custom', 'job_limitation_reason'],
                'interamt' => '',
                'univis' => 'type3',
                'label' => 'Grund der Befristung',
            ],
            'job_salary_type' => [
                'bite' => ['custom', 'entgelt_art'],
                'interamt' => '', // existiert nicht, da inkludiert in job_salary_from
                'univis' => '', // existiert nicht, da inkludiert in job_salary_from
                'label' => 'Entgelt Gruppe (nur bei BITE)',
                'desc' =>  'nur bei BITE',
            ],
            'job_salary_from' => [
                'bite' => ['custom', 'estimatedsalary'],
                'interamt' => 'TarifEbeneVon',
                'univis' => 'vonbesold',
                'label' => 'Tarifebene von',
            ],
            'job_salary_to' => [
                'bite' => '',
                'interamt' => 'TarifEbeneBis',
                'univis' => 'bisbesold',
                'label' => 'Tarifebene bis',
                'desc' =>  'nicht bei BITE',
            ],
            'job_qualifications' => [
                'bite' => ['custom', 'profil'],
                'interamt' => 'Qualifikation',
                'univis' => 'desc2',
                'label' => 'Qualifikationen',
            ],
            'job_qualifications_nth' => [
                'bite' => '',
                'interamt' => '', // fehlt
                'univis' => 'desc3',
                'label' => 'Wünschenswerte Qualifikationen',
                'desc' =>  'nur bei UnivIS',
            ],
            'job_employmenttype' => [
                'bite' => ['seo', 'employmentType', 0], // (full_time OR part_time) AND (temporary OR '') => substring
                'interamt' => 'Teilzeit',
                'univis' => 'type2',
                'label' => 'Vollzeit / Teilzeit',
                'desc' =>  'erlaubte Werte: "full_time" oder "part_time" und gegebenenfalls mit Leerzeichen getrennt: "temporary"',
            ],
            'job_workhours' => [
                'bite' => '', // fehlt
                'interamt' => 'WochenarbeitszeitArbeitnehmer',
                'univis' => 'wstunden',
                'label' => 'Wochenarbeitszeit',
                'desc' =>  'nicht bei BITE',
            ],
            'job_category' => [
                'bite' => ['custom', 'zuordnung'], // "wiss", "n-wiss", "hiwi", "azubi", "prof" or "other"
                'interamt' => 'Fachrichtung', // bis 2022-01-20: FachrichtungCluster
                'univis' => 'group',
                'label' => 'Berufsgruppe',
                'desc' =>  'erlaubte Werte: "wiss", "n-wiss", "hiwi", "azubi", "prof" oder "other"',
            ],
            'job_description' => [
                'bite' => ['custom', 'aufgaben'],
                'interamt' => 'Beschreibung',
                'univis' => 'desc1',
                'label' => 'Beschreibung',
            ],
            'job_description_introduction' => [
                'bite' => ['custom', 'einleitung'],
                'interamt' => '', // fehlt
                'univis' => 'desc5',
                'label' => 'Beschreibung - Einleitung',
                'desc' =>  'nicht bei Interamt',
            ],
            'job_description_introduction_added' => [
                'bite' => ['custom', 'stellenzusatz'],
                'interamt' => '', // fehlt
                'univis' => '', // fehlt
                'label' => 'Stellenzusatz',
                'desc' =>  'nur bei BITE',
            ],
            'job_experience' => [
                'bite' => ['custom', 'profil'],
                'interamt' => '', // fehlt
                'univis' => 'desc2',
                'label' => 'Berufserfahrung',
                'desc' =>  'nicht bei Interamt',
            ],
            'job_benefits' => [
                'bite' => ['custom', 'wir_bieten'],
                'interamt' => '', // fehlt
                'univis' => 'desc4',
                'label' => 'Benefits',
                'desc' =>  'nicht bei Interamt',
            ],
            'employer_organization' => [
                'bite' => ['custom', 'hiringorganization'], // tu_nuernberg => 'Technische Universität Nürnberg'
                'interamt' => 'StellenangebotBehoerde',
                'univis' => 'orgname',
                'label' => 'Organisationseinheit',
            ],
            'employer_street' => [
                'bite' => ['location', 'street'],
                'interamt' => ['Einsatzort', 'EinsatzortStrasse'],
                'univis' => ['Person', 'locations', 'location', 'street'],
                'label' => 'Straße',
            ],
            'employer_street_nr' => [
                'bite' => ['location', 'houseNumber'],
                'interamt' => '', // fehlt
                'univis' => '', // fehlt
                'label' => 'Hausnummer', // fehlt
                'desc' =>  'nur bei BITE',
            ],
            'employer_postalcode' => [
                'bite' => ['location', 'postCode'],
                'interamt' => ['Einsatzort', 'EinsatzortPLZ'],
                'univis' => '', // fehlt
                'label' => 'PLZ',
                'desc' =>  'nicht bei UnivIS',
            ],
            'employer_city' => [
                'bite' => ['location', 'city'],
                'interamt' => ['Einsatzort', 'EinsatzortOrt'],
                'univis' => ['Person', 'locations', 'location', 'ort'],
                'label' => 'Ort',
            ],
            'employer_district' => [
                'bite' => '', // fehlt
                'interamt' => 'BeschaeftigungBereichBundesland',
                'univis' => '', // fehlt
                'label' => 'Bezirk',
                'desc' => 'nur bei Interamt',
            ],
            'contact_link' => [
                'bite' => '', // fehlt
                'interamt' => 'HomepageBehoerde',
                'univis' => '', // see fillPersons()
                'label' => 'Ansprechpartner Link',
                'desc' => 'nicht bei BITE',
            ],
            'contact_title' => [
                'bite' => '', // existiert nicht, aber contact_name
                'interamt' => ['ExtAnsprechpartner', 'ExtAnsprechpartnerAnrede'],
                'univis' => '', // see fillPersons()
                'label' => 'Ansprechpartner Titel',
                'desc' => 'nicht bei BITE',
            ],
            'contact_firstname' => [
                'bite' => '', // existiert nicht, aber contact_name
                'interamt' => ['ExtAnsprechpartner', 'ExtAnsprechpartnerVorname'],
                'univis' => '', // see fillPersons()
                'label' => 'Ansprechpartner Vorname',
                'desc' => 'nicht bei BITE',
            ],
            'contact_lastname' => [
                'bite' => '', // existiert nicht, aber contact_name
                'interamt' => ['ExtAnsprechpartner', 'ExtAnsprechpartnerNachname'],
                'univis' => '', // see fillPersons()
                'label' => 'Ansprechpartner Nachname',
                'desc' => 'nicht bei BITE',
            ],
            'contact_name' => [
                'bite' => ['custom', 'contact_name'],
                'interamt' => '', // exisitert nicht, aber aufgeschlüsselt in contact_title, contact_fistname, contact_lastname
                'univis' => '', // see fillPersons()
                'label' => 'Ansprechpartner Name',
                'desc' => 'nicht bei Interamt',
            ],
            'contact_tel' => [
                'bite' => ['custom', 'contact_tel'],
                'interamt' => ['ExtAnsprechpartner', 'ExtAnsprechpartnerTelefon'],
                'univis' => '', // see fillPersons()
                'label' => 'Ansprechpartner Telefonnummer',                
            ],
            'contact_mobile' => [
                'bite' => ['custom', 'contact_mobile'],
                'interamt' => ['ExtAnsprechpartner', 'ExtAnsprechpartnerMobil'],
                'univis' => '', // fehlt
                'label' => 'Ansprechpartner Mobilnummer',
                'desc' => 'nicht bei UnivIS',
            ],
            'contact_email' => [
                'bite' => ['custom', 'contact_email'],
                'interamt' => ['ExtAnsprechpartner', 'ExtAnsprechpartnerEMail'],
                'univis' => '', // see fillPersons()
                'label' => 'Ansprechpartner E-Mail',
            ],
            'contact_street' => [
                'bite' => '', // exisitert nicht, aber contact_address
                'interamt' => ['Einsatzort', 'EinsatzortStrasse'],
                'univis' => '', // see fillPersons()
                'label' => 'Ansprechpartner Straße mit Hausnummer',
                'desc' => 'nicht bei BITE',
            ],
            'contact_postalcode' => [
                'bite' => '', // exisitert nicht, aber contact_address
                'interamt' => ['Einsatzort', 'EinsatzortPLZ'],
                'univis' => '', // see fillPersons()
                'label' => 'Ansprechpartner PLZ',
                'desc' => 'nicht bei BITE',
            ],
            'contact_city' => [
                'bite' => '', // exisitert nicht, aber contact_address
                'interamt' => ['Einsatzort', 'EinsatzortOrt'],
                'univis' => '', // see fillPersons()
                'label' => 'Ansprechpartner Ort',
                'desc' => 'nicht bei BITE',
            ],
            'contact_address' => [
                'bite' => ['custom', 'contact_address'],
                'interamt' => '', // exisitert nicht, aber aufgeschlüsset in contact_street, contact_postalcode, contact_city
                'univis' => '', // see fillPersons()
                'label' => 'Ansprechpartner Adresse',
                'desc' => 'nur bei BITE',
            ],
        ];

        if ($bStructure){
            return $map;
        }else{
            $provider_map = [];

            foreach ($map as $field => $aVal) {
                if (!empty($aVal[$provider])){
                    $provider_map[$field] = $aVal[$provider];
                }
            }
            return $provider_map;
        }
    }

    /* * * * * * * * * * * * * * *
     *
     * CONFIG ENDS HERE
     *
     * * * * * * * * * * * * * * */

    public function getUnivisPersons($personsData)
    {
        $persons = array();
        foreach ($personsData as $person) {
            $postalTmp = '';
            $key = $person['key'];
            if (isset($person['title'])) {
                $persons[$key]['contact_title'] = $person['title'];
            } elseif (isset($person['atitle'])) {
                $persons[$key]['contact_title'] = $person['atitle'];
            }
            if (isset($person['firstname'])) {
                $persons[$key]['contact_firstname'] = $person['firstname'];
            }
            if (isset($person['lastname'])) {
                $persons[$key]['contact_lastname'] = $person['lastname'];
            }
            if (isset($person['location'][0]['tel'])) {
                $persons[$key]['contact_tel'] = $person['location'][0]['tel'];
            }
            if (isset($person['location'][0]['email'])) {
                $persons[$key]['contact_email'] = $person['location'][0]['email'];
            }
            if (isset($person['location'][0]['street'])) {
                $persons[$key]['contact_street'] = $person['location'][0]['street'];
            }
            if (isset($person['location'][0]['url'])) {
                $persons[$key]['contact_link'] = $person['location'][0]['url'];
            }
            if (isset($person['location'][0]['ort'])) {
                $postalTmp = $person['location'][0]['ort'];
            }
            if ($postalTmp != '') {
                $postalTmp = preg_replace('/\s+/', ' ', $postalTmp);
                $parts = explode(' ', $postalTmp);
                if (sizeof($parts) == 2) {
                    $persons[$key]['contact_postalcode'] = $parts[0];
                    $persons[$key]['contact_city'] = $parts[1];
                } else {
                    $persons[$key]['contact_city'] = $postalTmp;
                }
            }
        }
        return $persons;
    }

    /**
     * Füllt die Map mit Werten mit Defaultwerten und denen aus der Schnittstelle
     * @return array
     */
    public function fillMap(&$map, &$job, &$options = NULL)
    {
        $map_ret = array();

        foreach ($map as $k => $val) {
            // did user store default?
            // if (!empty($options[]))
            if (is_array($val)) {
                switch (count($val)) {
                    case 2:
                        if (isset($job[$val[0]][$val[1]])) {
                            if (is_string($job[$val[0]][$val[1]])) {
                                // check if_string() is only needed to supress PHP Notices while BITE API development is in progress (['custom', 'stellenzusatz'] ought to return string, but during development it might return an array as well)
                                $map_ret[$k] = htmlentities($job[$val[0]][$val[1]]);
                            }
                        }
                        break;
                    case 3:
                        if (isset($job[$val[0]][$val[1]][$val[2]])) {
                            if (is_array($job[$val[0]][$val[1]][$val[2]])) {
                                $map_ret[$k] = htmlentities(implode(PHP_EOL, $job[$val[0]][$val[1]][$val[2]]));
                            } else {
                                $map_ret[$k] = htmlentities($job[$val[0]][$val[1]][$val[2]]);
                            }
                        }
                        break;
                    case 4:
                        if (isset($job[$val[0]][$val[1]][$val[2]][$val[3]])) {
                            $map_ret[$k] = htmlentities($job[$val[0]][$val[1]][$val[2]][$val[3]]);
                        }
                        break;
                }
            } elseif (isset($job[$val])) {
                $map_ret[$k] = $job[$val];
            }
        }

        return $map_ret;
    }

    private static function isIPinRange($fromIP, $toIP, $myIP)
    {
        $min = ip2long($fromIP);
        $max = ip2long($toIP);
        $needle = ip2long($myIP);

        return (($needle >= $min) and ($needle <= $max));
    }

    /**
     * Prüft, ob interne Jobs synchronisiert bzw angezeigt werden dürfen
     * IP-Range der Public Displays (dürfen interne Jobs nicht anzeigen): 10.26.24.0/24 und 10.26.25.0/24
     * @return boolean
     */
    public function isInternAllowed()
    {
        $allowedHost = 'uni-erlangen.de';
        $remoteIP = $_SERVER['REMOTE_ADDR'];
        $remoteAdr = gethostbyaddr($remoteIP);

        if (self::isIPinRange('10.26.24.0', '10.26.24.24', $remoteIP) || self::isIPinRange('10.26.25.0', '10.26.25.24', $remoteIP)) {
            return false;
        }

        if (is_user_logged_in() || (strpos($remoteAdr, $allowedHost) !== false)) {
            return true;
        }

        return false;
    }


    
    private function skipJob(&$job, $intern_allowed){
        // Skip if expired
        if ($job['application_end'] < date('Y-m-d')) {
            return true;
        }
        
        // Skip internal job offers if necessary
        $isInternJob = (!empty($map['job_intern']) && $map['job_intern'] == 'ja' ? 1 : 0);
        switch ($intern_allowed) {
            case 'only':
            case 'include':
                if ($isInternJob) {
                    return false;
                }
                break;
            case 'exclude':
                if ($isInternJob) {
                    return true;
                }
                break;
        }

        return false;
    }

    public function cleanData(&$provider, &$job, $intern_allowed){
        // Skip job?
        if ($this->skipJob($job, $intern_allowed)){
            return false;
        }

        // Convert dates
        $aFields = ['job_start', 'application_start', 'application_end'];
        foreach($aFields as $field){
            if (!empty($job[$field])){
                $job[$field] = date('Y-m-d', strtotime($job[$field]));
            }
        }
        if (!empty($job['application_end'])){
            $job['application_end'] = date('d.m.Y', strtotime($job['application_end']));
        }

        // Set 'job_start_sort'
        if (!empty($job['job_start'])) {
            // field might contain a date - if it contains a date, it must be in english format
            if (preg_match("/\d{4}-\d{2}-\d{2}/", $job['job_start'], $parts)) {
                $job['job_start_sort'] = $parts[0];
            } elseif (preg_match("/(\d{2}).(\d{2}).(\d{4})/", $job['job_start'], $parts)) {
                $job['job_start_sort'] = $parts[3] . '-' . $parts[2] . '-' . $parts[1];
            } else {
                // field contains only a string - check if it is ASAP
                $val = strtolower($job['job_start']);
                if (strpos($val, 'sofort') !== false || strpos($val, 'bald') !== false || strpos($val, 'glich') !== false || strpos($val, 'asap') !== false || strpos($val, 'a.s.a.p.') !== false) {
                    // sofort, ab sofort, baldmöglich, baldmöglichst, zum nächstmöglichen Zeitpunkt, nächstmöglich, frühst möglich, frühestmöglich, asap, a.s.a.p.
                    $job['job_start_sort'] = '0';
                } else {
                    $job['job_start_sort'] = $job[$field];
                }
            }
            // Convert date 'job_start'
            $job['job_start'] = date('d.m.Y', strtotime($job['job_start']));
        }

        // Set 'job_employmenttype'
        if ($provider == 'univis'){
            if (!empty($job['job_employmenttype'])) {
                $job['job_employmenttype'] = ucfirst($job['job_employmenttype']) . 'zeit';
            }
        }

        // Set 'job_category_grouped'
        if ($provider == 'interamt'){
            if (!empty($job['job_category'])) {
                if ($job['job_category'] == 'Bildung und Wissenschaft') {
                    $job['job_category_grouped'] = 'wiss';
                } else {
                    $job['job_category_grouped'] = 'n-wiss';
                }
            }
            if (strpos($job['job_title'], 'Auszubildende') !== false) {
                $job['job_category_grouped'] = 'azubi';
            } elseif (strpos($job['job_title'], 'Wissenschaftliche Hilfs') !== false) {
                $job['job_category_grouped'] = 'hiwi';
            }
        }

        // Set 'application_link'
        if ($provider == 'interamt' && !empty($job['job_description'])){
            $start_application_string = strpos($job['job_description'], 'Bitte bewerben Sie sich');
            if ($start_application_string === false) {
                $start_application_string = strpos($job['job_description'], 'Senden Sie Ihre Bewerbung');
            }
            if ($start_application_string !== false && $start_application_string > 100) {
                $application_string = substr($job['job_description'], $start_application_string);
                $job['application_link'] = strip_tags(html_entity_decode($application_string), '<a><br><br /><b><strong><i><em>');
            }
        }

        // Set 'application_email'
        if (!empty($job['application_link'])) {
            preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+[a-zA-Z]+/i", $job['application_link'], $matches);
            if (!empty($matches[0])) {
                $application_email = $matches[0][0];
                $job['application_email'] = $application_email;
            }
        }

        // Set 'employer_organization'
        if (!empty($job['employer_organization'])) {
            $job['employer_organization'] = nl2br(str_replace('Zentrale wissenschaftliche Einrichtungen der FAU' . PHP_EOL, '', $job['employer_organization']));
        }

        // Set 'job_salary_search'
        if (!empty($job['job_salary_from'])) {
            $job['job_salary_search'] = (int) filter_var($job['job_salary_from'], FILTER_SANITIZE_NUMBER_INT);
        } elseif (isset($job['job_salary_to'])) {
            $job['job_salary_search'] = (int) filter_var($job['job_salary_to'], FILTER_SANITIZE_NUMBER_INT);
        }
        if (empty($job['job_salary_search'])) {
            $job['job_salary_search'] = 99;
        }
        $job['job_salary_search'] = abs($job['job_salary_search']);

        // Set 'job_limitation_boolean'
        if (!empty($job['job_limitation']) || !empty($job['job_limitation_duration'])) {
            $job['job_limitation_boolean'] = 1;
        } else {
            $job['job_limitation_boolean'] = 0;
        }

        // format fields
        $aFields = [
            'job_description',
            'job_qualifications',
            'job_qualifications_nth',
            'job_benefits',
            'application_link',
        ];

        foreach($aFields as $field){
            if (!empty($job[$field])){
                $job[$field] = $this->formatUnivIS($job[$field]);
            }
        }
    }

    private function formatUnivIS($txt)
    {
        $subs = array(
            '/^\-+\s+(.*)?/mi' => '<ul><li>$1</li></ul>', // list
            '/(<\/ul>\n(.*)<ul>*)+/' => '', // list
            '/\*{2}/m' => '/\*/', // **
            '/_{2}/m' => '/_/', // __
            '/\|(.*)\|/m' => '<i>$1</i>', // |itallic|
            '/_(.*)_/m' => '<sub>$1</sub>', // H_2_O
            '/\^(.*)\^/m' => '<sup>$1</sup>', // pi^2^
            '/\[([^\]]*)\]\s{0,1}((http|https|ftp|ftps):\/\/\S*)/mi' => '<a href="$2">$1</a>', // [link text] http...
            '/\[([^\]]*)\]\s{0,1}(mailto:)([^")\s<>]+)/mi' => '<a href="mailto:$3">$1</a>', // find [link text] mailto:email@address.tld but not <a href="mailto:email@address.tld">mailto:email@address.tld</a>
            '/\*(.*)\*/m' => '<strong>$1</strong>', // *bold*
        );

        $txt = preg_replace(array_keys($subs), array_values($subs), $txt);
        $txt = nl2br($txt);
        $txt = make_clickable($txt);
        return $txt;
    }

}
