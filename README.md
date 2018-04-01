# Rollbar for PHP [![Build Status](https://api.travis-ci.org/rollbar/rollbar-php.png)](https://travis-ci.org/rollbar/rollbar-php)

<!-- RemoveNext -->

This library detects errors and exceptions in your application and reports them to [Rollbar](https://rollbar.com) for alerts, reporting, and analysis.

Supported PHP versions: 5.3, 5.4, 5.5, 5.6, 7, 7.1, 7.2 and HHVM (currently tested on 3.6.6).

The documentation for the latest release can be found [here](https://github.com/rollbar/rollbar-php/tree/v1.3.3). The README that is
available on master is updated as code is changed prior to making a release.

<!-- Sub:[TOC] -->

## Quick start

```php

    use \Rollbar\Rollbar;
    use \Rollbar\Payload\Level;
    
    // installs global error and exception handlers
    Rollbar::init(
        array(
            'access_token' => ROLLBAR_TEST_TOKEN,
            'environment' => 'production'
        )
    );
    
    try {
        throw new \Exception('test exception');
    } catch (\Exception $e) {
        Rollbar::log(Level::ERROR, $e);
    }
    
    // Message at level 'info'
    Rollbar::log(Level::INFO, 'testing info level');
    
    // With extra data (3rd arg) and custom payload options (4th arg)
    Rollbar::log(
        Level::INFO,
        'testing extra data',
        array("some_key" => "some value") // key-value additional data
    );
            
    // If you want to check if logging with Rollbar was successful
    $response = Rollbar::log(Level::INFO, 'testing wasSuccessful()');
    if (!$response->wasSuccessful()) {
        throw new \Exception('logging with Rollbar failed');
    }
    
    // raises an E_NOTICE which will *not* be reported by the error handler
    $foo = $bar;
    
    // will be reported by the exception handler
    throw new \Exception('testing exception handler');
    
```

## Installation

### Using Composer (recommended)

Add `rollbar/rollbar` to your `composer.json`:

```json

    {
        "require": {
            "rollbar/rollbar": "^1"
        }
    }
    
```

### Manual installation if you are not using composer.json for your project

Keep in mind, that even if you're not using composer for your project (using composer.json), you will still need composer package to install rollbar-php dependencies.

1. If you don't have composer yet, follow these instructions to get the package: [install composer](https://getcomposer.org/doc/00-intro.md). It will be needed to install dependencies.
2. Clone git repository [rollbar/rollbar-php](https://github.com/rollbar/rollbar-php) into a your external libraries path: `git clone https://github.com/rollbar/rollbar-php`
2. Install rollbar-php dependencies: `cd rollbar-php && composer install && cd ..`
3. Require rollbar-php in your PHP scripts: `require_once YOUR_LIBS_PATH . '/rollbar-php/vendor/autoload.php';`

## Setup

Add the following code at your application's entry point:

```php

    use \Rollbar\Rollbar;
    
    $config = array(
        // required
        'access_token' => 'POST_SERVER_ITEM_ACCESS_TOKEN',
        // optional - environment name. any string will do.
        'environment' => 'production',
        // optional - path to directory your code is in. used for linking stack traces.
        'root' => '/Users/brian/www/myapp'
    );
    Rollbar::init($config);
    
```
<!-- RemoveNextIfProject -->
Be sure to replace ```POST_SERVER_ITEM_ACCESS_TOKEN``` with your project's ```post_server_item``` access token, which you can find in the Rollbar.com interface.

This will install an exception handler (with `set_exception_handler`) and an error handler (with `set_error_handler`). If you'd rather not do that:

```php

    $set_exception_handler = false;
    $set_error_handler = false;
    Rollbar::init($config, $set_exception_handler, $set_error_handler);
    
```

### For CodeIgniter Users

If you are using CodeIgniter you can place `Rollbar::init` in either of the two places:
* inside the Controller's constructor
```php

	public function __construct()
	{
		Rollbar::init(array(
			'access_token' => config_item('rollbar_access_token'),
		 	'environment' => ENVIRONMENT
		));
		parent::__construct();
	}
	
```
* `pre_system` hook
```php

    $hook['pre_system'] = function () {
        Rollbar::init([
            'access_token' => config_item('rollbar_access_token'),
            'environment' => ENVIRONMENT,
            'root' => APPPATH . '../'
        ]);
    };
    
```

**Note: If you wish to log `E_NOTICE` errors make sure to pass `'included_errno' => E_ALL` to `Rollbar::init`.**

### For Heroku Users

First, add the addon:

```
    heroku addons:create rollbar:free
```

The `access_token` and `root` config variables will be automatically detected, so the config is simply:

```php

    use Rollbar\Rollbar;
    
    Rollbar::init(array(
        'environment' => 'production'
    ));
    
```

## Integration with Rollbar.js

In case you want to report your JavaScript errors using [Rollbar.js](https://github.com/rollbar/rollbar.js), you can configure the SDK to enable Rollbar.js on your site. Example:

```php

    $rollbarJs = Rollbar\RollbarJsHelper::buildJs(
        array(
            "accessToken" => "POST_CLIENT_ITEM_ACCESS_TOKEN",
            "captureUncaught" => true,
            "payload" => array(
                "environment" => "production"
            ),
            /* other configuration you want to pass to RollbarJS */
        )
    );
    
```

Or if you are using Content-Security-Policy: script-src 'unsafe-inline'
```php

    $rollbarJs = Rollbar\RollbarJsHelper::buildJs(
        array(
            "accessToken" => "POST_CLIENT_ITEM_ACCESS_TOKEN",
            "captureUncaught" => true,
            "payload" => array(
                "environment" => "production"
            ),
            /* other configuration you want to pass to RollbarJS */
        ),
        headers_list(),
        $yourNonceString
    );
    
```

## Basic Usage

That's it! Uncaught errors and exceptions will now be reported to Rollbar.

If you'd like to report exceptions that you catch yourself:

```php

    use Rollbar\Rollbar;
    use Rollbar\Payload\Level;
    
    try {
        do_something();
    } catch (\Exception $e) {
        Rollbar::log(Level::ERROR, $e);
        // or
        Rollbar::log(Level::ERROR, $e, array("my" => "extra", "data" => 42));
    }
    
```

You can also send Rollbar log-like messages:

```php

    use Rollbar\Rollbar;
    use Rollbar\Payload\Level;
    
    Rollbar::log(Level::WARNING, 'could not connect to mysql server');
    Rollbar::log(
        Level::INFO, 
        'Here is a message with some additional data',
        array('x' => 10, 'code' => 'blue')
    );
    
```

## Using dependency injection

If you're using dependency injection containers, you can create and get a `RollbarLogger` from the container and use it
to initialize Rollbar error logging.

It's up to the container to properly create and configure the logger.

```php

    use Rollbar\Rollbar;
    use Rollbar\RollbarLogger;
    
    $logger = $container->get(RollbarLogger::class);
    
    // installs global error and exception handlers
    Rollbar::init($logger);

```

## Using Monolog

Here is an example of how to use Rollbar as a handler for Monolog:

```php

    use Rollbar\Rollbar;
    use Monolog\Logger;
    use Rollbar\Monolog\Handler\RollbarHandler;
    
    Rollbar::init(
    	array(
    		'access_token' => 'xxx',
    		'environment' => 'development'
    	)
    );
    
    // create a log channel
    $log = new Logger('RollbarHandler');
    $log->pushHandler(new RollbarHandler(Rollbar::logger(), Logger::WARNING));
    
    // add records to the log
    $log->addWarning('Foo');

```

*Note:* Currently there is an outstanding Pull Request [Sync RollbarHandler with the latest changes rollbar/rollbar package](https://github.com/Seldaek/monolog/pull/1042) in `Seldaek:monolog` repository with an update for our `Monolog\Handler\RollbarHandler`. Unfortunately, it has not been merged in by the maintainers yet. In meantime, we included the Monolog handler as part of our repository. We recommend using `Rollbar\Monolog\Handler\RollbarHandler` from `rollbar/rollbar-php` repo. Do *NOT* use `Monolog\Handler\RollbarHandler` from `Seldaek:monolog` repo as it is outdated.

## Configuration

### Asynchronous Reporting

By default, payloads (batched or not) are sent as part of script execution. This is easy to configure but may negatively impact performance. With some additional setup, payloads can be written to a local relay file instead; that file will be consumed by [rollbar-agent](https://github.com/rollbar/rollbar-agent) asynchronously. To turn this on, set the following config params:

```php

    $config = array(
      // ... rest of current config
      'handler' => 'agent',
      'agent_log_location' => '/var/www'  // not including final slash. must be writeable by the user php runs as.
    );
    
```

You'll also need to run the agent. See the [rollbar-agent docs](https://github.com/rollbar/rollbar-agent) for setup instructions.


### Centralized Log Aggregation with fluentd

If you have a [fluentd](https://www.fluentd.org/) instance running available you can forward payloads to this instance. To turn this on, set the following config params.

```php

    $config = array(
      // ... rest of current config
      'handler' => 'fluent',
      'fluent_host' => 'localhost',  // localhost is the default setting but any other host IP or a unix socket is possible
      'fluent_port' => 24224, // 24224 is the default setting, please adapt it to your settings
      'fluent_tag' => 'rollbar', // rollbar is the default setting, you can adjust it to your needs
    );

```

Also you will have to install a suggested package `fluent/logger`.


### Configuration reference

All of the following options can be passed as keys in the `$config` array.

  <dl>
	
<dt>access_token
</dt>
<dd>Your project access token.
</dd>

<dt>agent_log_location
</dt>
<dd>Path to the directory where agent relay log files should be written. Should not include final slash. Only used when handler is `agent`.

Default: `/var/www`
</dd>

<dt>allow_exec
</dt>
<dd>If the branch option is not set, we will attempt to call out to git to discover the branch name
via the php `shell_exec` function call. If you do not want to allow `shell_exec` to be called, and therefore
possibly to not gather this context if you do not otherwise provide it via the separate
configuration option, then set this option to false.

Default: `true`
</dd>

<dt>endpoint
</dt>
<dd>The API URL to post to. Note: the URL has to end with a trailing slash.

Default: `https://api.rollbar.com/api/1/`
</dd>

<dt>base_api_url
</dt>
<dd><strong>Deprecated (use <i>endpoint</i> instead).</strong> The base api url to post to.

Default: `https://api.rollbar.com/api/1/`
</dd>

<dt>branch
</dt>
<dd>Name of the current branch.
</dd>

<dt>capture_error_stacktraces
</dt>
<dd>Record full stacktraces for PHP errors.

Default: `true`
</dd>

<dt>check_ignore
</dt>
<dd>Function called before sending payload to Rollbar, return true to stop the error from being sent to Rollbar.

Default: `null`

Parameters:

* *$isUncaught*: boolean value set to true if the error was an uncaught exception.
* *$exception*: a RollbarException instance that will allow you to get the message or exception
* *$payload*: an array containing the payload as it will be sent to Rollbar. Payload schema can be found at https://rollbar.com/docs/api/items_post/

Example::

```php

    $config = array(
        'access_token' => '...',
        'check_ignore' => function ($isUncaught, $exception, $payload) {
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

<dt>code_version
</dt>
<dd>The currently-deployed version of your code/application (e.g. a Git SHA). Should be a string.

Default: empty string
</dd>

<dt>custom
</dt>
<dd>An array of key/value pairs which will be merged with the custom data in the final payload of
all items sent to Rollbar. This allows for custom data to be added globally to all payloads. Any key
in this array which is also present in the custom data passed to a log/debug/error/... call will
have the value of the latter.
</dd>

<dt>enabled
</dt>
<dd>Enable or disable Rollbar in your project. This can be changed at runtime with `Rollbar::enable()` and `Rollbar::disable()` or through `Rollbar::configure()`.
	
Default: `true`
</dd>

<dt>environment
</dt>
<dd>Environment name, e.g. `production` or `development`

Default: `production`
</dd>

<dt>error_sample_rates
</dt>
<dd>Associative array mapping error numbers to sample rates. Sample rates are ratio out of 1, e.g. 0 is "never report", 1 is "always report", and 0.1 is "report 10% of the time". Sampling is done on a per-error basis.

Default: empty array, meaning all errors are reported.
</dd>

<dt>exception_sample_rates
</dt>
<dd>Associative array mapping exception classes to sample rates. Sample rates are ratio out of 1, e.g. 0 is "never report", 1 is "always report", and 0.1 is "report 10% of the time". Sampling is done on a per-exception basis. It also respects class inheritance meaning if Exception is at 1.0 then ExceptionSublcass is also at 1.0, unless explicitly configured otherwise. If ExceptionSubclass is set to 0.5, but Exception is at 1.0 then Exception and all its' subclasses run at 1.0, except for ExceptionSubclass and its' subclasses which run at 0.5. Names of exception classes should NOT be prefixed with additional `\` for global namespace, i.e. Rollbar\SampleException and NOT \Rollbar\SampleException.

Default: empty array, meaning all exceptions are reported.
</dd>

<dt>fluent_host</dt>
<dd>Either an `IPv4`, `IPv6`, or a `unix socket`.

Default: `127.0.0.1`
</dd>

<dt>fluent_port</dt>
<dd>The port on which the fluentd instance is listening on. If you use a unix socket this setting is ignored.

Default: `24224`
</dd>

<dt>fluent_tag</dt>
<dd>The tag of your fluentd filter and match sections. It can be any string, please consult the [fluentd documentation](http://docs.fluentd.org/) for valid tags.

Default: `rollbar`
</dd>

<dt>handler
</dt>
<dd>Either `blocking`, `agent`, or `fluent`. `blocking` uses curl to send requests immediately; `agent` writes a relay log to be consumed by [rollbar-agent](https://github.com/rollbar/rollbar-agent); `fluent` send the requests to a [fluentd](https://www.fluentd.org/) instance and requires the suggested package `fluent/logger`.

Default: `blocking`
</dd>

<dt>host
</dt>
<dd>Server hostname.

Default: `null`, which will result in a call to `gethostname()` (or `php_uname('n')` if that function does not exist)
</dd>

<dt>include_error_code_context
</dt>
<dd>A boolean that indicates you wish to gather code context for instances of PHP Errors.
This can take a while because it requires reading the file from disk, so it's off by default.

Default: `false`
</dd>

<dt>include_exception_code_context
</dt>
<dd>A boolean that indicates you wish to gather code context for instances of PHP Exceptions.
This can take a while because it requires reading the file from disk, so it's off by default.

Default: `false`
</dd>

<dt>included_errno
</dt>
<dd>A bitmask that includes all of the error levels to report. E.g. (E_ERROR \| E_WARNING) to only report E_ERROR and E_WARNING errors. This will be used in combination with `error_reporting()` to prevent reporting of errors if `use_error_reporting` is set to `true`.

Default: `(E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)`
</dd>

<dt>logger
</dt>
<dd>An object that has a `log($level, $message)` method. If provided, will be used by RollbarNotifier to log messages.
</dd>

<dt>person
</dt>
<dd>An associative array containing data about the currently-logged in user. Required: `id`, optional: `username`, `email`. All values are strings.
</dd>

<dt>person_fn
</dt>
<dd>A function reference (string, etc. - anything that [call_user_func()](http://php.net/call_user_func) can handle) returning an array like the one for 'person'.
</dd>

<dt>root
</dt>
<dd>Path to your project's root dir
</dd>

<dt>scrub_fields
</dt>
<dd>Array of field names to scrub out of \_POST and \_SESSION. Values will be replaced with asterisks. If overriding, make sure to list all fields you want to scrub, not just fields you want to add to the default. Param names are converted to lowercase before comparing against the scrub list.

Default: `('passwd', 'password', 'secret', 'confirm_password', 'password_confirmation', 'auth_token', 'csrf_token')`
</dd>

<dt>scrub_whitelist
</dt>
<dd>Array of fields that you do NOT want to be scrubbed even if they match entries in scrub_fields. Entries should be provided in associative array dot notation, i.e. `data.person.username`.
</dd>

<dt>timeout
</dt>
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

    $config['proxy'] = "127.0.0.1:8080";
    
```

E.g. Using a local proxy with basic authentication

```php

    $config['proxy'] = array(
        'address' => '127.0.0.1:8080',
        'username' => 'my_user',
        'password' => 'my_password'
    );
    
```

Default: No proxy
</dd>

<dt>send_message_trace</dt>
<dd>Should backtrace be include with string messages reported to Rollbar

Default: `false`
</dd>


<dt>include_raw_request_body</dt>
<dd>Include the raw request body from php://input in payloads.
Note: in PHP < 5.6 if you enable this, php://input will be empty for PUT requests
as Rollbar SDK will read from it 
@see http://php.net/manual/pl/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-descriptiop
If you still want to read the request body for your PUT requests Rollbar SDK saves
the content of php://input in $_SERVER['php://input']

Default: `false`
</dd>

<dt>local_vars_dump</dt>
<dd>Should backtraces include arguments passed to stack frames.

Default: `true`
</dd>

<dt>verbosity</dt>
<dd>This configuration option will make the SDK more verbose. It can be used to
troubleshoot problems with the library. The supported values are the level
constants of `\Psr\Log\LogLevel`. These internal logs are written to
`sys_get_temp_dir() . '/rollbar.debug.log` (usually `/tmp/rollbar.debug.log`). 
`\Psr\Log\LogLevel::INFO` results in some troubleshooting information. 
`\Psr\Log\LogLevel::DEBUG` results in all available information, including 
scrubbed payloads and responses from the API. If you are running into problems 
with the SDK and would like to submit a GitHub issue, we highly recommend that 
you set `verbosity` to `\Psr\Log\LogLevel::DEBUG` and include the contents of 
your `rollbar.debug.log` (NOTE: remember to scrub your access token before
posting online).

Default: `\Psr\Log\LogLevel::ERROR` (no internal logging)
</dd>

</dl>

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

Example use of exception_sample_rates:

```php

    $config['exception_sample_rates'] = array(
        // Exception omitted, so defaults to 1
        
        // SometimesException set at 0.1 so only reported 10% of the time
        'SometimesException' => 0.1,
    );
    
```

Example use of person_fn:

```php

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
    
```

## Related projects

A range of examples of using Rollbar PHP is available here: [Rollbar PHP Examples](https://github.com/rollbar/rollbar-php-examples).

A Wordpress Plugin is available through Wordpress Admin Panel or through Wordpress Plugin directory: [Rollbar Wordpress](https://wordpress.org/plugins/rollbar/)

A Laravel-specific package is available for integrating with Laravel: [Rollbar Laravel](https://github.com/rollbar/rollbar-php-laravel)

A CakePHP-specific package is avaliable for integrating with CakePHP 2.x:
[CakeRollbar](https://github.com/tranfuga25s/CakeRollbar)

A Flow-specific package is available for integrating with Neos Flow: [m12/flow-rollbar](https://packagist.org/packages/m12/flow-rollbar)

Yii package: [baibaratsky/yii-rollbar](https://github.com/baibaratsky/yii-rollbar)

Yii2 package: [baibaratsky/yii2-rollbar](https://github.com/baibaratsky/yii2-rollbar)

## Help / Support

If you run into any issues, please email us at [support@rollbar.com](mailto:support@rollbar.com)

You can also find us in IRC: [#rollbar on chat.freenode.net](irc://chat.freenode.net/rollbar)

For bug reports, please [open an issue on GitHub](https://github.com/rollbar/rollbar-php/issues/new).
The best, configure your Rollbar with `verbosity` at level `\Psr\Log\LogLevel::DEBUG` and attach
the contents of your `sys_get_temp_dir() . '/rollbar.debug.log'` (usually `/tmp/rollbar.debug.log`).


## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request


## Testing
Tests are in `tests`.
To run the tests: `composer test`
To fix code style issues: `composer fix`
