<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') }} | OTP</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:'Segoe UI',Arial,sans-serif;color:#1a1a2e;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:40px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);">
                <!-- Header -->
                <tr>
                    <td style="background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);padding:32px 24px;text-align:center;">
                        <h1 style="margin:0;font-size:24px;font-weight:700;color:#ffffff;">{{ config('app.name') }}</h1>
                    </td>
                </tr>
                <!-- Body -->
                <tr>
                    <td style="padding:32px 24px;">
                        <p style="margin:0 0 8px;font-size:18px;font-weight:600;color:#1a1a2e;">Verification Code</p>
                        <p style="margin:0 0 24px;font-size:14px;color:#64748b;">Hi {{ $recipientName ?? 'there' }}, use the code below to verify your identity.</p>

                        <!-- OTP Box -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td align="center" style="padding:24px;background:#f8fafc;border-radius:12px;border:2px dashed #cbd5e1;">
                                    <p style="margin:0;font-size:36px;font-weight:700;letter-spacing:8px;color:#2563eb;font-family:monospace;">{{ $otp }}</p>
                                </td>
                            </tr>
                        </table>

                        <!-- Expiry Notice -->
                        <p style="margin:16px 0 0;font-size:13px;color:#ef4444;text-align:center;">
                            ⏱ Expires in <strong>10 minutes</strong>
                        </p>

                        <!-- Security Notice -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;">
                            <tr>
                                <td style="padding:16px;background:#fefce8;border-radius:8px;border-left:4px solid #eab308;">
                                    <p style="margin:0;font-size:13px;color:#854d0e;">
                                        🔒 <strong>Security tip:</strong> Never share this code with anyone. We will never ask for it.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:24px 0 0;font-size:14px;color:#475569;">
                            If you didn't request this code, you can safely ignore this email.
                        </p>
                        <p style="margin:16px 0 0;font-size:14px;color:#1a1a2e;">
                            Best regards,<br><strong>{{ config('app.name') }} Team</strong>
                        </p>
                    </td>
                </tr>
                <!-- Footer -->
                <tr>
                    <td style="padding:20px 24px;background:#f8fafc;text-align:center;border-top:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:12px;color:#94a3b8;">© {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
