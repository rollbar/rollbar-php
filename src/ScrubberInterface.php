<?php declare(strict_types=1);

namespace Rollbar;

interface ScrubberInterface
{
    public function scrub(&$data, $replacement);
}
