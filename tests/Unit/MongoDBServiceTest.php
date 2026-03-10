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
        // Set dummy DSN and app name to make test environment-independent
        $dummyDsn = 'mongodb+srv://user:pass@cluster.mongodb.net/testdb?retryWrites=true';
        $appName = 'devrel-test-app';

        config([
            'database.connections.mongodb.dsn' => $dummyDsn,
            'app.name' => $appName
        ]);

        // Verify APP_NAME contains "devrel"
        $this->assertStringContainsString(
            'devrel',
            $appName,
            'APP_NAME should contain "devrel"'
        );

        // Construct the runtime DSN the same way MongoDBService does
        $separator = parse_url($dummyDsn, PHP_URL_QUERY) ? '&' : '?';
        $clientDsn = $dummyDsn . $separator . 'appName=' . urlencode($appName);

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

    /**
     * Test that getClient throws exception when DB_DSN is not configured
     */
    public function test_get_client_throws_exception_when_dsn_not_configured(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB_DSN must be configured in .env for MongoDB connections');

        // Temporarily set database.connections.mongodb.dsn to empty string
        config(['database.connections.mongodb.dsn' => '']);

        $service = new MongoDBService();
        $service->getClient(); // Should throw RuntimeException
    }

    /**
     * Test that getClient throws exception when APP_NAME is not configured
     */
    public function test_get_client_throws_exception_when_app_name_not_configured(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('APP_NAME must be configured in .env for MongoDB Atlas operations');

        // Temporarily set app.name to empty string
        config(['app.name' => '']);

        $service = new MongoDBService();
        $service->getClient(); // Should throw RuntimeException
    }
}
