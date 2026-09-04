<?php

namespace App\Enums\Workflow;

enum WorkflowActionType: string
{
    case SendAiReply = 'send_ai_reply';
    case AssignConversation = 'assign_conversation';
    case AddLabel = 'add_label';
    case RemoveLabel = 'remove_label';
    case AddInternalNote = 'add_internal_note';
    case UpdateCustomer = 'update_customer';
    case CreateTask = 'create_task';
    case SendEmail = 'send_email';
    case SendWhatsAppTemplate = 'send_whatsapp_template';
    case DelayExecution = 'delay_execution';
    case CallWebhook = 'call_webhook';
    case RunCustomAction = 'run_custom_action';

    public function label(): string
    {
        return match ($this) {
            self::SendAiReply => 'Send AI reply',
            self::AssignConversation => 'Assign conversation',
            self::AddLabel => 'Add label',
            self::RemoveLabel => 'Remove label',
            self::AddInternalNote => 'Add internal note',
            self::UpdateCustomer => 'Update customer',
            self::CreateTask => 'Create task',
            self::SendEmail => 'Send email',
            self::SendWhatsAppTemplate => 'Send WhatsApp template',
            self::DelayExecution => 'Delay execution',
            self::CallWebhook => 'Call webhook',
            self::RunCustomAction => 'Run custom action',
        };
    }
}