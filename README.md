ratchetio-php
===========

PHP notifier for Ratchet.io. Catches and reports exceptions to [Ratchet.io](https://ratchet.io/) for alerts, reporting, and analysis.

```php
// installs global error and exception handlers
Ratchetio::init(array('access_token' => 'your_access_token'));

try {
    throw new Exception('test exception');
} catch (Exception $e) {
    Ratchetio::report_exception($e);
}

Ratchetio::report_message('testing 123', 'info');

// raises an E_NOTICE which will be reported by the error handler
$foo = $bar;

// will be reported by the exception handler
throw new Exception('test 2');
```

## Installation and Configuration

1. Download the code and put `ratchetio.php` somewhere you can access it

2. Add the following code at your application's entry point:

```php
require_once 'ratchetio.php';

$config = array(
    // required
    'access_token' => 'your_ratchetio_access_token',
    // optional - environment name. any string will do.
    'environment' => 'production',
    // optional - dir your code is in. used for linking stack traces.
    'root' => '/Users/brian/www/myapp',
    // optional - max error number to report. defaults to -1 (report all errors)
    'max_errno' => E_USER_NOTICE  // ignore E_STRICT and above
);
Ratchetio::init($config);
```

This will install an exception handler (with `set_exception_handler`) and an error handler (with `set_error_handler`). If you'd rather not do that:

```php
$set_exception_handler = false;
$set_error_handler = false;
Ratchetio::init($config, $set_exception_handler, $set_error_handler);
```

3. That's it! If you'd like to report exceptions that you catch yourself:

```php
try {
    do_something();
} catch (Exception $e) {
    Ratchetio::report_exception($e);
}
```

You can also send ratchet log-like messages:

```php
Ratchetio::report_message('could not connect to mysql server', 'warning');
```


## Configuration reference

All of the following options can be passed as keys in the $config array.

- access_token: your project access token
- base_api_url: the base api url to post to (default 'https://submit.ratchet.io/api/1/')
- batch_size: flush batch early if it reaches this size. default: 50
- batched: true to batch all reports from a single request together. default true.
- branch: name of the current branch (default 'master')
- capture_error_stacktraces: record full stacktraces for PHP errors. default: true.
- environment: environment name, e.g. 'production' or 'development'
- error_sample_rates: associative array mapping error numbers to sample rates. Sample rates are ratio out of 1, e.g. 0 is "never report", 1 is "always report", and 0.1 is "report 10% of the time". Sampling is done on a per-error basis. Default: empty array, meaning all errors are reported.
- host: server hostname. Default: null, which will result in a call to `gethostname()` (or `php_uname('n')` if that function does not exist)
- logger: an object that has a log($level, $message) method. If provided, will be used by RatchetioNotifier to log messages.
- max_errno: max PHP error number to report. e.g. 1024 will ignore all errors above E_USER_NOTICE. default: 1024 (ignore E_STRICT and above).
- person: an associative array containing data about the currently-logged in user. Required: 'id', optional: 'username', 'email'. All values are strings.
- person_fn: a function reference (string, etc. - anything that [call_user_func()](http://php.net/call_user_func) can handle) returning an array like the one for 'person'.
- root: path to your project's root dir
- scrub_fields: array of field names to scrub out of POST. Values will be replaced with astrickses. If overridiing, make sure to list all fields you want to scrub, not just fields you want to add to the default. Param names are converted to lowercase before comparing against the scrub list. default: ('passwd', 'password', 'secret').
- shift_function: whether to shift function names in stack traces down one frame, so that the function name correctly reflects the context of each frame. default: true.
- timeout: request timeout for posting to ratchet, in seconds. default 3.

Example use of error_sample_rates:
```php
$config['error_sample_rates'] = array(
    // E_WARNING omitted, so defaults to 1
    E_NOTICE => 0.1,
    E_USER_ERROR => 0.5,
    // E_USER_WARNING will take the same value, 0.5
    E_USER_NOTICE => 0.1,
    // E_STRICT and beyond will all be 0.1
);
```

Example use of person_fn:
```php
function get_current_user() {
    if ($_SESSION['user_id']) {
        return array(
            'id' => $_SESSION['user_id'], // required - value is a string
            'username' => '$_SESSION['username'], // optional - value is a string
            'email' => $_SESSION['user_email'] // optional - value is a string
        );
    }
    return null;
}
$config['person_fn'] = 'get_current_user';
```


## Support

If you have any feedback or run into any problems, please contact support at support@ratchet.io


## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request



