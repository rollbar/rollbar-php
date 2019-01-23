<?php namespace Rollbar;

class Scrubber implements ScrubberInterface
{
    protected static $defaults;
    protected $scrubFields;
    protected $whitelist;

    public function __construct($config)
    {
        self::$defaults = Defaults::get();
        $this->setScrubFields($config);
        $this->setWhitelist($config);
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

    protected function setWhitelist($config)
    {
        $fromConfig = isset($config['scrubWhitelist']) ? $config['scrubWhitelist'] : null;
        if (!isset($fromConfig)) {
            $fromConfig = isset($config['scrub_whitelist']) ? $config['scrub_whitelist'] : null;
        }
        $this->whitelist = $fromConfig ? $fromConfig : array();
    }

    public function getWhitelist()
    {
        return $this->whitelist;
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
                parse_str($data, $parsedData);
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

            if (in_array($current, $scrubber->getWhitelist())) {
                return;
            }

            if (isset($fields[strtolower($key)])) {
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
        parse_str($query, $parsed);
        $scrubbed = $this->internalScrub($parsed, $fields, $replacement, '');
        return http_build_query($scrubbed);
    }
}
