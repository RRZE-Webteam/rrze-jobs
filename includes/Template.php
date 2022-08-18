<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;

class Template {

    /**
     * Get the template content.
     * @param string $template
     * @param array $data
     */
    public static function getContent($template = '', $data = [])  {
        return self::parseContent($template, $data);
    }

    /**
     * Parses the content of the template with the data provided.
     * @param  string $template
     * @param  array  $data
     * @return string
     */
    protected static function parseContent($template, $data)  {
        $templateFile = self::getTemplateLocale($template);
        if ($templateFile) {
            $parser = new Parser();
            return $parser->parse($templateFile, $data);
        }
        return '';
    }

    /**
     * Load the locale template file
     * @param  string $templateFile
     * @return string
     */
    protected static function getTemplateLocale($templateFile)  {
        return is_readable($templateFile) ? $templateFile : '';
    }

    
}
