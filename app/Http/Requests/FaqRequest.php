<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => filter_var(
                $this->input('is_active', false),
                FILTER_VALIDATE_BOOLEAN
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'question' => [
                'required',
                'string',
                'max:500',
            ],

            'answer' => [
                'required',
                'string',
                'max:20000',
            ],

            'position' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' =>
                'La question est obligatoire.',

            'question.max' =>
                'La question ne peut pas dépasser 500 caractères.',

            'answer.required' =>
                'La réponse est obligatoire.',

            'answer.max' =>
                'La réponse est trop longue.',

            'position.required' =>
                'La position est obligatoire.',

            'position.integer' =>
                'La position doit être un nombre entier.',

            'position.min' =>
                'La position ne peut pas être négative.',
        ];
    }
}
