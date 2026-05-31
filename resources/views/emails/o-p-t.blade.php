<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | OTP</title>
</head>

<body>

    <table width="100%" cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif;">
        <tr>
            <td align="center" style="padding: 20px;">

                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px;">

                    <!-- Header -->
                    <tr>
                        <td style="padding: 20px; text-align: left; border-bottom: 1px solid #dddddd;">
                            <h2 style="margin: 0; font-size: 16px; font-weight: normal; color: #222222;">Verification
                                Code</h2>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px 20px;">

                            <!-- Greeting -->
                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.5; color: #444444;">
                                Hello {{ $recipientName ?? 'there' }},
                            </p>

                            <!-- Message -->
                            <p style="margin: 0 0 30px 0; font-size: 14px; line-height: 1.6; color: #444444;">
                                Use the code below to verify your identity:
                            </p>

                            <!-- OTP Code -->
                            <p
                                style="margin: 0 0 30px 0; padding: 20px; background-color: #f0f0f0; border: 1px solid #cccccc; font-size: 32px; font-weight: bold; letter-spacing: 4px; font-family: monospace; color: #000000; text-align: center;">
                                {{ $otp }}
                            </p>

                            <!-- Expiry Info -->
                            <p
                                style="margin: 0 0 20px 0; font-size: 13px; color: #cc0000; text-align: center; font-weight: bold;">
                                Expires in 10 minutes
                            </p>

                            <!-- Security Notice -->
                            <p style="margin: 0 0 20px 0; font-size: 13px; color: #666666;">
                                Never share this code with anyone. We will never ask for it.
                            </p>

                            <!-- Additional Note -->
                            <p style="margin: 0; font-size: 13px; color: #666666;">
                                If you didn't request this code, you can safely ignore this email.
                            </p>

                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="height: 1px; background-color: #dddddd;"></td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="padding: 20px; font-size: 12px; line-height: 1.5; color: #666666; text-align: center;">
                            <p style="margin: 0 0 8px 0;">
                                &copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #999999;">
                                For support, contact us at devhub-community@outlook.com
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>