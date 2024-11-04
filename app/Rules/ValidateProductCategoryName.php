<?php



namespace App\Rules;

use App\Models\ProductCategory;
use Illuminate\Contracts\Validation\Rule;

class ValidateProductCategoryName implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return  void
     */

     protected $id;
    protected $errMessage;

    public function __construct($id)
    {
        $this->id = $id;
        $this->errMessage = "";

    }


    /**
     * Determine if the validation rule passes.
     *
     * @param    string  $attribute
     * @param    mixed  $value
     * @return  bool
     */
    public function passes($attribute, $value)
    {
        $created_by  = NULL;
        if(auth()->user()->business) {
            $created_by = auth()->user()->business->created_by;
        }

        $data = ProductCategory::where("product_categories.name",$value)
        ->when(!empty($this->id),function($query) {
            $query->whereNotIn("id",[$this->id]);
        })
                            ->where('product_categories.business_id', auth()->user()->business_id)


        ->first();

        if(!empty($data)){


            if ($data->is_active) {
                $this->errMessage = "A product category with the same name already exists.";
            } else {
                $this->errMessage = "A product category with the same name exists but is deactivated. Please activate it to use.";
            }


            return 0;

        }
     return 1;
    }

    /**
     * Get the validation error message.
     *
     * @return  string
     */
    public function message()
    {
        return $this->errMessage;
    }

}

