#!/bin/bash
set -e

#----------------------------------------------------------------------------------------

printf "\n\nRun main website migrations\n-----------\n";
php artisan october:up

#----------------------------------------------------------------------------------------

printf "\n\nBuild pages structure\n-----------\n";
php artisan perevorot:page:build

#----------------------------------------------------------------------------------------

cd ./integer
for d in *; do
    printf "\n\nRun $d website migrations\n-----------\n";
    php ../artisan integer:update --theme="$d"
done
cd ../

printf "\n\n-----------\n\nMigration COMPLETE\n\n";
