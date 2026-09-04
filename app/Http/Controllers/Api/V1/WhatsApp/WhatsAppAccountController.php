<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WhatsApp\ConnectAccountRequest;
use App\Http\Requests\Api\V1\WhatsApp\UpdateAccountRequest;
use App\Http\Resources\WhatsApp\PhoneNumberResource;
use App\Http\Resources\WhatsApp\WhatsAppAccountResource;
use App\Models\WhatsApp\WhatsAppAccount;
use App\Repositories\WhatsApp\WhatsAppAccountRepository;
use App\Services\TenantService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppAccountController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly WhatsAppAccountRepository $accountRepo,
        private readonly TenantService $tenantService,
    ) {}

    private function business()
    {
        return $this->tenantService->currentOrFail();
    }

    public function show(): JsonResponse
    {
        $business = $this->business();
        $account = $this->accountRepo->findByBusinessId($business->id);

        if (!$account) {
            return response()->json(['data' => null, 'message' => 'No WhatsApp account connected']);
        }

        $account->load('phoneNumbers');

        return response()->json([
            'data' => new WhatsAppAccountResource($account),
        ]);
    }

    public function connect(ConnectAccountRequest $request): JsonResponse
    {
        $business = $this->business();

        $account = $this->whatsAppService->connect(
            $business->id,
            $request->input('access_token'),
            $request->input('waba_id'),
            $request->input('phone_number_id'),
        );

        $account->load('phoneNumbers');

        return response()->json([
            'data' => new WhatsAppAccountResource($account),
            'message' => 'WhatsApp account connected successfully',
        ], 201);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'access_token' => ['required', 'string'],
            'waba_id' => ['nullable', 'string'],
            'phone_number_id' => ['nullable', 'string'],
        ]);

        try {
            $result = $this->whatsAppService->verify(
                $request->input('access_token'),
                $request->input('waba_id'),
                $request->input('phone_number_id'),
            );

            return response()->json(['data' => $result]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function disconnect(): JsonResponse
    {
        $business = $this->business();
        $account = $this->accountRepo->findByBusinessId($business->id);

        if (!$account) {
            return response()->json(['message' => 'No WhatsApp account to disconnect'], 404);
        }

        $this->whatsAppService->disconnect($account);

        return response()->json(['message' => 'WhatsApp account disconnected successfully']);
    }

    public function reconnect(UpdateAccountRequest $request): JsonResponse
    {
        $business = $this->business();
        $account = $this->accountRepo->findByBusinessId($business->id);

        if (!$account) {
            return response()->json(['message' => 'No WhatsApp account to reconnect'], 404);
        }

        $account = $this->whatsAppService->reconnect(
            $account,
            $request->input('access_token', ''),
        );

        return response()->json([
            'data' => new WhatsAppAccountResource($account),
            'message' => 'WhatsApp account reconnected successfully',
        ]);
    }

    public function sync(): JsonResponse
    {
        $business = $this->business();
        $account = $this->accountRepo->findByBusinessId($business->id);

        if (!$account) {
            return response()->json(['message' => 'No WhatsApp account connected'], 404);
        }

        try {
            $phoneNumbers = $this->whatsAppService->syncPhoneNumbers($account);

            $this->accountRepo->update($account, ['last_synced_at' => now()]);

            return response()->json([
                'data' => [
                    'phone_numbers' => PhoneNumberResource::collection($phoneNumbers),
                    'synced_at' => now()->toISOString(),
                ],
                'message' => 'Phone numbers synced successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to sync phone numbers: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function status(): JsonResponse
    {
        $business = $this->business();
        $account = $this->accountRepo->findByBusinessId($business->id);

        if (!$account) {
            return response()->json([
                'data' => [
                    'connected' => false,
                    'status' => 'disconnected',
                ],
            ]);
        }

        return response()->json([
            'data' => $this->whatsAppService->getDiagnostics($account),
        ]);
    }

    public function info(): JsonResponse
    {
        $business = $this->business();
        $account = $this->accountRepo->findByBusinessId($business->id);

        if (!$account) {
            return response()->json(['message' => 'No WhatsApp account connected'], 404);
        }

        $info = $this->whatsAppService->getAccountInfo($account);

        return response()->json([
            'data' => [
                'account' => new WhatsAppAccountResource($account),
                'business_profile' => $info,
            ],
        ]);
    }

    public function diagnostics(): JsonResponse
    {
        $business = $this->business();
        $account = $this->accountRepo->findByBusinessId($business->id);

        if (!$account) {
            return response()->json(['data' => null], 200);
        }

        return response()->json([
            'data' => $this->whatsAppService->getDiagnostics($account),
        ]);
    }
}
