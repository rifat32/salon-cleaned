<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HoldSlotRequest extends FormRequest
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

    public function rules()
    {
        return [
            'held_slots' => 'present|array', // Ensure held_slots is an array
            'held_slots.*' => [
            'date_format:g:i A',
        ],
            'customer_id' => 'required|integer|exists:users,id', // Ensure customer_id exists in users table
            'expert_id' => 'required|integer|exists:users,id', // Ensure expert_id exists in users table
        ];
    }

    public function messages()
    {
        return [
            'held_slots.required' => 'The held slots are required.',
            'held_slots.array' => 'The held slots must be an array.',
            'held_slots.*.integer' => 'Each held slot must be an integer.',
            'held_slots.*.distinct' => 'Held slots must be unique.',
            'customer_id.required' => 'The customer ID is required.',
            'customer_id.integer' => 'The customer ID must be an integer.',
            'customer_id.exists' => 'The selected customer ID is invalid.',
            'expert_id.required' => 'The expert ID is required.',
            'expert_id.integer' => 'The expert ID must be an integer.',
            'expert_id.exists' => 'The selected expert ID is invalid.',
        ];
    }
}
