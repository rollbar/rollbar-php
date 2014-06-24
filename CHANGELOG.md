# Changelog

**0.9.4**
- New `use_error_reporting` flag that when enabled will respect the current `error_reporting()` level when deciding to report an error ([#29](https://github.com/rollbar/rollbar-php/pull/29))
- Access token no longer required if using the agent handler

**0.9.3**
- Walk payloads to ensure strings are correctly utf-8 encoded for json encoding

**0.9.2**
- Append timestamp (in milliseconds) to agent log file names, to prevent collisions.

**0.9.1**
- Lazy create the agent log file ([#22](https://github.com/rollbar/rollbar-php/pull/22))

**0.9.0**
- Added `included_errno`, which allows specifying which set of error levels to send to Rollbar (instead of everything below a certain level). This replaces `max_errno`.
- Changed the default settings to no longer send E_NOTICE errors.
- Removed the max_errno config option.
- Added a performance optimization which sends the access token as a header.

**0.8.1**
- Added support for more seamless configuration on Heroku

**0.8.0**
- Added ability to disable the notifier's fatal error handler ([#18](https://github.com/rollbar/rollbar-php/pull/18))

**0.7.0**
- Fix regression introduced in 0.5.6 which would prevent the default php error handler from running, resulting in scripts no longer halting after such errors.

**0.6.4**
- Composer package defenition optimizations
- Use subclass for Ratchetio backwards-compatibility layer instead of a class_alias

**0.6.3**
- Fix issue where POST params could get clobbered while scrubbing
- Convert internal methods from `private` to `protected` for better extensibility

**0.6.2**
- Adding "pass" to default scrub fields

**0.6.1**
- Respect HTTP_X_FORWARDED_ http headers for request url construction

**0.6.0**
- Don't report errors suppressed with '@' by default

**0.5.6**
- A uuid is now generated and sent with each item

**0.5.5**
- Added `code_version` configuration setting

**0.5.4**
- Fix E_WARNING when scrubbing a param that is an array

**0.5.3**
- Scrub fields from session params too (instead of just POST). Add csrf_token and auth_token to list of default scrub fields.

**0.5.2**
- Fix compatability issue with PHP 5.2.

**0.5.1**
- Adding ability to write to rollbar-agent files

**0.5.0**
- Rename to rollbar

**0.4.2**
- Added new default scrub params

**0.4.1**
- Added optional extra_data param to report_message()

**0.4.0**
- Error handler function (`report_php_error`) now always returns false, so that the default php error handler still runs. This is a breaking change if your code relied on the old behavior where the error handler did *not* ever halt script execution.
