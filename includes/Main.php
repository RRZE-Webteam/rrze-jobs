<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;

use RRZE\Jobs\Settings;

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

   //     remove_filter('the_content', 'wpautop');
        // add_filter('the_content', 'wpautop', 12);
        // add_filter('the_content', 'wpautop', 99);
        // add_filter('the_content', 'shortcode_unautop', 100);
        
        
        add_filter('the_content', [$this, 'removeEmptyParagraphs']);

    }

    public function onLoaded() {
        // Settings-Klasse wird instanziiert.
        $settings = new Settings($this->pluginFile);
        $settings->onLoaded();

        // Shortcode wird eingebunden.
        $shortcode = new Shortcode($this->pluginFile, $settings);
    }

    public static function removeEmptyParagraphs($content) {
        $pattern = "/<p[^>]>\s*<\/p[^>]>/";
        $content = preg_replace($pattern, '', $content);
        $content = str_replace("<p></p>", "", $content);
        return $content;
    }
}
