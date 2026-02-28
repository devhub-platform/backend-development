#!/bin/bash
sudo systemctl restart php-fpm
php artisan queue:restart
