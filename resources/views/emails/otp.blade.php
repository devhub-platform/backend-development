<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Your OTP Code</title>
    <style>
        body,
        table,
        td,
        p,
        a {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        @media only screen and (max-width: 620px) {
            .wrapper {
                width: 100% !important;
            }

            .content-padding {
                padding: 24px 20px !important;
            }

            .otp-code {
                font-size: 36px !important;
            }
        }
    </style>
</head>
<body>
@php
    $appName = config('app.name');
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f5f5f5; padding:20px 0;">
    <tr>
        <td align="center" style="padding:0;">
            <!-- Header -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%; background:linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding:0;">
                <tr>
                    <td align="center" style="padding:30px 20px;">
                        <h1 style="margin:0; font-size:24px; color:#ffffff; font-weight:700;">🔐 Security Code</h1>
                    </td>
                </tr>
            </table>

            <!-- Main Content -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="wrapper" style="width:600px; max-width:600px; background:#ffffff; margin:20px auto;">
                <tr>
                    <td class="content-padding" style="padding:40px 34px; text-align:center;">
                        <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#4b5563;">
                            Your One-Time Password (OTP) for {{ $appName }} is:
                        </p>

                        <div style="background:linear-gradient(135deg, #f0f4ff 0%, #f8faff 100%); border:2px solid #4f46e5; border-radius:12px; padding:24px; margin:24px 0;">
                            <p class="otp-code" style="margin:0; font-size:48px; font-weight:700; letter-spacing:8px; font-family:monospace; color:#4f46e5; font-variant-numeric:tabular-nums;">
                                {{ $user->otp }}
                            </p>
                        </div>

                        <p style="margin:0 0 6px; font-size:13px; color:#6b7280;">
                            Valid for <strong style="color:#111827;">10 minutes</strong>
                        </p>
                        <p style="margin:0 0 24px; font-size:13px; color:#dc2626; font-weight:600;">
                            ⚠️ Do not share this code with anyone
                        </p>

                        <div style="background:#fef3c7; border-left:4px solid #f59e0b; border-radius:6px; padding:16px; text-align:left; margin-top:24px;">
                            <p style="margin:0; font-size:13px; line-height:1.6; color:#78350f;">
                                <strong style="color:#92400e;">💡 Tip:</strong> If you didn't request this code, you can ignore this email. Your account will remain secure.
                            </p>
                        </div>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:24px 34px; background-color:#f9fafb; border-top:1px solid #e5e7eb; text-align:center;">
                        <p style="margin:0 0 4px; font-size:12px; color:#6b7280;">
                            &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
                        </p>
                        <p style="margin:0; font-size:11px; color:#9ca3af;">
                            Questions? Contact us at devhub-community@outlook.com
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
