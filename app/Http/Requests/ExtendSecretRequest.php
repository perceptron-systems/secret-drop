<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'hours' => ['required', 'integer', 'min:1', 'max:720'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hours.required' => __('messages.val_hours_required'),
            'hours.integer' => __('messages.val_hours_integer'),
            'hours.min' => __('messages.val_hours_min'),
            'hours.max' => __('messages.val_hours_max'),
        ];
    }
}
