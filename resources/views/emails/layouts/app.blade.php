<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? config('app.name') }}</title>
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

        .btn:hover {
            opacity: 0.9;
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
    @yield('styles')
</head>
<body>
@php
    $appName = config('app.name');
    $appUrl = rtrim(config('app.url') ?? url('/'), '/');
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f5f5f5; padding:20px 0;">
    <tr>
        <td align="center" style="padding:0;">
            <!-- Logo/Header -->
            @yield('header')

            <!-- Main Content -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="wrapper" style="width:600px; max-width:600px; background:#ffffff; margin:20px auto;">
                @yield('content')

                <!-- Footer -->
                <tr>
                    <td style="padding:24px 34px; background-color:#f9fafb; border-top:1px solid #e5e7eb; text-align:center;">
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
                                You received this email because you have an account on {{ $appName }}.
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
