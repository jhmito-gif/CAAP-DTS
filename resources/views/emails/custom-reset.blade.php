<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f9fafb; padding: 40px;">
    <p align="center">        
        <img src="https://www.caap.gov.ph/wp-content/uploads/2023/10/caap_logo-300x220.png" class="banner" alt="Banner" width="105px"><img src="https://www.caap.gov.ph/wp-content/uploads/2023/09/Bagong-Pilipinas-logo.png" class="banner" alt="Banner" width="105px">
    </p>
    <h2 align="center">Civil Aviation Authurity of the Philippines</h2>
    

    <div style="max-width: 600px; margin: auto; background: #fff; padding: 30px; border-radius: 10px;">
        <h2 style="color: #2563eb;"  align="center">CAAP Data Tracking Password Reset</h2>
        <br><br>

        <p>Hello {{ $user->name }},</p>
        <p>You requested a password reset. Click the button below to create a new password:</p>
        <br><br>
        <p align="center">
            <a href="{{ $url }}" style="background-color: #101010; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                Reset Password
            </a>
        </p>
        <br><br>
        <p align="center">If you didn’t request this, please ignore this email.</p>
        <p align="center" style="color: gray; font-size: 12px;">&copy; {{ date('Y') }} Civil Aviation Authority of the Philippines</p>
    </div>
</body>
</html>
