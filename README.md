# Rollbar notifier for PHP

PHP notifier for Rollbar. Catches and reports exceptions to [Rollbar.com](https://rollbar.com/) for alerts, reporting, and analysis.

<!-- Sub:[TOC] -->

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

Rollbar::report_message('testing 123', 'info');

// raises an E_NOTICE which will be reported by the error handler
$foo = $bar;

// will be reported by the exception handler
throw new Exception('test 2');
?>
```

## Installation

Download [rollbar.php](https://raw.github.com/rollbar/rollbar-php/master/rollbar.php) and put it somewhere you can access.

Add the following code at your application's entry point:

```php
<?php
require_once 'rollbar.php';

$config = array(
    // required
    'access_token' => 'POST_SERVER_ITEM_ACCESS_TOKEN',
    // optional - environment name. any string will do.
    'environment' => 'production',
    // optional - dir your code is in. used for linking stack traces.
    'root' => '/Users/brian/www/myapp',
    // optional - max error number to report. defaults to -1 (report all errors)
    'max_errno' => E_USER_NOTICE  // ignore E_STRICT and above
);
Rollbar::init($config);
?>
```

This will install an exception handler (with `set_exception_handler`) and an error handler (with `set_error_handler`). If you'd rather not do that:

```php
<?php
$set_exception_handler = false;
$set_error_handler = false;
Rollbar::init($config, $set_exception_handler, $set_error_handler);
?>
```

That's it! If you'd like to report exceptions that you catch yourself:

```php
<?php
try {
    do_something();
} catch (Exception $e) {
    Rollbar::report_exception($e);
}
?>
```

You can also send Rollbar log-like messages:

```php
<?php
Rollbar::report_message('could not connect to mysql server', 'warning');
Rollbar::report_message('Here is a message with some additional data', 'info', 
    array('x' => 10, 'code' => 'blue'));
?>
```

## Configuration

### Asynchronous Reporting

By default, payloads are batched and sent to the Rollbar servers at the end of every script execution (or when the batch size reaches 50, whichever comes first). This is easy to configure but may negatively impact performance. 

With some additional setup, payloads can be written to a local relay file instead; that file will be consumed by [rollbar-agent](https://github.com/rollbar/rollbar-agent) asynchronously. To turn this on, set the following config params:

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

  <dt>environment</dt>
  <dd>Environment name, e.g. `'production'` or `'development'`
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

  <dt>logger</dt>
  <dd>An object that has a `log($level, $message)` method. If provided, will be used by RollbarNotifier to log messages.
  </dd>

  <dt>max_errno</dt>
  <dd>Max PHP error number to report. e.g. 1024 will ignore all errors above E_USER_NOTICE.
  
Default: `1024` (ignore E_STRICT and above)
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
  <dd>Array of field names to scrub out of POST. Values will be replaced with astrickses. If overridiing, make sure to list all fields you want to scrub, not just fields you want to add to the default. Param names are converted to lowercase before comparing against the scrub list.
  
Default: `('passwd', 'password', 'secret', 'confirm_password', 'password_confirmation')`
  </dd>

  <dt>shift_function</dt>
  <dd>Whether to shift function names in stack traces down one frame, so that the function name correctly reflects the context of each frame.
  
Default: `true`
  </dd>

  <dt>timeout</dt>
  <dd>Request timeout for posting to rollbar, in seconds.
  
Default: `3`
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


## Support

If you have any feedback or run into any problems, please contact support at support@rollbar.com


## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request



