<?php

namespace App\Actions\Users;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

// tfw quand t'as perdu quasi deux semaines à t'obstiner à utiliser un FormRequest pour de la validation partielle sans redirection, quand t'aurais pu enterrer ton égo et changer d'approche...

class ValidateUserIndexAction
{
    /**
     * Valide les paramètres d'index et fusionne avec les valeurs par défaut
     *
     * @param Request $request
     * @return array Les paramètres validés avec les valeurs par défaut
     */
    public function execute(Request $request): array
    {
        Log::info('Request data before validation:', $request->all());

        $validator = Validator::make($request->all(), $this->rules());

        // On ne check PAS si ça "fail", on prend juste ce qui est valide
        $validated = $validator->valid();


        $result = array_merge($this->defaults(), $validated);

        Log::info('After tolerant validation:', [
            'validated' => $validated,
            'merged' => $result,
        ]);

        return $result;
    }

    /**
     * Règles de validation pour l'index d'users
     */
    private function rules(): array
    {
        return [
            'tab' => ['string', 'in:users,attendees'],

            'users_search' => ['string', 'max:255'],
            'users_search_filters' => ['array'],
            'users_search_filters.*' => ['string', 'in:last_name,first_name,email,phone_number,locality,postal_code'],

            // Users date filters
            'users_created_at_operator' => ['string', 'in:before,after,equal,before_equal,after_equal,between'],
            'users_created_at_date' => ['date'],
            'users_created_at_date_end' => ['date'],

            // Users birthday filters
            'users_birthday_operator' => ['string', 'in:before,after,equal,before_equal,after_equal,between'],
            'users_birthday_day' => ['integer', 'min:1', 'max:31'],
            'users_birthday_month' => ['integer', 'min:1', 'max:12'],
            'users_birthday_year' => ['integer', 'min:1900', 'max:2100'],
            'users_birthday_end_day' => ['integer', 'min:1', 'max:31'],
            'users_birthday_end_month' => ['integer', 'min:1', 'max:12'],
            'users_birthday_end_year' => ['integer', 'min:1900', 'max:2100'],

            // Attendees pagination & sorting
            'attendees_page' => ['integer', 'min:1'],
            'attendees_perPage' => ['integer', 'min:1', 'max:100'],
            'attendees_sortField' => ['string'],
            'attendees_sortDirection' => ['string', 'in:asc,desc'],
            'attendees_search' => ['string', 'max:255'],
            'attendees_search_filters' => ['array'],
            'attendees_search_filters.*' => ['string', 'in:last_name,first_name,user_name,user_email'],

            // Attendees date filters
            'attendees_created_at_operator' => ['string', 'in:before,after,equal,before_equal,after_equal,between'],
            'attendees_created_at_date' => ['date'],
            'attendees_created_at_date_end' => ['date'],

            // Attendees birthday filters
            'attendees_birthday_operator' => ['string', 'in:before,after,equal,before_equal,after_equal,between'],
            'attendees_birthday_day' => ['integer', 'min:1', 'max:31'],
            'attendees_birthday_month' => ['integer', 'min:1', 'max:12'],
            'attendees_birthday_year' => ['integer', 'min:1900', 'max:2100'],
            'attendees_birthday_end_day' => ['integer', 'min:1', 'max:31'],
            'attendees_birthday_end_month' => ['integer', 'min:1', 'max:12'],
            'attendees_birthday_end_year' => ['integer', 'min:1900', 'max:2100'],
        ];
    }

    /**
     * Valeurs par défaut pour les champs invalides
     */
    private function defaults(): array
    {
        return [
            'tab' => 'users',
            'users_page' => 1,
            'users_perPage' => 25,
            'users_sortField' => 'last_name',
            'users_sortDirection' => 'asc',
            'users_search' => '',
            'users_search_filters' => ['last_name', 'first_name', 'email', 'phone_number'],
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
}
