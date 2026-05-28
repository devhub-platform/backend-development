<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verified</title>
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
                        <h2 style="margin: 0; font-size: 16px; font-weight: normal; color: #222222;">Email Verified Successfully</h2>
                    </td>
                </tr>
                
                <!-- Content -->
                <tr>
                    <td style="padding: 30px 20px;">
                        
                        <!-- Greeting -->
                        <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.5; color: #444444;">
                            Hello,
                        </p>
                        
                        <!-- Main Message -->
                        <p style="margin: 0 0 15px 0; font-size: 14px; line-height: 1.6; color: #444444;">
                            Your email address has been verified successfully. You now have full access to all {{ $appName }} features and can start using your account.
                        </p>
                        
                        <!-- CTA Button -->
                        <p style="margin: 30px 0;">
                            <a href="{{ $appUrl }}" style="display: inline-block; padding: 10px 25px; background-color: #0066cc; color: #ffffff; text-decoration: none; font-size: 14px; border: 1px solid #0066cc;">
                                Go to Dashboard
                            </a>
                        </p>
                        
                        <!-- Next Steps -->
                        <p style="margin: 25px 0 15px 0; font-size: 14px; font-weight: bold; color: #222222;">
                            Next Steps:
                        </p>
                        
                        <ol style="margin: 10px 0 0 20px; padding: 0; font-size: 14px; line-height: 1.6; color: #444444;">
                            <li style="margin-bottom: 8px;">Complete your profile with a picture and bio</li>
                            <li style="margin-bottom: 8px;">Explore communities related to your interests</li>
                            <li style="margin-bottom: 8px;">Follow developers and creators you want to keep up with</li>
                            <li>Share your first post with the community</li>
                        </ol>
                        
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
                            &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
                        </p>
                        <p style="margin: 0;">
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
