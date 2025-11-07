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



$createVectorIndexHandler = function () {
    try {
        $dsn = env('DB_DSN');
        $database = env('DB_DATABASE', 'sample_mflix');
        $collection = 'movies';
        $indexName = 'movies_vector_index';

        // Get vector configuration from environment
        $vectorDimensions = (int) env('VECTOR_DIMENSIONS', 512);
        $vectorSimilarity = env('VECTOR_SIMILARITY', 'cosine');

        $client = new MongoDB\Client($dsn);
        $db = $client->selectDatabase($database);

        // Check if vector index already exists
        $indexes = $db->$collection->listSearchIndexes();
        foreach ($indexes as $index) {
            if (isset($index['name']) && $index['name'] === $indexName) {
                return response()->json([
                    'response' => 'vector index already exists',
                    'index_name' => $indexName,
                    'collection' => $collection,
                    'dimensions' => $vectorDimensions,
                    'similarity' => $vectorSimilarity
                ]);
            }
        }

        // Create vector search index
        $result = $db->command([
            'createSearchIndexes' => $collection,
            'indexes' => [
                [
                    'name' => $indexName,
                    'type' => 'vectorSearch',
                    'definition' => [
                        'fields' => [
                            [
                                'type' => 'vector',
                                'path' => 'embeddings',
                                'numDimensions' => $vectorDimensions,
                                'similarity' => $vectorSimilarity
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        return response()->json([
            'response' => 'vector search index created successfully',
            'index_name' => $indexName,
            'collection' => $collection,
            'dimensions' => $vectorDimensions,
            'similarity' => $vectorSimilarity,
            'result' => $result
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to create vector search index',
            'message' => $e->getMessage()
        ], 500);
    }
};

Route::get('/create-vector-index', $createVectorIndexHandler);
Route::post('/create-vector-index', $createVectorIndexHandler);


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

        // Get configuration
        $dsn = env('DB_DSN');
        $database = env('DB_DATABASE', 'sample_mflix');
        $collection = 'movies';
        $indexName = 'movies_vector_index';

        $client = new MongoDB\Client($dsn);
        $db = $client->selectDatabase($database);

        // Perform vector search using MongoDB aggregation pipeline
        $pipeline = [
            [
                '$vectorSearch' => [
                    'index' => $indexName,
                    'path' => 'embeddings',
                    'queryVector' => $queryVector,
                    'numCandidates' => 100,
                    'limit' => 10
                ]
            ],
            [
                '$project' => [
                    '_id' => 1,
                    'title' => 1,
                    'plot' => 1,
                    'fullplot' => 1,
                    'genres' => 1,
                    'year' => 1,
                    'cast' => 1,
                    'directors' => 1,
                    'poster' => 1,
                    'score' => ['$meta' => 'vectorSearchScore']
                ]
            ]
        ];

        $results = $db->$collection->aggregate($pipeline)->toArray();

        return response()->json([
            'query' => $query,
            'results' => $results,
            'count' => count($results),
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
