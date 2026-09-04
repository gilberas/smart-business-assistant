<?php

namespace App\Models;

use Database\Factories\PromptVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptVersion extends Model
{
    /** @use HasFactory<PromptVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'prompt_template_id',
        'version',
        'content',
        'changelog',
        'variables',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'variables' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PromptTemplate::class, 'prompt_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
