<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkSubServicesToGoodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'good_id' => 'required|integer|exists:goods,id',
            'sub_service_ids' => 'present|array',
            'sub_service_ids.*' => 'required|integer|exists:sub_services,id'
        ];
    }

    public function messages()
    {
        return [
            'good_id.required' => 'The product ID (good_id) is required.',
            'good_id.integer' => 'The product ID must be an integer.',
            'good_id.exists' => 'The selected product is invalid.',
            'sub_service_ids.required' => 'Sub-service IDs are required.',
            'sub_service_ids.array' => 'Sub-service IDs must be an array.',
            'sub_service_ids.*.required' => 'Each sub-service ID is required.',
            'sub_service_ids.*.integer' => 'Each sub-service ID must be an integer.',
            'sub_service_ids.*.exists' => 'The selected sub-service ID is invalid.'
        ];
    }
}
