ratchet-php
===========

PHP notifier for Ratchet.io. Catches and reports exceptions to [Ratchet.io](https://ratchet.io/) for alerts, reporting, and analysis.

```php
// installs global error and exception handlers
Ratchet::init(array('access_token' => 'your_access_token'));

try {
    throw new Exception('test exception');
} catch (Exception $e) {
    Ratchet::report_exception($e);
}

Ratchet::report_message('testing 123', 'info');

// raises an E_NOTICE which will be reported by the error handler
$foo = $bar;

// will be reported by the exception handler
throw new Exception('test 2');
```

## Installation and Configuration

1. Download the code and put `Ratchet.php` somewhere you can access it

2. Add the following code at your application's entry point:

```php
require_once 'Ratchet.php';

$config = array(
    // required
    'access_token' => 'your_ratchetio_access_token',
    // optional - environment name. any string will do.
    'environment' => 'production',
    // optional - dir your code is in. used for linking stack traces.
    'root' => '/Users/brian/www/myapp'
);
Ratchet::init($config);
```

This will install an exception handler (with `set_exception_handler`) and an error handler (with `set_error_handler`). If you'd rather not do that:

```php
$set_exception_handler = false;
$set_error_handler = false;
Ratchet::init($config, $set_exception_handler, $set_error_handler);
```

3. That's it! If you'd like to report exceptions that you catch yourself:

```php
try {
    do_something();
} catch (Exception $e) {
    Ratchet::report_exception($e);
}
```

You can also send ratchet log-like messages:

```php
Ratchet::report_message('could not connect to mysql server', 'warning');
```


## Configuration reference

All of the following options can be passed as keys in the $config array.

- access_token: your project access token
- environment: environment name, e.g. 'production' or 'development'
- root: path to your project's root dir
- branch: name of the current branch (default 'master')
- logger: an object that has a log($level, $message) method. If provided, will be used by RatchetNotifier to log messages.
- base_api_url: the base api url to post to (default 'https://submit.ratchet.io/api/1/')
- batched: true to batch all reports from a single request together. default true.
- batch_size: flush batch early if it reaches this size. default: 50
- timeout: request timeout for posting to ratchet, in seconds. default 3.


## Support

If you have any feedback or run into any problems, please contact support at support@ratchet.io


## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request



