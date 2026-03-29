<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $fillable = ['name', 'email_or_whatsapp', 'is_active', 'category_id', 'city_id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
