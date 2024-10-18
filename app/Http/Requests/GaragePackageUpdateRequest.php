<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GaragePackageUpdateRequest extends FormRequest
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
            "id" => "required|numeric",
            "garage_id" => "required|numeric",
            "name" => "required|string",
            "description" => "nullable|string",
            "price" => "required|numeric",
            'sub_service_ids' => 'required|array',
            'sub_service_ids.*' => 'required|numeric',
            "is_active" => "required|boolean"
        ];
    }
}
