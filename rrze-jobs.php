<?php

/*
Plugin Name:     RRZE Jobs
Plugin URI:      https://gitlab.rrze.fau.de/rrze-webteam/rrze-jobs
Description:     Embedding job offers from job portal apis via shortcode
Version:         3.4.21
Author:          RRZE Webteam
Author URI:      https://blogs.fau.de/webworking/
License:         GNU General Public License v3
License URI:     http://www.gnu.org/licenses/gpl-2.0.html
Text Domain:     rrze-jobs
 */

namespace RRZE\Jobs;

/*
Die Codezeile defined('ABSPATH') || exit;
verhindert den direkten Zugriff auf die PHP-Dateien über URL und stellt sicher,
dass die Plugin-Dateien nur innerhalb der WordPress-Umgebung ausgeführt werden.
Denn wenn bspw. eine Datei I/O-Operationen enthält,
kann sie schließlich kompromittiert werden (durch einen Angreifer),
was zu unerwartetem Verhalten führen kann.
 */
defined('ABSPATH') || exit;

require_once 'config/config.php';

use RRZE\Jobs\Main;

const RRZE_PHP_VERSION = '7.4';
const RRZE_WP_VERSION = '6.0';
const RRZE_TEXTDOMAIN = 'rrze-jobs';
const RRZE_PLUGIN_FILE = __FILE__;

// Automatische Laden von Klassen.
spl_autoload_register(function ($class) {
    $prefix = __NAMESPACE__;
    $base_dir = __DIR__ . '/includes';

    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Registriert die Plugin-Funktion, die bei Aktivierung des Plugins ausgeführt werden soll.
register_activation_hook(__FILE__, __NAMESPACE__ . '\activation');
// Registriert die Plugin-Funktion, die ausgeführt werden soll, wenn das Plugin deaktiviert wird.
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivation');
// Wird aufgerufen, sobald alle aktivierten Plugins geladen wurden.
add_action('plugins_loaded', __NAMESPACE__ . '\loaded');

/**
 * Einbindung der Sprachdateien.
 */
function load_textdomain() {
    load_plugin_textdomain('rrze-jobs', false, sprintf('%s/languages/', dirname(plugin_basename(__FILE__))));
}

/**
 * Überprüft die minimal erforderliche PHP- u. WP-Version.
 */
function system_requirements() {
    $error = '';
    if (version_compare(PHP_VERSION, RRZE_PHP_VERSION, '<')) {
        /* Übersetzer: 1: aktuelle PHP-Version, 2: erforderliche PHP-Version */
        $error = sprintf(__('The server is running PHP version %1$s. The Plugin requires at least PHP version %2$s.', 'rrze-jobs'), PHP_VERSION, RRZE_PHP_VERSION);
    } elseif (version_compare($GLOBALS['wp_version'], RRZE_WP_VERSION, '<')) {
        /* Übersetzer: 1: aktuelle WP-Version, 2: erforderliche WP-Version */
        $error = sprintf(__('The server is running WordPress version %1$s. The Plugin requires at least WordPress version %2$s.', 'rrze-jobs'), $GLOBALS['wp_version'], RRZE_WP_VERSION);
    }
    return $error;
}

/**
 * Wird durchgeführt, nachdem das Plugin aktiviert wurde.
 */
function activation() {
    // Sprachdateien werden eingebunden.
    load_textdomain();

    // Überprüft die minimal erforderliche PHP- u. WP-Version.
    // Wenn die Überprüfung fehlschlägt, dann wird das Plugin automatisch deaktiviert.
    if ($error = system_requirements()) {
        deactivate_plugins(plugin_basename(__FILE__), false, true);
        wp_die($error);
    }

    // Ab hier können die Funktionen hinzugefügt werden,
    // die bei der Aktivierung des Plugins aufgerufen werden müssen.
    // Bspw. wp_schedule_event, flush_rewrite_rules, etc.
}

/**
 * Wird durchgeführt, nachdem das Plugin deaktiviert wurde.
 */
function deactivation() {
    // Hier können die Funktionen hinzugefügt werden, die
    // bei der Deaktivierung des Plugins aufgerufen werden müssen.
    // Bspw. delete_option, wp_clear_scheduled_hook, flush_rewrite_rules, etc.

    // delete_option(Options::get_option_name());
}
/**
 * Instantiate Plugin class.
 * @return object Plugin
 */
function plugin() {
    static $instance;
    if (null === $instance) {
        $instance = new Plugin(__FILE__);
    }

    return $instance;
}
/**
 * Wird durchgeführt, nachdem das WP-Grundsystem hochgefahren
 * und alle Plugins eingebunden wurden.
 */
function loaded() {
    // Sprachdateien werden eingebunden.
    load_textdomain();
    plugin()->loaded();
    // Überprüft die minimal erforderliche PHP- u. WP-Version.
    if ($error = system_requirements()) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugin_data = get_plugin_data(__FILE__);
        $plugin_name = $plugin_data['Name'];
        $tag = is_network_admin() ? 'network_admin_notices' : 'admin_notices';
        add_action($tag, function () use ($plugin_name, $error) {
            printf('<div class="notice notice-error"><p>%1$s: %2$s</p></div>', esc_html($plugin_name), esc_html($error));
        });
    } else {
        // Hauptklasse (Main) wird instanziiert.
        $main = new Main(__FILE__);
        $main->onLoaded();
    }
}

