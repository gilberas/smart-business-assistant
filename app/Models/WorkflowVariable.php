<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowVariable extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'workflow_version_id',
        'business_id',
        'name',
        'data_type',
        'default_value',
        'description',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'default_value' => 'array',
            'description' => 'array',
            'is_required' => 'boolean',
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