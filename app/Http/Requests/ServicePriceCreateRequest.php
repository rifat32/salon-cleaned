<?php



namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidateServicePriceName;

class ServicePriceCreateRequest extends FormRequest
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

            'service_id' => [
                'required',
                'numeric',
                'exists:services,id'

            ],

            'price' => [
                'required',
                'numeric',

            ],

            'expert_id' => [
                'required',
                'numeric',
                         'exists:users,id'


            ],




        ];



        return $rules;
    }

}
