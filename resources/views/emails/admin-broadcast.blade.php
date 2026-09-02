<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Communication Atelier' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 24px;
            line-height: 1.6;
        }
        .wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #f1f5f9;
            padding: 24px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.025em;
        }
        .test-badge {
            display: inline-block;
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .content {
            padding: 32px 28px;
            font-size: 14px;
            color: #334155;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 24px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            @if($isTest)
                <div class="test-badge">⚠️ Ceci est un e-mail de test administrateur</div>
            @endif
            <h1>Atelier de Céramique</h1>
        </div>

        <div class="content">{!! nl2br(e($content)) !!}</div>

        <div class="footer">
            <p style="margin: 0;">Atelier de Céramique & Création</p>
            <p style="margin: 4px 0 0 0;">Vous recevez cet e-mail suite à votre inscription à nos cours ou stages.</p>
        </div>
    </div>
</body>
</html>
