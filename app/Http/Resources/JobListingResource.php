<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'slug'                 => $this->slug,
            'url'                  => url('/jobs/' . $this->slug),
            'description_html'     => $this->description_html,
            // Relationships
            'category'             => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'category_id'          => $this->category_id,
            'city'                 => $this->whenLoaded('city', fn () => [
                'id'   => $this->city->id,
                'name' => $this->city->name,
                'slug' => $this->city->slug,
            ]),
            'city_id'              => $this->city_id,
            // Status
            'is_active'            => (bool) $this->is_active,
            'is_featured'          => (bool) $this->is_featured,
            'is_premium'           => (bool) $this->is_premium,
            // Job Details
            'company_name'         => $this->company_name,
            'company_logo'         => $this->company_logo ? asset('storage/' . $this->company_logo) : null,
            'department'           => $this->department,
            'deadline'             => $this->deadline,
            'whatsapp_number'      => $this->whatsapp_number,
            'salary_min'           => $this->salary_min,
            'salary_max'           => $this->salary_max,
            'salary_range'         => $this->salary_range,
            'experience'           => $this->experience,
            'job_type'             => $this->job_type,
            'contract_type'        => $this->contract_type,
            'job_role'             => $this->job_role,
            'skills'               => $this->skills,
            // Classification
            'education'            => $this->education,
            'qualification_degree' => $this->qualification_degree,
            'newspaper'            => $this->newspaper,
            'province'             => $this->province,
            'gender'               => $this->gender,
            'bps_scale'            => $this->bps_scale,
            'testing_service'      => $this->testing_service,
            'sector'               => $this->sector,
            'sub_sector'           => $this->sub_sector,
            'registration_council' => $this->registration_council,
            'country'              => $this->country,
            // Boolean Flags
            'is_overseas'          => (bool) $this->is_overseas,
            'is_remote'            => (bool) $this->is_remote,
            'has_walkin_interview'  => (bool) $this->has_walkin_interview,
            'is_whatsapp_apply'    => (bool) $this->is_whatsapp_apply,
            'is_retired_army'      => (bool) $this->is_retired_army,
            'is_student_friendly'  => (bool) $this->is_student_friendly,
            'has_accommodation'    => (bool) $this->has_accommodation,
            'has_transport'        => (bool) $this->has_transport,
            'has_medical_insurance' => (bool) $this->has_medical_insurance,
            'is_special_quota'     => (bool) $this->is_special_quota,
            'is_minority_quota'    => (bool) $this->is_minority_quota,
            // SEO
            'meta_description'     => $this->meta_description,
            'meta_keywords'        => $this->meta_keywords,
            'schema_json'          => $this->schema_json,
            // Timestamps
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
