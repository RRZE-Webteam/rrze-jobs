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

    /**
     * Enqueue der globale Skripte.
     */
    public function enqueue_scripts()  {
        wp_register_style('rrze-jobs', plugins_url('assets/css/rrze-jobs.css', plugin_basename(RRZE_PLUGIN_FILE)));
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
