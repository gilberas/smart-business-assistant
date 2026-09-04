<?php

namespace App\Services\Inbox;

use App\DTOs\RAG\SearchQueryDTO;
use App\Enums\Conversation\ConversationStatus;
use App\Http\Resources\Conversation\ConversationDetailResource;
use App\Http\Resources\Conversation\ConversationEventResource;
use App\Http\Resources\Conversation\ConversationResource;
use App\Http\Resources\Conversation\MessageResource;
use App\Models\Conversation;
use App\Models\HandoverLog;
use App\Models\User;
use App\Repositories\Conversation\ConversationDraftRepository;
use App\Repositories\Conversation\ConversationRepository;
use App\Repositories\Conversation\ConversationEventRepository;
use App\Services\Conversation\ConversationEventService;
use App\Services\Conversation\ConversationService;
use App\Services\Conversation\MessageService;
use App\Services\RAG\RetrievalService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class InboxService
{
    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationService $conversationService,
        private readonly ConversationEventRepository $eventRepository,
        private readonly ConversationEventService $eventService,
        private readonly ConversationDraftRepository $draftRepository,
        private readonly MessageService $messageService,
        private readonly RetrievalService $retrievalService,
        private readonly InboxSuggestionService $suggestionService,
    ) {}

    public function list(int $businessId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->conversationService->list($businessId, $filters, $perPage);
    }

    public function workspace(Conversation $conversation, int $messagePerPage = 50, ?User $viewer = null): array
    {
        $conversation->load(['customer', 'assignedTo', 'labels', 'activeParticipants.user', 'lastMessage']);
        $messages = $this->messageService->getForConversation($conversation->id, $messagePerPage);
        $events = $this->eventService->getForConversation($conversation->id, 50);
        $drafts = $this->draftRepository->forConversation($conversation);
        $readState = $this->readState($conversation, $viewer?->id);

        return [
            'conversation' => (new ConversationDetailResource($conversation))->toArray(request()),
            'messages' => MessageResource::collection($messages->getCollection())->resolve(),
            'events' => ConversationEventResource::collection($events)->resolve(),
            'drafts' => $drafts->map(fn ($draft) => [
                'id' => $draft->id,
                'conversation_id' => $draft->conversation_id,
                'user_id' => $draft->user_id,
                'content' => $draft->content,
                'metadata' => $draft->metadata,
                'sent_at' => $draft->sent_at,
                'updated_at' => $draft->updated_at,
                'author' => $draft->relationLoaded('user') ? $draft->user?->only(['id', 'name']) : null,
            ])->values()->all(),
            'typing' => $this->typingState($conversation),
            'read_state' => $readState,
        ];
    }

    public function assign(Conversation $conversation, User $user, ?string $reason = null): Conversation
    {
        $this->conversationService->assign($conversation, $user, $reason);
        $this->eventService->addEvent($conversation->id, 'assigned', ['assigned_to' => $user->id, 'reason' => $reason], $user->id);

        return $conversation->fresh(['customer', 'assignedTo', 'labels', 'activeParticipants.user', 'lastMessage']);
    }

    public function takeover(Conversation $conversation, User $user, ?string $reason = null): Conversation
    {
        $this->conversationService->assign($conversation, $user, $reason ?? 'takeover');
        HandoverLog::create([
            'conversation_id' => $conversation->id,
            'business_id' => $conversation->business_id,
            'initiated_by' => $user->id,
            'assigned_to' => $user->id,
            'reason' => $reason ?? 'human takeover',
            'handover_type' => 'takeover',
        ]);

        return $conversation->fresh(['customer', 'assignedTo', 'labels', 'activeParticipants.user', 'lastMessage']);
    }

    public function release(Conversation $conversation, User $user, ?string $reason = null): Conversation
    {
        $previous = $conversation->assigned_to;
        $conversation->assigned_to = null;
        $conversation->handover_mode = null;
        $conversation->handover_reason = $reason;
        $conversation->save();

        HandoverLog::create([
            'conversation_id' => $conversation->id,
            'business_id' => $conversation->business_id,
            'initiated_by' => $user->id,
            'assigned_to' => $previous,
            'reason' => $reason ?? 'released by agent',
            'handover_type' => 'release',
        ]);

        $this->eventService->addEvent($conversation->id, 'released', ['previous_assignee' => $previous, 'reason' => $reason], $user->id);

        return $conversation->fresh(['customer', 'assignedTo', 'labels', 'activeParticipants.user', 'lastMessage']);
    }

    public function setStatus(Conversation $conversation, ConversationStatus $status): Conversation
    {
        $conversation->status = $status;
        $conversation->save();

        $this->eventService->addEvent($conversation->id, 'status_changed', ['status' => $status->value], null);

        return $conversation->fresh(['customer', 'assignedTo', 'labels', 'activeParticipants.user', 'lastMessage']);
    }

    public function pin(Conversation $conversation, bool $isPinned): Conversation
    {
        $metadata = $conversation->metadata ?? [];
        $metadata['pinned'] = $isPinned;
        $conversation->metadata = $metadata;
        $conversation->save();

        return $conversation->fresh(['customer', 'assignedTo', 'labels', 'activeParticipants.user', 'lastMessage']);
    }

    public function addNote(Conversation $conversation, User $user, string $content, array $metadata = []): void
    {
        $this->eventService->addEvent($conversation->id, 'note_added', [
            'content' => $content,
            'metadata' => $metadata,
        ], $user->id);
    }

    public function saveDraft(Conversation $conversation, User $user, string $content, array $metadata = []): array
    {
        $draft = $this->draftRepository->upsertForUser($conversation, $user, [
            'content' => $content,
            'metadata' => $metadata,
        ]);

        return [
            'id' => $draft->id,
            'conversation_id' => $draft->conversation_id,
            'user_id' => $draft->user_id,
            'content' => $draft->content,
            'metadata' => $draft->metadata,
            'updated_at' => $draft->updated_at,
        ];
    }

    public function deleteDraft(Conversation $conversation, User $user): bool
    {
        return $this->draftRepository->deleteForUser($conversation, $user);
    }

    public function setTyping(Conversation $conversation, User $user, bool $isTyping): array
    {
        $key = $this->typingKey($conversation, $user);
        if ($isTyping) {
            Redis::setex($key, (int) config('inbox.typing_ttl', 12), json_encode([
                'user_id' => $user->id,
                'name' => $user->name,
                'at' => now()->toISOString(),
            ]));
        } else {
            Redis::del($key);
        }

        return $this->typingState($conversation);
    }

    public function typingState(Conversation $conversation): array
    {
        $prefix = $this->typingPrefix($conversation);

        $keys = $this->scanKeys($prefix . '*');

        $users = [];
        foreach ($keys as $key) {
            $payload = Redis::get($key);
            if (is_string($payload)) {
                $payload = json_decode($payload, true);
            }
            if (is_array($payload)) {
                $users[] = $payload;
            }
        }

        return ['users' => $users, 'is_typing' => !empty($users)];
    }

    public function markRead(Conversation $conversation, User $user): array
    {
        $state = $this->readState($conversation, $user->id);
        Cache::put($this->readKey($conversation, $user), now()->toISOString(), now()->addDays(7));

        return $state;
    }

    public function readState(Conversation $conversation, ?int $userId): array
    {
        if (!$userId) {
            return ['read_at' => null, 'is_unread' => false];
        }

        $readAt = Cache::get($this->readKey($conversation, $userId));
        $lastMessageAt = $conversation->last_message_at?->toISOString();

        return [
            'read_at' => $readAt,
            'is_unread' => $lastMessageAt && (!$readAt || $lastMessageAt > $readAt),
        ];
    }

    public function suggestions(Conversation $conversation, int $businessId, array $options = []): array
    {
        return $this->suggestionService->suggest($conversation, $businessId, $options);
    }

    private function typingPrefix(Conversation $conversation): string
    {
        return "inbox:typing:{$conversation->business_id}:{$conversation->id}:";
    }

    private function typingKey(Conversation $conversation, User $user): string
    {
        return $this->typingPrefix($conversation) . $user->id;
    }

    private function readKey(Conversation $conversation, User|int $user): string
    {
        $userId = $user instanceof User ? $user->id : $user;

        return "inbox:read:{$conversation->business_id}:{$conversation->id}:{$userId}";
    }

    private function scanKeys(string $pattern): array
    {
        $keys = [];
        $cursor = null;

        do {
            $result = Redis::scan($cursor, ['match' => $pattern, 'count' => 100]);

            if (is_array($result) && isset($result[1]) && is_array($result[1])) {
                $keys = array_merge($keys, $result[1]);
                $cursor = is_array($result[0]) ? $result[0] : (int) $result[0];
            } elseif (is_array($result) && $result !== []) {
                $keys = array_merge($keys, $result);
                $cursor = null;
            } else {
                break;
            }
        } while ((int) $cursor !== 0);

        return array_values(array_unique($keys));
    }
}