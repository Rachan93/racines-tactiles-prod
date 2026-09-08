<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GalleryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'original_ids' => ['required', 'array', 'min:1'],
            'original_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'La liste des images est obligatoire.',
            'array' => 'La liste des images est invalide.',
            'integer' => 'Un identifiant d’image est invalide.',
            'distinct' => 'Une image ne peut apparaître qu’une seule fois.',
            'min' => 'La liste des images est invalide.',
        ];
    }
}
