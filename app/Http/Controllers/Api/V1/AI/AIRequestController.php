<?php

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\AIRequestFormRequest;
use App\Http\Resources\AI\AIRequestResource;
use App\DTOs\AI\AIRequestDTO;
use App\DTOs\AI\MessageDTO;
use App\Repositories\AI\AIRequestRepository;
use App\Services\AI\AIService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIRequestController extends Controller
{
    public function __construct(
        private readonly AIService $aiService,
        private readonly AIRequestRepository $requestRepository,
        private readonly TenantService $tenantService,
    ) {}

    private function businessId(): int
    {
        return $this->tenantService->currentOrFail()->id;
    }

    public function generate(AIRequestFormRequest $request): JsonResponse
    {
        $messageDTOs = [];
        foreach ($request->input('messages') as $msg) {
            $messageDTOs[] = new MessageDTO(
                role: $msg['role'],
                content: $msg['content'],
            );
        }

        $aiRequest = new AIRequestDTO(
            messages: $messageDTOs,
            model: $request->input('model'),
            temperature: $request->input('temperature'),
            topP: $request->input('top_p'),
            maxTokens: $request->input('max_tokens'),
            stream: $request->input('stream', false),
            businessId: $this->businessId(),
            userId: $request->user()?->id,
        );

        $response = $this->aiService->generateText($aiRequest);

        return response()->json($response->toArray());
    }

    public function history(Request $request): JsonResponse
    {
        $requests = $this->requestRepository->paginate(
            $this->businessId(),
            $request->only(['provider', 'model', 'status', 'date_from', 'date_to']),
            $request->input('per_page', 15),
        );

        return response()->json([
            'data' => AIRequestResource::collection($requests),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $request = $this->requestRepository->findById($id);
        if (!$request || $request->business_id !== $this->businessId()) {
            abort(404);
        }

        return response()->json([
            'data' => new AIRequestResource($request),
        ]);
    }
}
