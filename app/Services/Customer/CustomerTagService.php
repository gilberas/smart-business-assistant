<?php

namespace App\Services\Customer;

use App\Enums\ActivityType;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Models\CustomerTag;
use App\Models\User;
use App\Repositories\Customer\CustomerTagRepository;
use Illuminate\Validation\ValidationException;

class CustomerTagService
{
    public function __construct(
        private readonly CustomerTagRepository $tagRepository,
    ) {}

    public function all(Business $business): iterable
    {
        return $this->tagRepository->all($business);
    }

    public function find(Business $business, int $id): CustomerTag
    {
        $tag = $this->tagRepository->findById($business, $id);

        if (!$tag) {
            throw ValidationException::withMessages([
                'tag' => [__('customers.tag_not_found')],
            ]);
        }

        return $tag;
    }

    public function create(Business $business, array $data): CustomerTag
    {
        $existing = $this->tagRepository->findByName($business, $data['name']);

        if ($existing) {
            throw ValidationException::withMessages([
                'name' => [__('customers.tag_duplicate')],
            ]);
        }

        return $this->tagRepository->create($business, $data);
    }

    public function update(Business $business, CustomerTag $tag, array $data): CustomerTag
    {
        if (isset($data['name']) && $data['name'] !== $tag->name) {
            $existing = $this->tagRepository->findByName($business, $data['name']);
            if ($existing) {
                throw ValidationException::withMessages([
                    'name' => [__('customers.tag_duplicate')],
                ]);
            }
        }

        return $this->tagRepository->update($tag, $data);
    }

    public function delete(CustomerTag $tag): void
    {
        $this->tagRepository->delete($tag);
    }
    public function attachToCustomer(Business $business, Customer $customer, array $tagNames, User $user): Customer
    {
        $tagIds = $this->resolveTagIds($business, $tagNames);

        $customer->tags()->syncWithoutDetaching($tagIds);

        foreach ($tagIds as $tagId) {
            $tag = $this->tagRepository->findById($business, $tagId);
            if ($tag) {
                CustomerActivity::record(
                    $business->id,
                    $customer->id,
                    ActivityType::TagAttached,
                    $user->id,
                    __('customers.activity_tag_attached', ['tag' => $tag->name]),
                );
            }
        }

        return $customer->fresh()->load('tags');
    }

    public function detachFromCustomer(Business $business, Customer $customer, array $tagNames, User $user): Customer
    {
        $tagIds = $this->resolveTagIds($business, $tagNames);

        $customer->tags()->detach($tagIds);

        CustomerActivity::record(
            $business->id,
            $customer->id,
            ActivityType::TagDetached,
            $user->id,
            __('customers.activity_tag_detached'),
        );


        return $customer->fresh()->load('tags');
    }

    private function resolveTagIds(Business $business, array $tagNames): array
    {
        return $this->tagRepository->syncTags($business, $tagNames);
    }
}
