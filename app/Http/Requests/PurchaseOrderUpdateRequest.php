<?php




namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderUpdateRequest extends FormRequest
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

            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {

                    $purchase_order_query_params = [
                        "id" => $this->id,
                    ];
                    $purchase_order = PurchaseOrder::where($purchase_order_query_params)
                        ->first();
                    if (!$purchase_order) {
                        // $fail($attribute . " is invalid.");
                        $fail("no purchase order found");
                        return 0;
                    }



                    if ($purchase_order->business_id != auth()->user()->business_id) {
                        // $fail($attribute . " is invalid.");
                        $fail("You do not have permission to update this purchase order due to role restrictions.");
                    }
                },
            ],


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

              // Validation for purchase order items
        'items' => [
            'sometimes', // Optional: Validate only if provided
            'array',
        ],

        'items.*.product_id' => [
            'required',
            'numeric',
            'exists:products,id'
        ],

        'items.*.quantity' => [
            'required',
            'numeric',
            'min:1'
        ],

        'items.*.price' => [
            'required',
            'numeric',
            'min:0'
        ],







        ];



        return $rules;
    }
}
