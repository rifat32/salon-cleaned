<?php




namespace App\Http\Requests;

use App\Models\SubServicePrice;
use App\Rules\ValidateExpert;
use App\Rules\ValidateSubService;
use App\Rules\ValidateSubServicePriceName;
use Illuminate\Foundation\Http\FormRequest;

class SubServicePriceUpdateRequest extends FormRequest
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

                    $sub_service_price_query_params = [
                        "id" => $value,
                        "business_id" => auth()->user()->business_id
                    ];
                    $sub_service_price = SubServicePrice::where($sub_service_price_query_params)
                        ->first();
                    if (!$sub_service_price) {
                        // $fail($attribute . " is invalid.");
                        $fail("no sub service price found");
                        return 0;
                    }
                    if (empty(auth()->user()->business_id)) {

                        if (auth()->user()->hasRole('superadmin')) {
                            if (($sub_service_price->business_id != NULL)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this sub service price due to role restrictions.");
                            }
                        } else {
                            if (($sub_service_price->business_id != NULL || $sub_service_price->is_default != 0 || $sub_service_price->created_by != auth()->user()->id)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this sub service price due to role restrictions.");
                            }
                        }
                    } else {
                        if (($sub_service_price->business_id != auth()->user()->business_id || $sub_service_price->is_default != 0)) {
                            // $fail($attribute . " is invalid.");
                            $fail("You do not have permission to update this sub service price due to role restrictions.");
                        }
                    }
                },
            ],



            'sub_service_id' => [
                'required',
                'numeric',





                new ValidateSubService(NULL)


            ],

            'price' => [
                'required',
                'numeric',
            ],

            'expert_id' => [
                'required',
                'numeric',

                new ValidateExpert(NULL)


            ],

            'description' => [
                'nullable',
                'string',







            ],







        ];



        return $rules;
    }




}
