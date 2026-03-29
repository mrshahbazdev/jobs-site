<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingGroup extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function links()
    {
        return $this->hasMany(LandingLink::class)->orderBy('sort_order', 'asc');
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
