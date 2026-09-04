<?php

namespace App\Models\Scopes;

use App\Managers\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function __construct(
        private readonly string $column = 'business_id',
    ) {}

    public function apply(Builder $builder, Model $model): void
    {
        $tenantManager = app(TenantManager::class);
        $businessId = $tenantManager->currentId();

        if ($businessId) {
            $builder->where($this->column, $businessId);
        }
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenant', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
