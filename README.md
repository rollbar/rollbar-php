# Rollbar-PHP [![Build Status](https://api.travis-ci.org/rollbar/rollbar-php.png)](https://travis-ci.org/rollbar/rollbar-php)

This library detects errors and exceptions in your application and reports them to [Rollbar](https://rollbar.com) for alerts, reporting, and analysis.

Supported PHP versions: 5.3, 5.4, 5.5, 5.6, 7, 7.1, 7.2 and HHVM (currently tested on 3.6.6).

# Setup Instructions

1. [Sign up for a Rollbar account](https://rollbar.com/signup)
2. Follow the [Quick Start](https://docs.rollbar.com/v1.0.0/docs/php#section-quick-start) instructions in our [PHP SDK docs](https://docs.rollbar.com/docs/php) to install rollbar-php and configure it for your platform.

# Usage and Reference

For complete usage instructions and configuration reference, see our [PHP SDK docs](https://docs.rollbar.com/docs/php).
  
# Release History & Changelog

See our [Releases](https://github.com/rollbar/rollbar-php/releases) page for a list of all releases, including changes.

# Related projects

A range of examples of using Rollbar PHP is available here: [Rollbar PHP Examples](https://github.com/rollbar/rollbar-php-examples).

A Wordpress Plugin is available through Wordpress Admin Panel or through Wordpress Plugin directory: [Rollbar Wordpress](https://wordpress.org/plugins/rollbar/)

A Laravel-specific package is available for integrating with Laravel: [Rollbar Laravel](https://github.com/rollbar/rollbar-php-laravel)

A CakePHP-specific package is avaliable for integrating with CakePHP 2.x:
[CakeRollbar](https://github.com/tranfuga25s/CakeRollbar)

A Flow-specific package is available for integrating with Neos Flow: [m12/flow-rollbar](https://packagist.org/packages/m12/flow-rollbar)

Yii package: [baibaratsky/yii-rollbar](https://github.com/baibaratsky/yii-rollbar)

Yii2 package: [baibaratsky/yii2-rollbar](https://github.com/baibaratsky/yii2-rollbar)

# Help / Support

If you run into any issues, please email us at [support@rollbar.com](mailto:support@rollbar.com)

For bug reports, please [open an issue on GitHub](https://github.com/rollbar/rollbar-php/issues/new).
The best, configure your Rollbar with `verbosity` at level `\Psr\Log\LogLevel::DEBUG` and attach
the contents of your `sys_get_temp_dir() . '/rollbar.debug.log'` (usually `/tmp/rollbar.debug.log`).

# Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

# Testing
Tests are in `tests`.
To run the tests: `composer test`
To fix code style issues: `composer fix`

# Tagging

1. `ROLLBAR_PHP_TAG=[version number]`
2. `git checkout master`
3. Update version numbers in `src/Payload/Notifier.php` and `tests/NotifierTest.php`.
4. `git add .`
5. `git commit -m"Bump version numbers"`.
6. `git push origin master`
7. `git tag v$ROLLBAR_PHP_TAG`
8. `git push --tags`

# License
Rollbar-PHP is free software released under the MIT License. See [LICENSE.txt](LICENSE.txt) for details.
