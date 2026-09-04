<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'workflow_version_id',
        'business_id',
        'cron_expression',
        'next_run_at',
        'last_run_at',
        'schedule_payload',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'schedule_payload' => 'array',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'is_enabled' => 'boolean',
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
}