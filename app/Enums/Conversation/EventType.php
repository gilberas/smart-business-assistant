<?php

namespace App\Enums\Conversation;

enum EventType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Assigned = 'assigned';
    case Unassigned = 'unassigned';
    case Closed = 'closed';
    case Reopened = 'reopened';
    case Archived = 'archived';
    case PriorityChanged = 'priority_changed';
    case StatusChanged = 'status_changed';
    case LabelAdded = 'label_added';
    case LabelRemoved = 'label_removed';
    case ParticipantAdded = 'participant_added';
    case ParticipantRemoved = 'participant_removed';
    case Routed = 'routed';
    case NoteAdded = 'note_added';
    case MessageReceived = 'message_received';
    case MessageSent = 'message_sent';
    case AiRequested = 'ai_requested';
    case AiResponded = 'ai_responded';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::Assigned => 'Assigned',
            self::Unassigned => 'Unassigned',
            self::Closed => 'Closed',
            self::Reopened => 'Reopened',
            self::Archived => 'Archived',
            self::PriorityChanged => 'Priority Changed',
            self::StatusChanged => 'Status Changed',
            self::LabelAdded => 'Label Added',
            self::LabelRemoved => 'Label Removed',
            self::ParticipantAdded => 'Participant Added',
            self::ParticipantRemoved => 'Participant Removed',
            self::Routed => 'Routed',
            self::NoteAdded => 'Note Added',
            self::MessageReceived => 'Message Received',
            self::MessageSent => 'Message Sent',
            self::AiRequested => 'AI Requested',
            self::AiResponded => 'AI Responded',
        };
    }
}
