<?php

namespace App\Services\Broadcast;

use App\Contracts\Broadcast\DeliveryServiceInterface;
use App\Jobs\Broadcast\SendBroadcastJob;
use App\Models\BroadcastCampaign;
use App\Models\BroadcastRecipient;
use App\Models\WhatsApp\Message;
use App\Repositories\Broadcast\BroadcastRecipientRepository;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryService implements DeliveryServiceInterface
{
    public function __construct(
        private readonly BroadcastRecipientRepository $recipientRepo,
        private readonly WhatsAppService $whatsAppService,
    ) {}

    public function send(BroadcastRecipient $recipient): array
    {
        $campaign = $recipient->campaign;
        $customer = $recipient->customer;

        if (!$customer || !$customer->phone) {
            $this->recipientRepo->markAsFailed($recipient, 'Customer has no phone number');
            return ['status' => 'failed', 'error' => 'No phone number'];
        }

        try {
            $message = $this->buildMessage($campaign, $customer);

            $message->save();
            $response = $this->whatsAppService->sendMessage($message);

            $metaMessageId = $response['body']['messages'][0]['id'] ?? null;
            $this->recipientRepo->markAsSent($recipient, $metaMessageId);

            if ($metaMessageId) {
                $message->update(['meta_message_id' => $metaMessageId]);
            }

            return ['status' => 'sent', 'message_id' => $metaMessageId];
        } catch (\Throwable $e) {
            $this->recipientRepo->markAsFailed($recipient, $e->getMessage());
            Log::error('Broadcast send failed', [
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    public function handleDeliveryCallback(BroadcastRecipient $recipient, array $webhookPayload): void
    {
        $status = $webhookPayload['status'] ?? '';

        match ($status) {
            'sent' => $this->recipientRepo->markAsSent($recipient, $webhookPayload['message_id'] ?? null),
            'delivered' => $this->recipientRepo->markAsDelivered($recipient),
            'read' => $this->recipientRepo->markAsRead($recipient),
            'failed' => $this->recipientRepo->markAsFailed($recipient, $webhookPayload['error'] ?? 'Delivery failed'),
            default => Log::warning('Unknown delivery status', ['status' => $status, 'payload' => $webhookPayload]),
        };
    }

    public function batchQueue(BroadcastCampaign $campaign): void
    {
        $recipients = $campaign->recipients()->whereNull('sent_at')->get();

        foreach ($recipients as $recipient) {
            SendBroadcastJob::dispatch($recipient)->onQueue(config('broadcast.queues.delivery', 'delivery'));
        }
    }

    public function retryFailed(BroadcastCampaign $campaign): int
    {
        $recipients = $campaign->recipients()->where('status', 'failed')->get();

        foreach ($recipients as $recipient) {
            SendBroadcastJob::dispatch($recipient)->onQueue(config('broadcast.queues.delivery', 'delivery'));
        }

        return $recipients->count();
    }

    private function buildMessage(BroadcastCampaign $campaign, \App\Models\Customer $customer): Message
    {
        $message = new Message();
        $message->business_id = $campaign->business_id;
        $message->to_number = $customer->phone;
        $message->direction = 'outgoing';
        $message->status = 'pending';
        $message->source = 'broadcast';

        if ($campaign->template_id && $campaign->template) {
            $message->message_type = 'template';
            $message->template_name = $campaign->template->name;
            $message->template_language = $campaign->template->language ?? 'en';
            $message->template_params = $this->buildTemplateParams($campaign->template, $customer);
        } else {
            $message->message_type = 'text';
            $message->body = $this->personalizeContent($campaign->message_content ?? '', $customer);
        }

        return $message;
    }

    private function buildTemplateParams(\App\Models\WhatsappTemplate $template, \App\Models\Customer $customer): array
    {
        $params = [];
        $bodyComponent = null;
        $components = $template->buttons ?? [];

        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                $bodyComponent = $component;
                break;
            }
        }

        if (!$bodyComponent && !empty($template->body)) {
            preg_match_all('/\{\{(\d+)\}\}/', $template->body, $matches);
            if (!empty($matches[1])) {
                $components = [['type' => 'BODY', 'parameters' => []]];
                $bodyComponent = $components[0];
            }
        }

        if ($bodyComponent) {
            $params[] = [
                'type' => 'body',
                'parameters' => array_map(fn($i) => [
                    'type' => 'text',
                    'text' => $this->resolveTemplateVar($i + 1, $customer),
                ], range(0, 2)),
            ];
        }

        return $params;
    }

    private function resolveTemplateVar(int $index, \App\Models\Customer $customer): string
    {
        $vars = [
            1 => $customer->name,
            2 => $customer->phone,
            3 => $customer->email ?? '',
        ];
        return $vars[$index] ?? '';
    }

    private function personalizeContent(string $content, \App\Models\Customer $customer): string
    {
        $replacements = [
            '{{name}}' => $customer->name,
            '{{phone}}' => $customer->phone,
            '{{email}}' => $customer->email ?? '',
            '{{company}}' => $customer->company_name ?? '',
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
