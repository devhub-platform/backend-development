<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body,
        table,
        td,
        p,
        a {
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        @media only screen and (max-width: 620px) {
            .wrapper {
                width: 100% !important;
            }

            .content-padding {
                padding: 28px 20px !important;
            }

            .title {
                font-size: 26px !important;
            }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#eef2ff;">
@php
    $appName = config('app.name');
    $appUrl = rtrim(config('app.url') ?? url('/'), '/');
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef2ff; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="wrapper" style="width:600px; max-width:600px; background:#ffffff; border-radius:16px; overflow:hidden;">
                <tr>
                    <td style="padding:36px 30px; background:linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); text-align:center;">
                        <p style="margin:0; font-size:12px; letter-spacing:1.8px; text-transform:uppercase; color:#dbeafe;">Welcome to</p>
                        <h1 class="title" style="margin:10px 0 0; font-size:32px; line-height:1.2; color:#ffffff;">{{ $appName }}</h1>
                    </td>
                </tr>

                <tr>
                    <td class="content-padding" style="padding:36px 34px 18px;">
                        <p style="margin:0 0 14px; font-size:24px; line-height:1.3; color:#111827; font-weight:700;">Hello {{ $user->name ?? 'there' }},</p>
                        <p style="margin:0 0 16px; font-size:16px; line-height:1.7; color:#4b5563;">
                            We are thrilled to have you on board. Your account is ready, and your journey with <strong style="color:#111827;">{{ $appName }}</strong> starts now.
                        </p>
                        <p style="margin:0 0 22px; font-size:16px; line-height:1.7; color:#4b5563;">
                            Explore the platform, connect with the community, and discover new opportunities every day.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 34px 26px;" align="center">
                        <a href="{{ $appUrl }}" style="display:inline-block; padding:13px 28px; border-radius:999px; background-color:#4f46e5; color:#ffffff; font-size:15px; font-weight:600; text-decoration:none;">
                            Get Started
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 34px 30px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8faff; border:1px solid #e5e7eb; border-radius:12px;">
                            <tr>
                                <td style="padding:16px 18px; font-size:14px; line-height:1.6; color:#4b5563;">
                                    <strong style="color:#111827;">Quick tip:</strong>
                                    Add {{ $appName }} to your trusted contacts so you never miss updates.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 24px 24px; background-color:#f9fafb; border-top:1px solid #e5e7eb; text-align:center;">
                        <p style="margin:0 0 6px; font-size:13px; color:#6b7280;">&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
                        <p style="margin:0; font-size:13px; color:#6b7280;">Questions? Contact us at devhub-community@outlook.com</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
