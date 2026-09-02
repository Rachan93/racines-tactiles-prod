<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        protected Request $request
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        // Normalisation avant validation Precognition + soumission réelle.
        $normalized = [
            ...$input,

            'first_name' => trim((string) ($input['first_name'] ?? '')),
            'last_name' => trim((string) ($input['last_name'] ?? '')),

            'email' => Str::lower(
                trim((string) ($input['email'] ?? ''))
            ),

            'phone_number' => trim((string) ($input['phone_number'] ?? '')),

            'address' => trim((string) ($input['address'] ?? '')),
            'locality' => trim((string) ($input['locality'] ?? '')),
            'postal_code' => trim((string) ($input['postal_code'] ?? '')),

            'company_name' => trim((string) ($input['company_name'] ?? '')),
            'vat_number' => trim((string) ($input['vat_number'] ?? '')),
            'company_address' => trim((string) ($input['company_address'] ?? '')),
            'company_locality' => trim((string) ($input['company_locality'] ?? '')),
            'company_postal_code' => trim(
                (string) ($input['company_postal_code'] ?? '')
            ),
        ];

        // Important pour ton middleware/helper Precognition :
        // request->validate() doit valider les mêmes valeurs normalisées
        // que celles que nous enregistrerons ensuite.
        $this->request->merge($normalized);

        precognitive(function () {
            $this->request->validate([
                'first_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'last_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique(User::class, 'email'),
                ],

                'password' => $this->passwordRules(),

                'birthday' => [
                    'nullable',
                    'date',
                    'before_or_equal:today',
                ],

                'phone_number' => [
                    'required',
                    'string',
                    'max:30',
                    'regex:/^\+?[0-9\s().-]{6,30}$/',
                    Rule::unique(User::class, 'phone_number'),
                ],

                'address' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'locality' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'postal_code' => [
                    'required',
                    'string',
                    'max:20',
                ],

                'billing' => [
                    'required',
                    'boolean',
                ],

                // Facturation :
                // ces champs n'existent logiquement que si billing = true.
                'company_name' => [
                    'exclude_unless:billing,true',
                    'required',
                    'string',
                    'max:255',
                ],

                'vat_number' => [
                    'exclude_unless:billing,true',
                    'required',
                    'string',
                    'max:50',
                ],

                'company_address' => [
                    'exclude_unless:billing,true',
                    'required',
                    'string',
                    'max:255',
                ],

                'company_locality' => [
                    'exclude_unless:billing,true',
                    'required',
                    'string',
                    'max:100',
                ],

                'company_postal_code' => [
                    'exclude_unless:billing,true',
                    'required',
                    'string',
                    'max:20',
                ],

                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature()
                    ? ['required', 'accepted']
                    : ['nullable'],
            ]);
        });

        $billing = (bool) ($normalized['billing'] ?? false);

        $userData = [
            'first_name' => $normalized['first_name'],
            'last_name' => $normalized['last_name'],
            'email' => $normalized['email'],
            'password' => Hash::make($input['password']),

            'birthday' => ! empty($input['birthday'])
                ? $input['birthday']
                : null,

            'phone_number' => $normalized['phone_number'],

            'address' => $normalized['address'],
            'locality' => $normalized['locality'],
            'postal_code' => $normalized['postal_code'],

            'billing' => $billing,
        ];
        // pour insérer les données de facturation uniquement si la checkbox 'billing' est cochée
        if ($billing) {
            $userData += [
                'company_name' => $normalized['company_name'],
                'vat_number' => $normalized['vat_number'],
                'company_address' => $normalized['company_address'],
                'company_locality' => $normalized['company_locality'],
                'company_postal_code' => $normalized['company_postal_code'],
            ];
        }

        return User::create($userData);
    }
}
