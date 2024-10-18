<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServicePriceMultipleCreateRequest extends FormRequest
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
            'service_prices' => 'required|array|min:1',
            'service_prices.*.service_id' => 'required|numeric|exists:services,id',
            'service_prices.*.price' => 'required|numeric',
            'service_prices.*.expert_id' => 'required|numeric|exists:users,id',
            'service_prices.*.business_id' => 'nullable|numeric|exists:businesses,id',
        ];
    }
}
