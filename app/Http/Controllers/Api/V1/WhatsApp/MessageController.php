<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WhatsApp\SendMessageRequest;
use App\Http\Resources\WhatsApp\MessageListResource;
use App\Http\Resources\WhatsApp\MessageResource;
use App\Models\WhatsApp\Message;
use App\Repositories\WhatsApp\MessageRepository;
use App\Repositories\WhatsApp\WhatsAppAccountRepository;
use App\Services\TenantService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessageRepository $messageRepo,
        private readonly WhatsAppService $whatsAppService,
        private readonly WhatsAppAccountRepository $accountRepo,
        private readonly TenantService $tenantService,
    ) {}

    private function business()
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $business = $this->business();

        $messages = $this->messageRepo->getByBusinessId(
            $business->id,
            $request->only(['direction', 'message_type', 'status', 'from_number', 'to_number', 'conversation_id', 'date_from', 'date_to']),
            $request->integer('per_page', 15)
        );

        return response()->json(
            MessageListResource::collection($messages)->response()->getData()
        );
    }

    public function show(int $messageId): JsonResponse
    {
        $business = $this->business();

        $message = Message::withoutGlobalScopes()->findOrFail($messageId);

        if ($message->business_id !== $business->id) {
            abort(403);
        }

        $message->load(['media', 'statuses']);

        return response()->json([
            'data' => new MessageResource($message),
        ]);
    }

    public function send(SendMessageRequest $request): JsonResponse
    {
        $business = $this->business();

        $account = $this->accountRepo->findByBusinessId($business->id);
        if (!$account || !$account->isConnected()) {
            return response()->json(['message' => 'No connected WhatsApp account'], 400);
        }

        $messageData = [
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'whatsapp_account_id' => $account->id,
            'to_number' => $request->input('to'),
            'from_number' => $account->phoneNumbers()->first()?->phone_number,
            'direction' => 'outbound',
            'message_type' => $request->input('type', 'text'),
            'status' => 'pending',
            'sender_type' => 'agent',
        ];

        if ($request->filled('body')) {
            $messageData['body'] = $request->input('body');
        }

        if ($request->filled('media_id')) {
            $messageData['media_id'] = $request->input('media_id');
        }

        if ($request->filled('caption')) {
            $messageData['caption'] = $request->input('caption');
        }

        if ($request->filled('template_name')) {
            $messageData['template_name'] = $request->input('template_name');
            $messageData['template_language'] = $request->input('template_language');
            $messageData['template_params'] = $request->input('template_params');
        }

        if ($request->filled('reply_to_message_id')) {
            $replyMessage = Message::where('meta_message_id', $request->input('reply_to_message_id'))->first();
            if ($replyMessage) {
                $messageData['reply_to_message_id'] = $replyMessage->id;
                $messageData['quoted_message_id'] = $request->input('reply_to_message_id');
            }
        }

        if ($request->input('type') === 'location') {
            $messageData['body'] = json_encode([
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'name' => $request->input('location_name'),
                'address' => $request->input('location_address'),
            ]);
        }

        $messageData['content'] = $messageData['body'] ?? '';
        $messageData['content_type'] = 'text';

        $message = $this->messageRepo->create($messageData);

        if ($request->boolean('schedule', false)) {
            \App\Jobs\WhatsApp\SendOutgoingMessage::dispatch($message)->delay(
                now()->addMinutes($request->integer('schedule_minutes', 0))
            );

            return response()->json([
                'data' => new MessageResource($message),
                'message' => 'Message scheduled successfully',
            ]);
        }

        try {
            $response = $this->whatsAppService->sendMessage($message);
            $metaMessageId = $response['body']['messages'][0]['id'] ?? null;

            if ($metaMessageId) {
                $this->messageRepo->update($message, [
                    'meta_message_id' => $metaMessageId,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            $message = $message->fresh();
        } catch (\Throwable $e) {
            $this->messageRepo->markAsFailed($message, [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'data' => new MessageResource($message->fresh()),
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'data' => new MessageResource($message),
            'message' => 'Message sent successfully',
        ], 201);
    }

    public function conversation(Request $request, string $phoneNumber): JsonResponse
    {
        $business = $this->business();

        $messages = $this->messageRepo->getConversation(
            $business->id,
            $phoneNumber,
            $request->integer('limit', 50)
        );

        return response()->json([
            'data' => MessageResource::collection($messages),
        ]);
    }

    public function stats(): JsonResponse
    {
        $business = $this->business();

        $account = $this->accountRepo->findByBusinessId($business->id);
        if (!$account) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => $this->whatsAppService->getMessageStats($account),
        ]);
    }
}
