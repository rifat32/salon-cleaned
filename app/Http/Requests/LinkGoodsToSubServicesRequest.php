<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkGoodsToSubServicesRequest extends FormRequest
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

    public function rules()
    {
        return [
            'sub_service_id' => 'required|exists:sub_services,id',  // Validate that sub_service_id exists
            'good_ids' => 'present|array', // Validate that good_ids is an array
            'good_ids.*' => 'exists:goods,id' // Ensure each good_id exists in the goods table
        ];
    }

    /**
     * Customize the error messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'sub_service_id.required' => 'Sub-service ID is required.',
            'sub_service_id.exists' => 'The sub-service does not exist.',
            'good_ids.required' => 'Good IDs are required.',
            'good_ids.array' => 'Good IDs must be an array.',
            'good_ids.*.exists' => 'One or more goods do not exist.'
        ];
    }
}
