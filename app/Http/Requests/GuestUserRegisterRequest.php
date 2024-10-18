<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuestUserRegisterRequest extends FormRequest
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
            "id" => "nullable|exists:users,id",
            'first_Name' => 'required|string|max:255',
            'last_Name' => 'required|string|max:255',
            'email' => 'nullable|string|unique:users,email,' . $this->id . ',id',
            'phone' => 'required|string',
            'image' => 'nullable|string',
            'address_line_1' => 'nullable|string',
            'address_line_2' => 'nullable|string',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
            'postcode' => 'nullable|string',
            'lat' => 'nullable|string',
            'long' => 'nullable|string',
        ];

    }
}
