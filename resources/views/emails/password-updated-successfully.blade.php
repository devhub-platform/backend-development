<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Updated Successfully</title>
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
                        <h2 style="margin: 0; font-size: 16px; font-weight: normal; color: #222222;">Password Updated Successfully</h2>
                    </td>
                </tr>
                
                <!-- Content -->
                <tr>
                    <td style="padding: 30px 20px;">
                        
                        <!-- Greeting -->
                        <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.5; color: #444444;">
                            Hello {{ $user->name }},
                        </p>
                        
                        <!-- Main Message -->
                        <p style="margin: 0 0 15px 0; font-size: 14px; line-height: 1.6; color: #444444;">
                            This email confirms that your password was just changed. If you did not make this change, please secure your account immediately.
                        </p>
                        
                        <!-- Security Note -->
                        <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #444444; font-weight: bold;">
                            Your account is secure. Your new password is now active.
                        </p>
                        
                        <!-- Action Section -->
                        <p style="margin: 20px 0 15px 0; font-size: 14px; font-weight: bold; color: #222222;">
                            Didn't make this change?
                        </p>
                        
                        <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #444444;">
                            If you did not authorize this password change, please reset your password immediately:
                        </p>
                        
                        <!-- CTA Button -->
                        <p style="margin: 25px 0;">
                            <a href="{{ $appUrl }}/reset-password" style="display: inline-block; padding: 10px 25px; background-color: #cc0000; color: #ffffff; text-decoration: none; font-size: 14px; border: 1px solid #cc0000;">
                                Reset Password Now
                            </a>
                        </p>
                        
                        <!-- Tips Section -->
                        <p style="margin: 25px 0 10px 0; font-size: 14px; font-weight: bold; color: #222222;">
                            Tips for strong passwords:
                        </p>
                        
                        <ul style="margin: 10px 0 0 20px; padding: 0; font-size: 14px; line-height: 1.8; color: #444444;">
                            <li style="margin-bottom: 8px;">Use a mix of uppercase, lowercase, numbers, and symbols</li>
                            <li style="margin-bottom: 8px;">Avoid using personal information or common words</li>
                            <li>Never share your password with anyone</li>
                        </ul>
                        
                    </td>
                </tr>
                
                <!-- Security Settings Section -->
                <tr>
                    <td style="padding: 20px; background-color: #f5f5f5; border-top: 1px solid #dddddd;">
                        <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: bold; color: #222222;">
                            Account Security
                        </p>
                        <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #444444;">
                            For your security, we recommend enabling two-factor authentication. Visit your security settings to enable this feature.
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
                            &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
                        </p>
                        <p style="margin: 0; font-size: 11px; color: #999999;">
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
