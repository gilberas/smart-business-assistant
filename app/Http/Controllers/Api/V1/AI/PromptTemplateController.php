<?php

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\StorePromptTemplateRequest;
use App\Http\Requests\AI\UpdatePromptTemplateRequest;
use App\Http\Resources\AI\PromptTemplateResource;
use App\Models\PromptTemplate;
use App\Repositories\AI\PromptTemplateRepository;
use App\Services\AI\PromptService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptTemplateController extends Controller
{
    public function __construct(
        private readonly PromptTemplateRepository $templateRepository,
        private readonly PromptService $promptService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    private function businessId(): int
    {
        return $this->business()->id;
    }

    public function index(Request $request): JsonResponse
    {
        $templates = $this->templateRepository->all($this->businessId());

        if ($request->input('type')) {
            $templates = $templates->filter(fn($t) => $t->type === $request->input('type'))->values();
        }

        return response()->json([
            'data' => PromptTemplateResource::collection($templates),
        ]);
    }

    public function store(StorePromptTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['business_id'] = $this->businessId();

        $template = $this->templateRepository->create($data);

        $this->promptService->createVersion($template, $data['content'], 'Initial version');

        return response()->json([
            'data' => new PromptTemplateResource($template->load('versions')),
            'message' => __('ai.prompt_created'),
        ], 201);
    }

    public function show(PromptTemplate $promptTemplate): JsonResponse
    {
        return response()->json([
            'data' => new PromptTemplateResource($promptTemplate->load('versions')),
        ]);
    }

    public function update(UpdatePromptTemplateRequest $request, PromptTemplate $promptTemplate): JsonResponse
    {
        $data = $request->validated();
        $changelog = $data['changelog'] ?? '';
        unset($data['changelog']);

        $this->templateRepository->update($promptTemplate, $data);

        if (isset($data['content'])) {
            $this->promptService->createVersion($promptTemplate, $data['content'], $changelog);
        }

        return response()->json([
            'data' => new PromptTemplateResource($promptTemplate->fresh()->load('versions')),
            'message' => __('ai.prompt_updated'),
        ]);
    }

    public function destroy(PromptTemplate $promptTemplate): JsonResponse
    {
        $this->templateRepository->delete($promptTemplate);
        return response()->json(['message' => __('ai.prompt_deleted')]);
    }

    public function test(Request $request, PromptTemplate $promptTemplate): JsonResponse
    {
        $request->validate([
            'variables' => ['nullable', 'array'],
        ]);

        $rendered = $this->promptService->preview($promptTemplate->id, $request->input('variables', []));
        $variables = $this->promptService->getVariables($promptTemplate->content);

        return response()->json([
            'data' => [
                'rendered' => $rendered,
                'variables' => $variables,
                'token_estimate' => (int) ceil(mb_strlen($rendered) / 4),
            ],
        ]);
    }
}
