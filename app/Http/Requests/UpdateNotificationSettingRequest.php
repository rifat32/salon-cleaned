<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingRequest extends FormRequest
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
            'notify_expert' => 'required|boolean',
            'notify_customer' => 'required|boolean',
            'notify_receptionist' => 'required|boolean',
            'notify_business_owner' => 'required|boolean',
        ];
    }
}
