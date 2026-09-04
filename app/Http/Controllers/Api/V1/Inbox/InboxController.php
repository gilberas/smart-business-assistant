<?php

namespace App\Http\Controllers\Api\V1\Inbox;

use App\Enums\Conversation\ConversationStatus;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Inbox\InboxService;
use App\Services\TenantService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly InboxService $inboxService,
        private readonly TenantService $tenantService,
    ) {}

    private function businessId(): int
    {
        return $this->tenantService->currentOrFail()->id;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $filters = $request->only([
            'status', 'priority', 'channel', 'source', 'assigned_to', 'unassigned',
            'search', 'label_id', 'customer_id', 'active', 'sort', 'direction',
        ]);

        $conversations = $this->inboxService->list(
            $this->businessId(),
            $filters,
            (int) $request->input('per_page', 15),
        );

        $items = $conversations->getCollection()->map(function (Conversation $conversation) use ($request) {
            $conversation->loadMissing(['customer', 'assignedTo', 'labels', 'lastMessage']);

            return array_merge(
                (new \App\Http\Resources\Conversation\ConversationResource($conversation))->toArray($request),
                [
                    'is_pinned' => (bool) data_get($conversation->metadata, 'pinned', false),
                    'is_unread' => $conversation->last_message_at !== null,
                    'last_message_preview' => $conversation->lastMessage?->content,
                ],
            );
        })->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
            'stats' => $this->conversationStats($request),
        ]);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return response()->json([
            'data' => $this->inboxService->workspace($conversation, (int) $request->input('messages_per_page', 50), $request->user()),
        ]);
    }

    public function assign(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('assign', $conversation);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::findOrFail($data['user_id']);

        return response()->json(['data' => $this->inboxService->assign($conversation, $user, $data['reason'] ?? null)]);
    }

    public function takeover(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('assign', $conversation);

        return response()->json(['data' => $this->inboxService->takeover($conversation, $request->user(), $request->input('reason'))]);
    }

    public function release(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('assign', $conversation);

        return response()->json(['data' => $this->inboxService->release($conversation, $request->user(), $request->input('reason'))]);
    }

    public function status(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:new,waiting,assigned,ai_processing,human_reply,resolved,closed,archived'],
        ]);

        return response()->json(['data' => $this->inboxService->setStatus($conversation, ConversationStatus::from($data['status']))]);
    }

    public function pin(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $data = $request->validate([
            'is_pinned' => ['required', 'boolean'],
        ]);

        return response()->json(['data' => $this->inboxService->pin($conversation, $data['is_pinned'])]);
    }

    public function note(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $data = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:10000'],
            'visibility' => ['nullable', 'string', 'in:private,shared'],
        ]);

        $this->inboxService->addNote($conversation, $request->user(), $data['content'], [
            'visibility' => $data['visibility'] ?? 'private',
        ]);

        return response()->json(['message' => 'Note added']);
    }

    public function draft(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $data = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:' . config('inbox.draft.max_length', 10000)],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json(['data' => $this->inboxService->saveDraft($conversation, $request->user(), $data['content'], $data['metadata'] ?? [])]);
    }

    public function deleteDraft(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $this->inboxService->deleteDraft($conversation, $request->user());

        return response()->json(['message' => 'Draft deleted']);
    }

    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('viewMessages', $conversation);

        $data = $request->validate([
            'is_typing' => ['required', 'boolean'],
        ]);

        return response()->json(['data' => $this->inboxService->setTyping($conversation, $request->user(), $data['is_typing'])]);
    }

    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return response()->json(['data' => $this->inboxService->markRead($conversation, $request->user())]);
    }

    public function suggestions(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        return response()->json([
            'data' => $this->inboxService->suggestions($conversation, $this->businessId(), [
                'user_id' => $request->user()?->id,
                'timezone' => $request->user()?->timezone,
            ]),
        ]);
    }

    private function conversationStats(Request $request): array
    {
        $businessId = $this->businessId();
        $statusCounts = app(\App\Repositories\Conversation\ConversationRepository::class)->countByStatus($businessId);
        $unassigned = app(\App\Repositories\Conversation\ConversationRepository::class)->getForBusiness($businessId, ['unassigned' => true], 1)->total();

        return [
            'by_status' => $statusCounts,
            'unassigned' => $unassigned,
        ];
    }
}