<?php



namespace App\Http\Requests;

use App\Rules\ValidateExpert;
use App\Rules\ValidateSubService;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidateSubServicePriceName;

class SubServicePriceCreateRequest extends FormRequest
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


