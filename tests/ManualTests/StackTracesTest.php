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

require __DIR__ . '/../../vendor/autoload.php';
error_reporting(E_ALL);

use \Rollbar\Rollbar;
use \Rollbar\Payload\Level;

/**
 * Uncomment one of the following test cases to run the test.
 */
 
$token = 'eb2561a52efb4d4bba5a1d4b68be13e9';
 
// nestedException($token);
fatalError($token);
// warning($token);
// andrewsExample($token);

/**
 * Results
 *
 * x - passing
 * o - failing
 *
 * 5X - PHP 5 with Xdebug
 * 5noX - PHP 5 without Xdebug
 * 7X - PHP 7 with Xdebug
 * 7noX - PHP 7 without Xdebug
 *
 *                  |5X |5noX   |7X |7noX
 * ---------------------------------------
 * nestedException  |o  |o      |o  |o
 * fatalError       |x  |o      |o  |o
 * warning          |o  |o      |o  |o
 * andrewsExample   |o  |o      |o  |o
 *
 */

/**
 * Andrew's example (https://github.com/rollbar/rollbar-php/issues/292)
 *
 * This logs a double record in Rollbar dashboard. One triggered by errorHandler,
 * the other by fatalHandler.
 */
function andrewsExample($token)
{
    Rollbar::init(
        array(
            'access_token' => $token,
            'ignore_validation' => true,
            'environment' => 'production'
        )
    );
    
    function something()
    {
        somethingElse();
    }
    
    function somethingElse()
    {
        trigger_error("Oops!", E_USER_ERROR);
    }
    
    something();
}

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
    
    
    function something()
    {
        somethingElse();
    }
    
    function somethingElse()
    {
        $null = null;
        $null->noMethod();
    }
    
    something();
}

/**
 * Trigger an exception
 */
function nestedException($token)
{
    Rollbar::init(
        array(
            'access_token' => $token,
            'ignore_validation' => true,
            'environment' => 'production'
        )
    );
    
    
    function something()
    {
        somethingElse();
    }
    
    function somethingElse()
    {
        throw new \Exception();
    }
    
    something();
}

/**
 * Trigger a non-fatal PHP error.
 */
function warning($token)
{
    
    Rollbar::init(
        array(
            'access_token' => $token,
            'ignore_validation' => true,
            'environment' => 'production'
        )
    );
    
    
    function something()
    {
        somethingElse();
    }
    
    function somethingElse()
    {
        require("No file");
    }
    
    something();
}
