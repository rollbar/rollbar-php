```
{
  // Required: access_token
  // An access token with scope "post_server_item" or "post_client_item".
  // A post_client_item token must be used if the "platform" is "browser", "android", "ios", "flash", or "client"
  // A post_server_item token should be used for other platforms.
  "access_token": "aaaabbbbccccddddeeeeffff00001111",

  // Required: data
  "data": {

    // Required: environment
    // The name of the environment in which this occurrence was seen.
    // A string up to 255 characters. For best results, use "production" or "prod" for your
    // production environment.
    // You don't need to configure anything in the Rollbar UI for new environment names;
    // we'll detect them automatically.
    "environment": "production",

    // Required: body
    // The main data being sent. It can either be a message, an exception, or a crash report.
    "body": {

      // Required: "trace", "trace_chain", "message", or "crash_report" (exactly one)
      // If this payload is a single exception, use "trace"
      // If a chain of exceptions (for languages that support inner exceptions), use "trace_chain"
      // If a message with no stack trace, use "message"
      // If an iOS crash report, use "crash_report"

      // Option 1: "trace"
      "trace": {

        // Required: frames
        // A list of stack frames, ordered such that the most recent call is last in the list.
        "frames": [
          // Each frame is an object.
          {
            // Required: filename
            // The filename including its full path.
            "filename": "/Users/brian/www/mox/mox/views/project.py",

            // Optional: lineno
            // The line number as an integer
            "lineno": 26,

            // Optional: colno
            // The column number as an integer
            "colno": 8,

            // Optional: method
            // The method or function name
            "method": "index",

            // Optional: code
            // The line of code
            "code": "_save_last_project(request, project_id, force=True)",

            // Optional: context
            // Additional code before and after the "code" line
            "context": {
              // Optional: pre
              // List of lines of code before the "code" line
              "pre": [
                "project = request.context",
                "project_id = project.id"
              ],

              // Optional: post
              // List of lines of code after the "code" line
              "post": []
            },

            // Optional: args
            // List of values of positional arguments to the method/function call
            // NOTE: as this can contain sensitive data, you may want to scrub the values
            "args": ["<Request object>", 25],

            // Optional: kwargs
            // Object of keyword arguments (name => value) to the method/function call
            // NOTE: as this can contain sensitive data, you may want to scrub the values
            "kwargs": {
              "force": true
            }
          },
          {
            "filename": "/Users/brian/www/mox/mox/views/project.py",
            "lineno": 497,
            "method": "_save_last_project",
            "code": "user = foo"
          }
        ],

        // Required: exception
        // An object describing the exception instance.
        "exception": {
          // Required: class
          // The exception class name.
          "class": "NameError",

          // Optional: message
          // The exception message, as a string
          "message": "global name 'foo' is not defined",

          // Optional: description
          // An alternate human-readable string describing the exception
          // Usually the original exception message will have been machine-generated;
          // you can use this to send something custom
          "description": "Something went wrong while trying to save the user object"
        }

      },

      // Option 2: "trace_chain"
      // Used for exceptions with inner exceptions or causes
      "trace_chain": [
        // Each element in the list should be a "trace" object, as shown above
        // Must contain at least one element.
      ],

      // Option 3: "message"
      // Only one of "trace", "trace_chain", "message", or "crash_report" should be present.
      // Presence of a "message" key means that this payload is a log message.
      "message": {

        // Required: body
        // The primary message text, as a string
        "body": "Request over threshold of 10 seconds",

        // Can also contain any arbitrary keys of metadata. Their values can be any valid JSON.
        // For example:

        "route": "home#index",
        "time_elapsed": 15.23

      },

      // Option 4: "crash_report"
      // Only one of "trace", "trace_chain", "message", or "crash_report" should be present.
      "crash_report": {
        // Required: raw
        // The raw crash report, as a string
        // Rollbar expects the format generated by rollbar-ios
        "raw": "<crash report here>"
      }

    },

    // Optional: level
    // The severity level. One of: "critical", "error", "warning", "info", "debug"
    // Defaults to "error" for exceptions and "info" for messages.
    // The level of the *first* occurrence of an item is used as the item's level.
    "level": "error",

    // Optional: timestamp
    // When this occurred, as a unix timestamp.
    "timestamp": 1369188822,

    // Optional: code_version
    // A string, up to 40 characters, describing the version of the application code
    // Rollbar understands these formats:
    // - semantic version (i.e. "2.1.12")
    // - integer (i.e. "45")
    // - git SHA (i.e. "3da541559918a808c2402bba5012f6c60b27661c")
    // If you have multiple code versions that are relevant, those can be sent inside "client" and "server"
    // (see those sections below)
    // For most cases, just send it here.
    "code_version": "3da541559918a808c2402bba5012f6c60b27661c"

    // Optional: platform
    // The platform on which this occurred. Meaningful platform names:
    // "browser", "android", "ios", "flash", "client", "heroku", "google-app-engine"
    // If this is a client-side event, be sure to specify the platform and use a post_client_item access token.
    "platform": "linux",

    // Optional: language
    // The name of the language your code is written in.
    "language": "python",

    // Optional: framework
    // The name of the framework your code uses
    "framework": "pyramid",

    // Optional: context
    // An identifier for which part of your application this event came from.
    // Items can be searched by context (prefix search)
    // For example, in a Rails app, this could be `controller#action`.
    // In a single-page javascript app, it could be the name of the current screen or route.
    "context": "project#index",

    // Optional: request
    // Data about the request this event occurred in.
    "request": {
      // Can contain any arbitrary keys. Rollbar understands the following:

      // url: full URL where this event occurred
      "url": "https://rollbar.com/project/1",

      // method: the request method
      "method": "GET",

      // headers: object containing the request headers.
      "headers": {
        // Header names should be formatted like they are in HTTP.
        "Accept": "text/html",
        "Referer": "https://rollbar.com/"
      },

      // params: any routing parameters (i.e. for use with Rails Routes)
      "params": {
        "controller": "project",
        "action": "index"
      },

      // GET: query string params
      "GET": {},

      // query_string: the raw query string
      "query_string": "",

      // POST: POST params
      "POST": {},

      // body: the raw POST body
      "body": "",

      // user_ip: the user's IP address as a string.
      // Can also be the special value "$remote_ip", which will be replaced with the source IP of the API request.
      // Will be indexed, as long as it is a valid IPv4 address.
      "user_ip": "100.51.43.14"

    },

    // Optional: person
    // The user affected by this event. Will be indexed by ID, username, and email.
    // People are stored in Rollbar keyed by ID. If you send a multiple different usernames/emails for the
    // same ID, the last received values will overwrite earlier ones.
    "person": {
      // Required: id
      // A string up to 40 characters identifying this user in your system.
      "id": "12345",

      // Optional: username
      // A string up to 255 characters
      "username": "brianr",

      // Optional: email
      // A string up to 255 characters
      "email": "brian@rollbar.com"
    },

    // Optional: server
    // Data about the server related to this event.
    "server": {
      // Can contain any arbitrary keys. Rollbar understands the following:

      // host: The server hostname. Will be indexed.
      "host": "web4",

      // root: Path to the application code root, not including the final slash.
      // Used to collapse non-project code when displaying tracebacks.
      "root": "/Users/brian/www/mox",

      // branch: Name of the checked-out source control branch. Defaults to "master"
      "branch": "master",

      // Optiona: code_version
      // String describing the running code version on the server
      // See note about "code_version" above
      "code_version": "b6437f45b7bbbb15f5eddc2eace4c71a8625da8c",

      // (Deprecated) sha: Git SHA of the running code revision. Use the full sha.
      "sha": "b6437f45b7bbbb15f5eddc2eace4c71a8625da8c"
    },

    // Optional: client
    // Data about the client device this event occurred on.
    // As there can be multiple client environments for a given event (i.e. Flash running inside
    // an HTML page), data should be namespaced by platform.
    "client": {
      // Can contain any arbitrary keys. Rollbar understands the following:

      "javascript": {

        // Optional: browser
        // The user agent string
        "browser": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3)",

        // Optional: code_version
        // String describing the running code version in javascript
        // See note about "code_version" above
        "code_version": "b6437f45b7bbbb15f5eddc2eace4c71a8625da8c"

        // Optional: source_map_enabled
        // Set to true to enable source map deobfuscation
        // See the "Source Maps" guide for more details.
        "source_map_enabled": false,

        // Optional: guess_uncaught_frames
        // Set to true to enable frame guessing
        // See the "Source Maps" guide for more details.
        "guess_uncaught_frames": false

      }
    },

    // Optional: custom
    // Any arbitrary metadata you want to send. "custom" itself should be an object.
    "custom": {},

    // Optional: fingerprint
    // A string controlling how this occurrence should be grouped. Occurrences with the same
    // fingerprint are grouped together. See the "Grouping" guide for more information.
    // Should be a string up to 40 characters long; if longer than 40 characters, we'll use its SHA1 hash.
    // If omitted, we'll determine this on the backend.
    "fingerprint": "50a5ef9dbcf9d0e0af2d4e25338da0d430f20e52",

    // Optional: title
    // A string that will be used as the title of the Item occurrences will be grouped into.
    // Max length 255 characters.
    // If omitted, we'll determine this on the backend.
    "title": "NameError when setting last project in views/project.py",

    // Optional: uuid
    // A string, up to 36 characters, that uniquely identifies this occurrence.
    // While it can now be any latin1 string, this may change to be a 16 byte field in the future.
    // We recommend using a UUID4 (16 random bytes).
    // The UUID space is unique to each project, and can be used to look up an occurrence later.
    // It is also used to detect duplicate requests. If you send the same UUID in two payloads, the second
    // one will be discarded.
    "uuid": "d4c7acef55bf4c9ea95e4fe9428a8287",

    // Optional: notifier
    // Describes the library used to send this event.
    "notifier": {
      // Optional: name
      // Name of the library
      "name": "pyrollbar",

      // Optional: version
      // Library version string
      "version": "0.5.5"
    }

  }
}
```
