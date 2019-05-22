<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;

class Main
{
    /**
     * Main-Klasse wird instanziiert.
     */
    public function __construct()
    {
        //add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        //new Settings();
        new Shortcode();
    }

    /**
     * Enqueue der globale Skripte.
     */
    public function enqueue_scripts()
    {
        wp_register_style('rrze-jobs', plugins_url('assets/css/rrze-jobs.min.css', plugin_basename(RRZE_PLUGIN_FILE)));
    }
}
