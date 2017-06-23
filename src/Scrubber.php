<?php namespace Rollbar;

class Scrubber implements ScrubberInterface
{
    protected static $defaults;
    protected $scrubFields;

    public function __construct($config)
    {
        self::$defaults = Defaults::get();
        $this->setScrubFields($config);
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
    
    /**
     * Scrub a data structure including arrays and query strings.
     *
     * @param mixed $data Data to be scrubbed.
     * @param array $fields Sequence of field names to scrub.
     * @param string $replacement Character used for scrubbing.
     */
    public function scrub(&$data, $replacement = '*')
    {
        $fields = $this->getScrubFields();
        
        if (!$fields || !$data) {
            return $data;
        }
        
        if (is_array($data)) { // scrub arrays
            $data = $this->scrubArray($data, $replacement);
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

    protected function scrubArray(&$arr, $replacement = '*')
    {
        $fields = $this->getScrubFields();
        
        if (!$fields || !$arr) {
            return $arr;
        }
        
        $scrubber = $this;

        $scrubberFn = function (&$val, $key) use ($fields, $replacement, &$scrubberFn, $scrubber) {
            if (in_array($key, $fields, true)) {
                $val = str_repeat($replacement, 8);
            } else {
                $val = $scrubber->scrub($val, $replacement);
            }
        };

        array_walk($arr, $scrubber);

        return $arr;
    }

    protected function scrubQueryString($query, $replacement = 'x')
    {
        parse_str($query, $parsed);
        $scrubbed = $this->scrub($parsed, $replacement);
        return http_build_query($scrubbed);
    }
}
