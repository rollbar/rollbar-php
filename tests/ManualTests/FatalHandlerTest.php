<?php

/**
 * The following test file is used to check how the stack frames are built
 * when reporting different scenarios with Rollbar.
 * 
 * This can not be a part of the PHPUnit test suite since PHPUnit doesn't
 * support testing shutdown functions and exceptions handlers well.
 * 
 * This is tightly related to https://github.com/rollbar/rollbar-php/issues/292.
 */

require __DIR__ . '/bootstrap.php';

use \Rollbar\Rollbar;
use \Rollbar\Payload\Level;

$token = 'ad865e76e7fb496fab096ac07b1dbabb';

fatalError($token);

/** 
 * Trigger a fatal error.
 * 
 * On PHP 7+ this is treated as an exception and thus handled by the exception
 * handler.
 * 
 * On PHP 5 this is treated as a fatal error and handled by the fatal handler.
 */
function fatalError($token)
{
    Rollbar::init(
        array(
            'access_token' => $token,
            'ignore_validation' => true,
            'environment' => 'production'
        )
    );
    
    
    function something() {
      somethingElse();
    }
    
    function somethingElse() {
        $null = null;
        $null->noMethod();
    }
    
    something();
}