<?php

use Illuminate\Support\Facades\Route;
use App\Models\Book;
use App\Models\Movie;
use App\Services\VoyageAIService;

Route::get('/hello', function () {
    return response()->json([
        'response' => 'hello world'
    ]);
});

Route::get('/mongodb-test', function () {
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
});

Route::get('/embedding-model-info', function () {
    $voyageAI = new VoyageAIService();

    if (!$voyageAI->isConfigured()) {
        return response()->json([
            'error' => 'VOYAGE_AI_API_KEY is not set in .env file',
            'configured' => false
        ], 400);
    }

    $result = $voyageAI->testConnection();

    if ($result['success']) {
        return response()->json([
            'status' => 'connected',
            'model' => $voyageAI->getModel(),
            'embedding_dimensions' => $result['data']['embedding_dimensions'],
            'api_response' => [
                'model' => $result['data']['model'],
                'usage' => $result['data']['usage'],
            ],
            'configured' => true
        ]);
    }

    return response()->json([
        'error' => 'Failed to connect to Voyage AI',
        'message' => $result['error'],
        'configured' => true
    ], $result['status_code'] ?? 500);
});

Route::get('/embedding-model-vectorize/{input}', function ($input) {
    $voyageAI = new VoyageAIService();

    if (!$voyageAI->isConfigured()) {
        return response()->json([
            'error' => 'VOYAGE_AI_API_KEY is not set in .env file',
            'configured' => false
        ], 400);
    }

    // Decode URL parameter
    $inputText = urldecode($input);

    $result = $voyageAI->generateEmbeddings([$inputText]);

    if ($result['success']) {
        $embedding = $result['embeddings'][0]['embedding'];
        return response()->json([
            'input' => $inputText,
            'embedding' => $embedding,
            'embedding_dimensions' => count($embedding),
            'model' => $voyageAI->getModel(),
            'usage' => $result['usage']
        ]);
    }

    return response()->json([
        'error' => 'Failed to generate embedding',
        'message' => $result['error']
    ], $result['status_code'] ?? 500);
});

Route::get('/get-movie-by-title/{title}', function ($title) {
    try {
        // Decode URL parameter
        $movieTitle = urldecode($title);

        // Find movie by exact title match
        $movie = Movie::where('title', $movieTitle)->first();

        if (!$movie) {
            return response()->json([
                'error' => 'No movie found',
                'title' => $movieTitle
            ], 404);
        }

        return response()->json($movie);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to retrieve movie',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::post('/movie-search-vector', function (Illuminate\Http\Request $request) {
    try {
        $query = $request->input('query');

        if (!$query) {
            return response()->json([
                'error' => 'Query parameter is required'
            ], 400);
        }

        // Generate embedding for the query using VoyageAI
        $voyageAI = new VoyageAIService();

        if (!$voyageAI->isConfigured()) {
            return response()->json([
                'error' => 'VOYAGE_AI_API_KEY is not set in .env file'
            ], 400);
        }

        $result = $voyageAI->generateEmbeddings([$query]);

        if (!$result['success']) {
            return response()->json([
                'error' => 'Failed to generate query embedding',
                'message' => $result['error']
            ], 500);
        }

        $queryVector = $result['embeddings'][0]['embedding'];

        // Perform vector search using Eloquent method
        $results = Movie::vectorSearch(
            index: config('vector.index.name'),
            path: config('vector.field_path'),
            queryVector: $queryVector,
            limit: config('vector.search.limit'),
            numCandidates: config('vector.search.num_candidates')
        );

        // Format results with score and selected fields
        $formattedResults = $results->map(function ($movie) {
            return [
                '_id' => $movie->_id,
                'title' => $movie->title,
                'plot' => $movie->plot,
                'fullplot' => $movie->fullplot,
                'genres' => $movie->genres,
                'year' => $movie->year,
                'cast' => $movie->cast,
                'directors' => $movie->directors,
                'poster' => $movie->poster,
                'score' => $movie->vectorSearchScore
            ];
        });

        return response()->json([
            'query' => $query,
            'results' => $formattedResults,
            'count' => $formattedResults->count(),
            'embedding_model' => $voyageAI->getModel(),
            'vector_dimensions' => count($queryVector)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Vector search failed',
            'message' => $e->getMessage()
        ], 500);
    }
});
