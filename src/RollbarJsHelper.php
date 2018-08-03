<?php namespace Rollbar;

class RollbarJsHelper
{

    protected $config;
    
    /**
     * @param array $config The array passed to he should have the same
     * available options that you can find in Rollbar.js, using symbols or
     * strings for the keys.
     */
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * Shortcut method for building the RollbarJS Javascript
     *
     * @param array $config @see addJs()
     * @param string $nonce @see addJs()
     * @param string $customJs @see addJs()
     *
     * @return string
     */
    public static function buildJs(
        $config,
        $headers = null,
        $nonce = null,
        $customJs = ""
    ) {
        $helper = new self($config);
        return $helper->addJs($headers, $nonce, $customJs);
    }
    
    /**
     * Build Javascript required to include RollbarJS on
     * an HTML page
     *
     * @param array $headers Response headers usually retrieved through
     * headers_list() used to verify if nonce should be added to script
     * tags based on Content-Security-Policy
     * @param string $nonce Content-Security-Policy nonce string if exists
     * @param strong $customJs Additional JavaScript to add at the end of
     * RollbarJs snippet
     *
     * @return string
     */
    public function addJs($headers = null, $nonce = null, $customJs = "")
    {
        return $this->scriptTag(
            $this->configJsTag() . $this->jsSnippet() . ";" . $customJs,
            $headers,
            $nonce
        );
    }
    
    /**
     * Build RollbarJS config script
     *
     * @return string
     */
    public function configJsTag()
    {
        return "var _rollbarConfig = " . json_encode($this->config, JSON_FORCE_OBJECT) . ";";
    }
    
    /**
     * Build rollbar.snippet.js string
     *
     * @return string
     */
    public function jsSnippet()
    {
        return file_get_contents(
            $this->snippetPath()
        );
    }
    
    /**
     * @return string Path to the rollbar.snippet.js
     */
    public function snippetPath()
    {
        return realpath(__DIR__ . "/../data/rollbar.snippet.js");
    }
    
    /**
     * Should JS snippet be added to the HTTP response
     *
     * @param int $status
     * @param array $headers
     *
     * @return boolean
     */
    public function shouldAddJs($status, $headers)
    {
        return
            $status == 200 &&
            $this->isHtml($headers) &&
            !$this->hasAttachment($headers);
            
            /**
             * @todo not sure if below two conditions will be applicable
             */
            /* !env[JS_IS_INJECTED_KEY] */
            /* && !streaming?(env) */
    }
    
    /**
     * Is the HTTP response a valid HTML response
     *
     * @param array $headers
     *
     * @return boolean
     */
    public function isHtml($headers)
    {
        return in_array('Content-Type: text/html', $headers);
    }
    
    /**
     * Does the HTTP response include an attachment
     *
     * @param array $headers
     *
     * @return boolean
     */
    public function hasAttachment($headers)
    {
        return in_array('Content-Disposition: attachment', $headers);
    }
    
    /**
     * Is `nonce` attribute on the script tag needed?
     *
     * @param array $headers
     *
     * @return boolean
     */
    public function shouldAppendNonce($headers)
    {
        foreach ($headers as $header) {
            if (strpos($header, 'Content-Security-Policy') !== false &&
                strpos($header, "'unsafe-inline'") !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Build safe HTML script tag
     *
     * @param string $content
     * @param array $headers
     * @param
     *
     * @return string
     */
    public function scriptTag($content, $headers = null, $nonce = null)
    {
        if ($headers !== null && $this->shouldAppendNonce($headers)) {
            if (!$nonce) {
                throw new \Exception(
                    'Content-Security-Policy is script-src '.
                    'inline-unsafe but nonce value not provided.'
                );
            }
            
            return "\n<script type=\"text/javascript\" nonce=\"$nonce\">$content</script>";
        }
        return "\n<script type=\"text/javascript\">$content</script>";
    }
}
