<?php

namespace App\Http\Requests;

use App\Rules\TimeValidation;
use Illuminate\Foundation\Http\FormRequest;

class JobBidCreateRequest extends FormRequest
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
            "garage_id" => "required|numeric",
            "pre_booking_id" => "required|numeric",
            "price" => "required|numeric",
            "offer_template"=> "required|string",
            "job_start_date" => "required|date_format:Y-m-d",
            "job_start_time" => ['required','date_format:H:i', new TimeValidation
        ],
            "job_end_time" => ['required','date_format:H:i', new TimeValidation
        ],
        ];
    }
}
