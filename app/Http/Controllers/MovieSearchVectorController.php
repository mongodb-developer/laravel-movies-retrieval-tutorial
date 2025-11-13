<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Services\VoyageAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieSearchVectorController extends Controller
{
    /**
     * Perform semantic vector search on movies.
     */
    public function __invoke(Request $request, VoyageAIService $voyageAI): JsonResponse
    {
        try {
            $query = $request->input('query');

            if (!$query) {
                return response()->json([
                    'error' => 'Query parameter is required'
                ], 400);
            }

            // Generate embedding for the query using VoyageAI
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
    }
}
