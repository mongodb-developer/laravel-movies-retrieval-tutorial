<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MongoDbTestController extends Controller
{
    /**
     * Test MongoDB Atlas connection and display collection information.
     */
    public function __invoke(): JsonResponse
    {
        try {
            // Test MongoDB connection
            $connection = DB::connection('mongodb');

            // Get database name
            $databaseName = config('database.connections.mongodb.database');

            // List all collections in the database
            $collections = $connection->getMongoDB()->listCollections();
            $collectionNames = [];
            foreach ($collections as $collection) {
                $collectionNames[] = $collection->getName();
            }

            // Check if 'movies' collection exists
            $moviesExists = in_array('movies', $collectionNames);

            // Get count of documents in movies collection if it exists
            $movieCount = 0;
            if ($moviesExists) {
                $movieCount = Movie::count();
            }

            return response()->json([
                'status' => 'success',
                'connection' => 'MongoDB connection successful',
                'database' => $databaseName,
                'collections_found' => count($collectionNames),
                'collections' => $collectionNames,
                'movies_collection' => [
                    'exists' => $moviesExists,
                    'document_count' => $movieCount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'connection' => 'MongoDB connection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
