<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Type;

class CourseStoreRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }
    protected function prepareForValidation(): void
    {
        $isStage = $this->selectedTypeIsStage();

        $this->merge([
            // Jamais de sous-type pour un non-stage
            'sub_type' => $isStage
                ? ($this->input('sub_type') ?: null)
                : null,

            // Mise en avant également réservée aux stages
            'is_featured' => $isStage
                ? filter_var(
                    $this->input('is_featured', false),
                    FILTER_VALIDATE_BOOLEAN
                )
                : false,

            'is_active' => filter_var(
                $this->input('is_active', true),
                FILTER_VALIDATE_BOOLEAN
            ),
        ]);
    }
    /**
     * Règles de validation du formulaire de création.
     */
    public function rules(): array
    {
        return [
            // Informations générales
            'name' => ['required', 'string', 'max:255'],
            'type_id' => ['required', 'integer', 'exists:types,id'],
            'default_instructor_id' => ['required', 'integer', 'exists:instructors,id'],

            // Calendrier & Récurrence
            'first_lesson_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:first_lesson_date'],
            'default_start_time' => ['required', 'date_format:H:i'],
            'default_end_time' => ['required', 'date_format:H:i', 'after:default_start_time'],
            'frequency' => ['required', 'integer', 'min:1', 'max:365'],

            // Capacités de la salle
            'default_spots_max_wheel' => ['required', 'integer', 'min:0', 'max:50'],
            'default_spots_max_handbuilding' => ['required', 'integer', 'min:0', 'max:50'],

            // Tarification
            'default_price' => ['required', 'numeric', 'min:0'],

            // Publication & Visibilité
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],

            // Contenu éditorial & bilingue (Stages & Masterclasses)
            'name_en' => ['nullable', 'string', 'max:255'],
            'sub_type' => [
                $this->selectedTypeIsStage() ? 'required' : 'nullable',
                'string',
                Rule::in([
                    'wheel',
                    'external',
                    'themed',
                    'one-off',
                ]),
            ],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'practical_info' => ['nullable', 'string', 'max:10000'],
            'practical_info_en' => ['nullable', 'string', 'max:10000'],

            // Options de calcul des congés
            'exclude_public_holidays' => ['nullable', 'boolean'],
            'exclude_school_holidays' => ['nullable', 'boolean'],
            'exclude_studio_closures' => ['nullable', 'boolean'],
            'exclude_weekends' => ['nullable', 'boolean'],

            // Séances générées et validées par l'admin
            'confirmed_dates' => ['required', 'array', 'min:1'],
            'confirmed_dates.*' => ['required', 'date'],
        ];
    }

    /**
     * Garde-fou métier : au moins 1 place au total (Tour ou Modelage).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $wheel = (int) $this->input('default_spots_max_wheel', 0);
            $hand = (int) $this->input('default_spots_max_handbuilding', 0);

            if ($wheel + $hand <= 0) {
                $validator->errors()->add(
                    'default_spots_max_wheel',
                    'Le cours doit comporter au moins 1 place au total (Tour ou Modelage).'
                );
            }
        });
    }

    /**
     * Noms d'attributs personnalisés en français pour Precognition.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom du cours (FR)',
            'name_en' => 'nom du cours (EN)',
            'type_id' => 'type de formule',
            'default_instructor_id' => 'professeur par défaut',
            'first_lesson_date' => 'date de la première séance',
            'end_date' => 'date de fin',
            'default_start_time' => 'heure de début',
            'default_end_time' => 'heure de fin',
            'frequency' => 'fréquence',
            'default_spots_max_wheel' => 'places tour',
            'default_spots_max_handbuilding' => 'places modelage',
            'default_price' => 'tarif par séance',
            'is_active' => 'statut de publication',
            'is_featured' => 'mise en avant',
            'sub_type' => 'catégorie du stage',
            'subtitle' => 'sous-titre (FR)',
            'subtitle_en' => 'sous-titre (EN)',
            'description' => 'description (FR)',
            'description_en' => 'description (EN)',
            'practical_info' => 'informations pratiques (FR)',
            'practical_info_en' => 'informations pratiques (EN)',
            'exclude_public_holidays' => 'exclusion des jours fériés',
            'exclude_school_holidays' => 'exclusion des vacances scolaires',
            'exclude_studio_closures' => 'exclusion des fermetures atelier',
            'exclude_weekends' => 'exclusion des week-ends',
            'confirmed_dates' => 'séances retenues',
        ];
    }

    /**
     * Messages d'erreur explicites en français.
     */
    public function messages(): array
    {
        return [
            'required' => 'Ce champ est obligatoire.',
            'date' => 'Veuillez renseigner une date valide.',
            'integer' => 'Veuillez renseigner un nombre entier valide.',
            'numeric' => 'Veuillez renseigner une valeur numérique valide.',
            'min' => 'La valeur minimale est :min.',
            'max' => 'La valeur maximale est :max.',

            // Messages dédiés par champ
            'name.required' => 'Le nom du cours est obligatoire.',
            'type_id.required' => 'Veuillez sélectionner un type de formule.',
            'type_id.exists' => 'Le type de formule sélectionné est invalide.',
            'default_instructor_id.required' => 'Veuillez sélectionner un professeur par défaut.',
            'default_instructor_id.exists' => 'Le professeur sélectionné est invalide.',
            'first_lesson_date.required' => 'La date de première séance est obligatoire.',
            'end_date.required' => 'La date de fin est obligatoire.',
            'end_date.after_or_equal' => 'La date de fin doit être identique ou postérieure à la première séance.',
            'default_start_time.required' => "L'heure de début est obligatoire.",
            'default_end_time.required' => "L'heure de fin est obligatoire.",
            'default_end_time.after' => "L'heure de fin doit être postérieure à l'heure de début.",
            'frequency.required' => 'La fréquence est obligatoire.',
            'frequency.min' => 'La fréquence doit être d\'au moins 1 jour.',
            'default_spots_max_wheel.required' => 'Le nombre de tours est obligatoire.',
            'default_spots_max_wheel.min' => 'Le nombre de tours ne peut pas être négatif.',
            'default_spots_max_handbuilding.required' => 'Le nombre de places modelage est obligatoire.',
            'default_spots_max_handbuilding.min' => 'Le nombre de places modelage ne peut pas être négatif.',
            'default_price.required' => 'Le tarif est obligatoire.',
            'default_price.min' => 'Le tarif ne peut pas être négatif.',
            'sub_type.in' => 'La catégorie de stage sélectionnée est invalide.',
            'confirmed_dates.required' => 'Vous devez sélectionner au moins une séance à générer dans le planning.',
            'confirmed_dates.min' => 'Vous devez sélectionner au moins une séance à générer dans le planning.',
        ];
    }
    private function selectedTypeIsStage(): bool
    {
        $typeId = $this->input('type_id');

        if (! $typeId) {
            return false;
        }

        $typeName = Type::query()
            ->whereKey($typeId)
            ->value('name');

        return str_contains(
            mb_strtolower((string) $typeName),
            'stage'
        );
    }
}
