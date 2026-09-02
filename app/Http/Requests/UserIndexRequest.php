<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation des filtres de recherche et pagination.
     */
    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'string', 'in:users,attendees'],

            // --- FILTRES MEMBRES (USERS) ---
            'users_page' => ['nullable', 'integer', 'min:1'],
            'users_perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'users_sortField' => ['nullable', 'string'],
            'users_sortDirection' => ['nullable', 'string', 'in:asc,desc'],
            'users_search' => ['nullable', 'string', 'max:255'],
            'users_search_filters' => ['nullable', 'array'],
            'users_search_filters.*' => [
                'string',
                'in:last_name,first_name,email,phone_number,locality,postal_code,address,company_name,company_address,company_locality,company_postal_code,vat_number',
            ],

            // Filtre par cours
            'users_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'users_module_status' => ['nullable', 'string', 'in:all,active,completed,upcoming,none'],

            // Filtre Date de séance (avec opérateurs)
            'users_lesson_date_operator' => ['nullable', 'string', 'in:before,after,equal,before_equal,after_equal,between'],
            'users_lesson_date' => ['nullable', 'date'],
            'users_lesson_date_end' => ['nullable', 'date'],

            // Date d'inscription
            'users_created_at_operator' => ['nullable', 'string', 'in:before,after,equal,before_equal,after_equal,between'],
            'users_created_at_date' => ['nullable', 'date'],
            'users_created_at_date_end' => ['nullable', 'date'],

            // Date d'anniversaire (jour/mois/année facultatifs)
            'users_birthday_operator' => ['nullable', 'string', 'in:before,after,equal,before_equal,after_equal,between'],
            'users_birthday_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'users_birthday_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'users_birthday_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'users_birthday_end_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'users_birthday_end_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'users_birthday_end_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],

            // --- FILTRES INVITÉS (ATTENDEES) ---
            'attendees_page' => ['nullable', 'integer', 'min:1'],
            'attendees_perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'attendees_sortField' => ['nullable', 'string'],
            'attendees_sortDirection' => ['nullable', 'string', 'in:asc,desc'],
            'attendees_search' => ['nullable', 'string', 'max:255'],
            'attendees_search_filters' => ['nullable', 'array'],
            'attendees_search_filters.*' => ['string', 'in:last_name,first_name,user_name,user_email'],

            // Filtre par cours
            'attendees_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'attendees_module_status' => ['nullable', 'string', 'in:all,active,completed,upcoming,none'],

            // Filtre Date de séance (avec opérateurs)
            'attendees_lesson_date_operator' => ['nullable', 'string', 'in:before,after,equal,before_equal,after_equal,between'],
            'attendees_lesson_date' => ['nullable', 'date'],
            'attendees_lesson_date_end' => ['nullable', 'date'],

            // Date d'inscription
            'attendees_created_at_operator' => ['nullable', 'string', 'in:before,after,equal,before_equal,after_equal,between'],
            'attendees_created_at_date' => ['nullable', 'date'],
            'attendees_created_at_date_end' => ['nullable', 'date'],

            // Date d'anniversaire (jour/mois/année facultatifs)
            'attendees_birthday_operator' => ['nullable', 'string', 'in:before,after,equal,before_equal,after_equal,between'],
            'attendees_birthday_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'attendees_birthday_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'attendees_birthday_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'attendees_birthday_end_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'attendees_birthday_end_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'attendees_birthday_end_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        ];
    }

    /**
     * Valeurs par défaut complètes pour initialiser l'état propre de la recherche.
     */
    public function defaults(): array
    {
        return [
            'tab' => 'users',

            'users_page' => 1,
            'users_perPage' => 25,
            'users_sortField' => 'last_name',
            'users_sortDirection' => 'asc',
            'users_search' => '',
            'users_search_filters' => ['last_name', 'first_name', 'email', 'phone_number'],
            'users_course_id' => '',
            'users_module_status' => 'all',
            'users_lesson_date_operator' => '',
            'users_lesson_date' => '',
            'users_lesson_date_end' => '',
            'users_created_at_operator' => '',
            'users_created_at_date' => '',
            'users_created_at_date_end' => '',
            'users_birthday_operator' => '',
            'users_birthday_day' => '',
            'users_birthday_month' => '',
            'users_birthday_year' => '',
            'users_birthday_end_day' => '',
            'users_birthday_end_month' => '',
            'users_birthday_end_year' => '',

            'attendees_page' => 1,
            'attendees_perPage' => 25,
            'attendees_sortField' => 'last_name',
            'attendees_sortDirection' => 'asc',
            'attendees_search' => '',
            'attendees_search_filters' => ['last_name', 'first_name', 'user_name'],
            'attendees_course_id' => '',
            'attendees_module_status' => 'all',
            'attendees_lesson_date_operator' => '',
            'attendees_lesson_date' => '',
            'attendees_lesson_date_end' => '',
            'attendees_created_at_operator' => '',
            'attendees_created_at_date' => '',
            'attendees_created_at_date_end' => '',
            'attendees_birthday_operator' => '',
            'attendees_birthday_day' => '',
            'attendees_birthday_month' => '',
            'attendees_birthday_year' => '',
            'attendees_birthday_end_day' => '',
            'attendees_birthday_end_month' => '',
            'attendees_birthday_end_year' => '',
        ];
    }

    /**
     * Sur un listing GET, on n'interrompt pas avec une redirection en cas d'erreur.
     */
    protected function failedValidation(Validator $validator): void
    {
        // Pas de redirection pour les requêtes de consultation
    }

    /**
     * Retourne les données validées fusionnées avec les valeurs par défaut.
     */
    public function validatedWithDefaults(): array
    {
        $validator = $this->getValidatorInstance();

        if (! $validator->fails()) {
            $valid = $this->validated();
        } else {
            $failedKeys = array_keys($validator->failed());
            $valid = array_diff_key($this->only(array_keys($this->rules())), array_flip($failedKeys));
        }

        $filtered = array_filter($valid, fn ($v) => $v !== null && $v !== '');

        return array_merge($this->defaults(), $filtered);
    }
}
