# Check if the vendor directory does not exist
if [ ! -d "vendor" ]; then
    # Run composer install
    composer install
    cp .env.example .env
    php artisan key:generate
fi