[![Build Status](https://api.travis-ci.org/rollbar/rollbar-php.png)](https://travis-ci.org/rollbar/rollbar-php)

[Rollbar-PHP](https://github.com/rollbar/rollbar-php) detects errors and exceptions in your application and reports them to [Rollbar](https://rollbar.com) for alerts, reporting, and analysis.

Supported PHP versions: 5.3, 5.4, 5.5, 5.6, 7, 7.1, 7.2 and HHVM (currently tested on 3.6.6).

For PHP 5.3, make sure you install `packfire/php5.3-compat` as outlined in the suggested dependencies in `composer.json`.

## Quick start

```php
<?php
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
<?php
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

Be sure to replace ```POST_SERVER_ITEM_ACCESS_TOKEN``` with your project's ```post_server_item``` access token, which you can find in the Rollbar.com interface.

This will install an exception handler (with `set_exception_handler`) and an error handler (with `set_error_handler`). If you'd rather not do that:

```php
<?php
$set_exception_handler = false;
$set_error_handler = false;
Rollbar::init($config, $set_exception_handler, $set_error_handler);    
```

### For CodeIgniter Users

If you are using CodeIgniter you can place `Rollbar::init` in either of the two places:
* inside the Controller's constructor
```php
<?php
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
<?php
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

```bash
heroku addons:create rollbar:free
```

The `access_token` and `root` config variables will be automatically detected, so the config is simply:

```php
<?php
use Rollbar\Rollbar;
  
Rollbar::init(array(
    'environment' => 'production'
));    
```

## Integration with Rollbar.js

In case you want to report your JavaScript errors using [Rollbar.js](https://github.com/rollbar/rollbar.js), you can configure the SDK to enable Rollbar.js on your site. Example:

```php
<?php
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
<?php
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
<?php
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
<?php
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

If you're using dependency injection containers, you can create and get a `RollbarLogger` from the container and use it to initialize Rollbar error logging.

It's up to the container to properly create and configure the logger.

```php
<?php
use Rollbar\Rollbar;
use Rollbar\RollbarLogger;
    
$logger = $container->get(RollbarLogger::class);
    
// installs global error and exception handlers
Rollbar::init($logger);
```

## Using Monolog

Here is an example of how to use Rollbar as a handler for Monolog:

```php
<?php
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
<?php
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
<?php
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

