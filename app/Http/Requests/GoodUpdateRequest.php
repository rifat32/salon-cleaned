<?php




namespace App\Http\Requests;

use App\Models\Good;
use App\Rules\ValidateGoodSku;
use App\Rules\ValidateGoodBarcode;
use Illuminate\Foundation\Http\FormRequest;

class GoodUpdateRequest extends FormRequest
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

                    $good_query_params = [
                        "id" => $this->id,
                    ];
                    $good = Good::where($good_query_params)
                        ->first();
                    if (!$good) {
                        // $fail($attribute . " is invalid.");
                        $fail("no good found");
                        return 0;
                    }

                    if ($good->business_id != auth()->user()->business_id) {
                        // $fail($attribute . " is invalid.");
                        $fail("You do not have permission to update this good due to role restrictions.");
                    }
                },
            ],



            'name' => [
                'required',
                'string',

            ],

            'sku' => [
                'required',
                'string',
                new ValidateGoodSku($this->id)

            ],

            'product_category_id' => [
                'required',
                'numeric',
                'exists:product_categories,id'
            ],

            'preferred_supplier_id' => [
                'required',
                'numeric',
                'exists:suppliers,id'
            ],

            'cost_price' => [
                'required',
                'numeric',



            ],

            'retail_price' => [
                'required',
                'numeric',







            ],

            'barcode' => [
                'required',
                'string',



                new ValidateGoodBarcode(NULL)




            ],

            'current_stock' => [
                'required',
                'integer',







            ],

            'min_stock_level' => [
                'required',
                'integer',







            ],







        ];



        return $rules;
    }
}
