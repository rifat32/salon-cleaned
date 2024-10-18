<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingStatusChangeRequest extends FormRequest
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
            "status" => "required|string|in:pending,rejected_by_garage_owner,check_in,arrived,converted_to_job",
            "reason" => "nullable|string",
        ];
    }

    public function messages()
    {

        return [
       "status.in" => 'The :attribute field must be one of pending,rejected_by_garage_owner,check_in,arrived,converted_to_job',
        ];
    }
}
