# Clear all cached config
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Generate a new app key (this will update your .env file)
php artisan key:generate

# Test the health endpoint
php artisan serve --host=0.0.0.0 --port=8000
