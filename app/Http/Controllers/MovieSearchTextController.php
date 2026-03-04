<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MongoDB\Builder\Search;

class MovieSearchTextController extends Controller
{
    /**
     * Perform naive full-text search on movies.
     *
     * This endpoint performs a simple text search across indexed fields
     * without any field weighting or boosting.
     */
    public function naive(Request $request): JsonResponse
    {
        try {
            $query = $request->input('query');

            if (!$query) {
                return response()->json([
                    'error' => 'Query parameter is required'
                ], 400);
            }

            // Perform full-text search using Eloquent with Search builder
            // Using aggregation to access search scores
            $results = Movie::query()
                ->aggregate()
                ->search(
                    operator: Search::text(
                        path: config('fulltext.index.fields', ['title', 'plot', 'fullplot', 'cast', 'directors']),
                        query: $query
                    ),
                    index: config('fulltext.index.name')
                )
                ->addFields(score: ['$meta' => 'searchScore'])
                ->limit(config('fulltext.search.limit', 10))
                ->get();

            // Format results with score and selected fields
            $formattedResults = collect($results)->map(function ($movie) {
                return [
                    '_id' => ['$oid' => (string) ($movie['_id'] ?? '')],
                    'title' => $movie['title'] ?? null,
                    'plot' => $movie['plot'] ?? null,
                    'fullplot' => $movie['fullplot'] ?? null,
                    'genres' => $movie['genres'] ?? [],
                    'year' => $movie['year'] ?? null,
                    'cast' => $movie['cast'] ?? [],
                    'directors' => $movie['directors'] ?? [],
                    'poster' => $movie['poster'] ?? null,
                    'score' => $movie['score'] ?? null
                ];
            });

            return response()->json([
                'query' => $query,
                'results' => $formattedResults,
                'count' => $formattedResults->count(),
                'search_type' => 'naive',
                'index' => config('fulltext.index.name')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Full-text search failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform weighted full-text search on movies.
     *
     * This endpoint performs text search with field-specific weights optimized
     * for typical movie search behavior:
     * - Title (5x): Highest weight for exact title matches
     * - Plot (3x): Medium-high weight for curated summaries
     * - Cast (2x): Medium weight for actor-based searches
     * - Directors (2x): Medium weight for director-based searches
     * - Fullplot (1x): Standard weight for comprehensive descriptions
     */
    public function weighted(Request $request): JsonResponse
    {
        try {
            $query = $request->input('query');

            if (!$query) {
                return response()->json([
                    'error' => 'Query parameter is required'
                ], 400);
            }

            // Perform weighted full-text search using Eloquent with compound Search builder
            // Weights based on typical movie search patterns:
            // - Title: 5 (primary identifier, exact matches rank highest)
            // - Plot: 3 (curated summary, captures movie essence)
            // - Cast: 2 (actor-based searches, 10-15% of queries)
            // - Directors: 2 (director-based searches, 10-15% of queries)
            // - Fullplot: 1 (comprehensive details, useful but can be verbose)
            $results = Movie::query()
                ->aggregate()
                ->search(
                    operator: Search::compound(
                        should: [
                            Search::text(
                                path: 'title',
                                query: $query,
                                score: ['boost' => ['value' => 5]]
                            ),
                            Search::text(
                                path: 'plot',
                                query: $query,
                                score: ['boost' => ['value' => 3]]
                            ),
                            Search::text(
                                path: 'cast',
                                query: $query,
                                score: ['boost' => ['value' => 2]]
                            ),
                            Search::text(
                                path: 'directors',
                                query: $query,
                                score: ['boost' => ['value' => 2]]
                            ),
                            Search::text(
                                path: 'fullplot',
                                query: $query,
                                score: ['boost' => ['value' => 1]]
                            ),
                        ]
                    ),
                    index: config('fulltext.index.name')
                )
                ->addFields(score: ['$meta' => 'searchScore'])
                ->limit(config('fulltext.search.limit', 10))
                ->get();

            // Format results with score and selected fields
            $formattedResults = collect($results)->map(function ($movie) {
                return [
                    '_id' => ['$oid' => (string) ($movie['_id'] ?? '')],
                    'title' => $movie['title'] ?? null,
                    'plot' => $movie['plot'] ?? null,
                    'fullplot' => $movie['fullplot'] ?? null,
                    'genres' => $movie['genres'] ?? [],
                    'year' => $movie['year'] ?? null,
                    'cast' => $movie['cast'] ?? [],
                    'directors' => $movie['directors'] ?? [],
                    'poster' => $movie['poster'] ?? null,
                    'score' => $movie['score'] ?? null
                ];
            });

            return response()->json([
                'query' => $query,
                'results' => $formattedResults,
                'count' => $formattedResults->count(),
                'search_type' => 'weighted',
                'weights' => ['title' => 5, 'plot' => 3, 'cast' => 2, 'directors' => 2, 'fullplot' => 1],
                'index' => config('fulltext.index.name')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Weighted full-text search failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
