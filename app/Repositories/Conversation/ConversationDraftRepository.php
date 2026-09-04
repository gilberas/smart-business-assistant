<?php

namespace App\Repositories\Conversation;

use App\Models\Conversation;
use App\Models\ConversationDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ConversationDraftRepository
{
    public function __construct(
        private readonly ConversationDraft $model,
    ) {}

    public function forConversation(Conversation $conversation): Collection
    {
        return $this->model->with('user')
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findForUser(Conversation $conversation, User $user): ?ConversationDraft
    {
        return $this->model->with('user')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function upsertForUser(Conversation $conversation, User $user, array $data): ConversationDraft
    {
        $draft = $this->model->updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ],
            [
                'business_id' => $conversation->business_id,
                'content' => $data['content'],
                'metadata' => $data['metadata'] ?? [],
                'sent_at' => $data['sent_at'] ?? null,
            ],
        );

        return $draft->fresh('user');
    }

    public function deleteForUser(Conversation $conversation, User $user): bool
    {
        return (bool) $this->model->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->delete();
    }
}