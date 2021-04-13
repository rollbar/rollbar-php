<?php declare(strict_types=1);

namespace Rollbar;

trait UtilitiesTrait
{
    private function utilities(): Utilities
    {
        static $utilities = null;
        if (null === $utilities) {
            $utilities = new Utilities;
        }
        return $utilities;
    }
}
