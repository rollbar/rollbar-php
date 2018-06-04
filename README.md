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

## Installation using Composer

Add `rollbar/rollbar` to your `composer.json`:

```json

    {
        "require": {
            "rollbar/rollbar": "^1"
        }
    }
    
```

More installation options are available in [Rollbar Docs](https://docs.rollbar.com/v1.0.0/docs/php).

## Documentation

Full documentation is available in [Rollbar Docs](https://docs.rollbar.com/v1.0.0/docs/php).

## Related projects

A range of examples of using Rollbar PHP is available here: [Rollbar PHP Examples](https://github.com/rollbar/rollbar-php-examples).

A Wordpress Plugin is available through Wordpress Admin Panel or through Wordpress Plugin directory: [Rollbar Wordpress](https://wordpress.org/plugins/rollbar/)

A Laravel-specific package is available for integrating with Laravel: [Rollbar Laravel](https://github.com/rollbar/rollbar-php-laravel)

A CakePHP-specific package is avaliable for integrating with CakePHP 2.x:
[CakeRollbar](https://github.com/tranfuga25s/CakeRollbar)

A Flow-specific package is available for integrating with Neos Flow: [m12/flow-rollbar](https://packagist.org/packages/m12/flow-rollbar)

Yii package: [baibaratsky/yii-rollbar](https://github.com/baibaratsky/yii-rollbar)

Yii2 package: [baibaratsky/yii2-rollbar](https://github.com/baibaratsky/yii2-rollbar)

## Changelog

We moved away from using `CHANGELOG.md` in favor of [release notes](https://github.com/rollbar/rollbar-php/releases).

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
