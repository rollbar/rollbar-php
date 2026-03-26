<?php declare(strict_types=1);

namespace Rollbar;

/**
 * The Scrubber class removes protected or sensitive data and PII from the payload before it is sent over the wire to
 * the Rollbar Service. It can be configured with the 'scrub_fields' and 'scrub_safelist' configs.
 */
class Scrubber implements ScrubberInterface
{
    /**
     * A list of field names to scrub data from.
     *
     * @var string[]
     */
    protected array $scrubFields;

    /**
     * A list of fields to NOT scrub data from. Each field should be a '.' delimited list of nested keys.
     *
     * @var string[]
     */
    protected array $safelist;

    /**
     * Sets up and configures the Scrubber from the array of configs.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->setScrubFields($config);
        $this->setSafelist($config);
    }

    /**
     * Sets the fields to scrub from the configs array.
     *
     * @param array $config The configs.
     *
     * @return void
     */
    protected function setScrubFields(array $config): void
    {
        $fromConfig = $config['scrubFields'] ?? null;
        if (!isset($fromConfig)) {
            $fromConfig = $config['scrub_fields'] ?? null;
        }
        $this->scrubFields = Defaults::get()->scrubFields($fromConfig);
    }

    /**
     * Returns the list of keys to scrub data from.
     *
     * @return string[]
     */
    public function getScrubFields()
    {
        return $this->scrubFields;
    }

    /**
     * Sets the list of keys to not scrub data from.
     *
     * @param $config
     *
     * @return void
     */
    protected function setSafelist($config)
    {
        $fromConfig = $config['scrubSafelist'] ?? null;
        if (!isset($fromConfig)) {
            $fromConfig = $config['scrub_safelist'] ?? null;
        }
        $this->safelist = $fromConfig ?: array();
    }

    /**
     * Returns the list of keys that data will not be scrubbed from.
     *
     * @return string[]
     */
    public function getSafelist()
    {
        return $this->safelist;
    }

    /**
     * Scrub a data structure including arrays and query strings.
     *
     * @param array  $data        Data to be scrubbed.
     * @param string $replacement Character used for scrubbing.
     * @param string $path        Path of traversal in the array
     *
     * @return mixed
     */
    #[\Override]
    public function scrub(array &$data, string $replacement = '********', string $path = ''): array
    {
        $fields = $this->getScrubFields();

        if (!$fields || !$data) {
            return $data;
        }

        // Scrub fields is case-insensitive, so force all fields to lowercase
        $fields = array_change_key_case(array_flip($fields), CASE_LOWER);

        return $this->internalScrub($data, $fields, $replacement, $path);
    }

    /**
     * This method does most of the heavy lifting of scrubbing sensitive data from the serialized paylaod. It executes
     * recursively over arrays and attempts to parse key / value pairs from strings.
     *
     * @param mixed  $data        the data to be scrubbed.
     * @param array  $fields      The keys to private data that should be scrubbed.
     * @param string $replacement The text to replace sensitive data with.
     * @param string $path        The path to the current field delimited with '.'. It may be several fields long, if
     *                            the current field is deeply nested.
     *
     * @return mixed
     */
    public function internalScrub(mixed &$data, array $fields, string $replacement, string $path): mixed
    {
        if (is_array($data)) {
            // scrub arrays
            return $this->scrubArray($data, $fields, $replacement, $path);
        }
        if (is_string($data)) {
            // scrub URLs and query strings
            $query = parse_url($data, PHP_URL_QUERY);
            if ($query) {
                return str_replace(
                    $query,
                    $this->scrubQueryString($query, $fields),
                    $data,
                );
            }
            if (self::isQueryStringable($data)) {
                return $this->scrubQueryString($data, $fields);
            }
        }
        return $data;
    }

    /**
     * Checks if a string is a valid query string.
     *
     * @param string $data The string to check.
     * @return bool True if the string is a valid query string, false otherwise.
     *
     * @since 4.2.0
     */
    public static function isQueryStringable(string $data): bool
    {
        // Because we only scrub values based on keys, we can safely assume that the string is not a query string if
        // it does not contain an '=' character.
        if (!str_contains($data, '=')) {
            return false;
        }
        // PHP reports warning if parse_str() detects more than max_input_vars items.
        @parse_str($data, $parsedData);
        $rebuilt = http_build_query($parsedData);
        $parts = explode('&', $data);
        $partsEncoded = array_map(urldecode(...), $parts);
        $rebuiltParts = explode('&', $rebuilt);
        $partsRebuiltEncoded = array_map(urldecode(...), $rebuiltParts);

        // Because nested arrays are supported by parse_str(), we need to sort the parts before comparing them. To
        // avoid false negatives due to ordering differences. Since the original nested data keys do not need to be in
        // order.
        sort($parts);
        sort($partsEncoded);
        sort($rebuiltParts);
        sort($partsRebuiltEncoded);
        return $parts === $rebuiltParts || $partsEncoded === $partsRebuiltEncoded;
    }

    /**
     * Checks if the given string contains percent encoded data.
     *
     * @param string $data The string to check.
     * @return bool
     *
     * @since 4.2.0
     */
    public static function hasPercentEncodedData(string $data): bool
    {
        $parts = explode('%', $data);
        if (count($parts) === 1) {
            return false;
        }

        $percentEncoded = false;
        foreach ($parts as $part) {
            if (strlen($part) < 2) {
                continue;
            }
            // Check if the first two characters are hexadecimal digits.
            if (ctype_xdigit(substr($part, 0, 2))) {
                $percentEncoded = true;
                break;
            }
        }
        return $percentEncoded;
    }

    /**
     * Scrubs sensitive data from an array. This will call {@see self::internalScrub()} and can execute recursively.
     *
     * @param array  $arr         The array of values to scrub.
     * @param array  $fields      The keys to scrub from the data.
     * @param string $replacement The text to replace scrubbed data with.
     * @param string $path        The path to the current array of values. This will be an empty string if it is the
     *                            top level array. Otherwise, it will be '.' delimited list of field names.
     *
     * @return array The scrubbed data.
     */
    protected function scrubArray(
        array &$arr,
        array $fields,
        string $replacement = '********',
        string $path = '',
    ): array {
        if (!$fields || !$arr) {
            return $arr;
        }

        $scrubber   = $this;
        $scrubberFn = function (
            &$val,
            $key,
        ) use (
            $fields,
            $replacement,
            $scrubber,
            &$path,
        ) {
            $current = !$path ? (string)$key : $path . '.' . $key;

            if (in_array($current, $scrubber->getSafelist())) {
                return;
            }

            // $key may be an integer (proper), such as when scrubbing
            // backtraces -- coerce to string to satisfy strict types
            if (isset($fields[strtolower((string)$key)])) {
                $val = $replacement;
                return;
            }
            $val = $scrubber->internalScrub($val, $fields, $replacement, $current);
        };

        // We use array_walk() recursively, instead of array_walk_recursive() so we can build the nested path.
        array_walk($arr, $scrubberFn);

        return $arr;
    }

    /**
     * Scrubs sensitive data from a query string formatted string.
     *
     * @param string $query       The string to scrub data from.
     * @param array  $fields      The keys to scrub data from.
     * @param string $replacement the text to replace scrubbed data.
     *
     * @return string
     */
    protected function scrubQueryString(string $query, array $fields, string $replacement = 'xxxxxxxx'): string
    {
        // Check if the query string contains percent encoded characters.
        $percentEncoded = self::hasPercentEncodedData($query);
        // PHP reports warning if parse_str() detects more than max_input_vars items.
        @parse_str($query, $parsed);
        $scrubbed = $this->internalScrub($parsed, $fields, $replacement, '');

        // If the original query string was not percent encoded, we need to decode the scrubbed data.
        if (!$percentEncoded) {
            return str_replace(' ', '+', urldecode(http_build_query($scrubbed)));
        }
        return http_build_query($scrubbed);
    }
}
