<?php

namespace App\Http\Controllers\Api\V1\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\ListConversationRequest;
use App\Http\Requests\Conversation\StoreConversationRequest;
use App\Http\Requests\Conversation\UpdateConversationRequest;
use App\Http\Resources\Conversation\ConversationDetailResource;
use App\Http\Resources\Conversation\ConversationEventResource;
use App\Http\Resources\Conversation\ConversationResource;
use App\Models\Conversation;
use App\Services\Conversation\ConversationEventService;
use App\Services\Conversation\ConversationService;
use App\Services\Conversation\RoutingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ConversationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly ConversationEventService $eventService,
        private readonly RoutingService $routingService,
    ) {}

    public function index(ListConversationRequest $request)
    {
        $this->authorize('viewAny', Conversation::class);

        $businessId = $request->user()->currentBusiness()?->id;
        if (!$businessId) {
            return response()->json(['message' => 'No business selected'], 400);
        }

        $conversations = $this->conversationService->list(
            $businessId,
            $request->validated(),
            $request->input('per_page', 15),
        );

        return ConversationResource::collection($conversations);
    }

    public function store(StoreConversationRequest $request)
    {
        $this->authorize('create', Conversation::class);

        $businessId = $request->user()->currentBusiness()?->id;
        if (!$businessId) {
            return response()->json(['message' => 'No business selected'], 400);
        }

        $conversation = $this->conversationService->create([
            'business_id' => $businessId,
            'customer_id' => $request->customer_id,
            'title' => $request->title,
            'source' => $request->source ?? 'internal',
            'channel' => $request->channel,
            'channel_id' => $request->channel_id,
            'priority' => $request->priority ?? 'normal',
            'metadata' => $request->metadata,
        ]);

        $this->routingService->routeToAi($conversation);

        return new ConversationDetailResource($conversation->load(['customer', 'assignedTo', 'labels', 'activeParticipants.user']));
    }

    public function show(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $conversation->load(['customer', 'assignedTo', 'labels', 'activeParticipants.user']);

        return new ConversationDetailResource($conversation);
    }

    public function update(UpdateConversationRequest $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $this->conversationService->update($conversation, $request->validated());

        return new ConversationDetailResource($conversation->fresh(['customer', 'assignedTo', 'labels', 'activeParticipants.user']));
    }

    public function destroy(Conversation $conversation)
    {
        $this->authorize('delete', $conversation);

        $this->conversationService->delete($conversation);

        return response()->json(['message' => 'Conversation deleted']);
    }

    public function assign(Conversation $conversation, \App\Http\Requests\Conversation\AssignConversationRequest $request)
    {
        $this->authorize('assign', $conversation);

        $user = \App\Models\User::findOrFail($request->user_id);
        $this->conversationService->assign($conversation, $user, $request->reason);

        return new ConversationDetailResource($conversation->fresh(['customer', 'assignedTo', 'labels', 'activeParticipants.user']));
    }

    public function close(Conversation $conversation, \Illuminate\Http\Request $request)
    {
        $this->authorize('close', $conversation);

        $this->conversationService->close($conversation, $request->reason);

        return new ConversationDetailResource($conversation->fresh(['customer', 'assignedTo', 'labels']));
    }

    public function reopen(Conversation $conversation)
    {
        $this->authorize('reopen', $conversation);

        $this->conversationService->reopen($conversation);

        return new ConversationDetailResource($conversation->fresh(['customer', 'assignedTo', 'labels']));
    }

    public function events(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $events = $this->eventService->getForConversation($conversation->id);

        return ConversationEventResource::collection($events);
    }

    public function stats(\Illuminate\Http\Request $request)
    {
        $this->authorize('viewAny', Conversation::class);

        $businessId = $request->user()->currentBusiness()?->id;
        if (!$businessId) {
            return response()->json(['message' => 'No business selected'], 400);
        }

        return response()->json($this->conversationService->stats($businessId));
    }

    public function attachLabel(Conversation $conversation, \Illuminate\Http\Request $request)
    {
        $this->authorize('update', $conversation);

        $request->validate(['label_id' => 'required|integer|exists:conversation_labels,id']);

        $this->conversationService->attachLabel($conversation, $request->label_id);

        return new ConversationDetailResource($conversation->fresh(['customer', 'assignedTo', 'labels', 'activeParticipants.user']));
    }

    public function detachLabel(Conversation $conversation, \App\Models\ConversationLabel $label, \Illuminate\Http\Request $request)
    {
        $this->authorize('update', $conversation);

        $this->conversationService->detachLabel($conversation, $label->id);

        return new ConversationDetailResource($conversation->fresh(['customer', 'assignedTo', 'labels', 'activeParticipants.user']));
    }
}
