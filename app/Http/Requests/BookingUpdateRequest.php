<?php

namespace App\Http\Requests;

use App\Rules\TimeValidation;
use App\Rules\ValidateExpert;
use Illuminate\Foundation\Http\FormRequest;

class BookingUpdateRequest extends FormRequest
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

            "next_visit_date" => "nullable|date",
            "send_notification" => "nullable|boolean",

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
"reason" => "nullable|string",



            // "customer_id",
            "garage_id" => "required|numeric",

            // "coupon_code" => "nullable|string",
            "discount_type" => "nullable|string|in:fixed,percentage",
            "discount_amount" => "required_if:discount_type,!=,null|numeric|min:0",

            "total_price" => "nullable|numeric",
            "additional_information" => "nullable|string",

            "status" => "required|string|in:pending,rejected_by_garage_owner,check_in,arrived,converted_to_job",
            "job_start_date" => "required_if:status,confirmed|date",

        'booking_sub_service_ids' => 'nullable|array',
        'booking_sub_service_ids.*' => 'nullable|numeric',

        'booking_garage_package_ids' => 'nullable|array',
        'booking_garage_package_ids.*' => 'nullable|numeric',

        "payments" => "present|array",
        "payments.*.payment_type" => "required|string",
        "payments.*.amount" => "required|numeric",
"tip_type" => "nullable|string|in:fixed,percentage",
"tip_amount" => "required_if:tip_type,!=,null|numeric|min:0",
        ];
    }

    public function messages()
    {

        return [
       "status.in" => 'The :attribute field must be one of  pending,confirmed,rejected_by_garage_owner',

        ];
    }
}
