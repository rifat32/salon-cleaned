<?php


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class SaleCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return  bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return  array
     */
    public function rules()
    {

        $rules = [
            'date' => [
                'required',
                'date',
            ],
            'total_amount' => [
                'required',
                'numeric'
            ],
            'customer_id' => [
                'required',
                'numeric',
                'exists:users,id'
            ],
        'sale_items' => ['required', 'array'],
        'sale_items.*.good_id' => ['required', 'integer', 'exists:goods,id'],
        'sale_items.*.quantity' => ['required', 'integer', 'min:1'],
        'sale_items.*.price_per_unit' => ['required', 'numeric', 'min:0'],
        ];


        return $rules;
    }
}
