<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_version_id',
        'workflow_condition_id',
        'branch_key',
        'type',
        'name',
        'payload',
        'position',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'position' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    public function workflowVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class);
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(WorkflowCondition::class, 'workflow_condition_id');
    }
}