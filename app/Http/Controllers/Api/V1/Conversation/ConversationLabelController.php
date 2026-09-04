<?php

namespace App\Http\Controllers\Api\V1\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\StoreLabelRequest;
use App\Http\Requests\Conversation\UpdateLabelRequest;
use App\Http\Resources\Conversation\ConversationLabelResource;
use App\Models\ConversationLabel;
use App\Services\Conversation\ConversationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ConversationLabelController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function index(\Illuminate\Http\Request $request)
    {
        $businessId = $request->user()->currentBusiness()?->id;
        if (!$businessId) {
            return response()->json(['message' => 'No business selected'], 400);
        }

        $labels = $this->conversationService->getLabels($businessId);

        return ConversationLabelResource::collection($labels);
    }

    public function store(StoreLabelRequest $request)
    {
        $businessId = $request->user()->currentBusiness()?->id;
        if (!$businessId) {
            return response()->json(['message' => 'No business selected'], 400);
        }

        $label = $this->conversationService->createLabel($businessId, $request->validated());

        return new ConversationLabelResource($label);
    }

    public function update(UpdateLabelRequest $request, ConversationLabel $conversationLabel)
    {
        $user = $request->user();
        $authService = app(\App\Contracts\AuthorizationServiceInterface::class);
        $business = $user->currentBusiness();

        if (!$business || !$authService->can($user, \App\Support\PermissionRegistry::CONVERSATIONS_MANAGE_LABELS, $business)) {
            throw \Illuminate\Auth\Access\AuthorizationException::forPermissions([\App\Support\PermissionRegistry::CONVERSATIONS_MANAGE_LABELS]);
        }

        $conversationLabel->update($request->validated());

        return new ConversationLabelResource($conversationLabel->fresh());
    }

    public function destroy(ConversationLabel $conversationLabel)
    {
        $user = request()->user();
        $authService = app(\App\Contracts\AuthorizationServiceInterface::class);
        $business = $user->currentBusiness();

        if (!$business || !$authService->can($user, \App\Support\PermissionRegistry::CONVERSATIONS_MANAGE_LABELS, $business)) {
            throw \Illuminate\Auth\Access\AuthorizationException::forPermissions([\App\Support\PermissionRegistry::CONVERSATIONS_MANAGE_LABELS]);
        }

        $conversationLabel->delete();

        return response()->json(['message' => 'Label deleted']);
    }
}
