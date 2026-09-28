#!/bin/bash

set -e

echo
echo "======================================"
echo " Laravel Dev Container"
echo "======================================"
echo

echo "==> PHP"
php --version | head -n 1

echo
echo "==> PHP extensions"
required_extensions=(
    bcmath
    intl
    mbstring
    pdo_mysql
    xml
    zip
)

for extension in "${required_extensions[@]}"; do
    if php -m | grep -qi "^${extension}$"; then
        echo "  ✓ ${extension}"
    else
        echo "  ✗ ${extension} MISSING"
    fi
done

echo
echo "==> Composer"
composer --version

echo
echo "==> Node.js"
node --version

echo
echo "==> npm"
npm --version

echo
echo "==> Laravel Installer"
if command -v laravel >/dev/null 2>&1; then
    laravel --version
else
    echo "WARNING: Laravel installer not found in PATH"
    echo "Composer global bin:"
    composer global config bin-dir --absolute
fi

echo
echo "==> Laravel"

php artisan --version

echo
echo "==> Laravel environment"

php artisan about --only=Environment

echo
echo "==> MariaDB client"
mariadb --version

echo
echo "==> Laravel/MariaDB database connection"

if php artisan migrate:status >/dev/null 2>&1; then
    echo "  ✓ Laravel database connection successful"
else
    echo "  ✗ Laravel could not connect to the database"
    exit 1
fi


echo
echo "==> Database migrations"

php artisan migrate:status

echo
echo "======================================"
echo " Dev Container ready"
echo "======================================"
echo
