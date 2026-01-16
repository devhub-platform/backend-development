<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Support Report Received</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px #e0e0e0;
            padding: 32px;
        }

        .header {
            border-bottom: 1px solid #eee;
            margin-bottom: 24px;
            padding-bottom: 16px;
        }

        .logo {
            height: 40px;
        }

        .footer {
            margin-top: 32px;
            color: #888;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }} Logo" class="logo">
        <h2>{{ config('app.name') }} Support Team</h2>
    </div>
    <p>Hello{{ $user->name }},</p>
    <p>Thank you for reaching out. We have received your report and are currently reviewing your submission.</p>
    @isset($report->message)
        <p><strong>Your message:</strong></p>
        <blockquote style="background:#f1f1f1; padding:12px; border-radius:4px;">{{ $report->message }}</blockquote>
    @endisset
    <p>Submitted on: {{ $report->created_at }}</p>
    <p>We will respond to you soon.</p>
    <div class="footer">
        Best regards,<br>
        {{ config('app.name') }} Support Team
    </div>
</div>
</body>
</html>
