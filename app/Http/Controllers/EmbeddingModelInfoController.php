<?php

namespace App\Http\Controllers;

use App\Services\VoyageAIService;
use Illuminate\Http\JsonResponse;

class EmbeddingModelInfoController extends Controller
{
    /**
     * Test Voyage AI connection and return model information.
     */
    public function __invoke(VoyageAIService $voyageAI): JsonResponse
    {
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
    }
}
