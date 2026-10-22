<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class NotificationPreferencesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $groups = config('notification_preferences.groups');

        return [
            'preferences' => ['required', 'array:'.implode(',', $groups)],
            'preferences.*' => ['boolean'],
        ];
    }
}
