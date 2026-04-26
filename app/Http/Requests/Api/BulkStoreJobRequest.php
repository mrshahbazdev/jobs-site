<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkStoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jobs'                        => 'required|array|min:1|max:50',
            'jobs.*.title'                => 'required|string|max:500',
            'jobs.*.description'          => 'required|string',
            'jobs.*.category_id'          => 'required|exists:categories,id',
            'jobs.*.city_id'              => 'required|exists:cities,id',
            'jobs.*.slug'                 => 'nullable|string|max:600',
            'jobs.*.schema_json'          => 'nullable|string',
            'jobs.*.is_active'            => 'nullable|boolean',
            'jobs.*.is_featured'          => 'nullable|boolean',
            'jobs.*.is_premium'           => 'nullable|boolean',
            'jobs.*.deadline'             => 'nullable|string|max:100',
            'jobs.*.department'           => 'nullable|string|max:300',
            'jobs.*.company_name'         => 'nullable|string|max:300',
            'jobs.*.whatsapp_number'      => 'nullable|string|max:20',
            'jobs.*.salary_min'           => 'nullable|numeric',
            'jobs.*.salary_max'           => 'nullable|numeric',
            'jobs.*.salary_range'         => 'nullable|string|max:100',
            'jobs.*.experience'           => 'nullable|string|max:100',
            'jobs.*.job_type'             => 'nullable|string|max:100',
            'jobs.*.contract_type'        => 'nullable|string|max:100',
            'jobs.*.job_role'             => 'nullable|string|max:300',
            'jobs.*.skills'               => 'nullable|string|max:500',
            'jobs.*.education'            => 'nullable|string|max:100',
            'jobs.*.qualification_degree' => 'nullable|string|max:200',
            'jobs.*.newspaper'            => 'nullable|string|max:200',
            'jobs.*.province'             => 'nullable|string|max:100',
            'jobs.*.gender'               => 'nullable|string|max:50',
            'jobs.*.bps_scale'            => 'nullable|string|max:20',
            'jobs.*.testing_service'      => 'nullable|string|max:100',
            'jobs.*.sector'               => 'nullable|string|max:100',
            'jobs.*.sub_sector'           => 'nullable|string|max:200',
            'jobs.*.registration_council' => 'nullable|string|max:200',
            'jobs.*.country'              => 'nullable|string|max:100',
            'jobs.*.is_overseas'          => 'nullable|boolean',
            'jobs.*.is_remote'            => 'nullable|boolean',
            'jobs.*.has_walkin_interview'  => 'nullable|boolean',
            'jobs.*.is_whatsapp_apply'    => 'nullable|boolean',
            'jobs.*.is_retired_army'      => 'nullable|boolean',
            'jobs.*.is_student_friendly'  => 'nullable|boolean',
            'jobs.*.has_accommodation'    => 'nullable|boolean',
            'jobs.*.has_transport'        => 'nullable|boolean',
            'jobs.*.has_medical_insurance' => 'nullable|boolean',
            'jobs.*.is_special_quota'     => 'nullable|boolean',
            'jobs.*.is_minority_quota'    => 'nullable|boolean',
            'jobs.*.meta_description'     => 'nullable|string|max:160',
            'jobs.*.meta_keywords'        => 'nullable|string',
            'jobs.*.thumbnail_base64'     => 'nullable|string',
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
