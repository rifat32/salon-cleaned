<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkSubServicePriceUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust authorization logic if needed
    }

    public function rules()
    {
        return [
            'expert_id' => 'required|numeric',
            'sub_service_prices' => 'required|array',
            'sub_service_prices.*.sub_service_id' => 'required|numeric',
            'sub_service_prices.*.price' => 'required|numeric',
            'sub_service_prices.*.description' => 'nullable|string',
        ];
    }
}
