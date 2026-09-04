<?php

namespace App\Services\Workflow;

use App\Contracts\Workflow\ActionHandlerInterface;
use App\Models\WorkflowAction;
use App\Models\WorkflowLog;
use App\Models\WorkflowRun;
use App\Services\Conversation\ConversationEventService;
use App\Services\Conversation\ConversationService;
use App\Services\Conversation\MessageService;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ActionEngineService implements ActionHandlerInterface
{
    private array $handlerCache = [];

    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly ConversationEventService $conversationEventService,
        private readonly MessageService $messageService,
    ) {}

    public function execute(WorkflowRun $run, WorkflowAction $action, array $context): array
    {
        $payload = $action->payload ?? [];

        $handler = $this->resolveHandler($action->type);
        if ($handler !== null) {
            return $handler($run, $action, $context, $payload);
        }

        return match ($action->type) {
            'send_ai_reply' => $this->sendAiReply($run, $action, $context, $payload),
            'assign_conversation' => $this->assignConversation($run, $action, $context, $payload),
            'add_label' => $this->addLabel($run, $action, $context, $payload),
            'remove_label' => $this->removeLabel($run, $action, $context, $payload),
            'add_internal_note' => $this->addInternalNote($run, $action, $context, $payload),
            'update_customer' => $this->updateCustomer($run, $action, $context, $payload),
            'create_task' => $this->createTask($run, $action, $context, $payload),
            'send_email' => $this->sendEmail($run, $action, $context, $payload),
            'send_whatsapp_template' => $this->sendWhatsAppTemplate($run, $action, $context, $payload),
            'delay_execution' => $this->delayExecution($run, $action, $payload),
            'call_webhook' => $this->callWebhook($run, $action, $context, $payload),
            'run_custom_action' => $this->runCustomAction($run, $action, $context, $payload),
            default => $this->logUnsupported($run, $action, $context),
        };
    }

    public function supports(string $actionType): bool
    {
        return in_array($actionType, [
            'send_ai_reply', 'assign_conversation', 'add_label', 'remove_label',
            'add_internal_note', 'update_customer', 'create_task', 'send_email',
            'send_whatsapp_template', 'delay_execution', 'call_webhook', 'run_custom_action',
        ], true);
    }

    public function registerHandler(string $actionType, callable $handler): void
    {
        $this->handlerCache[$actionType] = $handler;
    }

    private function resolveHandler(string $actionType): ?callable
    {
        return $this->handlerCache[$actionType] ?? null;
    }

    private function sendAiReply(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $conversationId = (int) data_get($context, 'conversation_id', data_get($payload, 'conversation_id'));

        if (!$conversationId) {
            return $this->log($run, $action, 'warn', 'action', 'AI reply skipped — no conversation', []);
        }

        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return $this->log($run, $action, 'error', 'action', 'Conversation not found', ['conversation_id' => $conversationId]);
        }

        $prompt = (string) data_get($payload, 'prompt', '');
        if (empty($prompt)) {
            $prompt = 'Please respond to the customer inquiry.';
        }

        $prompt = $this->interpolateVariables($prompt, $context);

        $this->log($run, $action, 'info', 'action', 'AI reply queued', [
            'conversation_id' => $conversationId,
            'prompt_preview' => mb_substr($prompt, 0, 200),
        ]);

        return ['status' => 'queued', 'conversation_id' => $conversationId];
    }

    private function assignConversation(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $conversationId = (int) data_get($context, 'conversation_id', data_get($payload, 'conversation_id'));
        $userId = (int) data_get($payload, 'user_id', data_get($context, 'assigned_user_id'));

        if (!$conversationId || !$userId) {
            return $this->log($run, $action, 'warn', 'action', 'Assign conversation skipped', ['reason' => 'missing conversation_id or user_id']);
        }

        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return $this->log($run, $action, 'error', 'action', 'Conversation not found', ['conversation_id' => $conversationId]);
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->log($run, $action, 'error', 'action', 'Assignee not found', ['user_id' => $userId]);
        }

        $this->conversationService->assign($conversation, $user, data_get($payload, 'reason'));

        return $this->log($run, $action, 'info', 'action', 'Conversation assigned', ['conversation_id' => $conversationId, 'user_id' => $userId]);
    }

    private function addInternalNote(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $conversationId = (int) data_get($context, 'conversation_id', data_get($payload, 'conversation_id'));
        $content = $this->interpolateVariables((string) data_get($payload, 'content', 'Workflow note'), $context);

        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return $this->log($run, $action, 'error', 'action', 'Conversation not found', ['conversation_id' => $conversationId]);
        }

        $this->conversationEventService->addEvent(
            $conversation->id,
            'note_added',
            ['content' => $content, 'source' => 'workflow'],
            $run->triggered_by,
        );

        return $this->log($run, $action, 'info', 'action', 'Internal note added', ['conversation_id' => $conversationId]);
    }

    private function addLabel(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $conversationId = (int) data_get($context, 'conversation_id', data_get($payload, 'conversation_id'));
        $labelId = (int) data_get($payload, 'label_id');
        $conversation = Conversation::find($conversationId);

        if (!$conversation || !$labelId) {
            return $this->log($run, $action, 'warn', 'action', 'Add label skipped', ['conversation_id' => $conversationId, 'label_id' => $labelId]);
        }

        $conversation->labels()->syncWithoutDetaching([$labelId]);

        return $this->log($run, $action, 'info', 'action', 'Label added', ['conversation_id' => $conversationId, 'label_id' => $labelId]);
    }

    private function removeLabel(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $conversationId = (int) data_get($context, 'conversation_id', data_get($payload, 'conversation_id'));
        $labelId = (int) data_get($payload, 'label_id');
        $conversation = Conversation::find($conversationId);

        if (!$conversation || !$labelId) {
            return $this->log($run, $action, 'warn', 'action', 'Remove label skipped', ['conversation_id' => $conversationId, 'label_id' => $labelId]);
        }

        $conversation->labels()->detach($labelId);

        return $this->log($run, $action, 'info', 'action', 'Label removed', ['conversation_id' => $conversationId, 'label_id' => $labelId]);
    }

    private function updateCustomer(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $customerId = (int) data_get($context, 'customer_id', data_get($payload, 'customer_id'));
        $customer = Customer::find($customerId);

        if (!$customer) {
            return $this->log($run, $action, 'warn', 'action', 'Update customer skipped', ['customer_id' => $customerId]);
        }

        $updates = collect($payload)->except(['customer_id'])->mapWithKeys(
            fn ($value, $key) => [$key => $this->interpolateVariables((string) $value, $context)]
        )->toArray();

        $customer->fill($updates);
        $customer->save();

        return $this->log($run, $action, 'info', 'action', 'Customer updated', ['customer_id' => $customerId, 'fields' => array_keys($updates)]);
    }

    private function createTask(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $title = $this->interpolateVariables((string) data_get($payload, 'title', 'Workflow task'), $context);
        $description = $this->interpolateVariables((string) data_get($payload, 'description', ''), $context);
        $assignedTo = (int) data_get($payload, 'assigned_to', data_get($context, 'assigned_user_id'));
        $dueInDays = (int) data_get($payload, 'due_in_days', 0);

        $task = Task::create([
            'business_id' => $run->business_id,
            'created_by' => $run->triggered_by,
            'assigned_to' => $assignedTo ?: null,
            'title' => $title,
            'description' => $description,
            'status' => 'open',
            'due_at' => $dueInDays > 0 ? now()->addDays($dueInDays) : null,
            'metadata' => ['workflow_run_id' => $run->id, 'workflow_id' => $run->workflow_id],
        ]);

        return $this->log($run, $action, 'info', 'action', 'Task created', ['task_id' => $task->id, 'title' => $title]);
    }

    private function sendEmail(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $to = $this->interpolateVariables((string) data_get($payload, 'to', data_get($context, 'customer_email', '')), $context);
        $subject = $this->interpolateVariables((string) data_get($payload, 'subject', 'Notification'), $context);
        $body = $this->interpolateVariables((string) data_get($payload, 'body', ''), $context);

        if (empty($to)) {
            return $this->log($run, $action, 'warn', 'action', 'Email skipped — no recipient', []);
        }

        try {
            Mail::raw($body, fn ($msg) => $msg->to($to)->subject($subject));
        } catch (\Throwable $e) {
            return $this->log($run, $action, 'error', 'action', 'Email failed', ['error' => $e->getMessage()]);
        }

        return $this->log($run, $action, 'info', 'action', 'Email sent', ['to' => $to, 'subject' => $subject]);
    }

    private function sendWhatsAppTemplate(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $templateName = (string) data_get($payload, 'template_name', '');
        $to = (string) data_get($payload, 'to', data_get($context, 'customer_phone', ''));

        if (empty($templateName) || empty($to)) {
            return $this->log($run, $action, 'warn', 'action', 'WhatsApp template skipped', ['reason' => 'missing template_name or to']);
        }

        $params = collect(data_get($payload, 'parameters', []))
            ->map(fn ($p) => $this->interpolateVariables((string) $p, $context))
            ->toArray();

        try {
            $whatsAppService = app(\App\Services\WhatsApp\WhatsAppService::class);
            $whatsAppService->sendTemplate($to, $templateName, $params, []);
        } catch (\Throwable $e) {
            return $this->log($run, $action, 'error', 'action', 'WhatsApp template failed', ['error' => $e->getMessage()]);
        }

        return $this->log($run, $action, 'info', 'action', 'WhatsApp template queued', ['template' => $templateName, 'to' => $to]);
    }

    private function callWebhook(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $url = data_get($payload, 'url');
        if (!$url) {
            return $this->log($run, $action, 'warn', 'action', 'Webhook skipped', ['reason' => 'missing url']);
        }

        $this->validateWebhookUrl($url);

        $method = mb_strtoupper(data_get($payload, 'method', 'POST'));
        $headers = data_get($payload, 'headers', []);

        $body = [
            'workflow_run_id' => $run->id,
            'workflow_id' => $run->workflow_id,
            'business_id' => $run->business_id,
            'trigger_type' => $run->trigger_type,
            'context' => $context,
            'payload' => $payload,
        ];

        try {
            $http = Http::timeout((int) data_get($payload, 'timeout', config('workflow.action_timeout', 30)))
                ->acceptJson();

            foreach ($headers as $key => $value) {
                $http->withHeader($key, $this->interpolateVariables((string) $value, $context));
            }

            $response = match ($method) {
                'GET' => $http->get($url, $body),
                'PUT' => $http->put($url, $body),
                'PATCH' => $http->patch($url, $body),
                'DELETE' => $http->delete($url, $body),
                default => $http->post($url, $body),
            };
        } catch (\Throwable $e) {
            return $this->log($run, $action, 'error', 'action', 'Webhook failed', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return $this->log($run, $action, $response->successful() ? 'info' : 'error', 'action', 'Webhook invoked', [
            'url' => $url,
            'method' => $method,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
    }

    private function delayExecution(WorkflowRun $run, WorkflowAction $action, array $payload): array
    {
        $delayValue = (int) data_get($payload, 'value', 5);
        $unit = data_get($payload, 'unit', 'minutes');

        $delayMinutes = match ($unit) {
            'seconds' => $delayValue / 60,
            'hours' => $delayValue * 60,
            'days' => $delayValue * 1440,
            default => $delayValue,
        };

        $run->update([
            'status' => 'waiting',
            'next_retry_at' => now()->addMinutes(max(0.0167, $delayMinutes)),
        ]);

        return $this->log($run, $action, 'info', 'action', 'Execution delayed', ['delay' => $delayValue, 'unit' => $unit, 'next_retry_at' => $run->next_retry_at]);
    }

    private function runCustomAction(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $actionKey = data_get($payload, 'action_key');
        if (!$actionKey) {
            return $this->log($run, $action, 'warn', 'action', 'Custom action skipped', ['reason' => 'missing action_key']);
        }

        $handler = $this->handlerCache[$actionKey] ?? null;

        if ($handler) {
            try {
                return $handler($run, $action, $context, $payload);
            } catch (\Throwable $e) {
                return $this->log($run, $action, 'error', 'action', 'Custom action failed', ['key' => $actionKey, 'error' => $e->getMessage()]);
            }
        }

        return $this->log($run, $action, 'info', 'action', 'Custom action dispatched', ['key' => $actionKey, 'context_keys' => array_keys($context)]);
    }

    private function logUnsupported(WorkflowRun $run, WorkflowAction $action, array $context): array
    {
        return $this->log($run, $action, 'info', 'action', 'Action prepared for future integration', [
            'type' => $action->type,
            'context_keys' => array_keys($context),
        ]);
    }

    private function log(WorkflowRun $run, WorkflowAction $action, string $level, string $stage, string $message, array $payload = []): array
    {
        $log = WorkflowLog::create([
            'workflow_run_id' => $run->id,
            'workflow_action_id' => $action->id,
            'level' => $level,
            'stage' => $stage,
            'message' => $message,
            'payload' => $payload,
        ]);

        return ['status' => $level, 'log' => $log->toArray()];
    }

    private function interpolateVariables(string $text, array $context): string
    {
        return preg_replace_callback('/\{\{(\w+(?:\.\w+)*)\}\}/', function ($matches) use ($context) {
            return (string) data_get($context, $matches[1], $matches[0]);
        }, $text);
    }

    private function validateWebhookUrl(string $url): void
    {
        $allowedDomains = config('workflow.security.allowed_webhook_domains', []);
        if (!empty($allowedDomains)) {
            $host = parse_url($url, PHP_URL_HOST);
            $matched = false;
            foreach ($allowedDomains as $domain) {
                if (str_ends_with($host, $domain)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                throw new \InvalidArgumentException("Webhook URL domain not allowed: {$host}");
            }
        }

        if (config('workflow.security.blocked_private_ips', true)) {
            $host = parse_url($url, PHP_URL_HOST);
            if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
                throw new \InvalidArgumentException('Webhook calls to localhost are blocked');
            }
        }
    }
}
