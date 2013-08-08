# Changelog

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
- Error handler function (`report_php_error`) now always returns false, so that the default php error handler still runs. This is  a breaking change if your code relied on the old behavior where the error handler did *not* ever halt script execution.
