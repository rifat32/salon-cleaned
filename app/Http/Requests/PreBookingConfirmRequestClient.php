<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreBookingConfirmRequestClient extends FormRequest
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
            "pre_booking_id"=>"required|numeric",
            "job_bid_id"=>"required|numeric",
            "is_confirmed"=>"required|boolean"
        ];
    }
}
