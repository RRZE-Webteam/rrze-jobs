<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;

class Options
{
    /**
     * Optionsname
     * @var string
     */
    protected static $option_name = 'rrze_jobs';

    /**
     * Standard Einstellungen werden definiert
     * @return array
     */
    protected static function default_options()
    {
        $options = [
            'rrze_jobs_field_1' => '',
            // Hier können weitere Felder ('key' => 'value') angelegt werden.
        ];

        return $options;
    }

    /**
     * Gibt die Einstellungen zurück.
     * @return object
     */
    public static function get_options()
    {
        $defaults = self::default_options();

        $options = (array) get_option(self::$option_name);
        $options = wp_parse_args($options, $defaults);
        $options = array_intersect_key($options, $defaults);

        return (object) $options;
    }

    /**
     * Gibt den Namen der Option zurück.
     * @return string
     */
    public static function get_option_name()
    {
        return self::$option_name;
    }
}
