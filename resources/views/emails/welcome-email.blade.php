<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body>
@php
    $appName = config('app.name');
    $appUrl = rtrim(config('app.url') ?? url('/'), '/');
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif;">
    <tr>
        <td align="center" style="padding: 20px;">

            <!-- Main Container -->
            <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px;">

                <!-- Header -->
                <tr>
                    <td style="padding: 20px; text-align: left; border-bottom: 1px solid #dddddd;">
                        <h2 style="margin: 0; font-size: 16px; font-weight: normal; color: #222222;">Welcome to {{ $appName }}</h2>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding: 30px 20px;">

                        <!-- Greeting -->
                        <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.5; color: #444444;">
                            Hello {{ $user->name ?? 'Developer' }},
                        </p>

                        <!-- Main Message -->
                        <p style="margin: 0 0 15px 0; font-size: 14px; line-height: 1.6; color: #444444;">
                            Welcome to {{ $appName }}! We're excited to have you join our community. Your account is now active and ready to explore.
                        </p>

                        <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #444444;">
                            Connect with fellow developers, share your knowledge, ask questions, and grow together. Let's build amazing things!
                        </p>

                        <!-- CTA Button -->
                        <p style="margin: 30px 0;">
                            <a href="{{ $appUrl }}" style="display: inline-block; padding: 10px 25px; background-color: #0066cc; color: #ffffff; text-decoration: none; font-size: 14px; border: 1px solid #0066cc;">
                                Start Exploring
                            </a>
                        </p>

                        <!-- Features Section -->
                        <p style="margin: 25px 0 15px 0; font-size: 14px; font-weight: bold; color: #222222;">
                            What you can do:
                        </p>

                        <ul style="margin: 10px 0 0 20px; padding: 0; font-size: 14px; line-height: 1.8; color: #444444;">
                            <li style="margin-bottom: 8px;">Share knowledge - Write posts and articles to help the community</li>
                            <li style="margin-bottom: 8px;">Connect with peers - Follow developers and stay updated</li>
                            <li style="margin-bottom: 8px;">Ask questions - Get solutions from experienced developers</li>
                            <li>Grow your profile - Build your reputation and showcase your skills</li>
                        </ul>

                        <!-- Community Stats -->
                        <p style="margin: 25px 0 15px 0; font-size: 12px; color: #666666; text-align: center;">
                            Join 10,000+ developers | 50,000+ Posts and Articles | 24/7 Community Support
                        </p>

                    </td>
                </tr>

                <!-- Divider -->
                <tr>
                    <td style="height: 1px; background-color: #dddddd;"></td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding: 20px; font-size: 12px; line-height: 1.5; color: #666666; text-align: center;">
                        <p style="margin: 0 0 8px 0;">
                            Questions? Check out our Help Center or contact us at devhub-community@outlook.com
                        </p>
                        <p style="margin: 0 0 8px 0;">
                            &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
                        </p>
                        <p style="margin: 0; font-size: 11px; color: #999999;">
                            You received this email because you signed up for {{ $appName }}.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
