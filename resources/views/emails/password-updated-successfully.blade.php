<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Password Updated Successfully</title>
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
        }
    </style>
</head>
<body>
@php
    $appName = config('app.name');
    $appUrl = rtrim(config('app.url') ?? url('/'), '/');
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f5f5f5; padding:20px 0;">
    <tr>
        <td align="center" style="padding:0;">
            <!-- Header -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%; background:linear-gradient(135deg, #10b981 0%, #059669 100%); padding:0;">
                <tr>
                    <td align="center" style="padding:30px 20px;">
                        <h1 style="margin:0; font-size:24px; color:#ffffff; font-weight:700;">✅ Password Updated</h1>
                    </td>
                </tr>
            </table>

            <!-- Main Content -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="wrapper" style="width:600px; max-width:600px; background:#ffffff; margin:20px auto;">
                <tr>
                    <td class="content-padding" style="padding:40px 34px;">
                        <p style="margin:0 0 8px; font-size:16px; color:#6b7280;">Hi {{ $user->name }},</p>
                        <h2 style="margin:0 0 16px; font-size:22px; line-height:1.3; color:#111827; font-weight:700;">Your password has been successfully updated</h2>
                        <p style="margin:0 0 24px; font-size:15px; line-height:1.8; color:#4b5563;">
                            This email confirms that your password was just changed. If you did not make this change, please secure your account immediately.
                        </p>

                        <div style="background:#d1fae5; border-left:4px solid #10b981; border-radius:6px; padding:16px; margin:24px 0;">
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#047857;">
                                <strong>✓ Your account is secure</strong><br>
                                <span style="font-size:13px; color:#065f46;">Your new password is now active. Please make sure to keep it safe and never share it with anyone.</span>
                            </p>
                        </div>

                        <h3 style="margin:24px 0 16px; font-size:16px; color:#111827; font-weight:700;">Didn't make this change?</h3>
                        <p style="margin:0 0 16px; font-size:15px; line-height:1.8; color:#4b5563;">
                            If you did not authorize this password change, please reset your password immediately:
                        </p>
                        <div style="text-align:center; margin:20px 0;">
                            <a href="{{ $appUrl }}/reset-password" style="display:inline-block; padding:12px 28px; background-color:#dc2626; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:600; font-size:15px;">
                                Reset Password Now
                            </a>
                        </div>

                        <div style="background:#fef3c7; border-left:4px solid #f59e0b; border-radius:6px; padding:16px; margin-top:24px;">
                            <p style="margin:0; font-size:13px; line-height:1.6; color:#78350f;">
                                <strong style="color:#92400e;">💡 Tips for strong passwords:</strong><br>
                                <span style="font-size:12px;">Use a mix of uppercase, lowercase, numbers, and symbols. Avoid using personal information or common words.</span>
                            </p>
                        </div>
                    </td>
                </tr>

                <!-- Quick Info -->
                <tr>
                    <td style="padding:24px 34px; background-color:#f9fafb; border-top:1px solid #e5e7eb;">
                        <p style="margin:0 0 12px; font-size:13px; font-weight:600; color:#111827;">🔒 Account Security</p>
                        <p style="margin:0; font-size:13px; line-height:1.6; color:#4b5563;">
                            For your security, we recommend enabling two-factor authentication. <a href="{{ $appUrl }}/security" style="color:#4f46e5; text-decoration:none; font-weight:600;">Manage security settings</a>
                        </p>
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
