<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
    protected $fillable = [
        'title', 'slug', 'category_id', 'city_id', 'department', 
        'salary_range', 'deadline', 'description_html', 'schema_json',
        'is_featured', 'is_premium', 'is_active', 'job_source_image_id',
        'meta_description', 'meta_keywords', 'experience', 'job_type',
        'whatsapp_number', 'company_name', 'company_logo', 'salary_min', 'salary_max',
        'education', 'newspaper', 'province', 'gender', 'bps_scale',
        'qualification_degree', 'is_special_quota', 'is_minority_quota',
        'testing_service', 'country', 'is_overseas', 'sector',
        'job_role', 'registration_council',
        'has_walkin_interview', 'is_remote', 'is_whatsapp_apply', 'is_retired_army', 'is_student_friendly',
        'sub_sector', 'contract_type', 'skills', 'has_accommodation', 'has_transport', 'has_medical_insurance'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function sourceImage()
    {
        return $this->belongsTo(JobSourceImage::class, 'job_source_image_id');
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function bookmarkedByUsers()
    {
        return $this->belongsToMany(User::class, 'bookmarks', 'user_id', 'job_listing_id')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
