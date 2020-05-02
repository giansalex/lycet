#!/usr/bin/env sh

ARGS='--host 0.0.0.0 --port 8000'

# symfony bootstrap
ARGS="$ARGS --bootstrap=symfony --app-env=$APP_ENV --logging=0 --debug=0"

# make sure static-directory is not served by php-pm
ARGS="$ARGS --static-directory=''"

# no limits
ARGS="$ARGS --max-execution-time 0"

php vendor/bin/ppm start $ARGS $@

