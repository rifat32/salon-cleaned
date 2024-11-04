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
        'stripe_enabled' => 'boolean',
        'STRIPE_KEY' => 'string|nullable|required_if:stripe_enabled,true',
        'STRIPE_SECRET' => 'string|nullable|required_if:stripe_enabled,true',
        'is_expert_price' => 'boolean',
        'is_auto_booking_approve' => 'boolean',

        'allow_pay_after_service' => 'boolean',
        'allow_expert_booking' => 'boolean',
        'allow_expert_self_busy' => 'boolean',
        'allow_expert_booking_cancel' => 'boolean',
        'allow_expert_view_revenue' => 'boolean',
        'allow_expert_view_customer_details' => 'boolean',
        'allow_receptionist_add_question' => 'boolean',
        'default_currency' => 'string|nullable|max:10',
        'default_language' => 'string|nullable|max:10',
        'vat_enabled' => 'boolean',
        'vat_percentage' => 'nullable|numeric|required_if:vat_enabled,true|min:0|max:100'
        'vat_number' => 'nullable|string|required_if:vat_enabled,true'

        ];

    }


}
