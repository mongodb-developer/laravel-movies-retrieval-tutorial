<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Full-Text Search Index Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for MongoDB Atlas Full-Text (Lucene) Search index
    | creation and management.
    |
    */

    'index' => [
        // The name of the full-text search index in MongoDB Atlas
        'name' => env('FULLTEXT_INDEX_NAME', 'movies_fulltext_index'),

        // Fields to include in the full-text search index
        'fields' => [
            'title',
            'plot',
            'fullplot',
        ],

        // Maximum time to wait for index deletion to propagate (seconds)
        'delete_wait_time' => (int) env('FULLTEXT_INDEX_DELETE_WAIT_TIME', 30),

        // Interval between deletion status checks (seconds)
        'delete_wait_interval' => (int) env('FULLTEXT_INDEX_DELETE_WAIT_INTERVAL', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Full-Text Search Configuration
    |--------------------------------------------------------------------------
    |
    | Default parameters for full-text search queries.
    |
    */

    'search' => [
        // Maximum number of results to return
        'limit' => (int) env('FULLTEXT_SEARCH_LIMIT', 10),

        // Enable fuzzy matching for typos
        'fuzzy' => env('FULLTEXT_SEARCH_FUZZY', true),
    ],

];
