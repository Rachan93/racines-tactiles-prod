@extends('emails.layouts.racines')

@section('title', 'Réinitialisation de votre mot de passe')

@section('content')
    <p style="margin:0 0 18px;">
        Bonjour {{ $user->first_name ?? 'Membre' }},
    </p>

    <p style="margin:0 0 18px;">
        Nous avons reçu une demande de réinitialisation du mot de passe
        associé à votre compte {{ config('racines.name') }}.
    </p>

    <table
        role="presentation"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="margin:26px 0;"
    >
        <tr>
            <td>
                <a
                    href="{{ $resetUrl }}"
                    style="
                        display:inline-block;
                        padding:11px 18px;
                        background-color:#18181b;
                        color:#ffffff;
                        text-decoration:none;
                        border-radius:8px;
                        font-size:14px;
                        font-weight:600;
                    "
                >
                    Réinitialiser mon mot de passe
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 18px;">
        Si vous n’avez pas demandé cette réinitialisation,
        aucune action n’est nécessaire.
    </p>

    <p style="margin:0;">
        À bientôt à l’atelier,<br>
        {{ config('racines.owner') }}
    </p>
@endsection
