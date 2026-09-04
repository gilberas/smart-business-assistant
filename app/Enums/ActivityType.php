<?php

namespace App\Enums;

enum ActivityType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Archived = 'archived';
    case Restored = 'restored';
    case Deleted = 'deleted';
    case Merged = 'merged';
    case NoteAdded = 'note_added';
    case TagAttached = 'tag_attached';
    case TagDetached = 'tag_detached';
    case Imported = 'imported';
    case Exported = 'exported';
    case StatusChanged = 'status_changed';
    case StageChanged = 'stage_changed';
    case Conversation = 'conversation';
    case Message = 'message';
    case Order = 'order';
    case Broadcast = 'broadcast';
    case AiInteraction = 'ai_interaction';
}
