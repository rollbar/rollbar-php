# Rollbar-PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rollbar/rollbar.svg?style=flat-square)](https://packagist.org/packages/rollbar/rollbar)
![Build Status](https://github.com/rollbar/rollbar-php/workflows/Rollbar-PHP%20CI/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/rollbar/rollbar.svg?style=flat-square)](https://packagist.org/packages/rollbar/rollbar)

This library detects errors and exceptions in your application and sends them
to [Rollbar] for alerts, reporting, and analysis.

[Rollbar]: https://rollbar.com

# Quickstart

If you've never used Rollbar before, [sign up for a Rollbar account][signup]
and follow the simple, three-step tour. In no time, you'll be capturing errors
and exceptions thrown in your code.

If you already have a Rollbar account, [log in to your Rollbar account][login].
From the Settings > Project Access Token menu, click Create New Access Token.
Copy the `post_client_item` value and paste it into the code below.

```php
require 'vendor/autoload.php'; // composer require rollbar/rollbar:^2

\Rollbar\Rollbar::init(
  [ 'access_token' => '***', 'environment' => 'development' ]
);
```

For detailed usage instructions and configuration reference, refer to our
[PHP SDK docs][sdkdoc].

[login]: https://rollbar.com/login/
[sdkdoc]:https://docs.rollbar.com/docs/php
[signup]: https://rollbar.com/signup

# Getting Help

* If you have a question, ask in our [Discussion Q&amp;A][q-a]
* To report a bug, raise [an issue][issue]
* For account service, reach out to [support@rollbar.com][support]

[issue]:https://github.com/rollbar/rollbar-php/issues
[q-a]:https://github.com/rollbar/rollbar-php/discussions/categories/q-a
[support]:mailto:support@rollbar.com

# Releases, Versions, and PHP Compatibility

Major releases of this library support major versions of PHP, as follows:

* For PHP 8, choose the `master` branch.
* For PHP 7, choose a `2.x` release.
* For PHP 5, choose a `1.x` release.

To obtain a release, download an archive from the [Releases] page or use
composer:

```sh
# for PHP 8 compatibility
$ composer require rollbar/rollbar:dev-master

# for PHP 7 compatibility
$ composer require rollbar/rollbar:^2

# for PHP 5 compatibility
$ composer require rollbar/rollbar:^1
```

Refer to [CHANGELOG.md] for a complete history.

[CHANGELOG.md]: ./CHANGELOG.md
[Releases]: https://github.com/rollbar/rollbar-php/releases

# License
Rollbar-PHP is free software released under the MIT License. See [LICENSE]
for details.

[LICENSE]: ./LICENSE
