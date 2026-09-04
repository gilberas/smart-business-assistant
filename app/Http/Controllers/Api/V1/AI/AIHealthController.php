<?php

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\AIService;
use Illuminate\Http\JsonResponse;

class AIHealthController extends Controller
{
    public function __construct(
        private readonly AIService $aiService,
    ) {}

    public function index(): JsonResponse
    {
        $health = $this->aiService->health();
        return response()->json(['data' => $health]);
    }

    public function models(): JsonResponse
    {
        $models = $this->aiService->listModels();
        return response()->json(['data' => $models]);
    }
}
