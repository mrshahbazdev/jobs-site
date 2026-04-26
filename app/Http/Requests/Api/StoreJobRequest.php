<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Core
            'title'                => 'required|string|max:500',
            'description'          => 'required|string',
            'category_id'          => 'required|exists:categories,id',
            'city_id'              => 'required|exists:cities,id',
            'slug'                 => 'nullable|string|max:600',
            'schema_json'          => 'nullable|string',
            // Status
            'is_active'            => 'nullable|boolean',
            'is_featured'          => 'nullable|boolean',
            'is_premium'           => 'nullable|boolean',
            // Job Details
            'deadline'             => 'nullable|string|max:100',
            'department'           => 'nullable|string|max:300',
            'company_name'         => 'nullable|string|max:300',
            'whatsapp_number'      => 'nullable|string|max:20',
            'salary_min'           => 'nullable|numeric',
            'salary_max'           => 'nullable|numeric',
            'salary_range'         => 'nullable|string|max:100',
            'experience'           => 'nullable|string|max:100',
            'job_type'             => 'nullable|string|max:100',
            'contract_type'        => 'nullable|string|max:100',
            'job_role'             => 'nullable|string|max:300',
            'skills'               => 'nullable|string|max:500',
            // Classification
            'education'            => 'nullable|string|max:100',
            'qualification_degree' => 'nullable|string|max:200',
            'newspaper'            => 'nullable|string|max:200',
            'province'             => 'nullable|string|max:100',
            'gender'               => 'nullable|string|max:50',
            'bps_scale'            => 'nullable|string|max:20',
            'testing_service'      => 'nullable|string|max:100',
            'sector'               => 'nullable|string|max:100',
            'sub_sector'           => 'nullable|string|max:200',
            'registration_council' => 'nullable|string|max:200',
            'country'              => 'nullable|string|max:100',
            // Boolean Flags
            'is_overseas'          => 'nullable|boolean',
            'is_remote'            => 'nullable|boolean',
            'has_walkin_interview'  => 'nullable|boolean',
            'is_whatsapp_apply'    => 'nullable|boolean',
            'is_retired_army'      => 'nullable|boolean',
            'is_student_friendly'  => 'nullable|boolean',
            'has_accommodation'    => 'nullable|boolean',
            'has_transport'        => 'nullable|boolean',
            'has_medical_insurance' => 'nullable|boolean',
            'is_special_quota'     => 'nullable|boolean',
            'is_minority_quota'    => 'nullable|boolean',
            // SEO
            'meta_description'     => 'nullable|string|max:160',
            'meta_keywords'        => 'nullable|string',
            'thumbnail_base64'     => 'nullable|string',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
