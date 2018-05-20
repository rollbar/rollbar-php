# Changelog

## 1.5.1
- #367 Newest version 1.5.0 missing changelog, Notifier version not bumped
- #366 Make the anonymized IP addresses indexable
- Removed `CHANGELOG.md` in favor of using [release notes](https://github.com/rollbar/rollbar-php/releases)

## 1.5.0
- #362 - Fix running isolated tests by requiring the Config.php file with ROLLBAR_INCLUDED_ERRNO_BITMASK definition
- #353 - Only collect person.id by default for person tracking
- #354 - Allow IP collection to be easily turned on and off in config
- #355 - Anonymize IP address config option

## 1.4.1
- Temporarily add `\Rollbar\Monolog\Handler\MonologHandler` until `Seldaek:monolog` PR 1042 gets merged into their `master`
- Refactor JSON encoding mechanis to limit calls to `json_encode` to minimum with `\Rollbar\Payload\EncodedPayload`
- Optimize performance of `StringsStrategy` and `FramesStrategy` truncation strategies
- Remove `RawStrategy` and `MinBodyStrategy` completely (they are not adding any value - the same results are achieved by the combination of other strategies)
- Fix for non-encodable values in the payload data. This was occasionally causing `400` response codes from the API
- Updat code examples in README.md
- Lock Monolog dependency at `^1.23`. The implementation of `\Monolog\TestCase` class in their master is currently not stable
- Add instructions on using Rollbar with Monolog after bringing the `MonologHandler` into this repo
- Clean up `RollbarLogger` instances after each test in the testsuite so the configuration doesn't persist between test cases
- Add `composer performance` command to run performance test suite

## 1.4.0
- Add internal SDK debugging with `verbosity` configuration option
- `local_vars_dump` configuration option is now enabled by default
- Update rollbar.js snippet to v2.3.6
- Refactor error, fatal error and exception handling from the ground up
- Exception traces will now include an additional frame with the file name and line
  where the Exception was actually thrown
- Fix a bug where `E_USER_ERROR` type errors were reported twice
- Add enable / disable functionality to Rollbar class and `enabled` configuration option
- `Rollbar`'s class proxy methods will now return the return value of the proxied method

## 1.3.6

- Replace a leftover invocation to exec() with shell_exec()
- Eliminate error duplication on PHP 7+ / Symfony environments by expanding ignore to all Throwables
[293](https://github.com/rollbar/rollbar-php/issues/293)

## 1.3.5

- Fix sending $context argument from the log() method with exception logs.

## 1.3.4

- Increase the minimum version constraint for the monolog/monolog package to support composer --prefer-minimum
- Decrease the minimum version constraint for psr/log package to match the monolog/monolog package in --prefer-minimum
- Add support for dynamic custom data

## 1.3.3

- Remove fluent/logger from the required section of composer.json

## 1.3.2

- Performance improvments
- Include request body for PUT requests if `include_raw_request_body` is true

## 1.3.1

- Remove dependency on rollbar.js through composer
- Actually change the notifier version number constant
- Stop duplicate logging some errors [#240](https://github.com/rollbar/rollbar-php/pull/240)

## 1.3.0

- all of the senders should return a response
[#239](https://github.com/rollbar/rollbar-php/pull/239)
- add an explicit semicolon between snippet and custom js
[#238](https://github.com/rollbar/rollbar-php/pull/238)
- GitHub Issue #225: Allow custom JS addition in RollbarJsHelper
[#235](https://github.com/rollbar/rollbar-php/pull/235)
- Minor spacing and indentation on Scrubber
[#234](https://github.com/rollbar/rollbar-php/pull/234)
- GitHub Issue #227: Add curl_error() to check for log submission failures
[#231](https://github.com/rollbar/rollbar-php/pull/231)
- GitHub Issue 229: Make fluentd a suggest requirement rather than mandatory
[#230](https://github.com/rollbar/rollbar-php/pull/230)
- Github Issue 226: Update rollbar.js code snippet
[#228](https://github.com/rollbar/rollbar-php/pull/228)
- configuration option to disallow calling exec to get git information
[#223](https://github.com/rollbar/rollbar-php/pull/223)
- use the configuration array directly, don't nest it under options
[#222](https://github.com/rollbar/rollbar-php/pull/222)
- GitHub Issue #144: allow passing ROLLBAR_TEST_TOKEN in phpunit arguments
[#219](https://github.com/rollbar/rollbar-php/pull/219)
- Performance fixes
[#217](https://github.com/rollbar/rollbar-php/pull/217)

## 1.2.0

- No longer ignore the native PHP error handler:
  [#175](https://github.com/rollbar/rollbar-php/pull/175)
- Fix for when `HTTP_X_FORWARDED_PROTO` includes multiple values: [#179](https://github.com/rollbar/rollbar-php/pull/179)
- Add the `use_error_reporting` option back: [#182](https://github.com/rollbar/rollbar-php/pull/182)
- Fix a backwards compatibility bug with `base_api_url` which has since been deprecated in favor of
  `endpoint`: [#189](https://github.com/rollbar/rollbar-php/pull/189)
- Add a helper method to inject the javascript snippet into views:
  [#186](https://github.com/rollbar/rollbar-php/pull/186)
- Add option to include some local variables to stack traces:
  [#192](https://github.com/rollbar/rollbar-php/pull/192)
- Address an issue reading from php://input for PHP < 5.6 and provide a workaround:
  [#181](https://github.com/rollbar/rollbar-php/pull/181)
- Add static helper methods for Level: [#210](https://github.com/rollbar/rollbar-php/pull/210)
- Correctly reverse frames in a stacktrace: [#211](https://github.com/rollbar/rollbar-php/pull/211).
  NOTE: this behaviour is consistent with this library for versions less than 1.0, we consider it a
  bug that the frames were in the wrong order for versions 1.0.0 to 1.1.1. However, this change will
  cause the fingerprints of errors to change and therefore may result in some errors showing up as
  new when in fact they already existed with the stack trace in the opposite order.
- Allow sampling for exceptions: [#215](https://github.com/rollbar/rollbar-php/pull/215)
- Add a whitelist to prevent scrubbing of specific fields:
  [#216](https://github.com/rollbar/rollbar-php/pull/216)
- Various refactorings and cleanup

## 1.1.1

- Forgot to bump the version number in the README and in the Notifier configuration.

## 1.1.0

This release includes some new features, some improvements to existing functionality, and several
bug fixes. Below are the highlights of issues/PRs that are included in this release:

- GitHub Issue [#38](https://github.com/rollbar/rollbar-php/issues/38): truncate payload [#167](https://github.com/rollbar/rollbar-php/pull/167)
- GitHub Issue [#102](https://github.com/rollbar/rollbar-php/issues/102): Support the Forwarded (RFC 7239) header [#155](https://github.com/rollbar/rollbar-php/pull/155)
- GitHub Issue [#72](https://github.com/rollbar/rollbar-php/issues/72): status 200 when using set_exception_handler [#143](https://github.com/rollbar/rollbar-php/pull/143)
- GitHub Issue [#53](https://github.com/rollbar/rollbar-php/issues/53): Option to capture stack trace in report_message() [#145](https://github.com/rollbar/rollbar-php/pull/145)
- Fix how include_error_code_context works with defaults [#168](https://github.com/rollbar/rollbar-php/pull/168)
- Fix how we handle scrubbing related to query strings so that we don't accidentially urlencode
  things that should not be, such as sql queries [#164](https://github.com/rollbar/rollbar-php/pull/164)
- Bug: infinite loop when previous exception set [#158](https://github.com/rollbar/rollbar-php/pull/158) (@vilius-g)
- Bug: checkIgnore was not getting passed documented arguments [#152](https://github.com/rollbar/rollbar-php/pull/152)
- Only report legitimate fatal errors during shutdown rather than anything returned by
  error_get_last(), Fatal handler type check [#161](https://github.com/rollbar/rollbar-php/pull/161) (@vilius-g)
- Move packfire/php5.3-compat from require to suggest in composer.json [#169](https://github.com/rollbar/rollbar-php/pull/169) (@elazar)

## 1.0.1

- Bug fix related to scrubbing potential query strings
- Update notifier to send the correct version number in the payload

## 1.0.0

Almost everything has been refactored or rewritten. The updated README has all of the current
information on how to use the notifier. This release includes API that is backwards compatible with 0.18.2,
however this is for convenience only and the methods that have changed have been marked deprecated.

## 0.18.2

- removed type hinting from RollbarException

## 0.18.1

- added configuration switch for disabling utf 8 sanitization

## 0.18.0

- Added support for checkIgnore function See [#82](https://github.com/rollbar/rollbar-php/pull/82)

## 0.17.0

- Accidental tag of documentation change. No API change present.

## 0.16.0

Features:

- Added support for reporting errors in Command Line Scripts.
- Added (opt-in) support for capturing line of code and context around that code in stack traces. See [#76](https://github.com/rollbar/rollbar-php/pull/76)
- Added Level class with string constants for Level values.

Bug fixes:

- Fixed the severity level that E_PARSE errors are reproted at. See [#75](https://github.com/rollbar/rollbar-php/pull/75)
- Captured \Throwable rather than \Exception if using PHP 7

## 0.15.0

Bug fixes (all of which are unlikely, but possibly, breaking changes):

- Fix bug where `scrub_fields` were case-sensitive, instead of case-insensitive as the docs say. See [#63](https://github.com/rollbar/rollbar-php/pull/63)
- Fix bug where integer 0 keys would always be scrubbed. See [#64](https://github.com/rollbar/rollbar-php/pull/64) and [#65](https://github.com/rollbar/rollbar-php/pull/65)
- Fix detection of the current URL when the protocol is `https` but no `SERVER_PORT` is set. See [#50](https://github.com/rollbar/rollbar-php/pull/50)

## 0.14.0

Possibly-breaking changes:

- Fix bug where generated UUIDs could overlap if the application calls mt_srand() with a predictable value, and then Rollbar methods are called later. Rollbar now calls `mt_srand()` itself. This shouldn't affect anyone, unless you happen to be relying on the sequence of numbers coming from mt_rand across Rollbar calls.

## 0.13.0

Possibly-breaking changes (again relating to param scrubbing):

- Param scrubbing is now applied to query string params inside the request URL. See [#59](https://github.com/rollbar/rollbar-php/pull/59)


## 0.12.1

Bug fixes:

- `branch` now defaults to null (meaning it will not be set) instead of `master`. This fixes a bug where the Rollbar UI wouldn't use the "default branch" setting because it was being overridden by the value sent by rollbar-php. See [#58](https://github.com/rollbar/rollbar-php/pull/58).

## 0.12.0

Possibly-breaking changes (all related to param scrubbing):

- Param scrubbing now accepts a regex string for the key name. Key names starting with `/` are assumed to be a regex.
- Headers are now scrubbed
- Arrays are recursively scrubbed

## 0.11.2

Bug fixes:

- Fix issue where fatal E_PARSE errors were not reported. See [#55](https://github.com/rollbar/rollbar-php/issues/55)

## 0.11.1

- Add in dependency for cURL library to warn users if they do not have the cURL extension installed ([#54](https://github.com/rollbar/rollbar-php/pull/54))

## 0.11.0

New features:

- Added support for nested exceptions. See [#51](https://github.com/rollbar/rollbar-php/pull/51)

Possible breaking changes:

- Calling report_exception with a non-exception will result in the data being dropped and a message being logged to the Rollbar logger, instead of an empty message being reported
- Exceptions are now sent as trace_chain, not trace, so any Custom Grouping Rules in the Rollbar UI will need to be updated to use `body.trace_chain.*.` in place of `body.trace.`

## 0.10.0

New features:

- `report_exception` now accepts args for `extra_data` and `payload_data`, providing full access to the Rollbar API. See [#47](https://github.com/rollbar/rollbar-php/pull/47)
- Fix a json_encode warning with utf8 request param keys. See [#42](https://github.com/rollbar/rollbar-php/pull/42)

Backwards-incompatible changes:

- Moved `rollbar.php` inside `src/`

Other:

- Added a unit test suite running on Travis CI (running for PHP 5.3 and higher)


## 0.9.12

- Fix PHP < 5.4 compatability. (Regression added in 0.9.11)


## 0.9.11

- Added proxy support. ([#44](https://github.com/rollbar/rollbar-php/pull/44))


## 0.9.10

New features:

- Add  ability to send fingerprint, title, and other advanced payload options in `Rollbar::report_message()`.


## 0.9.9

- Fix an error caused when `report_exception` is called with a non-object (e.g. `null`).


## 0.9.8

- Fixes a bug where `iconv()` will sometimes throw an error, (#36).


## 0.9.7

- Force cURL to use IPV4 (`CURLOPT_IPRESOLVE_V4`) if supported ([#35](https://github.com/rollbar/rollbar-php/pull/35))


## 0.9.6

- No longer have `error_reporting()` prevent simple log message reports ([#33](https://github.com/rollbar/rollbar-php/pull/33))

## 0.9.5

- Only define `ROLLBAR_INCLUDED_ERRNO_BITMASK` once to prevent warnings and test framework breakages ([#32](https://github.com/rollbar/rollbar-php/pull/32))


## 0.9.4

- New `use_error_reporting` flag that when enabled will respect the current `error_reporting()` level when deciding to report an error ([#29](https://github.com/rollbar/rollbar-php/pull/29))
- Access token no longer required if using the agent handler


## 0.9.3

- Walk payloads to ensure strings are correctly utf-8 encoded for json encoding


## 0.9.2

- Append timestamp (in milliseconds) to agent log file names, to prevent collisions.


## 0.9.1

- Lazy create the agent log file ([#22](https://github.com/rollbar/rollbar-php/pull/22))


## 0.9.0

- Added `included_errno`, which allows specifying which set of error levels to send to Rollbar (instead of everything below a certain level). This replaces `max_errno`.
- Changed the default settings to no longer send E_NOTICE errors.
- Removed the max_errno config option.
- Added a performance optimization which sends the access token as a header.

## 0.8.1

- Added support for more seamless configuration on Heroku


## 0.8.0

- Added ability to disable the notifier's fatal error handler ([#18](https://github.com/rollbar/rollbar-php/pull/18))


## 0.7.0

- Fix regression introduced in 0.5.6 which would prevent the default php error handler from running, resulting in scripts no longer halting after such errors.


## 0.6.4

- Composer package defenition optimizations
- Use subclass for Ratchetio backwards-compatibility layer instead of a class_alias


## 0.6.3

- Fix issue where POST params could get clobbered while scrubbing
- Convert internal methods from `private` to `protected` for better extensibility


## 0.6.2

- Adding "pass" to default scrub fields


## 0.6.1

- Respect HTTP_X_FORWARDED_ http headers for request url construction


## 0.6.0

- Don't report errors suppressed with '@' by default


## 0.5.6

- A uuid is now generated and sent with each item


## 0.5.5

- Added `code_version` configuration setting


## 0.5.4

- Fix E_WARNING when scrubbing a param that is an array


## 0.5.3

- Scrub fields from session params too (instead of just POST). Add csrf_token and auth_token to list of default scrub fields.


## 0.5.2

- Fix compatability issue with PHP 5.2.


## 0.5.1

- Adding ability to write to rollbar-agent files


## 0.5.0

- Rename to rollbar


## 0.4.2

- Added new default scrub params


## 0.4.1

- Added optional extra_data param to report_message()


## 0.4.0

- Error handler function (`report_php_error`) now always returns false, so that the default php error handler still runs. This is a breaking change if your code relied on the old behavior where the error handler did *not* ever halt script execution.
