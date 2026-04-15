<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f7fb;
            margin: 0;
            padding: 0;
        }

        .wrapper {
            max-width: 640px;
            margin: 24px auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .header {
            padding: 20px 24px;
            background: #0f172a;
            color: #ffffff;
        }

        .content {
            padding: 24px;
            color: #111827;
            line-height: 1.6;
            white-space: pre-line;
        }

        .footer {
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 13px;
            background: #f9fafb;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="header">
            <h2 style="margin:0; font-size:18px;">{{ config('app.name') }} Admin Message</h2>
        </div>

        <div class="content">
            <p style="margin-top: 0;">Hello {{ $user->name ?? 'there' }},</p>

            <p>{!! nl2br(e($message)) !!}</p>

            <p style="margin-bottom: 0;">
                Regards,<br>
                {{ $senderName ?: config('app.name') . ' Admin' }}
            </p>
        </div>

        <div class="footer">
            This email was sent from the admin panel of {{ config('app.name') }}.
        </div>
    </div>
</body>

</html>