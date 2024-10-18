<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GarageTimeFormatUpdateRequest extends FormRequest
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
            "garage_id"=> "required|numeric|exists:garages,id",
            "time_format"=>"required|string|in:12-hour,24-hour",
        ];


    }
    public function messages()
{
    return [

        'time_format.required' => 'The time format is required.',
        'time_format.string' => 'The time format must be a string.',
        'time_format.in' => 'The time format must be either "12-hour" or "24-hour".', // Customized error message
    ];
}
}
