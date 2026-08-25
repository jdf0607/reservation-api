#!/bin/bash
source /opt/elasticbeanstalk/deployment/env
export $(cat /opt/elasticbeanstalk/deployment/env | xargs)
cd /var/app/current

# Ensure storage directories exist
mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p storage/logs
chown -R webapp:webapp storage

php artisan config:cache
php artisan route:cache
php artisan view:cache
