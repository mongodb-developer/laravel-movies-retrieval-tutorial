<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MongoDBService;

class MongoDBServiceTest extends TestCase
{
    /**
     * Test that MongoDBService can be instantiated
     */
    public function test_service_can_be_instantiated(): void
    {
        $service = new MongoDBService();
        $this->assertInstanceOf(MongoDBService::class, $service);
    }

    /**
     * Test that runtime DSN contains appName with "devrel" in it
     */
    public function test_runtime_dsn_contains_devrel_in_app_name(): void
    {
        // Get the configuration values
        $dsn = config('database.connections.mongodb.dsn');
        $appName = config('app.name', 'Laravel');

        // Verify APP_NAME contains "devrel"
        $this->assertStringContainsString(
            'devrel',
            $appName,
            'APP_NAME should contain "devrel"'
        );

        // Construct the runtime DSN the same way MongoDBService does
        $separator = parse_url($dsn, PHP_URL_QUERY) ? '&' : '?';
        $clientDsn = $dsn . $separator . 'appName=' . urlencode($appName);

        // Verify the runtime DSN contains the appName parameter
        $this->assertStringContainsString(
            'appName=',
            $clientDsn,
            'Runtime DSN should contain appName parameter'
        );

        // Verify the runtime DSN contains "devrel"
        $this->assertStringContainsString(
            'devrel',
            $clientDsn,
            'Runtime DSN should contain "devrel" in appName parameter'
        );
    }

    /**
     * Test that getCollection returns MongoDB Collection instance
     */
    public function test_get_collection_returns_collection_instance(): void
    {
        $service = new MongoDBService();
        $collection = $service->getCollection('movies');

        $this->assertInstanceOf(
            \MongoDB\Collection::class,
            $collection,
            'getCollection should return MongoDB Collection instance'
        );
    }

    /**
     * Test that getClient returns MongoDB Client instance
     */
    public function test_get_client_returns_client_instance(): void
    {
        $service = new MongoDBService();
        $client = $service->getClient();

        $this->assertInstanceOf(
            \MongoDB\Client::class,
            $client,
            'getClient should return MongoDB Client instance'
        );
    }
}
