<?php




namespace App\Http\Requests;

use App\Models\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class SupplierUpdateRequest extends FormRequest
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

      $supplier_query_params = [
          "id" => $this->id,
      ];
      $supplier = Supplier::where($supplier_query_params)
          ->first();
      if (!$supplier) {
          // $fail($attribute . " is invalid.");
          $fail("no supplier found");
          return 0;
      }
      if (empty(auth()->user()->business_id)) {

          if (auth()->user()->hasRole('superadmin')) {
              if (($supplier->business_id != NULL )) {
                  // $fail($attribute . " is invalid.");
                  $fail("You do not have permission to update this supplier due to role restrictions.");
              }
          } else {
              if (($supplier->business_id != NULL || $supplier->is_default != 0 || $supplier->created_by != auth()->user()->id)) {
                  // $fail($attribute . " is invalid.");
                  $fail("You do not have permission to update this supplier due to role restrictions.");
              }
          }
      } else {
          if (($supplier->business_id != auth()->user()->business_id || $supplier->is_default != 0)) {
              // $fail($attribute . " is invalid.");
              $fail("You do not have permission to update this supplier due to role restrictions.");
          }
      }
  },
],



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



