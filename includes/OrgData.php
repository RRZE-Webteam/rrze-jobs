<?php

/**
 * OrgData 
 * 
 * Created on : 27.10.2022, 17:34:17
 */

namespace RRZE\Jobs;

defined('ABSPATH') || exit;
// const RRZE_TEXTDOMAIN = 'rrze-jobs';

class OrgData {
    

    /*-----------------------------------------------------------------------------------*/
    /* Get FAU.ORG by faculty
    /*-----------------------------------------------------------------------------------*/
    public function __construct() {
	    $this->orgdata = $this->fau_orga_data();
    }
    
    // fill the data
    public function fau_orga_data() {
	$data = array(
	    '0000000000' => array(
		'title'		=> 'Friedrich-Alexander-Universität',
		'shorttitle'	=> 'FAU',
		'url'		=> __('https://www.fau.de', RRZE_TEXTDOMAIN)
	    ),
	    '1005000000' => array(
		'title'	    => __('Zentrale Universitätsverwaltung', RRZE_TEXTDOMAIN),
		'shorttitle'    => 'ZUV',
		'parent'    => '0000000000'
	    ),
	     '1011000000'	=> array(
		'title'	=> __('Zentrale Einrichtungen', RRZE_TEXTDOMAIN),
		'parent'    => '0000000000',
		'hide'	    => true,
	    ),
	    '1011110000' => array(
		'title'	    => __('Universitätsbibliothek', RRZE_TEXTDOMAIN),
		'shorttitle'    => 'UB',
		'url'	    => __('https://ub.fau.de', RRZE_TEXTDOMAIN),
		'parent'    => '1011000000'
	    ),
	     '1011120000' => array(
		'title'	    => __('Regionales Rechenzentrum Erlangen', RRZE_TEXTDOMAIN),
		'shorttitle'    => 'RRZE',
		'url'	    => __('https://rrze.fau.de', RRZE_TEXTDOMAIN),
		'parent'    => '1011000000'
	    ),
	     '1011130000' => array(
		'title'	    => __('Zentralinstitut für Regionenforschung', RRZE_TEXTDOMAIN),
		'parent'    => '1011000000'
	    ),
	    '1011200000' => array(
		'title'	    => __('Graduiertenzentrum der FAU', RRZE_TEXTDOMAIN),
		'parent'    => '1011000000'
	    ),
	     '1011290000' => array(
		'title'	    => __('Zentrum für Lehr-/Lernforschung, -innovation und Transfer', RRZE_TEXTDOMAIN),
		'shorttitle'    => 'ZeLLIT',
		'parent'    => '1011000000'
	    ),
	     '1011320000' => array(
		'title'	    => __('FAU Forschungszentren', RRZE_TEXTDOMAIN),
		'parent'    => '1011000000'
	    ),
	    '1011330000' => array(
		'title'	    => __('FAU Kompetenzzentren', RRZE_TEXTDOMAIN),
		'parent'    => '1011000000'
	    ),
	    '1013000000'	=> array(
		'title'	=> __('Interdisziplinäre Zentren', RRZE_TEXTDOMAIN),
		'parent'    => '0000000000',
	    ),
	     '1014000000'	=> array(
		'title'	=> __('Museen und Sammlungen', RRZE_TEXTDOMAIN),
		'parent'    => '0000000000',
	    ),
	    '1015000000'	=> array(
		'title'	=> __('DFG-Sonderforschungsbereiche/ Transregios/Transferbereiche', RRZE_TEXTDOMAIN),
		'parent'    => '0000000000',
	    ),
	    '1016000000'	=> array(
		'title'	=> __('DFG-Graduiertenkollegs', RRZE_TEXTDOMAIN),
		'parent'    => '0000000000',
	    ),
	    '1040000000'	=> array(
		'title'	=> __('Zentrale Serviceeinrichtungen', RRZE_TEXTDOMAIN),
		'parent'    => '0000000000',
	    ),
	    '1100000000' => array(
		'title'	    => __('Philosophische Fakultät und Fachbereich Theologie', RRZE_TEXTDOMAIN),
		'shorttitle'    => 'Phil',
		'url'	    => __('https://phil.fau.de', RRZE_TEXTDOMAIN),
		'parent'    => '0000000000',
		'class'	    => 'phil',
		'faculty'   => 'phil'
	    ),
	    '1111000000' => array(
		'title'	    => __('Department Alte Welt und Asiatische Kulturen', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	    '1112000000' => array(
		'title'	    => __('Department Anglistik/Amerikanistik und Romanistik', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	    '1113000000' => array(
		'title'	    => __('Department Fachdidaktiken', RRZE_TEXTDOMAIN),
		'url'	    => __('http://www.fachdidaktiken.uni-erlangen.de/', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	    '1114000000' => array(
		'title'	    => __('Department Germanistik und Komparatistik', RRZE_TEXTDOMAIN),
		'url'	    => __('https://www.germanistik.phil.fau.de/', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	    '1115000000' => array(
		'title'	    => __('Department Geschichte', RRZE_TEXTDOMAIN),
		'url'	    => __('https://www.geschichte.phil.fau.de/', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	    '1116000000' => array(
		'title'	    => __('Department Medienwissenschaften und Kunstgeschichte', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	    '1117000000' => array(
		'title'	    => __('Department Pädagogik', RRZE_TEXTDOMAIN),
		'url'	    => __('https://www.department-paedagogik.phil.fau.de/', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	    '1118000000' => array(
		'title'	    => __('Department Psychologie', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	    '1119000000' => array(
		'title'	    => __('Department Sozialwissenschaften und Philosophie', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	    '1120000000' => array(
		'title'	    => __('Fachbereich Theologie', RRZE_TEXTDOMAIN),
		'url'	    => __('https://www.theologie.fau.de', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	     '1121000000' => array(
		'title'	    => __('Department Islamisch-Religiöse Studien', RRZE_TEXTDOMAIN),
		'url'	    => __('https://www.dirs.phil.fau.de/', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	    '1122000000' => array(
		'title'	    => __('Department Sportwissenschaft und Sport', RRZE_TEXTDOMAIN),
		'url'	    => __('https://www.sport.fau.de', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),
	    '1123000000' => array(
		'title'	    => __('Department Digital Humanities and Social Studies', RRZE_TEXTDOMAIN),
		'url'	    => __('https://www.dhss.phil.fau.de/', RRZE_TEXTDOMAIN),
		'parent'    => '1100000000'
	    ),


	    '1200000000' => array(
		'title'	    => __('Rechts- und Wirtschaftswissenschaftliche Fakultät', RRZE_TEXTDOMAIN),
		'shorttitle'    => 'RW',
		'url'	    => __('https://rw.fau.de', RRZE_TEXTDOMAIN),
		'parent'    => '0000000000',
		'class'	    => 'rw',
		'faculty'   => 'rw'
	    ),
	    '1211000000' => array(
		'title'	    => __('Fachbereich Rechtswissenschaft', RRZE_TEXTDOMAIN),
		'shorttitle'    => __('FB Rechtsw.', RRZE_TEXTDOMAIN),
		'url'	    => __('https://jura.rw.fau.de', RRZE_TEXTDOMAIN),
		'parent'    => '0000000000',
		'class'	    => 'rw',
		'faculty'   => 'rw'
	    ),
	    '1212000000' => array(
		'title'	    => __('Fachbereich Wirtschafts- und Sozialwissenschaften', RRZE_TEXTDOMAIN),
		'shorttitle'    => 'FB WiSo',
		'url'	    => __('https://wiso.rw.fau.de', RRZE_TEXTDOMAIN),
		'parent'    => '0000000000',
		'class'	    => 'rw',
		'faculty'   => 'rw'
	    ),


	    '1300000000' => array(
		    'title'	    => __('Medizinische Fakultät', RRZE_TEXTDOMAIN),
		    'shorttitle'    => 'Med', 
		    'url'	    => __('https://med.fau.de', RRZE_TEXTDOMAIN),
		    'parent'    => '0000000000',
		'class'	    => 'med',
		'faculty'   => 'med'
	    ),
	    '1311000000' => array(
		    'title'	    => __('Einrichtungen, die nicht zum Universitätsklinikum Erlangen gehören', RRZE_TEXTDOMAIN),
		    'parent'    => '1300000000',
		    'hide'	    => true,
	    ),
	    '1311110000' => array(
		    'title'	    => __('Institut für Anatomie', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.anatomie.med.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'	    => '1311000000'
	    ),
	    '1311120000' => array(
		    'title'	    => __('Institut für Physiologie und Pathophysiologie', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.physiologie1.uni-erlangen.de/', RRZE_TEXTDOMAIN),
		    'parent'	    => '1311000000'
	    ),
	    '1311130000' => array(
		    'title'	    => __('Institut für Zelluläre und Molekulare Physiologie', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.physiologie2.med.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'	    => '1311000000'
	    ),
	    '1311140000' => array(
		    'title'	    => __('Institut für Biochemie', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.biochemie.med.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'	    => '1311000000'
	    ),
	    '1311310000' => array(
		    'title'	    => __('Institut für Medizininformatik, Biometrie und Epidemiologie', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.imbe.med.uni-erlangen.de/', RRZE_TEXTDOMAIN),
		    'parent'	    => '1311000000'
	    ),
	    '1311320000' => array(
		    'title'	    => __('Institut für Geschichte und Ethik der Medizin', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.igem.med.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'	    => '1311000000'
	    ),
	    '1311330000' => array(
		    'title'	    => __('Institut für Rechtsmedizin', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.recht.med.uni-erlangen.de/', RRZE_TEXTDOMAIN),
		    'parent'	    => '1311000000'
	    ),
	    '1311340000' => array(
		    'title'	    => __('Institut für Experimentelle und Klinische Pharmakologie und Toxikologie', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.pharmakologie.med.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'	    => '1311000000'
	    ),
	    '1311350000' => array(
		    'title'	    => __('Institut und Poliklinik für Arbeits-, Sozial- und Umweltmedizin', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.ipasum.med.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'	    => '1311000000'
	    ),
	    '1311360000' => array(
		    'title'	    => __('Institut für Biomedizin des Alterns', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.iba.med.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'	    => '1311000000'
	    ),
	    '1311370000' => array(
		    'title'	    => __('Klinisch-Molekularbiologisches Forschungszentrum', RRZE_TEXTDOMAIN),
		    'url'	    => __('http://www.molmed.uni-erlangen.de/', RRZE_TEXTDOMAIN),
		    'parent'	    => '1311000000'
	    ),



	    '1400000000' => array(
		    'title'	    => __('Naturwissenschaftliche Fakultät', RRZE_TEXTDOMAIN),
		    'shorttitle'    => 'Nat', 
		    'url'	    => __('https://nat.fau.de', RRZE_TEXTDOMAIN),
		    'parent'    => '0000000000',
		    'class'	    => 'nat',
		    'faculty'   => 'nat'
	    ),
	    '1411000000' => array(
		    'title'	    => __('Department Biologie', RRZE_TEXTDOMAIN),
		    'shorttitle'    => 'Bio',
		    'url'	    => __('https://www.biologie.nat.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'    => '1400000000'
	    ),
	    '1412000000' => array(
		    'title'	    => __('Department Chemie und Pharmazie', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.chemie.nat.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'    => '1400000000'
	    ),
	    '1413000000' => array(
		    'title'	    => __('Department Geographie und Geowissenschaften', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.geo.nat.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'    => '1400000000'
	    ),
	    '1414000000' => array(
		    'title'	    => __('Department Mathematik', RRZE_TEXTDOMAIN),
		    'url'	    => __('http://www.math.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'    => '1400000000'
	    ),
	    '1415000000' => array(
		    'title'	    => __('Department Physik', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.physik.nat.fau.de/', RRZE_TEXTDOMAIN),
		    'parent'    => '1400000000'
	    ),
	     '1416000000' => array(
		    'title'	    => __('Department of Data Science', RRZE_TEXTDOMAIN),
		    'url'	    => __('https://www.datascience.nat.fau.eu/', RRZE_TEXTDOMAIN),
		    'parent'    => '1400000000'
	    ),


	    '1500000000' => array(
		'title'         => __('Technische Fakultät', RRZE_TEXTDOMAIN),
		'shorttitle'    =>  'TF', 
		'url'           => __('https://tf.fau.de', RRZE_TEXTDOMAIN),
		'parent'    => '0000000000',
		'class'	    => 'tf',
		'faculty'   => 'tf'
	    ),    
	    '1511000000'   => array(
		'title'          => __('Department Chemie- und Bioingenieurwesen', RRZE_TEXTDOMAIN),
		'shorttitle'     => 'CBI', 
		'url'            => __('https://www.cbi.tf.fau.de/', RRZE_TEXTDOMAIN),
		'parent'    => '1500000000'
	    ),
	    '1512000000'   => array(
		'title'          => __('Department Elektrotechnik-Elektronik-Informationstechnik', RRZE_TEXTDOMAIN),
		'shorttitle'     => 'EEI', 
		'url'            => __('https://www.eei.tf.fau.de/', RRZE_TEXTDOMAIN),
		'parent'    => '1500000000'
	    ),
	    '1513000000'   => array(
		'title'          => __('Department Informatik', RRZE_TEXTDOMAIN),
		'shorttitle'     => 'CS',
		'url'            => __('https://cs.fau.de/', RRZE_TEXTDOMAIN),
		'parent'    => '1500000000'
	    ),
	    '1514000000'   => array(
		'title'          => __('Department Maschinenbau', RRZE_TEXTDOMAIN),
		'shorttitle'     => 'MB', 
		'url'            => __('https://www.department.mb.tf.fau.de/', RRZE_TEXTDOMAIN),
		'parent'    => '1500000000'
	    ),
	    '1515000000'   => array(
		'title'          => __('Department Werkstoffwissenschaften', RRZE_TEXTDOMAIN),
		'url'            => __('https://www.ww.tf.fau.de', RRZE_TEXTDOMAIN),
		'parent'    => '1500000000'
	    ), 
	       '1518000000'   => array(
		'title'          => __('Department Artificial Intelligence in Biomedical Engineering', RRZE_TEXTDOMAIN),
		'url'            => __('https://www.aibe.tf.fau.de/', RRZE_TEXTDOMAIN),
		'parent'    => '1500000000'
	    ), 

	    '9900000000' => array(
		'title'		=> 'Externe Einrichtungen',
	    ),
	);
	return $data;
    }
    
    
    /*-----------------------------------------------------------------------------------*/
    /* Get FAU.ORG Data by orgnum
    /*-----------------------------------------------------------------------------------*/
    public function get_org($orgnum) {
	$fau_orga_breadcrumb_data = $this->fau_orga_data();
	

	if ((isset($orgnum)) && isset($fau_orga_breadcrumb_data[$orgnum])) {
        $fau_orga_breadcrumb_data[$orgnum]['orgnum'] = $orgnum;
        return $fau_orga_breadcrumb_data[$orgnum];
	}

	return false;
    }
    /*-----------------------------------------------------------------------------------*/
    /* Get FAU.ORG by faculty
    /*-----------------------------------------------------------------------------------*/
    public function get_fau_orga_fauorg_by_faculty( $faculty = '') {
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
    public function get_fau_orga_childs( $fauorg = '000000000') {
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