<?php

namespace App\Services\Workflow;

use App\Enums\Workflow\WorkflowActionType;
use App\Models\WorkflowAction;
use App\Models\WorkflowLog;
use App\Models\WorkflowRun;
use App\Models\Conversation;
use App\Models\Customer;
use App\Services\Conversation\ConversationEventService;
use App\Services\Conversation\ConversationService;
use Illuminate\Support\Facades\Http;

class WorkflowActionEngineService
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly ConversationEventService $conversationEventService,
    ) {}

    public function execute(WorkflowRun $run, WorkflowAction $action, array $context): array
    {
        $payload = $action->payload ?? [];

        return match ($action->type) {
            WorkflowActionType::AssignConversation->value => $this->assignConversation($run, $action, $context, $payload),
            WorkflowActionType::AddInternalNote->value => $this->addInternalNote($run, $action, $context, $payload),
            WorkflowActionType::AddLabel->value => $this->addLabel($run, $action, $context, $payload),
            WorkflowActionType::RemoveLabel->value => $this->removeLabel($run, $action, $context, $payload),
            WorkflowActionType::UpdateCustomer->value => $this->updateCustomer($run, $action, $context, $payload),
            WorkflowActionType::CallWebhook->value => $this->callWebhook($run, $action, $context, $payload),
            WorkflowActionType::DelayExecution->value => $this->delayExecution($run, $action, $payload),
            default => $this->logUnsupported($run, $action, $context),
        };
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

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return $this->log($run, $action, 'error', 'action', 'Assignee not found', ['user_id' => $userId]);
        }

        $this->conversationService->assign($conversation, $user, data_get($payload, 'reason'));

        return $this->log($run, $action, 'info', 'action', 'Conversation assigned', ['conversation_id' => $conversationId, 'user_id' => $userId]);
    }

    private function addInternalNote(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $conversationId = (int) data_get($context, 'conversation_id', data_get($payload, 'conversation_id'));
        $content = (string) data_get($payload, 'content', 'Workflow note');

        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return $this->log($run, $action, 'error', 'action', 'Conversation not found', ['conversation_id' => $conversationId]);
        }

        $this->conversationEventService->addEvent($conversation->id, 'note_added', ['content' => $content, 'source' => 'workflow'], $run->triggered_by);

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

        $customer->fill(collect($payload)->except(['customer_id'])->toArray());
        $customer->save();

        return $this->log($run, $action, 'info', 'action', 'Customer updated', ['customer_id' => $customerId]);
    }

    private function callWebhook(WorkflowRun $run, WorkflowAction $action, array $context, array $payload): array
    {
        $url = data_get($payload, 'url');
        if (!$url) {
            return $this->log($run, $action, 'warn', 'action', 'Webhook skipped', ['reason' => 'missing url']);
        }

        $response = Http::timeout((int) data_get($payload, 'timeout', 10))
            ->acceptJson()
            ->post($url, [
                'workflow_run_id' => $run->id,
                'workflow_id' => $run->workflow_id,
                'business_id' => $run->business_id,
                'trigger_type' => $run->trigger_type,
                'context' => $context,
                'payload' => $payload,
            ]);

        return $this->log($run, $action, $response->successful() ? 'info' : 'error', 'action', 'Webhook invoked', [
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
    }

    private function delayExecution(WorkflowRun $run, WorkflowAction $action, array $payload): array
    {
        $minutes = (int) data_get($payload, 'minutes', 5);
        $run->update([
            'status' => 'waiting',
            'next_retry_at' => now()->addMinutes($minutes),
        ]);

        return $this->log($run, $action, 'info', 'action', 'Execution delayed', ['minutes' => $minutes]);
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
}