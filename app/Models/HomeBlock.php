<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeBlock extends Model
{
    protected $guarded = [];

    protected $casts = [
        'page_slug' => 'string',
        'settings' => 'array',
        'cards' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'show_sidebar' => 'boolean',
        'job_count' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
