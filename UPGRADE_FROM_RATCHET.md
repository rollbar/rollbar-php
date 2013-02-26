# Upgrading from ratchetio-php

Replace your existing `ratchetio.php` with the latest [rollbar.php](https://github.com/rollbar/rollbar-php/raw/master/rollbar.php).

Optionally, search your app for references to `Ratchetio` and replace them with `Rollbar`. `rollbar.php` sets up a class alias from `Ratchetio` to `Rollbar` which is why this step is optional.