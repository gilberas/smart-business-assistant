<?php

namespace App\Repositories\Conversation;

use App\Models\ConversationLabel;
use Illuminate\Database\Eloquent\Collection;

class ConversationLabelRepository
{
    public function __construct(
        private readonly ConversationLabel $model
    ) {}

    public function findById(int $id): ?ConversationLabel
    {
        return $this->model->find($id);
    }

    public function getForBusiness(int $businessId): Collection
    {
        return $this->model->where('business_id', $businessId)->orderBy('name')->get();
    }

    public function create(array $data): ConversationLabel
    {
        return $this->model->create($data);
    }

    public function update(ConversationLabel $label, array $data): bool
    {
        return $label->update($data);
    }

    public function delete(ConversationLabel $label): bool
    {
        return $label->delete();
    }
}
