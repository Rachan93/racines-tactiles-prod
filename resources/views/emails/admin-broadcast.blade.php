<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>

<body style="margin:0; padding:0; background-color:#f4f4f5; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif; color:#27272a;">

<table
    role="presentation"
    width="100%"
    cellspacing="0"
    cellpadding="0"
    border="0"
    style="width:100%; background-color:#f4f4f5;"
>
    <tr>
        <td align="center" style="padding:32px 16px;">

            <table
                role="presentation"
                width="600"
                cellspacing="0"
                cellpadding="0"
                border="0"
                style="width:100%; max-width:600px; background-color:#ffffff; border:1px solid #e4e4e7; border-radius:14px; overflow:hidden;"
            >

                {{-- ===================================================== --}}
                {{-- HEADER                                                --}}
                {{-- ===================================================== --}}
                <tr>
                    <td
                        align="center"
                        style="padding:28px 24px 22px; background-color:#fafafa; border-bottom:1px solid #e4e4e7;"
                    >
                        @if($isTest)
                            <div style="margin-bottom:16px;">
                                <span
                                    style="
                                        display:inline-block;
                                        padding:5px 10px;
                                        background-color:#fef3c7;
                                        border:1px solid #fde68a;
                                        border-radius:999px;
                                        color:#92400e;
                                        font-size:11px;
                                        font-weight:700;
                                    "
                                >
                                    ⚠️ E-mail de test administrateur
                                </span>
                            </div>
                        @endif

                       @if($isPreview)
    <img
        src="{{ asset(config('racines.logo')) }}"
        alt="{{ config('racines.name') }}"
        width="110"
        style="
            display:block;
            width:110px;
            max-width:100%;
            height:auto;
            margin:0 auto 14px;
            border:0;
        "
    >
@else
    <img
        src="{{ $message->embed(public_path(config('racines.logo'))) }}"
        alt="{{ config('racines.name') }}"
        width="110"
        style="
            display:block;
            width:110px;
            max-width:100%;
            height:auto;
            margin:0 auto 14px;
            border:0;
        "
    >
@endif

                        <div
                            style="
                                font-size:20px;
                                line-height:1.3;
                                font-weight:700;
                                color:#18181b;
                            "
                        >
                            {{ config('racines.name') }}
                        </div>
                    </td>
                </tr>

                {{-- ===================================================== --}}
                {{-- CONTENT                                               --}}
                {{-- ===================================================== --}}
                <tr>
                    <td
                        style="
                            padding:32px 30px;
                            font-size:15px;
                            line-height:1.7;
                            color:#3f3f46;
                            word-break:break-word;
                        "
                    >
                        {!! nl2br(e($content)) !!}
                    </td>
                </tr>

                {{-- ===================================================== --}}
                {{-- FOOTER                                                --}}
                {{-- ===================================================== --}}
                <tr>
                    <td
                        align="center"
                        style="
                            padding:24px;
                            background-color:#fafafa;
                            border-top:1px solid #e4e4e7;
                            color:#71717a;
                            font-size:12px;
                            line-height:1.6;
                        "
                    >
                        <div
                            style="
                                margin-bottom:5px;
                                color:#3f3f46;
                                font-size:13px;
                                font-weight:700;
                            "
                        >
                            {{ config('racines.legal_name') }}
                        </div>

                        <div>
                            {{ config('racines.owner') }}
                        </div>

                        <div style="margin-top:6px;">
                            {{ config('racines.address') }}<br>
                            {{ config('racines.postal_code') }}
                            {{ config('racines.city') }}
                        </div>

                        <div style="margin-top:6px;">
                            <a
                                href="tel:+32487328826"
                                style="color:#52525b; text-decoration:none;"
                            >
                                {{ config('racines.phone') }}
                            </a>
                        </div>

                        <div style="margin-top:3px;">
                            <a
                                href="mailto:{{ config('mail.from.address') }}"
                                style="color:#52525b; text-decoration:none;"
                            >
                                {{ config('mail.from.address') }}
                            </a>
                        </div>

                        <div
                            style="
                                margin-top:18px;
                                padding-top:16px;
                                border-top:1px solid #e4e4e7;
                                font-size:11px;
                                color:#a1a1aa;
                            "
                        >
                            Vous recevez cet e-mail dans le cadre de votre relation avec
                            {{ config('racines.name') }}.
                        </div>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
