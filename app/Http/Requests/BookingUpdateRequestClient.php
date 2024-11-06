<?php

namespace App\Http\Requests;

use App\Rules\ValidateExpert;
use Illuminate\Foundation\Http\FormRequest;

class BookingUpdateRequestClient extends FormRequest
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

'booking_from' => [
    'nullable',
    'string',
],

"reason" => "nullable|string",
            "garage_id" => "required|numeric",
             "additional_information" => "nullable|string",
            // "status",
            "coupon_code" => "nullable|string",
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
}
