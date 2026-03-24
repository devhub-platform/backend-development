#!/bin/bash

cd /var/app/current

php artisan queue:restart

nohup php artisan queue:work --sleep=3 --tries=3 --timeout=90 >> storage/logs/queue.log 2>&1 &