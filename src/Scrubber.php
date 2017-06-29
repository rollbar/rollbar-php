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

    protected function tryGet($array, $key)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }

    protected function setScrubFields($config)
    {
        $fromConfig = $this->tryGet($config, 'scrubFields');
        if (!isset($fromConfig)) {
            $fromConfig = $this->tryGet($config, 'scrub_fields');
        }
        $this->scrubFields = self::$defaults->scrubFields($fromConfig);
    }

    public function getScrubFields()
    {
        return $this->scrubFields;
    }

    protected function setWhitelist($config)
    {
        $fromConfig = $this->tryGet($config, 'scrubWhitelist');
        if (!isset($fromConfig)) {
            $fromConfig = $this->tryGet($config, 'scrub_whitelist');
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
    public function scrub(&$data, $replacement = '*', $path = '')
    {
        $fields = $this->getScrubFields();

        if (!$fields || !$data) {
            return $data;
        }

        if (is_array($data)) { // scrub arrays
            $data = $this->scrubArray($data, $replacement, $path);
        } elseif (is_string($data)) { // scrub URLs and query strings
            $query = parse_url($data, PHP_URL_QUERY);
            if ($query) {
                $data = str_replace(
                    $query,
                    $this->scrubQueryString($query),
                    $data
                );
            }
        }
        return $data;
    }

    protected function scrubArray(&$arr, $replacement = '*', $path = '')
    {
        $fields = $this->getScrubFields();

        if (!$fields || !$arr) {
            return $arr;
        }

        $fields = array_flip($fields);
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

            if (isset($fields[$key])) {
                $val = str_repeat($replacement, 8);
            } else {
                $val = $scrubber->scrub($val, $replacement, $current);
            }

            $current = $parent;
        };

        array_walk($arr, $scrubberFn);

        return $arr;
    }

    protected function scrubQueryString($query, $replacement = 'x')
    {
        parse_str($query, $parsed);
        $scrubbed = $this->scrub($parsed, $replacement);
        return http_build_query($scrubbed);
    }
}
