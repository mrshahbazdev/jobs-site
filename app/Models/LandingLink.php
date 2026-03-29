<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingLink extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'landing_group_id' => 'integer',
    ];

    public function group()
    {
        return $this->belongsTo(LandingGroup::class, 'landing_group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
