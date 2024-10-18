<?php

namespace App\Http\Requests;

use App\Rules\TimeValidation;
use Illuminate\Foundation\Http\FormRequest;

class JobUpdateRequest extends FormRequest
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
            "id" => "required|numeric",
            // "customer_id",
            "garage_id" => "required|numeric",

            "additional_information" => "nullable|string",



            'job_sub_service_ids' => 'nullable|array',
            'job_sub_service_ids.*' => 'nullable|numeric',

            'job_garage_package_ids' => 'nullable|array',
            'job_garage_package_ids.*' => 'nullable|numeric',


            "coupon_code" => "nullable|string",

             "discount_type" => "nullable|string|in:fixed,percentage",
             "discount_amount" => "required_if:discount_type,!=,null|numeric|min:0",
            

             "job_start_date" => "required|date",

         "status" => "required|string|in:pending,active,completed,cancelled",


        ];
    }


    public function messages()
    {

        return [
       "status.in" => 'The :attribute field must be of pending,active,completed,cancelled',

        ];
    }
}
