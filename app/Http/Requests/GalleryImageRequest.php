<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GalleryImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Les routes de mutation sont protégées par CheckAdminRole.
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('description'))) {
            $this->merge(['description' => trim($this->input('description'))]);
        }
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:500'],
            'image' => [
                $this->route('galleryImage') || $this->isPrecognitive() ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:max_width=6000,max_height=6000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Décrivez brièvement ce que représente cette image.',
            'description.string' => 'La description doit contenir du texte.',
            'description.max' => 'La description ne doit pas dépasser 500 caractères.',
            'image.required' => 'Choisissez une image à ajouter.',
            'image.image' => 'Le fichier doit être une image valide.',
            'image.mimes' => 'Formats acceptés : JPG, PNG et WebP.',
            'image.max' => 'L’image ne doit pas dépasser 5 Mo.',
            'image.dimensions' => 'L’image ne doit pas dépasser 6 000 pixels de largeur ou de hauteur.',
            'image.uploaded' => 'Le transfert de l’image a échoué. Vérifiez sa taille et réessayez.',
        ];
    }
}
