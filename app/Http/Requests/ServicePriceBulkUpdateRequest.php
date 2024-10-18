<?php

namespace App\Http\Requests;

use App\Models\ServicePrice;
use Illuminate\Foundation\Http\FormRequest;

class ServicePriceBulkUpdateRequest extends FormRequest
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
            'services' => ['required', 'array'],
            'services.*.id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $service_price = ServicePrice::where([
                        "business_id" => auth()->user()->business_id,
                        "id" => $value
                    ])->first();

                    if (!$service_price) {
                        $fail("Service price with ID $value not found.");
                    }
                }
            ],
            'services.*.service_id' => ['required', 'numeric', 'exists:services,id'],
            'services.*.price' => ['required', 'numeric'],
            'services.*.expert_id' => ['required', 'numeric', 'exists:users,id'],
            'services.*.business_id' => ['nullable', 'numeric', 'exists:businesses,id'],
        ];
    }
}
