<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Verify Alternative Email</title>
</head>
<body>
<h2>Verify Your Alternative Email Address</h2>
@if($userName)
    <p>Hi {{ $userName }},</p>
@endif
<p>You have requested to add this email as your alternative email address.</p>
<p>Your verification code is: <strong>{{ $otp }}</strong></p>
<p>This code is valid for the next 10 minutes.</p>
<p>If you did not request this, please ignore this email or contact support if you have concerns.</p>
<br>
<p>Thank you! devhub team</p>
</body>
</html>
