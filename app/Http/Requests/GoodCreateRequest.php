<?php



namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;


use App\Rules\ValidateGoodSku;

use App\Rules\ValidateGoodBarcode;


class GoodCreateRequest extends FormRequest
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

            'name' => [
                'required',
                'string'
            ],

            'sku' => [
                'required',
                'string',
                new ValidateGoodSku(NULL)

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
