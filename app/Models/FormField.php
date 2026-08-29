<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    protected $fillable = [
        'form_template_id', 'section', 'label', 'field_key', 'field_type',
        'options', 'placeholder', 'is_required', 'sort_order', 'width'
    ];

    protected $casts = ['options' => 'array', 'is_required' => 'boolean'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FormAnswer::class);
    }
}
