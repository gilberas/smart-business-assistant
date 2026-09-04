<?php

namespace App\Models;

use App\Enums\Workflow\WorkflowExecutionMode;
use App\Enums\Workflow\WorkflowStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'owner_id',
        'name',
        'description',
        'category',
        'status',
        'execution_mode',
        'version',
        'variables',
        'metadata',
        'enabled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkflowStatus::class,
            'execution_mode' => WorkflowExecutionMode::class,
            'variables' => 'array',
            'metadata' => 'array',
            'enabled_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class);
    }

    public function latestVersion(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class)->orderByDesc('version');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    public function variablesDefinition(): HasMany
    {
        return $this->hasMany(WorkflowVariable::class);
    }
}