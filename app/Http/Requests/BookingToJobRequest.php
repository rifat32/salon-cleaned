<?php

namespace App\Http\Requests;

use App\Rules\TimeValidation;
use Illuminate\Foundation\Http\FormRequest;

class BookingToJobRequest extends FormRequest
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
            "booking_id" => "required|numeric",
            "garage_id" => "required|numeric",

            "discount_type" => "nullable|string|in:fixed,percentage",

            "discount_amount" => "required_if:discount_type,!=,null|numeric|min:0",



            "coupon_code" => "nullable|string",

        "status" => "required|string|in:pending,active",
        "fuel" => "nullable|string",
        "transmission" => "nullable|string",
        ];
    }

    public function messages()
    {

        return [
       "status.in" => 'The :attribute field must be of pending,active',

        ];
    }
}
