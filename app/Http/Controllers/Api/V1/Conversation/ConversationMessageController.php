<?php

namespace App\Http\Controllers\Api\V1\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\SendMessageRequest;
use App\Http\Resources\Conversation\MessageResource;
use App\Models\Conversation;
use App\Services\Conversation\MessageService;
use App\Services\Conversation\ConversationEventService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ConversationMessageController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly MessageService $messageService,
        private readonly ConversationEventService $eventService,
    ) {}

    public function index(Conversation $conversation, \Illuminate\Http\Request $request)
    {
        $this->authorize('viewMessages', $conversation);

        $perPage = $request->input('per_page', 50);
        $messages = $this->messageService->getForConversation($conversation->id, $perPage);

        return MessageResource::collection($messages);
    }

    public function store(SendMessageRequest $request, Conversation $conversation)
    {
        $this->authorize('sendMessage', $conversation);

        $message = $this->messageService->send($conversation->id, [
            'business_id' => $conversation->business_id,
            'sender_type' => $request->sender_type ?? 'agent',
            'sender_id' => $request->user()->id,
            'content' => $request->content,
            'content_type' => $request->content_type ?? 'text',
            'direction' => 'outbound',
            'message_type' => $request->content_type ?? 'text',
        ]);

        $this->eventService->addEvent(
            $conversation->id,
            'message_sent',
            ['message_id' => $message->id, 'sender_id' => $request->user()->id],
            $request->user()->id,
        );

        return new MessageResource($message);
    }
}
