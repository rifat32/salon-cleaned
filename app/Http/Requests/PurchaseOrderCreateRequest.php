<?php



namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;



class PurchaseOrderCreateRequest extends FormRequest
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
            'supplier_id' => [
                'required',
                'numeric',
                "exists:suppliers,id"

            ],

            'order_date' => [
                'required',
                'date',

            ],

            'status' => [
                'required',
                'string',


            ],

            'total_amount' => [
                'required',
                'numeric',

            ],

            'received_date' => [
                'required',
                'date',

            ],
            'purchase_items' => [
                'present',
                'array',
            ],
            'purchase_items.*.good_id' => [
                'required',
                'exists:goods,id',
            ],
            'purchase_items.*.quantity' => [
                'required',
                'numeric',
                'min:1',
            ],
            'purchase_items.*.cost_per_unit' => [
                'required',
                'numeric',
                'min:0',
            ],

        ];



        return $rules;
    }

}
