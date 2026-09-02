<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UserSearchRequestOld extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Log the request data before validation
        Log::info('Request data before validation:', $this->all());
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'created_at_operator' => 'nullable|string|max:2|in:=,<,>,<=,>=',
            'created_at_date' => 'nullable|date',
            'birthday_operator' => 'nullable|string|max:2|in:=,<,>,<=,>=',
            'birthday_date' => 'nullable|date',
            'next_lesson_operator' => 'nullable|string|max:2|in:=,<,>,<=,>=',
            'next_lesson_date' => 'nullable|date',
            'filter_date_date' => 'nullable|date',
            'role' => 'nullable|numeric|in:1,2,3',
            'billing' => 'nullable|boolean',
            'course' => 'nullable|integer|exists:courses,id',
            'company_search' => 'nullable|string|max:255',
            'sorting' => 'nullable|string|in:last_name,birth_date,created_at',
        ];
    }
    /**
     * Custom validation conditions.
     */
    public function withValidator($validator)
    {
        // Log the validation status
        Log::info('Validation status:', [
            'errors' => $validator->errors()->all(),
            'validated' => $validator->valid(),

        ]);
    }

}
