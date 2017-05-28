<?php namespace Rollbar;

class RollbarJsHelper
{
    const JS_IS_INJECTED_KEY = 'rollbar.js_is_injected';
    
    protected $config;
    
    // public function __construct($config)
    // {
    //     $this->config = $config;
    // }
    
    /**
     * Build RollbarJS script tag
     * 
     * @param array $headers
     * @param string $nonce
     * 
     * @return string
     */
    public function snippetJsTag($headers, $nonce = null)
    {
        return $this->scriptTag($this->jsSnippet(), $headers, $nonce);
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
    public function scriptTag($content, $headers, $nonce = null)
    {
        if ($this->shouldAppendNonce($headers)) {
            
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