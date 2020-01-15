<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;

use RRZE\Jobs\Settings;
use RRZE\Jobs\TinyMCEButtons;


class Main {
    /**
     * Der vollständige Pfad- und Dateiname der Plugin-Datei.
     * @var string
     */
    protected $pluginFile;

    /**
     * Main-Klasse wird instanziiert.
     */
    public function __construct($pluginFile) {
        $this->pluginFile = $pluginFile;

        remove_filter('the_content', 'wpautop');
        add_filter('the_content', 'wpautop', 12);

        new TinyMCEButtons();
    }

    public function onLoaded() {
        // Settings-Klasse wird instanziiert.
        $settings = new Settings($this->pluginFile);
        $settings->onLoaded();
  
        // Shortcode wird eingebunden.
        include 'Shortcode.php';

        $shortcode = new Shortcode();
        
      }

}
