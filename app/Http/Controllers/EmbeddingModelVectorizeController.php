<?php

namespace App\Http\Controllers;

use App\Services\VoyageAIService;
use Illuminate\Http\JsonResponse;

class EmbeddingModelVectorizeController extends Controller
{
    /**
     * Generate a vector embedding for a single text input.
     */
    public function __invoke(string $input, VoyageAIService $voyageAI): JsonResponse
    {
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
    }
}
