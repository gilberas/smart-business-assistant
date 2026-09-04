<?php

namespace App\Services\Customer;

use App\Enums\ActivityType;
use App\Enums\CustomerStatus;
use App\Events\Customer\CustomerArchived;
use App\Events\Customer\CustomerCreated;
use App\Events\Customer\CustomerDeleted;
use App\Events\Customer\CustomerMerged;
use App\Events\Customer\CustomerRestored;
use App\Events\Customer\CustomerUpdated;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Models\User;
use App\Repositories\Customer\CustomerRepository;
use App\Repositories\Customer\CustomerTagRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerTagRepository $tagRepository,
    ) {}

    public function list(Business $business, array $params = []): LengthAwarePaginator
    {
        return $this->customerRepository->paginate($business, $params);
    }

    public function get(Business $business, int $id): Customer
    {
        $customer = $this->customerRepository->findById($business, $id);

        if (!$customer) {
            throw ValidationException::withMessages([
                'customer' => [__('customers.not_found')],
            ]);
        }

        return $customer;
    }

    public function getByUuid(Business $business, string $uuid): Customer
    {
        $customer = $this->customerRepository->findByUuid($business, $uuid);

        if (!$customer) {
            throw ValidationException::withMessages([
                'customer' => [__('customers.not_found')],
            ]);
        }

        return $customer;
    }

    public function create(Business $business, User $user, array $data): Customer
    {
        if (!empty($data['phone'])) {
            $existing = $this->customerRepository->findByPhone($business, $data['phone']);
            if ($existing) {
                throw ValidationException::withMessages([
                    'phone' => [__('customers.duplicate_phone')],
                ]);
            }
        }

        if (!empty($data['email'])) {
            $existing = $this->customerRepository->findByEmail($business, $data['email']);
            if ($existing) {
                throw ValidationException::withMessages([
                    'email' => [__('customers.duplicate_email')],
                ]);
            }
        }

        $customer = $this->customerRepository->create($business, $data);

        if (!empty($data['tag_ids'])) {
            $customer->tags()->sync($data['tag_ids']);
        }

        if (!empty($data['tags'])) {
            $tagIds = $this->tagRepository->syncTags($business, $data['tags']);
            $customer->tags()->sync($tagIds);
        }

        if (!empty($data['address'])) {
            $customer->addresses()->create($data['address']);
        }

        event(new CustomerCreated($customer, $user));

        return $customer->load(['tags', 'addresses', 'primaryAddress']);
    }

    public function update(Business $business, User $user, Customer $customer, array $data): Customer
    {
        if (!empty($data['phone'])) {
            $existing = $this->customerRepository->findByPhone($business, $data['phone']);
            if ($existing && $existing->id !== $customer->id) {
                throw ValidationException::withMessages([
                    'phone' => [__('customers.duplicate_phone')],
                ]);
            }
        }

        if (!empty($data['email'])) {
            $existing = $this->customerRepository->findByEmail($business, $data['email']);
            if ($existing && $existing->id !== $customer->id) {
                throw ValidationException::withMessages([
                    'email' => [__('customers.duplicate_email')],
                ]);
            }
        }

        $changes = $customer->getDirty();
        $customer = $this->customerRepository->update($customer, $data);

        if (isset($data['tag_ids'])) {
            $customer->tags()->sync($data['tag_ids']);
        }

        if (isset($data['tags'])) {
            $tagIds = $this->tagRepository->syncTags($business, $data['tags']);
            $customer->tags()->sync($tagIds);
        }

        if (!empty($data['address'])) {
            if ($data['address']['is_primary'] ?? false) {
                $customer->addresses()->update(['is_primary' => false]);
            }
            $customer->addresses()->updateOrCreate(
                ['id' => $data['address']['id'] ?? null],
                $data['address'],
            );
        }

        if (isset($data['status']) && $data['status'] === CustomerStatus::Archived->value) {
            event(new CustomerArchived($customer, $user));
        } else {
            event(new CustomerUpdated($customer, $user, $changes));
        }

        return $customer->load(['tags', 'addresses', 'primaryAddress']);
    }

    public function archive(Business $business, User $user, Customer $customer): Customer
    {
        $customer = $this->customerRepository->update($customer, [
            'status' => CustomerStatus::Archived->value,
        ]);

        event(new CustomerArchived($customer, $user));

        return $customer;
    }

    public function restore(Business $business, User $user, Customer $customer): Customer
    {
        $customer = $this->customerRepository->update($customer, [
            'status' => CustomerStatus::Active->value,
        ]);

        event(new CustomerRestored($customer, $user));

        return $customer;
    }

    public function delete(Business $business, User $user, Customer $customer): void
    {
        $this->customerRepository->delete($customer);
        event(new CustomerDeleted($customer, $user));
    }

    public function forceDelete(Business $business, User $user, Customer $customer): void
    {
        $this->customerRepository->forceDelete($customer);
    }

    public function merge(Business $business, User $user, Customer $primary, Customer $duplicate): Customer
    {
        if ($primary->id === $duplicate->id) {
            throw ValidationException::withMessages([
                'customer' => [__('customers.cannot_merge_self')],
            ]);
        }

        $primary->notes()->saveMany($duplicate->notes()->get());
        $primary->addresses()->saveMany($duplicate->addresses()->get());

        foreach ($duplicate->tags()->get() as $tag) {
            if (!$primary->tags()->where('customer_tag_id', $tag->id)->exists()) {
                $primary->tags()->attach($tag->id);
            }
        }

        $duplicate->conversations()->update(['customer_id' => $primary->id]);
        $duplicate->activity()->update(['customer_id' => $primary->id]);

        event(new CustomerMerged($primary, $duplicate, $user));

        $primary->update([
            'total_orders' => $primary->total_orders + $duplicate->total_orders,
            'total_spent' => $primary->total_spent + $duplicate->total_spent,
            'metadata' => array_merge(
                $primary->metadata ?? [],
                $duplicate->metadata ?? [],
                ['merged_from' => $duplicate->id],
            ),
        ]);

        Customer::withoutEvents(fn() => $this->customerRepository->forceDelete($duplicate));

        return $primary->fresh()->load(['tags', 'addresses', 'primaryAddress']);
    }

    public function search(Business $business, string $term, int $limit = 10): iterable
    {
        return Customer::where('business_id', $business->id)
            ->search($term)
            ->limit($limit)
            ->get();
    }

    public function stats(Business $business): array
    {
        return [
            'total' => Customer::where('business_id', $business->id)->count(),
            'by_status' => $this->customerRepository->countByStatus($business),
            'by_stage' => $this->customerRepository->countByStage($business),
            'active' => Customer::where('business_id', $business->id)->where('status', 'active')->count(),
            'archived' => Customer::where('business_id', $business->id)->where('status', 'archived')->count(),
        ];
    }
}
