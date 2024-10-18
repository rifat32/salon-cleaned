<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "garage_id"=>"required|numeric",
            "name"=>"required|string",
             'code' => 'required|string',
             "discount_type"=>"required|string|in:fixed,percentage",
            "discount_amount"=>"required|numeric",
            "min_total"=>"nullable|numeric",
            "max_total"=>"nullable|numeric",
            "redemptions"=>"nullable|numeric",

            "coupon_start_date"=>"required|date",
            "coupon_end_date"=>"required|date",
            "is_auto_apply"=>"required|boolean",
            "is_active"=>"required|boolean",
        ];
    }
}
