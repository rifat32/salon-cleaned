<?php

namespace App\Http\Requests;

use App\Rules\DayValidation;
use App\Rules\SomeTimes;
use App\Rules\TimeOrderRule;
use App\Rules\TimeValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class GarageCreateRequest extends FormRequest
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

            'garage.owner_id' => 'required|numeric',



            'garage.name' => 'required|string|max:255',
            'garage.about' => 'nullable|string',
            'garage.web_page' => 'nullable|string',
            'garage.phone' => 'nullable|string',
            // 'garage.email' => 'required|string|email|indisposable|max:255|unique:garages,email',
            'garage.email' => 'nullable|string|email|max:255|unique:garages,email',
            'garage.additional_information' => 'nullable|string',

            'garage.lat' => 'nullable|string',
            'garage.long' => 'nullable|string',
            'garage.country' => 'nullable|string',
            'garage.city' => 'nullable|string',

            'garage.currency' => 'nullable|string',

            'garage.postcode' => 'nullable|string',
            'garage.address_line_1' => 'nullable|string',
            'garage.address_line_2' => 'nullable|string',


            'garage.logo' => 'nullable|string',

            'garage.image' => 'nullable|string',

            'garage.images' => 'nullable|array',
            'garage.images.*' => 'nullable|string',

            'garage.is_mobile_garage' => 'required|boolean',
            'garage.wifi_available' => 'required|boolean',
            'garage.labour_rate' => 'nullable|numeric',
            "garage.time_format"=>"required|string|in:12-hour,24-hour",

            'times' => 'required|array|min:1',
            "times.*.day" => ["numeric",new DayValidation],
            "times.*.opening_time" => ['required','date_format:H:i', new TimeValidation, new TimeOrderRule
        ],
         "times.*.closing_time" => ['required','date_format:H:i', new TimeValidation, new TimeOrderRule
        ],
           "times.*.is_closed" => ['required',"boolean"],



            'service' => "array|required",
            'service.*.automobile_category_id' => "required|numeric",

            'service.*.services' => ["required","array",new SomeTimes],
            'service.*.services.*.id' => "required|numeric",
            'service.*.services.*.checked' => "required|boolean",

            'service.*.automobile_makes' => ["required","array",new SomeTimes],
            'service.*.automobile_makes.*.id' => "required|numeric",
            'service.*.automobile_makes.*.checked' => ["required","boolean"],
            // 'service.automobile_categories' => "array|required",


        ];


    }

    public function messages()
    {
        return [
            'garage.owner_id.required' => 'The owner ID field is required.',
            'garage.owner_id.numeric' => 'The owner ID must be a numeric value.',

            'garage.name.required' => 'The name field is required.',
            'garage.name.string' => 'The name field must be a string.',
            'garage.name.max' => 'The name field may not be greater than :max characters.',
            'garage.about.string' => 'The about field must be a string.',
            'garage.web_page.string' => 'The web page field must be a string.',
            'garage.phone.string' => 'The phone field must be a string.',
            // 'garage.email.required' => 'The email field is required.',
            'garage.email.string' => 'The email field must be a string.',
            'garage.email.email' => 'The email field must be a valid email address.',
            'garage.email.max' => 'The email field may not be greater than :max characters.',
            'garage.email.unique' => 'The email has already been taken.',
            'garage.additional_information.string' => 'The additional information field must be a string.',
            'garage.lat.required' => 'The latitude field is required.',
            'garage.lat.string' => 'The latitude field must be a string.',
            'garage.long.required' => 'The longitude field is required.',
            'garage.long.string' => 'The longitude field must be a string.',
            'garage.country.required' => 'The country field is required.',
            'garage.country.string' => 'The country field must be a string.',
            'garage.city.required' => 'The city field is required.',
            'garage.city.string' => 'The city field must be a string.',
            'garage.currency.string' => 'The currency field must be a string.',
            'garage.currency.string' => 'The currency field must be a string.',
            'garage.postcode.required' => 'The postcode field is required.',
            'garage.postcode.string' => 'The postcode field must be a string.',
            'garage.address_line_1.required' => 'The address line 1 field is required.',
            'garage.address_line_1.string' => 'The address line 1 field must be a string.',
            'garage.address_line_2.string' => 'The address line 2 field must be a string.',
            'garage.logo.string' => 'The logo field must be a string.',
            'garage.image.string' => 'The image field must be a string.',
            'garage.images.array' => 'The images field must be an array.',
            'garage.images.*.string' => 'Each image in the images field must be a string.',
            'garage.is_mobile_garage.required' => 'The is mobile garage field is required.',
            'garage.is_mobile_garage.boolean' => 'The is mobile garage field must be a boolean.',
            'garage.wifi_available.required' => 'The wifi available field is required.',
            'garage.wifi_available.boolean' => 'The wifi available field must be a boolean.',
            'garage.labour_rate.numeric' => 'The labour rate field must be numeric.',


            'garage.time_format.required' => 'The time format is required.',
            'garage.time_format.string' => 'The time format must be a string.',
            'garage.time_format.in' => 'The time format must be either "12-hour" or "24-hour".',

            'times.required' => 'The times field is required.',
            'times.array' => 'The times field must be an array.',
            'times.min' => 'There must be at least :min times defined.',
            'times.*.day.numeric' => 'Each day in the times field must be numeric.',
            'times.*.opening_time.required' => 'The opening time field is required.',
            'times.*.opening_time.date_format' => 'The opening time field must be in the format H:i.',
            'times.*.closing_time.required' => 'The closing time field is required.',
            'times.*.closing_time.date_format' => 'The closing time field must be in the format H:i.',
            'times.*.is_closed.required' => 'The is closed field is required.',
            'times.*.is_closed.boolean' => 'The is closed field must be a boolean.',

            'service.array' => 'The service field must be an array.',
            'service.required' => 'The service field is required.',
            'service.*.automobile_category_id.required' => 'The automobile category ID field is required.',
            'service.*.automobile_category_id.numeric' => 'The automobile category ID must be a numeric value.',
            'service.*.services.required' => 'The services field is required.',
            'service.*.services.array' => 'The services field must be an array.',
            'service.*.services.*.id.required' => 'The service ID field is required.',
            'service.*.services.*.id.numeric' => 'The service ID must be a numeric value.',
            'service.*.services.*.checked.required' => 'The checked field for services is required.',
            'service.*.services.*.checked.boolean' => 'The checked field for services must be a boolean.',
            'service.*.automobile_makes.required' => 'The automobile makes field is required.',
            'service.*.automobile_makes.array' => 'The automobile makes field must be an array.',
            'service.*.automobile_makes.*.id.required' => 'The automobile make ID field is required.',
            'service.*.automobile_makes.*.id.numeric' => 'The automobile make ID must be a numeric value.',
            'service.*.automobile_makes.*.checked.required' => 'The checked field for automobile makes is required.',
            'service.*.automobile_makes.*.checked.boolean' => 'The checked field for automobile makes must be a boolean.',
        ];
    }



}
