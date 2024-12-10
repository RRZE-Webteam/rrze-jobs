<?php

/**
 * Positions
 *
 * Created on : 03.08.2022, 13:58:52
 */

namespace RRZE\Jobs;

defined('ABSPATH') || exit;

use RRZE\Jobs\OrgData;
use RRZE\Jobs\Parsedown;

class Provider {
    public $name;
    public $version;
    public $url;

    public $systems = ["UnivIS", "Interamt", "BITE"];
    public $common_methods = ["get_group", "get_list", "get_single"];

    public $use_cache;
    public $positions = [];
    public $lastcheck = '';
    public $params = [];
    
    public function __construct($use_cache = true) {
        $this->use_cache = $use_cache;
        $this->positions = array();
        $this->lastcheck = '';
        $this->params = array();
    }

    public function is_valid_provider($provider) {
        if ((!isset($provider)) || (empty($provider))) {
            return false;
        }
        $found = false;
        foreach ($this->systems as $system_name) {
            if ((strtolower(trim($provider))) == strtolower(trim($system_name))) {
                $found = $system_name;
                break;
            }
        }
        return $found;
    }

    // set parameters to a defined provider
    public function set_provider_params($provider, $params)
    {
        $provider = $this->is_valid_provider($provider);
        if ($provider === false) {
            return false;
        }

        if (!empty($provider)) {
            if (is_array($params)) {
                $this->params[$provider] = $params;
            }
        }

        return;
    }

    public function set_params($params)
    {
        if (is_array($params)) {
            $this->params = $params;
        }
        return;
    }

    // merge all positions from different provider, if there are more as one
    public function merge_positions()
    {
        if ((!isset($this->positions)) || (empty($this->positions))) {
            return false;
        }

        $res = array();
        $positionlist = array();
        $truecounter = 0;
        $poscounter = 0;

        $res['valid'] = false;

        foreach ($this->positions as $providername => $provider) {
            if (isset($provider['request'])) {
                $res['request'][$providername]['request'] = $provider['request'];
            }

            $res['status'][$providername]['valid'] = $provider['valid'];
            if ($provider['valid'] === true) {
                $res['valid'] = true;
                $truecounter++;
                if ((is_array($provider['content'])) && (isset($provider['content']['JobPosting']))) {
                    foreach ($provider['content']['JobPosting'] as $num => $posdata) {
                        $res['positions'][] = $posdata;
                        $poscounter++;
                    }
                } else {
                    //        $res['unused-data'][$providername] = $provider['content'];
                }
            } else {
                if (isset($provider['error'])) {
                    $res['status'][$providername]['error'] = $provider['error'];
                }
                if (isset($provider['code'])) {
                    $res['status'][$providername]['code'] = $provider['code'];
                }
                if (isset($provider['params_given'])) {
                    $res['status'][$providername]['params_given'] = $provider['params_given'];
                }
                if (isset($provider['content'])) {
                    $res['status'][$providername]['content'] = $provider['content'];
                }

            }
        }


        // the following lines don't work, as count($this->positions) counts number of providers and not number of positions
        // if ($truecounter == count($this->positions)) {
        //     $res['valid'] = true;
        // } else {
        //     $res['valid'] = false;
        // }

        if ((count($this->positions) > 1) && ($poscounter > 0)) {
            // check for duplicate posts

            // a job is duplicate if all the folowing fields together are
            // identical in content:
            $matching_fields = array(
                'title',
                'validThrough',
                'employmentUnit.name',
                'jobStartDate',
                'applicationContact.url',
                'applicationContact.email',
                'baseSalary.value.value'
            );

            // todo later:
            // make a checksum of the content of description (after remove all
            // html and other markup and look if this matches too

            $dup = array();
            foreach ($res['positions'] as $num => $pos) {
                foreach ($res['positions'] as $num2 => $pos2) {
                    if ($num == $num2) {
                        continue;
                    }
                    if ($num > $num2) {
                        // we dont need to look backwards, cause we did them in
                        // a previous round already
                        continue;
                    }

                    $matchnum = 0;
                    foreach ($matching_fields as $field) {
                        if (strpos($field, ".") !== false) {
                            $fieldnames = explode(".", $field);

                            if (isset($fieldnames[3]) && isset($pos[$fieldnames[0]][$fieldnames[1]][$fieldnames[3]]) && isset($pos2[$fieldnames[0]][$fieldnames[1]][$fieldnames[3]])) {
                                if ($pos[$fieldnames[0]][$fieldnames[1]][$fieldnames[3]] == $pos2[$fieldnames[0]][$fieldnames[1]][$fieldnames[3]]) {
                                    $matchnum++;
                                }
                            } elseif (isset($fieldnames[1]) && isset($pos[$fieldnames[0]][$fieldnames[1]]) && isset($pos2[$fieldnames[0]][$fieldnames[1]])) {
                                if ($pos[$fieldnames[0]][$fieldnames[1]] == $pos2[$fieldnames[0]][$fieldnames[1]]) {
                                    $matchnum++;
                                }
                            }

                        } elseif (isset($pos[$field]) && $pos[$field] == $pos2[$field]) {
                            $matchnum++;

                        }
                    }
                    if ($matchnum == count($matching_fields)) {
                        //ok, we found a duplicate entry.
                        // mark it in a dup-list
                        
                        $dup[$num2] = true;
                    }
                }
            }
            if (count($dup) > 0) {
                // there is one or more duplicate entries
                $newposlist = array();
                foreach ($res['positions'] as $num => $pos) {
                    if ((!isset($dup[$num])) || ($dup[$num] !== true)) {
                        $newposlist[] = $pos;
                    }
                }
                $res['positions'] = $newposlist;
            }
        }

        return $res;
    }

    public function get_positions($provider = '', $query = 'get_list')
    {
        // Ask all providers

        foreach ($this->systems as $system_name) {
            $system_class = 'RRZE\\Jobs\\Provider\\' . $system_name;
            $system = new $system_class();

            if (!empty($provider)) {
                if ($system->name !== $provider) {
                    continue;
                }
            }
            if (method_exists($system, $query)) {
                $params = array();

                if (isset($this->params[$system->name])) {
                    $params = $this->params[$system->name];
                }
                $positions = $system->$query($params);

                if ($positions) {
                    $this->positions[$system->name] = $positions;
                    $this->lastcheck = time();
                }
            }

            return $this->positions;
        }

        return false;

    }

    // Sanitize Requests values
    public function sanitize_type($value, $type = 'string')
    {
        switch ($type) {
            case 'uriparam':
                $value = sanitize_text_field($value);
                $value = preg_replace('/[^0-9a-z\-_,\.]+/i', '', $value);
                break;

            case 'string':
                $value = sanitize_text_field($value);
                break;
            case 'key':
                $value = sanitize_key($value);
                break;
            case 'url':
                $value = sanitize_url($value);
                break;
            case 'number':
            case 'int':
                $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                break;
            case 'float':
                $value = preg_replace('/[^0-9,\.]+/i', '', $value);
                break;
            default:
                $value = sanitize_text_field($value);

        }
        return $value;
    }

    // sanitize boolean thingis
    public function sanitize_bool($value)
    {

        if (is_bool($value) === true) {
            return $value;
        }

        if ((!isset($value)) || (empty($value))) {
            return false;
        }

        $val = strtolower($value);

        if (
            strpos($val, 'ja') !== false
            || strpos($val, 'yes') !== false
            || strpos($val, '1') !== false
        ) {
            return true;

        }
        return false;
    }

    // sanitize org numbers to their name, if know.
    // TODO: this needs a translation table, thats dependend on the customer
    public function sanitize_custom_org($value)
    {
        $value = sanitize_text_field($value);

        $orgdata = new OrgData();

        $orginfo = $orgdata->get_org($value);
        if ($orginfo !== false) {
            return $orginfo; // $orginfo['title'];
        }

        return $value;
    }

    // sanitize markdown text input
    public function sanitize_markdown_field($dateinput)
    {
        // first translate input into markdown, then sanitize with html sanitizer
        $Parsedown = new Parsedown();

        // ToDo: Zweites Pattern + Replacement kann entfernt werden, wenn B-ITE den Export-Bug mit den doppelt escapten Doppelpunkten behoben hat.
        $dateinput = preg_replace(['/\\\s*$/m', '/(\\\:)/m'], [' ', ':'], $dateinput);
        $dateinput = nl2br($dateinput);

        $html = $Parsedown->text($dateinput);
        return $this->sanitize_html_field($html);

    }

    // check for the language tag
    public function sanitize_lang($lang)
    {
        $res = 'de';
        if (empty($lang)) {
            return $res;
        }
        $lang = strtolower($lang);

        switch ($lang) {
            case 'de':
            case 'en':
            case 'es':
            case 'fr':
            case 'zh':
            case 'ru':
                $res = $lang;
                break;
            case 'eng':
                $res = 'en';
                break;
            default:
                $res = 'de';
        }

        return $res;
    }


    // sanitize html text input
    public function sanitize_html_field($dateinput)
    {
        $allowed_html = array(
            'img' => array(
                'title' => array(),
                'src' => array(),
                'alt' => array(),
            ),
            'a' => array(
                'title' => array(),
                'href' => array(),
                'title' => array(),
            ),
            'br' => array(),
            'p' => array(),
            'strong' => array(),
            'ol' => array(),
            'ul' => array(),
            'li' => array(),
            'dl' => array(),
            'dd' => array(),
            'dt' => array(),
            'h3' => array(),
            'h4' => array(),
            'h5' => array(),
            'h6' => array(),
        );
        // before we remove all unwanted html, we search for h1 and h2 to map
        // them to h3.
        $dateinput = preg_replace('/<h(1|2)\s*([^>]*)>/m', '<h3>', $dateinput);
        $dateinput = preg_replace('/<\/h(1|2)>/m', '</h3>', $dateinput);
        $value = wp_kses($dateinput, $allowed_html);

        $value = preg_replace('/<\/p>[\s\t\n\r]+<p>/i', '</p><p>', $value);
        $value = preg_replace('/<p>[\s\t\n\r]+<\/p>/i', '', $value);
        $value = trim($value);
        return $value;
    }

    // check for given date fields, that may be corrupt...
    public function sanitize_dates($dateinput)
    {
        $res = '';
        if (!empty($dateinput)) {
            if (preg_match("/(\d{4}-\d{2}-\d{2})T([0-9:]+)/", $dateinput, $parts)) {
                $dateinput = $parts[1];
            } elseif (preg_match("/\d{4}-\d{2}-\d{2}\s*$/", $dateinput, $parts)) {
                $dateinput = $parts[0];
            } elseif (preg_match("/(\d{2}).(\d{2}).(\d{4})/", $dateinput, $parts)) {
                $dateinput = $parts[3] . '-' . $parts[2] . '-' . $parts[1];
            } elseif (preg_match("/(\d{2}).(\d{2}).(\d{2})\s*$/", $dateinput, $parts)) {
                $dateinput = '20' . $parts[3] . '-' . $parts[2] . '-' . $parts[1];
            } elseif (preg_match("/(\d{4}-\d{2}-\d{2}) ([0-9:]+)/", $dateinput, $parts)) {
                $dateinput = $parts[1] . ' ' . $parts[2];
            }

            $res = gmdate('Y-m-d', strtotime($dateinput));

            //  $res = $dateinput;
        }
        return $res;
    }

    // try to sanitize and repair the telephone number
    public function sanitize_telefon($phone_number)
    {

        $phone_number = trim($phone_number);

        if ((strpos($phone_number, '+49 9131 85-') !== 0) && (strpos($phone_number, '+49 911 5302-') !== 0)) {
            if (!preg_match('/\+49 [1-9][0-9]{1,4} [1-9][0-9]+/', $phone_number)) {
                $phone_data = preg_replace('/\D/', '', $phone_number);
                $vorwahl_erl = '+49 9131 85-';
                $vorwahl_nbg = '+49 911 5302-';

                switch (strlen($phone_data)) {
                    case '3':
                        $phone_number = $vorwahl_nbg . $phone_data;
                        break;
                    case '5':
                        if (strpos($phone_data, '06') === 0) {
                            $phone_number = $vorwahl_nbg . substr($phone_data, -3);
                            break;
                        }
                        $phone_number = $vorwahl_erl . $phone_data;
                        break;
                    case '7':
                        if (strpos($phone_data, '85') === 0 || strpos($phone_data, '06') === 0) {
                            $phone_number = $vorwahl_erl . substr($phone_data, -5);
                            break;
                        }
                        if (strpos($phone_data, '5302') === 0) {
                            $phone_number = $vorwahl_nbg . substr($phone_data, -3);
                            break;
                        }
                    default:
                        if (strpos($phone_data, '9115302') !== false) {
                            $durchwahl = explode('9115302', $phone_data);
                            if (strlen($durchwahl[1]) === 3) {
                                $phone_number = $vorwahl_nbg . substr($phone_data, -3);
                            }
                            break;
                        }
                        if (strpos($phone_data, '913185') !== false) {
                            $durchwahl = explode('913185', $phone_data);
                            if (strlen($durchwahl[1]) === 5) {
                                $phone_number = $vorwahl_erl . substr($phone_data, -5);
                            }
                            break;
                        }
                        if (strpos($phone_data, '09131') === 0 || strpos($phone_data, '499131') === 0) {
                            $durchwahl = explode('9131', $phone_data);
                            $phone_number = "+49 9131 " . $durchwahl[1];
                            break;
                        }
                        if (strpos($phone_data, '0911') === 0 || strpos($phone_data, '49911') === 0) {
                            $durchwahl = explode('911', $phone_data);
                            $phone_number = "+49 911 " . $durchwahl[1];
                            break;
                        }

                }

            }
        }
        return $phone_number;
    }

    /*
     * convert timestring to 24 hour format
     */
    public function convert_time_24hours($time)
    {
        if (strpos($time, 'PM')) {
            $modtime = explode(':', rtrim($time, ' PM'));
            if ($modtime[0] != 12) {
                $modtime[0] = $modtime[0] + 12;
            }
            $time = implode(':', $modtime);
        } elseif (strpos($time, 'AM')) {
            $time = str_replace('12:', '00:', $time);
            $time = rtrim($time, ' AM');
        }

        return $time;
    }

    /*
    * Try to make the Salary Schema MonetaryAmount by the textual strings of TV-L
    *
    * Example: https://schema.org/MonetaryAmount
    "baseSalary": {
    "@type": "MonetaryAmount",
    "currency": "EUR",
    "value": {
    "@type": "Text",
    "value": "TV-L E1 - TV-L E2",
    "unitText": "MONTH"
    }
    }
    */
    public function get_Salary_by_TVL($vonbesold, $bisbesold = '') {

        $res = array();
        $value = $htmlvalue = '';

        if (!empty($bisbesold)) {
            if (!empty($vonbesold) && ($vonbesold != $bisbesold)) {
                $value = $vonbesold . ' - ' . $bisbesold;
                $htmlvalue = $vonbesold . ' &mdash; ' . $bisbesold;
            } else {
                $value = $bisbesold;
                $htmlvalue = $value;
            }
        } elseif (!empty($vonbesold)) {
            $value = $vonbesold;
            $htmlvalue = $value;
        }

        $res["currency"] = "EUR";
        $res["stringvalue"] = $htmlvalue;
        $res["value"]["@type"] = "Text";
        $res["value"]["value"] = $value;
        $res["value"]["unitText"] = "MONTH";

        return $res;

    }

    // Nimmt einen String entgegen, der eine Entgeltgruppe darstellen soll
    // und formatiert den in eine einheitliche Form
    public function sanitize_tvl($entgelt) {
        $ret = '';

        // $aTests = [
        //     'TV L 9b',
        //     'Tarifvertrag E12',
        //     'A12',
        //     'E-12',
        //     'TVLE 12',
        //     'TVA-L BBiG',
        //     'TVA-L',
        //     'C1',
        //     'W2',
        //     'E13 TVL',
        //     'e13 tv-L',
        //     'tvl',
        //     '1C',
        //     'Wir gedenken Ihnen 2C anzubieten',
        //     'E13 TVL oder TV-L e13 oder tvL 13 oder nur 13 ergibt TV-L E 13',
        //     '13, 15, 17, irgendwas blabla', 
        //     'TVL12 (50%)'
        //     'Vergütung nach Vergütungstabelle der FAU ab 01.01.2024 (entspricht inkl. Sozialversicherungsabgaben 622,80 bei 9 Std./Woche)'
        // ];

        if (!empty($entgelt)) {
            // Zuerst entfernen wir runde Klammern und deren Inhalte
            // diese werden ab un dzu fälschlich verwendet um Teilzeitstellen
            // zu kennzeichnen. Dadurch ändert sich aber die Entlöhnung nicht.
            
            $entgelt = preg_replace('/\([^\(\)]*\)/i', '', $entgelt);
            
            // falls der Satz Kommas enthält, z.B. weil da jemand mehr als ein 
            // Tarif einträgt, ist das ungültig. Daher entferne den Komma und 
            // alles danach
            $entgelt = preg_replace('/,\s*(.*)/i', '', $entgelt);
            $gruppe = '';
            
            preg_match('/([ACW]{1})?(TVA\-?L)?/i', $entgelt, $matches);
            
            if (!empty($matches[2])) {
                // Azubi
                $ret = 'TVA-L BBiG';
            } elseif(!empty($matches[1])) {
                // Besoldungsordnung A, C oder W
                preg_match('/(\D)*([\d]+)*/i', $entgelt, $matchnumber);            
                if (!empty($matchnumber[2])) {
                    $gruppe = intval($matchnumber[2]);
                }
                $ret = strtoupper(trim($matches[1])) . ' ' . $gruppe . ' ' . BESOLDUNG_TXT;
            } else {
                if (preg_match('/bbig/i', $entgelt)) {
                    // Azubis
                    $ret = 'TVA-L BBiG';
                    
                } elseif (preg_match('/(tv[\-\s]?l|e\s*\d{1,2})/i', $entgelt)) {
                    // Tarifvertrag TV-L
                    preg_match('/(\D)*([\d]+)*(a|b)?/i', $entgelt, $matches);            
                    if (!empty($matches[2])) {
                        $gruppe = intval($matches[2]);
                    }
                    $ret = 'TV-L E ' . $gruppe . (!empty($matches[3])?strtolower($matches[3]):'');
                } else {
                    // Nicht interpretierbar, wahrscheinlich Prosatext
                    $ret = esc_html($entgelt);
                }
                
              
            }

        }

        return $ret;
    }

    // Nimmt einen String entgegen, der eine Entgeltgruppe darstellen soll
    // und formatiert den in eine einheitliche Form
    public function sanitize_besoldung($entgeld) {
        $res = '';
        if (!empty($entgeld)) {
            if (preg_match('/^(a|b|c|w|r|aw)\s*([0-9]+)$/i', $entgeld, $output_array)) {
                if (isset($output_array[2])) {
                    $gruppe = $output_array[2];
                    $gruppe = intval($gruppe);
                    $res = strtoupper($output_array[1]) . ' ' . $gruppe;
                } else {
                    // irgendwas anderes, was wir nicht interpretieren können..
                    // => behalte es, wie es ist.
                    $res = $entgeld;
                }
            }

        }
        return $res;
    }

    public function get_array_as_string($type)  {
        if (is_array($type)) {
            $res = '';
            foreach ($type as $i) {
                if (!empty($res)) {
                    $res .= ', ' . strval($i);
                } else {
                    $res = strval($i);
                }
            }
            return $res;
        } else {
            return strval($type);
        }
    }

    public function get_empoymentType_as_string($type)
    {
        return $this->get_array_as_string($type);
    }

    // create the url for apply
    public function get_apply_url($data, $fallback = '', $defaultSubject = '')
    {

        if ((isset($data['applicationContact']['url'])) && (!empty($data['applicationContact']['url']))) {
            return $data['applicationContact']['url'];
        } elseif ((isset($data['applicationContact']['email'])) && (!empty($data['applicationContact']['email']))) {
            $mailadr = $data['applicationContact']['email'];
            if ($defaultSubject != '' || !empty($data['applicationContact']['email_subject'])) {
                $subjects = [];
                if ($defaultSubject != '') {
                    $subjects[] = sanitize_text_field($defaultSubject);
                }
                if (!empty($data['applicationContact']['email_subject'])) {
                    $subjects[] = $data['applicationContact']['email_subject'];
                }
                $mailadr .= '?subject=' . implode(' ', $subjects);
            }

            return 'mailto:' . $mailadr;
        }

        if ((isset($fallback)) && (!empty($fallback))) {
            if (strpos($fallback, '@', 1) === false) {
                // no mail adress, asuming url
                $fallback = sanitize_url($fallback);
            } else {
                $fallback = sanitize_email($fallback);
                if (!empty($fallback)) {
                    $fallback = 'mailto:' . $fallback;
                }

            }
            return $fallback;
        }
        return;

    }
    public function get_application_subject_by_text($text)
    {
        $res = '';
        if (!empty($text)) {
            preg_match_all('/(Subject|Betreff):\s*([\wßäöü\-\._\[\] ]+)/i', $text, $output_array);

            if (!empty($output_array)) {
                if ((isset($output_array[2])) && (isset($output_array[2][0]))) {
                    $res = $output_array[2][0];
                }
            }
        }
        return $res;
    }

    // check for select value of group and translate into the desired long form
    public function sanitize_occupationalCategory($group)
    {
        $res = '';
        if (empty($group)) {
            return $res;
        }
        switch ($group) {
            case 'wiss':
                $res = __('Research & Teaching', 'rrze-jobs');
                break;
            case 'verw':
            case 'tech':
            case 'pflege':
            case 'arb':
            case 'n-wiss':
                $res = __('Technology & Administration', 'rrze-jobs');
                break;
            case 'azubi':
                $res = __('Trainee', 'rrze-jobs');
                break;

            case 'hiwi':
                $res = __('Student assistants', 'rrze-jobs');
                break;
            case 'prof':
                $res = __('Professorships', 'rrze-jobs');
                break;
            case 'aush':
            case 'other':
                $res = __('Other', 'rrze-jobs');
                break;

            default:
                $res = __('Other', 'rrze-jobs');
        }

        return $res;
    }

    public function map_occupationalCategory($group)
    {
        $res = '';
        if (empty($group)) {
            return $res;
        }
        $group = strtolower($group);

        switch ($group) {
            case 'wiss':
                $res = 'wiss';
                break;
            case 'verw':
            case 'pflege':
            case 'arb':
            case 'n-wiss':
                $res = 'n-wiss';
                break;
            case 'azubi':
                $res = 'azubi'; // UnivIS + B-ITE
                break;
            case 'hiwi':
                $res = 'hiwi'; // UnivIS + B-ITE
                break;
            case 'prof':
                $res = 'prof';
                break;
            case 'postdoc':
            case 'promo':
            case 'unimgt':
            case 'it':
            case 'tech':
            case 'prak':
            case 'studjobs':
                // neue B-ITE-Gruppen
                $res = $group;
                break;
            default:
            case 'aush':
            case 'other':
                $res = 'other';
                break;
        }

        return $res;
    }

}