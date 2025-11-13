<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\JsonResponse;

class GetMovieByTitleController extends Controller
{
    /**
     * Retrieve a movie by its exact title.
     */
    public function __invoke(string $title): JsonResponse
    {
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
    }
}
