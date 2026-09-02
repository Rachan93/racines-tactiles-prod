<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(
        protected Request $request
    ) {}

    public function update(User $user, array $input): void
    {
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

        $this->request->merge($normalized);

        $rules = [
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
                Rule::unique(User::class, 'email')
                    ->ignore($user->id),
            ],

            'birthday' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'phone_number' => [
                'required',
                'string',
                'max:30',
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
        ];

        /*
         * Ton workaround Precognition/Fortify.
         * Une requête précognitive s'arrête après la validation
         * et ne doit jamais modifier l'utilisateur.
         */
        precognitive(function () use ($rules) {
            $this->request->validate($rules);
        });

        // Validation réelle de la soumission.
        $validated = Validator::make(
            $normalized,
            $rules
        )->validateWithBag('updateProfileInformation');

        $data = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'birthday' => $validated['birthday'] ?? null,
            'phone_number' => $validated['phone_number'],
            'address' => $validated['address'],
            'locality' => $validated['locality'],
            'postal_code' => $validated['postal_code'],
            'billing' => (bool) $validated['billing'],
        ];

        if ($data['billing']) {
            $data += [
                'company_name' => $validated['company_name'],
                'vat_number' => $validated['vat_number'],
                'company_address' => $validated['company_address'],
                'company_locality' => $validated['company_locality'],
                'company_postal_code' => $validated['company_postal_code'],
            ];
        } else {
            // On nettoie les anciennes informations pro.
            $data += [
                'company_name' => null,
                'vat_number' => null,
                'company_address' => null,
                'company_locality' => null,
                'company_postal_code' => null,
            ];
        }

        $emailChanged = $data['email'] !== $user->email;

        if (
            $emailChanged &&
            $user instanceof MustVerifyEmail
        ) {
            $data['email_verified_at'] = null;

            $user->forceFill($data)->save();

            $user->sendEmailVerificationNotification();

            return;
        }

        $user->forceFill($data)->save();
    }
}
