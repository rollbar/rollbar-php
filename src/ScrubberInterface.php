<?php namespace Rollbar;

interface ScrubberInterface
{
    public function scrub(&$data, $replacement);
}
