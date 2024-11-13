<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingRequest extends FormRequest
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
            "allow_receptionist_user_discount" => "required|boolean",
            "discount_percentage_limit" => "required|numeric",

        'slot_duration' => 'required|integer',

        'stripe_enabled' => 'required|boolean',
        'STRIPE_KEY' => 'string|nullable|required_if:stripe_enabled,true',
        'STRIPE_SECRET' => 'string|nullable|required_if:stripe_enabled,true',
        'is_expert_price' => 'required|boolean',
        'is_auto_booking_approve' => 'boolean',



        'allow_pay_after_service' => 'required|boolean',
        'allow_expert_booking' => 'required|boolean',
        'allow_expert_self_busy' => 'required|boolean',
        'allow_expert_booking_cancel' => 'required|boolean',
        'allow_expert_take_payment' => 'required|boolean',

        'allow_expert_view_revenue' => 'required|boolean',
        'allow_expert_view_customer_details' => 'required|boolean',
        'allow_receptionist_add_question' => 'required|boolean',
        'default_currency' => 'required|string|max:10',
        'default_language' => 'required|string|max:10',
        'vat_enabled' => 'required|boolean',
        'vat_percentage' => 'nullable|numeric|required_if:vat_enabled,true|min:0|max:100',
        'vat_number' => 'nullable|string|required_if:vat_enabled,true'

        ];

    }


}
