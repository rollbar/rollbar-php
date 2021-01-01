FROM shivammathur/node:xenial

VOLUME [ "/opt/rollbar/rollbar-php" ]
WORKDIR /opt/rollbar/rollbar-php
ENTRYPOINT [ "/bin/bash" ]

RUN apt-get update \
  && apt-get install -y ca-certificates git vim tree

RUN spc --php-version "5.6" --extensions "curl" --coverage "xdebug"
