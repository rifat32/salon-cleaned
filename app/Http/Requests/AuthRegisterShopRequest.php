<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthRegisterShopRequest extends FormRequest
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
            'user.password' => 'required|confirmed|string|min:6',
            'user.phone' => 'nullable|string',
            'user.image' => 'nullable|string',
            'user.address_line_1' => 'nullable|string',
            'user.address_line_2' => 'nullable|string',
            'user.country' => 'nullable|string',
            'user.city' => 'nullable|string',
            'user.postcode' => 'nullable|string',
            'user.lat' => 'nullable|string',
            'user.long' => 'nullable|string',


            'shop.name' => 'required|string|max:255',
            'shop.about' => 'nullable|string',
            'shop.web_page' => 'nullable|string',
            'shop.phone' => 'nullable|string',
            // 'shop.email' => 'required|string|email|indisposable|max:255|unique:shop,email',
            'shop.email' => 'required|string|email|max:255|unique:shops,email',
            'shop.additional_information' => 'nullable|string',

            'shop.lat' => 'nullable|string',
            'shop.long' => 'nullable|string',
            'shop.country' => 'required|string',
            'shop.city' => 'required|string',
            'shop.postcode' => 'nullable|string',
            'shop.address_line_1' => 'required|string',
            'shop.address_line_2' => 'nullable|string',

            'shop.sku_prefix' => 'nullable|string',


            'shop.logo' => 'nullable|string',

            'shop.image' => 'nullable|string',

            'shop.images' => 'nullable|array',
            'shop.images.*' => 'nullable|string',


            'shop.is_mobile_shop' => 'required|boolean',
            'shop.wifi_available' => 'required|boolean',
            'shop.labour_rate' => 'nullable|numeric',







        ];


    }
    public function messages()
    {
        return [
            'user.first_Name.required' => 'The first name field is required.',
            'user.first_Name.string' => 'The first name field must be a string.',
            'user.first_Name.max' => 'The first name field may not be greater than :max characters.',

            'user.last_Name.required' => 'The last name field is required.',
            'user.last_Name.string' => 'The last name field must be a string.',
            'user.last_Name.max' => 'The last name field may not be greater than :max characters.',

            'user.email.required' => 'The email field is required.',
            'user.email.email' => 'The email must be a valid email address.',
            'user.email.string' => 'The email field must be a string.',
            'user.email.email' => 'The email field must be a valid email address.',
            'user.email.max' => 'The email field may not be greater than :max characters.',
            'user.email.unique' => 'The email has already been taken.',

            'user.password.required' => 'The password field is required.',
            'user.password.confirmed' => 'The password confirmation does not match.',
            'user.password.string' => 'The password field must be a string.',
            'user.password.min' => 'The password must be at least :min characters.',

            // 'user.phone.required' => 'The phone field is required.',
            'user.phone.string' => 'The phone field must be a string.',

            'user.image.nullable' => 'The image field must be nullable.',

            'user.address_line_1.string' => 'The address line 1 field must be a string.',
            'user.address_line_2.string' => 'The address line 2 field must be a string.',
            'user.country.string' => 'The country field must be a string.',
            'user.city.string' => 'The city field must be a string.',
            'user.postcode.string' => 'The postcode field must be a string.',
            'user.lat.string' => 'The latitude field must be a string.',
            'user.long.string' => 'The longitude field must be a string.',

            'shop.name.required' => 'The shop name field is required.',
            'shop.name.string' => 'The shop name field must be a string.',
            'shop.name.max' => 'The shop name field may not be greater than :max characters.',

            'shop.about.string' => 'The about field must be a string.',
            'shop.web_page.string' => 'The web page field must be a string.',
            'shop.phone.string' => 'The phone field must be a string.',

            'shop.email.required' => 'The shop email field is required.',
            'shop.email.string' => 'The shop email field must be a string.',
            'shop.email.email' => 'The shop email field must be a valid email address.',
            'shop.email.max' => 'The shop email field may not be greater than :max characters.',
            'shop.email.unique' => 'The shop email has already been taken.',

            'shop.additional_information.string' => 'The additional information field must be a string.',

            'shop.lat.string' => 'The latitude field must be a string.',
            'shop.long.string' => 'The longitude field must be a string.',

            'shop.country.required' => 'The country field is required.',
            'shop.country.string' => 'The country field must be a string.',

            'shop.city.required' => 'The city field is required.',
            'shop.city.string' => 'The city field must be a string.',

            'shop.postcode.string' => 'The postcode field must be a string.',

            'shop.address_line_1.required' => 'The address line 1 field is required.',
            'shop.address_line_1.string' => 'The address line 1 field must be a string.',

            'shop.address_line_2.string' => 'The address line 2 field must be a string.',

            'shop.sku_prefix.string' => 'The SKU prefix field must be a string.',

            'shop.logo.nullable' => 'The logo field must be nullable.',
            'shop.image.nullable' => 'The image field must be nullable.',

            'shop.images.array' => 'The images field must be an array.',
            'shop.images.*.string' => 'Each image in the images field must be a string.',

            'shop.is_mobile_shop.required' => 'The is mobile shop field is required.',
            'shop.is_mobile_shop.boolean' => 'The is mobile shop field must be a boolean.',

            'shop.wifi_available.required' => 'The wifi available field is required.',
            'shop.wifi_available.boolean' => 'The wifi available field must be a boolean.',

            'shop.labour_rate.numeric' => 'The labour rate field must be numeric.',
        ];
    }




}
