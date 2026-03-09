#!/bin/bash
cd /home/u257189575/domains/plum-gerbil-537001.hostingersite.com/public_html/backend
php artisan migrate --force
php artisan db:seed --force
