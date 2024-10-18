<?php

namespace App\Http\Requests;

use App\Rules\SomeTimes;
use Illuminate\Foundation\Http\FormRequest;

class GarageUpdateSeparateRequest extends FormRequest
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

            'garage.id' => 'required|numeric|required|exists:garages,id',
            'garage.name' => 'required|string|max:255',
            'garage.about' => 'nullable|string',
            'garage.web_page' => 'nullable|string',
            'garage.phone' => 'nullable|string',
            // 'garage.email' => 'required|string|email|indisposable|max:255',
            'garage.email' => 'nullable|string|email|unique:garages,email,' . $this->garage["id"] . ',id',
            'garage.additional_information' => 'nullable|string',


            'garage.lat' => 'nullable|string',
            'garage.long' => 'nullable|string',
            'garage.country' => 'nullable|string',
            'garage.city' => 'nullable|string',
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
            'garage.currency' => 'required|string',



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
            'garage.id.required' => 'The garage ID field is required.',
            'garage.id.numeric' => 'The garage ID must be a numeric value.',
            'garage.id.exists' => 'The selected garage ID is invalid.',

            'garage.name.required' => 'The name field is required.',
            'garage.name.string' => 'The name field must be a string.',
            'garage.name.max' => 'The name field may not be greater than :max characters.',

            'garage.about.string' => 'The about field must be a string.',
            'garage.web_page.string' => 'The web page field must be a string.',
            'garage.phone.string' => 'The phone field must be a string.',
            // 'garage.email.required' => 'The email field is required.',
            'garage.email.email' => 'The email must be a valid email address.',
            'garage.email.string' => 'The email field must be a string.',
            'garage.email.unique' => 'The email has already been taken.',
            'garage.email.exists' => 'The selected email is invalid.',
            'garage.additional_information.string' => 'The additional information field must be a string.',

            'garage.lat.required' => 'The latitude field is required.',
            'garage.lat.string' => 'The latitude field must be a string.',

            'garage.long.required' => 'The longitude field is required.',
            'garage.long.string' => 'The longitude field must be a string.',

            'garage.country.required' => 'The country field is required.',
            'garage.country.string' => 'The country field must be a string.',

            'garage.city.required' => 'The city field is required.',
            'garage.city.string' => 'The city field must be a string.',
            'garage.currency.required' => 'The currency field is required.',
            'garage.currency.string' => 'The currency must be a string.',

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
