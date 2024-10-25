<?php

namespace App\Http\Requests;

use App\Models\Holiday;
use Illuminate\Foundation\Http\FormRequest;

class HolidayUpdateRequest extends FormRequest
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
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = Holiday::where('id', $value)
                    ->where([
                        "business_id" => auth()->user()->business_id
                    ])
                    ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                        return;
                    }
                },
            ],

            'name' => 'required|string',
            'description' => 'nullable|string',

            'start_date' => [
                'required',
                'date'
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date'
            ],

            'repeats_annually' => 'required|boolean',

        ];
    }
}
