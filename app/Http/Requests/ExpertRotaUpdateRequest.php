<?php




namespace App\Http\Requests;

use App\Models\ExpertRota;
use App\Rules\ValidateExpert;
use App\Rules\ValidateExpertRotaName;
use Illuminate\Foundation\Http\FormRequest;

class ExpertRotaUpdateRequest extends FormRequest
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

      $expert_rota_query_params = [
          "id" => $value,
      ];
      $expert_rota = ExpertRota::
      whereHas("user", function($query) {
            $query->where("users.business_id",auth()->user()->business_id);
      })
      ->where($expert_rota_query_params)
          ->first();
      if (!$expert_rota) {
          // $fail($attribute . " is invalid.");
          $fail("no expert rota found");
          return 0;
      }

  },
],



    'expert_id' => [
    'required',
    'numeric',
    new ValidateExpert(NULL)
],

    'date' => [
    'required',
    'date',
],

'busy_slots' => [
    'present',
    'array',
],
'busy_slots.*' => [
    'date_format:g:i A',
],


];



return $rules;
}
}



