# Check if the vendor directory does not exist
if [ ! -d "vendor" ]; then
    # Run composer install
    composer install
    cp .env.example .env
    php artisan key:generate
fi


# Configuration
PORT_NUMBER=80
MAX_ATTEMPTS=10
SLEEP_TIME=5 # seconds

echo "Attempting to set port $PORT_NUMBER visibility to public..."

# Loop to retry the command
for i in $(seq 1 $MAX_ATTEMPTS); do
    # The 'gh codespace ports visibility' command is non-interactive by default
    # The -c or --codespace flag is used to select the codespace, but is usually
    # optional when running inside the codespace.
    
    # Run the command and capture success/failure
    if gh codespace ports visibility $PORT_NUMBER:public; then
        echo "Successfully set port $PORT_NUMBER to public."
        exit 0 # Exit the script successfully
    fi
    
    echo "Attempt $i/$MAX_ATTEMPTS failed. Waiting $SLEEP_TIME seconds..."
    sleep $SLEEP_TIME
done

echo "Error: Could not set port $PORT_NUMBER to public after $MAX_ATTEMPTS attempts."
exit 1