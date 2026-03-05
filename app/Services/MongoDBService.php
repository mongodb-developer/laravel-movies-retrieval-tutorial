<?php

namespace App\Services;

use MongoDB\Client;
use MongoDB\Collection;

/**
 * Service for handling MongoDB operations, especially for Atlas Search Indexes.
 *
 * This service provides methods for connecting to MongoDB with proper appName
 * configuration, which is required for Atlas Search Index API operations.
 */
class MongoDBService
{
    /**
     * Get the MongoDB client with proper appName configuration.
     *
     * This method dynamically appends the appName parameter to the connection string,
     * which is required for Atlas Search Index operations (listSearchIndexes, createSearchIndex, etc.)
     * to function correctly.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        $dsn = config('database.connections.mongodb.dsn');
        $appName = config('app.name');

        // Fail fast if APP_NAME is not configured
        // OPTIONAL in production, added for DEVREL usage
        if (empty($appName)) {
            throw new \RuntimeException(
                'APP_NAME must be configured in .env for MongoDB Atlas operations. ' .
                'This is required for proper client identification in Atlas logs.'
            );
        }

        // Dynamically append appName to connection string
        $separator = parse_url($dsn, PHP_URL_QUERY) ? '&' : '?';
        $clientDsn = $dsn . $separator . 'appName=' . urlencode($appName);

        return new Client($clientDsn);
    }

    /**
     * Get MongoDB collection with proper client configuration for Atlas operations.
     *
     * Uses getClient() internally to ensure consistent client configuration.
     *
     * @param string $collectionName The name of the collection to access
     * @return Collection
     */
    public function getCollection(string $collectionName): Collection
    {
        $database = config('database.connections.mongodb.database');
        $client = $this->getClient();

        return $client->selectCollection($database, $collectionName);
    }
}
