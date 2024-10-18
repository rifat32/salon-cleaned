<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                font-family: Arial, sans-serif;
                color: #333;
                background-color: #f5f5f5;
                padding: 40px;
            }

            h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }

            p {
                margin-bottom: 20px;
                font-size: 16px;
                line-height: 1.5;
            }

            .otp {
                font-size: 20px;
                font-weight: bold;
                background-color: #e0e0e0;
                padding: 10px;
                text-align: center;
                border-radius: 5px;
                width: 150px;
                margin: 0 auto;
            }
        </style>
    </head>
    <body>
        <h1>Dear {{ $user->first_name }},</h1>
        <p>Thank you for registering for our service. To complete your registration, please use the following One-Time Password (OTP) to verify your email address:</p>
        <p class="otp">{{ $user->email_verify_token }}</p>
        <p>Enter this OTP in the verification form to activate your account. The OTP is valid for a limited time.</p>
        <p>If you did not sign up for our service, please ignore this email. Your email address will not be used for any other purpose.</p>
        {{-- <p>If you have any questions or concerns, please contact us at {{ $contactEmail }}. We're here to help!</p> --}}
        <p>Best regards,</p>
        <p>{{ env('APP_NAME') }}</p>
    </body>
</html>
