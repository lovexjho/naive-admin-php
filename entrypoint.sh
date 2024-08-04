#!/bin/bash

# Check if vendor directory exists
if [ ! -d "/opt/www/vendor" ]; then
    # Run composer install if vendor directory does not exist
    if [ "$APP_ENV" == "dev" ]; then
      composer install
    else
      composer install --no-dev
    fi
fi

# Run Hyperf application
php bin/hyperf.php "$@"
