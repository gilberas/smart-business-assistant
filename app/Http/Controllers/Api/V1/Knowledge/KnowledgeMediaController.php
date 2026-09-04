<?php

namespace App\Http\Controllers\Api\V1\Knowledge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\KnowledgeBase\UploadMediaRequest;
use App\Http\Resources\Knowledge\KnowledgeMediaResource;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeMedium;
use App\Services\Knowledge\KnowledgeMediaService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeMediaController extends Controller
{
    public function __construct(
        private readonly KnowledgeMediaService $mediaService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $business = $this->business();

        if ($article->business_id !== $business->id) {
            abort(403);
        }

        $media = $this->mediaService->forArticle($business, $article);

        return response()->json([
            'data' => KnowledgeMediaResource::collection($media),
        ]);
    }

    public function store(UploadMediaRequest $request, KnowledgeArticle $article): JsonResponse
    {
        $business = $this->business();

        if ($article->business_id !== $business->id) {
            abort(403);
        }

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $path = $file->store('knowledge', 'public');

        $data = [
            'article_id' => $article->id,
            'filename' => basename($path),
            'original_name' => $filename,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => 'public',
            'path' => $path,
            'url' => asset('storage/' . $path),
            'media_type' => $request->input('media_type', 'document'),
        ];

        $media = $this->mediaService->create($business, $data);

        return response()->json([
            'data' => new KnowledgeMediaResource($media),
            'message' => __('knowledge.media_uploaded'),
        ], 201);
    }

    public function destroy(Request $request, KnowledgeMedium $media): JsonResponse
    {
        $business = $this->business();

        if ($media->business_id !== $business->id) {
            abort(403);
        }

        $this->mediaService->delete($business, $media);

        return response()->json(['message' => __('knowledge.media_deleted')]);
    }
}