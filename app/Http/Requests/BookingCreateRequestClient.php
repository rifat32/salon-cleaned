<?php

namespace App\Http\Requests;

use App\Rules\TimeValidation;
use App\Rules\ValidateExpert;
use Illuminate\Foundation\Http\FormRequest;

class BookingCreateRequestClient extends FormRequest
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
            'expert_id' => [
                'required',
                'numeric',
                 new ValidateExpert(NULL)
            ],
   'booked_slots' => [
    'required',
    'array',
],
'booked_slots.*' => [
    'required',
    'date_format:g:i A',
],


            "garage_id" => "required|numeric",
            "additional_information" => "nullable|string",
            "reason" => "nullable|string",

            // "status",
            "job_start_date" => "required|date_format:Y-m-d",
            "job_start_time" => ['nullable','date_format:H:i', new TimeValidation
        ],

            // "job_end_date" => "required|date",
            "coupon_code" => "nullable|string",
            "payment_method" => "nullable|string",


    'booking_sub_service_ids' => 'nullable|array',
    'booking_sub_service_ids.*' => 'nullable|numeric',

    'booking_garage_package_ids' => 'nullable|array',
    'booking_garage_package_ids.*' => 'nullable|numeric',

"tip_type" => "nullable|string|in:fixed,percentage",
"tip_amount" => "required_if:tip_type,!=,null|numeric|min:0",


        ];
    }
}
