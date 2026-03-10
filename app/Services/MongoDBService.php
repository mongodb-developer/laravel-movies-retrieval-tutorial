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
    private string $dsn;
    private string $appName;
    private string $database;

    /**
     * Create a new MongoDBService instance.
     *
     * @param string|null $dsn MongoDB connection string (DSN)
     * @param string|null $appName Application name for Atlas logs
     * @param string|null $database Database name
     */
    public function __construct(
        ?string $dsn = null,
        ?string $appName = null,
        ?string $database = null
    ) {
        $this->dsn = $dsn ?? config('database.connections.mongodb.dsn');
        $this->appName = $appName ?? config('app.name');
        $this->database = $database ?? config('database.connections.mongodb.database');

        // Fail fast if DB_DSN is not configured
        if (empty($this->dsn)) {
            throw new \RuntimeException(
                'DB_DSN must be configured in .env for MongoDB connections. ' .
                'Set DB_DSN to your MongoDB connection string (e.g., mongodb+srv://user:pass@cluster.mongodb.net/database).'
            );
        }

        // Fail fast if APP_NAME is not configured
        // This strict validation is intentional for tutorial/learning purposes
        // to ensure proper configuration and visibility in Atlas logs
        if (empty($this->appName)) {
            throw new \RuntimeException(
                'APP_NAME must be configured in .env for MongoDB Atlas operations. ' .
                'This is required for proper client identification in Atlas logs.'
            );
        }
    }

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
        // Dynamically append appName to connection string
        $separator = parse_url($this->dsn, PHP_URL_QUERY) ? '&' : '?';
        $clientDsn = $this->dsn . $separator . 'appName=' . urlencode($this->appName);

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
        $client = $this->getClient();
        return $client->selectCollection($this->database, $collectionName);
    }
}
