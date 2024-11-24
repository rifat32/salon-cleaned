<?php




namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class SaleUpdateRequest extends FormRequest
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

                    $sale_query_params = [
                        "id" => $this->id,
                    ];
                    $sale = Sale::where($sale_query_params)
                        ->first();
                    if (!$sale) {
                        // $fail($attribute . " is invalid.");
                        $fail("no sale found");
                        return 0;
                    }


                    if ($sale->business_id != auth()->user()->business_id) {
                        // $fail($attribute . " is invalid.");
                        $fail("You do not have permission to update this sale due to role restrictions.");
                    }
                },
            ],


            'date' => [
                'required',
                'date',
            ],

            'total_amount' => [
                'required',
                'numeric',
            ],

            'customer_id' => [
                'required',
                'numeric',
                'exists:users,id'
            ],
            'sale_items' => [
                'required',
                'array',
                'min:1',
            ],
            'sale_items.*.good_id' => [
                'required',
                'numeric',
                'exists:goods,id',
            ],
            'sale_items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
            'sale_items.*.price_per_unit' => [
                'required',
                'numeric',
            ],
            'sale_items.*.total_price' => [
                'required',
                'numeric',
            ],
        ];



        return $rules;
    }
}
