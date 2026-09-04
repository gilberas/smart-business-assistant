<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Resources\WhatsApp\WebhookEventResource;
use App\Repositories\WhatsApp\WebhookEventRepository;
use App\Repositories\WhatsApp\WhatsAppAccountRepository;
use App\Services\TenantService;
use App\Services\WhatsApp\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService,
        private readonly WebhookEventRepository $webhookEventRepo,
        private readonly WhatsAppAccountRepository $accountRepo,
        private readonly TenantService $tenantService,
    ) {}

    public function verify(Request $request, string $businessUuid)
    {
        $result = $this->webhookService->verify($request, $businessUuid);

        if (!$result['success']) {
            return response()->json(['error' => 'Verification failed'], $result['status']);
        }

        return response($result['challenge'], $result['status'])
            ->header('Content-Type', 'text/plain');
    }

    public function handle(Request $request, string $businessUuid): JsonResponse
    {
        $this->webhookService->handleIncoming($request, $businessUuid);

        return response()->json(['success' => true]);
    }

    public function health(): JsonResponse
    {
        $business = $this->tenantService->currentOrFail();

        return response()->json([
            'data' => $this->webhookService->getHealth($business->id),
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $business = $this->tenantService->currentOrFail();

        $perPage = $request->integer('per_page', 15);
        $status = $request->input('status');

        $query = \App\Models\WhatsApp\WebhookEvent::where('business_id', $business->id);

        if ($status) {
            $query->where('status', $status);
        }

        $events = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json(
            WebhookEventResource::collection($events)->response()->getData()
        );
    }
}
