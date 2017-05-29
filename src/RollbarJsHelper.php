<?php namespace Rollbar;

class RollbarJsHelper
{

    protected $config;
    
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * Build Javascript required to include RollbarJS on
     * an HTML page
     *
     * @param array $headers Response headers usually retrieved through
     * headers_list() used to verify if nonce should be added to script
     * tags based on Content-Security-Policy
     * @param string $nonce Content-Security-Policy nonce string if exists
     *
     * @return string
     */
    public function addJs($headers = null, $nonce = null)
    {
        return
            $this->configJsTag($headers = null, $nonce) .
            $this->snippetJsTag($headers = null, $nonce);
    }
    
    /**
     * Build RollbarJS snippet script tag
     *
     * @param array $headers
     * @param string $nonce
     *
     * @return string
     */
    public function snippetJsTag($headers = null, $nonce = null)
    {
        return $this->scriptTag($this->jsSnippet(), $headers, $nonce);
    }
    
    /**
     * Build RollbarJS config script tag
     *
     * @param array $headers
     * @param string $nonce
     *
     * @return string
     */
    public function configJsTag($headers = null, $nonce = null)
    {
        $configJs = "var _rollbarConfig = " . json_encode($this->config['options']) . ";";
        return $this->scriptTag($configJs, $headers, $nonce);
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
                throw new \Exception('Content-Security-Policy is script-src '.
                                     'inline-unsafe but nonce value not provided.');
            }
            
            return "\n<script type=\"text/javascript\" nonce=\"$nonce\">$content</script>";
        } else {
            return "\n<script type=\"text/javascript\">$content</script>";
        }
    }
}
