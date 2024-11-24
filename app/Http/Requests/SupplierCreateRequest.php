<?php


namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;


class SupplierCreateRequest extends FormRequest
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
                'string',

            ],

            'contact_info' => [
                'nullable',
                'string',






            ],

            'address' => [
                'nullable',
                'string',






            ],

            'payment_terms' => [
                'nullable',
                'string',






            ],


        ];



        return $rules;
    }
}
