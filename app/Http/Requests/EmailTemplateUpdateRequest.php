<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailTemplateUpdateRequest extends FormRequest
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
            "type" => "required|string|in:booking_update,booking_status_update",
        "template" => "required|string",
        "is_active" => "required|boolean",
        "wrapper_id" => "nullable|numeric",
        ];
    }


public function messages()
{
    return [
        "type.required" => "The type field is required.",
        "type.in" => "The selected type is invalid. Allowed values are 'booking_update' and 'booking_status_update'.",
        "template.required" => "The template field is required.",
        "is_active.required" => "The is_active field is required.",
        "is_active.boolean" => "The is_active field must be true or false.",
        "wrapper_id.numeric" => "The wrapper_id field must be a number.",
    ];
}
}
