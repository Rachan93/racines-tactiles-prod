@extends('emails.layouts.racines')

@section('title', 'Vérifiez votre adresse e-mail')

@section('content')
    <p style="margin:0 0 18px;">
        Bonjour {{ $user->first_name ?? 'Membre' }},
    </p>

    <p style="margin:0 0 18px;">
        Bienvenue chez {{ config('racines.name') }}.
        Pour finaliser votre inscription et accéder pleinement à votre espace membre,
        veuillez confirmer votre adresse e-mail.
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
                    href="{{ $verificationUrl }}"
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
                    Vérifier mon adresse e-mail
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 18px;">
        Si vous n’êtes pas à l’origine de cette demande, vous pouvez simplement ignorer cet e-mail.
    </p>

    <p style="margin:0;">
        À bientôt à l’atelier,<br>
        {{ config('racines.owner') }}
    </p>
@endsection
