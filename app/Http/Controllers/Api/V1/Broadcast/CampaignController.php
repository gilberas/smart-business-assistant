<?php

namespace App\Http\Controllers\Api\V1\Broadcast;

use App\DTOs\Broadcast\CampaignStatsDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Broadcast\AudiencePreviewRequest;
use App\Http\Requests\Broadcast\StoreCampaignRequest;
use App\Http\Requests\Broadcast\UpdateCampaignRequest;
use App\Http\Resources\Broadcast\AudiencePreviewResource;
use App\Http\Resources\Broadcast\CampaignListResource;
use App\Http\Resources\Broadcast\CampaignResource;
use App\Http\Resources\Broadcast\RecipientResource;
use App\Models\BroadcastCampaign;
use App\Repositories\Broadcast\BroadcastRecipientRepository;
use App\Services\Broadcast\AudienceService;
use App\Services\Broadcast\CampaignService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService,
        private readonly AudienceService $audienceService,
        private readonly BroadcastRecipientRepository $recipientRepo,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('broadcasts.view');

        $campaigns = $this->campaignService->paginate(
            $this->business(),
            $request->all(),
            (int) $request->input('per_page', 15),
        );

        return response()->json(
            CampaignListResource::collection($campaigns)->response()->getData()
        );
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $this->authorize('broadcasts.create');

        $campaign = $this->campaignService->create(
            $this->business(),
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'data' => new CampaignResource($campaign),
            'message' => __('broadcast.campaign_created'),
        ], 201);
    }

    public function show(Request $request, BroadcastCampaign $campaign): JsonResponse
    {
        $this->authorize('broadcasts.view');

        if ($campaign->business_id !== $this->business()->id) {
            abort(403);
        }

        $campaign->load(['template', 'creator']);
        $stats = $this->campaignService->stats($campaign);

        return response()->json([
            'data' => new CampaignResource($campaign),
            'stats' => $stats,
        ]);
    }

    public function update(UpdateCampaignRequest $request, BroadcastCampaign $campaign): JsonResponse
    {
        $this->authorize('broadcasts.create');

        if ($campaign->business_id !== $this->business()->id) {
            abort(403);
        }

        $campaign = $this->campaignService->update($campaign, $request->validated());

        return response()->json([
            'data' => new CampaignResource($campaign),
            'message' => __('broadcast.campaign_updated'),
        ]);
    }

    public function destroy(Request $request, BroadcastCampaign $campaign): JsonResponse
    {
        $this->authorize('broadcasts.delete');

        if ($campaign->business_id !== $this->business()->id) {
            abort(403);
        }

        $this->campaignService->delete($campaign);

        return response()->json(['message' => __('broadcast.campaign_deleted')]);
    }

    public function duplicate(Request $request, BroadcastCampaign $campaign): JsonResponse
    {
        $this->authorize('broadcasts.create');

        if ($campaign->business_id !== $this->business()->id) {
            abort(403);
        }

        $duplicate = $this->campaignService->duplicate($campaign);

        return response()->json([
            'data' => new CampaignResource($duplicate),
            'message' => __('broadcast.campaign_duplicated'),
        ], 201);
    }

    public function schedule(Request $request, BroadcastCampaign $campaign): JsonResponse
    {
        $this->authorize('broadcasts.send');

        if ($campaign->business_id !== $this->business()->id) {
            abort(403);
        }

        try {
            $campaign = $this->campaignService->schedule($campaign);
            return response()->json([
                'data' => new CampaignResource($campaign),
                'message' => __('broadcast.campaign_scheduled'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function pause(Request $request, BroadcastCampaign $campaign): JsonResponse
    {
        $this->authorize('broadcasts.send');

        if ($campaign->business_id !== $this->business()->id) {
            abort(403);
        }

        try {
            $campaign = $this->campaignService->pause($campaign);
            return response()->json([
                'data' => new CampaignResource($campaign),
                'message' => __('broadcast.campaign_paused'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function resume(Request $request, BroadcastCampaign $campaign): JsonResponse
    {
        $this->authorize('broadcasts.send');

        if ($campaign->business_id !== $this->business()->id) {
            abort(403);
        }

        try {
            $campaign = $this->campaignService->resume($campaign);
            return response()->json([
                'data' => new CampaignResource($campaign),
                'message' => __('broadcast.campaign_resumed'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request, BroadcastCampaign $campaign): JsonResponse
    {
        $this->authorize('broadcasts.send');

        if ($campaign->business_id !== $this->business()->id) {
            abort(403);
        }

        try {
            $campaign = $this->campaignService->cancel($campaign);
            return response()->json([
                'data' => new CampaignResource($campaign),
                'message' => __('broadcast.campaign_cancelled'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function archive(Request $request, BroadcastCampaign $campaign): JsonResponse
    {
        $this->authorize('broadcasts.delete');

        if ($campaign->business_id !== $this->business()->id) {
            abort(403);
        }

        $this->campaignService->archive($campaign);

        return response()->json(['message' => __('broadcast.campaign_archived')]);
    }

    public function stats(Request $request, BroadcastCampaign $campaign): JsonResponse
    {
        $this->authorize('broadcasts.view');

        if ($campaign->business_id !== $this->business()->id) {
            abort(403);
        }

        $stats = $this->campaignService->stats($campaign);

        return response()->json(['data' => $stats]);
    }

    public function recipients(Request $request, BroadcastCampaign $campaign): JsonResponse
    {
        $this->authorize('broadcasts.view');

        if ($campaign->business_id !== $this->business()->id) {
            abort(403);
        }

        $recipients = $this->recipientRepo->paginate(
            $campaign,
            $request->all(),
            (int) $request->input('per_page', 15),
        );

        return response()->json(
            RecipientResource::collection($recipients)->response()->getData()
        );
    }

    public function previewAudience(AudiencePreviewRequest $request): JsonResponse
    {
        $this->authorize('broadcasts.view');

        $preview = $this->audienceService->preview(
            $this->business(),
            $request->input('filters'),
            (int) $request->input('limit', 50),
        );

        return response()->json(['data' => new AudiencePreviewResource($preview)]);
    }

    public function countAudience(Request $request): JsonResponse
    {
        $this->authorize('broadcasts.view');

        $count = $this->audienceService->count(
            $this->business(),
            $request->input('filters', []),
        );

        return response()->json(['data' => ['count' => $count]]);
    }
}
