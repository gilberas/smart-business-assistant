<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCustomFieldValue extends Model
{
    protected $fillable = [
        'customer_id',
        'custom_field_id',
        'value',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomerCustomField::class, 'custom_field_id');
    }
}
