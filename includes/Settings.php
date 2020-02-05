<?php

namespace RRZE\Jobs;

use RRZE\Jobs\Main;

defined('ABSPATH') || exit;

use function RRZE\Jobs\Config\getMenuSettings;
use function RRZE\Jobs\Config\getHelpTab;
use function RRZE\Jobs\Config\getSections;
use function RRZE\Jobs\Config\getFields;

/**
 * Settings-Klasse
 */
class Settings {
    /**
     * Der vollständige Pfad- und Dateiname der Plugin-Datei.
     * @var string
     */
    protected $pluginFile;

    /**
     * Optionsname
     * @var string
     */
    protected $optionName;

    /**
     * Einstellungsoptionen
     * @var array
     */
    protected $options;

    /**
     * Settings-Menü
     * @var array
     */
    protected $settingsMenu;

    /**
     * Settings-Bereiche
     * @var array
     */
    protected $settingsSections;

    /**
     * Settings-Felder
     * @var array
     */
    protected $settingsFields;

    /**
     * Alle Registerkarte
     * @var array
     */
    protected $allTabs = [];

    /**
     * Standard-Registerkarte
     * @var string
     */
    protected $defaultTab = '';

    /**
     * Aktuelle Registerkarte
     * @var string
     */
    protected $currentTab = '';
    
    /**
     * Settings für mime types link icons folgen:
     */

     
     /**
      * @var array	Default option values - this array will be enriched by the enrich_default_settings() method
      * @todo		IMPORTANT: For now, on change in default size, type or alignment, also copy
      *				the new defaults to style.php
      */
     public $defaults = array(
            );
    
     

    /**
     * Variablen Werte zuweisen.
     * @param string $pluginFile [description]
     */
    public function __construct($pluginFile) {
        $this->pluginFile = $pluginFile;
    }

    /**
     * Er wird ausgeführt, sobald die Klasse instanziiert wird.
     * @return void
     */
    public function onLoaded() {
        $this->setMenu();
        $this->setSections();
        $this->setFields();
        $this->setTabs();

        $this->optionName = RRZE_JOBS_TEXTDOMAIN;
        $this->options = $this->getOptions();

        add_action('admin_init', [$this, 'adminInit']);
        add_action('admin_menu', [$this, 'adminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
    }

    protected function setMenu() {
      $this->settingsMenu = getMenuSettings();
    }

    /**
     * Einstellungsbereiche einstellen.
     */
    protected function setSections() {
      $this->settingsSections = getSections();
    }

    /**
     * Einen einzelnen Einstellungsbereich hinzufügen.
     * @param array   $section
     */
    protected function addSection($section) {
      $this->settingsSections[] = $section;
    }

    /**
     * Einstellungsfelder einstellen.
     */
    protected function setFields() {
      $this->settingsFields = getFields();
    }

    /**
     * Ein einzelnes Einstellungsfeld hinzufügen.
     * @param [type] $section [description]
     * @param [type] $field   [description]
     */
    protected function addField($section, $field) {
      $defaults = array(
            'name'  => '',
            'label' => '',
            'desc'  => '',
            'type'  => 'text'
      );

      $arg = wp_parse_args($field, $defaults);
      $this->settingsFields[$section][] = $arg;
    }

    /**
     * Gibt die Standardeinstellungen zurück.
     * @return array
     */
    protected function defaultOptions() {
      $options = [];

      foreach ($this->settingsFields as $section => $field) {
        foreach ($field as $option) {
          $name = $option['name'];
          $default = isset($option['default']) ? $option['default'] : '';
          $options = array_merge($options, [$section . '_' . $name => $default]);
        }
      }

      return $options;
    }

    /**
     * Gibt die Einstellungen zurück.
     * @return array
     */
    public function getOptions() {
      $defaults = $this->defaultOptions();

      $options = (array) get_option($this->optionName);
      $options = wp_parse_args($options, $defaults);
      $options = array_intersect_key($options, $defaults);

      return $options;
    }

    /**
     * Gibt den Wert eines Einstellungsfelds zurück.
     * @param string  $name  settings field name
     * @param string  $section the section name this field belongs to
     * @param string  $default default text if it's not found
     * @return string
     */
    public function getOption($section, $name, $default = '') {
        $option = $section . '_' . $name;

        if (isset($this->options[$option])) {
            return $this->options[$option];
        }

        return $default;
    }

    /**
     * Sanitize-Callback für die Optionen.
     * @return mixed
     */
    public function sanitizeOptions($options) {
        if (!$options) {
            return $options;
        }

        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
            $sanitizeCallback = $this->getSanitizeCallback($key);
            if ($sanitizeCallback) {
                $this->options[$key] = call_user_func($sanitizeCallback, $value);
            }
        }

        return $this->options;
    }

    /**
     * Gibt die Sanitize-Callback-Funktion für die angegebene Option-Key.
     * @param string $key Option-Key
     * @return mixed string oder (bool) false
     */
    protected function getSanitizeCallback($key = '') {
        if (empty($key)) {
            return false;
        }

        foreach ($this->settingsFields as $section => $options) {
            foreach ($options as $option) {
                if ($section . '_' . $option['name'] != $key) {
                    continue;
                }

                return isset($option['sanitize_callback']) && is_callable($option['sanitize_callback']) ? $option['sanitize_callback'] : false;
            }
        }

        return false;
    }

    /**
     * Einstellungsbereiche als Registerkarte anzeigen.
     * Zeigt alle Beschriftungen der Einstellungsbereiche als Registerkarte an.
     */
    public function showTabs() {
        $html = '<h1>' . $this->settingsMenu['title'] . '</h1>' . PHP_EOL;

        if (count($this->settingsSections) < 2) {
            return;
        }

        $html .= '<h2 class="nav-tab-wrapper wp-clearfix">';

        foreach ($this->settingsSections as $section) {
            $class = $section['id'] == $this->currentTab ? 'nav-tab-active' : $this->defaultTab;
            $html .= sprintf(
                '<a href="?page=%4$s&current-tab=%1$s" class="nav-tab %3$s" id="%1$s-tab">%2$s</a>',
                esc_attr($section['id']),
                $section['title'],
                esc_attr($class),
                $this->settingsMenu['menu_slug']
            );
        }

        $html .= '</h2>' . PHP_EOL;

        echo $html;
    }

    /**
     * Anzeigen der Einstellungsbereiche.
     * Zeigt für jeden Einstellungsbereich das entsprechende Formular an.
     */
    public function showSections() {
        foreach ($this->settingsSections as $section) {
            if ($section['id'] != $this->currentTab) {
                continue;
            } ?>
            <div id="<?php echo $section['id']; ?>">
                <form method="post" action="options.php">
                    <?php settings_fields($section['id']); ?>
                    <?php do_settings_sections($section['id']); ?>
                    <?php submit_button(); ?>
                </form>
            </div>
        <?php
        }
    }

    /**
     * Optionen Seitenausgabe
     */
    public function pageOutput() {
        echo '<div class="wrap">', PHP_EOL;
        $this->showTabs();
        $this->showSections();
        echo '</div>', PHP_EOL;
    }

    /**
     * Erstellt die Kontexthilfe der Einstellungsseite.
     */
    public function adminHelpTab() {
        $screen = get_current_screen();

        if (!method_exists($screen, 'add_help_tab') || $screen->id != $this->optionsPage) {
            return;
        }

        $helpTab = getHelpTab();

        if (empty($helpTab)) {
            return;
        }

        foreach ($helpTab as $help) {
            $screen->add_help_tab(
                [
                    'id' => $help['id'],
                    'title' => $help['title'],
                    'content' => implode(PHP_EOL, $help['content'])
                ]
            );
            $screen->set_help_sidebar($help['sidebar']);
        }
    }

    /**
     * Initialisierung und Registrierung der Bereiche und Felder.
     */
    public function adminInit() {
        // Hinzufügen von Einstellungsbereichen
        foreach ($this->settingsSections as $section) {
            if (isset($section['desc']) && !empty($section['desc'])) {
                $section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';
                $callback = function () use ($section) {
                    echo str_replace('"', '\"', $section['desc']);
                };
            } elseif (isset($section['callback'])) {
                $callback = $section['callback'];
            } else {
                $callback = null;
            }

            add_settings_section($section['id'], $section['title'], $callback, $section['id']);
        }

        // Hinzufügen von Einstellungsfelder
        foreach ($this->settingsFields as $section => $field) {
            foreach ($field as $option) {
                $name = $option['name'];
                $type = isset($option['type']) ? $option['type'] : 'text';
                $label = isset($option['label']) ? $option['label'] : '';
                $callback = isset($option['callback']) ? $option['callback'] : [$this, 'callback' . ucfirst($type)];

                $args = [
                    'id' => $name,
                    'class' => isset($option['class']) ? $option['class'] : $name,
                    'label_for' => "{$section}[{$name}]",
                    'desc' => isset($option['desc']) ? $option['desc'] : '',
                    'name' => $label,
                    'section' => $section,
                    'size' => isset($option['size']) ? $option['size'] : null,
                    'options' => isset($option['options']) ? $option['options'] : '',
                    'default' => isset($option['default']) ? $option['default'] : '',
                    'sanitize_callback' => isset($option['sanitize_callback']) ? $option['sanitize_callback'] : '',
                    'type' => $type,
                    'placeholder' => isset($option['placeholder']) ? $option['placeholder'] : '',
                    'min' => isset($option['min']) ? $option['min'] : '',
                    'max' => isset($option['max']) ? $option['max'] : '',
                    'step' => isset($option['step']) ? $option['step'] : '',
                ];

                add_settings_field("{$section}[{$name}]", $label, $callback, $section, $section, $args);

                if (in_array($type, ['color', 'file'])) {
                    add_action('admin_enqueue_scripts', [$this, $type . 'EnqueueScripts']);
                }
            }
        }

        // Registrieren der Einstellungen
        foreach ($this->settingsSections as $section) {
            register_setting($section['id'], $this->optionName, [$this, 'sanitizeOptions']);
        }
    }

    /**
     * Hinzufügen der Optionen-Seite
     * @return void
     */
    public function adminMenu() {
        $this->optionsPage = add_options_page(
            $this->settingsMenu['page_title'],
            $this->settingsMenu['menu_title'],
            $this->settingsMenu['capability'],
            $this->settingsMenu['menu_slug'],
            [$this, 'pageOutput']
        );

        add_action('load-' . $this->optionsPage, [$this, 'adminHelpTab']);
    }

    /**
     * Registerkarten einstellen
     */
    protected function setTabs() {
        foreach ($this->settingsSections as $key => $val) {
            if ($key == 0) {
                $this->defaultTab = $val['id'];
            }
            $this->allTabs[] = $val['id'];
        }

        $this->currentTab = array_key_exists('current-tab', $_GET) && in_array($_GET['current-tab'], $this->allTabs) ? $_GET['current-tab'] : $this->defaultTab;
    }

    /**
     * Enqueue Skripte und Style
     * @return void
     */
    public function adminEnqueueScripts() {
    //   wp_register_script('icons-settings', plugins_url('assets/js/icons.min.js', plugin_basename($this->pluginFile)));
    //   wp_enqueue_script('icons-settings');
      wp_enqueue_script('jquery');
    }


    /**
     * Gibt die Feldbeschreibung des Einstellungsfelds zurück.
     * @param array   $args Argumente des Einstellungsfelds
     */
    public function getFieldDescription($args) {
        if (! empty($args['desc'])) {
            $desc = sprintf('<p class="description">%s</p>', $args['desc']);
        } else {
            $desc = '';
        }

        return $desc;
    }

    
    /**
     * Zeigt ein Kontrollkästchen (Checkbox) für ein Einstellungsfeld an.
     * @param array   $args Argumente des Einstellungsfelds
     */
    public function callbackCheckbox($args) {
        $value = esc_attr($this->getOption($args['section'], $args['id'], $args['default']));

        $html = '<fieldset>';
        $html .= sprintf(
            '<label for="%1$s-%2$s">',
            $args['section'],
            $args['id']
        );
        $html .= sprintf(
            '<input type="hidden" name="%1$s[%2$s_%3$s]" value="off">',
            $this->optionName,
            $args['section'],
            $args['id']
        );
        $html .= sprintf(
            '<input type="checkbox" class="checkbox" id="%2$s-%3$s" name="%1$s[%2$s_%3$s]" value="on" %4$s>',
            $this->optionName,
            $args['section'],
            $args['id'],
            checked($value, 'on', false)
        );
        $html .= sprintf(
            '%1$s</label>',
            $args['desc']
        );
        $html .= '</fieldset>';

        echo $html;
    }

    /**
     * Zeigt ein Multicheckbox für ein Einstellungsfeld an.
     * @param array   $args Argumente des Einstellungsfelds
     */
    public function callbackMulticheck($args) {
        $value = $this->getOption($args['section'], $args['id'], $args['default']);
        $html = '<fieldset>';
        $html .= sprintf(
            '<input type="hidden" name="%1$s[%2$s_%3$s]" value="">',
            $this->optionName,
            $args['section'],
            $args['id']
        );
        foreach ($args['options'] as $key => $label) {
            $checked = isset($value[$key]) ? $value[$key] : '0';
            $html .= sprintf(
                '<label for="%1$s-%2$s-%3$s">',
                $args['section'],
                $args['id'],
                $key
            );
            $html .= sprintf(
                '<input type="checkbox" class="checkbox" id="%2$s-%3$s-%4$s" name="%1$s[%2$s_%3$s][%4$s]" value="%4$s" %5$s>',
                $this->optionName,
                $args['section'],
                $args['id'],
                $key,
                checked($checked, $key, false)
            );
            $html .= sprintf('%1$s</label><br>', $label);
        }

        $html .= $this->getFieldDescription($args);
        $html .= '</fieldset>';

        echo $html;
    }


    /**
     * Zeigt ein Textfeld für ein Einstellungsfeld an.
     * @param array   $args Argumente des Einstellungsfelds
     */
    public function callbackTextarea($args)
    {
        $value = esc_textarea($this->getOption($args['section'], $args['id'], $args['default']));
        $size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
        $placeholder = empty($args['placeholder']) ? '' : ' placeholder="' . $args['placeholder'] . '"';

        $html = sprintf(
            '<textarea rows="5" cols="55" class="%1$s-text" id="%3$s-%4$s" name="%2$s[%3$s_%4$s]"%5$s>%6$s</textarea>',
            $size,
            $this->optionName,
            $args['section'],
            $args['id'],
            $placeholder,
            $value
        );
        $html .= $this->getFieldDescription($args);

        echo $html;
    }

    /**
     * Zeigt einen Auswahlknopf (Radio-Button) für ein Einstellungsfeld an.
     * @param array   $args Argumente des Einstellungsfelds
     */
    public function callbackRadio($args) {
        $value = $this->getOption($args['section'], $args['id'], $args['default']);
        $html  = '<fieldset>';

        foreach ($args['options'] as $key => $label) {
            $html .= sprintf(
                '<label for="%1$s-%2$s-%3$s">',
                $args['section'],
                $args['id'],
                $key
            );
            $html .= sprintf(
                '<input type="radio" class="radio" id="%2$s-%3$s-%4$s" name="%1$s[%2$s_%3$s]" value="%4$s" %5$s>',
                $this->optionName,
                $args['section'],
                $args['id'],
                $key,
                checked($value, $key, false)
            );
            $html .= sprintf(
                '%1$s</label><br>',
                $label
            );
        }

        $html .= $this->getFieldDescription($args);
        $html .= '</fieldset>';

        echo $html;
    }

    /**
     * Zeigt ein Textfeld für ein Einstellungsfeld an.
     * @param array   $args Argumente des Einstellungsfelds
     */
    public function callbackText($args)
    {
        $value = esc_attr($this->getOption($args['section'], $args['id'], $args['default']));
        $size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
        $type = isset($args['type']) ? $args['type'] : 'text';
        $placeholder = empty($args['placeholder']) ? '' : ' placeholder="' . $args['placeholder'] . '"';

        $html = sprintf(
            '<input type="%1$s" class="%2$s-text" id="%4$s-%5$s" name="%3$s[%4$s_%5$s]" value="%6$s"%7$s>',
            $type,
            $size,
            $this->optionName,
            $args['section'],
            $args['id'],
            $value,
            $placeholder
        );
        $html .= $this->getFieldDescription($args);

        echo $html;
    }

    /**
     * Zeigt eine Auswahlliste (Selectbox) für ein Einstellungsfeld an.
     * @param array   $args Argumente des Einstellungsfelds
     */
    public function callbackSelect($args) {
        $value = esc_attr($this->getOption($args['section'], $args['id'], $args['default']));
        $size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
        $html  = sprintf(
            '<select class="%1$s" id="%3$s-%4$s" name="%2$s[%3$s_%4$s]">',
            $size,
            $this->optionName,
            $args['section'],
            $args['id']
        );

        foreach ($args['options'] as $key => $label) {
            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                $key,
                selected($value, $key, false),
                $label
            );
        }

        $html .= sprintf('</select>');
        $html .= $this->getFieldDescription($args);

        echo $html;
    }

    /**
     * Zeigt eine Auswahlliste (Selectbox) für ein Einstellungsfeld an.
     * @param array   $args Argumente des Einstellungsfelds
     */
    public function callbackSelectPage($args) {
        $value = esc_attr($this->getOption($args['section'], $args['id'], $args['default']));
        $size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
        $select_args = [
            'selected'  => $value,
            'echo'      => 0,
            'name'      => $this->optionName.'['.$args['section'].'_'.$args['id'].']',
            'id'        => $args['section'].'-'.$args['id'],
            'class'     => $size
        ];

        $html = wp_dropdown_pages($select_args);
        $html .= $this->getFieldDescription($args);

        echo $html;
    }
}
