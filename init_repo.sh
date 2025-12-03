# Check if the vendor directory does not exist
if [ ! -d "vendor" ]; then
    # Run composer install
    composer install
    cp .env.example .env
    php artisan key:generate
fi


# try opening port 80 on Github Codespaces
if [ -z "$CODESPACE_NAME" ]; then
    echo "Not running in a codespace, exiting."
    exit
fi

echo "Exposing ports"
gh codespace ports visibility 80:public -c $CODESPACE_NAME