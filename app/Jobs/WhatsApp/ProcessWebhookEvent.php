<?php

namespace App\Jobs\WhatsApp;

use App\Events\WhatsApp\MessageReceived;
use App\Events\WhatsApp\MessageStatusUpdated;
use App\Models\BroadcastRecipient;
use App\Models\WhatsApp\Message;
use App\Models\WhatsApp\WebhookEvent;
use App\Repositories\Broadcast\BroadcastRecipientRepository;
use App\Repositories\WhatsApp\MessageRepository;
use App\Repositories\WhatsApp\MessageStatusRepository;
use App\Repositories\WhatsApp\WebhookEventRepository;
use App\Services\Broadcast\DeliveryService;
use App\Services\WhatsApp\MediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 30;

    public function __construct(
        public readonly WebhookEvent $webhookEvent,
    ) {
        $this->onQueue(config('whatsapp.queues.webhook', 'webhook'));
    }

    public function handle(
        WebhookEventRepository $webhookEventRepo,
        MessageRepository $messageRepo,
        MessageStatusRepository $messageStatusRepo,
        MediaService $mediaService,
        BroadcastRecipientRepository $broadcastRecipientRepo,
        DeliveryService $deliveryService,
    ): void {
        $webhookEventRepo->markAsProcessing($this->webhookEvent);

        try {
            $payload = $this->webhookEvent->payload;
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];

                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];
                    $field = $change['field'] ?? '';

                    if ($field === 'messages') {
                        $this->processMessages($value, $messageRepo, $messageStatusRepo, $mediaService, $broadcastRecipientRepo);
                    } elseif ($field === 'message_template_status_update') {
                        $this->processTemplateUpdate($value);
                    } elseif (in_array($field, ['message_deliveries', 'message_reads', 'message_delivery_receipts'])) {
                        $this->processStatusUpdate($value, $messageRepo, $messageStatusRepo, $broadcastRecipientRepo);
                    }
                }
            }

            $webhookEventRepo->markAsProcessed($this->webhookEvent);
        } catch (\Throwable $e) {
            Log::error('Webhook processing failed', [
                'event_id' => $this->webhookEvent->id,
                'error' => $e->getMessage(),
            ]);

            $webhookEventRepo->markAsFailed($this->webhookEvent, $e->getMessage());

            if ($this->attempts() >= $this->tries) {
                $this->webhookEvent->markAsDeadLetter();
            }

            throw $e;
        }
    }

    protected function processMessages(array $value, MessageRepository $messageRepo, MessageStatusRepository $messageStatusRepo, MediaService $mediaService, BroadcastRecipientRepository $broadcastRecipientRepo): void
    {
        $messages = $value['messages'] ?? [];
        $metadata = $value['metadata'] ?? [];
        $phoneNumberId = $metadata['phone_number_id'] ?? null;
        $businessId = $this->webhookEvent->business_id;

        foreach ($messages as $msg) {
            $existing = $messageRepo->findByMetaMessageId($msg['id'] ?? '');
            if ($existing) {
                continue;
            }

            $fromNumber = $msg['from'] ?? '';
            $msgType = $msg['type'] ?? 'text';
            $direction = 'inbound';

            $messageData = [
                'business_id' => $businessId,
                'whatsapp_account_id' => $this->webhookEvent->whatsapp_account_id,
                'phone_number_id' => $phoneNumberId,
                'from_number' => $fromNumber,
                'to_number' => $metadata['display_phone_number'] ?? '',
                'direction' => $direction,
                'message_type' => $msgType,
                'meta_message_id' => $msg['id'] ?? null,
                'status' => 'received',
                'sender_type' => 'customer',
                'context' => isset($msg['context']) ? $msg['context'] : null,
            ];

            $body = match ($msgType) {
                'text' => $msg['text']['body'] ?? '',
                'interactive' => $this->extractInteractiveBody($msg),
                'button' => $msg['button']['text'] ?? '',
                'location' => json_encode($msg['location'] ?? []),
                'contacts' => json_encode($msg['contacts'] ?? []),
                default => $msg[$msgType]['caption'] ?? $msg[$msgType]['id'] ?? '',
            };

            $messageData['body'] = $body;
            $messageData['content'] = $body;
            $messageData['content_type'] = $msgType;

            $message = $messageRepo->create($messageData);

            if (in_array($msgType, ['image', 'audio', 'video', 'document', 'sticker'])) {
                $mediaId = $msg[$msgType]['id'] ?? null;
                if ($mediaId) {
                    $message->update(['media_id' => $mediaId]);
                    $mediaService->queueDownload($message, $mediaId);
                }
            }

            MessageReceived::dispatch($message, $this->webhookEvent->whatsappAccount, $msg);
        }

        $statuses = $value['statuses'] ?? [];
        foreach ($statuses as $status) {
            $this->processSingleStatus($status, $messageRepo, $messageStatusRepo, $broadcastRecipientRepo);
        }
    }

    protected function processSingleStatus(array $status, MessageRepository $messageRepo, MessageStatusRepository $messageStatusRepo, BroadcastRecipientRepository $broadcastRecipientRepo): void
    {
        $metaMessageId = $status['id'] ?? '';
        $message = $messageRepo->findByMetaMessageId($metaMessageId);
        if (!$message) {
            return;
        }

        $statusType = $status['status'] ?? '';
        $messageStatusRepo->create([
            'business_id' => $message->business_id,
            'message_id' => $message->id,
            'status' => $statusType,
            'meta_status' => $statusType,
            'timestamp' => isset($status['timestamp']) ? date('Y-m-d H:i:s', (int) $status['timestamp']) : now(),
            'details' => $status,
        ]);

        $updatedMessage = match ($statusType) {
            'sent' => $messageRepo->markAsSent($message),
            'delivered' => $messageRepo->markAsDelivered($message),
            'read' => $messageRepo->markAsRead($message),
            'failed' => $messageRepo->markAsFailed($message, [
                'code' => $status['errors'][0]['code'] ?? null,
                'title' => $status['errors'][0]['title'] ?? null,
                'message' => $status['errors'][0]['message'] ?? null,
            ]),
            default => $message,
        };

        $this->updateBroadcastRecipient($metaMessageId, $statusType, $broadcastRecipientRepo);

        MessageStatusUpdated::dispatch($updatedMessage, $statusType, $status);
    }

    protected function processStatusUpdate(array $value, MessageRepository $messageRepo, MessageStatusRepository $messageStatusRepo, BroadcastRecipientRepository $broadcastRecipientRepo): void
    {
        $statuses = $value['statuses'] ?? [];
        foreach ($statuses as $status) {
            $this->processSingleStatus($status, $messageRepo, $messageStatusRepo, $broadcastRecipientRepo);
        }
    }

    protected function processTemplateUpdate(array $value): void
    {
        Log::info('Template status update received', ['data' => $value]);
    }

    protected function updateBroadcastRecipient(string $metaMessageId, string $status, BroadcastRecipientRepository $repo): void
    {
        if (empty($metaMessageId)) {
            return;
        }

        $recipient = BroadcastRecipient::where('message_id', $metaMessageId)->first();
        if (!$recipient) {
            return;
        }

        match ($status) {
            'sent' => $recipient->update(['status' => 'sent', 'sent_at' => now()]),
            'delivered' => $recipient->update(['status' => 'delivered', 'delivered_at' => now()]),
            'read' => $recipient->update(['status' => 'read', 'read_at' => now()]),
            'failed' => $recipient->update(['status' => 'failed', 'failed_at' => now()]),
            default => null,
        };
    }

    protected function extractInteractiveBody(array $msg): string
    {
        $interactive = $msg['interactive'] ?? [];
        $type = $interactive['type'] ?? '';

        return match ($type) {
            'button_reply' => $interactive['button_reply']['title'] ?? $interactive['button_reply']['id'] ?? '',
            'list_reply' => $interactive['list_reply']['title'] ?? $interactive['list_reply']['id'] ?? '',
            'nfm_reply' => json_encode($interactive['nfm_reply'] ?? []),
            default => json_encode($interactive),
        };
    }
}
