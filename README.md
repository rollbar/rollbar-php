<p align="center">
  <img alt="rollbar-logo" src="https://user-images.githubusercontent.com/3300063/207964480-54eda665-d6fe-4527-ba51-b0ab3f41f10b.png" />
</p>

<h1 align="center">Rollbar PHP SDK</h1>

<p align="center">
  <strong>Proactively discover, predict, and resolve errors in real-time with <a href="https://rollbar.com">Rollbar’s</a> error monitoring platform. <a href="https://rollbar.com/signup/">Start tracking errors today</a>!</strong>
</p>


[![Latest Version on Packagist](https://img.shields.io/packagist/v/rollbar/rollbar.svg?style=flat-square)](https://packagist.org/packages/rollbar/rollbar)
[![CI for Rollbar-PHP, master](https://github.com/rollbar/rollbar-php/actions/workflows/ci.yml/badge.svg)](https://github.com/rollbar/rollbar-php/actions/workflows/ci.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/rollbar/rollbar.svg?style=flat-square)](https://packagist.org/packages/rollbar/rollbar)

---

## Key benefits of using Rollbar PHP SDK are:
- **Frameworks:** Rollbar php supports many popular php frameworks such as <a href="https://docs.rollbar.com/docs/laravel">Laravel</a>, <a href="https://docs.rollbar.com/docs/codeigniter">CodeIgniter</a>, <a href="https://docs.rollbar.com/docs/symfony">Symfony</a> and many more!
- **Plugins:** Rollbar php has plugin support for <a href="https://docs.rollbar.com/docs/php-heroku">Heroku</a>, <a href="https://docs.rollbar.com/docs/wordpress">Wordpress</a>, <a href="https://docs.rollbar.com/docs/php-integration-with-rollbarjs">Rollbar.js</a> and more.
- **Automatic error grouping:** Rollbar aggregates Occurrences caused by the same error into Items that represent application issues. <a href="https://docs.rollbar.com/docs/grouping-occurrences">Learn more about reducing log noise</a>.
- **Advanced search:** Filter items by many different properties. <a href="https://docs.rollbar.com/docs/search-items">Learn more about search</a>.
- **Customizable notifications:** Rollbar supports several messaging and incident management tools where your team can get notified about errors and important events by real-time alerts. <a href="https://docs.rollbar.com/docs/notifications">Learn more about Rollbar notifications</a>.



# Quickstart

If you've never used Rollbar before, [sign up for a Rollbar account][signup]
and follow the simple, three-step tour. In no time, you'll be capturing errors
and exceptions thrown in your code.

If you already have a Rollbar account, [log in to your Rollbar account][login].
From the Settings > Project Access Token menu, click Create New Access Token.
Copy the `post_client_item` value and paste it into the code below.

```php
require 'vendor/autoload.php'; // composer require rollbar/rollbar

\Rollbar\Rollbar::init([
    'access_token' => '***', 
    'environment'  => 'development',
]);
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

* For PHP 8, choose the `4.x` or `3.x` branch.
* For PHP 7, choose a `2.x` release.
* For PHP 5, choose a `1.x` release.

To obtain a release, download an archive from the [Releases] page or use
composer:

```sh
# for PHP 8 compatibility
$ composer require rollbar/rollbar:^4
# or
$ composer require rollbar/rollbar:^3

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
