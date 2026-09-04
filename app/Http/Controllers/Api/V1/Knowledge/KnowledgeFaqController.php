<?php

namespace App\Http\Controllers\Api\V1\Knowledge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\KnowledgeBase\CreateFaqRequest;
use App\Http\Requests\Api\V1\KnowledgeBase\UpdateFaqRequest;
use App\Http\Resources\Knowledge\KnowledgeFaqResource;
use App\Models\KnowledgeFaq;
use App\Services\Knowledge\KnowledgeFaqService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeFaqController extends Controller
{
    public function __construct(
        private readonly KnowledgeFaqService $faqService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $business = $this->business();

        $faqs = $this->faqService->list($business, $request->all());

        return response()->json(
            KnowledgeFaqResource::collection($faqs)
                ->response()
                ->getData()
        );
    }

    public function store(CreateFaqRequest $request): JsonResponse
    {
        $business = $this->business();

        $faq = $this->faqService->create($business, $request->validated());

        return response()->json([
            'data' => new KnowledgeFaqResource($faq),
            'message' => __('knowledge.faq_created'),
        ], 201);
    }

    public function show(Request $request, KnowledgeFaq $faq): JsonResponse
    {
        $business = $this->business();

        if ($faq->business_id !== $business->id) {
            abort(403);
        }

        return response()->json([
            'data' => new KnowledgeFaqResource($faq),
        ]);
    }

    public function update(UpdateFaqRequest $request, KnowledgeFaq $faq): JsonResponse
    {
        $business = $this->business();

        if ($faq->business_id !== $business->id) {
            abort(403);
        }

        $faq = $this->faqService->update($business, $faq, $request->validated());

        return response()->json([
            'data' => new KnowledgeFaqResource($faq),
            'message' => __('knowledge.faq_updated'),
        ]);
    }

    public function destroy(Request $request, KnowledgeFaq $faq): JsonResponse
    {
        $business = $this->business();

        if ($faq->business_id !== $business->id) {
            abort(403);
        }

        $this->faqService->delete($business, $faq);

        return response()->json(['message' => __('knowledge.faq_deleted')]);
    }
}