<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GarageAffiliationUpdateRequest extends FormRequest
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
            "garage_id" => "required|numeric",
            "affiliation_id" => "required|numeric",

            "start_date" => "required|date",
            "end_date" => "nullable|date",
        ];
    }
}
