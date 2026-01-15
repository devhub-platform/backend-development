<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 40px 20px;
            line-height: 1.7;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }

        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .content {
            padding: 40px 35px;
        }

        .greeting {
            color: #1a202c;
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 20px 0;
        }

        .content p {
            color: #4a5568;
            font-size: 16px;
            margin: 0 0 18px 0;
        }

        .button-wrapper {
            text-align: center;
            margin: 35px 0;
        }

        .button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .divider {
            height: 1px;
            background: #e2e8f0;
            margin: 30px 0;
        }

        .footer {
            background: #f7fafc;
            padding: 25px 35px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            font-size: 13px;
            color: #718096;
            margin: 0 0 8px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
    </div>
    <div class="content">
        <h2 class="greeting">Hello {{ $user->name ?? 'there' }},</h2>
        <p>Welcome to <strong>{{ config('app.name') }}</strong>! We're excited to have you join our community.</p>
        <p>To get started, please verify your email address by clicking the button below:</p>
        <div class="button-wrapper">
            <a href="{{ $verificationUrl ?? '#' }}" class="button">Verify Email Address</a>
        </div>
        <div class="divider"></div>
        <p style="font-size: 14px; color: #718096;">If you didn't create an account, you can safely ignore this email.</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>Questions? Contact us at support@example.com</p>
    </div>
</div>
</body>
</html>
