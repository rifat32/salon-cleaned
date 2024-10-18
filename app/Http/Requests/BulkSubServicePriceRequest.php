<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkSubServicePriceRequest extends FormRequest
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
            'sub_service_prices' => 'required|array',
            'sub_service_prices.*.sub_service_id' => 'required|numeric',
            'sub_service_prices.*.price' => 'required|numeric',
            'sub_service_prices.*.expert_id' => 'required|numeric',
            'sub_service_prices.*.description' => 'nullable|string',
        ];
    }
}
