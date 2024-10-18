<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AutomobileFuelTypeUpdateRequest extends FormRequest
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
            "automobile_model_variant_id" => "required|numeric"
        ];
    }
}
