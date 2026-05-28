<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Email Verified Successfully</title>
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
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%; background:linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding:0;">
                <tr>
                    <td align="center" style="padding:30px 20px;">
                        <h1 style="margin:0; font-size:24px; color:#ffffff; font-weight:700;">✅ Email Verified</h1>
                    </td>
                </tr>
            </table>

            <!-- Main Content -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="wrapper" style="width:600px; max-width:600px; background:#ffffff; margin:20px auto;">
                <tr>
                    <td class="content-padding" style="padding:40px 34px; text-align:center;">
                        <div style="background:#dbeafe; border-radius:50%; width:80px; height:80px; margin:0 auto 24px; display:flex; align-items:center; justify-content:center;">
                            <span style="font-size:40px;">✓</span>
                        </div>

                        <h2 style="margin:0 0 12px; font-size:22px; line-height:1.3; color:#111827; font-weight:700;">Your email has been verified!</h2>
                        <p style="margin:0 0 24px; font-size:15px; line-height:1.8; color:#4b5563;">
                            Congratulations! Your email address has been successfully verified. You now have full access to all {{ $appName }} features.
                        </p>

                        <div style="background:#f0f4ff; border:2px dashed #4f46e5; border-radius:8px; padding:20px; margin:24px 0;">
                            <p style="margin:0 0 8px; font-size:13px; font-weight:600; color:#4f46e5;">🎉 You're all set!</p>
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#4b5563;">
                                Your account is now fully activated. Explore communities, share your knowledge, and connect with other developers.
                            </p>
                        </div>

                        <div style="margin:24px 0;">
                            <a href="{{ $appUrl }}" style="display:inline-block; padding:14px 32px; background-color:#4f46e5; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:600; font-size:15px;">
                                Go to Dashboard
                            </a>
                        </div>

                        <hr style="border:none; border-top:1px solid #e5e7eb; margin:24px 0;">

                        <h3 style="margin:20px 0 12px; font-size:15px; color:#111827; font-weight:700; text-align:left;">What's next?</h3>
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0;">
                            <tr>
                                <td style="padding:10px 0; text-align:left;">
                                    <p style="margin:0; font-size:14px; color:#4b5563;">
                                        <strong style="color:#111827;">1. Complete your profile</strong><br>
                                        <span style="font-size:13px; color:#6b7280;">Add a profile picture and bio to help others know you better</span>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0; text-align:left;">
                                    <p style="margin:0; font-size:14px; color:#4b5563;">
                                        <strong style="color:#111827;">2. Explore communities</strong><br>
                                        <span style="font-size:13px; color:#6b7280;">Find communities related to your interests and follow them</span>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0; text-align:left;">
                                    <p style="margin:0; font-size:14px; color:#4b5563;">
                                        <strong style="color:#111827;">3. Follow developers</strong><br>
                                        <span style="font-size:13px; color:#6b7280;">Connect with other developers to stay updated with their posts</span>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0; text-align:left;">
                                    <p style="margin:0; font-size:14px; color:#4b5563;">
                                        <strong style="color:#111827;">4. Share your first post</strong><br>
                                        <span style="font-size:13px; color:#6b7280;">Share your knowledge, experiences, or ask questions with the community</span>
                                    </p>
                                </td>
                            </tr>
                        </table>
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
