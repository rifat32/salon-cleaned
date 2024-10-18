<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubServiceUpdateRequest extends FormRequest
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
            "name" => "required|string",
            "description" => "nullable|string",
            "is_fixed_price" => "nullable|numeric",
            "service_time_in_minute" => "required|numeric",
            "default_price" => "nullable|numeric"

            // "automobile_category_id" => "required|numeric"
        ];
    }
}
