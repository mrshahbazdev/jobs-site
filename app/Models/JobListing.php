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

    /**
     * Generate a JobPosting Schema JSON-LD if none exists.
     */
    public function generateSchema()
    {
        if ($this->schema_json && strlen($this->schema_json) > 50) {
            return $this->schema_json;
        }

        $schema = [
            "@context" => "https://schema.org/",
            "@type" => "JobPosting",
            "title" => $this->title,
            "description" => strip_tags($this->description_html),
            "datePosted" => $this->created_at->toIso8601String(),
            "validThrough" => $this->deadline ? \Carbon\Carbon::parse($this->deadline)->toIso8601String() : $this->created_at->addMonths(3)->toIso8601String(),
            "employmentType" => $this->job_type ?: "FULL_TIME",
            "hiringOrganization" => [
                "@type" => "Organization",
                "name" => $this->company_name ?: "JobsPic Pakistan",
                "sameAs" => url('/'),
                "logo" => $this->company_logo ? asset('storage/'.$this->company_logo) : asset('icons/icon-192x192.png')
            ],
            "jobLocation" => [
                "@type" => "Place",
                "address" => [
                    "@type" => "PostalAddress",
                    "addressLocality" => $this->city ? $this->city->name : "Pakistan",
                    "addressRegion" => $this->province ?: "Pakistan",
                    "addressCountry" => "PK"
                ]
            ]
        ];

        if ($this->salary_min) {
            $schema['baseSalary'] = [
                "@type" => "MonetaryAmount",
                "currency" => "PKR",
                "value" => [
                    "@type" => "QuantitativeValue",
                    "minValue" => $this->salary_min,
                    "maxValue" => $this->salary_max ?: $this->salary_min,
                    "unitText" => "MONTH"
                ]
            ];
        }

        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
