<?php

/**
 * OrgData 
 * 
 * Created on : 27.10.2022, 17:34:17
 */

namespace RRZE\Jobs;
use function RRZE\Jobs\Config\getConstants;

defined('ABSPATH') || exit;

class OrgData {
    
    public $orgdata;
    protected $api = 'https://api.fau.de/pub/v3/vz/organizations';
    protected $atts;
    protected $api_timeout = 5;
    protected $fauorg_transient_timeout = 30;
    
    /*-----------------------------------------------------------------------------------*/
    /* Get FAU.ORG by faculty
    /*-----------------------------------------------------------------------------------*/
    public function __construct() {
	    $this->orgdata = $this->fau_orga_data();

    }
    /*-----------------------------------------------------------------------------------*/
    /* Get API Key for api.fau.de
    /*-----------------------------------------------------------------------------------*/
    private function get_apikey(): string {
        $optionlist = get_option('rrze-jobs');

        if (!empty($optionlist['basic_ApiKey'])){
            return $optionlist['basic_ApiKey'];
        } elseif(is_multisite()){
            $settingsOptions = get_site_option('rrze_settings');
            if (!empty($settingsOptions->plugins->dip_apiKey)){
                return $settingsOptions->plugins->dip_apiKey;
            }
        }
        return '';
    }
    /*-----------------------------------------------------------------------------------*/
    /* FAUORG-Data by api.fau.de
    /*-----------------------------------------------------------------------------------*/
    private function get_fau_orga_from_api(string $orgnum): array {
        $aRet = [
            'valid' => FALSE, 
            'content' => ''
        ];
         
        if (!$this->is_valid_orgnum($orgnum)) {
            $aRet['content'] = 'No valid ORG num';
            return $aRet;
        }
        $apikey = $this->get_apikey();
        if (empty($apikey)) {
            $aRet['content'] = 'Missing API key';
            return $aRet;
        }
        $aGetArgs = [
            'timeout' => $this->api_timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key' => $apikey,
                ]
        ];
         
         
        $apirequest =  $this->api .  '/' . $orgnum;
        $apiResponse = wp_remote_get($apirequest, $aGetArgs);
        $aRet['request_string'] =$apirequest;
        if ( is_array( $apiResponse ) && ! is_wp_error( $apiResponse ) ) {
  
             $content = json_decode($apiResponse['body'], true);
             
            if (empty($content['data'])) {
                $aRet['valid'] = FALSE;
                $aRet['code'] = 404;
            } else {
                $aRet['content'] = $content;
                $aRet['valid'] = true;
                $aRet['code'] = 200;
            }
            
          
        } else {
            $aRet['code'] = $apiResponse->get_error_code();
            $aRet['content']  =  $apiResponse->get_error_message();
           
        }
        return $aRet;
        
    }
    
    /*-----------------------------------------------------------------------------------*/
    /* Static ORG-Data, that stays permanent so that we dont need to call the app
    /*-----------------------------------------------------------------------------------*/
    public function fau_orga_data(): array {
        $data = array(
            '0000000000' => array(
                'title'		=> 'Friedrich-Alexander-Universität',
                'shorttitle'	=> 'FAU',
                'url'		=> __('https://www.fau.de', 'rrze-jobs')
            ),
            '1005000000' => array(
                'title'	    => __('Zentrale Universitätsverwaltung', 'rrze-jobs'),
                'shorttitle'    => 'ZUV',
                'parent'    => '0000000000'
            ),
             '1011000000'	=> array(
                'title'	=> __('Zentrale Einrichtungen', 'rrze-jobs'),
                'parent'    => '0000000000',
                'hide'	    => true,
            ),
            '1011110000' => array(
                'title'	    => __('Universitätsbibliothek', 'rrze-jobs'),
                'shorttitle'    => 'UB',
                'url'	    => __('https://ub.fau.de', 'rrze-jobs'),
                'parent'    => '1011000000'
            ),
             '1011120000' => array(
                'title'	    => __('Regionales Rechenzentrum Erlangen', 'rrze-jobs'),
                'shorttitle'    => 'RRZE',
                'url'	    => __('https://rrze.fau.de', 'rrze-jobs'),
                'parent'    => '1011000000'
            ),
             '1011130000' => array(
                'title'	    => __('Zentralinstitut für Regionenforschung', 'rrze-jobs'),
                'parent'    => '1011000000'
            ),
            '1011200000' => array(
                'title'	    => __('Graduiertenzentrum der FAU', 'rrze-jobs'),
                'parent'    => '1011000000'
            ),
             '1011290000' => array(
                'title'	    => __('Zentrum für Lehr-/Lernforschung, -innovation und Transfer', 'rrze-jobs'),
                'shorttitle'    => 'ZeLLIT',
                'parent'    => '1011000000'
            ),
             '1011320000' => array(
                'title'	    => __('FAU Forschungszentren', 'rrze-jobs'),
                'parent'    => '1011000000'
            ),
            '1011330000' => array(
                'title'	    => __('FAU Kompetenzzentren', 'rrze-jobs'),
                'parent'    => '1011000000'
            ),
            '1013000000'	=> array(
                'title'	=> __('Interdisziplinäre Zentren', 'rrze-jobs'),
                'parent'    => '0000000000',
            ),
             '1014000000'	=> array(
                'title'	=> __('Museen und Sammlungen', 'rrze-jobs'),
                'parent'    => '0000000000',
            ),
            '1015000000'	=> array(
                'title'	=> __('DFG-Sonderforschungsbereiche/ Transregios/Transferbereiche', 'rrze-jobs'),
                'parent'    => '0000000000',
            ),
            '1016000000'	=> array(
                'title'	=> __('DFG-Graduiertenkollegs', 'rrze-jobs'),
                'parent'    => '0000000000',
            ),
            '1040000000'	=> array(
                'title'	=> __('Zentrale Serviceeinrichtungen', 'rrze-jobs'),
                'parent'    => '0000000000',
            ),
            '1100000000' => array(
                'title'	    => __('Philosophische Fakultät und Fachbereich Theologie', 'rrze-jobs'),
                'shorttitle'    => 'Phil',
                'url'	    => __('https://phil.fau.de', 'rrze-jobs'),
                'parent'    => '0000000000',
                'class'	    => 'phil',
                'faculty'   => 'phil'
            ),
            '1111000000' => array(
                'title'	    => __('Department Alte Welt und Asiatische Kulturen', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
            '1112000000' => array(
                'title'	    => __('Department Anglistik/Amerikanistik und Romanistik', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
            '1113000000' => array(
                'title'	    => __('Department Fachdidaktiken', 'rrze-jobs'),
                'url'	    => __('https://www.fachdidaktiken.fau.de/', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
            '1114000000' => array(
                'title'	    => __('Department Germanistik und Komparatistik', 'rrze-jobs'),
                'url'	    => __('https://www.germanistik.phil.fau.de/', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
            '1115000000' => array(
                'title'	    => __('Department Geschichte', 'rrze-jobs'),
                'url'	    => __('https://www.geschichte.phil.fau.de/', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
            '1116000000' => array(
                'title'	    => __('Department Medienwissenschaften und Kunstgeschichte', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
            '1117000000' => array(
                'title'	    => __('Department Pädagogik', 'rrze-jobs'),
                'url'	    => __('https://www.department-paedagogik.phil.fau.de/', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
            '1118000000' => array(
                'title'	    => __('Department Psychologie', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
            '1119000000' => array(
                'title'	    => __('Department Sozialwissenschaften und Philosophie', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
            '1120000000' => array(
                'title'	    => __('Fachbereich Theologie', 'rrze-jobs'),
                'url'	    => __('https://www.theologie.fau.de', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
             '1121000000' => array(
                'title'	    => __('Department Islamisch-Religiöse Studien', 'rrze-jobs'),
                'url'	    => __('https://www.dirs.phil.fau.de/', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
            '1122000000' => array(
                'title'	    => __('Department Sportwissenschaft und Sport', 'rrze-jobs'),
                'url'	    => __('https://www.sport.fau.de', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),
            '1123000000' => array(
                'title'	    => __('Department Digital Humanities and Social Studies', 'rrze-jobs'),
                'url'	    => __('https://www.dhss.phil.fau.de/', 'rrze-jobs'),
                'parent'    => '1100000000'
            ),


            '1200000000' => array(
                'title'	    => __('Rechts- und Wirtschaftswissenschaftliche Fakultät', 'rrze-jobs'),
                'shorttitle'    => 'RW',
                'url'	    => __('https://rw.fau.de', 'rrze-jobs'),
                'parent'    => '0000000000',
                'class'	    => 'rw',
                'faculty'   => 'rw'
            ),
            '1211000000' => array(
                'title'	    => __('Fachbereich Rechtswissenschaft', 'rrze-jobs'),
                'shorttitle'    => __('FB Rechtsw.', 'rrze-jobs'),
                'url'	    => __('https://jura.rw.fau.de', 'rrze-jobs'),
                'parent'    => '0000000000',
                'class'	    => 'rw',
                'faculty'   => 'rw'
            ),
            '1212000000' => array(
                'title'	    => __('Fachbereich Wirtschafts- und Sozialwissenschaften', 'rrze-jobs'),
                'shorttitle'    => 'FB WiSo',
                'url'	    => __('https://wiso.rw.fau.de', 'rrze-jobs'),
                'parent'    => '0000000000',
                'class'	    => 'rw',
                'faculty'   => 'rw'
            ),


            '1300000000' => array(
                'title'	    => __('Medizinische Fakultät', 'rrze-jobs'),
                'shorttitle'    => 'Med', 
                'url'	    => __('https://med.fau.de', 'rrze-jobs'),
                'parent'    => '0000000000',
                'class'	    => 'med',
                'faculty'   => 'med'
            ),
            '1311000000' => array(
                'title'	    => __('Einrichtungen, die nicht zum Universitätsklinikum Erlangen gehören', 'rrze-jobs'),
                'parent'    => '1300000000',
                'hide'	    => true,
            ),
            '1311110000' => array(
                'title'	    => __('Institut für Anatomie', 'rrze-jobs'),
                'url'	    => __('https://www.anatomie.med.fau.de/', 'rrze-jobs'),
                'parent'	    => '1311000000'
            ),
            '1311120000' => array(
                'title'	    => __('Institut für Physiologie und Pathophysiologie', 'rrze-jobs'),
                'url'	    => __('https://www.physiologie1.med.fau.de/', 'rrze-jobs'),
                'parent'	    => '1311000000'
            ),
            '1311130000' => array(
                'title'	    => __('Institut für Zelluläre und Molekulare Physiologie', 'rrze-jobs'),
                'url'	    => __('https://www.physiologie2.med.fau.de/', 'rrze-jobs'),
                'parent'	    => '1311000000'
            ),
            '1311140000' => array(
                'title'	    => __('Institut für Biochemie', 'rrze-jobs'),
                'url'	    => __('https://www.biochemie.med.fau.de/', 'rrze-jobs'),
                'parent'	    => '1311000000'
            ),
            '1311310000' => array(
                'title'	    => __('Institut für Medizininformatik, Biometrie und Epidemiologie', 'rrze-jobs'),
                'url'	    => __('https://www.imbe.med.fau.de/', 'rrze-jobs'),
                'parent'	    => '1311000000'
            ),
            '1311320000' => array(
                'title'	    => __('Institut für Geschichte und Ethik der Medizin', 'rrze-jobs'),
                'url'	    => __('https://www.igem.med.fau.de/', 'rrze-jobs'),
                'parent'	    => '1311000000'
            ),
            '1311330000' => array(
                'title'	    => __('Institut für Rechtsmedizin', 'rrze-jobs'),
                'url'	    => __('https://www.recht.med.uni-erlangen.de/', 'rrze-jobs'),
                'parent'	    => '1311000000'
            ),
            '1311340000' => array(
                'title'	    => __('Institut für Experimentelle und Klinische Pharmakologie und Toxikologie', 'rrze-jobs'),
                'url'	    => __('https://www.pharmakologie.med.fau.de/', 'rrze-jobs'),
                'parent'	    => '1311000000'
            ),
            '1311350000' => array(
                'title'	    => __('Institut und Poliklinik für Arbeits-, Sozial- und Umweltmedizin', 'rrze-jobs'),
                'url'	    => __('https://www.ipasum.med.fau.de/', 'rrze-jobs'),
                'parent'	    => '1311000000'
            ),
            '1311360000' => array(
                'title'	    => __('Institut für Biomedizin des Alterns', 'rrze-jobs'),
                'url'	    => __('https://www.iba.med.fau.de/', 'rrze-jobs'),
                'parent'	    => '1311000000'
            ),
            '1311370000' => array(
                'title'	    => __('Klinisch-Molekularbiologisches Forschungszentrum', 'rrze-jobs'),
                'url'	    => __('http://www.molmed.uni-erlangen.de/', 'rrze-jobs'),
                'parent'	    => '1311000000'
            ),



            '1400000000' => array(
                'title'	    => __('Naturwissenschaftliche Fakultät', 'rrze-jobs'),
                'shorttitle'    => 'Nat', 
                'url'	    => __('https://nat.fau.de', 'rrze-jobs'),
                'parent'    => '0000000000',
                'class'	    => 'nat',
                'faculty'   => 'nat'
            ),
            '1411000000' => array(
                'title'	    => __('Department Biologie', 'rrze-jobs'),
                'shorttitle'    => 'Bio',
                'url'	    => __('https://www.biologie.nat.fau.de/', 'rrze-jobs'),
                'parent'    => '1400000000'
            ),
            '1412000000' => array(
                'title'	    => __('Department Chemie und Pharmazie', 'rrze-jobs'),
                'url'	    => __('https://www.chemie.nat.fau.de/', 'rrze-jobs'),
                'parent'    => '1400000000'
            ),
            '1413000000' => array(
                'title'	    => __('Department Geographie und Geowissenschaften', 'rrze-jobs'),
                'url'	    => __('https://www.geo.nat.fau.de/', 'rrze-jobs'),
                'parent'    => '1400000000'
            ),
            '1414000000' => array(
                'title'	    => __('Department Mathematik', 'rrze-jobs'),
                'url'	    => __('http://www.math.fau.de/', 'rrze-jobs'),
                'parent'    => '1400000000'
            ),
            '1415000000' => array(
                'title'	    => __('Department Physik', 'rrze-jobs'),
                'url'	    => __('https://www.physik.nat.fau.de/', 'rrze-jobs'),
                'parent'    => '1400000000'
            ),
             '1416000000' => array(
                'title'	    => __('Department of Data Science', 'rrze-jobs'),
                'url'	    => __('https://www.datascience.nat.fau.eu/', 'rrze-jobs'),
                'parent'    => '1400000000'
            ),


            '1500000000' => array(
                'title'         => __('Technische Fakultät', 'rrze-jobs'),
                'shorttitle'    =>  'TF', 
                'url'           => __('https://tf.fau.de', 'rrze-jobs'),
                'parent'    => '0000000000',
                'class'	    => 'tf',
                'faculty'   => 'tf'
            ),    
            '1511000000'   => array(
                'title'          => __('Department Chemie- und Bioingenieurwesen', 'rrze-jobs'),
                'shorttitle'     => 'CBI', 
                'url'            => __('https://www.cbi.tf.fau.de/', 'rrze-jobs'),
                'parent'    => '1500000000'
            ),
            '1512000000'   => array(
                'title'          => __('Department Elektrotechnik-Elektronik-Informationstechnik', 'rrze-jobs'),
                'shorttitle'     => 'EEI', 
                'url'            => __('https://www.eei.tf.fau.de/', 'rrze-jobs'),
                'parent'    => '1500000000'
            ),
            '1513000000'   => array(
                'title'          => __('Department Informatik', 'rrze-jobs'),
                'shorttitle'     => 'CS',
                'url'            => __('https://cs.fau.de/', 'rrze-jobs'),
                'parent'    => '1500000000'
            ),
            '1514000000'   => array(
                'title'          => __('Department Maschinenbau', 'rrze-jobs'),
                'shorttitle'     => 'MB', 
                'url'            => __('https://www.department.mb.tf.fau.de/', 'rrze-jobs'),
                'parent'    => '1500000000'
            ),
            '1515000000'   => array(
                'title'          => __('Department Werkstoffwissenschaften', 'rrze-jobs'),
                'url'            => __('https://www.ww.tf.fau.de', 'rrze-jobs'),
                'parent'    => '1500000000'
            ), 
            '1518000000'   => array(
                'title'          => __('Department Artificial Intelligence in Biomedical Engineering', 'rrze-jobs'),
                'url'            => __('https://www.aibe.tf.fau.de/', 'rrze-jobs'),
                'parent'    => '1500000000'
            ), 

            '9900000000' => array(
                'title'		=> 'Externe Einrichtungen',
            ),
        );
        return $data;
    }
    
    
    /*-----------------------------------------------------------------------------------*/
    /* Check for valid Orgnum
    /*-----------------------------------------------------------------------------------*/
    public function is_valid_orgnum(string $num): bool {
        if ((isset($num)) && (preg_match('/\b[0-9]{10}\b/', $num))) {
            return true;
        }
        return false;
    }
    
    /*-----------------------------------------------------------------------------------*/
    /* Get FAU.ORG Data by orgnum
    /*-----------------------------------------------------------------------------------*/
    public function get_org(string $orgnum): array {       
        if ($this->is_valid_orgnum($orgnum)) {
            $fau_orga_breadcrumb_data = $this->fau_orga_data();


            if ((isset($orgnum)) && isset($fau_orga_breadcrumb_data[$orgnum])) {
                $fau_orga_breadcrumb_data[$orgnum]['orgnum'] = $orgnum;
                return $fau_orga_breadcrumb_data[$orgnum];
            } elseif (isset($orgnum)) {
                // ORGNum nicht in der statischen Liste vorhanden
                // Suche und Abruf über API notwendig
                $orgdata = $this->get_fau_orga_from_api($orgnum);
                if ($orgdata['valid']) {
                   $fau_orga_breadcrumb_data[$orgnum]['orgnum'] = $orgnum;
                   $fau_orga_breadcrumb_data[$orgnum]['title'] = $orgdata['content']['name'];
                   $fau_orga_breadcrumb_data[$orgnum]['shorttitle'] = $orgdata['content']['alternateName'];
                   return $fau_orga_breadcrumb_data[$orgnum];
                }

            }
        }

        return [];
    }
    /*-----------------------------------------------------------------------------------*/
    /* Get FAU.ORG by faculty
    /*-----------------------------------------------------------------------------------*/
    public function get_fau_orga_fauorg_by_faculty(string $faculty = ''): string {
        $fau_orga_breadcrumb_data = $this->fau_orga_data();
        $res = '';
           // $fauorg = san_fauorg_number($fauorg);
        if (isset($faculty)) {
            foreach($fau_orga_breadcrumb_data as $key => $listdata) {
                if (isset($listdata['faculty']) && ($listdata['faculty'] == $faculty)) {
                    $res = $key;
                    break;
                }
            }
        }
        return $res;
    }
    /*-----------------------------------------------------------------------------------*/
    /* Get child elements
    /*-----------------------------------------------------------------------------------*/
    public function get_fau_orga_childs(string $fauorg = '000000000'): string {
        $fau_orga_breadcrumb_data = $this->fau_orga_data();
        $res = array();
           // $fauorg = san_fauorg_number($fauorg);
        if (isset($fauorg)) {
            foreach($fau_orga_breadcrumb_data as $key => $listdata) {
                if (isset($listdata['parent']) && ($listdata['parent'] == $fauorg)) {
                    $res[] = $key;
                }
            }
        }
        return $res;
    }
}