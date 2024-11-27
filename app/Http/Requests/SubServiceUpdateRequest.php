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
            "number_of_slots" => "required|numeric",
            "default_price" => "required|numeric",
            "discounted_price" => "required|numeric",

            // "automobile_category_id" => "required|numeric"
        ];
    }
}
