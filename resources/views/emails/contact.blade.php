@extends('emails.layouts.racines')

@section('title', 'Nouveau message de contact')

@section('content')
    <p style="margin:0 0 18px;">
        Un nouveau message a été envoyé depuis le formulaire de contact.
    </p>

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="
            width:100%;
            margin:24px 0;
            border:1px solid #e4e4e7;
            border-radius:8px;
        "
    >
        <tr>
            <td style="padding:14px 16px; border-bottom:1px solid #e4e4e7;">
                <strong>Nom</strong><br>
                {{ $contact['first_name'] }} {{ $contact['last_name'] }}
            </td>
        </tr>

        <tr>
            <td style="padding:14px 16px; border-bottom:1px solid #e4e4e7;">
                <strong>Adresse e-mail</strong><br>

                <a
                    href="mailto:{{ $contact['email'] }}"
                    style="color:#3f3f46;"
                >
                    {{ $contact['email'] }}
                </a>
            </td>
        </tr>

        @if($isExistingMember)
    <tr>
        <td style="padding:14px 16px; border-bottom:1px solid #e4e4e7;">
            <strong>Compte membre</strong><br>
            Cette adresse e-mail est associée à un compte membre existant.
        </td>
    </tr>
@endif

        <tr>
            <td style="padding:14px 16px;">
                <strong>Objet</strong><br>
                {{ $contact['subject'] }}
            </td>
        </tr>
    </table>

    <div
        style="
            padding:18px;
            background-color:#fafafa;
            border:1px solid #e4e4e7;
            border-radius:8px;
            white-space:pre-line;
        "
    >{{ $contact['message'] }}</div>
@endsection
