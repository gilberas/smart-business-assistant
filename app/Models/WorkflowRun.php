<?php

namespace App\Models;

use App\Enums\Workflow\WorkflowRunStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'workflow_version_id',
        'business_id',
        'triggered_by',
        'trigger_type',
        'status',
        'execution_mode',
        'attempts',
        'max_attempts',
        'input_payload',
        'context',
        'result',
        'started_at',
        'finished_at',
        'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkflowRunStatus::class,
            'input_payload' => 'array',
            'context' => 'array',
            'result' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowLog::class);
    }
}