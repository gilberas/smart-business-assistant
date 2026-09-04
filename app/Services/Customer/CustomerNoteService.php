<?php

namespace App\Services\Customer;

use App\Enums\ActivityType;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Models\CustomerNote;
use App\Models\User;
use App\Repositories\Customer\CustomerNoteRepository;
use Illuminate\Validation\ValidationException;

class CustomerNoteService
{
    public function __construct(
        private readonly CustomerNoteRepository $noteRepository,
    ) {}

    public function forCustomer(Business $business, Customer $customer): iterable
    {
        return $this->noteRepository->forCustomer($business, $customer);
    }

    public function create(Business $business, Customer $customer, User $user, array $data): CustomerNote
    {
        $note = $this->noteRepository->create($business, $customer, $user->id, $data);

        CustomerActivity::record(
            $business->id,
            $customer->id,
            ActivityType::NoteAdded,
            $user->id,
            __('customers.activity_note_added'),
        );

        return $note->load('author');
    }

    public function update(Business $business, Customer $customer, CustomerNote $note, array $data): CustomerNote
    {
        if ($note->business_id !== $business->id || $note->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'note' => [__('customers.note_not_found')],
            ]);
        }

        return $this->noteRepository->update($note, $data);
    }

    public function delete(Business $business, Customer $customer, CustomerNote $note): void
    {
        if ($note->business_id !== $business->id || $note->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'note' => [__('customers.note_not_found')],
            ]);
        }

        $this->noteRepository->delete($note);
    }

    public function togglePin(Business $business, Customer $customer, CustomerNote $note): CustomerNote
    {
        if ($note->business_id !== $business->id || $note->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'note' => [__('customers.note_not_found')],
            ]);
        }

        return $this->noteRepository->togglePin($note);
    }
}
