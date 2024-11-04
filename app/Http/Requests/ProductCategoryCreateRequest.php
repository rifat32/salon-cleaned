<?php


namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidateProductCategoryName;

class ProductCategoryCreateRequest extends FormRequest
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


                new ValidateProductCategoryName(NULL)




    ],

        'description' => [
        'required',
        'string',






    ],


];



return $rules;
}
}


