<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MongoDB Collection Configuration
    |--------------------------------------------------------------------------
    |
    | The MongoDB collection name where movie documents are stored.
    |
    */

    'collection' => env('MONGODB_COLLECTION', 'movies'),

    /*
    |--------------------------------------------------------------------------
    | Vector Field Configuration
    |--------------------------------------------------------------------------
    |
    | The field path in MongoDB documents where vector embeddings are stored.
    |
    */

    'field_path' => env('VECTOR_FIELD_PATH', 'embeddings'),

    /*
    |--------------------------------------------------------------------------
    | Vector Index Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for MongoDB Atlas Vector Search index creation and management.
    |
    */

    'index' => [
        // The name of the vector search index in MongoDB Atlas
        'name' => env('VECTOR_INDEX_NAME', 'movies_vector_index'),

        // Number of dimensions in the embedding vectors (must match the embedding model)
        'dimensions' => (int) env('VECTOR_DIMENSIONS', 512),

        // Similarity function for vector comparisons (cosine, euclidean, or dotProduct)
        'similarity' => env('VECTOR_SIMILARITY', 'cosine'),

        // Maximum time to wait for index deletion to propagate (seconds)
        'delete_wait_time' => (int) env('VECTOR_INDEX_DELETE_WAIT_TIME', 30),

        // Interval between deletion status checks (seconds)
        'delete_wait_interval' => (int) env('VECTOR_INDEX_DELETE_WAIT_INTERVAL', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Generation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for batch processing and safety limits when generating
    | embeddings via the Voyage AI service.
    |
    */

    'embeddings' => [
        // Number of movies to process in each batch
        'batch_size' => (int) env('EMBEDDING_BATCH_SIZE', 10),

        // Hard limit on total movies processed per invocation (prevents high API costs)
        'safety_limit' => (int) env('EMBEDDING_SAFETY_LIMIT', 100),

        // Delay between batches in milliseconds (for rate limiting)
        'batch_delay_ms' => (int) env('EMBEDDING_BATCH_DELAY_MS', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vector Search Configuration
    |--------------------------------------------------------------------------
    |
    | Default parameters for vector search queries.
    |
    */

    'search' => [
        // Maximum number of results to return
        'limit' => (int) env('VECTOR_SEARCH_LIMIT', 10),

        // Number of candidates to consider during search (affects accuracy vs performance)
        'num_candidates' => (int) env('VECTOR_SEARCH_NUM_CANDIDATES', 100),
    ],

];
