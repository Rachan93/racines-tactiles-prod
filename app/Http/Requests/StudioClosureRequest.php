<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudioClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Les quatre routes sont protégées par les middlewares admin existants.
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'notes'] as $field) {
            if (is_string($this->input($field))) {
                $value = trim($this->input($field));
                $this->merge([$field => $field === 'notes' && $value === '' ? null : $value]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['school_holiday', 'studio_closure'])],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'type' => 'catégorie',
            'start_date' => 'date de début',
            'end_date' => 'date de fin',
            'notes' => 'notes',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'string' => 'Le champ :attribute doit contenir du texte.',
            'max' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'date_format' => 'Le champ :attribute doit être une date valide au format AAAA-MM-JJ.',
            'type.in' => 'Choisissez Congé scolaire ou Fermeture atelier.',
            'end_date.after_or_equal' => 'La date de fin doit être égale ou postérieure à la date de début.',
        ];
    }
}
