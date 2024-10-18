<?php

namespace App\Http\Requests;

use App\Rules\TimeValidation;
use Illuminate\Foundation\Http\FormRequest;

class GarageRulesUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {


        return [
            "garage_id"=> "required|numeric",
            "standard_lead_time"=>"required|integer",

            "booking_accept_start_time"=>  ['required','date_format:H:i', new TimeValidation
        ],

            "booking_accept_end_time"=> ['required','date_format:H:i', new TimeValidation
        ],

            "block_out_days"=>"required|array",
            "block_out_days.*"=>['required','date_format:H:i', new TimeValidation
            ]
        ];
    }
}
