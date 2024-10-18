<?php

namespace App\Http\Requests;

use App\Rules\DayValidation;
use App\Rules\TimeOrderRule;
use App\Rules\TimeValidation;
use Illuminate\Foundation\Http\FormRequest;

class FuelStationUpdateRequest extends FormRequest
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
            'id' => 'required|numeric',
            "name" => "required|string",
            "address" => "required|string",
            "opening_time"=>['required','date_format:H:i', new TimeValidation
        ],
            "closing_time" => ['required','date_format:H:i', new TimeValidation
        ],
            "description" => "nullable|string",

            'lat' => 'required|string',
            'long' => 'required|string',

            "options" => "nullable|array",
            "options.*.option_id" => "required_if:options,!=,null|numeric",
            "options.*.is_active" => "required_if:options,!=,null|boolean",


            'country' => 'required|string',
            'city' => 'required|string',
            'postcode' => 'required|string',
            'address_line_1' => 'required|string',
            'address_line_2' => 'nullable|string',
            'additional_information' => 'nullable|string',


            'times' => 'required|array|min:1',
            "times.*.day" => ["numeric",new DayValidation],
            "times.*.opening_time" => ['required','date_format:H:i', new TimeValidation, new TimeOrderRule
           ],
            "times.*.closing_time" => ['required','date_format:H:i', new TimeValidation, new TimeOrderRule
           ],
           "times.*.is_closed" => ['required',"boolean"],

        ];

    }
}
