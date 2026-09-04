<?php

namespace App\Services\Broadcast;

use App\Contracts\Broadcast\AudienceServiceInterface;
use App\DTOs\Broadcast\AudiencePreviewDTO;
use App\Models\Business;
use App\Models\Customer;
use App\Repositories\Broadcast\BroadcastRecipientRepository;
use Illuminate\Database\Eloquent\Builder;

class AudienceService implements AudienceServiceInterface
{
    private const ALLOWED_FIELDS = [
        'tags', 'status', 'lifecycle_stage', 'customer_type',
        'city', 'state', 'country', 'total_orders', 'total_spent',
        'created_at', 'last_interacted_at', 'loyalty_tier',
    ];

    public function __construct(
        private readonly BroadcastRecipientRepository $recipientRepo,
    ) {}

    public function count(Business $business, array $filters): int
    {
        return $this->buildQuery($business, $filters)->count();
    }

    public function preview(Business $business, array $filters, int $limit = 50): AudiencePreviewDTO
    {
        $customers = $this->buildQuery($business, $filters)
            ->limit($limit)
            ->get(['id', 'uuid', 'name', 'phone', 'status', 'city', 'state', 'country', 'total_orders', 'total_spent', 'last_interacted_at']);

        return AudiencePreviewDTO::fromResult(
            count: $this->count($business, $filters),
            preview: $customers->toArray(),
            filters: $filters,
        );
    }

    public function resolve(Business $business, array $filters): iterable
    {
        return $this->buildQuery($business, $filters)->lazy(100);
    }

    private function buildQuery(Business $business, array $filters): Builder
    {
        $query = Customer::query()->where('business_id', $business->id);

        foreach ($filters as $field => $condition) {
            if (!in_array($field, self::ALLOWED_FIELDS, true)) {
                continue;
            }

            if ($field === 'tags') {
                $this->applyTagFilter($query, $condition);
                continue;
            }

            if (is_array($condition)) {
                $operator = $condition['operator'] ?? 'equals';
                $value = $condition['value'] ?? null;
                $this->applyFilter($query, $field, $operator, $value);
            } else {
                $query->where($field, $condition);
            }
        }

        return $query;
    }

    private function applyTagFilter(Builder $query, $condition): void
    {
        $value = is_array($condition) ? ($condition['value'] ?? []) : $condition;
        $operator = is_array($condition) ? ($condition['operator'] ?? 'in') : 'in';
        $tagIds = is_array($value) ? $value : [$value];

        if ($operator === 'in') {
            $query->whereHas('tags', fn(Builder $q) => $q->whereIn('customer_tags.id', $tagIds));
        } elseif ($operator === 'not_in') {
            $query->whereDoesntHave('tags', fn(Builder $q) => $q->whereIn('customer_tags.id', $tagIds));
        }
    }

    private function applyFilter(Builder $query, string $field, string $operator, mixed $value): void
    {
        match ($operator) {
            'equals' => $query->where($field, $value),
            'not_equals' => $query->where($field, '!=', $value),
            'in' => $query->whereIn($field, (array) $value),
            'not_in' => $query->whereNotIn($field, (array) $value),
            'contains' => $query->where($field, 'like', '%'.$value.'%'),
            'greater_than' => $query->where($field, '>', $value),
            'less_than' => $query->where($field, '<', $value),
            'date_before' => $query->whereDate($field, '<', $value),
            'date_after' => $query->whereDate($field, '>', $value),
            'is_set' => $query->whereNotNull($field),
            'is_not_set' => $query->whereNull($field),
            default => $query->where($field, $value),
        };
    }
}
