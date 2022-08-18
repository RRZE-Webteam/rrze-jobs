<?php

namespace RRZE\Jobs;

defined('ABSPATH') || exit;

/**
 * Parse a HTML string with embedded interpolation expressions.
 *
 * Value interpolation: {{=value}}
 * Value to HTML entities interpolation: {{%unsafe_value}}
 * Mutidimensional value: {{=user.address.city}}
 * If blocks: {{value}} <<markup>> {{/value}}
 * If not blocks: {{!value}} <<markup>> {{/!value}}
 * If/else blocks: {{value}} <<markup>> {{:value}} <<alternate markup>> {{/value}}
 * Values iteration: {{@values}} {{=_key}}:{{=_val}} {{/@values}}
 */
class Parser
{
    /**
     * Block start delimiter.
     * @var string
     */
    protected $blockRegex = '/\\{\\{(([@!]?)(.+?))\\}\\}(([\\s\\S]+?)(\\{\\{:\\1\\}\\}([\\s\\S]+?))?)\\{\\{\\/\\1\\}\\}/';

    /**
     * Value interpolation delimiter.
     * @var string
     */
    protected $valRegex = '/\\{\\{([=%])(.+?)\\}\\}/';

    /**
     * Variables array.
     * @var array
     */
    protected $vars;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->vars = [];
    }

    /**
     * Convert special characters to HTML entities
     * @param  string $val [description]
     * @return string      [description]
     */
    public function convertToHtmlEntities($val)
    {
        return htmlspecialchars($val . '', ENT_QUOTES);
    }

    /**
     * Get a value from the variables array.
     * @param  string $index [description]
     * @return mixed        [description]
     */
    public function getValue($index)
    {
        $index = explode('.', $index);
        return $this->searchValue($index, $this->vars);
    }

    /**
     * Search a value in the variables array.
     * @param array $index  [description]
     * @param array $value [description]
     * @return mixed       [description]
     */
    protected function searchValue($index, $value)
    {
        if (
            is_array($index) &&
            !empty($index)
        ) {
            $currentIndex = array_shift($index);
        }
        if (
            is_array($index) &&
            !empty($index) &&
            isset($value[$currentIndex]) &&
            is_array($value[$currentIndex]) &&
            !empty($value[$currentIndex])
        ) {
            return $this->searchValue($index, $value[$currentIndex]);
        } else {
            $val = $value[$currentIndex] ?? '';
            return str_replace('{{', "{\f{", $val);
        }
    }

    /**
     * Match Tags
     * @param  array $matches
     * @return string
     */
    public function matchTags($matches)
    {
        $_key = $matches[0] ?? '';
        $_val = $matches[1] ?? '';
        $meta = $matches[2] ?? '';
        $key = $matches[3] ?? '';
        $expr = $matches[4] ?? '';
        $ifTrue = $matches[5] ?? '';
        $ifElse = $matches[6] ?? '';
        $ifFalse = $matches[7] ?? '';

        $val = $this->getValue($key);

        $temp = '';

        if (!$val) {
            // Check for if negation
            if ($meta == '!') {
                return $this->render($expr);
            }
            // Check for if else
            if ($ifElse) {
                return $this->render($ifFalse);
            }
            return '';
        }

        // Check for regular if expr
        if (!$meta) {
            return $this->render($ifTrue);
        }

        // Process array iteration
        if ($meta == '@') {
            // Store any previous vars by reusing existing vars
            $_key = $this->vars['_key'] ?? '';
            $_val = $this->vars['_val'] ?? '';

            foreach ($val as $i => $v) {
                $this->vars['_key'] = $i;
                $this->vars['_val'] = $v;

                $temp .= $this->render($expr);
            }

            $this->vars['_key'] = $_key;
            $this->vars['_val'] = $_val;

            return $temp;
        }
    }

    /**
     * Replace tags with values.
     * @param  array $matches [description]
     * @return string         [description]
     */
    public function replaceTags($matches)
    {
        $meta = $matches[1] ?? '';
        $key = $matches[2] ?? '';

        $val = $this->getValue($key);

        if ($val || $val === 0) {
            return $meta == '%' ? $this->convertToHtmlEntities($val) : $val;
        }

        return '';
    }

    /**
     * Render a string with embedded interpolation expressions.
     * @param  string $fragment [description]
     * @return mixed            [description]
     */
    protected function render($fragment)
    {
        $matchTags = preg_replace_callback($this->blockRegex, [$this, 'matchTags'], $fragment);
        $replaceTags = preg_replace_callback($this->valRegex, [$this, 'replaceTags'], $matchTags);

        return $replaceTags;
    }

    /**
     * Parse a string with embedded interpolation expressions.
     * @param  string $template [description]
     * @param  array $data      [description]
     * @return string           [description]
     */
    public function parse($templateFile, $data)
    {
        if (!is_readable($templateFile)) {
            return '';
        }
        ob_start();
        include($templateFile);
        $content = ob_get_clean();
        if (!$data) {
            return $content;
        }
        $this->vars = (array) $data;
        return $this->render($content);
    }
}
