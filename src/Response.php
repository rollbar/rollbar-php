<?php namespace Rollbar;

class Response
{
    public function __construct(
        private $status,
        private $info,
        private $uuid = null
    ) {
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function wasSuccessful()
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function getOccurrenceUrl()
    {
        if (is_null($this->uuid)) {
            return null;
        }
        if (!$this->wasSuccessful()) {
            return null;
        }
        return "https://rollbar.com/occurrence/uuid/?uuid=" . $this->uuid;
    }

    public function __toString()
    {
        $url = $this->getOccurrenceUrl();
        return "Status: $this->status\n" .
               "Body: " . json_encode($this->info) . "\n" .
               "URL: $url";
    }
}
