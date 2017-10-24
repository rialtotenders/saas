#!/bin/bash
set -e

#----------------------------------------------------------------------------------------

printf "\n\nRun NPM package manager https://www.npmjs.com/\n-----------\n";
if [ ! -d "node_modules" ]; then
    npm install
else
    echo 'npm installed';
fi

#----------------------------------------------------------------------------------------

printf "\n\nRun Composer https://getcomposer.org/\n-----------\n";
if [ ! -d "vendor" ]; then
    composer install
else
    echo 'composer installed';
fi

#----------------------------------------------------------------------------------------

printf "\n\nRun Bower package manager https://bower.io/\n-----------\n";
if [ ! -d "bower_components" ]; then
    bower install
else
    echo 'bower installed';
fi
#----------------------------------------------------------------------------------------

printf "\n\nRun gulp https://gulpjs.com/\n-----------\n";
if [ -d "node_modules" ]; then
    ./gulp
fi

#----------------------------------------------------------------------------------------

printf "\n\nCreate main website .env file\n-----------\n";
if [ ! -f "./.env" ]; then
    cp -n ./.env.example ./.env
    if [ $? -eq 0 ]; then echo "./.env ... DONE"; fi
else
    echo ".env exists";
fi

#----------------------------------------------------------------------------------------

printf "\n\nCreate projects .env files\n-----------\n";
for d in ./integer/*; do
    if [ ! -f "$d/.env" ]; then
        cp  ./.env.example "$d/.env";
        if [ $? -eq 0 ]; then echo "$d/.env ... DONE"; fi
    else
        echo "$d/.env exists";
    fi
done

#----------------------------------------------------------------------------------------

printf "\n\nSet main website folder permissions\n-----------\n";

find ./storage -type d -exec chmod 775 {} \;
if [ $? -eq 0 ]; then echo "./storage ... DONE"; fi

find ./themes/rialtotender/pages -type d -exec chmod 775 {} \;
if [ $? -eq 0 ]; then echo "./themes/rialtotender/pages ... DONE"; fi

find ./themes/rialtotender/partials -type d -exec chmod 775 {} \;
if [ $? -eq 0 ]; then echo "./themes/rialtotender/partials ... DONE"; fi

#----------------------------------------------------------------------------------------

printf "\n\nSet projects folder permissions\n-----------\n";
for d in ./integer/*; do
    find "$d/storage" -type d -exec chmod 775 {} \;

    if [ $? -eq 0 ]; then
        echo "$d/storage ... DONE";
    fi
done

#----------------------------------------------------------------------------------------

printf "\n\nGenerating laravel project key\n-----------\n";
php artisan key:generate

#----------------------------------------------------------------------------------------
app_key="$(sed -n -e '/^APP_KEY=/p' .env)"

printf "\n\nCopy APP_KEY to integer .env files\n-----------\n";

for d in ./integer/*; do
    sed "s/APP_KEY=.*/$(echo $app_key | sed -e 's/\\/\\\\/g; s/\//\\\//g; s/&/\\\&/g')/g" .env > "$d/.env";
    if [ $? -eq 0 ]; then
        echo "$d/.env ... DONE";
    fi
done

printf "\n\n-----------\n\nInstall COMPLETE\nPrease proceed to mysql setup at .env files\n";
