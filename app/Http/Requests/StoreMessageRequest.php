<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request. A new thread also names its recipient.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.config('messaging.body_max'), 'regex:/\S/'],
            'recipient_id' => [
                Rule::requiredIf($this->route('conversation') === null),
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                Rule::notIn([$this->user()?->id]),
            ],
        ];
    }
}
