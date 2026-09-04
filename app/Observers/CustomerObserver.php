<?php

namespace App\Observers;

use App\Enums\ActivityType;
use App\Models\Customer;
use App\Models\CustomerActivity;
use Illuminate\Support\Facades\Auth;

class CustomerObserver
{
    private function recordActivity(Customer $customer, ActivityType $type, ?array $metadata = null): void
    {
        $actor = Auth::user();

        CustomerActivity::record(
            $customer->business_id,
            $customer->id,
            $type,
            $actor?->id,
            $type->value,
            $metadata,
        );
    }

    public function created(Customer $customer): void
    {
        $this->recordActivity($customer, ActivityType::Created);
    }

    public function updated(Customer $customer): void
    {
        if ($customer->isDirty('status')) {
            if ($customer->status === 'archived') {
                $this->recordActivity($customer, ActivityType::Archived);
            } elseif ($customer->status === 'active' && $customer->getOriginal('status') === 'archived') {
                $this->recordActivity($customer, ActivityType::Restored);
            }
            return;
        }

        $changes = [];
        foreach ($customer->getDirty() as $field => $value) {
            if (!in_array($field, ['updated_at', 'last_interacted_at', 'last_activity_at'])) {
                $changes[$field] = [
                    'old' => $customer->getOriginal($field),
                    'new' => $value,
                ];
            }
        }

        if (!empty($changes)) {
            $this->recordActivity($customer, ActivityType::Updated, ['changes' => $changes]);
        }
    }

    public function archived(Customer $customer): void
    {
        $this->recordActivity($customer, ActivityType::Archived);
    }

    public function restored(Customer $customer): void
    {
        $this->recordActivity($customer, ActivityType::Restored);
    }

    public function deleted(Customer $customer): void
    {
        $this->recordActivity($customer, ActivityType::Deleted);
    }
}
