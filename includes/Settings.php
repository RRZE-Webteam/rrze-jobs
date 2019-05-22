<?php

namespace RRZE\Jobs;

use RRZE\Jobs\Main;

defined('ABSPATH') || exit;

class Settings
{
    /**
     * Optionsname
     * @var string
     */
    protected $option_name;

    /**
     * Einstellungsoptionen
     * @var object
     */
    protected $options;

    /**
     * "Screen ID" der Einstellungsseite.
     * @var string
     */
    protected $admin_settings_page;

    /**
     * Settings-Klasse wird instanziiert.
     */
    public function __construct()
    {
        $this->option_name = Options::get_option_name();
        $this->options = Options::get_options();

        add_action('admin_menu', [$this, 'admin_settings_page']);
        add_action('admin_init', [$this, 'admin_settings']);

        add_filter('plugin_action_links_' . plugin_basename(RRZE_PLUGIN_FILE), [$this, 'plugin_action_link']);
    }

    /**
     * Füge einen Einstellungslink hinzu, der auf der Plugins-Seite angezeigt wird.
     * @param  array $links Linkliste
     * @return array        zusammengeführte Liste von Links
     */
    public function plugin_action_link($links)
    {
        if (! current_user_can('manage_options')) {
            return $links;
        }
        return array_merge($links, array(sprintf('<a href="%s">%s</a>', add_query_arg(array('page' => 'rrze-jobs'), admin_url('options-general.php')), __('Settings', 'rrze-jobs'))));
    }

    /**
     * Füge eine Einstellungsseite in das Menü "Einstellungen" hinzu.
     */
    public function admin_settings_page()
    {
        $this->admin_settings_page = add_options_page(__('RRZE Jobs', 'rrze-jobs'), __('RRZE Jobs', 'rrze-jobs'), 'manage_options', 'rrze-jobs', [$this, 'settings_page']);
        add_action('load-' . $this->admin_settings_page, [$this, 'admin_help_menu']);
    }

    /**
     * Die Ausgabe der Einstellungsseite.
     */
    public function settings_page()
    {
        ?>
        <div class="wrap">
            <h2><?php echo __('Settings &rsaquo; RRZE Jobs', 'rrze-jobs'); ?></h2>
            <form method="post" action="options.php">
            <?php
            settings_fields('rrze_jobs_options');
            do_settings_sections('rrze_jobs_options');
            submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Legt die Einstellungen der Einstellungsseite fest.
     */
    public function admin_settings()
    {
        register_setting('rrze_jobs_options', $this->option_name, [$this, 'options_validate']);
        add_settings_section('rrze_jobs_section_1', false, '__return_false', 'rrze_jobs_options');
        add_settings_field('rrze_jobs_field_1', __('Field 1', 'rrze-jobs'), [$this, 'rrze_jobs_field_1'], 'rrze_jobs_options', 'rrze_jobs_section_1');
    }

    /**
     * Validiert die Eingabe der Einstellungsseite.
     * @param array $input
     * @return array
     */
    public function options_validate($input)
    {
        $input['rrze_jobs_text'] = !empty($input['rrze_jobs_field_1']) ? $input['rrze_jobs_field_1'] : '';
        return $input;
    }

    /**
     * Erstes Feld der Einstellungsseite.
     */
    public function rrze_jobs_field_1()
    {
        ?>
        <input type='text' name="<?php printf('%s[rrze_jobs_field_1]', $this->option_name); ?>" value="<?php echo $this->options->rrze_jobs_field_1; ?>">
        <?php
    }

    /**
     * Erstellt die Kontexthilfe der Einstellungsseite.
     * @return void
     */
    public function admin_help_menu()
    {
        $content = [
            '<p>' . __('Here comes the Context Help content.', 'rrze-jobs') . '</p>',
        ];


        $help_tab = [
            'id' => $this->admin_settings_page,
            'title' => __('Overview', 'rrze-jobs'),
            'content' => implode(PHP_EOL, $content),
        ];

        $help_sidebar = sprintf('<p><strong>%1$s:</strong></p><p><a href="http://blogs.fau.de/webworking">RRZE-Webworking</a></p><p><a href="https://gitlab.rrze.fau.de/rrze-webteam">%2$s</a></p>', __('For more information', 'rrze-jobs'), __('RRZE Webteam on Github', 'rrze-jobs'));

        $screen = get_current_screen();

        if ($screen->id != $this->admin_settings_page) {
            return;
        }

        $screen->add_help_tab($help_tab);

        $screen->set_help_sidebar($help_sidebar);
    }
}
