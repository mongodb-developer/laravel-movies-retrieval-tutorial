<?php

use Illuminate\Support\Facades\Route;
use App\Models\Book;

Route::get('/hello', function () {
    return response()->json([
        'response' => 'hello world'
    ]);
});

Route::get('/getbook_isbn/{isbn}', function ($isbn) {
    $book = Book::find($isbn);

    if (!$book) {
        return response()->json([
            'error' => 'Book not found',
            'isbn' => $isbn
        ], 404);
    }

    return response()->json($book);
});

$createVectorIndexHandler = function () {
    try {
        $dsn = env('DB_DSN');
        $database = env('DB_DATABASE', 'library');

        $client = new MongoDB\Client($dsn);
        $db = $client->selectDatabase($database);

        // Check if vector index already exists
        $indexes = $db->books->listSearchIndexes();
        foreach ($indexes as $index) {
            if (isset($index['name']) && $index['name'] === 'books_vector_index') {
                return response()->json([
                    'response' => 'vector index already exists'
                ]);
            }
        }

        // Create vector search index
        $result = $db->command([
            'createSearchIndexes' => 'books',
            'indexes' => [
                [
                    'name' => 'books_vector_index',
                    'type' => 'vectorSearch',
                    'definition' => [
                        'fields' => [
                            [
                                'type' => 'vector',
                                'path' => 'embeddings',
                                'numDimensions' => 1408,
                                'similarity' => 'cosine'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        return response()->json([
            'response' => 'vector search index created successfully',
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

$createFullTextIndexHandler = function () {
    try {
        $dsn = env('DB_DSN');
        $database = env('DB_DATABASE', 'library');

        $client = new MongoDB\Client($dsn);
        $db = $client->selectDatabase($database);

        // Check if full-text search index already exists
        $indexes = $db->books->listSearchIndexes();
        foreach ($indexes as $index) {
            if (isset($index['name']) && $index['name'] === 'books_fulltext_index') {
                return response()->json([
                    'response' => 'full-text search index already exists'
                ]);
            }
        }

        // Create full-text search index using Atlas Search (Lucene)
        $result = $db->command([
            'createSearchIndexes' => 'books',
            'indexes' => [
                [
                    'name' => 'books_fulltext_index',
                    'definition' => [
                        'mappings' => [
                            'dynamic' => false,
                            'fields' => [
                                'title' => [
                                    'type' => 'string',
                                    'analyzer' => 'lucene.standard'
                                ],
                                'synopsis' => [
                                    'type' => 'string',
                                    'analyzer' => 'lucene.standard'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        return response()->json([
            'response' => 'full-text search index created successfully',
            'result' => $result
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to create full-text search index',
            'message' => $e->getMessage()
        ], 500);
    }
};

Route::get('/create-fulltext-search-index', $createFullTextIndexHandler);
Route::post('/create-fulltext-search-index', $createFullTextIndexHandler);

Route::get('/get-books-fulltext/{search}', function ($search) {
    try {
        if (!$search) {
            return response()->json([
                'error' => 'Search parameter is required'
            ], 400);
        }

        $searchPhrase = urldecode($search);

        $dsn = env('DB_DSN');
        $database = env('DB_DATABASE', 'library');

        $client = new MongoDB\Client($dsn);
        $db = $client->selectDatabase($database);
        $collection = $db->books;

        // Perform full-text search using MongoDB Atlas Search (Lucene)
        $pipeline = [
            [
                '$search' => [
                    'index' => 'books_fulltext_index',
                    'text' => [
                        'query' => $searchPhrase,
                        'path' => ['title', 'synopsis']
                    ]
                ]
            ],
            [
                '$project' => [
                    '_id' => 1,
                    'title' => 1,
                    'synopsis' => 1,
                    'score' => ['$meta' => 'searchScore']
                ]
            ],
            [
                '$limit' => 10
            ]
        ];

        $results = $collection->aggregate($pipeline)->toArray();

        return response()->json([
            'search' => $searchPhrase,
            'results' => $results,
            'count' => count($results)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Full-text search failed',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::post('/book-search-vector', function (Illuminate\Http\Request $request) {
    try {
        $query = $request->input('query');

        if (!$query) {
            return response()->json([
                'error' => 'Query parameter is required'
            ], 400);
        }

        // TODO: Convert query string to embedding vector
        // You need to call an embedding API here (e.g., OpenAI, Cohere, etc.)
        // For now, this is a placeholder
        $queryVector = []; // This should be a 1408-dimensional array

        if (empty($queryVector)) {
            return response()->json([
                'error' => 'Query embedding generation not implemented. Please provide embedding service configuration.'
            ], 501);
        }

        $dsn = env('DB_DSN');
        $database = env('DB_DATABASE', 'library');

        $client = new MongoDB\Client($dsn);
        $db = $client->selectDatabase($database);
        $collection = $db->books;

        // Perform vector search using MongoDB aggregation pipeline
        $pipeline = [
            [
                '$vectorSearch' => [
                    'index' => 'books_vector_index',
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
                    'authors' => 1,
                    'synopsis' => 1,
                    'cover' => 1,
                    'publisher' => 1,
                    'year' => 1,
                    'score' => ['$meta' => 'vectorSearchScore']
                ]
            ]
        ];

        $results = $collection->aggregate($pipeline)->toArray();

        return response()->json([
            'query' => $query,
            'results' => $results,
            'count' => count($results)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Vector search failed',
            'message' => $e->getMessage()
        ], 500);
    }
});
