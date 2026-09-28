# Extinguard
Offline-first fire extinguisher management and inspection system

# Start the services
composer run dev

# Destroy Database
php artisan migrate:fresh

# Seed Database (DatabaseSeeder.php)
php artisan db:seed

# Destroy Database and Repopulate with the DatabaseSeeder.php
php artisan migrate:fresh --seed