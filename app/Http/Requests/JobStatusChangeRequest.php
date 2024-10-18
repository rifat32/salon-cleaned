<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobStatusChangeRequest extends FormRequest
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
            "status" => "required|string|in:pending,active,completed,cancelled",
        ];
    }

    public function messages()
    {

        return [
       "status.in" => 'The :attribute field must be of pending,active,completed,cancelled',

        ];
    }

}
