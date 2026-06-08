#!/bin/bash
# Script de bootstrap "production minimale".
# Il applique les migrations puis charge un jeu de données minimal et idempotent
# (référentiels RH/RDC/finance + matières) via `ProductionBootstrapSeeder`.
cd /home/u257189575/domains/plum-gerbil-537001.hostingersite.com/public_html/backend
php artisan migrate --force
# Données minimales nécessaires pour que les dropdowns de référence ne soient pas vides.
php artisan db:seed --force --class=ProductionBootstrapSeeder
