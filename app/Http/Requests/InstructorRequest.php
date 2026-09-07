<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Les routes sont protégées par auth, verified et CheckAdminRole.
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['first_name', 'last_name', 'bio'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['staff', 'external'])],
            'bio' => ['required', 'string', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'type' => 'type d’instructeur',
            'bio' => 'biographie',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'string' => 'Le champ :attribute doit être du texte.',
            'max' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'type.in' => 'Choisissez Titulaire ou Intervenant externe.',
        ];
    }
}
