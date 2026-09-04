<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_version_id',
        'workflow_trigger_id',
        'group_key',
        'field',
        'operator',
        'value',
        'boolean_connector',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'priority' => 'integer',
        ];
    }

    public function workflowVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class);
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(WorkflowTrigger::class, 'workflow_trigger_id');
    }
}