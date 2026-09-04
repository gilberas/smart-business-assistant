<?php

namespace App\Http\Controllers\Api\V1\Broadcast;

use App\Http\Controllers\Controller;
use App\Http\Requests\Broadcast\StoreTemplateRequest;
use App\Http\Resources\Broadcast\TemplateResource;
use App\Models\WhatsappTemplate;
use App\Services\Broadcast\TemplateService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('whatsapp.templates.manage');

        $templates = $this->templateService->paginate(
            $this->business(),
            $request->all(),
            (int) $request->input('per_page', 15),
        );

        return response()->json(
            TemplateResource::collection($templates)->response()->getData()
        );
    }

    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $this->authorize('whatsapp.templates.manage');

        $template = $this->templateService->create(
            $this->business(),
            $request->validated(),
        );

        return response()->json([
            'data' => new TemplateResource($template),
            'message' => __('broadcast.template_created'),
        ], 201);
    }

    public function show(Request $request, WhatsappTemplate $template): JsonResponse
    {
        $this->authorize('whatsapp.templates.manage');

        if ($template->business_id !== $this->business()->id) {
            abort(403);
        }

        return response()->json(['data' => new TemplateResource($template)]);
    }

    public function update(StoreTemplateRequest $request, WhatsappTemplate $template): JsonResponse
    {
        $this->authorize('whatsapp.templates.manage');

        if ($template->business_id !== $this->business()->id) {
            abort(403);
        }

        $template = $this->templateService->update($template, $request->validated());

        return response()->json([
            'data' => new TemplateResource($template),
            'message' => __('broadcast.template_updated'),
        ]);
    }

    public function destroy(Request $request, WhatsappTemplate $template): JsonResponse
    {
        $this->authorize('whatsapp.templates.manage');

        if ($template->business_id !== $this->business()->id) {
            abort(403);
        }

        $this->templateService->delete($template);

        return response()->json(['message' => __('broadcast.template_deleted')]);
    }

    public function sync(Request $request): JsonResponse
    {
        $this->authorize('whatsapp.templates.manage');

        try {
            $count = $this->templateService->syncFromMeta($this->business());
            return response()->json([
                'message' => __('broadcast.templates_synced', ['count' => $count]),
                'data' => ['synced' => $count],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function active(Request $request): JsonResponse
    {
        $this->authorize('whatsapp.templates.manage');

        $templates = app(\App\Repositories\Broadcast\TemplateRepository::class)
            ->findActive($this->business());

        return response()->json([
            'data' => TemplateResource::collection($templates),
        ]);
    }
}
