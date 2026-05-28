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
        a,
        h1,
        h2,
        h3 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        .btn {
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background-color: #4f46e5;
            color: #ffffff;
            padding: 14px 32px;
            font-size: 15px;
        }

        .btn-secondary {
            background-color: #ffffff;
            color: #4f46e5;
            padding: 14px 32px;
            font-size: 15px;
            border: 2px solid #4f46e5;
        }

        .feature-item {
            background-color: #f8faff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 12px;
        }

        .divider {
            background-color: #e5e7eb;
            height: 1px;
            margin: 24px 0;
        }

        @media only screen and (max-width: 620px) {
            .wrapper {
                width: 100% !important;
            }

            .content-padding {
                padding: 24px 20px !important;
            }

            .title {
                font-size: 28px !important;
            }

            .subtitle {
                font-size: 18px !important;
            }

            .feature-item {
                padding: 16px !important;
            }

            table.button-group {
                display: block !important;
                width: 100% !important;
            }

            table.button-group tr {
                display: block !important;
            }

            table.button-group td {
                display: block !important;
                width: 100% !important;
                padding-bottom: 12px !important;
            }

            .btn {
                width: 100% !important;
                box-sizing: border-box !important;
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
            <!-- Header/Logo Section -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%; background:linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding:0;">
                <tr>
                    <td align="center" style="padding:40px 20px;">
                        <h1 style="margin:0; font-size:28px; color:#ffffff; font-weight:700; letter-spacing:-0.5px;">✨ Welcome to {{ $appName }}!</h1>
                        <p style="margin:8px 0 0; font-size:16px; color:#dbeafe; font-weight:400;">Join thousands of developers sharing knowledge</p>
                    </td>
                </tr>
            </table>

            <!-- Main Content -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="wrapper" style="width:600px; max-width:600px; background:#ffffff; margin:20px auto;">
                <!-- Welcome Section -->
                <tr>
                    <td class="content-padding" style="padding:40px 34px;">
                        <p style="margin:0 0 8px; font-size:16px; color:#6b7280;">Hello 👋</p>
                        <h2 class="subtitle" style="margin:0 0 16px; font-size:24px; line-height:1.3; color:#111827; font-weight:700;">Welcome, {{ $user->name ?? 'Developer' }}!</h2>
                        <p style="margin:0 0 18px; font-size:15px; line-height:1.8; color:#4b5563;">
                            We're excited to have you join the <strong style="color:#4f46e5;">{{ $appName }}</strong> community. Your account is now active and ready to explore.
                        </p>
                        <p style="margin:0 0 24px; font-size:15px; line-height:1.8; color:#4b5563;">
                            Connect with fellow developers, share your knowledge, ask questions, and grow together. Let's build amazing things! 🚀
                        </p>
                    </td>
                </tr>

                <!-- CTA Buttons -->
                <tr>
                    <td style="padding:0 34px 28px;" align="center">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="button-group" style="width:100%; margin:0;">
                            <tr>
                                <td style="padding-right:12px; width:50%;" align="center">
                                    <a href="{{ $appUrl }}" class="btn btn-primary" style="display:inline-block; width:100%; box-sizing:border-box;">
                                        Start Exploring
                                    </a>
                                </td>
                                <td style="padding-left:12px; width:50%;" align="center">
                                    <a href="{{ $appUrl }}/profile" class="btn btn-secondary" style="display:inline-block; width:100%; box-sizing:border-box;">
                                        Edit Profile
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Features Section -->
                <tr>
                    <td style="padding:0 34px 28px;">
                        <h3 style="margin:0 0 20px; font-size:18px; color:#111827; font-weight:700;">What you can do:</h3>

                        <div class="feature-item" style="background-color:#f0f4ff; border-left:4px solid #4f46e5; padding-left:16px;">
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#111827;">
                                <strong>📝 Share Knowledge</strong><br>
                                <span style="color:#4b5563; font-size:13px;">Write posts and articles to share your expertise with the community.</span>
                            </p>
                        </div>

                        <div class="feature-item" style="background-color:#f0f4ff; border-left:4px solid #7c3aed; padding-left:16px;">
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#111827;">
                                <strong>🤝 Connect with Peers</strong><br>
                                <span style="color:#4b5563; font-size:13px;">Follow developers and stay updated with their latest insights.</span>
                            </p>
                        </div>

                        <div class="feature-item" style="background-color:#f0f4ff; border-left:4px solid #06b6d4; padding-left:16px;">
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#111827;">
                                <strong>❓ Ask Questions</strong><br>
                                <span style="color:#4b5563; font-size:13px;">Get solutions from experienced developers in the community.</span>
                            </p>
                        </div>

                        <div class="feature-item" style="background-color:#f0f4ff; border-left:4px solid #10b981; padding-left:16px;">
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#111827;">
                                <strong>🏆 Grow Your Profile</strong><br>
                                <span style="color:#4b5563; font-size:13px;">Build your reputation and showcase your skills to the world.</span>
                            </p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 34px 28px;">
                        <div style="background:#f8faff; border:1px solid #e5e7eb; border-radius:8px; padding:16px; text-align:left;">
                            <p style="margin:0 0 8px; font-size:13px; font-weight:600; color:#4f46e5;">💡 Pro Tip</p>
                            <p style="margin:0; font-size:13px; line-height:1.6; color:#4b5563;">
                                Complete your profile picture and bio to help others get to know you better. Users with complete profiles receive more engagement!
                            </p>
                        </div>
                    </td>
                </tr>

                <!-- Community Stats -->
                <tr>
                    <td style="padding:28px 34px; background-color:#f9fafb; border-top:1px solid #e5e7eb; border-bottom:1px solid #e5e7eb;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td align="center" style="padding:0 10px;">
                                    <p style="margin:0 0 4px; font-size:18px; font-weight:700; color:#4f46e5;">10K+</p>
                                    <p style="margin:0; font-size:12px; color:#6b7280;">Active Members</p>
                                </td>
                                <td align="center" style="padding:0 10px; border-left:1px solid #e5e7eb; border-right:1px solid #e5e7eb;">
                                    <p style="margin:0 0 4px; font-size:18px; font-weight:700; color:#7c3aed;">50K+</p>
                                    <p style="margin:0; font-size:12px; color:#6b7280;">Posts & Articles</p>
                                </td>
                                <td align="center" style="padding:0 10px;">
                                    <p style="margin:0 0 4px; font-size:18px; font-weight:700; color:#06b6d4;">24/7</p>
                                    <p style="margin:0; font-size:12px; color:#6b7280;">Community Support</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Questions/Support Section -->
                <tr>
                    <td style="padding:28px 34px;">
                        <h3 style="margin:0 0 12px; font-size:16px; color:#111827; font-weight:700;">Questions or Need Help?</h3>
                        <p style="margin:0 0 8px; font-size:14px; line-height:1.6; color:#4b5563;">
                            Check out our <a href="{{ $appUrl }}/help" style="color:#4f46e5; text-decoration:none; font-weight:600;">Help Center</a> or reach out to our support team at <a href="mailto:devhub-community@outlook.com" style="color:#4f46e5; text-decoration:none; font-weight:600;">devhub-community@outlook.com</a>
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:24px 34px; background-color:#f9fafb; border-top:1px solid #e5e7eb; text-align:center;">
                        <!-- Social Links -->
                        <div style="margin-bottom:16px;">
                            <a href="https://twitter.com" style="display:inline-block; margin:0 8px; text-decoration:none; color:#4f46e5;">
                                <span style="font-weight:600; font-size:12px;">Twitter</span>
                            </a>
                            <a href="https://github.com" style="display:inline-block; margin:0 8px; text-decoration:none; color:#4f46e5;">
                                <span style="font-weight:600; font-size:12px;">GitHub</span>
                            </a>
                            <a href="https://discord.com" style="display:inline-block; margin:0 8px; text-decoration:none; color:#4f46e5;">
                                <span style="font-weight:600; font-size:12px;">Discord</span>
                            </a>
                        </div>

                        <div style="border-top:1px solid #e5e7eb; padding-top:16px;">
                            <p style="margin:0 0 4px; font-size:12px; color:#6b7280;">
                                &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
                            </p>
                            <p style="margin:0; font-size:11px; color:#9ca3af;">
                                You received this email because you signed up for {{ $appName }}.
                                <a href="{{ $appUrl }}/unsubscribe" style="color:#4f46e5; text-decoration:none;">Manage preferences</a>
                            </p>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
