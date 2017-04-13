 1. Include Code Context
 1. Implement Agent Sender
 1. Get and sanitize function arguments from backtrace:
   * You can get argument names like so: http://stackoverflow.com/a/2692514/456188
   * You can use `array_combine` to get a kwargs version of the arguments
   * You can then sanitize based on argument name

## github-122-scrubbing TO DO
1. Decide if the scrubbing code should be taken out of DataBuilder completely
3. Check if any other places require scrubbing test coverage (e.g. args and kwargs
as per POST_FORMAT.md)
4. Is scrubbing of the $extra_data argument for old report_* methods covered?