<?php



namespace App\Http\Requests;

use App\Models\ProductCategory;
use App\Rules\ValidateProductCategoryName;
use Illuminate\Foundation\Http\FormRequest;

class ProductCategoryUpdateRequest extends BaseFormRequest
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

      $product_category_query_params = [
          "id" => $this->id,
      ];
      $product_category = ProductCategory::where($product_category_query_params)
          ->first();
      if (!$product_category) {
          // $fail($attribute . " is invalid.");
          $fail("no product category found");
          return 0;
      }
      if (empty(auth()->user()->business_id)) {

          if (auth()->user()->hasRole('superadmin')) {
              if (($product_category->business_id != NULL )) {
                  // $fail($attribute . " is invalid.");
                  $fail("You do not have permission to update this product category due to role restrictions.");
              }
          } else {
              if (($product_category->business_id != NULL || $product_category->is_default != 0 || $product_category->created_by != auth()->user()->id)) {
                  // $fail($attribute . " is invalid.");
                  $fail("You do not have permission to update this product category due to role restrictions.");
              }
          }
      } else {
          if (($product_category->business_id != auth()->user()->business_id || $product_category->is_default != 0)) {
              // $fail($attribute . " is invalid.");
              $fail("You do not have permission to update this product category due to role restrictions.");
          }
      }
  },
],



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



