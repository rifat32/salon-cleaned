<?php




namespace App\Http\Requests;

use App\Models\ServicePrice;
use App\Rules\ValidateServicePriceName;
use Illuminate\Foundation\Http\FormRequest;

class ServicePriceUpdateRequest extends FormRequest
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

                    $service_price_query_params = [
                        "business_id" => auth()->user()->business_id,
                        "id" => $value
                    ];
                    $service_price = ServicePrice::where($service_price_query_params)
                        ->first();
                    if (!$service_price) {
                        // $fail($attribute . " is invalid.");
                        $fail("no service price found");
                        return 0;
                    }

                },
            ],





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
