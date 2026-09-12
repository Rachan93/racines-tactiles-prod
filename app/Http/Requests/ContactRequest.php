<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Veuillez renseigner votre prénom.',
            'last_name.required' => 'Veuillez renseigner votre nom.',
            'email.required' => 'Veuillez renseigner votre adresse e-mail.',
            'email.email' => 'Veuillez renseigner une adresse e-mail valide.',
            'subject.required' => 'Veuillez renseigner l’objet de votre message.',
            'message.required' => 'Veuillez rédiger votre message.',
            'message.min' => 'Votre message doit contenir au moins 10 caractères.',
            'message.max' => 'Votre message ne peut pas dépasser 5000 caractères.',
        ];
    }
}
