<?php

namespace App\Repositories\Customer;

use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerNote;

class CustomerNoteRepository
{
    public function forCustomer(Business $business, Customer $customer): iterable
    {
        return CustomerNote::where('business_id', $business->id)
            ->where('customer_id', $customer->id)
            ->with('author')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findById(Business $business, int $id): ?CustomerNote
    {
        return CustomerNote::where('business_id', $business->id)
            ->with('author')
            ->find($id);
    }

    public function create(Business $business, Customer $customer, int $authorId, array $data): CustomerNote
    {
        return CustomerNote::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'author_id' => $authorId,
            'content' => $data['content'],
            'is_pinned' => $data['is_pinned'] ?? false,
        ]);
    }

    public function update(CustomerNote $note, array $data): CustomerNote
    {
        $note->update($data);
        return $note->fresh();
    }

    public function delete(CustomerNote $note): void
    {
        $note->delete();
    }

    public function togglePin(CustomerNote $note): CustomerNote
    {
        $note->update(['is_pinned' => !$note->is_pinned]);
        return $note->fresh();
    }
}
