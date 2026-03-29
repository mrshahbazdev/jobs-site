<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name', 'slug'];

    public function jobListings()
    {
        return $this->hasMany(JobListing::class);
    }
}
