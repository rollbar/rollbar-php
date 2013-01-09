# Changelog

**0.4.0**
- Error handler function (`report_php_error`) now always returns false, so that the default php error handler still runs. This is  a breaking change if your code relied on the old behavior where the error handler did *not* ever halt script execution.
