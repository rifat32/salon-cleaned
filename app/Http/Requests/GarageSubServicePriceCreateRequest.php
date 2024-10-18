<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GarageSubServicePriceCreateRequest extends FormRequest
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
       "garage_id" => "required|numeric",
       "garage_sub_service_id" => "required|numeric",
       "garage_sub_service_prices" => "required|array",
       "garage_sub_service_prices.*.automobile_make_id" => "nullable|numeric",
       "garage_sub_service_prices.*.price" => "required|numeric",
       'expert_id' => [
        'required',
        'numeric',
                 'exists:users,id'

    ],

        ];
    }
}
