<?php

namespace App\Http\Requests;

use App\Rules\DayValidation;
use App\Rules\SomeTimes;
use App\Rules\TimeOrderRule;
use App\Rules\TimeValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class AuthRegisterGarageRequest extends FormRequest
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
            'user.first_Name' => 'required|string|max:255',
            'user.last_Name' => 'required|string|max:255',
            // 'user.email' => 'required|string|email|indisposable|max:255|unique:users,email',
            'user.email' => 'required|string|email|max:255|unique:users,email',
            'user.password' => 'nullable|confirmed|string|min:6',
            'user.send_password' => 'required|boolean',

            'user.phone' => 'nullable|string',
            'user.image' => 'nullable|string',

            // 'user.address_line_1' => 'nullable|string',
            // 'user.address_line_2' => 'nullable|string',
            // 'user.country' => 'nullable|string',
            // 'user.city' => 'nullable|string',
            // 'user.postcode' => 'nullable|string',

            // 'user.lat' => 'nullable|string',
            // 'user.long' => 'nullable|string',

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
            'user.first_Name.required' => 'The first name field is required.',
            'user.last_Name.required' => 'The last name field is required.',
            'user.email.required' => 'The email field is required.',
            'user.email.email' => 'The email must be a valid email address.',
            'user.email.unique' => 'The email has already been taken.',
            'user.password.min' => 'The password must be at least :min characters.',
            'user.send_password.required' => 'The send password field is required.',
            // 'user.phone.required' => 'The phone field is required.',
            'user.image.string' => 'The image must be a string.',
            // Add custom messages for other fields as needed

            'garage.name.required' => 'The name field is required.',
            'garage.about.string' => 'The about must be a string.',
            'garage.web_page.string' => 'The web page must be a string.',
            'garage.phone.string' => 'The phone must be a string.',
            // 'garage.email.required' => 'The email field is required.',
            'garage.email.email' => 'The email must be a valid email address.',

            'garage.email.unique' => 'The email has already been taken.',
            'garage.lat.required' => 'The latitude field is required.',
            'garage.long.required' => 'The longitude field is required.',
            'garage.country.required' => 'The country field is required.',
            'garage.city.required' => 'The city field is required.',
            'garage.currency.required' => 'The currency field is required.',
            'garage.currency.string' => 'The currency must be a string.',
            'garage.postcode.required' => 'The postcode field is required.',
            'garage.address_line_1.required' => 'The address line 1 field is required.',
            'garage.address_line_2.string' => 'The address line 2 must be a string.',
            'garage.logo.string' => 'The logo must be a string.',
            'garage.image.string' => 'The image must be a string.',
            'garage.images.array' => 'The images must be an array.',
            'garage.images.*.string' => 'The image must be a string.',
            'garage.is_mobile_garage.required' => 'The is mobile garage field is required.',
            'garage.wifi_available.required' => 'The wifi available field is required.',
            'garage.labour_rate.numeric' => 'The labour rate must be a number.',


            'garage.time_format.required' => 'The time format is required.',
            'garage.time_format.string' => 'The time format must be a string.',
            'garage.time_format.in' => 'The time format must be either "12-hour" or "24-hour".',


            'times.required' => 'The times field is required.',
            'times.min' => 'The times must have at least :min items.',
            'times.*.day.numeric' => 'The day must be a number.',
            'times.*.opening_time.required' => 'The opening time field is required.',
            'times.*.opening_time.date_format' => 'The opening time must be in the format H:i.',
            'times.*.closing_time.required' => 'The closing time field is required.',
            'times.*.closing_time.date_format' => 'The closing time must be in the format H:i.',
            'times.*.is_closed.required' => 'The is closed field is required.',

            'service.required' => 'The service field is required.',
            'service.*.automobile_category_id.required' => 'The automobile category id field is required.',
            'service.*.automobile_category_id.numeric' => 'The automobile category id must be a number.',
            'service.*.services.required' => 'The services field is required.',
            'service.*.services.array' => 'The services must be an array.',
            'service.*.services.*.id.required' => 'The service id field is required.',
            'service.*.services.*.id.numeric' => 'The service id must be a number.',
            'service.*.services.*.checked.required' => 'The checked field is required.',
            'service.*.services.*.checked.boolean' => 'The checked must be a boolean.',
            'service.*.automobile_makes.required' => 'The automobile makes field is required.',
            'service.*.automobile_makes.array' => 'The automobile makes must be an array.',
            'service.*.automobile_makes.*.id.required' => 'The automobile make id field is required.',
            'service.*.automobile_makes.*.id.numeric' => 'The automobile make id must be a number.',
            'service.*.automobile_makes.*.checked.required' => 'The checked field is required.',
            'service.*.automobile_makes.*.checked.boolean' => 'The checked must be a boolean.',
        ];
    }


}
