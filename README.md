---
layout: page
sidebar: rollbar_sidebar
permalink: /notifiers/rollbar-php/
toc: false
---
# Rollbar notifier for PHP [![Build Status](https://travis-ci.org/rollbar/rollbar-php.png?branch=v0.15.0)](https://travis-ci.org/rollbar/rollbar-php)

<!-- RemoveNext -->

This library detects errors and exceptions in your application and reports them to [Rollbar](https://rollbar.com) for alerts, reporting, and analysis.

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
##Table of Contents

- [Quick start](#quick-start)
- [Installation](#installation)
  - [General](#general)
  - [If Using Composer](#if-using-composer)
- [Setup](#setup)
  - [For Heroku Users](#for-heroku-users)
- [Basic Usage](#basic-usage)
- [Batching](#batching)
- [Using Monolog](#using-monolog)
- [Configuration](#configuration)
  - [Asynchronous Reporting](#asynchronous-reporting)
  - [Configuration reference](#configuration-reference)
- [Related projects](#related-projects)
- [Help / Support](#help--support)
- [Contributing](#contributing)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Quick start

```php
<?php
// installs global error and exception handlers
Rollbar::init(array('access_token' => 'POST_SERVER_ITEM_ACCESS_TOKEN'));

try {
    throw new Exception('test exception');
} catch (Exception $e) {
    Rollbar::report_exception($e);
}

// Message at level 'info'
Rollbar::report_message('testing 123', Level::INFO);

// With extra data (3rd arg) and custom payload options (4th arg)
Rollbar::report_message('testing 123', Level::INFO,
                        // key-value additional data
                        array("some_key" => "some value"),  
                        // payload options (overrides defaults) - see api docs
                        array("fingerprint" => "custom-fingerprint-here"));

// raises an E_NOTICE which will *not* be reported by the error handler
$foo = $bar;

// will be reported by the exception handler
throw new Exception('test 2');
?>
```

## Installation

### General

Download [rollbar.php](https://raw.github.com/rollbar/rollbar-php/master/src/rollbar.php) and [Level.php](https://raw.githubusercontent.com/rollbar/rollbar-php/master/src/Level.php)
and put them together somewhere you can access.

### If Using Composer

Add `rollbar/rollbar` to your `composer.json`:

```json
{
    "require": {
        "rollbar/rollbar": "~0.18.0"
    }
}
```

## Setup

Add the following code at your application's entry point:

```php
<?php
require_once 'rollbar.php';

$config = array(
    // required
    'access_token' => 'POST_SERVER_ITEM_ACCESS_TOKEN',
    // optional - environment name. any string will do.
    'environment' => 'production',
    // optional - path to directory your code is in. used for linking stack traces.
    'root' => '/Users/brian/www/myapp'
);
Rollbar::init($config);
?>
```
<!-- RemoveNextIfProject -->
Be sure to replace ```POST_SERVER_ITEM_ACCESS_TOKEN``` with your project's ```post_server_item``` access token, which you can find in the Rollbar.com interface.

This will install an exception handler (with `set_exception_handler`) and an error handler (with `set_error_handler`). If you'd rather not do that:

```php
<?php
$set_exception_handler = false;
$set_error_handler = false;
Rollbar::init($config, $set_exception_handler, $set_error_handler);
?>
```

### For Heroku Users

First, add the addon:

```
heroku addons:create rollbar:free
```

The `access_token` and `root` config variables will be automatically detected, so the config is simply:

```php
<?php
Rollbar::init(array(
    'environment' => 'production'
));
?>
```

## Basic Usage

That's it! Uncaught errors and exceptions will now be reported to Rollbar.

If you'd like to report exceptions that you catch yourself:

```php
<?php
try {
    do_something();
} catch (Exception $e) {
    Rollbar::report_exception($e);
    // or
    Rollbar::report_exception($e, array("my" => "extra", "data" => 42));
}
?>
```

You can also send Rollbar log-like messages:

```php
<?php
Rollbar::report_message('could not connect to mysql server', Level::WARNING);
Rollbar::report_message('Here is a message with some additional data',
    Level::INFO, array('x' => 10, 'code' => 'blue'));
?>
```

## Batching

By default, payloads are batched and sent to the Rollbar servers at the end of every script execution via a shutdown handler, or when the batch size reaches 50, whichever comes first. This works well in standard short-lived scripts, like serving web requests.

If you're using Rollbar in a long-running script, such as a Laravel project or a background worker, you may want to manually flush the batch. To flush, simply call:

```php
Rollbar::flush();
```

For example, if using Laravel, add the above line to your `App::after()` event handler. Or in a looping background worker, call it at the end of each loop.

You can also tune the max batch size or disable batching altogether. See the `batch_size` and `batched` config variables, documented below.

## Using Monolog

Here is an example of how to use Rollbar as a handler for Monolog:

```
use Monolog\Logger;
use Monolog\Handler\RollbarHandler;

$config = array('access_token' => 'POST_SERVER_ITEM_ACCESS_TOKEN');

// installs global error and exception handlers
Rollbar::init($config);

$log = new Logger('test');
$log->pushHandler(new RollbarHandler(Rollbar::$instance));

try {
    throw new Exception('exception for monolog');
} catch (Exception $e) {
    $log->error($e);
}
```

## Configuration

### Asynchronous Reporting

By default, payloads (batched or not) are sent as part of script execution. This is easy to configure but may negatively impact performance. With some additional setup, payloads can be written to a local relay file instead; that file will be consumed by [rollbar-agent](https://github.com/rollbar/rollbar-agent) asynchronously. To turn this on, set the following config params:

```php
<?php
$config = array(
  // ... rest of current config
  'handler' => 'agent',
  'agent_log_location' => '/var/www'  // not including final slash. must be writeable by the user php runs as.
);
?>
```

You'll also need to run the agent. See the [rollbar-agent docs](https://github.com/rollbar/rollbar-agent) for setup instructions.

### Configuration reference

All of the following options can be passed as keys in the `$config` array.

  <dl>
  <dt>access_token</dt>
  <dd>Your project access token.
  </dd>

  <dt>agent_log_location</dt>
  <dd>Path to the directory where agent relay log files should be written. Should not include final slash. Only used when handler is `agent`.

Default: `/var/www`
  </dd>

  <dt>base_api_url</dt>
  <dd>The base api url to post to.

Default: `https://api.rollbar.com/api/1/`
  </dd>

  <dt>batch_size</dt>
  <dd>Flush batch early if it reaches this size.

Default: `50`
  </dd>

  <dt>batched</dt>
  <dd>True to batch all reports from a single request together.

Default: `true`
  </dd>

  <dt>branch</dt>
  <dd>Name of the current branch.

Default: `master`
  </dd>

  <dt>capture_error_stacktraces</dt>
  <dd>Record full stacktraces for PHP errors.

Default: `true`
  </dd>

  <dt>checkIgnore</dt>
  <dd>Function called before sending payload to Rollbar, return true to stop the error from being sent to Rollbar.

Default: `null`
<br/>
Parameters:
* $isUncaught: boolean value set to true if the error was an uncaught exception.
* $exception: a RollbarException instance that will allow you to get the message or exception
* $payload: an array containing the payload as it will be sent to Rollbar. Payload schema can be found at https://rollbar.com/docs/api/items_post/
<br/>
```
$config = array(
    'access_token' => '...',
    'checkIgnore' => function ($isUncaught, $exception, $payload) {
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Baiduspider') !== false) {
          // ignore baidu spider
          return true;
        }

        // no other ignores
        return false;
    }; 
);
Rollbar::init($config);
```
  </dd>

  <dt>code_version</dt>
  <dd>The currently-deployed version of your code/application (e.g. a Git SHA). Should be a string.

Default: `null`
  </dd>
  
  <dt>enable_utf8_sanizations</dt>
  <dd>set to false, to disable running iconv on the payload, may be needed if there is invalid characters, and the payload is being destroyed
  
Default: `true`
  </dd>

  <dt>environment</dt>
  <dd>Environment name, e.g. `'production'` or `'development'`

Default: `'production'`
  </dd>

  <dt>error_sample_rates</dt>
  <dd>Associative array mapping error numbers to sample rates. Sample rates are ratio out of 1, e.g. 0 is "never report", 1 is "always report", and 0.1 is "report 10% of the time". Sampling is done on a per-error basis.

Default: empty array, meaning all errors are reported.
  </dd>

  <dt>handler</dt>
  <dd>Either `'blocking'` or `'agent'`. `'blocking'` uses curl to send requests immediately; `'agent'` writes a relay log to be consumed by [rollbar-agent](https://github.com/rollbar/rollbar-agent).

Default: `'blocking'`
  </dd>

  <dt>host</dt>
  <dd>Server hostname.

Default: `null`, which will result in a call to `gethostname()` (or `php_uname('n')` if that function does not exist)
  </dd>

  <dt>include_error_code_context</dt>
  <dd>A boolean that indicates you wish to gather code context for instances of PHP Errors.
    This can take a while because it requires reading the file from disk, so it's off by default.

Default: false
  </dd>

  <dt>include_exception_code_context</dt>
  <dd>A boolean that indicates you wish to gather code context for instances of PHP Exeptions.
    This can take a while because it requires reading the file from disk, so it's off by default.

Default: false
  </dd>

  <dt>included_errno</dt>
  <dd>A bitmask that includes all of the error levels to report. E.g. (E_ERROR | E_WARNING) to only report E_ERROR and E_WARNING errors. This will be used in combination with `error_reporting()` to prevent reporting of errors if `use_error_reporting` is set to `true`.

Default: (E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)
  </dd>

  <dt>logger</dt>
  <dd>An object that has a `log($level, $message)` method. If provided, will be used by RollbarNotifier to log messages.
  </dd>

  <dt>person</dt>
  <dd>An associative array containing data about the currently-logged in user. Required: `id`, optional: `username`, `email`. All values are strings.
  </dd>

  <dt>person_fn</dt>
  <dd>A function reference (string, etc. - anything that [call_user_func()](http://php.net/call_user_func) can handle) returning an array like the one for 'person'.
  </dd>

  <dt>root</dt>
  <dd>Path to your project's root dir
  </dd>

  <dt>scrub_fields</dt>
  <dd>Array of field names to scrub out of _POST and _SESSION. Values will be replaced with asterisks. If overriding, make sure to list all fields you want to scrub, not just fields you want to add to the default. Param names are converted to lowercase before comparing against the scrub list.

Default: `('passwd', 'password', 'secret', 'confirm_password', 'password_confirmation', 'auth_token', 'csrf_token')`
  </dd>

  <dt>shift_function</dt>
  <dd>Whether to shift function names in stack traces down one frame, so that the function name correctly reflects the context of each frame.

Default: `true`
  </dd>

  <dt>timeout</dt>
  <dd>Request timeout for posting to rollbar, in seconds.

Default: `3`
  </dd>

  <dt>report_suppressed</dt>
  <dd>Sets whether errors suppressed with '@' should be reported or not

Default: `false`
  </dd>

  <dt>use_error_reporting</dt>
  <dd>Sets whether to respect current `error_reporting()` level or not

Default: `false`
  </dd>

  <dt>proxy</dt>
  <dd>Send data via a proxy server.

E.g. Using a local proxy with no authentication

```php
<?php
$config['proxy'] = "127.0.0.1:8080";
?>
```

E.g. Using a local proxy with basic authentication

```php
<?php
$config['proxy'] = array(
    'address' => '127.0.0.1:8080',
    'username' => 'my_user',
    'password' => 'my_password'
);
?>
```

Default: No proxy
  </dd>

  </dl>

Example use of error_sample_rates:
```php
<?php
$config['error_sample_rates'] = array(
    // E_WARNING omitted, so defaults to 1
    E_NOTICE => 0.1,
    E_USER_ERROR => 0.5,
    // E_USER_WARNING will take the same value, 0.5
    E_USER_NOTICE => 0.1,
    // E_STRICT and beyond will all be 0.1
);
?>
```

Example use of person_fn:
```php
<?php
function get_current_user() {
    if ($_SESSION['user_id']) {
        return array(
            'id' => $_SESSION['user_id'], // required - value is a string
            'username' => $_SESSION['username'], // optional - value is a string
            'email' => $_SESSION['user_email'] // optional - value is a string
        );
    }
    return null;
}
$config['person_fn'] = 'get_current_user';
?>
```

## Related projects

A Laravel-specific package is available for integrating with Laravel: [Laravel-Rollbar](https://github.com/jenssegers/Laravel-Rollbar)

A CakePHP-specific package is avaliable for integrating with CakePHP 2.x:
[CakeRollbar](https://github.com/tranfuga25s/CakeRollbar)


## Help / Support

If you run into any issues, please email us at [support@rollbar.com](mailto:support@rollbar.com)

You can also find us in IRC: [#rollbar on chat.freenode.net](irc://chat.freenode.net/rollbar)

For bug reports, please [open an issue on GitHub](https://github.com/rollbar/rollbar-php/issues/new).


## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

Tests are in `tests`. To run the tests: `phpunit`
