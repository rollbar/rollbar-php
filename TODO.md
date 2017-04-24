 1. Include Code Context
 1. Implement Agent Sender
 1. Get and sanitize function arguments from backtrace:
   * You can get argument names like so: http://stackoverflow.com/a/2692514/456188
   * You can use `array_combine` to get a kwargs version of the arguments
   * You can then sanitize based on argument name