<?php

namespace App\Http\Controllers\Api\V1\Knowledge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\KnowledgeBase\CreateArticleRequest;
use App\Http\Requests\Api\V1\KnowledgeBase\UpdateArticleRequest;
use App\Http\Resources\Knowledge\KnowledgeArticleListResource;
use App\Http\Resources\Knowledge\KnowledgeArticleResource;
use App\Models\KnowledgeArticle;
use App\Services\Knowledge\KnowledgeArticleService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeArticleController extends Controller
{
    public function __construct(
        private readonly KnowledgeArticleService $articleService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $business = $this->business();

        $articles = $this->articleService->list($business, $request->all());

        return response()->json(
            KnowledgeArticleListResource::collection($articles)
                ->response()
                ->getData()
        );
    }

    public function store(CreateArticleRequest $request): JsonResponse
    {
        $business = $this->business();

        $article = $this->articleService->create(
            $business,
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'data' => new KnowledgeArticleResource($article),
            'message' => __('knowledge.article_created'),
        ], 201);
    }

    public function show(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $business = $this->business();

        if ($article->business_id !== $business->id) {
            abort(403);
        }

        $article->load(['category', 'tags', 'author', 'media', 'versions']);

        return response()->json([
            'data' => new KnowledgeArticleResource($article),
        ]);
    }

    public function update(UpdateArticleRequest $request, KnowledgeArticle $article): JsonResponse
    {
        $business = $this->business();

        if ($article->business_id !== $business->id) {
            abort(403);
        }

        $article = $this->articleService->update(
            $business,
            $request->user(),
            $article,
            $request->validated(),
        );

        return response()->json([
            'data' => new KnowledgeArticleResource($article),
            'message' => __('knowledge.article_updated'),
        ]);
    }

    public function destroy(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $business = $this->business();

        if ($article->business_id !== $business->id) {
            abort(403);
        }

        $this->articleService->delete($business, $article);

        return response()->json(['message' => __('knowledge.article_deleted')]);
    }

    public function publish(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $business = $this->business();

        if ($article->business_id !== $business->id) {
            abort(403);
        }

        $article = $this->articleService->publish($business, $request->user(), $article);

        return response()->json([
            'data' => new KnowledgeArticleResource($article),
            'message' => __('knowledge.article_published'),
        ]);
    }

    public function archive(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $business = $this->business();

        if ($article->business_id !== $business->id) {
            abort(403);
        }

        $article = $this->articleService->archive($business, $article);

        return response()->json([
            'data' => new KnowledgeArticleResource($article),
            'message' => __('knowledge.article_archived'),
        ]);
    }

    public function restore(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $business = $this->business();

        if ($article->business_id !== $business->id) {
            abort(403);
        }

        $article = $this->articleService->restore($business, $article);

        return response()->json([
            'data' => new KnowledgeArticleResource($article),
            'message' => __('knowledge.article_restored'),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $business = $this->business();

        $articles = $this->articleService->search(
            $business,
            $request->input('q', ''),
            $request->input('limit', 10),
        );

        return response()->json([
            'data' => KnowledgeArticleListResource::collection($articles),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $business = $this->business();

        return response()->json(
            $this->articleService->stats($business)
        );
    }

    public function trackView(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $business = $this->business();

        if ($article->business_id !== $business->id) {
            abort(403);
        }

        $this->articleService->trackView($business, $article);

        return response()->json(['message' => 'View tracked']);
    }

    public function markHelpful(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $business = $this->business();

        if ($article->business_id !== $business->id) {
            abort(403);
        }

        $this->articleService->markHelpful($business, $article);

        return response()->json(['message' => 'Helpful vote recorded']);
    }
}