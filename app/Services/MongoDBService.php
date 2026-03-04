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
     * Get MongoDB collection with proper client configuration for Atlas operations.
     *
     * This method dynamically appends the appName parameter to the connection string,
     * which is required for Atlas Search Index operations (listSearchIndexes, createSearchIndex, etc.)
     * to function correctly.
     *
     * @param string $collectionName The name of the collection to access
     * @return Collection
     */
    public function getCollection(string $collectionName): Collection
    {
        $dsn = config('database.connections.mongodb.dsn');
        $database = config('database.connections.mongodb.database');
        $appName = config('app.name', 'Laravel');

        // Dynamically append appName to connection string
        $separator = parse_url($dsn, PHP_URL_QUERY) ? '&' : '?';
        $clientDsn = $dsn . $separator . 'appName=' . urlencode($appName);

        // Create MongoDB client with appName
        $client = new Client($clientDsn);

        return $client->selectCollection($database, $collectionName);
    }

    /**
     * Get the MongoDB client with proper appName configuration.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        $dsn = config('database.connections.mongodb.dsn');
        $appName = config('app.name', 'Laravel');

        // Dynamically append appName to connection string
        $separator = parse_url($dsn, PHP_URL_QUERY) ? '&' : '?';
        $clientDsn = $dsn . $separator . 'appName=' . urlencode($appName);

        return new Client($clientDsn);
    }
}
