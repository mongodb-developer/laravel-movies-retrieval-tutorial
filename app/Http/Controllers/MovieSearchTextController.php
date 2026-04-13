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
            // Search fields: ['title', 'plot', 'fullplot', 'cast', 'directors']
            $results = Movie::query()
                ->aggregate()
                ->search(
                    operator: Search::text(
                        path: config('fulltext.index.fields'),
                        query: $query
                    ),
                    index: config('fulltext.index.name')
                )
                ->addFields(score: ['$meta' => 'searchScore'])
                ->limit(10)
                ->get();

            // Format results with score and selected fields
            $formattedResults = collect($results)->map(fn($movie) => $this->formatSearchResult($movie));

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
     * for typical movie search behavior with layered title boosting:
     * - Title exact phrase (10x): Highest weight for exact title matches
     * - Title fuzzy text (7x): High weight for partial/fuzzy title matches
     * - Cast (5x): High weight for actor-based searches
     * - Plot (3x): Medium-high weight for curated summaries
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
            // Layered weighting strategy for optimal relevance:
            // - Title phrase match: 10x (exact phrase "The Godfather" in title)
            // - Title text match: 7x (fuzzy/partial matches like "Godfather")
            // - Cast: 5x (actor-based searches - high priority)
            // - Plot: 3x (curated summary, captures movie essence)
            // - Directors: 2x (director-based searches)
            // - Fullplot: 1x (comprehensive details, useful but can be verbose)
            $results = Movie::query()
                ->aggregate()
                ->search(
                    operator: Search::compound(
                        should: [
                            // Exact phrase match on title - highest priority
                            Search::phrase(
                                path: 'title',
                                query: $query,
                                score: ['boost' => ['value' => 10]]
                            ),
                            // Fuzzy text match on title - high priority
                            Search::text(
                                path: 'title',
                                query: $query,
                                score: ['boost' => ['value' => 7]]
                            ),
                            Search::text(
                                path: 'cast',
                                query: $query,
                                score: ['boost' => ['value' => 5]]
                            ),
                            Search::text(
                                path: 'plot',
                                query: $query,
                                score: ['boost' => ['value' => 3]]
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
                ->limit(10)
                ->get();

            // Format results with score and selected fields
            $formattedResults = collect($results)->map(fn($movie) => $this->formatSearchResult($movie));

            return response()->json([
                'query' => $query,
                'results' => $formattedResults,
                'count' => $formattedResults->count(),
                'search_type' => 'weighted',
                'weights' => [
                    'title_phrase' => 10,
                    'title_text' => 7,
                    'plot' => 3,
                    'cast' => 5,
                    'directors' => 2,
                    'fullplot' => 1
                ],
                'index' => config('fulltext.index.name')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Weighted full-text search failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format a movie search result with score and selected fields.
     *
     * @param array $movie Raw movie document from search results
     * @return array Formatted movie result
     */
    private function formatSearchResult(array $movie): array
    {
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
    }
}
