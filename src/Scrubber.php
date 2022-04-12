<?php declare(strict_types=1);

namespace Rollbar;

class Scrubber implements ScrubberInterface
{
    protected static $defaults;
    protected $scrubFields;
    protected $safelist;

    public function __construct($config)
    {
        self::$defaults = Defaults::get();
        $this->setScrubFields($config);
        $this->setSafelist($config);
    }

    protected function setScrubFields($config)
    {
        $fromConfig = isset($config['scrubFields']) ? $config['scrubFields'] : null;
        if (!isset($fromConfig)) {
            $fromConfig = isset($config['scrub_fields']) ? $config['scrub_fields'] : null;
        }
        $this->scrubFields = self::$defaults->scrubFields($fromConfig);
    }

    public function getScrubFields()
    {
        return $this->scrubFields;
    }

    protected function setSafelist($config)
    {
        $fromConfig = isset($config['scrubSafelist']) ? $config['scrubSafelist'] : null;
        if (!isset($fromConfig)) {
            $fromConfig = isset($config['scrub_safelist']) ? $config['scrub_safelist'] : null;
        }
        $this->safelist = $fromConfig ? $fromConfig : array();
    }

    public function getSafelist()
    {
        return $this->safelist;
    }

    /**
     * Scrub a data structure including arrays and query strings.
     *
     * @param mixed $data Data to be scrubbed.
     * @param array $fields Sequence of field names to scrub.
     * @param string $replacement Character used for scrubbing.
     * @param string $path Path of traversal in the array
     */
    public function scrub(&$data, $replacement = '********', $path = '')
    {
        $fields = $this->getScrubFields();

        if (!$fields || !$data) {
            return $data;
        }

        // Scrub fields is case insensitive, so force all fields to lowercase
        $fields = array_change_key_case(array_flip($fields), CASE_LOWER);

        return $this->internalScrub($data, $fields, $replacement, $path);
    }

    public function internalScrub(&$data, $fields, $replacement, $path)
    {
        if (is_array($data)) {
// scrub arrays
            $data = $this->scrubArray($data, $fields, $replacement, $path);
        } elseif (is_string($data)) {
// scrub URLs and query strings
            $query = parse_url($data, PHP_URL_QUERY);
            if ($query) {
                $data = str_replace(
                    $query,
                    $this->scrubQueryString($query, $fields),
                    $data
                );
            } else {
                // PHP reports warning if parse_str() detects more than max_input_vars items.
                @parse_str($data, $parsedData);
                if (http_build_query($parsedData) === $data) {
                    $data = $this->scrubQueryString($data, $fields);
                }
            }
        }
        return $data;
    }

    protected function scrubArray(&$arr, $fields, $replacement = '********', $path = '')
    {
        if (!$fields || !$arr) {
            return $arr;
        }

        $scrubber = $this;
        $scrubberFn = function (
            &$val,
            $key
        ) use (
            $fields,
            $replacement,
            &$scrubberFn,
            $scrubber,
            &$path
        ) {
            $parent = $path;
            $current = !$path ? $key : $path . '.' . $key;

            if (in_array($current, $scrubber->getSafelist())) {
                return;
            }

            // $key may be an integer (proper), such as when scrubbing
            // backtraces -- coerce to string to satisfy strict types
            if (isset($fields[strtolower((string)$key)])) {
                $val = $replacement;
            } else {
                $val = $scrubber->internalScrub($val, $fields, $replacement, $current);
            }

            $current = $parent;
        };

        array_walk($arr, $scrubberFn);

        return $arr;
    }

    protected function scrubQueryString($query, $fields, $replacement = 'xxxxxxxx')
    {
        // PHP reports warning if parse_str() detects more than max_input_vars items.
        @parse_str($query, $parsed);
        $scrubbed = $this->internalScrub($parsed, $fields, $replacement, '');
        return http_build_query($scrubbed);
    }
}
