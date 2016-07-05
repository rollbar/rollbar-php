# Changelog

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
