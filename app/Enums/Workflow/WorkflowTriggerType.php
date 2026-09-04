<?php

namespace App\Enums\Workflow;

enum WorkflowTriggerType: string
{
    case Manual = 'manual';
    case Webhook = 'webhook';
    case Scheduled = 'scheduled';
    case Api = 'api';
    case WhatsAppMessage = 'whatsapp_message';
    case ConversationAssigned = 'conversation_assigned';
    case ConversationResolved = 'conversation_resolved';
    case CustomerCreated = 'customer_created';
    case CustomerUpdated = 'customer_updated';
    case ProductUpdated = 'product_updated';
    case KnowledgeArticlePublished = 'knowledge_article_published';
    case BusinessHoursStarted = 'business_hours_started';
    case BusinessHoursEnded = 'business_hours_ended';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual trigger',
            self::Webhook => 'Webhook trigger',
            self::Scheduled => 'Scheduled trigger',
            self::Api => 'API trigger',
            self::WhatsAppMessage => 'New WhatsApp message',
            self::ConversationAssigned => 'Conversation assigned',
            self::ConversationResolved => 'Conversation resolved',
            self::CustomerCreated => 'Customer created',
            self::CustomerUpdated => 'Customer updated',
            self::ProductUpdated => 'Product updated',
            self::KnowledgeArticlePublished => 'Knowledge article published',
            self::BusinessHoursStarted => 'Business hours started',
            self::BusinessHoursEnded => 'Business hours ended',
        };
    }
}